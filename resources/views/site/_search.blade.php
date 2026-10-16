@php $advanced = $advanced ?? false; $filters = $filters ?? []; @endphp
<form method="get" action="{{ route('apartments.index') }}" class="search search-wide" style="{{ $advanced ? 'grid-template-columns:2fr 1fr 1fr auto' : '' }}">
  <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama atau lokasi, contoh: Jatinangor">
  <input type="date" name="checkin" min="{{ today()->toDateString() }}" value="{{ $filters['checkin'] ?? '' }}" title="Check-in">
  <input type="date" name="checkout" min="{{ today()->toDateString() }}" value="{{ $filters['checkout'] ?? '' }}" title="Check-out">
  <button class="btn">Cari Apartment</button>

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
