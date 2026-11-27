<?php
namespace SagaraBaja\Core\Document;

use SagaraBaja\Core\Database\Transaction;
use SagaraBaja\Core\Audit\AuditLogger;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Layanan penomoran dokumen yang aman terhadap race condition dan bebas dari celah nomor ganda/terlewat.
 */
class NumberingService {
    private \wpdb $wpdb;
    private string $table_name;

    public function __construct( ?\wpdb $wpdb = null ) {
        global $wpdb;
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $this->table_name = $this->wpdb->prefix . SB_TABLE_PREFIX . 'document_sequences';
    }

    /**
     * Dapatkan nomor urut dokumen berikutnya secara atomik dengan FOR UPDATE lock.
     *
     * @param string $doc_type Tipe dokumen (misal: 'invoice_resmi', 'nota_non_resmi', 'purchase_order')
     * @param \DateTimeInterface|null $date Tanggal acuan penomoran (default: saat ini)
     * @return string Nomor dokumen lengkap terformat (contoh: 'INV-RES/2026/00001')
     * @throws \RuntimeException Jika transaksi database gagal atau doc_type tidak valid.
     */
    public function generate( string $doc_type, ?\DateTimeInterface $date = null ): string {
        $now = $date ?? new \DateTimeImmutable( 'now', new \DateTimeZone( 'Asia/Jakarta' ) );

        // Buka transaksi database untuk isolasi ketat
        Transaction::begin( $this->wpdb );

        try {
            // Row-lock record sequence dengan FOR UPDATE
            $sql = $this->wpdb->prepare(
                "SELECT id, prefix, current_number, pad_length, reset_period, last_period 
                 FROM `{$this->table_name}` 
                 WHERE doc_type = %s 
                 FOR UPDATE",
                $doc_type
            );

            $row = $this->wpdb->get_row( $sql, \ARRAY_A );

            if ( ! $row ) {
                throw new \RuntimeException( sprintf( "Tipe dokumen '%s' tidak terdaftar dalam sequence.", esc_html( $doc_type ) ) );
            }

            $current_number = (int) $row['current_number'];
            $pad_length     = max( 1, (int) $row['pad_length'] );
            $reset_period   = $row['reset_period'];
            $last_period    = $row['last_period'];

            // Tentukan periode saat ini berdasarkan konfigurasi
            $current_period = match ( $reset_period ) {
                'monthly' => $now->format( 'Ym' ),
                'yearly'  => $now->format( 'Y' ),
                default   => null,
            };

            // Reset nomor jika masuk periode baru
            if ( $current_period !== null && $current_period !== $last_period ) {
                $current_number = 0;
                $last_period    = $current_period;
            }

            $next_number = $current_number + 1;
            $updated_at  = $now->format( 'Y-m-d H:i:s' );

            // Perbarui nomor sequence di tabel
            $updated = $this->wpdb->update(
                $this->table_name,
                [
                    'current_number' => $next_number,
                    'last_period'    => $last_period,
                    'updated_at'     => $updated_at,
                ],
                [ 'doc_type' => $doc_type ],
                [ '%d', '%s', '%s' ],
                [ '%s' ]
            );

            if ( $updated === false ) {
                throw new \RuntimeException( "Gagal memperbarui urutan nomor dokumen." );
            }

            // Commit transaksi
            Transaction::commit( $this->wpdb );

            // Format nomor dokumen
            $period_slug = $current_period ? $current_period . '/' : '';
            $number_slug = str_pad( (string) $next_number, $pad_length, '0', STR_PAD_LEFT );
            $formatted   = $row['prefix'] . $period_slug . $number_slug;

            // Catat audit log untuk setiap penerbitan nomor
            AuditLogger::log(
                'generate_sequence',
                'document_sequence',
                $doc_type,
                [ 'previous_number' => $current_number ],
                [ 'generated_number' => $next_number, 'formatted' => $formatted ]
            );

            return $formatted;

        } catch ( \Throwable $e ) {
            Transaction::rollback( $this->wpdb );
            throw new \RuntimeException( "Gagal menerbitkan nomor dokumen: " . $e->getMessage(), 0, $e );
        }
    }

    /**
     * Intip nomor terakhir tanpa menaikkan urutan (Read-only).
     */
    public function peek( string $doc_type ): ?int {
        $sql = $this->wpdb->prepare(
            "SELECT current_number FROM `{$this->table_name}` WHERE doc_type = %s",
            $doc_type
        );
        $val = $this->wpdb->get_var( $sql );
        return $val !== null ? (int) $val : null;
    }
}
