<?php
namespace SagaraBaja\Modules\Sales\Services;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
	exit;
}

/**
 * ReceivablesAgingService
 *
 * Laporan umur piutang: penjualan berstatus 'terbit' yang belum lunas,
 * dikelompokkan menurut keterlambatan dari tanggal jatuh tempo.
 *
 * Aturan yang dipakai:
 * 1. Hanya dokumen 'terbit' yang masuk aging. Dokumen 'lunas' sudah tidak
 *    berpiutang, 'draft' belum diterbitkan, 'batal' sudah dibatalkan.
 * 2. Sisa piutang per dokumen = grand_total - paid_amount. Sisa <= 0 dilewati.
 * 3. Jatuh tempo = sale_date + 30 hari (DUE_DAYS). sb_sales belum punya kolom
 *    jatuh tempo; angka 30 hari adalah asumsi yang dicatat di PROGRESS.md.
 * 4. Bucket menurut hari terlambat dari jatuh tempo ke tanggal acuan.
 * 5. Uang integer Rupiah, tidak pernah float.
 * 6. Semua tanggal dihitung sebagai tanggal saja (jam 00:00, Asia/Jakarta)
 *    supaya selisih hari tidak meleset karena jam atau zona waktu server.
 */
class ReceivablesAgingService {

	public const DUE_DAYS = 30;

	public const BUCKET_NOT_DUE  = 'belum_jatuh_tempo';
	public const BUCKET_1_30     = '1_30';
	public const BUCKET_31_60    = '31_60';
	public const BUCKET_61_90    = '61_90';
	public const BUCKET_OVER_90  = 'lebih_90';

	private \wpdb $wpdb;
	private string $t_sales;
	private string $t_customers;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->t_sales    = $wpdb->prefix . 'sb_sales';
		$this->t_customers = $wpdb->prefix . 'sb_customers';
	}

	/**
	 * Hitung umur piutang per tanggal acuan (default: hari ini, Asia/Jakarta).
	 *
	 * @return array{as_of:string, rows:array[], totals:array<string,int>, count:int}
	 * @throws \InvalidArgumentException jika tanggal acuan bukan format YYYY-MM-DD.
	 */
	public function getAging( ?string $asOf = null, ?int $customerId = null ): array {
		$asOfDate = $this->parseAsOf( $asOf );

		$sql = "SELECT s.*, c.name AS customer_name FROM {$this->t_sales} s"
			. " LEFT JOIN {$this->t_customers} c ON c.id = s.customer_id"
			. " WHERE s.status = 'terbit'";
		if ( $customerId !== null ) {
			$sql .= $this->wpdb->prepare( ' AND s.customer_id = %d', $customerId );
		}
		$sql .= ' ORDER BY s.sale_date ASC, s.id ASC';

		$sales = (array) $this->wpdb->get_results( $sql, ARRAY_A );

		$rows   = [];
		$totals = [
			self::BUCKET_NOT_DUE => 0,
			self::BUCKET_1_30    => 0,
			self::BUCKET_31_60   => 0,
			self::BUCKET_61_90   => 0,
			self::BUCKET_OVER_90 => 0,
		];

		foreach ( $sales as $sale ) {
			$outstanding = (int) $sale['grand_total'] - (int) $sale['paid_amount'];
			if ( $outstanding <= 0 ) {
				continue;
			}

			$dueDate = ( new \DateTimeImmutable( $sale['sale_date'], new \DateTimeZone( 'Asia/Jakarta' ) ) )
				->modify( '+' . self::DUE_DAYS . ' days' )
				->setTime( 0, 0 );
			$daysOverdue = $this->daysOverdue( $dueDate, $asOfDate );
			$bucket      = $this->bucketForDays( $daysOverdue );
			$totals[ $bucket ] += $outstanding;

			$rows[] = [
				'sale_id'       => (int) $sale['id'],
				'doc_number'    => $sale['doc_number'],
				'sale_date'     => $sale['sale_date'],
				'due_date'      => $dueDate->format( 'Y-m-d' ),
				'customer_id'   => $sale['customer_id'] !== null ? (int) $sale['customer_id'] : null,
				'customer_name' => $sale['customer_name'] ?? 'Umum',
				'grand_total'   => (int) $sale['grand_total'],
				'paid_amount'   => (int) $sale['paid_amount'],
				'outstanding'   => $outstanding,
				'days_overdue'  => $daysOverdue,
				'bucket'        => $bucket,
			];
		}

		$totals['total'] = array_sum( $totals );

		return [
			'as_of'  => $asOfDate->format( 'Y-m-d' ),
			'rows'   => $rows,
			'totals' => $totals,
			'count'  => count( $rows ),
		];
	}

	public static function bucketLabel( string $bucket ): string {
		return match ( $bucket ) {
			self::BUCKET_NOT_DUE => 'Belum Jatuh Tempo',
			self::BUCKET_1_30    => '1–30 hari',
			self::BUCKET_31_60   => '31–60 hari',
			self::BUCKET_61_90   => '61–90 hari',
			self::BUCKET_OVER_90 => '>90 hari',
			default              => $bucket,
		};
	}

	private function bucketForDays( int $daysOverdue ): string {
		if ( $daysOverdue <= 0 ) {
			return self::BUCKET_NOT_DUE;
		}
		if ( $daysOverdue <= 30 ) {
			return self::BUCKET_1_30;
		}
		if ( $daysOverdue <= 60 ) {
			return self::BUCKET_31_60;
		}
		if ( $daysOverdue <= 90 ) {
			return self::BUCKET_61_90;
		}
		return self::BUCKET_OVER_90;
	}

	private function daysOverdue( \DateTimeImmutable $dueDate, \DateTimeImmutable $asOf ): int {
		$diff = $dueDate->diff( $asOf );
		return $diff->invert === 1 ? -$diff->days : $diff->days;
	}

	private function parseAsOf( ?string $asOf ): \DateTimeImmutable {
		if ( $asOf === null ) {
			return ( new \DateTimeImmutable( 'now', new \DateTimeZone( 'Asia/Jakarta' ) ) )->setTime( 0, 0 );
		}
		$parsed = \DateTimeImmutable::createFromFormat( 'Y-m-d', $asOf, new \DateTimeZone( 'Asia/Jakarta' ) );
		if ( $parsed === false || $parsed->format( 'Y-m-d' ) !== $asOf ) {
			throw new \InvalidArgumentException( "Tanggal acuan harus format YYYY-MM-DD, dapat: '{$asOf}'." );
		}
		return $parsed->setTime( 0, 0 );
	}
}