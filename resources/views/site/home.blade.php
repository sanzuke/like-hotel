@extends('layouts.public')
@section('content')
@php $wa = 'https://wa.me/'.config('likehotel.admin_wa'); @endphp
<section class="hero hero-modern">
  <div class="hero-text">
    <span class="badge"><i class="ms" style="font-size:15px">verified</i> Marketplace Sewa &amp; Jual Apartemen</span>
    <h1>Temukan Apartemen <em>Dekat Kampus &amp; Kantor</em></h1>
    <p class="lead">Harian, mingguan, dan bulanan. Booking mudah, invoice otomatis, dan pembayaran aman tanpa biaya tersembunyi.</p>
    @include('site._search')
    <div class="hero-points">
      <span><i class="ms">lock</i> Pembayaran Aman</span>
      <span><i class="ms">receipt_long</i> Invoice Otomatis</span>
      <span><i class="ms">verified_user</i> Owner Terverifikasi</span>
    </div>
  </div>
  <div class="hero-card">
    <span class="badge"><i class="ms" style="font-size:15px">support_agent</i> CS Siap Membantu</span>
    <h2>Butuh Bantuan Booking?</h2>
    <p>Kami kirimkan rekomendasi unit terdekat sesuai kebutuhan Anda.</p>
    <p style="font-size:26px!important;font-weight:800;color:#fff!important;margin:0 0 16px!important">{{ \App\Support\Fmt::adminWaDisplay() }}</p>
    <a class="btn" href="{{ $wa }}" target="_blank" rel="noopener"><i class="ms">chat</i> Chat WhatsApp</a>
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
  <p style="text-align:center;margin-top:24px"><a class="btn-outline" href="{{ route('apartments.index') }}">Lihat Semua Apartment</a></p>
</section>

<section class="features">
  <div><span class="ico"><i class="ms">bolt</i></span><h3>Booking Mudah</h3><p>Pilih unit, isi data, dan invoice langsung dibuat otomatis.</p></div>
  <div><span class="ico"><i class="ms">payments</i></span><h3>Pembayaran Online</h3><p>Terhubung dengan Midtrans untuk pembayaran digital.</p></div>
  <div><span class="ico"><i class="ms">apartment</i></span><h3>Multi Owner</h3><p>Owner apartment bisa mendaftarkan unit sendiri.</p></div>
  <div><span class="ico"><i class="ms">account_balance_wallet</i></span><h3>Settlement Owner</h3><p>Hak owner dan komisi Like Hotel dihitung otomatis.</p></div>
</section>

<section class="section">
  <h2>3 Langkah Mudah Booking</h2>
  <p class="section-subtitle">Dari pilih unit sampai dapat invoice, tidak sampai 5 menit.</p>
  <div class="steps">
    <div class="step"><b class="n">1</b><div><h4>Pilih Unit &amp; Tanggal</h4><p>Tentukan unit dan tanggal sesuai kebutuhan harian atau bulanan.</p></div></div>
    <div class="step"><b class="n">2</b><div><h4>Bayar via QRIS / VA</h4><p>Pembayaran digital dikonfirmasi otomatis oleh sistem.</p></div></div>
    <div class="step"><b class="n">3</b><div><h4>Terima Invoice</h4><p>Invoice resmi langsung tersedia dan bisa dicek kapan saja.</p></div></div>
  </div>
</section>

<section class="section" style="padding-top:8px">
  <div class="owner-banner">
    <div>
      <span class="tagline">Untuk Pemilik Unit</span>
      <h3>Punya apartemen menganggur? Titipkan ke Like Hotel.</h3>
      <p>Kami bantu pemasaran, booking, hingga settlement yang transparan. Bisa untuk disewakan maupun dijual.</p>
    </div>
    <div class="acts">
      <a class="w" href="{{ route('sale-request.create') }}"><i class="ms">add_business</i> Titip Jual</a>
      <a class="g" href="{{ $wa }}" target="_blank" rel="noopener"><i class="ms">support</i> Konsultasi</a>
    </div>
  </div>
</section>
@endsection
