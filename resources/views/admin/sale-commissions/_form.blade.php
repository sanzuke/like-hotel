@php $v = fn ($k, $d = '') => old($k, isset($r) ? $r->{$k} : $d); @endphp
<label>Nama Penjual</label><input name="seller_name" value="{{ $v('seller_name') }}" placeholder="Nama pemilik / penjual">
<label>Apartment</label><input name="apartment_name" value="{{ $v('apartment_name') }}" placeholder="Contoh: Easton Park Residence" required>
<label>Harga Deal</label><input type="number" min="0" name="deal_price" value="{{ isset($r) ? old('deal_price', (int) $r->deal_price) : old('deal_price') }}" required>
<label>Tipe Komisi</label>
<select name="commission_type">
  <option value="percent" @selected($v('commission_type', 'percent') === 'percent')>Persen (%)</option>
  <option value="fixed" @selected($v('commission_type') === 'fixed')>Nominal Rupiah</option>
</select>
<label>Nilai Komisi</label><input type="number" step="any" min="0" name="commission_value" value="{{ isset($r) ? old('commission_value', (float) $r->commission_value) : old('commission_value') }}" required>
<label>Biaya Lain (pajak, dll.)</label><input type="number" min="0" name="other_cost" value="{{ isset($r) ? old('other_cost', (int) $r->other_cost) : old('other_cost', 0) }}">
<label>Catatan</label><textarea name="notes">{{ $v('notes') }}</textarea>
