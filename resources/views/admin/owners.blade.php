@extends('layouts.panel')
@section('title', 'Kelola Owner')
@section('content')
<h1>Kelola Owner</h1>
<div class="alert">Owner yang baru mendaftar berstatus <b>pending</b> dan baru bisa login setelah Anda mengaktifkannya.</div>
<table class="table">
  <tr><th>Nama</th><th>WA</th><th>Email</th><th>Status</th><th>Unit</th><th>Aksi</th></tr>
  @foreach($owners as $o)
    <tr>
      <td>{{ $o->name }}</td>
      <td>{{ $o->phone }}</td>
      <td>{{ $o->email }}</td>
      <td><b style="color:{{ \App\Support\Fmt::statusColor($o->status) }}">{{ $o->status }}</b></td>
      <td>{{ $o->apartments_count }}</td>
      <td>
        @if($o->status !== 'aktif')
          <form class="inline-form" method="post" action="{{ route('admin.owners.status', $o) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="aktif"><button class="btn-outline">Aktifkan</button></form>
        @else
          <form class="inline-form" method="post" action="{{ route('admin.owners.status', $o) }}" onsubmit="return confirm('Nonaktifkan owner ini?')">@csrf @method('PATCH')<input type="hidden" name="status" value="nonaktif"><button class="btn-outline">Nonaktifkan</button></form>
        @endif
      </td>
    </tr>
  @endforeach
</table>
@endsection
