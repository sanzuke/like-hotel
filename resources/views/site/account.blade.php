@extends('layouts.public')
@section('robots', 'noindex,nofollow')
@section('title', 'Akun Saya - Like Hotel')
@section('content')
<section class="mb-page">
  <div class="mb-head">
    <div>
      <span class="badge"><i class="ms">person</i> Akun Saya</span>
      <h1>Halo, {{ $guest->name }}</h1>
      <p class="muted">Satu akun untuk menyewa, menyewakan unit, dan titip jual.</p>
    </div>
    <form method="post" action="{{ route('guest.logout') }}">@csrf<button class="btn-outline">Keluar</button></form>
  </div>

  @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
  @if(session('info'))<div class="alert">{{ session('info') }}</div>@endif

  <div class="apartment-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px">
    <article class="card" style="padding:22px">
      <i class="ms" style="font-size:34px;color:#087a35">key</i>
      <h2 style="margin:10px 0 6px">Booking Saya</h2>
      <p class="muted">{{ $bookingCount }} booking tersimpan di akun ini.</p>
      <a class="btn-outline" href="{{ route('guest.bookings') }}">Lihat Booking</a>
    </article>

    <article class="card" style="padding:22px">
      <i class="ms" style="font-size:34px;color:#087a35">real_estate_agent</i>
      <h2 style="margin:10px 0 6px">Titip Jual</h2>
      <p class="muted">{{ $saleRequestCount }} pengajuan titip jual.</p>
      <a class="btn-outline" href="{{ route('account.sale-requests') }}">Pengajuan Saya</a>
      <a class="btn" href="{{ route('sale-request.create') }}" style="margin-top:8px">+ Ajukan Unit</a>
    </article>

    <article class="card" style="padding:22px">
      <i class="ms" style="font-size:34px;color:#087a35">apartment</i>
      <h2 style="margin:10px 0 6px">Mode Pemilik</h2>
      @if($guest->owner)
        <p class="muted">Aktif sejak {{ $guest->owner->created_at?->translatedFormat('d M Y') }}. Kelola unit sewa dan pencairan dana.</p>
        <a class="btn" href="{{ route('owner.dashboard') }}">Buka Dashboard Pemilik</a>
      @else
        <p class="muted">Punya unit untuk disewakan? Aktifkan profil pemilik tanpa membuat akun baru.</p>
        <a class="btn" href="{{ route('owner.activate') }}">Aktifkan Mode Pemilik</a>
      @endif
    </article>
  </div>

  <p class="muted" style="margin-top:22px">Ingin memperbarui nama atau nomor WhatsApp? Buka <a href="{{ route('guest.bookings') }}">Booking Saya</a> lalu simpan di bagian Data Saya.</p>
</section>
@endsection
