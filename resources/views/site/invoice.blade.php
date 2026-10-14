@extends('layouts.site')
@section('title', 'Invoice '.$booking->invoice_number)

@section('content')
@php($apt = $booking->apartment)
<div class="invoice">

  <div class="invoice-head">
    <div>
      <div class="logo">
        <span class="logo-box">LH</span>
        <span>LIKE HOTEL<br><small>Sewa Apartment Dekat</small></span>
      </div>
      <p>{{ config('likehotel.site_domain') }}<br>WhatsApp: {{ config('likehotel.admin_wa_display') }}</p>
    </div>

    <div style="text-align:right">
      <div class="invoice-title">INVOICE</div>
      <p><b>{{ $booking->invoice_number }}</b><br>{{ $booking->created_at->format('d/m/Y') }}</p>
      <span class="badge">{{ strtoupper($booking->payment_status) }}</span>
    </div>
  </div>

  @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif

  <div class="alert">
    @if($booking->isPaid())
      Pembayaran diterima. Terima kasih telah memesan di Like Hotel.
    @elseif($booking->booking_status === 'batal')
      Booking ini telah dibatalkan / kedaluwarsa.
    @else
      Booking berhasil dibuat. Silakan lanjutkan pembayaran sesuai total tagihan.
    @endif
  </div>

  <div class="row">
    <div>
      <h3>Data Tamu</h3>
      <p>Nama: <b>{{ $booking->guest_name }}</b><br>
         WhatsApp: {{ $booking->guest_phone }}<br>
         Email: {{ $booking->guest_email }}</p>
    </div>
    <div>
      <h3>Data Booking</h3>
      <p>Kode Booking: <b>{{ $booking->booking_code }}</b><br>
         Check-in: {{ $booking->checkin->toDateString() }}<br>
         Check-out: {{ $booking->checkout->toDateString() }}<br>
         Jumlah malam: {{ $booking->nights() }} malam</p>
    </div>
  </div>

  <table class="table">
    <tr><th>Apartment</th><th>Lokasi</th><th>Tipe</th><th>Harga/Malam</th><th>Malam</th><th>Total</th></tr>
    <tr>
      <td>{{ $apt->name }}</td>
      <td>{{ $apt->location }}</td>
      <td>{{ $apt->type }}</td>
      <td>{{ rupiah($booking->subtotal() / $booking->nights()) }}</td>
      <td>{{ $booking->nights() }}</td>
      <td><b>{{ rupiah($booking->subtotal()) }}</b></td>
    </tr>
    @if($booking->discount_amount > 0)
      <tr>
        <td colspan="5" style="text-align:right">Diskon Promo {{ $booking->promo_code }}</td>
        <td><b style="color:#991b1b">-{{ rupiah($booking->discount_amount) }}</b></td>
      </tr>
    @endif
    <tr>
      <td colspan="5" style="text-align:right"><b>Total Bayar</b></td>
      <td><b style="color:#087a35">{{ rupiah($booking->total_price) }}</b></td>
    </tr>
  </table>

  <br>
  <div style="text-align:right">
    <p>Status Pembayaran</p>
    <h2>{{ strtoupper($booking->payment_status) }}</h2>
    <h1 style="color:#087a35">Total: {{ rupiah($booking->total_price) }}</h1>
  </div>

  <div class="alert">Invoice ini dibuat otomatis oleh sistem Like Hotel.</div>

  <div class="no-print" style="display:flex;gap:10px;flex-wrap:wrap">
    @unless($booking->isPaid() || $booking->booking_status === 'batal')
      <a class="btn" href="{{ route('payment.show', $booking) }}">Bayar Sekarang</a>
    @endunless
    <button class="btn-outline" onclick="window.print()">Cetak / Simpan PDF</button>
    <a class="btn-outline" href="{{ route('booking.notify', $booking) }}">Kirim Notifikasi WhatsApp</a>
    <a class="btn-outline" href="{{ route('booking.lookup') }}">Cek Booking</a>
  </div>
</div>
@endsection
