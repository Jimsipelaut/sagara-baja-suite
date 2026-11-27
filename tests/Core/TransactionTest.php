<?php
namespace SagaraBaja\Tests\Core;

require_once __DIR__ . '/../../tests/bootstrap.php';

use SagaraBaja\Core\Database\Transaction;

class TransactionTest {
	public function run(): void {
		global $wpdb;
		$wpdb->reset();

		$this->test_nested_begin_commit_issues_single_transaction();
		$this->test_rollback_from_nested_depth_undoes_everything();
		$this->test_commit_and_rollback_outside_transaction_are_noops();
	}

	/**
	 * C-01: begin() bersarang tidak mengirim START kedua, dan commit()
	 * bersarang tidak mengirim COMMIT — hanya commit terluar yang
	 * mengirim COMMIT ke database.
	 */
	private function test_nested_begin_commit_issues_single_transaction(): void {
		global $wpdb;
		$wpdb->reset();
		$queries_before = count( $wpdb->queries );

		Transaction::begin( $wpdb );
		assert( Transaction::depth( $wpdb ) === 1, 'Kedalaman harus 1 setelah begin pertama.' );
		Transaction::begin( $wpdb );
		assert( Transaction::depth( $wpdb ) === 2, 'Kedalaman harus 2 setelah begin bersarang.' );
		Transaction::commit( $wpdb );
		assert( Transaction::depth( $wpdb ) === 1, 'Kedalaman harus kembali 1 setelah commit bersarang.' );
		Transaction::commit( $wpdb );
		assert( Transaction::depth( $wpdb ) === 0, 'Kedalaman harus 0 setelah commit terluar.' );

		$new_queries = array_slice( $wpdb->queries, $queries_before );
		$starts  = count( array_filter( $new_queries, fn( $q ) => strtoupper( trim( $q ) ) === 'START TRANSACTION' ) );
		$commits = count( array_filter( $new_queries, fn( $q ) => strtoupper( trim( $q ) ) === 'COMMIT' ) );
		assert( $starts === 1, "Hanya boleh ada 1 START TRANSACTION, dapat: {$starts}." );
		assert( $commits === 1, "Hanya boleh ada 1 COMMIT, dapat: {$commits}." );
		echo "  [PASS] test_nested_begin_commit_issues_single_transaction\n";
	}

	/**
	 * C-01: rollback() dari kedalaman bersarang membatalkan SELURUH
	 * transaksi (semantik MySQL tanpa savepoint) dan mengembalikan
	 * kedalaman ke 0.
	 */
	private function test_rollback_from_nested_depth_undoes_everything(): void {
		global $wpdb;
		$wpdb->reset();

		Transaction::begin( $wpdb );
		$wpdb->insert( $wpdb->prefix . 'sb_products', [
			'id' => 99, 'sku' => 'TX-001', 'name' => 'Produk Transaksi', 'base_unit_id' => 1, 'status' => 'active',
		] );
		Transaction::begin( $wpdb );
		$wpdb->insert( $wpdb->prefix . 'sb_products', [
			'id' => 100, 'sku' => 'TX-002', 'name' => 'Produk Transaksi 2', 'base_unit_id' => 1, 'status' => 'active',
		] );
		Transaction::rollback( $wpdb );

		assert( Transaction::depth( $wpdb ) === 0, 'Kedalaman harus 0 setelah rollback.' );
		assert( ! isset( $wpdb->tables['wp_sb_products'][99] ), 'Baris dari transaksi luar harus ikut batal.' );
		assert( ! isset( $wpdb->tables['wp_sb_products'][100] ), 'Baris dari transaksi dalam harus ikut batal.' );

		// Transaksi berikutnya tetap bisa dibuka normal setelah rollback.
		Transaction::begin( $wpdb );
		$wpdb->insert( $wpdb->prefix . 'sb_products', [
			'id' => 101, 'sku' => 'TX-003', 'name' => 'Produk Sesudah Rollback', 'base_unit_id' => 1, 'status' => 'active',
		] );
		Transaction::commit( $wpdb );
		assert( isset( $wpdb->tables['wp_sb_products'][101] ), 'Baris transaksi berikutnya harus tersimpan.' );
		assert( Transaction::depth( $wpdb ) === 0, 'Kedalaman harus 0 setelah commit.' );
		echo "  [PASS] test_rollback_from_nested_depth_undoes_everything\n";
	}

	/**
	 * C-01: commit()/rollback() tanpa transaksi aktif tidak boleh
	 * mengirim query dan tidak boleh error.
	 */
	private function test_commit_and_rollback_outside_transaction_are_noops(): void {
		global $wpdb;
		$wpdb->reset();
		$queries_before = count( $wpdb->queries );

		Transaction::commit( $wpdb );
		Transaction::rollback( $wpdb );
		assert( Transaction::depth( $wpdb ) === 0, 'Kedalaman harus tetap 0.' );
		assert( count( $wpdb->queries ) === $queries_before, 'Tidak boleh ada query transaksi yang dikirim.' );
		echo "  [PASS] test_commit_and_rollback_outside_transaction_are_noops\n";
	}
}
