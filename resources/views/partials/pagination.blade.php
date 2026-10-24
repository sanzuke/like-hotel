{{-- Pagination umum: ringkasan jumlah data + tombol halaman bernomor. Pakai: @include('partials.pagination', ['paginator' => $items]) --}}
<div class="pager">
  <span class="pager-info">
    @if($paginator->total() > 0)
      Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
    @else
      Tidak ada data
    @endif
  </span>

  @if($paginator->hasPages())
    <nav class="pagination" aria-label="Navigasi halaman">
      @foreach($paginator->linkCollection() as $link)
        @php
          $label = $loop->first ? '‹' : ($loop->last ? '›' : $link['label']);
          $aria = $loop->first ? 'Halaman sebelumnya' : ($loop->last ? 'Halaman berikutnya' : 'Halaman '.$link['label']);
        @endphp
        @if($link['active'])
          <span class="active" aria-current="page">{{ $label }}</span>
        @elseif($link['url'] === null)
          <span class="disabled">{{ $label }}</span>
        @else
          <a href="{{ $link['url'] }}" aria-label="{{ $aria }}">{{ $label }}</a>
        @endif
      @endforeach
    </nav>
  @endif
</div>
