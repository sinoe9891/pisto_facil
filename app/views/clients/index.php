<?php $currency = setting('app_currency','L'); ?>

<!-- HEADER -->
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-bold">Clientes</h5>
    <small class="text-muted"><?= number_format($paged['total']) ?> registros encontrados</small>
  </div>
  <?php if (\App\Core\Auth::isAdmin()): ?>
  <a href="<?= url('/clients/create') ?>" class="btn btn-primary btn-sm">
    <i class="bi bi-person-plus me-1"></i> Nuevo Cliente
  </a>
  <?php endif; ?>
</div>

<!-- FILTERS -->
<div class="card shadow-sm border-0 mb-3">
  <div class="card-body py-2">
    <form method="GET" action="<?= url('/clients') ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Buscar nombre, código, DNI, teléfono..."
               value="<?= htmlspecialchars($filters['search']) ?>">
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">Todos los estados</option>
          <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>Activos</option>
          <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>Inactivos</option>
        </select>
      </div>
      <?php if (\App\Core\Auth::isAdmin()): ?>
      <div class="col-md-3">
        <select name="assigned_to" class="form-select form-select-sm">
          <option value="">Todos los asesores</option>
          <?php foreach ($advisors as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $filters['assigned_to'] == $a['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($a['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-md-auto">
        <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Buscar</button>
        <a href="<?= url('/clients') ?>" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
      </div>
    </form>
  </div>
</div>

<!-- TABLE -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-custom table-hover mb-0">
        <thead>
          <tr>
            <th>Código</th>
            <th>Cliente</th>
            <th>DNI/RTN</th>
            <th>Teléfono</th>
            <th>Ciudad</th>
            <th class="text-center">Préstamos</th>
            <th class="text-end">Saldo Total</th>
            <th class="text-center">Estado</th>
            <th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($paged['data'] as $c): ?>
          <tr>
            <td><span class="badge bg-light text-secondary"><?= htmlspecialchars($c['code']) ?></span></td>
            <td>
              <a href="<?= url('/clients/' . $c['id']) ?>" class="fw-semibold text-dark text-decoration-none">
                <?= htmlspecialchars($c['last_name'] . ', ' . $c['first_name']) ?>
              </a>
              <?php if ($c['email']): ?>
              <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($c['email']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($c['identity_number'] ?? '-') ?></td>
            <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
            <td><?= htmlspecialchars($c['city'] ?? '-') ?></td>
            <td class="text-center">
              <span class="badge bg-primary"><?= $c['active_loans'] ?></span>
            </td>
            <td class="text-end">
              <?= $c['total_balance'] > 0 ? '<span class="text-danger fw-semibold">' . $currency . ' ' . number_format($c['total_balance'], 2) . '</span>' : '<span class="text-muted">-</span>' ?>
            </td>
            <td class="text-center">
              <span class="badge <?= $c['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                <?= $c['is_active'] ? 'Activo' : 'Inactivo' ?>
              </span>
            </td>
            <td class="text-center">
              <div class="btn-group btn-group-sm">
                <a href="<?= url('/clients/' . $c['id']) ?>" class="btn btn-outline-secondary" title="Ver detalle">
                  <i class="bi bi-eye"></i>
                </a>
                <?php if (\App\Core\Auth::isAdmin()): ?>
                <a href="<?= url('/clients/' . $c['id'] . '/edit') ?>" class="btn btn-outline-primary" title="Editar">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="<?= url('/loans/create?client_id=' . $c['id']) ?>" class="btn btn-outline-success" title="Nuevo préstamo">
                  <i class="bi bi-plus-circle"></i>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($paged['data'])): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">
          <i class="bi bi-search d-block fs-3 mb-2"></i>No se encontraron clientes.
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($paged['last_page'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-3 border-top">
      <small class="text-muted">
        Mostrando <?= min($paged['per_page'], $paged['total']) ?> de <?= $paged['total'] ?> registros
      </small>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($p = 1; $p <= $paged['last_page']; $p++): ?>
          <li class="page-item <?= $p == $paged['current_page'] ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?>&<?= http_build_query(array_filter($filters)) ?>">
              <?= $p ?>
            </a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
    <?php endif; ?>
  </div>
</div>