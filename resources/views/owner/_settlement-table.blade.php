<table class="table">
  <tr><th>Tanggal</th><th>Nominal</th><th>Bank</th><th>Rekening</th><th>Status</th><th>Catatan</th></tr>
  @forelse($settlements as $s)
    <tr>
      <td>{{ $s->created_at->format('d-m-Y H:i') }}@if($s->paid_at)<br><small>Dibayar: {{ $s->paid_at->format('d-m-Y H:i') }}</small>@endif</td>
      <td>{{ \App\Support\Fmt::rupiah($s->amount) }}</td>
      <td>{{ $s->bank_name }}</td>
      <td>{{ $s->bank_account_name }}<br>{{ $s->bank_account_number }}</td>
      <td><span style="color:{{ \App\Support\Fmt::statusColor($s->status) }};font-weight:800">{{ strtoupper($s->status) }}</span></td>
      <td>{{ $s->admin_notes ?? $s->notes ?? '-' }}</td>
    </tr>
  @empty
    <tr><td colspan="6">Belum ada riwayat pencairan.</td></tr>
  @endforelse
</table>
