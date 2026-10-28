@extends('layouts.panel')
@section('title', 'Ganti Password')
@section('content')
<div class="page-head">
  <h1>Ganti Password</h1>
</div>
<p class="muted">Akun: <b>{{ auth('admin')->user()->username }}</b>. Gunakan password yang kuat dan tidak dipakai di layanan lain.</p>

<div class="form-box" style="max-width:520px;margin:20px 0">
  <form method="post" action="{{ route('admin.password.update') }}" autocomplete="off">
    @csrf @method('PUT')
    <label>Password Saat Ini</label>
    <input type="password" name="current_password" autocomplete="current-password" required>
    @error('current_password')<div class="field-error">{{ $message }}</div>@enderror

    <label>Password Baru</label>
    <input type="password" name="password" autocomplete="new-password" minlength="10" required>
    <small>Minimal 10 karakter, mengandung huruf dan angka.</small>
    @error('password')<div class="field-error">{{ $message }}</div>@enderror

    <label>Ulangi Password Baru</label>
    <input type="password" name="password_confirmation" autocomplete="new-password" required>

    <br><br>
    <button class="btn">Simpan Password</button>
  </form>
</div>
@endsection
