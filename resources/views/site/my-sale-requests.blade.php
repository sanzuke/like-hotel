@extends('layouts.public')
@section('robots', 'noindex,nofollow')
@section('title', 'Pengajuan Titip Jual - Like Hotel')
@section('content')
<section class="mb-page">
  <div class="mb-head">
    <div>
      <span class="badge"><i class="ms">real_estate_agent</i> Titip Jual</span>
      <h1>Pengajuan Saya</h1>
      <p class="muted">Pantau status unit yang Anda titipkan ke Like Hotel.</p>
    </div>
    <a class="btn" href="{{ route('sale-request.create') }}">+ Ajukan Unit</a>
  </div>

  @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif

  <div class="mb-list">
    @forelse($requests as $r)
      <article class="mb-card">
        <div class="mb-top">
          <div>
            <b>{{ $r->apartment_name }}</b>
            <small>{{ $r->location }} &middot; {{ $r->unit_type ?: 'Tipe belum diisi' }}</small>
          </div>
          <span class="badge">{{ $stages[$r->status] ?? ucfirst($r->status) }}</span>
        </div>
        <div class="mb-meta">
          <span><i class="ms">event</i> Diajukan {{ $r->created_at->translatedFormat('d M Y') }}</span>
          @if($r->expected_price)<span><i class="ms">payments</i> Harapan {{ \App\Support\Fmt::rupiah($r->expected_price) }}</span>@endif
        </div>
        @if($r->notes)<p class="muted">{{ \Illuminate\Support\Str::limit($r->notes, 140) }}</p>@endif
      </article>
    @empty
      <div class="mb-empty">
        <i class="ms">real_estate_agent</i>
        <b>Belum ada pengajuan</b>
        <p class="muted">Titipkan unit Anda dan tim kami akan menghubungi untuk survei.</p>
        <a class="btn" href="{{ route('sale-request.create') }}">Ajukan Titip Jual</a>
      </div>
    @endforelse
    {{ $requests->links() }}
  </div>
</section>
@endsection
