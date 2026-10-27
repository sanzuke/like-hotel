@extends('layouts.panel')
@section('title', 'Unit Dijual')
@section('content')
<div class="page-head">
  <h1>Unit Dijual</h1>
  <button type="button" class="btn" data-open-dialog="sale-unit-create">+ Tambah Unit Dijual</button>
</div>
<p class="muted">Kelola iklan unit apartemen yang dijual. Unit berstatus aktif tampil di halaman Jual Beli.</p>
<div class="stats">
  <div class="stat"><p>Total Unit</p><h2>{{ $stats['total'] }}</h2></div>
  <div class="stat"><p>Aktif Tayang</p><h2>{{ $stats['aktif'] }}</h2></div>
  <div class="stat"><p>Terjual</p><h2>{{ $stats['terjual'] }}</h2></div>
  <div class="stat"><p>Nilai Unit Aktif</p><h2>{{ \App\Support\Fmt::rupiah($stats['nilai']) }}</h2></div>
</div>
<br>

<form id="sale-unit-filter" method="get" action="{{ route('admin.sale-units.index') }}"></form>
<table class="table">
  <tr><th>Foto</th><th>Judul</th><th>Apartment</th><th>Harga</th><th>Penjual</th><th>Status</th><th>Aksi</th></tr>
  <tr class="filter-row">
    <td></td>
    <td><input form="sale-unit-filter" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul / lokasi…" aria-label="Filter judul"></td>
    <td><input form="sale-unit-filter" name="apartment" value="{{ $filters['apartment'] ?? '' }}" placeholder="Cari apartment…" aria-label="Filter apartment"></td>
    <td></td>
    <td><input form="sale-unit-filter" name="seller" value="{{ $filters['seller'] ?? '' }}" placeholder="Nama / telepon…" aria-label="Filter penjual"></td>
    <td>
      <select form="sale-unit-filter" name="status" onchange="this.form.submit()" aria-label="Filter status">
        <option value="">Semua</option>
        @foreach(['aktif', 'terjual', 'nonaktif'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ $s }}</option>@endforeach
      </select>
    </td>
    <td class="filter-actions">
      <button form="sale-unit-filter" class="btn-outline">Cari</button>
      @if($filters)<a href="{{ route('admin.sale-units.index') }}" class="filter-reset">Reset</a>@endif
    </td>
  </tr>
  @forelse($rows as $r)
    <tr>
      <td>@if($r->image)<img class="su-thumb" src="{{ asset('storage/sale_units/'.$r->image) }}" alt="">@else<span class="su-thumb su-none">-</span>@endif</td>
      <td><b>{{ $r->title }}</b>@if($r->status === 'aktif')<br><a class="su-link" target="_blank" href="{{ route('sale-units.show', $r) }}">Lihat di situs ↗</a>@endif</td>
      <td>{{ $r->apartment_name }}<br><small>{{ $r->location }}</small></td>
      <td><b class="su-price">{{ \App\Support\Fmt::rupiah($r->price) }}</b></td>
      <td>{{ $r->seller_name }}<br><small>{{ $r->seller_phone }}</small></td>
      <td>
        <form class="inline-form" method="post" action="{{ route('admin.sale-units.status', $r) }}">
          @csrf @method('PATCH')
          <select name="status" class="su-status su-{{ $r->status }}" onchange="this.form.submit()">
            @foreach(['aktif', 'terjual', 'nonaktif'] as $s)<option value="{{ $s }}" @selected($r->status === $s)>{{ $s }}</option>@endforeach
          </select>
        </form>
      </td>
      <td>
        <a class="btn-outline" href="{{ route('admin.sale-units.edit', $r) }}">Edit</a>
        <form class="inline-form" method="post" action="{{ route('admin.sale-units.destroy', $r) }}" onsubmit="return confirm('Hapus unit ini beserta fotonya?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </td>
    </tr>
  @empty
    <tr><td colspan="7" style="text-align:center;color:#6b7280">
      @if($filters)Tidak ada data yang cocok dengan filter. <a href="{{ route('admin.sale-units.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada unit dijual. Klik "Tambah Unit Dijual" untuk menambahkan.@endif
    </td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $rows])

<x-modal id="sale-unit-create" title="Tambah Unit Dijual">
  <form method="post" action="{{ route('admin.sale-units.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.sale-units._form')
    <div class="modal-actions">
      <button type="button" class="btn-outline" data-close-dialog>Batal</button>
      <button class="btn">Simpan</button>
    </div>
  </form>
</x-modal>
@endsection
