@extends('layouts.public')
@section('title', $a->name)
@section('content')
@php
    $urls = $gallery->map(fn ($i) => asset('storage/apartments/'.$i))->all();
    $facs = collect(preg_split('/[,;\n]+/', (string) $a->facilities))->map(fn ($f) => trim($f))->filter()->values();
    $wa = 'https://wa.me/'.config('likehotel.admin_wa');
    $price = (int) $a->price_per_night;
@endphp
<div class="pd">
  <nav class="crumbs no-print" aria-label="Breadcrumb">
    <a href="{{ route('home') }}">Beranda</a><i class="ms">chevron_right</i>
    <a href="{{ route('apartments.index') }}">Sewa Apartment</a><i class="ms">chevron_right</i>
    <span>{{ $a->name }}</span>
  </nav>

  <div class="pd-title">
    <div class="pd-badges">
      <span class="badge"><i class="ms">verified</i> Unit Terverifikasi</span>
      <span class="badge"><i class="ms">shield_person</i> Owner Terpercaya</span>
      <span class="badge">{{ $a->type }}</span>
    </div>
    <h1>{{ $a->name }}</h1>
    <p class="pd-loc"><i class="ms">pin_drop</i> {{ $a->location }}</p>
  </div>

  {{-- Galeri --}}
  @if($urls)
    <div class="pd-gallery">
      <img id="mainPhoto" class="pd-main-photo" src="{{ $urls[0] }}" alt="{{ $a->name }}" onclick="openLightbox(currentIndex)">
      @if(count($urls) > 1)
        <div class="pd-thumbs">
          @foreach($urls as $i => $url)
            <img src="{{ $url }}" onclick="changePhoto({{ $i }})" class="thumb-img {{ $i === 0 ? 'active-thumb' : '' }}" data-index="{{ $i }}" alt="Foto {{ $i + 1 }}" loading="lazy">
          @endforeach
        </div>
      @endif
      <button type="button" class="pd-all-photos" onclick="openLightbox(currentIndex)"><i class="ms">photo_library</i> {{ count($urls) }} Foto</button>
    </div>
  @else
    <div class="pd-gallery"><div class="pd-ph"><span>LIKE HOTEL</span><b>Apartment</b></div></div>
  @endif

  <div class="pd-grid">
    <div class="pd-col">
      {{-- Ringkasan --}}
      <div class="pd-specs">
        <div><i class="ms">apartment</i><small>Tipe Unit</small><b>{{ $a->type }}</b></div>
        <div><i class="ms">location_on</i><small>Lokasi</small><b>{{ $a->location }}</b></div>
        <div><i class="ms">person</i><small>Owner</small><b>{{ $a->owner_name }}</b></div>
        <div><i class="ms">receipt_long</i><small>Invoice</small><b>Otomatis</b></div>
      </div>

      @if($a->description)
        <section class="pd-card">
          <h2>Deskripsi Apartemen</h2>
          <div class="pd-desc">{!! nl2br(e($a->description)) !!}</div>
        </section>
      @endif

      <section class="pd-card">
        <h2>Fasilitas Unit</h2>
        @if($facs->isNotEmpty())
          <div class="pd-facs">
            @foreach($facs as $f)<span><i class="ms">check_circle</i> {{ $f }}</span>@endforeach
          </div>
        @else
          <p class="muted">Belum ada daftar fasilitas.</p>
        @endif
      </section>

      <section class="pd-card">
        <h2>Kenapa Booking di Like Hotel?</h2>
        <div class="pd-trust">
          <div><i class="ms">bolt</i><b>Booking Online</b><p>Pilih tanggal, isi data, invoice langsung dibuat.</p></div>
          <div><i class="ms">lock</i><b>Pembayaran Aman</b><p>Pembayaran digital lewat Midtrans, status otomatis.</p></div>
          <div><i class="ms">verified_user</i><b>Owner Terverifikasi</b><p>Unit dikelola owner yang sudah disetujui admin.</p></div>
          <div><i class="ms">support_agent</i><b>CS WhatsApp</b><p>Admin siap membantu sebelum dan sesudah booking.</p></div>
        </div>
      </section>
    </div>

    {{-- Kartu booking --}}
    <aside class="pd-side">
      <form class="pd-book" method="get" action="{{ route('booking.create', $a) }}" id="bookCard">
        <div class="pd-price"><b>{{ \App\Support\Fmt::rupiah($price) }}</b><span>/ malam</span></div>
        <div class="pd-dates">
          <label>Check-in<input type="date" name="checkin" id="ci" min="{{ today()->toDateString() }}" value="{{ request('checkin') }}"></label>
          <label>Check-out<input type="date" name="checkout" id="co" min="{{ today()->toDateString() }}" value="{{ request('checkout') }}"></label>
        </div>
        <div class="pd-sum">
          <div><span id="sumLbl">{{ \App\Support\Fmt::rupiah($price) }} x 1 malam</span><span id="sumAmt">{{ \App\Support\Fmt::rupiah($price) }}</span></div>
          <div class="tot"><span>Estimasi Total</span><b id="sumTot">{{ \App\Support\Fmt::rupiah($price) }}</b></div>
          <small>Promo (jika ada) dihitung di langkah berikutnya.</small>
        </div>
        <button class="btn btn-full"><i class="ms">bolt</i> Booking Sekarang</button>
        <a class="btn-outline btn-full" target="_blank" rel="noopener" href="{{ $wa }}"><i class="ms">chat</i> Tanya via WhatsApp</a>
        <ul class="pd-checks">
          <li><i class="ms">check_circle</i> Invoice otomatis</li>
          <li><i class="ms">check_circle</i> Pembayaran aman</li>
          <li><i class="ms">check_circle</i> Owner terverifikasi</li>
        </ul>
      </form>
    </aside>
  </div>
</div>

{{-- Bar booking mobile --}}
<div class="pd-bar no-print">
  <div><small>Mulai dari</small><b>{{ \App\Support\Fmt::rupiah($price) }}<span>/malam</span></b></div>
  <a class="btn" href="{{ route('booking.create', $a).(request()->filled('checkin') ? '?'.http_build_query(request()->only('checkin','checkout')) : '') }}">Booking</a>
</div>

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

// Estimasi total di kartu booking (harga final & promo tetap dihitung server).
(function(){
  const price = {{ $price }}, ci = document.getElementById('ci'), co = document.getElementById('co');
  const rp = n => 'Rp' + n.toLocaleString('id-ID');
  function calc(){
    let n = 1;
    if(ci.value && co.value){ n = Math.round((new Date(co.value) - new Date(ci.value)) / 86400000); }
    if(n < 1) n = 1;
    document.getElementById('sumLbl').textContent = rp(price) + ' x ' + n + ' malam';
    document.getElementById('sumAmt').textContent = rp(price * n);
    document.getElementById('sumTot').textContent = rp(price * n);
  }
  ci.addEventListener('change', () => { if(co.value && co.value <= ci.value) co.value = ''; co.min = ci.value || co.min; calc(); });
  co.addEventListener('change', calc);
  calc();
})();
</script>
@endpush
