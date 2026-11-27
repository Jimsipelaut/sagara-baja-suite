<?php
namespace SagaraBaja\Modules\Sales\Frontend;

use SagaraBaja\Modules\Sales\Services\SaleService;
use SagaraBaja\Modules\Sales\Services\ShiftService;
use SagaraBaja\Modules\Sales\Services\PdfInvoiceService;
use SagaraBaja\Modules\Sales\Repositories\CustomerRepository;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Core\Document\NumberingService;
use SagaraBaja\Core\Settings\SettingsRepository;
use SagaraBaja\Core\Audit\AuditLogger;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * PosFrontend
 *
 * Mengelola antarmuka layar penuh Kasir / POS di frontend WordPress:
 * - Shortcode: [sb_pos]
 * - Laman otomatis: /pos/
 * - Terproteksi otorisasi peran kasir (sb_access_pos / sb_process_sale / admin)
 * - Alur shift terintegrasi (wajib buka shift sebelum transaksi)
 * - Transaksi langsung potong stok atomik & cetak struk/nota
 */
class PosFrontend {

	public static function register(): void {
		add_shortcode( 'sb_pos', [ self::class, 'render_shortcode' ] );
		add_action( 'template_include', [ self::class, 'override_pos_template' ] );

		// AJAX Endpoints untuk Kasir Frontend
		add_action( 'wp_ajax_sb_pos_open_shift',   [ self::class, 'ajaxOpenShift' ] );
		add_action( 'wp_ajax_sb_pos_close_shift',  [ self::class, 'ajaxCloseShift' ] );
		add_action( 'wp_ajax_sb_pos_checkout',     [ self::class, 'ajaxCheckout' ] );
		add_action( 'wp_ajax_sb_pos_print',        [ self::class, 'ajaxPrint' ] );
	}

	/**
	 * Pastikan laman /pos/ telah dibuat di tabel wp_posts dan diset sebagai Beranda jika dikehendaki.
	 */
	public static function ensure_pos_page( bool $set_as_front = true ): int {
		global $wpdb;

		$page_id = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page' AND post_status != 'trash' LIMIT 1", 'pos' )
		);

		if ( ! $page_id ) {
			$page_id = wp_insert_post( [
				'post_title'     => 'Kasir POS — Sagara Baja',
				'post_name'      => 'pos',
				'post_content'    => '[sb_pos]',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			] );
		}

		if ( $set_as_front && $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $page_id );
		}

		return (int) $page_id;
	}

	/**
	 * Template fullscreen untuk halaman kasir agar bebas dari gangguan header/footer tema blog.
	 */
	public static function override_pos_template( string $template ): string {
		if ( ! is_admin() && ( is_page( 'pos' ) || is_page( get_option( 'page_on_front' ) ) ) ) {
			global $post;
			if ( $post && has_shortcode( $post->post_content, 'sb_pos' ) ) {
				// Render langsung via buffer fullscreen
				self::render_fullscreen_wrapper();
				exit;
			}
		}
		return $template;
	}

	/**
	 * Wrapper fullscreen mandiri.
	 */
	public static function render_fullscreen_wrapper(): void {
		header( 'Content-Type: text/html; charset=utf-8' );
		?>
		<!DOCTYPE html>
		<html lang="id">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Terminal Kasir POS — Sagara Baja</title>
			<?php wp_head(); ?>
			<style>
				* { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
				body { background: #0f172a; color: #f8fafc; height: 100vh; overflow: hidden; }
				#wpadminbar { display: none !important; }
				html { margin-top: 0 !important; }
			</style>
		</head>
		<body>
			<?php echo self::render_shortcode(); ?>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Render Shortcode [sb_pos].
	 */
	public static function render_shortcode(): string {
		if ( ! is_user_logged_in() ) {
			return self::render_login_view();
		}

		$user = wp_get_current_user();
		$can_pos = current_user_can( 'sb_access_pos' ) || current_user_can( 'sb_process_sale' ) || current_user_can( 'manage_options' );
		if ( ! $can_pos ) {
			return '<div style="max-width:500px;margin:100px auto;background:#1e293b;padding:30px;border-radius:12px;text-align:center;color:#ef4444;border:1px solid #ef4444;"><h3>Akses Ditolak</h3><p style="margin-top:10px;color:#cbd5e1;">Akun Anda tidak memiliki wewenang untuk membuka Terminal Kasir POS.</p><p style="margin-top:20px;"><a href="' . esc_url( wp_logout_url( home_url() ) ) . '" style="color:#38bdf8;">Keluar (Logout)</a></p></div>';
		}

		global $wpdb;
		$user_id = (int) $user->ID;

		// Cek Shift Aktif
		$shift = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, w.name as warehouse_name FROM {$wpdb->prefix}sb_cashier_shifts s LEFT JOIN {$wpdb->prefix}sb_warehouses w ON w.id = s.warehouse_id WHERE s.cashier_user_id = %d AND s.status = 'open' ORDER BY s.opened_at DESC LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		if ( ! $shift ) {
			return self::render_open_shift_view( $user );
		}

		return self::render_pos_app( $user, $shift );
	}

	// ── 1. Tampilan Login Kasir ──────────────────────────────────────────────

	private static function render_login_view(): string {
		$login_url = wp_login_url( home_url( '/pos/' ) );
		ob_start();
		?>
		<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#090d16;padding:20px;">
			<div style="width:100%;max-width:420px;background:#131c2e;padding:40px;border-radius:16px;border:1px solid #1e293b;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);text-align:center;">
				<div style="width:64px;height:64px;margin:0 auto 16px;background:linear-gradient(135deg, #2563eb, #1d4ed8);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;">🏗️</div>
				<h2 style="font-size:24px;font-weight:700;color:#f8fafc;margin-bottom:8px;">SAGARA BAJA</h2>
				<p style="color:#94a3b8;font-size:14px;margin-bottom:28px;">Sistem Kasir & Toko Material Bangunan</p>

				<form method="post" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" style="text-align:left;">
					<div style="margin-bottom:16px;">
						<label style="display:block;font-size:13px;color:#cbd5e1;margin-bottom:6px;">Username / Email</label>
						<input type="text" name="log" required style="width:100%;padding:12px;background:#0b1120;border:1px solid #334155;border-radius:8px;color:#fff;font-size:15px;outline:none;">
					</div>
					<div style="margin-bottom:24px;">
						<label style="display:block;font-size:13px;color:#cbd5e1;margin-bottom:6px;">Kata Sandi</label>
						<input type="password" name="pwd" required style="width:100%;padding:12px;background:#0b1120;border:1px solid #334155;border-radius:8px;color:#fff;font-size:15px;outline:none;">
					</div>
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( '/pos/' ) ); ?>">
					<button type="submit" style="width:100%;padding:14px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;transition:background 0.2s;">Masuk ke Kasir</button>
				</form>
				<p style="margin-top:20px;font-size:12px;color:#64748b;">Gunakan akun staf kasir atau admin toko Sagara Baja.</p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// ── 2. Tampilan Buka Shift ──────────────────────────────────────────────

	private static function render_open_shift_view( \WP_User $user ): string {
		global $wpdb;
		$warehouses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sb_warehouses WHERE is_active = 1 ORDER BY id ASC", ARRAY_A );
		$nonce = wp_create_nonce( 'sb_pos_shift_nonce' );

		ob_start();
		?>
		<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#090d16;padding:20px;">
			<div style="width:100%;max-width:480px;background:#131c2e;padding:36px;border-radius:16px;border:1px solid #1e293b;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
				<div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
					<div style="width:48px;height:48px;background:#0284c7;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;">🔑</div>
					<div>
						<h2 style="font-size:20px;font-weight:700;color:#f8fafc;">Buka Shift Kasir Baru</h2>
						<p style="color:#94a3b8;font-size:13px;">Kasir: <strong><?php echo esc_html( $user->display_name ); ?></strong></p>
					</div>
				</div>

				<p style="font-size:13px;color:#cbd5e1;margin-bottom:20px;background:#1e293b;padding:12px;border-radius:8px;border-left:4px solid #0284c7;">
					Shift wajib dibuka sebelum memproses transaksi toko untuk menjaga akurasi kas fisik dan rekonsiliasi uang laci.
				</p>

				<form id="form-open-shift" style="text-align:left;">
					<input type="hidden" name="action" value="sb_pos_open_shift">
					<input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>">

					<div style="margin-bottom:16px;">
						<label style="display:block;font-size:13px;color:#cbd5e1;margin-bottom:6px;">Lokasi Gudang / Toko</label>
						<select name="warehouse_id" required style="width:100%;padding:12px;background:#0b1120;border:1px solid #334155;border-radius:8px;color:#fff;font-size:14px;">
							<?php foreach ( $warehouses as $w ): ?>
								<option value="<?php echo esc_attr( $w['id'] ); ?>"><?php echo esc_html( $w['name'] . ' (' . $w['code'] . ')' ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div style="margin-bottom:16px;">
						<label style="display:block;font-size:13px;color:#cbd5e1;margin-bottom:6px;">Modal Kas Awal di Laci (Rp)</label>
						<input type="number" name="opening_cash" value="0" min="0" step="1000" required style="width:100%;padding:12px;background:#0b1120;border:1px solid #334155;border-radius:8px;color:#fff;font-size:16px;font-weight:600;">
					</div>

					<div style="margin-bottom:24px;">
						<label style="display:block;font-size:13px;color:#cbd5e1;margin-bottom:6px;">Catatan (Opsional)</label>
						<textarea name="notes" placeholder="Contoh: Pecahan kecil 5rb dan 2rb" rows="2" style="width:100%;padding:10px;background:#0b1120;border:1px solid #334155;border-radius:8px;color:#fff;font-size:13px;"></textarea>
					</div>

					<button type="submit" id="btn-open-shift" style="width:100%;padding:14px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;">Buka Shift & Masuk Kasir</button>
				</form>
				<p style="margin-top:20px;text-align:center;"><a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="color:#64748b;font-size:13px;text-decoration:none;">Keluar Akun</a></p>
			</div>
		</div>

		<script>
		document.getElementById('form-open-shift').addEventListener('submit', function(e) {
			e.preventDefault();
			const btn = document.getElementById('btn-open-shift');
			btn.disabled = true;
			btn.textContent = 'Membuka shift...';

			const formData = new FormData(this);
			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(r => r.json())
			.then(res => {
				if (res.success) {
					window.location.reload();
				} else {
					alert(res.data?.message || 'Gagal membuka shift');
					btn.disabled = false;
					btn.textContent = 'Buka Shift & Masuk Kasir';
				}
			})
			.catch(err => {
				alert('Terjadi kesalahan jaringan');
				btn.disabled = false;
				btn.textContent = 'Buka Shift & Masuk Kasir';
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	// ── 3. Tampilan Aplikasi Kasir POS Utama ──────────────────────────────────

	private static function render_pos_app( \WP_User $user, array $shift ): string {
		global $wpdb;

		$warehouse_id = (int) $shift['warehouse_id'];

		// Ambil Produk aktif & Stok di gudang shift ini
		$products = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.id, p.sku, p.name, p.category_id, p.base_unit_id,
				        p.cost_price, p.sell_price_retail, p.sell_price_wholesale, p.sell_price_project,
				        u.name as unit_name,
				        c.name as category_name,
				        COALESCE(b.qty, 0) as stock_qty
				 FROM {$wpdb->prefix}sb_products p
				 LEFT JOIN {$wpdb->prefix}sb_units u ON u.id = p.base_unit_id
				 LEFT JOIN {$wpdb->prefix}sb_categories c ON c.id = p.category_id
				 LEFT JOIN {$wpdb->prefix}sb_stock_balances b ON b.product_id = p.id AND b.warehouse_id = %d
				 WHERE p.is_active = 1
				 ORDER BY p.name ASC",
				$warehouse_id
			),
			ARRAY_A
		);

		// Ambil Pelanggan
		$customers = $wpdb->get_results( "SELECT id, code, name, credit_limit FROM {$wpdb->prefix}sb_customers WHERE is_active = 1 ORDER BY name ASC", ARRAY_A );

		// Kategori
		$categories = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sb_categories ORDER BY name ASC", ARRAY_A );

		$nonce_checkout = wp_create_nonce( 'sb_pos_checkout_nonce' );
		$nonce_shift    = wp_create_nonce( 'sb_pos_shift_nonce' );

		ob_start();
		?>
		<div id="sb-pos-app" style="display:flex;flex-direction:column;height:100vh;background:#090d16;">
			<!-- Header Atas -->
			<header style="background:#131c2e;border-bottom:1px solid #1e293b;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
				<div style="display:flex;align-items:center;gap:12px;">
					<div style="width:36px;height:36px;background:#2563eb;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;">🏗️</div>
					<div>
						<h1 style="font-size:16px;font-weight:700;color:#f8fafc;letter-spacing:0.5px;">SAGARA BAJA POS</h1>
						<p style="font-size:12px;color:#94a3b8;">Gudang: <strong style="color:#38bdf8;"><?php echo esc_html( $shift['warehouse_name'] ); ?></strong> | Kasir: <strong><?php echo esc_html( $user->display_name ); ?></strong></p>
					</div>
				</div>

				<div style="display:flex;align-items:center;gap:16px;">
					<div style="background:#0b1120;padding:6px 14px;border-radius:20px;border:1px solid #334155;font-size:12px;color:#cbd5e1;">
						Shift #<?php echo esc_html( $shift['id'] ); ?> (Buka: <?php echo esc_html( date( 'H:i', strtotime( $shift['opened_at'] ) ) ); ?> | Kas Awal: Rp <?php echo number_format( (int) $shift['opening_cash'], 0, ',', '.' ); ?>)
					</div>

					<button onclick="openCloseShiftModal()" style="background:#dc2626;color:#fff;border:none;padding:8px 14px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">Tutup Shift</button>

					<?php if ( current_user_can( 'manage_options' ) || current_user_can( 'sb_access_dashboard' ) ): ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=sagara-baja' ) ); ?>" target="_blank" style="color:#94a3b8;font-size:12px;text-decoration:none;border:1px solid #334155;padding:7px 12px;border-radius:6px;">Dashboard Admin ↗</a>
					<?php endif; ?>

					<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="color:#ef4444;font-size:12px;text-decoration:none;">Keluar</a>
				</div>
			</header>

			<!-- Konten Utama 2 Kolom -->
			<div style="display:flex;flex:1;overflow:hidden;">

				<!-- Kolom Kiri: Katalog & Pencarian Material (60%) -->
				<section style="flex:6;display:flex;flex-direction:column;border-right:1px solid #1e293b;background:#090d16;overflow:hidden;">
					<!-- Search Bar & Kategori -->
					<div style="padding:16px 20px;background:#0f172a;border-bottom:1px solid #1e293b;">
						<div style="display:flex;gap:12px;margin-bottom:12px;">
							<input type="text" id="pos-search" placeholder="🔍 Cari nama barang / SKU material (Besi beton, spandek, hollow, semen...)" style="flex:1;padding:12px 16px;background:#1e293b;border:1px solid #334155;border-radius:8px;color:#fff;font-size:14px;outline:none;">
						</div>
						<div id="category-pills" style="display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;">
							<button class="cat-pill active" onclick="filterCategory(0)" style="background:#2563eb;color:#fff;border:none;padding:6px 14px;border-radius:20px;font-size:12px;cursor:pointer;white-space:nowrap;">Semua Material</button>
							<?php foreach ( $categories as $cat ): ?>
								<button class="cat-pill" data-id="<?php echo esc_attr( $cat['id'] ); ?>" onclick="filterCategory(<?php echo esc_attr( $cat['id'] ); ?>)" style="background:#1e293b;color:#cbd5e1;border:none;padding:6px 14px;border-radius:20px;font-size:12px;cursor:pointer;white-space:nowrap;"><?php echo esc_html( $cat['name'] ); ?></button>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Grid Produk -->
					<div id="products-grid" style="flex:1;overflow-y:auto;padding:20px;display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:16px;align-content:start;">
						<?php foreach ( $products as $prod ): ?>
							<div class="product-card"
							     data-id="<?php echo esc_attr( $prod['id'] ); ?>"
							     data-sku="<?php echo esc_attr( strtolower( $prod['sku'] ) ); ?>"
							     data-name="<?php echo esc_attr( strtolower( $prod['name'] ) ); ?>"
							     data-cat="<?php echo esc_attr( $prod['category_id'] ); ?>"
							     data-retail="<?php echo esc_attr( $prod['sell_price_retail'] ); ?>"
							     data-wholesale="<?php echo esc_attr( $prod['sell_price_wholesale'] ); ?>"
							     data-project="<?php echo esc_attr( $prod['sell_price_project'] ); ?>"
							     data-unit="<?php echo esc_attr( $prod['unit_name'] ); ?>"
							     data-unit-id="<?php echo esc_attr( $prod['base_unit_id'] ); ?>"
							     data-stock="<?php echo esc_attr( (float) $prod['stock_qty'] ); ?>"
							     onclick="addToCart(this)"
							     style="background:#131c2e;border:1px solid #1e293b;border-radius:12px;padding:16px;cursor:pointer;transition:transform 0.1s, border-color 0.1s;display:flex;flex-direction:column;justify-content:space-between;">
								<div>
									<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
										<span style="font-size:11px;color:#94a3b8;font-family:monospace;"><?php echo esc_html( $prod['sku'] ); ?></span>
										<span style="font-size:11px;padding:2px 8px;border-radius:10px;background:<?php echo (float)$prod['stock_qty'] > 0 ? '#064e3b' : '#7f1d1d'; ?>;color:<?php echo (float)$prod['stock_qty'] > 0 ? '#34d399' : '#f87171'; ?>;">
											Stok: <?php echo number_format( (float) $prod['stock_qty'], 0 ); ?> <?php echo esc_html( $prod['unit_name'] ); ?>
										</span>
									</div>
									<h4 style="font-size:14px;color:#f8fafc;font-weight:600;line-height:1.3;margin-bottom:6px;"><?php echo esc_html( $prod['name'] ); ?></h4>
								</div>
								<div style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;border-top:1px dashed #1e293b;padding-top:10px;">
									<span style="font-size:15px;font-weight:700;color:#38bdf8;">Rp <?php echo number_format( (int) $prod['sell_price_retail'], 0, ',', '.' ); ?></span>
									<span style="background:#2563eb;color:#fff;width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold;">+</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</section>

				<!-- Kolom Kanan: Keranjang Kasir & Pembayaran (40%) -->
				<section style="flex:4;display:flex;flex-direction:column;background:#0f172a;overflow:hidden;">
					<!-- Opsi Transaksi (Nota vs Resmi & Pelanggan) -->
					<div style="padding:16px 20px;border-bottom:1px solid #1e293b;background:#131c2e;">
						<div style="display:flex;gap:12px;margin-bottom:12px;">
							<div style="flex:1;">
								<label style="font-size:11px;color:#94a3b8;display:block;margin-bottom:4px;">Jenis Dokumen</label>
								<select id="pos-invoice-type" style="width:100%;padding:8px 12px;background:#0b1120;border:1px solid #334155;border-radius:6px;color:#fff;font-size:13px;font-weight:600;">
									<option value="non_resmi">Nota Toko (Non-Resmi)</option>
									<option value="resmi">Invoice Resmi</option>
								</select>
							</div>
							<div style="flex:1;">
								<label style="font-size:11px;color:#94a3b8;display:block;margin-bottom:4px;">Pelanggan</label>
								<select id="pos-customer" style="width:100%;padding:8px 12px;background:#0b1120;border:1px solid #334155;border-radius:6px;color:#fff;font-size:13px;">
									<option value="">— Pelanggan Umum —</option>
									<?php foreach ( $customers as $c ): ?>
										<option value="<?php echo esc_attr( $c['id'] ); ?>" data-limit="<?php echo esc_attr( $c['credit_limit'] ); ?>">
											<?php echo esc_html( $c['name'] ); ?> (<?php echo esc_html( $c['code'] ); ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>

					<!-- Daftar Item Keranjang -->
					<div id="cart-list" style="flex:1;overflow-y:auto;padding:12px 20px;">
						<div id="cart-empty" style="text-align:center;padding:60px 20px;color:#64748b;">
							<div style="font-size:48px;margin-bottom:12px;">🛒</div>
							<p style="font-size:14px;">Keranjang transaksi masih kosong.</p>
							<p style="font-size:12px;margin-top:4px;">Klik salah satu material di sebelah kiri untuk menambahkan.</p>
						</div>
						<div id="cart-items" style="display:none;display:flex;flex-direction:column;gap:10px;"></div>
					</div>

					<!-- Ringkasan & Pembayaran -->
					<div style="background:#131c2e;border-top:1px solid #1e293b;padding:16px 20px;">
						<!-- Subtotal, Diskon, Grand Total -->
						<div style="display:flex;justify-content:space-between;font-size:13px;color:#94a3b8;margin-bottom:6px;">
							<span>Subtotal</span>
							<span id="txt-subtotal" style="color:#f8fafc;font-weight:600;">Rp 0</span>
						</div>
						<div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;color:#94a3b8;margin-bottom:10px;">
							<span>Diskon Nota (Rp)</span>
							<input type="number" id="inp-discount" value="0" min="0" step="1000" oninput="recalcTotals()" style="width:120px;padding:4px 8px;background:#0b1120;border:1px solid #334155;border-radius:6px;color:#fff;text-align:right;font-size:13px;">
						</div>
						<div style="display:flex;justify-content:space-between;font-size:18px;font-weight:700;color:#f8fafc;padding-top:10px;border-top:1px dashed #334155;margin-bottom:16px;">
							<span>Grand Total</span>
							<span id="txt-grandtotal" style="color:#38bdf8;">Rp 0</span>
						</div>

						<!-- Metode Bayar & Nominal Uang -->
						<div style="display:flex;gap:10px;margin-bottom:12px;">
							<select id="pos-payment-method" onchange="onPaymentMethodChange()" style="flex:1;padding:10px;background:#0b1120;border:1px solid #334155;border-radius:8px;color:#fff;font-size:13px;font-weight:600;">
								<option value="tunai">💵 Tunai (Cash)</option>
								<option value="transfer">🏦 Transfer Bank</option>
								<option value="qris">📱 QRIS</option>
								<option value="kasbon">📝 Kasbon / Tempo</option>
							</select>
							<input type="number" id="inp-cash-paid" placeholder="Uang Diterima" oninput="recalcTotals()" style="flex:1;padding:10px;background:#0b1120;border:1px solid #334155;border-radius:8px;color:#fff;font-size:15px;font-weight:600;text-align:right;">
						</div>

						<!-- Quick Cash Buttons -->
						<div id="quick-cash-row" style="display:flex;gap:6px;margin-bottom:12px;">
							<button type="button" onclick="setQuickCash('pas')" style="flex:1;padding:6px;background:#1e293b;color:#cbd5e1;border:none;border-radius:4px;font-size:11px;cursor:pointer;">Uang Pas</button>
							<button type="button" onclick="setQuickCash(50000)" style="flex:1;padding:6px;background:#1e293b;color:#cbd5e1;border:none;border-radius:4px;font-size:11px;cursor:pointer;">50rb</button>
							<button type="button" onclick="setQuickCash(100000)" style="flex:1;padding:6px;background:#1e293b;color:#cbd5e1;border:none;border-radius:4px;font-size:11px;cursor:pointer;">100rb</button>
							<button type="button" onclick="setQuickCash(200000)" style="flex:1;padding:6px;background:#1e293b;color:#cbd5e1;border:none;border-radius:4px;font-size:11px;cursor:pointer;">200rb</button>
							<button type="button" onclick="setQuickCash(500000)" style="flex:1;padding:6px;background:#1e293b;color:#cbd5e1;border:none;border-radius:4px;font-size:11px;cursor:pointer;">500rb</button>
						</div>

						<!-- Kembalian -->
						<div style="display:flex;justify-content:space-between;font-size:14px;color:#94a3b8;margin-bottom:16px;">
							<span>Kembalian</span>
							<span id="txt-change" style="color:#10b981;font-weight:700;font-size:15px;">Rp 0</span>
						</div>

						<!-- Tombol Bayar & Proses -->
						<button id="btn-submit-sale" onclick="submitSale()" style="width:100%;padding:14px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
							<span>⚡</span>
							<span id="btn-submit-text">BAYAR & CETAK STRUK</span>
						</button>
					</div>
				</section>
			</div>
		</div>

		<!-- Modal Tutup Shift -->
		<div id="modal-close-shift" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;z-index:9999;">
			<div style="background:#131c2e;padding:30px;border-radius:16px;width:100%;max-width:400px;border:1px solid #334155;">
				<h3 style="color:#f8fafc;font-size:18px;margin-bottom:12px;">Tutup Shift Kasir #<?php echo esc_html( $shift['id'] ); ?></h3>
				<p style="font-size:13px;color:#94a3b8;margin-bottom:16px;">Hitung total fisik uang tunai di laci kasir saat ini.</p>
				<form id="form-close-shift">
					<input type="hidden" name="action" value="sb_pos_close_shift">
					<input type="hidden" name="shift_id" value="<?php echo esc_attr( $shift['id'] ); ?>">
					<input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce_shift ); ?>">

					<div style="margin-bottom:16px;">
						<label style="display:block;font-size:12px;color:#cbd5e1;margin-bottom:6px;">Uang Tunai Fisik di Laci (Rp)</label>
						<input type="number" name="closing_cash" required min="0" step="1000" style="width:100%;padding:12px;background:#0b1120;border:1px solid #334155;border-radius:8px;color:#fff;font-size:16px;font-weight:bold;">
					</div>

					<div style="margin-bottom:20px;">
						<label style="display:block;font-size:12px;color:#cbd5e1;margin-bottom:6px;">Catatan Penutupan</label>
						<textarea name="notes" placeholder="Catatan selisih kas bila ada..." rows="2" style="width:100%;padding:10px;background:#0b1120;border:1px solid #334155;border-radius:8px;color:#fff;font-size:13px;"></textarea>
					</div>

					<div style="display:flex;gap:10px;">
						<button type="button" onclick="closeCloseShiftModal()" style="flex:1;padding:12px;background:#334155;color:#fff;border:none;border-radius:8px;cursor:pointer;">Batal</button>
						<button type="submit" style="flex:1;padding:12px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Tutup Shift</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Struk Iframe untuk Silent Print -->
		<iframe id="receipt-frame" style="display:none;width:0;height:0;"></iframe>

		<script>
		let cart = [];
		const warehouseId = <?php echo (int) $warehouse_id; ?>;
		const shiftId = <?php echo (int) $shift['id']; ?>;
		const cashierId = <?php echo (int) $user->ID; ?>;
		const ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
		const checkoutNonce = '<?php echo esc_attr( $nonce_checkout ); ?>';

		// ── Filter Produk & Kategori ─────────────────────────────────────────
		function filterCategory(catId) {
			document.querySelectorAll('.cat-pill').forEach(p => {
				p.style.background = '#1e293b';
				p.style.color = '#cbd5e1';
			});
			const btn = event.target;
			btn.style.background = '#2563eb';
			btn.style.color = '#fff';

			const search = document.getElementById('pos-search').value.toLowerCase().trim();
			applyFilter(catId, search);
		}

		document.getElementById('pos-search').addEventListener('input', function(e) {
			const search = e.target.value.toLowerCase().trim();
			applyFilter(null, search);
		});

		function applyFilter(catId, search) {
			document.querySelectorAll('.product-card').forEach(card => {
				const cardCat = parseInt(card.dataset.cat);
				const cardName = card.dataset.name;
				const cardSku = card.dataset.sku;

				let matchCat = (catId === null || catId === 0 || cardCat === catId);
				let matchSearch = (!search || cardName.includes(search) || cardSku.includes(search));

				card.style.display = (matchCat && matchSearch) ? 'flex' : 'none';
			});
		}

		// ── Keranjang Transaksi ──────────────────────────────────────────────
		function addToCart(el) {
			const id = parseInt(el.dataset.id);
			const sku = el.dataset.sku.toUpperCase();
			const name = el.querySelector('h4').textContent;
			const unit = el.dataset.unit;
			const unitId = parseInt(el.dataset.unitId);
			const retailPrice = parseInt(el.dataset.retail);
			const wholesalePrice = parseInt(el.dataset.wholesale);
			const projectPrice = parseInt(el.dataset.project);
			const stock = parseFloat(el.dataset.stock);

			const existing = cart.find(i => i.id === id);
			if (existing) {
				existing.qty += 1;
			} else {
				cart.push({
					id: id,
					sku: sku,
					name: name,
					unit: unit,
					unit_id: unitId,
					price_tier: 'retail',
					price_retail: retailPrice,
					price_wholesale: wholesalePrice,
					price_project: projectPrice,
					unit_price: retailPrice,
					qty: 1,
					discount: 0,
					stock: stock
				});
			}
			renderCart();
		}

		function renderCart() {
			const emptyBox = document.getElementById('cart-empty');
			const itemsBox = document.getElementById('cart-items');

			if (cart.length === 0) {
				emptyBox.style.display = 'block';
				itemsBox.style.display = 'none';
				itemsBox.innerHTML = '';
				recalcTotals();
				return;
			}

			emptyBox.style.display = 'none';
			itemsBox.style.display = 'flex';
			itemsBox.innerHTML = '';

			cart.forEach((item, index) => {
				const lineTotal = (item.qty * item.unit_price) - item.discount;
				const div = document.createElement('div');
				div.style.cssText = 'background:#131c2e;border:1px solid #1e293b;border-radius:8px;padding:12px;display:flex;flex-direction:column;gap:8px;';

				div.innerHTML = `
					<div style="display:flex;justify-content:space-between;align-items:start;">
						<div>
							<div style="font-size:13px;font-weight:600;color:#f8fafc;">${item.name}</div>
							<div style="font-size:11px;color:#94a3b8;">${item.sku} — Satuan: ${item.unit}</div>
						</div>
						<button onclick="removeFromCart(${index})" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:16px;">✕</button>
					</div>

					<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
						<!-- Tier Harga -->
						<select onchange="changeTier(${index}, this.value)" style="padding:4px 8px;background:#0b1120;border:1px solid #334155;border-radius:4px;color:#cbd5e1;font-size:11px;">
							<option value="retail" ${item.price_tier === 'retail' ? 'selected' : ''}>Retail (Rp ${item.price_retail.toLocaleString('id-ID')})</option>
							<option value="wholesale" ${item.price_tier === 'wholesale' ? 'selected' : ''}>Grosir (Rp ${item.price_wholesale.toLocaleString('id-ID')})</option>
							<option value="project" ${item.price_tier === 'project' ? 'selected' : ''}>Proyek (Rp ${item.price_project.toLocaleString('id-ID')})</option>
						</select>

						<!-- Qty Stepper -->
						<div style="display:flex;align-items:center;gap:4px;">
							<button onclick="changeQty(${index}, -1)" style="width:24px;height:24px;background:#1e293b;border:1px solid #334155;color:#fff;border-radius:4px;cursor:pointer;">-</button>
							<input type="number" min="0.01" step="any" value="${item.qty}" onchange="setQty(${index}, this.value)" style="width:50px;text-align:center;padding:2px 4px;background:#0b1120;border:1px solid #334155;border-radius:4px;color:#fff;font-size:12px;">
							<button onclick="changeQty(${index}, 1)" style="width:24px;height:24px;background:#1e293b;border:1px solid #334155;color:#fff;border-radius:4px;cursor:pointer;">+</button>
						</div>

						<!-- Subtotal baris -->
						<div style="font-size:13px;font-weight:700;color:#38bdf8;text-align:right;">
							Rp ${lineTotal.toLocaleString('id-ID')}
						</div>
					</div>
				`;
				itemsBox.appendChild(div);
			});

			recalcTotals();
		}

		function changeQty(index, delta) {
			cart[index].qty = Math.max(1, cart[index].qty + delta);
			renderCart();
		}

		function setQty(index, val) {
			const n = parseFloat(val);
			cart[index].qty = (isNaN(n) || n <= 0) ? 1 : n;
			renderCart();
		}

		function changeTier(index, tier) {
			cart[index].price_tier = tier;
			if (tier === 'retail') cart[index].unit_price = cart[index].price_retail;
			if (tier === 'wholesale') cart[index].unit_price = cart[index].price_wholesale;
			if (tier === 'project') cart[index].unit_price = cart[index].price_project;
			renderCart();
		}

		function removeFromCart(index) {
			cart.splice(index, 1);
			renderCart();
		}

		function recalcTotals() {
			let subtotal = 0;
			cart.forEach(i => {
				subtotal += (i.qty * i.unit_price) - i.discount;
			});

			const discInp = parseInt(document.getElementById('inp-discount').value) || 0;
			const grandTotal = Math.max(0, subtotal - discInp);

			document.getElementById('txt-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
			document.getElementById('txt-grandtotal').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');

			const method = document.getElementById('pos-payment-method').value;
			const cashPaid = parseInt(document.getElementById('inp-cash-paid').value) || 0;
			const changeTxt = document.getElementById('txt-change');

			if (method === 'tunai') {
				const change = cashPaid - grandTotal;
				if (cashPaid === 0) {
					changeTxt.textContent = 'Rp 0';
					changeTxt.style.color = '#94a3b8';
				} else if (change >= 0) {
					changeTxt.textContent = 'Rp ' + change.toLocaleString('id-ID');
					changeTxt.style.color = '#10b981';
				} else {
					changeTxt.textContent = 'Kurang Rp ' + Math.abs(change).toLocaleString('id-ID');
					changeTxt.style.color = '#ef4444';
				}
			} else {
				changeTxt.textContent = 'Pas (Non-Tunai)';
				changeTxt.style.color = '#38bdf8';
			}
		}

		function onPaymentMethodChange() {
			const method = document.getElementById('pos-payment-method').value;
			const row = document.getElementById('quick-cash-row');
			const inp = document.getElementById('inp-cash-paid');

			if (method !== 'tunai') {
				row.style.opacity = '0.3';
				row.style.pointerEvents = 'none';
				inp.placeholder = 'Jumlah Transfer/Bayar';
			} else {
				row.style.opacity = '1';
				row.style.pointerEvents = 'auto';
				inp.placeholder = 'Uang Diterima';
			}
			recalcTotals();
		}

		function setQuickCash(val) {
			let subtotal = 0;
			cart.forEach(i => subtotal += (i.qty * i.unit_price) - i.discount);
			const disc = parseInt(document.getElementById('inp-discount').value) || 0;
			const grand = Math.max(0, subtotal - disc);

			if (val === 'pas') {
				document.getElementById('inp-cash-paid').value = grand;
			} else {
				document.getElementById('inp-cash-paid').value = val;
			}
			recalcTotals();
		}

		// ── Submit Transaksi Kasir ──────────────────────────────────────────
		function submitSale() {
			if (cart.length === 0) {
				alert('Keranjang belanja kosong! Pilih barang terlebih dahulu.');
				return;
			}

			let subtotal = 0;
			cart.forEach(i => subtotal += (i.qty * i.unit_price) - i.discount);
			const disc = parseInt(document.getElementById('inp-discount').value) || 0;
			const grand = Math.max(0, subtotal - disc);

			const method = document.getElementById('pos-payment-method').value;
			let paid = parseInt(document.getElementById('inp-cash-paid').value) || 0;

			if (method !== 'tunai' && paid === 0) {
				paid = grand; // default otomatis lunas untuk transfer / qris
			}

			if (method === 'tunai' && paid < grand) {
				if (!confirm('Uang yang diterima kurang dari total belanja. Lanjutkan sebagai piutang/sebagian?')) {
					return;
				}
			}

			const btn = document.getElementById('btn-submit-sale');
			const txt = document.getElementById('btn-submit-text');
			btn.disabled = true;
			txt.textContent = 'Memproses Transaksi & Mutasi Stok...';

			// C-13: satu token idempotensi per percobaan checkout. Bila
			// permintaan yang sama terkirim dua kali, pembayaran dicatat
			// sekali saja (SaleService::addPayment mengembalikan pembayaran
			// lama). Catatan: percobaan ulang setelah gagal jaringan tetap
			// membuat dokumen penjualan baru — di luar cakupan C-13.
			const idemKey = (window.crypto && crypto.randomUUID)
				? crypto.randomUUID()
				: ('pos-' + Date.now() + '-' + Math.random().toString(36).slice(2));

			const payload = {
				action: 'sb_pos_checkout',
				_nonce: checkoutNonce,
				invoice_type: document.getElementById('pos-invoice-type').value,
				customer_id: document.getElementById('pos-customer').value || null,
				warehouse_id: warehouseId,
				shift_id: shiftId,
				discount_amount: disc,
				payment_method: method,
				paid_amount: paid,
				idempotency_key: idemKey,
				items: cart.map(i => ({
					product_id: i.id,
					unit_id: i.unit_id,
					qty: i.qty,
					unit_price: i.unit_price,
					discount_amount: i.discount
				}))
			};

			fetch(ajaxUrl + '?action=sb_pos_checkout', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(payload)
			})
			.then(r => r.json())
			.then(res => {
				btn.disabled = false;
				txt.textContent = 'BAYAR & CETAK STRUK';

				if (res && res.success) {
					// Cetak struk otomatis
					printReceipt(res.data.sale_id);

					alert('✅ Transaksi Berhasil Terbit!\nNo. Dokumen: ' + res.data.doc_number + '\nTotal: Rp ' + Number(res.data.grand_total).toLocaleString('id-ID'));

					// Reset Keranjang
					cart = [];
					document.getElementById('inp-discount').value = 0;
					document.getElementById('inp-cash-paid').value = '';
					renderCart();
				} else {
					alert('❌ Transaksi Gagal: ' + (res?.data?.message || 'Terjadi kesalahan sistem.'));
				}
			})
			.catch(err => {
				btn.disabled = false;
				txt.textContent = 'BAYAR & CETAK STRUK';
				alert('❌ Terjadi kesalahan jaringan saat checkout.');
			});
		}

		function printReceipt(saleId) {
			const frame = document.getElementById('receipt-frame');
			const url = ajaxUrl + '?action=sb_pos_print&id=' + saleId + '&_nonce=' + checkoutNonce;
			frame.src = url;
			frame.onload = function() {
				frame.contentWindow.focus();
				frame.contentWindow.print();
			};
		}

		// ── Modal Tutup Shift ────────────────────────────────────────────────
		function openCloseShiftModal() {
			document.getElementById('modal-close-shift').style.display = 'flex';
		}
		function closeCloseShiftModal() {
			document.getElementById('modal-close-shift').style.display = 'none';
		}

		document.getElementById('form-close-shift').addEventListener('submit', function(e) {
			e.preventDefault();
			const form = new FormData(this);
			fetch(ajaxUrl, {
				method: 'POST',
				body: form
			})
			.then(r => r.json())
			.then(res => {
				if (res.success) {
					alert('Shift berhasil ditutup.');
					window.location.reload();
				} else {
					alert(res.data?.message || 'Gagal menutup shift');
				}
			})
			.catch(err => alert('Kesalahan jaringan'));
		});
		</script>
		<?php
		return ob_get_clean();
	}

	// ── AJAX Handlers ────────────────────────────────────────────────────────

	public static function ajaxOpenShift(): void {
		check_ajax_referer( 'sb_pos_shift_nonce', '_nonce' );

		if ( ! current_user_can( 'sb_access_pos' ) && ! current_user_can( 'sb_process_sale' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Akses ditolak.' ] );
		}

		global $wpdb;
		$user_id      = get_current_user_id();
		$warehouse_id = (int) ( $_POST['warehouse_id'] ?? 1 );
		$opening_cash = (int) ( $_POST['opening_cash'] ?? 0 );
		$notes        = sanitize_text_field( $_POST['notes'] ?? '' );

		$shift_service = new ShiftService( $wpdb );

		try {
			$shift_id = $shift_service->open( $user_id, $warehouse_id, $opening_cash, $notes );
			wp_send_json_success( [ 'shift_id' => $shift_id ] );
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	public static function ajaxCloseShift(): void {
		check_ajax_referer( 'sb_pos_shift_nonce', '_nonce' );

		if ( ! current_user_can( 'sb_access_pos' ) && ! current_user_can( 'sb_process_sale' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Akses ditolak.' ] );
		}

		global $wpdb;
		$shift_id     = (int) ( $_POST['shift_id'] ?? 0 );
		$closing_cash = (int) ( $_POST['closing_cash'] ?? 0 );
		$notes        = sanitize_text_field( $_POST['notes'] ?? '' );

		$shift_service = new ShiftService( $wpdb );

		try {
			$shift_service->close( $shift_id, $closing_cash, $notes );
			wp_send_json_success();
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	public static function ajaxCheckout(): void {
		$raw  = file_get_contents( 'php://input' );
		$data = json_decode( $raw, true );
		if ( ! $data && ! empty( $_POST ) ) {
			$data = $_POST;
			if ( isset( $data['items'] ) && is_string( $data['items'] ) ) {
				$data['items'] = json_decode( stripslashes( $data['items'] ), true );
			}
		}

		if ( ! $data || ! wp_verify_nonce( $data['_nonce'] ?? '', 'sb_pos_checkout_nonce' ) ) {
			wp_send_json_error( [ 'message' => 'Token keamanan (nonce) tidak valid.' ] );
		}

		if ( ! current_user_can( 'sb_access_pos' ) && ! current_user_can( 'sb_process_sale' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Akses ditolak.' ] );
		}

		global $wpdb;
		$user_id = get_current_user_id();

		$numbering = new NumberingService( $wpdb );
		$stock     = new StockService( $wpdb );
		$sales     = new SaleService( $wpdb, $numbering, $stock );

		try {
			$sale_data = [
				'invoice_type'    => $data['invoice_type'] ?? 'non_resmi',
				'sale_date'       => date( 'Y-m-d' ),
				'warehouse_id'    => (int) ( $data['warehouse_id'] ?? 1 ),
				'shift_id'        => (int) ( $data['shift_id'] ?? 0 ),
				'customer_id'     => ! empty( $data['customer_id'] ) ? (int) $data['customer_id'] : null,
				'cashier_user_id' => $user_id,
				'discount_amount' => (int) ( $data['discount_amount'] ?? 0 ),
				'notes'           => 'Kasir POS Frontend',
				'items'           => $data['items'] ?? [],
			];

			// 1. Buat Draft
			$sale_id = $sales->createDraft( $sale_data );

			// 2. Terbitkan (Atomik Potong Stok Fisik)
			$sales->publish( $sale_id );

			$sale = $sales->find( $sale_id );

			// 3. Catat Pembayaran jika ada nominal
			$paid_amount = (int) ( $data['paid_amount'] ?? 0 );
			if ( $paid_amount > 0 ) {
				$method = $data['payment_method'] ?? 'tunai';
				// Untuk tunai, uang masuk penjualan maksimal sebesar grand_total jika bayar berlebih (ada kembalian)
				$applied_amount = ( $method === 'tunai' && $paid_amount > (int) $sale['grand_total'] )
					? (int) $sale['grand_total']
					: $paid_amount;

				$notes = 'Pembayaran Kasir POS';
				if ( $method === 'tunai' && $paid_amount > (int) $sale['grand_total'] ) {
					$notes .= ' (Diterima: Rp ' . number_format( $paid_amount, 0, ',', '.' ) . ', Kembali: Rp ' . number_format( $paid_amount - (int) $sale['grand_total'], 0, ',', '.' ) . ')';
				}

				$sales->addPayment( $sale_id, [
					'method'          => $method,
					'amount'          => $applied_amount,
					'created_by'      => $user_id,
					'idempotency_key' => sanitize_text_field( $data['idempotency_key'] ?? '' ),
					'notes'           => $notes,
				] );

				$sale = $sales->find( $sale_id );
			}

			wp_send_json_success( [
				'sale_id'     => $sale_id,
				'doc_number'  => $sale['doc_number'],
				'grand_total' => (int) $sale['grand_total'],
				'status'      => $sale['status'],
			] );

		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	public static function ajaxPrint(): void {
		$sale_id = (int) ( $_GET['id'] ?? 0 );
		$nonce   = $_GET['_nonce'] ?? '';

		if ( ! wp_verify_nonce( $nonce, 'sb_pos_checkout_nonce' ) ) {
			wp_die( 'Akses ditolak (nonce invalid).' );
		}

		global $wpdb;
		$numbering = new NumberingService( $wpdb );
		$stock     = new StockService( $wpdb );
		$sales     = new SaleService( $wpdb, $numbering, $stock );
		$settings  = new SettingsRepository( $wpdb );
		$customers = new CustomerRepository( $wpdb );
		$pdf       = new PdfInvoiceService();

		$sale = $sales->find( $sale_id );
		if ( ! $sale ) {
			wp_die( 'Transaksi tidak ditemukan.' );
		}

		$items    = $sales->getItems( $sale_id );
		$payments = $sales->getPayments( $sale_id );
		$store    = $settings->all();
		$customer = $sale['customer_id'] ? $customers->find( (int) $sale['customer_id'] ) : null;

		echo $pdf->render( $sale, $items, $payments, $store, $customer );
		exit;
	}
}
