<?php
namespace SagaraBaja\Core\Admin;

use SagaraBaja\Core\Auth\Capabilities;
use SagaraBaja\Core\Settings\SettingsRepository;
use SagaraBaja\Core\Audit\AuditLogger;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Pendaftaran Menu Admin WordPress dan antarmuka pengaturan toko Sagara Baja.
 */
class AdminMenu {
    public static function register(): void {
        add_action( 'admin_menu', [ self::class, 'add_menus' ] );
        add_action( 'admin_post_sb_save_settings', [ self::class, 'handle_save_settings' ] );
    }

    public static function add_menus(): void {
        // Menu Utama
        add_menu_page(
            __( 'Sagara Baja Suite', 'sagara-baja-suite' ),
            __( 'Sagara Baja', 'sagara-baja-suite' ),
            Capabilities::ACCESS_DASHBOARD,
            'sagara-baja',
            [ self::class, 'render_dashboard' ],
            'dashicons-store',
            25
        );

        // Submenu: Dashboard
        add_submenu_page(
            'sagara-baja',
            __( 'Dashboard Sagara Baja', 'sagara-baja-suite' ),
            __( 'Ringkasan', 'sagara-baja-suite' ),
            Capabilities::ACCESS_DASHBOARD,
            'sagara-baja',
            [ self::class, 'render_dashboard' ]
        );

        // Submenu: Pengaturan
        add_submenu_page(
            'sagara-baja',
            __( 'Pengaturan Toko', 'sagara-baja-suite' ),
            __( 'Pengaturan', 'sagara-baja-suite' ),
            Capabilities::MANAGE_SETTINGS,
            'sb-settings',
            [ self::class, 'render_settings' ]
        );

        // Submenu: Audit Log
        add_submenu_page(
            'sagara-baja',
            __( 'Jejak Audit Sistem', 'sagara-baja-suite' ),
            __( 'Audit Log', 'sagara-baja-suite' ),
            Capabilities::VIEW_AUDIT_LOGS,
            'sb-audit-log',
            [ self::class, 'render_audit_log' ]
        );
    }

    /**
     * Tampilan Ringkasan Dashboard.
     */
    public static function render_dashboard(): void {
        if ( ! current_user_can( Capabilities::ACCESS_DASHBOARD ) ) {
            wp_die( esc_html__( 'Anda tidak memiliki hak akses ke halaman ini.', 'sagara-baja-suite' ) );
        }

        $settings = SettingsRepository::get_all();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $settings['store_name'] ); ?> — Suite Panel</h1>
            <p class="description"><?php echo esc_html( $settings['store_tagline'] ); ?></p>
            <hr class="wp-header-end">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-top: 20px;">
                <div class="postbox" style="padding: 16px; border-left: 4px solid #2563EB;">
                    <h2 style="margin-top: 0;">Status Sistem</h2>
                    <p><strong>Versi:</strong> <?php echo esc_html( SB_VERSION ); ?></p>
                    <p><strong>Zona Waktu:</strong> <?php echo esc_html( $settings['timezone'] ); ?></p>
                    <p><strong>Mata Uang:</strong> <?php echo esc_html( $settings['currency'] . ' (' . $settings['currency_symbol'] . ')' ); ?></p>
                </div>

                <div class="postbox" style="padding: 16px; border-left: 4px solid #10B981;">
                    <h2 style="margin-top: 0;">Sprint 0 — Core Ready</h2>
                    <p>Database migration system, role & capabilities, audit logger append-only, dan transactional document numbering telah aktif.</p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Tampilan Halaman Pengaturan Toko.
     */
    public static function render_settings(): void {
        if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
            wp_die( esc_html__( 'Anda tidak memiliki wewenang untuk mengubah pengaturan.', 'sagara-baja-suite' ) );
        }

        $settings = SettingsRepository::get_all();
        $updated  = isset( $_GET['updated'] ) && $_GET['updated'] === 'true';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Pengaturan Toko Sagara Baja', 'sagara-baja-suite' ); ?></h1>
            <hr class="wp-header-end">

            <?php if ( $updated ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Pengaturan berhasil disimpan.', 'sagara-baja-suite' ); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 800px; margin-top: 20px;">
                <input type="hidden" name="action" value="sb_save_settings">
                <?php wp_nonce_field( 'sb_save_settings_nonce', '_sb_nonce' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="store_name"><?php esc_html_e( 'Nama Toko / Usaha', 'sagara-baja-suite' ); ?></label></th>
                        <td><input name="store_name" type="text" id="store_name" value="<?php echo esc_attr( $settings['store_name'] ); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="store_tagline"><?php esc_html_e( 'Slogan / Deskripsi Singkat', 'sagara-baja-suite' ); ?></label></th>
                        <td><input name="store_tagline" type="text" id="store_tagline" value="<?php echo esc_attr( $settings['store_tagline'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="store_address"><?php esc_html_e( 'Alamat Lengkap', 'sagara-baja-suite' ); ?></label></th>
                        <td><textarea name="store_address" id="store_address" rows="3" class="large-text"><?php echo esc_textarea( $settings['store_address'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="store_phone"><?php esc_html_e( 'No. Telepon / WhatsApp', 'sagara-baja-suite' ); ?></label></th>
                        <td><input name="store_phone" type="text" id="store_phone" value="<?php echo esc_attr( $settings['store_phone'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="store_email"><?php esc_html_e( 'Email Operasional', 'sagara-baja-suite' ); ?></label></th>
                        <td><input name="store_email" type="email" id="store_email" value="<?php echo esc_attr( $settings['store_email'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="timezone"><?php esc_html_e( 'Zona Waktu', 'sagara-baja-suite' ); ?></label></th>
                        <td>
                            <input name="timezone" type="text" id="timezone" value="<?php echo esc_attr( $settings['timezone'] ); ?>" class="regular-text" readonly>
                            <p class="description"><?php esc_html_e( 'Sesuai standar operasional toko material (Asia/Jakarta, WIB).', 'sagara-baja-suite' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="currency"><?php esc_html_e( 'Mata Uang & Simbol', 'sagara-baja-suite' ); ?></label></th>
                        <td>
                            <input name="currency" type="text" id="currency" value="<?php echo esc_attr( $settings['currency'] ); ?>" style="width: 80px;" readonly>
                            <input name="currency_symbol" type="text" id="currency_symbol" value="<?php echo esc_attr( $settings['currency_symbol'] ); ?>" style="width: 60px;" readonly>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Simpan Pengaturan', 'sagara-baja-suite' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Tampilan Riwayat Audit Log.
     */
    public static function render_audit_log(): void {
        if ( ! current_user_can( Capabilities::VIEW_AUDIT_LOGS ) ) {
            wp_die( esc_html__( 'Anda tidak memiliki akses melihat Audit Log.', 'sagara-baja-suite' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . SB_TABLE_PREFIX . 'audit_logs';

        $logs = [];
        $check = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( $check ) {
            $logs = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT 50", \ARRAY_A );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Jejak Audit Sistem (Append-Only)', 'sagara-baja-suite' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Setiap mutasi stok, penerbitan invoice resmi/non-resmi, transaksi kasir, dan penggajian terekam permanen di sini.', 'sagara-baja-suite' ); ?></p>
            <hr class="wp-header-end">

            <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 140px;">Waktu (WIB)</th>
                        <th style="width: 120px;">Pengguna</th>
                        <th style="width: 100px;">Aksi</th>
                        <th style="width: 120px;">Tipe Objek</th>
                        <th style="width: 100px;">ID Objek</th>
                        <th>Perubahan Data</th>
                        <th style="width: 120px;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $logs ) ) : ?>
                        <tr>
                            <td colspan="8"><?php esc_html_e( 'Belum ada catatan audit log.', 'sagara-baja-suite' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td>#<?php echo esc_html( $log['id'] ); ?></td>
                                <td><?php echo esc_html( $log['created_at'] ); ?></td>
                                <td><?php echo esc_html( $log['user_name'] ?: 'Sistem' ); ?></td>
                                <td><code><?php echo esc_html( $log['action'] ); ?></code></td>
                                <td><?php echo esc_html( $log['object_type'] ); ?></td>
                                <td><?php echo esc_html( $log['object_id'] ?: '-' ); ?></td>
                                <td>
                                    <?php if ( $log['new_values'] ) : ?>
                                        <pre style="margin: 0; font-size: 11px; white-space: pre-wrap;"><?php echo esc_html( $log['new_values'] ); ?></pre>
                                    <?php else : ?>
                                        <em>-</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $log['ip_address'] ?: '-' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Handler simpan pengaturan dengan proteksi Nonce dan Capability.
     */
    public static function handle_save_settings(): void {
        if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
            wp_die( esc_html__( 'Akses ditolak.', 'sagara-baja-suite' ), 403 );
        }

        check_admin_referer( 'sb_save_settings_nonce', '_sb_nonce' );

        $old_settings = SettingsRepository::get_all();
        SettingsRepository::update( $_POST );
        $new_settings = SettingsRepository::get_all();

        AuditLogger::log( 'update_settings', 'settings', 'global', $old_settings, $new_settings );

        wp_safe_redirect( add_query_arg( [ 'page' => 'sb-settings', 'updated' => 'true' ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
