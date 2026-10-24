@extends('layouts.panel')
@section('title', 'Log Midtrans')
@section('content')
<h1>Log Notifikasi Midtrans</h1>
<p class="muted">Setiap notifikasi pembayaran yang masuk ke webhook. <b>Ditolak</b> berarti signature salah, order tidak dikenal, atau nominal tidak cocok.</p>

@if($rejected > 0)
  <div class="alert warn"><b>{{ $rejected }}</b> notifikasi ditolak dalam 24 jam terakhir. Periksa jika tidak sesuai harapan.</div>
@endif

<form method="get" class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <div class="row">
    <div>
      <label>Hasil</label>
      <select name="result">
        <option value="">Semua</option>
        @foreach(['processed' => 'Diproses', 'ignored' => 'Diabaikan', 'rejected' => 'Ditolak'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['result'] ?? '') === $v)>{{ $l }}</option>
        @endforeach
      </select>
    </div>
    <div><label>Order ID / kode booking</label><input name="q" value="{{ $filters['q'] ?? '' }}"></div>
  </div>
  <button class="btn">Filter</button>
  <a class="btn-outline" href="{{ route('admin.midtrans-logs.index') }}">Reset</a>
</form>

<table class="table">
  <tr><th>Waktu</th><th>Order</th><th>Status Midtrans</th><th>Hasil</th><th>Catatan</th><th>Payload</th></tr>
  @forelse($logs as $l)
    <tr>
      <td>{{ $l->created_at->format('d-m-Y H:i:s') }}</td>
      <td>{{ $l->order_id ?? '-' }}</td>
      <td>{{ $l->transaction_status }}</td>
      <td><b style="color:{{ ['processed' => '#087a35', 'ignored' => '#6b7280', 'rejected' => '#991b1b'][$l->result] ?? '#6b7280' }}">{{ $l->result }}</b></td>
      <td>{{ $l->note ?? '-' }}</td>
      <td><details><summary>lihat</summary><pre style="white-space:pre-wrap;max-width:420px;font-size:12px">{{ json_encode(json_decode($l->payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details></td>
    </tr>
  @empty
    <tr><td colspan="6">Belum ada notifikasi.</td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $logs])
@endsection
