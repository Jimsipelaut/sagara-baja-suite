<?php
namespace SagaraBaja\Modules\Purchasing\Services;

use SagaraBaja\Core\Database\Transaction;
use SagaraBaja\Core\Audit\AuditLogger;
use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Inventory\Services\StockService;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * GoodsReceiptService
 *
 * Mencatat penerimaan barang atas PO yang sudah disetujui:
 *   PO disetujui/diterima_sebagian → tanda terima → stok bertambah → status PO diperbarui
 *
 * Invariant yang dijaga:
 * 1. Penerimaan hanya untuk PO berstatus 'disetujui' atau 'diterima_sebagian'.
 *    PO draft atau batal tidak bisa diterima.
 * 2. Total kumulatif qty_received per baris PO tidak boleh melebihi qty_ordered
 *    (over-receipt ditolak). Semua baris divalidasi dulu sebelum ada mutasi apa pun.
 * 3. Stok bertambah TEPAT SATU KALI per tanda terima, hanya lewat
 *    StockService->add_stock (kartu stok + saldo), dengan ref_type 'goods_receipt'.
 * 4. Status PO menjadi 'diterima' hanya jika SEMUA baris sudah diterima penuh;
 *    jika tidak, 'diterima_sebagian'.
 * 5. Nomor tanda terima memakai sequence 'goods_receipt' (GR-SB/), tidak berbagi
 *    dengan dokumen lain.
 * 6. Hutang usaha atas PO = grand_total − paid_amount. Di Sprint 3 paid_amount
 *    selalu 0 — penerimaan mencatat hutang, bukan membayarnya. Pembayaran
 *    hutang adalah Sprint 4 (Accounting); tidak ada metode bayar di sini.
 * 7. Semua langkah dibungkus database transaction.
 *
 * Catatan transaksi (C-01, diperbaiki 27 Nov 2026): semua batas transaksi
 * di sini, di NumberingService, dan di StockService memakai
 * Core\Database\Transaction (penghitung kedalaman). COMMIT di dalam
 * layanan yang dipanggil TIDAK LAGI meng-commit transaksi luar: bila
 * penerimaan gagal di langkah akhir, ROLLBACK membatalkan mutasi stok
 * dan nomor tanda terima sekaligus. Validasi seluruh baris sebelum
 * mutasi pertama tetap dipertahankan sebagai pertahanan kedua.
 */
class GoodsReceiptService {

	private \wpdb $wpdb;
	private NumberingService $numbering;
	private StockService $stock;
	private PurchaseOrderService $po_service;

	private string $t_receipts;
	private string $t_receipt_items;
	private string $t_po_items;

	public function __construct(
		\wpdb $wpdb,
		NumberingService $numbering,
		StockService $stock,
		PurchaseOrderService $po_service
	) {
		$this->wpdb        = $wpdb;
		$this->numbering   = $numbering;
		$this->stock       = $stock;
		$this->po_service  = $po_service;
		$this->t_receipts      = $wpdb->prefix . 'sb_goods_receipts';
		$this->t_receipt_items = $wpdb->prefix . 'sb_goods_receipt_items';
		$this->t_po_items      = $wpdb->prefix . 'sb_purchase_items';
	}

	// ── Penerimaan Barang ────────────────────────────────────────────────────

	/**
	 * Terima barang atas PO. Menambah stok dan memperbarui status PO.
	 *
	 * @param array<int, array{po_item_id:int, qty_received:float}> $items
	 * @return int ID tanda terima baru
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function receive( int $po_id, array $items, int $received_by, ?string $notes = null ): int {
		if ( $received_by <= 0 ) {
			throw new \InvalidArgumentException( 'Penerima barang wajib diisi.' );
		}
		if ( count( $items ) === 0 ) {
			throw new \InvalidArgumentException( 'Penerimaan barang harus memiliki minimal 1 baris.' );
		}

		// Cek cepat sebelum transaksi (PO harus ada dan berstatus bisa diterima).
		$po = $this->po_service->find( $po_id );
		if ( ! $po ) {
			throw new \RuntimeException( "PO ID {$po_id} tidak ditemukan." );
		}
		if ( ! in_array( $po['status'], [ 'disetujui', 'diterima_sebagian' ], true ) ) {
			throw new \RuntimeException( "Penerimaan hanya bisa dilakukan untuk PO berstatus 'disetujui' atau 'diterima_sebagian'. Status saat ini: {$po['status']}." );
		}

		Transaction::begin( $this->wpdb );
		try {
			// Kunci baris PO dan validasi ulang terhadap data terbaru di dalam
			// transaksi — dua penerimaan bersamaan tidak boleh sama-sama lolos
			// cek over-receipt.
			$po = $this->lockPo( $po_id );
			if ( ! in_array( $po['status'], [ 'disetujui', 'diterima_sebagian' ], true ) ) {
				throw new \RuntimeException( "Penerimaan hanya bisa dilakukan untuk PO berstatus 'disetujui' atau 'diterima_sebagian'. Status saat ini: {$po['status']}." );
			}
			$po_items = $this->indexPoItems( $this->po_service->getItems( $po_id ) );
			$clean    = $this->validateReceiptItems( $items, $po_items );

			$receipt_no = $this->numbering->generate( 'goods_receipt' );

			$ok = $this->wpdb->insert( $this->t_receipts, [
				'po_id'          => $po_id,
				'receipt_number' => $receipt_no,
				'receipt_date'   => current_time( 'mysql' ),
				'warehouse_id'   => (int) $po['warehouse_id'],
				'received_by'    => $received_by,
				'notes'          => $notes,
			] );
			if ( $ok === false ) {
				throw new \RuntimeException( 'Gagal mencatat tanda terima: ' . $this->wpdb->last_error );
			}
			$receipt_id = (int) $this->wpdb->insert_id;

			foreach ( $clean as $line ) {
				$po_item = $po_items[ $line['po_item_id'] ];

				$ok = $this->wpdb->insert( $this->t_receipt_items, [
					'receipt_id'   => $receipt_id,
					'po_item_id'  => $line['po_item_id'],
					'product_id'  => (int) $po_item['product_id'],
					'qty_received' => $line['qty_received'],
					'unit_cost'   => (int) $po_item['unit_cost'],
				] );
				if ( $ok === false ) {
					throw new \RuntimeException( 'Gagal mencatat baris tanda terima: ' . $this->wpdb->last_error );
				}

				// Stok bertambah tepat satu kali per baris, lewat StockService.
				$this->stock->add_stock(
					(int) $po_item['product_id'],
					(int) $po['warehouse_id'],
					(float) $line['qty_received'],
					'goods_receipt',
					$receipt_no,
					(int) $po_item['unit_id'],
					(int) $po_item['unit_cost'],
					'Penerimaan PO ' . $po['doc_number'],
					$received_by
				);

				$new_received = (float) $po_item['qty_received'] + (float) $line['qty_received'];
				$this->wpdb->update(
					$this->t_po_items,
					[ 'qty_received' => $new_received ],
					[ 'id' => $line['po_item_id'] ]
				);
			}

			$new_status = $this->calcPoStatusAfterReceipt( $po_id );
			$this->wpdb->update( $this->t_pos(), [ 'status' => $new_status ], [ 'id' => $po_id ] );

			// C-05: jejak penerimaan barang di audit log (sebelumnya tidak
			// ada entri khusus goods_receipt; penerimaan hanya terlihat
			// lewat mutasi stok dan nomor tanda terima).
			AuditLogger::log(
				'goods_receipt',
				'goods_receipt',
				$receipt_no,
				[ 'po_id' => $po_id, 'po_number' => $po['doc_number'], 'status' => $po['status'] ],
				[ 'receipt_id' => $receipt_id, 'status' => $new_status, 'received_by' => $received_by, 'items' => $clean ]
			);

			Transaction::commit( $this->wpdb );
			return $receipt_id;

		} catch ( \Throwable $e ) {
			Transaction::rollback( $this->wpdb );
			throw $e;
		}
	}

	// ── Baca ─────────────────────────────────────────────────────────────────

	public function findReceipt( int $receipt_id ): ?array {
		return $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->t_receipts} WHERE id = %d", $receipt_id ),
			ARRAY_A
		) ?: null;
	}

	public function getReceiptItems( int $receipt_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT i.*, p.name AS product_name, p.sku AS product_sku
				 FROM {$this->t_receipt_items} i
				 LEFT JOIN {$this->wpdb->prefix}sb_products p ON p.id = i.product_id
				 WHERE i.receipt_id = %d",
				$receipt_id
			),
			ARRAY_A
		);
	}

	// ── Helper Privat ─────────────────────────────────────────────────────────

	private function t_pos(): string {
		return $this->wpdb->prefix . 'sb_purchase_orders';
	}

	/**
	 * Ambil PO dengan row lock. Dipakai di dalam transaksi penerimaan.
	 */
	private function lockPo( int $po_id ): array {
		$po = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->t_pos()} WHERE id = %d FOR UPDATE", $po_id ),
			ARRAY_A
		);
		if ( ! $po ) {
			throw new \RuntimeException( "PO ID {$po_id} tidak ditemukan." );
		}
		return $po;
	}

	/**
	 * @param array<int, array> $po_items baris PO dari PurchaseOrderService::getItems()
	 * @return array<int, array> dipetakan berdasarkan ID baris PO
	 */
	private function indexPoItems( array $po_items ): array {
		$indexed = [];
		foreach ( $po_items as $row ) {
			$indexed[ (int) $row['id'] ] = $row;
		}
		return $indexed;
	}

	/**
	 * Validasi seluruh baris penerimaan terhadap baris PO.
	 * Menolak: baris kosong, duplikat po_item_id, qty ≤ 0, po_item bukan milik
	 * PO ini, dan kumulatif qty_received melebihi qty_ordered.
	 *
	 * @return array<int, array{po_item_id:int, qty_received:float}>
	 */
	private function validateReceiptItems( array $raw, array $po_items ): array {
		$clean = [];
		$seen  = [];
		foreach ( $raw as $i => $row ) {
			$line = $i + 1;
			$po_item_id = (int) ( $row['po_item_id'] ?? 0 );
			if ( $po_item_id <= 0 ) {
				throw new \InvalidArgumentException( "Baris {$line}: baris PO wajib dipilih." );
			}
			if ( isset( $seen[ $po_item_id ] ) ) {
				throw new \InvalidArgumentException( "Baris {$line}: baris PO yang sama tidak boleh muncul dua kali dalam satu tanda terima." );
			}
			$seen[ $po_item_id ] = true;
			if ( ! isset( $po_items[ $po_item_id ] ) ) {
				throw new \InvalidArgumentException( "Baris {$line}: baris PO ID {$po_item_id} bukan bagian dari PO ini." );
			}
			$qty = (float) ( $row['qty_received'] ?? 0 );
			if ( $qty <= 0 ) {
				throw new \InvalidArgumentException( "Baris {$line}: kuantitas diterima harus lebih dari nol." );
			}
			$po_item   = $po_items[ $po_item_id ];
			$cumulative = (float) $po_item['qty_received'] + $qty;
			if ( round( $cumulative - (float) $po_item['qty_ordered'], 4 ) > 0 ) {
				throw new \InvalidArgumentException(
					"Baris {$line}: kuantitas diterima melebihi kuantitas pesan. Dipesan: {$po_item['qty_ordered']}, sudah diterima: {$po_item['qty_received']}, diminta: {$qty}."
				);
			}
			$clean[] = [ 'po_item_id' => $po_item_id, 'qty_received' => $qty ];
		}
		return $clean;
	}

	/**
	 * Status PO setelah penerimaan: 'diterima' jika semua baris sudah penuh,
	 * jika tidak 'diterima_sebagian'.
	 */
	private function calcPoStatusAfterReceipt( int $po_id ): string {
		$items = $this->po_service->getItems( $po_id );
		foreach ( $items as $item ) {
			if ( round( (float) $item['qty_ordered'] - (float) $item['qty_received'], 4 ) > 0 ) {
				return 'diterima_sebagian';
			}
		}
		return 'diterima';
	}
}
