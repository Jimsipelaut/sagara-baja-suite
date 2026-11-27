<?php
namespace SagaraBaja\Modules\Inventory\Seeders;

use SagaraBaja\Core\Database\Transaction;
use SagaraBaja\Modules\Inventory\Repositories\ProductRepository;
use SagaraBaja\Modules\Inventory\Services\StockService;
use SagaraBaja\Modules\Inventory\Services\UnitConversionService;
use SagaraBaja\Modules\Sales\Repositories\CustomerRepository;
use SagaraBaja\Modules\Sales\Repositories\SupplierRepository;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * Seeder Master Data Dummy Toko Material Sagara Baja.
 * Mengisi kategori, satuan, gudang, konversi, produk material, dan saldo awal stok.
 */
class DummyDataSeeder {
	private \wpdb $wpdb;
	private string $prefix;
	private ProductRepository $product_repo;
	private StockService $stock_service;
	private UnitConversionService $conversion_service;
	private ?CustomerRepository $customer_repo = null;
	private ?SupplierRepository $supplier_repo = null;

	public function __construct( ?\wpdb $wpdb = null ) {
		global $wpdb;
		$this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
		$this->prefix = $this->wpdb->prefix . SB_TABLE_PREFIX;
		$this->product_repo       = new ProductRepository( $this->wpdb );
		$this->stock_service      = new StockService( $this->wpdb );
		$this->conversion_service = new UnitConversionService( $this->wpdb );
		if ( class_exists( CustomerRepository::class ) ) {
			$this->customer_repo = new CustomerRepository( $this->wpdb );
		}
		if ( class_exists( SupplierRepository::class ) ) {
			$this->supplier_repo = new SupplierRepository( $this->wpdb );
		}
	}

	public function run(): array {
		Transaction::begin( $this->wpdb );

		try {
			// 1. Gudang
			$wh_utama = $this->seed_warehouse( 'GD-UTAMA', 'Gudang Utama Setu', 'Jl. Raya Setu No. 88, Bekasi' );
			$wh_toko  = $this->seed_warehouse( 'GD-TOKO', 'Gudang Toko & Display', 'Area Depan Toko Sagara Baja' );

			// 2. Satuan
			$u_btg  = $this->seed_unit( 'btg', 'Batang' );
			$u_ikat = $this->seed_unit( 'ikat', 'Ikat' );
			$u_zak  = $this->seed_unit( 'zak', 'Zak' );
			$u_ton  = $this->seed_unit( 'ton', 'Ton' );
			$u_kg   = $this->seed_unit( 'kg', 'Kilogram' );
			$u_lbr  = $this->seed_unit( 'lbr', 'Lembar' );
			$u_m3   = $this->seed_unit( 'm3', 'Meter Kubik' );
			$u_dus  = $this->seed_unit( 'dus', 'Dus / Box' );
			$u_pcs  = $this->seed_unit( 'pcs', 'Pcs' );

			// 3. Kategori
			$c_besi    = $this->seed_category( 'Besi & Baja', 'besi-baja' );
			$c_baja    = $this->seed_category( 'Baja Ringan & Atap', 'baja-ringan-atap' );
			$c_semen   = $this->seed_category( 'Semen & Pasir', 'semen-pasir' );
			$c_kayu    = $this->seed_category( 'Kayu & Plywood', 'kayu-plywood' );
			$c_baut    = $this->seed_category( 'Baut & Perlengkapan', 'baut-perlengkapan' );

			// 4. Konversi Global (1 Ton = 20 Zak @ 50kg, 1 Ton = 1000 Kg)
			$this->conversion_service->register_conversion( $u_ton, $u_zak, 20.0 );
			$this->conversion_service->register_conversion( $u_ton, $u_kg, 1000.0 );

			// 5. Produk Dummy Material Bangunan (30 Item Lengkap)
			$products_data = [
				// Besi & Baja
				[
					'sku'                  => 'SB-BESI-06',
					'barcode'              => '899100106001',
					'name'                 => 'Besi Beton 6mm Polos SNI (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 28000,
					'sell_price_retail'    => 33000,
					'sell_price_wholesale' => 31000,
					'sell_price_project'   => 29500,
					'min_stock'            => 100,
					'initial_qty_utama'    => 600,
					'initial_qty_toko'     => 100,
					'conversions'          => [ [ 'from' => $u_ikat, 'to' => $u_btg, 'ratio' => 30.0 ] ],
				],
				[
					'sku'                  => 'SB-BESI-08',
					'barcode'              => '899100108001',
					'name'                 => 'Besi Beton 8mm Polos SNI (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 42000,
					'sell_price_retail'    => 48000,
					'sell_price_wholesale' => 46000,
					'sell_price_project'   => 44500,
					'min_stock'            => 100,
					'initial_qty_utama'    => 500,
					'initial_qty_toko'     => 100,
					'conversions'          => [ [ 'from' => $u_ikat, 'to' => $u_btg, 'ratio' => 25.0 ] ],
				],
				[
					'sku'                  => 'SB-BESI-10',
					'barcode'              => '899100110001',
					'name'                 => 'Besi Beton 10mm Ulir SNI (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 65000,
					'sell_price_retail'    => 74000,
					'sell_price_wholesale' => 71000,
					'sell_price_project'   => 69000,
					'min_stock'            => 80,
					'initial_qty_utama'    => 400,
					'initial_qty_toko'     => 80,
					'conversions'          => [ [ 'from' => $u_ikat, 'to' => $u_btg, 'ratio' => 20.0 ] ],
				],
				[
					'sku'                  => 'SB-BESI-12',
					'barcode'              => '899100112001',
					'name'                 => 'Besi Beton 12mm Ulir SNI (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 96000,
					'sell_price_retail'    => 108000,
					'sell_price_wholesale' => 104000,
					'sell_price_project'   => 101000,
					'min_stock'            => 60,
					'initial_qty_utama'    => 300,
					'initial_qty_toko'     => 50,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-BESI-16',
					'barcode'              => '899100116001',
					'name'                 => 'Besi Beton 16mm Ulir SNI (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 170000,
					'sell_price_retail'    => 192000,
					'sell_price_wholesale' => 185000,
					'sell_price_project'   => 179000,
					'min_stock'            => 40,
					'initial_qty_utama'    => 200,
					'initial_qty_toko'     => 30,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-HLW-2040',
					'barcode'              => '899100120401',
					'name'                 => 'Besi Hollow Galvanis 20x40 1.2mm 6m (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 58000,
					'sell_price_retail'    => 68000,
					'sell_price_wholesale' => 64000,
					'sell_price_project'   => 61000,
					'min_stock'            => 50,
					'initial_qty_utama'    => 350,
					'initial_qty_toko'     => 60,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-HLW-4040',
					'barcode'              => '899100140401',
					'name'                 => 'Besi Hollow Galvanis 40x40 1.4mm 6m (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 98000,
					'sell_price_retail'    => 115000,
					'sell_price_wholesale' => 109000,
					'sell_price_project'   => 104000,
					'min_stock'            => 40,
					'initial_qty_utama'    => 250,
					'initial_qty_toko'     => 50,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-PIP-150',
					'barcode'              => '899100115001',
					'name'                 => 'Pipa Besi Galvanis 1.5 Inch Medium (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 125000,
					'sell_price_retail'    => 145000,
					'sell_price_wholesale' => 138000,
					'sell_price_project'   => 132000,
					'min_stock'            => 30,
					'initial_qty_utama'    => 180,
					'initial_qty_toko'     => 30,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-PLT-3030',
					'barcode'              => '899100130301',
					'name'                 => 'Plat Strip 3x30mm 6m (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 31000,
					'sell_price_retail'    => 38000,
					'sell_price_wholesale' => 35000,
					'sell_price_project'   => 33000,
					'min_stock'            => 40,
					'initial_qty_utama'    => 250,
					'initial_qty_toko'     => 40,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-WRM-M06',
					'barcode'              => '899100100601',
					'name'                 => 'Wiremesh M6 Ulir SNI 2.1x5.4m (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_lbr,
					'cost_price'           => 295000,
					'sell_price_retail'    => 340000,
					'sell_price_wholesale' => 325000,
					'sell_price_project'   => 310000,
					'min_stock'            => 20,
					'initial_qty_utama'    => 120,
					'initial_qty_toko'     => 20,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-WRM-M08',
					'barcode'              => '899100100801',
					'name'                 => 'Wiremesh M8 Ulir SNI 2.1x5.4m (Dummy)',
					'category_id'          => $c_besi,
					'base_unit_id'         => $u_lbr,
					'cost_price'           => 510000,
					'sell_price_retail'    => 580000,
					'sell_price_wholesale' => 555000,
					'sell_price_project'   => 535000,
					'min_stock'            => 15,
					'initial_qty_utama'    => 80,
					'initial_qty_toko'     => 15,
					'conversions'          => [],
				],

				// Baja Ringan & Atap
				[
					'sku'                  => 'SB-CNL-C75',
					'barcode'              => '899100207501',
					'name'                 => 'Kanal C75 Baja Ringan 0.75mm (Dummy)',
					'category_id'          => $c_baja,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 72000,
					'sell_price_retail'    => 82000,
					'sell_price_wholesale' => 79000,
					'sell_price_project'   => 76000,
					'min_stock'            => 50,
					'initial_qty_utama'    => 350,
					'initial_qty_toko'     => 70,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-CNL-C65',
					'barcode'              => '899100206501',
					'name'                 => 'Kanal C75 Baja Ringan 0.65mm (Dummy)',
					'category_id'          => $c_baja,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 62000,
					'sell_price_retail'    => 71000,
					'sell_price_wholesale' => 68000,
					'sell_price_project'   => 65000,
					'min_stock'            => 50,
					'initial_qty_utama'    => 300,
					'initial_qty_toko'     => 60,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-RNG-045',
					'barcode'              => '899100204501',
					'name'                 => 'Reng Baja Ringan 0.45mm (Dummy)',
					'category_id'          => $c_baja,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 34000,
					'sell_price_retail'    => 40000,
					'sell_price_wholesale' => 38000,
					'sell_price_project'   => 36500,
					'min_stock'            => 50,
					'initial_qty_utama'    => 300,
					'initial_qty_toko'     => 60,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-RNG-040',
					'barcode'              => '899100204001',
					'name'                 => 'Reng Baja Ringan 0.40mm (Dummy)',
					'category_id'          => $c_baja,
					'base_unit_id'         => $u_btg,
					'cost_price'           => 29000,
					'sell_price_retail'    => 35000,
					'sell_price_wholesale' => 33000,
					'sell_price_project'   => 31000,
					'min_stock'            => 50,
					'initial_qty_utama'    => 300,
					'initial_qty_toko'     => 60,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-SPD-035',
					'barcode'              => '899100403501',
					'name'                 => 'Atap Spandek Pasir 0.35mm 6m (Dummy)',
					'category_id'          => $c_baja,
					'base_unit_id'         => $u_lbr,
					'cost_price'           => 45000,
					'sell_price_retail'    => 54000,
					'sell_price_wholesale' => 51000,
					'sell_price_project'   => 48000,
					'min_stock'            => 30,
					'initial_qty_utama'    => 150,
					'initial_qty_toko'     => 30,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-SPD-030',
					'barcode'              => '899100403001',
					'name'                 => 'Atap Spandek Polos 0.30mm 6m (Dummy)',
					'category_id'          => $c_baja,
					'base_unit_id'         => $u_lbr,
					'cost_price'           => 36000,
					'sell_price_retail'    => 43000,
					'sell_price_wholesale' => 40500,
					'sell_price_project'   => 38500,
					'min_stock'            => 30,
					'initial_qty_utama'    => 200,
					'initial_qty_toko'     => 40,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-GTM-2X4',
					'barcode'              => '899100402401',
					'name'                 => 'Genteng Metal Pasir 2x4 Daun (Dummy)',
					'category_id'          => $c_baja,
					'base_unit_id'         => $u_lbr,
					'cost_price'           => 32000,
					'sell_price_retail'    => 38500,
					'sell_price_wholesale' => 36000,
					'sell_price_project'   => 34000,
					'min_stock'            => 40,
					'initial_qty_utama'    => 250,
					'initial_qty_toko'     => 50,
					'conversions'          => [],
				],

				// Semen & Pasir
				[
					'sku'                  => 'SB-SMN-TR50',
					'barcode'              => '899100305001',
					'name'                 => 'Semen Tiga Roda 50kg (Dummy)',
					'category_id'          => $c_semen,
					'base_unit_id'         => $u_zak,
					'cost_price'           => 62000,
					'sell_price_retail'    => 68000,
					'sell_price_wholesale' => 66000,
					'sell_price_project'   => 64500,
					'min_stock'            => 50,
					'initial_qty_utama'    => 200,
					'initial_qty_toko'     => 40,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-SMN-GS40',
					'barcode'              => '899100304001',
					'name'                 => 'Semen Gresik 40kg (Dummy)',
					'category_id'          => $c_semen,
					'base_unit_id'         => $u_zak,
					'cost_price'           => 49000,
					'sell_price_retail'    => 54500,
					'sell_price_wholesale' => 52500,
					'sell_price_project'   => 51000,
					'min_stock'            => 50,
					'initial_qty_utama'    => 250,
					'initial_qty_toko'     => 50,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-MTR-M100',
					'barcode'              => '899100310001',
					'name'                 => 'Semen Mortar Perekat Hebel 40kg (Dummy)',
					'category_id'          => $c_semen,
					'base_unit_id'         => $u_zak,
					'cost_price'           => 63000,
					'sell_price_retail'    => 72000,
					'sell_price_wholesale' => 69000,
					'sell_price_project'   => 66500,
					'min_stock'            => 40,
					'initial_qty_utama'    => 180,
					'initial_qty_toko'     => 35,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-HBL-10CM',
					'barcode'              => '899100320001',
					'name'                 => 'Bata Ringan Hebel 10x20x60cm (Dummy)',
					'category_id'          => $c_semen,
					'base_unit_id'         => $u_m3,
					'cost_price'           => 550000,
					'sell_price_retail'    => 620000,
					'sell_price_wholesale' => 595000,
					'sell_price_project'   => 580000,
					'min_stock'            => 10,
					'initial_qty_utama'    => 40,
					'initial_qty_toko'     => 8,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-PSR-PSG',
					'barcode'              => null,
					'name'                 => 'Pasir Pasang Bangunan Super (Dummy)',
					'category_id'          => $c_semen,
					'base_unit_id'         => $u_m3,
					'cost_price'           => 240000,
					'sell_price_retail'    => 290000,
					'sell_price_wholesale' => 275000,
					'sell_price_project'   => 260000,
					'min_stock'            => 10,
					'initial_qty_utama'    => 50,
					'initial_qty_toko'     => 10,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-BTU-SPL',
					'barcode'              => null,
					'name'                 => 'Batu Split / Kerikil Cor (Dummy)',
					'category_id'          => $c_semen,
					'base_unit_id'         => $u_m3,
					'cost_price'           => 260000,
					'sell_price_retail'    => 310000,
					'sell_price_wholesale' => 290000,
					'sell_price_project'   => 280000,
					'min_stock'            => 10,
					'initial_qty_utama'    => 45,
					'initial_qty_toko'     => 10,
					'conversions'          => [],
				],

				// Kayu & Plywood
				[
					'sku'                  => 'SB-PLW-09MM',
					'barcode'              => '899100509001',
					'name'                 => 'Triplek Meranti Cor 9mm 122x244 (Dummy)',
					'category_id'          => $c_kayu,
					'base_unit_id'         => $u_lbr,
					'cost_price'           => 85000,
					'sell_price_retail'    => 98000,
					'sell_price_wholesale' => 94000,
					'sell_price_project'   => 90000,
					'min_stock'            => 20,
					'initial_qty_utama'    => 120,
					'initial_qty_toko'     => 25,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-PLW-12MM',
					'barcode'              => '899100512001',
					'name'                 => 'Triplek Meranti 12mm 122x244 (Dummy)',
					'category_id'          => $c_kayu,
					'base_unit_id'         => $u_lbr,
					'cost_price'           => 125000,
					'sell_price_retail'    => 145000,
					'sell_price_wholesale' => 139000,
					'sell_price_project'   => 133000,
					'min_stock'            => 20,
					'initial_qty_utama'    => 100,
					'initial_qty_toko'     => 20,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-PLW-18MM',
					'barcode'              => '899100518001',
					'name'                 => 'Triplek Meranti 18mm 122x244 (Dummy)',
					'category_id'          => $c_kayu,
					'base_unit_id'         => $u_lbr,
					'cost_price'           => 185000,
					'sell_price_retail'    => 210000,
					'sell_price_wholesale' => 202000,
					'sell_price_project'   => 195000,
					'min_stock'            => 15,
					'initial_qty_utama'    => 80,
					'initial_qty_toko'     => 15,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-GRC-04MM',
					'barcode'              => '899100504001',
					'name'                 => 'Papan GRC / Kalsiboard 4mm 122x244 (Dummy)',
					'category_id'          => $c_kayu,
					'base_unit_id'         => $u_lbr,
					'cost_price'           => 52000,
					'sell_price_retail'    => 62000,
					'sell_price_wholesale' => 59000,
					'sell_price_project'   => 56000,
					'min_stock'            => 30,
					'initial_qty_utama'    => 150,
					'initial_qty_toko'     => 30,
					'conversions'          => [],
				],

				// Baut & Perlengkapan
				[
					'sku'                  => 'SB-SKR-ROOF',
					'barcode'              => '899100600101',
					'name'                 => 'Baut Roofing Baja Ringan 10x19 (500 pcs/dus) (Dummy)',
					'category_id'          => $c_baut,
					'base_unit_id'         => $u_dus,
					'cost_price'           => 68000,
					'sell_price_retail'    => 85000,
					'sell_price_wholesale' => 79000,
					'sell_price_project'   => 74000,
					'min_stock'            => 20,
					'initial_qty_utama'    => 100,
					'initial_qty_toko'     => 20,
					'conversions'          => [],
				],
				[
					'sku'                  => 'SB-PKU-KYU',
					'barcode'              => '899100600201',
					'name'                 => 'Paku Kayu Campur 5-10cm SNI (Dummy)',
					'category_id'          => $c_baut,
					'base_unit_id'         => $u_kg,
					'cost_price'           => 14500,
					'sell_price_retail'    => 18000,
					'sell_price_wholesale' => 16500,
					'sell_price_project'   => 15500,
					'min_stock'            => 50,
					'initial_qty_utama'    => 200,
					'initial_qty_toko'     => 40,
					'conversions'          => [],
				],
			];

			$seeded_products = [];
			foreach ( $products_data as $p ) {
				$existing = $this->product_repo->find_by_sku( $p['sku'] );
				if ( $existing ) {
					$pid = (int) $existing['id'];
				} else {
					$pid = $this->product_repo->create( $p );

					// Daftarkan konversi spesifik barang jika ada
					foreach ( $p['conversions'] as $conv ) {
						$this->conversion_service->register_conversion(
							$conv['from'],
							$conv['to'],
							$conv['ratio'],
							$pid
						);
					}
				}

				// Pastikan stok awal ada di Gudang Utama
				if ( ! empty( $p['initial_qty_utama'] ) ) {
					$bal_utama = $this->stock_service->get_balance( $pid, $wh_utama );
					if ( $bal_utama <= 0 ) {
						$this->stock_service->add_stock(
							$pid,
							$wh_utama,
							(float) $p['initial_qty_utama'],
							'initial',
							'INIT-' . $p['sku'] . '-GD1',
							$p['base_unit_id'],
							$p['cost_price'],
							'Saldo awal dummy data Gudang Utama'
						);
					}
				}

				// Pastikan stok awal ada di Gudang Toko & Display
				if ( ! empty( $p['initial_qty_toko'] ) ) {
					$bal_toko = $this->stock_service->get_balance( $pid, $wh_toko );
					if ( $bal_toko <= 0 ) {
						$this->stock_service->add_stock(
							$pid,
							$wh_toko,
							(float) $p['initial_qty_toko'],
							'initial',
							'INIT-' . $p['sku'] . '-GD2',
							$p['base_unit_id'],
							$p['cost_price'],
							'Saldo awal dummy data Gudang Toko'
						);
					}
				}

				$seeded_products[] = $pid;
			}

			// 6. Dummy Pelanggan (Customers)
			if ( $this->customer_repo ) {
				$customers_data = [
					[ 'code' => 'CUST-001', 'name' => 'CV Bangun Mandiri Perkasa', 'phone' => '081298765432', 'credit_limit' => 50000000, 'payment_terms_days' => 30 ],
					[ 'code' => 'CUST-002', 'name' => 'Mandor Bambang Prakoso',     'phone' => '085712345678', 'credit_limit' => 15000000, 'payment_terms_days' => 14 ],
					[ 'code' => 'CUST-003', 'name' => 'Toko Material Berkah Jaya',  'phone' => '087855443322', 'credit_limit' => 30000000, 'payment_terms_days' => 21 ],
					[ 'code' => 'CUST-004', 'name' => 'PT Cipta Graha Sejahtera',   'phone' => '02188997766',  'credit_limit' => 100000000, 'payment_terms_days' => 45 ],
				];
				foreach ( $customers_data as $cd ) {
					if ( ! $this->customer_repo->findByCode( $cd['code'] ) ) {
						$this->customer_repo->create( $cd );
					}
				}
			}

			// 7. Dummy Pemasok (Suppliers)
			if ( $this->supplier_repo ) {
				$suppliers_data = [
					[ 'code' => 'SUPP-001', 'name' => 'PT Krakatau Steel (Persero) Tbk', 'contact_person' => 'Bpk. Hendra', 'phone' => '0213854123' ],
					[ 'code' => 'SUPP-002', 'name' => 'PT Kencana Maju Bersama',          'contact_person' => 'Ibu Maya',   'phone' => '0215432987' ],
					[ 'code' => 'SUPP-003', 'name' => 'PT Indocement Tunggal Prakarsa',   'contact_person' => 'Bpk. Surya',  'phone' => '0218765432' ],
					[ 'code' => 'SUPP-004', 'name' => 'Distributor Kayu Makmur Sentosa',  'contact_person' => 'Bpk. David',  'phone' => '081399887766' ],
				];
				foreach ( $suppliers_data as $sd ) {
					if ( ! $this->supplier_repo->findByCode( $sd['code'] ) ) {
						$this->supplier_repo->create( $sd );
					}
				}
			}

			Transaction::commit( $this->wpdb );

			return [
				'status'   => 'success',
				'products' => $seeded_products,
			];
		} catch ( \Throwable $e ) {
			Transaction::rollback( $this->wpdb );
			throw $e;
		}
	}

	private function seed_warehouse( string $code, string $name, string $address ): int {
		$table = $this->prefix . 'warehouses';
		$id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM `{$table}` WHERE code = %s", $code ) );
		if ( $id ) {
			return (int) $id;
		}

		$this->wpdb->insert(
			$table,
			[
				'code'       => $code,
				'name'       => $name,
				'address'    => $address,
				'is_active'  => 1,
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%d', '%s' ]
		);

		return (int) $this->wpdb->insert_id;
	}

	private function seed_unit( string $code, string $name ): int {
		$table = $this->prefix . 'units';
		$id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM `{$table}` WHERE code = %s", $code ) );
		if ( $id ) {
			return (int) $id;
		}

		$this->wpdb->insert(
			$table,
			[
				'code'       => $code,
				'name'       => $name,
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s' ]
		);

		return (int) $this->wpdb->insert_id;
	}

	private function seed_category( string $name, string $slug ): int {
		$table = $this->prefix . 'categories';
		$id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM `{$table}` WHERE slug = %s", $slug ) );
		if ( $id ) {
			return (int) $id;
		}

		$this->wpdb->insert(
			$table,
			[
				'name'       => $name,
				'slug'       => $slug,
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s' ]
		);

		return (int) $this->wpdb->insert_id;
	}
}
