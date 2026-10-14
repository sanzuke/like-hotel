@extends('layouts.admin')
@section('page', 'Edit Closing')

@section('content')
<h1>Edit Data Closing {{ $r->number() }}</h1>
<div class="form-box" style="max-width:100%;margin:0 0 20px 0">
  <form method="post" action="{{ route('admin.sale-commissions.update', $r) }}">
    @csrf @method('PUT')
    @include('admin.partials.sale-commission-fields', ['r' => $r])
    <label>Status</label>
    <select name="status">@foreach(['closing', 'selesai', 'batal'] as $s)<option value="{{ $s }}" @selected(old('status', $r->status) === $s)>{{ $s }}</option>@endforeach</select>
    <br><br>
    <button class="btn">Simpan</button>
    <a class="btn-outline" href="{{ route('admin.sale-commissions') }}">Kembali</a>
  </form>
</div>
@endsection
