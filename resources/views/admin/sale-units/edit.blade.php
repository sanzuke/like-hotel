@extends('layouts.panel')
@section('title', 'Edit Unit Dijual')
@section('content')
<h1>Edit Unit Dijual</h1>
<a class="btn-outline" href="{{ route('admin.sale-units.index') }}">← Kembali</a>

<div class="form-box" style="max-width:100%;margin:20px 0">
  <form method="post" action="{{ route('admin.sale-units.update', $r) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    @include('admin.sale-units._form')
    <label>Status</label>
    <select name="status">
      @foreach(['aktif', 'terjual', 'nonaktif'] as $s)<option value="{{ $s }}" @selected(old('status', $r->status) === $s)>{{ $s }}</option>@endforeach
    </select>
    <br><br><button class="btn">Simpan Perubahan</button>
  </form>
</div>

<h2>Galeri Foto</h2>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px">
  @foreach($images as $img)
    <div class="card" style="padding:10px">
      <img src="{{ asset('storage/sale_units/'.$img->image) }}" style="width:100%;height:150px;object-fit:cover;border-radius:12px" alt="">
      @if($r->image === $img->image)
        <p><b style="color:#087a35">Cover Utama</b></p>
      @else
        <form class="inline-form" method="post" action="{{ route('admin.sale-units.cover', [$r, $img]) }}">@csrf<button class="btn-outline">Jadikan Cover</button></form>
      @endif
      <form class="inline-form" method="post" action="{{ route('admin.sale-units.images.destroy', [$r, $img]) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
    </div>
  @endforeach
</div>
@endsection
