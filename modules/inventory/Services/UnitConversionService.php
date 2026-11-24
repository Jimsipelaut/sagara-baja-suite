<?php
namespace SagaraBaja\Modules\Inventory\Services;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Layanan Konversi Antar-Satuan Barang Toko Material.
 * Memastikan konversi selalu lewat satu titik acuan dengan presisi desimal teruji.
 */
class UnitConversionService {
    private \wpdb $wpdb;
    private string $table_conversions;
    private string $table_units;

    public function __construct( ?\wpdb $wpdb = null ) {
        global $wpdb;
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $prefix = $this->wpdb->prefix . SB_TABLE_PREFIX;
        $this->table_conversions = $prefix . 'unit_conversions';
        $this->table_units       = $prefix . 'units';
    }

    /**
     * Konversi nilai kuantitas dari satu satuan ke satuan lain.
     *
     * @param float|int|string $qty
     * @param int|string       $from_unit ID atau kode satuan asal
     * @param int|string       $to_unit   ID atau kode satuan tujuan
     * @param int|null         $product_id ID barang (opsional jika ada rasio spesifik barang)
     * @return float Kuantitas hasil konversi
     * @throws \InvalidArgumentException jika konversi tidak terdaftar
     */
    public function convert( float|int|string $qty, int|string $from_unit, int|string $to_unit, ?int $product_id = null ): float {
        $qty = (float) $qty;
        if ( $qty <= 0 ) {
            throw new \InvalidArgumentException( "Kuantitas konversi harus bernilai positif lebih besar dari nol." );
        }

        $from_id = $this->resolve_unit_id( $from_unit );
        $to_id   = $this->resolve_unit_id( $to_unit );

        if ( $from_id === $to_id ) {
            return $qty;
        }

        // 1. Cek konversi langsung (from -> to)
        $ratio = $this->get_ratio( $from_id, $to_id, $product_id );
        if ( $ratio !== null ) {
            return round( $qty * $ratio, 4 );
        }

        // 2. Cek konversi kebalikan (to -> from)
        $reverse_ratio = $this->get_ratio( $to_id, $from_id, $product_id );
        if ( $reverse_ratio !== null && $reverse_ratio > 0 ) {
            return round( $qty / $reverse_ratio, 4 );
        }

        throw new \InvalidArgumentException( sprintf(
            "Konversi satuan dari '%s' ke '%s'%s tidak terdaftar di sistem.",
            esc_html( (string) $from_unit ),
            esc_html( (string) $to_unit ),
            $product_id ? " untuk produk ID {$product_id}" : ""
        ) );
    }

    /**
     * Daftarkan atau perbarui rasio konversi satuan.
     * Contoh: 1 ikat = 25 batang -> register_conversion('ikat', 'btg', 25.0, $product_id);
     */
    public function register_conversion( int|string $from_unit, int|string $to_unit, float $multiplier, ?int $product_id = null ): bool {
        $from_id = $this->resolve_unit_id( $from_unit );
        $to_id   = $this->resolve_unit_id( $to_unit );

        if ( $from_id === $to_id ) {
            return true;
        }

        // Cek apakah sudah ada
        $existing = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT id FROM `{$this->table_conversions}` 
             WHERE from_unit_id = %d AND to_unit_id = %d AND (product_id = %d OR (product_id IS NULL AND %d IS NULL))",
            $from_id, $to_id, $product_id, $product_id
        ) );

        if ( $existing ) {
            return (bool) $this->wpdb->update(
                $this->table_conversions,
                [ 'multiplier' => $multiplier ],
                [ 'id' => $existing ],
                [ '%f' ],
                [ '%d' ]
            );
        }

        return (bool) $this->wpdb->insert(
            $this->table_conversions,
            [
                'product_id'   => $product_id,
                'from_unit_id' => $from_id,
                'to_unit_id'   => $to_id,
                'multiplier'   => $multiplier,
                'created_at'   => current_time( 'mysql', true ),
            ],
            [ '%d', '%d', '%d', '%f', '%s' ]
        );
    }

    /**
     * Dapatkan rasio pengali langsung (1 from_unit = ratio * to_unit).
     */
    private function get_ratio( int $from_id, int $to_id, ?int $product_id ): ?float {
        // Prioritas 1: Rasio spesifik barang
        if ( $product_id !== null ) {
            $ratio = $this->wpdb->get_var( $this->wpdb->prepare(
                "SELECT multiplier FROM `{$this->table_conversions}` 
                 WHERE product_id = %d AND from_unit_id = %d AND to_unit_id = %d",
                $product_id, $from_id, $to_id
            ) );
            if ( $ratio !== null ) {
                return (float) $ratio;
            }
        }

        // Prioritas 2: Rasio global standar (product_id IS NULL)
        $ratio = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT multiplier FROM `{$this->table_conversions}` 
             WHERE product_id IS NULL AND from_unit_id = %d AND to_unit_id = %d",
            $from_id, $to_id
        ) );

        return $ratio !== null ? (float) $ratio : null;
    }

    /**
     * Resolve kode string satuan ('btg', 'ikat') menjadi ID numerik satuan.
     */
    public function resolve_unit_id( int|string $unit ): int {
        if ( is_numeric( $unit ) && (int) $unit > 0 ) {
            return (int) $unit;
        }

        $code = sanitize_key( (string) $unit );
        $id = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT id FROM `{$this->table_units}` WHERE code = %s",
            $code
        ) );

        if ( ! $id ) {
            throw new \InvalidArgumentException( sprintf( "Satuan dengan kode '%s' tidak ditemukan.", esc_html( $code ) ) );
        }

        return (int) $id;
    }
}
