@extends('layouts.site')
@section('title', 'Jual Beli Apartment - Like Hotel')

@section('content')
<section class="apartments-hero">
  <div>
    <span class="badge">🏢 Marketplace Apartment</span>
    <h1>Jual Beli Apartment</h1>
    <p class="muted">Temukan unit apartment terbaik untuk hunian, investasi, atau titip jual melalui Tim Like Hotel.</p>
  </div>
</section>

<section class="apartment-grid">
@forelse($rows as $r)
  @php($msg = "Halo Tim Like Hotel, saya tertarik dengan unit apartment dijual:\nJudul: {$r->title}\nApartment: {$r->apartment_name}\nLokasi: {$r->location}\nHarga: ".rupiah($r->price)."\nMohon informasi detail unit ini.")
  <div class="apartment-card">
    @if($r->image)
      <img class="card-img-modern" src="{{ $r->coverUrl() }}" alt="{{ $r->title }}">
    @else
      <div class="card-img-placeholder">Apartment Dijual</div>
    @endif

    <div class="card-body">
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
        <span class="badge">🏅 Verified</span><span class="badge">Dijual</span>
      </div>
      <h3>{{ $r->title }}</h3>
      <p class="muted">📍 {{ $r->location }}</p>
      <p>🏙️ {{ $r->apartment_name }}</p>
      <p>🏠 {{ $r->unit_type ?: '-' }}@if($r->size) • 📐 {{ $r->size }}@endif @if($r->floor) • 🏢 {{ $r->floor }}@endif</p>
      @if($r->furnishing || $r->certificate)
        <p class="muted">@if($r->furnishing)🛋️ {{ $r->furnishing }}@endif @if($r->certificate) • 📄 {{ $r->certificate }}@endif</p>
      @endif
      <div class="price">{{ rupiah($r->price) }}</div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
        <a class="btn" href="{{ route('sale.show', $r) }}">Lihat Detail</a>
        <a class="btn-outline" target="_blank" rel="noopener" href="{{ wa_link(config('likehotel.admin_wa'), $msg) }}">💬 Konsultasi</a>
      </div>
    </div>
  </div>
@empty
  <div class="alert">Belum ada unit apartment yang dijual.</div>
@endforelse
</section>
@endsection
