@php $v = fn ($k) => old($k, isset($r) ? $r->{$k} : ''); @endphp
<label>Judul Iklan</label><input name="title" value="{{ $v('title') }}" placeholder="Contoh: Dijual Studio Easton Park Full Furnished" required>
<label>Nama Apartment</label><input name="apartment_name" value="{{ $v('apartment_name') }}" required>
<label>Lokasi</label><input name="location" value="{{ $v('location') }}" required>
<label>Tipe Unit</label><input name="unit_type" value="{{ $v('unit_type') }}">
<label>Lantai</label><input name="floor" value="{{ $v('floor') }}">
<label>Luas</label><input name="size" value="{{ $v('size') }}">
<label>Sertifikat</label><input name="certificate" value="{{ $v('certificate') }}" placeholder="SHM / AJB / HGB">
<label>Furnishing</label><input name="furnishing" value="{{ $v('furnishing') }}">
<label>Harga</label><input type="number" min="0" name="price" value="{{ isset($r) ? old('price', (int) $r->price) : old('price') }}" required>
<label>Deskripsi</label><textarea name="description">{{ $v('description') }}</textarea>
<label>Nama Penjual</label><input name="seller_name" value="{{ $v('seller_name') }}">
<label>No HP Penjual</label><input name="seller_phone" value="{{ $v('seller_phone') }}">
<label>Foto</label>
<input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
<small>Maks. 10 foto (JPG/PNG/WEBP, 4 MB per foto). Foto pertama menjadi cover.</small>
