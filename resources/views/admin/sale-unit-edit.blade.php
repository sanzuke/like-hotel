@extends('layouts.admin')
@section('page', 'Edit Unit Dijual')

@section('content')
<h1>Edit Unit Dijual</h1>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <form method="post" action="{{ route('admin.sale-units.update', $r) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    @include('admin.partials.sale-unit-fields', ['r' => $r])
    <label>Status</label>
    <select name="status">
      @foreach(['aktif', 'terjual', 'nonaktif'] as $s)<option value="{{ $s }}" @selected(old('status', $r->status) === $s)>{{ $s }}</option>@endforeach
    </select>
    <label>Tambah Foto</label>
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
    <br><br>
    <button class="btn">Simpan Perubahan</button>
    <a class="btn-outline" href="{{ route('admin.sale-units') }}">Kembali</a>
  </form>
</div>

<h3>Galeri Foto</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px">
  @forelse($images as $img)
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px">
      <img src="{{ $img->url() }}" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:8px">
      @if($r->image === $img->image)<p><span class="badge">Cover</span></p>@endif
      <div class="actions" style="margin-top:8px">
        @if($r->image !== $img->image)
          <form class="inline" method="post" action="{{ route('admin.sale-units.images.cover', [$r, $img]) }}">@csrf<button class="btn-outline">Jadikan Cover</button></form>
        @endif
        <form class="inline" method="post" action="{{ route('admin.sale-units.images.destroy', [$r, $img]) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </div>
    </div>
  @empty
    <p>Belum ada foto.</p>
  @endforelse
</div>
@endsection
