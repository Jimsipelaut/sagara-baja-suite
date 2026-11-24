<?php
namespace SagaraBaja\Core;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * PSR-4 Autoloader untuk namespace SagaraBaja.
 */
class Autoloader {
    public static function register(): void {
        spl_autoload_register( [ self::class, 'autoload' ] );
    }

    public static function autoload( string $class ): void {
        $prefix = 'SagaraBaja\\';
        $base_dir = defined( 'SB_PLUGIN_DIR' ) ? SB_PLUGIN_DIR : dirname( __DIR__ ) . '/';

        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class, $len );

        // Map Core namespace
        if ( strncmp( $relative_class, 'Core\\', 5 ) === 0 ) {
            $sub_class = substr( $relative_class, 5 );
            $file = $base_dir . 'core/' . str_replace( '\\', '/', $sub_class ) . '.php';
            if ( file_exists( $file ) ) {
                require_once $file;
                return;
            }
        }

        // Map Modules namespace (contoh: SagaraBaja\Modules\Inventory\Services\StockService)
        if ( strncmp( $relative_class, 'Modules\\', 8 ) === 0 ) {
            $sub_class = substr( $relative_class, 8 );
            $parts = explode( '\\', $sub_class, 2 );
            $module_name = strtolower( $parts[0] );
            $module_sub = isset( $parts[1] ) ? str_replace( '\\', '/', $parts[1] ) : '';
            $file = $base_dir . 'modules/' . $module_name . '/' . $module_sub . '.php';
            if ( file_exists( $file ) ) {
                require_once $file;
                return;
            }
        }
    }
}
