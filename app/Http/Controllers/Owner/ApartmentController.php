<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Support\ImageStore;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function index()
    {
        return view('owner.apartments', [
            'apartments' => auth('owner')->user()->apartments()->latest('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $owner = auth('owner')->user();

        $data = $request->validate([
            'name' => 'required|string|max:180',
            'location' => 'required|string|max:180',
            'type' => 'required|string|max:80',
            'price_per_night' => 'required|numeric|min:0',
            'facilities' => 'nullable|string',
            'description' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => ImageStore::RULE,
        ]);
        unset($data['images']);

        // Unit baru dari owner menunggu verifikasi admin sebelum tampil di situs.
        $apartment = $owner->apartments()->create($data + ['owner_name' => $owner->name, 'status' => 'nonaktif']);

        foreach ($request->file('images', []) as $file) {
            $name = ImageStore::store($file, 'apartments', 'apt');
            $apartment->images()->create(['image' => $name]);

            if (! $apartment->image) {
                $apartment->update(['image' => $name]);
            }
        }

        return back()->with('success', 'Unit berhasil dikirim. Unit akan tampil setelah diaktifkan admin.');
    }
}
