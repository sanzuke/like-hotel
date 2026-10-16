@extends('layouts.public')
@section('title', 'Cek Booking')
@section('content')
<div class="form-box">
  <h2>Cek Booking / Invoice</h2>
  @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
  <form method="post" action="{{ route('booking.check.submit') }}">
    @csrf
    <label>Kode Booking / Nomor Invoice</label>
    <input name="code" value="{{ old('code') }}" placeholder="Contoh: LH261017ABC123" required>
    <label>No WhatsApp saat booking</label>
    <input name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required>
    <button class="btn">Cek</button>
  </form>
</div>
@endsection
