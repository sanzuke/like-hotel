@extends('layouts.public')
@section('title', 'Jual Beli Apartemen - Like Hotel')
@section('meta_description', 'Cari apartemen dijual dengan harga terbaik dan listing terverifikasi. Tersedia info lokasi, luas, sertifikat, dan furnish di Like Hotel.')
@section('content')
@php
    use App\Support\Fmt;
    $wa = fn (string $text) => Fmt::waLink(Fmt::adminWa(), $text);
@endphp
<section class="sl-hero">
  <div class="in">
    <span class="badge"><i class="ms" style="font-size:15px">real_estate_agent</i> Jual Beli Apartment</span>
    <h1>Unit Apartemen Dijual</h1>
    <p>Pilihan unit apartemen untuk investasi maupun hunian, dibantu tim Like Hotel dari survei hingga closing.</p>
    <div class="sl-trust">
      <span><i class="ms">verified</i> Listing diverifikasi tim</span>
      <span><i class="ms">gavel</i> Pendampingan legalitas</span>
      <span><i class="ms">support_agent</i> Konsultasi via WhatsApp</span>
    </div>
  </div>
</section>

<div class="pd sl">
  <form method="get" action="{{ route('sale-units.index') }}" class="sl-filter">
    <div class="sl-tabs">
      <span class="on">Unit Dijual</span>
      <a href="{{ route('apartments.index') }}">Unit Disewakan</a>
    </div>
    <div class="sl-fields">
      <label><span><i class="ms">search</i> Lokasi / Gedung</span>
        <input name="q" value="{{ $filters['q'] }}" placeholder="Contoh: Jatinangor, Easton Park"></label>
      <label><span><i class="ms">apartment</i> Tipe Kamar</span>
        <select name="type"><option value="">Semua tipe</option>
          @foreach($types as $t)<option value="{{ $t }}" @selected($filters['type'] === $t)>{{ $t }}</option>@endforeach
        </select></label>
      <label><span><i class="ms">payments</i> Rentang Harga</span>
        <select name="price"><option value="">Semua budget</option>
          @foreach($priceRanges as $k => [$lbl])<option value="{{ $k }}" @selected($filters['price'] === $k)>{{ $lbl }}</option>@endforeach
        </select></label>
      <label><span><i class="ms">chair</i> Kelengkapan</span>
        <select name="furnishing"><option value="">Semua kondisi</option>
          @foreach($furnishings as $t)<option value="{{ $t }}" @selected($filters['furnishing'] === $t)>{{ $t }}</option>@endforeach
        </select></label>
      <label><span><i class="ms">sort</i> Urutkan</span>
        <select name="sort">
          @foreach(['terbaru' => 'Terbaru', 'termurah' => 'Harga terendah', 'termahal' => 'Harga tertinggi'] as $v => $l)
            <option value="{{ $v }}" @selected($filters['sort'] === $v)>{{ $l }}</option>
          @endforeach
        </select></label>
      <button class="btn"><i class="ms">search</i> Terapkan</button>
    </div>
    <div class="sl-meta">
      <b>Ditemukan {{ $rows->count() }} unit</b>
      @if($hasFilter)<a href="{{ route('sale-units.index') }}"><i class="ms">refresh</i> Reset filter</a>@endif
    </div>
  </form>

  @if($rows->isEmpty())
    <div class="sl-empty">
      <i class="ms">search_off</i>
      <h3>Unit tidak ditemukan</h3>
      <p>@if($hasFilter)Tidak ada unit yang cocok dengan filter Anda. Coba ubah pilihan filter atau hubungi kami.@else Belum ada unit yang dijual saat ini.@endif</p>
      <a class="btn" target="_blank" rel="noopener" href="{{ $wa('Halo Like Hotel, saya mencari unit apartemen untuk dibeli. Mohon rekomendasinya.') }}"><i class="ms">chat</i> Tanya Konsultan</a>
    </div>
  @else
    <div class="sl-grid">
      @foreach($rows as $r)
        @php $msg = "Halo Like Hotel, saya tertarik dengan unit dijual:\n{$r->title}\n{$r->apartment_name}, {$r->location}\nHarga: ".Fmt::rupiah($r->price)."\nLink: ".route('sale-units.show', $r); @endphp
        <article class="sl-card">
          <a class="sl-media" href="{{ route('sale-units.show', $r) }}">
            @if($r->image)
              <img src="{{ asset('storage/sale_units/'.$r->image) }}" alt="{{ \App\Support\Seo::alt('Dijual', $r->title, $r->apartment_name, $r->location) }}" width="640" height="400" loading="lazy" decoding="async">
            @else
              <div class="sl-ph"><span>LIKE HOTEL</span><b>Dijual</b></div>
            @endif
            <span class="tag">Dijual</span>
            @if($r->furnishing)<span class="tag2">{{ $r->furnishing }}</span>@endif
          </a>
          <div class="sl-body">
            <p class="sl-apt"><i class="ms">domain</i> {{ $r->apartment_name }}</p>
            <h3><a href="{{ route('sale-units.show', $r) }}">{{ $r->title }}</a></h3>
            <p class="loc"><i class="ms">near_me</i> {{ $r->location }}@if($r->floor) &bull; {{ \Illuminate\Support\Str::startsWith(strtolower($r->floor), 'lantai') ? $r->floor : 'Lantai '.$r->floor }}@endif</p>
            <div class="sl-specs">
              @if($r->size)<div><b>{{ $r->size }}</b><small>Luas</small></div>@endif
              @if($r->unit_type)<div><b>{{ $r->unit_type }}</b><small>Tipe</small></div>@endif
              @if($r->certificate)<div><b>{{ $r->certificate }}</b><small>Sertifikat</small></div>@endif
            </div>
          </div>
          <div class="sl-foot">
            <div><small>Harga Jual</small><p class="price">{{ Fmt::rupiah($r->price) }}</p></div>
            <div class="acts">
              <a class="wa-ic" target="_blank" rel="noopener" href="{{ $wa($msg) }}" aria-label="Tanya via WhatsApp"><i class="ms">chat</i></a>
              <a class="btn" href="{{ route('sale-units.show', $r) }}">Detail Unit</a>
            </div>
          </div>
        </article>
      @endforeach
    </div>
  @endif

  <section class="sl-cta">
    <div>
      <h3>Punya unit yang ingin dijual?</h3>
      <p>Titipkan ke Like Hotel. Tim kami bantu survei, foto, listing, negosiasi, sampai closing.</p>
    </div>
    <a class="btn" href="{{ route('sale-request.create') }}"><i class="ms">add_business</i> Titip Jual</a>
  </section>
</div>
@endsection
