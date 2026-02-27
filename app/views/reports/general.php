<?php $currency = setting('app_currency','L'); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0 fw-bold">Reporte General de Préstamos</h5>
  <div class="btn-group btn-group-sm">
    <a href="<?= url('/reports/projection') ?>" class="btn btn-outline-success">
      <i class="bi bi-graph-up me-1"></i>Proyección
    </a>
    <a href="<?= url('/reports/export?' . http_build_query($filters)) ?>" class="btn btn-outline-primary">
      <i class="bi bi-download me-1"></i>Exportar CSV
    </a>
  </div>
</div>

<!-- FILTERS -->
<div class="card shadow-sm border-0 mb-3">
  <div class="card-body py-2">
    <form method="GET" action="<?= url('/reports/general') ?>" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label form-label-sm mb-0">Desde</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= $filters['from'] ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label form-label-sm mb-0">Hasta</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= $filters['to'] ?>">
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">Todos los estados</option>
          <?php foreach (['active'=>'Activo','paid'=>'Pagado','defaulted'=>'Moroso','cancelled'=>'Cancelado'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $filters['status']===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="loan_type" class="form-select form-select-sm">
          <option value="">Todos los tipos</option>
          <option value="A" <?= $filters['loanType']==='A'?'selected':'' ?>>Tipo A</option>
          <option value="B" <?= $filters['loanType']==='B'?'selected':'' ?>>Tipo B</option>
          <option value="C" <?= $filters['loanType']==='C'?'selected':'' ?>>Tipo C</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="advisor" class="form-select form-select-sm">
          <option value="">Todos asesores</option>
          <?php foreach ($advisors as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $filters['advisor']==$a['id']?'selected':'' ?>>
            <?= htmlspecialchars($a['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-auto">
        <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
        <a href="<?= url('/reports/general') ?>" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
      </div>
    </form>
  </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
  <?php $cards = [
    ['Total Préstamos',    $summary['total_loans'],       'bi-hash',            'primary',  false],
    ['Capital Prestado',   $summary['total_principal'],   'bi-cash-stack',      'info',     true],
    ['Total Cobrado',      $summary['total_collected'],   'bi-check-circle',    'success',  true],
    ['Intereses Cobrados', $summary['total_interest'],    'bi-percent',         'warning',  true],
    ['Mora Cobrada',       $summary['total_late_fees'],   'bi-exclamation-circle','danger', true],
    ['Cartera Vigente',    $summary['total_outstanding'], 'bi-clock',           'secondary',true],
  ]; ?>
  <?php foreach ($cards as [$label,$val,$icon,$color,$isMoney]): ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body p-3 text-center">
        <i class="bi <?= $icon ?> text-<?= $color ?> fs-4"></i>
        <div class="fw-bold mt-1 <?= $color==='danger'?'text-danger':'' ?>">
          <?= $isMoney ? $currency.' '.number_format((float)$val,2) : number_format((float)$val) ?>
        </div>
        <div class="text-muted" style="font-size:.72rem"><?= $label ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- CHART -->
<?php if (!empty($chartData)): ?>
<div class="card shadow-sm border-0 mb-3">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-bar-chart-line me-2 text-primary"></i>Cobros Mensuales
  </div>
  <div class="card-body" style="height:200px">
    <canvas id="collectionsChart"></canvas>
  </div>
</div>
<?php endif; ?>

<!-- LOANS TABLE -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white fw-semibold py-2 border-bottom d-flex justify-content-between">
    <span><i class="bi bi-table me-2 text-primary"></i>Detalle de Préstamos (<?= count($loans) ?>)</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-custom table-hover mb-0" style="font-size:.8rem">
        <thead>
          <tr>
            <th>Número</th><th>Cliente / DNI</th><th class="text-center">Tipo</th>
            <th class="text-end">Principal</th><th class="text-end">Pagado</th>
            <th class="text-end">Saldo</th><th class="text-end">Interés</th>
            <th class="text-center">Estado</th><th>Asesor</th><th>Fecha</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $statusBadge = ['active'=>'bg-success','paid'=>'bg-primary','defaulted'=>'bg-danger',
                        'cancelled'=>'bg-secondary','restructured'=>'bg-warning'];
        $statusLabel = ['active'=>'Activo','paid'=>'Pagado','defaulted'=>'Moroso',
                        'cancelled'=>'Cancelado','restructured'=>'Restruct.'];
        ?>
        <?php foreach ($loans as $l): ?>
        <tr>
          <td><a href="<?= url('/loans/'.$l['id']) ?>"><?= htmlspecialchars($l['loan_number']) ?></a></td>
          <td>
            <div><?= htmlspecialchars($l['client_name']) ?></div>
            <div class="text-muted"><?= htmlspecialchars($l['identity_number']??'-') ?></div>
          </td>
          <td class="text-center"><span class="badge bg-info text-dark"><?= $l['loan_type'] ?></span></td>
          <td class="text-end"><?= $currency ?> <?= number_format($l['principal'],2) ?></td>
          <td class="text-end text-success"><?= $currency ?> <?= number_format($l['total_paid'],2) ?></td>
          <td class="text-end <?= $l['balance']>0?'text-danger fw-semibold':'' ?>"><?= $currency ?> <?= number_format($l['balance'],2) ?></td>
          <td class="text-end"><?= $currency ?> <?= number_format($l['total_interest_paid'],2) ?></td>
          <td class="text-center">
            <span class="badge <?= $statusBadge[$l['status']]??'bg-secondary' ?>"><?= $statusLabel[$l['status']]??$l['status'] ?></span>
          </td>
          <td><?= htmlspecialchars($l['advisor_name']??'–') ?></td>
          <td><?= date('d/m/Y',strtotime($l['disbursement_date'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($loans)): ?>
        <tr><td colspan="10" class="text-center text-muted py-3">No hay datos para los filtros seleccionados.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (!empty($chartData)): ?>
<script>
(function() {
  const data = <?= json_encode($chartData) ?>;
  const ctx  = document.getElementById('collectionsChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(d => d.month),
      datasets: [{
        label: 'Cobros (<?= $currency ?>)',
        data: data.map(d => parseFloat(d.collected)),
        backgroundColor: '#2563eb88',
        borderColor: '#2563eb',
        borderWidth: 2, borderRadius: 4,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { callback: v => '<?= $currency ?> '+v.toLocaleString() } } }
    }
  });
})();
</script>
<?php endif; ?>
