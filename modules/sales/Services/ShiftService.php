<?php
namespace SagaraBaja\Modules\Sales\Services;

/**
 * ShiftService
 *
 * Mengelola shift kasir: buka, tutup, cari shift aktif.
 *
 * Invariant:
 * - Seorang kasir hanya boleh punya satu shift 'open' per gudang di satu waktu.
 * - Shift yang sudah ditutup tidak dapat dibuka ulang.
 * - closing_cash dicatat saat tutup shift; bisa nol (kasir menyerahkan semua ke brankas).
 */
class ShiftService {

	private \wpdb $wpdb;
	private string $table;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . 'sb_cashier_shifts';
	}

	/**
	 * Buka shift baru. Tolak jika kasir sudah punya shift terbuka di gudang yang sama.
	 *
	 * @throws \RuntimeException
	 */
	public function open( int $cashier_user_id, int $warehouse_id, int $opening_cash = 0, string $notes = '' ): int {
		$existing = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT id FROM {$this->table}
				 WHERE cashier_user_id = %d AND warehouse_id = %d AND status = 'open'
				 LIMIT 1",
				$cashier_user_id, $warehouse_id
			)
		);
		if ( $existing ) {
			throw new \RuntimeException( "Kasir masih memiliki shift terbuka (ID: {$existing}). Tutup shift sebelumnya terlebih dahulu." );
		}

		$result = $this->wpdb->insert( $this->table, [
			'cashier_user_id' => $cashier_user_id,
			'warehouse_id'    => $warehouse_id,
			'opened_at'       => current_time( 'mysql' ),
			'opening_cash'    => $opening_cash,
			'notes'           => $notes ?: null,
			'status'          => 'open',
		] );

		if ( $result === false ) {
			throw new \RuntimeException( 'Gagal membuka shift: ' . $this->wpdb->last_error );
		}

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Tutup shift. Catat uang fisik akhir (closing_cash).
	 *
	 * @throws \RuntimeException jika shift tidak ditemukan atau sudah ditutup.
	 */
	public function close( int $shift_id, int $closing_cash, string $notes = '' ): void {
		$shift = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $shift_id ),
			ARRAY_A
		);

		if ( ! $shift ) {
			throw new \RuntimeException( "Shift ID {$shift_id} tidak ditemukan." );
		}
		if ( $shift['status'] === 'closed' ) {
			throw new \RuntimeException( 'Shift sudah ditutup sebelumnya.' );
		}

		$this->wpdb->update( $this->table, [
			'status'       => 'closed',
			'closed_at'    => current_time( 'mysql' ),
			'closing_cash' => $closing_cash,
			'notes'        => $notes ?: $shift['notes'],
		], [ 'id' => $shift_id ] );
	}

	/**
	 * Ambil shift yang sedang terbuka untuk kasir dan gudang tertentu.
	 * Kembalikan null jika tidak ada shift terbuka.
	 */
	public function getOpenShift( int $cashier_user_id, int $warehouse_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table}
				 WHERE cashier_user_id = %d AND warehouse_id = %d AND status = 'open'
				 LIMIT 1",
				$cashier_user_id, $warehouse_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * Ambil data shift berdasarkan ID.
	 */
	public function find( int $shift_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $shift_id ),
			ARRAY_A
		);
		return $row ?: null;
	}
}
