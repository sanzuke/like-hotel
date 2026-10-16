@extends('layouts.public')
@section('title', 'Booking Berhasil')
@php
  use App\Support\Fmt;
  $detail = "Kode: {$b->booking_code}\nApartment: {$b->apartment->name}\nCheck-in: {$b->checkin->toDateString()}\nCheck-out: {$b->checkout->toDateString()}\nTotal: ".Fmt::rupiah($b->total_price);
  $adminMsg = "BOOKING BARU LIKE HOTEL\n{$detail}\nNama Tamu: {$b->guest_name}\nWA Tamu: {$b->guest_phone}\nLokasi: {$b->apartment->location}\nStatus: {$b->payment_status}";
  $ownerMsg = "BOOKING BARU UNIT ANDA\n{$detail}\nNama Tamu: {$b->guest_name}\nWA Tamu: {$b->guest_phone}\nHak Owner: ".Fmt::rupiah($b->owner_amount);
  $guestMsg = "Halo {$b->guest_name}, booking Anda di Like Hotel telah dibuat.\n{$detail}\nInvoice: ".route('invoice.show', $b->access_token)."\nSilakan lanjutkan pembayaran atau hubungi admin Like Hotel.";
@endphp
@section('content')
<section class="booking-page">
  <div class="booking-form-card">
    <span class="badge">Booking Berhasil</span>
    <h1>🎉 Booking Berhasil Dibuat</h1>
    <p class="muted">Simpan tautan halaman ini. Silakan kirim notifikasi WhatsApp atau lanjut ke pembayaran.</p>

    <div class="facility-box">
      <p><b>Kode Booking</b><br>{{ $b->booking_code }}</p>
      <p><b>Apartment</b><br>{{ $b->apartment->name }}</p>
      <p><b>Check In</b><br>{{ $b->checkin->toDateString() }}</p>
      <p><b>Check Out</b><br>{{ $b->checkout->toDateString() }}</p>
      <p><b>Status</b><br>{{ $b->payment_status }}</p>
    </div>
    <br>
    <a class="btn btn-full" href="{{ route('payment.show', $b->access_token) }}">💳 Lanjut Pembayaran</a>
    <br><br>
    <a class="btn-outline" href="{{ route('invoice.show', $b->access_token) }}">📄 Lihat Invoice</a>
  </div>

  <div class="booking-side-card">
    <div class="booking-side-body">
      <h2>Total Tagihan</h2>
      <div class="price-big">{{ Fmt::rupiah($b->total_price) }}</div>
      <hr>
      <h3>Notifikasi WhatsApp</h3>
      <a class="btn btn-full" target="_blank" href="{{ Fmt::waLink(Fmt::adminWa(), $adminMsg) }}">Kirim ke Admin</a>
      <br><br>
      <a class="btn-outline btn-full" target="_blank" href="{{ Fmt::waLink($b->owner?->phone ?: Fmt::adminWa(), $ownerMsg) }}">Kirim ke Owner</a>
      <br><br>
      <a class="btn-outline btn-full" target="_blank" href="{{ Fmt::waLink($b->guest_phone, $guestMsg) }}">Kirim ke Tamu</a>
    </div>
  </div>
</section>
@endsection
