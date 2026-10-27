@php $withFilters = $withFilters ?? false; $filters = $filters ?? []; @endphp
@if($withFilters)<form id="owner-settlement-filter" method="get" action="{{ route('owner.settlements.index') }}"></form>@endif
<table class="table">
  <tr><th>Tanggal</th><th>Nominal</th><th>Bank</th><th>Rekening</th><th>Status</th><th>Catatan</th></tr>
  @if($withFilters)
  <tr class="filter-row">
    <td></td><td></td><td></td><td></td>
    <td>
      <select form="owner-settlement-filter" name="status" onchange="this.form.submit()" aria-label="Filter status">
        <option value="">Semua</option>
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Rejected'] as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>@endforeach
      </select>
    </td>
    <td class="filter-actions">
      <button form="owner-settlement-filter" class="btn-outline">Cari</button>
      @if(array_filter($filters))<a href="{{ route('owner.settlements.index') }}" class="filter-reset">Reset</a>@endif
    </td>
  </tr>
  @endif
  @forelse($settlements as $s)
    <tr>
      <td>{{ \App\Support\Fmt::dateTime($s->created_at) }}@if($s->paid_at)<br><small>Dibayar: {{ \App\Support\Fmt::dateTime($s->paid_at) }}</small>@endif</td>
      <td>{{ \App\Support\Fmt::rupiah($s->amount) }}</td>
      <td>{{ $s->bank_name }}</td>
      <td>{{ $s->bank_account_name }}<br>{{ $s->bank_account_number }}</td>
      <td><span style="color:{{ \App\Support\Fmt::statusColor($s->status) }};font-weight:800">{{ strtoupper($s->status) }}</span></td>
      <td>{{ $s->admin_notes ?? $s->notes ?? '-' }}</td>
    </tr>
  @empty
    <tr><td colspan="6">
      @if($withFilters && array_filter($filters))Tidak ada data yang cocok dengan filter. <a href="{{ route('owner.settlements.index') }}" style="color:#087a35;font-weight:700">Reset filter</a>
      @else Belum ada riwayat pencairan.@endif
    </td></tr>
  @endforelse
</table>
