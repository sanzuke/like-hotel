@extends('layouts.site')
@section('title', 'Notifikasi WhatsApp')

@section('content')
<section class="booking-page">
  <div class="booking-form-card">
    <span class="badge">Booking Berhasil</span>
    <h1>🎉 Booking Berhasil Dibuat</h1>
    <p class="muted">Silakan kirim notifikasi WhatsApp atau lanjut ke pembayaran.</p>

    <div class="facility-box">
      <p><b>Kode Booking</b><br>{{ $booking->booking_code }}</p>
      <p><b>Apartment</b><br>{{ $booking->apartment->name }}</p>
      <p><b>Check In</b><br>{{ $booking->checkin->toDateString() }}</p>
      <p><b>Check Out</b><br>{{ $booking->checkout->toDateString() }}</p>
      <p><b>Status</b><br>{{ $booking->payment_status }}</p>
    </div>
    <br>
    @unless($booking->isPaid())
      <a class="btn btn-full" href="{{ route('payment.show', $booking) }}">💳 Lanjut Pembayaran</a><br><br>
    @endunless
    <a class="btn-outline" href="{{ route('invoice.show', $booking) }}">📄 Lihat Invoice</a>
  </div>

  <div class="booking-side-card">
    <div class="booking-side-body">
      <h2>Total Tagihan</h2>
      <div class="price-big">{{ rupiah($booking->total_price) }}</div>
      <hr>
      <h3>Notifikasi WhatsApp</h3>
      <a class="btn btn-full" target="_blank" rel="noopener" href="{{ $links['admin'] }}">Kirim ke Admin</a><br><br>
      <a class="btn-outline btn-full" target="_blank" rel="noopener" href="{{ $links['owner'] }}">Kirim ke Owner</a><br><br>
      <a class="btn-outline btn-full" target="_blank" rel="noopener" href="{{ $links['guest'] }}">Kirim ke Tamu</a>
    </div>
  </div>
</section>
@endsection
