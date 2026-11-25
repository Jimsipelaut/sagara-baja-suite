<?php
namespace SagaraBaja\Modules\Sales\Services;

/**
 * PdfInvoiceService
 *
 * Menghasilkan HTML invoice yang siap dicetak atau disimpan ke PDF via browser print.
 * Tidak menggunakan library eksternal (Rp0 constraint).
 *
 * Menghasilkan dua template berbeda:
 *   - 'resmi'     : invoice formal dengan nomor INV-xxx, tanda tangan, peruntukan legal.
 *   - 'non_resmi' : nota/struk ringkas dengan nomor NTA-xxx.
 *
 * Output: string HTML UTF-8. Caller bertanggung jawab echo + wp_die() atau stream ke browser.
 */
class PdfInvoiceService {

	/**
	 * Render HTML invoice.
	 *
	 * @param array $sale      Baris dari sb_sales.
	 * @param array $items     Baris dari sb_sale_items (dengan nama produk & satuan bila tersedia).
	 * @param array $payments  Baris dari sb_payments.
	 * @param array $store     Profil toko dari sb_settings (nama, alamat, telepon).
	 * @param array|null $customer Baris dari sb_customers (null = umum / tanpa nama).
	 * @return string HTML siap tampil.
	 */
	public function render( array $sale, array $items, array $payments, array $store, ?array $customer = null ): string {
		$is_resmi = $sale['invoice_type'] === 'resmi';
		$title    = $is_resmi ? 'INVOICE' : 'NOTA';
		$doc_no   = esc_html( $sale['doc_number'] );
		$date     = esc_html( date( 'd/m/Y', strtotime( $sale['sale_date'] ) ) );

		$store_name    = esc_html( $store['store_name']    ?? 'Sagara Baja' );
		$store_address = esc_html( $store['store_address'] ?? '' );
		$store_phone   = esc_html( $store['store_phone']   ?? '' );

		$cust_name = $customer ? esc_html( $customer['name'] ) : 'Umum';
		$cust_addr = $customer ? esc_html( $customer['address'] ?? '' ) : '';

		// ── Baris Barang ───────────────────────────────────────────────────
		$rows_html = '';
		$no = 1;
		foreach ( $items as $item ) {
			$product_name = esc_html( $item['product_name'] ?? "Produk #{$item['product_id']}" );
			$unit_name    = esc_html( $item['unit_name']    ?? "Sat.#{$item['unit_id']}" );
			$qty          = number_format( (float) $item['qty'], 2 );
			$price        = 'Rp ' . number_format( (int) $item['unit_price'], 0, ',', '.' );
			$disc         = (int) $item['discount_amount'];
			$total        = 'Rp ' . number_format( (int) $item['line_total'], 0, ',', '.' );

			$disc_cell = $disc > 0
				? 'Rp ' . number_format( $disc, 0, ',', '.' )
				: '-';

			$rows_html .= "
			<tr>
				<td>{$no}</td>
				<td>{$product_name}</td>
				<td>{$qty} {$unit_name}</td>
				<td style='text-align:right'>{$price}</td>
				<td style='text-align:right'>{$disc_cell}</td>
				<td style='text-align:right'>{$total}</td>
			</tr>";
			$no++;
		}

		// ── Pembayaran ─────────────────────────────────────────────────────
		$payments_html = '';
		foreach ( $payments as $p ) {
			$pdate  = esc_html( date( 'd/m/Y H:i', strtotime( $p['payment_date'] ) ) );
			$method = esc_html( strtoupper( $p['method'] ) );
			$amt    = 'Rp ' . number_format( (int) $p['amount'], 0, ',', '.' );
			$payments_html .= "<tr><td>{$pdate}</td><td>{$method}</td><td style='text-align:right'>{$amt}</td></tr>";
		}

		// ── Angka Ringkasan ─────────────────────────────────────────────────
		$subtotal    = 'Rp ' . number_format( (int) $sale['subtotal'],        0, ',', '.' );
		$discount    = 'Rp ' . number_format( (int) $sale['discount_amount'], 0, ',', '.' );
		$grand_total = 'Rp ' . number_format( (int) $sale['grand_total'],     0, ',', '.' );
		$paid        = 'Rp ' . number_format( (int) $sale['paid_amount'],     0, ',', '.' );
		$sisa        = 'Rp ' . number_format( max( 0, (int) $sale['grand_total'] - (int) $sale['paid_amount'] ), 0, ',', '.' );
		$status_lbl  = esc_html( strtoupper( $sale['status'] ) );

		// ── Blok Tanda Tangan (hanya invoice resmi) ─────────────────────────
		$ttd_block = '';
		if ( $is_resmi ) {
			$ttd_block = "
			<div style='display:flex;justify-content:space-between;margin-top:40px'>
				<div style='text-align:center;width:200px'>
					<p style='margin:0'>Penerima,</p>
					<div style='height:60px'></div>
					<p style='margin:0;border-top:1px solid #000'>(____________________)</p>
				</div>
				<div style='text-align:center;width:200px'>
					<p style='margin:0'>{$store_name},</p>
					<div style='height:60px'></div>
					<p style='margin:0;border-top:1px solid #000'>(____________________)</p>
				</div>
			</div>";
		}

		// ── Render HTML ─────────────────────────────────────────────────────
		return "<!DOCTYPE html>
<html lang='id'>
<head>
<meta charset='UTF-8'>
<title>{$title} {$doc_no}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 30px; }
  h1   { font-size: 20px; margin: 0; }
  h2   { font-size: 14px; margin: 0 0 4px; }
  table { width: 100%; border-collapse: collapse; margin-top: 12px; }
  th, td { padding: 6px 8px; border: 1px solid #ccc; vertical-align: top; }
  th { background: #f4f4f4; }
  .summary td { border: none; }
  .label { font-weight: bold; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 4px;
           background: #e0f0e0; color: #165016; font-size: 11px; }
  @media print { body { margin: 10px; } button { display: none; } }
</style>
</head>
<body>
<button onclick='window.print()' style='margin-bottom:12px;padding:6px 16px;cursor:pointer'>🖨 Cetak</button>

<div style='display:flex;justify-content:space-between;align-items:flex-start'>
  <div>
    <h1>{$store_name}</h1>
    <p style='margin:4px 0'>{$store_address}</p>
    <p style='margin:4px 0'>Telp: {$store_phone}</p>
  </div>
  <div style='text-align:right'>
    <h2>{$title}</h2>
    <p style='margin:2px 0'><strong>{$doc_no}</strong></p>
    <p style='margin:2px 0'>Tanggal: {$date}</p>
    <span class='badge'>{$status_lbl}</span>
  </div>
</div>

<hr>
<p><strong>Pelanggan:</strong> {$cust_name}</p>
" . ( $cust_addr ? "<p style='margin-top:-8px'>{$cust_addr}</p>" : '' ) . "

<table>
  <thead>
    <tr>
      <th style='width:30px'>No</th>
      <th>Nama Barang</th>
      <th>Qty</th>
      <th>Harga Satuan</th>
      <th>Diskon</th>
      <th>Total</th>
    </tr>
  </thead>
  <tbody>
    {$rows_html}
  </tbody>
</table>

<table class='summary' style='width:300px;margin-left:auto;margin-top:8px'>
  <tr><td class='label'>Subtotal</td><td style='text-align:right'>{$subtotal}</td></tr>
  <tr><td class='label'>Diskon Dokumen</td><td style='text-align:right'>- {$discount}</td></tr>
  <tr><td class='label' style='font-size:14px'>Total</td><td style='text-align:right;font-size:14px'><strong>{$grand_total}</strong></td></tr>
  <tr><td class='label'>Dibayar</td><td style='text-align:right'>{$paid}</td></tr>
  <tr><td class='label'>Sisa</td><td style='text-align:right'>{$sisa}</td></tr>
</table>

" . ( $payments_html ? "
<h3 style='margin-top:20px'>Riwayat Pembayaran</h3>
<table>
  <thead><tr><th>Tanggal</th><th>Metode</th><th>Jumlah</th></tr></thead>
  <tbody>{$payments_html}</tbody>
</table>" : '' ) . "

{$ttd_block}
</body>
</html>";
	}
}
