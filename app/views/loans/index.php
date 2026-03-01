<?php $currency = setting('app_currency','L'); ?>

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
          <?php foreach (['active'=>'Activo','paid'=>'Pagado','defaulted'=>'Moroso','cancelled'=>'Cancelado'] as $v=>$l): ?>
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

<!-- QUICK FILTERS -->
<div class="d-flex gap-2 mb-3 flex-wrap">
  <?php $qf = $filters['filter'] ?? ''; ?>
  <a href="<?= url('/loans') ?>" class="btn btn-sm <?= !$qf?'btn-secondary':'btn-outline-secondary' ?>">Todos</a>
  <a href="<?= url('/loans?filter=overdue') ?>" class="btn btn-sm <?= $qf==='overdue'?'btn-danger':'btn-outline-danger' ?>">
    <i class="bi bi-exclamation-triangle me-1"></i>Con mora
  </a>
  <a href="<?= url('/loans?filter=upcoming') ?>" class="btn btn-sm <?= $qf==='upcoming'?'btn-warning text-dark':'btn-outline-warning' ?>">
    <i class="bi bi-clock me-1"></i>Por vencer
  </a>
</div>

<!-- TABLE -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-custom table-hover mb-0">
        <thead>
          <tr>
            <th>Número</th><th>Cliente</th><th class="text-center">Tipo</th>
            <th class="text-end">Principal</th><th class="text-end">Saldo</th>
            <th class="text-center">Cuotas</th><th>Desembolso</th>
            <th class="text-center">Estado</th><th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $statusMap = [
          'active'      => ['success','Activo'],
          'paid'        => ['primary','Pagado'],
          'defaulted'   => ['danger','Moroso'],
          'cancelled'   => ['secondary','Cancelado'],
          'restructured'=> ['warning','Restructurado'],
        ];
        $typeLabels = ['A'=>'Nivelado','B'=>'Variable','C'=>'Simple'];
        $freqLabels = ['weekly'=>'Sem','biweekly'=>'Quin','monthly'=>'Mens','bimonthly'=>'Bim','quarterly'=>'Trim','semiannual'=>'Sem','annual'=>'Anual'];
        ?>
        <?php foreach ($paged['data'] as $l): ?>
        <?php [$sc,$sl] = $statusMap[$l['status']] ?? ['secondary','Otro']; ?>
        <tr>
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
          <td class="text-center"><span class="badge bg-<?= $sc ?>"><?= $sl ?></span></td>
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
                onclick="confirmDelete(<?= $l['id'] ?>, '<?= htmlspecialchars($l['loan_number'], ENT_QUOTES) ?>', '<?= htmlspecialchars($l['client_name'], ENT_QUOTES) ?>', '<?= $l['status'] ?>')"
                class="btn btn-outline-danger" title="Cancelar / Eliminar">
                <i class="bi bi-trash"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($paged['data'])): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">
          <i class="bi bi-search d-block fs-3 mb-2"></i>No se encontraron préstamos.
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($paged['last_page'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-3 border-top">
      <small class="text-muted">Página <?= $paged['current_page'] ?> de <?= $paged['last_page'] ?></small>
      <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($p=1;$p<=$paged['last_page'];$p++): ?>
        <li class="page-item <?= $p==$paged['current_page']?'active':'' ?>">
          <a class="page-link" href="?page=<?= $p ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Hidden delete form (POST with CSRF) -->
<form id="deleteLoanForm" method="POST" action="" class="d-none">
  <?= \App\Core\CSRF::field() ?>
</form>

<script>
function confirmDelete(id, loanNumber, clientName, status) {
  const isActive = status === 'active';
  const title    = isActive ? 'Cancelar Préstamo Activo' : 'Eliminar Préstamo';
  const text     = isActive
    ? `El préstamo <strong>${loanNumber}</strong> de <strong>${clientName}</strong> está activo.<br>Se marcará como <strong>Cancelado</strong> y no se podrán registrar más pagos.`
    : `¿Eliminar el préstamo <strong>${loanNumber}</strong> de <strong>${clientName}</strong>?<br>Esta acción no se puede deshacer.`;

  Swal.fire({
    title: title,
    html: text,
    icon: isActive ? 'warning' : 'error',
    showCancelButton: true,
    confirmButtonColor: isActive ? '#f59e0b' : '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: isActive ? '<i class="bi bi-x-circle me-1"></i>Sí, cancelar préstamo' : '<i class="bi bi-trash me-1"></i>Sí, eliminar',
    cancelButtonText: 'No, volver',
    reverseButtons: true,
  }).then(result => {
    if (result.isConfirmed) {
      const form = document.getElementById('deleteLoanForm');
      form.action = '<?= url('/loans/') ?>' + id + '/delete';
      form.submit();
    }
  });
}
</script>
