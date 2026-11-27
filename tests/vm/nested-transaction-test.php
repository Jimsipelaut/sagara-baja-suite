<?php
/**
 * Verifikasi C-01 di VM (sekali pakai): penerimaan yang gagal di baris
 * kedua harus membatalkan mutasi stok baris pertama + nomor GR di MySQL
 * nyata, lalu penerimaan normal berikutnya harus tetap berhasil.
 * Keluar 0 bila lulus.
 */
require ( getenv( 'HOME' ) ?: '/home/hatch' ) . '/workspace/sagara-wp/wordpress/wp-load.php';

use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Purchasing\Services\GoodsReceiptService;
use SagaraBaja\Modules\Purchasing\Services\PurchaseOrderService;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;

class FailingSecondAddStockServiceVM extends StockService {
	public int $calls = 0;

	public function add_stock(
		int $product_id,
		int $warehouse_id,
		float $qty,
		string $ref_type,
		?string $ref_id = null,
		?int $unit_id = null,
		int $unit_cost = 0,
		?string $notes = null,
		?int $user_id = null
	): array {
		$this->calls++;
		if ( $this->calls >= 2 ) {
			throw new \RuntimeException( 'Simulasi gagal di tengah penerimaan (C-01 VM).' );
		}
		return parent::add_stock( $product_id, $warehouse_id, $qty, $ref_type, $ref_id, $unit_id, $unit_cost, $notes, $user_id );
	}
}

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
		[ 'product_id' => 1, 'unit_id' => 1, 'qty_ordered' => 10, 'unit_cost' => 50000 ],
		[ 'product_id' => 2, 'unit_id' => 1, 'qty_ordered' => 5, 'unit_cost' => 20000 ],
	],
] );
$po_svc->approve( $po_id );
$po_doc_number = $po_svc->find( $po_id )['doc_number'];
$items = $po_svc->getItems( $po_id );
$item1 = (int) $items[0]['id'];
$item2 = (int) $items[1]['id'];

$gr_before      = $numbering->peek( 'goods_receipt' );
$bal1_before    = $stock->get_balance( 1, 1 );
$bal2_before    = $stock->get_balance( 2, 1 );
$audit_gr_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sb_audit_logs WHERE action = 'goods_receipt'" );
$audit_po_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sb_audit_logs WHERE action = 'po_approve'" );

// ── 1. Penerimaan yang gagal di baris kedua.
$failing_stock = new FailingSecondAddStockServiceVM( $wpdb );
$gr_failing    = new GoodsReceiptService( $wpdb, $numbering, $failing_stock, $po_svc );
$thrown = false;
try {
	$gr_failing->receive( $po_id, [
		[ 'po_item_id' => $item1, 'qty_received' => 10 ],
		[ 'po_item_id' => $item2, 'qty_received' => 5 ],
	], 1 );
} catch ( \Throwable $e ) {
	$thrown = true;
	echo "Penerimaan gagal seperti diharapkan: {$e->getMessage()}\n";
}

$po_after = $po_svc->find( $po_id );
$checks = [];
$checks['exception_dilempar'] = $thrown;
$checks['tidak_ada_tanda_terima'] = count( $po_svc->getReceipts( $po_id ) ) === 0;
$checks['status_tetap_disetujui'] = $po_after['status'] === 'disetujui';
$checks['saldo_produk1_tidak_berubah'] = $stock->get_balance( 1, 1 ) === $bal1_before;
$checks['saldo_produk2_tidak_berubah'] = $stock->get_balance( 2, 1 ) === $bal2_before;
$checks['nomor_GR_tidak_terpakai'] = $numbering->peek( 'goods_receipt' ) === $gr_before;
$checks['audit_goods_receipt_tidak_bertambah'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sb_audit_logs WHERE action = 'goods_receipt'" ) === $audit_gr_before;
foreach ( $checks as $name => $ok ) {
	echo ( $ok ? '  OK  ' : '  GAGAL ' ) . $name . "\n";
}

// ── 2. Penerimaan normal sesudahnya harus berhasil penuh.
$receipt_id = $gr_svc->receive( $po_id, [
	[ 'po_item_id' => $item1, 'qty_received' => 10 ],
	[ 'po_item_id' => $item2, 'qty_received' => 5 ],
], 1 );
$po_final = $po_svc->find( $po_id );
$checks2 = [];
$checks2['tanda_terima_tersimpan'] = $receipt_id > 0 && count( $po_svc->getReceipts( $po_id ) ) === 1;
$checks2['status_diterima'] = $po_final['status'] === 'diterima';
$checks2['saldo_produk1_naik_10'] = $stock->get_balance( 1, 1 ) === $bal1_before + 10.0;
$checks2['saldo_produk2_naik_5'] = $stock->get_balance( 2, 1 ) === $bal2_before + 5.0;
$checks2['audit_po_approve_ada'] = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$wpdb->prefix}sb_audit_logs WHERE action = 'po_approve' AND object_id = %s",
	$po_doc_number
) ) === 1;
$checks2['audit_goods_receipt_ada'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sb_audit_logs WHERE action = 'goods_receipt'" ) === $audit_gr_before + 1;
$rec = $stock->reconcile( 1, 1 );
$checks2['kartu_stok_balance'] = $rec['is_balanced'] === true;
foreach ( $checks2 as $name => $ok ) {
	echo ( $ok ? '  OK  ' : '  GAGAL ' ) . $name . "\n";
}

$pass = ! in_array( false, $checks, true ) && ! in_array( false, $checks2, true );
echo $pass ? "C-01 VM LULUS: rollback penerimaan gagal membatalkan stok + nomor; penerimaan normal tetap jalan.\n" : "C-01 VM GAGAL.\n";
exit( $pass ? 0 : 1 );
