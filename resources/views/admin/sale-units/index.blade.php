@extends('layouts.panel')
@section('title', 'Unit Dijual')
@section('content')
<h1>Unit Dijual</h1>
<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <h3>Tambah Unit Dijual</h3>
  <form method="post" action="{{ route('admin.sale-units.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.sale-units._form')
    <br><br><button class="btn">Simpan</button>
  </form>
</div>

<table class="table">
  <tr><th>Foto</th><th>Judul</th><th>Apartment</th><th>Harga</th><th>Penjual</th><th>Status</th><th>Aksi</th></tr>
  @foreach($rows as $r)
    <tr>
      <td>@if($r->image)<img src="{{ asset('storage/sale_units/'.$r->image) }}" style="width:90px;height:65px;object-fit:cover;border-radius:8px" alt="">@else - @endif</td>
      <td>{{ $r->title }}</td>
      <td>{{ $r->apartment_name }}<br><small>{{ $r->location }}</small></td>
      <td>{{ \App\Support\Fmt::rupiah($r->price) }}</td>
      <td>{{ $r->seller_name }}<br><small>{{ $r->seller_phone }}</small></td>
      <td>
        <form class="inline-form" method="post" action="{{ route('admin.sale-units.status', $r) }}">
          @csrf @method('PATCH')
          <select name="status" onchange="this.form.submit()">
            @foreach(['aktif', 'terjual', 'nonaktif'] as $s)<option value="{{ $s }}" @selected($r->status === $s)>{{ $s }}</option>@endforeach
          </select>
        </form>
      </td>
      <td>
        <a class="btn-outline" href="{{ route('admin.sale-units.edit', $r) }}">Edit</a>
        <form class="inline-form" method="post" action="{{ route('admin.sale-units.destroy', $r) }}" onsubmit="return confirm('Hapus unit ini beserta fotonya?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </td>
    </tr>
  @endforeach
</table>
@endsection
