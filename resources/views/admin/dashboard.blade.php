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

<h2>Aktivitas Terbaru</h2>
<div class="stats" style="align-items:flex-start">
  <div class="stat">
    <h3>📅 Booking Terbaru</h3>
    @forelse($bookings as $b)
      <div class="activity-card"><b>{{ $b->booking_code }}</b><br>{{ $b->apartment->name }}<br><small>{{ $b->created_at->format('d-m-Y H:i') }}</small><br><b style="color:#087a35">{{ Fmt::rupiah($b->total_price) }}</b></div>
    @empty <p>Belum ada booking.</p> @endforelse
  </div>
  <div class="stat">
    <h3>🤝 Leads Titip Jual</h3>
    @forelse($leads as $l)
      <div class="activity-card"><b>{{ $l->owner_name }}</b><br>{{ $l->apartment_name }}<br><small>{{ $l->status }} • {{ $l->created_at->format('d-m-Y H:i') }}</small></div>
    @empty <p>Belum ada leads.</p> @endforelse
  </div>
  <div class="stat">
    <h3>🏷 Unit Dijual Terbaru</h3>
    @forelse($saleUnits as $s)
      <div class="activity-card"><b>{{ $s->title }}</b><br>{{ $s->apartment_name }}<br><small>{{ $s->status }}</small><br><b style="color:#087a35">{{ Fmt::rupiah($s->price) }}</b></div>
    @empty <p>Belum ada unit dijual.</p> @endforelse
  </div>
</div>
@endsection
