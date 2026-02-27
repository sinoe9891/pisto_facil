<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0 fw-bold">Usuarios del Sistema</h5>
  <a href="<?= url('/users/create') ?>" class="btn btn-primary btn-sm">
    <i class="bi bi-person-plus me-1"></i>Nuevo Usuario
  </a>
</div>

<!-- FILTERS -->
<div class="card shadow-sm border-0 mb-3">
  <div class="card-body py-2">
    <form method="GET" action="<?= url('/users') ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Nombre, email..." value="<?= htmlspecialchars($filters['search']) ?>">
      </div>
      <div class="col-md-3">
        <select name="role" class="form-select form-select-sm">
          <option value="">Todos los roles</option>
          <?php foreach ($roles as $r): ?>
          <option value="<?= $r['slug'] ?>" <?= $filters['role']===$r['slug']?'selected':'' ?>>
            <?= htmlspecialchars($r['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-auto">
        <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Buscar</button>
        <a href="<?= url('/users') ?>" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
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
            <th>Usuario</th><th>Rol</th><th>Teléfono</th>
            <th>Último acceso</th><th class="text-center">Estado</th>
            <th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold"
                   style="width:34px;height:34px;font-size:.75rem;flex-shrink:0">
                <?= strtoupper(substr($u['name'],0,2)) ?>
              </div>
              <div>
                <div class="fw-semibold" style="font-size:.875rem"><?= htmlspecialchars($u['name']) ?></div>
                <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($u['email']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <?php
            $roleColors = ['superadmin'=>'bg-danger','admin'=>'bg-primary','asesor'=>'bg-info text-dark','cliente'=>'bg-secondary'];
            $rc = $roleColors[$u['role_slug']] ?? 'bg-secondary';
            ?>
            <span class="badge <?= $rc ?>"><?= htmlspecialchars($u['role_name']) ?></span>
          </td>
          <td><?= htmlspecialchars($u['phone'] ?? '–') ?></td>
          <td class="text-muted" style="font-size:.8rem">
            <?= $u['last_login'] ? date('d/m/Y H:i',strtotime($u['last_login'])) : 'Nunca' ?>
          </td>
          <td class="text-center">
            <span class="badge <?= $u['is_active']?'bg-success':'bg-secondary' ?>">
              <?= $u['is_active']?'Activo':'Inactivo' ?>
            </span>
          </td>
          <td class="text-center">
            <div class="btn-group btn-group-sm">
              <a href="<?= url('/users/'.$u['id'].'/edit') ?>" class="btn btn-outline-primary" title="Editar">
                <i class="bi bi-pencil"></i>
              </a>
              <?php if ($u['id'] != \App\Core\Auth::id()): ?>
              <a href="#"
                 onclick="confirmAction('<?= url('/users/'.$u['id'].'/toggle') ?>','<?= $u['is_active']?'¿Desactivar':'¿Activar' ?> usuario?','<?= htmlspecialchars($u['name']) ?>','question')"
                 class="btn btn-outline-<?= $u['is_active']?'warning':'success' ?>" title="<?= $u['is_active']?'Desactivar':'Activar' ?>">
                <i class="bi bi-<?= $u['is_active']?'toggle-on':'toggle-off' ?>"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No se encontraron usuarios.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
