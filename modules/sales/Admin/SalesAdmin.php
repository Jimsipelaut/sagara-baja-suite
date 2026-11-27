<?php
namespace SagaraBaja\Modules\Sales\Admin;

use SagaraBaja\Modules\Sales\Services\SaleService;
use SagaraBaja\Modules\Sales\Services\ShiftService;
use SagaraBaja\Modules\Sales\Services\PdfInvoiceService;
use SagaraBaja\Modules\Sales\Repositories\CustomerRepository;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;
use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Core\Audit\AuditLogger;
use SagaraBaja\Core\Settings\SettingsRepository;

/**
 * SalesAdmin
 *
 * Halaman admin WordPress untuk modul Sales:
 *   - Master Pelanggan
 *   - Master Supplier
 *   - Daftar & Buat Penjualan (resmi & non-resmi)
 *   - Manajemen Shift Kasir
 *   - Cetak Invoice (HTML print)
 *
 * Setiap aksi POST diperiksa:
 *   1. current_user_can() dengan capability yang tepat.
 *   2. nonce WordPress.
 *   3. Validasi diserahkan ke Service layer.
 */
class SalesAdmin {

	private SaleService $sale_service;
	private ShiftService $shift_service;
	private PdfInvoiceService $pdf_service;
	private CustomerRepository $customers;
	private SupplierRepository $suppliers;
	private AuditLogger $audit;
	private SettingsRepository $settings;

	public function __construct(
		SaleService $sale_service,
		ShiftService $shift_service,
		PdfInvoiceService $pdf_service,
		CustomerRepository $customers,
		SupplierRepository $suppliers,
		AuditLogger $audit,
		SettingsRepository $settings
	) {
		$this->sale_service  = $sale_service;
		$this->shift_service = $shift_service;
		$this->pdf_service   = $pdf_service;
		$this->customers     = $customers;
		$this->suppliers     = $suppliers;
		$this->audit         = $audit;
		$this->settings      = $settings;
	}

	public static function register(): void {
		$instance = self::boot();
		add_action( 'admin_menu', [ $instance, 'addMenus' ] );
		add_action( 'admin_post_sb_sale_draft',    [ $instance, 'handleSaleDraft' ] );
		add_action( 'admin_post_sb_sale_publish',  [ $instance, 'handleSalePublish' ] );
		add_action( 'admin_post_sb_sale_void',     [ $instance, 'handleSaleVoid' ] );
		add_action( 'admin_post_sb_add_payment',   [ $instance, 'handleAddPayment' ] );
		add_action( 'admin_post_sb_shift_open',    [ $instance, 'handleShiftOpen' ] );
		add_action( 'admin_post_sb_shift_close',   [ $instance, 'handleShiftClose' ] );
		add_action( 'admin_post_sb_save_customer', [ $instance, 'handleSaveCustomer' ] );
		add_action( 'admin_post_sb_save_supplier', [ $instance, 'handleSaveSupplier' ] );
		// Cetak invoice: GET param action=sb_print_invoice
		add_action( 'admin_post_sb_print_invoice', [ $instance, 'handlePrintInvoice' ] );
	}

	private static function boot(): self {
		global $wpdb;
		$numbering   = new NumberingService( $wpdb );
		$stock       = new StockService( $wpdb );
		$audit       = new AuditLogger( $wpdb );
		$settings    = new SettingsRepository( $wpdb );
		return new self(
			new SaleService( $wpdb, $numbering, $stock ),
			new ShiftService( $wpdb ),
			new PdfInvoiceService(),
			new CustomerRepository( $wpdb ),
			new SupplierRepository( $wpdb ),
			$audit,
			$settings
		);
	}

	// ── Menu ─────────────────────────────────────────────────────────────────

	public function addMenus(): void {
		add_submenu_page( 'sagara-baja', 'Penjualan',  'Penjualan',  'sb_view_sales',     'sb-sales',     [ $this, 'pageSaleList' ] );
		add_submenu_page( 'sagara-baja', 'Kasir / POS', 'Kasir / POS', 'sb_process_sale', 'sb-pos',       [ $this, 'pagePOS' ] );
		add_submenu_page( 'sagara-baja', 'Pelanggan',  'Pelanggan',  'sb_view_sales',     'sb-customers', [ $this, 'pageCustomers' ] );
		add_submenu_page( 'sagara-baja', 'Supplier',   'Supplier',   'sb_view_sales',     'sb-suppliers', [ $this, 'pageSuppliers' ] );
		add_submenu_page( 'sagara-baja', 'Shift Kasir','Shift Kasir','sb_manage_shifts',  'sb-shifts',    [ $this, 'pageShifts' ] );
	}

	// ── Halaman: Daftar Penjualan ─────────────────────────────────────────────

	public function pageSaleList(): void {
		if ( ! current_user_can( 'sb_view_sales' ) ) {
			wp_die( 'Akses ditolak.' );
		}
		global $wpdb;
		$sales = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}sb_sales ORDER BY created_at DESC LIMIT 100",
			ARRAY_A
		);
		echo '<div class="wrap"><h1>Daftar Penjualan</h1>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=sb-pos' ) ) . '" class="button button-primary">+ Transaksi Baru</a>';
		echo '<table class="wp-list-table widefat fixed striped" style="margin-top:16px">';
		echo '<thead><tr><th>No. Dokumen</th><th>Jenis</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead><tbody>';
		foreach ( $sales as $s ) {
			$print_url = esc_url( admin_url( 'admin-post.php?action=sb_print_invoice&id=' . $s['id'] . '&_wpnonce=' . wp_create_nonce( 'sb_print_' . $s['id'] ) ) );
			echo '<tr>';
			echo '<td>' . esc_html( $s['doc_number'] ) . '</td>';
			echo '<td>' . esc_html( $s['invoice_type'] === 'resmi' ? 'Invoice Resmi' : 'Nota' ) . '</td>';
			echo '<td>' . esc_html( $s['sale_date'] ) . '</td>';
			echo '<td>' . esc_html( $s['customer_id'] ? "ID#{$s['customer_id']}" : 'Umum' ) . '</td>';
			echo '<td>Rp ' . esc_html( number_format( (int) $s['grand_total'], 0, ',', '.' ) ) . '</td>';
			echo '<td>' . esc_html( $s['status'] ) . '</td>';
			echo '<td><a href="' . $print_url . '" target="_blank">Cetak</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	// ── Halaman: POS / Buat Penjualan ────────────────────────────────────────

	public function pagePOS(): void {
		if ( ! current_user_can( 'sb_process_sale' ) ) {
			wp_die( 'Akses ditolak.' );
		}
		global $wpdb;
		$products  = $wpdb->get_results( "SELECT p.*, u.name as unit_name FROM {$wpdb->prefix}sb_products p LEFT JOIN {$wpdb->prefix}sb_units u ON u.id = p.base_unit_id WHERE p.is_active = 1 ORDER BY p.name", ARRAY_A );
		$customers = $this->customers->list();
		$warehouses= $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_warehouses WHERE is_active = 1", ARRAY_A );
		$nonce     = wp_create_nonce( 'sb_sale_draft' );

		echo '<div class="wrap"><h1>Kasir / POS — Transaksi Baru</h1>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="sb_sale_draft">';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';

		// Jenis invoice
		echo '<p><label><strong>Jenis Dokumen:</strong></label><br>';
		echo '<select name="invoice_type"><option value="non_resmi">Nota (Non-Resmi)</option><option value="resmi">Invoice Resmi</option></select></p>';

		// Tanggal & gudang
		echo '<p><label>Tanggal:</label><br><input type="date" name="sale_date" value="' . esc_attr( date( 'Y-m-d' ) ) . '" required></p>';
		echo '<p><label>Gudang:</label><br><select name="warehouse_id" required>';
		foreach ( $warehouses as $w ) {
			echo '<option value="' . esc_attr( $w['id'] ) . '">' . esc_html( $w['name'] ) . '</option>';
		}
		echo '</select></p>';

		// Pelanggan (opsional)
		echo '<p><label>Pelanggan (opsional):</label><br><select name="customer_id"><option value="">— Umum —</option>';
		foreach ( $customers as $c ) {
			echo '<option value="' . esc_attr( $c['id'] ) . '">' . esc_html( $c['name'] ) . '</option>';
		}
		echo '</select></p>';

		// Diskon dokumen
		echo '<p><label>Diskon Dokumen (Rp):</label><br><input type="number" name="discount_amount" value="0" min="0"></p>';

		// Baris barang
		echo '<h3>Baris Barang</h3>';
		echo '<table class="wp-list-table widefat fixed">';
		echo '<thead><tr><th>Produk</th><th>Qty</th><th>Harga Satuan (Rp)</th><th>Diskon Baris (Rp)</th></tr></thead>';
		echo '<tbody id="pos-rows">';
		echo '<tr>';
		echo '<td><select name="items[0][product_id]" required>';
		foreach ( $products as $p ) {
			echo '<option value="' . esc_attr( $p['id'] ) . '" data-unit="' . esc_attr( $p['base_unit_id'] ) . '" data-price="' . esc_attr( $p['sell_price_retail'] ) . '">' . esc_html( $p['sku'] . ' — ' . $p['name'] ) . '</option>';
		}
		echo '</select><input type="hidden" name="items[0][unit_id]" value="1"></td>';
		echo '<td><input type="number" name="items[0][qty]" value="1" min="0.01" step="0.01" required></td>';
		echo '<td><input type="number" name="items[0][unit_price]" value="" placeholder="Harga" required></td>';
		echo '<td><input type="number" name="items[0][discount_amount]" value="0" min="0"></td>';
		echo '</tr>';
		echo '</tbody></table>';

		echo '<p><input type="hidden" name="cashier_user_id" value="' . esc_attr( get_current_user_id() ) . '">';
		echo '<button type="submit" class="button button-primary" style="margin-top:16px">Simpan sebagai Draft</button></p>';
		echo '</form></div>';
	}

	// ── Halaman: Pelanggan ────────────────────────────────────────────────────

	public function pageCustomers(): void {
		if ( ! current_user_can( 'sb_view_sales' ) ) {
			wp_die( 'Akses ditolak.' );
		}
		$list  = $this->customers->list( sanitize_text_field( $_GET['s'] ?? '' ) );
		$nonce = wp_create_nonce( 'sb_save_customer' );

		echo '<div class="wrap"><h1>Master Pelanggan</h1>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="background:#fff;padding:16px;margin-bottom:20px;border:1px solid #ccd0d4">';
		echo '<h3 style="margin-top:0">Tambah Pelanggan</h3>';
		echo '<input type="hidden" name="action" value="sb_save_customer">';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
		echo '<p><label>Kode:</label><br><input type="text" name="code" required></p>';
		echo '<p><label>Nama:</label><br><input type="text" name="name" required style="width:400px"></p>';
		echo '<p><label>Telepon:</label><br><input type="text" name="phone"></p>';
		echo '<p><label>Alamat:</label><br><textarea name="address" rows="2" style="width:400px"></textarea></p>';
		echo '<p><label>Limit Kredit (Rp):</label><br><input type="number" name="credit_limit" value="0" min="0"></p>';
		echo '<button type="submit" class="button button-primary">Simpan</button>';
		echo '</form>';

		echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Kode</th><th>Nama</th><th>Telepon</th><th>Limit Kredit</th></tr></thead><tbody>';
		foreach ( $list as $c ) {
			echo '<tr><td>' . esc_html( $c['code'] ) . '</td><td>' . esc_html( $c['name'] ) . '</td><td>' . esc_html( $c['phone'] ?? '-' ) . '</td><td>Rp ' . esc_html( number_format( (int) $c['credit_limit'], 0, ',', '.' ) ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	// ── Halaman: Supplier ─────────────────────────────────────────────────────

	public function pageSuppliers(): void {
		if ( ! current_user_can( 'sb_view_sales' ) ) {
			wp_die( 'Akses ditolak.' );
		}
		$list  = $this->suppliers->list( sanitize_text_field( $_GET['s'] ?? '' ) );
		$nonce = wp_create_nonce( 'sb_save_supplier' );

		echo '<div class="wrap"><h1>Master Supplier</h1>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="background:#fff;padding:16px;margin-bottom:20px;border:1px solid #ccd0d4">';
		echo '<h3 style="margin-top:0">Tambah Supplier</h3>';
		echo '<input type="hidden" name="action" value="sb_save_supplier">';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
		echo '<p><label>Kode:</label><br><input type="text" name="code" required></p>';
		echo '<p><label>Nama:</label><br><input type="text" name="name" required style="width:400px"></p>';
		echo '<p><label>Telepon:</label><br><input type="text" name="phone"></p>';
		echo '<p><label>Alamat:</label><br><textarea name="address" rows="2" style="width:400px"></textarea></p>';
		echo '<button type="submit" class="button button-primary">Simpan</button>';
		echo '</form>';

		echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Kode</th><th>Nama</th><th>Telepon</th></tr></thead><tbody>';
		foreach ( $list as $s ) {
			echo '<tr><td>' . esc_html( $s['code'] ) . '</td><td>' . esc_html( $s['name'] ) . '</td><td>' . esc_html( $s['phone'] ?? '-' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	// ── Halaman: Shift Kasir ──────────────────────────────────────────────────

	public function pageShifts(): void {
		if ( ! current_user_can( 'sb_manage_shifts' ) ) {
			wp_die( 'Akses ditolak.' );
		}
		global $wpdb;
		$open_nonce  = wp_create_nonce( 'sb_shift_open' );
		$close_nonce = wp_create_nonce( 'sb_shift_close' );
		$warehouses  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_warehouses WHERE is_active = 1", ARRAY_A );
		$uid         = get_current_user_id();
		$open_shift  = null;
		if ( ! empty( $warehouses ) ) {
			$open_shift = $this->shift_service->getOpenShift( $uid, (int) $warehouses[0]['id'] );
		}

		echo '<div class="wrap"><h1>Manajemen Shift Kasir</h1>';
		if ( $open_shift ) {
			echo '<div style="background:#e7f7e7;padding:12px;border-left:4px solid #46b450;margin-bottom:16px">';
			echo '<strong>Shift Aktif</strong> — Dibuka: ' . esc_html( $open_shift['opened_at'] ) . ' | Modal: Rp ' . esc_html( number_format( (int) $open_shift['opening_cash'], 0, ',', '.' ) );
			echo '</div>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			echo '<input type="hidden" name="action" value="sb_shift_close">';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $close_nonce ) . '">';
			echo '<input type="hidden" name="shift_id" value="' . esc_attr( $open_shift['id'] ) . '">';
			echo '<p><label>Uang Fisik Akhir Shift (Rp):</label><br><input type="number" name="closing_cash" value="0" min="0" required></p>';
			echo '<p><label>Catatan:</label><br><input type="text" name="notes" style="width:400px"></p>';
			echo '<button type="submit" class="button button-secondary">Tutup Shift</button>';
			echo '</form>';
		} else {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			echo '<input type="hidden" name="action" value="sb_shift_open">';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $open_nonce ) . '">';
			echo '<p><label>Gudang:</label><br><select name="warehouse_id" required>';
			foreach ( $warehouses as $w ) {
				echo '<option value="' . esc_attr( $w['id'] ) . '">' . esc_html( $w['name'] ) . '</option>';
			}
			echo '</select></p>';
			echo '<p><label>Modal Awal (Rp):</label><br><input type="number" name="opening_cash" value="0" min="0"></p>';
			echo '<button type="submit" class="button button-primary">Buka Shift</button>';
			echo '</form>';
		}
		echo '</div>';
	}

	// ── Handler POST ──────────────────────────────────────────────────────────

	public function handleSaleDraft(): void {
		if ( ! current_user_can( 'sb_process_sale' ) ) wp_die( 'Akses ditolak.' );
		check_admin_referer( 'sb_sale_draft' );

		$items_raw = $_POST['items'] ?? [];
		$items     = [];
		foreach ( $items_raw as $row ) {
			$items[] = [
				'product_id'      => (int) ( $row['product_id'] ?? 0 ),
				'unit_id'         => (int) ( $row['unit_id'] ?? 1 ),
				'qty'             => (float) ( $row['qty'] ?? 0 ),
				'unit_price'      => (int) ( $row['unit_price'] ?? 0 ),
				'discount_amount' => (int) ( $row['discount_amount'] ?? 0 ),
			];
		}

		try {
			$sale_id = $this->sale_service->createDraft( [
				'invoice_type'    => sanitize_text_field( $_POST['invoice_type'] ?? 'non_resmi' ),
				'sale_date'       => sanitize_text_field( $_POST['sale_date'] ?? date( 'Y-m-d' ) ),
				'warehouse_id'    => (int) ( $_POST['warehouse_id'] ?? 0 ),
				'cashier_user_id' => (int) ( $_POST['cashier_user_id'] ?? get_current_user_id() ),
				'customer_id'     => (int) ( $_POST['customer_id'] ?? 0 ) ?: null,
				'discount_amount' => (int) ( $_POST['discount_amount'] ?? 0 ),
				'notes'           => sanitize_textarea_field( $_POST['notes'] ?? '' ),
				'items'           => $items,
			] );
			$this->audit->log( 'sale_draft', 'sb_sales', $sale_id, null, [ 'created_by' => get_current_user_id() ] );
			wp_redirect( admin_url( 'admin.php?page=sb-sales&created=' . $sale_id ) );
		} catch ( \Throwable $e ) {
			wp_die( esc_html( 'Gagal membuat draft: ' . $e->getMessage() ) );
		}
		exit;
	}

	public function handleSalePublish(): void {
		if ( ! current_user_can( 'sb_process_sale' ) ) wp_die( 'Akses ditolak.' );
		check_admin_referer( 'sb_sale_publish' );
		$id = (int) ( $_POST['sale_id'] ?? 0 );
		try {
			$this->sale_service->publish( $id );
			$this->audit->log( 'sale_publish', 'sb_sales', $id, null, [] );
			wp_redirect( admin_url( 'admin.php?page=sb-sales&published=' . $id ) );
		} catch ( \Throwable $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
		exit;
	}

	public function handleSaleVoid(): void {
		if ( ! current_user_can( 'sb_process_sale' ) ) wp_die( 'Akses ditolak.' );
		check_admin_referer( 'sb_sale_void' );
		$id     = (int) ( $_POST['sale_id'] ?? 0 );
		$reason = sanitize_textarea_field( $_POST['void_reason'] ?? '' );
		try {
			$this->sale_service->void( $id, $reason, get_current_user_id() );
			$this->audit->log( 'sale_void', 'sb_sales', $id, null, [ 'reason' => $reason ] );
			wp_redirect( admin_url( 'admin.php?page=sb-sales&voided=' . $id ) );
		} catch ( \Throwable $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
		exit;
	}

	public function handleAddPayment(): void {
		if ( ! current_user_can( 'sb_process_sale' ) ) wp_die( 'Akses ditolak.' );
		check_admin_referer( 'sb_add_payment' );
		$sale_id = (int) ( $_POST['sale_id'] ?? 0 );
		// C-13: bila pengiriman ini sudah pernah tercatat (klik ganda),
		// langsung kembali tanpa audit log baru — service mengembalikan
		// pembayaran lama, bukan mencatat yang baru.
		$idem_key = sanitize_text_field( $_POST['idempotency_key'] ?? '' );
		if ( $idem_key !== '' ) {
			$existing = $this->sale_service->findByIdempotencyKey( $sale_id, $idem_key );
			if ( $existing !== null ) {
				wp_redirect( admin_url( 'admin.php?page=sb-sales&paid=' . $sale_id ) );
				exit;
			}
		}
		try {
			$pid = $this->sale_service->addPayment( $sale_id, [
				'method'          => sanitize_text_field( $_POST['method'] ?? 'tunai' ),
				'amount'          => (int) ( $_POST['amount'] ?? 0 ),
				'reference'       => sanitize_text_field( $_POST['reference'] ?? '' ),
				'notes'           => sanitize_textarea_field( $_POST['notes'] ?? '' ),
				'idempotency_key' => $idem_key,
				'created_by'      => get_current_user_id(),
			] );
			$this->audit->log( 'payment_added', 'sb_payments', $pid, null, [] );
			wp_redirect( admin_url( 'admin.php?page=sb-sales&paid=' . $sale_id ) );
		} catch ( \Throwable $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
		exit;
	}

	public function handleShiftOpen(): void {
		if ( ! current_user_can( 'sb_manage_shifts' ) ) wp_die( 'Akses ditolak.' );
		check_admin_referer( 'sb_shift_open' );
		try {
			$sid = $this->shift_service->open(
				get_current_user_id(),
				(int) ( $_POST['warehouse_id'] ?? 0 ),
				(int) ( $_POST['opening_cash'] ?? 0 )
			);
			$this->audit->log( 'shift_open', 'sb_cashier_shifts', $sid, null, [] );
			wp_redirect( admin_url( 'admin.php?page=sb-shifts&opened=1' ) );
		} catch ( \Throwable $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
		exit;
	}

	public function handleShiftClose(): void {
		if ( ! current_user_can( 'sb_manage_shifts' ) ) wp_die( 'Akses ditolak.' );
		check_admin_referer( 'sb_shift_close' );
		try {
			$this->shift_service->close(
				(int) ( $_POST['shift_id'] ?? 0 ),
				(int) ( $_POST['closing_cash'] ?? 0 ),
				sanitize_text_field( $_POST['notes'] ?? '' )
			);
			$this->audit->log( 'shift_close', 'sb_cashier_shifts', (int) $_POST['shift_id'], null, [] );
			wp_redirect( admin_url( 'admin.php?page=sb-shifts&closed=1' ) );
		} catch ( \Throwable $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
		exit;
	}

	public function handleSaveCustomer(): void {
		if ( ! current_user_can( 'sb_view_sales' ) ) wp_die( 'Akses ditolak.' );
		check_admin_referer( 'sb_save_customer' );
		try {
			$id = $this->customers->create( [
				'code'         => sanitize_text_field( $_POST['code'] ?? '' ),
				'name'         => sanitize_text_field( $_POST['name'] ?? '' ),
				'phone'        => sanitize_text_field( $_POST['phone'] ?? '' ),
				'address'      => sanitize_textarea_field( $_POST['address'] ?? '' ),
				'credit_limit' => (int) ( $_POST['credit_limit'] ?? 0 ),
			] );
			$this->audit->log( 'customer_created', 'sb_customers', $id, null, [] );
			wp_redirect( admin_url( 'admin.php?page=sb-customers&saved=1' ) );
		} catch ( \Throwable $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
		exit;
	}

	public function handleSaveSupplier(): void {
		if ( ! current_user_can( 'sb_view_sales' ) ) wp_die( 'Akses ditolak.' );
		check_admin_referer( 'sb_save_supplier' );
		try {
			$id = $this->suppliers->create( [
				'code'    => sanitize_text_field( $_POST['code'] ?? '' ),
				'name'    => sanitize_text_field( $_POST['name'] ?? '' ),
				'phone'   => sanitize_text_field( $_POST['phone'] ?? '' ),
				'address' => sanitize_textarea_field( $_POST['address'] ?? '' ),
			] );
			$this->audit->log( 'supplier_created', 'sb_suppliers', $id, null, [] );
			wp_redirect( admin_url( 'admin.php?page=sb-suppliers&saved=1' ) );
		} catch ( \Throwable $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
		exit;
	}

	public function handlePrintInvoice(): void {
		if ( ! current_user_can( 'sb_view_sales' ) ) wp_die( 'Akses ditolak.' );
		$id = (int) ( $_GET['id'] ?? 0 );
		check_admin_referer( 'sb_print_' . $id );

		$sale = $this->sale_service->find( $id );
		if ( ! $sale ) wp_die( 'Dokumen tidak ditemukan.' );

		$items    = $this->sale_service->getItems( $id );
		$payments = $this->sale_service->getPayments( $id );
		$store    = $this->settings->all();
		$customer = $sale['customer_id'] ? $this->customers->find( (int) $sale['customer_id'] ) : null;

		echo $this->pdf_service->render( $sale, $items, $payments, $store, $customer );
		exit;
	}
}
