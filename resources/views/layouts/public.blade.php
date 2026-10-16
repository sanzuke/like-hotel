<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Like Hotel - Sewa Apartment Dekat')</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=20">
@stack('head')
</head>
<body>

<div class="nav no-print">
  <a class="logo" href="{{ route('home') }}">
    <span class="logo-box">LH</span>
    <span>LIKE HOTEL<br><small>Sewa Apartment Dekat</small></span>
  </a>
  <div class="menu">
    <a href="{{ route('home') }}">Beranda</a>
    <a href="{{ route('apartments.index') }}">Sewa Apartment</a>
    <a href="{{ route('sale-units.index') }}">Jual Beli</a>
    <a href="{{ route('sale-request.create') }}">Titip Jual</a>
    <a href="{{ route('booking.check') }}">Cek Booking</a>
    <a href="{{ route('owner.login') }}">Owner</a>
    <a href="{{ route('admin.login') }}" class="btn-outline">Admin</a>
  </div>
</div>

@yield('content')

<div class="footer no-print">
  <b>Like Hotel Sewa Apartment Dekat</b><br>
  WhatsApp: {{ \App\Support\Fmt::adminWaDisplay() }}<br>
  &copy; {{ date('Y') }} {{ config('likehotel.domain') }}
</div>

@stack('scripts')
</body>
</html>
