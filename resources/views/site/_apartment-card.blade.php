@php
    $facs = collect(preg_split('/[,;\n]+/', (string) $a->facilities))->map(fn ($f) => trim($f))->filter()->take(4);
    $href = route('apartments.show', $a).(request()->filled('checkin') ? '?'.http_build_query(request()->only('checkin','checkout')) : '');
@endphp
<div class="card apartment-card">
  <div class="card-media">
    @if($a->image)
      <img src="{{ asset('storage/apartments/'.$a->image) }}" class="card-photo" alt="{{ \App\Support\Seo::alt('Sewa apartemen', $a->name, $a->type, 'di', $a->location) }}" width="640" height="420" loading="lazy" decoding="async">
    @else
      <div class="card-img card-img-modern"><span>LIKE HOTEL</span><b>Apartment</b></div>
    @endif
    <span class="tag">{{ $a->type }}</span>
    <span class="rate"><i class="ms">star</i> 4.8</span>
  </div>
  <div class="card-body">
    <h3>{{ $a->name }}</h3>
    <p class="loc"><i class="ms">location_on</i> {{ $a->location }}</p>
    @if($facs->isNotEmpty())
      <div class="fac">@foreach($facs as $f)<span>{{ $f }}</span>@endforeach</div>
    @endif
    <div class="card-bottom">
      <div>
        <small>Mulai dari</small>
        <p class="price">{{ \App\Support\Fmt::rupiah($a->price_per_night) }}<span>/malam</span></p>
      </div>
      <a class="btn" href="{{ $href }}">Detail <i class="ms">chevron_right</i></a>
    </div>
  </div>
</div>
