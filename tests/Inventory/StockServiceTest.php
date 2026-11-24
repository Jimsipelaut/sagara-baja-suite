<?php
namespace SagaraBaja\Tests\Inventory;

use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Inventory\Repositories\ProductRepository;

class StockServiceTest {
    private StockService $stock_service;
    private ProductRepository $product_repo;
    private int $test_product_id;
    private int $warehouse_1 = 1;
    private int $warehouse_2 = 2;

    public function __construct() {
    }

    public function run(): void {
        global $wpdb;
        $wpdb->reset();

        $this->stock_service = new StockService();
        $this->product_repo  = new ProductRepository();

        // Register dummy units
        $wpdb->insert( 'wp_sb_units', [ 'id' => 1, 'code' => 'btg', 'name' => 'Batang' ] );

        // Register test product
        $this->test_product_id = $this->product_repo->create( [
            'sku'                  => 'TEST-BESI-08',
            'name'                 => 'Besi Test 8mm',
            'base_unit_id'         => 1,
            'cost_price'           => 40000,
            'sell_price_retail'    => 45000,
            'sell_price_wholesale' => 43000,
            'sell_price_project'   => 42000,
            'min_stock'            => 10,
        ] );

        $this->test_add_stock_updates_balance_and_moves();
        $this->test_deduct_stock_reduces_balance();
        $this->test_deduct_stock_prevents_negative_stock();
        $this->test_transfer_stock_between_warehouses();
    }

    public function test_add_stock_updates_balance_and_moves(): void {
        $result = $this->stock_service->add_stock(
            $this->test_product_id,
            $this->warehouse_1,
            50.0,
            'initial',
            'INIT-001',
            1,
            40000,
            'Stok awal gudang'
        );

        assert( $result['success'] === true, "Add stock harus berhasil." );
        assert( $result['balance_after'] === 50.0, "Saldo akhir harus 50. Didapat: " . $result['balance_after'] );

        // Verifikasi saldo rekonsiliasi
        $rec = $this->stock_service->reconcile( $this->test_product_id, $this->warehouse_1 );
        assert( $rec['is_balanced'] === true, "Saldo dan mutasi harus seimbang." );
        assert( $rec['balance_qty'] === 50.0, "Saldo balance_qty harus 50." );
        echo "  [PASS] test_add_stock_updates_balance_and_moves\n";
    }

    public function test_deduct_stock_reduces_balance(): void {
        // Kurangi 20
        $result = $this->stock_service->deduct_stock(
            $this->test_product_id,
            $this->warehouse_1,
            20.0,
            'pos_sale',
            'POS-001',
            1,
            40000,
            'Penjualan kasir'
        );

        assert( $result['balance_after'] === 30.0, "Saldo setelah pengurangan 20 harus 30. Didapat: " . $result['balance_after'] );

        // Rekonsiliasi
        $rec = $this->stock_service->reconcile( $this->test_product_id, $this->warehouse_1 );
        assert( $rec['is_balanced'] === true, "Saldo dan mutasi harus seimbang setelah pengurangan." );
        assert( $rec['balance_qty'] === 30.0, "Saldo balance_qty harus 30." );
        echo "  [PASS] test_deduct_stock_reduces_balance\n";
    }

    public function test_deduct_stock_prevents_negative_stock(): void {
        try {
            // Coba jual 100 padahal stok tersisa hanya 30
            $this->stock_service->deduct_stock(
                $this->test_product_id,
                $this->warehouse_1,
                100.0,
                'pos_sale',
                'POS-FAIL',
                1
            );
            assert( false, "Harus melempar UnderflowException saat stok tidak mencukupi." );
        } catch ( \UnderflowException $e ) {
            assert( true, "Penolakan stok negatif bekerja." );
        }
        echo "  [PASS] test_deduct_stock_prevents_negative_stock\n";
    }

    public function test_transfer_stock_between_warehouses(): void {
        // Transfer 15 dari Warehouse 1 ke Warehouse 2
        $result = $this->stock_service->transfer_stock(
            $this->test_product_id,
            $this->warehouse_1,
            $this->warehouse_2,
            15.0,
            1,
            'Pindah display toko'
        );

        assert( $result['success'] === true, "Transfer stok harus sukses." );
        assert( $result['out']['balance_after'] === 15.0, "Sisa di gudang 1 harus 15. Didapat: " . $result['out']['balance_after'] );
        assert( $result['in']['balance_after'] === 15.0, "Saldo di gudang 2 harus 15. Didapat: " . $result['in']['balance_after'] );

        // Rekonsiliasi kedua gudang
        $rec1 = $this->stock_service->reconcile( $this->test_product_id, $this->warehouse_1 );
        $rec2 = $this->stock_service->reconcile( $this->test_product_id, $this->warehouse_2 );
        assert( $rec1['is_balanced'] === true, "Gudang 1 harus balance." );
        assert( $rec2['is_balanced'] === true, "Gudang 2 harus balance." );
        echo "  [PASS] test_transfer_stock_between_warehouses\n";
    }
}
