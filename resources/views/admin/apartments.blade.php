@extends('layouts.admin')
@section('page', 'Kelola Apartment')

@section('content')
<h1>Kelola Apartment</h1>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <h3>Tambah Apartment</h3>
  <form method="post" action="{{ route('admin.apartments.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.partials.apartment-fields', ['apt' => null])
    <label>Foto Apartment</label>
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small>Bisa pilih lebih dari 1 foto (JPG/PNG/WEBP, maks 4 MB). Foto pertama menjadi cover.</small>
    <br><br><button class="btn">Simpan</button>
  </form>
</div>

<table class="table">
  <tr><th>Foto</th><th>Nama</th><th>Owner</th><th>Lokasi</th><th>Tipe</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
  @foreach($apartments as $a)
    <tr>
      <td>@if($a->image)<img src="{{ $a->coverUrl() }}" alt="" style="width:90px;height:65px;object-fit:cover;border-radius:8px">@else - @endif</td>
      <td>{{ $a->name }}</td><td>{{ $a->owner_name }}</td><td>{{ $a->location }}</td><td>{{ $a->type }}</td>
      <td>{{ rupiah($a->price_per_night) }}</td><td>{{ $a->status }}</td>
      <td><a class="btn" href="{{ route('admin.apartments.edit', $a) }}">Edit</a></td>
    </tr>
  @endforeach
</table>
@endsection
