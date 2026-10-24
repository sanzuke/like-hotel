@extends('layouts.panel')
@section('title', 'Booking Unit Saya')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Booking Unit Saya</h1>
<form id="owner-booking-filter" method="get" action="{{ route('owner.bookings.index') }}"></form>
<table class="table">
  <tr><th>Kode</th><th>Apartment</th><th>Tamu</th><th>Periode</th><th>Total</th><th>Hak Owner</th><th>Status</th><th>WhatsApp</th></tr>
  <tr class="filter-row">
    <td><input form="owner-booking-filter" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Kode / nama / HP…" aria-label="Filter kode, tamu, atau telepon"></td>
    <td>
      <select form="owner-booking-filter" name="apartment" onchange="this.form.submit()" aria-label="Filter apartment">
        <option value="">Semua</option>
        @foreach($filterApartments as $a)<option value="{{ $a->id }}" @selected(($filters['apartment'] ?? '') === (string) $a->id)>{{ $a->name }}</option>@endforeach
      </select>
    </td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td>
      <select form="owner-booking-filter" name="payment" onchange="this.form.submit()" aria-label="Filter status pembayaran">
        <option value="">Semua</option>
        @foreach($payments as $p)<option value="{{ $p }}" @selected(($filters['payment'] ?? '') === $p)>{{ $p }}</option>@endforeach
      </select>
    </td>
    <td class="filter-actions">
      <button form="owner-booking-filter" class="btn-outline">Cari</button>
      @if(array_filter($filters))<a href="{{ route('owner.bookings.index') }}" class="filter-reset">Reset</a>@endif
    </td>
  </tr>
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
    <tr><td colspan="8">
      @if(array_filter($filters))Tidak ada data yang cocok dengan filter. <a href="{{ route('owner.bookings.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada booking untuk unit Anda.@endif
    </td></tr>
  @endforelse
</table>

@include('partials.pagination', ['paginator' => $bookings])
@endsection
