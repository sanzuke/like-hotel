@props(['id', 'title'])
{{-- Popup form. Pakai: <x-modal id="x" title="..."> <form>...</form> </x-modal>, buka dengan <button data-open-dialog="x">.
     Otomatis terbuka lagi bila ada error validasi (dikendalikan skrip di layouts/panel). --}}
<dialog id="{{ $id }}" class="modal" @if($errors->any()) data-open-on-load @endif>
  <div class="modal-head">
    <h3>{{ $title }}</h3>
    <button type="button" class="modal-close" data-close-dialog aria-label="Tutup">&times;</button>
  </div>
  @if($errors->any())
    <div class="alert danger">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
  @endif
  {{ $slot }}
</dialog>
