@extends('layouts.public')
@section('title', $r->title)
@php
  use App\Support\Fmt;
  $urls = $gallery->map(fn ($i) => asset('storage/sale_units/'.$i))->all();
  $info = "Judul: {$r->title}\nApartment: {$r->apartment_name}\nLokasi: {$r->location}\nHarga: ".Fmt::rupiah($r->price);
  $msg = "Halo Like Hotel, saya tertarik dengan unit apartment dijual:\n{$info}\nLink: ".url()->current();
  $surveyMsg = "Halo Tim Like Hotel, saya ingin menjadwalkan survei untuk unit:\n{$info}\nMohon informasi jadwal survei yang tersedia.";
@endphp
@section('content')
<section class="detail-hero">
  @if($urls)
    <img id="mainPhoto" class="detail-photo" src="{{ $urls[0] }}" alt="{{ $r->title }}">
    <div class="thumb-row">
      @foreach($urls as $i => $url)
        <img src="{{ $url }}" onclick="document.getElementById('mainPhoto').src=this.src" class="thumb-img {{ $i === 0 ? 'active-thumb' : '' }}" alt="">
      @endforeach
    </div>
  @else
    <div class="detail-placeholder">Dijual</div>
  @endif
</section>

<section class="detail-wrap">
  <div class="detail-main">
    <span class="badge">Dijual</span>
    <h1>{{ $r->title }}</h1>
    <p class="detail-location">📍 {{ $r->location }}</p>
    <p><b>Apartment:</b> {{ $r->apartment_name }}</p>
    <p><b>Tipe:</b> {{ $r->unit_type ?: '-' }} &nbsp;|&nbsp; <b>Lantai:</b> {{ $r->floor ?: '-' }} &nbsp;|&nbsp; <b>Luas:</b> {{ $r->size ?: '-' }}</p>
    <p><b>Sertifikat:</b> {{ $r->certificate ?: '-' }} &nbsp;|&nbsp; <b>Furnish:</b> {{ $r->furnishing ?: '-' }}</p>
    @if($r->description)
      <h3>Deskripsi</h3>
      <div class="facility-box">{!! nl2br(e($r->description)) !!}</div>
    @endif
  </div>
  <div class="booking-box">
    <div class="price-big">{{ Fmt::rupiah($r->price) }}</div>
    <a class="btn btn-full" target="_blank" href="{{ Fmt::waLink(Fmt::adminWa(), $msg) }}">Hubungi via WhatsApp</a>
    <a class="btn-outline btn-full" target="_blank" href="{{ Fmt::waLink(Fmt::adminWa(), $surveyMsg) }}">Jadwalkan Survei</a>
  </div>
</section>
@endsection
