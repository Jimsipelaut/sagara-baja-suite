<?php
namespace SagaraBaja\Tests\Inventory;

use SagaraBaja\Modules\Inventory\Seeders\DummyDataSeeder;
use SagaraBaja\Modules\Inventory\Repositories\ProductRepository;
use SagaraBaja\Modules\Inventory\Services\StockService;

class DummyDataSeederTest {
    public function __construct() {
    }

    public function run(): void {
        global $wpdb;
        $wpdb->reset();
        $this->test_seeder_populates_products_and_initial_stock();
    }

    public function test_seeder_populates_products_and_initial_stock(): void {
        $seeder = new DummyDataSeeder();
        $result = $seeder->run();

        assert( $result['status'] === 'success', "Seeder harus berhasil." );
        assert( count( $result['products'] ) === 30, "Harus men-seed 30 jenis barang dummy material. Didapat: " . count( $result['products'] ) );

        $repo = new ProductRepository();
        $besi8 = $repo->find_by_sku( 'SB-BESI-08' );
        assert( $besi8 !== null, "Besi Beton 8mm harus ada." );
        assert( (int) $besi8['sell_price_retail'] === 48000, "Harga eceran Besi Beton 8mm harus 48.000." );

        // Verifikasi saldo stok di Gudang Utama
        $stock_service = new StockService();
        $rec = $stock_service->reconcile( (int) $besi8['id'], 11 ); // ID warehouse pertama yang di-seed
        assert( $rec['is_balanced'] === true, "Kartu stok seeder harus balance." );
        echo "  [PASS] test_seeder_populates_products_and_initial_stock\n";
    }
}
