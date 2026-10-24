@extends('layouts.panel')
@section('title', 'Promo Code')
@section('content')
<div class="page-head">
  <h1>Promo Code</h1>
  <button type="button" class="btn" data-open-dialog="promo-create">+ Tambah Promo</button>
</div>
<p class="muted">Kelola kode promo untuk diskon booking Like Hotel.</p>

<form id="promo-filter" method="get" action="{{ route('admin.promos.index') }}"></form>
<table class="table">
  <tr><th>Kode</th><th>Tipe</th><th>Nilai</th><th>Status</th><th>Expired</th><th>Aksi</th></tr>
  <tr class="filter-row">
    <td><input form="promo-filter" name="code" value="{{ $filters['code'] ?? '' }}" placeholder="Cari kode…" aria-label="Filter kode"></td>
    <td>
      <select form="promo-filter" name="type" onchange="this.form.submit()" aria-label="Filter tipe">
        <option value="">Semua</option>
        <option value="percent" @selected(($filters['type'] ?? '') === 'percent')>Persen</option>
        <option value="fixed" @selected(($filters['type'] ?? '') === 'fixed')>Nominal Rupiah</option>
      </select>
    </td>
    <td></td>
    <td>
      <select form="promo-filter" name="status" onchange="this.form.submit()" aria-label="Filter status">
        <option value="">Semua</option>
        <option value="aktif" @selected(($filters['status'] ?? '') === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected(($filters['status'] ?? '') === 'nonaktif')>Nonaktif</option>
      </select>
    </td>
    <td></td>
    <td class="filter-actions">
      <button form="promo-filter" class="btn-outline">Cari</button>
      @if($filters)<a href="{{ route('admin.promos.index') }}" class="filter-reset">Reset</a>@endif
    </td>
  </tr>
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
    <tr><td colspan="6" style="text-align:center;color:#6b7280">
      @if($filters)Tidak ada data yang cocok dengan filter. <a href="{{ route('admin.promos.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada promo. Klik "Tambah Promo" untuk menambahkan.@endif
    </td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $promos])

<x-modal id="promo-create" title="Tambah Promo">
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
    <div class="modal-actions">
      <button type="button" class="btn-outline" data-close-dialog>Batal</button>
      <button class="btn">Simpan Promo</button>
    </div>
  </form>
</x-modal>
@endsection
