<?php
namespace SagaraBaja\Tests\Inventory;

use SagaraBaja\Modules\Inventory\Services\UnitConversionService;

class UnitConversionTest {
    private UnitConversionService $service;

    public function __construct() {
    }

    public function run(): void {
        global $wpdb;
        $wpdb->reset();
        $this->service = new UnitConversionService();

        $this->test_same_unit_conversion_returns_exact_qty();
        $this->test_direct_conversion();
        $this->test_inverse_conversion();
        $this->test_zero_or_negative_qty_throws_exception();
        $this->test_unregistered_conversion_throws_exception();
    }

    public function test_same_unit_conversion_returns_exact_qty(): void {
        $result = $this->service->convert( 15.5, 1, 1 );
        assert( $result === 15.5, "Konversi unit sama harus menghasilkan nilai identik." );
        echo "  [PASS] test_same_unit_conversion_returns_exact_qty\n";
    }

    public function test_direct_conversion(): void {
        // Register: 1 Ikat (ID 2) = 25 Batang (ID 1)
        $this->service->register_conversion( 2, 1, 25.0 );

        // Convert 4 Ikat to Batang -> 100 Batang
        $batang = $this->service->convert( 4.0, 2, 1 );
        assert( $batang === 100.0, "4 ikat @ 25 batang harus menghasilkan 100 batang. Didapat: " . $batang );
        echo "  [PASS] test_direct_conversion\n";
    }

    public function test_inverse_conversion(): void {
        // Convert 50 Batang back to Ikat -> 2.0 Ikat
        $ikat = $this->service->convert( 50.0, 1, 2 );
        assert( $ikat === 2.0, "50 batang harus menghasilkan 2 ikat. Didapat: " . $ikat );
        echo "  [PASS] test_inverse_conversion\n";
    }

    public function test_zero_or_negative_qty_throws_exception(): void {
        try {
            $this->service->convert( -5.0, 1, 2 );
            assert( false, "Harus melempar exception saat kuantitas negatif." );
        } catch ( \InvalidArgumentException $e ) {
            assert( true );
        }
        echo "  [PASS] test_zero_or_negative_qty_throws_exception\n";
    }

    public function test_unregistered_conversion_throws_exception(): void {
        try {
            // Unit 99 to Unit 88 belum terdaftar
            $this->service->convert( 10.0, 99, 88 );
            assert( false, "Harus melempar exception saat jalur konversi tidak ditemukan." );
        } catch ( \InvalidArgumentException $e ) {
            assert( true );
        }
        echo "  [PASS] test_unregistered_conversion_throws_exception\n";
    }
}
