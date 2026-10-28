@extends('layouts.public')
@section('title', 'Sewa Apartemen Harian & Bulanan Dekat Kampus dan Kantor | Like Hotel')
@section('meta_description', 'Sewa apartemen harian, mingguan, bulanan dekat kampus & kantor di Like Hotel. Unit dari owner terverifikasi, bayar aman QRIS/Virtual Account, invoice otomatis. Juga jual beli & titip jual apartemen.')
@php
    $wa = 'https://wa.me/'.config('likehotel.admin_wa');
    $faqs = [
        ['Berapa harga sewa apartemen di Like Hotel?', 'Harga sewa dimulai dari tarif per malam yang tertera di setiap unit. Tersedia sewa harian, mingguan, dan bulanan tanpa biaya tersembunyi.'],
        ['Bagaimana cara booking apartemen?', 'Pilih unit dan tanggal, isi data pemesan, lalu bayar via QRIS atau Virtual Account. Invoice resmi langsung dibuat otomatis dan bisa dicek kapan saja lewat menu Cek Booking.'],
        ['Apakah pembayaran aman?', 'Ya. Pembayaran diproses melalui Midtrans dengan metode QRIS dan Virtual Account, dan dikonfirmasi otomatis oleh sistem.'],
        ['Apakah unit apartemen bisa dibeli atau dititipkan untuk dijual?', 'Bisa. Lihat unit dijual di halaman Jual Beli, atau titipkan unit Anda lewat halaman Titip Jual dan tim kami bantu pemasarannya.'],
        ['Bagaimana jika butuh bantuan memilih unit?', 'Hubungi CS kami via WhatsApp di '.\App\Support\Fmt::adminWaDisplay().' untuk rekomendasi unit terdekat sesuai kebutuhan Anda.'],
    ];
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'LodgingBusiness',
                '@id' => url('/').'#organization',
                'name' => config('likehotel.name'),
                'description' => 'Marketplace sewa dan jual beli apartemen harian, mingguan, dan bulanan.',
                'url' => url('/'),
                'image' => file_exists(public_path('images/og-default.jpg')) ? asset('images/og-default.jpg') : null,
                'telephone' => '+'.config('likehotel.admin_wa'),
                'priceRange' => 'Rp',
                'areaServed' => 'ID',
                'paymentAccepted' => 'QRIS, Virtual Account',
                'currenciesAccepted' => 'IDR',
                'sameAs' => array_values(array_filter(config('likehotel.social'))) ?: null,
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'url' => url('/'),
                'name' => config('likehotel.name'),
                'inLanguage' => 'id-ID',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => route('apartments.index').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($f) => [
                    '@type' => 'Question',
                    'name' => $f[0],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
                ], $faqs),
            ],
        ],
    ];
@endphp
@push('jsonld')
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
@section('content')
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
  <h2>Rekomendasi Sewa Apartemen Harian &amp; Bulanan</h2>
  <p class="section-subtitle">Pilihan unit apartemen aktif dari owner terverifikasi yang siap disewa.</p>
  <div class="grid">
    @foreach($apartments as $a)
      @include('site._apartment-card', ['a' => $a])
    @endforeach
  </div>
  <p style="text-align:center;margin-top:24px"><a class="btn-outline" href="{{ route('apartments.index') }}">Lihat Semua Apartment</a></p>
</section>

<section class="section">
  <h2>Kenapa Sewa Apartemen di Like Hotel?</h2>
  <p class="section-subtitle">Like Hotel mempertemukan penyewa dengan pemilik apartemen secara langsung. Cocok untuk mahasiswa, pekerja, dan keluarga yang butuh hunian harian, mingguan, maupun bulanan dekat kampus dan kantor. Mau punya unit sendiri? Lihat <a href="{{ route('sale-units.index') }}">apartemen dijual</a> atau <a href="{{ route('sale-request.create') }}">titip jual unit Anda</a>.</p>
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

<section class="section" aria-labelledby="faq-title">
  <h2 id="faq-title">Pertanyaan yang Sering Diajukan</h2>
  <div class="faq" style="max-width:820px;margin:16px auto 0">
    @foreach($faqs as [$q, $ans])
      <details style="border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-bottom:10px;background:#fff">
        <summary style="cursor:pointer;font-weight:700">{{ $q }}</summary>
        <p style="margin:10px 0 0">{{ $ans }}</p>
      </details>
    @endforeach
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
