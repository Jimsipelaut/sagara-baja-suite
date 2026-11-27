<?php
namespace SagaraBaja\Modules\Purchasing\Admin;

use SagaraBaja\Core\Audit\AuditLogger;
use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Purchasing\Services\GoodsReceiptService;
use SagaraBaja\Modules\Purchasing\Services\PurchaseOrderService;
use SagaraBaja\Modules\Purchasing\Services\SupplierPaymentService;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;

/**
 * PurchasingAdmin
 *
 * Halaman admin WordPress untuk modul Purchasing:
 *   - Daftar PO (filter status) + Detail PO (baris barang, tanda terima,
 *     riwayat pembayaran, sisa hutang, formulir catat pembayaran)
 *   - Daftar Tanda Terima
 *   - Daftar Pembayaran Hutang
 *
 * Setiap aksi POST diperiksa:
 *   1. current_user_can() dengan capability yang tepat
 *      (lihat = sb_view_purchasing, catat pembayaran = sb_manage_purchasing).
 *   2. nonce WordPress.
 *   3. Validasi diserahkan ke Service layer.
 *
 * Catatan: pembuatan PO dan penerimaan barang TIDAK ada di UI ini — keduanya
 * tetap lewat Service (dan tes). UI ini hanya menampilkan dan mencatat
 * pembayaran hutang. Pembayaran hutang dicatat audit log sekali, di dalam
 * SupplierPaymentService (tidak diulang di handler).
 */
class PurchasingAdmin {

	private PurchaseOrderService $po_service;
	private GoodsReceiptService $gr_service;
	private SupplierPaymentService $payment_service;
	private SupplierRepository $suppliers;

	public function __construct(
		PurchaseOrderService $po_service,
		GoodsReceiptService $gr_service,
		SupplierPaymentService $payment_service,
		SupplierRepository $suppliers
	) {
		$this->po_service      = $po_service;
		$this->gr_service      = $gr_service;
		$this->payment_service = $payment_service;
		$this->suppliers       = $suppliers;
	}

	public static function register(): void {
		$instance = self::boot();
		add_action( 'admin_menu', [ $instance, 'addMenus' ] );
		add_action( 'admin_post_sb_supplier_payment', [ $instance, 'handleRecordPayment' ] );
	}

	private static function boot(): self {
		global $wpdb;
		$numbering   = new NumberingService( $wpdb );
		$stock       = new StockService( $wpdb );
		$suppliers   = new SupplierRepository( $wpdb );
		$po_service  = new PurchaseOrderService( $wpdb, $numbering, $suppliers );
		$gr_service  = new GoodsReceiptService( $wpdb, $numbering, $stock, $po_service );
		$payments    = new SupplierPaymentService( $wpdb, $numbering, $po_service, $suppliers );
		return new self( $po_service, $gr_service, $payments, $suppliers );
	}

	// ── Menu ─────────────────────────────────────────────────────────────────

	public function addMenus(): void {
		add_submenu_page( 'sagara-baja', 'Pembelian', 'Pembelian', 'sb_view_purchasing', 'sb-purchasing', [ $this, 'pagePurchaseList' ] );
		add_submenu_page( 'sagara-baja', 'Tanda Terima', 'Tanda Terima', 'sb_view_purchasing', 'sb-receipts', [ $this, 'pageReceipts' ] );
		add_submenu_page( 'sagara-baja', 'Pembayaran Hutang', 'Pembayaran Hutang', 'sb_view_purchasing', 'sb-supplier-payments', [ $this, 'pagePayments' ] );
	}

	// ── Halaman: Daftar PO (+ Detail PO) ─────────────────────────────────────

	public function pagePurchaseList(): void {
		if ( ! current_user_can( 'sb_view_purchasing' ) ) {
			wp_die( 'Akses ditolak.' );
		}

		$po_id = (int) ( $_GET['po_id'] ?? 0 );
		if ( $po_id > 0 ) {
			$this->pagePurchaseDetail( $po_id );
			return;
		}

		global $wpdb;
		$status_filter = sanitize_text_field( $_GET['status'] ?? '' );
		$allowed_status = [ '', 'draft', 'disetujui', 'diterima_sebagian', 'diterima', 'batal' ];
		if ( ! in_array( $status_filter, $allowed_status, true ) ) {
			$status_filter = '';
		}

		$rows = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}sb_purchase_orders ORDER BY po_date DESC, id DESC LIMIT 100",
			ARRAY_A
		);

		echo '<div class="wrap"><h1>Pembelian — Daftar PO</h1>';
		echo '<p class="description">Hutang usaha tercatat saat barang diterima. Pembayaran dicatat dari halaman Detail PO.</p>';

		// Filter status
		echo '<form method="get" style="margin:12px 0">';
		echo '<input type="hidden" name="page" value="sb-purchasing">';
		echo '<label for="status">Status:</label> ';
		echo '<select name="status" id="status" onchange="this.form.submit()">';
		$labels = [ '' => '— Semua —', 'draft' => 'Draft', 'disetujui' => 'Disetujui', 'diterima_sebagian' => 'Diterima Sebagian', 'diterima' => 'Diterima', 'batal' => 'Batal' ];
		foreach ( $labels as $value => $label ) {
			$selected = $status_filter === $value ? ' selected' : '';
			echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></form>';

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr><th>No. PO</th><th>Tanggal</th><th>Supplier</th><th>Total</th><th>Sudah Dibayar</th><th>Sisa Hutang</th><th>Status PO</th><th>Status Bayar</th><th>Aksi</th></tr></thead><tbody>';
		$shown = 0;
		foreach ( $rows as $po ) {
			if ( $status_filter !== '' && $po['status'] !== $status_filter ) {
				continue;
			}
			$shown++;
			$supplier   = $this->suppliers->find( (int) $po['supplier_id'] );
			$outstanding = (int) $po['grand_total'] - (int) $po['paid_amount'];
			$pay_status  = $outstanding === 0 ? 'Lunas' : ( (int) $po['paid_amount'] > 0 ? 'Sebagian' : 'Belum Bayar' );
			$detail_url  = esc_url( admin_url( 'admin.php?page=sb-purchasing&po_id=' . (int) $po['id'] ) );
			echo '<tr>';
			echo '<td>' . esc_html( $po['doc_number'] ) . '</td>';
			echo '<td>' . esc_html( $po['po_date'] ) . '</td>';
			echo '<td>' . esc_html( $supplier['name'] ?? 'ID#' . (int) $po['supplier_id'] ) . '</td>';
			echo '<td>Rp ' . esc_html( number_format( (int) $po['grand_total'], 0, ',', '.' ) ) . '</td>';
			echo '<td>Rp ' . esc_html( number_format( (int) $po['paid_amount'], 0, ',', '.' ) ) . '</td>';
			echo '<td><strong>Rp ' . esc_html( number_format( $outstanding, 0, ',', '.' ) ) . '</strong></td>';
			echo '<td>' . esc_html( $this->poStatusLabel( $po['status'] ) ) . '</td>';
			echo '<td>' . esc_html( $pay_status ) . '</td>';
			echo '<td><a href="' . $detail_url . '">Detail</a></td>';
			echo '</tr>';
		}
		if ( $shown === 0 ) {
			echo '<tr><td colspan="9">Belum ada PO.</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Detail satu PO: ringkasan hutang, baris barang, tanda terima,
	 * riwayat pembayaran, dan formulir catat pembayaran.
	 */
	private function pagePurchaseDetail( int $po_id ): void {
		$po = $this->po_service->find( $po_id );
		if ( ! $po ) {
			wp_die( 'PO tidak ditemukan.' );
		}
		$supplier  = $this->suppliers->find( (int) $po['supplier_id'] );
		$items     = $this->po_service->getItems( $po_id );
		$receipts  = $this->po_service->getReceipts( $po_id );
		$payments  = $this->payment_service->getPaymentsByPo( $po_id );
		$out       = $this->payment_service->getOutstanding( $po_id );
		$can_pay   = current_user_can( 'sb_manage_purchasing' )
			&& in_array( $po['status'], [ 'disetujui', 'diterima_sebagian', 'diterima' ], true )
			&& count( $receipts ) > 0
			&& $out['outstanding'] > 0;
		$just_paid = isset( $_GET['paid'] ) && $_GET['paid'] === '1';

		$back_url = esc_url( admin_url( 'admin.php?page=sb-purchasing' ) );
		echo '<div class="wrap"><h1>Detail PO ' . esc_html( $po['doc_number'] ) . '</h1>';
		echo '<p><a href="' . $back_url . '">&larr; Kembali ke Daftar PO</a></p>';

		if ( $just_paid ) {
			echo '<div class="notice notice-success is-dismissible"><p>Pembayaran hutang berhasil dicatat.</p></div>';
		}

		// Ringkasan hutang
		$pay_label = $out['status'] === 'lunas' ? 'Lunas' : ( $out['status'] === 'sebagian' ? 'Sebagian' : 'Belum Bayar' );
		echo '<div style="background:#fff;padding:16px;border:1px solid #ccd0d4;margin-bottom:20px">';
		echo '<p><strong>Supplier:</strong> ' . esc_html( $supplier['name'] ?? 'ID#' . (int) $po['supplier_id'] ) . '</p>';
		echo '<p><strong>Tanggal PO:</strong> ' . esc_html( $po['po_date'] ) . ' &nbsp;|&nbsp; <strong>Status PO:</strong> ' . esc_html( $this->poStatusLabel( $po['status'] ) ) . '</p>';
		echo '<p><strong>Total PO:</strong> Rp ' . esc_html( number_format( $out['grand_total'], 0, ',', '.' ) ) . '</p>';
		echo '<p><strong>Sudah Dibayar:</strong> Rp ' . esc_html( number_format( $out['paid_amount'], 0, ',', '.' ) ) . '</p>';
		echo '<p><strong>Sisa Hutang:</strong> Rp ' . esc_html( number_format( $out['outstanding'], 0, ',', '.' ) ) . ' &nbsp;|&nbsp; <strong>Status Bayar:</strong> ' . esc_html( $pay_label ) . '</p>';
		if ( $po['status'] === 'batal' && ! empty( $po['cancel_reason'] ) ) {
			echo '<p><strong>Alasan Batal:</strong> ' . esc_html( $po['cancel_reason'] ) . '</p>';
		}
		echo '</div>';

		// Baris barang
		echo '<h2>Baris Barang</h2>';
		echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Produk</th><th>Qty Dipesan</th><th>Qty Diterima</th><th>Harga Satuan</th><th>Total Baris</th></tr></thead><tbody>';
		foreach ( $items as $item ) {
			echo '<tr>';
			echo '<td>' . esc_html( ( $item['product_sku'] ?? '' ) . ' — ' . ( $item['product_name'] ?? 'ID#' . (int) $item['product_id'] ) ) . '</td>';
			echo '<td>' . esc_html( (string) $item['qty_ordered'] . ' ' . ( $item['unit_name'] ?? '' ) ) . '</td>';
			echo '<td>' . esc_html( (string) $item['qty_received'] ) . '</td>';
			echo '<td>Rp ' . esc_html( number_format( (int) $item['unit_cost'], 0, ',', '.' ) ) . '</td>';
			echo '<td>Rp ' . esc_html( number_format( (int) $item['line_total'], 0, ',', '.' ) ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		// Tanda terima
		echo '<h2>Tanda Terima</h2>';
		if ( empty( $receipts ) ) {
			echo '<p>Belum ada penerimaan barang untuk PO ini.</p>';
		} else {
			echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>No. Tanda Terima</th><th>Tanggal</th><th>Barang Diterima</th></tr></thead><tbody>';
			foreach ( $receipts as $r ) {
				$r_items = $this->gr_service->getReceiptItems( (int) $r['id'] );
				$lines   = [];
				foreach ( $r_items as $ri ) {
					$lines[] = ( $ri['product_sku'] ?? '' ) . ' — ' . ( $ri['product_name'] ?? 'ID#' . (int) $ri['product_id'] ) . ': ' . (string) $ri['qty_received'];
				}
				echo '<tr>';
				echo '<td>' . esc_html( $r['receipt_number'] ) . '</td>';
				echo '<td>' . esc_html( $r['receipt_date'] ) . '</td>';
				echo '<td>' . esc_html( implode( '; ', $lines ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		// Riwayat pembayaran
		echo '<h2>Riwayat Pembayaran Hutang</h2>';
		if ( empty( $payments ) ) {
			echo '<p>Belum ada pembayaran untuk PO ini.</p>';
		} else {
			echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>No. Pembayaran</th><th>Tanggal</th><th>Metode</th><th>Jumlah</th><th>Referensi</th><th>Catatan</th></tr></thead><tbody>';
			foreach ( $payments as $pay ) {
				echo '<tr>';
				echo '<td>' . esc_html( $pay['payment_number'] ) . '</td>';
				echo '<td>' . esc_html( $pay['payment_date'] ) . '</td>';
				echo '<td>' . esc_html( $pay['method'] === 'transfer' ? 'Transfer' : 'Tunai' ) . '</td>';
				echo '<td>Rp ' . esc_html( number_format( (int) $pay['amount'], 0, ',', '.' ) ) . '</td>';
				echo '<td>' . esc_html( $pay['reference'] ?? '-' ) . '</td>';
				echo '<td>' . esc_html( $pay['notes'] ?? '-' ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		// Formulir catat pembayaran
		if ( $can_pay ) {
			$nonce = wp_create_nonce( 'sb_supplier_payment' );
			// C-13: satu token idempotensi per tampilan formulir. Pengiriman
			// ulang (klik ganda / refresh setelah submit) memakai kunci yang
			// sama dan mengembalikan pembayaran lama, bukan mencatat baru.
			$idem_key = wp_generate_uuid4();
			echo '<h2>Catat Pembayaran Hutang</h2>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="background:#fff;padding:16px;border:1px solid #ccd0d4;max-width:600px">';
			echo '<input type="hidden" name="action" value="sb_supplier_payment">';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
			echo '<input type="hidden" name="idempotency_key" value="' . esc_attr( $idem_key ) . '">';
			echo '<input type="hidden" name="po_id" value="' . esc_attr( $po_id ) . '">';
			echo '<p><label>Jumlah Bayar (Rp):</label><br><input type="number" name="amount" value="' . esc_attr( $out['outstanding'] ) . '" min="1" max="' . esc_attr( $out['outstanding'] ) . '" required></p>';
			echo '<p class="description">Sisa hutang: Rp ' . esc_html( number_format( $out['outstanding'], 0, ',', '.' ) ) . '. Jumlah bayar tidak boleh melebihi sisa hutang.</p>';
			echo '<p><label>Metode:</label><br><select name="method"><option value="tunai">Tunai</option><option value="transfer">Transfer</option></select></p>';
			echo '<p><label>Tanggal Bayar:</label><br><input type="date" name="payment_date" value="' . esc_attr( date( 'Y-m-d' ) ) . '" required></p>';
			echo '<p><label>Referensi (no. bukti transfer / kuitansi):</label><br><input type="text" name="reference" style="width:400px"></p>';
			echo '<p><label>Catatan:</label><br><input type="text" name="notes" style="width:400px"></p>';
			echo '<button type="submit" class="button button-primary">Simpan Pembayaran</button>';
			echo '</form>';
		} elseif ( $out['outstanding'] <= 0 ) {
			echo '<p><strong>PO ini sudah lunas.</strong></p>';
		} elseif ( ! current_user_can( 'sb_manage_purchasing' ) ) {
			echo '<p>Anda hanya bisa melihat — pencatatan pembayaran butuh hak kelola pembelian.</p>';
		} elseif ( empty( $receipts ) ) {
			echo '<p>Pembayaran baru bisa dicatat setelah ada penerimaan barang.</p>';
		}

		echo '</div>';
	}

	// ── Halaman: Daftar Tanda Terima ─────────────────────────────────────────

	public function pageReceipts(): void {
		if ( ! current_user_can( 'sb_view_purchasing' ) ) {
			wp_die( 'Akses ditolak.' );
		}
		global $wpdb;
		$receipts = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}sb_goods_receipts ORDER BY receipt_date DESC, id DESC LIMIT 100",
			ARRAY_A
		);

		echo '<div class="wrap"><h1>Pembelian — Daftar Tanda Terima</h1>';
		echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>No. Tanda Terima</th><th>Tanggal</th><th>No. PO</th><th>Barang Diterima</th></tr></thead><tbody>';
		if ( empty( $receipts ) ) {
			echo '<tr><td colspan="4">Belum ada tanda terima.</td></tr>';
		}
		foreach ( $receipts as $r ) {
			$po      = $this->po_service->find( (int) $r['po_id'] );
			$r_items = $this->gr_service->getReceiptItems( (int) $r['id'] );
			$lines   = [];
			foreach ( $r_items as $ri ) {
				$lines[] = ( $ri['product_sku'] ?? '' ) . ' — ' . ( $ri['product_name'] ?? 'ID#' . (int) $ri['product_id'] ) . ': ' . (string) $ri['qty_received'];
			}
			$po_url = esc_url( admin_url( 'admin.php?page=sb-purchasing&po_id=' . (int) $r['po_id'] ) );
			echo '<tr>';
			echo '<td>' . esc_html( $r['receipt_number'] ) . '</td>';
			echo '<td>' . esc_html( $r['receipt_date'] ) . '</td>';
			echo '<td><a href="' . $po_url . '">' . esc_html( $po['doc_number'] ?? 'PO#' . (int) $r['po_id'] ) . '</a></td>';
			echo '<td>' . esc_html( implode( '; ', $lines ) ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	// ── Halaman: Daftar Pembayaran Hutang ────────────────────────────────────

	public function pagePayments(): void {
		if ( ! current_user_can( 'sb_view_purchasing' ) ) {
			wp_die( 'Akses ditolak.' );
		}
		$payments = $this->payment_service->listPayments( 100 );

		echo '<div class="wrap"><h1>Pembelian — Daftar Pembayaran Hutang</h1>';
		$total = 0;
		foreach ( $payments as $pay ) {
			$total += (int) $pay['amount'];
		}
		echo '<p>Total pembayaran tercatat: <strong>Rp ' . esc_html( number_format( $total, 0, ',', '.' ) ) . '</strong></p>';
		echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>No. Pembayaran</th><th>Tanggal</th><th>No. PO</th><th>Supplier</th><th>Metode</th><th>Jumlah</th><th>Referensi</th></tr></thead><tbody>';
		if ( empty( $payments ) ) {
			echo '<tr><td colspan="7">Belum ada pembayaran hutang.</td></tr>';
		}
		foreach ( $payments as $pay ) {
			$po       = $this->po_service->find( (int) $pay['purchase_order_id'] );
			$supplier = $this->suppliers->find( (int) $pay['supplier_id'] );
			$po_url   = esc_url( admin_url( 'admin.php?page=sb-purchasing&po_id=' . (int) $pay['purchase_order_id'] ) );
			echo '<tr>';
			echo '<td>' . esc_html( $pay['payment_number'] ) . '</td>';
			echo '<td>' . esc_html( $pay['payment_date'] ) . '</td>';
			echo '<td><a href="' . $po_url . '">' . esc_html( $po['doc_number'] ?? 'PO#' . (int) $pay['purchase_order_id'] ) . '</a></td>';
			echo '<td>' . esc_html( $supplier['name'] ?? 'ID#' . (int) $pay['supplier_id'] ) . '</td>';
			echo '<td>' . esc_html( $pay['method'] === 'transfer' ? 'Transfer' : 'Tunai' ) . '</td>';
			echo '<td>Rp ' . esc_html( number_format( (int) $pay['amount'], 0, ',', '.' ) ) . '</td>';
			echo '<td>' . esc_html( $pay['reference'] ?? '-' ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	// ── Handler POST ──────────────────────────────────────────────────────────

	public function handleRecordPayment(): void {
		if ( ! current_user_can( 'sb_manage_purchasing' ) ) {
			wp_die( 'Akses ditolak.', 403 );
		}
		check_admin_referer( 'sb_supplier_payment' );

		$po_id = (int) ( $_POST['po_id'] ?? 0 );
		try {
			$result = $this->payment_service->recordPayment( $po_id, [
				'amount'          => (int) ( $_POST['amount'] ?? 0 ),
				'method'          => sanitize_text_field( $_POST['method'] ?? 'tunai' ),
				'payment_date'    => sanitize_text_field( $_POST['payment_date'] ?? '' ),
				'reference'       => sanitize_text_field( $_POST['reference'] ?? '' ),
				'notes'           => sanitize_textarea_field( $_POST['notes'] ?? '' ),
				'idempotency_key' => sanitize_text_field( $_POST['idempotency_key'] ?? '' ),
				'created_by'      => get_current_user_id(),
			] );
			// Audit log sudah dicatat di dalam SupplierPaymentService — tidak diulang di sini.
			wp_redirect( admin_url( 'admin.php?page=sb-purchasing&po_id=' . $po_id . '&paid=1&payment=' . (int) $result['payment_id'] ) );
		} catch ( \Throwable $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
		exit;
	}

	// ── Helper ────────────────────────────────────────────────────────────────

	private function poStatusLabel( string $status ): string {
		return [
			'draft'            => 'Draft',
			'disetujui'        => 'Disetujui',
			'diterima_sebagian' => 'Diterima Sebagian',
			'diterima'         => 'Diterima',
			'batal'            => 'Batal',
		][ $status ] ?? $status;
	}
}
