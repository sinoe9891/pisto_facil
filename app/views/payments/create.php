<?php $currency = setting('app_currency','L'); ?>

<div class="row justify-content-center">
<div class="col-xl-9">

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= url('/payments') ?>">Pagos</a></li>
    <li class="breadcrumb-item active">Registrar Pago</li>
  </ol>
</nav>

<div class="row g-3">
  <!-- FORM -->
  <div class="col-md-7">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white fw-semibold py-2 border-bottom">
        <i class="bi bi-cash me-2 text-success"></i>Registrar Pago
      </div>
      <div class="card-body">
        <form method="POST" action="<?= url('/payments/store') ?>" id="paymentForm">
          <?= \App\Core\CSRF::field() ?>

          <?php if (!$loan): ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Préstamo <span class="text-danger">*</span></label>
            <select name="loan_id" id="loan_id" class="form-select" required
                    onchange="if(this.value) window.location='/payments/create?loan_id='+this.value">
              <option value="">-- Seleccionar préstamo activo --</option>
              <?php foreach ($activeLoans as $l): ?>
              <option value="<?= $l['id'] ?>">
                <?= htmlspecialchars($l['loan_number'] . ' · ' . $l['client_name']) ?>
                – Saldo: <?= $currency ?> <?= number_format($l['balance'],2) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php else: ?>
          <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
          <div class="mb-3 p-3 bg-light rounded d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?= htmlspecialchars($loan['loan_number']) ?>
                <span class="badge bg-info text-dark ms-1">Tipo <?= $loan['loan_type'] ?></span>
              </div>
              <div class="text-muted small"><?= htmlspecialchars($loan['client_name']) ?></div>
            </div>
            <div class="text-end">
              <div class="text-danger fw-bold"><?= $currency ?> <?= number_format($loan['balance'],2) ?></div>
              <div class="text-muted small">saldo actual</div>
            </div>
            <a href="<?= url('/payments/create') ?>" class="btn btn-sm btn-outline-secondary">Cambiar</a>
          </div>
          <?php endif; ?>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Monto Recibido (<?= $currency ?>) <span class="text-danger">*</span></label>
              <?php
              // Pre-rellenar con el monto correcto según tipo y estado
              $preAmount = 0;
              if ($loan && $nextInstallment) {
                if ($loan['loan_type'] === 'C' && !empty($currentState['total_due'])) {
                  // Tipo C: usar el interés acumulado real + mora
                  $preAmount = number_format($currentState['total_due'], 2, '.', '');
                } else {
                  $preAmount = number_format(
                    $nextInstallment['total_amount'] - $nextInstallment['paid_amount'],
                    2, '.', ''
                  );
                }
              }
              ?>
              <input type="number" name="amount" id="amountInput" class="form-control form-control-lg"
                     min="0.01" step="0.01" required placeholder="0.00"
                     value="<?= $preAmount ?: '' ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Fecha de Pago</label>
              <input type="date" name="payment_date" class="form-control form-control-lg"
                     value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Método de Pago</label>
              <select name="payment_method" class="form-select">
                <option value="cash">💵 Efectivo</option>
                <option value="transfer">🏦 Transferencia</option>
                <option value="check">📝 Cheque</option>
                <option value="other">Otro</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Número de Recibo</label>
              <input type="text" name="receipt_number" class="form-control" maxlength="50" placeholder="Opcional">
            </div>
            <div class="col-12">
              <label class="form-label">Notas</label>
              <textarea name="notes" class="form-control" rows="2" maxlength="500"
                        placeholder="Observaciones del pago..."></textarea>
            </div>
          </div>

          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
              <i class="bi bi-check-circle me-2"></i>Confirmar y Registrar Pago
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- SIDEBAR -->
  <div class="col-md-5">

    <!-- Estado actual del préstamo -->
    <?php if ($loan && !empty($currentState)): ?>
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-header bg-warning text-dark fw-semibold py-2">
        <i class="bi bi-calculator me-2"></i>Estado Actual del Préstamo
      </div>
      <div class="card-body py-2" style="font-size:.85rem">

        <?php if ($loan['loan_type'] === 'C'): ?>
        <!-- Tipo C: mostrar desglose de interés acumulado -->
        <?php
          $periodInt  = $currentState['period_interest']      ?? 0;
          $accumInt   = $currentState['accumulated_interest']  ?? $periodInt;
          $paidInt    = $currentState['paid_interest']         ?? 0;
          $pendingInt = $currentState['pending_interest']      ?? $accumInt;
          $lateFee    = $currentState['late_fee']              ?? 0;
          $totalDue   = $currentState['total_due']             ?? 0;
          $daysLate   = $currentState['days_late']             ?? 0;
          $periods    = $currentState['periods_elapsed']       ?? 1;
        ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Saldo capital</span>
          <strong><?= $currency ?> <?= number_format($currentState['balance'],2) ?></strong>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Interés por período</span>
          <span><?= $currency ?> <?= number_format($periodInt,2) ?></span>
        </div>
        <?php if ($periods > 1): ?>
        <div class="d-flex justify-content-between py-1 border-bottom bg-warning bg-opacity-10">
          <span class="text-warning fw-semibold">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Interés acumulado (<?= $periods ?> períodos)
          </span>
          <strong class="text-warning"><?= $currency ?> <?= number_format($accumInt,2) ?></strong>
        </div>
        <?php endif; ?>
        <?php if ($paidInt > 0): ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Ya pagado (interés)</span>
          <span class="text-success">– <?= $currency ?> <?= number_format($paidInt,2) ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Interés pendiente</span>
          <strong class="text-danger"><?= $currency ?> <?= number_format($pendingInt,2) ?></strong>
        </div>
        <?php if ($lateFee > 0): ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Mora</span>
          <strong class="text-danger"><?= $currency ?> <?= number_format($lateFee,2) ?></strong>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between py-1 border-bottom bg-danger bg-opacity-10">
          <span class="fw-bold">Total a pagar (sin capital)</span>
          <strong class="text-danger fs-6"><?= $currency ?> <?= number_format($totalDue,2) ?></strong>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Total con capital</span>
          <strong><?= $currency ?> <?= number_format($currentState['total_due_with_cap'] ?? ($currentState['balance'] + $totalDue),2) ?></strong>
        </div>
        <?php if ($daysLate > 0): ?>
        <div class="alert alert-danger py-1 mt-2 mb-0" style="font-size:.78rem">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <strong><?= $daysLate ?> días vencido</strong> · <?= $periods ?> período<?= $periods > 1 ? 's' : '' ?> sin pagar
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Tipo A y B: mostrar estado genérico -->
        <?php foreach ($currentState as $k => $v): if (!is_numeric($v)) continue; ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted"><?= ucfirst(str_replace('_',' ',$k)) ?></span>
          <strong class="<?= str_contains($k,'late')||str_contains($k,'fee')?'text-danger':'' ?>">
            <?= (str_contains($k,'fee')||str_contains($k,'interest')||str_contains($k,'balance')||str_contains($k,'due')||str_contains($k,'total'))
                ? $currency.' '.number_format($v,2) : $v ?>
          </strong>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Próxima cuota (solo referencia para DB) -->
    <?php if ($nextInstallment && $loan['loan_type'] !== 'C'): ?>
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-header bg-info text-white fw-semibold py-2">
        <i class="bi bi-calendar-check me-2"></i>Próxima Cuota
      </div>
      <div class="card-body py-2" style="font-size:.85rem">
        <?php
        $today = date('Y-m-d');
        $isOD  = $nextInstallment['due_date'] < $today;
        $pend  = round($nextInstallment['total_amount'] - $nextInstallment['paid_amount'], 2);
        ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Vencimiento</span>
          <strong class="<?= $isOD?'text-danger':'' ?>"><?= date('d/m/Y',strtotime($nextInstallment['due_date'])) ?></strong>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Capital</span>
          <span><?= $currency ?> <?= number_format($nextInstallment['principal_amount'],2) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Interés</span>
          <span><?= $currency ?> <?= number_format($nextInstallment['interest_amount'],2) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span class="fw-semibold">Pendiente</span>
          <strong class="text-danger fs-6"><?= $currency ?> <?= number_format($pend,2) ?></strong>
        </div>
        <?php if ($isOD): ?>
        <div class="alert alert-danger py-1 mt-2 mb-0" style="font-size:.78rem">
          <i class="bi bi-exclamation-triangle me-1"></i>
          Vencida hace <?= (new \DateTime($nextInstallment['due_date']))->diff(new \DateTime())->days ?> días. Aplica mora.
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Atajos rápidos -->
    <?php if ($loan): ?>
    <div class="card shadow-sm border-0">
      <div class="card-body p-2">
        <div class="text-muted small mb-2 fw-semibold">Atajos rápidos:</div>
        <div class="d-grid gap-1">

          <?php if ($loan['loan_type'] === 'C' && !empty($currentState)): ?>
            <?php
            $pendingInt = $currentState['pending_interest'] ?? 0;
            $lateFee    = $currentState['late_fee']         ?? 0;
            $balance    = $currentState['balance']          ?? $loan['balance'];
            $onlyInt    = round($pendingInt + $lateFee, 2);
            $withCap    = round($balance + $pendingInt + $lateFee, 2);
            $periodInt  = $currentState['period_interest']  ?? 0;
            ?>
            <?php if ($onlyInt > 0): ?>
            <button type="button" class="btn btn-outline-warning btn-sm" onclick="setAmount(<?= $onlyInt ?>)">
              <i class="bi bi-cash me-1"></i>
              Solo interés<?= $lateFee > 0 ? '+mora' : '' ?>: <?= $currency ?> <?= number_format($onlyInt,2) ?>
              <?php if (($currentState['periods_elapsed'] ?? 1) > 1): ?>
              <span class="badge bg-warning text-dark ms-1"><?= $currentState['periods_elapsed'] ?> per.</span>
              <?php endif; ?>
            </button>
            <?php endif; ?>
            <?php if ($periodInt > 0 && $periodInt !== $onlyInt): ?>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(<?= round($periodInt,2) ?>)">
              Solo 1 período: <?= $currency ?> <?= number_format($periodInt,2) ?>
            </button>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="setAmount(<?= $withCap ?>)">
              <i class="bi bi-check-all me-1"></i>Cancelar todo: <?= $currency ?> <?= number_format($withCap,2) ?>
            </button>

          <?php elseif ($nextInstallment): ?>
            <?php $pend = round($nextInstallment['total_amount']-$nextInstallment['paid_amount'],2); ?>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="setAmount(<?= $pend ?>)">
              Cuota completa: <?= $currency ?> <?= number_format($pend,2) ?>
            </button>
            <?php if ($loan['balance'] > 0): ?>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="setAmount(<?= round($loan['balance']+$pend,2) ?>)">
              Cancelar todo: <?= $currency ?> <?= number_format($loan['balance']+$pend,2) ?>
            </button>
            <?php endif; ?>
          <?php endif; ?>

        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div><!-- /row -->
</div><!-- /col -->
</div><!-- /row -->

<script>
function setAmount(val) {
  document.getElementById('amountInput').value = parseFloat(val).toFixed(2);
}
document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
});
</script>