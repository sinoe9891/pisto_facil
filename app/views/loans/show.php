<?php
$currency = setting('app_currency','L');
$typeMap  = ['A'=>'Nivelada','B'=>'Variable','C'=>'Simple Mensual'];
$statusMap= ['active'=>['success','Activo'],'paid'=>['primary','Pagado'],
             'defaulted'=>['danger','Moroso'],'cancelled'=>['secondary','Cancelado'],
             'restructured'=>['warning','Restructurado']];
[$sc, $sl] = $statusMap[$loan['status']] ?? ['secondary','Otro'];
$isAdmin   = \App\Core\Auth::isAdmin();
$isTypeC   = $loan['loan_type'] === 'C';
$isTypeB   = $loan['loan_type'] === 'B';
?>

<!-- BREADCRUMB -->
<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= url('/loans') ?>">Préstamos</a></li>
      <li class="breadcrumb-item"><a href="<?= url('/clients/' . $loan['client_id']) ?>"><?= htmlspecialchars($loan['client_name']) ?></a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($loan['loan_number']) ?></li>
    </ol>
  </nav>
  <div class="btn-group btn-group-sm">
    <?php if ($loan['status'] === 'active'): ?>
    <a href="<?= url('/payments/create?loan_id=' . $loan['id']) ?>" class="btn btn-success">
      <i class="bi bi-cash me-1"></i>Registrar Pago
    </a>
    <?php endif; ?>
    <a href="<?= url('/loans/' . $loan['id'] . '/amortization') ?>" class="btn btn-outline-secondary" target="_blank">
      <i class="bi bi-table me-1"></i>Tabla
    </a>
    <?php if ($isAdmin): ?>
    <a href="<?= url('/loans/' . $loan['id'] . '/edit') ?>" class="btn btn-outline-primary">
      <i class="bi bi-pencil"></i>
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <!-- LEFT: Info del préstamo -->
  <div class="col-xl-4">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="rounded bg-primary bg-opacity-10 p-2">
            <i class="bi bi-cash-coin text-primary fs-5"></i>
          </div>
          <div>
            <div class="fw-bold"><?= htmlspecialchars($loan['loan_number']) ?></div>
            <span class="badge bg-info text-dark">Tipo <?= $loan['loan_type'] ?> · <?= $typeMap[$loan['loan_type']] ?></span>
            <span class="badge bg-<?= $sc ?> ms-1"><?= $sl ?></span>
          </div>
        </div>

        <?php $rows = [
          ['Cliente',        '<a href="'.url('/clients/'.$loan['client_id']).'">'.htmlspecialchars($loan['client_name']).'</a>'],
          ['Monto Original', $currency . ' ' . number_format($loan['principal'], 2)],
          ['Saldo Actual',   '<strong class="text-'.($loan['balance']>0?'danger':'success').'">'.$currency.' '.number_format($loan['balance'],2).'</strong>'],
          ['Tasa de Interés',number_format($loan['interest_rate']*100,2).'% / '.($loan['rate_type']==='annual'?'año':'mes')],
          ['Tasa Moratoria', number_format($loan['late_fee_rate']*100,2).'% / mes'],
          ['Plazo',          $loan['term_months'] ? $loan['term_months'].' cuotas' : 'Variable'],
          ['Frecuencia',     match($loan['payment_frequency']??'monthly'){'weekly'=>'Semanal','biweekly'=>'Quincenal','monthly'=>'Mensual','bimonthly'=>'Bimensual','quarterly'=>'Trimestral','semiannual'=>'Semestral','annual'=>'Anual',default=>'Mensual'}],
          ['Desembolso',     date('d/m/Y',strtotime($loan['disbursement_date']))],
          ['Primer Pago',    $loan['first_payment_date'] ? date('d/m/Y',strtotime($loan['first_payment_date'])) : '–'],
          ['Vencimiento',    $loan['maturity_date'] ? date('d/m/Y',strtotime($loan['maturity_date'])) : '–'],
          ['Asesor',         htmlspecialchars($loan['assigned_name'] ?? 'Sin asignar')],
          ['Total Cobrado',  $currency.' '.number_format($loan['total_paid'],2)],
          ['Días de Gracia', $loan['grace_days'].' días'],
        ]; ?>
        <?php foreach ($rows as [$label, $val]): ?>
        <div class="d-flex justify-content-between border-bottom py-1" style="font-size:.85rem">
          <span class="text-muted"><?= $label ?></span>
          <span><?= $val ?></span>
        </div>
        <?php endforeach; ?>

        <?php if ($loan['notes']): ?>
        <div class="mt-3 p-2 bg-light rounded" style="font-size:.8rem">
          <div class="text-muted mb-1">Notas:</div>
          <?= nl2br(htmlspecialchars($loan['notes'])) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ═══ ESTADO ACTUAL (según tipo de préstamo) ═══ -->
    <?php if ($loan['status'] === 'active' && !empty($currentState)): ?>
    <div class="card shadow-sm border-0 mt-3">
      <div class="card-header py-2 border-bottom d-flex align-items-center justify-content-between
        <?= ($currentState['days_late'] ?? 0) > 0 ? 'bg-danger text-white' : 'bg-white' ?>">
        <span class="fw-semibold">
          <i class="bi bi-calculator me-2 <?= ($currentState['days_late'] ?? 0) > 0 ? '' : 'text-warning' ?>"></i>
          Estado Actual
        </span>
        <?php if (($currentState['days_late'] ?? 0) > 0): ?>
        <span class="badge bg-white text-danger">
          <?= $currentState['days_late'] ?> días vencido
        </span>
        <?php endif; ?>
      </div>
      <div class="card-body py-2" style="font-size:.85rem">

        <?php if ($isTypeC): ?>
        <!-- ── Tipo C: desglose de interés acumulado ── -->
        <?php
          $balance     = $currentState['balance']              ?? $loan['balance'];
          $periodInt   = $currentState['period_interest']      ?? 0;
          $accumInt    = $currentState['accumulated_interest']  ?? $periodInt;
          $paidInt     = $currentState['paid_interest']         ?? 0;
          $pendingInt  = $currentState['pending_interest']      ?? $accumInt;
          $lateFee     = $currentState['late_fee']              ?? 0;
          $totalDue    = $currentState['total_due']             ?? 0;
          $totalWithC  = $currentState['total_due_with_cap']    ?? ($balance + $totalDue);
          $daysLate    = $currentState['days_late']             ?? 0;
          $periods     = $currentState['periods_elapsed']       ?? 0;
        ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Capital pendiente</span>
          <strong class="text-danger"><?= $currency ?> <?= number_format($balance, 2) ?></strong>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Interés por período</span>
          <span><?= $currency ?> <?= number_format($periodInt, 2) ?></span>
        </div>

        <?php if ($periods > 1): ?>
        <div class="d-flex justify-content-between py-1 border-bottom" style="background:#fff3cd">
          <span class="fw-semibold text-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Interés acumulado (<?= $periods ?> períodos)
          </span>
          <strong class="text-warning"><?= $currency ?> <?= number_format($accumInt, 2) ?></strong>
        </div>
        <?php else: ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Interés acumulado</span>
          <span><?= $currency ?> <?= number_format($accumInt, 2) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($paidInt > 0): ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Ya pagado (interés)</span>
          <span class="text-success">– <?= $currency ?> <?= number_format($paidInt, 2) ?></span>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="fw-semibold">Interés pendiente</span>
          <strong class="text-danger"><?= $currency ?> <?= number_format($pendingInt, 2) ?></strong>
        </div>

        <?php if ($lateFee > 0): ?>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Mora</span>
          <strong class="text-danger"><?= $currency ?> <?= number_format($lateFee, 2) ?></strong>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between py-1 border-bottom fw-bold" style="background:#fee2e2">
          <span>Total sin capital</span>
          <strong class="text-danger"><?= $currency ?> <?= number_format($totalDue, 2) ?></strong>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Cancelación total</span>
          <strong><?= $currency ?> <?= number_format($totalWithC, 2) ?></strong>
        </div>

        <?php elseif ($isTypeB): ?>
        <!-- ── Tipo B: interés por días ── -->
        <?php
          $labels = [
            'balance'          => 'Saldo capital',
            'days_elapsed'     => 'Días transcurridos',
            'days_late'        => 'Días vencido',
            'accrued_interest' => 'Interés acumulado',
            'late_fee'         => 'Mora',
            'total_due'        => 'Total a pagar',
          ];
        ?>
        <?php foreach ($labels as $key => $label): ?>
          <?php if (!isset($currentState[$key]) || !is_numeric($currentState[$key])) continue; ?>
          <?php $v = $currentState[$key]; ?>
          <div class="d-flex justify-content-between py-1 border-bottom
            <?= in_array($key,['total_due','accrued_interest','late_fee']) && $v > 0 ? 'fw-semibold' : '' ?>">
            <span class="text-muted"><?= $label ?></span>
            <strong class="<?= in_array($key,['late_fee','days_late']) && $v > 0 ? 'text-danger' : '' ?>">
              <?php if (in_array($key, ['accrued_interest','late_fee','total_due','balance'])): ?>
                <?= $currency ?> <?= number_format($v, 2) ?>
              <?php else: ?>
                <?= $v ?>
              <?php endif; ?>
            </strong>
          </div>
        <?php endforeach; ?>

        <?php else: ?>
        <!-- ── Tipo A: cuotas vencidas ── -->
        <?php
          $labels = [
            'days_late'     => 'Días de mora',
            'late_fee'      => 'Cargo por mora',
            'overdue_count' => 'Cuotas vencidas',
          ];
        ?>
        <?php foreach ($labels as $key => $label): ?>
          <?php if (!isset($currentState[$key]) || !is_numeric($currentState[$key])) continue; ?>
          <?php $v = $currentState[$key]; ?>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted"><?= $label ?></span>
            <strong class="<?= $v > 0 ? 'text-danger' : '' ?>">
              <?= $key === 'late_fee' ? $currency.' '.number_format($v,2) : $v ?>
            </strong>
          </div>
        <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>
  </div><!-- /col left -->

  <!-- RIGHT: Tabs -->
  <div class="col-xl-8">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-bottom">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-installments">
              <i class="bi bi-calendar3 me-1"></i>Cuotas (<?= count($installments) ?>)
              <?php
              // Para Tipo C: contar períodos vencidos con interés pendiente
              // Para A/B: contar cuotas vencidas del DB
              $overdueCount = 0;
              $today = date('Y-m-d');
              if ($isTypeC && ($currentState['days_late'] ?? 0) > 0) {
                // Contar solo los períodos que tienen interés pendiente de pago
                $paidLeft2 = (float)($currentState['paid_interest'] ?? 0);
                $periodInt2= (float)($currentState['period_interest'] ?? 0);
                $perElap2  = (int)($currentState['periods_elapsed'] ?? 0);
                for ($p2 = 1; $p2 <= $perElap2; $p2++) {
                  if ($paidLeft2 < $periodInt2) $overdueCount++;
                  else $paidLeft2 -= $periodInt2;
                }
              } else {
                foreach ($installments as $inst) {
                  if ($inst['due_date'] < $today && in_array($inst['status'],['pending','partial'])) $overdueCount++;
                }
              }
              if ($overdueCount > 0): ?>
              <span class="badge bg-danger ms-1"><?= $overdueCount ?> vencida<?= $overdueCount > 1 ? 's' : '' ?></span>
              <?php endif; ?>
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payments">
              <i class="bi bi-receipt me-1"></i>Pagos (<?= count($payments) ?>)
            </button>
          </li>
        </ul>
      </div>
      <div class="card-body p-0">
        <div class="tab-content">

          <!-- TAB CUOTAS -->
          <div class="tab-pane fade show active" id="tab-installments">
            <?php
            // ── Para Tipo C: construir filas virtuales de períodos vencidos ──────
            // El DB solo tiene las cuotas del plazo original. Si han pasado más
            // períodos, generamos filas virtuales para visualización.
            $freqDaysMap = ['weekly'=>7,'biweekly'=>15,'monthly'=>30,'bimonthly'=>60,'quarterly'=>90,'semiannual'=>180,'annual'=>365];
            $freqModMap  = ['weekly'=>'+7 days','biweekly'=>'+15 days','monthly'=>'+1 month','bimonthly'=>'+2 months','quarterly'=>'+3 months','semiannual'=>'+6 months','annual'=>'+1 year'];
            $freq        = $loan['payment_frequency'] ?? 'monthly';
            $freqMod     = $freqModMap[$freq] ?? '+1 month';

            $virtualRows   = [];  // filas extra para Tipo C vencido
            $nextDueDate   = null; // próximo vencimiento calculado
            $paidInterest  = (float)($currentState['paid_interest'] ?? 0);
            $periodInt     = (float)($currentState['period_interest'] ?? 0);
            $periodsElapsed= (int)($currentState['periods_elapsed'] ?? 0);

            if ($isTypeC && $periodsElapsed > 0) {
              // Generar todas las fechas de vencimiento de períodos transcurridos
              $firstDue = new \DateTime($loan['first_payment_date'] ?? $loan['disbursement_date']);
              $curDate  = clone $firstDue;
              $paidLeft = $paidInterest; // cuánto interés se ha pagado para distribuir

              for ($p = 1; $p <= $periodsElapsed; $p++) {
                $dueStr = $curDate->format('Y-m-d');
                // Cuánto se pagó de este período
                $periodPaid = 0;
                if ($paidLeft >= $periodInt) {
                  $periodPaid = $periodInt;
                  $paidLeft  -= $periodInt;
                } elseif ($paidLeft > 0) {
                  $periodPaid = $paidLeft;
                  $paidLeft   = 0;
                }
                $periodPending = round($periodInt - $periodPaid, 2);
                $status = $periodPaid >= $periodInt ? 'paid' : ($periodPaid > 0 ? 'partial' : 'overdue');

                $virtualRows[] = [
                  'num'       => $p,
                  'due_date'  => $dueStr,
                  'interest'  => $periodInt,
                  'paid'      => $periodPaid,
                  'pending'   => $periodPending,
                  'status'    => $status,
                  'days_late' => $dueStr < $today ? (int)(new \DateTime($dueStr))->diff(new \DateTime())->days : 0,
                ];
                $curDate->modify($freqMod);
              }
              // El próximo vencimiento es la fecha DESPUÉS del último período transcurrido
              $nextDueDate = $curDate->format('Y-m-d');
            }

            // Bandera para mostrar aviso de mora
            $hasMora = $isTypeC && ($currentState['days_late'] ?? 0) > 0;
            ?>

            <?php if ($hasMora): ?>
            <?php
              $accumInt = $currentState['accumulated_interest'] ?? $periodInt;
              $daysLate = $currentState['days_late'];
              $pendInt  = $currentState['pending_interest'] ?? 0;
            ?>
            <div class="alert alert-warning mb-0 rounded-0 border-0 border-bottom py-2" style="font-size:.83rem">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <strong>Tipo C · Interés acumulado:</strong>
              <strong><?= $daysLate ?> días vencido</strong>
              (<?= $periodsElapsed ?> período<?= $periodsElapsed > 1 ? 's' : '' ?> transcurridos ·
              Interés acumulado <strong class="text-danger"><?= $currency ?> <?= number_format($accumInt, 2) ?></strong>).
              <?php if ($pendInt > 0): ?>
              Pendiente de pago: <strong class="text-danger"><?= $currency ?> <?= number_format($pendInt, 2) ?></strong>.
              <?php else: ?>
              <span class="text-success fw-semibold">Interés al día ✓</span>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
              <table class="table table-custom table-hover mb-0">
                <thead>
                  <tr>
                    <th class="text-center">#</th>
                    <th>Vencimiento</th>
                    <th class="text-end">Capital</th>
                    <th class="text-end">Interés</th>
                    <th class="text-end">Cuota Total</th>
                    <th class="text-end">Pagado</th>
                    <th class="text-end">Saldo</th>
                    <th class="text-center">Estado</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                $statClass = ['pending'=>'bg-secondary','partial'=>'bg-warning text-dark','paid'=>'bg-success','overdue'=>'bg-danger'];
                $statLabel = ['pending'=>'Pendiente','partial'=>'Parcial','paid'=>'Pagado','overdue'=>'Vencida'];
                ?>

                <?php if ($isTypeC && !empty($virtualRows)): ?>
                  <?php foreach ($virtualRows as $vr): ?>
                  <?php
                    $vrClass = $vr['status'] === 'paid'    ? 'table-success'
                             : ($vr['status'] === 'partial' ? 'table-warning'
                             : 'table-danger');
                    $vrBadge = $statClass[$vr['status']] ?? 'bg-secondary';
                    $vrLabel = $statLabel[$vr['status']] ?? '–';
                  ?>
                  <tr class="<?= $vrClass ?>">
                    <td class="text-center fw-semibold"><?= $vr['num'] ?></td>
                    <td>
                      <?= date('d/m/Y', strtotime($vr['due_date'])) ?>
                      <?php if ($vr['days_late'] > 0 && $vr['status'] !== 'paid'): ?>
                      <div style="font-size:.7rem;color:#dc2626"><?= $vr['days_late'] ?> días mora</div>
                      <?php endif; ?>
                    </td>
                    <td class="text-end text-muted">–</td>
                    <td class="text-end"><?= $currency ?> <?= number_format($vr['interest'],2) ?></td>
                    <td class="text-end fw-semibold"><?= $currency ?> <?= number_format($vr['interest'],2) ?></td>
                    <td class="text-end <?= $vr['paid'] > 0 ? 'text-success' : 'text-muted' ?>">
                      <?= $vr['paid'] > 0 ? $currency.' '.number_format($vr['paid'],2) : '–' ?>
                    </td>
                    <td class="text-end text-muted">–</td>
                    <td class="text-center">
                      <span class="badge <?= $vrBadge ?>"><?= $vrLabel ?></span>
                    </td>
                  </tr>
                  <?php endforeach; ?>

                  <?php if ($nextDueDate): ?>
                  <!-- Fila de próximo vencimiento -->
                  <tr style="background:#f0fdf4;border-top:2px solid #16a34a">
                    <td class="text-center text-success fw-semibold"><?= $periodsElapsed + 1 ?></td>
                    <td>
                      <strong class="text-success"><?= date('d/m/Y', strtotime($nextDueDate)) ?></strong>
                      <div style="font-size:.72rem" class="text-success">
                        <i class="bi bi-clock me-1"></i>Próximo vencimiento
                      </div>
                    </td>
                    <td class="text-end text-muted">–</td>
                    <td class="text-end text-success"><?= $currency ?> <?= number_format($periodInt,2) ?></td>
                    <td class="text-end text-success fw-semibold"><?= $currency ?> <?= number_format($periodInt,2) ?></td>
                    <td class="text-end text-muted">–</td>
                    <td class="text-end text-muted">–</td>
                    <td class="text-center">
                      <span class="badge bg-success bg-opacity-75">Próxima</span>
                    </td>
                  </tr>
                  <?php endif; ?>

                <?php else: ?>
                  <?php foreach ($installments as $inst): ?>
                  <?php
                    $isOD     = $inst['due_date'] < $today && in_array($inst['status'],['pending','partial']);
                    $rowClass = $isOD ? 'table-danger' : ($inst['status'] === 'paid' ? 'table-success' : '');
                    $sk       = $isOD ? 'overdue' : $inst['status'];
                    $realDaysLate = $isOD ? (int)(new \DateTime($inst['due_date']))->diff(new \DateTime())->days : 0;
                  ?>
                  <tr class="<?= $rowClass ?>">
                    <td class="text-center fw-semibold"><?= $inst['installment_number'] ?></td>
                    <td>
                      <?= date('d/m/Y',strtotime($inst['due_date'])) ?>
                      <?php if ($isOD): ?>
                      <div style="font-size:.7rem;color:#dc2626"><?= $realDaysLate ?> días mora</div>
                      <?php endif; ?>
                    </td>
                    <td class="text-end"><?= $currency ?> <?= number_format($inst['principal_amount'],2) ?></td>
                    <td class="text-end"><?= $currency ?> <?= number_format($inst['interest_amount'],2) ?></td>
                    <td class="text-end fw-semibold"><?= $currency ?> <?= number_format($inst['total_amount'],2) ?></td>
                    <td class="text-end text-success">
                      <?= $inst['paid_amount'] > 0 ? $currency.' '.number_format($inst['paid_amount'],2) : '–' ?>
                    </td>
                    <td class="text-end"><?= $currency ?> <?= number_format($inst['balance_after'],2) ?></td>
                    <td class="text-center">
                      <span class="badge <?= $statClass[$sk] ?? 'bg-secondary' ?>"><?= $statLabel[$sk] ?? '–' ?></span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (empty($installments)): ?>
                  <tr><td colspan="8" class="text-center text-muted py-3">Sin cuotas registradas.</td></tr>
                  <?php endif; ?>
                <?php endif; ?>

                </tbody>
                <?php if ($isTypeC && !empty($virtualRows)): ?>
                <?php
                  $totIntVirtual  = round($periodInt * $periodsElapsed, 2);
                  $totPaidVirtual = $paidInterest;
                  $totPendVirtual = round($currentState['pending_interest'] ?? 0, 2);
                ?>
                <tfoot class="table-secondary fw-semibold" style="font-size:.82rem">
                  <tr>
                    <td colspan="2">
                      TOTALES (<?= $periodsElapsed ?> períodos)
                      <?php if ($hasMora): ?>
                      <span class="badge bg-warning text-dark ms-1">interés acumulado</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end"><?= $currency ?> <?= number_format($loan['balance'],2) ?></td>
                    <td class="text-end text-danger"><?= $currency ?> <?= number_format($totIntVirtual,2) ?></td>
                    <td class="text-end"><?= $currency ?> <?= number_format($loan['balance'] + $totIntVirtual,2) ?></td>
                    <td class="text-end text-success"><?= $currency ?> <?= number_format($totPaidVirtual,2) ?></td>
                    <td class="text-end text-danger"><?= $currency ?> <?= number_format($totPendVirtual,2) ?></td>
                    <td></td>
                  </tr>
                </tfoot>
                <?php elseif (!empty($installments)): ?>
                <?php
                  $totCap = array_sum(array_column($installments,'principal_amount'));
                  $totInt = array_sum(array_column($installments,'interest_amount'));
                  $totPay = array_sum(array_column($installments,'total_amount'));
                ?>
                <tfoot class="table-secondary fw-semibold" style="font-size:.82rem">
                  <tr>
                    <td colspan="2">TOTALES</td>
                    <td class="text-end"><?= $currency ?> <?= number_format($totCap,2) ?></td>
                    <td class="text-end text-danger"><?= $currency ?> <?= number_format($totInt,2) ?></td>
                    <td class="text-end"><?= $currency ?> <?= number_format($totPay,2) ?></td>
                    <td colspan="3"></td>
                  </tr>
                </tfoot>
                <?php endif; ?>
              </table>
            </div>
          </div><!-- /tab-installments -->

          <!-- TAB PAGOS -->
          <div class="tab-pane fade" id="tab-payments">
            <div class="table-responsive">
              <table class="table table-custom table-hover mb-0">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Recibo</th>
                    <th>Método</th>
                    <th class="text-end">Monto</th>
                    <th>Registró</th>
                    <?php if ($isAdmin): ?><th></th><?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                <tr class="<?= $p['voided'] ? 'table-secondary' : '' ?>">
                  <td><?= date('d/m/Y',strtotime($p['payment_date'])) ?></td>
                  <td>
                    <a href="<?= url('/payments/'.$p['id']) ?>" class="text-decoration-none">
                      <?= htmlspecialchars($p['payment_number']) ?>
                    </a>
                    <?php if ($p['voided']): ?>
                    <span class="badge bg-danger ms-1" style="font-size:.65rem">Anulado</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php $methodLabel = ['cash'=>'💵 Efectivo','transfer'=>'🏦 Transferencia','check'=>'📝 Cheque','other'=>'Otro']; ?>
                    <span class="badge bg-secondary"><?= $methodLabel[$p['payment_method']] ?? $p['payment_method'] ?></span>
                  </td>
                  <td class="text-end fw-semibold <?= $p['voided'] ? 'text-decoration-line-through text-muted' : 'text-success' ?>">
                    <?= $currency ?> <?= number_format($p['total_received'],2) ?>
                  </td>
                  <td style="font-size:.8rem"><?= htmlspecialchars($p['registered_by_name']) ?></td>
                  <?php if ($isAdmin): ?>
                  <td>
                    <?php if (!$p['voided']): ?>
                    <a href="#" onclick="confirmAction('<?= url('/payments/'.$p['id'].'/void') ?>','¿Anular pago?','Esta acción no se puede deshacer.','warning')"
                       class="btn btn-sm btn-outline-danger py-0">Anular</a>
                    <?php endif; ?>
                  </td>
                  <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                <tr>
                  <td colspan="<?= $isAdmin ? 6 : 5 ?>" class="text-center text-muted py-3">
                    Sin pagos registrados.
                  </td>
                </tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($payments)): ?>
                <?php $totalValidPaid = array_sum(array_map(fn($p)=>$p['voided']?0:$p['total_received'],$payments)); ?>
                <tfoot class="table-secondary fw-semibold" style="font-size:.82rem">
                  <tr>
                    <td colspan="3">TOTAL COBRADO</td>
                    <td class="text-end text-success"><?= $currency ?> <?= number_format($totalValidPaid,2) ?></td>
                    <td colspan="<?= $isAdmin ? 2 : 1 ?>"></td>
                  </tr>
                </tfoot>
                <?php endif; ?>
              </table>
            </div>
          </div><!-- /tab-payments -->

        </div><!-- /tab-content -->
      </div>
    </div>
  </div><!-- /col right -->
</div><!-- /row -->