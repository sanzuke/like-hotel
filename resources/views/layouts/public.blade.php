<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#15803d">
<title>@yield('title', 'Like Hotel - Sewa Apartment Dekat')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=block">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block">
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=20">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v=7">
@stack('head')
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
  <b>Like Hotel Sewa Apartment Dekat</b><br>
  WhatsApp: {{ \App\Support\Fmt::adminWaDisplay() }}
  <div class="foot-badges"><span>QRIS</span><span>Virtual Account</span><span>Midtrans</span></div>
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
