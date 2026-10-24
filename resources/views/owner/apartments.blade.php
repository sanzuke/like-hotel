@extends('layouts.panel')
@section('title', 'Unit Saya')
@section('content')
<div class="page-head">
  <h1>Unit Saya</h1>
  <button type="button" class="btn" data-open-dialog="owner-apartment-create">+ Tambah Unit</button>
</div>

<form id="owner-apartment-filter" method="get" action="{{ route('owner.apartments.index') }}"></form>
<table class="table">
  <tr><th>Foto</th><th>Nama</th><th>Lokasi</th><th>Tipe</th><th>Harga</th><th>Status</th></tr>
  <tr class="filter-row">
    <td class="filter-actions">
      <button form="owner-apartment-filter" class="btn-outline">Cari</button>
      @if(array_filter($filters))<a href="{{ route('owner.apartments.index') }}" class="filter-reset">Reset</a>@endif
    </td>
    <td><input form="owner-apartment-filter" name="name" value="{{ $filters['name'] ?? '' }}" placeholder="Cari nama…" aria-label="Filter nama"></td>
    <td><input form="owner-apartment-filter" name="location" value="{{ $filters['location'] ?? '' }}" placeholder="Cari lokasi…" aria-label="Filter lokasi"></td>
    <td>
      <select form="owner-apartment-filter" name="type" onchange="this.form.submit()" aria-label="Filter tipe">
        <option value="">Semua</option>
        @foreach($types as $t)<option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>{{ $t }}</option>@endforeach
      </select>
    </td>
    <td></td>
    <td>
      <select form="owner-apartment-filter" name="status" onchange="this.form.submit()" aria-label="Filter status">
        <option value="">Semua</option>
        @foreach(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif / menunggu verifikasi', 'maintenance' => 'Maintenance'] as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>@endforeach
      </select>
    </td>
  </tr>
  @forelse($apartments as $a)
    <tr>
      <td>@if($a->image)<img src="{{ asset('storage/apartments/'.$a->image) }}" style="width:90px;height:65px;object-fit:cover;border-radius:8px" alt="">@else - @endif</td>
      <td>{{ $a->name }}</td>
      <td>{{ $a->location }}</td>
      <td>{{ $a->type }}</td>
      <td>{{ \App\Support\Fmt::rupiah($a->price_per_night) }}</td>
      <td><b style="color:{{ \App\Support\Fmt::statusColor($a->status) }}">{{ $a->status === 'nonaktif' ? 'menunggu verifikasi' : $a->status }}</b></td>
    </tr>
  @empty
    <tr><td colspan="6" style="text-align:center;color:#6b7280">
      @if(array_filter($filters))Tidak ada data yang cocok dengan filter. <a href="{{ route('owner.apartments.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada unit. Klik "Tambah Unit" untuk menambahkan.@endif
    </td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $apartments])

<x-modal id="owner-apartment-create" title="Tambah Unit">
  <div class="alert">Unit baru akan tampil di situs setelah diverifikasi dan diaktifkan admin.</div>
  <form method="post" action="{{ route('owner.apartments.store') }}" enctype="multipart/form-data">
    @csrf
    <label>Nama Apartment</label><input name="name" value="{{ old('name') }}" required>
    <label>Lokasi</label><input name="location" value="{{ old('location') }}" required>
    <label>Tipe</label><input name="type" value="{{ old('type') }}" placeholder="Studio / 1 Bedroom / 2 Bedroom" required>
    <label>Harga per Malam</label><input type="number" min="0" name="price_per_night" value="{{ old('price_per_night') }}" required>
    <label>Fasilitas</label><textarea name="facilities">{{ old('facilities') }}</textarea>
    <label>Deskripsi</label><textarea name="description">{{ old('description') }}</textarea>
    <label>Foto</label>
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small>Maks. 10 foto (JPG/PNG/WEBP, 4 MB per foto).</small>
    <div class="modal-actions">
      <button type="button" class="btn-outline" data-close-dialog>Batal</button>
      <button class="btn">Simpan Unit</button>
    </div>
  </form>
</x-modal>
@endsection
