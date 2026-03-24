<?php

/** @var array $payment */
/** @var array $cai */
/** @var float $capital */
/** @var float $interest */
/** @var float $lateFee */
/** @var float $saldoAnterior */
$currency = setting('app_currency', 'L');
$total    = round($capital + $interest + $lateFee, 2);
$newSaldo = round(($payment['balance'] ?? 0), 2);
?>

<div class="row justify-content-center">
    <div class="col-xl-9">

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/payments') ?>">Pagos</a></li>
                <li class="breadcrumb-item">
                    <a href="<?= url('/payments/' . $payment['id']) ?>">
                        <?= htmlspecialchars($payment['payment_number']) ?>
                    </a>
                </li>
                <li class="breadcrumb-item active">Emitir Factura</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div
                class="card-header bg-white fw-semibold py-2 border-bottom d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-receipt me-2 text-primary"></i>Emitir Factura Fiscal
                </span>
                <span class="badge bg-success font-monospace" style="font-size:.75rem">
                    CAI activo: <?= htmlspecialchars($cai['cai_code'] ?? '—') ?>
                </span>
            </div>
            <div class="card-body">

                <!-- Info del pago -->
                <div class="alert alert-light border mb-4 py-2" style="font-size:.85rem">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <span class="text-muted">Pago:</span>
                            <strong><?= htmlspecialchars($payment['payment_number']) ?></strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">Fecha:</span>
                            <strong><?= date('d/m/Y', strtotime($payment['payment_date'])) ?></strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">Préstamo:</span>
                            <strong><?= htmlspecialchars($payment['loan_number']) ?></strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">Monto cobrado:</span>
                            <strong class="text-success"><?= $currency ?>
                                <?= number_format($payment['total_received'], 2) ?></strong>
                        </div>
                    </div>
                </div>

                <form method="POST" action="<?= url('/invoices/store') ?>">
                    <?= \App\Core\CSRF::field() ?>
                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                    <input type="hidden" name="cai_id" value="<?= $cai['id'] ?>">

                    <div class="row g-3">

                        <!-- Fecha de la factura -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                Fecha de Factura <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="invoice_date" class="form-control" required
                                value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Datos del cliente -->
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">
                                Nombre Cliente en Factura <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="client_name" class="form-control" required maxlength="200"
                                value="<?= htmlspecialchars($payment['client_name'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">RTN del Cliente</label>
                            <input type="text" name="client_rtn" class="form-control font-monospace" maxlength="30"
                                placeholder="Opcional — dejar vacío = Consumidor Final"
                                value="<?= htmlspecialchars($payment['client_identity'] ?? '') ?>">
                            <div class="form-text">Si no tiene RTN, se emite como Consumidor Final.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="client_phone" class="form-control" maxlength="30"
                                value="<?= htmlspecialchars($payment['client_phone'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="client_email" class="form-control" maxlength="180"
                                value="<?= htmlspecialchars($payment['client_email'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Dirección del Cliente</label>
                            <input type="text" name="client_address" class="form-control" maxlength="500"
                                value="<?= htmlspecialchars($payment['client_address'] ?? '') ?>">
                        </div>

                        <!-- DESGLOSE FINANCIERO -->
                        <div class="col-12 mt-2">
                            <div class="fw-semibold border-top pt-3 mb-2"
                                style="font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b">
                                <i class="bi bi-table me-1"></i>Desglose de la Factura
                                <small class="text-success ms-2">(Intereses = Exentos de ISV según ley HN)</small>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Saldo Anterior</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="saldo_anterior" class="form-control" step="0.01" min="0"
                                    value="<?= number_format($saldoAnterior, 2, '.', '') ?>">
                            </div>
                            <div class="form-text text-success">Exento</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Interés Corriente</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="interes_corriente" class="form-control" step="0.01" min="0"
                                    value="<?= number_format($interest, 2, '.', '') ?>">
                            </div>
                            <div class="form-text text-success">Exento</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Interés Moratorio</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="interes_moratorio" class="form-control" step="0.01" min="0"
                                    value="<?= number_format($lateFee, 2, '.', '') ?>">
                            </div>
                            <div class="form-text text-success">Exento</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Otros Cargos</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="otros_cargos" class="form-control" step="0.01" min="0"
                                    value="0.00">
                            </div>
                            <div class="form-text text-warning">Gravado 15%</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Abono a Capital</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="abono_capital" class="form-control" step="0.01" min="0"
                                    value="<?= number_format($capital, 2, '.', '') ?>">
                            </div>
                            <div class="form-text text-success">Exento</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nuevo Saldo</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="nuevo_saldo" class="form-control" step="0.01" min="0"
                                    value="<?= number_format($newSaldo, 2, '.', '') ?>">
                            </div>
                        </div>

                        <!-- Resumen -->
                        <div class="col-md-4">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body py-2" style="font-size:.82rem">
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted">Total cobrado</span>
                                        <strong><?= $currency ?>
                                            <?= number_format($payment['total_received'], 2) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between py-1 border-top">
                                        <span class="text-muted">ISV (intereses exentos)</span>
                                        <span class="text-success">L 0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1 border-top fw-bold">
                                        <span>Gran Total</span>
                                        <span class="text-primary" id="grandTotalPreview">
                                            <?= $currency ?> <?= number_format($payment['total_received'], 2) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notas de la factura</label>
                            <input type="text" name="notes" class="form-control" maxlength="500"
                                placeholder="Opcional...">
                        </div>

                        <!-- CAI INFO -->
                        <div class="col-12">
                            <div class="alert alert-info py-2" style="font-size:.82rem">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>CAI:</strong> <?= htmlspecialchars($cai['cai_code']) ?>
                                &nbsp;|&nbsp;
                                <strong>Vence:</strong> <?= date('d/m/Y', strtotime($cai['limit_date'])) ?>
                                &nbsp;|&nbsp;
                                <strong>Correlativo siguiente:</strong>
                                <?php
                                $prefix  = substr($cai['range_from'], 0, -8);
                                $nextNum = str_pad((string)($cai['current_counter'] + 1), 8, '0', STR_PAD_LEFT);
                                ?>
                                <strong class="font-monospace"><?= $prefix . $nextNum ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-primary btn-lg px-5" onclick="confirmEmitInvoice()">
                            <i class="bi bi-receipt me-2"></i>Emitir Factura
                        </button>
                        <a href="<?= url('/payments/' . $payment['id']) ?>"
                            class="btn btn-outline-secondary">Cancelar</a>
                    </div>

                    <button type="submit" id="submitInvoiceBtn" class="d-none"></button>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
    function confirmEmitInvoice() {
        const clientName = document.querySelector('[name="client_name"]').value;
        const date = document.querySelector('[name="invoice_date"]').value;
        if (!clientName) {
            Swal.fire('Nombre requerido', 'Ingresá el nombre del cliente.', 'warning');
            return;
        }
        const [y, m, d] = date.split('-');
        Swal.fire({
            title: '¿Emitir factura?',
            html: `<p>Se emitirá una factura fiscal para:</p>
           <p><strong><?= htmlspecialchars($payment['client_name']) ?></strong></p>
           <p>Pago: <strong><?= htmlspecialchars($payment['payment_number']) ?></strong></p>
           <p class="text-danger fw-semibold mb-0">⚠ Esta acción consumirá un correlativo del CAI y no se puede deshacer fácilmente.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, emitir',
            cancelButtonText: 'Cancelar',
        }).then(r => {
            if (r.isConfirmed) document.getElementById('submitInvoiceBtn').click();
        });
    }
</script>