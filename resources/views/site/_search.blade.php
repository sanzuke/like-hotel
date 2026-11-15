@php
  $advanced = $advanced ?? false;
  $filters = $filters ?? [];
  $types = $types ?? collect();
  $categories = $categories ?? \App\Models\Apartment::CATEGORIES;
@endphp
<div class="search-card">
  <div class="mode-tabs">
    <a class="on" href="{{ route('apartments.index') }}">Sewa Apartemen</a>
    <a href="{{ route('sale-units.index') }}">Jual / Beli Unit</a>
  </div>
  <form method="get" action="{{ route('apartments.index') }}" class="search search-wide{{ $advanced ? " search-adv" : "" }}">
    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama atau lokasi, contoh: Jatinangor" aria-label="Lokasi atau nama apartemen">
    <select name="category" aria-label="Kategori unit">
      <option value="">Semua kategori</option>
      @foreach($categories as $v => $l)<option value="{{ $v }}" @selected(($filters['category'] ?? '') === $v)>{{ $l }}</option>@endforeach
    </select>
    <input type="{{ ! empty($filters['checkin']) ? 'date' : 'text' }}" name="checkin" placeholder="Check-in" min="{{ today()->toDateString() }}" value="{{ $filters['checkin'] ?? '' }}" title="Check-in" aria-label="Check-in" autocomplete="off" onfocus="this.type='date';try{this.showPicker()}catch(e){}" onblur="if(!this.value)this.type='text'">
    <input type="{{ ! empty($filters['checkout']) ? 'date' : 'text' }}" name="checkout" placeholder="Check-out" min="{{ today()->toDateString() }}" value="{{ $filters['checkout'] ?? '' }}" title="Check-out" aria-label="Check-out" autocomplete="off" onfocus="this.type='date';try{this.showPicker()}catch(e){}" onblur="if(!this.value)this.type='text'">
    <button class="btn"><i class="ms">search</i> Cari Apartment</button>
    <select name="near" aria-label="Dekat landmark">
      <option value="">Dekat mana saja</option>
      @foreach(config('likehotel.landmarks') as $k => $lm)<option value="{{ $k }}" @selected(($filters['near'] ?? '') === $k)>Dekat {{ $lm[0] }}</option>@endforeach
    </select>
    <input type="hidden" name="lat" id="geo-lat" value="{{ $filters['lat'] ?? '' }}">
    <input type="hidden" name="lng" id="geo-lng" value="{{ $filters['lng'] ?? '' }}">
    <button type="button" class="btn-outline" id="geo-me"><i class="ms">my_location</i> Dekat saya</button>

    @if($advanced)
      <select name="type" style="grid-column:span 1">
        <option value="">Semua tipe</option>
        @foreach($types as $t)<option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>{{ $t }}</option>@endforeach
      </select>
      <input type="number" min="0" step="10000" name="max_price" value="{{ $filters['max_price'] ?? '' }}" placeholder="Harga maks. / malam">
      <select name="sort">
        @foreach(['terbaru' => 'Terbaru', 'terdekat' => 'Terdekat', 'termurah' => 'Harga terendah', 'termahal' => 'Harga tertinggi'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['sort'] ?? 'terbaru') === $v)>{{ $l }}</option>
        @endforeach
      </select>
      <a class="btn-outline" href="{{ route('apartments.index') }}">Reset</a>
    @endif
  </form>
</div>
@once
@push('scripts')
<script>
(function () {
  var b = document.getElementById('geo-me'); if (!b) return;
  var f = b.form, near = f.querySelector('[name=near]');
  near.addEventListener('change', function () { document.getElementById('geo-lat').value = ''; document.getElementById('geo-lng').value = ''; });
  b.addEventListener('click', function () {
    if (!navigator.geolocation) { alert('Browser tidak mendukung geolokasi.'); return; }
    b.disabled = true;
    navigator.geolocation.getCurrentPosition(function (p) {
      document.getElementById('geo-lat').value = p.coords.latitude.toFixed(6);
      document.getElementById('geo-lng').value = p.coords.longitude.toFixed(6);
      near.value = '';
      var s = f.querySelector('[name=sort]'); if (s) s.value = 'terdekat';
      f.submit();
    }, function () { b.disabled = false; alert('Izin lokasi ditolak. Pilih landmark atau ketik lokasi tujuan.'); }, {timeout: 10000});
  });
})();
</script>
@endpush
@endonce
