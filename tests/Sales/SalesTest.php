<?php
namespace SagaraBaja\Tests\Sales;

require_once __DIR__ . '/../../tests/bootstrap.php';

use SagaraBaja\Modules\Sales\Repositories\CustomerRepository;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;
use SagaraBaja\Modules\Sales\Services\SaleService;
use SagaraBaja\Modules\Sales\Services\ShiftService;
use SagaraBaja\Modules\Sales\Services\PdfInvoiceService;
use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Inventory\Services\StockService;

class CustomerRepositoryTest {
	public function run(): void {
		global $wpdb;
		$wpdb->reset();
		$repo = new CustomerRepository( $wpdb );

		$id = $this->test_create_customer( $repo );
		$this->test_duplicate_code_rejected( $repo );
		$this->test_empty_name_rejected( $repo );
		$this->test_find_customer( $repo, $id );
		$this->test_update_customer( $repo, $id );
	}

	private function test_create_customer( CustomerRepository $repo ): int {
		$id = $repo->create( [ 'code' => 'CUST-001', 'name' => 'Budi Santoso', 'phone' => '0812', 'credit_limit' => 5000000 ] );
		assert( $id > 0, "ID harus positif setelah create." );
		echo "  [PASS] test_create_customer\n";
		return $id;
	}

	private function test_duplicate_code_rejected( CustomerRepository $repo ): void {
		$thrown = false;
		try {
			$repo->create( [ 'code' => 'CUST-001', 'name' => 'Duplikat' ] );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Duplikat kode pelanggan harus ditolak." );
		echo "  [PASS] test_duplicate_code_rejected\n";
	}

	private function test_empty_name_rejected( CustomerRepository $repo ): void {
		$thrown = false;
		try {
			$repo->create( [ 'code' => 'CUST-002', 'name' => '' ] );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Nama kosong harus ditolak." );
		echo "  [PASS] test_empty_name_rejected\n";
	}

	private function test_find_customer( CustomerRepository $repo, int $id ): void {
		$cust = $repo->find( $id );
		assert( $cust !== null, "Pelanggan ID {$id} harus ditemukan." );
		assert( $cust['name'] === 'Budi Santoso', "Nama harus Budi Santoso, dapat: {$cust['name']}." );
		echo "  [PASS] test_find_customer\n";
	}

	private function test_update_customer( CustomerRepository $repo, int $id ): void {
		$repo->update( $id, [ 'name' => 'Budi Santoso Updated', 'phone' => '0813' ] );
		$updated = $repo->find( $id );
		assert( $updated['name'] === 'Budi Santoso Updated', "Nama harus berubah setelah update." );
		echo "  [PASS] test_update_customer\n";
	}
}

class SupplierRepositoryTest {
	public function run(): void {
		global $wpdb;
		$wpdb->reset();
		$repo = new SupplierRepository( $wpdb );

		$id = $repo->create( [ 'code' => 'SUP-001', 'name' => 'PT Krakatau Steel', 'phone' => '021-999' ] );
		assert( $id > 0, "ID supplier harus positif." );
		echo "  [PASS] test_create_supplier\n";

		$found = $repo->find( $id );
		assert( $found['name'] === 'PT Krakatau Steel', "Nama supplier harus cocok." );
		echo "  [PASS] test_find_supplier\n";

		$thrown = false;
		try {
			$repo->create( [ 'code' => 'SUP-001', 'name' => 'Duplikat' ] );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Duplikat kode supplier harus ditolak." );
		echo "  [PASS] test_duplicate_supplier_code_rejected\n";
	}
}

class ShiftServiceTest {
	public function run(): void {
		global $wpdb;
		$wpdb->reset();
		$svc = new ShiftService( $wpdb );

		$id = $this->test_open_shift( $svc );
		$this->test_duplicate_shift_rejected( $svc );
		$this->test_close_shift( $svc, $id );
		$this->test_close_already_closed( $svc, $id );
	}

	private function test_open_shift( ShiftService $svc ): int {
		$id = $svc->open( 1, 1, 500000 );
		assert( $id > 0, "ID shift harus positif setelah buka." );
		echo "  [PASS] test_open_shift\n";
		return $id;
	}

	private function test_duplicate_shift_rejected( ShiftService $svc ): void {
		$thrown = false;
		try {
			$svc->open( 1, 1, 100000 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Shift ganda pada kasir+gudang yang sama harus ditolak." );
		echo "  [PASS] test_duplicate_shift_rejected\n";
	}

	private function test_close_shift( ShiftService $svc, int $id ): void {
		$svc->close( $id, 450000, 'Tutup normal' );
		$shift = $svc->find( $id );
		assert( $shift['status'] === 'closed', "Status shift harus 'closed' setelah ditutup." );
		assert( (int) $shift['closing_cash'] === 450000, "closing_cash harus 450000." );
		echo "  [PASS] test_close_shift\n";
	}

	private function test_close_already_closed( ShiftService $svc, int $id ): void {
		$thrown = false;
		try {
			$svc->close( $id, 0 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Menutup shift yang sudah ditutup harus gagal." );
		echo "  [PASS] test_close_already_closed\n";
	}
}

class SaleServiceTest {
	public function run(): void {
		global $wpdb;
		$wpdb->reset();

		// Seed satuan dan produk awal agar StockService bisa validasi base_unit
		$wpdb->insert( $wpdb->prefix . 'sb_units', [
			'id'   => 1,
			'code' => 'BTM',
			'name' => 'Batang',
		] );
		$wpdb->insert( $wpdb->prefix . 'sb_products', [
			'id'           => 1,
			'sku'          => 'BJA-001',
			'name'         => 'Besi Beton Polos 8mm',
			'base_unit_id' => 1,
			'status'       => 'active',
		] );

		$numbering = new NumberingService( $wpdb );
		$stock     = new StockService( $wpdb );
		$svc       = new SaleService( $wpdb, $numbering, $stock );

		// Seed stok awal melalui StockService agar saldo dan mutasi selalu balance
		$stock->add_stock( 1, 1, 100.0, 'initial_stock', 'SEED/001', 1, 45000, 'Stok Awal Testing' );

		$this->test_create_draft( $svc );
		$this->test_publish_reduces_stock( $svc, $stock );
		$this->test_add_payment_lunas( $svc );
		$this->test_void_draft_ok( $svc );
		$this->test_void_lunas_rejected( $svc );
		$this->test_empty_items_rejected( $svc );
		$this->test_negative_total_rejected( $svc );
	}

	private function base_data(): array {
		return [
			'invoice_type'    => 'non_resmi',
			'sale_date'       => date( 'Y-m-d' ),
			'warehouse_id'    => 1,
			'cashier_user_id' => 1,
			'items'           => [
				[ 'product_id' => 1, 'unit_id' => 1, 'qty' => 2, 'unit_price' => 50000, 'discount_amount' => 0 ],
			],
		];
	}

	private function test_create_draft( SaleService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		assert( $id > 0, "ID sale draft harus positif." );
		$sale = $svc->find( $id );
		assert( $sale['status'] === 'draft', "Status awal harus 'draft'." );
		assert( (int) $sale['grand_total'] === 100000, "Grand total harus 100000 (2 × 50000). Dapat: {$sale['grand_total']}." );
		echo "  [PASS] test_create_draft\n";
	}

	private function test_publish_reduces_stock( SaleService $svc, StockService $stock ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->publish( $id );
		$sale = $svc->find( $id );
		assert( $sale['status'] === 'terbit', "Status harus 'terbit' setelah publish." );
		$rec = $stock->reconcile( 1, 1 );
		assert( $rec['is_balanced'] === true, "Kartu stok harus balance setelah penjualan." );
		echo "  [PASS] test_publish_reduces_stock\n";
	}

	private function test_add_payment_lunas( SaleService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->publish( $id );
		$svc->addPayment( $id, [ 'method' => 'tunai', 'amount' => 100000, 'created_by' => 1 ] );
		$sale = $svc->find( $id );
		assert( $sale['status'] === 'lunas', "Status harus 'lunas' setelah bayar penuh." );
		echo "  [PASS] test_add_payment_lunas\n";
	}

	private function test_void_draft_ok( SaleService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->void( $id, 'Salah input', 1 );
		$sale = $svc->find( $id );
		assert( $sale['status'] === 'batal', "Status harus 'batal' setelah void draft." );
		echo "  [PASS] test_void_draft_ok\n";
	}

	private function test_void_lunas_rejected( SaleService $svc ): void {
		$id = $svc->createDraft( $this->base_data() );
		$svc->publish( $id );
		$svc->addPayment( $id, [ 'method' => 'tunai', 'amount' => 100000, 'created_by' => 1 ] );
		$thrown = false;
		try {
			$svc->void( $id, 'Coba batal transaksi lunas', 1 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Void dokumen lunas harus ditolak." );
		echo "  [PASS] test_void_lunas_rejected\n";
	}

	private function test_empty_items_rejected( SaleService $svc ): void {
		$thrown = false;
		$data   = $this->base_data();
		$data['items'] = [];
		try {
			$svc->createDraft( $data );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Draft tanpa baris barang harus ditolak." );
		echo "  [PASS] test_empty_items_rejected\n";
	}

	private function test_negative_total_rejected( SaleService $svc ): void {
		$thrown = false;
		$data   = $this->base_data();
		$data['discount_amount'] = 999999;
		try {
			$svc->createDraft( $data );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Total negatif akibat diskon berlebih harus ditolak." );
		echo "  [PASS] test_negative_total_rejected\n";
	}
}

class PdfInvoiceServiceTest {
	public function run(): void {
		$svc = new PdfInvoiceService();

		$sale = [
			'doc_number'      => 'NTA-0001',
			'invoice_type'    => 'non_resmi',
			'sale_date'       => date( 'Y-m-d' ),
			'customer_id'     => null,
			'subtotal'        => 100000,
			'discount_amount' => 0,
			'tax_amount'      => 0,
			'grand_total'     => 100000,
			'paid_amount'     => 100000,
			'status'          => 'lunas',
		];
		$items = [[
			'product_id'      => 1,
			'product_name'    => 'Besi Beton 8mm',
			'unit_id'         => 1,
			'unit_name'       => 'batang',
			'qty'             => 2,
			'unit_price'      => 50000,
			'discount_amount' => 0,
			'line_total'      => 100000,
		]];
		$store = [ 'store_name' => 'Sagara Baja', 'store_address' => 'Bekasi', 'store_phone' => '021-123' ];

		$html = $svc->render( $sale, $items, [], $store );
		assert( str_contains( $html, 'NTA-0001' ), "HTML harus memuat nomor dokumen." );
		assert( str_contains( $html, 'NOTA' ),     "Non-resmi harus berjudul NOTA." );
		assert( str_contains( $html, 'Besi Beton 8mm' ), "Nama produk harus muncul di invoice." );
		echo "  [PASS] test_render_nota\n";

		// Invoice resmi: harus ada blok tanda tangan
		$sale_resmi = array_merge( $sale, [ 'doc_number' => 'INV-0001', 'invoice_type' => 'resmi' ] );
		$html_resmi = $svc->render( $sale_resmi, $items, [], $store );
		assert( str_contains( $html_resmi, 'INVOICE' ),  "Invoice resmi harus berjudul INVOICE." );
		assert( str_contains( $html_resmi, 'Penerima' ), "Invoice resmi harus ada blok tanda tangan." );
		echo "  [PASS] test_render_invoice_resmi\n";
	}
}
