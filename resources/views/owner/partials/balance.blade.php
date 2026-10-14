<div class="stats">
  <div class="stat"><p>Total Hak Owner</p><h2>{{ rupiah($balance['total_hak']) }}</h2></div>
  <div class="stat"><p>Proses/Sudah Cair</p><h2>{{ rupiah($balance['requested']) }}</h2></div>
  <div class="stat"><p>Sudah Dibayar</p><h2>{{ rupiah($balance['paid']) }}</h2></div>
  <div class="stat"><p>Saldo Tersedia</p><h2>{{ rupiah($balance['available']) }}</h2></div>
</div>
