<?php
namespace SagaraBaja\Tests\Purchasing;

require_once __DIR__ . '/../../tests/bootstrap.php';

use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Purchasing\Services\PurchaseOrderService;

class PurchaseOrderTest {
	public function run(): void {
		global $wpdb;
		$wpdb->reset();
		$this->seedMaster( $wpdb );

		$numbering = new NumberingService( $wpdb );
		$suppliers = new SupplierRepository( $wpdb );
		$svc       = new PurchaseOrderService( $wpdb, $numbering, $suppliers );
		$stock     = new StockService( $wpdb );

		$this->test_create_draft_totals( $svc );
		$this->test_create_draft_no_stock_change( $svc, $stock );
		$this->test_approve( $svc );
		$this->test_approve_twice_rejected( $svc );
		$this->test_cancel_draft_ok( $svc );
		$this->test_cancel_without_reason_rejected( $svc );
		$this->test_cancel_approved_ok( $svc );
		$this->test_approve_cancelled_rejected( $svc );
		$this->test_empty_items_rejected( $svc );
		$this->test_negative_total_rejected( $svc );
		$this->test_unknown_supplier_rejected( $svc );
		$this->test_unknown_product_rejected( $svc );
		$this->test_zero_qty_rejected( $svc );
		$this->test_get_payable_amount( $svc );
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
		// Insert langsung dengan ID eksplisit agar base_data() bisa memakai supplier_id 1.
		$wpdb->insert( $wpdb->prefix . 'sb_suppliers', [
			'id'        => 1,
			'code'      => 'SUP-001',
			'name'      => 'PT Krakatau Steel',
			'phone'     => '021-999',
			'is_active' => 1,
		] );
	}

	private function base_data(): array {
		return [
			'supplier_id'     => 1,
			'warehouse_id'    => 1,
			'po_date'         => date( 'Y-m-d' ),
			'expected_date'   => date( 'Y-m-d', strtotime( '+7 days' ) ),
			'discount_amount' => 40000,
			'items'           => [
				[ 'product_id' => 1, 'unit_id' => 1, 'qty_ordered' => 10, 'unit_cost' => 50000, 'discount_amount' => 0 ],
				[ 'product_id' => 2, 'unit_id' => 1, 'qty_ordered' => 5,  'unit_cost' => 20000, 'discount_amount' => 10000 ],
			],
		];
	}

	private function test_create_draft_totals( PurchaseOrderService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		assert( $id > 0, "ID PO draft harus positif." );
		$po = $svc->find( $id );
		assert( $po['status'] === 'draft', "Status awal harus 'draft'." );
		assert( str_starts_with( (string) $po['doc_number'], 'PO-SB/' ), "Nomor PO harus berawalan 'PO-SB/'. Dapat: {$po['doc_number']}." );
		// Baris 1: 10 × 50000 = 500000. Baris 2: 5 × 20000 − 10000 = 90000.
		assert( (int) $po['subtotal'] === 590000, "Subtotal harus 590000. Dapat: {$po['subtotal']}." );
		assert( (int) $po['grand_total'] === 550000, "Grand total harus 550000 (590000 − 40000). Dapat: {$po['grand_total']}." );
		assert( (int) $po['tax_amount'] === 0, "Pajak harus 0 (non-PKP)." );
		assert( (int) $po['paid_amount'] === 0, "paid_amount awal harus 0." );
		$items = $svc->getItems( $id );
		assert( count( $items ) === 2, "PO harus punya 2 baris barang." );
		assert( (float) $items[0]['qty_received'] === 0.0, "qty_received awal harus 0." );
		assert( (int) $items[1]['line_total'] === 90000, "line_total baris 2 harus 90000." );
		echo "  [PASS] test_create_draft_totals\n";
	}

	private function test_create_draft_no_stock_change( PurchaseOrderService $svc, StockService $stock ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->approve( $id );
		assert( $stock->get_balance( 1, 1 ) === 0.0, "Stok tidak boleh berubah saat PO dibuat/disujui. Saldo: {$stock->get_balance( 1, 1 )}." );
		assert( $stock->get_balance( 2, 1 ) === 0.0, "Stok produk 2 juga harus tetap 0." );
		echo "  [PASS] test_create_draft_no_stock_change\n";
	}

	private function test_approve( PurchaseOrderService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->approve( $id );
		$po = $svc->find( $id );
		assert( $po['status'] === 'disetujui', "Status harus 'disetujui' setelah approve." );
		echo "  [PASS] test_approve\n";
	}

	private function test_approve_twice_rejected( PurchaseOrderService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->approve( $id );
		$thrown = false;
		try {
			$svc->approve( $id );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Approve kedua kalinya harus ditolak." );
		echo "  [PASS] test_approve_twice_rejected\n";
	}

	private function test_cancel_draft_ok( PurchaseOrderService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->cancel( $id, 'Supplier tidak bisa kirim' );
		$po = $svc->find( $id );
		assert( $po['status'] === 'batal', "Status harus 'batal' setelah cancel draft." );
		assert( $po['cancel_reason'] === 'Supplier tidak bisa kirim', "Alasan batal harus tersimpan." );
		echo "  [PASS] test_cancel_draft_ok\n";
	}

	private function test_cancel_without_reason_rejected( PurchaseOrderService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$thrown = false;
		try {
			$svc->cancel( $id, '   ' );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Cancel tanpa alasan harus ditolak." );
		echo "  [PASS] test_cancel_without_reason_rejected\n";
	}

	private function test_cancel_approved_ok( PurchaseOrderService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->approve( $id );
		$svc->cancel( $id, 'Harga berubah' );
		$po = $svc->find( $id );
		assert( $po['status'] === 'batal', "PO yang sudah disetujui harus bisa dibatalkan." );
		echo "  [PASS] test_cancel_approved_ok\n";
	}

	private function test_approve_cancelled_rejected( PurchaseOrderService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->cancel( $id, 'Batal' );
		$thrown = false;
		try {
			$svc->approve( $id );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Approve PO yang sudah batal harus ditolak." );
		echo "  [PASS] test_approve_cancelled_rejected\n";
	}

	private function test_empty_items_rejected( PurchaseOrderService $svc ): void {
		$thrown = false;
		$data   = $this->base_data();
		$data['items'] = [];
		try {
			$svc->createDraft( $data );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "PO tanpa baris barang harus ditolak." );
		echo "  [PASS] test_empty_items_rejected\n";
	}

	private function test_negative_total_rejected( PurchaseOrderService $svc ): void {
		$thrown = false;
		$data   = $this->base_data();
		$data['discount_amount'] = 99999999;
		try {
			$svc->createDraft( $data );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Total negatif akibat diskon berlebih harus ditolak (totals mismatch)." );
		echo "  [PASS] test_negative_total_rejected\n";
	}

	private function test_unknown_supplier_rejected( PurchaseOrderService $svc ): void {
		$thrown = false;
		$data   = $this->base_data();
		$data['supplier_id'] = 999;
		try {
			$svc->createDraft( $data );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Supplier yang tidak ada harus ditolak." );
		echo "  [PASS] test_unknown_supplier_rejected\n";
	}

	private function test_unknown_product_rejected( PurchaseOrderService $svc ): void {
		$thrown = false;
		$data   = $this->base_data();
		$data['items'][0]['product_id'] = 999;
		try {
			$svc->createDraft( $data );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Produk yang tidak ada harus ditolak." );
		echo "  [PASS] test_unknown_product_rejected\n";
	}

	private function test_zero_qty_rejected( PurchaseOrderService $svc ): void {
		$thrown = false;
		$data   = $this->base_data();
		$data['items'][0]['qty_ordered'] = 0;
		try {
			$svc->createDraft( $data );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Kuantitas pesan 0 harus ditolak." );
		echo "  [PASS] test_zero_qty_rejected\n";
	}

	private function test_get_payable_amount( PurchaseOrderService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$payable = $svc->getPayableAmount( $id );
		assert( $payable === 550000, "Hutang usaha harus 550000 (= grand_total − paid_amount 0). Dapat: {$payable}." );
		echo "  [PASS] test_get_payable_amount\n";
	}
}
