@extends('layouts.site')
@section('title', 'Titip Jual Apartment - Like Hotel')

@section('content')
<section class="booking-page">
  <div class="booking-form-card">
    <span class="badge">Titip Jual Apartment</span>
    <h1>Ajukan Unit untuk Dijual</h1>
    <p class="muted">Tim Properti Like Hotel akan membantu proses pemasaran, konsultasi, survei, negosiasi, hingga transaksi.</p>

    @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ route('sale.request.store') }}">
      @csrf
      <label>Nama Pemilik</label>
      <input name="owner_name" value="{{ old('owner_name') }}" placeholder="Nama lengkap pemilik unit" required>

      <label>No WhatsApp</label>
      <input name="owner_phone" value="{{ old('owner_phone') }}" placeholder="Contoh: 082129248953" required>

      <label>Nama Apartment</label>
      <input name="apartment_name" value="{{ old('apartment_name') }}" placeholder="Contoh: Easton Park Residence" required>

      <label>Lokasi</label>
      <input name="location" value="{{ old('location') }}" placeholder="Contoh: Jatinangor - Bandung" required>

      <label>Tipe Unit</label>
      <input name="unit_type" value="{{ old('unit_type') }}" placeholder="Studio / 1 Bedroom / 2 Bedroom">

      <label>Luas Unit</label>
      <input name="size" value="{{ old('size') }}" placeholder="Contoh: 24 m2">

      <label>Harga Jual Harapan</label>
      <input type="number" min="0" name="expected_price" value="{{ old('expected_price') }}" placeholder="Contoh: 350000000">

      <label>Catatan Tambahan</label>
      <textarea name="notes" placeholder="Contoh: full furnished, lantai 12, sertifikat lengkap">{{ old('notes') }}</textarea>

      <button class="btn btn-full">Kirim Pengajuan</button>
    </form>
  </div>

  <div class="booking-side-card">
    <div class="booking-side-body">
      <h2>🤝 Didampingi Tim Like Hotel</h2>
      <div class="facility-box">
        <p>✅ Konsultasi harga jual</p>
        <p>✅ Bantuan pemasaran unit</p>
        <p>✅ Komunikasi calon pembeli satu pintu</p>
        <p>✅ Pendampingan survei unit</p>
        <p>✅ Negosiasi hingga transaksi selesai</p>
      </div>
      <hr>
      <h3>Alur Titip Jual</h3>
      <p>1. Isi data unit</p>
      <p>2. Tim Like Hotel menghubungi pemilik</p>
      <p>3. Data unit diverifikasi</p>
      <p>4. Unit tampil di marketplace</p>
      <p>5. Tim Like Hotel mendampingi proses penjualan</p>
    </div>
  </div>
</section>
@endsection
