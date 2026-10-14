@extends('layouts.site')
@section('title', $apartment->name)

@section('content')
@php($gallery = $apartment->galleryUrls())

<section class="detail-hero">
  @if($gallery)
    <img id="mainPhoto" class="detail-photo" src="{{ $gallery[0] }}" alt="{{ $apartment->name }}" onclick="openLightbox(currentIndex)">
    <div class="thumb-row">
      @foreach($gallery as $i => $url)
        <img src="{{ $url }}" onclick="changePhoto({{ $i }})" class="thumb-img {{ $i === 0 ? 'active-thumb' : '' }}" data-index="{{ $i }}" alt="">
      @endforeach
    </div>
  @else
    <div class="detail-placeholder">Apartment</div>
  @endif
</section>

<section class="detail-wrap">
  <div class="detail-main">
    <span class="badge">Terverifikasi</span>
    <h1>{{ $apartment->name }}</h1>
    <p class="detail-location">📍 {{ $apartment->location }}</p>
    <p><b>Tipe Unit:</b> {{ $apartment->type }}</p>
    <p><b>Owner:</b> {{ $apartment->owner_name }}</p>

    @if($apartment->description)
      <h3>Deskripsi Apartment</h3>
      <div class="facility-box" style="margin-bottom:20px">{!! nl2br(e($apartment->description)) !!}</div>
    @endif

    <h3>Fasilitas</h3>
    <div class="facility-box">{!! nl2br(e($apartment->facilities)) !!}</div>
  </div>

  <div class="booking-box">
    <div class="price-big">{{ rupiah($apartment->price_per_night) }} <span>/ malam</span></div>
    <ul class="booking-info">
      <li>✅ Booking Online</li>
      <li>✅ Invoice Otomatis</li>
      <li>✅ Pembayaran Aman</li>
      <li>✅ Owner Terverifikasi</li>
    </ul>
    <a class="btn btn-full" href="{{ route('booking.create', $apartment) }}">Booking Sekarang</a>
    <a class="btn-outline btn-full" target="_blank" rel="noopener" href="https://wa.me/{{ config('likehotel.admin_wa') }}">Tanya via WhatsApp</a>
  </div>
</section>

@include('partials.gallery', ['photos' => $gallery])
@endsection
