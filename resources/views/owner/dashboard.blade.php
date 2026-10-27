@extends('layouts.panel')
@section('title', 'Dashboard Owner')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Dashboard Owner</h1>
<p class="muted">Selamat datang, <b>{{ $owner->name }}</b>. Berikut ringkasan unit dan pendapatan Anda.</p>

<div class="stats">
  <div class="stat"><p>Unit Saya</p><h2>{{ $units }}</h2></div>
  <div class="stat"><p>Total Booking</p><h2>{{ $bookingTotal }}</h2></div>
  <div class="stat"><p>Booking Lunas</p><h2>{{ $bookingPaid }}</h2></div>
  <div class="stat"><p>Belum Lunas</p><h2>{{ $bookingNew }}</h2></div>
</div>
<br>
<div class="stats">
  <div class="stat"><p>Total Hak Owner</p><h2>{{ Fmt::rupiah($earnings) }}</h2></div>
  <div class="stat"><p>Proses/Sudah Cair</p><h2>{{ Fmt::rupiah($requested) }}</h2></div>
  <div class="stat"><p>Sudah Dibayar</p><h2>{{ Fmt::rupiah($paid) }}</h2></div>
  <div class="stat"><p>Saldo Tersedia</p><h2>{{ Fmt::rupiah($available) }}</h2></div>
</div>
<br>
<div class="alert">Pembayaran tamu masuk ke Like Hotel. Hak owner dihitung otomatis dari transaksi yang sudah lunas.</div>
@if($available > 0)
  <a class="btn" href="{{ route('owner.settlements.index') }}">Ajukan Pencairan Dana</a>
@else
  <a class="btn-outline" href="{{ route('owner.settlements.index') }}">Lihat Riwayat Pencairan</a>
@endif
<br><br>

<h2>Booking Terbaru</h2>
<table class="table">
  <tr><th>Kode</th><th>Apartment</th><th>Tamu</th><th>Periode</th><th>Total</th><th>Hak Owner</th><th>Status</th></tr>
  @forelse($latestBookings as $b)
    <tr>
      <td><b>{{ $b->booking_code }}</b><br><small>{{ \App\Support\Fmt::dateTime($b->created_at) }}</small></td>
      <td>{{ $b->apartment->name }}</td>
      <td>{{ $b->guest_name }}<br><small>{{ $b->guest_phone }}</small></td>
      <td>{{ $b->checkin_label }}<br>s/d {{ $b->checkout_label }}</td>
      <td>{{ Fmt::rupiah($b->total_price) }}</td>
      <td>{{ Fmt::rupiah($b->owner_amount) }}</td>
      <td><span style="color:{{ Fmt::statusColor($b->payment_status) }};font-weight:800">{{ $b->payment_status }}</span><br><small>{{ $b->booking_status }}</small></td>
    </tr>
  @empty
    <tr><td colspan="7">Belum ada booking untuk unit Anda.</td></tr>
  @endforelse
</table>
<br>
<h2>Riwayat Pencairan Terbaru</h2>
@include('owner._settlement-table', ['settlements' => $latestSettlements])
@endsection
