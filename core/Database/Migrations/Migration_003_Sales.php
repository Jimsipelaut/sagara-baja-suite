<?php
namespace SagaraBaja\Core\Database\Migrations;

/**
 * Migration_003_Sales
 *
 * Membuat tabel untuk modul Sales Sprint 2:
 *   sb_customers       — master pelanggan
 *   sb_suppliers       — master supplier
 *   sb_cashier_shifts  — shift kasir (buka/tutup)
 *   sb_sales           — header dokumen penjualan (resmi & non-resmi)
 *   sb_sale_items      — baris penjualan per SKU
 *   sb_payments        — pembayaran dan alokasi ke dokumen
 *
 * Desain:
 * - tax_amount ada di sb_sales tapi diisi 0 (non-PKP sekarang, siap diaktifkan).
 * - Semua nilai uang: integer Rupiah.
 * - invoice_type: 'resmi' | 'non_resmi'  (tidak boleh berbagi nomor urut).
 * - status state machine: draft → terbit → lunas / batal
 */
class Migration_003_Sales {
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

		// 1. Pelanggan
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}customers` (
			`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`code`          VARCHAR(30)     NOT NULL,
			`name`          VARCHAR(150)    NOT NULL,
			`phone`         VARCHAR(30)     DEFAULT NULL,
			`address`       TEXT            DEFAULT NULL,
			`credit_limit`  BIGINT          NOT NULL DEFAULT 0,
			`notes`         TEXT            DEFAULT NULL,
			`is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
			`created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uq_customers_code` (`code`),
			KEY `idx_customers_name` (`name`)
		) {$charset};";

		// 2. Supplier
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}suppliers` (
			`id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`code`          VARCHAR(30)     NOT NULL,
			`name`          VARCHAR(150)    NOT NULL,
			`phone`         VARCHAR(30)     DEFAULT NULL,
			`address`       TEXT            DEFAULT NULL,
			`notes`         TEXT            DEFAULT NULL,
			`is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
			`created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uq_suppliers_code` (`code`),
			KEY `idx_suppliers_name` (`name`)
		) {$charset};";

		// 3. Shift Kasir
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}cashier_shifts` (
			`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`cashier_user_id` BIGINT UNSIGNED NOT NULL,
			`warehouse_id`    BIGINT UNSIGNED NOT NULL,
			`opened_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`closed_at`       DATETIME        DEFAULT NULL,
			`opening_cash`    BIGINT          NOT NULL DEFAULT 0,
			`closing_cash`    BIGINT          DEFAULT NULL,
			`notes`           TEXT            DEFAULT NULL,
			`status`          ENUM('open','closed') NOT NULL DEFAULT 'open',
			`created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY `idx_shifts_cashier` (`cashier_user_id`),
			KEY `idx_shifts_status` (`status`)
		) {$charset};";

		// 4. Header Penjualan
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}sales` (
			`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`doc_number`      VARCHAR(30)     NOT NULL,
			`invoice_type`    ENUM('resmi','non_resmi') NOT NULL DEFAULT 'non_resmi',
			`sale_date`       DATE            NOT NULL,
			`customer_id`     BIGINT UNSIGNED DEFAULT NULL,
			`warehouse_id`    BIGINT UNSIGNED NOT NULL,
			`shift_id`        BIGINT UNSIGNED DEFAULT NULL,
			`cashier_user_id` BIGINT UNSIGNED NOT NULL,
			`subtotal`        BIGINT          NOT NULL DEFAULT 0,
			`discount_amount` BIGINT          NOT NULL DEFAULT 0,
			`tax_amount`      BIGINT          NOT NULL DEFAULT 0,
			`grand_total`     BIGINT          NOT NULL DEFAULT 0,
			`paid_amount`     BIGINT          NOT NULL DEFAULT 0,
			`status`          ENUM('draft','terbit','lunas','batal') NOT NULL DEFAULT 'draft',
			`void_reason`     TEXT            DEFAULT NULL,
			`notes`           TEXT            DEFAULT NULL,
			`created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uq_sales_doc_number` (`doc_number`),
			KEY `idx_sales_date` (`sale_date`),
			KEY `idx_sales_customer` (`customer_id`),
			KEY `idx_sales_status` (`status`),
			KEY `idx_sales_shift` (`shift_id`)
		) {$charset};";

		// 5. Baris Penjualan
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}sale_items` (
			`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`sale_id`         BIGINT UNSIGNED NOT NULL,
			`product_id`      BIGINT UNSIGNED NOT NULL,
			`unit_id`         BIGINT UNSIGNED NOT NULL,
			`qty`             DECIMAL(15,4)   NOT NULL,
			`unit_price`      BIGINT          NOT NULL,
			`discount_amount` BIGINT          NOT NULL DEFAULT 0,
			`line_total`      BIGINT          NOT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_sale_items_sale` (`sale_id`),
			KEY `idx_sale_items_product` (`product_id`)
		) {$charset};";

		// 6. Pembayaran
		$sql[] = "CREATE TABLE IF NOT EXISTS `{$p}payments` (
			`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`sale_id`         BIGINT UNSIGNED NOT NULL,
			`payment_date`    DATETIME        NOT NULL,
			`method`          ENUM('tunai','transfer','qris','kasbon') NOT NULL DEFAULT 'tunai',
			`amount`          BIGINT          NOT NULL,
			`reference`       VARCHAR(100)    DEFAULT NULL,
			`notes`           TEXT            DEFAULT NULL,
			`created_by`      BIGINT UNSIGNED NOT NULL,
			`created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY `idx_payments_sale` (`sale_id`),
			KEY `idx_payments_date` (`payment_date`)
		) {$charset};";

		foreach ( $sql as $statement ) {
			$this->wpdb->query( $statement );
		}
	}

	public function down(): void {
		$p = $this->prefix;
		foreach ( [ 'payments', 'sale_items', 'sales', 'cashier_shifts', 'suppliers', 'customers' ] as $t ) {
			$this->wpdb->query( "DROP TABLE IF EXISTS `{$p}{$t}`" );
		}
	}
}
