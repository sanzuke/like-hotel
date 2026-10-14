@extends('layouts.admin')
@section('page', 'Komisi & Settlement')

@section('content')
<h1>Komisi &amp; Settlement Owner</h1>
<div class="alert">Model Opsi 1: pembayaran masuk ke Like Hotel. Komisi default {{ config('likehotel.commission_rate') * 100 }}%, hak owner {{ 100 - config('likehotel.commission_rate') * 100 }}%.</div>
<table class="table">
  <tr><th>Owner</th><th>WA</th><th>Booking</th><th>Total Lunas</th><th>Komisi Like Hotel</th><th>Hak Owner</th><th>Proses/Sudah Cair</th><th>Saldo Tersedia</th></tr>
  @foreach($rows as $r)
    <tr>
      <td>{{ $r['owner']->name }}</td><td>{{ $r['owner']->phone }}</td><td>{{ $r['total_booking'] }}</td>
      <td>{{ rupiah($r['gross']) }}</td><td>{{ rupiah($r['commission']) }}</td>
      <td><b>{{ rupiah($r['owner_amount']) }}</b></td><td>{{ rupiah($r['requested']) }}</td><td><b>{{ rupiah($r['available']) }}</b></td>
    </tr>
  @endforeach
</table>
@endsection
