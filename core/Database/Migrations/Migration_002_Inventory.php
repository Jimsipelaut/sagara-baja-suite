<?php
namespace SagaraBaja\Core\Database\Migrations;

use SagaraBaja\Core\Database\Migrator;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Migrasi Skema Database Sprint 1: Inventory & Master Data.
 */
class Migration_002_Inventory {
    private \wpdb $wpdb;
    private string $prefix;

    public function __construct( \wpdb $wpdb, string $prefix ) {
        $this->wpdb = $wpdb;
        $this->prefix = $prefix;
    }

    public function up(): void {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = [];

        // 1. Kategori Barang
        $table_categories = $this->prefix . 'categories';
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_categories}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `slug` VARCHAR(100) NOT NULL,
            `parent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`),
            KEY `parent_id` (`parent_id`)
        ) {$charset_collate};";

        // 2. Satuan Barang
        $table_units = $this->prefix . 'units';
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_units}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(20) NOT NULL,
            `name` VARCHAR(50) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) {$charset_collate};";

        // 3. Konversi Satuan
        $table_conversions = $this->prefix . 'unit_conversions';
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_conversions}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `product_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL jika konversi standar global',
            `from_unit_id` BIGINT UNSIGNED NOT NULL,
            `to_unit_id` BIGINT UNSIGNED NOT NULL,
            `multiplier` DECIMAL(12, 4) NOT NULL COMMENT '1 from_unit = multiplier * to_unit',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `product_lookup` (`product_id`, `from_unit_id`, `to_unit_id`)
        ) {$charset_collate};";

        // 4. Gudang
        $table_warehouses = $this->prefix . 'warehouses';
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_warehouses}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(20) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `address` TEXT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) {$charset_collate};";

        // 5. Master Barang (Produk)
        $table_products = $this->prefix . 'products';
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_products}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `sku` VARCHAR(50) NOT NULL,
            `barcode` VARCHAR(50) NULL DEFAULT NULL,
            `name` VARCHAR(255) NOT NULL,
            `category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
            `base_unit_id` BIGINT UNSIGNED NOT NULL,
            `cost_price` BIGINT NOT NULL DEFAULT 0 COMMENT 'Harga pokok dalam integer rupiah',
            `sell_price_retail` BIGINT NOT NULL DEFAULT 0 COMMENT 'Harga eceran rupiah',
            `sell_price_wholesale` BIGINT NOT NULL DEFAULT 0 COMMENT 'Harga grosir rupiah',
            `sell_price_project` BIGINT NOT NULL DEFAULT 0 COMMENT 'Harga proyek/kontraktor rupiah',
            `min_stock` DECIMAL(12, 4) NOT NULL DEFAULT '0.0000',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `sku` (`sku`),
            KEY `barcode` (`barcode`),
            KEY `category_id` (`category_id`),
            KEY `name` (`name`(50))
        ) {$charset_collate};";

        // 6. Saldo Stok per Gudang
        $table_stock_balances = $this->prefix . 'stock_balances';
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_stock_balances}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `product_id` BIGINT UNSIGNED NOT NULL,
            `warehouse_id` BIGINT UNSIGNED NOT NULL,
            `qty` DECIMAL(12, 4) NOT NULL DEFAULT '0.0000',
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `product_warehouse` (`product_id`, `warehouse_id`),
            KEY `warehouse_id` (`warehouse_id`)
        ) {$charset_collate};";

        // 7. Kartu Stok (Mutasi Stok Masuk/Keluar)
        $table_stock_moves = $this->prefix . 'stock_moves';
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_stock_moves}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `move_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ref_type` VARCHAR(50) NOT NULL COMMENT 'initial, purchase_receipt, sale_pos, sale_invoice, adjustment, transfer',
            `ref_id` VARCHAR(50) NULL DEFAULT NULL,
            `product_id` BIGINT UNSIGNED NOT NULL,
            `warehouse_id` BIGINT UNSIGNED NOT NULL,
            `qty_change` DECIMAL(12, 4) NOT NULL COMMENT 'Positif: masuk, Negatif: keluar',
            `unit_id` BIGINT UNSIGNED NOT NULL,
            `unit_cost` BIGINT NOT NULL DEFAULT 0,
            `balance_after` DECIMAL(12, 4) NOT NULL,
            `notes` VARCHAR(255) NULL DEFAULT NULL,
            `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `product_date` (`product_id`, `move_date`),
            KEY `warehouse_id` (`warehouse_id`),
            KEY `ref_lookup` (`ref_type`, `ref_id`)
        ) {$charset_collate};";

        // 8. Stock Opname Header
        $table_stock_opnames = $this->prefix . 'stock_opnames';
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_stock_opnames}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `opname_number` VARCHAR(50) NOT NULL,
            `warehouse_id` BIGINT UNSIGNED NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, approved, cancelled',
            `notes` TEXT NULL,
            `created_by` BIGINT UNSIGNED NOT NULL,
            `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `approved_at` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `opname_number` (`opname_number`),
            KEY `status` (`status`)
        ) {$charset_collate};";

        // 9. Stock Opname Items
        $table_stock_opname_items = $this->prefix . 'stock_opname_items';
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_stock_opname_items}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `opname_id` BIGINT UNSIGNED NOT NULL,
            `product_id` BIGINT UNSIGNED NOT NULL,
            `system_qty` DECIMAL(12, 4) NOT NULL,
            `physical_qty` DECIMAL(12, 4) NOT NULL,
            `diff_qty` DECIMAL(12, 4) NOT NULL,
            `notes` VARCHAR(255) NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `opname_id` (`opname_id`),
            KEY `product_id` (`product_id`)
        ) {$charset_collate};";

        foreach ( $sql as $statement ) {
            $this->wpdb->query( $statement );
        }

        // Daftarkan sequence untuk stock opname dan transfer barang
        $seq_table = $this->prefix . 'document_sequences';
        $this->wpdb->query( $this->wpdb->prepare(
            "INSERT IGNORE INTO `{$seq_table}` (`doc_type`, `prefix`, `current_number`, `pad_length`, `reset_period`, `last_period`)
             VALUES (%s, %s, %d, %d, %s, %s)",
            'stock_opname', 'OPN/', 0, 5, 'yearly', gmdate( 'Y' )
        ) );

        $this->wpdb->query( $this->wpdb->prepare(
            "INSERT IGNORE INTO `{$seq_table}` (`doc_type`, `prefix`, `current_number`, `pad_length`, `reset_period`, `last_period`)
             VALUES (%s, %s, %d, %d, %s, %s)",
            'stock_transfer', 'TRF/', 0, 5, 'yearly', gmdate( 'Y' )
        ) );
    }
}
