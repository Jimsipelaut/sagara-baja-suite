<?php
namespace SagaraBaja\Tests\Core;

use SagaraBaja\Core\Document\NumberingService;

class NumberingServiceTest {
    public function run(): void {
        $this->test_generate_increments_consecutively();
        $this->test_separate_sequences_never_mix();
        $this->test_padding_format();
        $this->test_invalid_doc_type_throws();
        $this->test_yearly_reset();
    }

    private function test_generate_increments_consecutively(): void {
        global $wpdb;
        $wpdb->sequences['invoice_resmi']['current_number'] = 0;
        $wpdb->sequences['invoice_resmi']['last_period'] = '2026';

        $service = new NumberingService( $wpdb );
        $date = new \DateTimeImmutable( '2026-11-24 10:00:00' );

        $num1 = $service->generate( 'invoice_resmi', $date );
        $num2 = $service->generate( 'invoice_resmi', $date );
        $num3 = $service->generate( 'invoice_resmi', $date );

        assert( $num1 === 'INV-RES/2026/00001', "Gagal: num1 diharapkan INV-RES/2026/00001, didapat {$num1}" );
        assert( $num2 === 'INV-RES/2026/00002', "Gagal: num2 diharapkan INV-RES/2026/00002, didapat {$num2}" );
        assert( $num3 === 'INV-RES/2026/00003', "Gagal: num3 diharapkan INV-RES/2026/00003, didapat {$num3}" );
        echo "  [PASS] test_generate_increments_consecutively\n";
    }

    private function test_separate_sequences_never_mix(): void {
        global $wpdb;
        $wpdb->sequences['invoice_resmi']['current_number'] = 5;
        $wpdb->sequences['invoice_resmi']['last_period'] = '2026';
        $wpdb->sequences['nota_non_resmi']['current_number'] = 2;
        $wpdb->sequences['nota_non_resmi']['last_period'] = '2026';

        $service = new NumberingService( $wpdb );
        $date = new \DateTimeImmutable( '2026-11-24 10:00:00' );

        $inv = $service->generate( 'invoice_resmi', $date );
        $not = $service->generate( 'nota_non_resmi', $date );

        assert( $inv === 'INV-RES/2026/00006', "Gagal: invoice resmi salah urutan: {$inv}" );
        assert( $not === 'NOT-NR/2026/00003', "Gagal: nota non-resmi salah urutan: {$not}" );
        echo "  [PASS] test_separate_sequences_never_mix\n";
    }

    private function test_padding_format(): void {
        global $wpdb;
        $wpdb->sequences['invoice_resmi']['current_number'] = 99;
        $wpdb->sequences['invoice_resmi']['last_period'] = '2026';

        $service = new NumberingService( $wpdb );
        $date = new \DateTimeImmutable( '2026-11-24 10:00:00' );

        $num = $service->generate( 'invoice_resmi', $date );
        assert( $num === 'INV-RES/2026/00100', "Gagal: padding 100 salah: {$num}" );
        echo "  [PASS] test_padding_format\n";
    }

    private function test_invalid_doc_type_throws(): void {
        global $wpdb;
        $service = new NumberingService( $wpdb );

        $threw = false;
        try {
            $service->generate( 'unregistered_doc_type' );
        } catch ( \RuntimeException $e ) {
            $threw = true;
        }

        assert( $threw, "Gagal: generate doc_type tidak dikenal harus melempar exception." );
        echo "  [PASS] test_invalid_doc_type_throws\n";
    }

    private function test_yearly_reset(): void {
        global $wpdb;
        $wpdb->sequences['invoice_resmi']['current_number'] = 450;
        $wpdb->sequences['invoice_resmi']['last_period'] = '2025';

        $service = new NumberingService( $wpdb );
        // Masuk ke tahun baru 2026
        $date = new \DateTimeImmutable( '2026-01-01 08:00:00' );

        $num = $service->generate( 'invoice_resmi', $date );
        assert( $num === 'INV-RES/2026/00001', "Gagal: reset tahun baru harus mulai dari 00001, didapat: {$num}" );
        echo "  [PASS] test_yearly_reset\n";
    }
}
