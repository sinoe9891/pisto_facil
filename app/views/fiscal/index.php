<?php
// app/views/fiscal/index.php
$isAdmin = \App\Core\Auth::isAdmin();
$today   = date('Y-m-d');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="bi bi-file-earmark-check me-2 text-primary"></i>Información Fiscal — CAI
        </h5>
        <small class="text-muted">Códigos de Autorización de Impresión del SAR Honduras</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/invoices') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-receipt me-1"></i>Ver Facturas
        </a>
        <a href="<?= url('/fiscal/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus me-1"></i>Registrar CAI
        </a>
    </div>
</div>

<!-- ALERTA SI NO HAY CAI ACTIVO -->
<?php
$activeCai = array_filter($cais, fn($c) => $c['is_active'] && $c['limit_date'] >= $today && $c['used_count'] < ($c['total_range'] ?? PHP_INT_MAX));
if (empty($activeCai)):
?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
        <div>
            <strong>¡Sin CAI activo y vigente!</strong>
            No se pueden emitir facturas hasta registrar un CAI válido del SAR.
            <a href="<?= url('/fiscal/create') ?>" class="alert-link">Registrar ahora →</a>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($cais)): ?>
    <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-file-earmark-x fs-1 d-block mb-3"></i>
            <h6>No hay CAI registrados</h6>
            <p class="mb-3">Registre el CAI emitido por el SAR para comenzar a facturar.</p>
            <a href="<?= url('/fiscal/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus me-1"></i>Registrar primer CAI
            </a>
        </div>
    </div>
<?php else: ?>

    <div class="row g-3">
        <?php foreach ($cais as $cai):
            $isExpired   = $cai['limit_date'] < $today;
            $isExhausted = $cai['pct_used'] >= 100;
            $isSoonExp   = !$isExpired && $cai['days_left'] <= 30;
            $cardClass   = !$cai['is_active'] ? 'border-secondary' : ($isExpired || $isExhausted ? 'border-danger' : ($isSoonExp ? 'border-warning' : 'border-success'));
            $headerClass = !$cai['is_active'] ? 'bg-secondary text-white' : ($isExpired || $isExhausted ? 'bg-danger text-white' : ($isSoonExp ? 'bg-warning text-dark' : 'bg-success text-white'));
        ?>
            <div class="col-lg-6">
                <div class="card shadow-sm border-2 <?= $cardClass ?>">
                    <div class="card-header py-2 <?= $headerClass ?> d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-upc-scan me-2"></i>
                            <strong><?= $cai['emission_type'] ?></strong>
                            <?php if (!$cai['is_active']): ?>
                                <span class="badge bg-white text-secondary ms-2">Inactivo</span>
                            <?php elseif ($isExpired): ?>
                                <span class="badge bg-white text-danger ms-2">Vencido</span>
                            <?php elseif ($isExhausted): ?>
                                <span class="badge bg-white text-danger ms-2">Agotado</span>
                            <?php elseif ($isSoonExp): ?>
                                <span class="badge bg-dark ms-2">Vence en <?= $cai['days_left'] ?> días</span>
                            <?php else: ?>
                                <span class="badge bg-white text-success ms-2">Activo</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="<?= url('/fiscal/' . $cai['id'] . '/edit') ?>" class="btn btn-sm btn-light py-0 px-2"
                                title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="#" onclick="confirmAction('<?= url('/fiscal/' . $cai['id'] . '/toggle') ?>',
            '<?= $cai['is_active'] ? '¿Desactivar CAI?' : '¿Activar CAI?' ?>',
            '<?= $cai['is_active'] ? 'Se desactivará para emitir facturas.' : 'Se activará para emitir facturas.' ?>',
            '<?= $cai['is_active'] ? 'warning' : 'info' ?>')" class="btn btn-sm btn-light py-0 px-2"
                                title="<?= $cai['is_active'] ? 'Desactivar' : 'Activar' ?>">
                                <i class="bi bi-<?= $cai['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body py-3" style="font-size:.85rem">
                        <!-- CAI Code -->
                        <div class="bg-light rounded p-2 mb-3 text-center">
                            <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em">CAI
                            </div>
                            <div class="fw-bold" style="font-family:monospace;font-size:.95rem;letter-spacing:.05em">
                                <?= htmlspecialchars($cai['cai_code']) ?>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="text-muted" style="font-size:.7rem">Rango Desde</div>
                                <code style="font-size:.8rem"><?= htmlspecialchars($cai['range_from']) ?></code>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size:.7rem">Rango Hasta</div>
                                <code style="font-size:.8rem"><?= htmlspecialchars($cai['range_to']) ?></code>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size:.7rem">Fecha Límite Emisión</div>
                                <strong class="<?= $isExpired ? 'text-danger' : ($isSoonExp ? 'text-warning' : '') ?>">
                                    <?= date('d/m/Y', strtotime($cai['limit_date'])) ?>
                                </strong>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size:.7rem">Último correlativo</div>
                                <strong><?= number_format($cai['current_counter']) ?> /
                                    <?= number_format($cai['total_range'] ?? 0) ?></strong>
                            </div>
                        </div>

                        <!-- Barra de uso -->
                        <div class="mt-2">
                            <div class="d-flex justify-content-between mb-1" style="font-size:.72rem">
                                <span class="text-muted">Uso del rango</span>
                                <span class="fw-semibold"><?= $cai['pct_used'] ?>%</span>
                            </div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar <?= $cai['pct_used'] >= 90 ? 'bg-danger' : ($cai['pct_used'] >= 70 ? 'bg-warning' : 'bg-success') ?>"
                                    style="width:<?= $cai['pct_used'] ?>%"></div>
                            </div>
                        </div>

                        <?php if ($cai['bmt_rtn'] || $cai['cert_number']): ?>
                            <hr class="my-2">
                            <div class="row g-1" style="font-size:.78rem">
                                <?php if ($cai['bmt_rtn']): ?>
                                    <div class="col-6">
                                        <span class="text-muted">RTN:</span>
                                        <strong><?= htmlspecialchars($cai['bmt_rtn']) ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?php if ($cai['cert_number']): ?>
                                    <div class="col-6">
                                        <span class="text-muted">No. Certificado:</span>
                                        <strong><?= htmlspecialchars($cai['cert_number']) ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <small class="text-muted">
                                <?= $cai['invoices_count'] ?> factura<?= $cai['invoices_count'] != 1 ? 's' : '' ?>
                                emitida<?= $cai['invoices_count'] != 1 ? 's' : '' ?>
                            </small>
                            <a href="<?= url('/invoices?cai_id=' . $cai['id']) ?>" class="btn btn-sm btn-outline-primary py-0">
                                Ver facturas <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>