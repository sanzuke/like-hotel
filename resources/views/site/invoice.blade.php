@extends('layouts.public')
@section('robots', 'noindex,nofollow')
@section('title', 'Invoice '.$b->invoice_number)
@php
  use App\Support\Fmt;
  $nights = $b->nights();
  $discount = (float) $b->discount_amount;
  $total = (float) $b->total_price;
  $subtotal = $total + $discount;
@endphp
@section('content')
<div class="invoice">
  @if(session('success'))<div class="alert no-print">{{ session('success') }}</div>@endif

  <div class="invoice-head">
    <div>
      <div class="logo"><span class="logo-box">LH</span><span>LIKE HOTEL<br><small>Sewa Apartment Dekat</small></span></div>
      <p>{{ config('likehotel.domain') }}<br>WhatsApp: {{ Fmt::adminWaDisplay() }}</p>
    </div>
    <div style="text-align:right">
      <div class="invoice-title">INVOICE</div>
      <p><b>{{ $b->invoice_number }}</b><br>{{ \App\Support\Fmt::date($b->created_at) }}</p>
      <span class="badge">{{ strtoupper($b->payment_status) }}</span>
    </div>
  </div>

  @if(! $b->isPaid() && ! $b->isCancelled())
    <div class="alert">Booking berhasil dibuat. Silakan lanjutkan pembayaran sesuai total tagihan.
      @if($b->expires_at) Batas pembayaran: <b>{{ \App\Support\Fmt::dateTime($b->expires_at) }}</b>. @endif
    </div>
  @endif

  <div class="row">
    <div>
      <h3>Data Tamu</h3>
      <dl class="inv-dl">
        <div><dt>Nama</dt><dd><b>{{ $b->guest_name }}</b></dd></div>
        <div><dt>WhatsApp</dt><dd>{{ $b->guest_phone }}</dd></div>
        <div><dt>Email</dt><dd>{{ $b->guest_email }}</dd></div>
      </dl>
    </div>
    <div>
      <h3>Data Booking</h3>
      <dl class="inv-dl">
        <div><dt>Kode Booking</dt><dd><b>{{ $b->booking_code }}</b></dd></div>
        <div><dt>Check-in</dt><dd>{{ $b->checkin_label }}</dd></div>
        <div><dt>Check-out</dt><dd>{{ $b->checkout_label }}</dd></div>
        <div><dt>Jumlah malam</dt><dd>{{ $nights }} malam</dd></div>
      </dl>
    </div>
  </div>

  <table class="table">
    <tr><th>Apartment</th><th>Lokasi</th><th>Tipe</th><th>Harga/Malam</th><th>Malam</th><th>Total</th></tr>
    <tr>
      <td>{{ $b->apartment->name }}</td>
      <td>{{ $b->apartment->location }}</td>
      <td>{{ $b->apartment->type }}</td>
      <td>{{ Fmt::rupiah($subtotal / $nights) }}</td>
      <td>{{ $nights }}</td>
      <td><b>{{ Fmt::rupiah($subtotal) }}</b></td>
    </tr>
    @if($discount > 0)
      <tr>
        <td colspan="5" style="text-align:right">Diskon Promo {{ $b->promo_code }}</td>
        <td><b style="color:#991b1b">-{{ Fmt::rupiah($discount) }}</b></td>
      </tr>
    @endif
    <tr>
      <td colspan="5" style="text-align:right"><b>Total Bayar</b></td>
      <td><b style="color:#087a35">{{ Fmt::rupiah($total) }}</b></td>
    </tr>
  </table>
  <br>
  <div style="text-align:right">
    <p>Status Pembayaran</p>
    <h2>{{ strtoupper($b->payment_status) }}</h2>
    <h1 style="color:#087a35">Total: {{ Fmt::rupiah($total) }}</h1>
  </div>

  <div class="alert">Invoice ini dibuat otomatis oleh sistem Like Hotel.</div>

  <div class="no-print" style="display:flex;gap:10px;flex-wrap:wrap">
    @if(! $b->isPaid() && ! $b->isCancelled())
      <a class="btn" href="{{ route('payment.show', $b->access_token) }}">Bayar Sekarang</a>
    @endif
    <button class="btn-outline" onclick="window.print()">Cetak / Simpan PDF</button>
    <a class="btn-outline" href="{{ route('booking.check') }}">Cek Booking</a>
  </div>
</div>
@endsection
