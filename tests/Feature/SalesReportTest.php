<?php

namespace Tests\Feature;

use App\Models\SaleCommission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use CreatesFixtures, RefreshDatabase;

    private function closing(string $date, string $apt, float $deal, float $comm, string $status = 'closing', float $other = 0): SaleCommission
    {
        return SaleCommission::create([
            'seller_name' => 'S', 'apartment_name' => $apt, 'deal_price' => $deal, 'commission_type' => 'fixed', 'commission_value' => $comm,
            'commission_amount' => $comm, 'other_cost' => $other, 'seller_net_amount' => $deal - $comm - $other, 'status' => $status, 'created_at' => $date,
        ]);
    }

    public function test_report_aggregates_by_period_month_and_apartment_and_excludes_cancelled(): void
    {
        $this->closing('2026-01-10', 'Easton', 300000000, 30000000);
        $this->closing('2026-01-20', 'Easton', 200000000, 10000000, 'selesai', 1000000);
        $this->closing('2026-02-05', 'Melati', 100000000, 5000000);
        $this->closing('2026-02-06', 'Melati', 999000000, 99000000, 'batal');
        $this->closing('2025-12-31', 'Easton', 50000000, 1000000); // di luar periode

        $this->actingAs($this->admin(), 'admin');
        $res = $this->get(route('admin.sales-report', ['from' => '2026-01-01', 'to' => '2026-02-28']))->assertOk();

        $t = $res->viewData('total');
        $this->assertSame(3, $t['count']);
        $this->assertEquals(600000000, $t['deal']);
        $this->assertEquals(45000000, $t['commission']);
        $this->assertEquals(1000000, $t['other']);
        $this->assertEquals(554000000, $t['net']);
        $this->assertEquals(7.5, $t['rate']);

        $months = $res->viewData('byMonth');
        $this->assertSame(['2026-01', '2026-02'], $months->pluck('label')->all());
        $this->assertSame(2, $months[0]['count']);

        $apts = $res->viewData('byApartment');
        $this->assertSame('Easton', $apts[0]['label']); // urut harga deal terbesar
        $this->assertEquals(500000000, $apts[0]['deal']);

        $withCancelled = $this->get(route('admin.sales-report', ['from' => '2026-01-01', 'to' => '2026-02-28', 'include_cancelled' => 1]))->viewData('total');
        $this->assertSame(4, $withCancelled['count']);
    }

    public function test_empty_period_and_validation(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.sales-report', ['from' => '2030-01-01', 'to' => '2030-01-31']))->assertOk()->assertSee('Tidak ada closing');
        $this->get(route('admin.sales-report', ['from' => '2026-02-01', 'to' => '2026-01-01']))->assertSessionHasErrors('to');
    }

    public function test_csv_export_contains_summary_and_rows(): void
    {
        $this->closing('2026-03-01', 'Easton', 100000000, 5000000);
        $this->actingAs($this->admin(), 'admin');

        $res = $this->get(route('admin.sales-report.export', ['from' => '2026-03-01', 'to' => '2026-03-31']));
        $res->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $res->streamedContent();
        $this->assertStringContainsString('"Jumlah Closing",1', $csv);
        $this->assertStringContainsString('Rekap per Bulan', $csv);
        $this->assertStringContainsString('CLS00001', $csv);
    }

    public function test_requires_admin(): void
    {
        $this->get(route('admin.sales-report'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.sales-report.export'))->assertRedirect(route('admin.login'));
    }
}
