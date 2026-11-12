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
    <input type="date" name="checkin" min="{{ today()->toDateString() }}" value="{{ $filters['checkin'] ?? '' }}" title="Check-in" aria-label="Check-in">
    <input type="date" name="checkout" min="{{ today()->toDateString() }}" value="{{ $filters['checkout'] ?? '' }}" title="Check-out" aria-label="Check-out">
    <button class="btn"><i class="ms">search</i> Cari Apartment</button>

    @if($advanced)
      <select name="type" style="grid-column:span 1">
        <option value="">Semua tipe</option>
        @foreach($types as $t)<option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>{{ $t }}</option>@endforeach
      </select>
      <input type="number" min="0" step="10000" name="max_price" value="{{ $filters['max_price'] ?? '' }}" placeholder="Harga maks. / malam">
      <select name="sort">
        @foreach(['terbaru' => 'Terbaru', 'termurah' => 'Harga terendah', 'termahal' => 'Harga tertinggi'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['sort'] ?? 'terbaru') === $v)>{{ $l }}</option>
        @endforeach
      </select>
      <a class="btn-outline" href="{{ route('apartments.index') }}">Reset</a>
    @endif
  </form>
</div>
