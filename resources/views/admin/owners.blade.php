@extends('layouts.admin')
@section('page', 'Kelola Owner')

@section('content')
<h1>Kelola Owner</h1>
<table class="table">
  <tr><th>Nama</th><th>WA</th><th>Email</th><th>Status</th><th>Unit</th><th>Aksi</th></tr>
  @foreach($owners as $o)
    <tr>
      <td>{{ $o->name }}</td><td>{{ $o->phone }}</td><td>{{ $o->email }}</td>
      <td><b style="color:{{ ['aktif' => '#087a35', 'pending' => '#b45309'][$o->status] ?? '#991b1b' }}">{{ $o->status }}</b></td>
      <td>{{ $o->apartments_count }}</td>
      <td class="actions">
        @foreach(['aktif' => ($o->status === 'pending' ? 'Setujui' : 'Aktifkan'), 'nonaktif' => 'Nonaktifkan'] as $st => $label)
          @if($o->status !== $st)
            <form class="inline" method="post" action="{{ route('admin.owners.status', $o) }}">@csrf<input type="hidden" name="status" value="{{ $st }}"><button class="btn-outline">{{ $label }}</button></form>
          @endif
        @endforeach
      </td>
    </tr>
  @endforeach
</table>
@endsection
