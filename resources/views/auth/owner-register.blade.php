@extends('layouts.public')
@section('title', 'Daftar Owner')
@section('content')
<div class="form-box">
  <h2>Daftar Owner Apartment</h2>
  <div class="alert">Akun baru akan aktif setelah disetujui admin Like Hotel.</div>
  <form method="post" action="{{ route('owner.register.submit') }}">
    @csrf
    <label>Nama Pemilik</label>
    <input name="name" value="{{ old('name') }}" required>
    @error('name')<div class="field-error">{{ $message }}</div>@enderror
    <label>No WhatsApp</label>
    <input name="phone" value="{{ old('phone') }}" required>
    @error('phone')<div class="field-error">{{ $message }}</div>@enderror
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email') }}" required>
    @error('email')<div class="field-error">{{ $message }}</div>@enderror
    <label>Password (min. 8 karakter)</label>
    <input type="password" name="password" autocomplete="new-password" required>
    @error('password')<div class="field-error">{{ $message }}</div>@enderror
    <label>Ulangi Password</label>
    <input type="password" name="password_confirmation" autocomplete="new-password" required>
    <button class="btn">Daftar</button>
    <a class="btn-outline" href="{{ route('owner.login') }}">Login</a>
  </form>
  @include('auth.partials.google-button', ['label' => 'Daftar dengan Google'])
</div>
@endsection
