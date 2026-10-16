<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'Panel') - Like Hotel</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=20">
@stack('head')
</head>
<body>
@php
    $isAdmin = request()->is('admin*');
    $menu = $isAdmin ? [
        ['admin.dashboard', 'Dashboard'],
        ['admin.apartments.index', 'Kelola Apartment'],
        ['admin.owners.index', 'Kelola Owner'],
        ['admin.bookings.index', 'Booking & Pembayaran'],
        ['admin.promos.index', 'Promo'],
        ['admin.commission', 'Komisi & Settlement'],
        ['admin.settlements.index', 'Pencairan Owner'],
        ['admin.reports.index', 'Laporan'],
        ['admin.sale-units.index', 'Unit Dijual'],
        ['admin.sale-requests.index', 'CRM Titip Jual'],
        ['admin.sale-commissions.index', 'Komisi Penjualan'],
        ['admin.audit-logs.index', 'Audit Log'],
    ] : [
        ['owner.dashboard', 'Dashboard'],
        ['owner.apartments.index', 'Unit Saya'],
        ['owner.bookings.index', 'Booking Unit Saya'],
        ['owner.settlements.index', 'Pencairan Dana'],
    ];
@endphp
<div class="admin-layout">
  <div class="sidebar">
    <h2>{{ $isAdmin ? 'LIKE HOTEL' : 'OWNER' }}</h2>
    @foreach($menu as [$route, $label])
      <a href="{{ route($route) }}">{{ $label }}</a>
    @endforeach
    <form method="post" action="{{ route($isAdmin ? 'admin.logout' : 'owner.logout') }}">
      @csrf
      <button type="submit" class="sidebar-logout">Logout</button>
    </form>
  </div>
  <div class="content">
    @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert danger">{{ session('error') }}</div>@endif
    @if($errors->any())
      <div class="alert danger">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
    @endif
    @yield('content')
  </div>
</div>
@stack('scripts')
</body>
</html>
