@extends('layouts.site')
@section('title', 'Cek Booking')

@section('content')
<div class="form-box">
  <h2>Cek Booking / Invoice</h2>
  <p class="muted">Masukkan kode booking beserta email atau nomor WhatsApp yang dipakai saat booking.</p>

  @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

  <form method="post" action="{{ route('booking.lookup') }}">
    @csrf
    <label>Kode Booking / Invoice</label>
    <input name="code" value="{{ old('code') }}" placeholder="Contoh: LH20260616220135" required>
    <label>Email atau No WhatsApp</label>
    <input name="contact" value="{{ old('contact') }}" placeholder="Email / nomor WhatsApp saat booking" required>
    <button class="btn">Cek</button>
  </form>
</div>
@endsection
