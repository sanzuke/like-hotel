@extends('layouts.panel')
@section('title', 'Pencairan Dana')
@php use App\Support\Fmt; @endphp
@section('content')
<div class="page-head">
  <h1>Pencairan Dana Owner</h1>
  @if($available > 0)
    <button type="button" class="btn" data-open-dialog="settlement-create">+ Ajukan Pencairan</button>
  @endif
</div>
<p class="muted">Kelola pengajuan pencairan saldo untuk {{ $owner->name }}.</p>

<div class="stats">
  <div class="stat"><p>Total Hak Owner</p><h2>{{ Fmt::rupiah($earnings) }}</h2></div>
  <div class="stat"><p>Proses/Sudah Cair</p><h2>{{ Fmt::rupiah($requested) }}</h2></div>
  <div class="stat"><p>Sudah Dibayar</p><h2>{{ Fmt::rupiah($paid) }}</h2></div>
  <div class="stat"><p>Saldo Tersedia</p><h2>{{ Fmt::rupiah($available) }}</h2></div>
</div>

@if($available <= 0)
  <div class="alert warn">Belum ada saldo yang bisa dicairkan saat ini.</div>
@endif

<h2>Riwayat Pencairan</h2>
@include('owner._settlement-table', ['withFilters' => true, 'filters' => $filters])
@include('partials.pagination', ['paginator' => $settlements])
<div class="alert">Saldo tersedia = hak owner dari transaksi lunas dikurangi pencairan yang pending, approved, dan paid.</div>

@if($available > 0)
<x-modal id="settlement-create" title="Ajukan Pencairan">
  <form method="post" action="{{ route('owner.settlements.store') }}">
    @csrf
    <label>Nominal Pencairan</label>
    <input name="amount" type="number" min="1" max="{{ (int) $available }}" value="{{ old('amount') }}" placeholder="Maksimal {{ number_format($available, 0, ',', '.') }}" required>
    <small>Saldo tersedia: <b>{{ Fmt::rupiah($available) }}</b></small>
    <br><br>
    <label>Nama Bank</label><input name="bank_name" value="{{ old('bank_name') }}" placeholder="BCA / Mandiri / BRI / BNI" required>
    <label>Nama Pemilik Rekening</label><input name="bank_account_name" value="{{ old('bank_account_name') }}" placeholder="Nama sesuai rekening" required>
    <label>Nomor Rekening</label><input name="bank_account_number" value="{{ old('bank_account_number') }}" placeholder="Nomor rekening tujuan" required>
    <label>Catatan</label><textarea name="notes" placeholder="Opsional">{{ old('notes') }}</textarea>
    <div class="modal-actions">
      <button type="button" class="btn-outline" data-close-dialog>Batal</button>
      <button class="btn" onclick="return confirm('Kirim pengajuan pencairan dana?')">Ajukan Pencairan</button>
    </div>
  </form>
</x-modal>
@endif
@endsection
