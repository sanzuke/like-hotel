@extends('layouts.panel')
@section('title', 'Komisi Penjualan')
@php use App\Support\Fmt; @endphp
@section('content')
<div class="page-head">
  <h1>Komisi Penjualan</h1>
  <button type="button" class="btn" data-open-dialog="sale-commission-create">+ Catat Closing</button>
</div>
<div class="stats">
  <div class="stat"><p>Total Nilai Deal</p><h2>{{ Fmt::rupiah($totalDeal) }}</h2></div>
  <div class="stat"><p>Total Komisi Like Hotel</p><h2>{{ Fmt::rupiah($totalCommission) }}</h2></div>
  <div class="stat"><p>Bersih Penjual</p><h2>{{ Fmt::rupiah($totalNet) }}</h2></div>
</div>
<br>

<form id="sale-commission-filter" method="get" action="{{ route('admin.sale-commissions.index') }}"></form>
<table class="table">
  <tr><th>Tanggal</th><th>Penjual</th><th>Apartment</th><th>Harga Deal</th><th>Komisi</th><th>Biaya Lain</th><th>Bersih Penjual</th><th>Status</th><th>Aksi</th></tr>
  <tr class="filter-row">
    <td></td>
    <td><input form="sale-commission-filter" name="seller" value="{{ $filters['seller'] ?? '' }}" placeholder="Cari penjual…" aria-label="Filter penjual"></td>
    <td><input form="sale-commission-filter" name="apartment" value="{{ $filters['apartment'] ?? '' }}" placeholder="Cari apartment…" aria-label="Filter apartment"></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td>
      <select form="sale-commission-filter" name="status" onchange="this.form.submit()" aria-label="Filter status">
        <option value="">Semua</option>
        @foreach($statuses as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ $s }}</option>@endforeach
      </select>
    </td>
    <td class="filter-actions">
      <button form="sale-commission-filter" class="btn-outline">Cari</button>
      @if($filters)<a href="{{ route('admin.sale-commissions.index') }}" class="filter-reset">Reset</a>@endif
    </td>
  </tr>
  @forelse($rows as $r)
    <tr>
      <td>{{ \App\Support\Fmt::date($r->created_at) }}</td>
      <td>{{ $r->seller_name }}</td>
      <td>{{ $r->apartment_name }}</td>
      <td>{{ Fmt::rupiah($r->deal_price) }}</td>
      <td>{{ Fmt::rupiah($r->commission_amount) }}<br><small>{{ $r->commission_type === 'percent' ? (float) $r->commission_value.'%' : 'nominal' }}</small></td>
      <td>{{ Fmt::rupiah($r->other_cost) }}</td>
      <td><b>{{ Fmt::rupiah($r->seller_net_amount) }}</b></td>
      <td>{{ $r->status }}</td>
      <td>
        <a class="btn-outline" target="_blank" href="{{ route('admin.sale-commissions.invoice', $r) }}">Invoice</a>
        <a class="btn-outline" href="{{ route('admin.sale-commissions.edit', $r) }}">Edit</a>
        <form class="inline-form" method="post" action="{{ route('admin.sale-commissions.destroy', $r) }}" onsubmit="return confirm('Hapus data closing ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </td>
    </tr>
  @empty
    <tr><td colspan="9" style="text-align:center;color:#6b7280">
      @if($filters)Tidak ada data yang cocok dengan filter. <a href="{{ route('admin.sale-commissions.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada data closing. Klik "Catat Closing" untuk menambahkan.@endif
    </td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $rows])

<x-modal id="sale-commission-create" title="Catat Closing">
  <form method="post" action="{{ route('admin.sale-commissions.store') }}">
    @csrf
    @include('admin.sale-commissions._form')
    <div class="modal-actions">
      <button type="button" class="btn-outline" data-close-dialog>Batal</button>
      <button class="btn">Simpan</button>
    </div>
  </form>
</x-modal>
@endsection
