@extends('layouts.panel')
@section('title', 'Edit Closing')
@section('content')
<h1>Edit Data Closing</h1>
<a class="btn-outline" href="{{ route('admin.sale-commissions.index') }}">← Kembali</a>
<div class="form-box" style="max-width:100%;margin:20px 0">
  <form method="post" action="{{ route('admin.sale-commissions.update', $r) }}">
    @csrf @method('PUT')
    @include('admin.sale-commissions._form')
    <label>Status</label>
    <select name="status">
      @foreach(['closing', 'lunas', 'batal'] as $s)<option value="{{ $s }}" @selected(old('status', $r->status) === $s)>{{ $s }}</option>@endforeach
    </select>
    <br><br><button class="btn">Simpan Perubahan</button>
  </form>
</div>
@endsection
