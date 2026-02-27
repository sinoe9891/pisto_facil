<?php $currency = setting('app_currency','L'); ?>

<div class="mb-3">
  <h5 class="fw-bold mb-0">Portal del Cliente</h5>
  <small class="text-muted">Bienvenido, <?= htmlspecialchars($auth['name'] ?? '') ?></small>
</div>

<?php if (!$client): ?>
<div class="card shadow-sm border-0">
  <div class="card-body text-center py-5">
    <i class="bi bi-person-exclamation text-muted fs-1 d-block mb-3"></i>
    <h5>No hay un perfil de cliente vinculado a su cuenta.</h5>
    <p class="text-muted">Contacte al administrador para que le asigne un perfil de cliente.</p>
  </div>
</div>
<?php return; endif; ?>

<!-- KPI CARDS -->
<div class="row g-3 mb-4">
  <?php
  $totalBalance   = array_sum(array_column($loans, 'balance'));
  $activeCount    = count(array_filter($loans, fn($l) => $l['status'] === 'active'));
  $nextInst       = $upcoming[0] ?? null;
  ?>
  <div class="col-sm-6 col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body text-center p-3">
        <i class="bi bi-cash-coin text-primary fs-3 d-block mb-1"></i>
        <div class="fw-bold fs-4"><?= $activeCount ?></div>
        <div class="text-muted small">Préstamos activos</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body text-center p-3">
        <i class="bi bi-bank text-danger fs-3 d-block mb-1"></i>
        <div class="fw-bold fs-4 text-danger"><?= $currency ?> <?= number_format($totalBalance,2) ?></div>
        <div class="text-muted small">Saldo total</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body text-center p-3">
        <i class="bi bi-calendar-check text-warning fs-3 d-block mb-1"></i>
        <div class="fw-bold fs-4">
          <?= $nextInst ? date('d/m/Y',strtotime($nextInst['due_date'])) : '–' ?>
        </div>
        <div class="text-muted small">Próxima cuota</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body text-center p-3">
        <i class="bi bi-wallet2 text-success fs-3 d-block mb-1"></i>
        <div class="fw-bold fs-4">
          <?= $nextInst ? $currency.' '.number_format($nextInst['total_amount']-$nextInst['paid_amount'],2) : '–' ?>
        </div>
        <div class="text-muted small">Monto próxima cuota</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- LOANS -->
  <div class="col-md-7">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white fw-semibold py-2 border-bottom">
        <i class="bi bi-cash-coin me-2 text-primary"></i>Mis Préstamos
      </div>
      <div class="card-body p-0">
        <?php foreach ($loans as $l):
          $sc = ['active'=>'bg-success','paid'=>'bg-primary','defaulted'=>'bg-danger','cancelled'=>'bg-secondary'][$l['status']]??'bg-secondary';
          $sl = ['active'=>'Activo','paid'=>'Pagado','defaulted'=>'Moroso','cancelled'=>'Cancelado'][$l['status']]??$l['status'];
        ?>
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($l['loan_number']) ?></div>
            <div class="text-muted small">
              Tipo <?= $l['loan_type'] ?> ·
              <?= $currency ?> <?= number_format($l['principal'],2) ?> ·
              <?= number_format($l['interest_rate']*100,0) ?>%/mes ·
              <?= date('d/m/Y',strtotime($l['disbursement_date'])) ?>
            </div>
          </div>
          <div class="text-end">
            <div class="fw-bold text-danger"><?= $currency ?> <?= number_format($l['balance'],2) ?></div>
            <span class="badge <?= $sc ?>"><?= $sl ?></span>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($loans)): ?>
        <div class="text-center text-muted py-4">No tiene préstamos registrados.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- UPCOMING + DOCS -->
  <div class="col-md-5">
    <!-- Upcoming installments -->
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-header bg-warning text-dark fw-semibold py-2">
        <i class="bi bi-calendar3 me-2"></i>Próximas Cuotas
      </div>
      <div class="card-body p-0">
        <?php foreach (array_slice($upcoming,0,5) as $u):
          $isOD = $u['due_date'] < date('Y-m-d');
          $pend = $u['total_amount'] - $u['paid_amount'];
        ?>
        <div class="p-2 border-bottom d-flex justify-content-between align-items-center" style="font-size:.85rem">
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($u['loan_number']) ?></div>
            <div class="<?= $isOD?'text-danger':'text-muted' ?>"><?= date('d/m/Y',strtotime($u['due_date'])) ?></div>
          </div>
          <div class="fw-bold <?= $isOD?'text-danger':'' ?>">
            <?= $currency ?> <?= number_format($pend,2) ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($upcoming)): ?>
        <div class="text-center text-muted py-3 small">Sin cuotas próximas.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Documents -->
    <?php if (!empty($documents)): ?>
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white fw-semibold py-2 border-bottom">
        <i class="bi bi-folder2 me-2 text-warning"></i>Mis Documentos
      </div>
      <div class="card-body p-2">
        <?php foreach ($documents as $doc): ?>
        <div class="d-flex align-items-center gap-2 p-2 border rounded mb-1" style="font-size:.82rem">
          <i class="bi <?= str_contains($doc['mime_type'],'pdf')?'bi-file-pdf text-danger':'bi-file-image text-info' ?> fs-5"></i>
          <div class="flex-grow-1 overflow-hidden">
            <div class="text-truncate"><?= htmlspecialchars($doc['original_name']) ?></div>
            <div class="text-muted"><?= \App\Services\DocumentService::docTypeLabel($doc['doc_type']) ?></div>
          </div>
          <a href="<?= url('/clients/'.$client['id'].'/doc/'.$doc['id'].'/download') ?>"
             class="btn btn-sm btn-outline-secondary py-0">
            <i class="bi bi-download"></i>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
