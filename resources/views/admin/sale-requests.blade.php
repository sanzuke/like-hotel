@extends('layouts.admin')
@section('page', 'CRM Titip Jual')

@push('head')<link rel="stylesheet" href="{{ asset('css/crm.css') }}?v=10">@endpush

@section('content')
@php
  $fmtLuas = function ($size) { $size = trim((string) $size); return $size === '' ? '-' : (stripos($size, 'm') !== false ? $size : $size.' m²'); };
@endphp

<div class="crm-page-header">
  <div>
    <h1>CRM Titip Jual</h1>
    <p class="muted">Kelola alur kerja pemilik yang ingin menjual unit apartemen melalui Like Hotel.</p>
  </div>
</div>

<div class="crm-stats-grid">
  <div class="crm-stat-card"><span>📋 Total Pengajuan</span><strong>{{ $rows->count() }}</strong></div>
  @foreach(\App\Models\SaleRequest::STATUSES as $key => $label)
    <div class="crm-stat-card"><span>{{ $label }}</span><strong>{{ $counts[$key] ?? 0 }}</strong></div>
  @endforeach
</div>

<div class="crm-flow">
  <strong>Alur CRM:</strong> 🆕 Baru <span>→</span> 📞 Follow Up <span>→</span> 🏠 Survey <span>→</span> 📷 Foto Unit <span>→</span>
  📢 Listing <span>→</span> 🤝 Negosiasi <span>→</span> 💵 Closing <span>→</span> ✅ Selesai
</div>

<div class="crm-table-wrapper">
<table class="crm-table">
  <thead><tr><th>Tanggal</th><th>Pemilik</th><th>Properti</th><th>Unit</th><th>Harga Harapan</th><th>Status</th><th>Catatan</th><th>WhatsApp</th><th>Aksi</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    @php
      $price = rupiah($r->expected_price ?? 0);
      $msgFollowUp = "Halo {$r->owner_name},\n\nKami dari Tim Properti Like Hotel telah menerima pengajuan titip jual unit Anda.\n\nApartment: {$r->apartment_name}\nLokasi: {$r->location}\nTipe Unit: ".($r->unit_type ?: '-')."\nHarga Harapan: {$price}\n\nKami ingin melakukan follow up dan verifikasi data unit. Mohon informasikan waktu yang nyaman untuk kami menghubungi Anda.";
      $msgSurvey = "Halo {$r->owner_name},\n\nKami dari Tim Properti Like Hotel ingin menjadwalkan survey unit berikut:\n\nApartment: {$r->apartment_name}\nLokasi: {$r->location}\nTipe Unit: ".($r->unit_type ?: '-')."\n\nMohon informasikan pilihan hari dan jam yang tersedia untuk pelaksanaan survey.";
      $msgListing = "Halo {$r->owner_name},\n\nUnit apartemen Anda sedang kami proses untuk ditayangkan pada listing penjualan Like Hotel.\n\nApartment: {$r->apartment_name}\nTipe Unit: ".($r->unit_type ?: '-')."\nHarga Harapan: {$price}\n\nTim kami akan menghubungi Anda apabila terdapat calon pembeli yang berminat.";
      $color = \App\Models\SaleRequest::COLORS[$r->status] ?? '#6b7280';
    @endphp
    <tr>
      <td class="crm-date-cell">{{ $r->created_at->translatedFormat('d F Y, H:i') }}</td>
      <td><strong>{{ $r->owner_name }}</strong>
        <a class="crm-phone" href="{{ wa_link($r->owner_phone, 'Halo '.$r->owner_name) }}" target="_blank" rel="noopener">{{ $r->owner_phone ?: '-' }}</a></td>
      <td><strong>{{ $r->apartment_name }}</strong><small>{{ $r->location ?: '-' }}</small></td>
      <td><strong>{{ $r->unit_type ?: '-' }}</strong><small>{{ $fmtLuas($r->size) }}</small></td>
      <td class="crm-price">{{ $price }}</td>
      <td><span class="crm-badge" style="color:{{ $color }};border-color:{{ $color }}">{{ strtoupper(\App\Models\SaleRequest::STATUSES[$r->status] ?? $r->status) }}</span></td>
      <td class="crm-notes">{!! nl2br(e($r->notes ?: '-')) !!}</td>
      <td>
        <div class="crm-wa-actions">
          <a class="crm-btn crm-btn-wa" target="_blank" rel="noopener" href="{{ wa_link($r->owner_phone, $msgFollowUp) }}">📞 Follow Up</a>
          <a class="crm-btn crm-btn-wa" target="_blank" rel="noopener" href="{{ wa_link($r->owner_phone, $msgSurvey) }}">🏠 Survey</a>
          <a class="crm-btn crm-btn-wa" target="_blank" rel="noopener" href="{{ wa_link($r->owner_phone, $msgListing) }}">📢 Listing</a>
        </div>
      </td>
      <td>
        <div class="crm-action-box">
          <form method="post" action="{{ route('admin.sale-requests.status', $r) }}" class="crm-status-form">
            @csrf
            <select name="status" class="crm-status-select" aria-label="Pilih status">
              @foreach(\App\Models\SaleRequest::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($r->status === $key)>{{ $label }}</option>
              @endforeach
            </select>
            <button type="submit" class="crm-btn crm-btn-update">Simpan Status</button>
          </form>
          <form method="post" action="{{ route('admin.sale-requests.destroy', $r) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengajuan ini?')">
            @csrf @method('DELETE')
            <button type="submit" class="crm-btn crm-btn-delete">🗑 Hapus</button>
          </form>
        </div>
      </td>
    </tr>
  @empty
    <tr><td colspan="9" class="crm-empty">Belum ada pengajuan titip jual.</td></tr>
  @endforelse
  </tbody>
</table>
</div>
@endsection
