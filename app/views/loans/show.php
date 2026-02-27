<?php
$currency = setting('app_currency','L');
$typeMap  = ['A'=>'Nivelada','B'=>'Variable','C'=>'Simple Mensual'];
$statusMap= ['active'=>['success','Activo'],'paid'=>['primary','Pagado'],
             'defaulted'=>['danger','Moroso'],'cancelled'=>['secondary','Cancelado'],
             'restructured'=>['warning','Restructurado']];
[$sc, $sl] = $statusMap[$loan['status']] ?? ['secondary','Otro'];
$isAdmin   = \App\Core\Auth::isAdmin();
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
  <!-- LEFT: Loan Info -->
  <div class="col-xl-4">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="rounded bg-primary bg-opacity-10 p-2"><i class="bi bi-cash-coin text-primary fs-5"></i></div>
          <div>
            <div class="fw-bold"><?= htmlspecialchars($loan['loan_number']) ?></div>
            <span class="badge bg-info text-dark">Tipo <?= $loan['loan_type'] ?> · <?= $typeMap[$loan['loan_type']] ?></span>
            <span class="badge bg-<?= $sc ?> ms-1"><?= $sl ?></span>
          </div>
        </div>

        <?php $rows = [
          ['Cliente',       '<a href="'.url('/clients/'.$loan['client_id']).'">'.htmlspecialchars($loan['client_name']).'</a>'],
          ['Monto Original',$currency . ' ' . number_format($loan['principal'], 2)],
          ['Saldo Actual',  '<strong class="text-'.($loan['balance']>0?'danger':'success').'">'.$currency.' '.number_format($loan['balance'],2).'</strong>'],
          ['Tasa Interés',  number_format($loan['interest_rate']*100,2).'% / '.($loan['rate_type']==='annual'?'año':'mes')],
          ['Tasa Mora',     number_format($loan['late_fee_rate']*100,2).'% / mes'],
          ['Plazo',         $loan['term_months'] ? $loan['term_months'].' meses' : 'Variable'],
          ['Desembolso',    date('d/m/Y',strtotime($loan['disbursement_date']))],
          ['Primer Pago',   $loan['first_payment_date'] ? date('d/m/Y',strtotime($loan['first_payment_date'])) : '-'],
          ['Vencimiento',   $loan['maturity_date'] ? date('d/m/Y',strtotime($loan['maturity_date'])) : '-'],
          ['Asesor',        htmlspecialchars($loan['assigned_name'] ?? 'Sin asignar')],
          ['Total Cobrado', $currency.' '.number_format($loan['total_paid'],2)],
          ['Días de Gracia',$loan['grace_days'].' días'],
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

    <!-- Current state card -->
    <?php if ($loan['status'] === 'active'): ?>
    <div class="card shadow-sm border-0 mt-3">
      <div class="card-header bg-white fw-semibold py-2 border-bottom">
        <i class="bi bi-calculator me-2 text-warning"></i>Estado Actual
      </div>
      <div class="card-body py-2">
        <?php foreach ($currentState as $k => $v): ?>
        <?php if (is_numeric($v)): ?>
        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:.85rem">
          <span class="text-muted"><?= ucfirst(str_replace('_',' ',$k)) ?></span>
          <strong><?= (str_contains($k,'fee')||str_contains($k,'interest')||str_contains($k,'balance')||str_contains($k,'due')) ? $currency.' '.number_format($v,2) : $v ?></strong>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT: Tabs -->
  <div class="col-xl-8">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-bottom">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-installments">
              <i class="bi bi-calendar3 me-1"></i>Cuotas (<?= count($installments) ?>)
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

          <!-- INSTALLMENTS TAB -->
          <div class="tab-pane fade show active" id="tab-installments">
            <div class="table-responsive">
              <table class="table table-custom table-hover mb-0">
                <thead><tr>
                  <th>#</th><th>Vencimiento</th>
                  <th class="text-end">Capital</th><th class="text-end">Interés</th>
                  <th class="text-end">Cuota</th><th class="text-end">Pagado</th>
                  <th class="text-end">Saldo</th><th class="text-center">Estado</th>
                </tr></thead>
                <tbody>
                <?php
                $today = date('Y-m-d');
                $statClass = ['pending'=>'bg-secondary','partial'=>'bg-warning text-dark',
                              'paid'=>'bg-success','overdue'=>'bg-danger'];
                $statLabel = ['pending'=>'Pendiente','partial'=>'Parcial',
                              'paid'=>'Pagado','overdue'=>'Vencida'];
                ?>
                <?php foreach ($installments as $inst): ?>
                <?php
                  $isOD = $inst['due_date'] < $today && in_array($inst['status'],['pending','partial']);
                  $rowClass = $isOD ? 'table-danger' : ($inst['status'] === 'paid' ? 'table-success' : '');
                ?>
                <tr class="<?= $rowClass ?>">
                  <td><?= $inst['installment_number'] ?></td>
                  <td><?= date('d/m/Y',strtotime($inst['due_date'])) ?></td>
                  <td class="text-end"><?= $currency ?> <?= number_format($inst['principal_amount'],2) ?></td>
                  <td class="text-end"><?= $currency ?> <?= number_format($inst['interest_amount'],2) ?></td>
                  <td class="text-end fw-semibold"><?= $currency ?> <?= number_format($inst['total_amount'],2) ?></td>
                  <td class="text-end text-success"><?= $inst['paid_amount'] > 0 ? $currency.' '.number_format($inst['paid_amount'],2) : '-' ?></td>
                  <td class="text-end"><?= $currency ?> <?= number_format($inst['balance_after'],2) ?></td>
                  <td class="text-center">
                    <span class="badge <?= $statClass[$isOD ? 'overdue' : $inst['status']] ?? 'bg-secondary' ?>">
                      <?= $statLabel[$isOD ? 'overdue' : $inst['status']] ?? '-' ?>
                    </span>
                    <?php if ($isOD): ?>
                    <div style="font-size:.7rem;color:#dc2626"><?= $inst['days_late'] ?> días mora</div>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($installments)): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">Sin cuotas registradas.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- PAYMENTS TAB -->
          <div class="tab-pane fade" id="tab-payments">
            <div class="table-responsive">
              <table class="table table-custom table-hover mb-0">
                <thead><tr>
                  <th>Fecha</th><th>Recibo</th><th>Método</th>
                  <th class="text-end">Monto</th><th>Registró</th>
                  <?php if ($isAdmin): ?><th></th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                  <td><?= date('d/m/Y',strtotime($p['payment_date'])) ?></td>
                  <td>
                    <a href="<?= url('/payments/'.$p['id']) ?>" class="text-decoration-none">
                      <?= htmlspecialchars($p['payment_number']) ?>
                    </a>
                  </td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($p['payment_method']) ?></span></td>
                  <td class="text-end fw-semibold text-success"><?= $currency ?> <?= number_format($p['total_received'],2) ?></td>
                  <td style="font-size:.8rem"><?= htmlspecialchars($p['registered_by_name']) ?></td>
                  <?php if ($isAdmin): ?>
                  <td>
                    <a href="#" onclick="confirmAction('<?= url('/payments/'.$p['id'].'/void') ?>','¿Anular pago?','Esta acción no se puede deshacer.','warning')"
                       class="btn btn-sm btn-outline-danger py-0">Anular</a>
                  </td>
                  <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Sin pagos registrados.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div><!-- /tab-content -->
      </div>
    </div>
  </div>
</div>
