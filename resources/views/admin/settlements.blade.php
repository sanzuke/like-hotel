@extends('layouts.admin')
@section('page', 'Pencairan Owner')

@section('content')
<h1>Pencairan Owner</h1>

<h2>Ringkasan Saldo Owner</h2>
<table class="table">
  <tr><th>Owner</th><th>Kontak</th><th>Total Hak Owner</th><th>Diminta / Dicairkan</th><th>Sudah Dibayar</th><th>Saldo Tersedia</th><th>Aksi</th></tr>
  @foreach($summary as $s)
    <tr>
      <td>{{ $s['owner']->name }}</td>
      <td>{{ $s['owner']->phone }}<br><small>{{ $s['owner']->email }}</small></td>
      <td>{{ rupiah($s['total_hak']) }}</td><td>{{ rupiah($s['requested']) }}</td><td>{{ rupiah($s['paid']) }}</td>
      <td><b>{{ rupiah($s['available']) }}</b></td>
      <td>
        @if($s['available'] > 0)
          <form class="inline" method="post" action="{{ route('admin.settlements.pay-owner', $s['owner']) }}" onsubmit="return confirm('Cairkan seluruh saldo {{ rupiah($s['available']) }} untuk {{ $s['owner']->name }}?')">@csrf<button class="btn-outline">Cairkan Saldo</button></form>
        @endif
      </td>
    </tr>
  @endforeach
</table>
<br>

<h2>Daftar Pengajuan</h2>
<table class="table">
  <tr><th>Tanggal</th><th>Owner</th><th>Nominal</th><th>Rekening</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr>
  @forelse($rows as $r)
    @php($msg = "Halo {$r->owner->name}, pencairan dana Like Hotel sebesar ".rupiah($r->amount)." berstatus ".strtoupper($r->status).'.')
    <tr>
      <td>{{ $r->created_at->format('d-m-Y H:i') }}@if($r->paid_at)<br><small>Dibayar: {{ $r->paid_at->format('d-m-Y H:i') }}</small>@endif</td>
      <td>{{ $r->owner->name }}<br><small>{{ $r->owner->phone }}</small></td>
      <td><b>{{ rupiah($r->amount) }}</b></td>
      <td>{{ $r->bank_name }}<br>{{ $r->bank_account_name }}<br>{{ $r->bank_account_number }}</td>
      <td><span style="color:{{ $r->statusColor() }};font-weight:800">{{ strtoupper($r->status) }}</span></td>
      <td>{{ $r->notes }}<br><small>{{ $r->admin_notes }}</small></td>
      <td>
        <div class="actions">
          @if($r->status === 'pending')
            <form class="inline" method="post" action="{{ route('admin.settlements.transition', [$r, 'approve']) }}">@csrf<button class="btn-outline">Approve</button></form>
          @endif
          @if(in_array($r->status, ['pending', 'approved']))
            <form class="inline" method="post" action="{{ route('admin.settlements.transition', [$r, 'paid']) }}" onsubmit="return confirm('Tandai sudah ditransfer?')">@csrf<button class="btn-outline">Tandai Dibayar</button></form>
          @endif
          @if($r->status === 'pending')
            <form class="inline" method="post" action="{{ route('admin.settlements.transition', [$r, 'reject']) }}" onsubmit="return confirm('Tolak pengajuan ini?')">@csrf<button class="btn-outline">Tolak</button></form>
          @endif
          <a class="btn-outline" target="_blank" rel="noopener" href="{{ wa_link($r->owner->phone, $msg) }}">WA</a>
        </div>
      </td>
    </tr>
  @empty
    <tr><td colspan="7">Belum ada pengajuan.</td></tr>
  @endforelse
</table>
@endsection
