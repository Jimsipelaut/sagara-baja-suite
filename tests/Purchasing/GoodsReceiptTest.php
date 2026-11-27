<?php
namespace SagaraBaja\Tests\Purchasing;

require_once __DIR__ . '/../../tests/bootstrap.php';

use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Purchasing\Services\PurchaseOrderService;
use SagaraBaja\Modules\Purchasing\Services\GoodsReceiptService;

class GoodsReceiptTest {
	private PurchaseOrderService $po_svc;
	private GoodsReceiptService $gr_svc;
	private StockService $stock;

	public function run(): void {
		global $wpdb;
		$wpdb->reset();
		$this->seedMaster( $wpdb );

		$numbering    = new NumberingService( $wpdb );
		$suppliers    = new SupplierRepository( $wpdb );
		$this->stock  = new StockService( $wpdb );
		$this->po_svc = new PurchaseOrderService( $wpdb, $numbering, $suppliers );
		$this->gr_svc = new GoodsReceiptService( $wpdb, $numbering, $this->stock, $this->po_svc );

		$this->test_partial_receipt_status();
		$this->test_full_receipt_status();
		$this->test_receipt_adds_stock_exactly_once();
		$this->test_over_receipt_rejected();
		$this->test_receipt_on_draft_rejected();
		$this->test_receipt_on_cancelled_rejected();
		$this->test_cancel_after_receipt_rejected();
		$this->test_receipt_unknown_po_item_rejected();
		$this->test_receipt_zero_qty_rejected();
		$this->test_payable_after_receipt();
	}

	private function seedMaster( $wpdb ): void {
		$wpdb->insert( $wpdb->prefix . 'sb_units', [
			'id'   => 1,
			'code' => 'BTM',
			'name' => 'Batang',
		] );
		$wpdb->insert( $wpdb->prefix . 'sb_warehouses', [
			'id'   => 1,
			'code' => 'GDG-UTAMA',
			'name' => 'Gudang Utama',
		] );
		$wpdb->insert( $wpdb->prefix . 'sb_products', [
			'id'           => 1,
			'sku'          => 'BJA-001',
			'name'         => 'Besi Beton Polos 8mm',
			'base_unit_id' => 1,
			'status'       => 'active',
		] );
		$wpdb->insert( $wpdb->prefix . 'sb_products', [
			'id'           => 2,
			'sku'          => 'SMN-001',
			'name'         => 'Semen Tiga Roda 50kg',
			'base_unit_id' => 1,
			'status'       => 'active',
		] );
		$wpdb->insert( $wpdb->prefix . 'sb_suppliers', [
			'id'        => 1,
			'code'      => 'SUP-001',
			'name'      => 'PT Krakatau Steel',
			'phone'     => '021-999',
			'is_active' => 1,
		] );
	}

	/**
	 * Buat PO yang sudah disetujui. Mengembalikan [po_id, po_item_id_1, po_item_id_2].
	 */
	private function makeApprovedPo(): array {
		$po_id = $this->po_svc->createDraft( [
			'supplier_id'  => 1,
			'warehouse_id' => 1,
			'po_date'      => date( 'Y-m-d' ),
			'items'        => [
				[ 'product_id' => 1, 'unit_id' => 1, 'qty_ordered' => 10, 'unit_cost' => 50000 ],
				[ 'product_id' => 2, 'unit_id' => 1, 'qty_ordered' => 5,  'unit_cost' => 20000 ],
			],
		] );
		$this->po_svc->approve( $po_id );
		$items = $this->po_svc->getItems( $po_id );
		return [ $po_id, (int) $items[0]['id'], (int) $items[1]['id'] ];
	}

	private function test_partial_receipt_status(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$receipt_id = $this->gr_svc->receive( $po_id, [
			[ 'po_item_id' => $item1, 'qty_received' => 4 ],
		], 1, 'Terima sebagian' );

		$receipt = $this->gr_svc->findReceipt( $receipt_id );
		assert( str_starts_with( (string) $receipt['receipt_number'], 'GR-SB/' ), "Nomor tanda terima harus berawalan 'GR-SB/'. Dapat: {$receipt['receipt_number']}." );
		$po = $this->po_svc->find( $po_id );
		assert( $po['status'] === 'diterima_sebagian', "Status PO harus 'diterima_sebagian' setelah terima sebagian." );
		assert( $this->stock->get_balance( 1, 1 ) === 4.0, "Stok produk 1 harus 4 setelah terima 4. Saldo: {$this->stock->get_balance( 1, 1 )}." );
		assert( $this->stock->get_balance( 2, 1 ) === 0.0, "Stok produk 2 harus tetap 0." );
		$items = $this->po_svc->getItems( $po_id );
		assert( (float) $items[0]['qty_received'] === 4.0, "qty_received baris 1 harus 4." );
		assert( count( $this->po_svc->getReceipts( $po_id ) ) === 1, "PO harus punya 1 tanda terima." );
		echo "  [PASS] test_partial_receipt_status\n";
	}

	private function test_full_receipt_status(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$this->gr_svc->receive( $po_id, [
			[ 'po_item_id' => $item1, 'qty_received' => 10 ],
			[ 'po_item_id' => $item2, 'qty_received' => 5 ],
		], 1 );
		$po = $this->po_svc->find( $po_id );
		assert( $po['status'] === 'diterima', "Status PO harus 'diterima' setelah semua baris penuh." );
		assert( $this->stock->get_balance( 1, 1 ) === 14.0, "Stok produk 1 harus 14 (4 dari tes sebelumnya + 10). Saldo: {$this->stock->get_balance( 1, 1 )}." );
		echo "  [PASS] test_full_receipt_status\n";
	}

	private function test_receipt_adds_stock_exactly_once(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$before_moves = $this->stock->reconcile( 1, 1 );
		$this->gr_svc->receive( $po_id, [
			[ 'po_item_id' => $item1, 'qty_received' => 3 ],
		], 1 );
		$after = $this->stock->reconcile( 1, 1 );
		assert( $after['is_balanced'] === true, "Kartu stok harus balance setelah penerimaan." );
		assert( round( $after['moves_sum'] - $before_moves['moves_sum'], 4 ) === 3.0, "Mutasi stok harus bertambah tepat 3, bukan ganda." );
		assert( $this->stock->get_balance( 1, 1 ) === 17.0, "Saldo produk 1 harus 17 (14 + 3)." );
		echo "  [PASS] test_receipt_adds_stock_exactly_once\n";
	}

	private function test_over_receipt_rejected(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$this->gr_svc->receive( $po_id, [
			[ 'po_item_id' => $item1, 'qty_received' => 6 ],
		], 1 );
		$thrown = false;
		try {
			$this->gr_svc->receive( $po_id, [
				[ 'po_item_id' => $item1, 'qty_received' => 5 ],
			], 1 );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Penerimaan kumulatif 6 + 5 > 10 harus ditolak (over-receipt)." );
		assert( $this->stock->get_balance( 1, 1 ) === 23.0, "Stok harus tetap 23 (17 + 6) — penerimaan yang ditolak tidak boleh menambah stok." );
		$po = $this->po_svc->find( $po_id );
		assert( $po['status'] === 'diterima_sebagian', "Status PO harus tetap 'diterima_sebagian'." );
		echo "  [PASS] test_over_receipt_rejected\n";
	}

	private function test_receipt_on_draft_rejected(): void {
		$po_id = $this->po_svc->createDraft( [
			'supplier_id'  => 1,
			'warehouse_id' => 1,
			'po_date'      => date( 'Y-m-d' ),
			'items'        => [
				[ 'product_id' => 1, 'unit_id' => 1, 'qty_ordered' => 10, 'unit_cost' => 50000 ],
			],
		] );
		$items = $this->po_svc->getItems( $po_id );
		$thrown = false;
		try {
			$this->gr_svc->receive( $po_id, [
				[ 'po_item_id' => (int) $items[0]['id'], 'qty_received' => 2 ],
			], 1 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Penerimaan atas PO draft harus ditolak." );
		echo "  [PASS] test_receipt_on_draft_rejected\n";
	}

	private function test_receipt_on_cancelled_rejected(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$this->po_svc->cancel( $po_id, 'Supplier batal kirim' );
		$thrown = false;
		try {
			$this->gr_svc->receive( $po_id, [
				[ 'po_item_id' => $item1, 'qty_received' => 2 ],
			], 1 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Penerimaan atas PO batal harus ditolak." );
		echo "  [PASS] test_receipt_on_cancelled_rejected\n";
	}

	private function test_cancel_after_receipt_rejected(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$this->gr_svc->receive( $po_id, [
			[ 'po_item_id' => $item1, 'qty_received' => 2 ],
		], 1 );
		$thrown = false;
		try {
			$this->po_svc->cancel( $po_id, 'Coba batal setelah terima' );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Cancel PO setelah ada penerimaan harus ditolak." );
		echo "  [PASS] test_cancel_after_receipt_rejected\n";
	}

	private function test_receipt_unknown_po_item_rejected(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$thrown = false;
		try {
			$this->gr_svc->receive( $po_id, [
				[ 'po_item_id' => 99999, 'qty_received' => 2 ],
			], 1 );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Penerimaan dengan baris PO yang bukan milik PO ini harus ditolak." );
		echo "  [PASS] test_receipt_unknown_po_item_rejected\n";
	}

	private function test_receipt_zero_qty_rejected(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$thrown = false;
		try {
			$this->gr_svc->receive( $po_id, [
				[ 'po_item_id' => $item1, 'qty_received' => 0 ],
			], 1 );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Penerimaan dengan kuantitas 0 harus ditolak." );
		echo "  [PASS] test_receipt_zero_qty_rejected\n";
	}

	private function test_payable_after_receipt(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$this->gr_svc->receive( $po_id, [
			[ 'po_item_id' => $item1, 'qty_received' => 10 ],
			[ 'po_item_id' => $item2, 'qty_received' => 5 ],
		], 1 );
		$po = $this->po_svc->find( $po_id );
		// grand_total: 10 × 50000 + 5 × 20000 = 600000.
		assert( (int) $po['grand_total'] === 600000, "Grand total PO harus 600000." );
		assert( (int) $po['paid_amount'] === 0, "paid_amount tetap 0 di Sprint 3 (bayar hutang = Sprint 4)." );
		assert( $this->po_svc->getPayableAmount( $po_id ) === 600000, "Hutang usaha harus 600000 setelah penerimaan penuh." );
		echo "  [PASS] test_payable_after_receipt\n";
	}
}
