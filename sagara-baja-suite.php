<?php
/**
 * Plugin Name:       Sagara Baja Suite
 * Plugin URI:        https://github.com/Jimsipelaut/sagara-baja-suite
 * Description:       Sistem terpadu Inventory, POS, Invoice Resmi/Non-Resmi, Akuntansi, dan HR/Payroll untuk Sagara Baja.
 * Version:           0.1.0-planning
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Sagara Baja Team
 * Text Domain:       sagara-baja-suite
 * Domain Path:       /languages
 * License:           Proprietary
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Minimal PHP 8.1
if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Sagara Baja Suite membutuhkan PHP minimal versi 8.1.', 'sagara-baja-suite' ) . '</p></div>';
    } );
    return;
}

// Definisi Konstanta Plugin
define( 'SB_VERSION', '0.1.0-planning' );
define( 'SB_PLUGIN_FILE', __FILE__ );
define( 'SB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SB_TABLE_PREFIX', 'sb_' );

// Registrasi Autoloader
require_once SB_PLUGIN_DIR . 'core/Autoloader.php';
\SagaraBaja\Core\Autoloader::register();

// Hook Aktivasi & Deaktivasi
register_activation_hook( __FILE__, [ \SagaraBaja\Core\Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \SagaraBaja\Core\Plugin::class, 'deactivate' ] );

// Inisialisasi Plugin
add_action( 'plugins_loaded', function() {
    \SagaraBaja\Core\Plugin::instance()->init();
} );
