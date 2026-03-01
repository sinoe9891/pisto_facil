<?php $currency = setting('app_currency', 'L'); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-bold">Préstamos</h5>
    <small class="text-muted"><?= number_format($paged['total']) ?> registros encontrados</small>
  </div>
  <?php if (\App\Core\Auth::isAdmin()): ?>
    <a href="<?= url('/loans/create') ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-circle me-1"></i>Nuevo Préstamo
    </a>
  <?php endif; ?>
</div>

<!-- FILTERS -->
<div class="card shadow-sm border-0 mb-3">
  <div class="card-body py-2">
    <form method="GET" action="<?= url('/loans') ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Número, cliente..."
               value="<?= htmlspecialchars($filters['search']) ?>">
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">Todos los estados</option>
          <?php foreach (['active'=>'Activo','paid'=>'Pagado','defaulted'=>'Moroso','cancelled'=>'Cancelado','deleted'=>'Archivado'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $filters['status']===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="loan_type" class="form-select form-select-sm">
          <option value="">Todos los tipos</option>
          <option value="A" <?= $filters['loan_type']==='A'?'selected':'' ?>>Tipo A – Nivelado</option>
          <option value="B" <?= $filters['loan_type']==='B'?'selected':'' ?>>Tipo B – Variable</option>
          <option value="C" <?= $filters['loan_type']==='C'?'selected':'' ?>>Tipo C – Simple</option>
        </select>
      </div>
      <?php if (\App\Core\Auth::isAdmin()): ?>
      <div class="col-md-2">
        <select name="assigned_to" class="form-select form-select-sm">
          <option value="">Todos asesores</option>
          <?php foreach ($advisors as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $filters['assigned_to']==$a['id']?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-md-auto">
        <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Buscar</button>
        <a href="<?= url('/loans') ?>" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
      </div>
    </form>
  </div>
</div>

<!-- QUICK FILTERS con contadores -->
<?php
$qf = $filters['filter'] ?? '';
$overdueCount  = (int)($counts['overdue_count']  ?? 0);
$upcomingCount = (int)($counts['upcoming_count'] ?? 0);
?>
<div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
  <a href="<?= url('/loans') ?>"
     class="btn btn-sm <?= !$qf ? 'btn-secondary' : 'btn-outline-secondary' ?>">
    Todos
  </a>
  <a href="<?= url('/loans?filter=overdue') ?>"
     class="btn btn-sm <?= $qf==='overdue' ? 'btn-danger' : 'btn-outline-danger' ?>">
    <i class="bi bi-exclamation-triangle me-1"></i>Con Mora
    <?php if ($overdueCount > 0): ?>
    <span class="badge <?= $qf==='overdue' ? 'bg-white text-danger' : 'bg-danger' ?> ms-1">
      <?= $overdueCount ?>
    </span>
    <?php endif; ?>
  </a>
  <a href="<?= url('/loans?filter=upcoming') ?>"
     class="btn btn-sm <?= $qf==='upcoming' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">
    <i class="bi bi-clock me-1"></i>Por Vencer (7 días)
    <?php if ($upcomingCount > 0): ?>
    <span class="badge <?= $qf==='upcoming' ? 'bg-dark' : 'bg-warning text-dark' ?> ms-1">
      <?= $upcomingCount ?>
    </span>
    <?php endif; ?>
  </a>

  <?php if ($qf === 'overdue' && $overdueCount > 0): ?>
  <span class="text-muted small ms-2">
    <i class="bi bi-info-circle me-1"></i>
    <?= $overdueCount ?> préstamo<?= $overdueCount>1?'s':'' ?> con cuotas vencidas
  </span>
  <?php elseif ($qf === 'upcoming' && $upcomingCount > 0): ?>
  <span class="text-muted small ms-2">
    <i class="bi bi-info-circle me-1"></i>
    <?= $upcomingCount ?> vencen esta semana
  </span>
  <?php endif; ?>
</div>

<!-- TABLE -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-custom table-hover mb-0">
        <thead>
          <tr>
            <th>Número</th>
            <th>Cliente</th>
            <th class="text-center">Tipo</th>
            <th class="text-end">Principal</th>
            <th class="text-end">Saldo</th>
            <th class="text-center">Cuotas</th>
            <th>Desembolso</th>
            <th class="text-center">Estado / Mora</th>
            <th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
<?php
$statusMap = [
  'active'       => ['success','Activo'],
  'paid'         => ['primary','Pagado'],
  'defaulted'    => ['danger','Moroso'],
  'cancelled'    => ['secondary','Cancelado'],
  'restructured' => ['warning','Restructurado'],
];
$typeLabels = ['A'=>'Nivelado','B'=>'Variable','C'=>'Simple'];
$freqLabels = ['weekly'=>'Sem','biweekly'=>'Quin','monthly'=>'Mens','bimonthly'=>'Bim','quarterly'=>'Trim','semiannual'=>'Sem','annual'=>'Anual'];
$today = date('Y-m-d');
?>
<?php foreach ($paged['data'] as $l): ?>
<?php
[$sc, $sl] = $statusMap[$l['status']] ?? ['secondary','Otro'];
$daysOD  = (int)($l['days_overdue']  ?? 0);
$daysUntil = $l['days_until_due'] ?? null;
$overCnt = (int)($l['overdue_count'] ?? 0);

// Clase de fila según urgencia
if ($daysOD > 30)       $rowClass = 'table-danger';
elseif ($daysOD > 0)    $rowClass = 'table-warning';
elseif ($daysUntil !== null && $daysUntil <= 3) $rowClass = 'table-warning bg-opacity-50';
else $rowClass = '';
?>
<tr class="<?= $rowClass ?>" title="<?= $daysOD > 0 ? "$daysOD días de mora" : ($daysUntil!==null ? "Vence en $daysUntil días" : '') ?>">
  <td>
    <a href="<?= url('/loans/'.$l['id']) ?>" class="fw-semibold text-decoration-none text-primary">
      <?= htmlspecialchars($l['loan_number']) ?>
    </a>
  </td>
  <td>
    <a href="<?= url('/clients/'.$l['client_id']) ?>" class="text-dark text-decoration-none">
      <?= htmlspecialchars($l['client_name']) ?>
    </a>
  </td>
  <td class="text-center">
    <span class="badge bg-info text-dark">
      <?= $l['loan_type'] ?> · <?= $typeLabels[$l['loan_type']] ?? '' ?>
    </span>
    <?php if (!empty($l['payment_frequency']) && $l['payment_frequency'] !== 'monthly'): ?>
    <span class="badge bg-secondary" style="font-size:.65rem"><?= $freqLabels[$l['payment_frequency']] ?? $l['payment_frequency'] ?></span>
    <?php endif; ?>
  </td>
  <td class="text-end"><?= $currency ?> <?= number_format($l['principal'],2) ?></td>
  <td class="text-end <?= $l['balance']>0?'text-danger fw-semibold':'' ?>">
    <?= $currency ?> <?= number_format($l['balance'],2) ?>
  </td>
  <td class="text-center text-muted"><?= $l['term_months'] ? $l['term_months'].'c' : '–' ?></td>
  <td><?= date('d/m/Y',strtotime($l['disbursement_date'])) ?></td>

  <!-- Columna estado + indicador de mora/vencimiento -->
  <td class="text-center">
    <span class="badge bg-<?= $sc ?>"><?= $sl ?></span>

    <?php if ($daysOD > 0 && $l['status'] === 'active'): ?>
    <?php
    // Intensidad según antigüedad
    if ($daysOD > 60)     { $moraBg = 'bg-danger';   $moraText = 'text-white'; }
    elseif ($daysOD > 30) { $moraBg = 'bg-danger';   $moraText = 'text-white'; }
    elseif ($daysOD > 15) { $moraBg = 'bg-warning';  $moraText = 'text-dark'; }
    else                  { $moraBg = 'bg-warning';  $moraText = 'text-dark'; }
    ?>
    <div class="mt-1">
      <span class="badge <?= $moraBg ?> <?= $moraText ?>" style="font-size:.7rem">
        <i class="bi bi-exclamation-triangle me-1"></i><?= $daysOD ?>d mora
      </span>
      <?php if ($overCnt > 1): ?>
      <span class="badge bg-danger bg-opacity-75" style="font-size:.65rem"><?= $overCnt ?> cuotas</span>
      <?php endif; ?>
    </div>

    <?php elseif ($daysUntil !== null && $l['status'] === 'active'): ?>
    <?php
    // Color según cercanía
    if ($daysUntil === 0)     { $dueBg = 'bg-danger';   $dueLabel = 'Vence HOY'; }
    elseif ($daysUntil <= 3)  { $dueBg = 'bg-warning';  $dueLabel = "Vence en {$daysUntil}d"; }
    elseif ($daysUntil <= 7)  { $dueBg = 'bg-warning bg-opacity-75'; $dueLabel = "Vence en {$daysUntil}d"; }
    else                      { $dueBg = 'bg-success bg-opacity-75'; $dueLabel = "Vence en {$daysUntil}d"; }
    ?>
    <div class="mt-1">
      <span class="badge <?= $dueBg ?> text-dark" style="font-size:.7rem">
        <i class="bi bi-clock me-1"></i><?= $dueLabel ?>
      </span>
    </div>
    <?php endif; ?>
  </td>

  <td class="text-center">
    <div class="btn-group btn-group-sm">
      <a href="<?= url('/loans/'.$l['id']) ?>" class="btn btn-outline-secondary" title="Ver detalle">
        <i class="bi bi-eye"></i>
      </a>
      <?php if ($l['status']==='active'): ?>
      <a href="<?= url('/payments/create?loan_id='.$l['id']) ?>" class="btn btn-outline-success" title="Registrar pago">
        <i class="bi bi-cash"></i>
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::isAdmin()): ?>
      <a href="<?= url('/loans/'.$l['id'].'/edit') ?>" class="btn btn-outline-primary" title="Editar">
        <i class="bi bi-pencil"></i>
      </a>
      <button type="button"
        onclick="confirmDelete(<?= $l['id'] ?>,'<?= htmlspecialchars($l['loan_number'],ENT_QUOTES) ?>','<?= htmlspecialchars($l['client_name'],ENT_QUOTES) ?>','<?= $l['status'] ?>')"
        class="btn btn-outline-danger" title="Cancelar / Eliminar">
        <i class="bi bi-trash"></i>
      </button>
      <?php endif; ?>
    </div>
  </td>
</tr>
<?php endforeach; ?>
<?php if (empty($paged['data'])): ?>
<tr>
  <td colspan="9" class="text-center text-muted py-5">
    <i class="bi bi-<?= $qf==='overdue'?'exclamation-triangle text-success':($qf==='upcoming'?'clock text-success':'search') ?> d-block fs-2 mb-2"></i>
    <?php if ($qf==='overdue'): ?>
      <strong class="text-success">¡Sin préstamos en mora!</strong><br>
      <small>Todos los préstamos están al día.</small>
    <?php elseif ($qf==='upcoming'): ?>
      <strong class="text-success">Sin vencimientos esta semana.</strong>
    <?php else: ?>
      No se encontraron préstamos.
    <?php endif; ?>
  </td>
</tr>
<?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($paged['last_page'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-3 border-top">
      <small class="text-muted">Página <?= $paged['current_page'] ?> de <?= $paged['last_page'] ?></small>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($p = 1; $p <= $paged['last_page']; $p++): ?>
          <li class="page-item <?= $p==$paged['current_page']?'active':'' ?>">
            <a class="page-link" href="?page=<?= $p ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $p ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- Leyenda de colores -->
<div class="d-flex gap-3 mt-2 flex-wrap" style="font-size:.75rem;color:#6b7280">
  <span><span class="d-inline-block" style="width:12px;height:12px;background:#fef3c7;border:1px solid #f59e0b;border-radius:2px;vertical-align:middle"></span> Mora 1–30 días</span>
  <span><span class="d-inline-block" style="width:12px;height:12px;background:#fee2e2;border:1px solid #dc2626;border-radius:2px;vertical-align:middle"></span> Mora +30 días</span>
  <span><i class="bi bi-clock text-warning"></i> Por vencer esta semana</span>
  <span><i class="bi bi-check-circle text-success"></i> Al día</span>
</div>

<!-- Hidden delete form -->
<form id="deleteLoanForm" method="POST" action="" class="d-none">
  <?= \App\Core\CSRF::field() ?>
</form>

<script>
function confirmDelete(id, loanNumber, clientName, status) {
  const isActive = status === 'active';
  Swal.fire({
    title: isActive ? 'Cancelar Préstamo Activo' : 'Eliminar Préstamo',
    html: isActive
      ? `El préstamo <strong>${loanNumber}</strong> de <strong>${clientName}</strong> está activo.<br>Se marcará como <strong>Cancelado</strong>.`
      : `¿Eliminar <strong>${loanNumber}</strong> de <strong>${clientName}</strong>?<br>Esta acción no se puede deshacer.`,
    icon: isActive ? 'warning' : 'error',
    showCancelButton: true,
    confirmButtonColor: isActive ? '#f59e0b' : '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: isActive ? '<i class="bi bi-x-circle me-1"></i>Sí, cancelar' : '<i class="bi bi-trash me-1"></i>Eliminar',
    cancelButtonText: 'No, volver',
    reverseButtons: true,
  }).then(r => {
    if (r.isConfirmed) {
      const form = document.getElementById('deleteLoanForm');
      form.action = '<?= url('/loans/') ?>' + id + '/delete';
      form.submit();
    }
  });
}
</script>