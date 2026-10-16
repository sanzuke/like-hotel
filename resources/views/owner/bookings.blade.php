@extends('layouts.panel')
@section('title', 'Booking Unit Saya')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Booking Unit Saya</h1>
<table class="table">
  <tr><th>Kode</th><th>Apartment</th><th>Tamu</th><th>Periode</th><th>Total</th><th>Hak Owner</th><th>Status</th><th>WhatsApp</th></tr>
  @forelse($bookings as $b)
    @php $msg = "Halo {$b->guest_name}, ini konfirmasi booking Anda di {$b->apartment->name}.\nKode: {$b->booking_code}\nCheck-in: {$b->checkin->toDateString()}\nCheck-out: {$b->checkout->toDateString()}"; @endphp
    <tr>
      <td><b>{{ $b->booking_code }}</b></td>
      <td>{{ $b->apartment->name }}</td>
      <td>{{ $b->guest_name }}<br><small>{{ $b->guest_phone }}</small></td>
      <td>{{ $b->checkin->toDateString() }}<br>s/d {{ $b->checkout->toDateString() }}</td>
      <td>{{ Fmt::rupiah($b->total_price) }}</td>
      <td>{{ Fmt::rupiah($b->owner_amount) }}</td>
      <td><span style="color:{{ Fmt::statusColor($b->payment_status) }};font-weight:800">{{ $b->payment_status }}</span><br><small>{{ $b->booking_status }}</small></td>
      <td><a class="btn-outline" target="_blank" href="{{ Fmt::waLink($b->guest_phone, $msg) }}">Chat Tamu</a></td>
    </tr>
  @empty
    <tr><td colspan="8">Belum ada booking untuk unit Anda.</td></tr>
  @endforelse
</table>
@endsection
