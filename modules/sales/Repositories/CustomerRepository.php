<?php
namespace SagaraBaja\Modules\Sales\Repositories;

/**
 * CustomerRepository
 *
 * CRUD master pelanggan. Tidak menyentuh tabel lain.
 * Semua query pakai $wpdb->prepare().
 */
class CustomerRepository {

	private \wpdb $wpdb;
	private string $table;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . 'sb_customers';
	}

	/**
	 * Buat pelanggan baru. Kembalikan ID baris baru.
	 *
	 * @param array{code:string, name:string, phone?:string, address?:string, credit_limit?:int, notes?:string} $data
	 * @throws \InvalidArgumentException jika code atau name kosong, atau code sudah ada.
	 * @throws \RuntimeException jika INSERT gagal.
	 */
	public function create( array $data ): int {
		$code = trim( $data['code'] ?? '' );
		$name = trim( $data['name'] ?? '' );

		if ( $code === '' ) {
			throw new \InvalidArgumentException( 'Kode pelanggan wajib diisi.' );
		}
		if ( $name === '' ) {
			throw new \InvalidArgumentException( 'Nama pelanggan wajib diisi.' );
		}

		$existing = $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT id FROM {$this->table} WHERE code = %s LIMIT 1", $code )
		);
		if ( $existing ) {
			throw new \InvalidArgumentException( "Kode pelanggan '{$code}' sudah digunakan." );
		}

		$result = $this->wpdb->insert( $this->table, [
			'code'         => $code,
			'name'         => $name,
			'phone'        => $data['phone']        ?? null,
			'address'      => $data['address']      ?? null,
			'credit_limit' => (int) ( $data['credit_limit'] ?? 0 ),
			'notes'        => $data['notes']        ?? null,
		] );

		if ( $result === false ) {
			throw new \RuntimeException( 'Gagal menyimpan pelanggan: ' . $this->wpdb->last_error );
		}

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Ambil pelanggan berdasarkan ID. Null jika tidak ditemukan.
	 */
	public function find( int $id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * Ambil pelanggan berdasarkan Kode. Null jika tidak ditemukan.
	 */
	public function findByCode( string $code ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE code = %s", $code ),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * Daftar pelanggan aktif, bisa disaring nama/kode.
	 *
	 * @return array[]
	 */
	public function list( string $search = '', int $limit = 50, int $offset = 0 ): array {
		if ( $search !== '' ) {
			$like = '%' . $this->wpdb->esc_like( $search ) . '%';
			return (array) $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT * FROM {$this->table}
					 WHERE is_active = 1 AND (name LIKE %s OR code LIKE %s)
					 ORDER BY name ASC LIMIT %d OFFSET %d",
					$like, $like, $limit, $offset
				),
				ARRAY_A
			);
		}

		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY name ASC LIMIT %d OFFSET %d",
				$limit, $offset
			),
			ARRAY_A
		);
	}

	/**
	 * Perbarui data pelanggan. Kembalikan jumlah baris yang berubah.
	 *
	 * @throws \InvalidArgumentException jika name kosong.
	 */
	public function update( int $id, array $data ): int {
		$name = trim( $data['name'] ?? '' );
		if ( $name === '' ) {
			throw new \InvalidArgumentException( 'Nama pelanggan wajib diisi.' );
		}

		$payload = [ 'name' => $name ];
		if ( isset( $data['phone'] ) )        $payload['phone']        = $data['phone'];
		if ( isset( $data['address'] ) )      $payload['address']      = $data['address'];
		if ( isset( $data['credit_limit'] ) ) $payload['credit_limit'] = (int) $data['credit_limit'];
		if ( isset( $data['notes'] ) )        $payload['notes']        = $data['notes'];
		if ( isset( $data['is_active'] ) )    $payload['is_active']    = (int) $data['is_active'];

		return (int) $this->wpdb->update( $this->table, $payload, [ 'id' => $id ] );
	}

	/**
	 * Non-aktifkan pelanggan (soft delete). Data tidak dihapus.
	 */
	public function deactivate( int $id ): void {
		$this->wpdb->update( $this->table, [ 'is_active' => 0 ], [ 'id' => $id ] );
	}
}
