<?php
/**
 * Test Bootstrap & WordPress Lightweight Test Doubles.
 */
define( 'SB_TEST_SUITE', true );
define( 'SB_VERSION', '0.1.0-planning' );
define( 'SB_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'SB_TABLE_PREFIX', 'sb_' );

if ( ! defined( 'ARRAY_A' ) ) {
    define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'OBJECT' ) ) {
    define( 'OBJECT', 'OBJECT' );
}

// Mock WordPress functions if not loaded
if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) {
        return trim( strip_tags( (string) $str ) );
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $str ) {
        return trim( strip_tags( (string) $str ) );
    }
}

if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $email ) {
        return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '';
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $val ) {
        return is_string( $val ) ? stripslashes( $val ) : $val;
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data, JSON_UNESCAPED_UNICODE );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = false ) {
        return gmdate( 'Y-m-d H:i:s' );
    }
}

if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id() {
        return 1;
    }
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
    function wp_get_current_user() {
        return (object) [
            'ID' => 1,
            'display_name' => 'Owner Sagara',
            'user_login' => 'owner',
        ];
    }
}

$GLOBALS['_mock_options'] = [];
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = false ) {
        return $GLOBALS['_mock_options'][ $name ] ?? $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $name, $value ) {
        $GLOBALS['_mock_options'][ $name ] = $value;
        return true;
    }
}

if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( $args, $defaults = [] ) {
        if ( is_object( $args ) ) {
            $r = get_object_vars( $args );
        } elseif ( is_array( $args ) ) {
            $r =& $args;
        } else {
            $r = [];
        }
        return array_merge( $defaults, $r );
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}

// In-Memory wpdb Mock
if ( ! class_exists( 'wpdb' ) ) {
    class wpdb {
        public string $prefix = 'wp_';
        public int $insert_id = 0;
    }
}

class MockWPDB extends wpdb {
    public array $inserted_rows = [];
    public array $updated_rows = [];
    public array $queries = [];
    public array $sequences = [];

    public function __construct() {
        // Seed default sequences
        $this->sequences['invoice_resmi'] = [
            'id' => 1,
            'doc_type' => 'invoice_resmi',
            'prefix' => 'INV-RES/',
            'current_number' => 0,
            'pad_length' => 5,
            'reset_period' => 'yearly',
            'last_period' => null,
            'updated_at' => '2026-01-01 00:00:00',
        ];
        $this->sequences['nota_non_resmi'] = [
            'id' => 2,
            'doc_type' => 'nota_non_resmi',
            'prefix' => 'NOT-NR/',
            'current_number' => 0,
            'pad_length' => 5,
            'reset_period' => 'yearly',
            'last_period' => null,
            'updated_at' => '2026-01-01 00:00:00',
        ];
    }

    public function query( string $query ): bool {
        $this->queries[] = $query;
        return true;
    }

    public function prepare( string $query, ...$args ): string {
        $query = str_replace( '%s', "'%s'", $query );
        $query = str_replace( '%d', '%d', $query );
        return vsprintf( $query, $args );
    }

    public function get_row( string $query, string $output = 'OBJECT' ) {
        $this->queries[] = $query;
        if ( preg_match( "/WHERE doc_type = '([^']+)'/i", $query, $matches ) ) {
            $doc_type = $matches[1];
            if ( isset( $this->sequences[ $doc_type ] ) ) {
                return $this->sequences[ $doc_type ];
            }
        }
        return null;
    }

    public function get_var( string $query ) {
        $this->queries[] = $query;
        if ( preg_match( "/SELECT current_number FROM `[^`]+` WHERE doc_type = '([^']+)'/i", $query, $matches ) ) {
            $doc_type = $matches[1];
            return $this->sequences[ $doc_type ]['current_number'] ?? null;
        }
        return null;
    }

    public function insert( string $table, array $data, ?array $format = null ): int|false {
        $this->insert_id++;
        $data['id'] = $this->insert_id;
        $this->inserted_rows[] = [ 'table' => $table, 'data' => $data ];
        return 1;
    }

    public function update( string $table, array $data, array $where, ?array $format = null, ?array $where_format = null ): int|false {
        $this->updated_rows[] = [ 'table' => $table, 'data' => $data, 'where' => $where ];
        if ( isset( $where['doc_type'] ) && isset( $this->sequences[ $where['doc_type'] ] ) ) {
            $this->sequences[ $where['doc_type'] ] = array_merge( $this->sequences[ $where['doc_type'] ], $data );
            return 1;
        }
        return 1;
    }
}

// Inisialisasi Mock WPDB global
$GLOBALS['wpdb'] = new MockWPDB();

// Register autoloader
require_once SB_PLUGIN_DIR . 'core/Autoloader.php';
\SagaraBaja\Core\Autoloader::register();
