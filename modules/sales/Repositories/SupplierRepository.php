<?php
namespace SagaraBaja\Modules\Sales\Repositories;

/**
 * SupplierRepository
 *
 * CRUD master supplier. Pola identik dengan CustomerRepository.
 * Tidak menyentuh tabel lain.
 */
class SupplierRepository {

	private \wpdb $wpdb;
	private string $table;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . 'sb_suppliers';
	}

	/**
	 * @param array{code:string, name:string, phone?:string, address?:string, notes?:string} $data
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function create( array $data ): int {
		$code = trim( $data['code'] ?? '' );
		$name = trim( $data['name'] ?? '' );

		if ( $code === '' ) {
			throw new \InvalidArgumentException( 'Kode supplier wajib diisi.' );
		}
		if ( $name === '' ) {
			throw new \InvalidArgumentException( 'Nama supplier wajib diisi.' );
		}

		$existing = $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT id FROM {$this->table} WHERE code = %s LIMIT 1", $code )
		);
		if ( $existing ) {
			throw new \InvalidArgumentException( "Kode supplier '{$code}' sudah digunakan." );
		}

		$result = $this->wpdb->insert( $this->table, [
			'code'    => $code,
			'name'    => $name,
			'phone'   => $data['phone']   ?? null,
			'address' => $data['address'] ?? null,
			'notes'   => $data['notes']   ?? null,
		] );

		if ( $result === false ) {
			throw new \RuntimeException( 'Gagal menyimpan supplier: ' . $this->wpdb->last_error );
		}

		return (int) $this->wpdb->insert_id;
	}

	public function find( int $id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public function findByCode( string $code ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE code = %s", $code ),
			ARRAY_A
		);
		return $row ?: null;
	}

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

	public function update( int $id, array $data ): int {
		$name = trim( $data['name'] ?? '' );
		if ( $name === '' ) {
			throw new \InvalidArgumentException( 'Nama supplier wajib diisi.' );
		}

		$payload = [ 'name' => $name ];
		if ( isset( $data['phone'] ) )     $payload['phone']     = $data['phone'];
		if ( isset( $data['address'] ) )   $payload['address']   = $data['address'];
		if ( isset( $data['notes'] ) )     $payload['notes']     = $data['notes'];
		if ( isset( $data['is_active'] ) ) $payload['is_active'] = (int) $data['is_active'];

		return (int) $this->wpdb->update( $this->table, $payload, [ 'id' => $id ] );
	}

	public function deactivate( int $id ): void {
		$this->wpdb->update( $this->table, [ 'is_active' => 0 ], [ 'id' => $id ] );
	}
}
