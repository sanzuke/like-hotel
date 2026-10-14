<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'Like Hotel - Sewa Apartment Dekat')</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=30">
@stack('head')
</head>
<body>

<div class="nav no-print">
  <a class="logo" href="{{ route('home') }}">
    <span class="logo-box">LH</span>
    <span>LIKE HOTEL<br><small>Sewa &amp; Jual Beli Apartment</small></span>
  </a>
  <div class="menu">
    <a href="{{ route('home') }}">Beranda</a>
    <a href="{{ route('apartments.index') }}">Sewa Apartment</a>
    <a href="{{ route('sale.index') }}">Jual Beli</a>
    <a href="{{ route('sale.request') }}">Titip Jual</a>
    <a href="{{ route('booking.lookup') }}">Cek Booking</a>
    <a href="{{ route('owner.login') }}">Owner</a>
    <a href="{{ route('admin.login') }}" class="btn-outline">Admin</a>
  </div>
</div>

@yield('content')

@hasSection('nofooter')
@else
<div class="footer">
  <b>Like Hotel Sewa Apartment Dekat</b><br>
  WhatsApp: {{ config('likehotel.admin_wa_display') }}<br>
  © {{ date('Y') }} {{ config('likehotel.site_domain') }}
</div>
@endif

@stack('scripts')
</body>
</html>
