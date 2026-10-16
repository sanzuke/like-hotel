@extends('layouts.panel')
@section('title', 'Unit Saya')
@section('content')
<h1>Unit Saya</h1>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <h3>Tambah Unit</h3>
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
    <br><br>
    <button class="btn">Simpan Unit</button>
  </form>
</div>

<table class="table">
  <tr><th>Foto</th><th>Nama</th><th>Lokasi</th><th>Tipe</th><th>Harga</th><th>Status</th></tr>
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
    <tr><td colspan="6">Belum ada unit.</td></tr>
  @endforelse
</table>
@endsection
