@extends('layouts.panel')
@section('title', 'Dashboard')
@php use App\Support\Fmt; @endphp
@section('content')
<div class="dashboard-welcome">
  <h1>👋 Selamat Datang, {{ auth('admin')->user()->name }}</h1>
  <p>{{ now()->translatedFormat('l, d F Y') }}</p>
  <p>Selamat bekerja. Semoga hari ini banyak booking, leads baru, dan closing penjualan.</p>
</div>

@if($pendingOwners > 0)
  <div class="alert warn"><b>{{ $pendingOwners }} owner</b> menunggu persetujuan. <a href="{{ route('admin.owners.index') }}">Tinjau sekarang</a></div>
@endif

<div class="stats">
  <div class="stat"><p>🏆 Business Health Score</p><div class="score-big">{{ $health }}/100</div><small>Kondisi bisnis keseluruhan</small></div>
  <div class="stat"><p>🏨 Unit Disewakan</p><h2>{{ $totalA }}</h2><small>Aktif: {{ $activeA }} | Maintenance: {{ $maintenanceA }}</small></div>
  <div class="stat"><p>🏷 Unit Dijual</p><h2>{{ $totalSale }}</h2><small>Aktif: {{ $activeSale }} | Terjual: {{ $soldSale }}</small></div>
  <div class="stat"><p>📅 Booking Bulan Ini</p><h2>{{ $bookingMonth }}</h2><small>Hari ini: {{ $todayBooking }}</small></div>
</div>
<br>
<div class="stats">
  <div class="stat"><p>💰 Omzet Bulan Ini</p><h2>{{ Fmt::rupiah($omzet) }}</h2></div>
  <div class="stat"><p>💵 Komisi Like Hotel</p><h2>{{ Fmt::rupiah($commission) }}</h2></div>
  <div class="stat"><p>🤝 Hak Owner Bulan Ini</p><h2>{{ Fmt::rupiah($ownerShare) }}</h2></div>
  <div class="stat"><p>💳 Settlement Diproses</p><h2>{{ $settlementPending }}</h2></div>
</div>
<br>

<h2>Quick Action</h2>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:25px">
  <a class="btn" href="{{ route('admin.apartments.index') }}">🏨 Tambah Apartment</a>
  <a class="btn" href="{{ route('admin.sale-units.index') }}">🏷 Tambah Unit Dijual</a>
  <a class="btn" href="{{ route('admin.sale-requests.index') }}">🤝 CRM Titip Jual</a>
  <a class="btn-outline" href="{{ route('admin.promos.index') }}">🎁 Buat Promo</a>
  <a class="btn-outline" href="{{ route('admin.owners.index') }}">👤 Kelola Owner</a>
  <a class="btn-outline" href="{{ route('admin.reports.index') }}">📊 Laporan</a>
</div>

<h2>Target Bisnis Bulan Ini</h2>
<div class="stats">
  <div class="stat"><p>📅 Booking</p><h2>{{ $bookingMonth }} / {{ $targets['booking'] }}</h2><div class="progress-wrap"><div class="progress-bar" style="width:{{ $bookingPct }}%"></div></div><small>{{ $bookingPct }}% tercapai</small></div>
  <div class="stat"><p>💰 Omzet</p><h2>{{ Fmt::rupiah($omzet) }}</h2><div class="progress-wrap"><div class="progress-bar" style="width:{{ $omzetPct }}%"></div></div><small>Target {{ Fmt::rupiah($targets['omzet']) }} — {{ $omzetPct }}%</small></div>
  <div class="stat"><p>💵 Closing Penjualan</p><h2>{{ $closingLeads }} / {{ $targets['closing'] }}</h2><div class="progress-wrap"><div class="progress-bar" style="width:{{ $closingPct }}%"></div></div><small>{{ $closingPct }}% tercapai</small></div>
  <div class="stat"><p>🤝 Leads Titip Jual</p><h2>{{ $totalLeads }}</h2><small>Baru: {{ $newLeads }} | Follow Up: {{ $followLeads }} | Survey: {{ $surveyLeads }}</small></div>
</div>
<br>

<div class="section-head">
  <h2>Aktivitas Terbaru</h2>
  <span class="muted">5 data terakhir dari tiap modul</span>
</div>
<div class="activity-grid">
  <section class="activity-panel">
    <header class="activity-head">
      <h3>Booking Terbaru</h3>
      <a href="{{ route('admin.bookings.index') }}">Lihat semua →</a>
    </header>
    <ul class="activity-list">
      @forelse($bookings as $b)
        <li>
          <span class="avatar-sq">📅</span>
          <div class="activity-main">
            <div class="activity-title">{{ $b->apartment->name }}</div>
            <div class="activity-sub">{{ $b->booking_code }} · {{ $b->guest_name }}</div>
            <div class="activity-time" title="{{ $b->created_at->format('d M Y H:i') }}">{{ $b->created_at->diffForHumans() }}</div>
          </div>
          <div class="activity-side">
            <div class="activity-amount">{{ Fmt::rupiah($b->total_price) }}</div>
            <span class="pill" style="--c:{{ Fmt::statusColor($b->payment_status) }}">{{ $b->payment_status }}</span>
          </div>
        </li>
      @empty
        <li class="activity-empty">Belum ada booking.</li>
      @endforelse
    </ul>
  </section>

  <section class="activity-panel">
    <header class="activity-head">
      <h3>Leads Titip Jual</h3>
      <a href="{{ route('admin.sale-requests.index') }}">Lihat semua →</a>
    </header>
    <ul class="activity-list">
      @forelse($leads as $l)
        <li>
          <span class="avatar-sq">🤝</span>
          <div class="activity-main">
            <div class="activity-title">{{ $l->apartment_name }}</div>
            <div class="activity-sub">{{ $l->owner_name }}</div>
            <div class="activity-time" title="{{ $l->created_at->format('d M Y H:i') }}">{{ $l->created_at->diffForHumans() }}</div>
          </div>
          <div class="activity-side">
            <span class="pill" style="--c:{{ Fmt::statusColor($l->status) }}">{{ ucfirst($l->status) }}</span>
          </div>
        </li>
      @empty
        <li class="activity-empty">Belum ada leads.</li>
      @endforelse
    </ul>
  </section>

  <section class="activity-panel">
    <header class="activity-head">
      <h3>Unit Dijual Terbaru</h3>
      <a href="{{ route('admin.sale-units.index') }}">Lihat semua →</a>
    </header>
    <ul class="activity-list">
      @forelse($saleUnits as $s)
        <li>
          <span class="avatar-sq">🏷</span>
          <div class="activity-main">
            <div class="activity-title">{{ $s->title }}</div>
            <div class="activity-sub">{{ $s->apartment_name }}</div>
            <div class="activity-time" title="{{ $s->created_at->format('d M Y H:i') }}">{{ $s->created_at->diffForHumans() }}</div>
          </div>
          <div class="activity-side">
            <div class="activity-amount">{{ Fmt::rupiah($s->price) }}</div>
            <span class="pill" style="--c:{{ Fmt::statusColor($s->status) }}">{{ ucfirst($s->status) }}</span>
          </div>
        </li>
      @empty
        <li class="activity-empty">Belum ada unit dijual.</li>
      @endforelse
    </ul>
  </section>
</div>
@endsection
