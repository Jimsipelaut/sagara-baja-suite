<?php
namespace SagaraBaja\Modules\Inventory\Services;

use SagaraBaja\Core\Audit\AuditLogger;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Layanan Mutasi dan Saldo Stok Toko Material Sagara Baja.
 * Mengimplementasikan proteksi row lock (FOR UPDATE), pencegahan stok negatif,
 * dan rekonsiliasi kartu stok sesuai SOUL.md.
 */
class StockService {
    private \wpdb $wpdb;
    private string $table_balances;
    private string $table_moves;
    private string $table_products;
    private string $table_warehouses;
    private UnitConversionService $conversion_service;

    public function __construct( ?\wpdb $wpdb = null, ?UnitConversionService $conversion_service = null ) {
        global $wpdb;
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $prefix = $this->wpdb->prefix . SB_TABLE_PREFIX;
        $this->table_balances   = $prefix . 'stock_balances';
        $this->table_moves      = $prefix . 'stock_moves';
        $this->table_products   = $prefix . 'products';
        $this->table_warehouses = $prefix . 'warehouses';
        $this->conversion_service = $conversion_service ?? new UnitConversionService( $this->wpdb );
    }

    /**
     * Tambah stok masuk (Pembelian, Penerimaan Barang, Retur Penjualan, Penyesuaian Positif).
     */
    public function add_stock(
        int $product_id,
        int $warehouse_id,
        float $qty,
        string $ref_type,
        ?string $ref_id = null,
        ?int $unit_id = null,
        int $unit_cost = 0,
        ?string $notes = null,
        ?int $user_id = null
    ): array {
        if ( $qty <= 0 ) {
            throw new \InvalidArgumentException( "Kuantitas masuk harus lebih besar dari 0." );
        }

        $base_unit_id = $this->get_product_base_unit( $product_id );
        $unit_id = $unit_id ?? $base_unit_id;

        // Konversi kuantitas ke satuan dasar produk
        $base_qty = $this->conversion_service->convert( $qty, $unit_id, $base_unit_id, $product_id );

        $this->wpdb->query( "START TRANSACTION" );

        try {
            // Kunci baris saldo untuk gudang dan produk tersebut
            $current_balance = $this->lock_balance( $product_id, $warehouse_id );
            $new_balance = round( $current_balance + $base_qty, 4 );

            // Update atau insert saldo
            $this->save_balance( $product_id, $warehouse_id, $new_balance );

            // Catat kartu stok (sb_stock_moves)
            $move_id = $this->record_move(
                $product_id,
                $warehouse_id,
                $base_qty,
                $new_balance,
                $ref_type,
                $ref_id,
                $unit_id,
                $unit_cost,
                $notes,
                $user_id
            );

            // Catat audit log untuk penelusuran akuntansi
            AuditLogger::log(
                'stock_in',
                'stock',
                "PROD-{$product_id}-WH-{$warehouse_id}",
                [ 'qty' => $current_balance ],
                [ 'qty' => $new_balance, 'delta' => $base_qty, 'ref_type' => $ref_type, 'ref_id' => $ref_id ]
            );

            $this->wpdb->query( "COMMIT" );

            return [
                'success'         => true,
                'move_id'         => $move_id,
                'product_id'      => $product_id,
                'warehouse_id'    => $warehouse_id,
                'qty_added'       => $base_qty,
                'balance_before'  => $current_balance,
                'balance_after'   => $new_balance,
            ];
        } catch ( \Throwable $e ) {
            $this->wpdb->query( "ROLLBACK" );
            throw $e;
        }
    }

    /**
     * Kurangi stok keluar (Penjualan POS, Invoice, Retur Pembelian, Penyesuaian Negatif).
     * Menerapkan row lock dan tolak stok negatif secara mutlak (kecuali allow_negative disetel eksplisit).
     */
    public function deduct_stock(
        int $product_id,
        int $warehouse_id,
        float $qty,
        string $ref_type,
        ?string $ref_id = null,
        ?int $unit_id = null,
        int $unit_cost = 0,
        ?string $notes = null,
        ?int $user_id = null,
        bool $allow_negative = false
    ): array {
        if ( $qty <= 0 ) {
            throw new \InvalidArgumentException( "Kuantitas keluar harus lebih besar dari 0." );
        }

        $base_unit_id = $this->get_product_base_unit( $product_id );
        $unit_id = $unit_id ?? $base_unit_id;

        // Konversi kuantitas ke satuan dasar
        $base_qty = $this->conversion_service->convert( $qty, $unit_id, $base_unit_id, $product_id );

        $this->wpdb->query( "START TRANSACTION" );

        try {
            // Kunci baris saldo untuk mencegah race condition penjualan bersamaan
            $current_balance = $this->lock_balance( $product_id, $warehouse_id );

            if ( ! $allow_negative && ( $current_balance < $base_qty ) ) {
                $sku = $this->get_product_sku( $product_id );
                throw new \UnderflowException( sprintf(
                    "Stok tidak mencukupi untuk barang '%s' di gudang ini. Sisa stok: %s, diminta: %s.",
                    esc_html( $sku ),
                    number_format( $current_balance, 2 ),
                    number_format( $base_qty, 2 )
                ) );
            }

            $new_balance = round( $current_balance - $base_qty, 4 );

            // Update saldo
            $this->save_balance( $product_id, $warehouse_id, $new_balance );

            // Catat kartu stok (nilai qty_change negatif)
            $move_id = $this->record_move(
                $product_id,
                $warehouse_id,
                -$base_qty,
                $new_balance,
                $ref_type,
                $ref_id,
                $unit_id,
                $unit_cost,
                $notes,
                $user_id
            );

            // Audit log
            AuditLogger::log(
                'stock_out',
                'stock',
                "PROD-{$product_id}-WH-{$warehouse_id}",
                [ 'qty' => $current_balance ],
                [ 'qty' => $new_balance, 'delta' => -$base_qty, 'ref_type' => $ref_type, 'ref_id' => $ref_id ]
            );

            $this->wpdb->query( "COMMIT" );

            return [
                'success'         => true,
                'move_id'         => $move_id,
                'product_id'      => $product_id,
                'warehouse_id'    => $warehouse_id,
                'qty_deducted'    => $base_qty,
                'balance_before'  => $current_balance,
                'balance_after'   => $new_balance,
            ];
        } catch ( \Throwable $e ) {
            $this->wpdb->query( "ROLLBACK" );
            throw $e;
        }
    }

    /**
     * Transfer stok antar-gudang (misal dari Gudang Utama ke Toko).
     */
    public function transfer_stock(
        int $product_id,
        int $from_warehouse_id,
        int $to_warehouse_id,
        float $qty,
        ?int $unit_id = null,
        ?string $notes = null,
        ?int $user_id = null
    ): array {
        if ( $from_warehouse_id === $to_warehouse_id ) {
            throw new \InvalidArgumentException( "Gudang asal dan tujuan tidak boleh sama." );
        }

        $this->wpdb->query( "START TRANSACTION" );

        try {
            $transfer_ref = 'TRF-' . uniqid();

            // 1. Kurangi dari gudang asal
            $out = $this->deduct_stock(
                $product_id,
                $from_warehouse_id,
                $qty,
                'transfer_out',
                $transfer_ref,
                $unit_id,
                0,
                $notes ?? "Transfer keluar ke gudang #{$to_warehouse_id}",
                $user_id
            );

            // 2. Tambah ke gudang tujuan
            $in = $this->add_stock(
                $product_id,
                $to_warehouse_id,
                $qty,
                'transfer_in',
                $transfer_ref,
                $unit_id,
                0,
                $notes ?? "Transfer masuk dari gudang #{$from_warehouse_id}",
                $user_id
            );

            $this->wpdb->query( "COMMIT" );

            return [
                'success'      => true,
                'transfer_ref' => $transfer_ref,
                'out'          => $out,
                'in'           => $in,
            ];
        } catch ( \Throwable $e ) {
            $this->wpdb->query( "ROLLBACK" );
            throw $e;
        }
    }

    /**
     * Periksa rekonsiliasi: Saldo tabel sb_stock_balances wajib sama persis
     * dengan total akumulasi mutasi di sb_stock_moves.
     */
    public function reconcile( int $product_id, int $warehouse_id ): array {
        $balance = (float) $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT qty FROM `{$this->table_balances}` WHERE product_id = %d AND warehouse_id = %d",
            $product_id, $warehouse_id
        ) );

        $total_moves = (float) $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT COALESCE(SUM(qty_change), 0) FROM `{$this->table_moves}` WHERE product_id = %d AND warehouse_id = %d",
            $product_id, $warehouse_id
        ) );

        $diff = round( abs( $balance - $total_moves ), 4 );
        $is_balanced = ( $diff === 0.0 );

        return [
            'product_id'   => $product_id,
            'warehouse_id' => $warehouse_id,
            'balance_qty'  => $balance,
            'moves_sum'    => $total_moves,
            'difference'   => $diff,
            'is_balanced'  => $is_balanced,
        ];
    }

    /**
     * Dapatkan saldo stok saat ini untuk produk dan gudang tertentu.
     */
    public function get_balance( int $product_id, int $warehouse_id ): float {
        $qty = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT qty FROM `{$this->table_balances}` WHERE product_id = %d AND warehouse_id = %d",
            $product_id, $warehouse_id
        ) );
        return $qty !== null ? (float) $qty : 0.0;
    }

    /**
     * Kunci baris saldo dengan SELECT ... FOR UPDATE.
     */
    private function lock_balance( int $product_id, int $warehouse_id ): float {
        $row = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT id, qty FROM `{$this->table_balances}` WHERE product_id = %d AND warehouse_id = %d FOR UPDATE",
            $product_id, $warehouse_id
        ), \ARRAY_A );

        if ( ! $row ) {
            // Belum ada baris, inisialisasi awal 0
            $this->wpdb->insert(
                $this->table_balances,
                [
                    'product_id'   => $product_id,
                    'warehouse_id' => $warehouse_id,
                    'qty'          => 0,
                    'updated_at'   => current_time( 'mysql', true ),
                ],
                [ '%d', '%d', '%f', '%s' ]
            );
            return 0.0;
        }

        return (float) $row['qty'];
    }

    /**
     * Simpan saldo baru ke tabel sb_stock_balances.
     */
    private function save_balance( int $product_id, int $warehouse_id, float $new_balance ): void {
        $this->wpdb->update(
            $this->table_balances,
            [
                'qty'        => $new_balance,
                'updated_at' => current_time( 'mysql', true ),
            ],
            [
                'product_id'   => $product_id,
                'warehouse_id' => $warehouse_id,
            ],
            [ '%f', '%s' ],
            [ '%d', '%d' ]
        );
    }

    /**
     * Catat histori kartu stok di sb_stock_moves.
     */
    private function record_move(
        int $product_id,
        int $warehouse_id,
        float $qty_change,
        float $balance_after,
        string $ref_type,
        ?string $ref_id,
        int $unit_id,
        int $unit_cost,
        ?string $notes,
        ?int $user_id
    ): int {
        $this->wpdb->insert(
            $this->table_moves,
            [
                'move_date'     => current_time( 'mysql', true ),
                'ref_type'      => $ref_type,
                'ref_id'        => $ref_id,
                'product_id'    => $product_id,
                'warehouse_id'  => $warehouse_id,
                'qty_change'    => $qty_change,
                'unit_id'       => $unit_id,
                'unit_cost'     => $unit_cost,
                'balance_after' => $balance_after,
                'notes'         => $notes,
                'user_id'       => $user_id ?? get_current_user_id(),
                'created_at'    => current_time( 'mysql', true ),
            ],
            [ '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%f', '%s', '%d', '%s' ]
        );

        return (int) $this->wpdb->insert_id;
    }

    private function get_product_base_unit( int $product_id ): int {
        $base_unit = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT base_unit_id FROM `{$this->table_products}` WHERE id = %d",
            $product_id
        ) );

        if ( ! $base_unit ) {
            throw new \InvalidArgumentException( sprintf( "Produk dengan ID %d tidak ditemukan.", $product_id ) );
        }

        return (int) $base_unit;
    }

    private function get_product_sku( int $product_id ): string {
        $sku = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT sku FROM `{$this->table_products}` WHERE id = %d",
            $product_id
        ) );
        return (string) ( $sku ?? "ID-{$product_id}" );
    }
}
