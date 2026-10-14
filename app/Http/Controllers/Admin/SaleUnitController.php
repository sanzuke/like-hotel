<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleUnit;
use App\Models\SaleUnitImage;
use App\Support\ImageUploader;
use Illuminate\Http\Request;

class SaleUnitController extends Controller
{
    private function rules(bool $creating): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'apartment_name' => ['required', 'string', 'max:150'],
            'location' => ['required', 'string', 'max:150'],
            'unit_type' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:50'],
            'size' => ['nullable', 'string', 'max:50'],
            'certificate' => ['nullable', 'string', 'max:100'],
            'furnishing' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:5000'],
            'seller_name' => ['nullable', 'string', 'max:150'],
            'seller_phone' => ['nullable', 'string', 'max:50'],
            'status' => $creating ? ['nullable'] : ['required', 'in:aktif,terjual,nonaktif'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ImageUploader::rules(),
        ];
    }

    private function addImages(SaleUnit $unit, Request $request): void
    {
        foreach (ImageUploader::store($request->file('images'), SaleUnit::FOLDER, 'sale') as $name) {
            $unit->images()->create(['image' => $name]);

            if (! $unit->image) {
                $unit->update(['image' => $name]);
            }
        }
    }

    public function index()
    {
        return view('admin.sale-units', ['rows' => SaleUnit::latest('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = collect($request->validate($this->rules(true)))->except(['images', 'status'])->all();

        $unit = SaleUnit::create($data + ['status' => 'aktif']);
        $this->addImages($unit, $request);

        return redirect()->route('admin.sale-units')->with('status', 'Unit dijual ditambahkan.');
    }

    public function edit(SaleUnit $saleUnit)
    {
        return view('admin.sale-unit-edit', [
            'r' => $saleUnit,
            'images' => $saleUnit->images()->reorder('id', 'desc')->get(),
        ]);
    }

    public function update(Request $request, SaleUnit $saleUnit)
    {
        $data = collect($request->validate($this->rules(false)))->except('images')->all();

        $saleUnit->update($data);
        $this->addImages($saleUnit, $request);

        return redirect()->route('admin.sale-units.edit', $saleUnit)->with('status', 'Unit dijual diperbarui.');
    }

    public function status(Request $request, SaleUnit $saleUnit)
    {
        $data = $request->validate(['status' => ['required', 'in:aktif,terjual,nonaktif']]);
        $saleUnit->update($data);

        return back();
    }

    public function destroy(SaleUnit $saleUnit)
    {
        foreach ($saleUnit->images as $img) {
            ImageUploader::delete(SaleUnit::FOLDER, $img->image);
        }

        ImageUploader::delete(SaleUnit::FOLDER, $saleUnit->image);
        $saleUnit->delete(); // sale_unit_images ikut terhapus (cascade)

        return redirect()->route('admin.sale-units')->with('status', 'Unit dijual dihapus.');
    }

    public function destroyImage(SaleUnit $saleUnit, SaleUnitImage $image)
    {
        abort_unless($image->sale_unit_id === $saleUnit->id, 404);

        ImageUploader::delete(SaleUnit::FOLDER, $image->image);

        if ($saleUnit->image === $image->image) {
            $saleUnit->update(['image' => null]);
        }

        $image->delete();

        return back()->with('status', 'Foto dihapus.');
    }

    public function setCover(SaleUnit $saleUnit, SaleUnitImage $image)
    {
        abort_unless($image->sale_unit_id === $saleUnit->id, 404);
        $saleUnit->update(['image' => $image->image]);

        return back()->with('status', 'Foto cover diperbarui.');
    }
}
