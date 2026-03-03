<?php
/**
 * VISTA: views/settings/index.php
 * Configuración del Sistema - SIN DUPLICACIÓN
 */

$groupLabels = [
  'general'   => ['General', 'bi-gear'],
  'loans'     => ['Préstamos', 'bi-cash-coin'],
  'dashboard' => ['Dashboard / Alertas', 'bi-speedometer2'],
  'documents' => ['Documentos', 'bi-folder2'],
  'reports'   => ['Reportes', 'bi-bar-chart-line'],
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h5 class="mb-1 fw-bold">⚙️ Configuración del Sistema</h5>
    <p class="text-muted mb-0" style="font-size: .9rem;">Gestiona todos los parámetros de la aplicación</p>
  </div>
  <div class="text-muted small"><i class="bi bi-shield-lock me-1"></i>Solo SuperAdmin</div>
</div>

<form method="POST" action="<?= url('/settings/update') ?>" id="settingsForm">
  <?= \App\Core\CSRF::field() ?>

  <div class="row g-3">
    <!-- CONFIGURACIONES DE GRUPOS (general, loans, dashboard, documents, reports) -->
    <?php foreach ($settings as $group => $items): if (empty($items)) continue;
      [$groupName, $groupIcon] = $groupLabels[$group] ?? [$group, 'bi-gear'];
    ?>
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white fw-semibold py-3 border-bottom d-flex align-items-center">
            <i class="bi <?= $groupIcon ?> me-2 text-primary" style="font-size: 1.1rem;"></i><?= $groupName ?>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <?php foreach ($items as $s): ?>
                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    <?= htmlspecialchars($s['description'] ?? $s['setting_key']) ?>
                  </label>
                  <small class="text-muted d-block mb-2">(<?= $s['setting_key'] ?>)</small>
                  <?php
                  $val = htmlspecialchars($s['setting_value']);
                  $key = htmlspecialchars($s['setting_key']);
                  if ($s['setting_type'] === 'boolean'): ?>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" name="<?= $key ?>"
                        value="1" <?= $s['setting_value'] ? 'checked' : '' ?>>
                      <label class="form-check-label">Habilitado</label>
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

    <!-- ═════════════════════════════════════════════════════════════ -->
    <!-- SECCIÓN ÚNICA: CUENTAS BANCARIAS PARA PAGOS -->
    <!-- ═════════════════════════════════════════════════════════════ -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold py-3 border-bottom d-flex align-items-center">
          <i class="bi bi-bank2 me-2 text-primary" style="font-size:1.1rem"></i>
          🏦 Cuentas Bancarias para Pagos
        </div>
        <div class="card-body">
          <p class="text-muted mb-4" style="font-size:.9rem">
            <i class="bi bi-info-circle me-1"></i>
            Configure hasta 3 cuentas bancarias. Estas aparecerán en los préstamos y documentos (Pagaré, Contrato)
            como opciones de pago para los clientes.
          </p>

          <div class="row g-3">
            <?php for ($i = 1; $i <= 3; $i++): ?>
              <div class="col-12">
                <div class="card border-light bg-light p-3">
                  <div class="d-flex align-items-center mb-3">
                    <h6 class="mb-0 fw-semibold">
                      <i class="bi bi-bank2 me-2 text-info"></i>Cuenta <?= $i ?>
                    </h6>
                    <div class="ms-auto">
                      <span class="badge bg-secondary" style="font-size:.7rem">Opcional</span>
                    </div>
                  </div>

                  <div class="row g-2">
                    <!-- Nombre del Banco -->
                    <div class="col-md-4">
                      <label class="form-label" style="font-size:.85rem">
                        <i class="bi bi-bank2 me-1"></i>Banco
                      </label>
                      <input
                        type="text"
                        name="bank_name_<?= $i ?>"
                        class="form-control form-control-sm"
                        placeholder="Ej: Banco Atlántida"
                        value="<?= htmlspecialchars(setting("bank_name_$i", '')) ?>">
                    </div>

                    <!-- Número de Cuenta -->
                    <div class="col-md-4">
                      <label class="form-label" style="font-size:.85rem">
                        <i class="bi bi-credit-card me-1"></i>Número de Cuenta
                      </label>
                      <input
                        type="text"
                        name="bank_account_<?= $i ?>"
                        class="form-control form-control-sm"
                        placeholder="Ej: 123456789"
                        value="<?= htmlspecialchars(setting("bank_account_$i", '')) ?>">
                    </div>

                    <!-- Tipo de Cuenta -->
                    <div class="col-md-4">
                      <label class="form-label" style="font-size:.85rem">
                        <i class="bi bi-diagram-3 me-1"></i>Tipo de Cuenta
                      </label>
                      <select name="bank_account_type_<?= $i ?>" class="form-select form-select-sm">
                        <option value="">-- Seleccionar --</option>
                        <option value="checking" <?= setting("bank_account_type_$i") === 'checking' ? 'selected' : '' ?>>
                          Corriente
                        </option>
                        <option value="savings" <?= setting("bank_account_type_$i") === 'savings' ? 'selected' : '' ?>>
                          Ahorros
                        </option>
                      </select>
                    </div>

                    <!-- Titular de la Cuenta -->
                    <div class="col-md-6">
                      <label class="form-label" style="font-size:.85rem">
                        <i class="bi bi-person me-1"></i>Titular de la Cuenta
                      </label>
                      <input
                        type="text"
                        name="bank_account_holder_<?= $i ?>"
                        class="form-control form-control-sm"
                        placeholder="Ej: Empresa S.A. de C.V."
                        value="<?= htmlspecialchars(setting("bank_account_holder_$i", '')) ?>">
                      <small class="text-muted">A nombre de quién está la cuenta</small>
                    </div>

                    <!-- IBAN/Código -->
                    <div class="col-md-6">
                      <label class="form-label" style="font-size:.85rem">
                        <i class="bi bi-code-square me-1"></i>IBAN / Código (Opcional)
                      </label>
                      <input
                        type="text"
                        name="bank_account_iban_<?= $i ?>"
                        class="form-control form-control-sm"
                        placeholder="Ej: HN123456789"
                        value="<?= htmlspecialchars(setting("bank_account_iban_$i", '')) ?>">
                    </div>
                  </div>
                </div>
              </div>
            <?php endfor; ?>
          </div>

          <div class="alert alert-info py-2 mt-3 mb-0" style="font-size:.85rem">
            <i class="bi bi-lightbulb me-2"></i>
            <strong>Tip:</strong>
            Al crear un préstamo, los clientes podrán seleccionar efectivo, transferencia bancaria, cheque u otro método de pago.
          </div>
        </div>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════ -->
    <!-- SECCIÓN: CONFIGURACIÓN DE DOCUMENTOS LEGALES -->
    <!-- ═════════════════════════════════════════════════════════════ -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold py-3 border-bottom d-flex align-items-center">
          <i class="bi bi-file-earmark-pdf me-2 text-primary" style="font-size:1.1rem"></i>
          📄 Configuración de Documentos Legales
        </div>
        <div class="card-body">
          <div class="row g-3">
            
            <!-- CONFIGURACIÓN DE CONTRATO -->
            <div class="col-12">
              <h5 style="color:#333; border-bottom:2px solid #ddd; padding-bottom:10px; margin-bottom:15px;">
                <i class="bi bi-file-text me-2"></i>Contrato de Préstamo
              </h5>

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    Tamaño de Página
                  </label>
                  <small class="text-muted d-block mb-2">Formato de impresión</small>
                  <select name="contract_page_size" class="form-select form-select-sm">
                    <option value="letter" <?= setting('contract_page_size') === 'letter' ? 'selected' : '' ?>>Letter (8.5" × 11")</option>
                    <option value="legal" <?= setting('contract_page_size') === 'legal' ? 'selected' : '' ?>>Legal (8.5" × 14")</option>
                    <option value="a4" <?= setting('contract_page_size') === 'a4' ? 'selected' : '' ?>>A4 (210mm × 297mm)</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    Margen Superior/Inferior
                  </label>
                  <small class="text-muted d-block mb-2">Ej: 1.5cm</small>
                  <input type="text" name="contract_margin_top" class="form-control form-control-sm"
                    value="<?= htmlspecialchars(setting('contract_margin_top', '1.5cm')) ?>" placeholder="1.5cm">
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    Margen Izq/Der
                  </label>
                  <small class="text-muted d-block mb-2">Ej: 2cm</small>
                  <input type="text" name="contract_margin_right" class="form-control form-control-sm"
                    value="<?= htmlspecialchars(setting('contract_margin_right', '2cm')) ?>" placeholder="2cm">
                </div>

                <div class="col-md-12">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    Jurisdicción Competente
                  </label>
                  <small class="text-muted d-block mb-2">Tribunal/Juzgado para cobro judicial</small>
                  <input type="text" name="contract_jurisdiction" class="form-control form-control-sm"
                    value="<?= htmlspecialchars(setting('contract_jurisdiction', 'Juzgado de Letras de lo Civil')) ?>"
                    placeholder="Juzgado de Letras de lo Civil">
                </div>
              </div>
            </div>

            <!-- CONFIGURACIÓN DE PAGARÉ -->
            <div class="col-12">
              <h5 style="color:#333; border-bottom:2px solid #ddd; padding-bottom:10px; margin:20px 0 15px;">
                <i class="bi bi-file-earmark me-2"></i>Pagaré
              </h5>

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    Tamaño de Página
                  </label>
                  <small class="text-muted d-block mb-2">Formato de impresión</small>
                  <select name="pagare_page_size" class="form-select form-select-sm">
                    <option value="letter" <?= setting('pagare_page_size') === 'letter' ? 'selected' : '' ?>>Letter (8.5" × 11")</option>
                    <option value="legal" <?= setting('pagare_page_size') === 'legal' ? 'selected' : '' ?>>Legal (8.5" × 14")</option>
                    <option value="a4" <?= setting('pagare_page_size') === 'a4' ? 'selected' : '' ?>>A4 (210mm × 297mm)</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    Margen Superior/Inferior
                  </label>
                  <small class="text-muted d-block mb-2">Ej: 1.5cm</small>
                  <input type="text" name="pagare_margin_top" class="form-control form-control-sm"
                    value="<?= htmlspecialchars(setting('pagare_margin_top', '1.5cm')) ?>" placeholder="1.5cm">
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    Margen Izq/Der
                  </label>
                  <small class="text-muted d-block mb-2">Ej: 2cm</small>
                  <input type="text" name="pagare_margin_right" class="form-control form-control-sm"
                    value="<?= htmlspecialchars(setting('pagare_margin_right', '2cm')) ?>" placeholder="2cm">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    Jurisdicción Competente
                  </label>
                  <small class="text-muted d-block mb-2">Tribunal/Juzgado para ejecución</small>
                  <input type="text" name="pagare_jurisdiction" class="form-control form-control-sm"
                    value="<?= htmlspecialchars(setting('pagare_jurisdiction', 'Juzgado de Letras de lo Civil')) ?>"
                    placeholder="Juzgado de Letras de lo Civil">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold" style="font-size:.85rem">
                    Ciudad para Firma
                  </label>
                  <small class="text-muted d-block mb-2">Vacío = usa la ciudad de la empresa</small>
                  <input type="text" name="pagare_city" class="form-control form-control-sm"
                    value="<?= htmlspecialchars(setting('pagare_city', '')) ?>" placeholder="Ej: Tegucigalpa">
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- BOTONES DE ENVÍO -->
  <div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary btn-lg px-4">
      <i class="bi bi-check-lg me-2"></i>Guardar Configuración
    </button>
    <a href="<?= url('/dashboard') ?>" class="btn btn-outline-secondary btn-lg px-4">
      <i class="bi bi-x-lg me-1"></i>Cancelar
    </a>
  </div>
</form>

<script>
  document.getElementById('settingsForm')?.addEventListener('submit', function(e) {
    console.log('Configuración guardada');
  });
</script>

<style>
  .form-label {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
  }
</style>