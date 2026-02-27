<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tabla de Amortización – <?= htmlspecialchars($loan['loan_number']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    @media print { .no-print { display:none!important; } body { font-size:11px; } }
    body { font-family:'Inter',system-ui,sans-serif; background:#f8fafc; }
    .table th { background:#1e293b; color:#fff; font-size:.75rem; text-transform:uppercase; }
    .table-striped>tbody>tr:nth-of-type(odd)>* { background-color:#f1f5f9; }
    .header-card { background:linear-gradient(135deg,#1e293b,#2563eb); color:#fff; border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
  </style>
</head>
<body class="p-4">

<?php
$currency = setting('app_currency','L');
$typeMap  = ['A'=>'Cuota Nivelada','B'=>'Variable','C'=>'Interés Simple Mensual'];
$totCap   = array_sum(array_column($installments,'principal_amount'));
$totInt   = array_sum(array_column($installments,'interest_amount'));
$totPay   = array_sum(array_column($installments,'total_amount'));
?>

<div class="header-card">
  <div class="row">
    <div class="col">
      <h4 class="fw-bold mb-1"><?= setting('app_name','SistemaPréstamos') ?></h4>
      <div>Tabla de Amortización · <?= htmlspecialchars($loan['loan_number']) ?></div>
      <div style="opacity:.7;font-size:.85rem">Generado: <?= date('d/m/Y H:i') ?></div>
    </div>
    <div class="col text-end">
      <button onclick="window.print()" class="btn btn-light btn-sm no-print">
        <i class="bi bi-printer me-1"></i>Imprimir
      </button>
      <a href="<?= url('/loans/'.$loan['id']) ?>" class="btn btn-outline-light btn-sm ms-2 no-print">← Volver</a>
    </div>
  </div>
</div>

<!-- LOAN INFO -->
<div class="row g-3 mb-4 no-print">
  <?php $infoItems = [
    ['Cliente',     $loan['client_name']],
    ['Tipo',        'Tipo '.$loan['loan_type'].' – '.($typeMap[$loan['loan_type']]??'')],
    ['Principal',   $currency.' '.number_format($loan['principal'],2)],
    ['Tasa',        number_format($loan['interest_rate']*100,2).'% / '.($loan['rate_type']==='annual'?'año':'mes')],
    ['Plazo',       ($loan['term_months']??'-').' meses'],
    ['Desembolso',  date('d/m/Y',strtotime($loan['disbursement_date']))],
  ]; ?>
  <?php foreach ($infoItems as [$label,$val]): ?>
  <div class="col-6 col-md-2">
    <div class="card border-0 shadow-sm p-2 text-center h-100">
      <div class="text-muted small"><?= $label ?></div>
      <div class="fw-semibold" style="font-size:.85rem"><?= htmlspecialchars((string)$val) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- AMORTIZATION TABLE -->
<div class="card shadow-sm border-0">
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
      $today = date('Y-m-d');
      foreach ($installments as $inst):
        $isOD = $inst['due_date'] < $today && in_array($inst['status'],['pending','partial']);
        $rowCls = $isOD?'table-danger':($inst['status']==='paid'?'table-success':'');
      ?>
      <tr class="<?= $rowCls ?>">
        <td class="text-center fw-semibold"><?= $inst['installment_number'] ?></td>
        <td><?= date('d/m/Y',strtotime($inst['due_date'])) ?></td>
        <td class="text-end"><?= $currency ?> <?= number_format($inst['principal_amount'],2) ?></td>
        <td class="text-end"><?= $currency ?> <?= number_format($inst['interest_amount'],2) ?></td>
        <td class="text-end fw-bold"><?= $currency ?> <?= number_format($inst['total_amount'],2) ?></td>
        <td class="text-end"><?= $currency ?> <?= number_format($inst['balance_after'],2) ?></td>
        <td class="text-center">
          <?php
          $sMap = ['pending'=>['bg-secondary','Pendiente'],'partial'=>['bg-warning text-dark','Parcial'],
                   'paid'=>['bg-success','Pagado'],'overdue'=>['bg-danger','Vencida']];
          $sk = $isOD ? 'overdue' : $inst['status'];
          [$bc,$bl] = $sMap[$sk] ?? ['bg-secondary','–'];
          ?>
          <span class="badge <?= $bc ?>"><?= $bl ?></span>
        </td>
        <td class="text-end text-success">
          <?= $inst['paid_amount']>0 ? $currency.' '.number_format($inst['paid_amount'],2) : '–' ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="fw-bold" style="background:#1e293b;color:#fff">
          <td colspan="2" class="text-center">TOTALES</td>
          <td class="text-end"><?= $currency ?> <?= number_format($totCap,2) ?></td>
          <td class="text-end"><?= $currency ?> <?= number_format($totInt,2) ?></td>
          <td class="text-end"><?= $currency ?> <?= number_format($totPay,2) ?></td>
          <td colspan="3"></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<div class="text-muted mt-3 no-print" style="font-size:.75rem">
  * Esta tabla es de carácter informativo. Los montos pueden variar por pagos anticipados, parciales o mora.
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"></script>
</body>
</html>
