@extends('layouts.panel')
@section('title', 'Audit Log')
@section('content')
<h1>Audit Log</h1>
<p class="muted">Jejak perubahan penting: pembayaran, pencairan, status owner, CRM, dan login.</p>

<form method="get" class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <div class="row">
    <div>
      <label>Pelaku</label>
      <select name="actor_type">
        <option value="">Semua</option>
        @foreach(['admin' => 'Admin', 'owner' => 'Owner', 'guest' => 'Tamu / publik', 'system' => 'Sistem (Midtrans, otomatis)'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['actor_type'] ?? '') === $v)>{{ $l }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label>Kategori</label>
      <select name="group">
        <option value="">Semua</option>
        @foreach($groups as $g)<option value="{{ $g }}" @selected(($filters['group'] ?? '') === $g)>{{ $g }}</option>@endforeach
      </select>
    </div>
  </div>
  <label>Cari deskripsi</label>
  <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Kode booking, nama, dll.">
  <button class="btn">Filter</button>
  <a class="btn-outline" href="{{ route('admin.audit-logs.index') }}">Reset</a>
</form>

<table class="table">
  <tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Keterangan</th><th>IP</th></tr>
  @forelse($logs as $l)
    <tr>
      <td>{{ $l->created_at->format('d-m-Y H:i:s') }}</td>
      <td><b>{{ $l->actor_name ?? '-' }}</b><br><small>{{ $l->actor_type }}</small></td>
      <td><code>{{ $l->action }}</code></td>
      <td>{{ $l->description }}</td>
      <td><small>{{ $l->ip ?? '-' }}</small></td>
    </tr>
  @empty
    <tr><td colspan="5">Belum ada catatan.</td></tr>
  @endforelse
</table>
<br>
<div style="display:flex;gap:10px;align-items:center">
  @if($logs->previousPageUrl())<a class="btn-outline" href="{{ $logs->previousPageUrl() }}">← Sebelumnya</a>@endif
  <span>Halaman {{ $logs->currentPage() }} dari {{ $logs->lastPage() }} ({{ $logs->total() }} catatan)</span>
  @if($logs->nextPageUrl())<a class="btn-outline" href="{{ $logs->nextPageUrl() }}">Berikutnya →</a>@endif
</div>
@endsection
