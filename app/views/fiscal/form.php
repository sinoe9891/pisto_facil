<?php
// app/views/fiscal/form.php
$isEdit = !empty($cai);
$action = $isEdit
    ? url('/fiscal/' . $cai['id'] . '/update')
    : url('/fiscal/store');
?>

<div class="row justify-content-center">
    <div class="col-xl-8">

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/fiscal') ?>">Información Fiscal</a></li>
                <li class="breadcrumb-item active"><?= $isEdit ? 'Editar CAI' : 'Registrar CAI' ?></li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold py-2 border-bottom">
                <i class="bi bi-upc-scan me-2 text-primary"></i>
                <?= $isEdit ? 'Editar CAI' : 'Registrar nuevo CAI' ?>
            </div>
            <div class="card-body">

                <?php if (!$isEdit): ?>
                    <div class="alert alert-info d-flex gap-2 py-2 mb-3" style="font-size:.85rem">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            Ingresá los datos tal como aparecen en el documento
                            <strong>SAR-924 (Solicitud de Autorización de Impresión)</strong>
                            o en la factura física emitida por la imprenta.
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= $action ?>">
                    <?= \App\Core\CSRF::field() ?>

                    <?php if ($isEdit): ?>
                        <!-- Verificación de contraseña para editar -->
                        <div class="alert alert-warning py-2 mb-3" style="font-size:.85rem">
                            <i class="bi bi-lock me-1"></i>
                            Para modificar datos fiscales, confirmá tu contraseña.
                        </div>
                        <div class="row g-3 mb-3 pb-3 border-bottom">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Contraseña de confirmación <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="confirm_password" required class="form-control"
                                    placeholder="Tu contraseña actual">
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- DATOS DEL CAI -->
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                CAI <span class="text-danger">*</span>
                                <small class="text-muted fw-normal ms-1">Ej:
                                    347A96-CC1ADA-F962E0-63BE03-0909EB-40</small>
                            </label>
                            <input type="text" name="cai_code" required
                                class="form-control font-monospace text-uppercase" maxlength="50"
                                style="letter-spacing:.05em" placeholder="XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX-XX"
                                value="<?= htmlspecialchars($cai['cai_code'] ?? '') ?>">
                            <div class="form-text">Copiar exactamente como aparece en el SAR-924.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo de Documento</label>
                            <select name="emission_type" class="form-select">
                                <?php foreach (['Factura', 'Factura prevalorada', 'Nota de crédito', 'Nota de débito', 'Otro'] as $t): ?>
                                    <option value="<?= $t ?>"
                                        <?= ($cai['emission_type'] ?? 'Factura') === $t ? 'selected' : '' ?>>
                                        <?= $t ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                Fecha Límite de Emisión <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="limit_date" required class="form-control"
                                value="<?= htmlspecialchars($cai['limit_date'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Establecimiento</label>
                            <input type="text" name="establishment" class="form-control" maxlength="150"
                                placeholder="Nombre o dirección del local"
                                value="<?= htmlspecialchars($cai['establishment'] ?? '') ?>">
                        </div>

                        <!-- RANGO AUTORIZADO -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Rango Autorizado — Desde <span class="text-danger">*</span>
                                <small class="text-muted fw-normal">000-001-01-00001351</small>
                            </label>
                            <input type="text" name="range_from" required class="form-control font-monospace"
                                maxlength="30" placeholder="000-001-01-00000001" pattern="\d{3}-\d{3}-\d{2}-\d{8}"
                                title="Formato: 000-001-01-00000001"
                                value="<?= htmlspecialchars($cai['range_from'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Rango Autorizado — Hasta <span class="text-danger">*</span>
                                <small class="text-muted fw-normal">000-001-01-00001650</small>
                            </label>
                            <input type="text" name="range_to" required class="form-control font-monospace"
                                maxlength="30" placeholder="000-001-01-00000500" pattern="\d{3}-\d{3}-\d{2}-\d{8}"
                                title="Formato: 000-001-01-00000500"
                                value="<?= htmlspecialchars($cai['range_to'] ?? '') ?>">
                        </div>

                        <!-- DATOS DE LA IMPRENTA -->
                        <div class="col-12">
                            <div class="fw-semibold text-muted mb-2 mt-2 border-top pt-3"
                                style="font-size:.78rem;text-transform:uppercase;letter-spacing:.05em">
                                <i class="bi bi-printer me-1"></i>Datos de la Imprenta (BMT)
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">RTN Imprenta (BMT RTN)</label>
                            <input type="text" name="bmt_rtn" class="form-control font-monospace" maxlength="20"
                                placeholder="04019004010909" value="<?= htmlspecialchars($cai['bmt_rtn'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre Imprenta</label>
                            <input type="text" name="bmt_name" class="form-control" maxlength="200"
                                placeholder="Nombre de la imprenta"
                                value="<?= htmlspecialchars($cai['bmt_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No. Certificado</label>
                            <input type="text" name="cert_number" class="form-control font-monospace" maxlength="50"
                                placeholder="9231-23-10500-105"
                                value="<?= htmlspecialchars($cai['cert_number'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notas internas</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="500"
                                placeholder="Observaciones..."><?= htmlspecialchars($cai['notes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- PREVIEW RANGO -->
                    <div id="rangePreview" class="alert alert-light border mt-3 d-none" style="font-size:.85rem">
                        <i class="bi bi-info-circle me-1 text-info"></i>
                        Rango: <strong id="rangeTotal">—</strong> facturas disponibles
                        (de <code id="rangeFromPrev">—</code> a <code id="rangeToPrev">—</code>)
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>
                            <?= $isEdit ? 'Guardar Cambios' : 'Registrar CAI' ?>
                        </button>
                        <a href="<?= url('/fiscal') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function calcRange() {
        const from = document.querySelector('[name="range_from"]').value;
        const to = document.querySelector('[name="range_to"]').value;
        const prev = document.getElementById('rangePreview');

        if (from && to && /^\d{3}-\d{3}-\d{2}-\d{8}$/.test(from) && /^\d{3}-\d{3}-\d{2}-\d{8}$/.test(to)) {
            const fromN = parseInt(from.replace(/-/g, '').slice(-8));
            const toN = parseInt(to.replace(/-/g, '').slice(-8));
            const total = toN - fromN + 1;
            if (total > 0) {
                document.getElementById('rangeTotal').textContent = total.toLocaleString('es-HN');
                document.getElementById('rangeFromPrev').textContent = from;
                document.getElementById('rangeToPrev').textContent = to;
                prev.classList.remove('d-none');
                return;
            }
        }
        prev.classList.add('d-none');
    }

    document.querySelector('[name="range_from"]').addEventListener('input', calcRange);
    document.querySelector('[name="range_to"]').addEventListener('input', calcRange);
    calcRange();
</script>