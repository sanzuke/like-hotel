@extends('layouts.public')
@section('title', 'Titip Jual Apartment')
@section('content')
<div class="form-box">
  <h2>Titip Jual Apartment</h2>
  <p class="muted">Isi data unit Anda. Tim Like Hotel akan menghubungi untuk survei dan penawaran.</p>
  @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert danger">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>@endif
  <form method="post" action="{{ route('sale-request.store') }}">
    @csrf
    <label>Nama Pemilik</label><input name="owner_name" value="{{ old('owner_name') }}" placeholder="Nama lengkap pemilik unit" required>
    <label>No WhatsApp</label><input name="owner_phone" value="{{ old('owner_phone') }}" placeholder="Contoh: 082129248953" required>
    <label>Nama Apartment</label><input name="apartment_name" value="{{ old('apartment_name') }}" placeholder="Contoh: Easton Park Residence" required>
    <label>Lokasi</label><input name="location" value="{{ old('location') }}" placeholder="Contoh: Jatinangor - Bandung" required>
    <label>Tipe Unit</label><input name="unit_type" value="{{ old('unit_type') }}" placeholder="Studio / 1 Bedroom / 2 Bedroom">
    <label>Luas</label><input name="size" value="{{ old('size') }}" placeholder="Contoh: 34 m2">
    <label>Harga yang Diharapkan</label><input type="number" min="0" name="expected_price" value="{{ old('expected_price') }}">
    <label>Catatan</label><textarea name="notes">{{ old('notes') }}</textarea>
    @include('partials.turnstile')
    <button class="btn">Kirim Pengajuan</button>
  </form>
</div>
@endsection
