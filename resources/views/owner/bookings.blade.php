@extends('layouts.owner')
@section('page', 'Booking Unit Saya')

@section('content')
<h1>Booking Unit Saya</h1>
<table class="table">
  <tr><th>Kode</th><th>Tamu</th><th>Apartment</th><th>Total</th><th>Komisi</th><th>Hak Owner</th><th>Status</th><th>WhatsApp</th></tr>
  @foreach($bookings as $b)
    @php($msg = "Halo {$b->guest_name}, kami dari owner {$b->apartment->name}. Booking Anda kode {$b->booking_code} status {$b->payment_status}.")
    <tr>
      <td>{{ $b->booking_code }}</td>
      <td>{{ $b->guest_name }}</td>
      <td>{{ $b->apartment->name }}</td>
      <td>{{ rupiah($b->total_price) }}</td>
      <td>{{ rupiah($b->commission_amount) }}</td>
      <td><b>{{ rupiah($b->owner_amount) }}</b></td>
      <td>{{ $b->payment_status }}</td>
      <td><a class="btn-outline" target="_blank" rel="noopener" href="{{ wa_link($b->guest_phone, $msg) }}">Chat Tamu</a></td>
    </tr>
  @endforeach
</table>
@endsection
