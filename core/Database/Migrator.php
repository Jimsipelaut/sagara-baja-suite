<?php
namespace SagaraBaja\Core\Database;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Migration runner otomatis untuk tabel-tabel Sagara Baja Suite berawalan prefix `sb_`.
 */
class Migrator {
    private \wpdb $wpdb;
    private string $prefix;

    public function __construct( ?\wpdb $wpdb = null ) {
        global $wpdb;
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $this->prefix = $this->wpdb->prefix . SB_TABLE_PREFIX;
    }

    /**
     * Jalankan migrasi yang belum terpasang.
     */
    public function run(): array {
        $this->ensure_versions_table();

        $applied = $this->get_applied_migrations();
        $migration_files = glob( SB_PLUGIN_DIR . 'core/Database/Migrations/*.php' );
        sort( $migration_files );

        $executed = [];

        foreach ( $migration_files as $file ) {
            $class_name = pathinfo( $file, PATHINFO_FILENAME );
            if ( in_array( $class_name, $applied, true ) ) {
                continue;
            }

            require_once $file;
            $full_class = "SagaraBaja\\Core\\Database\\Migrations\\{$class_name}";
            if ( class_exists( $full_class ) ) {
                $migration = new $full_class( $this->wpdb, $this->prefix );
                $migration->up();

                $this->record_applied( $class_name );
                $executed[] = $class_name;
            }
        }

        return $executed;
    }

    /**
     * Pastikan tabel pelacak versi schema ada.
     */
    private function ensure_versions_table(): void {
        $table_name = $this->prefix . 'schema_versions';
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `version` VARCHAR(100) NOT NULL,
            `applied_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_version` (`version`)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Ambil daftar versi migrasi yang sudah pernah dijalankan.
     */
    public function get_applied_migrations(): array {
        $table_name = $this->prefix . 'schema_versions';
        // Periksa apakah tabel sudah ada
        $check = $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
        if ( ! $check ) {
            return [];
        }

        $results = $this->wpdb->get_col( "SELECT version FROM `{$table_name}` ORDER BY id ASC" );
        return is_array( $results ) ? $results : [];
    }

    /**
     * Catat migrasi yang sukses dijalankan.
     */
    private function record_applied( string $version ): void {
        $table_name = $this->prefix . 'schema_versions';
        $this->wpdb->insert(
            $table_name,
            [
                'version'    => $version,
                'applied_at' => current_time( 'mysql', true ),
            ],
            [ '%s', '%s' ]
        );
    }
}
