@extends('layouts.public')
@section('title', $r->title)
@php
  use App\Support\Fmt;
  $urls = $gallery->map(fn ($i) => asset('storage/sale_units/'.$i))->all();
  $info = "Judul: {$r->title}\nApartment: {$r->apartment_name}\nLokasi: {$r->location}\nHarga: ".Fmt::rupiah($r->price);
  $msg = "Halo Like Hotel, saya tertarik dengan unit apartment dijual:\n{$info}\nLink: ".url()->current();
  $surveyMsg = "Halo Tim Like Hotel, saya ingin menjadwalkan survei untuk unit:\n{$info}\nMohon informasi jadwal survei yang tersedia.";
  $specs = array_filter([
      ['apartment', 'Apartment', $r->apartment_name],
      ['bed', 'Tipe Unit', $r->unit_type],
      ['straighten', 'Luas', $r->size],
      ['stairs', 'Lantai', $r->floor],
      ['description', 'Sertifikat', $r->certificate],
      ['chair', 'Furnish', $r->furnishing],
  ], fn ($s) => filled($s[2]));
@endphp
@section('content')
<div class="pd">
  <nav class="crumbs no-print" aria-label="Breadcrumb">
    <a href="{{ route('home') }}">Beranda</a><i class="ms">chevron_right</i>
    <a href="{{ route('sale-units.index') }}">Jual Beli</a><i class="ms">chevron_right</i>
    <span>{{ $r->title }}</span>
  </nav>

  <div class="pd-title">
    <div class="pd-badges">
      <span class="badge"><i class="ms">verified</i> Listing Terverifikasi</span>
      <span class="badge">Dijual</span>
      @if($r->furnishing)<span class="badge">{{ $r->furnishing }}</span>@endif
    </div>
    <h1>{{ $r->title }}</h1>
    <p class="pd-loc"><i class="ms">pin_drop</i> {{ $r->apartment_name }} &bull; {{ $r->location }}</p>
  </div>

  @if($urls)
    <div class="pd-gallery">
      <img id="mainPhoto" class="pd-main-photo" src="{{ $urls[0] }}" alt="{{ $r->title }}" onclick="openLightbox(currentIndex)">
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
    <div class="pd-gallery"><div class="pd-ph"><span>LIKE HOTEL</span><b>Dijual</b></div></div>
  @endif

  <div class="pd-grid">
    <div class="pd-col">
      @if($specs)
        <section class="pd-card">
          <h2>Ringkasan Spesifikasi Unit</h2>
          <div class="sd-specs">
            @foreach($specs as [$ic, $lbl, $val])
              <div><i class="ms">{{ $ic }}</i><small>{{ $lbl }}</small><b>{{ $val }}</b></div>
            @endforeach
          </div>
        </section>
      @endif

      @if($r->description)
        <section class="pd-card">
          <h2>Deskripsi Unit</h2>
          <div class="pd-desc">{!! nl2br(e($r->description)) !!}</div>
        </section>
      @endif

      <section class="pd-card">
        <h2>Alur Transaksi Aman &amp; Resmi</h2>
        <ol class="sd-steps">
          <li><b>1</b><div><h4>Survei Lokasi</h4><p>Didampingi tim Like Hotel.</p></div></li>
          <li><b>2</b><div><h4>Penawaran &amp; Negosiasi</h4><p>Harga disepakati bersama pemilik.</p></div></li>
          <li><b>3</b><div><h4>Cek Legalitas</h4><p>Pemeriksaan dokumen dan sertifikat.</p></div></li>
          <li><b>4</b><div><h4>Akad / AJB</h4><p>Tanda tangan jual beli dan pelunasan.</p></div></li>
          <li><b>5</b><div><h4>Serah Terima</h4><p>Kunci, akses, dan kelengkapan unit.</p></div></li>
        </ol>
      </section>
    </div>

    <aside class="pd-side">
      <div class="pd-book">
        <small class="sd-lbl">Harga Jual</small>
        <div class="pd-price"><b>{{ Fmt::rupiah($r->price) }}</b></div>
        <a class="btn btn-full" style="margin-top:16px" target="_blank" rel="noopener" href="{{ Fmt::waLink(Fmt::adminWa(), $msg) }}"><i class="ms">chat</i> Hubungi via WhatsApp</a>
        <a class="btn-outline btn-full" target="_blank" rel="noopener" href="{{ Fmt::waLink(Fmt::adminWa(), $surveyMsg) }}"><i class="ms">event_available</i> Jadwalkan Survei</a>
        <ul class="pd-checks">
          <li><i class="ms">check_circle</i> Listing diverifikasi tim</li>
          <li><i class="ms">check_circle</i> Didampingi dari survei sampai closing</li>
          <li><i class="ms">check_circle</i> Konsultasi gratis via WhatsApp</li>
        </ul>
      </div>
    </aside>
  </div>
</div>

<div class="pd-bar no-print">
  <div><small>Harga Jual</small><b>{{ Fmt::rupiah($r->price) }}</b></div>
  <a class="btn" target="_blank" rel="noopener" href="{{ Fmt::waLink(Fmt::adminWa(), $msg) }}">Hubungi</a>
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
</script>
@endpush
