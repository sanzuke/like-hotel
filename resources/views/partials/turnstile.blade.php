@if(\App\Rules\Turnstile::enabled())
  <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="id" style="margin:8px 0 14px"></div>
  @error('cf-turnstile-response')<div class="field-error">{{ $message }}</div>@enderror
  @once
    @push('scripts')<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endpush
  @endonce
@endif
