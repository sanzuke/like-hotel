<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Invoice Closing Penjualan</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=30">
<style>
.invoice-box{max-width:900px;margin:40px auto;background:#fff;padding:40px;border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,.08)}
.invoice-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:35px}
.invoice-title{font-size:34px;font-weight:bold;color:#087a35}
.company{line-height:1.8}
.summary{width:100%;border-collapse:collapse;margin-top:20px}
.summary th,.summary td{border:1px solid #ddd;padding:12px}
.summary th{background:#087a35;color:#fff}
.total{font-size:28px;color:#087a35;font-weight:bold}
.note{margin-top:40px;background:#eefcf2;padding:20px;border-radius:10px}
@media print{.no-print{display:none}body{background:#fff}.invoice-box{box-shadow:none;margin:0;max-width:100%}}
</style>
</head>
<body>
<div class="invoice-box">
  <div class="invoice-header">
    <div>
      <div class="invoice-title">LIKE HOTEL</div>
      <div class="company"><b>Sewa &amp; Jual Beli Apartment</b><br>WhatsApp : {{ config('likehotel.admin_wa_display') }}</div>
    </div>
    <div style="text-align:right">
      <h2>INVOICE CLOSING</h2>
      No : <b>{{ $r->number() }}</b><br><br>
      Tanggal : {{ $r->created_at->translatedFormat('d F Y') }}
    </div>
  </div>
  <hr>
  <h2>{{ $r->apartment_name }}</h2>
  <table class="summary">
    <tr><th width="45%">Keterangan</th><th>Nilai</th></tr>
    <tr><td>Nama Penjual</td><td>{{ $r->seller_name }}</td></tr>
    <tr><td>Harga Deal</td><td>{{ rupiah($r->deal_price) }}</td></tr>
    <tr><td>Komisi Like Hotel</td><td>{{ rupiah($r->commission_amount) }}</td></tr>
    <tr><td>Biaya Lain</td><td>{{ rupiah($r->other_cost) }}</td></tr>
    <tr><td><b>Hak Penjual</b></td><td class="total">{{ rupiah($r->seller_net_amount) }}</td></tr>
  </table>
  @if($r->notes)<div class="note"><b>Catatan :</b><br><br>{!! nl2br(e($r->notes)) !!}</div>@endif
  <br><br>
  <table width="100%"><tr>
    <td align="center">Mengetahui,<br><br><br><br>_____________________<br>Penjual</td>
    <td align="center">Like Hotel<br><br><br><br>_____________________<br>Admin</td>
  </tr></table>
  <br>
  <div class="no-print">
    <button class="btn" onclick="window.print()">🖨 Cetak Invoice</button>
    <a class="btn-outline" href="{{ route('admin.sale-commissions') }}">Kembali</a>
  </div>
</div>
</body>
</html>
