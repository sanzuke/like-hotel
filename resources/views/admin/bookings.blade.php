@extends('layouts.admin')
@section('page', 'Booking & Pembayaran')

@section('content')
<h1>Booking &amp; Pembayaran</h1>

<div class="stats">
  <div class="stat"><p>Total Booking</p><h2>{{ $bookings->count() }}</h2></div>
  <div class="stat"><p>Booking Lunas</p><h2>{{ $totalLunas }}</h2></div>
  <div class="stat"><p>Belum Lunas</p><h2>{{ $totalPending }}</h2></div>
  <div class="stat"><p>Komisi Like Hotel</p><h2>{{ rupiah($totalKomisi) }}</h2></div>
</div>
<br>

<table class="table">
  <tr><th>Kode</th><th>Tamu</th><th>Apartment</th><th>Periode</th><th>Total</th><th>Komisi</th><th>Hak Owner</th><th>Status</th><th>Aksi</th><th>WhatsApp</th></tr>
  @foreach($bookings as $b)
    @php
      $adminMsg = "BOOKING LIKE HOTEL\nKode: {$b->booking_code}\nTamu: {$b->guest_name}\nWA: {$b->guest_phone}\nApartment: {$b->apartment->name}\nCheck-in: {$b->checkin->toDateString()}\nCheck-out: {$b->checkout->toDateString()}\nTotal: ".rupiah($b->total_price)."\nStatus: {$b->payment_status}";
      $ownerMsg = "BOOKING UNIT ANDA\nKode: {$b->booking_code}\nTamu: {$b->guest_name}\nApartment: {$b->apartment->name}\nCheck-in: {$b->checkin->toDateString()}\nCheck-out: {$b->checkout->toDateString()}\nHak Owner: ".rupiah($b->owner_amount);
      $guestMsg = "Halo {$b->guest_name}, booking Like Hotel Anda sudah dibuat.\nKode: {$b->booking_code}\nApartment: {$b->apartment->name}\nTotal: ".rupiah($b->total_price)."\nStatus: {$b->payment_status}\nInvoice: ".($b->access_token ? $b->invoiceUrl() : '-');
    @endphp
    <tr>
      <td><b>{{ $b->booking_code }}</b><br><small>{{ $b->invoice_number }}</small></td>
      <td>{{ $b->guest_name }}<br><small>{{ $b->guest_phone }}</small></td>
      <td>{{ $b->apartment->name }}</td>
      <td>{{ $b->checkin->toDateString() }}<br>s/d {{ $b->checkout->toDateString() }}</td>
      <td>{{ rupiah($b->total_price) }}</td>
      <td>{{ rupiah($b->commission_amount) }}</td>
      <td>{{ rupiah($b->owner_amount) }}</td>
      <td><span style="color:{{ $b->statusColor() }};font-weight:800">{{ $b->payment_status }}</span><br><small>{{ $b->booking_status }}</small></td>
      <td>
        <div class="actions">
          @if($b->access_token)<a class="btn-outline" target="_blank" rel="noopener" href="{{ $b->invoiceUrl() }}">Invoice</a>@endif
          @unless($b->isPaid())
            <form class="inline" method="post" action="{{ route('admin.bookings.paid', $b) }}" onsubmit="return confirm('Tandai booking ini lunas?')">@csrf<button class="btn-outline">Lunas</button></form>
          @endunless
          @if($b->booking_status !== 'batal')
            <form class="inline" method="post" action="{{ route('admin.bookings.cancel', $b) }}" onsubmit="return confirm('Batalkan booking ini?')">@csrf<button class="btn-outline">Batal</button></form>
          @endif
        </div>
      </td>
      <td>
        <div class="actions">
          <a class="btn-outline" target="_blank" rel="noopener" href="{{ wa_link(config('likehotel.admin_wa'), $adminMsg) }}">Admin</a>
          <a class="btn-outline" target="_blank" rel="noopener" href="{{ wa_link($b->owner?->phone, $ownerMsg) }}">Owner</a>
          <a class="btn-outline" target="_blank" rel="noopener" href="{{ wa_link($b->guest_phone, $guestMsg) }}">Tamu</a>
        </div>
      </td>
    </tr>
  @endforeach
</table>

<div class="alert">Admin dapat memverifikasi pembayaran manual, membatalkan booking, melihat invoice, dan mengirim WhatsApp ke Admin, Owner, serta Tamu.</div>
@endsection
