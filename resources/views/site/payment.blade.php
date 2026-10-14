@extends('layouts.site')
@section('title', 'Pembayaran')

@push('head')
@if($configured)
<script src="{{ $snapJs }}" data-client-key="{{ config('likehotel.midtrans.client_key') }}"></script>
@endif
@endpush

@section('content')
<div class="form-box">
  <h2>Pembayaran Booking</h2>
  <div class="alert">Kode Booking: <b>{{ $booking->booking_code }}</b></div>

  @unless($configured)
    <div class="alert warn"><b>Pembayaran online belum aktif.</b> Hubungi admin untuk pembayaran manual.</div>
  @endunless

  <p><b>Apartment:</b> {{ $booking->apartment->name }}</p>
  <p><b>Nama:</b> {{ $booking->guest_name }}</p>
  <p><b>Total:</b> <span class="price">{{ rupiah($booking->total_price) }}</span></p>

  <button id="pay-button" class="btn" @disabled(! $configured)>Bayar Sekarang</button>
  <a class="btn-outline" href="{{ route('invoice.show', $booking) }}">Lihat Invoice</a>
  <div id="message" class="alert" style="display:none"></div>
</div>
@endsection

@push('scripts')
@if($configured)
<script>
document.getElementById('pay-button').onclick = function(){
    const msg = document.getElementById('message');
    msg.style.display = 'block';
    msg.textContent = 'Membuat halaman pembayaran...';

    fetch(@json(route('payment.snap', $booking)), {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'}
    })
    .then(r => r.json().then(data => ({ok: r.ok, data})))
    .then(({ok, data}) => {
        if(!ok || data.error){ msg.textContent = 'Gagal: ' + (data.error || 'Tidak diketahui'); return; }
        msg.style.display = 'none';
        snap.pay(data.token, {
            onSuccess: function(){ window.location = @json(route('payment.success', $booking)); },
            onPending: function(){ window.location = @json(route('payment.pending', $booking)); },
            onError: function(){ alert('Pembayaran gagal.'); },
            onClose: function(){ alert('Popup pembayaran ditutup.'); }
        });
    })
    .catch(err => { msg.textContent = 'Error koneksi: ' + err; });
};
</script>
@endif
@endpush
