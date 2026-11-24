<?php
namespace SagaraBaja\Core\Database\Migrations;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Migrasi 001: Membuat tabel audit log dan urutan penomoran dokumen (Core Sprint 0).
 */
class Migration_001_Initial_Core {
    private \wpdb $wpdb;
    private string $prefix;

    public function __construct( \wpdb $wpdb, string $prefix ) {
        $this->wpdb = $wpdb;
        $this->prefix = $prefix;
    }

    public function up(): void {
        $charset_collate = $this->wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Tabel sb_audit_logs (Append-only)
        $table_audit = $this->prefix . 'audit_logs';
        $sql_audit = "CREATE TABLE `{$table_audit}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` BIGINT UNSIGNED NULL,
            `user_name` VARCHAR(100) NULL,
            `action` VARCHAR(50) NOT NULL,
            `object_type` VARCHAR(50) NOT NULL,
            `object_id` VARCHAR(50) NULL,
            `old_values` LONGTEXT NULL,
            `new_values` LONGTEXT NULL,
            `ip_address` VARCHAR(45) NULL,
            `user_agent` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_object` (`object_type`, `object_id`),
            KEY `idx_user` (`user_id`),
            KEY `idx_action` (`action`),
            KEY `idx_created` (`created_at`)
        ) {$charset_collate};";
        dbDelta( $sql_audit );

        // 2. Tabel sb_document_sequences (Race-condition safe sequential numbering)
        $table_seq = $this->prefix . 'document_sequences';
        $sql_seq = "CREATE TABLE `{$table_seq}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `doc_type` VARCHAR(50) NOT NULL,
            `prefix` VARCHAR(20) NOT NULL,
            `current_number` INT UNSIGNED NOT NULL DEFAULT 0,
            `pad_length` TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `reset_period` VARCHAR(10) NOT NULL DEFAULT 'yearly',
            `last_period` VARCHAR(10) NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_doc_type` (`doc_type`)
        ) {$charset_collate};";
        dbDelta( $sql_seq );

        // 3. Seed data awal sequence jenis dokumen
        $now = current_time( 'mysql', true );
        $default_sequences = [
            [
                'doc_type'       => 'invoice_resmi',
                'prefix'         => 'INV-RES/',
                'pad_length'     => 5,
                'reset_period'   => 'yearly',
            ],
            [
                'doc_type'       => 'nota_non_resmi',
                'prefix'         => 'NOT-NR/',
                'pad_length'     => 5,
                'reset_period'   => 'yearly',
            ],
            [
                'doc_type'       => 'purchase_order',
                'prefix'         => 'PO-SB/',
                'pad_length'     => 5,
                'reset_period'   => 'yearly',
            ],
            [
                'doc_type'       => 'surat_jalan',
                'prefix'         => 'SJ-SB/',
                'pad_length'     => 5,
                'reset_period'   => 'yearly',
            ],
            [
                'doc_type'       => 'stock_opname',
                'prefix'         => 'SO-SB/',
                'pad_length'     => 4,
                'reset_period'   => 'yearly',
            ],
            [
                'doc_type'       => 'kasbon',
                'prefix'         => 'KB-SB/',
                'pad_length'     => 4,
                'reset_period'   => 'yearly',
            ],
        ];

        foreach ( $default_sequences as $seq ) {
            $exists = $this->wpdb->get_var(
                $this->wpdb->prepare( "SELECT id FROM `{$table_seq}` WHERE doc_type = %s", $seq['doc_type'] )
            );

            if ( ! $exists ) {
                $this->wpdb->insert(
                    $table_seq,
                    [
                        'doc_type'       => $seq['doc_type'],
                        'prefix'         => $seq['prefix'],
                        'current_number' => 0,
                        'pad_length'     => $seq['pad_length'],
                        'reset_period'   => $seq['reset_period'],
                        'last_period'    => null,
                        'updated_at'     => $now,
                    ],
                    [ '%s', '%s', '%d', '%d', '%s', '%s', '%s' ]
                );
            }
        }
    }
}
