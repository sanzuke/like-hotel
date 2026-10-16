@extends('layouts.public')
@section('title', 'Login Owner')
@section('content')
<div class="form-box">
  <h2>Login Owner Apartment</h2>
  @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
  <form method="post" action="{{ route('owner.login.submit') }}">
    @csrf
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required>
    <label>Password</label>
    <input type="password" name="password" autocomplete="current-password" required>
    <button class="btn">Login</button>
    <a class="btn-outline" href="{{ route('owner.register') }}">Daftar Owner</a>
  </form>
</div>
@endsection
