<?php
namespace SagaraBaja\Modules\Inventory\Repositories;

use SagaraBaja\Core\Audit\AuditLogger;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Repository Pengelolaan Master Barang (Produk) Toko Material.
 */
class ProductRepository {
    private \wpdb $wpdb;
    private string $table_products;
    private string $table_categories;
    private string $table_units;
    private string $table_balances;

    public function __construct( ?\wpdb $wpdb = null ) {
        global $wpdb;
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $prefix = $this->wpdb->prefix . SB_TABLE_PREFIX;
        $this->table_products   = $prefix . 'products';
        $this->table_categories = $prefix . 'categories';
        $this->table_units      = $prefix . 'units';
        $this->table_balances   = $prefix . 'stock_balances';
    }

    /**
     * Buat produk baru dengan 3 tingkat harga.
     */
    public function create( array $data ): int {
        $sku = strtoupper( sanitize_text_field( $data['sku'] ?? '' ) );
        if ( empty( $sku ) ) {
            throw new \InvalidArgumentException( "SKU barang wajib diisi." );
        }

        // Cek SKU unik
        $existing = $this->find_by_sku( $sku );
        if ( $existing ) {
            throw new \InvalidArgumentException( sprintf( "Barang dengan SKU '%s' sudah terdaftar.", esc_html( $sku ) ) );
        }

        $name = sanitize_text_field( $data['name'] ?? '' );
        if ( empty( $name ) ) {
            throw new \InvalidArgumentException( "Nama barang wajib diisi." );
        }

        $base_unit_id = (int) ( $data['base_unit_id'] ?? 0 );
        if ( $base_unit_id <= 0 ) {
            throw new \InvalidArgumentException( "Satuan dasar barang wajib dipilih." );
        }

        $record = [
            'sku'                  => $sku,
            'barcode'              => ! empty( $data['barcode'] ) ? sanitize_text_field( $data['barcode'] ) : null,
            'name'                 => $name,
            'category_id'          => ! empty( $data['category_id'] ) ? (int) $data['category_id'] : null,
            'base_unit_id'         => $base_unit_id,
            'cost_price'           => max( 0, (int) ( $data['cost_price'] ?? 0 ) ),
            'sell_price_retail'    => max( 0, (int) ( $data['sell_price_retail'] ?? 0 ) ),
            'sell_price_wholesale' => max( 0, (int) ( $data['sell_price_wholesale'] ?? 0 ) ),
            'sell_price_project'   => max( 0, (int) ( $data['sell_price_project'] ?? 0 ) ),
            'min_stock'            => max( 0.0, (float) ( $data['min_stock'] ?? 0 ) ),
            'is_active'            => isset( $data['is_active'] ) ? (int) (bool) $data['is_active'] : 1,
            'created_at'           => current_time( 'mysql', true ),
            'updated_at'           => current_time( 'mysql', true ),
        ];

        $inserted = $this->wpdb->insert(
            $this->table_products,
            $record,
            [ '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            throw new \RuntimeException( "Gagal menyimpan data barang ke database." );
        }

        $product_id = (int) $this->wpdb->insert_id;

        AuditLogger::log(
            'create_product',
            'product',
            $sku,
            null,
            $record
        );

        return $product_id;
    }

    /**
     * Dapatkan harga satuan berdasarkan tingkatan harga pembeli.
     * Tier yang didukung: 'retail' (eceran), 'wholesale' (grosir), 'project' (proyek/kontraktor).
     */
    public function get_price_for_tier( int $product_id, string $tier = 'retail' ): int {
        $product = $this->find_by_id( $product_id );
        if ( ! $product ) {
            throw new \InvalidArgumentException( sprintf( "Produk #%d tidak ditemukan.", $product_id ) );
        }

        return match ( strtolower( $tier ) ) {
            'wholesale', 'grosir'   => (int) $product['sell_price_wholesale'],
            'project', 'proyek'     => (int) $product['sell_price_project'],
            default                 => (int) $product['sell_price_retail'],
        };
    }

    public function find_by_id( int $id ): ?array {
        $row = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT p.*, c.name AS category_name, u.code AS unit_code, u.name AS unit_name
             FROM `{$this->table_products}` p
             LEFT JOIN `{$this->table_categories}` c ON p.category_id = c.id
             LEFT JOIN `{$this->table_units}` u ON p.base_unit_id = u.id
             WHERE p.id = %d",
            $id
        ), \ARRAY_A );

        return $row ?: null;
    }

    public function find_by_sku( string $sku ): ?array {
        $row = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT * FROM `{$this->table_products}` WHERE sku = %s",
            $sku
        ), \ARRAY_A );

        return $row ?: null;
    }

    /**
     * Ambil daftar produk dengan filter dan informasi total stok.
     */
    public function all( array $filters = [] ): array {
        $where = [ '1=1' ];
        $args  = [];

        if ( ! empty( $filters['search'] ) ) {
            $like = '%' . $this->wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
            $where[] = '(p.sku LIKE %s OR p.name LIKE %s OR p.barcode LIKE %s)';
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
        }

        if ( ! empty( $filters['category_id'] ) ) {
            $where[] = 'p.category_id = %d';
            $args[] = (int) $filters['category_id'];
        }

        if ( isset( $filters['is_active'] ) ) {
            $where[] = 'p.is_active = %d';
            $args[] = (int) (bool) $filters['is_active'];
        }

        $where_sql = implode( ' AND ', $where );
        $query = "SELECT p.*, c.name AS category_name, u.code AS unit_code,
                         COALESCE(SUM(b.qty), 0) AS total_stock
                  FROM `{$this->table_products}` p
                  LEFT JOIN `{$this->table_categories}` c ON p.category_id = c.id
                  LEFT JOIN `{$this->table_units}` u ON p.base_unit_id = u.id
                  LEFT JOIN `{$this->table_balances}` b ON p.id = b.product_id
                  WHERE {$where_sql}
                  GROUP BY p.id
                  ORDER BY p.name ASC";

        if ( ! empty( $args ) ) {
            $query = $this->wpdb->prepare( $query, ...$args );
        }

        $results = $this->wpdb->get_results( $query, \ARRAY_A );
        return is_array( $results ) ? $results : [];
    }
}
