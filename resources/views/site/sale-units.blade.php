@extends('layouts.public')
@section('title', 'Jual Beli Apartment - Like Hotel')
@section('content')
<section class="apartments-hero">
  <span class="badge">Jual Beli Apartment</span>
  <h1>Unit Apartment Dijual</h1>
  <p>Pilihan unit apartment untuk investasi maupun hunian, dibantu tim Like Hotel dari survei hingga closing.</p>
</section>

<section class="section">
  <div class="grid">
    @forelse($rows as $r)
      <div class="card apartment-card">
        @if($r->image)
          <img src="{{ asset('storage/sale_units/'.$r->image) }}" class="card-photo" alt="{{ $r->title }}">
        @else
          <div class="card-img card-img-modern"><span>LIKE HOTEL</span><b>Dijual</b></div>
        @endif
        <div class="card-body">
          <h3>{{ $r->title }}</h3>
          <p class="muted">📍 {{ $r->location }} • {{ $r->unit_type }}</p>
          <p class="facilities">🏢 {{ $r->apartment_name }} @if($r->size)• {{ $r->size }}@endif</p>
          <div class="card-bottom">
            <div><small>Harga</small><p class="price">{{ \App\Support\Fmt::rupiah($r->price) }}</p></div>
            <a class="btn" href="{{ route('sale-units.show', $r) }}">Detail</a>
          </div>
        </div>
      </div>
    @empty
      <p>Belum ada unit yang dijual.</p>
    @endforelse
  </div>
</section>
@endsection
