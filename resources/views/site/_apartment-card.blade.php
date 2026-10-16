<div class="card apartment-card">
  @if($a->image)
    <img src="{{ asset('storage/apartments/'.$a->image) }}" class="card-photo" alt="{{ $a->name }}">
  @else
    <div class="card-img card-img-modern"><span>LIKE HOTEL</span><b>Apartment</b></div>
  @endif
  <div class="card-body">
    <div class="rating">⭐ 4.8 • Unit aktif</div>
    <h3>{{ $a->name }}</h3>
    <p class="muted">📍 {{ $a->location }} • {{ $a->type }}</p>
    <p class="facilities">🏠 {{ $a->facilities }}</p>
    <div class="card-bottom">
      <div>
        <small>Mulai dari</small>
        <p class="price">{{ \App\Support\Fmt::rupiah($a->price_per_night) }}<span>/malam</span></p>
      </div>
      <a class="btn" href="{{ route('apartments.show', $a).(request()->filled('checkin') ? '?'.http_build_query(request()->only('checkin','checkout')) : '') }}">Detail</a>
    </div>
  </div>
</div>
