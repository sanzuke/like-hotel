@extends('layouts.panel')
@section('title', 'Komisi Penjualan')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Komisi Penjualan</h1>
<div class="stats">
  <div class="stat"><p>Total Nilai Deal</p><h2>{{ Fmt::rupiah($totalDeal) }}</h2></div>
  <div class="stat"><p>Total Komisi Like Hotel</p><h2>{{ Fmt::rupiah($totalCommission) }}</h2></div>
  <div class="stat"><p>Bersih Penjual</p><h2>{{ Fmt::rupiah($totalNet) }}</h2></div>
</div>

<div class="form-box" style="max-width:100%;margin:20px 0">
  <h3>Catat Closing</h3>
  <form method="post" action="{{ route('admin.sale-commissions.store') }}">
    @csrf
    @include('admin.sale-commissions._form')
    <br><br><button class="btn">Simpan</button>
  </form>
</div>

<table class="table">
  <tr><th>Tanggal</th><th>Penjual</th><th>Apartment</th><th>Harga Deal</th><th>Komisi</th><th>Biaya Lain</th><th>Bersih Penjual</th><th>Status</th><th>Aksi</th></tr>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->created_at->format('d-m-Y') }}</td>
      <td>{{ $r->seller_name }}</td>
      <td>{{ $r->apartment_name }}</td>
      <td>{{ Fmt::rupiah($r->deal_price) }}</td>
      <td>{{ Fmt::rupiah($r->commission_amount) }}<br><small>{{ $r->commission_type === 'percent' ? (float) $r->commission_value.'%' : 'nominal' }}</small></td>
      <td>{{ Fmt::rupiah($r->other_cost) }}</td>
      <td><b>{{ Fmt::rupiah($r->seller_net_amount) }}</b></td>
      <td>{{ $r->status }}</td>
      <td>
        <a class="btn-outline" target="_blank" href="{{ route('admin.sale-commissions.invoice', $r) }}">Invoice</a>
        <a class="btn-outline" href="{{ route('admin.sale-commissions.edit', $r) }}">Edit</a>
        <form class="inline-form" method="post" action="{{ route('admin.sale-commissions.destroy', $r) }}" onsubmit="return confirm('Hapus data closing ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </td>
    </tr>
  @empty
    <tr><td colspan="9">Belum ada data closing.</td></tr>
  @endforelse
</table>
@endsection
