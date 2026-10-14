@extends('layouts.owner')
@section('page', 'Pencairan Dana')

@section('content')
<h1>Pencairan Dana Owner</h1>
<p class="muted">Kelola pengajuan pencairan saldo owner untuk {{ $owner->name }}.</p>

@include('owner.partials.balance', ['balance' => $balance])

<div class="form-box" style="max-width:100%;margin:20px 0">
  <h3>Ajukan Pencairan</h3>
  @if($balance['available'] > 0)
    <form method="post" action="{{ route('owner.settlement.store') }}">
      @csrf
      <label>Nominal Pencairan</label>
      <input name="amount" type="number" min="1" max="{{ (int) $balance['available'] }}" value="{{ old('amount') }}" placeholder="Maksimal {{ number_format($balance['available'], 0, ',', '.') }}" required>
      <small>Saldo tersedia: <b>{{ rupiah($balance['available']) }}</b></small>
      <br><br>
      <label>Nama Bank</label>
      <input name="bank_name" value="{{ old('bank_name') }}" placeholder="BCA / Mandiri / BRI / BNI" required>
      <label>Nama Pemilik Rekening</label>
      <input name="bank_account_name" value="{{ old('bank_account_name') }}" placeholder="Nama sesuai rekening" required>
      <label>Nomor Rekening</label>
      <input name="bank_account_number" value="{{ old('bank_account_number') }}" placeholder="Nomor rekening tujuan" required>
      <label>Catatan</label>
      <textarea name="notes" placeholder="Opsional">{{ old('notes') }}</textarea>
      <button class="btn" onclick="return confirm('Kirim pengajuan pencairan dana?')">Ajukan Pencairan</button>
    </form>
  @else
    <div class="alert warn">Belum ada saldo yang bisa dicairkan saat ini.</div>
  @endif
</div>

<h2>Riwayat Pencairan</h2>
@include('owner.partials.settlement-table', ['rows' => $rows])

<div class="alert">Saldo tersedia = hak owner dari transaksi lunas dikurangi pencairan yang pending, approved, dan paid.</div>
@endsection
