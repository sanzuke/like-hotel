@extends('layouts.site')
@section('title', $mode === 'login' ? 'Login Owner' : 'Daftar Owner')
@section('nofooter', '1')

@section('content')
<div class="form-box">
  <h2>{{ $mode === 'login' ? 'Login Owner Apartment' : 'Daftar Owner Apartment' }}</h2>

  @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

  @if($mode === 'login')
    <form method="post" action="{{ route('owner.login') }}">
      @csrf
      <label>Email</label>
      <input type="email" name="email" value="{{ old('email') }}" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button class="btn">Login</button>
      <a class="btn-outline" href="{{ route('owner.register') }}">Daftar Owner</a>
    </form>
  @else
    <form method="post" action="{{ route('owner.register') }}">
      @csrf
      <label>Nama Pemilik</label>
      <input name="name" value="{{ old('name') }}" required maxlength="150">
      <label>No WhatsApp</label>
      <input name="phone" value="{{ old('phone') }}" required maxlength="50">
      <label>Email</label>
      <input type="email" name="email" value="{{ old('email') }}" required maxlength="150">
      <label>Password (minimal 8 karakter)</label>
      <input type="password" name="password" required minlength="8">
      <label>Ulangi Password</label>
      <input type="password" name="password_confirmation" required minlength="8">
      <button class="btn">Daftar</button>
      <a class="btn-outline" href="{{ route('owner.login') }}">Login</a>
    </form>
    <p><small>Akun baru perlu disetujui admin sebelum dapat digunakan.</small></p>
  @endif
</div>
@endsection
