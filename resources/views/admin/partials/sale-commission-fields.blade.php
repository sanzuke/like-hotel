<label>Nama Penjual</label>
<input name="seller_name" value="{{ old('seller_name', $r?->seller_name) }}" required>
<label>Nama Apartment</label>
<input name="apartment_name" value="{{ old('apartment_name', $r?->apartment_name) }}" required>
<label>Harga Deal</label>
<input name="deal_price" type="number" min="0" value="{{ old('deal_price', $r ? (int) $r->deal_price : '') }}" required>
<label>Tipe Komisi</label>
<select name="commission_type">
  <option value="percent" @selected(old('commission_type', $r?->commission_type) === 'percent')>Persen (%)</option>
  <option value="fixed" @selected(old('commission_type', $r?->commission_type) === 'fixed')>Nominal (Rp)</option>
</select>
<label>Nilai Komisi</label>
<input name="commission_value" type="number" step="0.01" min="0" value="{{ old('commission_value', $r?->commission_value) }}" required>
<label>Biaya Lain</label>
<input name="other_cost" type="number" min="0" value="{{ old('other_cost', $r ? (int) $r->other_cost : 0) }}">
<label>Catatan</label>
<textarea name="notes">{{ old('notes', $r?->notes) }}</textarea>
