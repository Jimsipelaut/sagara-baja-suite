<?php
namespace SagaraBaja\Tests\Purchasing;

require_once __DIR__ . '/../../tests/bootstrap.php';

use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Purchasing\Services\PurchaseOrderService;
use SagaraBaja\Modules\Purchasing\Services\GoodsReceiptService;
use SagaraBaja\Modules\Purchasing\Services\SupplierPaymentService;

class SupplierPaymentTest {
	private PurchaseOrderService $po_svc;
	private GoodsReceiptService $gr_svc;
	private SupplierPaymentService $pay_svc;

	public function run(): void {
		global $wpdb;
		$wpdb->reset();
		$this->seedMaster( $wpdb );

		$numbering    = new NumberingService( $wpdb );
		$suppliers    = new SupplierRepository( $wpdb );
		$stock        = new StockService( $wpdb );
		$this->po_svc  = new PurchaseOrderService( $wpdb, $numbering, $suppliers );
		$this->gr_svc  = new GoodsReceiptService( $wpdb, $numbering, $stock, $this->po_svc );
		$this->pay_svc = new SupplierPaymentService( $wpdb, $numbering, $this->po_svc, $suppliers );

		$this->test_record_payment_success_partial();
		$this->test_record_payment_full_lunas();
		$this->test_overpayment_rejected();
		$this->test_zero_and_negative_rejected();
		$this->test_payment_on_draft_rejected();
		$this->test_payment_on_cancelled_rejected();
		$this->test_payment_without_receipt_rejected();
		$this->test_partial_then_full_status();
		$this->test_unique_payment_numbers();
		$this->test_get_outstanding();
		$this->test_invalid_method_rejected();
		$this->test_payment_on_unknown_po_rejected();
		$this->test_list_payables();
		$this->test_list_payments();
		$this->test_idempotent_replay_returns_same_payment();
		$this->test_idempotent_replay_after_po_lunas();
		$this->test_different_idempotency_keys_record_twice();
		$this->test_invalid_idempotency_key_rejected();
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
	 * Buat PO yang sudah disetujui. Grand total: 10 × 50000 + 5 × 20000 = 600000.
	 * Mengembalikan [po_id, po_item_id_1, po_item_id_2].
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

	/**
	 * Buat PO yang sudah diterima penuh (hutang 600000 sudah tercatat).
	 */
	private function makeReceivedPo(): int {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$this->gr_svc->receive( $po_id, [
			[ 'po_item_id' => $item1, 'qty_received' => 10 ],
			[ 'po_item_id' => $item2, 'qty_received' => 5 ],
		], 1 );
		return $po_id;
	}

	private function pay( int $po_id, int $amount, string $method = 'tunai' ): array {
		return $this->pay_svc->recordPayment( $po_id, [
			'amount'     => $amount,
			'method'     => $method,
			'created_by' => 1,
		] );
	}

	/**
	 * C-13: pengiriman ulang dengan kunci idempotensi yang sama harus
	 * mengembalikan pembayaran lama — 1 baris, paid_amount tidak
	 * bertambah, nomor pembayaran sama, dan audit log hanya 1 entri.
	 */
	private function test_idempotent_replay_returns_same_payment(): void {
		global $wpdb;
		$po_id = $this->makeReceivedPo();
		$key   = 'uji-klik-ganda-001';
		$first = $this->pay_svc->recordPayment( $po_id, [
			'amount'          => 200000,
			'method'          => 'tunai',
			'created_by'      => 1,
			'idempotency_key' => $key,
		] );
		$second = $this->pay_svc->recordPayment( $po_id, [
			'amount'          => 200000,
			'method'          => 'tunai',
			'created_by'      => 1,
			'idempotency_key' => $key,
		] );

		assert( $second['payment_id'] === $first['payment_id'], 'Replay harus mengembalikan payment_id yang sama.' );
		assert( $second['payment_number'] === $first['payment_number'], 'Replay harus mengembalikan nomor pembayaran yang sama.' );
		assert( $second['paid_amount'] === 200000, 'paid_amount harus tetap 200000 — replay tidak boleh menambah bayar.' );
		assert( $second['outstanding'] === 400000, 'Sisa hutang harus tetap 400000.' );
		assert( count( $this->pay_svc->getPaymentsByPo( $po_id ) ) === 1, 'Hanya boleh ada 1 baris pembayaran untuk PO ini.' );
		$row = $this->pay_svc->findPayment( $first['payment_id'] );
		assert( ( $row['idempotency_key'] ?? null ) === $key, 'Kunci idempotensi harus tersimpan di baris pembayaran.' );

		$audit = array_filter(
			$wpdb->tables['wp_sb_audit_logs'],
			fn( $log ) => ( $log['action'] ?? '' ) === 'supplier_payment'
				&& ( ( json_decode( $log['new_values'] ?? '', true )['payment_number'] ?? '' ) === $first['payment_number'] )
		);
		assert( count( $audit ) === 1, 'Audit log pembayaran harus 1 entri — replay tidak boleh mencatat audit baru.' );

		$found = $this->pay_svc->findByIdempotencyKey( $po_id, $key );
		assert( $found !== null && (int) $found['id'] === $first['payment_id'], 'findByIdempotencyKey harus menemukan pembayaran lama.' );
		echo "  [PASS] test_idempotent_replay_returns_same_payment\n";
	}

	/**
	 * C-13 (regresi, ditemukan di VM 27 Nov 2026): replay harus tetap
	 * berhasil walau sisa hutang kini lebih kecil dari jumlah pembayaran
	 * pertama — mis. PO sudah dilunasi pembayaran lain sesudahnya.
	 * Sebelum perbaikan, replay ditolak sebagai overpayment.
	 */
	private function test_idempotent_replay_after_po_lunas(): void {
		$po_id = $this->makeReceivedPo();
		$first = $this->pay_svc->recordPayment( $po_id, [
			'amount' => 200000, 'method' => 'tunai', 'created_by' => 1, 'idempotency_key' => 'replay-setelah-lunas',
		] );
		// Lunasi sisa 400000 dengan kunci berbeda.
		$this->pay_svc->recordPayment( $po_id, [
			'amount' => 400000, 'method' => 'transfer', 'created_by' => 1, 'idempotency_key' => 'pelunasan-001',
		] );
		$po = $this->po_svc->find( $po_id );
		assert( (int) $po['paid_amount'] === 600000, 'PO harus lunas (600000) sebelum replay.' );

		$replay = $this->pay_svc->recordPayment( $po_id, [
			'amount' => 200000, 'method' => 'tunai', 'created_by' => 1, 'idempotency_key' => 'replay-setelah-lunas',
		] );
		assert( $replay['payment_id'] === $first['payment_id'], 'Replay setelah lunas harus mengembalikan pembayaran pertama.' );
		assert( $replay['paid_amount'] === 600000, 'paid_amount harus tetap 600000.' );
		assert( $replay['outstanding'] === 0, 'Sisa hutang harus tetap 0.' );
		assert( $replay['status'] === 'lunas', "Status replay harus 'lunas'." );
		assert( count( $this->pay_svc->getPaymentsByPo( $po_id ) ) === 2, 'Tetap hanya 2 baris pembayaran.' );
		echo "  [PASS] test_idempotent_replay_after_po_lunas\n";
	}

	/**
	 * C-13: dua kunci berbeda adalah dua pembayaran berbeda (bukan
	 * duplikat) — masing-masing tercatat dan paid_amount bertambah.
	 */
	private function test_different_idempotency_keys_record_twice(): void {
		$po_id = $this->makeReceivedPo();
		$first = $this->pay_svc->recordPayment( $po_id, [
			'amount' => 200000, 'method' => 'tunai', 'created_by' => 1, 'idempotency_key' => 'kunci-a-001',
		] );
		$second = $this->pay_svc->recordPayment( $po_id, [
			'amount' => 200000, 'method' => 'tunai', 'created_by' => 1, 'idempotency_key' => 'kunci-b-001',
		] );
		assert( $second['payment_id'] !== $first['payment_id'], 'Kunci berbeda harus menghasilkan pembayaran berbeda.' );
		assert( $second['paid_amount'] === 400000, 'paid_amount harus 400000 setelah dua pembayaran berbeda.' );
		assert( count( $this->pay_svc->getPaymentsByPo( $po_id ) ) === 2, 'PO harus punya 2 baris pembayaran.' );
		echo "  [PASS] test_different_idempotency_keys_record_twice\n";
	}

	/**
	 * C-13: kunci idempotensi yang tidak valid (karakter dilarang /
	 * lebih dari 64 karakter) harus ditolak, bukan disimpan diam-diam.
	 */
	private function test_invalid_idempotency_key_rejected(): void {
		$po_id = $this->makeReceivedPo();
		foreach ( [ 'kunci ada spasi', str_repeat( 'x', 65 ), 'kunci/tidak/valid' ] as $bad_key ) {
			$thrown = false;
			try {
				$this->pay_svc->recordPayment( $po_id, [
					'amount' => 100000, 'method' => 'tunai', 'created_by' => 1, 'idempotency_key' => $bad_key,
				] );
			} catch ( \InvalidArgumentException $e ) {
				$thrown = true;
			}
			assert( $thrown, "Kunci idempotensi '{$bad_key}' harus ditolak." );
		}
		assert( count( $this->pay_svc->getPaymentsByPo( $po_id ) ) === 0, 'Tidak boleh ada pembayaran tersimpan dari kunci yang ditolak.' );
		echo "  [PASS] test_invalid_idempotency_key_rejected\n";
	}

	private function test_record_payment_success_partial(): void {
		global $wpdb;
		$po_id = $this->makeReceivedPo();
		$result = $this->pay( $po_id, 200000, 'transfer' );

		assert( $result['payment_id'] > 0, 'ID pembayaran harus positif.' );
		assert( str_starts_with( (string) $result['payment_number'], 'BYR-SB/' ), "Nomor pembayaran harus berawalan 'BYR-SB/'. Dapat: {$result['payment_number']}." );
		assert( $result['amount'] === 200000, 'Jumlah pembayaran harus 200000.' );
		assert( $result['paid_amount'] === 200000, 'paid_amount PO harus 200000 setelah bayar sebagian.' );
		assert( $result['outstanding'] === 400000, 'Sisa hutang harus 400000 (600000 − 200000).' );
		assert( $result['status'] === 'sebagian', "Status harus 'sebagian' setelah bayar sebagian." );

		$row = $this->pay_svc->findPayment( $result['payment_id'] );
		assert( $row !== null, 'Baris pembayaran harus tersimpan.' );
		assert( (int) $row['amount'] === 200000, 'amount tersimpan harus 200000.' );
		assert( $row['method'] === 'transfer', "Metode tersimpan harus 'transfer'." );
		assert( (int) $row['supplier_id'] === 1, 'supplier_id harus 1 (dari PO).' );

		$po = $this->po_svc->find( $po_id );
		assert( (int) $po['paid_amount'] === 200000, 'paid_amount di PO harus 200000.' );
		assert( $po['status'] === 'diterima', "Status PO tetap 'diterima' — lunas diturunkan dari paid_amount, bukan status PO." );

		$audit = array_filter(
			$wpdb->tables['wp_sb_audit_logs'],
			fn( $log ) => ( $log['action'] ?? '' ) === 'supplier_payment'
		);
		assert( count( $audit ) === 1, 'Audit log harus mencatat 1 pembayaran hutang.' );
		echo "  [PASS] test_record_payment_success_partial\n";
	}

	private function test_record_payment_full_lunas(): void {
		$po_id = $this->makeReceivedPo();
		$result = $this->pay( $po_id, 600000 );

		assert( $result['paid_amount'] === 600000, 'paid_amount harus 600000 setelah bayar penuh.' );
		assert( $result['outstanding'] === 0, 'Sisa hutang harus 0 setelah bayar penuh.' );
		assert( $result['status'] === 'lunas', "Status harus 'lunas' bila paid_amount == grand_total." );
		$out = $this->pay_svc->getOutstanding( $po_id );
		assert( $out['status'] === 'lunas', "getOutstanding harus 'lunas'." );
		echo "  [PASS] test_record_payment_full_lunas\n";
	}

	private function test_overpayment_rejected(): void {
		$po_id = $this->makeReceivedPo();
		$thrown = false;
		try {
			$this->pay( $po_id, 600001 );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, 'Bayar melebihi sisa hutang (600001 > 600000) harus ditolak.' );
		$po = $this->po_svc->find( $po_id );
		assert( (int) $po['paid_amount'] === 0, 'paid_amount harus tetap 0 — pembayaran yang ditolak tidak boleh mengubah apa pun.' );
		assert( count( $this->pay_svc->getPaymentsByPo( $po_id ) ) === 0, 'Tidak boleh ada baris pembayaran tersimpan.' );
		echo "  [PASS] test_overpayment_rejected\n";
	}

	private function test_zero_and_negative_rejected(): void {
		$po_id = $this->makeReceivedPo();
		foreach ( [ 0, -50000 ] as $amount ) {
			$thrown = false;
			try {
				$this->pay( $po_id, $amount );
			} catch ( \InvalidArgumentException $e ) {
				$thrown = true;
			}
			assert( $thrown, "Jumlah bayar {$amount} harus ditolak." );
		}
		$po = $this->po_svc->find( $po_id );
		assert( (int) $po['paid_amount'] === 0, 'paid_amount harus tetap 0.' );
		echo "  [PASS] test_zero_and_negative_rejected\n";
	}

	private function test_payment_on_draft_rejected(): void {
		$po_id = $this->po_svc->createDraft( [
			'supplier_id'  => 1,
			'warehouse_id' => 1,
			'po_date'      => date( 'Y-m-d' ),
			'items'        => [
				[ 'product_id' => 1, 'unit_id' => 1, 'qty_ordered' => 10, 'unit_cost' => 50000 ],
			],
		] );
		$thrown = false;
		try {
			$this->pay( $po_id, 100000 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, 'Pembayaran atas PO draft harus ditolak.' );
		echo "  [PASS] test_payment_on_draft_rejected\n";
	}

	private function test_payment_on_cancelled_rejected(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$this->po_svc->cancel( $po_id, 'Supplier batal kirim' );
		$thrown = false;
		try {
			$this->pay( $po_id, 100000 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, 'Pembayaran atas PO batal harus ditolak.' );
		echo "  [PASS] test_payment_on_cancelled_rejected\n";
	}

	private function test_payment_without_receipt_rejected(): void {
		[ $po_id, $item1, $item2 ] = $this->makeApprovedPo();
		$thrown = false;
		try {
			$this->pay( $po_id, 100000 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, 'Pembayaran atas PO yang belum ada penerimaan barang harus ditolak (belum ada hutang).' );
		$po = $this->po_svc->find( $po_id );
		assert( (int) $po['paid_amount'] === 0, 'paid_amount harus tetap 0.' );
		echo "  [PASS] test_payment_without_receipt_rejected\n";
	}

	private function test_partial_then_full_status(): void {
		$po_id = $this->makeReceivedPo();
		$first  = $this->pay( $po_id, 200000 );
		$second = $this->pay( $po_id, 400000 );

		assert( $first['status'] === 'sebagian', "Pembayaran pertama harus 'sebagian'." );
		assert( $second['status'] === 'lunas', "Pembayaran kedua (pelunasan) harus 'lunas'." );
		assert( $second['outstanding'] === 0, 'Sisa hutang harus 0 setelah pelunasan.' );
		assert( $first['payment_number'] !== $second['payment_number'], 'Nomor pembayaran pertama dan kedua harus berbeda.' );

		$payments = $this->pay_svc->getPaymentsByPo( $po_id );
		assert( count( $payments ) === 2, 'PO harus punya 2 baris pembayaran.' );
		assert( (int) $payments[0]['amount'] + (int) $payments[1]['amount'] === 600000, 'Total pembayaran harus 600000.' );

		// PO yang sudah lunas tidak bisa dibayar lagi.
		$thrown = false;
		try {
			$this->pay( $po_id, 1000 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, 'Pembayaran atas PO yang sudah lunas harus ditolak.' );
		echo "  [PASS] test_partial_then_full_status\n";
	}

	private function test_unique_payment_numbers(): void {
		$po1 = $this->makeReceivedPo();
		$po2 = $this->makeReceivedPo();
		$r1 = $this->pay( $po1, 100000 );
		$r2 = $this->pay( $po2, 100000 );
		assert( $r1['payment_number'] !== $r2['payment_number'], 'Dua pembayaran harus punya nomor berbeda.' );
		assert( str_starts_with( (string) $r1['payment_number'], 'BYR-SB/' ), 'Nomor pembayaran 1 harus berawalan BYR-SB/.' );
		assert( str_starts_with( (string) $r2['payment_number'], 'BYR-SB/' ), 'Nomor pembayaran 2 harus berawalan BYR-SB/.' );
		echo "  [PASS] test_unique_payment_numbers\n";
	}

	private function test_get_outstanding(): void {
		$po_id = $this->makeReceivedPo();
		$out = $this->pay_svc->getOutstanding( $po_id );
		assert( $out['grand_total'] === 600000, 'Grand total harus 600000.' );
		assert( $out['paid_amount'] === 0, 'paid_amount awal harus 0.' );
		assert( $out['outstanding'] === 600000, 'Sisa hutang awal harus 600000.' );
		assert( $out['status'] === 'belum_bayar', "Status awal harus 'belum_bayar'." );

		$this->pay( $po_id, 200000 );
		$out = $this->pay_svc->getOutstanding( $po_id );
		assert( $out['outstanding'] === 400000, 'Sisa hutang harus 400000 setelah bayar 200000.' );
		assert( $out['status'] === 'sebagian', "Status harus 'sebagian'." );

		$this->pay( $po_id, 400000 );
		$out = $this->pay_svc->getOutstanding( $po_id );
		assert( $out['outstanding'] === 0, 'Sisa hutang harus 0 setelah lunas.' );
		assert( $out['status'] === 'lunas', "Status harus 'lunas'." );
		echo "  [PASS] test_get_outstanding\n";
	}

	private function test_invalid_method_rejected(): void {
		$po_id = $this->makeReceivedPo();
		$thrown = false;
		try {
			$this->pay( $po_id, 100000, 'qris' );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, "Metode 'qris' harus ditolak — hanya tunai/transfer." );
		$po = $this->po_svc->find( $po_id );
		assert( (int) $po['paid_amount'] === 0, 'paid_amount harus tetap 0.' );
		echo "  [PASS] test_invalid_method_rejected\n";
	}

	private function test_payment_on_unknown_po_rejected(): void {
		$thrown = false;
		try {
			$this->pay( 99999, 100000 );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
		}
		assert( $thrown, 'Pembayaran atas PO yang tidak ada harus ditolak.' );
		echo "  [PASS] test_payment_on_unknown_po_rejected\n";
	}

	private function test_list_payables(): void {
		// PO diterima penuh, belum dibayar → masuk daftar.
		$po_open = $this->makeReceivedPo();
		// PO draft → tidak masuk daftar.
		$this->po_svc->createDraft( [
			'supplier_id'  => 1,
			'warehouse_id' => 1,
			'po_date'      => date( 'Y-m-d' ),
			'items'        => [
				[ 'product_id' => 1, 'unit_id' => 1, 'qty_ordered' => 10, 'unit_cost' => 50000 ],
			],
		] );
		// PO diterima penuh lalu dilunasi → tidak masuk daftar.
		$po_paid = $this->makeReceivedPo();
		$this->pay( $po_paid, 600000 );

		$list = $this->pay_svc->listPayables();
		$ids  = array_map( fn( $row ) => (int) $row['po']['id'], $list );
		assert( in_array( $po_open, $ids, true ), 'PO yang masih berhutang harus ada di daftar hutang.' );
		assert( ! in_array( $po_paid, $ids, true ), 'PO yang sudah lunas tidak boleh ada di daftar hutang.' );
		foreach ( $list as $row ) {
			if ( (int) $row['po']['id'] === $po_open ) {
				assert( $row['outstanding'] === 600000, 'Sisa hutang PO terbuka harus 600000.' );
				assert( $row['supplier_name'] === 'PT Krakatau Steel', "Nama supplier harus terisi. Dapat: {$row['supplier_name']}." );
				assert( $row['status'] === 'belum_bayar', "Status harus 'belum_bayar'." );
			}
		}
		echo "  [PASS] test_list_payables\n";
	}

	private function test_list_payments(): void {
		$po1 = $this->makeReceivedPo();
		$po2 = $this->makeReceivedPo();
		$this->pay( $po1, 100000 );
		$this->pay( $po2, 250000, 'transfer' );

		$all = $this->pay_svc->listPayments( 100 );
		assert( count( $all ) >= 2, 'Daftar pembayaran harus memuat minimal 2 baris.' );
		$amounts = array_map( fn( $p ) => (int) $p['amount'], $all );
		assert( in_array( 100000, $amounts, true ), 'Pembayaran 100000 harus ada di daftar.' );
		assert( in_array( 250000, $amounts, true ), 'Pembayaran 250000 harus ada di daftar.' );
		// Terbaru dulu: pembayaran terakhir dibuat harus paling atas.
		assert( (int) $all[0]['amount'] === 250000, 'Pembayaran terbaru harus paling atas di daftar.' );
		echo "  [PASS] test_list_payments\n";
	}
}
