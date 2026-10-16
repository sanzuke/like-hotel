@extends('layouts.public')
@section('title', $a->name)
@section('content')
@php $urls = $gallery->map(fn ($i) => asset('storage/apartments/'.$i))->all(); @endphp
<section class="detail-hero">
  @if($urls)
    <img id="mainPhoto" class="detail-photo" src="{{ $urls[0] }}" alt="{{ $a->name }}" onclick="openLightbox(currentIndex)">
    <div class="thumb-row">
      @foreach($urls as $i => $url)
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
    <h1>{{ $a->name }}</h1>
    <p class="detail-location">📍 {{ $a->location }}</p>
    <p><b>Tipe Unit:</b> {{ $a->type }}</p>
    <p><b>Owner:</b> {{ $a->owner_name }}</p>
    @if($a->description)
      <h3>Deskripsi Apartment</h3>
      <div class="facility-box" style="margin-bottom:20px">{!! nl2br(e($a->description)) !!}</div>
    @endif
    <h3>Fasilitas</h3>
    <div class="facility-box">{!! nl2br(e($a->facilities)) !!}</div>
  </div>

  <div class="booking-box">
    <div class="price-big">{{ \App\Support\Fmt::rupiah($a->price_per_night) }} <span>/ malam</span></div>
    <ul class="booking-info">
      <li>✅ Booking Online</li>
      <li>✅ Invoice Otomatis</li>
      <li>✅ Pembayaran Aman</li>
      <li>✅ Owner Terverifikasi</li>
    </ul>
    <a class="btn btn-full" href="{{ route('booking.create', $a).(request()->filled('checkin') ? '?'.http_build_query(request()->only('checkin','checkout')) : '') }}">Booking Sekarang</a>
    <a class="btn-outline btn-full" target="_blank" href="https://wa.me/{{ config('likehotel.admin_wa') }}">Tanya via WhatsApp</a>
  </div>
</section>

<div id="lightbox" class="lightbox">
  <span class="lightbox-close" onclick="closeLightbox()">×</span>
  <span class="lightbox-nav lightbox-prev" onclick="prevPhoto()">❮</span>
  <img id="lightboxImage" alt="">
  <span class="lightbox-nav lightbox-next" onclick="nextPhoto()">❯</span>
</div>
@endsection

@push('scripts')
<script>
const photos = @json($urls);
let currentIndex = 0;
function changePhoto(i){
  currentIndex = i;
  const main = document.getElementById('mainPhoto');
  if(main && photos[i]) main.src = photos[i];
  document.querySelectorAll('.thumb-img').forEach(t => t.classList.toggle('active-thumb', Number(t.dataset.index) === i));
}
function showLightbox(){ document.getElementById('lightboxImage').src = photos[currentIndex]; }
function openLightbox(i){ if(!photos.length) return; currentIndex = i; document.getElementById('lightbox').style.display = 'flex'; showLightbox(); }
function closeLightbox(){ document.getElementById('lightbox').style.display = 'none'; }
function nextPhoto(){ if(!photos.length) return; changePhoto((currentIndex + 1) % photos.length); showLightbox(); }
function prevPhoto(){ if(!photos.length) return; changePhoto((currentIndex - 1 + photos.length) % photos.length); showLightbox(); }
document.addEventListener('keydown', e => {
  if(document.getElementById('lightbox').style.display !== 'flex') return;
  if(e.key === 'Escape') closeLightbox();
  if(e.key === 'ArrowRight') nextPhoto();
  if(e.key === 'ArrowLeft') prevPhoto();
});
</script>
@endpush
