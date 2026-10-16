@extends('layouts.panel')
@section('title', 'Detail Lead')
@php
  use App\Support\Fmt;
  $msg = "Halo {$r->owner_name}, kami dari Like Hotel menindaklanjuti pengajuan titip jual unit {$r->apartment_name} ({$r->location}).";
  $closed = $r->commission->isNotEmpty();
  $suggestedDeal = $r->agreed_price ?? $r->expected_price;
@endphp
@push('head')<link rel="stylesheet" href="{{ asset('css/crm.css') }}">@endpush
@section('content')
<a class="btn-outline" href="{{ route('admin.sale-requests.index') }}">← Semua Lead</a>
<h1 style="margin-top:14px">{{ $r->owner_name }} — {{ $r->apartment_name }}</h1>

<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:18px">
  <form class="inline-form" method="post" action="{{ route('admin.sale-requests.status', $r) }}">
    @csrf @method('PATCH')
    <select name="status" onchange="this.form.submit()">
      @foreach($stages as $key => $label)<option value="{{ $key }}" @selected($r->status === $key)>{{ $label }}</option>@endforeach
    </select>
  </form>
  <a class="btn-outline" target="_blank" href="{{ Fmt::waLink($r->owner_phone, $msg) }}">💬 WhatsApp Pemilik</a>
  @if($r->saleUnit)
    <a class="btn-outline" href="{{ route('admin.sale-units.edit', $r->saleUnit) }}">🏷 Unit Dijual #{{ $r->saleUnit->id }} ({{ $r->saleUnit->status }})</a>
  @else
    <form class="inline-form" method="post" action="{{ route('admin.sale-requests.publish', $r) }}" onsubmit="return confirm('Buat draf unit dijual dari lead ini?')">@csrf<button class="btn">📢 Publikasikan ke Unit Dijual</button></form>
  @endif
</div>

<div class="stats" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat"><p>Harga Harapan</p><h2>{{ $r->expected_price ? Fmt::rupiah($r->expected_price) : '-' }}</h2></div>
  <div class="stat"><p>Harga Disepakati</p><h2>{{ $r->agreed_price ? Fmt::rupiah($r->agreed_price) : '-' }}</h2></div>
  <div class="stat"><p>Jadwal Survei</p><h2 style="font-size:20px">{{ $r->survey_at ? $r->survey_at->format('d-m-Y H:i') : '-' }}</h2></div>
</div>
<br>

<div class="row" style="align-items:flex-start">
  <div>
    <div class="form-box" style="max-width:100%;margin:0 0 20px 0">
      <h3>Data Unit</h3>
      <p><b>Kontak:</b> {{ $r->owner_phone }}<br>
         <b>Lokasi:</b> {{ $r->location }}<br>
         <b>Tipe / Luas:</b> {{ $r->unit_type ?: '-' }} / {{ $r->size ?: '-' }}<br>
         <b>Masuk:</b> {{ $r->created_at->format('d-m-Y H:i') }}</p>
      @if($r->notes)<div class="alert">{{ $r->notes }}</div>@endif
    </div>

    <div class="form-box" style="max-width:100%;margin:0 0 20px 0">
      <h3>Jadwalkan Survei</h3>
      <form method="post" action="{{ route('admin.sale-requests.survey', $r) }}">
        @csrf
        <input type="datetime-local" name="survey_at" value="{{ old('survey_at') }}" required>
        <button class="btn">Simpan Jadwal</button>
      </form>
    </div>

    <div class="form-box" style="max-width:100%;margin:0 0 20px 0">
      <h3>Tambah Catatan Timeline</h3>
      <form method="post" action="{{ route('admin.sale-requests.notes', $r) }}">
        @csrf
        <label>Jenis</label>
        <select name="type">@foreach($noteTypes as $k => $l)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $l }}</option>@endforeach</select>
        <label>Isi catatan</label>
        <textarea name="body" required>{{ old('body') }}</textarea>
        <label>Harga disepakati (khusus negosiasi, opsional)</label>
        <input type="number" min="0" name="agreed_price" value="{{ old('agreed_price') }}">
        <button class="btn">Tambah</button>
      </form>
    </div>

    <div class="form-box" style="max-width:100%;margin:0 0 20px 0">
      <h3>💵 Closing & Komisi</h3>
      @if($closed)
        @php $c = $r->commission->first(); @endphp
        <div class="alert">Closing dicatat: deal <b>{{ Fmt::rupiah($c->deal_price) }}</b>, komisi <b>{{ Fmt::rupiah($c->commission_amount) }}</b>, bersih penjual <b>{{ Fmt::rupiah($c->seller_net_amount) }}</b>.</div>
        <a class="btn-outline" target="_blank" href="{{ route('admin.sale-commissions.invoice', $c) }}">Invoice Komisi</a>
      @else
        <form method="post" action="{{ route('admin.sale-requests.close', $r) }}" onsubmit="return confirm('Catat closing? Unit terkait akan ditandai terjual.')">
          @csrf
          <label>Harga Deal</label><input type="number" min="1" name="deal_price" value="{{ old('deal_price', $suggestedDeal ? (int) $suggestedDeal : '') }}" required>
          <label>Tipe Komisi</label>
          <select name="commission_type"><option value="percent" @selected(old('commission_type', 'percent') === 'percent')>Persen (%)</option><option value="fixed" @selected(old('commission_type') === 'fixed')>Nominal Rupiah</option></select>
          <label>Nilai Komisi</label><input type="number" step="any" min="0" name="commission_value" value="{{ old('commission_value', 2.5) }}" required>
          <label>Biaya Lain</label><input type="number" min="0" name="other_cost" value="{{ old('other_cost', 0) }}">
          <label>Catatan</label><textarea name="notes">{{ old('notes') }}</textarea>
          <button class="btn">Catat Closing</button>
        </form>
      @endif
    </div>
  </div>

  <div>
    <div class="form-box" style="max-width:100%;margin:0">
      <h3>Timeline</h3>
      @forelse($r->timeline as $n)
        <div class="activity-card">
          <b>{{ $noteTypes[$n->type] ?? ucfirst($n->type) }}</b>
          <small> • {{ $n->author ?? 'Sistem' }} • {{ $n->created_at->format('d-m-Y H:i') }}</small><br>
          {!! nl2br(e($n->body)) !!}
        </div>
      @empty
        <p>Belum ada aktivitas. Tambahkan catatan pertama.</p>
      @endforelse
    </div>
  </div>
</div>
@endsection
