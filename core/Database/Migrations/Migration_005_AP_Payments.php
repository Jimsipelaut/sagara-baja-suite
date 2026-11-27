<?php
namespace SagaraBaja\Core\Database\Migrations;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * Migration_005_AP_Payments
 *
 * Membuat tabel untuk pembayaran hutang usaha (Sprint 4, slice AP payment):
 *   sb_supplier_payments — satu baris per pembayaran atas satu PO.
 *
 * Desain:
 * - Semua nilai uang: integer Rupiah. Tidak ada float untuk uang (SOUL 4.2).
 * - Metode bayar hanya 'tunai' dan 'transfer' (lingkup slice ini).
 * - Satu pembayaran selalu terikat ke satu PO (purchase_order_id) dan satu
 *   supplier (supplier_id, didenormalisasi dari PO agar daftar hutang per
 *   supplier bisa dibaca tanpa join berantai).
 * - Nomor pembayaran memakai sequence baru 'supplier_payment' (BYR-SB/),
 *   urutan sendiri — tidak berbagi dengan PO (PO-SB/) atau tanda terima
 *   (GR-SB/) sesuai SOUL 4.4.
 * - Pembayaran TIDAK membuat jurnal dan TIDAK menggerakkan kas/bank di slice
 *   ini. Yang dicatat: baris pembayaran + paid_amount di sb_purchase_orders.
 *   Jurnal otomatis + kas/bank adalah sisa Sprint 4 dan tetap terbuka.
 * - PO tidak punya status 'lunas' di state machine-nya (draft → disetujui →
 *   diterima_sebagian → diterima / batal). Lunas diturunkan dari
 *   paid_amount == grand_total, bukan dari perubahan status PO.
 */
class Migration_005_AP_Payments {
	private \wpdb $wpdb;
	private string $prefix;

	public function __construct( \wpdb $wpdb, string $prefix ) {
		$this->wpdb   = $wpdb;
		$this->prefix = $prefix;
	}

	public function up(): void {
		$charset = $this->wpdb->get_charset_collate();
		$p       = $this->prefix;

		$sql = "CREATE TABLE IF NOT EXISTS `{$p}supplier_payments` (
			`id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`supplier_id`        BIGINT UNSIGNED NOT NULL,
			`purchase_order_id`  BIGINT UNSIGNED NOT NULL,
			`payment_number`     VARCHAR(30)     NOT NULL,
			`payment_date`       DATETIME        NOT NULL,
			`method`             ENUM('tunai','transfer') NOT NULL DEFAULT 'tunai',
			`amount`             BIGINT          NOT NULL,
			`reference`          VARCHAR(100)    DEFAULT NULL,
			`notes`              TEXT            DEFAULT NULL,
			`created_by`         BIGINT UNSIGNED NOT NULL,
			`created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uq_supplier_payments_number` (`payment_number`),
			KEY `idx_supplier_payments_po` (`purchase_order_id`),
			KEY `idx_supplier_payments_supplier` (`supplier_id`),
			KEY `idx_supplier_payments_date` (`payment_date`)
		) {$charset};";

		$this->wpdb->query( $sql );

		// Seed sequence penomoran pembayaran supplier (BYR-SB/), jika belum ada.
		$table_seq = $this->prefix . 'document_sequences';
		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT id FROM `{$table_seq}` WHERE doc_type = %s", 'supplier_payment' )
		);
		if ( ! $exists ) {
			$this->wpdb->insert(
				$table_seq,
				[
					'doc_type'       => 'supplier_payment',
					'prefix'         => 'BYR-SB/',
					'current_number' => 0,
					'pad_length'     => 5,
					'reset_period'   => 'yearly',
					'last_period'    => null,
					'updated_at'     => current_time( 'mysql', true ),
				],
				[ '%s', '%s', '%d', '%d', '%s', '%s', '%s' ]
			);
		}
	}

	public function down(): void {
		$p = $this->prefix;
		$this->wpdb->query( "DROP TABLE IF EXISTS `{$p}supplier_payments`" );
	}
}
