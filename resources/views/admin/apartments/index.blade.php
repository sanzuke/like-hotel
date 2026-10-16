@extends('layouts.panel')
@section('title', 'Kelola Apartment')
@section('content')
<h1>Kelola Apartment</h1>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <h3>Tambah Apartment</h3>
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
    <br><br>
    <button class="btn">Simpan</button>
  </form>
</div>

<table class="table">
  <tr><th>Foto</th><th>Nama</th><th>Owner</th><th>Lokasi</th><th>Tipe</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
  @foreach($apartments as $a)
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
  @endforeach
</table>
@endsection
