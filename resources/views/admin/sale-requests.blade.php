@extends('layouts.panel')
@section('title', 'CRM Titip Jual')
@php use App\Support\Fmt; @endphp
@push('head')<link rel="stylesheet" href="{{ asset('css/crm.css') }}">@endpush
@section('content')
<h1>CRM Titip Jual</h1>
<p class="muted">Kelola pengajuan titip jual dari pemilik unit, dari lead baru sampai closing.</p>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px">
  @foreach($stages as $key => $label)
    <span class="crm-badge">{{ $label }}: <b>{{ $counts[$key] ?? 0 }}</b></span>
  @endforeach
</div>

<form id="sale-filter" method="get" action="{{ route('admin.sale-requests.index') }}"></form>
<table class="table">
  <tr><th>Tanggal</th><th>Pemilik</th><th>Unit</th><th>Harga Harapan</th><th>Catatan</th><th>Status</th><th>Aksi</th></tr>
  <tr class="filter-row">
    <td></td>
    <td><input form="sale-filter" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama / HP…" aria-label="Filter pemilik"></td>
    <td><input form="sale-filter" name="unit" value="{{ $filters['unit'] ?? '' }}" placeholder="Unit / lokasi…" aria-label="Filter unit"></td>
    <td></td>
    <td></td>
    <td>
      <select form="sale-filter" name="status" onchange="this.form.submit()" aria-label="Filter status">
        <option value="">Semua</option>
        @foreach($stages as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach
      </select>
    </td>
    <td class="filter-actions">
      <button form="sale-filter" class="btn-outline">Cari</button>
      @if($filters)<a href="{{ route('admin.sale-requests.index') }}" class="filter-reset">Reset</a>@endif
    </td>
  </tr>
  @forelse($rows as $r)
    @php $msg = "Halo {$r->owner_name}, kami dari Like Hotel menindaklanjuti pengajuan titip jual unit {$r->apartment_name} ({$r->location}). Kapan kami bisa menghubungi/survei?"; @endphp
    <tr>
      <td>{{ \App\Support\Fmt::dateTime($r->created_at) }}</td>
      <td><a href="{{ route('admin.sale-requests.show', $r) }}"><b>{{ $r->owner_name }}</b></a><br><small>{{ $r->owner_phone }}</small></td>
      <td>{{ $r->apartment_name }}<br><small>{{ $r->location }} • {{ $r->unit_type ?: '-' }} • {{ $r->size ?: '-' }}</small></td>
      <td>{{ $r->expected_price ? Fmt::rupiah($r->expected_price) : '-' }}</td>
      <td>{{ $r->notes ?: '-' }}</td>
      <td>
        <form class="inline-form" method="post" action="{{ route('admin.sale-requests.status', $r) }}">
          @csrf @method('PATCH')
          <select name="status" onchange="this.form.submit()">
            @foreach($stages as $key => $label)<option value="{{ $key }}" @selected($r->status === $key)>{{ $label }}</option>@endforeach
          </select>
        </form>
      </td>
      <td>
        <a class="btn" href="{{ route('admin.sale-requests.show', $r) }}">Detail</a>
        <a class="btn-outline" target="_blank" href="{{ Fmt::waLink($r->owner_phone, $msg) }}">WhatsApp</a>
        <form class="inline-form" method="post" action="{{ route('admin.sale-requests.destroy', $r) }}" onsubmit="return confirm('Hapus pengajuan ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </td>
    </tr>
  @empty
    <tr><td colspan="7" style="text-align:center;color:#6b7280">
      @if($filters)Tidak ada data yang cocok dengan filter. <a href="{{ route('admin.sale-requests.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada pengajuan.@endif
    </td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $rows])
@endsection
