@extends('layouts.panel')
@section('title', 'Laporan Penjualan')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Laporan Penjualan Unit</h1>
<p class="muted">Rekap closing (komisi penjualan) berdasarkan tanggal closing dicatat.</p>

<form method="get" class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
    <div><label>Dari</label><input type="date" name="from" value="{{ $from }}"></div>
    <div><label>Sampai</label><input type="date" name="to" value="{{ $to }}"></div>
    <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="include_cancelled" value="1" @checked($withCancelled) style="width:auto"> Sertakan yang batal</label>
    <button class="btn">Tampilkan</button>
    <a class="btn-outline" href="{{ route('admin.sales-report.export', array_filter(['from' => $from, 'to' => $to, 'include_cancelled' => $withCancelled ? 1 : null])) }}">⬇ Ekspor CSV</a>
  </div>
</form>

<div class="stats">
  <div class="stat"><p>Jumlah Closing</p><h2>{{ $total['count'] }}</h2></div>
  <div class="stat"><p>Total Harga Deal</p><h2>{{ Fmt::rupiah($total['deal']) }}</h2></div>
  <div class="stat"><p>Total Komisi Like Hotel</p><h2>{{ Fmt::rupiah($total['commission']) }}</h2><small>Rata-rata {{ $total['rate'] }}% dari harga deal</small></div>
  <div class="stat"><p>Total Hak Penjual</p><h2>{{ Fmt::rupiah($total['net']) }}</h2><small>Biaya lain: {{ Fmt::rupiah($total['other']) }}</small></div>
</div>
<br>

<h2>Rekap per Bulan</h2>
<table class="table">
  <tr><th>Bulan</th><th>Closing</th><th>Harga Deal</th><th>Komisi</th><th>Biaya Lain</th><th>Hak Penjual</th></tr>
  @forelse($byMonth as $g)
    <tr>
      <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $g['label'])->translatedFormat('F Y') }}</td>
      <td>{{ $g['count'] }}</td><td>{{ Fmt::rupiah($g['deal']) }}</td><td>{{ Fmt::rupiah($g['commission']) }}</td><td>{{ Fmt::rupiah($g['other']) }}</td><td>{{ Fmt::rupiah($g['net']) }}</td>
    </tr>
  @empty
    <tr><td colspan="6">Tidak ada closing pada periode ini.</td></tr>
  @endforelse
  @if($byMonth->count() > 1)
    <tr><th>Total</th><th>{{ $total['count'] }}</th><th>{{ Fmt::rupiah($total['deal']) }}</th><th>{{ Fmt::rupiah($total['commission']) }}</th><th>{{ Fmt::rupiah($total['other']) }}</th><th>{{ Fmt::rupiah($total['net']) }}</th></tr>
  @endif
</table>
<br>

<h2>Rekap per Apartemen</h2>
<table class="table">
  <tr><th>Apartemen</th><th>Closing</th><th>Harga Deal</th><th>Komisi</th><th>Hak Penjual</th></tr>
  @forelse($byApartment as $g)
    <tr><td>{{ $g['label'] }}</td><td>{{ $g['count'] }}</td><td>{{ Fmt::rupiah($g['deal']) }}</td><td>{{ Fmt::rupiah($g['commission']) }}</td><td>{{ Fmt::rupiah($g['net']) }}</td></tr>
  @empty
    <tr><td colspan="5">-</td></tr>
  @endforelse
</table>
<br>

<h2>Detail Closing</h2>
<table class="table">
  <tr><th>No</th><th>Tanggal</th><th>Penjual</th><th>Apartemen</th><th>Harga Deal</th><th>Komisi</th><th>Biaya Lain</th><th>Hak Penjual</th><th>Status</th><th></th></tr>
  @forelse($rows as $c)
    <tr>
      <td>{{ $c->number() }}</td>
      <td>{{ \App\Support\Fmt::date($c->created_at) }}</td>
      <td>{{ $c->seller_name }}</td>
      <td>{{ $c->apartment_name }}</td>
      <td>{{ Fmt::rupiah($c->deal_price) }}</td>
      <td>{{ Fmt::rupiah($c->commission_amount) }}<br><small>{{ $c->commission_type === 'percent' ? (float) $c->commission_value.'%' : 'nominal' }}</small></td>
      <td>{{ Fmt::rupiah($c->other_cost) }}</td>
      <td><b>{{ Fmt::rupiah($c->seller_net_amount) }}</b></td>
      <td>{{ $c->status }}</td>
      <td><a class="btn-outline" target="_blank" href="{{ route('admin.sale-commissions.invoice', $c) }}">Invoice</a></td>
    </tr>
  @empty
    <tr><td colspan="10">Tidak ada data.</td></tr>
  @endforelse
</table>
@endsection
