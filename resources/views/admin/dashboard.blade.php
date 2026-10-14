@extends('layouts.admin')
@section('page', 'Executive Dashboard')

@push('head')
<style>
.progress-wrap{background:#e5e7eb;border-radius:999px;height:14px;overflow:hidden;margin:8px 0}
.progress-bar{height:100%;background:#087a35;border-radius:999px}
.dashboard-welcome{background:linear-gradient(135deg,#087a35,#0f5132);color:#fff;border-radius:24px;padding:28px;margin-bottom:24px}
.dashboard-welcome h1{color:#fff;margin:0}
.activity-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:18px;margin-bottom:14px}
.score-big{font-size:44px;font-weight:900;color:#087a35}
</style>
@endpush

@section('content')
<div class="dashboard-welcome">
  <h1>👋 Selamat Datang, {{ auth('admin')->user()->name }}</h1>
  <p>{{ now()->translatedFormat('l, d F Y') }}</p>
  <p>Selamat bekerja. Semoga hari ini banyak booking, leads baru, dan closing penjualan.</p>
</div>

<div class="stats">
  <div class="stat"><p>🏆 Business Health Score</p><div class="score-big">{{ $health }}/100</div><small>Kondisi bisnis keseluruhan</small></div>
  <div class="stat"><p>🏨 Unit Disewakan</p><h2>{{ $totalA }}</h2><small>Aktif: {{ $activeA }} | Maintenance: {{ $maintenanceA }}</small></div>
  <div class="stat"><p>🏷 Unit Dijual</p><h2>{{ $totalSale }}</h2><small>Aktif: {{ $activeSale }} | Terjual: {{ $soldSale }}</small></div>
  <div class="stat"><p>📅 Booking Bulan Ini</p><h2>{{ $bookingMonth }}</h2><small>Hari ini: {{ $todayBooking }}</small></div>
</div>
<br>
<div class="stats">
  <div class="stat"><p>💰 Omzet Bulan Ini</p><h2>{{ rupiah($incomeMonth) }}</h2></div>
  <div class="stat"><p>💵 Komisi Like Hotel</p><h2>{{ rupiah($commMonth) }}</h2></div>
  <div class="stat"><p>🤝 Hak Owner Bulan Ini</p><h2>{{ rupiah($ownerMonth) }}</h2></div>
  <div class="stat"><p>💳 Settlement Diproses</p><h2>{{ $settlementPending }}</h2></div>
</div>
<br>

@if($pendingOwners > 0)
  <div class="alert warn">⚠️ Ada <b>{{ $pendingOwners }}</b> pendaftaran owner menunggu persetujuan. <a href="{{ route('admin.owners') }}"><b>Tinjau sekarang</b></a></div>
@endif

<h2>Quick Action</h2>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:25px">
  <a class="btn" href="{{ route('admin.apartments') }}">🏨 Tambah Apartment</a>
  <a class="btn" href="{{ route('admin.sale-units') }}">🏷 Tambah Unit Dijual</a>
  <a class="btn" href="{{ route('admin.sale-requests') }}">🤝 CRM Titip Jual</a>
  <a class="btn-outline" href="{{ route('admin.promos') }}">🎁 Buat Promo</a>
  <a class="btn-outline" href="{{ route('admin.owners') }}">👤 Kelola Owner</a>
  <a class="btn-outline" href="{{ route('admin.reports') }}">📊 Laporan</a>
</div>

<h2>Target Bisnis Bulan Ini</h2>
<div class="stats">
  <div class="stat"><p>📅 Booking</p><h2>{{ $bookingMonth }} / {{ $targets['booking'] }}</h2>
    <div class="progress-wrap"><div class="progress-bar" style="width:{{ $bookingPercent }}%"></div></div><small>{{ $bookingPercent }}% tercapai</small></div>
  <div class="stat"><p>💰 Omzet</p><h2>{{ rupiah($incomeMonth) }}</h2>
    <div class="progress-wrap"><div class="progress-bar" style="width:{{ $omzetPercent }}%"></div></div><small>Target {{ rupiah($targets['omzet']) }} — {{ $omzetPercent }}%</small></div>
  <div class="stat"><p>💵 Closing Penjualan</p><h2>{{ $closingLeads }} / {{ $targets['closing'] }}</h2>
    <div class="progress-wrap"><div class="progress-bar" style="width:{{ $closingPercent }}%"></div></div><small>{{ $closingPercent }}% tercapai</small></div>
  <div class="stat"><p>🤝 Leads Titip Jual</p><h2>{{ $totalLeads }}</h2><small>Baru: {{ $newLeads }} | Follow Up: {{ $followLeads }} | Survey: {{ $surveyLeads }}</small></div>
</div>
<br>
<div class="alert">Fokus hari ini: follow up leads baru, proses survey unit, pantau booking belum lunas, dan cek settlement owner.</div>

<h2>Aktivitas Terbaru</h2>
<div class="stats" style="align-items:flex-start">
  <div class="stat">
    <h3>📅 Booking Terbaru</h3>
    @forelse($rows as $b)
      <div class="activity-card"><b>{{ $b->booking_code }}</b><br>{{ $b->apartment->name }}<br>
        <small>{{ $b->created_at->format('d-m-Y H:i') }}</small><br><b style="color:#087a35">{{ rupiah($b->total_price) }}</b></div>
    @empty <p>Belum ada booking.</p> @endforelse
  </div>
  <div class="stat">
    <h3>🤝 Leads Titip Jual</h3>
    @forelse($leads as $l)
      <div class="activity-card"><b>{{ $l->owner_name }}</b><br>{{ $l->apartment_name }}<br>
        <small>{{ $l->status }} • {{ $l->created_at->format('d-m-Y H:i') }}</small></div>
    @empty <p>Belum ada leads.</p> @endforelse
  </div>
  <div class="stat">
    <h3>🏷 Unit Dijual Terbaru</h3>
    @forelse($saleUnits as $s)
      <div class="activity-card"><b>{{ $s->title }}</b><br>{{ $s->apartment_name }}<br>
        <small>{{ $s->status }}</small><br><b style="color:#087a35">{{ rupiah($s->price) }}</b></div>
    @empty <p>Belum ada unit dijual.</p> @endforelse
  </div>
</div>
@endsection
