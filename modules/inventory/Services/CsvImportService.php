<?php
namespace SagaraBaja\Modules\Inventory\Services;

use SagaraBaja\Core\Database\Transaction;
use SagaraBaja\Core\Audit\AuditLogger;
use SagaraBaja\Modules\Inventory\Repositories\ProductRepository;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Layanan Impor Master Data Barang dari CSV/Excel dengan Validasi Ketat.
 */
class CsvImportService {
    private \wpdb $wpdb;
    private ProductRepository $product_repo;
    private StockService $stock_service;
    private string $table_categories;
    private string $table_units;
    private string $table_warehouses;

    public function __construct(
        ?\wpdb $wpdb = null,
        ?ProductRepository $product_repo = null,
        ?StockService $stock_service = null
    ) {
        global $wpdb;
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $prefix = $this->wpdb->prefix . SB_TABLE_PREFIX;
        $this->table_categories = $prefix . 'categories';
        $this->table_units      = $prefix . 'units';
        $this->table_warehouses = $prefix . 'warehouses';
        $this->product_repo     = $product_repo ?? new ProductRepository( $this->wpdb );
        $this->stock_service    = $stock_service ?? new StockService( $this->wpdb );
    }

    /**
     * Impor dari baris array atau parsing stream CSV.
     * Format header yang diharapkan:
     * sku, barcode, name, category, unit, cost_price, sell_price_retail, sell_price_wholesale, sell_price_project, min_stock, initial_stock, warehouse_code
     */
    public function import_rows( array $rows ): array {
        if ( empty( $rows ) ) {
            throw new \InvalidArgumentException( "Data impor kosong." );
        }

        $imported = 0;
        $errors   = [];

        Transaction::begin( $this->wpdb );

        try {
            foreach ( $rows as $index => $row ) {
                $line = $index + 1;
                $sku  = strtoupper( trim( $row['sku'] ?? '' ) );
                $name = trim( $row['name'] ?? '' );
                $unit_code = strtolower( trim( $row['unit'] ?? 'btg' ) );

                if ( empty( $sku ) || empty( $name ) ) {
                    $errors[] = "Baris {$line}: SKU dan nama barang wajib diisi.";
                    continue;
                }

                // Cek apakah SKU sudah ada
                if ( $this->product_repo->find_by_sku( $sku ) ) {
                    $errors[] = "Baris {$line}: SKU '{$sku}' sudah terdaftar.";
                    continue;
                }

                // Dapatkan atau buat unit
                $unit_id = $this->resolve_or_create_unit( $unit_code );

                // Dapatkan atau buat kategori
                $category_id = null;
                if ( ! empty( $row['category'] ) ) {
                    $category_id = $this->resolve_or_create_category( trim( $row['category'] ) );
                }

                $product_id = $this->product_repo->create( [
                    'sku'                  => $sku,
                    'barcode'              => ! empty( $row['barcode'] ) ? trim( $row['barcode'] ) : null,
                    'name'                 => $name,
                    'category_id'          => $category_id,
                    'base_unit_id'         => $unit_id,
                    'cost_price'           => (int) ( $row['cost_price'] ?? 0 ),
                    'sell_price_retail'    => (int) ( $row['sell_price_retail'] ?? 0 ),
                    'sell_price_wholesale' => (int) ( $row['sell_price_wholesale'] ?? 0 ),
                    'sell_price_project'   => (int) ( $row['sell_price_project'] ?? 0 ),
                    'min_stock'            => (float) ( $row['min_stock'] ?? 0 ),
                ] );

                // Inisialisasi stok awal jika ada
                $initial_stock = (float) ( $row['initial_stock'] ?? 0 );
                if ( $initial_stock > 0 ) {
                    $warehouse_code = ! empty( $row['warehouse_code'] ) ? trim( $row['warehouse_code'] ) : 'GD-UTAMA';
                    $warehouse_id = $this->resolve_warehouse_id( $warehouse_code );

                    $this->stock_service->add_stock(
                        $product_id,
                        $warehouse_id,
                        $initial_stock,
                        'initial',
                        'INIT-' . $sku,
                        $unit_id,
                        (int) ( $row['cost_price'] ?? 0 ),
                        "Saldo stok awal impor master data",
                        get_current_user_id()
                    );
                }

                $imported++;
            }

            if ( ! empty( $errors ) && $imported === 0 ) {
                Transaction::rollback( $this->wpdb );
                return [ 'success' => false, 'imported' => 0, 'errors' => $errors ];
            }

            Transaction::commit( $this->wpdb );

            AuditLogger::log(
                'import_csv_products',
                'inventory',
                'batch',
                null,
                [ 'imported_count' => $imported, 'errors_count' => count( $errors ) ]
            );

            return [
                'success'  => true,
                'imported' => $imported,
                'errors'   => $errors,
            ];
        } catch ( \Throwable $e ) {
            Transaction::rollback( $this->wpdb );
            throw $e;
        }
    }

    private function resolve_or_create_unit( string $code ): int {
        $id = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT id FROM `{$this->table_units}` WHERE code = %s",
            $code
        ) );

        if ( $id ) {
            return (int) $id;
        }

        $this->wpdb->insert(
            $this->table_units,
            [
                'code'       => $code,
                'name'       => ucfirst( $code ),
                'created_at' => current_time( 'mysql', true ),
            ],
            [ '%s', '%s', '%s' ]
        );

        return (int) $this->wpdb->insert_id;
    }

    private function resolve_or_create_category( string $name ): int {
        $slug = sanitize_title( $name );
        $id = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT id FROM `{$this->table_categories}` WHERE slug = %s",
            $slug
        ) );

        if ( $id ) {
            return (int) $id;
        }

        $this->wpdb->insert(
            $this->table_categories,
            [
                'name'       => $name,
                'slug'       => $slug,
                'created_at' => current_time( 'mysql', true ),
            ],
            [ '%s', '%s', '%s' ]
        );

        return (int) $this->wpdb->insert_id;
    }

    private function resolve_warehouse_id( string $code ): int {
        $id = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT id FROM `{$this->table_warehouses}` WHERE code = %s",
            $code
        ) );

        if ( $id ) {
            return (int) $id;
        }

        $this->wpdb->insert(
            $this->table_warehouses,
            [
                'code'       => $code,
                'name'       => "Gudang " . strtoupper( $code ),
                'is_active'  => 1,
                'created_at' => current_time( 'mysql', true ),
            ],
            [ '%s', '%s', '%d', '%s' ]
        );

        return (int) $this->wpdb->insert_id;
    }
}
