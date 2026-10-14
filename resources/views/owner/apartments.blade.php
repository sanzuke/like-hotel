@extends('layouts.owner')
@section('page', 'Unit Saya')

@section('content')
<h1>Unit Apartment Saya</h1>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <h3>Tambah Unit</h3>
  <form method="post" action="{{ route('owner.apartments.store') }}" enctype="multipart/form-data">
    @csrf
    <input name="name" value="{{ old('name') }}" placeholder="Nama apartment" required>
    <input name="location" value="{{ old('location') }}" placeholder="Lokasi" required>
    <input name="type" value="{{ old('type') }}" placeholder="Tipe" required>
    <input name="price_per_night" type="number" min="1" value="{{ old('price_per_night') }}" placeholder="Harga per malam" required>
    <textarea name="facilities" placeholder="Fasilitas">{{ old('facilities') }}</textarea>
    <label>Foto Unit (JPG/PNG/WEBP, maks 4 MB)</label>
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
    <button class="btn">Simpan Unit</button>
  </form>
</div>

<table class="table">
  <tr><th>Foto</th><th>Nama</th><th>Lokasi</th><th>Tipe</th><th>Harga</th><th>Status</th></tr>
  @foreach($apartments as $a)
    <tr>
      <td>@if($a->image)<img src="{{ $a->coverUrl() }}" alt="" style="width:80px;height:55px;object-fit:cover;border-radius:8px">@else - @endif</td>
      <td>{{ $a->name }}</td><td>{{ $a->location }}</td><td>{{ $a->type }}</td>
      <td>{{ rupiah($a->price_per_night) }}</td><td>{{ $a->status }}</td>
    </tr>
  @endforeach
</table>
@endsection
