@extends('layouts.panel')
@section('title', 'Kelola Owner')
@section('content')
<h1>Kelola Owner</h1>
<div class="alert">Owner yang baru mendaftar berstatus <b>pending</b> dan baru bisa login setelah Anda mengaktifkannya.</div>
<form id="owner-filter" method="get" action="{{ route('admin.owners.index') }}"></form>
<table class="table">
  <tr><th>Nama</th><th>WA</th><th>Email</th><th>Status</th><th>Unit</th><th>Aksi</th></tr>
  <tr class="filter-row">
    <td><input form="owner-filter" name="name" value="{{ $filters['name'] ?? '' }}" placeholder="Cari nama…" aria-label="Filter nama"></td>
    <td></td>
    <td><input form="owner-filter" name="email" value="{{ $filters['email'] ?? '' }}" placeholder="Cari email…" aria-label="Filter email"></td>
    <td>
      <select form="owner-filter" name="status" onchange="this.form.submit()" aria-label="Filter status">
        <option value="">Semua</option>
        @foreach(['pending' => 'Pending', 'aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>@endforeach
      </select>
    </td>
    <td></td>
    <td class="filter-actions">
      <button form="owner-filter" class="btn-outline">Cari</button>
      @if(array_filter($filters))<a href="{{ route('admin.owners.index') }}" class="filter-reset">Reset</a>@endif
    </td>
  </tr>
  @forelse($owners as $o)
    <tr>
      <td>{{ $o->name }}</td>
      <td>{{ $o->phone ?: '-' }}</td>
      <td>{{ $o->email }}</td>
      <td><b style="color:{{ \App\Support\Fmt::statusColor($o->status) }}">{{ $o->status }}</b></td>
      <td>{{ $o->apartments_count }}</td>
      <td>
        @if($o->status !== 'aktif')
          <form class="inline-form" method="post" action="{{ route('admin.owners.status', $o) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="aktif"><button class="btn-outline">Aktifkan</button></form>
        @else
          <form class="inline-form" method="post" action="{{ route('admin.owners.status', $o) }}" onsubmit="return confirm('Nonaktifkan owner ini?')">@csrf @method('PATCH')<input type="hidden" name="status" value="nonaktif"><button class="btn-outline">Nonaktifkan</button></form>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="6" style="text-align:center;color:#6b7280">
      @if(array_filter($filters))Tidak ada data yang cocok dengan filter. <a href="{{ route('admin.owners.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada owner.@endif
    </td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $owners])
@endsection
