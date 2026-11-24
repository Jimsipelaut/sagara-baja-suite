<?php
namespace SagaraBaja\Tests\Inventory;

use SagaraBaja\Modules\Inventory\Repositories\ProductRepository;

class ProductRepositoryTest {
    private ProductRepository $repo;

    public function __construct() {
    }

    public function run(): void {
        global $wpdb;
        $wpdb->reset();
        $this->repo = new ProductRepository();

        $wpdb->insert( 'wp_sb_units', [ 'id' => 1, 'code' => 'btg', 'name' => 'Batang' ] );

        $this->test_create_product_success();
        $this->test_duplicate_sku_throws_exception();
        $this->test_pricing_tiers_integrity();
    }

    public function test_create_product_success(): void {
        $id = $this->repo->create( [
            'sku'                  => 'SB-CNL-075',
            'barcode'              => '1234567890',
            'name'                 => 'Kanal C Baja Ringan',
            'base_unit_id'         => 1,
            'cost_price'           => 70000,
            'sell_price_retail'    => 80000,
            'sell_price_wholesale' => 77000,
            'sell_price_project'   => 75000,
            'min_stock'            => 20,
        ] );

        assert( $id > 0, "ID produk baru harus > 0." );

        $product = $this->repo->find_by_sku( 'SB-CNL-075' );
        assert( $product !== null, "Produk harus ditemukan via SKU." );
        assert( $product['name'] === 'Kanal C Baja Ringan', "Nama produk harus sesuai." );
        echo "  [PASS] test_create_product_success\n";
    }

    public function test_duplicate_sku_throws_exception(): void {
        try {
            $this->repo->create( [
                'sku'          => 'SB-CNL-075',
                'name'         => 'Kanal C Duplikat',
                'base_unit_id' => 1,
            ] );
            assert( false, "SKU duplikat harus ditolak dengan InvalidArgumentException." );
        } catch ( \InvalidArgumentException $e ) {
            assert( true );
        }
        echo "  [PASS] test_duplicate_sku_throws_exception\n";
    }

    public function test_pricing_tiers_integrity(): void {
        $product = $this->repo->find_by_sku( 'SB-CNL-075' );
        $id = (int) $product['id'];

        $retail    = $this->repo->get_price_for_tier( $id, 'retail' );
        $wholesale = $this->repo->get_price_for_tier( $id, 'wholesale' );
        $project   = $this->repo->get_price_for_tier( $id, 'project' );

        assert( $retail === 80000, "Harga eceran harus 80.000. Didapat: " . $retail );
        assert( $wholesale === 77000, "Harga grosir harus 77.000. Didapat: " . $wholesale );
        assert( $project === 75000, "Harga proyek harus 75.000. Didapat: " . $project );
        echo "  [PASS] test_pricing_tiers_integrity\n";
    }
}
