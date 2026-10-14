@extends('layouts.admin')
@section('page', 'Jual Unit')

@section('content')
<h1>Kelola Unit Dijual</h1>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <h3>Tambah Unit Dijual</h3>
  <form method="post" action="{{ route('admin.sale-units.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.partials.sale-unit-fields', ['r' => null])
    <label>Foto Unit</label>
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small>Foto pertama menjadi cover.</small>
    <br><br><button class="btn">Simpan</button>
  </form>
</div>

<table class="table">
  <tr><th>Foto</th><th>Judul</th><th>Apartment</th><th>Tipe</th><th>Harga</th><th>Penjual</th><th>Status</th><th>Aksi</th></tr>
  @foreach($rows as $r)
    <tr>
      <td>@if($r->image)<img src="{{ $r->coverUrl() }}" alt="" style="width:90px;height:65px;object-fit:cover;border-radius:8px">@else - @endif</td>
      <td>{{ $r->title }}</td><td>{{ $r->apartment_name }}<br><small>{{ $r->location }}</small></td>
      <td>{{ $r->unit_type }}</td><td>{{ rupiah($r->price) }}</td>
      <td>{{ $r->seller_name }}<br><small>{{ $r->seller_phone }}</small></td>
      <td>{{ $r->status }}</td>
      <td>
        <div class="actions">
          <a class="btn" href="{{ route('admin.sale-units.edit', $r) }}">Edit</a>
          @foreach(['aktif', 'terjual', 'nonaktif'] as $st)
            @if($r->status !== $st)
              <form class="inline" method="post" action="{{ route('admin.sale-units.status', $r) }}">@csrf<input type="hidden" name="status" value="{{ $st }}"><button class="btn-outline">{{ ucfirst($st) }}</button></form>
            @endif
          @endforeach
          <form class="inline" method="post" action="{{ route('admin.sale-units.destroy', $r) }}" onsubmit="return confirm('Hapus unit ini beserta fotonya?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
        </div>
      </td>
    </tr>
  @endforeach
</table>
@endsection
