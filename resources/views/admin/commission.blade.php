@extends('layouts.panel')
@section('title', 'Komisi & Settlement')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Komisi & Settlement Owner</h1>
<div class="alert">Pembayaran masuk ke Like Hotel. Komisi default {{ config('likehotel.commission_rate') * 100 }}%, sisanya hak owner.</div>
<table class="table">
  <tr><th>Owner</th><th>WA</th><th>Booking</th><th>Total Lunas</th><th>Komisi Like Hotel</th><th>Hak Owner</th><th>Proses/Sudah Cair</th><th>Saldo Tersedia</th></tr>
  @foreach($rows as $r)
    <tr>
      <td>{{ $r->owner->name }}</td>
      <td>{{ $r->owner->phone }}</td>
      <td>{{ $r->bookings }}</td>
      <td>{{ Fmt::rupiah($r->gross) }}</td>
      <td>{{ Fmt::rupiah($r->commission) }}</td>
      <td><b>{{ Fmt::rupiah($r->earnings) }}</b></td>
      <td>{{ Fmt::rupiah($r->requested) }}</td>
      <td><b>{{ Fmt::rupiah($r->balance) }}</b></td>
    </tr>
  @endforeach
</table>
@endsection
