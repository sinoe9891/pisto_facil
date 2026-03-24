<?php

/** @var array $invoices */
/** @var string $search */
/** @var string $from */
/** @var string $to */
/** @var string $status */
$currency = setting('app_currency', 'L');
$isAdmin  = \App\Core\Auth::isAdmin();
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="bi bi-receipt me-2 text-primary"></i>Facturas Emitidas
        </h5>
        <small class="text-muted"><?= count($invoices ?? []) ?> facturas en este período</small>
    </div>
    <a href="<?= url('/fiscal') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-file-earmark-check me-1"></i>Ver CAI
    </a>
</div>

<!-- FILTROS -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= url('/invoices') ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Número, cliente, préstamo..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-0">Desde</label>
                <input type="date" name="from" class="form-control form-control-sm"
                    value="<?= $from ?? date('Y-m-01') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-0">Hasta</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?? date('Y-m-d') ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos los estados</option>
                    <option value="active" <?= ($status ?? '') === 'active'  ? 'selected' : '' ?>>Válidas</option>
                    <option value="voided" <?= ($status ?? '') === 'voided'  ? 'selected' : '' ?>>Anuladas</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="<?= url('/invoices') ?>" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.87rem">
                <thead class="table-light">
                    <tr>
                        <th>No. Factura</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Préstamo</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices ?? [] as $inv): ?>
                        <tr class="<?= $inv['status'] === 'voided' ? 'table-secondary' : '' ?>">
                            <td>
                                <a href="<?= url('/invoices/' . $inv['id']) ?>" target="_blank"
                                    class="fw-semibold text-decoration-none font-monospace">
                                    <?= htmlspecialchars($inv['invoice_number']) ?>
                                </a>
                            </td>
                            <td><?= date('d/m/Y', strtotime($inv['invoice_date'])) ?></td>
                            <td>
                                <div><?= htmlspecialchars($inv['client_name']) ?></div>
                                <?php if ($inv['client_rtn']): ?>
                                    <small class="text-muted">RTN: <?= htmlspecialchars($inv['client_rtn']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= url('/loans/' . $inv['loan_id']) ?>" class="text-decoration-none small">
                                    <?= htmlspecialchars($inv['loan_number'] ?? '—') ?>
                                </a>
                                <?php if (!empty($inv['payment_number'])): ?>
                                    <div class="text-muted" style="font-size:.75rem">
                                        Pago: <?= htmlspecialchars($inv['payment_number']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td
                                class="text-end fw-semibold <?= $inv['status'] === 'voided' ? 'text-decoration-line-through text-muted' : '' ?>">
                                <?= $currency ?> <?= number_format($inv['total'], 2) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($inv['status'] === 'voided'): ?>
                                    <span class="badge bg-danger">Anulada</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Válida</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= url('/invoices/' . $inv['id']) ?>" target="_blank"
                                    class="btn btn-sm btn-outline-primary py-0 px-2" title="Ver factura">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($inv['payment_id']): ?>
                                    <a href="<?= url('/payments/' . $inv['payment_id']) ?>"
                                        class="btn btn-sm btn-outline-secondary py-0 px-2" title="Ver pago">
                                        <i class="bi bi-cash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-receipt d-block fs-2 mb-2"></i>
                                No hay facturas en este período.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>