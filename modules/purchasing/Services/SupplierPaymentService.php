<?php
namespace SagaraBaja\Modules\Purchasing\Services;

use SagaraBaja\Core\Audit\AuditLogger;
use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * SupplierPaymentService
 *
 * Mencatat pembayaran hutang usaha atas PO yang sudah ada penerimaan barang:
 *   PO diterima (sebagian/penuh) → bayar sebagian → ... → lunas
 *
 * Invariant yang dijaga:
 * 1. Hutang hanya ada setelah penerimaan barang. PO draft, PO batal, dan PO
 *    tanpa satu pun tanda terima tidak bisa dibayar.
 * 2. Semua nilai uang integer Rupiah. Jumlah bayar harus > 0 dan tidak boleh
 *    melebihi sisa hutang (grand_total − paid_amount).
 * 3. Metode bayar hanya 'tunai' atau 'transfer' (lingkup slice ini).
 * 4. Nomor pembayaran memakai sequence 'supplier_payment' (BYR-SB/), diambil
 *    lewat NumberingService di dalam transaksi, urutan sendiri.
 * 5. paid_amount di sb_purchase_orders diperbarui secara atomik: baris PO
 *    dikunci FOR UPDATE dan sisa hutang dihitung ulang di dalam transaksi,
 *    sehingga dua pembayaran bersamaan tidak bisa sama-sama lolos dan
 *    membayar melebihi sisa hutang.
 * 6. PO tidak punya status 'lunas' di state machine-nya. Status lunas
 *    diturunkan dari paid_amount == grand_total dan dikembalikan ke pemanggil,
 *    bukan disimpan sebagai status PO.
 * 7. Pembayaran dicatat di audit log. Pembayaran tidak bisa dihapus atau
 *    diedit — koreksi lewat pembayaran lawan / pembatalan beralasan di
 *    pekerjaan berikutnya (di luar slice ini).
 * 8. Slice ini TIDAK membuat jurnal dan TIDAK menggerakkan kas/bank.
 *    Yang dicatat hanya baris pembayaran + paid_amount. Jurnal otomatis
 *    dan kas/bank adalah sisa Sprint 4 dan tetap terbuka.
 *
 * Catatan transaksi (diwarisi dari pola GoodsReceiptService yang ada):
 * NumberingService menjalankan transaksi sendiri (START TRANSACTION/COMMIT).
 * Di MySQL nyata, COMMIT di dalamnya bersifat progresif terhadap transaksi
 * luar. Risiko ini ditekan dengan memvalidasi SELURUH aturan sebelum
 * menulis baris pertama, sehingga kegagalan setelah nomor diterbitkan
 * hampir tidak mungkin terjadi (hanya kegagalan tulis database).
 */
class SupplierPaymentService {

	private \wpdb $wpdb;
	private NumberingService $numbering;
	private PurchaseOrderService $po_service;
	private SupplierRepository $suppliers;

	private string $t_payments;
	private string $t_pos;

	public function __construct(
		\wpdb $wpdb,
		NumberingService $numbering,
		PurchaseOrderService $po_service,
		SupplierRepository $suppliers
	) {
		$this->wpdb        = $wpdb;
		$this->numbering   = $numbering;
		$this->po_service  = $po_service;
		$this->suppliers   = $suppliers;
		$this->t_payments  = $wpdb->prefix . 'sb_supplier_payments';
		$this->t_pos       = $wpdb->prefix . 'sb_purchase_orders';
	}

	// ── Catat Pembayaran ─────────────────────────────────────────────────────

	/**
	 * Catat satu pembayaran hutang atas PO.
	 *
	 * @param array{
	 *   amount:int,
	 *   method?:string,
	 *   payment_date?:string,
	 *   reference?:string,
	 *   notes?:string,
	 *   created_by:int
	 * } $payment
	 * @return array{
	 *   payment_id:int,
	 *   payment_number:string,
	 *   amount:int,
	 *   paid_amount:int,
	 *   outstanding:int,
	 *   status:string
	 * } status 'lunas' bila paid_amount == grand_total, jika tidak 'sebagian'.
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function recordPayment( int $po_id, array $payment ): array {
		$amount = (int) ( $payment['amount'] ?? 0 );
		if ( $amount <= 0 ) {
			throw new \InvalidArgumentException( 'Jumlah bayar harus lebih dari nol.' );
		}

		$method = $payment['method'] ?? 'tunai';
		if ( ! in_array( $method, [ 'tunai', 'transfer' ], true ) ) {
			throw new \InvalidArgumentException( "Metode bayar '{$method}' tidak dikenali. Pilih 'tunai' atau 'transfer'." );
		}

		if ( (int) ( $payment['created_by'] ?? 0 ) <= 0 ) {
			throw new \InvalidArgumentException( 'Pencatat pembayaran wajib diisi.' );
		}

		$payment_date = $payment['payment_date'] ?? '';
		if ( trim( (string) $payment_date ) === '' ) {
			$payment_date = current_time( 'mysql' );
		}

		// Cek cepat sebelum transaksi (PO harus ada dan punya penerimaan).
		$po = $this->po_service->find( $po_id );
		if ( ! $po ) {
			throw new \RuntimeException( "PO ID {$po_id} tidak ditemukan." );
		}
		$this->assertPayableStatus( $po );

		$this->wpdb->query( 'START TRANSACTION' );
		try {
			// Kunci baris PO dan validasi ulang terhadap data terbaru di dalam
			// transaksi — dua pembayaran bersamaan tidak boleh sama-sama lolos
			// cek sisa hutang.
			$po = $this->lockPo( $po_id );
			$this->assertPayableStatus( $po );
			if ( count( $this->po_service->getReceipts( $po_id ) ) === 0 ) {
				throw new \RuntimeException( 'PO ini belum ada penerimaan barang, jadi belum ada hutang yang bisa dibayar.' );
			}

			$outstanding = (int) $po['grand_total'] - (int) $po['paid_amount'];
			if ( $outstanding <= 0 ) {
				throw new \RuntimeException( 'PO ini sudah lunas. Tidak ada sisa hutang yang bisa dibayar.' );
			}
			if ( $amount > $outstanding ) {
				throw new \InvalidArgumentException(
					"Jumlah bayar (Rp " . number_format( $amount, 0, ',', '.' ) . ") melebihi sisa hutang (Rp " . number_format( $outstanding, 0, ',', '.' ) . ")."
				);
			}

			$payment_no = $this->numbering->generate( 'supplier_payment' );

			$ok = $this->wpdb->insert( $this->t_payments, [
				'supplier_id'       => (int) $po['supplier_id'],
				'purchase_order_id' => $po_id,
				'payment_number'    => $payment_no,
				'payment_date'      => $payment_date,
				'method'            => $method,
				'amount'            => $amount,
				'reference'         => $payment['reference'] ?? null,
				'notes'             => $payment['notes'] ?? null,
				'created_by'        => (int) $payment['created_by'],
			] );
			if ( $ok === false ) {
				throw new \RuntimeException( 'Gagal mencatat pembayaran hutang: ' . $this->wpdb->last_error );
			}
			$payment_id = (int) $this->wpdb->insert_id;

			$new_paid = (int) $po['paid_amount'] + $amount;
			$this->wpdb->update( $this->t_pos, [
				'paid_amount' => $new_paid,
			], [ 'id' => $po_id ] );

			$new_outstanding = (int) $po['grand_total'] - $new_paid;
			$status = $new_outstanding === 0 ? 'lunas' : 'sebagian';

			AuditLogger::log(
				'supplier_payment',
				'sb_supplier_payments',
				(string) $payment_id,
				[ 'po_id' => $po_id, 'paid_amount' => (int) $po['paid_amount'], 'outstanding' => $outstanding ],
				[ 'payment_number' => $payment_no, 'amount' => $amount, 'method' => $method, 'paid_amount' => $new_paid, 'outstanding' => $new_outstanding, 'status' => $status ]
			);

			$this->wpdb->query( 'COMMIT' );

			return [
				'payment_id'     => $payment_id,
				'payment_number' => $payment_no,
				'amount'         => $amount,
				'paid_amount'    => $new_paid,
				'outstanding'    => $new_outstanding,
				'status'         => $status,
			];

		} catch ( \Throwable $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	// ── Baca ─────────────────────────────────────────────────────────────────

	public function findPayment( int $payment_id ): ?array {
		return $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->t_payments} WHERE id = %d", $payment_id ),
			ARRAY_A
		) ?: null;
	}

	public function getPaymentsByPo( int $po_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->t_payments} WHERE purchase_order_id = %d ORDER BY payment_date ASC, id ASC",
				$po_id
			),
			ARRAY_A
		);
	}

	public function listPayments( int $limit = 100 ): array {
		$limit = max( 1, min( 500, $limit ) );
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->t_payments} ORDER BY payment_date DESC, id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Sisa hutang atas satu PO beserta status bayarnya.
	 * Status: 'lunas' bila sisa 0, 'sebagian' bila sudah ada pembayaran,
	 * 'belum_bayar' bila belum ada pembayaran sama sekali.
	 *
	 * @return array{po:array, grand_total:int, paid_amount:int, outstanding:int, status:string}
	 */
	public function getOutstanding( int $po_id ): array {
		$po = $this->po_service->find( $po_id );
		if ( ! $po ) {
			throw new \RuntimeException( "PO ID {$po_id} tidak ditemukan." );
		}
		$grand_total = (int) $po['grand_total'];
		$paid_amount = (int) $po['paid_amount'];
		$outstanding = $grand_total - $paid_amount;
		$status = $outstanding === 0 ? 'lunas' : ( $paid_amount > 0 ? 'sebagian' : 'belum_bayar' );
		return [
			'po'          => $po,
			'grand_total' => $grand_total,
			'paid_amount' => $paid_amount,
			'outstanding' => $outstanding,
			'status'      => $status,
		];
	}

	/**
	 * Daftar PO yang masih punya sisa hutang (sudah ada penerimaan, belum lunas),
	 * diurutkan dari PO terlama. Dipakai halaman Daftar Hutang di admin.
	 *
	 * @return array<int, array{po:array, supplier_name:string|null, grand_total:int, paid_amount:int, outstanding:int, status:string}>
	 */
	public function listPayables(): array {
		$rows = (array) $this->wpdb->get_results(
			"SELECT * FROM {$this->t_pos} ORDER BY po_date ASC, id ASC",
			ARRAY_A
		);
		$list = [];
		foreach ( $rows as $po ) {
			if ( ! in_array( $po['status'], [ 'disetujui', 'diterima_sebagian', 'diterima' ], true ) ) {
				continue;
			}
			$outstanding = (int) $po['grand_total'] - (int) $po['paid_amount'];
			if ( $outstanding <= 0 ) {
				continue;
			}
			if ( count( $this->po_service->getReceipts( (int) $po['id'] ) ) === 0 ) {
				continue;
			}
			$supplier = $this->suppliers->find( (int) $po['supplier_id'] );
			$list[] = [
				'po'            => $po,
				'supplier_name' => $supplier['name'] ?? null,
				'grand_total'   => (int) $po['grand_total'],
				'paid_amount'   => (int) $po['paid_amount'],
				'outstanding'   => $outstanding,
				'status'        => (int) $po['paid_amount'] > 0 ? 'sebagian' : 'belum_bayar',
			];
		}
		return $list;
	}

	// ── Helper Privat ─────────────────────────────────────────────────────────

	/**
	 * Ambil PO dengan row lock. Dipakai di dalam transaksi pembayaran.
	 */
	private function lockPo( int $po_id ): array {
		$po = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->t_pos} WHERE id = %d FOR UPDATE", $po_id ),
			ARRAY_A
		);
		if ( ! $po ) {
			throw new \RuntimeException( "PO ID {$po_id} tidak ditemukan." );
		}
		return $po;
	}

	/**
	 * PO hanya bisa dibayar bila statusnya 'disetujui', 'diterima_sebagian',
	 * atau 'diterima'. Draft belum menimbulkan hutang yang bisa dibayar,
	 * PO batal tidak bisa dibayar.
	 */
	private function assertPayableStatus( array $po ): void {
		if ( $po['status'] === 'draft' ) {
			throw new \RuntimeException( 'PO masih draft — belum ada hutang yang bisa dibayar. Setujui dan terima barang dulu.' );
		}
		if ( $po['status'] === 'batal' ) {
			throw new \RuntimeException( 'PO sudah dibatalkan — tidak bisa dibayar.' );
		}
		if ( ! in_array( $po['status'], [ 'disetujui', 'diterima_sebagian', 'diterima' ], true ) ) {
			throw new \RuntimeException( "PO berstatus '{$po['status']}' tidak bisa dibayar." );
		}
	}
}
