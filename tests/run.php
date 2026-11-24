<?php
/**
 * Test Runner Sagara Baja Suite.
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Core/NumberingServiceTest.php';
require_once __DIR__ . '/Core/AuditLoggerTest.php';
require_once __DIR__ . '/Core/RolesTest.php';
require_once __DIR__ . '/Core/SettingsRepositoryTest.php';

// Sprint 1 Inventory Test Suites
require_once __DIR__ . '/Inventory/UnitConversionTest.php';
require_once __DIR__ . '/Inventory/StockServiceTest.php';
require_once __DIR__ . '/Inventory/ProductRepositoryTest.php';
require_once __DIR__ . '/Inventory/StockOpnameTest.php';
require_once __DIR__ . '/Inventory/DummyDataSeederTest.php';

echo "=== Menjalankan Test Suite Sagara Baja Suite ===\n\n";

$tests = [
    'Roles & Capabilities' => new \SagaraBaja\Tests\Core\RolesTest(),
    'NumberingService'     => new \SagaraBaja\Tests\Core\NumberingServiceTest(),
    'AuditLogger'          => new \SagaraBaja\Tests\Core\AuditLoggerTest(),
    'SettingsRepository'   => new \SagaraBaja\Tests\Core\SettingsRepositoryTest(),
    'UnitConversion'       => new \SagaraBaja\Tests\Inventory\UnitConversionTest(),
    'StockService'         => new \SagaraBaja\Tests\Inventory\StockServiceTest(),
    'ProductRepository'    => new \SagaraBaja\Tests\Inventory\ProductRepositoryTest(),
    'StockOpname'          => new \SagaraBaja\Tests\Inventory\StockOpnameTest(),
    'DummyDataSeeder'      => new \SagaraBaja\Tests\Inventory\DummyDataSeederTest(),
];

$failed = 0;
foreach ( $tests as $name => $test ) {
    echo "Pengujian [{$name}]:\n";
    try {
        $test->run();
    } catch ( \Throwable $e ) {
        echo "  [FAIL] " . $e->getMessage() . "\n";
        echo "         " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
    echo "\n";
}

if ( $failed === 0 ) {
    echo "HASIL: SEMUA PENGUJIAN LULUS (100% OK).\n";
    exit( 0 );
} else {
    echo "HASIL: ADA {$failed} PENGUJIAN YANG GAGAL.\n";
    exit( 1 );
}
