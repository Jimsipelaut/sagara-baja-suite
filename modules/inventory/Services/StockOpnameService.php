<?php
namespace SagaraBaja\Modules\Inventory\Services;

use SagaraBaja\Core\Audit\AuditLogger;
use SagaraBaja\Core\Document\NumberingService;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Layanan Stock Opname (Penghitungan Fisik & Penyesuaian Selisih Stok).
 */
class StockOpnameService {
    private \wpdb $wpdb;
    private string $table_opnames;
    private string $table_items;
    private StockService $stock_service;
    private NumberingService $numbering_service;

    public function __construct(
        ?\wpdb $wpdb = null,
        ?StockService $stock_service = null,
        ?NumberingService $numbering_service = null
    ) {
        global $wpdb;
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $prefix = $this->wpdb->prefix . SB_TABLE_PREFIX;
        $this->table_opnames    = $prefix . 'stock_opnames';
        $this->table_items      = $prefix . 'stock_opname_items';
        $this->stock_service    = $stock_service ?? new StockService( $this->wpdb );
        $this->numbering_service = $numbering_service ?? new NumberingService( $this->wpdb );
    }

    /**
     * Buat draf stock opname baru.
     * $items: [ ['product_id' => 1, 'system_qty' => 10, 'physical_qty' => 8, 'notes' => 'patah'], ... ]
     */
    public function create_draft( int $warehouse_id, array $items, ?string $notes = null, ?int $user_id = null ): int {
        if ( empty( $items ) ) {
            throw new \InvalidArgumentException( "Daftar barang opname tidak boleh kosong." );
        }

        $opname_number = $this->numbering_service->generate( 'stock_opname' );
        $user_id = $user_id ?? get_current_user_id();

        $this->wpdb->query( "START TRANSACTION" );

        try {
            $this->wpdb->insert(
                $this->table_opnames,
                [
                    'opname_number' => $opname_number,
                    'warehouse_id'  => $warehouse_id,
                    'status'        => 'draft',
                    'notes'         => $notes,
                    'created_by'    => $user_id,
                    'created_at'    => current_time( 'mysql', true ),
                ],
                [ '%s', '%d', '%s', '%s', '%d', '%s' ]
            );

            $opname_id = (int) $this->wpdb->insert_id;

            foreach ( $items as $item ) {
                $product_id   = (int) $item['product_id'];
                $system_qty   = (float) $item['system_qty'];
                $physical_qty = (float) $item['physical_qty'];
                $diff_qty     = round( $physical_qty - $system_qty, 4 );

                $this->wpdb->insert(
                    $this->table_items,
                    [
                        'opname_id'    => $opname_id,
                        'product_id'   => $product_id,
                        'system_qty'   => $system_qty,
                        'physical_qty' => $physical_qty,
                        'diff_qty'     => $diff_qty,
                        'notes'        => $item['notes'] ?? null,
                    ],
                    [ '%d', '%d', '%f', '%f', '%f', '%s' ]
                );
            }

            AuditLogger::log(
                'create_opname_draft',
                'stock_opname',
                $opname_number,
                null,
                [ 'items_count' => count( $items ), 'warehouse_id' => $warehouse_id ]
            );

            $this->wpdb->query( "COMMIT" );

            return $opname_id;
        } catch ( \Throwable $e ) {
            $this->wpdb->query( "ROLLBACK" );
            throw $e;
        }
    }

    /**
     * Setujui opname dan posting penyesuaian selisih ke kartu stok.
     */
    public function approve( int $opname_id, ?int $approver_id = null ): bool {
        $opname = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT * FROM `{$this->table_opnames}` WHERE id = %d",
            $opname_id
        ), \ARRAY_A );

        if ( ! $opname ) {
            throw new \InvalidArgumentException( sprintf( "Opname #%d tidak ditemukan.", $opname_id ) );
        }

        if ( $opname['status'] !== 'draft' ) {
            throw new \LogicException( sprintf( "Opname #%d sudah berstatus '%s', tidak dapat disetujui lagi.", $opname_id, $opname['status'] ) );
        }

        $items = $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT * FROM `{$this->table_items}` WHERE opname_id = %d",
            $opname_id
        ), \ARRAY_A );

        $approver_id = $approver_id ?? get_current_user_id();

        $this->wpdb->query( "START TRANSACTION" );

        try {
            foreach ( $items as $item ) {
                $diff = (float) $item['diff_qty'];
                $product_id = (int) $item['product_id'];
                $warehouse_id = (int) $opname['warehouse_id'];

                if ( $diff > 0 ) {
                    // Selisih fisik lebih banyak -> tambah stok
                    $this->stock_service->add_stock(
                        $product_id,
                        $warehouse_id,
                        $diff,
                        'opname_adjustment',
                        $opname['opname_number'],
                        null,
                        0,
                        "Penyesuaian lebih hasil opname " . $opname['opname_number'],
                        $approver_id
                    );
                } elseif ( $diff < 0 ) {
                    // Selisih fisik kurang -> kurangi stok
                    $this->stock_service->deduct_stock(
                        $product_id,
                        $warehouse_id,
                        abs( $diff ),
                        'opname_adjustment',
                        $opname['opname_number'],
                        null,
                        0,
                        "Penyesuaian kurang hasil opname " . $opname['opname_number'],
                        $approver_id,
                        true // penyesuaian opname diizinkan menyamakan fisik
                    );
                }
            }

            // Update status opname menjadi approved
            $this->wpdb->update(
                $this->table_opnames,
                [
                    'status'      => 'approved',
                    'approved_by' => $approver_id,
                    'approved_at' => current_time( 'mysql', true ),
                ],
                [ 'id' => $opname_id ],
                [ '%s', '%d', '%s' ],
                [ '%d' ]
            );

            AuditLogger::log(
                'approve_opname',
                'stock_opname',
                $opname['opname_number'],
                [ 'status' => 'draft' ],
                [ 'status' => 'approved', 'approved_by' => $approver_id ]
            );

            $this->wpdb->query( "COMMIT" );

            return true;
        } catch ( \Throwable $e ) {
            $this->wpdb->query( "ROLLBACK" );
            throw $e;
        }
    }
}
