<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApartmentController extends Controller
{
    public function index()
    {
        return view('owner.apartments', [
            'apartments' => Auth::guard('owner')->user()->apartments()->latest('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $owner = Auth::guard('owner')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'location' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', 'max:80'],
            'price_per_night' => ['required', 'numeric', 'min:1'],
            'facilities' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', ...ImageUploader::rules()],
        ]);

        [$cover] = ImageUploader::store($request->file('image'), Apartment::FOLDER, 'apt') + [null];

        $apartment = Apartment::create([
            'owner_id' => $owner->id,
            'owner_name' => $owner->name,
            'name' => $data['name'],
            'location' => $data['location'],
            'type' => $data['type'],
            'price_per_night' => $data['price_per_night'],
            'facilities' => $data['facilities'] ?? null,
            'image' => $cover,
            'status' => 'aktif',
        ]);

        if ($cover) {
            $apartment->images()->create(['image' => $cover]);
        }

        return redirect()->route('owner.apartments')->with('status', 'Unit berhasil disimpan.');
    }
}
