@extends('layouts.public')
@section('robots', 'noindex,follow')
@section('title', 'Daftar Akun - Like Hotel')
@section('content')
<div class="form-box">
  <h2>Daftar Akun Penyewa</h2>
  <p class="muted">Satu akun untuk semua booking. Data tamu terisi otomatis dan invoice tersimpan.</p>
  <form method="post" action="{{ route('guest.register.submit') }}">
    @csrf
    <label>Nama Lengkap</label>
    <input name="name" value="{{ old('name') }}" autocomplete="name" required>
    @error('name')<div class="field-error">{{ $message }}</div>@enderror
    <label>No WhatsApp</label>
    <input name="phone" value="{{ old('phone') }}" placeholder="Contoh: 082129248953" autocomplete="tel" required>
    @error('phone')<div class="field-error">{{ $message }}</div>@enderror
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
    @error('email')<div class="field-error">{{ $message }}</div>@enderror
    <label>Password (min. 8 karakter)</label>
    <input type="password" name="password" autocomplete="new-password" required>
    @error('password')<div class="field-error">{{ $message }}</div>@enderror
    <label>Ulangi Password</label>
    <input type="password" name="password_confirmation" autocomplete="new-password" required>
    @include('partials.turnstile')
    <button class="btn">Daftar</button>
    <a class="btn-outline" href="{{ route('guest.login') }}">Sudah punya akun? Masuk</a>
  </form>
  @include('auth.partials.google-button', ['label' => 'Daftar dengan Google', 'href' => route('guest.google.redirect')])
</div>
@endsection
