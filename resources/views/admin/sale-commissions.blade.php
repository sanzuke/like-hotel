@extends('layouts.admin')
@section('page', 'Komisi Penjualan')

@section('content')
<h1>Komisi Penjualan Unit</h1>

<div class="stats">
  <div class="stat"><p>Total Closing</p><h2>{{ $rows->count() }}</h2></div>
  <div class="stat"><p>Total Harga Deal</p><h2>{{ rupiah($totalDeal) }}</h2></div>
  <div class="stat"><p>Total Komisi</p><h2>{{ rupiah($totalCommission) }}</h2></div>
  <div class="stat"><p>Total Hak Penjual</p><h2>{{ rupiah($totalSellerNet) }}</h2></div>
</div>
<br>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <h3>Catat Closing</h3>
  <form method="post" action="{{ route('admin.sale-commissions.store') }}">
    @csrf
    @include('admin.partials.sale-commission-fields', ['r' => null])
    <br><br><button class="btn">Simpan</button>
  </form>
</div>

<table class="table">
  <tr><th>No</th><th>Tanggal</th><th>Penjual</th><th>Apartment</th><th>Harga Deal</th><th>Komisi</th><th>Biaya Lain</th><th>Hak Penjual</th><th>Status</th><th>Aksi</th></tr>
  @foreach($rows as $r)
    <tr>
      <td>{{ $r->number() }}</td><td>{{ $r->created_at->format('d-m-Y') }}</td><td>{{ $r->seller_name }}</td><td>{{ $r->apartment_name }}</td>
      <td>{{ rupiah($r->deal_price) }}</td>
      <td>{{ rupiah($r->commission_amount) }}<br><small>{{ $r->commission_type === 'percent' ? rtrim(rtrim(number_format($r->commission_value, 2), '0'), '.').'%' : 'nominal' }}</small></td>
      <td>{{ rupiah($r->other_cost) }}</td><td><b>{{ rupiah($r->seller_net_amount) }}</b></td><td>{{ $r->status }}</td>
      <td class="actions">
        <a class="btn-outline" href="{{ route('admin.sale-commissions.invoice', $r) }}" target="_blank">Invoice</a>
        <a class="btn-outline" href="{{ route('admin.sale-commissions.edit', $r) }}">Edit</a>
        <form class="inline" method="post" action="{{ route('admin.sale-commissions.destroy', $r) }}" onsubmit="return confirm('Hapus data closing ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </td>
    </tr>
  @endforeach
</table>
@endsection
