<?php
/**
 * Uji konkurensi C-12 (Sagara Baja Suite) — skrip sekali pakai, BUKAN bagian
 * dari plugin. Dijalankan dari VM runtime WordPress lokal.
 *
 * Skenario: satu PO Rp450.000 yang sudah diterima penuh, lalu DUA proses
 * PHP terpisah (dua koneksi MySQL berbeda) membayar Rp300.000 secara
 * bersamaan. Yang benar: tepat 1 pembayaran berhasil, yang lain ditolak
 * karena sisa hutang tinggal Rp150.000 — total tercatat tidak boleh
 * melebihi Rp450.000.
 *
 * Cara pakai: php /tmp/sb-concurrency-test.php
 * Keluar 0 bila lulus, 1 bila gagal.
 */
define( 'SB_CONCURRENCY_TEST', true );

require ( getenv( 'HOME' ) ?: '/home/hatch' ) . '/workspace/sagara-wp/wordpress/wp-load.php';

use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Purchasing\Services\GoodsReceiptService;
use SagaraBaja\Modules\Purchasing\Services\PurchaseOrderService;
use SagaraBaja\Modules\Purchasing\Services\SupplierPaymentService;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;

$barrier_dir = '/tmp/sb-conc';
@mkdir( $barrier_dir, 0777, true );
foreach ( glob( $barrier_dir . '/*' ) as $f ) {
	@unlink( $f );
}

function build_payment_service( $wpdb ): SupplierPaymentService {
	$numbering = new NumberingService( $wpdb );
	$suppliers = new SupplierRepository( $wpdb );
	$po_svc    = new PurchaseOrderService( $wpdb, $numbering, $suppliers );
	return new SupplierPaymentService( $wpdb, $numbering, $po_svc, $suppliers );
}

// ── Setup (proses induk): PO 10 × Rp45.000 = Rp450.000, disetujui + diterima penuh.
global $wpdb;
$numbering = new NumberingService( $wpdb );
$suppliers = new SupplierRepository( $wpdb );
$stock     = new StockService( $wpdb );
$po_svc    = new PurchaseOrderService( $wpdb, $numbering, $suppliers );
$gr_svc    = new GoodsReceiptService( $wpdb, $numbering, $stock, $po_svc );

$po_id = $po_svc->createDraft( [
	'supplier_id'  => 1,
	'warehouse_id' => 1,
	'po_date'      => date( 'Y-m-d' ),
	'items'        => [
		[ 'product_id' => 1, 'unit_id' => 1, 'qty_ordered' => 10, 'unit_cost' => 45000 ],
	],
] );
$po_svc->approve( $po_id );
$items = $po_svc->getItems( $po_id );
$gr_svc->receive( $po_id, [
	[ 'po_item_id' => (int) $items[0]['id'], 'qty_received' => 10 ],
], 1 );

$po = $po_svc->find( $po_id );
echo "PO uji: {$po['doc_number']} (ID {$po_id}), grand_total Rp" . number_format( (int) $po['grand_total'], 0, ',', '.' ) . ", status {$po['status']}\n";

// ── Dua pekerja paralel, masing-masing koneksi MySQL sendiri.
$pids = [];
for ( $i = 1; $i <= 2; $i++ ) {
	$pid = pcntl_fork();
	if ( $pid === -1 ) {
		fwrite( STDERR, "Gagal fork pekerja {$i}\n" );
		exit( 1 );
	}
	if ( $pid === 0 ) {
		// Proses anak: koneksi MySQL baru (koneksi induk tidak boleh dipakai
		// bersama setelah fork — dua sesi harus benar-benar terpisah).
		// wpdb baru tidak mewarisi prefix tabel: setel eksplisit seperti
		// yang dilakukan wp-settings.php untuk $wpdb utama.
		$child_db = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		$child_db->set_prefix( $GLOBALS['table_prefix'] ?? 'wp_' );
		$GLOBALS['wpdb'] = $child_db;
		$svc = build_payment_service( $child_db );

		// Barrier: tunggu sampai kedua pekerja siap, lalu jalan bersamaan.
		touch( $barrier_dir . "/ready-{$i}" );
		$wait = 0;
		while ( ! ( file_exists( $barrier_dir . '/ready-1' ) && file_exists( $barrier_dir . '/ready-2' ) ) && $wait < 100 ) {
			usleep( 50000 );
			$wait++;
		}

		try {
			$result = $svc->recordPayment( $po_id, [
				'amount'          => 300000,
				'method'          => 'tunai',
				'created_by'      => 1,
				'idempotency_key' => "conc-uji-{$i}",
			] );
			$out = [ 'worker' => $i, 'ok' => true, 'result' => $result ];
		} catch ( \Throwable $e ) {
			$out = [ 'worker' => $i, 'ok' => false, 'error' => get_class( $e ) . ': ' . $e->getMessage() ];
		}
		file_put_contents( $barrier_dir . "/result-{$i}.json", json_encode( $out, JSON_UNESCAPED_UNICODE ) );
		exit( 0 );
	}
	$pids[] = $pid;
}

foreach ( $pids as $pid ) {
	pcntl_waitpid( $pid, $status );
}

$results = [];
for ( $i = 1; $i <= 2; $i++ ) {
	$results[ $i ] = json_decode( file_get_contents( $barrier_dir . "/result-{$i}.json" ), true );
	echo "Pekerja {$i}: " . json_encode( $results[ $i ], JSON_UNESCAPED_UNICODE ) . "\n";
}

// ── Verifikasi akhir dari koneksi induk (baca ulang dari DB).
$po_after  = $po_svc->find( $po_id );
$payments  = build_payment_service( $wpdb )->getPaymentsByPo( $po_id );
$total     = array_sum( array_map( fn( $p ) => (int) $p['amount'], $payments ) );
$paid      = (int) $po_after['paid_amount'];
$grand     = (int) $po_after['grand_total'];
$successes = count( array_filter( $results, fn( $r ) => ! empty( $r['ok'] ) ) );

echo "Hasil akhir: " . count( $payments ) . " pembayaran tercatat, total Rp" . number_format( $total, 0, ',', '.' )
	. ", paid_amount Rp" . number_format( $paid, 0, ',', '.' )
	. " dari grand_total Rp" . number_format( $grand, 0, ',', '.' ) . "\n";

$pass = $successes === 1
	&& count( $payments ) === 1
	&& $total === 300000
	&& $paid === 300000
	&& $total <= $grand;

echo $pass
	? "C-12 LULUS: hanya 1 dari 2 pembayaran bersamaan yang tercatat; total tidak melebihi sisa hutang.\n"
	: "C-12 GAGAL: hasil tidak sesuai harapan (harus 1 berhasil, total Rp300.000).\n";
exit( $pass ? 0 : 1 );
