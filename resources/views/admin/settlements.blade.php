@extends('layouts.panel')
@section('title', 'Pencairan Owner')
@php use App\Support\Fmt; @endphp
@section('content')
<h1>Pencairan Dana Owner</h1>
<div class="alert">Pantau saldo owner, pengajuan pencairan, dan status pembayaran dana owner.</div>

<h2>Ringkasan Saldo Owner</h2>
<table class="table">
  <tr><th>Owner</th><th>Total Hak Owner</th><th>Proses/Sudah Cair</th><th>Sudah Dibayar</th><th>Saldo Tersedia</th><th>Aksi</th><th>WA</th></tr>
  @foreach($owners as $o)
    @php $msg = "SALDO OWNER LIKE HOTEL\nOwner: {$o->name}\nTotal Hak Owner: ".Fmt::rupiah($o->earnings)."\nProses/Sudah Cair: ".Fmt::rupiah($o->requested)."\nSaldo Tersedia: ".Fmt::rupiah($o->balance); @endphp
    <tr>
      <td><b>{{ $o->name }}</b><br><small>{{ $o->email }}</small></td>
      <td>{{ Fmt::rupiah($o->earnings) }}</td>
      <td>{{ Fmt::rupiah($o->requested) }}</td>
      <td>{{ Fmt::rupiah($o->paid) }}</td>
      <td><b style="color:#087a35">{{ Fmt::rupiah($o->balance) }}</b></td>
      <td>
        @if($o->balance > 0)
          <form class="inline-form" method="post" action="{{ route('admin.settlements.pay-owner', $o) }}" onsubmit="return confirm('Bayarkan saldo owner sebesar {{ Fmt::rupiah($o->balance) }}?')">@csrf<button class="btn-outline">Bayarkan Saldo</button></form>
        @else
          <b style="color:#6b7280">Tidak ada saldo</b>
        @endif
      </td>
      <td><a class="btn-outline" target="_blank" href="{{ Fmt::waLink($o->phone, $msg) }}">Chat Owner</a></td>
    </tr>
  @endforeach
</table>
<br>

<h2>Riwayat Pengajuan / Pencairan</h2>
<table class="table">
  <tr><th>Owner</th><th>Nominal</th><th>Bank</th><th>Rekening</th><th>Status</th><th>Tanggal</th><th>Aksi</th><th>WA</th></tr>
  @foreach($rows as $r)
    @php $msg = "PENCAIRAN DANA LIKE HOTEL\nOwner: {$r->owner->name}\nNominal: ".Fmt::rupiah($r->amount)."\nStatus: {$r->status}\nBank: {$r->bank_name}\nRekening: {$r->bank_account_number}"; @endphp
    <tr>
      <td>{{ $r->owner->name }}<br><small>{{ $r->owner->email }}</small></td>
      <td>{{ Fmt::rupiah($r->amount) }}</td>
      <td>{{ $r->bank_name }}</td>
      <td>{{ $r->bank_account_name }}<br>{{ $r->bank_account_number }}</td>
      <td>
        <span style="color:{{ Fmt::statusColor($r->status) }};font-weight:800">{{ strtoupper($r->status) }}</span>
        @if($r->admin_notes)<br><small>{{ $r->admin_notes }}</small>@endif
      </td>
      <td>{{ $r->created_at->format('d-m-Y H:i') }}@if($r->paid_at)<br><small>Dibayar: {{ $r->paid_at->format('d-m-Y H:i') }}</small>@endif</td>
      <td>
        @if($r->status === 'pending')
          <form class="inline-form" method="post" action="{{ route('admin.settlements.approve', $r) }}" onsubmit="return confirm('Approve pencairan ini?')">@csrf<button class="btn-outline">Approve</button></form>
          <form class="inline-form" method="post" action="{{ route('admin.settlements.reject', $r) }}" onsubmit="return confirm('Tolak pencairan ini?')">@csrf<button class="btn-outline">Tolak</button></form>
        @endif
        @if(in_array($r->status, ['pending', 'approved']))
          <form class="inline-form" method="post" action="{{ route('admin.settlements.paid', $r) }}" onsubmit="return confirm('Tandai pencairan sudah dibayar?')">@csrf<button class="btn-outline">Tandai Dibayar</button></form>
        @endif
        @if($r->status === 'paid')<b style="color:#087a35">Selesai</b>@endif
        @if($r->status === 'rejected')<b style="color:#991b1b">Ditolak</b>@endif
      </td>
      <td><a class="btn-outline" target="_blank" href="{{ Fmt::waLink($r->owner->phone, $msg) }}">Chat Owner</a></td>
    </tr>
  @endforeach
</table>
@endsection
