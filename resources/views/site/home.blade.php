@extends('layouts.public')
@section('content')
<section class="hero hero-modern">
  <div class="hero-text">
    <span class="badge">Marketplace Sewa Apartment</span>
    <h1>Temukan Apartment Harian, Mingguan & Bulanan</h1>
    <p>Sewa apartment dekat kampus, kantor, mall, dan pusat kota. Booking mudah, invoice otomatis, dan pembayaran aman.</p>
    @include('site._search')
    <div class="hero-points">
      <span>✅ Pembayaran Aman</span>
      <span>✅ Invoice Otomatis</span>
      <span>✅ Owner Terverifikasi</span>
    </div>
  </div>
  <div class="hero-card">
    <span class="badge">Hubungi Admin</span>
    <h2>Butuh Bantuan Booking?</h2>
    <p style="font-size:28px;font-weight:900;color:#087a35">{{ \App\Support\Fmt::adminWaDisplay() }}</p>
    <a class="btn" href="https://wa.me/{{ config('likehotel.admin_wa') }}">Chat WhatsApp</a>
  </div>
</section>

<section class="section">
  <h2>Rekomendasi Apartment</h2>
  <p class="section-subtitle">Pilihan unit apartment aktif yang siap disewa.</p>
  <div class="grid">
    @foreach($apartments as $a)
      @include('site._apartment-card', ['a' => $a])
    @endforeach
  </div>
</section>

<section class="features">
  <div><h3>Booking Mudah</h3><p>Pilih unit, isi data, dan invoice langsung dibuat otomatis.</p></div>
  <div><h3>Pembayaran Online</h3><p>Terhubung dengan Midtrans untuk pembayaran digital.</p></div>
  <div><h3>Multi Owner</h3><p>Owner apartment bisa mendaftarkan unit sendiri.</p></div>
  <div><h3>Settlement Owner</h3><p>Hak owner dan komisi Like Hotel dihitung otomatis.</p></div>
</section>
@endsection
