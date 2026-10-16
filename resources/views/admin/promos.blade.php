@extends('layouts.panel')
@section('title', 'Promo Code')
@section('content')
<h1>Promo Code</h1>
<p class="muted">Kelola kode promo untuk diskon booking Like Hotel.</p>

<div class="form-box" style="max-width:100%;margin:20px 0">
  <h3>Tambah Promo</h3>
  <form method="post" action="{{ route('admin.promos.store') }}">
    @csrf
    <label>Kode Promo</label><input name="code" value="{{ old('code') }}" placeholder="Contoh: HEMAT10" required>
    <label>Tipe Diskon</label>
    <select name="type" required>
      <option value="percent" @selected(old('type') === 'percent')>Persen (%)</option>
      <option value="fixed" @selected(old('type') === 'fixed')>Nominal Rupiah</option>
    </select>
    <label>Nilai Diskon</label><input type="number" step="any" name="value" value="{{ old('value') }}" placeholder="Contoh: 10 atau 50000" required>
    <label>Tanggal Expired</label><input type="date" name="expired_at" value="{{ old('expired_at') }}">
    <small>Kosongkan jika promo tidak memiliki tanggal expired.</small>
    <br><br>
    <button class="btn">Simpan Promo</button>
  </form>
</div>

<h2>Daftar Promo</h2>
<table class="table">
  <tr><th>Kode</th><th>Tipe</th><th>Nilai</th><th>Status</th><th>Expired</th><th>Aksi</th></tr>
  @forelse($promos as $p)
    <tr>
      <td><b>{{ $p->code }}</b></td>
      <td>{{ $p->type === 'percent' ? 'Persen' : 'Nominal Rupiah' }}</td>
      <td>{{ $p->type === 'percent' ? number_format($p->value, 0, ',', '.').'%' : \App\Support\Fmt::rupiah($p->value) }}</td>
      <td><b style="color:{{ \App\Support\Fmt::statusColor($p->status) }}">{{ strtoupper($p->status) }}</b></td>
      <td>{{ $p->expired_at?->format('d-m-Y') ?? '-' }}</td>
      <td>
        <form class="inline-form" method="post" action="{{ route('admin.promos.toggle', $p) }}">@csrf @method('PATCH')<button class="btn-outline">{{ $p->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
        <form class="inline-form" method="post" action="{{ route('admin.promos.destroy', $p) }}" onsubmit="return confirm('Hapus promo ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </td>
    </tr>
  @empty
    <tr><td colspan="6">Belum ada promo.</td></tr>
  @endforelse
</table>
@endsection
