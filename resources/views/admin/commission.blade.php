@extends('layouts.panel')
@section('title', 'Komisi & Settlement')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Komisi & Settlement Owner</h1>
<div class="alert">Pembayaran masuk ke Like Hotel. Komisi sewa saat ini {{ \App\Support\Commission::rentPercent() }}%, sisanya hak owner.</div>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <h3>Pengaturan Komisi</h3>
  <form method="post" action="{{ route('admin.commission.update') }}">
    @csrf @method('PUT')
    <label>Komisi Sewa (%)</label>
    <input type="number" step="0.01" min="0" max="100" name="rent_percent" value="{{ old('rent_percent', \App\Support\Commission::rentPercent()) }}" required>
    @error('rent_percent')<div class="field-error">{{ $message }}</div>@enderror
    <label>Komisi Jual/Beli Default (%)</label>
    <input type="number" step="0.01" min="0" max="100" name="sale_percent" value="{{ old('sale_percent', \App\Support\Commission::salePercent()) }}" required>
    @error('sale_percent')<div class="field-error">{{ $message }}</div>@enderror
    <small>Komisi sewa berlaku untuk booking baru; booking yang sudah ada tidak berubah. Komisi jual/beli menjadi nilai awal form closing dan tetap bisa diubah per transaksi.</small>
    <br><br><button class="btn">Simpan Pengaturan</button>
  </form>
</div>
<table class="table">
  <tr><th>Owner</th><th>WA</th><th>Booking</th><th>Total Lunas</th><th>Komisi Like Hotel</th><th>Hak Owner</th><th>Proses/Sudah Cair</th><th>Saldo Tersedia</th></tr>
  @foreach($rows as $r)
    <tr>
      <td>{{ $r->owner->name }}</td>
      <td>{{ $r->owner->phone }}</td>
      <td>{{ $r->bookings }}</td>
      <td>{{ Fmt::rupiah($r->gross) }}</td>
      <td>{{ Fmt::rupiah($r->commission) }}</td>
      <td><b>{{ Fmt::rupiah($r->earnings) }}</b></td>
      <td>{{ Fmt::rupiah($r->requested) }}</td>
      <td><b>{{ Fmt::rupiah($r->balance) }}</b></td>
    </tr>
  @endforeach
</table>
@endsection
