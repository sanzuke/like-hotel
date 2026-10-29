@extends('layouts.public')
@section('robots', 'noindex,nofollow')
@section('title', 'Booking '.$a->name)
@section('content')
<section class="booking-page">
  <div class="booking-form-card">
    <span class="badge">Booking Apartment</span>
    <h1>Lengkapi Data Booking</h1>
    <p class="muted">Isi data tamu dan tanggal menginap dengan benar.</p>

    @if($errors->any())
      <div class="alert warn"><b>Informasi Booking</b><br>@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
    @endif

    <div id="dateWarning" class="alert warn">
      <b>Tanggal Tidak Tersedia</b><br>Tanggal yang dipilih bentrok dengan booking lain. Silakan pilih tanggal berbeda.
    </div>

    <form method="post" action="{{ route('booking.store', $a) }}" id="bookingForm">
      @csrf
      <label>Nama Lengkap</label>
      <input name="guest_name" placeholder="Nama sesuai KTP" value="{{ old('guest_name', $guest->name) }}" required>

      <label>No WhatsApp</label>
      <input name="guest_phone" placeholder="Contoh: 082129248953" value="{{ old('guest_phone', $guest->phone) }}" required>

      <label>Email</label>
      <input type="email" name="guest_email" placeholder="email@gmail.com" value="{{ old('guest_email', $guest->email) }}" required>

      <label>Kode Promo</label>
      <input name="promo_code" placeholder="Masukkan kode promo" value="{{ old('promo_code') }}">
      <small>Kosongkan jika tidak memiliki promo.</small>

      <div class="booking-date-grid">
        <div>
          <label>Check-in</label>
          <input type="date" name="checkin" id="checkin" min="{{ today()->toDateString() }}" value="{{ old('checkin', request('checkin')) }}" required>
        </div>
        <div>
          <label>Check-out</label>
          <input type="date" name="checkout" id="checkout" min="{{ today()->toDateString() }}" value="{{ old('checkout', request('checkout')) }}" required>
        </div>
      </div>

      <div class="booking-calendar">
        <div class="calendar-head">
          <button type="button" onclick="prevMonth()">‹</button>
          <div class="calendar-title" id="calendarTitle"></div>
          <button type="button" onclick="nextMonth()">›</button>
        </div>
        <div class="calendar-grid" id="calendarGrid"></div>
        <div class="calendar-legend">
          <span><span class="legend-box legend-available"></span>Tersedia / bisa diklik</span>
          <span><span class="legend-box legend-booked"></span>Sudah dibooking</span>
        </div>
      </div>

      <div class="booking-summary-mobile">
        <b>Ringkasan:</b>
        <p><span id="nightTextMobile">1 malam</span> • <span id="totalTextMobile">{{ \App\Support\Fmt::rupiah($a->price_per_night) }}</span></p>
      </div>

      <button class="btn btn-full">Lanjut Pembayaran</button>
    </form>
  </div>

  <div class="booking-side-card">
    @if($a->image)
      <img src="{{ asset('storage/apartments/'.$a->image) }}" class="booking-photo" alt="{{ $a->name }}">
    @else
      <div class="booking-photo-placeholder">Apartment</div>
    @endif
    <div class="booking-side-body">
      <h2>{{ $a->name }}</h2>
      <p class="muted">📍 {{ $a->location }} • {{ $a->type }}</p>
      <p>{{ $a->facilities }}</p>
      <hr>
      <div class="booking-price-row"><span>Harga per malam</span><b>{{ \App\Support\Fmt::rupiah($a->price_per_night) }}</b></div>
      <div class="booking-price-row"><span>Jumlah malam</span><b id="nightText">1 malam</b></div>
      <div class="booking-price-total"><span>Total (sebelum promo)</span><b id="totalText">{{ \App\Support\Fmt::rupiah($a->price_per_night) }}</b></div>
      <small>Diskon promo dihitung saat booking dibuat. Invoice otomatis dibuat setelah booking.</small>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
const pricePerNight = {{ (float) $a->price_per_night }};
const bookedDates = @json($bookedDates);
const checkin = document.getElementById('checkin');
const checkout = document.getElementById('checkout');
const dateWarning = document.getElementById('dateWarning');
const MONTHS = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const DAYS = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
let month = new Date().getMonth(), year = new Date().getFullYear();

const rupiah = n => 'Rp' + new Intl.NumberFormat('id-ID').format(n);
const fmt = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
const parse = s => { const [y,m,d] = s.split('-').map(Number); return new Date(y, m-1, d); };

function rangeHasBooked(){
  if(!checkin.value || !checkout.value) return false;
  for(let d = parse(checkin.value); d < parse(checkout.value); d.setDate(d.getDate()+1)){
    if(bookedDates.includes(fmt(d))) return true;
  }
  return false;
}

function recalc(){
  dateWarning.style.display = 'none';
  if(checkin.value && checkout.value){
    const nights = Math.max(1, Math.round((parse(checkout.value) - parse(checkin.value)) / 86400000));
    const total = rupiah(nights * pricePerNight);
    ['nightText','nightTextMobile'].forEach(id => document.getElementById(id).textContent = nights + ' malam');
    ['totalText','totalTextMobile'].forEach(id => document.getElementById(id).textContent = total);
    if(rangeHasBooked()) dateWarning.style.display = 'block';
  }
  renderCalendar();
}

function renderCalendar(){
  const grid = document.getElementById('calendarGrid');
  grid.innerHTML = '';
  document.getElementById('calendarTitle').textContent = MONTHS[month] + ' ' + year;
  DAYS.forEach(n => { const el = document.createElement('div'); el.className = 'calendar-day-name'; el.textContent = n; grid.appendChild(el); });

  const first = new Date(year, month, 1).getDay();
  const total = new Date(year, month + 1, 0).getDate();
  const today = fmt(new Date());

  for(let i = 0; i < first; i++){ const e = document.createElement('div'); e.className = 'calendar-date empty'; grid.appendChild(e); }

  for(let day = 1; day <= total; day++){
    const s = fmt(new Date(year, month, day));
    const el = document.createElement('div');
    el.className = 'calendar-date';
    el.textContent = day;

    if(bookedDates.includes(s) || s < today){
      el.classList.add('booked');
    } else {
      el.classList.add('available');
      el.addEventListener('click', () => {
        if(!checkin.value || checkout.value || s <= checkin.value){ checkin.value = s; checkout.value = ''; }
        else { checkout.value = s; }
        recalc();
      });
    }
    if(s === today) el.classList.add('today');
    if(checkin.value === s || checkout.value === s) el.classList.add('selected');
    grid.appendChild(el);
  }
}

function prevMonth(){ if(--month < 0){ month = 11; year--; } renderCalendar(); }
function nextMonth(){ if(++month > 11){ month = 0; year++; } renderCalendar(); }

checkin.addEventListener('change', recalc);
checkout.addEventListener('change', recalc);
document.getElementById('bookingForm').addEventListener('submit', e => {
  if(rangeHasBooked()){ e.preventDefault(); dateWarning.style.display = 'block'; dateWarning.scrollIntoView({behavior:'smooth', block:'center'}); }
});
recalc();
</script>
@endpush
