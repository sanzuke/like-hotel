<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Owner;
use App\Models\SaleUnit;
use App\Support\ImageStore;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function apt(array $o = []): Apartment
    {
        $owner = Owner::first() ?? Owner::create(['name' => 'O', 'phone' => '0812', 'email' => 'o@x.com', 'password' => 'password123', 'status' => 'aktif']);

        return Apartment::create($o + [
            'owner_id' => $owner->id, 'owner_name' => 'O', 'name' => 'Easton Park Residence Jatinangor', 'location' => 'Jatinangor - Bandung',
            'type' => 'Studio', 'price_per_night' => 300000, 'facilities' => 'AC, WiFi', 'description' => 'Unit nyaman dekat kampus UNPAD.', 'status' => 'aktif',
        ]);
    }

    public function test_robots_and_sitemap_use_current_domain_and_list_slug_urls(): void
    {
        $a = $this->apt();
        $unit = SaleUnit::create(['title' => 'Fully Furnished', 'apartment_name' => 'Easton Park', 'location' => 'Jatinangor', 'price' => 300000000, 'status' => 'aktif']);
        $this->apt(['name' => 'Tidak Aktif', 'status' => 'nonaktif']);

        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringNotContainsString('likehotelsewa.com', $robots);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString(route('apartments.show', $a), $xml);
        $this->assertStringContainsString('easton-park-residence-jatinangor-studio', $xml);
        $this->assertStringContainsString(route('sale-units.show', $unit), $xml);
        $this->assertStringNotContainsString('tidak-aktif', $xml);
    }

    public function test_slug_urls_canonical_redirect_and_404s(): void
    {
        $a = $this->apt();
        $slugUrl = route('apartments.show', $a);
        $this->assertStringContainsString('/apartments/'.$a->id.'-easton-park-residence-jatinangor-studio', $slugUrl);

        $this->get($slugUrl)->assertOk();
        $this->get('/apartments/'.$a->id.'?checkin=2030-01-01')->assertStatus(301)->assertRedirect($slugUrl.'?checkin=2030-01-01');
        $this->get('/apartments/'.$a->id.'-slug-salah')->assertStatus(301)->assertRedirect($slugUrl);
        $this->get('/apartments/abc')->assertNotFound();

        $off = $this->apt(['status' => 'nonaktif']);
        $this->get(route('apartments.show', $off))->assertNotFound();

        $unit = SaleUnit::create(['title' => 'Fully Furnished', 'apartment_name' => 'Easton Park', 'location' => 'Jatinangor', 'price' => 300000000, 'status' => 'aktif']);
        $this->get('/jual-beli/'.$unit->id)->assertStatus(301)->assertRedirect(route('sale-units.show', $unit));
        $this->get(route('sale-units.show', $unit))->assertOk();
    }

    public function test_detail_page_has_seo_title_description_canonical_schema_and_alt(): void
    {
        $a = $this->apt(['image' => 'x.jpg']);
        $html = $this->get(route('apartments.show', $a))->assertOk()->getContent();

        preg_match('/<title>(.*?)<\/title>/s', $html, $m);
        $this->assertLessThanOrEqual(60, mb_strlen(html_entity_decode($m[1])));
        $this->assertStringContainsString('Sewa Easton Park Residence Jatinangor', html_entity_decode($m[1]));
        $this->assertStringContainsString('<meta name="description"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('apartments.show', $a).'">', $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('<h1>Easton Park Residence Jatinangor</h1>', $html);
    }

    public function test_transactional_pages_are_noindex_and_home_has_faq_schema(): void
    {
        $this->get(route('booking.check'))->assertOk()->assertSee('noindex,nofollow', false);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('"@type":"FAQPage"', $home);
        $this->assertStringContainsString('index,follow', $home);
        $this->assertSame(1, substr_count($home, '<h1'));
    }

    public function test_title_helper_and_image_optimizer(): void
    {
        $this->assertSame('Sewa Studio | Like Hotel', Seo::title('Sewa Studio'));
        $this->assertLessThanOrEqual(60, mb_strlen(Seo::title(str_repeat('Apartemen Mewah ', 8))));

        $path = sys_get_temp_dir().'/lh_big_'.uniqid().'.jpg';
        $im = imagecreatetruecolor(2200, 1500);
        for ($i = 0; $i < 300; $i++) {
            imagefilledrectangle($im, random_int(0, 2100), random_int(0, 1400), random_int(0, 2200), random_int(0, 1500), random_int(0, 0xFFFFFF));
        }
        imagejpeg($im, $path, 100);
        unset($im);
        $before = filesize($path);

        $this->assertTrue(ImageStore::optimize($path));
        [$w, $h] = getimagesize($path);
        $this->assertLessThanOrEqual(1600, max($w, $h));
        $this->assertLessThan($before, filesize($path));
        @unlink($path);
    }
}
