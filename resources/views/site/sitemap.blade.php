{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($urls as [$loc, $mod, $prio])
  <url>
    <loc>{{ $loc }}</loc>
@if($mod)    <lastmod>{{ $mod->toAtomString() }}</lastmod>
@endif
    <priority>{{ $prio }}</priority>
  </url>
@endforeach
</urlset>
