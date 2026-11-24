<?php
namespace SagaraBaja\Tests\Core;

use SagaraBaja\Core\Settings\SettingsRepository;

class SettingsRepositoryTest {
    public function run(): void {
        $this->test_default_settings();
        $this->test_update_and_get_settings();
    }

    private function test_default_settings(): void {
        global $_mock_options;
        $_mock_options = [];

        $settings = SettingsRepository::get_all();
        assert( $settings['store_name'] === 'Sagara Baja', "Gagal: store_name default salah." );
        assert( $settings['timezone'] === 'Asia/Jakarta', "Gagal: timezone default harus Asia/Jakarta." );
        assert( $settings['currency'] === 'IDR', "Gagal: currency default harus IDR." );
        echo "  [PASS] test_default_settings\n";
    }

    private function test_update_and_get_settings(): void {
        global $_mock_options;
        $_mock_options = [];

        SettingsRepository::update( [
            'store_name'  => 'Sagara Baja Material',
            'store_phone' => '08123456789',
        ] );

        $updated = SettingsRepository::get_all();
        assert( $updated['store_name'] === 'Sagara Baja Material', "Gagal: update store_name tidak tersimpan." );
        assert( $updated['store_phone'] === '08123456789', "Gagal: update store_phone tidak tersimpan." );
        assert( $updated['timezone'] === 'Asia/Jakarta', "Gagal: fallback timezone hilang setelah update." );
        echo "  [PASS] test_update_and_get_settings\n";
    }
}
