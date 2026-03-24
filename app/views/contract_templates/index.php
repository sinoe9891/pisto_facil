<?php

/** @var array $templates */
$isAdmin = \App\Core\Auth::isAdmin();
?>


<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">Plantillas de Documentos</h5>
  <a class="btn btn-primary" href="<?= url('/contract-templates/create') ?>">
    <i class="bi bi-plus-lg me-1"></i>Nueva plantilla
  </a>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($templates ?? [] as $t): ?>
            <tr>
              <td><?= (int)$t['id'] ?></td>
              <td><?= e($t['name'] ?? '') ?></td>
              <td><?= e($t['template_type'] ?? '') ?></td>
              <td>
                <?php if (!empty($t['is_active'])): ?>
                  <span class="badge bg-success">Activa</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactiva</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary"
                  href="<?= url('/contract-templates/' . $t['id'] . '/edit') ?>">
                  <i class="bi bi-pencil"></i>
                </a>
                <a class="btn btn-sm btn-outline-secondary" target="_blank"
                  href="<?= url('/contract-templates/' . $t['id'] . '/preview') ?>">
                  <i class="bi bi-printer"></i>
                </a>
                <a class="btn btn-sm btn-outline-warning"
                  href="<?= url('/contract-templates/' . $t['id'] . '/toggle') ?>">
                  <i class="bi bi-toggle2-on"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($templates)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">No hay plantillas aún.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>