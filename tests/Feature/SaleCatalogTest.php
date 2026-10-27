<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SaleUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function unit(array $o = []): SaleUnit
    {
        return SaleUnit::create($o + [
            'title' => 'Unit A', 'apartment_name' => 'Easton Park', 'location' => 'Jatinangor',
            'unit_type' => 'Studio', 'furnishing' => 'Full furnished', 'price' => 250_000_000, 'status' => 'aktif',
        ]);
    }

    public function test_catalog_lists_only_active_units_and_filters_by_price_type_and_search(): void
    {
        $this->unit(['title' => 'Murah Studio']);
        $this->unit(['title' => 'Mahal 2BR', 'unit_type' => '2 Bedroom', 'price' => 520_000_000, 'location' => 'Bekasi']);
        $this->unit(['title' => 'Sudah Terjual', 'status' => 'terjual']);

        $html = $this->get(route('sale-units.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Murah Studio', $html);
        $this->assertStringContainsString('Mahal 2BR', $html);
        $this->assertStringNotContainsString('Sudah Terjual', $html);
        $this->assertStringContainsString('Ditemukan 2 unit', $html);

        $html = $this->get(route('sale-units.index', ['price' => 'gt400']))->getContent();
        $this->assertStringContainsString('Mahal 2BR', $html);
        $this->assertStringNotContainsString('Murah Studio', $html);

        $html = $this->get(route('sale-units.index', ['type' => 'Studio', 'q' => 'jatinangor']))->getContent();
        $this->assertStringContainsString('Murah Studio', $html);
        $this->assertStringNotContainsString('Mahal 2BR', $html);

        $this->get(route('sale-units.index', ['q' => 'tidak-ada']))->assertOk()->assertSee('Unit tidak ditemukan');
    }

    public function test_sort_by_price_and_detail_page_and_admin_stats(): void
    {
        $a = $this->unit(['title' => 'Murah Studio']);
        $this->unit(['title' => 'Mahal 2BR', 'price' => 520_000_000]);

        $html = $this->get(route('sale-units.index', ['sort' => 'termahal']))->getContent();
        $this->assertLessThan(strpos($html, 'Murah Studio'), strpos($html, 'Mahal 2BR'));

        $this->get(route('sale-units.show', $a))->assertOk()->assertSee('Alur Transaksi Aman')->assertSee('Jadwalkan Survei');

        $this->actingAs(Admin::create(['name' => 'A', 'username' => 'adm', 'password' => 'password123']), 'admin')
            ->get(route('admin.sale-units.index'))->assertOk()->assertSee('Nilai Unit Aktif')->assertSee('Lihat di situs');
    }
}
