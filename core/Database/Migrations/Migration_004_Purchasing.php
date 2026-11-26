<?php
namespace SagaraBaja\Core\Database\Migrations;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * Migration_004_Purchasing
 *
 * Membuat tabel untuk modul Purchasing Sprint 3:
 *   sb_purchase_orders      — header Purchase Order (PO)
 *   sb_purchase_items       — baris PO per SKU
 *   sb_goods_receipts       — header penerimaan barang (tanda terima)
 *   sb_goods_receipt_items  — baris penerimaan per baris PO
 *
 * Desain:
 * - tax_amount ada di sb_purchase_orders tapi diisi 0 (non-PKP sekarang, siap diaktifkan).
 * - Semua nilai uang: integer Rupiah. Kuantitas: DECIMAL(15,4) seperti sb_sale_items.
 * - Status PO state machine: draft → disetujui → diterima_sebagian → diterima / batal.
 * - Stok TIDAK berubah saat PO dibuat atau disetujui — hanya saat penerimaan
 *   barang, lewat StockService (kartu stok sb_stock_moves).
 * - Hutang usaha = grand_total − paid_amount. paid_amount tetap 0 di Sprint 3;
 *   pembayaran hutang adalah Sprint 4 (Accounting).
 * - Sequence 'purchase_order' (PO-SB/) sudah ter-seed di Migration_001.
 *   Migrasi ini men-seed sequence baru 'goods_receipt' (GR-SB/) agar nomor
 *   tanda terima punya urutan sendiri, tidak berbagi dengan dokumen lain.
 */
class Migration_004_Purchasing {
	private \wpdb $wpdb;
	private string $prefix;

	public function __construct( \wpdb $wpdb, string $prefix ) {
		$this->wpdb   = $wpdb;
		$this->prefix = $prefix;
	}

	public function up(): void {
		$charset = $this->wpdb->get_charset_collate();
		$p       = $this->prefix;

		$sql = [];

		// 1. Header Purchase Order
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}purchase_orders` (
			`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`doc_number`      VARCHAR(30)     NOT NULL,
			`supplier_id`     BIGINT UNSIGNED NOT NULL,
			`warehouse_id`    BIGINT UNSIGNED NOT NULL,
			`po_date`         DATE            NOT NULL,
			`expected_date`   DATE            DEFAULT NULL,
			`subtotal`        BIGINT          NOT NULL DEFAULT 0,
			`discount_amount` BIGINT          NOT NULL DEFAULT 0,
			`tax_amount`      BIGINT          NOT NULL DEFAULT 0,
			`grand_total`     BIGINT          NOT NULL DEFAULT 0,
			`paid_amount`     BIGINT          NOT NULL DEFAULT 0,
			`status`          ENUM('draft','disetujui','diterima_sebagian','diterima','batal') NOT NULL DEFAULT 'draft',
			`cancel_reason`   TEXT            DEFAULT NULL,
			`notes`           TEXT            DEFAULT NULL,
			`created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uq_purchase_orders_doc_number` (`doc_number`),
			KEY `idx_purchase_orders_supplier` (`supplier_id`),
			KEY `idx_purchase_orders_status` (`status`),
			KEY `idx_purchase_orders_date` (`po_date`)
		) {$charset};";

		// 2. Baris Purchase Order
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}purchase_items` (
			`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`po_id`           BIGINT UNSIGNED NOT NULL,
			`product_id`      BIGINT UNSIGNED NOT NULL,
			`unit_id`         BIGINT UNSIGNED NOT NULL,
			`qty_ordered`     DECIMAL(15,4)   NOT NULL,
			`qty_received`    DECIMAL(15,4)   NOT NULL DEFAULT 0,
			`unit_cost`       BIGINT          NOT NULL,
			`discount_amount` BIGINT          NOT NULL DEFAULT 0,
			`line_total`      BIGINT          NOT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_purchase_items_po` (`po_id`),
			KEY `idx_purchase_items_product` (`product_id`)
		) {$charset};";

		// 3. Header Penerimaan Barang
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}goods_receipts` (
			`id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`po_id`          BIGINT UNSIGNED NOT NULL,
			`receipt_number` VARCHAR(30)     NOT NULL,
			`receipt_date`   DATETIME        NOT NULL,
			`warehouse_id`   BIGINT UNSIGNED NOT NULL,
			`received_by`    BIGINT UNSIGNED NOT NULL,
			`notes`          TEXT            DEFAULT NULL,
			`created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uq_goods_receipts_number` (`receipt_number`),
			KEY `idx_goods_receipts_po` (`po_id`),
			KEY `idx_goods_receipts_date` (`receipt_date`)
		) {$charset};";

		// 4. Baris Penerimaan Barang
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}goods_receipt_items` (
			`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`receipt_id`    BIGINT UNSIGNED NOT NULL,
			`po_item_id`    BIGINT UNSIGNED NOT NULL,
			`product_id`    BIGINT UNSIGNED NOT NULL,
			`qty_received`  DECIMAL(15,4)   NOT NULL,
			`unit_cost`     BIGINT          NOT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_goods_receipt_items_receipt` (`receipt_id`),
			KEY `idx_goods_receipt_items_po_item` (`po_item_id`),
			KEY `idx_goods_receipt_items_product` (`product_id`)
		) {$charset};";

		foreach ( $sql as $statement ) {
			$this->wpdb->query( $statement );
		}

		// 5. Seed sequence penomoran tanda terima (GR-SB/), jika belum ada.
		$table_seq = $this->prefix . 'document_sequences';
		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT id FROM `{$table_seq}` WHERE doc_type = %s", 'goods_receipt' )
		);
		if ( ! $exists ) {
			$this->wpdb->insert(
				$table_seq,
				[
					'doc_type'       => 'goods_receipt',
					'prefix'         => 'GR-SB/',
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
		foreach ( [ 'goods_receipt_items', 'goods_receipts', 'purchase_items', 'purchase_orders' ] as $t ) {
			$this->wpdb->query( "DROP TABLE IF EXISTS `{$p}{$t}`" );
		}
	}
}
