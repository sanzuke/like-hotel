@extends('layouts.public')
@section('title', 'Daftar Apartemen Disewakan Harian & Bulanan - Like Hotel')
@section('meta_description', 'Daftar apartemen siap sewa harian, mingguan, dan bulanan dengan harga transparan, owner terverifikasi, dan invoice otomatis di Like Hotel.')
@section('content')
<section class="apartments-hero">
  <span class="badge">Pilihan Apartment Terbaik</span>
  <h1>Daftar Apartment Siap Sewa</h1>
  <p>Temukan unit harian, mingguan, dan bulanan dengan harga transparan dan invoice otomatis.</p>

  @include('site._search', ['filters' => $filters, 'types' => $types, 'advanced' => true])
</section>

<section class="section">
  @if($errors->any())
    <div class="alert warn">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
  @endif

  @if(! empty($filters['checkin']) && ! empty($filters['checkout']))
    <p class="muted">Menampilkan unit yang kosong {{ $filters['checkin'] }} s/d {{ $filters['checkout'] }}.</p>
  @endif

  <div class="grid">
    @forelse($apartments as $a)
      @include('site._apartment-card', ['a' => $a])
    @empty
      <div class="alert">Tidak ada apartemen yang cocok dengan pencarian Anda. Coba ubah tanggal atau hapus sebagian filter.
        <a href="{{ route('apartments.index') }}">Reset pencarian</a></div>
    @endforelse
  </div>
</section>
@endsection
