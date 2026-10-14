@extends('layouts.admin')
@section('page', 'Promo Code')

@section('content')
<h1>Promo Code</h1>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <h3>Buat Promo</h3>
  <form method="post" action="{{ route('admin.promos.store') }}">
    @csrf
    <label>Kode</label><input name="code" value="{{ old('code') }}" placeholder="HEMAT10" required maxlength="50">
    <label>Tipe</label>
    <select name="type"><option value="percent" @selected(old('type') === 'percent')>Persen (%)</option><option value="fixed" @selected(old('type') === 'fixed')>Nominal (Rp)</option></select>
    <label>Nilai</label><input name="value" type="number" step="0.01" min="0.01" value="{{ old('value') }}" required>
    <label>Berlaku sampai (opsional)</label><input name="expired_at" type="date" value="{{ old('expired_at') }}">
    <br><br><button class="btn">Simpan Promo</button>
  </form>
</div>

<table class="table">
  <tr><th>Kode</th><th>Tipe</th><th>Nilai</th><th>Kadaluarsa</th><th>Status</th><th>Aksi</th></tr>
  @foreach($promos as $p)
    <tr>
      <td><b>{{ $p->code }}</b></td>
      <td>{{ $p->type === 'percent' ? 'Persen' : 'Nominal' }}</td>
      <td>{{ $p->type === 'percent' ? rtrim(rtrim(number_format($p->value, 2), '0'), '.').'%' : rupiah($p->value) }}</td>
      <td>{{ $p->expired_at?->format('d-m-Y') ?? '-' }}</td>
      <td>{{ $p->status }}@if($p->status === 'aktif' && ! $p->isUsable()) <small>(kedaluwarsa)</small>@endif</td>
      <td class="actions">
        <form class="inline" method="post" action="{{ route('admin.promos.toggle', $p) }}">@csrf<button class="btn-outline">{{ $p->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
        <form class="inline" method="post" action="{{ route('admin.promos.destroy', $p) }}" onsubmit="return confirm('Hapus promo ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </td>
    </tr>
  @endforeach
</table>
@endsection
