@extends('layouts.site')
@section('title', $r->title)

@section('content')
@php
  $gallery = $r->galleryUrls();
  $msg = "Halo Like Hotel, saya tertarik dengan unit apartment dijual:\nJudul: {$r->title}\nApartment: {$r->apartment_name}\nLokasi: {$r->location}\nHarga: ".rupiah($r->price)."\nLink: ".url()->current();
  $surveyMsg = "Halo Tim Like Hotel, saya ingin menjadwalkan survei untuk unit:\nJudul: {$r->title}\nApartment: {$r->apartment_name}\nLokasi: {$r->location}\nHarga: ".rupiah($r->price)."\nMohon informasi jadwal survei yang tersedia.";
@endphp

<section class="detail-hero">
  @if($gallery)
    <img id="mainPhoto" class="detail-photo" src="{{ $gallery[0] }}" alt="{{ $r->title }}" onclick="openLightbox(currentIndex)">
    <div class="thumb-row">
      @foreach($gallery as $i => $url)
        <img src="{{ $url }}" onclick="changePhoto({{ $i }})" class="thumb-img {{ $i === 0 ? 'active-thumb' : '' }}" data-index="{{ $i }}" alt="">
      @endforeach
    </div>
  @else
    <div class="detail-placeholder">Apartment Dijual</div>
  @endif
</section>

<section class="detail-wrap">
  <div class="detail-main">
    <span class="badge">🏅 Verified Listing</span>
    <span class="badge">Unit Dijual</span>
    <h1>{{ $r->title }}</h1>
    <p class="detail-location">📍 {{ $r->location }}</p>

    <h3>🏢 Informasi Unit</h3>
    <div class="facility-box">
      <p>🏙️ <b>Nama Apartment:</b> {{ $r->apartment_name }}</p>
      <p>🏠 <b>Tipe Unit:</b> {{ $r->unit_type ?: '-' }}</p>
      <p>🏢 <b>Lantai:</b> {{ $r->floor ?: '-' }}</p>
      <p>📐 <b>Luas:</b> {{ $r->size ?: '-' }}</p>
      <p>📄 <b>Sertifikat:</b> {{ $r->certificate ?: '-' }}</p>
      <p>🛋️ <b>Furniture:</b> {{ $r->furnishing ?: '-' }}</p>
    </div>

    @if($r->description)
      <h3>📝 Deskripsi</h3>
      <div class="facility-box">{!! nl2br(e($r->description)) !!}</div>
    @endif
  </div>

  <div class="booking-box">
    <div class="price-big">{{ rupiah($r->price) }}</div>
    <ul class="booking-info">
      <li>✅ Unit siap dijual</li>
      <li>🛡️ Listing telah diverifikasi</li>
      <li>🤝 Didampingi Tim Like Hotel</li>
      <li>🏡 Cocok untuk hunian / investasi</li>
    </ul>
    <a class="btn btn-full" target="_blank" rel="noopener" href="{{ wa_link(config('likehotel.admin_wa'), $msg) }}">💬 Konsultasi dengan Tim Like Hotel</a>
    <a class="btn-outline btn-full" target="_blank" rel="noopener" href="{{ wa_link(config('likehotel.admin_wa'), $surveyMsg) }}">📅 Jadwalkan Survey Unit</a>

    <div class="facility-box" style="margin-top:20px">
      <h3>🤝 Didampingi Tim Like Hotel</h3>
      <p>Tim Properti Like Hotel akan mendampingi Anda mulai dari konsultasi, survei unit, negosiasi harga, hingga proses pembelian selesai.</p>
    </div>
  </div>
</section>

@include('partials.gallery', ['photos' => $gallery])
@endsection
