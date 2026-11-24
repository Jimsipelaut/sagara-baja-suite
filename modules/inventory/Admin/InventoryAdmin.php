<?php
namespace SagaraBaja\Modules\Inventory\Admin;

use SagaraBaja\Core\Auth\Capabilities;
use SagaraBaja\Modules\Inventory\Repositories\ProductRepository;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Inventory\Seeders\DummyDataSeeder;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Antarmuka Back-Office Admin untuk Modul Inventory & Master Data.
 */
class InventoryAdmin {
    public static function register(): void {
        add_action( 'admin_menu', [ self::class, 'add_menus' ], 20 );
        add_action( 'admin_post_sb_seed_dummy_products', [ self::class, 'handle_seed_dummy' ] );
    }

    public static function add_menus(): void {
        // Submenu: Master Barang
        add_submenu_page(
            'sagara-baja',
            __( 'Master Barang & Harga', 'sagara-baja-suite' ),
            __( 'Master Barang', 'sagara-baja-suite' ),
            Capabilities::VIEW_INVENTORY,
            'sb-products',
            [ self::class, 'render_products_page' ]
        );

        // Submenu: Kartu Stok
        add_submenu_page(
            'sagara-baja',
            __( 'Kartu Stok Material', 'sagara-baja-suite' ),
            __( 'Kartu Stok', 'sagara-baja-suite' ),
            Capabilities::VIEW_INVENTORY,
            'sb-stock-moves',
            [ self::class, 'render_stock_moves_page' ]
        );
    }

    /**
     * Handler untuk inisialisasi data dummy barang material.
     */
    public static function handle_seed_dummy(): void {
        if ( ! current_user_can( Capabilities::MANAGE_INVENTORY ) ) {
            wp_die( esc_html__( 'Anda tidak memiliki wewenang untuk tindakan ini.', 'sagara-baja-suite' ) );
        }

        check_admin_referer( 'sb_seed_dummy_action', 'sb_seed_dummy_nonce' );

        $seeder = new DummyDataSeeder();
        $seeder->run();

        wp_safe_redirect( add_query_arg( [ 'page' => 'sb-products', 'seeded' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Render Halaman Master Barang & Harga Bertingkat.
     */
    public static function render_products_page(): void {
        if ( ! current_user_can( Capabilities::VIEW_INVENTORY ) ) {
            wp_die( esc_html__( 'Akses ditolak.', 'sagara-baja-suite' ) );
        }

        $repo = new ProductRepository();
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $products = $repo->all( [ 'search' => $search ] );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Master Barang & Harga Bertingkat', 'sagara-baja-suite' ); ?></h1>
            
            <?php if ( current_user_can( Capabilities::MANAGE_INVENTORY ) && empty( $products ) ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-left:15px;">
                    <input type="hidden" name="action" value="sb_seed_dummy_products">
                    <?php wp_nonce_field( 'sb_seed_dummy_action', 'sb_seed_dummy_nonce' ); ?>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Muat Data Dummy Material Bangunan', 'sagara-baja-suite' ); ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if ( isset( $_GET['seeded'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Master data dummy material bangunan dan stok awal berhasil dimuat!', 'sagara-baja-suite' ); ?></p></div>
            <?php endif; ?>

            <form method="get" style="margin-top: 15px;">
                <input type="hidden" name="page" value="sb-products">
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Cari SKU, Barcode, atau Nama...', 'sagara-baja-suite' ); ?>">
                <button type="submit" class="button"><?php esc_html_e( 'Cari', 'sagara-baja-suite' ); ?></button>
            </form>

            <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th style="width: 120px;"><?php esc_html_e( 'SKU', 'sagara-baja-suite' ); ?></th>
                        <th><?php esc_html_e( 'Nama Barang Material', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 130px;"><?php esc_html_e( 'Kategori', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 80px;"><?php esc_html_e( 'Satuan', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 110px;"><?php esc_html_e( 'Harga Pokok', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 110px;"><?php esc_html_e( 'Eceran', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 110px;"><?php esc_html_e( 'Grosir', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 110px;"><?php esc_html_e( 'Proyek', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 100px;"><?php esc_html_e( 'Total Stok', 'sagara-baja-suite' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $products ) ) : ?>
                        <tr><td colspan="9"><?php esc_html_e( 'Belum ada barang terdaftar. Silakan klik tombol "Muat Data Dummy Material Bangunan" di atas.', 'sagara-baja-suite' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $products as $p ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $p['sku'] ); ?></strong></td>
                                <td><?php echo esc_html( $p['name'] ); ?></td>
                                <td><?php echo esc_html( $p['category_name'] ?? '-' ); ?></td>
                                <td><?php echo esc_html( $p['unit_code'] ?? '-' ); ?></td>
                                <td>Rp <?php echo number_format( (float) $p['cost_price'], 0, ',', '.' ); ?></td>
                                <td><strong>Rp <?php echo number_format( (float) $p['sell_price_retail'], 0, ',', '.' ); ?></strong></td>
                                <td>Rp <?php echo number_format( (float) $p['sell_price_wholesale'], 0, ',', '.' ); ?></td>
                                <td>Rp <?php echo number_format( (float) $p['sell_price_project'], 0, ',', '.' ); ?></td>
                                <td>
                                    <strong><?php echo number_format( (float) $p['total_stock'], 2, ',', '.' ); ?></strong>
                                    <?php echo esc_html( $p['unit_code'] ?? '' ); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render Halaman Kartu Stok (Mutasi).
     */
    public static function render_stock_moves_page(): void {
        if ( ! current_user_can( Capabilities::VIEW_INVENTORY ) ) {
            wp_die( esc_html__( 'Akses ditolak.', 'sagara-baja-suite' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . SB_TABLE_PREFIX;
        $table_moves = $prefix . 'stock_moves';
        $table_prod  = $prefix . 'products';
        $table_wh    = $prefix . 'warehouses';

        $moves = $wpdb->get_results(
            "SELECT m.*, p.sku, p.name AS product_name, w.name AS warehouse_name
             FROM `{$table_moves}` m
             LEFT JOIN `{$table_prod}` p ON m.product_id = p.id
             LEFT JOIN `{$table_wh}` w ON m.warehouse_id = w.id
             ORDER BY m.id DESC LIMIT 100",
            \ARRAY_A
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Kartu Stok Material (Riwayat Mutasi)', 'sagara-baja-suite' ); ?></h1>
            <p><?php esc_html_e( 'Setiap perubahan kuantitas fisik (masuk, keluar, transfer, opname) dicatat secara kekal untuk rekonsiliasi.', 'sagara-baja-suite' ); ?></p>

            <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th style="width: 140px;"><?php esc_html_e( 'Tanggal & Waktu', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 120px;"><?php esc_html_e( 'Gudang', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 120px;"><?php esc_html_e( 'SKU', 'sagara-baja-suite' ); ?></th>
                        <th><?php esc_html_e( 'Barang', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 120px;"><?php esc_html_e( 'Tipe Transaksi', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 100px;"><?php esc_html_e( 'Mutasi', 'sagara-baja-suite' ); ?></th>
                        <th style="width: 110px;"><?php esc_html_e( 'Saldo Akhir', 'sagara-baja-suite' ); ?></th>
                        <th><?php esc_html_e( 'Keterangan', 'sagara-baja-suite' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $moves ) ) : ?>
                        <tr><td colspan="8"><?php esc_html_e( 'Belum ada transaksi mutasi stok.', 'sagara-baja-suite' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $moves as $m ) : ?>
                            <?php $delta = (float) $m['qty_change']; ?>
                            <tr>
                                <td><?php echo esc_html( $m['move_date'] ); ?></td>
                                <td><?php echo esc_html( $m['warehouse_name'] ?? '-' ); ?></td>
                                <td><strong><?php echo esc_html( $m['sku'] ?? '-' ); ?></strong></td>
                                <td><?php echo esc_html( $m['product_name'] ?? '-' ); ?></td>
                                <td><code><?php echo esc_html( $m['ref_type'] ); ?></code></td>
                                <td style="color: <?php echo $delta >= 0 ? '#10b981' : '#ef4444'; ?>; font-weight: bold;">
                                    <?php echo ( $delta > 0 ? '+' : '' ) . number_format( $delta, 2, ',', '.' ); ?>
                                </td>
                                <td><strong><?php echo number_format( (float) $m['balance_after'], 2, ',', '.' ); ?></strong></td>
                                <td><?php echo esc_html( $m['notes'] ?? '-' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
