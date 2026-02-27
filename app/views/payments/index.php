<?php $currency = setting('app_currency','L'); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-bold">Pagos Registrados</h5>
    <small class="text-muted"><?= count($payments) ?> pagos · Total: <strong class="text-success"><?= $currency ?> <?= number_format($totalAmt,2) ?></strong></small>
  </div>
  <a href="<?= url('/payments/create') ?>" class="btn btn-success btn-sm">
    <i class="bi bi-cash me-1"></i>Registrar Pago
  </a>
</div>

<!-- FILTERS -->
<div class="card shadow-sm border-0 mb-3">
  <div class="card-body py-2">
    <form method="GET" action="<?= url('/payments') ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Número, cliente, préstamo..."
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label form-label-sm mb-0">Desde</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label form-label-sm mb-0">Hasta</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
      </div>
      <div class="col-md-auto">
        <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
        <a href="<?= url('/payments') ?>" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-custom table-hover mb-0">
        <thead>
          <tr>
            <th>Número</th><th>Fecha</th><th>Cliente / Préstamo</th>
            <th>Método</th><th class="text-end">Monto</th>
            <th>Registró</th><th class="text-center">Estado</th><th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
        <tr class="<?= $p['voided']?'table-secondary':'' ?>">
          <td>
            <a href="<?= url('/payments/'.$p['id']) ?>" class="fw-semibold text-decoration-none">
              <?= htmlspecialchars($p['payment_number']) ?>
            </a>
          </td>
          <td><?= date('d/m/Y',strtotime($p['payment_date'])) ?></td>
          <td>
            <div><?= htmlspecialchars($p['client_name']) ?></div>
            <div class="text-muted" style="font-size:.75rem">
              <a href="<?= url('/loans/'.$p['loan_id']) ?>"><?= htmlspecialchars($p['loan_number']) ?></a>
            </div>
          </td>
          <td><span class="badge bg-secondary"><?= htmlspecialchars($p['payment_method']) ?></span></td>
          <td class="text-end fw-semibold <?= $p['voided']?'text-decoration-line-through text-muted':'text-success' ?>">
            <?= $currency ?> <?= number_format($p['total_received'],2) ?>
          </td>
          <td style="font-size:.8rem"><?= htmlspecialchars($p['registered_by_name']) ?></td>
          <td class="text-center">
            <?php if ($p['voided']): ?>
            <span class="badge bg-danger">Anulado</span>
            <?php else: ?>
            <span class="badge bg-success">Válido</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <a href="<?= url('/payments/'.$p['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Ver detalle">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($payments)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">
          <i class="bi bi-inbox d-block fs-3 mb-2"></i>No se encontraron pagos en este período.
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
