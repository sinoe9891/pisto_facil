<?php $isEdit = $editMode ?? false; $u = $user ?? []; ?>

<div class="row justify-content-center">
<div class="col-xl-7">

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= url('/users') ?>">Usuarios</a></li>
    <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Nuevo' ?></li>
  </ol>
</nav>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-person-gear me-2 text-primary"></i><?= $isEdit ? 'Editar Usuario' : 'Nuevo Usuario' ?>
  </div>
  <div class="card-body">
    <form method="POST" action="<?= $isEdit ? url('/users/'.$u['id'].'/update') : url('/users/store') ?>">
      <?= \App\Core\CSRF::field() ?>

      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required maxlength="150"
                 value="<?= htmlspecialchars($u['name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Rol <span class="text-danger">*</span></label>
          <select name="role_id" class="form-select" required>
            <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id'] ?>" <?= ($u['role_id']??'') == $r['id']?'selected':'' ?>>
              <?= htmlspecialchars($r['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" required maxlength="180"
                 value="<?= htmlspecialchars($u['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono</label>
          <input type="tel" name="phone" class="form-control" maxlength="20"
                 value="<?= htmlspecialchars($u['phone'] ?? '') ?>" placeholder="+504 XXXX-XXXX">
        </div>
        <div class="col-md-6">
          <label class="form-label">
            Contraseña <?= !$isEdit ? '<span class="text-danger">*</span>' : '<span class="text-muted small">(dejar vacío para no cambiar)</span>' ?>
          </label>
          <div class="input-group">
            <input type="password" name="password" id="pwInput" class="form-control"
                   <?= !$isEdit ? 'required' : '' ?> minlength="8"
                   placeholder="Mínimo 8 caracteres">
            <button type="button" class="btn btn-outline-secondary" onclick="togglePw()">
              <i class="bi bi-eye" id="pwEye"></i>
            </button>
          </div>
          <div class="form-text">Debe tener 8+ caracteres, una mayúscula, número y símbolo.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Confirmar Contraseña</label>
          <input type="password" name="password_confirmation" class="form-control"
                 placeholder="Repetir contraseña">
        </div>
      </div>

      <!-- Password strength meter -->
      <div class="mt-2 mb-3 d-none" id="strengthMeter">
        <div class="progress" style="height:4px">
          <div class="progress-bar" id="strengthBar" role="progressbar" style="width:0%"></div>
        </div>
        <small id="strengthText" class="text-muted"></small>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Actualizar' : 'Crear Usuario' ?>
        </button>
        <a href="<?= url('/users') ?>" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>
</div>
</div>

<script>
function togglePw() {
  const input = document.getElementById('pwInput');
  const icon  = document.getElementById('pwEye');
  input.type  = input.type === 'password' ? 'text' : 'password';
  icon.className = input.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}

document.getElementById('pwInput').addEventListener('input', function() {
  const val = this.value;
  const meter = document.getElementById('strengthMeter');
  const bar   = document.getElementById('strengthBar');
  const txt   = document.getElementById('strengthText');
  if (!val) { meter.classList.add('d-none'); return; }
  meter.classList.remove('d-none');

  let score = 0;
  if (val.length >= 8)             score++;
  if (/[A-Z]/.test(val))          score++;
  if (/[0-9]/.test(val))          score++;
  if (/[\W_]/.test(val))          score++;

  const levels = [
    [25, 'bg-danger',  'Muy débil'],
    [50, 'bg-warning', 'Débil'],
    [75, 'bg-info',    'Buena'],
    [100,'bg-success', 'Fuerte'],
  ];
  const [w, cls, label] = levels[score-1] || levels[0];
  bar.style.width = w + '%';
  bar.className   = 'progress-bar ' + cls;
  txt.textContent = 'Seguridad: ' + label;
});
</script>
