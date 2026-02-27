<?php $currency = setting('app_currency','L'); ?>

<div class="row justify-content-center">
<div class="col-xl-8">
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= url('/loans') ?>">Préstamos</a></li>
    <li class="breadcrumb-item"><a href="<?= url('/loans/'.$loan['id']) ?>"><?= htmlspecialchars($loan['loan_number']) ?></a></li>
    <li class="breadcrumb-item active">Editar</li>
  </ol>
</nav>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-pencil me-2 text-primary"></i>Editar Préstamo <?= htmlspecialchars($loan['loan_number']) ?>
  </div>
  <div class="card-body">
    <div class="alert alert-info py-2 mb-3" style="font-size:.85rem">
      <i class="bi bi-info-circle me-2"></i>
      Solo se pueden modificar campos de configuración. El monto y tipo de préstamo no se pueden cambiar.
    </div>

    <form method="POST" action="<?= url('/loans/'.$loan['id'].'/update') ?>">
      <?= \App\Core\CSRF::field() ?>

      <div class="row g-3">
        <!-- Info de solo lectura -->
        <div class="col-md-4">
          <label class="form-label text-muted">Número</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($loan['loan_number']) ?>" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label text-muted">Cliente</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($loan['client_name']) ?>" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label text-muted">Principal</label>
          <input type="text" class="form-control" value="<?= $currency ?> <?= number_format($loan['principal'],2) ?>" readonly>
        </div>

        <!-- Editable -->
        <div class="col-md-4">
          <label class="form-label">Asesor Asignado</label>
          <select name="assigned_to" class="form-select">
            <option value="">Sin asignar</option>
            <?php foreach ($advisors as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $loan['assigned_to']==$a['id']?'selected':'' ?>>
              <?= htmlspecialchars($a['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Estado</label>
          <select name="status" class="form-select">
            <?php foreach (['active'=>'Activo','paid'=>'Pagado','defaulted'=>'Moroso','cancelled'=>'Cancelado','restructured'=>'Restructurado'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $loan['status']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tasa Moratoria (%/mes)</label>
          <input type="number" name="late_fee_rate" class="form-control" step="0.01" min="0" max="100"
                 value="<?= number_format($loan['late_fee_rate']*100,2) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Días de Gracia</label>
          <input type="number" name="grace_days" class="form-control" min="0" max="30"
                 value="<?= $loan['grace_days'] ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Notas</label>
          <textarea name="notes" class="form-control" rows="3" maxlength="1000"><?= htmlspecialchars($loan['notes'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i>Guardar Cambios
        </button>
        <a href="<?= url('/loans/'.$loan['id']) ?>" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>
</div>
</div>
