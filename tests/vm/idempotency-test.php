<?php
/**
 * Verifikasi C-13 di VM (sekali pakai): bayar PO yang sama dua kali dengan
 * kunci idempotensi yang sama → harus 1 baris pembayaran, paid_amount
 * hanya bertambah sekali. Keluar 0 bila lulus.
 */
require ( getenv( 'HOME' ) ?: '/home/hatch' ) . '/workspace/sagara-wp/wordpress/wp-load.php';

use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Purchasing\Services\PurchaseOrderService;
use SagaraBaja\Modules\Purchasing\Services\SupplierPaymentService;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;

global $wpdb;
$numbering = new NumberingService( $wpdb );
$suppliers = new SupplierRepository( $wpdb );
$po_svc    = new PurchaseOrderService( $wpdb, $numbering, $suppliers );
$pay_svc   = new SupplierPaymentService( $wpdb, $numbering, $po_svc, $suppliers );

// PO-SB/2026/00002 (ID 2) dari uji C-12: grand 450000, sudah dibayar 300000.
$po_id = 2;
$key   = 'vm-klik-ganda-001';
$first = $pay_svc->recordPayment( $po_id, [
	'amount' => 100000, 'method' => 'transfer', 'created_by' => 1, 'idempotency_key' => $key,
] );
$second = $pay_svc->recordPayment( $po_id, [
	'amount' => 100000, 'method' => 'transfer', 'created_by' => 1, 'idempotency_key' => $key,
] );

$po       = $po_svc->find( $po_id );
$payments = $pay_svc->getPaymentsByPo( $po_id );
$with_key = array_values( array_filter( $payments, fn( $p ) => ( $p['idempotency_key'] ?? '' ) === $key ) );

echo "Pertama: payment {$first['payment_number']} (ID {$first['payment_id']})\n";
echo "Kedua:   payment {$second['payment_number']} (ID {$second['payment_id']})\n";
echo "Baris dengan kunci '{$key}': " . count( $with_key ) . "\n";
echo "paid_amount PO: Rp" . number_format( (int) $po['paid_amount'], 0, ',', '.' ) . " (harusnya 400000 = 300000 uji C-12 + 100000)\n";

$pass = $second['payment_id'] === $first['payment_id']
	&& count( $with_key ) === 1
	&& (int) $po['paid_amount'] === 400000;

echo $pass ? "C-13 VM LULUS: pengiriman ulang tidak mencatat pembayaran kedua.\n" : "C-13 VM GAGAL.\n";
exit( $pass ? 0 : 1 );
