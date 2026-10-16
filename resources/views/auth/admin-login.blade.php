@extends('layouts.public')
@section('title', 'Login Admin')
@section('content')
<div class="form-box">
  <h2>Login Admin Like Hotel</h2>
  @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
  <form method="post" action="{{ route('admin.login.submit') }}">
    @csrf
    <label>Username / Email</label>
    <input name="login" value="{{ old('login') }}" autocomplete="username" required>
    <label>Password</label>
    <input type="password" name="password" autocomplete="current-password" required>
    <button class="btn">Login</button>
  </form>
</div>
@endsection
