@extends('layouts.public')
@section('robots', 'noindex,nofollow')
@section('title', 'Booking Saya - Like Hotel')
@section('content')
<section class="mb-page">
  <div class="mb-head">
    <div>
      <span class="badge"><i class="ms">person</i> Akun Penyewa</span>
      <h1>Booking Saya</h1>
      <p class="muted">Halo, {{ $guest->name }}. Semua booking dan invoice Anda ada di sini.</p>
    </div>
    <form method="post" action="{{ route('guest.logout') }}">@csrf<button class="btn-outline">Keluar</button></form>
  </div>

  @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

  <div class="mb-grid">
    <div class="mb-list">
      @forelse($bookings as $b)
        <article class="mb-card">
          <div class="mb-top">
            <div>
              <b>{{ $b->apartment->name }}</b>
              <small>{{ $b->booking_code }}</small>
            </div>
            <span class="badge" style="color:{{ \App\Support\Fmt::statusColor($b->booking_status === 'dibayar' ? 'lunas' : $b->payment_status) }}">{{ ucfirst($b->booking_status === 'dibayar' ? 'Lunas' : $b->payment_status) }}</span>
          </div>
          <div class="mb-meta">
            <span><i class="ms">calendar_month</i> {{ $b->checkin_label }} &ndash; {{ $b->checkout_label }} &middot; {{ $b->nights() }} malam</span>
            <span><i class="ms">payments</i> {{ \App\Support\Fmt::rupiah($b->total_price) }}</span>
          </div>
          <div class="mb-actions">
            <a class="btn-outline" href="{{ route('invoice.show', $b->access_token) }}">Invoice</a>
            @if($b->booking_status === 'baru')<a class="btn" href="{{ route('payment.show', $b->access_token) }}">Bayar Sekarang</a>@endif
          </div>
        </article>
      @empty
        <div class="mb-empty">
          <i class="ms">key</i>
          <b>Belum ada booking</b>
          <p class="muted">Pilih unit yang Anda suka lalu mulai booking.</p>
          <a class="btn" href="{{ route('apartments.index') }}">Cari Apartemen</a>
        </div>
      @endforelse
      {{ $bookings->links() }}
    </div>

    <aside class="mb-profile">
      <h2>Data Saya</h2>
      <p class="muted">Terisi otomatis di form booking.</p>
      <form method="post" action="{{ route('guest.profile') }}">
        @csrf @method('PUT')
        <label>Nama Lengkap</label>
        <input name="name" value="{{ old('name', $guest->name) }}" required>
        <label>No WhatsApp</label>
        <input name="phone" value="{{ old('phone', $guest->phone) }}" placeholder="Contoh: 082129248953" required>
        <label>Email</label>
        <input value="{{ $guest->email }}" disabled>
        <button class="btn btn-full">Simpan</button>
      </form>
    </aside>
  </div>
</section>
@endsection
