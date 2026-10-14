<label>Owner</label>
<select name="owner_id">
  <option value="">Like Hotel / Milik Sendiri</option>
  @foreach($owners as $o)
    <option value="{{ $o->id }}" @selected(old('owner_id', $apt?->owner_id) == $o->id)>{{ $o->name }}</option>
  @endforeach
</select>

<label>Nama Apartment</label>
<input name="name" value="{{ old('name', $apt?->name) }}" required>

<label>Lokasi</label>
<input name="location" value="{{ old('location', $apt?->location) }}" required>

<label>Tipe</label>
<input name="type" value="{{ old('type', $apt?->type) }}" placeholder="Studio / 1 Bedroom / 2 Bedroom" required>

<label>Harga per Malam</label>
<input name="price_per_night" type="number" min="1" value="{{ old('price_per_night', $apt ? (int) $apt->price_per_night : '') }}" required>

<label>Fasilitas</label>
<textarea name="facilities" placeholder="AC, WiFi, TV, Parkir">{{ old('facilities', $apt?->facilities) }}</textarea>

<label>Deskripsi</label>
<textarea name="description">{{ old('description', $apt?->description) }}</textarea>
