@extends('layouts.site')
@section('title', 'Booking '.$apartment->name)

@push('head')
<style>
.booking-calendar{margin-top:18px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:16px}
.calendar-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.calendar-head button{border:1px solid #087a35;background:#fff;color:#087a35;border-radius:8px;padding:7px 10px;font-weight:800;cursor:pointer}
.calendar-title{font-weight:900;color:#05331d}
.calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:7px}
.calendar-day-name{font-size:12px;font-weight:800;color:#6b7280;text-align:center}
.calendar-date{padding:10px 0;border-radius:10px;text-align:center;background:#fff;border:1px solid #e5e7eb;font-weight:700;font-size:13px}
.calendar-date.available{color:#087a35}
.calendar-date.available:hover{background:#e8fff1;border-color:#087a35}
.calendar-date.booked{background:#fee2e2;border-color:#fecaca;color:#991b1b;text-decoration:line-through;cursor:not-allowed}
.calendar-date.selected{background:#087a35;color:#fff;border-color:#087a35}
.calendar-date.today{border:2px solid #087a35}
.calendar-date.empty{background:transparent;border:0}
.calendar-legend{display:flex;gap:12px;flex-wrap:wrap;margin-top:12px;font-size:13px}
.legend-box{width:14px;height:14px;border-radius:4px;display:inline-block;vertical-align:middle;margin-right:5px}
.legend-available{background:#fff;border:1px solid #087a35}
.legend-booked{background:#fee2e2;border:1px solid #ef4444}
#dateWarning{display:none;margin-top:12px}
</style>
@endpush

@section('content')
<section class="booking-page">

  <div class="booking-form-card">
    <span class="badge">Booking Apartment</span>
    <h1>Lengkapi Data Booking</h1>
    <p class="muted">Isi data tamu dan tanggal menginap dengan benar.</p>

    @if($errors->any())
      <div class="alert warn"><b>Informasi Booking</b><br>
        @foreach($errors->all() as $err){{ $err }}<br>@endforeach
      </div>
    @endif

    <div id="dateWarning" class="alert warn">
      <b>Tanggal Tidak Tersedia</b><br>
      Tanggal yang dipilih bentrok dengan booking lain. Silakan pilih tanggal berbeda.
    </div>

    <form method="post" action="{{ route('booking.store', $apartment) }}" id="bookingForm">
      @csrf
      <label>Nama Lengkap</label>
      <input name="guest_name" placeholder="Nama sesuai KTP" value="{{ old('guest_name') }}" required maxlength="150">

      <label>No WhatsApp</label>
      <input name="guest_phone" placeholder="Contoh: 082129248953" value="{{ old('guest_phone') }}" required maxlength="50">

      <label>Email</label>
      <input type="email" name="guest_email" placeholder="email@gmail.com" value="{{ old('guest_email') }}" required maxlength="150">

      <label>Kode Promo</label>
      <input name="promo_code" placeholder="Masukkan kode promo" value="{{ old('promo_code') }}" maxlength="50">
      <small>Kosongkan jika tidak memiliki promo.</small>

      <div class="booking-date-grid">
        <div>
          <label>Check-in</label>
          <input type="date" name="checkin" id="checkin" value="{{ old('checkin') }}" min="{{ date('Y-m-d') }}" required>
        </div>
        <div>
          <label>Check-out</label>
          <input type="date" name="checkout" id="checkout" value="{{ old('checkout') }}" min="{{ date('Y-m-d') }}" required>
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
        <p><span id="nightTextMobile">1 malam</span> • <span id="totalTextMobile">{{ rupiah($apartment->price_per_night) }}</span></p>
      </div>

      <button class="btn btn-full">Lanjut Pembayaran</button>
    </form>
  </div>

  <div class="booking-side-card">
    @if($apartment->image)
      <img src="{{ $apartment->coverUrl() }}" class="booking-photo" alt="">
    @else
      <div class="booking-photo-placeholder">Apartment</div>
    @endif

    <div class="booking-side-body">
      <h2>{{ $apartment->name }}</h2>
      <p class="muted">📍 {{ $apartment->location }} • {{ $apartment->type }}</p>
      <p>{{ $apartment->facilities }}</p>
      <hr>
      <div class="booking-price-row"><span>Harga per malam</span><b>{{ rupiah($apartment->price_per_night) }}</b></div>
      <div class="booking-price-row"><span>Jumlah malam</span><b id="nightText">1 malam</b></div>
      <div class="booking-price-total"><span>Total</span><b id="totalText">{{ rupiah($apartment->price_per_night) }}</b></div>
      <small>Diskon promo (jika ada) dihitung saat booking dibuat. Invoice otomatis dibuat setelah booking.</small>
    </div>
  </div>

</section>
@endsection

@push('scripts')
<script>
const pricePerNight = {{ (float) $apartment->price_per_night }};
const bookedDates = @json($bookedDates);

const checkin = document.getElementById('checkin');
const checkout = document.getElementById('checkout');
const nightText = document.getElementById('nightText');
const totalText = document.getElementById('totalText');
const nightTextMobile = document.getElementById('nightTextMobile');
const totalTextMobile = document.getElementById('totalTextMobile');
const dateWarning = document.getElementById('dateWarning');
const bookingForm = document.getElementById('bookingForm');

let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();

function rupiah(num){ return 'Rp' + new Intl.NumberFormat('id-ID').format(num); }

function formatDate(date){
    const y = date.getFullYear();
    const m = String(date.getMonth()+1).padStart(2,'0');
    const d = String(date.getDate()).padStart(2,'0');
    return `${y}-${m}-${d}`;
}

function selectedRangeHasBookedDate(){
    if(!checkin.value || !checkout.value) return false;
    const start = new Date(checkin.value);
    const end = new Date(checkout.value);
    for(let d = new Date(start); d < end; d.setDate(d.getDate()+1)){
        if(bookedDates.includes(formatDate(d))) return true;
    }
    return false;
}

function calculateTotal(){
    dateWarning.style.display = 'none';
    if(!checkin.value || !checkout.value){ renderCalendar(); return; }

    const diff = Math.max(1, Math.round((new Date(checkout.value) - new Date(checkin.value)) / 86400000));
    const total = diff * pricePerNight;

    nightText.textContent = nightTextMobile.textContent = diff + ' malam';
    totalText.textContent = totalTextMobile.textContent = rupiah(total);

    if(selectedRangeHasBookedDate()) dateWarning.style.display = 'block';
    renderCalendar();
}

function renderCalendar(){
    const grid = document.getElementById('calendarGrid');
    const title = document.getElementById('calendarTitle');
    grid.innerHTML = '';

    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    title.textContent = monthNames[currentMonth] + ' ' + currentYear;

    ['Min','Sen','Sel','Rab','Kam','Jum','Sab'].forEach(day => {
        const el = document.createElement('div');
        el.className = 'calendar-day-name';
        el.textContent = day;
        grid.appendChild(el);
    });

    const firstDay = new Date(currentYear,currentMonth,1).getDay();
    const totalDays = new Date(currentYear,currentMonth+1,0).getDate();
    const todayDate = formatDate(new Date());

    for(let i=0;i<firstDay;i++){
        const empty = document.createElement('div');
        empty.className = 'calendar-date empty';
        grid.appendChild(empty);
    }

    for(let day=1;day<=totalDays;day++){
        const dateStr = formatDate(new Date(currentYear,currentMonth,day));
        const el = document.createElement('div');
        el.className = 'calendar-date';

        if(bookedDates.includes(dateStr) || dateStr < todayDate){
            el.classList.add('booked');
        } else {
            el.classList.add('available');
            el.style.cursor = 'pointer';
            el.addEventListener('click', function(){
                if(!checkin.value || (checkin.value && checkout.value)){
                    checkin.value = dateStr;
                    checkout.value = '';
                } else if(new Date(dateStr) <= new Date(checkin.value)){
                    checkin.value = dateStr;
                    checkout.value = '';
                } else {
                    checkout.value = dateStr;
                }
                calculateTotal();
            });
        }

        if(dateStr === todayDate) el.classList.add('today');
        if(checkin.value === dateStr || checkout.value === dateStr) el.classList.add('selected');

        el.textContent = day;
        grid.appendChild(el);
    }
}

function prevMonth(){ currentMonth--; if(currentMonth < 0){ currentMonth = 11; currentYear--; } renderCalendar(); }
function nextMonth(){ currentMonth++; if(currentMonth > 11){ currentMonth = 0; currentYear++; } renderCalendar(); }

checkin.addEventListener('change', calculateTotal);
checkout.addEventListener('change', calculateTotal);

bookingForm.addEventListener('submit', function(e){
    if(selectedRangeHasBookedDate()){
        e.preventDefault();
        dateWarning.style.display = 'block';
        dateWarning.scrollIntoView({behavior:'smooth', block:'center'});
    }
});

calculateTotal();
</script>
@endpush
