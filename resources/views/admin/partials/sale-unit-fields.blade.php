<label>Judul Listing</label>
<input name="title" value="{{ old('title', $r?->title) }}" required>
<label>Nama Apartment</label>
<input name="apartment_name" value="{{ old('apartment_name', $r?->apartment_name) }}" required>
<label>Lokasi</label>
<input name="location" value="{{ old('location', $r?->location) }}" required>
<label>Tipe Unit</label>
<input name="unit_type" value="{{ old('unit_type', $r?->unit_type) }}" placeholder="Studio / 1 Bedroom">
<label>Lantai</label>
<input name="floor" value="{{ old('floor', $r?->floor) }}" placeholder="Lantai 12">
<label>Luas</label>
<input name="size" value="{{ old('size', $r?->size) }}" placeholder="24 m2">
<label>Sertifikat</label>
<input name="certificate" value="{{ old('certificate', $r?->certificate) }}" placeholder="SHM / AJB">
<label>Furniture</label>
<input name="furnishing" value="{{ old('furnishing', $r?->furnishing) }}" placeholder="Full furnished">
<label>Harga Jual</label>
<input name="price" type="number" min="1" value="{{ old('price', $r ? (int) $r->price : '') }}" required>
<label>Deskripsi</label>
<textarea name="description">{{ old('description', $r?->description) }}</textarea>
<label>Nama Penjual</label>
<input name="seller_name" value="{{ old('seller_name', $r?->seller_name) }}">
<label>WhatsApp Penjual</label>
<input name="seller_phone" value="{{ old('seller_phone', $r?->seller_phone) }}">
