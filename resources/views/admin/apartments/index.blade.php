@extends('layouts.panel')
@section('title', 'Kelola Apartment')
@section('content')
<div class="page-head">
  <h1>Kelola Apartment</h1>
  <button type="button" class="btn" data-open-dialog="apartment-create">+ Tambah Apartment</button>
</div>

<form id="apartment-filter" method="get" action="{{ route('admin.apartments.index') }}"></form>
<table class="table">
  <tr><th>Foto</th><th>Nama</th><th>Owner</th><th>Lokasi</th><th>Tipe</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
  <tr class="filter-row">
    <td></td>
    <td><input form="apartment-filter" name="name" value="{{ $filters['name'] ?? '' }}" placeholder="Cari nama…" aria-label="Filter nama"></td>
    <td>
      <select form="apartment-filter" name="owner" onchange="this.form.submit()" aria-label="Filter owner">
        <option value="">Semua</option>
        <option value="none" @selected(($filters['owner'] ?? '') === 'none')>Like Hotel</option>
        @foreach($filterOwners as $o)<option value="{{ $o->id }}" @selected(($filters['owner'] ?? '') === (string) $o->id)>{{ $o->name }}</option>@endforeach
      </select>
    </td>
    <td><input form="apartment-filter" name="location" value="{{ $filters['location'] ?? '' }}" placeholder="Cari lokasi…" aria-label="Filter lokasi"></td>
    <td>
      <select form="apartment-filter" name="type" onchange="this.form.submit()" aria-label="Filter tipe">
        <option value="">Semua</option>
        @foreach($types as $t)<option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>{{ $t }}</option>@endforeach
      </select>
    </td>
    <td></td>
    <td>
      <select form="apartment-filter" name="status" onchange="this.form.submit()" aria-label="Filter status">
        <option value="">Semua</option>
        @foreach(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'maintenance' => 'Maintenance'] as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>@endforeach
      </select>
    </td>
    <td class="filter-actions">
      <button form="apartment-filter" class="btn-outline">Cari</button>
      @if(array_filter($filters))<a href="{{ route('admin.apartments.index') }}" class="filter-reset">Reset</a>@endif
    </td>
  </tr>
  @forelse($apartments as $a)
    <tr>
      <td>@if($a->image)<img src="{{ asset('storage/apartments/'.$a->image) }}" style="width:90px;height:65px;object-fit:cover;border-radius:8px" alt="">@else - @endif</td>
      <td>{{ $a->name }}</td>
      <td>{{ $a->owner_name }}</td>
      <td>{{ $a->location }}</td>
      <td>{{ $a->type }}</td>
      <td>{{ \App\Support\Fmt::rupiah($a->price_per_night) }}</td>
      <td>{{ $a->status }}</td>
      <td><a class="btn" href="{{ route('admin.apartments.edit', $a) }}">Edit</a></td>
    </tr>
  @empty
    <tr><td colspan="8" style="text-align:center;color:#6b7280">
      @if(array_filter($filters))Tidak ada apartment yang cocok dengan filter. <a href="{{ route('admin.apartments.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada apartment. Klik "Tambah Apartment" untuk menambahkan.@endif
    </td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $apartments])

<x-modal id="apartment-create" title="Tambah Apartment">
  <form method="post" action="{{ route('admin.apartments.store') }}" enctype="multipart/form-data">
    @csrf
    <label>Owner</label>
    <select name="owner_id">
      <option value="">Like Hotel / Milik Sendiri</option>
      @foreach($owners as $o)<option value="{{ $o->id }}" @selected(old('owner_id') == $o->id)>{{ $o->name }}</option>@endforeach
    </select>
    <label>Nama Apartment</label><input name="name" value="{{ old('name') }}" required>
    <label>Lokasi</label><input name="location" value="{{ old('location') }}" required>
    <label>Tipe</label><input name="type" value="{{ old('type') }}" placeholder="Studio / 1 Bedroom / 2 Bedroom" required>
    <label>Harga per Malam</label><input type="number" min="0" name="price_per_night" value="{{ old('price_per_night') }}" required>
    <label>Fasilitas</label><textarea name="facilities" placeholder="AC, WiFi, TV, Parkir">{{ old('facilities') }}</textarea>
    <label>Foto Apartment</label>
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small>Maks. 10 foto (JPG/PNG/WEBP, 4 MB per foto). Foto pertama menjadi cover.</small>
    <div class="modal-actions">
      <button type="button" class="btn-outline" data-close-dialog>Batal</button>
      <button class="btn">Simpan</button>
    </div>
  </form>
</x-modal>
@endsection
