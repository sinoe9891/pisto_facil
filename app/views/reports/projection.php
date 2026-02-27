<?php $currency = setting('app_currency','L'); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= url('/reports/general') ?>">Reportes</a></li>
        <li class="breadcrumb-item active">Proyección de Ganancia</li>
      </ol>
    </nav>
  </div>
</div>

<!-- PARAMS FORM -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-sliders me-2 text-primary"></i>Parámetros de Simulación
  </div>
  <div class="card-body">
    <form method="GET" action="<?= url('/reports/projection') ?>" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label fw-semibold">Capital Inicial (<?= $currency ?>)</label>
        <input type="number" name="capital" class="form-control" min="1" step="1000"
               value="<?= number_format($capital,0,'.','') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">Tasa Mensual (%)</label>
        <input type="number" name="rate" class="form-control" min="0.1" step="0.5" max="100"
               value="<?= $rate ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">Plazo (meses)</label>
        <input type="number" name="months" class="form-control" min="1" max="120"
               value="<?= $months ?>">
      </div>
      <div class="col-md-auto">
        <button class="btn btn-primary"><i class="bi bi-calculator me-1"></i>Calcular</button>
      </div>
    </form>
  </div>
</div>

<!-- SUMMARY -->
<div class="row g-3 mb-4">
  <?php $projCards = [
    ['Capital Inicial',    $capital,          'bi-cash-stack',   'info'],
    ['Capital Final',      $totalProjected,   'bi-graph-up',     'success'],
    ['Ganancia Total',     $totalGain,        'bi-piggy-bank',   'warning'],
    ['Retorno (%)',        round($totalGain/$capital*100,2).'%','bi-percent','primary'],
  ]; ?>
  <?php foreach ($projCards as [$lbl,$val,$icon,$color]): ?>
  <div class="col-6 col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body text-center p-3">
        <div class="rounded-circle bg-<?= $color ?> bg-opacity-10 d-inline-flex p-2 mb-2">
          <i class="bi <?= $icon ?> text-<?= $color ?> fs-5"></i>
        </div>
        <div class="fw-bold fs-5"><?= is_string($val) ? $val : $currency.' '.number_format($val,2) ?></div>
        <div class="text-muted small"><?= $lbl ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- CHART -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-graph-up-arrow me-2 text-success"></i>Crecimiento del Capital
  </div>
  <div class="card-body" style="height:250px">
    <canvas id="projectionChart"></canvas>
  </div>
</div>

<!-- TABLE -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-table me-2"></i>Tabla de Proyección Mensual
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-custom table-striped mb-0" style="font-size:.85rem">
        <thead>
          <tr>
            <th class="text-center">Mes</th>
            <th class="text-end">Capital Inicial</th>
            <th class="text-end">Interés (<?= $rate ?>%)</th>
            <th class="text-end">Capital Final</th>
            <th class="text-end">Ganancia Acum.</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($projections as $p): ?>
        <tr>
          <td class="text-center fw-semibold"><?= $p['month'] ?></td>
          <td class="text-end"><?= $currency ?> <?= number_format($p['balance'],2) ?></td>
          <td class="text-end text-success"><?= $currency ?> <?= number_format($p['interest'],2) ?></td>
          <td class="text-end fw-semibold"><?= $currency ?> <?= number_format($p['total'],2) ?></td>
          <td class="text-end text-primary"><?= $currency ?> <?= number_format($p['total']-$capital,2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="fw-bold" style="background:#1e293b;color:#fff">
            <td class="text-center">TOTAL</td>
            <td class="text-end"><?= $currency ?> <?= number_format($capital,2) ?></td>
            <td class="text-end"><?= $currency ?> <?= number_format($totalGain,2) ?></td>
            <td class="text-end"><?= $currency ?> <?= number_format($totalProjected,2) ?></td>
            <td class="text-end"><?= round($totalGain/$capital*100,2) ?>% ROI</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<script>
(function() {
  const data = <?= json_encode($projections) ?>;
  const ctx  = document.getElementById('projectionChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.map(d => 'Mes '+d.month),
      datasets: [
        { label: 'Capital', data: data.map(d=>d.total),
          borderColor:'#2563eb', backgroundColor:'#2563eb22', fill:true, tension:.4, pointRadius:3 },
        { label: 'Ganancia Acum.', data: data.map(d=>d.total-<?= $capital ?>),
          borderColor:'#16a34a', backgroundColor:'transparent', borderDash:[5,3], tension:.4, pointRadius:2 },
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins: { legend: { position:'bottom' } },
      scales: { y: { beginAtZero:true, ticks: { callback: v=>'<?= $currency ?> '+v.toLocaleString() } } }
    }
  });
})();
</script>
