<?php $currency = setting('app_currency','L'); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= url('/reports/general') ?>">Reportes</a></li>
        <li class="breadcrumb-item active">Reporte Cliente</li>
      </ol>
    </nav>
  </div>
  <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-printer me-1"></i>Imprimir
  </button>
</div>

<!-- CLIENT HEADER -->
<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <div class="row align-items-center">
      <div class="col-auto">
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4"
             style="width:64px;height:64px">
          <?= strtoupper(substr($client['first_name'],0,1).substr($client['last_name'],0,1)) ?>
        </div>
      </div>
      <div class="col">
        <h5 class="fw-bold mb-0"><?= htmlspecialchars($client['full_name']) ?></h5>
        <div class="text-muted small"><?= htmlspecialchars($client['code']) ?> · <?= htmlspecialchars($client['identity_number']??'Sin DNI') ?></div>
        <div class="small"><?= htmlspecialchars($client['phone']??'') ?> · <?= htmlspecialchars($client['email']??'') ?></div>
      </div>
      <div class="col-auto text-end">
        <?php $kpis = [
          ['Saldo Total',   $currency.' '.number_format($totalBalance,2),  'text-danger'],
          ['Total Pagado',  $currency.' '.number_format($totalPaid,2),     'text-success'],
          ['Mora Pendiente',$currency.' '.number_format($totalOverdue,2),  'text-danger fw-bold'],
        ]; ?>
        <?php foreach ($kpis as [$lbl,$val,$cls]): ?>
        <div><span class="text-muted small"><?= $lbl ?>: </span><strong class="<?= $cls ?>"><?= $val ?></strong></div>
        <?php endforeach; ?>
        <?php if ($daysInDefault > 0): ?>
        <div class="badge bg-danger mt-1"><?= $daysInDefault ?> días en mora</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- LOANS -->
<div class="card shadow-sm border-0 mb-3">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-cash-coin me-2 text-primary"></i>Historial de Préstamos (<?= count($loans) ?>)
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-custom table-hover mb-0" style="font-size:.82rem">
        <thead><tr>
          <th>Número</th><th class="text-center">Tipo</th>
          <th class="text-end">Principal</th><th class="text-end">Pagado</th>
          <th class="text-end">Saldo</th><th class="text-end">Interés</th>
          <th class="text-center">Estado</th><th>Fecha</th>
        </tr></thead>
        <tbody>
        <?php foreach ($loans as $l):
          $sc = ['active'=>'bg-success','paid'=>'bg-primary','defaulted'=>'bg-danger','cancelled'=>'bg-secondary'][$l['status']]??'bg-secondary';
          $sl = ['active'=>'Activo','paid'=>'Pagado','defaulted'=>'Moroso','cancelled'=>'Cancelado'][$l['status']]??$l['status'];
        ?>
        <tr>
          <td><a href="<?= url('/loans/'.$l['id']) ?>"><?= htmlspecialchars($l['loan_number']) ?></a></td>
          <td class="text-center"><span class="badge bg-info text-dark">Tipo <?= $l['loan_type'] ?></span></td>
          <td class="text-end"><?= $currency ?> <?= number_format($l['principal'],2) ?></td>
          <td class="text-end text-success"><?= $currency ?> <?= number_format($l['total_paid'],2) ?></td>
          <td class="text-end <?= $l['balance']>0?'text-danger fw-semibold':'' ?>"><?= $currency ?> <?= number_format($l['balance'],2) ?></td>
          <td class="text-end"><?= $currency ?> <?= number_format($l['total_interest_paid'],2) ?></td>
          <td class="text-center"><span class="badge <?= $sc ?>"><?= $sl ?></span></td>
          <td><?= date('d/m/Y',strtotime($l['disbursement_date'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- OVERDUE -->
<?php if (!empty($overdueInstallments)): ?>
<div class="card shadow-sm border-danger mb-3" style="border-left:4px solid #dc2626">
  <div class="card-header bg-danger text-white fw-semibold py-2">
    <i class="bi bi-exclamation-octagon me-2"></i>Cuotas Vencidas (<?= count($overdueInstallments) ?>)
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-custom mb-0 table-danger" style="font-size:.82rem">
        <thead><tr><th>Préstamo</th><th>Vencía</th><th class="text-center">Días Mora</th><th class="text-end">Pendiente</th></tr></thead>
        <tbody>
        <?php foreach ($overdueInstallments as $i):
          $daysLate = (int)(new \DateTime($i['due_date']))->diff(new \DateTime())->days;
        ?>
        <tr>
          <td><?= htmlspecialchars($i['loan_number']) ?></td>
          <td><?= date('d/m/Y',strtotime($i['due_date'])) ?></td>
          <td class="text-center"><span class="badge bg-danger"><?= $daysLate ?> días</span></td>
          <td class="text-end fw-bold text-danger"><?= $currency ?> <?= number_format($i['total_amount']-$i['paid_amount'],2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- PAYMENTS -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-receipt me-2 text-success"></i>Historial de Pagos (<?= count($payments) ?>)
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-custom table-hover mb-0" style="font-size:.82rem">
        <thead><tr><th>Número</th><th>Préstamo</th><th>Fecha</th><th>Método</th><th class="text-end">Monto</th><th>Registró</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><a href="<?= url('/payments/'.$p['id']) ?>"><?= htmlspecialchars($p['payment_number']) ?></a></td>
          <td><?= htmlspecialchars($p['loan_number']) ?></td>
          <td><?= date('d/m/Y',strtotime($p['payment_date'])) ?></td>
          <td><span class="badge bg-secondary"><?= $p['payment_method'] ?></span></td>
          <td class="text-end text-success fw-semibold"><?= $currency ?> <?= number_format($p['total_received'],2) ?></td>
          <td><?= htmlspecialchars($p['registered_by_name']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($payments)): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">Sin pagos registrados.</td></tr>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($payments)): ?>
        <tfoot><tr class="fw-bold bg-light">
          <td colspan="4">TOTAL PAGADO</td>
          <td class="text-end text-success"><?= $currency ?> <?= number_format($totalPaid,2) ?></td>
          <td></td>
        </tr></tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>
