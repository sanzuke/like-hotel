@extends('layouts.panel')
@section('title', 'Edit Apartment')
@section('content')
<h1>Edit Apartment</h1>
<a class="btn-outline" href="{{ route('admin.apartments.index') }}">← Kembali</a>

<div class="form-box" style="max-width:100%;margin:20px 0">
  <form method="post" action="{{ route('admin.apartments.update', $apt) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <label>Owner</label>
    <select name="owner_id">
      <option value="">Like Hotel / Milik Sendiri</option>
      @foreach($owners as $o)<option value="{{ $o->id }}" @selected(old('owner_id', $apt->owner_id) == $o->id)>{{ $o->name }}</option>@endforeach
    </select>
    <label>Nama Apartment</label><input name="name" value="{{ old('name', $apt->name) }}" required>
    <label>Lokasi</label><input name="location" value="{{ old('location', $apt->location) }}" required>
    <label>Tipe</label><input name="type" value="{{ old('type', $apt->type) }}" required>
    <label>Harga per Malam</label><input type="number" min="0" name="price_per_night" value="{{ old('price_per_night', (int) $apt->price_per_night) }}" required>
    <label>Fasilitas</label><textarea name="facilities">{{ old('facilities', $apt->facilities) }}</textarea>
    <label>Deskripsi Apartment</label><textarea name="description">{{ old('description', $apt->description) }}</textarea>
    <label>Status Apartment</label>
    <select name="status">
      @foreach(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'maintenance' => 'Maintenance'] as $v => $l)
        <option value="{{ $v }}" @selected(old('status', $apt->status) === $v)>{{ $l }}</option>
      @endforeach
    </select>
    <label>Tambah Foto Galeri</label>
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small>Maks. 10 foto (JPG/PNG/WEBP, 4 MB per foto).</small>
    <br><br>
    <button class="btn">Simpan Perubahan</button>
  </form>
</div>

<h2>Galeri Foto</h2>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px">
  @foreach($images as $img)
    <div class="card" style="padding:10px">
      <img src="{{ asset('storage/apartments/'.$img->image) }}" style="width:100%;height:150px;object-fit:cover;border-radius:12px" alt="">
      @if($apt->image === $img->image)
        <p><b style="color:#087a35">Cover Utama</b></p>
      @else
        <form class="inline-form" method="post" action="{{ route('admin.apartments.cover', [$apt, $img]) }}">@csrf<button class="btn-outline">Jadikan Cover</button></form>
      @endif
      <form class="inline-form" method="post" action="{{ route('admin.apartments.images.destroy', [$apt, $img]) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
    </div>
  @endforeach
</div>
@endsection
