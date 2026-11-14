@php
  $mapId = 'map-'.($id ?? 'pick');
  $lat = old('lat', $lat ?? null);
  $lng = old('lng', $lng ?? null);
@endphp
@once
  @push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  @endpush
  @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  @endpush
@endonce
<label>Titik Lokasi di Peta</label>
<div id="{{ $mapId }}" style="height:260px;border-radius:12px;border:1px solid #d1d5db;margin:4px 0 8px"></div>
<input type="hidden" name="lat" id="{{ $mapId }}-lat" value="{{ $lat }}">
<input type="hidden" name="lng" id="{{ $mapId }}-lng" value="{{ $lng }}">
<button type="button" class="btn-outline" id="{{ $mapId }}-me">Gunakan lokasi saya</button>
<small>Klik peta atau geser pin ke lokasi unit. Dipakai untuk pencarian "Dekat saya" dan peta di halaman unit.</small>
@error('lat')<div class="field-error">{{ $message }}</div>@enderror
@error('lng')<div class="field-error">{{ $message }}</div>@enderror
@push('scripts')
<script>
(function () {
  var el = document.getElementById(@json($mapId));
  if (!el || typeof L === 'undefined') return;
  var latI = document.getElementById(@json($mapId.'-lat')), lngI = document.getElementById(@json($mapId.'-lng'));
  var has = latI.value !== '' && lngI.value !== '';
  var map = L.map(el).setView(has ? [+latI.value, +lngI.value] : [-6.9175, 107.6191], has ? 16 : 11);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
  var marker = null;
  function put(lat, lng) {
    latI.value = (+lat).toFixed(7); lngI.value = (+lng).toFixed(7);
    if (marker) { marker.setLatLng([lat, lng]); return; }
    marker = L.marker([lat, lng], {draggable: true}).addTo(map);
    marker.on('dragend', function () { var p = marker.getLatLng(); put(p.lat, p.lng); });
  }
  if (has) put(+latI.value, +lngI.value);
  map.on('click', function (e) { put(e.latlng.lat, e.latlng.lng); });
  document.getElementById(@json($mapId.'-me')).addEventListener('click', function () {
    if (!navigator.geolocation) { alert('Browser tidak mendukung geolokasi.'); return; }
    navigator.geolocation.getCurrentPosition(function (p) {
      put(p.coords.latitude, p.coords.longitude); map.setView([p.coords.latitude, p.coords.longitude], 16);
    }, function () { alert('Izin lokasi ditolak atau tidak tersedia.'); });
  });
  // Peta di dalam dialog tersembunyi: hitung ulang ukuran saat tampil.
  if (window.ResizeObserver) new ResizeObserver(function () { map.invalidateSize(); }).observe(el);
})();
</script>
@endpush
