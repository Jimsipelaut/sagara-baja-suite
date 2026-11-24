<?php
namespace SagaraBaja\Tests\Inventory;

use SagaraBaja\Modules\Inventory\Services\StockOpnameService;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Inventory\Repositories\ProductRepository;

class StockOpnameTest {
    private StockOpnameService $opname_service;
    private StockService $stock_service;
    private ProductRepository $product_repo;
    private int $product_id;
    private int $warehouse_id = 1;

    public function __construct() {
    }

    public function run(): void {
        global $wpdb;
        $wpdb->reset();

        $this->stock_service   = new StockService();
        $this->opname_service  = new StockOpnameService();
        $this->product_repo    = new ProductRepository();

        $wpdb->insert( 'wp_sb_units', [ 'id' => 1, 'code' => 'btg', 'name' => 'Batang' ] );

        $this->product_id = $this->product_repo->create( [
            'sku'          => 'OPN-PROD-01',
            'name'         => 'Besi Opname Test',
            'base_unit_id' => 1,
        ] );

        // Set initial stock 50
        $this->stock_service->add_stock( $this->product_id, $this->warehouse_id, 50.0, 'initial' );

        $this->test_create_draft_and_approve_opname();
    }

    public function test_create_draft_and_approve_opname(): void {
        // Sistem mencatat 50, tetapi fisik hanya 48 (-2 selisih)
        $items = [
            [
                'product_id'   => $this->product_id,
                'system_qty'   => 50.0,
                'physical_qty' => 48.0,
                'notes'        => '2 batang rusak/bengkok',
            ],
        ];

        $opname_id = $this->opname_service->create_draft( $this->warehouse_id, $items, 'Opname Bulanan' );
        assert( $opname_id > 0, "ID Opname draft harus valid." );

        // Approve opname
        $approved = $this->opname_service->approve( $opname_id, 1 );
        assert( $approved === true, "Approval opname harus berhasil." );

        // Saldo sekarang harus menjadi 48
        $rec = $this->stock_service->reconcile( $this->product_id, $this->warehouse_id );
        assert( $rec['is_balanced'] === true, "Kartu stok harus balance setelah opname." );
        assert( $rec['balance_qty'] === 48.0, "Saldo fisik harus 48. Didapat: " . $rec['balance_qty'] );
        echo "  [PASS] test_create_draft_and_approve_opname\n";
    }
}
