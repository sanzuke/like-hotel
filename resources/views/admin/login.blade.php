@extends('layouts.site')
@section('title', 'Login Admin')
@section('nofooter', '1')

@section('content')
<div class="form-box">
  <h2>Login Admin Like Hotel</h2>
  @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
  <form method="post" action="{{ route('admin.login') }}">
    @csrf
    <label>Email atau Username</label>
    <input name="login" value="{{ old('login') }}" required autocomplete="username">
    <label>Password</label>
    <input type="password" name="password" required autocomplete="current-password">
    <button class="btn">Login</button>
  </form>
</div>
@endsection
