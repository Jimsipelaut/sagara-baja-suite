<?php
namespace SagaraBaja\Core\Database\Migrations;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * Migration_006_IdempotencyKeys
 *
 * Menambah kunci idempotensi ke kedua tabel pembayaran (C-13):
 *   sb_supplier_payments.idempotency_key — pembayaran hutang (Sprint 4)
 *   sb_payments.idempotency_key          — pembayaran penjualan (Sprint 2)
 *
 * Desain:
 * - Kunci dibuat oleh pemanggil (satu token per pengiriman formulir /
 *   per percobaan checkout) dan disimpan apa adanya, maks. 64 karakter
 *   [A-Za-z0-9_-]. NULL berarti "tanpa kunci" (pemanggil lama) — baris
 *   tanpa kunci tidak pernah dianggap duplikat satu sama lain.
 * - UNIQUE KEY per tabel adalah pengaman terakhir di database: dua
 *   pengiriman bersamaan dengan kunci yang sama tidak bisa sama-sama
 *   tersimpan, walau keduanya lolos cek baca. Service layer memeriksa
 *   kunci yang sudah ada di dalam transaksi dan mengembalikan pembayaran
 *   lama alih-alih mencatat yang baru (lihat SupplierPaymentService dan
 *   SaleService).
 * - Idempoten dan aman diulang: kolom dan index hanya ditambah bila
 *   belum ada, sehingga reaktivasi plugin setelah impor dump lama
 *   (yang menimpa schema_versions) tetap bisa menjalankan migrasi ini.
 */
class Migration_006_IdempotencyKeys {
	private \wpdb $wpdb;
	private string $prefix;

	public function __construct( \wpdb $wpdb, string $prefix ) {
		$this->wpdb   = $wpdb;
		$this->prefix = $prefix;
	}

	public function up(): void {
		$this->addKeyColumn( $this->prefix . 'supplier_payments', 'uq_supplier_payments_idem' );
		$this->addKeyColumn( $this->prefix . 'payments', 'uq_payments_idem' );
	}

	public function down(): void {
		$this->dropKeyColumn( $this->prefix . 'supplier_payments', 'uq_supplier_payments_idem' );
		$this->dropKeyColumn( $this->prefix . 'payments', 'uq_payments_idem' );
	}

	private function addKeyColumn( string $table, string $index_name ): void {
		$has_column = $this->wpdb->get_results(
			"SHOW COLUMNS FROM `{$table}` LIKE 'idempotency_key'"
		);
		if ( empty( $has_column ) ) {
			$this->wpdb->query(
				"ALTER TABLE `{$table}` ADD COLUMN `idempotency_key` VARCHAR(64) DEFAULT NULL AFTER `reference`"
			);
		}

		$has_index = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SHOW INDEX FROM `{$table}` WHERE Key_name = %s",
				$index_name
			)
		);
		if ( empty( $has_index ) ) {
			$this->wpdb->query(
				"ALTER TABLE `{$table}` ADD UNIQUE KEY `{$index_name}` (`idempotency_key`)"
			);
		}
	}

	private function dropKeyColumn( string $table, string $index_name ): void {
		$has_index = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SHOW INDEX FROM `{$table}` WHERE Key_name = %s",
				$index_name
			)
		);
		if ( ! empty( $has_index ) ) {
			$this->wpdb->query( "ALTER TABLE `{$table}` DROP INDEX `{$index_name}`" );
		}

		$has_column = $this->wpdb->get_results(
			"SHOW COLUMNS FROM `{$table}` LIKE 'idempotency_key'"
		);
		if ( ! empty( $has_column ) ) {
			$this->wpdb->query( "ALTER TABLE `{$table}` DROP COLUMN `idempotency_key`" );
		}
	}
}
