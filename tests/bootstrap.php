<?php
/**
 * Test Bootstrap & WordPress Lightweight Test Doubles.
 */
define( 'SB_TEST_SUITE', true );
define( 'SB_VERSION', '0.2.0-inventory' );
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

if ( ! function_exists( 'sanitize_title' ) ) {
    function sanitize_title( $title ) {
        return preg_replace( '/[^a-z0-9_\-]/', '-', strtolower( trim( (string) $title ) ) );
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
    public array $tables = [];
    public array $queries = [];
    public array $inserted_rows = [];
    public array $updated_rows = [];
    public array $sequences = [];

    public function __construct() {
        $this->reset();
    }

    public function reset(): void {
        $this->tables = [
            'wp_sb_document_sequences' => [
                'invoice_resmi' => [
                    'id' => 1,
                    'doc_type' => 'invoice_resmi',
                    'prefix' => 'INV-RES/',
                    'current_number' => 0,
                    'pad_length' => 5,
                    'reset_period' => 'yearly',
                    'last_period' => null,
                    'updated_at' => '2026-01-01 00:00:00',
                ],
                'nota_non_resmi' => [
                    'id' => 2,
                    'doc_type' => 'nota_non_resmi',
                    'prefix' => 'NOT-NR/',
                    'current_number' => 0,
                    'pad_length' => 5,
                    'reset_period' => 'yearly',
                    'last_period' => null,
                    'updated_at' => '2026-01-01 00:00:00',
                ],
                'stock_opname' => [
                    'id' => 3,
                    'doc_type' => 'stock_opname',
                    'prefix' => 'OPN/',
                    'current_number' => 0,
                    'pad_length' => 5,
                    'reset_period' => 'monthly',
                    'last_period' => null,
                    'updated_at' => '2026-01-01 00:00:00',
                ],
                // Sesuai seed Migration_001 (purchase_order) dan Migration_004 (goods_receipt).
                'purchase_order' => [
                    'id' => 4,
                    'doc_type' => 'purchase_order',
                    'prefix' => 'PO-SB/',
                    'current_number' => 0,
                    'pad_length' => 5,
                    'reset_period' => 'yearly',
                    'last_period' => null,
                    'updated_at' => '2026-01-01 00:00:00',
                ],
                'goods_receipt' => [
                    'id' => 5,
                    'doc_type' => 'goods_receipt',
                    'prefix' => 'GR-SB/',
                    'current_number' => 0,
                    'pad_length' => 5,
                    'reset_period' => 'yearly',
                    'last_period' => null,
                    'updated_at' => '2026-01-01 00:00:00',
                ],
            ],
            'wp_sb_categories' => [],
            'wp_sb_units' => [],
            'wp_sb_warehouses' => [],
            'wp_sb_products' => [],
            'wp_sb_unit_conversions' => [],
            'wp_sb_stock_balances' => [],
            'wp_sb_stock_moves' => [],
            'wp_sb_stock_opnames' => [],
            'wp_sb_stock_opname_items' => [],
            'wp_sb_audit_logs' => [],
            'wp_sb_customers' => [],
            'wp_sb_suppliers' => [],
            'wp_sb_cashier_shifts' => [],
            'wp_sb_sales' => [],
            'wp_sb_sale_items' => [],
            'wp_sb_payments' => [],
            'wp_sb_purchase_orders' => [],
            'wp_sb_purchase_items' => [],
            'wp_sb_goods_receipts' => [],
            'wp_sb_goods_receipt_items' => [],
        ];
        $this->sequences =& $this->tables['wp_sb_document_sequences'];
        $this->insert_id = 10;
        $this->queries = [];
        $this->inserted_rows = [];
        $this->updated_rows = [];
    }

    public function esc_like( string $text ): string {
        return addcslashes( $text, '_%\\' );
    }

    public function query( string $query ): bool {
        $this->queries[] = $query;
        return true;
    }

    public function prepare( string $query, ...$args ): string {
        if ( isset( $args[0] ) && is_array( $args[0] ) && count( $args ) === 1 ) {
            $args = $args[0];
        }
        $query = str_replace( '%s', "'%s'", $query );
        $query = str_replace( '%d', '%d', $query );
        $query = str_replace( '%f', '%f', $query );
        return vsprintf( $query, $args );
    }

    public function insert( string $table, array $data, ?array $format = null ): int|false {
        if ( isset( $data['id'] ) ) {
            $this->insert_id = max( $this->insert_id, (int) $data['id'] );
        } else {
            $this->insert_id++;
            $data['id'] = $this->insert_id;
        }
        if ( ! isset( $this->tables[ $table ] ) ) {
            $this->tables[ $table ] = [];
        }

        // Support specific key indexing
        if ( $table === 'wp_sb_document_sequences' && isset( $data['doc_type'] ) ) {
            $this->tables[ $table ][ $data['doc_type'] ] = $data;
        } else {
            $this->tables[ $table ][ $data['id'] ] = $data;
        }

        $this->inserted_rows[] = [ 'table' => $table, 'data' => $data ];
        return 1;
    }

    public function update( string $table, array $data, array $where, ?array $format = null, ?array $where_format = null ): int|false {
        $this->updated_rows[] = [ 'table' => $table, 'data' => $data, 'where' => $where ];
        if ( ! isset( $this->tables[ $table ] ) ) {
            return 0;
        }

        $count = 0;
        foreach ( $this->tables[ $table ] as $key => $row ) {
            $matches = true;
            foreach ( $where as $w_k => $w_v ) {
                if ( ! isset( $row[ $w_k ] ) || (string)$row[ $w_k ] !== (string)$w_v ) {
                    $matches = false;
                    break;
                }
            }
            if ( $matches ) {
                $this->tables[ $table ][ $key ] = array_merge( $row, $data );
                $count++;
            }
        }
        return $count;
    }

    public function get_row( string $query, string $output = 'OBJECT' ) {
        $this->queries[] = $query;

        // Sequence lookup
        if ( preg_match( "/WHERE doc_type = '([^']+)'/i", $query, $m ) ) {
            return $this->tables['wp_sb_document_sequences'][ $m[1] ] ?? null;
        }

        // Product by SKU
        if ( preg_match( "/FROM `wp_sb_products` WHERE sku = '([^']+)'/i", $query, $m ) ) {
            foreach ( $this->tables['wp_sb_products'] as $p ) {
                if ( isset( $p['sku'] ) && $p['sku'] === $m[1] ) {
                    return $output === 'ARRAY_A' ? $p : (object) $p;
                }
            }
            return null;
        }

        // Product by ID
        if ( preg_match( "/FROM [`]?wp_sb_products[`]? p.*WHERE p\.id = (\d+)/is", $query, $m ) ) {
            $id = (int) $m[1];
            $p = $this->tables['wp_sb_products'][ $id ] ?? null;
            if ( $p ) {
                $p['category_name'] = $this->tables['wp_sb_categories'][ $p['category_id'] ]['name'] ?? null;
                $p['unit_code'] = $this->tables['wp_sb_units'][ $p['base_unit_id'] ]['code'] ?? null;
                $p['unit_name'] = $this->tables['wp_sb_units'][ $p['base_unit_id'] ]['name'] ?? null;
                return $output === 'ARRAY_A' ? $p : (object) $p;
            }
            return null;
        }

        // Stock Balance by product_id and warehouse_id
        if ( preg_match( "/FROM `wp_sb_stock_balances` WHERE product_id = (\d+) AND warehouse_id = (\d+)/i", $query, $m ) ) {
            $pid = (int) $m[1];
            $wid = (int) $m[2];
            foreach ( $this->tables['wp_sb_stock_balances'] as $row ) {
                if ( (int)$row['product_id'] === $pid && (int)$row['warehouse_id'] === $wid ) {
                    return $output === 'ARRAY_A' ? $row : (object) $row;
                }
            }
            return null;
        }

        // Stock Opname by ID
        if ( preg_match( "/FROM `wp_sb_stock_opnames` WHERE id = (\d+)/i", $query, $m ) ) {
            $id = (int) $m[1];
            $row = $this->tables['wp_sb_stock_opnames'][ $id ] ?? null;
            return $row ? ( $output === 'ARRAY_A' ? $row : (object) $row ) : null;
        }

        // Generic lookup by ID: sb_customers, sb_suppliers, sb_cashier_shifts, sb_sales
        if ( preg_match( "/FROM [`]?wp_(sb_[a-z_]+)[`]?.*WHERE.*(?:id|\.id) = (\d+)/is", $query, $m ) ) {
            $tbl = 'wp_' . $m[1];
            $id  = (int) $m[2];
            $row = $this->tables[ $tbl ][ $id ] ?? null;
            return $row ? ( $output === 'ARRAY_A' ? $row : (object) $row ) : null;
        }

        // Shift by cashier_user_id and warehouse_id
        if ( preg_match( "/FROM [`]?wp_sb_cashier_shifts[`]?.*WHERE cashier_user_id = (\d+) AND warehouse_id = (\d+)/is", $query, $m ) ) {
            $cid = (int) $m[1];
            $wid = (int) $m[2];
            foreach ( $this->tables['wp_sb_cashier_shifts'] as $row ) {
                if ( (int) $row['cashier_user_id'] === $cid && (int) $row['warehouse_id'] === $wid && ( $row['status'] ?? '' ) === 'open' ) {
                    return $output === 'ARRAY_A' ? $row : (object) $row;
                }
            }
            return null;
        }

        return null;
    }

    public function get_var( string $query ) {
        $this->queries[] = $query;

        // Sequence number
        if ( preg_match( "/SELECT current_number FROM `[^`]+` WHERE doc_type = '([^']+)'/i", $query, $m ) ) {
            return $this->tables['wp_sb_document_sequences'][ $m[1] ]['current_number'] ?? null;
        }

        // Product base_unit_id
        if ( preg_match( "/SELECT base_unit_id FROM [`]?wp_sb_products[`]? WHERE id = (\d+)/i", $query, $m ) ) {
            $id = (int) $m[1];
            foreach ( $this->tables['wp_sb_products'] as $p ) {
                if ( (int) ( $p['id'] ?? 0 ) === $id ) {
                    return $p['base_unit_id'] ?? null;
                }
            }
            return $this->tables['wp_sb_products'][ $id ]['base_unit_id'] ?? null;
        }

        // Product SKU
        if ( preg_match( "/SELECT sku FROM [`]?wp_sb_products[`]? WHERE id = (\d+)/i", $query, $m ) ) {
            $id = (int) $m[1];
            foreach ( $this->tables['wp_sb_products'] as $p ) {
                if ( (int) ( $p['id'] ?? 0 ) === $id ) {
                    return $p['sku'] ?? null;
                }
            }
            return $this->tables['wp_sb_products'][ $id ]['sku'] ?? null;
        }

        // Customer / Supplier code uniqueness check
        if ( preg_match( "/SELECT id FROM [`]?wp_(sb_[a-z_]+)[`]? WHERE code = '([^']+)'/is", $query, $m ) ) {
            $tbl  = 'wp_' . $m[1];
            $code = $m[2];
            foreach ( $this->tables[ $tbl ] ?? [] as $row ) {
                if ( ( $row['code'] ?? '' ) === $code ) {
                    return $row['id'];
                }
            }
            return null;
        }

        // Shift open uniqueness check
        if ( preg_match( "/SELECT id FROM [`]?wp_sb_cashier_shifts[`]?.*cashier_user_id = (\d+) AND warehouse_id = (\d+) AND status = 'open'/is", $query, $m ) ) {
            $cid = (int) $m[1];
            $wid = (int) $m[2];
            foreach ( $this->tables['wp_sb_cashier_shifts'] as $row ) {
                if ( (int) $row['cashier_user_id'] === $cid && (int) $row['warehouse_id'] === $wid && ( $row['status'] ?? '' ) === 'open' ) {
                    return $row['id'];
                }
            }
            return null;
        }

        // Unit ID by code
        if ( preg_match( "/SELECT id FROM `wp_sb_units` WHERE code = '([^']+)'/i", $query, $m ) ) {
            $code = $m[1];
            foreach ( $this->tables['wp_sb_units'] as $u ) {
                if ( ( $u['code'] ?? '' ) === $code ) {
                    return $u['id'];
                }
            }
            return null;
        }

        // Category ID by slug
        if ( preg_match( "/SELECT id FROM `wp_sb_categories` WHERE slug = '([^']+)'/i", $query, $m ) ) {
            $slug = $m[1];
            foreach ( $this->tables['wp_sb_categories'] as $c ) {
                if ( ( $c['slug'] ?? '' ) === $slug ) {
                    return $c['id'];
                }
            }
            return null;
        }

        // Warehouse ID by code
        if ( preg_match( "/SELECT id FROM `wp_sb_warehouses` WHERE code = '([^']+)'/i", $query, $m ) ) {
            $code = $m[1];
            foreach ( $this->tables['wp_sb_warehouses'] as $w ) {
                if ( ( $w['code'] ?? '' ) === $code ) {
                    return $w['id'];
                }
            }
            return null;
        }

        // Conversion ratio (multiplier)
        if ( preg_match( "/SELECT multiplier FROM [`]?wp_sb_unit_conversions[`]?.*from_unit_id = (\d+) AND to_unit_id = (\d+)/is", $query, $m ) ) {
            $from = (int) $m[1];
            $to   = (int) $m[2];
            foreach ( $this->tables['wp_sb_unit_conversions'] as $conv ) {
                if ( (int)$conv['from_unit_id'] === $from && (int)$conv['to_unit_id'] === $to ) {
                    return $conv['multiplier'];
                }
            }
            return null;
        }

        // Conversion existing check
        if ( preg_match( "/SELECT id FROM [`]?wp_sb_unit_conversions[`]?.*from_unit_id = (\d+) AND to_unit_id = (\d+)/is", $query, $m ) ) {
            $from = (int) $m[1];
            $to   = (int) $m[2];
            foreach ( $this->tables['wp_sb_unit_conversions'] as $conv ) {
                if ( (int)$conv['from_unit_id'] === $from && (int)$conv['to_unit_id'] === $to ) {
                    return $conv['id'];
                }
            }
            return null;
        }

        // Stock Balance qty
        if ( preg_match( "/SELECT qty FROM `wp_sb_stock_balances` WHERE product_id = (\d+) AND warehouse_id = (\d+)/i", $query, $m ) ) {
            $pid = (int) $m[1];
            $wid = (int) $m[2];
            foreach ( $this->tables['wp_sb_stock_balances'] as $row ) {
                if ( (int)$row['product_id'] === $pid && (int)$row['warehouse_id'] === $wid ) {
                    return $row['qty'];
                }
            }
            return 0;
        }

        // Stock moves sum
        if ( preg_match( "/SELECT COALESCE\(SUM\(qty_change\), 0\) FROM `wp_sb_stock_moves` WHERE product_id = (\d+) AND warehouse_id = (\d+)/i", $query, $m ) ) {
            $pid = (int) $m[1];
            $wid = (int) $m[2];
            $sum = 0.0;
            foreach ( $this->tables['wp_sb_stock_moves'] as $row ) {
                if ( (int)$row['product_id'] === $pid && (int)$row['warehouse_id'] === $wid ) {
                    $sum += (float) $row['qty_change'];
                }
            }
            return $sum;
        }

        return null;
    }

    public function get_results( string $query, string $output = 'OBJECT' ): array {
        $this->queries[] = $query;

        // Stock Opname Items
        if ( preg_match( "/FROM `wp_sb_stock_opname_items` WHERE opname_id = (\d+)/i", $query, $m ) ) {
            $op_id = (int) $m[1];
            $items = [];
            foreach ( $this->tables['wp_sb_stock_opname_items'] as $item ) {
                if ( (int)$item['opname_id'] === $op_id ) {
                    $items[] = $output === 'ARRAY_A' ? $item : (object) $item;
                }
            }
            return $items;
        }

        // Sale Items
        if ( preg_match( "/FROM [`]?wp_sb_sale_items[`]? WHERE sale_id = (\d+)/i", $query, $m ) ) {
            $sid = (int) $m[1];
            $items = [];
            foreach ( $this->tables['wp_sb_sale_items'] as $item ) {
                if ( (int) $item['sale_id'] === $sid ) {
                    $items[] = $output === 'ARRAY_A' ? $item : (object) $item;
                }
            }
            return $items;
        }

        // Purchase Order Items (JOIN produk & satuan, seperti SaleService::getItems)
        if ( preg_match( "/FROM [`]?wp_sb_purchase_items[`]? i\b.*?WHERE i\.po_id = (\d+)/is", $query, $m ) ) {
            $pid = (int) $m[1];
            $items = [];
            foreach ( $this->tables['wp_sb_purchase_items'] as $item ) {
                if ( (int) $item['po_id'] === $pid ) {
                    $p = $this->tables['wp_sb_products'][ $item['product_id'] ] ?? [];
                    $u = $this->tables['wp_sb_units'][ $item['unit_id'] ] ?? [];
                    $item['product_name'] = $p['name'] ?? null;
                    $item['product_sku']  = $p['sku'] ?? null;
                    $item['unit_name']    = $u['name'] ?? null;
                    $items[] = $output === 'ARRAY_A' ? $item : (object) $item;
                }
            }
            return $items;
        }

        // Goods Receipts by PO
        if ( preg_match( "/FROM [`]?wp_sb_goods_receipts[`]? WHERE po_id = (\d+)/i", $query, $m ) ) {
            $pid = (int) $m[1];
            $receipts = [];
            foreach ( $this->tables['wp_sb_goods_receipts'] as $r ) {
                if ( (int) $r['po_id'] === $pid ) {
                    $receipts[] = $output === 'ARRAY_A' ? $r : (object) $r;
                }
            }
            return $receipts;
        }

        // Goods Receipt Items (JOIN produk)
        if ( preg_match( "/FROM [`]?wp_sb_goods_receipt_items[`]? i\b.*?WHERE i\.receipt_id = (\d+)/is", $query, $m ) ) {
            $rid = (int) $m[1];
            $items = [];
            foreach ( $this->tables['wp_sb_goods_receipt_items'] as $item ) {
                if ( (int) $item['receipt_id'] === $rid ) {
                    $p = $this->tables['wp_sb_products'][ $item['product_id'] ] ?? [];
                    $item['product_name'] = $p['name'] ?? null;
                    $item['product_sku']  = $p['sku'] ?? null;
                    $items[] = $output === 'ARRAY_A' ? $item : (object) $item;
                }
            }
            return $items;
        }

        // Payments
        if ( preg_match( "/FROM [`]?wp_sb_payments[`]? WHERE sale_id = (\d+)/i", $query, $m ) ) {
            $sid = (int) $m[1];
            $payments = [];
            foreach ( $this->tables['wp_sb_payments'] as $p ) {
                if ( (int) $p['sale_id'] === $sid ) {
                    $payments[] = $output === 'ARRAY_A' ? $p : (object) $p;
                }
            }
            return $payments;
        }

        // Umur Piutang: penjualan terbit yang belum lunas (JOIN pelanggan).
        if ( preg_match( "/FROM [`]?wp_sb_sales[`]? s\\b.*?WHERE s\\.status = 'terbit'/is", $query, $m ) ) {
            $customer_id = null;
            if ( preg_match( "/s\\.customer_id = (\\d+)/", $query, $cm ) ) {
                $customer_id = (int) $cm[1];
            }
            $rows = [];
            foreach ( $this->tables['wp_sb_sales'] as $sale ) {
                if ( ( $sale['status'] ?? '' ) !== 'terbit' ) {
                    continue;
                }
                if ( (int) ( $sale['paid_amount'] ?? 0 ) >= (int) ( $sale['grand_total'] ?? 0 ) ) {
                    continue;
                }
                if ( $customer_id !== null && (int) ( $sale['customer_id'] ?? 0 ) !== $customer_id ) {
                    continue;
                }
                $sale['customer_name'] = $this->tables['wp_sb_customers'][ $sale['customer_id'] ]['name'] ?? null;
                $rows[] = $output === 'ARRAY_A' ? $sale : (object) $sale;
            }
            usort( $rows, static function ( $a, $b ) {
                $a = (array) $a;
                $b = (array) $b;
                return [ $a['sale_date'], $a['id'] ] <=> [ $b['sale_date'], $b['id'] ];
            } );
            return $rows;
        }

        // All Products
        if ( str_contains( $query, 'FROM `wp_sb_products` p' ) ) {
            $list = [];
            foreach ( $this->tables['wp_sb_products'] as $p ) {
                $p['category_name'] = $this->tables['wp_sb_categories'][ $p['category_id'] ]['name'] ?? null;
                $p['unit_code'] = $this->tables['wp_sb_units'][ $p['base_unit_id'] ]['code'] ?? null;
                $p['unit_name'] = $this->tables['wp_sb_units'][ $p['base_unit_id'] ]['name'] ?? null;
                $tot = 0.0;
                foreach ( $this->tables['wp_sb_stock_balances'] as $b ) {
                    if ( (int)$b['product_id'] === (int)$p['id'] ) {
                        $tot += (float) $b['qty'];
                    }
                }
                $p['total_stock'] = $tot;
                $list[] = $output === 'ARRAY_A' ? $p : (object) $p;
            }
            return $list;
        }

        return [];
    }
}

// Inisialisasi Mock WPDB global
$GLOBALS['wpdb'] = new MockWPDB();

// Register autoloader
require_once SB_PLUGIN_DIR . 'core/Autoloader.php';
\SagaraBaja\Core\Autoloader::register();
