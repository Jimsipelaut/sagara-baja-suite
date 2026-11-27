<?php
namespace SagaraBaja\Core\Database;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * Transaction — penghitung kedalaman transaksi database (C-01).
 *
 * Masalah yang diperbaiki: NumberingService dan StockService masing-masing
 * membuka transaksi sendiri (START TRANSACTION … COMMIT). Bila keduanya
 * dipanggil dari dalam transaksi service lain (mis. GoodsReceiptService::
 * receive() atau SaleService::publish()), COMMIT di dalam itu bersifat
 * progresif di MySQL nyata: mutasi stok/nomor yang sudah terjadi TIDAK
 * ikut batal bila transaksi luar akhirnya ROLLBACK. Akibatnya penerimaan
 * barang yang gagal di langkah akhir bisa meninggalkan stok yang sudah
 * bertambah tanpa tanda terima.
 *
 * Cara kerja: kedalaman dihitung per koneksi $wpdb. begin() hanya
 * mengirim START TRANSACTION bila belum ada transaksi aktif; commit()
 * hanya mengirim COMMIT bila ini transaksi terluar; rollback() selalu
 * mengirim ROLLBACK dan mengembalikan kedalaman ke 0 — sesuai semantik
 * MySQL tanpa savepoint (ROLLBACK membatalkan SELURUH transaksi).
 * Semua service yang membuka transaksi WAJIB memakai helper ini, bukan
 * query mentah, supaya kedalamannya konsisten. Pemanggilan begin() /
 * commit() / rollback() di luar transaksi aktif adalah no-op yang aman.
 *
 * Catatan: kedalaman disimpan statis per proses PHP. Semua jalur service
 * memanggil rollback() di blok catch, jadi kedalaman kembali ke 0 setiap
 * kali operasi selesai (sukses maupun gagal). Bila sebuah request mati
 * fatal di tengah transaksi, kedalaman bisa bocor ke request berikutnya
 * pada server PHP yang sama prosesnya — dampak terburuknya sama dengan
 * perilaku lama (tiap statement auto-commit), bukan data rusak.
 */
class Transaction {

	/** @var array<int, int> kedalaman transaksi per koneksi (spl_object_id). */
	private static array $depths = [];

	public static function begin( \wpdb $wpdb ): void {
		$id = spl_object_id( $wpdb );
		$depth = self::$depths[ $id ] ?? 0;
		if ( $depth === 0 ) {
			$wpdb->query( 'START TRANSACTION' );
		}
		self::$depths[ $id ] = $depth + 1;
	}

	public static function commit( \wpdb $wpdb ): void {
		$id = spl_object_id( $wpdb );
		$depth = self::$depths[ $id ] ?? 0;
		if ( $depth <= 0 ) {
			return;
		}
		$depth--;
		self::$depths[ $id ] = $depth;
		if ( $depth === 0 ) {
			$wpdb->query( 'COMMIT' );
		}
	}

	public static function rollback( \wpdb $wpdb ): void {
		$id = spl_object_id( $wpdb );
		$depth = self::$depths[ $id ] ?? 0;
		if ( $depth <= 0 ) {
			return;
		}
		self::$depths[ $id ] = 0;
		$wpdb->query( 'ROLLBACK' );
	}

	/**
	 * Kedalaman saat ini (0 = tidak ada transaksi aktif). Dipakai tes.
	 */
	public static function depth( \wpdb $wpdb ): int {
		return self::$depths[ spl_object_id( $wpdb ) ] ?? 0;
	}

	/**
	 * Setel ulang semua kedalaman. Dipakai MockWPDB::reset() di tes agar
	 * satu suite tidak mewarisi kedalaman dari suite sebelumnya.
	 */
	public static function resetAll(): void {
		self::$depths = [];
	}
}
