@extends('layouts.admin')
@section('page', 'Detail Lead')

@push('head')
<link rel="stylesheet" href="{{ asset('css/crm.css') }}?v=10">
<style>
.lead-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:20px;align-items:start}
.lead-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;margin-bottom:18px}
.lead-card h3{margin-top:0}
.stepper{display:flex;gap:6px;flex-wrap:wrap;margin:10px 0 0}
.step{padding:6px 10px;border-radius:999px;background:#f3f4f6;font-size:12px;font-weight:700;color:#6b7280}
.step.done{background:#dcfce7;color:#166534}.step.now{background:#087a35;color:#fff}
.timeline{list-style:none;margin:0;padding:0}
.timeline li{border-left:3px solid #d1fae5;padding:0 0 16px 14px;margin-left:6px;position:relative}
.timeline small{color:#6b7280}.timeline p{margin:4px 0 0;white-space:pre-line}
.photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px}
.photo-grid img{width:100%;height:90px;object-fit:cover;border-radius:8px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:10px}
details{margin-bottom:10px}summary{cursor:pointer;font-weight:800;padding:8px 0}
@media(max-width:900px){.lead-grid,.two{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
  $flow = ['baru','follow up','survey','foto unit','listing','negosiasi','closing','selesai'];
  $idx = array_search($r->status, $flow);
  $price = rupiah($r->expected_price ?? 0);
  $color = \App\Models\SaleRequest::COLORS[$r->status] ?? '#6b7280';
  $wa = fn ($m) => wa_link($r->owner_phone, $m);
@endphp

<p><a href="{{ route('admin.sale-requests') }}">← Kembali ke CRM</a></p>
<div class="crm-page-header">
  <div>
    <h1>{{ $r->apartment_name }}</h1>
    <p class="muted">Pemilik: <b>{{ $r->owner_name }}</b> • <a href="{{ $wa('Halo '.$r->owner_name) }}" target="_blank" rel="noopener">{{ $r->owner_phone }}</a> •
      <span class="crm-badge" style="color:{{ $color }};border-color:{{ $color }}">{{ strtoupper(\App\Models\SaleRequest::STATUSES[$r->status] ?? $r->status) }}</span></p>
  </div>
</div>

<div class="stepper">
  @foreach($flow as $i => $st)
    <span class="step {{ $idx !== false && $i < $idx ? 'done' : '' }} {{ $r->status === $st ? 'now' : '' }}">{{ \App\Models\SaleRequest::STATUSES[$st] }}</span>
  @endforeach
  @if($r->status === 'batal')<span class="step now" style="background:#991b1b">❌ Batal</span>@endif
</div>
<br>

<div class="lead-grid">
<div>
  <div class="lead-card">
    <h3>Informasi Unit</h3>
    <p>📍 {{ $r->location }} • 🏠 {{ $r->unit_type ?: '-' }} • 📐 {{ $r->size ?: '-' }}</p>
    <p>💰 Harga harapan: <b>{{ $price }}</b>@if($r->last_offer) • Penawaran terakhir: <b>{{ rupiah($r->last_offer) }}</b>@endif</p>
    @if($r->survey_at)<p>🏠 Jadwal survey: <b>{{ $r->survey_at->translatedFormat('d F Y, H:i') }}</b></p>@endif
    @if($r->notes)<p style="white-space:pre-line">📝 {{ $r->notes }}</p>@endif
    <p class="muted">Masuk {{ $r->created_at->translatedFormat('d F Y, H:i') }}</p>

    @if($r->saleUnit)
      <div class="alert">📢 Sudah tayang: <a href="{{ route('sale.show', $r->saleUnit) }}" target="_blank">lihat listing</a> • <a href="{{ route('admin.sale-units.edit', $r->saleUnit) }}">edit unit dijual</a> ({{ $r->saleUnit->status }})</div>
    @endif
    @if($r->saleCommission)
      <div class="alert">💵 Closing {{ $r->saleCommission->number() }}: {{ rupiah($r->saleCommission->deal_price) }} •
        <a href="{{ route('admin.sale-commissions.invoice', $r->saleCommission) }}" target="_blank">invoice</a> • <a href="{{ route('admin.sale-commissions.edit', $r->saleCommission) }}">edit</a></div>
    @endif
  </div>

  <div class="lead-card">
    <h3>Foto Unit ({{ $r->photos->count() }})</h3>
    <div class="photo-grid">
      @foreach($r->photos as $p)
        <div>
          <img src="{{ $p->url() }}" alt="">
          <form method="post" action="{{ route('admin.sale-requests.photos.destroy', [$r, $p]) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="btn-outline" style="width:100%;margin-top:4px">Hapus</button></form>
        </div>
      @endforeach
    </div>
    <form method="post" action="{{ route('admin.sale-requests.photos', $r) }}" enctype="multipart/form-data" style="margin-top:12px">
      @csrf
      <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
      <button class="btn">Unggah Foto</button>
    </form>
  </div>

  <div class="lead-card">
    <h3>Timeline Aktivitas</h3>
    <ul class="timeline">
      @foreach($r->events as $e)
        <li>
          <b>{{ $e->icon() }} {{ ucfirst($e->type) }}</b>
          <small> • {{ $e->created_at->translatedFormat('d M Y H:i') }}@if($e->admin) • {{ $e->admin->name }}@endif</small>
          @if($e->body)<p>{{ $e->body }}</p>@endif
        </li>
      @endforeach
    </ul>
  </div>
</div>

<div>
  <div class="lead-card">
    <h3>Aksi</h3>

    <details open>
      <summary>🔄 Ubah Status</summary>
      <form method="post" action="{{ route('admin.sale-requests.status', $r) }}">
        @csrf
        <select name="status">@foreach(\App\Models\SaleRequest::STATUSES as $k => $l)<option value="{{ $k }}" @selected($r->status === $k)>{{ $l }}</option>@endforeach</select>
        <textarea name="note" placeholder="Catatan (opsional)"></textarea>
        <button class="btn">Simpan</button>
      </form>
    </details>

    <details>
      <summary>📝 Tambah Catatan</summary>
      <form method="post" action="{{ route('admin.sale-requests.note', $r) }}">@csrf<textarea name="note" required placeholder="Hasil telepon, info tambahan, dll."></textarea><button class="btn">Simpan Catatan</button></form>
    </details>

    <details>
      <summary>🏠 Jadwalkan Survey</summary>
      <form method="post" action="{{ route('admin.sale-requests.survey', $r) }}">@csrf
        <input type="datetime-local" name="survey_at" value="{{ $r->survey_at?->format('Y-m-d\TH:i') }}" required>
        <input name="note" placeholder="Catatan (opsional)">
        <button class="btn">Simpan Jadwal</button>
        @if($r->survey_at)
          <a class="btn-outline" target="_blank" rel="noopener" href="{{ $wa("Halo {$r->owner_name},\n\nSurvey unit {$r->apartment_name} dijadwalkan pada ".$r->survey_at->translatedFormat('d F Y, H:i').". Mohon konfirmasi kehadiran Anda.") }}">Kirim WA</a>
        @endif
      </form>
    </details>

    <details>
      <summary>🤝 Catat Penawaran</summary>
      <form method="post" action="{{ route('admin.sale-requests.offer', $r) }}">@csrf
        <input type="number" name="amount" min="1" placeholder="Nominal penawaran pembeli" required>
        <input name="note" placeholder="Catatan (opsional)">
        <button class="btn">Catat</button>
      </form>
    </details>

    @unless($r->sale_unit_id || $r->isFinal())
    <details>
      <summary>📢 Publikasikan ke Jual Beli</summary>
      <form method="post" action="{{ route('admin.sale-requests.publish', $r) }}" onsubmit="return confirm('Tayangkan unit ini di halaman publik Jual Beli?')">@csrf
        <input name="title" placeholder="Judul listing (opsional)">
        <input type="number" name="price" min="1" value="{{ (int) ($r->expected_price ?: 0) ?: '' }}" placeholder="Harga jual" required>
        <small>{{ $r->photos->count() }} foto unit akan ikut disalin.</small><br>
        <button class="btn">Publikasikan</button>
      </form>
    </details>
    @endunless

    @unless($r->sale_commission_id || $r->status === 'batal')
    <details>
      <summary>💵 Catat Closing</summary>
      <form method="post" action="{{ route('admin.sale-requests.close', $r) }}" onsubmit="return confirm('Catat closing? Unit dijual terkait akan ditandai terjual.')">@csrf
        <input type="number" name="deal_price" min="1" value="{{ (int) ($r->last_offer ?: 0) ?: '' }}" placeholder="Harga deal" required>
        <div class="two">
          <select name="commission_type"><option value="percent">Persen (%)</option><option value="fixed">Nominal (Rp)</option></select>
          <input type="number" step="0.01" min="0" name="commission_value" placeholder="Nilai komisi" required>
        </div>
        <input type="number" min="0" name="other_cost" placeholder="Biaya lain (opsional)">
        <textarea name="notes" placeholder="Catatan"></textarea>
        <button class="btn">Catat Closing</button>
      </form>
    </details>
    @endunless

    <hr>
    <b>WhatsApp cepat</b>
    <div class="crm-wa-actions" style="margin-top:8px">
      <a class="crm-btn crm-btn-wa" target="_blank" rel="noopener" href="{{ $wa("Halo {$r->owner_name},\n\nKami dari Tim Properti Like Hotel telah menerima pengajuan titip jual unit Anda.\n\nApartment: {$r->apartment_name}\nLokasi: {$r->location}\nHarga Harapan: {$price}\n\nKami ingin melakukan follow up dan verifikasi data unit.") }}">📞 Follow Up</a>
      <a class="crm-btn crm-btn-wa" target="_blank" rel="noopener" href="{{ $wa("Halo {$r->owner_name},\n\nUnit apartemen Anda sedang kami proses untuk ditayangkan pada listing penjualan Like Hotel.\n\nApartment: {$r->apartment_name}\nHarga Harapan: {$price}") }}">📢 Listing</a>
    </div>
  </div>
</div>
</div>
@endsection
