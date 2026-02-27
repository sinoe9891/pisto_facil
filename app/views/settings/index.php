<?php
$groupLabels = [
  'general'   => ['General', 'bi-gear'],
  'loans'     => ['Préstamos', 'bi-cash-coin'],
  'dashboard' => ['Dashboard / Alertas', 'bi-speedometer2'],
  'documents' => ['Documentos', 'bi-folder2'],
  'reports'   => ['Reportes', 'bi-bar-chart-line'],
];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0 fw-bold">Configuración del Sistema</h5>
  <div class="text-muted small"><i class="bi bi-shield-lock me-1"></i>Solo SuperAdmin</div>
</div>

<form method="POST" action="<?= url('/settings/update') ?>">
  <?= \App\Core\CSRF::field() ?>

  <div class="row g-3">
    <?php foreach ($settings as $group => $items): if (empty($items)) continue;
      [$groupName, $groupIcon] = $groupLabels[$group] ?? [$group, 'bi-gear'];
    ?>
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold py-2 border-bottom">
          <i class="bi <?= $groupIcon ?> me-2 text-primary"></i><?= $groupName ?>
        </div>
        <div class="card-body">
          <div class="row g-3">
          <?php foreach ($items as $s): ?>
          <div class="col-md-4">
            <label class="form-label fw-semibold" style="font-size:.8rem">
              <?= htmlspecialchars($s['description'] ?? $s['setting_key']) ?>
              <span class="text-muted fw-normal">(<?= $s['setting_key'] ?>)</span>
            </label>
            <?php
            $val = htmlspecialchars($s['setting_value']);
            $key = htmlspecialchars($s['setting_key']);
            if ($s['setting_type'] === 'boolean'): ?>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="<?= $key ?>"
                     value="1" <?= $s['setting_value'] ? 'checked' : '' ?>>
            </div>
            <?php elseif ($s['setting_type'] === 'integer'): ?>
            <input type="number" name="<?= $key ?>" class="form-control form-control-sm"
                   value="<?= $val ?>" step="1">
            <?php elseif ($s['setting_type'] === 'decimal'): ?>
            <input type="number" name="<?= $key ?>" class="form-control form-control-sm"
                   value="<?= $val ?>" step="0.0001">
            <?php elseif ($s['setting_type'] === 'json'): ?>
            <textarea name="<?= $key ?>" class="form-control form-control-sm font-monospace" rows="2"><?= $val ?></textarea>
            <?php else: ?>
            <input type="text" name="<?= $key ?>" class="form-control form-control-sm"
                   value="<?= $val ?>" maxlength="255">
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-check-lg me-1"></i>Guardar Configuración
    </button>
    <a href="<?= url('/dashboard') ?>" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
