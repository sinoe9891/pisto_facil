<?php

/**
 * VISTA: views/payments/create.php
 * Registrar Pago — Mejorado para Tipo A, B y C
 */

$currency = setting('app_currency', 'L');

// ─── CALCULAR VARIABLES UNIFICADAS PARA LOS 3 TIPOS ──────────────────
$loanType  = $loan['loan_type']  ?? '';
$balance   = (float)($loan['balance'] ?? 0);
$mora      = round((float)($currentState['late_fee'] ?? 0), 2);
$daysLate  = (int)($currentState['days_late'] ?? 0);
$graceDays = (int)($loan['grace_days'] ?? 0);

// Tipo A y B — basado en cuota pendiente
$pendCuota     = 0;
$pendCapita    = 0;
$pendInteres   = 0;
if ($nextInstallment && $loanType !== 'C') {
  $pendCuota   = round($nextInstallment['total_amount']    - $nextInstallment['paid_amount'],    2);
  $pendCapita  = round($nextInstallment['principal_amount'] - $nextInstallment['paid_principal'], 2);
  $pendInteres = round($nextInstallment['interest_amount']  - $nextInstallment['paid_interest'],  2);
}

// Tipo B — interés por días
$accruedInterest = round((float)($currentState['accrued_interest'] ?? 0), 2);
$daysElapsed     = (int)($currentState['days_elapsed'] ?? 0);

// Tipo C — basado en estado acumulado
$periodInt   = round((float)($currentState['period_interest']      ?? 0), 2);
$accumInt    = round((float)($currentState['accumulated_interest'] ?? $periodInt), 2);
$paidInt     = round((float)($currentState['paid_interest']        ?? 0), 2);
$pendingInt  = round((float)($currentState['pending_interest']     ?? $accumInt), 2);
$periods     = (int)($currentState['periods_elapsed'] ?? 1);
$totalDue    = round((float)($currentState['total_due']            ?? 0), 2);
$totalWithCap = round((float)($currentState['total_due_with_cap']   ?? ($balance + $totalDue)), 2);

// ─── MONTO PRE-RELLENO CORRECTO SEGÚN TIPO ───────────────────────────
$preAmount = 0;
if ($loan) {
  if ($loanType === 'C') {
    // Tipo C: interés pendiente + mora (SIN capital)
    $preAmount = round($pendingInt + $mora, 2);
  } elseif ($loanType === 'B') {
    // Tipo B: interés acumulado por días + mora + (capital si hay)
    $preAmount = round($accruedInterest + $mora, 2);
  } elseif ($nextInstallment) {
    // Tipo A: cuota pendiente + mora
    $preAmount = round($pendCuota + $mora, 2);
  }
}
?>

<div class="row justify-content-center">
  <div class="col-xl-9">

    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= url('/payments') ?>">Pagos</a></li>
        <li class="breadcrumb-item active">Registrar Pago</li>
      </ol>
    </nav>

    <div class="row g-3">

      <!-- ═══ FORMULARIO ═══════════════════════════════════════════════ -->
      <div class="col-md-7">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white fw-semibold py-2 border-bottom">
            <i class="bi bi-cash me-2 text-success"></i>Registrar Pago
          </div>
          <div class="card-body">
            <form method="POST" action="<?= url('/payments/store') ?>" id="paymentForm">
              <?= \App\Core\CSRF::field() ?>

              <?php if (!$loan): ?>
                <!-- Sin préstamo seleccionado -->
                <div class="mb-3">
                  <label class="form-label fw-semibold">Préstamo <span
                      class="text-danger">*</span></label>
                  <select name="loan_id" id="loan_id" class="form-select" required
                    onchange="if(this.value) window.location='<?= url('/payments/create') ?>?loan_id='+this.value">
                    <option value="">-- Seleccionar préstamo activo --</option>
                    <?php foreach ($activeLoans as $l): ?>
                      <option value="<?= $l['id'] ?>">
                        <?= htmlspecialchars($l['loan_number'] . ' · ' . $l['client_name']) ?>
                        – Saldo: <?= $currency ?> <?= number_format($l['balance'], 2) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

              <?php else: ?>
                <!-- Préstamo seleccionado -->
                <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                <div class="mb-3 p-3 bg-light rounded d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-semibold">
                      <?= htmlspecialchars($loan['loan_number']) ?>
                      <span class="badge bg-info text-dark ms-1">Tipo <?= $loanType ?></span>
                      <?php if ($daysLate > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $daysLate ?> días mora</span>
                      <?php endif; ?>
                    </div>
                    <div class="text-muted small"><?= htmlspecialchars($loan['client_name']) ?></div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold text-primary"><?= $currency ?> <?= number_format($balance, 2) ?>
                    </div>
                    <div class="text-muted" style="font-size:.75rem">saldo capital</div>
                  </div>
                  <a href="<?= url('/payments/create') ?>"
                    class="btn btn-sm btn-outline-secondary">Cambiar</a>
                </div>
              <?php endif; ?>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">
                    Monto Recibido (<?= $currency ?>) <span class="text-danger">*</span>
                  </label>
                  <input type="number" name="amount" id="amountInput"
                    class="form-control form-control-lg <?= ($mora > 0 && $preAmount > 0) ? 'border-warning' : '' ?>"
                    min="0.01" step="0.01" required placeholder="0.00"
                    value="<?= $preAmount > 0 ? number_format($preAmount, 2, '.', '') : '' ?>">
                  <?php if ($mora > 0 && $preAmount > 0): ?>
                    <div class="form-text text-warning fw-semibold">
                      <i class="bi bi-exclamation-triangle me-1"></i>
                      Incluye <?= $currency ?> <?= number_format($mora, 2) ?> de mora
                    </div>
                  <?php endif; ?>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Fecha de Pago</label>
                  <input type="date" name="payment_date" id="paymentDate"
                    class="form-control form-control-lg" value="<?= date('Y-m-d') ?>" required
                    max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Método de Pago</label>
                  <select name="payment_method" id="paymentMethod" class="form-select">
                    <?php
                    $methods = [
                      'cash' => '💵 Efectivo',
                      'transfer' => '🏦 Transferencia',
                      'check' => '📝 Cheque',
                      'other' => 'Otro'
                    ];
                    foreach ($methods as $val => $label):
                      // Pre-seleccionar según métodos del préstamo
                      $sel = ($loan && !empty($loan['payment_method_' . $val])) ? 'selected' : '';
                    ?>
                      <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Número de Recibo</label>
                  <input type="text" name="receipt_number" id="receiptNumber" class="form-control"
                    maxlength="50" placeholder="Opcional">
                </div>
                <div class="col-12">
                  <label class="form-label">Notas</label>
                  <textarea name="notes" class="form-control" rows="2" maxlength="500"
                    placeholder="Observaciones del pago..."></textarea>
                </div>
              </div>

              <div class="d-grid mt-4">
                <button type="button" class="btn btn-success btn-lg" id="confirmBtn"
                  onclick="showConfirm()">
                  <i class="bi bi-check-circle me-2"></i>Registrar Pago
                </button>
              </div>
              <button type="submit" id="submitBtn" class="d-none"></button>
            </form>
          </div>
        </div>
      </div><!-- /form col -->

      <!-- ═══ SIDEBAR ═══════════════════════════════════════════════════ -->
      <div class="col-md-5">

        <?php if ($loan && !empty($currentState)): ?>
          <!-- ESTADO ACTUAL — diferente por tipo -->
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header fw-semibold py-2
            <?= $daysLate > 0 ? 'bg-danger text-white' : 'bg-warning text-dark' ?>">
              <i class="bi bi-calculator me-2"></i>
              Estado Actual
              <?php if ($daysLate > 0): ?>
                <span class="badge bg-white text-danger ms-1"><?= $daysLate ?> días mora</span>
              <?php endif; ?>
            </div>
            <div class="card-body py-2" style="font-size:.85rem">

              <?php if ($loanType === 'C'): ?>
                <!-- ── TIPO C ─────────────────────────────────────── -->
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted">Saldo capital</span>
                  <strong><?= $currency ?> <?= number_format($balance, 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted">Interés por período</span>
                  <span><?= $currency ?> <?= number_format($periodInt, 2) ?></span>
                </div>
                <?php if ($periods > 1): ?>
                  <div class="d-flex justify-content-between py-1 border-bottom bg-warning bg-opacity-10">
                    <span class="text-warning fw-semibold">
                      <i class="bi bi-layers me-1"></i><?= $periods ?> períodos sin pagar
                    </span>
                    <strong class="text-warning"><?= $currency ?> <?= number_format($accumInt, 2) ?></strong>
                  </div>
                <?php endif; ?>
                <?php if ($paidInt > 0): ?>
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Interés ya pagado</span>
                    <span class="text-success">– <?= $currency ?> <?= number_format($paidInt, 2) ?></span>
                  </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted fw-semibold">Interés pendiente</span>
                  <strong class="text-danger"><?= $currency ?> <?= number_format($pendingInt, 2) ?></strong>
                </div>
                <?php if ($mora > 0): ?>
                  <div class="d-flex justify-content-between py-1 border-bottom bg-danger bg-opacity-10">
                    <span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Mora</span>
                    <strong class="text-danger"><?= $currency ?> <?= number_format($mora, 2) ?></strong>
                  </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between py-1 border-bottom bg-danger bg-opacity-10">
                  <span class="fw-bold">Total sin capital</span>
                  <strong class="text-danger fs-6"><?= $currency ?>
                    <?= number_format($totalDue, 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                  <span class="text-muted">Total con capital</span>
                  <strong><?= $currency ?> <?= number_format($totalWithCap, 2) ?></strong>
                </div>

              <?php elseif ($loanType === 'B'): ?>
                <!-- ── TIPO B ─────────────────────────────────────── -->
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted">Saldo capital</span>
                  <strong><?= $currency ?> <?= number_format($balance, 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted">Días transcurridos</span>
                  <span><?= $daysElapsed ?> días</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted">Interés acumulado</span>
                  <strong class="text-danger"><?= $currency ?>
                    <?= number_format($accruedInterest, 2) ?></strong>
                </div>
                <?php if ($mora > 0): ?>
                  <div class="d-flex justify-content-between py-1 border-bottom bg-danger bg-opacity-10">
                    <span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Mora</span>
                    <strong class="text-danger"><?= $currency ?> <?= number_format($mora, 2) ?></strong>
                  </div>
                <?php endif; ?>
                <?php
                $totalDueB = round($balance + $accruedInterest + $mora, 2);
                ?>
                <div class="d-flex justify-content-between py-1 bg-danger bg-opacity-10">
                  <span class="fw-bold">Total a cancelar</span>
                  <strong class="text-danger fs-6"><?= $currency ?>
                    <?= number_format($totalDueB, 2) ?></strong>
                </div>

              <?php else: ?>
                <!-- ── TIPO A ─────────────────────────────────────── -->
                <?php
                $totalConMora = round($pendCuota + $mora, 2);
                $overdueCount = (int)($currentState['overdue_count'] ?? 0);
                ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted">Saldo capital</span>
                  <strong><?= $currency ?> <?= number_format($balance, 2) ?></strong>
                </div>
                <?php if ($overdueCount > 0): ?>
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Cuotas vencidas</span>
                    <strong class="text-danger"><?= $overdueCount ?></strong>
                  </div>
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Días de mora</span>
                    <strong class="text-danger"><?= $daysLate ?> días</strong>
                  </div>
                <?php endif; ?>
                <?php if ($nextInstallment): ?>
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Capital de cuota</span>
                    <span><?= $currency ?> <?= number_format($pendCapita, 2) ?></span>
                  </div>
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Interés de cuota</span>
                    <span><?= $currency ?> <?= number_format($pendInteres, 2) ?></span>
                  </div>
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Cuota pendiente</span>
                    <strong><?= $currency ?> <?= number_format($pendCuota, 2) ?></strong>
                  </div>
                <?php endif; ?>
                <?php if ($mora > 0): ?>
                  <div class="d-flex justify-content-between py-1 border-bottom bg-danger bg-opacity-10">
                    <span class="text-danger fw-semibold">
                      <i class="bi bi-exclamation-triangle me-1"></i>Mora
                    </span>
                    <strong class="text-danger"><?= $currency ?> <?= number_format($mora, 2) ?></strong>
                  </div>
                  <div class="d-flex justify-content-between py-1 bg-warning bg-opacity-10">
                    <span class="fw-bold">Cuota + mora</span>
                    <strong class="text-warning fs-6"><?= $currency ?>
                      <?= number_format($totalConMora, 2) ?></strong>
                  </div>
                <?php elseif ($pendCuota > 0): ?>
                  <div class="d-flex justify-content-between py-1 bg-success bg-opacity-10">
                    <span class="fw-bold">Total a pagar</span>
                    <strong class="text-success fs-6"><?= $currency ?>
                      <?= number_format($pendCuota, 2) ?></strong>
                  </div>
                <?php endif; ?>
              <?php endif; ?>

              <!-- Nota aclaratoria de prioridad -->
              <div class="mt-2 pt-2 border-top text-muted" style="font-size:.72rem">
                <i class="bi bi-info-circle me-1"></i>
                Prioridad: <strong>Mora → Interés → Capital</strong>
              </div>
            </div>
          </div>

          <?php if ($nextInstallment && $loanType !== 'C'): ?>
            <!-- PRÓXIMA CUOTA (Tipo A y B) -->
            <div class="card shadow-sm border-0 mb-3">
              <div class="card-header bg-info text-white fw-semibold py-2">
                <i class="bi bi-calendar-check me-2"></i>Próxima Cuota
              </div>
              <div class="card-body py-2" style="font-size:.85rem">
                <?php
                $isOD    = $nextInstallment['due_date'] < date('Y-m-d');
                $daysOD  = $isOD
                  ? (new \DateTime($nextInstallment['due_date']))->diff(new \DateTime())->days
                  : 0;
                ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted">Vencimiento</span>
                  <strong class="<?= $isOD ? 'text-danger' : 'text-success' ?>">
                    <?= date('d/m/Y', strtotime($nextInstallment['due_date'])) ?>
                    <?= $isOD ? '<span class="badge bg-danger ms-1">Vencida</span>' : '<span class="badge bg-success ms-1">Al día</span>' ?>
                  </strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted">Capital</span>
                  <span><?= $currency ?> <?= number_format($nextInstallment['principal_amount'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                  <span class="text-muted">Interés</span>
                  <span><?= $currency ?> <?= number_format($nextInstallment['interest_amount'], 2) ?></span>
                </div>
                <?php if ((float)$nextInstallment['paid_amount'] > 0): ?>
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Ya pagado</span>
                    <span class="text-success">– <?= $currency ?>
                      <?= number_format($nextInstallment['paid_amount'], 2) ?></span>
                  </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between py-1 <?= $isOD ? '' : 'border-bottom' ?>">
                  <span class="fw-semibold">Pendiente</span>
                  <strong class="text-danger"><?= $currency ?> <?= number_format($pendCuota, 2) ?></strong>
                </div>
                <?php if ($isOD): ?>
                  <div class="alert alert-danger py-1 mt-2 mb-0" style="font-size:.78rem">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Vencida hace <strong><?= $daysOD ?> días</strong>.
                    <?php if ($daysOD > $graceDays): ?>
                      Mora activa (<?= $daysOD - $graceDays ?> días efectivos).
                    <?php else: ?>
                      Dentro del período de gracia (<?= $graceDays - $daysOD ?> días restantes).
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <!-- ═══ ATAJOS RÁPIDOS ═══════════════════════════════════════ -->
        <?php if ($loan): ?>
          <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold py-2 border-bottom">
              <i class="bi bi-lightning me-1 text-warning"></i>Atajos Rápidos
            </div>
            <div class="card-body p-2">

              <?php if ($mora > 0): ?>
                <div class="alert alert-warning py-2 mb-2" style="font-size:.8rem">
                  <i class="bi bi-exclamation-triangle-fill me-1"></i>
                  <strong>Hay mora de <?= $currency ?> <?= number_format($mora, 2) ?></strong><br>
                  <?php if ($loanType === 'A'): ?>
                    Si pagás solo la cuota (<?= $currency ?> <?= number_format($pendCuota, 2) ?>),
                    la cuota quedará <strong>PARCIAL</strong> porque la mora se descuenta primero.
                  <?php elseif ($loanType === 'B'): ?>
                    La mora se cobra antes que el interés y el capital.
                  <?php else: ?>
                    La mora se cobra antes que el interés acumulado.
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <div class="d-grid gap-1">

                <?php if ($loanType === 'C'): ?>
                  <!-- ── ATAJOS TIPO C ──── -->
                  <?php if ($mora > 0 || $pendingInt > 0): ?>
                    <button type="button" class="btn btn-warning btn-sm fw-semibold"
                      onclick="setAmount(<?= round($pendingInt + $mora, 2) ?>)">
                      <i class="bi bi-star-fill me-1"></i>
                      Interés + mora: <?= $currency ?> <?= number_format(round($pendingInt + $mora, 2), 2) ?>
                      <span class="badge bg-dark ms-1">Recomendado</span>
                    </button>
                  <?php endif; ?>
                  <?php if ($mora > 0 && $pendingInt > 0): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                      onclick="setAmount(<?= $pendingInt ?>)">
                      Solo interés (sin mora): <?= $currency ?> <?= number_format($pendingInt, 2) ?>
                    </button>
                  <?php endif; ?>
                  <?php if ($periods > 1 && $periodInt > 0): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                      onclick="setAmount(<?= round($periodInt + $mora, 2) ?>)">
                      1 período: <?= $currency ?> <?= number_format(round($periodInt + $mora, 2), 2) ?>
                      <span class="badge bg-secondary ms-1"><?= $periods ?> pendientes</span>
                    </button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-outline-success btn-sm"
                    onclick="setAmount(<?= $totalWithCap ?>)">
                    <i class="bi bi-check-all me-1"></i>
                    Cancelar todo: <?= $currency ?> <?= number_format($totalWithCap, 2) ?>
                  </button>

                <?php elseif ($loanType === 'B'): ?>
                  <!-- ── ATAJOS TIPO B ──── -->
                  <?php if ($accruedInterest > 0): ?>
                    <button type="button" class="btn btn-warning btn-sm fw-semibold"
                      onclick="setAmount(<?= round($accruedInterest + $mora, 2) ?>)">
                      <i class="bi bi-star-fill me-1"></i>
                      Solo interés<?= $mora > 0 ? ' + mora' : '' ?>:
                      <?= $currency ?> <?= number_format(round($accruedInterest + $mora, 2), 2) ?>
                      <?php if ($daysElapsed > 0): ?>
                        <span class="badge bg-dark ms-1"><?= $daysElapsed ?> días</span>
                      <?php endif; ?>
                    </button>
                  <?php endif; ?>
                  <?php if ($balance > 0): ?>
                    <button type="button" class="btn btn-outline-success btn-sm"
                      onclick="setAmount(<?= $totalDueB ?>)">
                      <i class="bi bi-check-all me-1"></i>
                      Cancelar todo: <?= $currency ?> <?= number_format($totalDueB, 2) ?>
                    </button>
                  <?php endif; ?>

                <?php else: ?>
                  <!-- ── ATAJOS TIPO A ──── -->
                  <?php if ($pendCuota > 0): ?>
                    <?php if ($mora > 0): ?>
                      <button type="button" class="btn btn-warning btn-sm fw-semibold"
                        onclick="setAmount(<?= round($pendCuota + $mora, 2) ?>)">
                        <i class="bi bi-star-fill me-1"></i>
                        Cuota + mora: <?= $currency ?> <?= number_format(round($pendCuota + $mora, 2), 2) ?>
                        <span class="badge bg-dark ms-1">Recomendado</span>
                      </button>
                      <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="setAmount(<?= $pendCuota ?>)">
                        Solo cuota (quedará parcial): <?= $currency ?> <?= number_format($pendCuota, 2) ?>
                      </button>
                    <?php else: ?>
                      <button type="button" class="btn btn-success btn-sm fw-semibold"
                        onclick="setAmount(<?= $pendCuota ?>)">
                        <i class="bi bi-check-circle me-1"></i>
                        Cuota completa: <?= $currency ?> <?= number_format($pendCuota, 2) ?>
                      </button>
                    <?php endif; ?>
                  <?php endif; ?>
                  <?php if ($balance > 0): ?>
                    <button type="button" class="btn btn-outline-primary btn-sm"
                      onclick="setAmount(<?= round($balance + $mora, 2) ?>)">
                      <i class="bi bi-check-all me-1"></i>
                      Cancelar todo: <?= $currency ?> <?= number_format(round($balance + $mora, 2), 2) ?>
                    </button>
                  <?php endif; ?>
                <?php endif; ?>

              </div>
            </div>
          </div>
        <?php endif; ?>

      </div><!-- /sidebar -->
    </div><!-- /row -->
  </div>
</div>

<script>
  // ─── DATOS DEL PRÉSTAMO (inyectados desde PHP) ────────────────────────
  const LOAN_DATA = {
    number: '<?= htmlspecialchars($loan['loan_number'] ?? '', ENT_QUOTES) ?>',
    client: '<?= htmlspecialchars($loan['client_name'] ?? '', ENT_QUOTES) ?>',
    type: '<?= $loanType ?>',
    currency: '<?= $currency ?>',
    balance: <?= $balance ?>,
    mora: <?= $mora ?>,
    daysLate: <?= $daysLate ?>,
    graceDays: <?= $graceDays ?>,

    // Tipo A
    pendCuota: <?= $pendCuota ?>,
    pendCapita: <?= $pendCapita ?>,
    pendInteres: <?= $pendInteres ?>,

    // Tipo B
    accruedInterest: <?= $accruedInterest ?>,
    daysElapsed: <?= $daysElapsed ?>,

    // Tipo C
    pendingInt: <?= $pendingInt ?>,
    periodInt: <?= $periodInt ?>,
    periods: <?= $periods ?>,
    totalDue: <?= $totalDue ?>,
    totalWithCap: <?= $totalWithCap ?>,
  };

  // ─── HELPERS ─────────────────────────────────────────────────────────
  function setAmount(val) {
    document.getElementById('amountInput').value = parseFloat(val).toFixed(2);
  }

  function fmt(n) {
    return LOAN_DATA.currency + ' ' + parseFloat(n || 0).toLocaleString('es-HN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function r2(x) {
    return Math.round(x * 100) / 100;
  }

  function methodLabel(val) {
    return {
      cash: '💵 Efectivo',
      transfer: '🏦 Transferencia',
      check: '📝 Cheque',
      other: 'Otro'
    } [val] || val;
  }

  // ─── CALCULAR DESGLOSE EN JS (espeja la lógica PHP/calculadoras) ──────
  function calcBreakdown(amount) {
    let remaining = amount;
    let paidMora = 0,
      paidInt = 0,
      paidCap = 0;

    // 1. Mora primero
    if (LOAN_DATA.mora > 0) {
      paidMora = r2(Math.min(remaining, LOAN_DATA.mora));
      remaining = r2(Math.max(0, remaining - paidMora));
    }

    // 2. Interés
    let maxInt = 0;
    if (LOAN_DATA.type === 'C') {
      maxInt = LOAN_DATA.pendingInt;
    } else if (LOAN_DATA.type === 'B') {
      maxInt = LOAN_DATA.accruedInterest;
    } else {
      maxInt = LOAN_DATA.pendInteres;
    }
    if (maxInt > 0) {
      paidInt = r2(Math.min(remaining, maxInt));
      remaining = r2(Math.max(0, remaining - paidInt));
    }

    // 3. Capital
    let maxCap = LOAN_DATA.type === 'C' ? LOAN_DATA.balance : LOAN_DATA.pendCapita;
    if (LOAN_DATA.type === 'B') maxCap = LOAN_DATA.balance;
    paidCap = r2(Math.min(remaining, maxCap));

    return {
      paidMora,
      paidInt,
      paidCap,
      remaining: r2(remaining)
    };
  }

  // ─── MODAL DE CONFIRMACIÓN ────────────────────────────────────────────
  function showConfirm() {
    const amount = parseFloat(document.getElementById('amountInput').value);
    const date = document.getElementById('paymentDate').value;
    const method = document.getElementById('paymentMethod').value;
    const receipt = document.getElementById('receiptNumber').value;

    if (!amount || amount <= 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Monto requerido',
        text: 'Ingresá un monto mayor a 0.',
        confirmButtonColor: '#2563eb'
      });
      return;
    }

    const bd = calcBreakdown(amount);
    const [y, m, d] = date.split('-');
    const dateLabel = `${d}/${m}/${y}`;

    // Advertencias
    let warnings = '';
    if (LOAN_DATA.mora > 0 && amount < r2(LOAN_DATA.mora + (LOAN_DATA.type === 'A' ? LOAN_DATA.pendCuota : 0))) {
      warnings += `<div class="alert alert-warning py-1 mt-2 mb-0" style="font-size:.78rem">
      ⚠ El monto no cubre la cuota completa + mora.
      La cuota quedará en estado <strong>Parcial</strong>.
    </div>`;
    }
    if (LOAN_DATA.daysLate > 0 && LOAN_DATA.mora === 0) {
      warnings += `<div class="alert alert-info py-1 mt-2 mb-0" style="font-size:.78rem">
      ℹ Dentro del período de gracia (${LOAN_DATA.graceDays} días). Sin mora.
    </div>`;
    }

    // Desglose visual
    const typeLabel = {
      A: 'Cuota Nivelada',
      B: 'Variable',
      C: 'Interés Simple'
    } [LOAN_DATA.type] || LOAN_DATA.type;
    const breakdown = `
    <table class="table table-sm table-bordered mb-0" style="font-size:.84rem">
      <tbody>
        ${bd.paidMora > 0 ? `<tr class="table-danger">
          <td class="text-start"><i class="bi bi-exclamation-triangle me-1"></i>Mora</td>
          <td class="text-end fw-semibold">${fmt(bd.paidMora)}</td>
        </tr>` : ''}
        ${bd.paidInt > 0 ? `<tr>
          <td class="text-start text-muted">
            ${LOAN_DATA.type === 'C' ? 'Interés acumulado' : LOAN_DATA.type === 'B' ? 'Interés por días' : 'Interés'}
          </td>
          <td class="text-end fw-semibold">${fmt(bd.paidInt)}</td>
        </tr>` : ''}
        ${bd.paidCap > 0 ? `<tr class="table-success">
          <td class="text-start">
            ${LOAN_DATA.type === 'C' ? 'Abono capital (voluntario)' : 'Capital'}
          </td>
          <td class="text-end fw-semibold">${fmt(bd.paidCap)}</td>
        </tr>` : ''}
        ${bd.paidMora === 0 && bd.paidInt === 0 && bd.paidCap === 0 ? `<tr>
          <td colspan="2" class="text-center text-muted">Sin aplicación calculada</td>
        </tr>` : ''}
        <tr class="table-light fw-bold">
          <td class="text-start">Total recibido</td>
          <td class="text-end text-success">${fmt(amount)}</td>
        </tr>
      </tbody>
    </table>`;

    Swal.fire({
      title: '¿Confirmar pago?',
      html: `
      <div style="text-align:left">
        <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded"
          style="background:#f8fafc;border:1px solid #e2e8f0">
          <div>
            <div class="fw-semibold">${LOAN_DATA.number}</div>
            <div class="text-muted" style="font-size:.82rem">${LOAN_DATA.client}</div>
          </div>
          <div class="text-end">
            <span class="badge bg-info text-dark d-block mb-1">Tipo ${LOAN_DATA.type} · ${typeLabel}</span>
            ${LOAN_DATA.daysLate > 0
              ? `<span class="badge bg-danger">${LOAN_DATA.daysLate} días mora</span>`
              : '<span class="badge bg-success">Al día</span>'}
          </div>
        </div>

        <div class="mb-3" style="font-size:.85rem">
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Fecha</span><strong>${dateLabel}</strong>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Método</span><strong>${methodLabel(method)}</strong>
          </div>
          ${receipt ? `<div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Recibo</span><strong>${receipt}</strong>
          </div>` : ''}
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">Saldo actual</span>
            <strong>${fmt(LOAN_DATA.balance)}</strong>
          </div>
        </div>

        <div class="mb-1 fw-semibold" style="font-size:.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em">
          Desglose estimado (Mora → Interés → Capital)
        </div>
        ${breakdown}
        ${warnings}
      </div>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: '<i class="bi bi-check-circle me-1"></i>Sí, registrar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      width: '440px',
    }).then(result => {
      if (result.isConfirmed) {
        document.getElementById('confirmBtn').disabled = true;
        document.getElementById('confirmBtn').innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
        document.getElementById('submitBtn').click();
      }
    });
  }
</script>