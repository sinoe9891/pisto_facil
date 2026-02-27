<?php
$currency = setting('app_currency', 'L');
$role     = $auth['role'] ?? '';
?>

<!-- METRIC CARDS -->
<div class="row g-3 mb-4">
  <!-- Préstamos Activos -->
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon" style="background:#dbeafe">
          <i class="bi bi-cash-coin" style="color:#2563eb"></i>
        </div>
        <div>
          <div class="text-muted small">Préstamos Activos</div>
          <div class="fw-bold fs-4 lh-1"><?= number_format($totalLoans['total'] ?? 0) ?></div>
          <div class="text-muted small mt-1">
            <?= $currency ?> <?= number_format($totalLoans['total_balance'] ?? 0, 2) ?> en cartera
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Clientes -->
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon" style="background:#dcfce7">
          <i class="bi bi-people" style="color:#16a34a"></i>
        </div>
        <div>
          <div class="text-muted small">Clientes Activos</div>
          <div class="fw-bold fs-4 lh-1"><?= number_format($totalClients['total'] ?? 0) ?></div>
          <div class="text-muted small mt-1">registrados en el sistema</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Cobros este mes -->
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon" style="background:#fef3c7">
          <i class="bi bi-wallet2" style="color:#d97706"></i>
        </div>
        <div>
          <div class="text-muted small">Cobros este Mes</div>
          <div class="fw-bold fs-4 lh-1"><?= $currency ?> <?= number_format($paymentsThisMonth['total'] ?? 0, 2) ?></div>
          <div class="text-muted small mt-1"><?= number_format($paymentsThisMonth['count'] ?? 0) ?> pagos registrados</div>
        </div>
      </div>
    </div>
  </div>

  <!-- En Mora -->
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card shadow-sm h-100" style="border-left:4px solid #ef4444">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon" style="background:#fee2e2">
          <i class="bi bi-exclamation-triangle" style="color:#dc2626"></i>
        </div>
        <div>
          <div class="text-muted small">Cartera Vencida</div>
          <div class="fw-bold fs-4 lh-1 text-danger"><?= $currency ?> <?= number_format($totalOverdue['total_owed'] ?? 0, 2) ?></div>
          <div class="text-muted small mt-1"><?= number_format($totalOverdue['loans_count'] ?? 0) ?> cuotas vencidas</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- TABLES ROW -->
<div class="row g-3 mb-4">

  <!-- VENCIDAS -->
  <div class="col-xl-7">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-danger text-white d-flex align-items-center justify-content-between py-2">
        <span class="fw-semibold"><i class="bi bi-exclamation-octagon me-2"></i>Cuotas Vencidas</span>
        <a href="<?= url('/loans?filter=overdue') ?>" class="btn btn-sm btn-light text-danger">Ver todas</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($overdue)): ?>
          <div class="text-center text-muted py-4"><i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i>¡Sin cuotas vencidas!</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead>
              <tr>
                <th>Cliente</th>
                <th>Préstamo</th>
                <th class="text-center">Días Mora</th>
                <th class="text-end">Pendiente</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($overdue as $r): ?>
              <tr>
                <td>
                  <a href="<?= url('/clients/' . $r['client_id']) ?>" class="fw-semibold text-decoration-none text-dark">
                    <?= htmlspecialchars($r['client_name']) ?>
                  </a>
                  <?php if ($r['client_phone']): ?>
                  <div class="text-muted" style="font-size:.75rem">
                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($r['client_phone']) ?>
                  </div>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?= url('/loans/' . $r['loan_id']) ?>" class="text-primary"><?= htmlspecialchars($r['loan_number']) ?></a>
                  <div class="text-muted" style="font-size:.75rem">Vence: <?= date('d/m/Y', strtotime($r['due_date'])) ?></div>
                </td>
                <td class="text-center">
                  <span class="badge bg-danger"><?= $r['days_late'] ?> días</span>
                </td>
                <td class="text-end fw-semibold text-danger">
                  <?= $currency ?> <?= number_format($r['pending'], 2) ?>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= url('/loans/' . $r['loan_id']) ?>" class="btn btn-outline-secondary" title="Ver préstamo">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= url('/payments/create?loan_id=' . $r['loan_id']) ?>" class="btn btn-outline-success" title="Registrar pago">
                      <i class="bi bi-cash"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- POR VENCER -->
  <div class="col-xl-5">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-warning text-dark d-flex align-items-center justify-content-between py-2">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2"></i>Por Vencer (<?= $alertDays ?> días)</span>
        <a href="<?= url('/loans?filter=upcoming') ?>" class="btn btn-sm btn-light">Ver todas</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($upcoming)): ?>
          <div class="text-center text-muted py-4"><i class="bi bi-calendar-check text-success fs-3 d-block mb-2"></i>¡Sin cuotas próximas!</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead>
              <tr>
                <th>Cliente / Préstamo</th>
                <th class="text-center">Vence en</th>
                <th class="text-end">Monto</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($upcoming as $r): ?>
              <tr>
                <td>
                  <div class="fw-semibold" style="font-size:.85rem"><?= htmlspecialchars($r['client_name']) ?></div>
                  <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($r['loan_number']) ?> · <?= date('d/m/Y', strtotime($r['due_date'])) ?></div>
                </td>
                <td class="text-center">
                  <?php
                  $days = (int)$r['days_left'];
                  $cls  = $days <= 2 ? 'bg-danger' : ($days <= 7 ? 'bg-warning text-dark' : 'bg-info');
                  ?>
                  <span class="badge <?= $cls ?>"><?= $days ?> día<?= $days !== 1 ? 's' : '' ?></span>
                </td>
                <td class="text-end fw-semibold">
                  <?= $currency ?> <?= number_format($r['pending'], 2) ?>
                </td>
                <td>
                  <a href="<?= url('/payments/create?loan_id=' . $r['loan_id']) ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-cash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- BOTTOM ROW: Recent payments + chart -->
<div class="row g-3">
  <!-- RECENT PAYMENTS -->
  <div class="col-xl-8">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white fw-semibold py-2 border-bottom">
        <i class="bi bi-list-check me-2 text-primary"></i>Últimos Pagos Registrados
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead>
              <tr>
                <th>Cliente</th>
                <th>Préstamo</th>
                <th>Fecha</th>
                <th class="text-end">Monto</th>
                <th>Método</th>
                <th>Registró</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($recentPayments as $p): ?>
              <tr>
                <td><?= htmlspecialchars($p['client_name']) ?></td>
                <td><a href="<?= url('/loans/' . $p['loan_id'] ?? '#') ?>"><?= htmlspecialchars($p['loan_number']) ?></a></td>
                <td><?= date('d/m/Y', strtotime($p['payment_date'])) ?></td>
                <td class="text-end text-success fw-semibold"><?= $currency ?> <?= number_format($p['total_received'], 2) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($p['payment_method']) ?></span></td>
                <td class="text-muted" style="font-size:.8rem"><?= htmlspecialchars($p['registered_by']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($recentPayments)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">Sin pagos registrados aún.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- CHART -->
  <div class="col-xl-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white fw-semibold py-2 border-bottom">
        <i class="bi bi-pie-chart me-2 text-primary"></i>Estado de Préstamos
      </div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <canvas id="loanStatusChart" style="max-height:220px"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const raw = <?= json_encode($loanStatusData) ?>;
  const labels = {
    active:'Activo', paid:'Pagado', defaulted:'Moroso',
    cancelled:'Cancelado', restructured:'Restructurado'
  };
  const colors = {
    active:'#2563eb', paid:'#16a34a', defaulted:'#dc2626',
    cancelled:'#64748b', restructured:'#d97706'
  };
  const data = raw.map(r => ({ label: labels[r.status] || r.status, value: parseInt(r.count), color: colors[r.status] || '#94a3b8' }));
  const ctx = document.getElementById('loanStatusChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map(d => d.label),
      datasets: [{ data: data.map(d => d.value), backgroundColor: data.map(d => d.color), borderWidth: 2 }]
    },
    options: {
      cutout: '65%',
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } }
      }
    }
  });
})();
</script>