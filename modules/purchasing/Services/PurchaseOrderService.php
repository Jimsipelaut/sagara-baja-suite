<?php
namespace SagaraBaja\Modules\Purchasing\Services;

use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * PurchaseOrderService
 *
 * Mengelola siklus hidup Purchase Order (PO):
 *   buat draft → setujui → (penerimaan barang via GoodsReceiptService) → batal
 *
 * Invariant yang dijaga:
 * 1. grand_total = subtotal − discount_amount + tax_amount (tax_amount=0 saat non-PKP).
 *    Total dihitung ulang di server dari baris barang — tidak percaya angka dari klien.
 * 2. Stok TIDAK berubah saat PO dibuat atau disetujui. Stok hanya bertambah
 *    saat penerimaan barang, lewat StockService.
 * 3. Pembatalan hanya dari status 'draft' atau 'disetujui', tidak pernah setelah
 *    ada penerimaan barang. Alasan batal wajib diisi.
 * 4. Nomor PO memakai sequence 'purchase_order' (PO-SB/), tidak berbagi dengan
 *    dokumen lain.
 * 5. Semua operasi multi-langkah dibungkus database transaction.
 * 6. Hutang usaha = grand_total − paid_amount. paid_amount tetap 0 di Sprint 3;
 *    pembayaran hutang adalah Sprint 4 (Accounting) — tidak ada metode bayar di sini.
 */
class PurchaseOrderService {

	private \wpdb $wpdb;
	private NumberingService $numbering;
	private SupplierRepository $suppliers;

	private string $t_pos;
	private string $t_items;

	public function __construct(
		\wpdb $wpdb,
		NumberingService $numbering,
		SupplierRepository $suppliers
	) {
		$this->wpdb      = $wpdb;
		$this->numbering = $numbering;
		$this->suppliers = $suppliers;
		$this->t_pos     = $wpdb->prefix . 'sb_purchase_orders';
		$this->t_items   = $wpdb->prefix . 'sb_purchase_items';
	}

	// ── Buat Dokumen ─────────────────────────────────────────────────────────

	/**
	 * Buat draft PO. Belum mengubah stok dan belum menimbulkan hutang jatuh tempo
	 * yang bisa dibayar — hutang tercatat saat barang diterima (lihat GoodsReceiptService).
	 *
	 * @param array{
	 *   supplier_id: int,
	 *   warehouse_id: int,
	 *   po_date: string,
	 *   expected_date?: string,
	 *   discount_amount?: int,
	 *   notes?: string,
	 *   items: array<array{product_id:int, unit_id:int, qty_ordered:float, unit_cost:int, discount_amount?:int}>
	 * } $data
	 * @return int ID PO baru
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function createDraft( array $data ): int {
		$this->validateHeader( $data );
		$items = $this->validateItems( $data['items'] ?? [] );

		$subtotal        = $this->calcSubtotal( $items );
		$discount_amount = (int) ( $data['discount_amount'] ?? 0 );
		$tax_amount      = 0; // Non-PKP — kolom siap, nilai 0
		$grand_total     = $subtotal - $discount_amount + $tax_amount;

		if ( $grand_total < 0 ) {
			throw new \InvalidArgumentException( 'Total akhir tidak boleh negatif. Periksa diskon.' );
		}

		$this->wpdb->query( 'START TRANSACTION' );
		try {
			$doc_no = $this->numbering->generate( 'purchase_order' );

			$ok = $this->wpdb->insert( $this->t_pos, [
				'doc_number'      => $doc_no,
				'supplier_id'     => (int) $data['supplier_id'],
				'warehouse_id'    => (int) $data['warehouse_id'],
				'po_date'         => $data['po_date'],
				'expected_date'   => $data['expected_date'] ?? null,
				'subtotal'        => $subtotal,
				'discount_amount' => $discount_amount,
				'tax_amount'      => $tax_amount,
				'grand_total'     => $grand_total,
				'paid_amount'     => 0,
				'status'          => 'draft',
				'notes'           => $data['notes'] ?? null,
			] );

			if ( $ok === false ) {
				throw new \RuntimeException( 'Gagal membuat draft PO: ' . $this->wpdb->last_error );
			}

			$po_id = (int) $this->wpdb->insert_id;
			$this->insertItems( $po_id, $items );

			$this->wpdb->query( 'COMMIT' );
			return $po_id;

		} catch ( \Throwable $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	// ── Transisi Status ──────────────────────────────────────────────────────

	/**
	 * Setujui draft PO: status draft → 'disetujui'. Stok tetap tidak berubah.
	 *
	 * @throws \RuntimeException jika PO tidak ditemukan atau status bukan 'draft'.
	 */
	public function approve( int $po_id ): void {
		$this->wpdb->query( 'START TRANSACTION' );
		try {
			$po = $this->lockPo( $po_id );
			if ( $po['status'] !== 'draft' ) {
				throw new \RuntimeException( "Hanya draft yang bisa disetujui. Status saat ini: {$po['status']}." );
			}

			$this->wpdb->update( $this->t_pos, [ 'status' => 'disetujui' ], [ 'id' => $po_id ] );
			$this->wpdb->query( 'COMMIT' );

		} catch ( \Throwable $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	/**
	 * Batalkan PO. Hanya dari 'draft' atau 'disetujui', dan tidak pernah setelah
	 * ada penerimaan barang. Alasan wajib diisi.
	 *
	 * @throws \InvalidArgumentException jika alasan kosong.
	 * @throws \RuntimeException jika PO sudah diterima (sebagian/penuh) atau sudah batal.
	 */
	public function cancel( int $po_id, string $reason ): void {
		if ( trim( $reason ) === '' ) {
			throw new \InvalidArgumentException( 'Alasan pembatalan wajib diisi.' );
		}

		$this->wpdb->query( 'START TRANSACTION' );
		try {
			$po = $this->lockPo( $po_id );
			if ( $po['status'] === 'batal' ) {
				throw new \RuntimeException( 'PO sudah dibatalkan sebelumnya.' );
			}
			if ( in_array( $po['status'], [ 'diterima_sebagian', 'diterima' ], true ) ) {
				throw new \RuntimeException( 'PO yang sudah ada penerimaan barang tidak bisa dibatalkan.' );
			}
			if ( $this->hasAnyReceipt( $po_id ) ) {
				throw new \RuntimeException( 'PO yang sudah ada penerimaan barang tidak bisa dibatalkan.' );
			}

			$this->wpdb->update( $this->t_pos, [
				'status'        => 'batal',
				'cancel_reason' => $reason,
			], [ 'id' => $po_id ] );
			$this->wpdb->query( 'COMMIT' );

		} catch ( \Throwable $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	// ── Baca ─────────────────────────────────────────────────────────────────

	public function find( int $po_id ): ?array {
		return $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->t_pos} WHERE id = %d", $po_id ),
			ARRAY_A
		) ?: null;
	}

	public function getItems( int $po_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT i.*, p.name AS product_name, p.sku AS product_sku, u.name AS unit_name
				 FROM {$this->t_items} i
				 LEFT JOIN {$this->wpdb->prefix}sb_products p ON p.id = i.product_id
				 LEFT JOIN {$this->wpdb->prefix}sb_units u ON u.id = i.unit_id
				 WHERE i.po_id = %d",
				$po_id
			),
			ARRAY_A
		);
	}

	public function getReceipts( int $po_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->wpdb->prefix}sb_goods_receipts WHERE po_id = %d ORDER BY receipt_date ASC, id ASC",
				$po_id
			),
			ARRAY_A
		);
	}

	/**
	 * Hutang usaha atas PO ini: grand_total − paid_amount.
	 * Di Sprint 3 paid_amount selalu 0 (pembayaran hutang = Sprint 4).
	 */
	public function getPayableAmount( int $po_id ): int {
		$po = $this->find( $po_id );
		if ( ! $po ) {
			throw new \RuntimeException( "PO ID {$po_id} tidak ditemukan." );
		}
		return (int) $po['grand_total'] - (int) $po['paid_amount'];
	}

	// ── Helper Privat ─────────────────────────────────────────────────────────

	/**
	 * Ambil PO dengan row lock. Dipakai di dalam transaksi oleh transisi status.
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

	private function hasAnyReceipt( int $po_id ): bool {
		return count( $this->getReceipts( $po_id ) ) > 0;
	}

	private function validateHeader( array $data ): void {
		if ( empty( $data['supplier_id'] ) ) {
			throw new \InvalidArgumentException( 'Supplier wajib dipilih.' );
		}
		$supplier = $this->suppliers->find( (int) $data['supplier_id'] );
		if ( ! $supplier ) {
			throw new \InvalidArgumentException( "Supplier ID {$data['supplier_id']} tidak ditemukan." );
		}
		if ( (int) ( $supplier['is_active'] ?? 1 ) !== 1 ) {
			throw new \InvalidArgumentException( "Supplier '{$supplier['name']}' sudah tidak aktif." );
		}
		if ( empty( $data['warehouse_id'] ) ) {
			throw new \InvalidArgumentException( 'Gudang wajib dipilih.' );
		}
		$warehouse = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->wpdb->prefix}sb_warehouses WHERE id = %d", (int) $data['warehouse_id'] ),
			ARRAY_A
		);
		if ( ! $warehouse ) {
			throw new \InvalidArgumentException( "Gudang ID {$data['warehouse_id']} tidak ditemukan." );
		}
		if ( empty( $data['po_date'] ) ) {
			throw new \InvalidArgumentException( 'Tanggal PO wajib diisi.' );
		}
	}

	private function validateItems( array $raw ): array {
		if ( count( $raw ) === 0 ) {
			throw new \InvalidArgumentException( 'PO harus memiliki minimal 1 baris barang.' );
		}
		$clean = [];
		foreach ( $raw as $i => $row ) {
			$line = $i + 1;
			if ( empty( $row['product_id'] ) ) {
				throw new \InvalidArgumentException( "Baris {$line}: produk wajib dipilih." );
			}
			if ( empty( $row['unit_id'] ) ) {
				throw new \InvalidArgumentException( "Baris {$line}: satuan wajib dipilih." );
			}
			$base_unit = $this->wpdb->get_var(
				$this->wpdb->prepare( "SELECT base_unit_id FROM `{$this->wpdb->prefix}sb_products` WHERE id = %d", (int) $row['product_id'] )
			);
			if ( $base_unit === null ) {
				throw new \InvalidArgumentException( "Baris {$line}: produk ID {$row['product_id']} tidak ditemukan." );
			}
			$unit = $this->wpdb->get_row(
				$this->wpdb->prepare( "SELECT * FROM {$this->wpdb->prefix}sb_units WHERE id = %d", (int) $row['unit_id'] ),
				ARRAY_A
			);
			if ( ! $unit ) {
				throw new \InvalidArgumentException( "Baris {$line}: satuan ID {$row['unit_id']} tidak ditemukan." );
			}
			$qty = (float) ( $row['qty_ordered'] ?? 0 );
			if ( $qty <= 0 ) {
				throw new \InvalidArgumentException( "Baris {$line}: kuantitas pesan harus lebih dari nol." );
			}
			$cost = (int) ( $row['unit_cost'] ?? 0 );
			if ( $cost < 0 ) {
				throw new \InvalidArgumentException( "Baris {$line}: harga beli satuan tidak boleh negatif." );
			}
			$disc       = (int) ( $row['discount_amount'] ?? 0 );
			if ( $disc < 0 ) {
				throw new \InvalidArgumentException( "Baris {$line}: diskon baris tidak boleh negatif." );
			}
			$line_total = (int) round( $qty * $cost ) - $disc;
			if ( $line_total < 0 ) {
				throw new \InvalidArgumentException( "Baris {$line}: total baris tidak boleh negatif." );
			}
			$clean[] = [
				'product_id'      => (int) $row['product_id'],
				'unit_id'         => (int) $row['unit_id'],
				'qty_ordered'     => $qty,
				'qty_received'    => 0,
				'unit_cost'       => $cost,
				'discount_amount' => $disc,
				'line_total'      => $line_total,
			];
		}
		return $clean;
	}

	private function calcSubtotal( array $items ): int {
		return array_sum( array_column( $items, 'line_total' ) );
	}

	private function insertItems( int $po_id, array $items ): void {
		foreach ( $items as $item ) {
			$ok = $this->wpdb->insert( $this->t_items, array_merge( [ 'po_id' => $po_id ], $item ) );
			if ( $ok === false ) {
				throw new \RuntimeException( 'Gagal menyimpan baris PO: ' . $this->wpdb->last_error );
			}
		}
	}
}
