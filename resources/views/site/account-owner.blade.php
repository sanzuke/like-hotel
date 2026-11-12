@extends('layouts.public')
@section('robots', 'noindex,nofollow')
@section('title', 'Aktifkan Mode Pemilik - Like Hotel')
@section('content')
<section class="mb-page">
  <div class="form-box" style="max-width:640px">
    <span class="badge"><i class="ms">apartment</i> Mode Pemilik</span>
    <h1>Aktifkan Mode Pemilik</h1>
    <p class="muted">Tetap memakai satu akun (<b>{{ $guest->email }}</b>). Setelah aktif, Anda bisa menambahkan unit sewa lewat dashboard pemilik. Setiap unit tetap diverifikasi admin sebelum tayang.</p>

    @if(session('error'))<div class="alert danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert danger">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>@endif

    <form method="post" action="{{ route('owner.activate.store') }}">
      @csrf
      <label>Nama Pemilik</label>
      <input name="name" value="{{ old('name', $guest->name) }}" required>
      <label>No WhatsApp</label>
      <input name="phone" value="{{ old('phone', $guest->phone) }}" placeholder="Contoh: 082129248953" required>
      <label>Email</label>
      <input value="{{ $guest->email }}" disabled>
      <div class="modal-actions" style="justify-content:flex-start">
        <a class="btn-outline" href="{{ route('account.index') }}">Batal</a>
        <button class="btn">Aktifkan Sekarang</button>
      </div>
    </form>

    <p class="muted" style="margin-top:16px">Sudah punya akun pemilik lama? <a href="{{ route('owner.login') }}">Masuk lewat halaman pemilik</a>.</p>
  </div>
</section>
@endsection
