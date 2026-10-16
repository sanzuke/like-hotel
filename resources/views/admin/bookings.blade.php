@extends('layouts.panel')
@section('title', 'Booking & Pembayaran')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Booking & Pembayaran</h1>
<div class="stats">
  <div class="stat"><p>Total Booking</p><h2>{{ $stats['total'] }}</h2></div>
  <div class="stat"><p>Booking Lunas</p><h2>{{ $stats['lunas'] }}</h2></div>
  <div class="stat"><p>Belum Lunas</p><h2>{{ $stats['belum'] }}</h2></div>
  <div class="stat"><p>Komisi Like Hotel</p><h2>{{ Fmt::rupiah($stats['komisi']) }}</h2></div>
</div>
<br>
<table class="table">
  <tr><th>Kode</th><th>Tamu</th><th>Apartment</th><th>Periode</th><th>Total</th><th>Komisi</th><th>Hak Owner</th><th>Status</th><th>Aksi</th><th>WhatsApp</th></tr>
  @foreach($bookings as $b)
    @php
      $invoice = route('invoice.show', $b->access_token);
      $adminMsg = "BOOKING LIKE HOTEL\nKode: {$b->booking_code}\nTamu: {$b->guest_name}\nWA: {$b->guest_phone}\nApartment: {$b->apartment->name}\nCheck-in: {$b->checkin->toDateString()}\nCheck-out: {$b->checkout->toDateString()}\nTotal: ".Fmt::rupiah($b->total_price)."\nStatus: {$b->payment_status}";
      $ownerMsg = "BOOKING UNIT ANDA\nKode: {$b->booking_code}\nTamu: {$b->guest_name}\nApartment: {$b->apartment->name}\nCheck-in: {$b->checkin->toDateString()}\nCheck-out: {$b->checkout->toDateString()}\nHak Owner: ".Fmt::rupiah($b->owner_amount);
      $guestMsg = "Halo {$b->guest_name}, booking Like Hotel Anda sudah dibuat.\nKode: {$b->booking_code}\nApartment: {$b->apartment->name}\nTotal: ".Fmt::rupiah($b->total_price)."\nStatus: {$b->payment_status}\nInvoice: {$invoice}";
    @endphp
    <tr>
      <td><b>{{ $b->booking_code }}</b><br><small>{{ $b->invoice_number }}</small></td>
      <td>{{ $b->guest_name }}<br><small>{{ $b->guest_phone }}</small></td>
      <td>{{ $b->apartment->name }}</td>
      <td>{{ $b->checkin->toDateString() }}<br>s/d {{ $b->checkout->toDateString() }}</td>
      <td>{{ Fmt::rupiah($b->total_price) }}</td>
      <td>{{ Fmt::rupiah($b->commission_amount) }}</td>
      <td>{{ Fmt::rupiah($b->owner_amount) }}</td>
      <td><span style="color:{{ Fmt::statusColor($b->payment_status) }};font-weight:800">{{ $b->payment_status }}</span><br><small>{{ $b->booking_status }}</small></td>
      <td>
        <a class="btn-outline" href="{{ $invoice }}" target="_blank">Invoice</a>
        @unless($b->isPaid() || $b->isCancelled())
          <form class="inline-form" method="post" action="{{ route('admin.bookings.paid', $b) }}" onsubmit="return confirm('Tandai booking ini lunas?')">@csrf<button class="btn-outline">Lunas</button></form>
        @endunless
        @unless($b->isPaid() || $b->isCancelled())
          <form class="inline-form" method="post" action="{{ route('admin.bookings.cancel', $b) }}" onsubmit="return confirm('Batalkan booking ini?')">@csrf<button class="btn-outline">Batal</button></form>
        @endunless
      </td>
      <td>
        <a class="btn-outline" target="_blank" href="{{ Fmt::waLink(Fmt::adminWa(), $adminMsg) }}">Admin</a>
        <a class="btn-outline" target="_blank" href="{{ Fmt::waLink($b->owner?->phone ?: Fmt::adminWa(), $ownerMsg) }}">Owner</a>
        <a class="btn-outline" target="_blank" href="{{ Fmt::waLink($b->guest_phone, $guestMsg) }}">Tamu</a>
      </td>
    </tr>
  @endforeach
</table>
@endsection
