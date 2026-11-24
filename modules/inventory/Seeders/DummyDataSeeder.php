<?php
namespace SagaraBaja\Modules\Inventory\Seeders;

use SagaraBaja\Modules\Inventory\Repositories\ProductRepository;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Inventory\Services\UnitConversionService;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Seeder Master Data Dummy Toko Material Sagara Baja.
 * Mengisi kategori, satuan, gudang, konversi, produk material, dan saldo awal.
 */
class DummyDataSeeder {
    private \wpdb $wpdb;
    private string $prefix;
    private ProductRepository $product_repo;
    private StockService $stock_service;
    private UnitConversionService $conversion_service;

    public function __construct( ?\wpdb $wpdb = null ) {
        global $wpdb;
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $this->prefix = $this->wpdb->prefix . SB_TABLE_PREFIX;
        $this->product_repo       = new ProductRepository( $this->wpdb );
        $this->stock_service      = new StockService( $this->wpdb );
        $this->conversion_service = new UnitConversionService( $this->wpdb );
    }

    public function run(): array {
        $this->wpdb->query( "START TRANSACTION" );

        try {
            // 1. Gudang
            $wh_utama = $this->seed_warehouse( 'GD-UTAMA', 'Gudang Utama Setu', 'Jl. Raya Setu No. 88, Bekasi' );
            $wh_toko  = $this->seed_warehouse( 'GD-TOKO', 'Gudang Toko & Display', 'Area Depan Toko Sagara Baja' );

            // 2. Satuan
            $u_btg  = $this->seed_unit( 'btg', 'Batang' );
            $u_ikat = $this->seed_unit( 'ikat', 'Ikat' );
            $u_zak  = $this->seed_unit( 'zak', 'Zak' );
            $u_ton  = $this->seed_unit( 'ton', 'Ton' );
            $u_kg   = $this->seed_unit( 'kg', 'Kilogram' );
            $u_lbr  = $this->seed_unit( 'lbr', 'Lembar' );
            $u_m3   = $this->seed_unit( 'm3', 'Meter Kubik' );

            // 3. Kategori
            $c_besi = $this->seed_category( 'Besi & Baja', 'besi-baja' );
            $c_baja = $this->seed_category( 'Baja Ringan & Atap', 'baja-ringan-atap' );
            $c_semen= $this->seed_category( 'Semen & Pasir', 'semen-pasir' );
            $c_kayu = $this->seed_category( 'Kayu & Plywood', 'kayu-plywood' );

            // 4. Konversi Global (1 Ton = 20 Zak @ 50kg, 1 Ton = 1000 Kg)
            $this->conversion_service->register_conversion( $u_ton, $u_zak, 20.0 );
            $this->conversion_service->register_conversion( $u_ton, $u_kg, 1000.0 );

            // 5. Produk Dummy Material Bangunan
            $products_data = [
                [
                    'sku'                  => 'SB-BESI-08',
                    'barcode'              => '899100108001',
                    'name'                 => 'Besi Beton 8mm Polos SNI (Dummy)',
                    'category_id'          => $c_besi,
                    'base_unit_id'         => $u_btg,
                    'cost_price'           => 42000,
                    'sell_price_retail'    => 48000,
                    'sell_price_wholesale' => 46000,
                    'sell_price_project'   => 44500,
                    'min_stock'            => 100,
                    'initial_qty'          => 500,
                    'conversions'          => [ [ 'from' => $u_ikat, 'to' => $u_btg, 'ratio' => 25.0 ] ], // 1 ikat = 25 btg
                ],
                [
                    'sku'                  => 'SB-BESI-10',
                    'barcode'              => '899100110001',
                    'name'                 => 'Besi Beton 10mm Ulir SNI (Dummy)',
                    'category_id'          => $c_besi,
                    'base_unit_id'         => $u_btg,
                    'cost_price'           => 65000,
                    'sell_price_retail'    => 74000,
                    'sell_price_wholesale' => 71000,
                    'sell_price_project'   => 69000,
                    'min_stock'            => 80,
                    'initial_qty'          => 400,
                    'conversions'          => [ [ 'from' => $u_ikat, 'to' => $u_btg, 'ratio' => 20.0 ] ], // 1 ikat = 20 btg
                ],
                [
                    'sku'                  => 'SB-CNL-C75',
                    'barcode'              => '899100207501',
                    'name'                 => 'Kanal C75 Baja Ringan 0.75mm (Dummy)',
                    'category_id'          => $c_baja,
                    'base_unit_id'         => $u_btg,
                    'cost_price'           => 72000,
                    'sell_price_retail'    => 82000,
                    'sell_price_wholesale' => 79000,
                    'sell_price_project'   => 76000,
                    'min_stock'            => 50,
                    'initial_qty'          => 350,
                    'conversions'          => [],
                ],
                [
                    'sku'                  => 'SB-RNG-045',
                    'barcode'              => '899100204501',
                    'name'                 => 'Reng Baja Ringan 0.45mm (Dummy)',
                    'category_id'          => $c_baja,
                    'base_unit_id'         => $u_btg,
                    'cost_price'           => 34000,
                    'sell_price_retail'    => 40000,
                    'sell_price_wholesale' => 38000,
                    'sell_price_project'   => 36500,
                    'min_stock'            => 50,
                    'initial_qty'          => 300,
                    'conversions'          => [],
                ],
                [
                    'sku'                  => 'SB-SMN-TR50',
                    'barcode'              => '899100305001',
                    'name'                 => 'Semen Tiga Roda 50kg (Dummy)',
                    'category_id'          => $c_semen,
                    'base_unit_id'         => $u_zak,
                    'cost_price'           => 62000,
                    'sell_price_retail'    => 68000,
                    'sell_price_wholesale' => 66000,
                    'sell_price_project'   => 64500,
                    'min_stock'            => 50,
                    'initial_qty'          => 200,
                    'conversions'          => [],
                ],
                [
                    'sku'                  => 'SB-PSR-PSG',
                    'barcode'              => null,
                    'name'                 => 'Pasir Pasang Bangunan Super (Dummy)',
                    'category_id'          => $c_semen,
                    'base_unit_id'         => $u_m3,
                    'cost_price'           => 240000,
                    'sell_price_retail'    => 290000,
                    'sell_price_wholesale' => 275000,
                    'sell_price_project'   => 260000,
                    'min_stock'            => 10,
                    'initial_qty'          => 50,
                    'conversions'          => [],
                ],
                [
                    'sku'                  => 'SB-SPD-035',
                    'barcode'              => '899100403501',
                    'name'                 => 'Atap Spandek Pasir 0.35mm (Dummy)',
                    'category_id'          => $c_baja,
                    'base_unit_id'         => $u_lbr,
                    'cost_price'           => 45000,
                    'sell_price_retail'    => 54000,
                    'sell_price_wholesale' => 51000,
                    'sell_price_project'   => 48000,
                    'min_stock'            => 30,
                    'initial_qty'          => 150,
                    'conversions'          => [],
                ],
                [
                    'sku'                  => 'SB-PLW-09MM',
                    'barcode'              => '899100509001',
                    'name'                 => 'Triplek Meranti Cor 9mm 122x244 (Dummy)',
                    'category_id'          => $c_kayu,
                    'base_unit_id'         => $u_lbr,
                    'cost_price'           => 85000,
                    'sell_price_retail'    => 98000,
                    'sell_price_wholesale' => 94000,
                    'sell_price_project'   => 90000,
                    'min_stock'            => 20,
                    'initial_qty'          => 120,
                    'conversions'          => [],
                ],
            ];

            $seeded_products = [];
            foreach ( $products_data as $p ) {
                $existing = $this->product_repo->find_by_sku( $p['sku'] );
                if ( $existing ) {
                    $seeded_products[] = $existing['id'];
                    continue;
                }

                $pid = $this->product_repo->create( $p );

                // Daftarkan konversi spesifik barang jika ada
                foreach ( $p['conversions'] as $conv ) {
                    $this->conversion_service->register_conversion(
                        $conv['from'],
                        $conv['to'],
                        $conv['ratio'],
                        $pid
                    );
                }

                // Inisialisasi stok awal di Gudang Utama
                if ( ! empty( $p['initial_qty'] ) ) {
                    $this->stock_service->add_stock(
                        $pid,
                        $wh_utama,
                        (float) $p['initial_qty'],
                        'initial',
                        'INIT-' . $p['sku'],
                        $p['base_unit_id'],
                        $p['cost_price'],
                        'Saldo stok awal dummy data'
                    );
                }

                $seeded_products[] = $pid;
            }

            $this->wpdb->query( "COMMIT" );

            return [
                'status'   => 'success',
                'products' => $seeded_products,
            ];
        } catch ( \Throwable $e ) {
            $this->wpdb->query( "ROLLBACK" );
            throw $e;
        }
    }

    private function seed_warehouse( string $code, string $name, string $address ): int {
        $table = $this->prefix . 'warehouses';
        $id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM `{$table}` WHERE code = %s", $code ) );
        if ( $id ) {
            return (int) $id;
        }

        $this->wpdb->insert(
            $table,
            [
                'code'       => $code,
                'name'       => $name,
                'address'    => $address,
                'is_active'  => 1,
                'created_at' => current_time( 'mysql', true ),
            ],
            [ '%s', '%s', '%s', '%d', '%s' ]
        );

        return (int) $this->wpdb->insert_id;
    }

    private function seed_unit( string $code, string $name ): int {
        $table = $this->prefix . 'units';
        $id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM `{$table}` WHERE code = %s", $code ) );
        if ( $id ) {
            return (int) $id;
        }

        $this->wpdb->insert(
            $table,
            [
                'code'       => $code,
                'name'       => $name,
                'created_at' => current_time( 'mysql', true ),
            ],
            [ '%s', '%s', '%s' ]
        );

        return (int) $this->wpdb->insert_id;
    }

    private function seed_category( string $name, string $slug ): int {
        $table = $this->prefix . 'categories';
        $id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM `{$table}` WHERE slug = %s", $slug ) );
        if ( $id ) {
            return (int) $id;
        }

        $this->wpdb->insert(
            $table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'created_at' => current_time( 'mysql', true ),
            ],
            [ '%s', '%s', '%s' ]
        );

        return (int) $this->wpdb->insert_id;
    }
}
