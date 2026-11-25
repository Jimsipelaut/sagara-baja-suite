<?php
namespace SagaraBaja\Core;

use SagaraBaja\Core\Auth\Roles;
use SagaraBaja\Core\Database\Migrator;
use SagaraBaja\Core\Admin\AdminMenu;
use SagaraBaja\Core\Audit\AuditLogger;
use SagaraBaja\Modules\Inventory\Admin\InventoryAdmin;
use SagaraBaja\Modules\Sales\Admin\SalesAdmin;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Singleton Kontainer Utama Sagara Baja Suite.
 */
class Plugin {
    private static ?Plugin $instance = null;

    public static function instance(): Plugin {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Inisialisasi plugin saat WordPress dimuat.
     */
    public function init(): void {
        $this->load_textdomain();

        // Registrasi menu dan antarmuka admin
        if ( is_admin() ) {
            AdminMenu::register();
            InventoryAdmin::register();
            SalesAdmin::register();
        }

        // Jalankan hook modul-modul
        do_action( 'sb_suite_loaded' );
    }

    /**
     * Prosedur saat plugin diaktifkan di WordPress.
     */
    public static function activate(): void {
        // 1. Daftarkan Peran dan Capability
        Roles::register_roles();

        // 2. Jalankan Migrasi Database
        $migrator = new Migrator();
        $executed = $migrator->run();

        // 3. Catat di Audit Log
        AuditLogger::log(
            'activate_plugin',
            'core',
            SB_VERSION,
            null,
            [ 'executed_migrations' => $executed ]
        );

        flush_rewrite_rules();
    }

    /**
     * Prosedur saat plugin dideaktivasi.
     */
    public static function deactivate(): void {
        AuditLogger::log( 'deactivate_plugin', 'core', SB_VERSION );
        flush_rewrite_rules();
    }

    /**
     * Muat terjemahan bahasa.
     */
    private function load_textdomain(): void {
        load_plugin_textdomain(
            'sagara-baja-suite',
            false,
            dirname( plugin_basename( SB_PLUGIN_FILE ) ) . '/languages'
        );
    }
}
