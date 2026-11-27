<?php
namespace SagaraBaja\Tests\Sales;

require_once __DIR__ . '/../../tests/bootstrap.php';

use SagaraBaja\Modules\Sales\Services\ReceivablesAgingService;
use SagaraBaja\Modules\Sales\Repositories\CustomerRepository;

class ReceivablesAgingTest {
	public function run(): void {
		global $wpdb;
		$wpdb->reset();

		$this->test_empty_aging();
		$customer_id = $this->seed_data();
		$svc = new ReceivablesAgingService( $wpdb );

		$this->test_buckets_and_totals( $svc );
		$this->test_due_date_and_days_overdue( $svc );
		$this->test_filter_by_customer( $svc, $customer_id );
		$this->test_invalid_asof_rejected( $svc );
	}

	private function test_empty_aging(): void {
		global $wpdb;
		$svc    = new ReceivablesAgingService( $wpdb );
		$result = $svc->getAging( '2026-11-26' );
		assert( $result['count'] === 0, 'Aging kosong harus 0 baris.' );
		assert( $result['totals']['total'] === 0, 'Total aging kosong harus 0.' );
		echo "  [PASS] test_empty_aging\n";
	}

	private function seed_data(): int {
		global $wpdb;
		$customers   = new CustomerRepository( $wpdb );
		$customer_id = $customers->create( [ 'code' => 'CUST-001', 'name' => 'Budi Santoso' ] );

		$this->seedSale( 'NTA-001', '2026-11-16', 100000, 0, 'terbit', $customer_id );
		$this->seedSale( 'NTA-002', '2026-10-17', 200000, 50000, 'terbit', $customer_id );
		$this->seedSale( 'NTA-003', '2026-09-12', 300000, 0, 'terbit', $customer_id );
		$this->seedSale( 'NTA-004', '2026-08-18', 400000, 100000, 'terbit', $customer_id );
		$this->seedSale( 'NTA-005', '2026-06-29', 500000, 0, 'terbit', $customer_id );
		$this->seedSale( 'NTA-006', '2026-11-06', 50000, 0, 'terbit', null );
		$this->seedSale( 'NTA-007', '2026-10-17', 100000, 100000, 'lunas', $customer_id );
		$this->seedSale( 'NTA-008', '2026-10-17', 100000, 0, 'draft', $customer_id );
		$this->seedSale( 'NTA-009', '2026-10-17', 100000, 0, 'batal', $customer_id );

		return $customer_id;
	}

	private function seedSale( string $doc, string $date, int $grand, int $paid, string $status, ?int $customer_id ): void {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'sb_sales', [
			'doc_number'      => $doc,
			'invoice_type'    => 'non_resmi',
			'sale_date'       => $date,
			'customer_id'     => $customer_id,
			'warehouse_id'    => 1,
			'cashier_user_id' => 1,
			'subtotal'        => $grand,
			'discount_amount' => 0,
			'tax_amount'      => 0,
			'grand_total'     => $grand,
			'paid_amount'     => $paid,
			'status'          => $status,
		] );
	}

	private function test_buckets_and_totals( ReceivablesAgingService $svc ): void {
		$result = $svc->getAging( '2026-11-26' );
		assert( $result['count'] === 6, 'Harus 6 baris (lunas/draft/batal tidak masuk). Dapat: ' . $result['count'] . '.' );
		assert( $result['totals']['belum_jatuh_tempo'] === 150000, 'Bucket belum jatuh tempo harus 150000.' );
		assert( $result['totals']['1_30'] === 150000, 'Bucket 1-30 harus 150000.' );
		assert( $result['totals']['31_60'] === 300000, 'Bucket 31-60 harus 300000.' );
		assert( $result['totals']['61_90'] === 300000, 'Bucket 61-90 harus 300000.' );
		assert( $result['totals']['lebih_90'] === 500000, 'Bucket >90 harus 500000.' );
		assert( $result['totals']['total'] === 1400000, 'Total piutang harus 1400000.' );
		echo "  [PASS] test_buckets_and_totals\n";
	}

	private function test_due_date_and_days_overdue( ReceivablesAgingService $svc ): void {
		$result = $svc->getAging( '2026-11-26' );
		$by_doc = [];
		foreach ( $result['rows'] as $row ) {
			$by_doc[ $row['doc_number'] ] = $row;
		}
		assert( $by_doc['NTA-001']['due_date'] === '2026-12-16', 'Jatuh tempo NTA-001 harus 2026-12-16.' );
		assert( $by_doc['NTA-001']['days_overdue'] === -20, 'NTA-001 harus -20 hari (belum jatuh tempo).' );
		assert( $by_doc['NTA-002']['days_overdue'] === 10, 'NTA-002 harus 10 hari terlambat.' );
		assert( $by_doc['NTA-002']['outstanding'] === 150000, 'Sisa NTA-002 harus 150000.' );
		assert( $by_doc['NTA-002']['customer_name'] === 'Budi Santoso', 'Nama pelanggan NTA-002 harus Budi Santoso.' );
		assert( $by_doc['NTA-006']['customer_name'] === 'Umum', 'Penjualan tanpa pelanggan harus bernama Umum.' );
		echo "  [PASS] test_due_date_and_days_overdue\n";
	}

	private function test_filter_by_customer( ReceivablesAgingService $svc, int $customer_id ): void {
		$result = $svc->getAging( '2026-11-26', $customer_id );
		assert( $result['count'] === 5, 'Filter pelanggan harus 5 baris (tanpa penjualan Umum). Dapat: ' . $result['count'] . '.' );
		assert( $result['totals']['total'] === 1350000, 'Total filter pelanggan harus 1350000.' );
		echo "  [PASS] test_filter_by_customer\n";
	}

	private function test_invalid_asof_rejected( ReceivablesAgingService $svc ): void {
		$thrown = false;
		try {
			$svc->getAging( 'bukan-tanggal' );
		} catch ( \InvalidArgumentException $e ) {
			$thrown = true;
		}
		assert( $thrown, 'Tanggal acuan tidak valid harus ditolak.' );
		echo "  [PASS] test_invalid_asof_rejected\n";
	}
}