@extends('layouts.public')
@section('title', 'Pembayaran')
@push('head')
<script src="{{ $snapJs }}" data-client-key="{{ $clientKey }}"></script>
@endpush
@section('content')
<div class="form-box">
  <h2>Pembayaran Booking</h2>
  <div class="alert">Kode Booking: <b>{{ $b->booking_code }}</b></div>
  @if(! $clientKey)
    <div class="alert warn"><b>Midtrans belum aktif.</b> Isi <code>MIDTRANS_CLIENT_KEY</code> dan <code>MIDTRANS_SERVER_KEY</code> di <code>.env</code>.</div>
  @endif
  <p><b>Apartment:</b> {{ $b->apartment->name }}</p>
  <p><b>Nama:</b> {{ $b->guest_name }}</p>
  <p><b>Total:</b> <span class="price">{{ \App\Support\Fmt::rupiah($b->total_price) }}</span></p>
  <button id="pay-button" class="btn">Bayar Sekarang</button>
  <a class="btn-outline" href="{{ route('invoice.show', $b->access_token) }}">Lihat Invoice</a>
  <div id="message" class="alert" style="display:none"></div>
</div>
@endsection

@push('scripts')
<script>
const token = @json($b->access_token);
const resultUrl = r => @json(route('payment.result', $b->access_token)) + '?result=' + r;
document.getElementById('pay-button').onclick = async function(){
  const msg = document.getElementById('message');
  msg.style.display = 'block'; msg.textContent = 'Membuat halaman pembayaran...';
  try {
    const res = await fetch(@json(route('payment.snap', $b->access_token)), {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'},
    });
    const data = await res.json();
    if(!res.ok || data.error){ msg.textContent = 'Gagal: ' + (data.error || 'Tidak diketahui'); return; }
    msg.style.display = 'none';
    snap.pay(data.token, {
      onSuccess: () => location.href = resultUrl('success'),
      onPending: () => location.href = resultUrl('pending'),
      onError: () => alert('Pembayaran gagal.'),
      onClose: () => {},
    });
  } catch(err){ msg.textContent = 'Error koneksi: ' + err; }
};
</script>
@endpush
