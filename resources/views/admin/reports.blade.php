@extends('layouts.panel')
@section('title', 'Laporan Keuangan')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Laporan Keuangan</h1>
<form method="get" class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <div class="row">
    <div><label>Dari Tanggal</label><input type="date" name="from" value="{{ $from }}"></div>
    <div><label>Sampai Tanggal</label><input type="date" name="to" value="{{ $to }}"></div>
  </div>
  <label>Filter berdasarkan</label>
  <select name="basis">
    @foreach($bases as $v => $l)<option value="{{ $v }}" @selected($basis === $v)>{{ $l }}</option>@endforeach
  </select>
  <button class="btn">Filter Laporan</button>
  <a class="btn-outline" href="{{ route('admin.reports.export', ['from' => $from, 'to' => $to, 'basis' => $basis]) }}">Export CSV</a>
</form>

<div class="stats">
  <div class="stat"><p>Total Booking</p><h2>{{ $total }}</h2></div>
  <div class="stat"><p>Booking Lunas</p><h2>{{ $lunas }}</h2></div>
  <div class="stat"><p>Belum Lunas</p><h2>{{ $belum }}</h2></div>
  <div class="stat"><p>Omzet Lunas</p><h2>{{ Fmt::rupiah($omzet) }}</h2></div>
</div>
<br>
<div class="stats">
  <div class="stat"><p>Komisi Like Hotel</p><h2>{{ Fmt::rupiah($komisi) }}</h2></div>
  <div class="stat"><p>Hak Owner</p><h2>{{ Fmt::rupiah($owner) }}</h2></div>
  <div class="stat"><p>Pencairan Owner (tgl. dibayar)</p><h2>{{ Fmt::rupiah($settlementPaid) }}</h2></div>
  <div class="stat"><p>Sisa Hak Owner</p><h2>{{ Fmt::rupiah(max(0, $owner - $settlementPaid)) }}</h2></div>
</div>
<br>

<h2>Detail Booking</h2>
<table class="table">
  <tr><th>Tanggal</th><th>Kode</th><th>Tamu</th><th>Apartment</th><th>Owner</th><th>Total</th><th>Komisi</th><th>Hak Owner</th><th>Status</th></tr>
  @forelse($rows as $r)
    <tr>
      <td>{{ \App\Support\Fmt::dateTime($r->created_at) }}</td>
      <td>{{ $r->booking_code }}</td>
      <td>{{ $r->guest_name }}</td>
      <td>{{ $r->apartment->name }}</td>
      <td>{{ $r->apartment->owner_name }}</td>
      <td>{{ Fmt::rupiah($r->total_price) }}</td>
      <td>{{ Fmt::rupiah($r->commission_amount) }}</td>
      <td>{{ Fmt::rupiah($r->owner_amount) }}</td>
      <td>{{ $r->payment_status }}</td>
    </tr>
  @empty
    <tr><td colspan="9">Tidak ada data pada periode ini.</td></tr>
  @endforelse
</table>
@endsection
