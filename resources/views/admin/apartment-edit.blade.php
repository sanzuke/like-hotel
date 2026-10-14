@extends('layouts.admin')
@section('page', 'Edit Apartment')

@section('content')
<h1>Edit Apartment</h1>

<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <form method="post" action="{{ route('admin.apartments.update', $apt) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    @include('admin.partials.apartment-fields', ['apt' => $apt])

    <label>Status</label>
    <select name="status">
      @foreach(['aktif', 'maintenance', 'nonaktif'] as $s)
        <option value="{{ $s }}" @selected(old('status', $apt->status) === $s)>{{ $s }}</option>
      @endforeach
    </select>

    <label>Tambah Foto</label>
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>

    <br><br>
    <button class="btn">Simpan Perubahan</button>
    <a class="btn-outline" href="{{ route('admin.apartments') }}">Kembali</a>
  </form>
</div>

<h3>Galeri Foto</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px">
  @forelse($images as $img)
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px">
      <img src="{{ $img->url() }}" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:8px">
      @if($apt->image === $img->image)<p><span class="badge">Cover</span></p>@endif
      <div class="actions" style="margin-top:8px">
        @if($apt->image !== $img->image)
          <form class="inline" method="post" action="{{ route('admin.apartments.images.cover', [$apt, $img]) }}">@csrf<button class="btn-outline">Jadikan Cover</button></form>
        @endif
        <form class="inline" method="post" action="{{ route('admin.apartments.images.destroy', [$apt, $img]) }}" onsubmit="return confirm('Hapus foto ini?')">@csrf @method('DELETE')<button class="btn-outline">Hapus</button></form>
      </div>
    </div>
  @empty
    <p>Belum ada foto.</p>
  @endforelse
</div>
@endsection
