<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleUnit;
use App\Models\SaleUnitImage;
use App\Support\Audit;
use App\Support\ImageStore;
use Illuminate\Http\Request;

class SaleUnitController extends Controller
{
    private const DIR = 'sale_units';

    public function index()
    {
        return view('admin.sale-units.index', ['rows' => SaleUnit::latest('id')->get()]);
    }

    public function store(Request $request)
    {
        $unit = SaleUnit::create($this->validated($request) + ['status' => 'aktif']);
        $this->attachImages($request, $unit);

        return redirect()->route('admin.sale-units.index')->with('success', 'Unit dijual ditambahkan.');
    }

    public function edit(SaleUnit $saleUnit)
    {
        return view('admin.sale-units.edit', [
            'r' => $saleUnit,
            'images' => $saleUnit->images()->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, SaleUnit $saleUnit)
    {
        $status = $request->validate(['status' => 'required|in:aktif,terjual,nonaktif'])['status'];
        $saleUnit->update($this->validated($request) + ['status' => $status]);
        $this->attachImages($request, $saleUnit);

        return redirect()->route('admin.sale-units.edit', $saleUnit)->with('success', 'Perubahan disimpan.');
    }

    public function status(Request $request, SaleUnit $saleUnit)
    {
        $from = $saleUnit->status;
        $saleUnit->update($request->validate(['status' => 'required|in:aktif,terjual,nonaktif']));
        Audit::log('sale_unit.status', "Unit dijual \"{$saleUnit->title}\": {$from} → {$saleUnit->status}", $saleUnit);

        return back()->with('success', 'Status unit diperbarui.');
    }

    public function destroy(SaleUnit $saleUnit)
    {
        foreach ($saleUnit->images as $img) {
            ImageStore::delete($img->image, self::DIR);
        }
        ImageStore::delete($saleUnit->image, self::DIR);
        Audit::log('sale_unit.deleted', "Unit dijual \"{$saleUnit->title}\" dihapus", $saleUnit);
        $saleUnit->delete();

        return redirect()->route('admin.sale-units.index')->with('success', 'Unit dihapus.');
    }

    public function setCover(SaleUnit $saleUnit, SaleUnitImage $image)
    {
        abort_unless($image->sale_unit_id === $saleUnit->id, 404);
        $saleUnit->update(['image' => $image->image]);

        return back()->with('success', 'Cover diperbarui.');
    }

    public function destroyImage(SaleUnit $saleUnit, SaleUnitImage $image)
    {
        abort_unless($image->sale_unit_id === $saleUnit->id, 404);

        if ($saleUnit->image === $image->image) {
            $saleUnit->update(['image' => null]);
        }

        ImageStore::delete($image->image, self::DIR);
        $image->delete();

        return back()->with('success', 'Foto dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'apartment_name' => 'required|string|max:150',
            'location' => 'required|string|max:150',
            'unit_type' => 'nullable|string|max:100',
            'floor' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:50',
            'certificate' => 'nullable|string|max:100',
            'furnishing' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'seller_name' => 'nullable|string|max:150',
            'seller_phone' => 'nullable|string|max:50',
            'images' => 'nullable|array|max:10',
            'images.*' => ImageStore::RULE,
        ]);
        unset($data['images']);

        return $data;
    }

    private function attachImages(Request $request, SaleUnit $unit): void
    {
        foreach ($request->file('images', []) as $file) {
            $name = ImageStore::store($file, self::DIR, 'sale');
            $unit->images()->create(['image' => $name]);

            if (! $unit->image) {
                $unit->update(['image' => $name]);
            }
        }
    }
}
