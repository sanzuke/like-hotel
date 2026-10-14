@extends('layouts.owner')
@section('page', 'Dashboard Owner')

@section('content')
<h1>Dashboard Owner</h1>
<p class="muted">Selamat datang, <b>{{ $owner->name }}</b>. Berikut ringkasan unit dan pendapatan Anda.</p>

<div class="stats">
  <div class="stat"><p>Unit Saya</p><h2>{{ $unit }}</h2></div>
  <div class="stat"><p>Total Booking</p><h2>{{ $booking }}</h2></div>
  <div class="stat"><p>Booking Lunas</p><h2>{{ $paidBooking }}</h2></div>
  <div class="stat"><p>Belum Lunas</p><h2>{{ $pendingBooking }}</h2></div>
</div>
<br>
@include('owner.partials.balance', ['balance' => $balance])
<br>
<div class="alert">Pembayaran tamu masuk ke Like Hotel. Hak owner dihitung otomatis dari transaksi yang sudah lunas.</div>

@if($balance['available'] > 0)
  <a class="btn" href="{{ route('owner.settlement') }}">Ajukan Pencairan Dana</a>
@else
  <a class="btn-outline" href="{{ route('owner.settlement') }}">Lihat Riwayat Pencairan</a>
@endif
<br><br>

<h2>Booking Terbaru</h2>
<table class="table">
  <tr><th>Kode</th><th>Apartment</th><th>Tamu</th><th>Periode</th><th>Total</th><th>Hak Owner</th><th>Status</th><th>Invoice</th></tr>
  @forelse($latestBookings as $b)
    <tr>
      <td><b>{{ $b->booking_code }}</b><br><small>{{ $b->created_at->format('d-m-Y H:i') }}</small></td>
      <td>{{ $b->apartment->name }}</td>
      <td>{{ $b->guest_name }}<br><small>{{ $b->guest_phone }}</small></td>
      <td>{{ $b->checkin->toDateString() }}<br>s/d {{ $b->checkout->toDateString() }}</td>
      <td>{{ rupiah($b->total_price) }}</td>
      <td>{{ rupiah($b->owner_amount) }}</td>
      <td><span style="color:{{ $b->statusColor() }};font-weight:800">{{ $b->payment_status }}</span><br><small>{{ $b->booking_status }}</small></td>
      <td><a class="btn-outline" href="{{ $b->invoiceUrl() }}" target="_blank" rel="noopener">Invoice</a></td>
    </tr>
  @empty
    <tr><td colspan="8">Belum ada booking untuk unit Anda.</td></tr>
  @endforelse
</table>
<br>
<h2>Riwayat Pencairan Terbaru</h2>
@include('owner.partials.settlement-table', ['rows' => $latestSettlements])
@endsection
