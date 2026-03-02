<?php
// app/views/clients/edit.php
// Variables esperadas: $client (array), $aval (array|null), $users (array)
$c = $client;
$av = $aval ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= url('/clients') ?>">Clientes</a></li>
      <li class="breadcrumb-item"><a href="<?= url('/clients/'.$c['id']) ?>"><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></a></li>
      <li class="breadcrumb-item active">Editar</li>
    </ol>
  </nav>
</div>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-pencil me-2 text-primary"></i>Editar Cliente: <strong><?= htmlspecialchars($c['code']??'') ?></strong>
  </div>
  <div class="card-body">
    <form method="POST" action="<?= url('/clients/'.$c['id'].'/update') ?>" enctype="multipart/form-data">
      <?= \App\Core\CSRF::field() ?>

      <!-- ── DATOS PERSONALES ─────────────────────────────────────── -->
      <h6 class="fw-bold text-primary mb-3 border-bottom pb-1">
        <i class="bi bi-person me-1"></i>Datos Personales
      </h6>
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Primer Nombre <span class="text-danger">*</span></label>
          <input type="text" name="first_name" class="form-control" required
                 value="<?= htmlspecialchars($c['first_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
          <input type="text" name="last_name" class="form-control" required
                 value="<?= htmlspecialchars($c['last_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">No. Identidad</label>
          <input type="text" name="identity_number" class="form-control"
                 value="<?= htmlspecialchars($c['identity_number'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Nacionalidad</label>
          <input type="text" name="nationality" class="form-control"
                 value="<?= htmlspecialchars($c['nationality'] ?? 'Hondureña') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Estado Civil</label>
          <select name="marital_status" id="marital_status" class="form-select" onchange="toggleSpouseFields(this.value)">
            <?php foreach(['soltero'=>'Soltero/a','casado'=>'Casado/a','divorciado'=>'Divorciado/a','viudo'=>'Viudo/a','union_libre'=>'Unión Libre'] as $val=>$lbl): ?>
            <option value="<?= $val ?>" <?= ($c['marital_status']??'soltero') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Profesión</label>
          <input type="text" name="profession" class="form-control" placeholder="Ej: Contador, Docente"
                 value="<?= htmlspecialchars($c['profession'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Oficio / Ocupación</label>
          <input type="text" name="occupation" class="form-control" placeholder="Ej: Comerciante"
                 value="<?= htmlspecialchars($c['occupation'] ?? '') ?>">
        </div>
      </div>

      <!-- ── CÓNYUGE ──────────────────────────────────────────────── -->
      <?php $showSpouse = in_array($c['marital_status']??'', ['casado','union_libre']); ?>
      <div id="spouse-fields" style="display:<?= $showSpouse ? 'block' : 'none' ?>">
        <h6 class="fw-bold text-secondary mb-3 border-bottom pb-1">
          <i class="bi bi-people me-1"></i>Datos del Cónyuge
        </h6>
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Nombre del Cónyuge</label>
            <input type="text" name="spouse_name" class="form-control"
                   value="<?= htmlspecialchars($c['spouse_name'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Identidad del Cónyuge</label>
            <input type="text" name="spouse_identity" class="form-control"
                   value="<?= htmlspecialchars($c['spouse_identity'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Teléfono del Cónyuge</label>
            <input type="text" name="spouse_phone" class="form-control"
                   value="<?= htmlspecialchars($c['spouse_phone'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- ── CONTACTO ──────────────────────────────────────────────── -->
      <h6 class="fw-bold text-primary mb-3 border-bottom pb-1">
        <i class="bi bi-telephone me-1"></i>Contacto
      </h6>
      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label fw-semibold">Celular <span class="text-danger">*</span></label>
          <input type="text" name="phone" class="form-control" required
                 value="<?= htmlspecialchars($c['phone'] ?? '') ?>">
          <div class="form-text text-muted">Número principal de celular</div>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Teléfono Fijo</label>
          <input type="text" name="phone2" class="form-control"
                 value="<?= htmlspecialchars($c['phone2'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Teléfono Trabajo</label>
          <input type="text" name="work_phone" class="form-control"
                 value="<?= htmlspecialchars($c['work_phone'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Correo Electrónico</label>
          <input type="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($c['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Dirección</label>
          <input type="text" name="address" class="form-control"
                 value="<?= htmlspecialchars($c['address'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Ciudad / Municipio</label>
          <input type="text" name="city" class="form-control"
                 value="<?= htmlspecialchars($c['city'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Ingreso Mensual (<?= setting('app_currency','L') ?>)</label>
          <input type="number" name="monthly_income" step="0.01" class="form-control"
                 value="<?= htmlspecialchars($c['monthly_income'] ?? '') ?>">
        </div>
      </div>

      <!-- ── DOCUMENTOS DE IDENTIDAD ──────────────────────────────── -->
      <h6 class="fw-bold text-primary mb-3 border-bottom pb-1">
        <i class="bi bi-card-image me-1"></i>Documentos de Identidad
      </h6>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Identidad – Frente</label>
          <?php if ($c['identity_front_path'] ?? ''): ?>
            <div class="mb-1">
              <img src="<?= htmlspecialchars($c['identity_front_path']) ?>" style="max-height:60px;border:1px solid #ccc;border-radius:4px" alt="Frente">
              <span class="text-muted ms-2" style="font-size:.8rem">Actual</span>
            </div>
          <?php endif; ?>
          <input type="file" name="identity_front" class="form-control" accept="image/*,.pdf">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Identidad – Reverso</label>
          <?php if ($c['identity_back_path'] ?? ''): ?>
            <div class="mb-1">
              <img src="<?= htmlspecialchars($c['identity_back_path']) ?>" style="max-height:60px;border:1px solid #ccc;border-radius:4px" alt="Reverso">
              <span class="text-muted ms-2" style="font-size:.8rem">Actual</span>
            </div>
          <?php endif; ?>
          <input type="file" name="identity_back" class="form-control" accept="image/*,.pdf">
        </div>
      </div>

      <!-- ── REFERENCIAS ───────────────────────────────────────────── -->
      <h6 class="fw-bold text-primary mb-3 border-bottom pb-1">
        <i class="bi bi-person-check me-1"></i>Referencia Personal
      </h6>
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Nombre</label>
          <input type="text" name="ref_personal_name" class="form-control"
                 value="<?= htmlspecialchars($c['ref_personal_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Teléfono</label>
          <input type="text" name="ref_personal_phone" class="form-control"
                 value="<?= htmlspecialchars($c['ref_personal_phone'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Parentesco / Relación</label>
          <input type="text" name="ref_personal_rel" class="form-control"
                 value="<?= htmlspecialchars($c['ref_personal_rel'] ?? '') ?>">
        </div>
      </div>

      <h6 class="fw-bold text-primary mb-3 border-bottom pb-1">
        <i class="bi bi-building me-1"></i>Referencia Laboral
      </h6>
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Nombre Jefe / Contacto</label>
          <input type="text" name="ref_labor_name" class="form-control"
                 value="<?= htmlspecialchars($c['ref_labor_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Teléfono Laboral</label>
          <input type="text" name="ref_labor_phone" class="form-control"
                 value="<?= htmlspecialchars($c['ref_labor_phone'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Empresa</label>
          <input type="text" name="ref_labor_company" class="form-control"
                 value="<?= htmlspecialchars($c['ref_labor_company'] ?? '') ?>">
        </div>
      </div>

      <!-- ── AVAL ──────────────────────────────────────────────────── -->
      <h6 class="fw-bold text-warning mb-2 border-bottom pb-1">
        <i class="bi bi-shield-check me-1"></i>Datos del Aval (Fiador Solidario)
      </h6>
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Nombre Completo del Aval</label>
          <input type="text" name="aval_full_name" class="form-control"
                 value="<?= htmlspecialchars($av['full_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">No. Identidad del Aval</label>
          <input type="text" name="aval_identity" class="form-control"
                 value="<?= htmlspecialchars($av['identity_number'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Relación con el Deudor</label>
          <input type="text" name="aval_relationship" class="form-control"
                 value="<?= htmlspecialchars($av['relationship'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Celular del Aval</label>
          <input type="text" name="aval_phone" class="form-control"
                 value="<?= htmlspecialchars($av['phone'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Teléfono 2 del Aval</label>
          <input type="text" name="aval_phone2" class="form-control"
                 value="<?= htmlspecialchars($av['phone2'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Dirección del Aval</label>
          <input type="text" name="aval_address" class="form-control"
                 value="<?= htmlspecialchars($av['address'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Ciudad del Aval</label>
          <input type="text" name="aval_city" class="form-control"
                 value="<?= htmlspecialchars($av['city'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Ocupación del Aval</label>
          <input type="text" name="aval_occupation" class="form-control"
                 value="<?= htmlspecialchars($av['occupation'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Identidad Aval – Frente</label>
          <?php if ($av['identity_front_path'] ?? ''): ?>
            <div class="mb-1"><img src="<?= htmlspecialchars($av['identity_front_path']) ?>" style="max-height:50px;border:1px solid #ccc;border-radius:4px"></div>
          <?php endif; ?>
          <input type="file" name="aval_identity_front" class="form-control" accept="image/*,.pdf">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Identidad Aval – Reverso</label>
          <?php if ($av['identity_back_path'] ?? ''): ?>
            <div class="mb-1"><img src="<?= htmlspecialchars($av['identity_back_path']) ?>" style="max-height:50px;border:1px solid #ccc;border-radius:4px"></div>
          <?php endif; ?>
          <input type="file" name="aval_identity_back" class="form-control" accept="image/*,.pdf">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Notas del Aval</label>
          <textarea name="aval_notes" class="form-control" rows="2"><?= htmlspecialchars($av['notes'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- ── CONFIG ─────────────────────────────────────────────────── -->
      <h6 class="fw-bold text-primary mb-3 border-bottom pb-1"><i class="bi bi-gear me-1"></i>Configuración</h6>
      <div class="row g-3 mb-3">
        <?php if (!empty($users)): ?>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Asesor Asignado</label>
          <select name="assigned_to" class="form-select">
            <option value="">-- Sin asignar --</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>" <?= ($c['assigned_to']??'') == $u['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Estado</label>
          <select name="is_active" class="form-select">
            <option value="1" <?= ($c['is_active']??1) ? 'selected':'' ?>>Activo</option>
            <option value="0" <?= !($c['is_active']??1) ? 'selected':'' ?>>Inactivo</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Notas Internas</label>
          <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($c['notes'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar Cambios</button>
        <a href="<?= url('/clients/'.$c['id']) ?>" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
function toggleSpouseFields(val) {
  document.getElementById('spouse-fields').style.display =
    ['casado','union_libre'].includes(val) ? 'block' : 'none';
}
</script>