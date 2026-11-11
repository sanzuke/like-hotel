<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationTranslationTest extends TestCase
{
    public function test_validation_messages_are_indonesian_with_readable_field_names(): void
    {
        app()->setLocale('id');

        $v = Validator::make(
            ['email' => 'bukan-email', 'checkin' => ''],
            ['email' => 'email', 'checkin' => 'required', 'price_per_night' => 'required']
        );

        $this->assertSame('Email harus berupa alamat email yang valid.', $v->errors()->first('email'));
        $this->assertSame('Tanggal check-in wajib diisi.', $v->errors()->first('checkin'));
        $this->assertSame('Harga per malam wajib diisi.', $v->errors()->first('price_per_night'));
    }

    public function test_indonesian_validation_file_covers_every_framework_rule(): void
    {
        $en = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
        $id = require base_path('lang/id/validation.php');

        unset($en['custom'], $en['attributes'], $id['custom'], $id['attributes']);

        $missing = array_diff(array_keys(Arr::dot($en)), array_keys(Arr::dot($id)));

        $this->assertSame([], array_values($missing));
    }
}
