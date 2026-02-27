<?php $currency = setting('app_currency','L'); ?>

<div class="row justify-content-center">
<div class="col-xl-9">

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= url('/loans') ?>">Préstamos</a></li>
    <li class="breadcrumb-item active">Nuevo Préstamo</li>
  </ol>
</nav>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white fw-semibold py-2 border-bottom">
    <i class="bi bi-cash-coin me-2 text-primary"></i>Nuevo Préstamo
  </div>
  <div class="card-body">
    <form method="POST" action="<?= url('/loans/store') ?>" id="loanForm">
      <?= \App\Core\CSRF::field() ?>

      <!-- STEP INDICATOR -->
      <div class="d-flex gap-0 mb-4 border-bottom pb-3">
        <?php foreach (['Tipo y Cliente','Condiciones','Configuración'] as $i => $step): ?>
        <div class="flex-fill text-center">
          <div class="fw-semibold" style="font-size:.8rem">
            <span class="badge rounded-pill <?= $i === 0 ? 'bg-primary' : 'bg-light text-muted' ?> me-1 step-badge" data-step="<?= $i ?>">
              <?= $i+1 ?>
            </span>
            <?= $step ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- STEP 1: Tipo y Cliente -->
      <div id="step-0">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Cliente <span class="text-danger">*</span></label>
            <select name="client_id" id="client_id" class="form-select" required>
              <option value="">-- Seleccionar cliente --</option>
              <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($client && $client['id'] == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['code'] . ' · ' . $c['full_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Asesor Asignado</label>
            <select name="assigned_to" class="form-select">
              <option value="">-- Sin asignar --</option>
              <?php foreach ($advisors as $a): ?>
              <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Tipo de Préstamo <span class="text-danger">*</span></label>
          <div class="row g-2">
            <?php $types = [
              'A' => ['Cuota Nivelada','Amortización fija mensual. Cuota constante con capital+interés.','bi-bar-chart-steps','primary'],
              'B' => ['Cuota Variable','Pagos parciales por días. Interés calculado por días transcurridos.','bi-graph-up-arrow','info'],
              'C' => ['Interés Simple Mensual','El cliente paga solo interés mensual. Capital se abona voluntariamente.','bi-calculator','warning'],
            ]; ?>
            <?php foreach ($types as $t => [$name, $desc, $icon, $color]): ?>
            <div class="col-md-4">
              <label class="cursor-pointer">
                <input type="radio" name="loan_type" value="<?= $t ?>" class="visually-hidden loan-type-radio" <?= $t === 'A' ? 'checked' : '' ?>>
                <div class="card border-2 h-100 type-card <?= $t === 'A' ? 'border-primary' : '' ?>" data-type="<?= $t ?>">
                  <div class="card-body p-3 text-center">
                    <i class="bi <?= $icon ?> fs-3 text-<?= $color ?>"></i>
                    <div class="fw-bold mt-1">Tipo <?= $t ?></div>
                    <div class="fw-semibold" style="font-size:.85rem"><?= $name ?></div>
                    <small class="text-muted"><?= $desc ?></small>
                  </div>
                </div>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="text-end">
          <button type="button" class="btn btn-primary" onclick="goStep(1)">
            Siguiente <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </div>
      </div>

      <!-- STEP 2: Condiciones Financieras -->
      <div id="step-1" class="d-none">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Monto a Prestar (<?= $currency ?>) <span class="text-danger">*</span></label>
            <input type="number" name="principal" id="principal" class="form-control" min="1" step="0.01" required
                   placeholder="Ej: 10000">
          </div>
          <div class="col-md-3">
            <label class="form-label">Tasa de Interés (%) <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" name="interest_rate" id="interest_rate" class="form-control"
                     min="0" step="0.01" required placeholder="Ej: 20">
              <span class="input-group-text" id="rate_type_label">%/mes</span>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo de Tasa</label>
            <select name="rate_type" id="rate_type" class="form-select">
              <option value="monthly">Mensual</option>
              <option value="annual">Anual</option>
            </select>
          </div>
          <div class="col-md-2" id="term_col">
            <label class="form-label">Plazo (meses)</label>
            <input type="number" name="term_months" id="term_months" class="form-control"
                   min="1" max="360" placeholder="Ej: 12">
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha Desembolso</label>
            <input type="date" name="disbursement_date" class="form-control"
                   value="<?= $defaults['disbursement_date'] ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha Primer Pago</label>
            <input type="date" name="first_payment_date" class="form-control"
                   value="<?= $defaults['first_payment_date'] ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Aplicar Pago a</label>
            <select name="apply_payment_to" class="form-select">
              <option value="interest_first">Interés primero, luego capital</option>
              <option value="capital">Capital primero</option>
            </select>
          </div>
        </div>

        <!-- LIVE PREVIEW -->
        <div id="loanPreview" class="mt-3 p-3 bg-light rounded d-none">
          <div class="row text-center g-2">
            <div class="col"><div class="fw-bold text-primary" id="prev_payment">-</div><div class="text-muted small">Cuota mensual</div></div>
            <div class="col"><div class="fw-bold text-danger" id="prev_interest">-</div><div class="text-muted small">Total intereses</div></div>
            <div class="col"><div class="fw-bold text-success" id="prev_total">-</div><div class="text-muted small">Total a pagar</div></div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="button" class="btn btn-outline-secondary" onclick="goStep(0)"><i class="bi bi-arrow-left me-1"></i>Anterior</button>
          <button type="button" class="btn btn-primary" onclick="goStep(2)">Siguiente <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
      </div>

      <!-- STEP 3: Configuración adicional -->
      <div id="step-2" class="d-none">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Tasa Moratoria (%/mes)</label>
            <input type="number" name="late_fee_rate" class="form-control" step="0.01" min="0" max="100"
                   value="<?= number_format($defaults['late_fee_rate'] * 100, 2) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Días de Gracia</label>
            <input type="number" name="grace_days" class="form-control" min="0" max="30"
                   value="<?= $defaults['grace_days'] ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Notas del Préstamo</label>
            <textarea name="notes" class="form-control" rows="3" maxlength="1000"
                      placeholder="Garantías, condiciones especiales, observaciones..."></textarea>
          </div>
        </div>

        <!-- RESUMEN -->
        <div class="alert alert-info mt-3 mb-0" id="finalSummary">
          <strong>Resumen:</strong> Llene los campos y el resumen aparecerá aquí.
        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="button" class="btn btn-outline-secondary" onclick="goStep(1)"><i class="bi bi-arrow-left me-1"></i>Anterior</button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check-lg me-1"></i>Crear Préstamo
          </button>
        </div>
      </div>

    </form>
  </div>
</div>
</div><!-- /col -->
</div><!-- /row -->

<script>
const CUR = '<?= $currency ?>';

function goStep(n) {
  document.querySelectorAll('[id^="step-"]').forEach((el, i) => {
    el.classList.toggle('d-none', i !== n);
  });
  document.querySelectorAll('.step-badge').forEach((el, i) => {
    el.classList.toggle('bg-primary', i <= n);
    el.classList.toggle('bg-light', i > n);
    el.classList.toggle('text-muted', i > n);
    el.classList.toggle('text-white', i <= n);
  });
  if (n === 2) updateSummary();
}

// Loan type card selection
document.querySelectorAll('.loan-type-radio').forEach(r => {
  r.addEventListener('change', () => {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('border-primary'));
    document.querySelector('.type-card[data-type="'+r.value+'"]').classList.add('border-primary');
    document.getElementById('term_col').style.display = r.value === 'A' ? '' : 'none';
    document.getElementById('rate_type').closest('.col-md-3').style.display = r.value === 'A' ? '' : 'none';
  });
});

document.getElementById('rate_type').addEventListener('change', function() {
  document.getElementById('rate_type_label').textContent = this.value === 'annual' ? '%/año' : '%/mes';
  calcPreview();
});

['principal','interest_rate','term_months'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', calcPreview);
});

function calcPreview() {
  const type    = document.querySelector('input[name="loan_type"]:checked')?.value;
  const P       = parseFloat(document.getElementById('principal').value) || 0;
  const rate    = parseFloat(document.getElementById('interest_rate').value) || 0;
  const n       = parseInt(document.getElementById('term_months').value) || 0;
  const rateType = document.getElementById('rate_type').value;
  const r = (rateType === 'annual' ? rate / 12 : rate) / 100;

  if (P <= 0 || rate <= 0) { document.getElementById('loanPreview').classList.add('d-none'); return; }

  let payment = 0, totalInt = 0, totalPay = 0;

  if (type === 'A' && n > 0) {
    payment   = r === 0 ? P/n : P * (r * Math.pow(1+r,n)) / (Math.pow(1+r,n)-1);
    totalPay  = payment * n;
    totalInt  = totalPay - P;
  } else if (type === 'C') {
    payment   = P * r;
    totalInt  = payment * (n || 12);
    totalPay  = P + totalInt;
  } else if (type === 'B') {
    payment   = P * r;
    totalInt  = 0;
    totalPay  = P;
  }

  document.getElementById('prev_payment').textContent = CUR + ' ' + payment.toFixed(2);
  document.getElementById('prev_interest').textContent = CUR + ' ' + totalInt.toFixed(2);
  document.getElementById('prev_total').textContent = CUR + ' ' + totalPay.toFixed(2);
  document.getElementById('loanPreview').classList.remove('d-none');
}

function updateSummary() {
  const client = document.getElementById('client_id');
  const P      = document.getElementById('principal').value;
  const rate   = document.getElementById('interest_rate').value;
  const type   = document.querySelector('input[name="loan_type"]:checked')?.value;
  const names  = {'A':'Cuota Nivelada','B':'Variable','C':'Simple Mensual'};
  const txt    = `Préstamo Tipo ${type} (${names[type]}) · ${CUR} ${parseFloat(P||0).toFixed(2)} · ${rate}%`;
  document.getElementById('finalSummary').innerHTML = '<strong>Resumen:</strong> ' + txt;
}
</script>
