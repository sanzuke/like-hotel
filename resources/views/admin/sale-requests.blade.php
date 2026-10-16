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

<table class="table">
  <tr><th>Tanggal</th><th>Pemilik</th><th>Unit</th><th>Harga Harapan</th><th>Catatan</th><th>Status</th><th>Aksi</th></tr>
  @forelse($rows as $r)
    @php $msg = "Halo {$r->owner_name}, kami dari Like Hotel menindaklanjuti pengajuan titip jual unit {$r->apartment_name} ({$r->location}). Kapan kami bisa menghubungi/survei?"; @endphp
    <tr>
      <td>{{ $r->created_at->format('d-m-Y H:i') }}</td>
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
    <tr><td colspan="7">Belum ada pengajuan.</td></tr>
  @endforelse
</table>
@endsection
