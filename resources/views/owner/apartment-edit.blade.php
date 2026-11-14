@extends('layouts.panel')
@section('title', 'Kelola Unit')
@section('content')
<h1>Kelola Unit: {{ $apt->name }}</h1>
<a class="btn-outline" href="{{ route('owner.apartments.index') }}">← Kembali</a>

@if($apt->status === 'nonaktif')<div class="alert warn" style="margin-top:16px">Unit ini menunggu verifikasi admin. Setelah diaktifkan, unit tampil di situs dan bisa dibooking.</div>@endif
@if($apt->isSponsored())<div class="alert" style="margin-top:16px">Unit ini sedang diiklankan Like Hotel (tampil di posisi atas){{ $apt->ad_until ? ' sampai '.\App\Support\Fmt::date($apt->ad_until) : '' }}.</div>@endif

<div class="form-box" style="max-width:100%;margin:20px 0">
  <h3>Harga, Ketersediaan &amp; Detail</h3>
  <form method="post" action="{{ route('owner.apartments.update', $apt) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <label>Nama Apartment</label><input name="name" value="{{ old('name', $apt->name) }}" required>
    <label>Lokasi</label><input name="location" value="{{ old('location', $apt->location) }}" required>
    @include('partials.map-picker', ['id' => 'edit', 'lat' => $apt->lat, 'lng' => $apt->lng])
    <label>Kategori</label>
    <select name="category" required>
      @foreach(\App\Models\Apartment::CATEGORIES as $v => $l)<option value="{{ $v }}" @selected(old('category', $apt->category) === $v)>{{ $l }}</option>@endforeach
    </select>
    <label>Tipe</label><input name="type" value="{{ old('type', $apt->type) }}" required>
    <label>Harga per Malam</label><input type="number" min="0" name="price_per_night" value="{{ old('price_per_night', (int) $apt->price_per_night) }}" required>
    <small>Perubahan harga hanya berlaku untuk booking baru.</small>
    @if($apt->status !== 'nonaktif')
      <label>Status Ketersediaan</label>
      <select name="status">
        @foreach(['aktif' => 'Aktif (bisa dibooking)', 'maintenance' => 'Maintenance (tidak bisa dibooking)'] as $v => $l)<option value="{{ $v }}" @selected(old('status', $apt->status) === $v)>{{ $l }}</option>@endforeach
      </select>
    @endif
    <label>Fasilitas</label><textarea name="facilities">{{ old('facilities', $apt->facilities) }}</textarea>
    <label>Deskripsi</label><textarea name="description">{{ old('description', $apt->description) }}</textarea>
    <label>Tambah Foto</label>
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small>Maks. 10 foto (JPG/PNG/WEBP, 4 MB per foto).</small>
    <br><br><button class="btn">Simpan Perubahan</button>
  </form>
</div>

<div class="form-box" style="max-width:100%;margin:20px 0">
  <h3>Tutup Tanggal (Tidak Tersedia)</h3>
  <form method="post" action="{{ route('owner.apartments.blocks.store', $apt) }}">
    @csrf
    <label>Dari Tanggal</label><input type="date" name="date_from" value="{{ old('date_from') }}" min="{{ today()->toDateString() }}" required>
    <label>Sampai Tanggal (tidak termasuk malam terakhir, seperti check-out)</label><input type="date" name="date_to" value="{{ old('date_to') }}" required>
    @error('date_from')<div class="field-error">{{ $message }}</div>@enderror
    @error('date_to')<div class="field-error">{{ $message }}</div>@enderror
    <label>Catatan</label><input name="note" value="{{ old('note') }}" placeholder="Dipakai pribadi, renovasi, dll.">
    <button class="btn">Tutup Tanggal</button>
  </form>
  @if($blocks->isNotEmpty())
    <table class="table" style="margin-top:16px">
      <tr><th>Dari</th><th>Sampai</th><th>Catatan</th><th></th></tr>
      @foreach($blocks as $b)
        <tr><td>{{ \App\Support\Fmt::date($b->date_from) }}</td><td>{{ \App\Support\Fmt::date($b->date_to) }}</td><td>{{ $b->note }}</td>
          <td><form class="inline-form" method="post" action="{{ route('owner.apartments.blocks.destroy', [$apt, $b]) }}">@csrf @method('DELETE')<button class="btn-outline">Buka</button></form></td></tr>
      @endforeach
    </table>
  @endif
</div>

<h3>Booking Mendatang</h3>
<table class="table">
  <tr><th>Kode</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr>
  @forelse($bookings as $b)
    <tr><td>{{ $b->booking_code }}</td><td>{{ $b->checkin_label }}</td><td>{{ $b->checkout_label }}</td><td>{{ $b->booking_status }}</td></tr>
  @empty
    <tr><td colspan="4" style="text-align:center;color:#6b7280">Belum ada booking mendatang.</td></tr>
  @endforelse
</table>

<h3>Galeri Foto</h3>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px">
  @foreach($images as $img)
    <div class="card" style="padding:10px">
      <img src="{{ asset('storage/apartments/'.$img->image) }}" style="width:100%;height:150px;object-fit:cover;border-radius:12px" alt="">
      @if($apt->image === $img->image)
        <p><b style="color:#087a35">Cover Utama</b></p>
      @else
        <form class="inline-form" method="post" action="{{ route('owner.apartments.cover', [$apt, $img]) }}">@csrf<button class="btn-outline">Jadikan Cover</button></form>
      @endif
      <form class="inline-form" method="post" action="{{ route('owner.apartments.images.destroy', [$apt, $img]) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
    </div>
  @endforeach
</div>
@endsection
