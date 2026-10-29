@extends('layouts.public')
@section('robots', 'noindex,follow')
@section('title', 'Masuk - Like Hotel')
@section('content')
<div class="form-box">
  <h2>Masuk ke Akun Anda</h2>
  <p class="muted">Masuk untuk booking apartemen, melihat riwayat, dan mengakses invoice.</p>
  @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
  @if(str_contains((string) session('url.intended'), '/booking'))<div class="alert warn">Masuk atau daftar dulu untuk melanjutkan booking.</div>@endif
  @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
  <form method="post" action="{{ route('guest.login.submit') }}">
    @csrf
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required>
    <label>Password</label>
    <input type="password" name="password" autocomplete="current-password" required>
    @include('partials.turnstile')
    <button class="btn">Masuk</button>
    <a class="btn-outline" href="{{ route('guest.register') }}">Daftar Akun</a>
  </form>
  @include('auth.partials.google-button', ['label' => 'Masuk dengan Google', 'href' => route('guest.google.redirect')])
  <p class="muted" style="margin-top:18px;font-size:13px">Pemilik apartemen? <a href="{{ route('owner.login') }}">Login Owner</a></p>
</div>
@endsection
