<?php $isEdit = $editMode ?? false; $c = $client ?? []; ?>

<div class="row justify-content-center">
<div class="col-xl-10">

<!-- BREADCRUMB -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= url('/clients') ?>">Clientes</a></li>
    <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Nuevo' ?></li>
  </ol>
</nav>

<form method="POST" action="<?= $isEdit ? url('/clients/' . $c['id'] . '/update') : url('/clients/store') ?>">
  <?= \App\Core\CSRF::field() ?>

  <div class="row g-3">
    <!-- DATOS PERSONALES -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header fw-semibold py-2 bg-white border-bottom">
          <i class="bi bi-person me-2 text-primary"></i>Datos Personales
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Nombre(s) <span class="text-danger">*</span></label>
              <input type="text" name="first_name" class="form-control"
                     value="<?= htmlspecialchars($c['first_name'] ?? '') ?>" required maxlength="100">
            </div>
            <div class="col-md-4">
              <label class="form-label">Apellido(s) <span class="text-danger">*</span></label>
              <input type="text" name="last_name" class="form-control"
                     value="<?= htmlspecialchars($c['last_name'] ?? '') ?>" required maxlength="100">
            </div>
            <div class="col-md-4">
              <label class="form-label">DNI / RTN</label>
              <input type="text" name="identity_number" class="form-control"
                     value="<?= htmlspecialchars($c['identity_number'] ?? '') ?>" maxlength="30"
                     placeholder="0801-YYYY-XXXXX">
            </div>
            <div class="col-md-4">
              <label class="form-label">Teléfono <span class="text-danger">*</span></label>
              <input type="tel" name="phone" class="form-control"
                     value="<?= htmlspecialchars($c['phone'] ?? '') ?>" required maxlength="20"
                     placeholder="+504 XXXX-XXXX">
            </div>
            <div class="col-md-4">
              <label class="form-label">Teléfono 2</label>
              <input type="tel" name="phone2" class="form-control"
                     value="<?= htmlspecialchars($c['phone2'] ?? '') ?>" maxlength="20">
            </div>
            <div class="col-md-4">
              <label class="form-label">Correo Electrónico</label>
              <input type="email" name="email" class="form-control"
                     value="<?= htmlspecialchars($c['email'] ?? '') ?>" maxlength="180">
            </div>
            <div class="col-md-6">
              <label class="form-label">Dirección</label>
              <textarea name="address" class="form-control" rows="2" maxlength="500"><?= htmlspecialchars($c['address'] ?? '') ?></textarea>
            </div>
            <div class="col-md-3">
              <label class="form-label">Ciudad</label>
              <input type="text" name="city" class="form-control"
                     value="<?= htmlspecialchars($c['city'] ?? '') ?>" maxlength="100">
            </div>
            <div class="col-md-3">
              <label class="form-label">Ocupación</label>
              <input type="text" name="occupation" class="form-control"
                     value="<?= htmlspecialchars($c['occupation'] ?? '') ?>" maxlength="100">
            </div>
            <div class="col-md-3">
              <label class="form-label">Ingreso Mensual (<?= setting('app_currency','L') ?>)</label>
              <input type="number" name="monthly_income" class="form-control" step="0.01" min="0"
                     value="<?= htmlspecialchars($c['monthly_income'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- REFERENCIA -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0">
        <div class="card-header fw-semibold py-2 bg-white border-bottom">
          <i class="bi bi-people me-2 text-secondary"></i>Referencia Personal
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-7">
              <label class="form-label">Nombre Referencia</label>
              <input type="text" name="reference_name" class="form-control" maxlength="150"
                     value="<?= htmlspecialchars($c['reference_name'] ?? '') ?>">
            </div>
            <div class="col-5">
              <label class="form-label">Teléfono</label>
              <input type="tel" name="reference_phone" class="form-control" maxlength="20"
                     value="<?= htmlspecialchars($c['reference_phone'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ASIGNACIÓN -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0">
        <div class="card-header fw-semibold py-2 bg-white border-bottom">
          <i class="bi bi-person-badge me-2 text-secondary"></i>Asignación y Estado
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-8">
              <label class="form-label">Asesor/Cobrador Asignado</label>
              <select name="assigned_to" class="form-select">
                <option value="">-- Sin asignar --</option>
                <?php foreach ($advisors as $a): ?>
                <option value="<?= $a['id'] ?>" <?= ($c['assigned_to'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($a['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($isEdit): ?>
            <div class="col-4">
              <label class="form-label">Activo</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                       <?= ($c['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Habilitado</label>
              </div>
            </div>
            <?php endif; ?>
            <div class="col-12">
              <label class="form-label">Notas Internas</label>
              <textarea name="notes" class="form-control" rows="2" maxlength="1000"
                        placeholder="Observaciones, historial, información adicional..."><?= htmlspecialchars($c['notes'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ACTIONS -->
    <div class="col-12">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Actualizar Cliente' : 'Guardar Cliente' ?>
        </button>
        <a href="<?= url('/clients' . ($isEdit ? '/' . $c['id'] : '')) ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x me-1"></i>Cancelar
        </a>
      </div>
    </div>
  </div><!-- /row -->
</form>
</div><!-- /col -->
</div><!-- /row -->