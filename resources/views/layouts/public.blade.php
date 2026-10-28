<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#15803d">
@php
    $seoTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES) ?: 'Like Hotel - Sewa Apartment Dekat';
    $seoDesc = html_entity_decode(trim($__env->yieldContent('meta_description')), ENT_QUOTES) ?: 'Sewa apartemen harian, mingguan, dan bulanan dekat kampus & kantor. Booking mudah, pembayaran aman via QRIS/Virtual Account, invoice otomatis di Like Hotel.';
    $seoCanonical = html_entity_decode(trim($__env->yieldContent('canonical')), ENT_QUOTES) ?: url()->current();
    $seoRobots = html_entity_decode(trim($__env->yieldContent('robots')), ENT_QUOTES) ?: 'index,follow,max-image-preview:large';
    $seoImage = html_entity_decode(trim($__env->yieldContent('og_image')), ENT_QUOTES) ?: (file_exists(public_path('images/og-default.jpg')) ? asset('images/og-default.jpg') : null);
    $seoType = html_entity_decode(trim($__env->yieldContent('og_type')), ENT_QUOTES) ?: 'website';
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">
@if(config('likehotel.google_site_verification'))<meta name="google-site-verification" content="{{ config('likehotel.google_site_verification') }}">@endif
<meta property="og:site_name" content="{{ config('likehotel.name') }}">
<meta property="og:locale" content="id_ID">
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:url" content="{{ $seoCanonical }}">
@if($seoImage)<meta property="og:image" content="{{ $seoImage }}">@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
@if($seoImage)<meta name="twitter:image" content="{{ $seoImage }}">@endif
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block">
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=20">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v=15">
@stack('head')
@stack('jsonld')
</head>
<body class="site">
@php
    $wa = 'https://wa.me/'.config('likehotel.admin_wa');
    $navItems = [
        ['home', 'home', 'Beranda', 'home', 'Beranda'],
        ['apartments.index', 'apartments.*', 'Sewa Apartment', 'key', 'Sewa'],
        ['sale-units.index', 'sale-units.*', 'Jual Beli', 'real_estate_agent', 'Jual/Beli'],
        ['sale-request.create', 'sale-request.*', 'Titip Jual', 'add_business', 'Titip'],
        ['booking.check', 'booking.check*', 'Cek Booking', 'receipt_long', 'Booking'],
    ];
@endphp

<div class="site-top no-print">
  <div class="in">
    <span><i class="ms">verified</i> Pembayaran aman &bull; Invoice otomatis &bull; Owner terverifikasi</span>
    <span><i class="ms">support_agent</i> CS WhatsApp <a href="{{ $wa }}">{{ \App\Support\Fmt::adminWaDisplay() }}</a></span>
  </div>
</div>

<header class="site-head no-print">
  <div class="nav">
    <a class="logo" href="{{ route('home') }}">
      <span class="logo-box">LH</span>
      <span>LIKE HOTEL<small>Sewa &amp; Jual Apartment Dekat</small></span>
    </a>
    <nav class="menu">
      @foreach($navItems as [$route, $pattern, $label])
        <a href="{{ route($route) }}" @class(['on' => request()->routeIs($pattern)])>{{ $label }}</a>
      @endforeach
      <a href="{{ $wa }}" class="wa-pill" target="_blank" rel="noopener"><i class="ms">chat</i> Chat WA</a>
      <a href="{{ route('owner.login') }}" class="btn-outline">Login</a>
    </nav>
    <a href="{{ $wa }}" class="wa-pill mobile-wa" target="_blank" rel="noopener" style="display:none" aria-label="Chat WhatsApp"><i class="ms">chat</i> CS</a>
  </div>
</header>
<style>@media(max-width:900px){.mobile-wa{display:inline-flex!important}}</style>

@yield('content')

<div class="footer no-print">
  <nav aria-label="Tautan footer"><a href="{{ route('apartments.index') }}">Sewa Apartment</a> &middot; <a href="{{ route('sale-units.index') }}">Jual Beli Apartment</a> &middot; <a href="{{ route('sale-request.create') }}">Titip Jual</a> &middot; <a href="{{ route('booking.check') }}">Cek Booking</a></nav>
  <b>Like Hotel Sewa Apartment Dekat</b><br>
  WhatsApp: {{ \App\Support\Fmt::adminWaDisplay() }}
  <div class="foot-badges"><span>QRIS</span><span>Virtual Account</span><span>Midtrans</span></div>
  @php $social = array_filter(config('likehotel.social')); @endphp
  @if($social)
    <div class="foot-social">@foreach(['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook', 'google_business' => 'Google Bisnisku'] as $k => $label)@if(!empty($social[$k]))<a href="{{ $social[$k] }}" target="_blank" rel="noopener me">{{ $label }}</a>@endif @endforeach</div>
  @endif
  &copy; {{ date('Y') }} {{ config('likehotel.domain') }}
</div>

<nav class="bottom-nav no-print" aria-label="Navigasi utama">
  @foreach($navItems as [$route, $pattern, $label, $icon, $short])
    <a href="{{ route($route) }}" @class(['on' => request()->routeIs($pattern)])><i class="ms">{{ $icon }}</i>{{ $short }}</a>
  @endforeach
</nav>

@stack('scripts')
</body>
</html>
