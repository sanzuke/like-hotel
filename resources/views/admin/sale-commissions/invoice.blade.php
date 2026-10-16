<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice Komisi #{{ $r->id }}</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=20">
</head>
<body>
@php use App\Support\Fmt; @endphp
<div class="invoice">
  <div class="invoice-head">
    <div>
      <div class="logo"><span class="logo-box">LH</span><span>LIKE HOTEL<br><small>Sewa Apartment Dekat</small></span></div>
      <p>{{ config('likehotel.domain') }}</p>
    </div>
    <div style="text-align:right">
      <div class="invoice-title">INVOICE KOMISI</div>
      <p><b>KOM-{{ str_pad($r->id, 5, '0', STR_PAD_LEFT) }}</b><br>{{ $r->created_at->format('d/m/Y') }}</p>
      <span class="badge">{{ strtoupper($r->status) }}</span>
    </div>
  </div>

  <p>Penjual: <b>{{ $r->seller_name ?: '-' }}</b><br>Apartment: <b>{{ $r->apartment_name }}</b></p>

  <table class="table">
    <tr><th>Keterangan</th><th style="text-align:right">Jumlah</th></tr>
    <tr><td>Harga Deal</td><td style="text-align:right">{{ Fmt::rupiah($r->deal_price) }}</td></tr>
    <tr><td>Komisi Like Hotel ({{ $r->commission_type === 'percent' ? (float) $r->commission_value.'%' : 'nominal' }})</td><td style="text-align:right">-{{ Fmt::rupiah($r->commission_amount) }}</td></tr>
    <tr><td>Biaya Lain</td><td style="text-align:right">-{{ Fmt::rupiah($r->other_cost) }}</td></tr>
    <tr><td><b>Diterima Penjual</b></td><td style="text-align:right"><b style="color:#087a35">{{ Fmt::rupiah($r->seller_net_amount) }}</b></td></tr>
  </table>

  @if($r->notes)<div class="alert">{{ $r->notes }}</div>@endif

  <div class="no-print" style="display:flex;gap:10px;margin-top:16px">
    <button class="btn-outline" onclick="window.print()">Cetak / Simpan PDF</button>
  </div>
</div>
</body>
</html>
