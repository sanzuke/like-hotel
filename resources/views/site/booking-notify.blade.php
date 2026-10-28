@extends('layouts.public')
@section('robots', 'noindex,nofollow')
@section('title', 'Booking Berhasil')
@php
  use App\Support\Fmt;
  $apt = $b->apartment;
  $nights = $b->nights();
  $paid = $b->isPaid();
  $cancelled = $b->isCancelled();
  $pending = ! $paid && ! $cancelled;
  $discount = (float) $b->discount_amount;
  $subtotal = (float) $b->total_price + $discount;
  $perNight = $nights > 0 ? $subtotal / $nights : $subtotal;
  $cover = $apt->image ? asset('storage/apartments/'.$apt->image) : null;
@endphp
@section('content')
<div class="pd bs">
  {{-- Banner status --}}
  <div class="bs-banner {{ $paid ? 'is-paid' : ($cancelled ? 'is-cancel' : '') }}">
    <div class="bs-banner-l">
      <div class="bs-ic"><i class="ms">{{ $paid ? 'task_alt' : ($cancelled ? 'cancel' : 'hourglass_top') }}</i></div>
      <div>
        <span class="bs-pill"><i class="dot"></i>{{ $paid ? 'Pembayaran Diterima' : ($cancelled ? 'Booking Tidak Aktif' : 'Booking Berhasil Dibuat') }}</span>
        <h1>{{ $paid ? 'Terima Kasih, Pembayaran Lunas' : ($cancelled ? 'Booking Ini Sudah Dibatalkan' : 'Selesaikan Pembayaran Anda') }}</h1>
        <p>
          @if($paid)
            Pembayaran Anda sudah kami terima. Simpan tautan halaman ini sebagai bukti booking.
          @elseif($cancelled)
            Booking ini tidak aktif lagi ({{ $b->payment_status }}). Silakan buat booking baru bila masih membutuhkan unit.
          @else
            Simpan tautan halaman ini, lalu lanjutkan ke pembayaran sebelum batas waktu agar booking tidak dibatalkan otomatis.
          @endif
        </p>
      </div>
    </div>
    @if($pending && $b->expires_at)
      <div class="bs-timer" data-expire="{{ $b->expires_at->toIso8601String() }}">
        <i class="ms">timer</i>
        <div><small>Batas Akhir Pembayaran</small>
          <div class="t"><span id="tH">--</span><b>:</b><span id="tM">--</span><b>:</b><span id="tS">--</span></div>
          <em>{{ Fmt::dateTime($b->expires_at) }}</em>
        </div>
      </div>
    @endif
  </div>

  <div class="pd-grid">
    <div class="pd-col">
      {{-- Referensi & status --}}
      <section class="pd-card">
        <div class="bs-ref">
          <div>
            <small>Nomor Referensi Booking</small>
            <div class="bs-code"><b id="bookingCode">{{ $b->booking_code }}</b>
              <button type="button" class="bs-copy" onclick="copyCode(this)"><i class="ms">content_copy</i><span>Salin Kode</span></button>
            </div>
          </div>
          <span class="bs-status {{ $paid ? 'ok' : ($cancelled ? 'bad' : 'wait') }}"><i class="ms">{{ $paid ? 'check_circle' : ($cancelled ? 'block' : 'schedule') }}</i> {{ ucfirst($b->payment_status) }}</span>
        </div>

        <div class="bs-prop">
          <div class="bs-prop-img">
            @if($cover)<img src="{{ $cover }}" alt="{{ $apt->name }}">@else<span>LIKE HOTEL</span>@endif
          </div>
          <div>
            <div class="bs-ver"><i class="ms">verified</i> Unit Terverifikasi</div>
            <h2>{{ $apt->name }}</h2>
            <p><i class="ms">location_on</i> {{ $apt->location }} @if($apt->type)&bull; {{ $apt->type }}@endif</p>
          </div>
        </div>

        <div class="bs-dates">
          <div><small><i class="ms">login</i> CHECK-IN</small><b>{{ Fmt::dayDate($b->checkin) }}</b></div>
          <div><small><i class="ms">logout</i> CHECK-OUT</small><b>{{ Fmt::dayDate($b->checkout) }}</b></div>
        </div>
        <div class="bs-meta">
          <span><i class="ms">bed</i> {{ $nights }} Malam Menginap</span>
          <span><i class="ms">person</i> {{ $b->guest_name }}</span>
          <span><i class="ms">call</i> {{ $b->guest_phone }}</span>
        </div>
      </section>

      {{-- Aksi utama --}}
      @if($pending)
        <div class="bs-cta">
          <a class="btn" href="{{ route('payment.show', $b->access_token) }}"><i class="ms">verified_user</i> Lanjut ke Pembayaran Online</a>
          <a class="btn-outline" href="{{ route('invoice.show', $b->access_token) }}"><i class="ms">receipt_long</i> Lihat Invoice</a>
        </div>
      @else
        <div class="bs-cta">
          <a class="btn" href="{{ route('invoice.show', $b->access_token) }}"><i class="ms">receipt_long</i> Lihat Invoice</a>
        </div>
      @endif

      <section class="pd-card">
        <h2>Langkah Selanjutnya</h2>
        <ol class="sd-steps">
          <li><b>1</b><div><h4>Bayar Tagihan</h4><p>Selesaikan pembayaran online (QRIS, Virtual Account, dan metode lain lewat Midtrans) sebelum batas waktu.</p></div></li>
          <li><b>2</b><div><h4>Konfirmasi Otomatis</h4><p>Status booking berubah menjadi lunas begitu pembayaran terverifikasi, dan invoice siap dicek kapan saja.</p></div></li>
          <li><b>3</b><div><h4>Koordinasi Check-in</h4><p>Hubungi admin lewat WhatsApp bila butuh informasi akses unit atau perubahan jadwal.</p></div></li>
        </ol>
      </section>
    </div>

    {{-- Ringkasan tagihan --}}
    <aside class="pd-side">
      <div class="pd-book">
        <div class="bs-sum-head"><h2>Ringkasan Tagihan</h2></div>
        <div class="bs-total"><small>TOTAL TAGIHAN</small><b>{{ Fmt::rupiah($b->total_price) }}</b></div>
        <div class="pd-sum">
          <div><span>Sewa Unit ({{ $nights }} malam x {{ Fmt::rupiah($perNight) }})</span><span>{{ Fmt::rupiah($subtotal) }}</span></div>
          @if($discount > 0)
            <div><span>Diskon @if($b->promo_code)({{ $b->promo_code }})@endif</span><span style="color:var(--primary)">- {{ Fmt::rupiah($discount) }}</span></div>
          @endif
          <div class="tot"><span>Total</span><b>{{ Fmt::rupiah($b->total_price) }}</b></div>
        </div>
        @if($pending)
          <a class="btn btn-full" href="{{ route('payment.show', $b->access_token) }}"><i class="ms">credit_score</i> Bayar Sekarang</a>
        @endif

        <p class="bs-help">Butuh bantuan? <a href="{{ Fmt::waLink(Fmt::adminWa(), 'Halo Like Hotel, saya butuh bantuan untuk booking '.$b->booking_code) }}" target="_blank" rel="noopener">CS WhatsApp {{ Fmt::adminWaDisplay() }}</a></p>
      </div>
    </aside>
  </div>
</div>
@endsection

@push('scripts')
<script>
function copyCode(btn){
  const code = document.getElementById('bookingCode').innerText;
  const done = () => { const l = btn.querySelector('span'), i = btn.querySelector('.ms'); l.textContent = 'Tersalin!'; i.textContent = 'check'; setTimeout(() => { l.textContent = 'Salin Kode'; i.textContent = 'content_copy'; }, 2200); };
  if(navigator.clipboard){ navigator.clipboard.writeText(code).then(done).catch(() => {}); }
  else { const t = document.createElement('textarea'); t.value = code; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); done(); } catch(e){} t.remove(); }
}
(function(){
  const box = document.querySelector('.bs-timer'); if(!box) return;
  const end = new Date(box.dataset.expire).getTime();
  const p = n => String(n).padStart(2, '0');
  function tick(){
    let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
    document.getElementById('tH').textContent = p(Math.floor(s / 3600));
    document.getElementById('tM').textContent = p(Math.floor(s % 3600 / 60));
    document.getElementById('tS').textContent = p(s % 60);
    if(s === 0) clearInterval(iv);
  }
  const iv = setInterval(tick, 1000); tick();
})();
</script>
@endpush
