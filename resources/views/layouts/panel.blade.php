<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'Panel') - Like Hotel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=33">
@stack('head')
</head>
<body class="panel">
@php
    $isAdmin = request()->is('admin*');
    // Ikon inline (gaya Lucide, viewBox 24); dirender lewat <svg class="ico"> sehingga mengikuti warna teks.
    $icons = [
        'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'building' => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/>',
        'tag' => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
        'percent' => '<line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'wallet' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        'chart' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'home' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'clipboard' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
        'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
        'trending' => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
        'card' => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
        'shield' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'bell' => '<path d="M15 18a3 3 0 0 0 2-5.86V9a6 6 0 1 0-12 0v3.14A3 3 0 0 0 9 18"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"/>',
        'chevron' => '<polyline points="6 9 12 15 18 9"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
    ];
    $menu = $isAdmin ? [
        ['admin.dashboard', 'Dashboard', 'dashboard'],
        ['admin.apartments.index', 'Kelola Apartment', 'building'],
        ['admin.owners.index', 'Kelola Owner', 'users'],
        ['admin.bookings.index', 'Booking & Pembayaran', 'calendar'],
        ['admin.promos.index', 'Promo', 'tag'],
        ['admin.commission', 'Komisi & Settlement', 'percent'],
        ['admin.settlements.index', 'Pencairan Owner', 'wallet'],
        ['admin.reports.index', 'Laporan', 'chart'],
        ['admin.sale-units.index', 'Unit Dijual', 'home'],
        ['admin.sale-requests.index', 'CRM Titip Jual', 'clipboard'],
        ['admin.sale-commissions.index', 'Komisi Penjualan', 'receipt'],
        ['admin.sales-report.index', 'Laporan Penjualan', 'trending'],
        ['admin.midtrans-logs.index', 'Log Midtrans', 'card'],
        ['admin.audit-logs.index', 'Audit Log', 'shield'],
    ] : [
        ['owner.dashboard', 'Dashboard', 'dashboard'],
        ['owner.apartments.index', 'Unit Saya', 'building'],
        ['owner.bookings.index', 'Booking Unit Saya', 'calendar'],
        ['owner.settlements.index', 'Pencairan Dana', 'wallet'],
    ];
    $icon = fn (string $name) => '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
@endphp
@php
    $actor = $isAdmin ? auth('admin')->user() : auth('owner')->user();
    $actorName = $actor?->name ?? ($actor?->username ?? 'Pengguna');
    $actorInitial = mb_substr($actorName, 0, 1, 'UTF-8');
    $actorSubtitle = $isAdmin ? ($actor?->username ?? '') : ($actor?->email ?? '');
    $logoutRoute = $isAdmin ? 'admin.logout' : 'owner.logout';
@endphp
<div class="topbar">
  <div class="topbar-brand">
    <span class="logo-box" style="width:38px;height:38px;font-size:16px">LH</span>
    <span class="topbar-title">{{ $isAdmin ? 'LIKE HOTEL' : 'OWNER' }}</span>
  </div>
  <div class="topbar-right">
    <button type="button" class="topbar-btn" data-open-dialog="notif" aria-label="Notifikasi">
      <svg class="ico" viewBox="0 0 24 24" aria-hidden="true">{!! $icons['bell'] !!}</svg>
      <span class="badge-dot"></span>
    </button>
    <button type="button" class="topbar-user" data-open-dialog="usermenu" aria-label="Menu pengguna">
      <span class="avatar">{{ $actorInitial }}</span>
      <span class="topbar-user-info">
        <span class="topbar-user-name">{{ $actorName }}</span>
        <span class="topbar-user-sub">{{ $actorSubtitle }}</span>
      </span>
      <svg class="ico chevron" viewBox="0 0 24 24" aria-hidden="true">{!! $icons['chevron'] !!}</svg>
    </button>
  </div>
</div>
<div class="admin-layout">
  <div class="sidebar">
    @foreach($menu as [$route, $label, $ico])
      @php
        // Aktif bila rute persis sama, atau halaman turunan modul (mis. admin.apartments.edit untuk admin.apartments.index).
        $isActive = request()->routeIs($route)
            || (str_ends_with($route, '.index') && request()->routeIs(substr($route, 0, -6).'.*'));
      @endphp
      <a href="{{ route($route) }}" @class(['active' => $isActive]) @if($isActive) aria-current="page" @endif>{!! $icon($ico) !!}<span>{{ $label }}</span></a>
    @endforeach
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
<dialog id="notif" class="modal">
  <div class="modal-head"><h3>Notifikasi</h3><button type="button" class="modal-close" data-close-dialog aria-label="Tutup">&times;</button></div>
  <div class="alert warn" style="margin:0">Belum ada notifikasi. Nanti notifikasi (booking, pencairan, CRM) akan muncul di sini.</div>
  <div class="modal-actions"><button type="button" class="btn-outline" data-close-dialog>Tutup</button></div>
</dialog>
<dialog id="usermenu" class="modal">
  <div class="modal-head"><h3>Akun</h3><button type="button" class="modal-close" data-close-dialog aria-label="Tutup">&times;</button></div>
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px">
    <span class="avatar" style="width:54px;height:54px;font-size:20px">{{ $actorInitial }}</span>
    <div>
      <div style="font-weight:800">{{ $actorName }}</div>
      <div style="color:#6b7280;font-size:13px">{{ $actorSubtitle }}</div>
    </div>
  </div>
  <form method="post" action="{{ route($logoutRoute) }}">
    @csrf
    <button type="submit" class="btn" style="width:100%;justify-content:center"><svg class="ico" viewBox="0 0 24 24" aria-hidden="true">{!! $icons['logout'] !!}</svg> Logout</button>
  </form>
</dialog>
<script>
// Dialog/popup: tombol [data-open-dialog="id"] membuka, [data-close-dialog] atau klik di luar kotak menutup.
document.querySelectorAll('[data-open-dialog]').forEach(b =>
  b.addEventListener('click', () => document.getElementById(b.dataset.openDialog).showModal()));
document.querySelectorAll('dialog [data-close-dialog]').forEach(b =>
  b.addEventListener('click', () => b.closest('dialog').close()));
document.querySelectorAll('dialog.modal').forEach(d =>
  d.addEventListener('click', e => { if (e.target === d) d.close(); }));
// Buka lagi otomatis bila validasi gagal, supaya pesan error & isian lama terlihat
document.querySelectorAll('dialog[data-open-on-load]').forEach(d => d.showModal());
</script>
@stack('scripts')
</body>
</html>
