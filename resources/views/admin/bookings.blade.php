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
<form id="booking-filter" method="get" action="{{ route('admin.bookings.index') }}"></form>
<table class="table">
  <tr><th>Kode</th><th>Tamu</th><th>Apartment</th><th>Periode</th><th>Total</th><th>Komisi</th><th>Hak Owner</th><th>Status</th><th>Aksi</th><th>WhatsApp</th></tr>
  <tr class="filter-row">
    <td><input form="booking-filter" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Kode / tamu / telepon…" aria-label="Filter booking"></td>
    <td></td>
    <td>
      <select form="booking-filter" name="apartment" onchange="this.form.submit()" aria-label="Filter apartment">
        <option value="">Semua</option>
        @foreach($filterApartments as $ap)<option value="{{ $ap->id }}" @selected(($filters['apartment'] ?? '') === (string) $ap->id)>{{ $ap->name }}</option>@endforeach
      </select>
    </td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td>
      <select form="booking-filter" name="payment" onchange="this.form.submit()" aria-label="Filter pembayaran">
        <option value="">Semua pembayaran</option>
        @foreach($paymentOptions as $v)<option value="{{ $v }}" @selected(($filters['payment'] ?? '') === $v)>{{ $v }}</option>@endforeach
      </select>
      <select form="booking-filter" name="booking" onchange="this.form.submit()" aria-label="Filter status booking" style="margin-top:6px">
        <option value="">Semua status booking</option>
        @foreach($bookingOptions as $v)<option value="{{ $v }}" @selected(($filters['booking'] ?? '') === $v)>{{ $v }}</option>@endforeach
      </select>
    </td>
    <td class="filter-actions">
      <button form="booking-filter" class="btn-outline">Cari</button>
      @if(array_filter($filters))<a href="{{ route('admin.bookings.index') }}" class="filter-reset">Reset</a>@endif
    </td>
    <td></td>
  </tr>
  @forelse($bookings as $b)
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
  @empty
    <tr><td colspan="10" style="text-align:center;color:#6b7280">
      @if(array_filter($filters))Tidak ada data yang cocok dengan filter. <a href="{{ route('admin.bookings.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada booking.@endif
    </td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $bookings])
@endsection
