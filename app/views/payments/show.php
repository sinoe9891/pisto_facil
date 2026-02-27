<?php $currency = setting('app_currency','L'); ?>

<div class="row justify-content-center">
<div class="col-xl-8">

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= url('/payments') ?>">Pagos</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($payment['payment_number']) ?></li>
    </ol>
  </nav>
  <div class="btn-group btn-group-sm no-print">
    <button onclick="window.print()" class="btn btn-outline-secondary">
      <i class="bi bi-printer me-1"></i>Imprimir
    </button>
    <?php if (!$payment['voided'] && \App\Core\Auth::isAdmin()): ?>
    <a href="#" onclick="confirmAction('<?= url('/payments/'.$payment['id'].'/void') ?>','¿Anular pago?','Esta acción revertirá los cambios al préstamo.','warning')"
       class="btn btn-outline-danger">
      <i class="bi bi-x-circle me-1"></i>Anular
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <!-- RECEIPT HEADER -->
    <div class="text-center border-bottom pb-3 mb-3">
      <h5 class="fw-bold text-primary mb-0"><?= setting('app_name','SistemaPréstamos') ?></h5>
      <div class="text-muted small">Comprobante de Pago</div>
      <?php if ($payment['voided']): ?>
      <div class="badge bg-danger fs-6 mt-2">⚠ ANULADO</div>
      <?php else: ?>
      <div class="badge bg-success mt-2">✓ VÁLIDO</div>
      <?php endif; ?>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-6">
        <div class="text-muted small">Número de Pago</div>
        <div class="fw-bold fs-5"><?= htmlspecialchars($payment['payment_number']) ?></div>
      </div>
      <div class="col-6 text-end">
        <div class="text-muted small">Fecha</div>
        <div class="fw-bold"><?= date('d/m/Y',strtotime($payment['payment_date'])) ?></div>
      </div>
      <div class="col-6">
        <div class="text-muted small">Cliente</div>
        <div class="fw-semibold"><?= htmlspecialchars($payment['client_name']) ?></div>
      </div>
      <div class="col-6">
        <div class="text-muted small">Préstamo</div>
        <div><a href="<?= url('/loans/'.$payment['loan_id']) ?>"><?= htmlspecialchars($payment['loan_number']) ?></a></div>
      </div>
      <div class="col-6">
        <div class="text-muted small">Método</div>
        <div><?= htmlspecialchars($payment['payment_method']) ?></div>
      </div>
      <div class="col-6">
        <div class="text-muted small">Recibo #</div>
        <div><?= htmlspecialchars($payment['receipt_number'] ?? '–') ?></div>
      </div>
    </div>

    <!-- BREAKDOWN TABLE -->
    <div class="table-responsive mb-3">
      <table class="table table-bordered" style="font-size:.85rem">
        <thead class="table-dark">
          <tr>
            <th>Concepto</th><th>Cuota #</th><th>Vence</th><th class="text-end">Monto</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $typeLabels = ['capital'=>'Abono Capital','interest'=>'Interés Corriente',
                       'late_fee'=>'Interés Moratorio','other'=>'Otros'];
        ?>
        <?php foreach ($items as $item): ?>
        <tr>
          <td>
            <span class="badge <?= match($item['item_type']){'capital'=>'bg-primary','interest'=>'bg-info text-dark','late_fee'=>'bg-danger',default=>'bg-secondary'} ?>">
              <?= $typeLabels[$item['item_type']] ?? $item['item_type'] ?>
            </span>
          </td>
          <td class="text-center"><?= $item['installment_number'] ?? '–' ?></td>
          <td><?= $item['due_date'] ? date('d/m/Y',strtotime($item['due_date'])) : '–' ?></td>
          <td class="text-end fw-semibold"><?= $currency ?> <?= number_format($item['amount'],2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="fw-bold table-success">
            <td colspan="3">TOTAL RECIBIDO</td>
            <td class="text-end fs-6"><?= $currency ?> <?= number_format($payment['total_received'],2) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <?php if ($payment['notes']): ?>
    <div class="alert alert-light border py-2 mb-3">
      <small class="text-muted">Notas:</small><br>
      <?= nl2br(htmlspecialchars($payment['notes'])) ?>
    </div>
    <?php endif; ?>

    <?php if ($payment['voided']): ?>
    <div class="alert alert-danger py-2 mb-3">
      <strong>Anulado por:</strong> <?= htmlspecialchars($payment['voided_by_name'] ?? '–') ?><br>
      <strong>Fecha:</strong> <?= date('d/m/Y H:i',strtotime($payment['voided_at'])) ?><br>
      <strong>Motivo:</strong> <?= htmlspecialchars($payment['void_reason'] ?? '–') ?>
    </div>
    <?php endif; ?>

    <div class="text-center border-top pt-3 text-muted" style="font-size:.75rem">
      Registrado por: <?= htmlspecialchars($payment['registered_by_name']) ?> ·
      <?= date('d/m/Y H:i',strtotime($payment['created_at'])) ?>
    </div>
  </div>
</div>

</div><!-- /col -->
</div><!-- /row -->

<style>
@media print {
  .no-print { display:none!important; }
  #sidebar, #topbar { display:none!important; }
  #main { margin-left:0!important; padding-top:0!important; }
}
</style>
