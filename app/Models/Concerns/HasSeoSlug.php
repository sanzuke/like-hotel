<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * URL ramah SEO: "/apartments/5-easton-park-residence-jatinangor-studio".
 * Pencarian tetap lewat id di depan, jadi URL lama ("/apartments/5") tetap berfungsi
 * dan dialihkan 301 ke URL kanonik oleh controller.
 */
trait HasSeoSlug
{
    abstract protected function seoSlugSource(): string;

    public function getRouteKey(): string
    {
        $slug = Str::slug(Str::limit($this->seoSlugSource(), 80, ''));

        return $slug === '' ? (string) $this->getKey() : $this->getKey().'-'.$slug;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        return preg_match('/^(\d+)(?:-|$)/', (string) $value, $m) ? $this->whereKey((int) $m[1])->first() : null;
    }
}
