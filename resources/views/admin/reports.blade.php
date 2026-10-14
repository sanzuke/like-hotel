@extends('layouts.admin')
@section('page', 'Laporan Keuangan')

@section('content')
<h1>Laporan Keuangan</h1>

<form method="get" class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
    <div><label>Dari</label><input type="date" name="from" value="{{ $from }}"></div>
    <div><label>Sampai</label><input type="date" name="to" value="{{ $to }}"></div>
    <div><label>Berdasarkan</label>
      <select name="basis">
        <option value="created_at" @selected($basis === 'created_at')>Tanggal booking dibuat</option>
        <option value="checkin" @selected($basis === 'checkin')>Tanggal check-in</option>
      </select></div>
    <button class="btn">Tampilkan</button>
    <a class="btn-outline" href="{{ route('admin.reports.export', ['from' => $from, 'to' => $to, 'basis' => $basis]) }}">⬇ Ekspor CSV</a>
  </div>
</form>

<div class="stats">
  <div class="stat"><p>Total Booking</p><h2>{{ $totalBooking }}</h2></div>
  <div class="stat"><p>Booking Lunas</p><h2>{{ $totalLunas }}</h2></div>
  <div class="stat"><p>Belum Lunas</p><h2>{{ $totalPending }}</h2></div>
  <div class="stat"><p>Malam Terjual</p><h2>{{ $totalMalam }}</h2></div>
</div>
<br>
<div class="stats">
  <div class="stat"><p>Omzet Lunas</p><h2>{{ rupiah($totalOmzet) }}</h2></div>
  <div class="stat"><p>Komisi Like Hotel</p><h2>{{ rupiah($totalKomisi) }}</h2></div>
  <div class="stat"><p>Hak Owner</p><h2>{{ rupiah($totalOwner) }}</h2></div>
  <div class="stat"><p>Pencairan Dibayar (tgl bayar)</p><h2>{{ rupiah($settlementPaid) }}</h2></div>
</div>
<br>

<table class="table">
  <tr><th>Tanggal</th><th>Kode</th><th>Tamu</th><th>Apartment</th><th>Periode</th><th>Total</th><th>Komisi</th><th>Hak Owner</th><th>Status</th></tr>
  @forelse($rows as $b)
    <tr>
      <td>{{ $b->created_at->format('d-m-Y H:i') }}</td><td>{{ $b->booking_code }}</td><td>{{ $b->guest_name }}</td>
      <td>{{ $b->apartment->name }}</td><td>{{ $b->checkin->toDateString() }} s/d {{ $b->checkout->toDateString() }}</td>
      <td>{{ rupiah($b->total_price) }}</td><td>{{ rupiah($b->commission_amount) }}</td><td>{{ rupiah($b->owner_amount) }}</td>
      <td><span style="color:{{ $b->statusColor() }};font-weight:800">{{ $b->payment_status }}</span></td>
    </tr>
  @empty
    <tr><td colspan="9">Tidak ada data pada periode ini.</td></tr>
  @endforelse
</table>
@endsection
