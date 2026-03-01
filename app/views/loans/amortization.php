<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tabla de Amortización – <?= htmlspecialchars($loan['loan_number']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    @media print {
      .no-print { display:none!important; }
      body { font-size:11px; background:#fff!important; }
      .header-card { background:#1e293b!important; color:#fff!important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .card { border:1px solid #dee2e6!important; box-shadow:none!important; break-inside:avoid; }
      .table-success td { background-color:#d1fae5!important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .table-danger  td { background-color:#fee2e2!important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .table-warning td { background-color:#fef3c7!important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .badge { border:1px solid #ccc; padding:2px 5px; }
      thead th { background:#1e293b!important; color:#fff!important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      tfoot tr td { background:#1e293b!important; color:#fff!important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .state-card.overdue .card-header { background:#dc2626!important; color:#fff!important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    }
    body { font-family:'Inter',system-ui,sans-serif; background:#f8fafc; }
    .table thead th { background:#1e293b; color:#fff; font-size:.75rem; text-transform:uppercase; }
    .table-striped>tbody>tr:nth-of-type(odd)>* { background-color:#f1f5f9; }
    .header-card { background:linear-gradient(135deg,#1e293b,#2563eb); color:#fff; border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
    .state-card { border-left:4px solid #f59e0b; }
    .state-card.overdue { border-left-color:#dc2626; }
  </style>
</head>
<body class="p-4">

<?php
$currency = setting('app_currency','L');
$typeMap  = ['A'=>'Cuota Nivelada','B'=>'Variable Decreciente','C'=>'Interés Simple Mensual'];
$isTypeC  = $loan['loan_type'] === 'C';
$today    = date('Y-m-d');

// Totales nominales del DB
$totCapDB  = array_sum(array_column($installments,'principal_amount'));
$totIntDB  = array_sum(array_column($installments,'interest_amount'));
$totPayDB  = array_sum(array_column($installments,'total_amount'));

// Para Tipo C: usar interés acumulado real si hay mora
$totIntReal = $totIntDB;
$totPayReal = $totPayDB;
if ($isTypeC && isset($currentState['accumulated_interest']) && $currentState['days_late'] > 0) {
  $totIntReal = $currentState['accumulated_interest'];
  $totPayReal = round($loan['balance'] + $currentState['total_due'], 2);
}

$hasOverdue = $isTypeC && ($currentState['days_late'] ?? 0) > 0;

// Variables para la tabla virtual de Tipo C
$periodsElapsed = (int)($currentState['periods_elapsed'] ?? 0);
$paidInt        = (float)($currentState['paid_interest']  ?? 0);
$periodInt      = (float)($currentState['period_interest'] ?? 0);
?>

<!-- HEADER -->
<div class="header-card">
  <div class="row align-items-center">
    <div class="col">
      <h4 class="fw-bold mb-1"><?= setting('app_name','SistemaPréstamos') ?></h4>
      <div class="opacity-90">Prestamo · <?= htmlspecialchars($loan['loan_number']) ?></div>
      <div class="opacity-70" style="font-size:.82rem">Generado: <?= date('d/m/Y H:i') ?></div>
    </div>
    <div class="col-auto no-print d-flex gap-2">
      <button onclick="window.print()" class="btn btn-light btn-sm">
        <i class="bi bi-printer me-1"></i>Imprimir
      </button>
      <a href="<?= url('/loans/'.$loan['id']) ?>" class="btn btn-outline-light btn-sm">← Volver</a>
    </div>
  </div>
</div>

<!-- INFO CARDS -->
 
<div class="row g-3 mb-4">
  <?php $infoItems = [
    ['Cliente',     $loan['client_name']],
    ['Tipo',        'Tipo '.$loan['loan_type'].' – '.($typeMap[$loan['loan_type']]??'')],
    ['Capital',     $currency.' '.number_format($loan['principal'],2)],
    ['Saldo actual',$currency.' '.number_format($loan['balance'],2)],
    ['Tasa',        number_format($loan['interest_rate']*100,2).'% / '.($loan['rate_type']==='annual'?'año':'mes')],
    ['Plazo',       ($loan['term_months']??'Variable').' '.($loan['term_months']?'cuotas':'')],
    ['Desembolso',  date('d/m/Y',strtotime($loan['disbursement_date']))],
    ['Frecuencia',  match($loan['payment_frequency']??'monthly'){'weekly'=>'Semanal','biweekly'=>'Quincenal','monthly'=>'Mensual','bimonthly'=>'Bimensual','quarterly'=>'Trimestral','semiannual'=>'Semestral','annual'=>'Anual',default=>'Mensual'}],
  ]; ?>
  <?php foreach ($infoItems as [$label,$val]): ?>
  <div class="col-6 col-md-3 col-lg-2">
    <div class="card border-0 shadow-sm p-2 text-center h-100">
      <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em"><?= $label ?></div>
      <div class="fw-semibold" style="font-size:.85rem"><?= htmlspecialchars((string)$val) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ═══ ESTADO ACTUAL TIPO C (solo si hay mora) ═══ -->
<?php if ($isTypeC && $loan['status'] === 'active' && !empty($currentState)): ?>
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
<div class="card shadow-sm border-0 mb-4 state-card <?= $daysLate > 0 ? 'overdue' : '' ?>">
  <div class="card-header py-2 d-flex justify-content-between align-items-center
    <?= $daysLate > 0 ? 'bg-danger text-white' : 'bg-white' ?>">
    <span class="fw-semibold">
      <i class="bi bi-calculator me-2"></i>
      Estado Actual · Tipo C – Interés Simple Mensual
    </span>
    <?php if ($daysLate > 0): ?>
    <span class="badge bg-white text-danger fw-bold"><?= $daysLate ?> días vencido · <?= $periods ?> período<?= $periods>1?'s':'' ?></span>
    <?php else: ?>
    <span class="badge bg-success">Al día</span>
    <?php endif; ?>
  </div>
  <div class="card-body py-0">
    <div class="row py-2" style="font-size:.85rem">
      <div class="col-md-6">
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="text-muted">Capital pendiente</span>
          <strong class="text-danger"><?= $currency ?> <?= number_format($balance,2) ?></strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="text-muted">Interés por período (base)</span>
          <span><?= $currency ?> <?= number_format($periodInt,2) ?></span>
        </div>
        <?php if ($periods > 1): ?>
        <div class="d-flex justify-content-between py-2 border-bottom" style="background:#fff3cd">
          <span class="fw-semibold text-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Interés acumulado (<?= $periods ?> períodos)
          </span>
          <strong class="text-warning"><?= $currency ?> <?= number_format($accumInt,2) ?></strong>
        </div>
        <?php endif; ?>
        <?php if ($paidInt > 0): ?>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="text-muted">Ya pagado (interés)</span>
          <span class="text-success">– <?= $currency ?> <?= number_format($paidInt,2) ?></span>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-md-6">
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="fw-semibold">Interés pendiente</span>
          <strong class="text-danger"><?= $currency ?> <?= number_format($pendingInt,2) ?></strong>
        </div>
        <?php if ($lateFee > 0): ?>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="text-muted">Mora</span>
          <strong class="text-danger"><?= $currency ?> <?= number_format($lateFee,2) ?></strong>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between py-2 border-bottom" style="background:#fee2e2">
          <span class="fw-bold">Total sin capital</span>
          <strong class="text-danger fs-6"><?= $currency ?> <?= number_format($totalDue,2) ?></strong>
        </div>
        <div class="d-flex justify-content-between py-2">
          <span class="text-muted">Cancelación total (capital + interés)</span>
          <strong><?= $currency ?> <?= number_format($totalWithC,2) ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($daysLate > 0): ?>
<div class="alert alert-warning py-2 mb-3" style="font-size:.82rem">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Tipo C · Interés acumulado:</strong>
  Los montos en la tabla corresponden al <em>plan original</em>.
  El interés <strong>real a cobrar</strong> hoy es
  <strong class="text-danger"><?= $currency ?> <?= number_format($accumInt,2) ?></strong>
  (<?= $currency ?> <?= number_format($periodInt,2) ?> × <?= $periods ?> períodos).
</div>
<?php endif; ?>
<?php endif; ?>

<!-- TABLA DE AMORTIZACIÓN -->
<div class="card shadow-sm border-0">
  <?php if ($hasOverdue): ?>
  <div class="card-header bg-warning text-dark py-2" style="font-size:.8rem">
    <i class="bi bi-exclamation-triangle me-1"></i>
    <strong>Tipo C · Interés por período.</strong>
    La tabla muestra cada período con su fecha real de vencimiento y estado de pago.
  </div>
  <?php endif; ?>
  <div class="table-responsive">
    <table class="table table-striped table-bordered mb-0" style="font-size:.82rem">
      <thead>
        <tr>
          <th class="text-center">#</th>
          <th>Fecha Vence</th>
          <th class="text-end">Capital</th>
          <th class="text-end">Interés</th>
          <th class="text-end">Cuota Total</th>
          <th class="text-end">Saldo</th>
          <th class="text-center">Estado</th>
          <th class="text-end">Pagado</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $sMap = [
        'pending' => ['bg-secondary','Pendiente'],
        'partial' => ['bg-warning text-dark','Parcial'],
        'paid'    => ['bg-success','Pagado'],
        'overdue' => ['bg-danger','Vencida'],
      ];

      if ($isTypeC && $periodsElapsed > 0):
        // ── Filas virtuales por período para Tipo C ──────────────────────────
        $freqModMap2  = ['weekly'=>'+7 days','biweekly'=>'+15 days','monthly'=>'+1 month','bimonthly'=>'+2 months','quarterly'=>'+3 months','semiannual'=>'+6 months','annual'=>'+1 year'];
        $freqMod2     = $freqModMap2[$loan['payment_frequency'] ?? 'monthly'] ?? '+1 month';
        $curDate2     = new \DateTime($loan['first_payment_date'] ?? $loan['disbursement_date']);
        $paidLeft2    = $paidInt; // interés ya pagado
        $loanBalance  = (float)$loan['principal'];

        for ($p = 1; $p <= $periodsElapsed; $p++):
          $dueStr2   = $curDate2->format('Y-m-d');
          $periodPaid2   = 0;
          if ($paidLeft2 >= $periodInt) {
            $periodPaid2 = $periodInt;
            $paidLeft2  -= $periodInt;
          } elseif ($paidLeft2 > 0) {
            $periodPaid2 = round($paidLeft2, 2);
            $paidLeft2   = 0;
          }
          $isOD2   = $dueStr2 <= $today;
          $daysOD2 = $isOD2 ? (int)(new \DateTime($dueStr2))->diff(new \DateTime())->days : 0;
          $status2 = $periodPaid2 >= $periodInt ? 'paid' : ($periodPaid2 > 0 ? 'partial' : ($isOD2 ? 'overdue' : 'pending'));
          $rowCls2 = $status2 === 'paid' ? 'table-success' : ($status2 === 'overdue' ? 'table-danger' : ($status2 === 'partial' ? 'table-warning' : ''));
          [$bc2,$bl2] = $sMap[$status2] ?? ['bg-secondary','–'];
      ?>
      <tr class="<?= $rowCls2 ?>">
        <td class="text-center fw-semibold"><?= $p ?></td>
        <td>
          <?= date('d/m/Y', strtotime($dueStr2)) ?>
          <?php if ($isOD2 && $status2 !== 'paid' && $daysOD2 > 0): ?>
          <div style="font-size:.68rem;color:#dc2626"><?= $daysOD2 ?> días mora</div>
          <?php endif; ?>
        </td>
        <td class="text-end text-muted">–</td>
        <td class="text-end"><?= $currency ?> <?= number_format($periodInt, 2) ?></td>
        <td class="text-end fw-bold"><?= $currency ?> <?= number_format($periodInt, 2) ?></td>
        <td class="text-end"><?= $currency ?> <?= number_format($loanBalance, 2) ?></td>
        <td class="text-center">
          <span class="badge <?= $bc2 ?>"><?= $bl2 ?></span>
        </td>
        <td class="text-end <?= $periodPaid2 > 0 ? 'text-success' : 'text-muted' ?>">
          <?= $periodPaid2 > 0 ? $currency.' '.number_format($periodPaid2,2) : '–' ?>
        </td>
      </tr>
      <?php
          $curDate2->modify($freqMod2);
        endfor;

        // Fila próximo vencimiento
        if ($nextDueDate2 = $curDate2->format('Y-m-d')):
      ?>
      <tr style="background:#f0fdf4;border-top:2px solid #16a34a">
        <td class="text-center text-success fw-semibold"><?= $periodsElapsed + 1 ?></td>
        <td>
          <strong class="text-success"><?= date('d/m/Y', strtotime($nextDueDate2)) ?></strong>
          <div style="font-size:.7rem" class="text-success"><i class="bi bi-clock me-1"></i>Próximo vencimiento</div>
        </td>
        <td class="text-end text-muted">–</td>
        <td class="text-end text-success"><?= $currency ?> <?= number_format($periodInt,2) ?></td>
        <td class="text-end text-success fw-bold"><?= $currency ?> <?= number_format($periodInt,2) ?></td>
        <td class="text-end"><?= $currency ?> <?= number_format($loanBalance,2) ?></td>
        <td class="text-center"><span class="badge bg-success bg-opacity-75">Próxima</span></td>
        <td class="text-end text-muted">–</td>
      </tr>
      <?php endif; ?>

      <?php else:
        // ── Tipo A / B o Tipo C sin mora: tabla normal del DB ────────────────
        foreach ($installments as $inst):
          $isOD   = $inst['due_date'] < $today && in_array($inst['status'],['pending','partial']);
          $rowCls = $isOD ? 'table-danger' : ($inst['status']==='paid' ? 'table-success' : '');
          $sk     = $isOD ? 'overdue' : $inst['status'];
          [$bc,$bl] = $sMap[$sk] ?? ['bg-secondary','–'];
          $daysLateRow = $isOD ? (int)(new \DateTime($inst['due_date']))->diff(new \DateTime())->days : 0;
      ?>
      <tr class="<?= $rowCls ?>">
        <td class="text-center fw-semibold"><?= $inst['installment_number'] ?></td>
        <td>
          <?= date('d/m/Y',strtotime($inst['due_date'])) ?>
          <?php if ($isOD): ?>
          <div style="font-size:.68rem;color:#dc2626"><?= $daysLateRow ?> días mora</div>
          <?php endif; ?>
        </td>
        <td class="text-end"><?= $currency ?> <?= number_format($inst['principal_amount'],2) ?></td>
        <td class="text-end"><?= $currency ?> <?= number_format($inst['interest_amount'],2) ?></td>
        <td class="text-end fw-bold"><?= $currency ?> <?= number_format($inst['total_amount'],2) ?></td>
        <td class="text-end"><?= $currency ?> <?= number_format($inst['balance_after'],2) ?></td>
        <td class="text-center">
          <span class="badge <?= $bc ?>"><?= $bl ?></span>
        </td>
        <td class="text-end text-success">
          <?= $inst['paid_amount']>0 ? $currency.' '.number_format($inst['paid_amount'],2) : '–' ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="fw-bold" style="background:#1e293b;color:#fff">
          <td colspan="2" class="text-center">TOTALES</td>
          <td class="text-end"><?= $currency ?> <?= number_format($loan['balance'],2) ?></td>
          <td class="text-end"><?= $isTypeC ? $currency.' '.number_format($totIntReal,2) : $currency.' '.number_format($totIntDB,2) ?></td>
          <td class="text-end"><?= $isTypeC ? $currency.' '.number_format($totPayReal,2) : $currency.' '.number_format($totPayDB,2) ?></td>
          <td colspan="3"></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<div class="text-muted mt-3 no-print" style="font-size:.75rem">
  * Esta tabla es de carácter informativo. Los montos pueden variar por pagos anticipados, parciales o mora.
  <?php if ($isTypeC): ?>
  Para Tipo C el interés mostrado en el plan es el de <em>un período</em>.
  Si hay mora, el interés acumulado real se indica arriba y en las filas vencidas.
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>