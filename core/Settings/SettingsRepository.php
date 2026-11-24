<?php
namespace SagaraBaja\Core\Settings;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Penyimpan dan pembaca konfigurasi global toko Sagara Baja.
 */
class SettingsRepository {
    public const OPTION_KEY = 'sb_settings';

    /**
     * Konfigurasi bawaan sistem.
     */
    public static function defaults(): array {
        return [
            'store_name'      => 'Sagara Baja',
            'store_tagline'   => 'Toko Material & Logistik Bangunan',
            'store_address'   => 'Jl. Raya Setu, Bekasi, Jawa Barat',
            'store_phone'     => '',
            'store_email'     => '',
            'currency'        => 'IDR',
            'currency_symbol' => 'Rp',
            'timezone'        => 'Asia/Jakarta',
            'decimal_places'  => 0,
            'vat_enabled'     => 0,
            'vat_rate_pct'    => 11,
        ];
    }

    /**
     * Dapatkan semua setting dengan fallback nilai bawaan.
     */
    public static function get_all(): array {
        $saved = get_option( self::OPTION_KEY, [] );
        return wp_parse_args( is_array( $saved ) ? $saved : [], self::defaults() );
    }

    /**
     * Dapatkan nilai satu item pengaturan.
     */
    public static function get( string $key, mixed $default = null ): mixed {
        $all = self::get_all();
        return $all[ $key ] ?? $default;
    }

    /**
     * Perbarui pengaturan toko dengan sanitasi ketat.
     */
    public static function update( array $data ): bool {
        $current = self::get_all();

        $clean = [
            'store_name'      => sanitize_text_field( $data['store_name'] ?? $current['store_name'] ),
            'store_tagline'   => sanitize_text_field( $data['store_tagline'] ?? $current['store_tagline'] ),
            'store_address'   => sanitize_textarea_field( $data['store_address'] ?? $current['store_address'] ),
            'store_phone'     => sanitize_text_field( $data['store_phone'] ?? $current['store_phone'] ),
            'store_email'     => sanitize_email( $data['store_email'] ?? $current['store_email'] ),
            'currency'        => sanitize_text_field( $data['currency'] ?? $current['currency'] ),
            'currency_symbol' => sanitize_text_field( $data['currency_symbol'] ?? $current['currency_symbol'] ),
            'timezone'        => sanitize_text_field( $data['timezone'] ?? $current['timezone'] ),
            'decimal_places'  => max( 0, min( 4, (int) ( $data['decimal_places'] ?? $current['decimal_places'] ) ) ),
            'vat_enabled'     => ! empty( $data['vat_enabled'] ) ? 1 : 0,
            'vat_rate_pct'    => max( 0, (float) ( $data['vat_rate_pct'] ?? $current['vat_rate_pct'] ) ),
        ];

        return update_option( self::OPTION_KEY, $clean );
    }
}
