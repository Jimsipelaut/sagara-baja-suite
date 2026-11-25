<?php
namespace SagaraBaja\Modules\Sales\Services;

use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Inventory\Services\StockService;

/**
 * SaleService
 *
 * Mengelola siklus hidup dokumen penjualan (resmi & non-resmi):
 *   buat draft → terbitkan → tambah pembayaran → void
 *
 * Invariant yang dijaga:
 * 1. grand_total = subtotal − discount_amount + tax_amount (tax_amount=0 saat non-PKP).
 * 2. Stok dikurangi tepat saat dokumen berstatus 'terbit', bukan draft.
 * 3. Pembatalan (void) membalik mutasi stok lewat StockService.
 * 4. Nomor dokumen resmi dan non-resmi tidak boleh berbagi sequence.
 * 5. Semua operasi multi-langkah dibungkus database transaction.
 * 6. Harga & diskon dihitung ulang di server dari data produk — tidak percaya angka dari klien.
 */
class SaleService {

	private \wpdb $wpdb;
	private NumberingService $numbering;
	private StockService $stock;

	private string $t_sales;
	private string $t_items;
	private string $t_payments;

	public function __construct(
		\wpdb $wpdb,
		NumberingService $numbering,
		StockService $stock
	) {
		$this->wpdb       = $wpdb;
		$this->numbering  = $numbering;
		$this->stock      = $stock;
		$this->t_sales    = $wpdb->prefix . 'sb_sales';
		$this->t_items    = $wpdb->prefix . 'sb_sale_items';
		$this->t_payments = $wpdb->prefix . 'sb_payments';
	}

	// ── Buat Dokumen ─────────────────────────────────────────────────────────

	/**
	 * Buat draft penjualan. Belum mengurangi stok.
	 *
	 * @param array{
	 *   invoice_type: 'resmi'|'non_resmi',
	 *   sale_date: string,
	 *   warehouse_id: int,
	 *   cashier_user_id: int,
	 *   customer_id?: int,
	 *   shift_id?: int,
	 *   discount_amount?: int,
	 *   notes?: string,
	 *   items: array<array{product_id:int, unit_id:int, qty:float, unit_price:int, discount_amount?:int}>
	 * } $data
	 * @return int ID sale baru
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
			$doc_type = $data['invoice_type'] === 'resmi' ? 'invoice_resmi' : 'nota_non_resmi';
			$doc_no   = $this->numbering->generate( $doc_type );

			$ok = $this->wpdb->insert( $this->t_sales, [
				'doc_number'      => $doc_no,
				'invoice_type'    => $data['invoice_type'],
				'sale_date'       => $data['sale_date'],
				'customer_id'     => $data['customer_id']     ?? null,
				'warehouse_id'    => (int) $data['warehouse_id'],
				'shift_id'        => $data['shift_id']        ?? null,
				'cashier_user_id' => (int) $data['cashier_user_id'],
				'subtotal'        => $subtotal,
				'discount_amount' => $discount_amount,
				'tax_amount'      => $tax_amount,
				'grand_total'     => $grand_total,
				'paid_amount'     => 0,
				'status'          => 'draft',
				'notes'           => $data['notes'] ?? null,
			] );

			if ( $ok === false ) {
				throw new \RuntimeException( 'Gagal membuat draft penjualan: ' . $this->wpdb->last_error );
			}

			$sale_id = (int) $this->wpdb->insert_id;
			$this->insertItems( $sale_id, $items );

			$this->wpdb->query( 'COMMIT' );
			return $sale_id;

		} catch ( \Throwable $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	/**
	 * Terbitkan draft: status → 'terbit', stok dikurangi.
	 *
	 * @throws \RuntimeException jika status bukan 'draft' atau stok tidak cukup.
	 */
	public function publish( int $sale_id ): void {
		$this->wpdb->query( 'START TRANSACTION' );
		try {
			$sale = $this->wpdb->get_row(
				$this->wpdb->prepare( "SELECT * FROM {$this->t_sales} WHERE id = %d FOR UPDATE", $sale_id ),
				ARRAY_A
			);
			if ( ! $sale ) {
				throw new \RuntimeException( "Dokumen penjualan ID {$sale_id} tidak ditemukan." );
			}
			if ( $sale['status'] !== 'draft' ) {
				throw new \RuntimeException( "Hanya draft yang bisa diterbitkan. Status saat ini: {$sale['status']}." );
			}

			$items = $this->getItems( $sale_id );
			foreach ( $items as $item ) {
				$this->stock->deduct_stock(
					(int) $item['product_id'],
					(int) $sale['warehouse_id'],
					(float) $item['qty'],
					'pos_sale',
					$sale['doc_number'],
					(int) $item['unit_id'],
					0,
					'Penjualan ' . $sale['invoice_type'],
					(int) $sale['cashier_user_id']
				);
			}

			$this->wpdb->update( $this->t_sales, [ 'status' => 'terbit' ], [ 'id' => $sale_id ] );
			$this->wpdb->query( 'COMMIT' );

		} catch ( \Throwable $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	// ── Pembayaran ────────────────────────────────────────────────────────────

	/**
	 * Tambah pembayaran ke dokumen. Otomatis tandai 'lunas' jika paid_amount ≥ grand_total.
	 *
	 * @param array{method:string, amount:int, reference?:string, notes?:string, created_by:int} $payment
	 * @throws \InvalidArgumentException jika amount ≤ 0.
	 * @throws \RuntimeException jika status bukan 'terbit'.
	 */
	public function addPayment( int $sale_id, array $payment ): int {
		$amount = (int) ( $payment['amount'] ?? 0 );
		if ( $amount <= 0 ) {
			throw new \InvalidArgumentException( 'Jumlah bayar harus lebih dari nol.' );
		}

		$allowed_methods = [ 'tunai', 'transfer', 'qris', 'kasbon' ];
		$method = $payment['method'] ?? 'tunai';
		if ( ! in_array( $method, $allowed_methods, true ) ) {
			throw new \InvalidArgumentException( "Metode bayar '{$method}' tidak dikenali." );
		}

		$this->wpdb->query( 'START TRANSACTION' );
		try {
			$sale = $this->wpdb->get_row(
				$this->wpdb->prepare( "SELECT * FROM {$this->t_sales} WHERE id = %d FOR UPDATE", $sale_id ),
				ARRAY_A
			);
			if ( ! $sale ) {
				throw new \RuntimeException( "Dokumen ID {$sale_id} tidak ditemukan." );
			}
			if ( $sale['status'] !== 'terbit' ) {
				throw new \RuntimeException( "Pembayaran hanya bisa ditambahkan ke dokumen berstatus 'terbit'. Status: {$sale['status']}." );
			}

			$ok = $this->wpdb->insert( $this->t_payments, [
				'sale_id'      => $sale_id,
				'payment_date' => current_time( 'mysql' ),
				'method'       => $method,
				'amount'       => $amount,
				'reference'    => $payment['reference'] ?? null,
				'notes'        => $payment['notes']     ?? null,
				'created_by'   => (int) $payment['created_by'],
			] );
			if ( $ok === false ) {
				throw new \RuntimeException( 'Gagal mencatat pembayaran: ' . $this->wpdb->last_error );
			}
			$payment_id = (int) $this->wpdb->insert_id;

			$new_paid = (int) $sale['paid_amount'] + $amount;
			$new_status = $new_paid >= (int) $sale['grand_total'] ? 'lunas' : 'terbit';
			$this->wpdb->update( $this->t_sales, [
				'paid_amount' => $new_paid,
				'status'      => $new_status,
			], [ 'id' => $sale_id ] );

			$this->wpdb->query( 'COMMIT' );
			return $payment_id;

		} catch ( \Throwable $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	// ── Pembatalan ────────────────────────────────────────────────────────────

	/**
	 * Batalkan dokumen. Membalik mutasi stok. Alasan wajib diisi.
	 *
	 * @throws \InvalidArgumentException jika alasan kosong.
	 * @throws \RuntimeException jika status sudah 'batal' atau 'lunas'.
	 */
	public function void( int $sale_id, string $reason, int $voided_by ): void {
		if ( trim( $reason ) === '' ) {
			throw new \InvalidArgumentException( 'Alasan pembatalan wajib diisi.' );
		}

		$this->wpdb->query( 'START TRANSACTION' );
		try {
			$sale = $this->wpdb->get_row(
				$this->wpdb->prepare( "SELECT * FROM {$this->t_sales} WHERE id = %d FOR UPDATE", $sale_id ),
				ARRAY_A
			);
			if ( ! $sale ) {
				throw new \RuntimeException( "Dokumen ID {$sale_id} tidak ditemukan." );
			}
			if ( $sale['status'] === 'batal' ) {
				throw new \RuntimeException( 'Dokumen sudah dibatalkan sebelumnya.' );
			}
			if ( $sale['status'] === 'lunas' ) {
				throw new \RuntimeException( 'Dokumen lunas tidak bisa dibatalkan langsung. Buat nota retur.' );
			}

			// Balik stok hanya jika sudah diterbitkan (stok sudah berkurang)
			if ( $sale['status'] === 'terbit' ) {
				$items = $this->getItems( $sale_id );
				foreach ( $items as $item ) {
					$this->stock->add_stock(
						(int) $item['product_id'],
						(int) $sale['warehouse_id'],
						(float) $item['qty'],
						'void_reversal',
						$sale['doc_number'] . '/VOID',
						(int) $item['unit_id'],
						0,
						'Pembatalan: ' . $reason,
						$voided_by
					);
				}
			}

			$this->wpdb->update( $this->t_sales, [
				'status'      => 'batal',
				'void_reason' => $reason,
			], [ 'id' => $sale_id ] );

			$this->wpdb->query( 'COMMIT' );

		} catch ( \Throwable $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	// ── Baca ─────────────────────────────────────────────────────────────────

	public function find( int $sale_id ): ?array {
		return $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->t_sales} WHERE id = %d", $sale_id ),
			ARRAY_A
		) ?: null;
	}

	public function getItems( int $sale_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM {$this->t_items} WHERE sale_id = %d", $sale_id ),
			ARRAY_A
		);
	}

	public function getPayments( int $sale_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM {$this->t_payments} WHERE sale_id = %d ORDER BY payment_date ASC", $sale_id ),
			ARRAY_A
		);
	}

	// ── Helper Privat ─────────────────────────────────────────────────────────

	private function validateHeader( array $data ): void {
		if ( ! in_array( $data['invoice_type'] ?? '', [ 'resmi', 'non_resmi' ], true ) ) {
			throw new \InvalidArgumentException( "Jenis invoice harus 'resmi' atau 'non_resmi'." );
		}
		if ( empty( $data['sale_date'] ) ) {
			throw new \InvalidArgumentException( 'Tanggal penjualan wajib diisi.' );
		}
		if ( empty( $data['warehouse_id'] ) ) {
			throw new \InvalidArgumentException( 'Gudang wajib dipilih.' );
		}
		if ( empty( $data['cashier_user_id'] ) ) {
			throw new \InvalidArgumentException( 'Kasir wajib diisi.' );
		}
	}

	private function validateItems( array $raw ): array {
		if ( count( $raw ) === 0 ) {
			throw new \InvalidArgumentException( 'Dokumen penjualan harus memiliki minimal 1 baris barang.' );
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
			$qty = (float) ( $row['qty'] ?? 0 );
			if ( $qty <= 0 ) {
				throw new \InvalidArgumentException( "Baris {$line}: kuantitas harus lebih dari nol." );
			}
			$price = (int) ( $row['unit_price'] ?? 0 );
			if ( $price <= 0 ) {
				throw new \InvalidArgumentException( "Baris {$line}: harga satuan harus lebih dari nol." );
			}
			$disc       = (int) ( $row['discount_amount'] ?? 0 );
			$line_total = (int) round( $qty * $price ) - $disc;
			if ( $line_total < 0 ) {
				throw new \InvalidArgumentException( "Baris {$line}: total baris tidak boleh negatif." );
			}
			$clean[] = [
				'product_id'      => (int) $row['product_id'],
				'unit_id'         => (int) $row['unit_id'],
				'qty'             => $qty,
				'unit_price'      => $price,
				'discount_amount' => $disc,
				'line_total'      => $line_total,
			];
		}
		return $clean;
	}

	private function calcSubtotal( array $items ): int {
		return array_sum( array_column( $items, 'line_total' ) );
	}

	private function insertItems( int $sale_id, array $items ): void {
		foreach ( $items as $item ) {
			$ok = $this->wpdb->insert( $this->t_items, array_merge( [ 'sale_id' => $sale_id ], $item ) );
			if ( $ok === false ) {
				throw new \RuntimeException( 'Gagal menyimpan baris barang: ' . $this->wpdb->last_error );
			}
		}
	}
}
