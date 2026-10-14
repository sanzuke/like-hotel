@extends('layouts.site')
@section('title', 'Daftar Apartment - Like Hotel')

@section('content')
<section class="apartments-hero">
  <span class="badge">Pilihan Apartment Terbaik</span>
  <h1>Daftar Apartment Siap Sewa</h1>
  <p>Temukan unit harian, mingguan, dan bulanan dengan harga transparan dan invoice otomatis.</p>

  <form class="search search-wide" method="get" action="{{ route('apartments.index') }}">
    <input name="q" value="{{ request('q') }}" placeholder="Cari lokasi / nama apartment">
    <input type="date" name="checkin" value="{{ request('checkin') }}" min="{{ date('Y-m-d') }}">
    <input type="date" name="checkout" value="{{ request('checkout') }}" min="{{ date('Y-m-d') }}">
    <button class="btn">Cari</button>
  </form>
</section>

<section class="section">
  <div class="grid">
    @forelse($apartments as $a)
      @include('partials.apartment-card', ['a' => $a, 'badge' => true, 'cta' => 'Pesan'])
    @empty
      <div class="alert">Tidak ada apartment yang cocok dengan pencarian Anda.</div>
    @endforelse
  </div>
</section>
@endsection
