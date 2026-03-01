<?php $currency = setting('app_currency','L'); ?>

<div class="row justify-content-center">
<div class="col-xl-10">

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
    <form method="POST" action="<?= url('/loans/store') ?>" id="loanForm" novalidate>
      <?= \App\Core\CSRF::field() ?>

      <!-- STEP INDICATOR -->
      <div class="d-flex mb-4 border-bottom pb-3">
        <?php foreach (['Tipo y Cliente','Condiciones Financieras','Configuración'] as $i => $step): ?>
        <div class="flex-fill text-center">
          <div class="fw-semibold" style="font-size:.8rem">
            <span class="badge rounded-pill bg-primary text-white me-1 step-badge" data-step="<?= $i ?>"><?= $i+1 ?></span>
            <?= $step ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- ========== STEP 1: TIPO Y CLIENTE ========== -->
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

        <!-- Tipo de préstamo -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Tipo de Préstamo <span class="text-danger">*</span></label>
          <div class="row g-2">
            <?php $types = [
              'A' => ['Cuota Nivelada','Cuota fija = Capital + Interés cada período. Ideal para préstamos de consumo o vehicular.','bi-bar-chart-steps','primary','Cada cuota es igual. El cliente siempre sabe cuánto paga.'],
              'B' => ['Cuota Variable','Interés por días transcurridos. Sin calendario fijo. Ideal para comerciantes.','bi-graph-up-arrow','info','Flexible. El cliente paga cuando puede y el interés es proporcional a los días.'],
              'C' => ['Interés Mensual','Cliente paga solo interés cada período. El capital se abona voluntariamente.','bi-calculator','warning','Cuota mínima = solo interés. El capital no baja hasta que el cliente lo abone.'],
            ]; ?>
            <?php foreach ($types as $t => [$name, $desc, $icon, $color, $tip]): ?>
            <div class="col-md-4">
              <label class="cursor-pointer d-block h-100">
                <input type="radio" name="loan_type" value="<?= $t ?>" class="visually-hidden loan-type-radio" <?= $t === 'A' ? 'checked' : '' ?>>
                <div class="card border-2 h-100 type-card <?= $t === 'A' ? 'border-primary' : 'border-light' ?>" data-type="<?= $t ?>" style="cursor:pointer;transition:all .2s">
                  <div class="card-body p-3 text-center">
                    <i class="bi <?= $icon ?> fs-3 text-<?= $color ?>"></i>
                    <div class="fw-bold mt-1 fs-5">Tipo <?= $t ?></div>
                    <div class="fw-semibold" style="font-size:.85rem"><?= $name ?></div>
                    <small class="text-muted d-block mb-1"><?= $desc ?></small>
                    <div class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> border border-<?= $color ?>" style="font-size:.7rem;white-space:normal"><?= $tip ?></div>
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

      <!-- ========== STEP 2: CONDICIONES FINANCIERAS ========== -->
      <div id="step-1" class="d-none">
        <div class="row g-3">
          <!-- Monto -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">Monto (<?= $currency ?>) <span class="text-danger">*</span></label>
            <input type="number" name="principal" id="principal" class="form-control form-control-lg"
                   min="1" step="0.01" required placeholder="0.00">
          </div>
          <!-- Tasa -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">Tasa de Interés <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" name="interest_rate" id="interest_rate" class="form-control form-control-lg"
                     min="0.01" step="0.01" required placeholder="20">
              <select name="rate_type" id="rate_type" class="form-select form-select-lg" style="max-width:110px">
                <option value="monthly">%/mes</option>
                <option value="annual">%/año</option>
              </select>
            </div>
          </div>
          <!-- Frecuencia -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">Frecuencia de Pago</label>
            <select name="payment_frequency" id="payment_frequency" class="form-select form-select-lg">
              <option value="weekly">Semanal (7 días)</option>
              <option value="biweekly">Quincenal (15 días)</option>
              <option value="monthly" selected>Mensual (30 días)</option>
              <option value="bimonthly">Bimensual (60 días)</option>
              <option value="quarterly">Trimestral (90 días)</option>
              <option value="semiannual">Semestral (180 días)</option>
              <option value="annual">Anual (365 días)</option>
            </select>
          </div>
          <!-- Número de cuotas (solo Tipo A y C) -->
          <div class="col-md-3" id="term_col">
            <label class="form-label fw-semibold">
              Número de Cuotas <span class="text-danger" id="term_star">*</span>
            </label>
            <div class="input-group">
              <input type="number" name="term_months" id="term_months" class="form-control form-control-lg"
                     min="1" max="600" placeholder="12">
              <span class="input-group-text" id="term_label">cuotas</span>
            </div>
            <div class="form-text" id="term_hint">Equivale a <span id="term_equiv">—</span></div>
          </div>
          <!-- Desembolso -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">Fecha Desembolso</label>
            <input type="date" name="disbursement_date" id="disbursement_date" class="form-control"
                   value="<?= $defaults['disbursement_date'] ?>" required>
          </div>
          <!-- Primer pago -->
          <div class="col-md-3">
            <label class="form-label fw-semibold">Fecha Primer Pago</label>
            <input type="date" name="first_payment_date" id="first_payment_date" class="form-control"
                   value="<?= $defaults['first_payment_date'] ?>" required>
          </div>
          <!-- Aplicar pago a -->
          <div class="col-md-3" id="apply_col">
            <label class="form-label">Prioridad de Pago</label>
            <select name="apply_payment_to" class="form-select">
              <option value="interest_first">Interés primero, luego capital</option>
              <option value="capital">Capital primero</option>
            </select>
          </div>
          <!-- Tipo B info -->
          <div class="col-md-3 d-none" id="typeB_info">
            <div class="alert alert-info py-2 mb-0" style="font-size:.8rem">
              <i class="bi bi-info-circle me-1"></i>
              <strong>Tipo B:</strong> El interés se calcula automáticamente por los días transcurridos al momento de cada pago. No hay tabla fija.
            </div>
          </div>
        </div>

        <!-- ===== TABLA DE AMORTIZACIÓN EN VIVO ===== -->
        <div id="liveTableWrap" class="mt-4 d-none">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0"><i class="bi bi-table me-2 text-primary"></i>Tabla de Amortización — Vista Previa</h6>
            <div class="d-flex gap-2 align-items-center">
              <div class="badge bg-primary" id="liveTableBadge">—</div>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleFullTable()">
                <i class="bi bi-arrows-expand" id="tableExpandIcon"></i> <span id="tableExpandText">Ver todo</span>
              </button>
            </div>
          </div>
          <!-- SUMMARY CARDS -->
          <div class="row g-2 mb-3" id="liveSummary">
            <div class="col-3"><div class="card border-0 bg-light p-2 text-center">
              <div class="fw-bold text-primary" id="sum_payment">—</div><div class="text-muted" style="font-size:.7rem">Cuota</div>
            </div></div>
            <div class="col-3"><div class="card border-0 bg-light p-2 text-center">
              <div class="fw-bold text-danger" id="sum_interest">—</div><div class="text-muted" style="font-size:.7rem">Total Interés</div>
            </div></div>
            <div class="col-3"><div class="card border-0 bg-light p-2 text-center">
              <div class="fw-bold text-success" id="sum_total">—</div><div class="text-muted" style="font-size:.7rem">Total a Pagar</div>
            </div></div>
            <div class="col-3"><div class="card border-0 bg-light p-2 text-center">
              <div class="fw-bold text-warning" id="sum_lastdate">—</div><div class="text-muted" style="font-size:.7rem">Último Pago</div>
            </div></div>
          </div>
          <!-- TABLE -->
          <div class="table-responsive" style="max-height:380px;overflow-y:auto">
            <table class="table table-sm table-bordered table-hover mb-0" style="font-size:.78rem">
              <thead class="table-dark sticky-top">
                <tr>
                  <th class="text-center">#</th>
                  <th>Fecha</th>
                  <th class="text-end">Capital</th>
                  <th class="text-end">Interés</th>
                  <th class="text-end fw-bold">Total Cuota</th>
                  <th class="text-end">Saldo</th>
                </tr>
              </thead>
              <tbody id="liveTableBody"></tbody>
              <tfoot id="liveTableFoot" class="fw-bold table-secondary"></tfoot>
            </table>
          </div>
          <div id="tableMoreNote" class="text-muted text-end mt-1 d-none" style="font-size:.73rem">
            Mostrando primeras y últimas cuotas. <a href="#" onclick="toggleFullTable();return false;">Ver todas</a>
          </div>
        </div>
        <!-- Tipo B notice -->
        <div id="typeBPreview" class="mt-2 d-none">
          <div class="alert alert-info py-2 mb-0" style="font-size:.82rem">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Tipo B — Simulación</strong>: interés real se recalcula por días al pagar.
            Interés diario: <strong id="typeb_daily">—</strong>
            (<strong id="typeb_principal">—</strong> × <strong id="typeb_rate">—</strong>%/mes ÷ 30).
            Ingresa el número de cuotas arriba para ver la tabla proyectada.
          </div>
        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="button" class="btn btn-outline-secondary" onclick="goStep(0)"><i class="bi bi-arrow-left me-1"></i>Anterior</button>
          <button type="button" class="btn btn-primary" onclick="goStep(2)">Siguiente <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
      </div>

      <!-- ========== STEP 3: CONFIGURACIÓN ========== -->
      <div id="step-2" class="d-none">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Tasa Moratoria (%/mes)</label>
            <div class="input-group">
              <input type="number" name="late_fee_rate" id="late_fee_rate_input" class="form-control"
                     step="0.01" min="0" max="100"
                     value="<?= number_format($defaults['late_fee_rate'] * 100, 2) ?>">
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Días de Gracia</label>
            <div class="input-group">
              <input type="number" name="grace_days" id="grace_days_input" class="form-control"
                     min="0" max="30" value="<?= $defaults['grace_days'] ?>">
              <span class="input-group-text">días</span>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Notas del Préstamo</label>
            <textarea name="notes" class="form-control" rows="2" maxlength="1000"
                      placeholder="Garantías, condiciones especiales, observaciones..."></textarea>
          </div>
        </div>

        <!-- EXPLICACIÓN DE MORA -->
        <div class="card border-warning mt-3">
          <div class="card-header bg-warning text-dark py-2 fw-semibold d-flex justify-content-between">
            <span><i class="bi bi-exclamation-triangle me-2"></i>¿Cómo funciona la mora?</span>
            <button type="button" class="btn btn-sm btn-outline-dark py-0" data-bs-toggle="collapse" data-bs-target="#moraExplain">
              <i class="bi bi-chevron-down"></i>
            </button>
          </div>
          <div class="collapse show" id="moraExplain">
            <div class="card-body py-2" style="font-size:.83rem">
              <p class="mb-2">La mora es un cargo adicional que se aplica <strong>solo cuando el cliente no paga en la fecha acordada</strong>, y solo después de los días de gracia.</p>
              <div class="bg-light rounded p-2 mb-2">
                <strong>Fórmula:</strong>
                <code class="d-block mt-1">Mora = Saldo vencido × (Tasa Moratoria / 30) × Días con mora</code>
              </div>
              <div id="moraExample" class="text-muted"></div>
              <p class="mb-0 mt-2"><i class="bi bi-info-circle text-info me-1"></i>Los intereses moratorios se cobran <em>antes</em> que el interés corriente y el capital al registrar un pago.</p>
            </div>
          </div>
        </div>

        <!-- RESUMEN FINAL -->
        <div class="alert alert-success mt-3" id="finalSummary">
          <i class="bi bi-check-circle me-2"></i>
          <strong>Resumen:</strong> Complete los datos del préstamo para ver el resumen aquí.
        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="button" class="btn btn-outline-secondary" onclick="goStep(1)"><i class="bi bi-arrow-left me-1"></i>Anterior</button>
          <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn">
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
let allRows      = [];
let showFull     = false;
let selectedType = 'A';   // ← tipo seleccionado actualmente
const PREVIEW_ROWS = 8;

// ─── NAVEGACIÓN DE PASOS ────────────────────────────────────
function goStep(n) {
  // Validate current step before advancing
  const current = parseInt(document.querySelector('[id^="step-"]:not(.d-none)')?.id.replace('step-','') || '0');
  if (n > current) {
    if (current === 0) {
      const clientId = document.getElementById('client_id');
      if (!clientId.value) {
        clientId.classList.add('is-invalid');
        clientId.focus();
        return;
      }
      clientId.classList.remove('is-invalid');
    }
    if (current === 1) {
      const principal = document.getElementById('principal');
      const rate      = document.getElementById('interest_rate');
      const term      = document.getElementById('term_months');
      let valid = true;
      [principal, rate].forEach(el => {
        if (!el.value || parseFloat(el.value) <= 0) { el.classList.add('is-invalid'); valid = false; }
        else el.classList.remove('is-invalid');
      });
      if (selectedType !== 'B' && (!term.value || parseInt(term.value) <= 0)) {
        term.classList.add('is-invalid'); valid = false;
      } else { term.classList.remove('is-invalid'); }
      if (!valid) return;
    }
  }
  document.querySelectorAll('[id^="step-"]').forEach((el, i) => el.classList.toggle('d-none', i !== n));
  document.querySelectorAll('.step-badge').forEach((el, i) => {
    el.className = 'badge rounded-pill me-1 step-badge ' + (i <= n ? 'bg-primary text-white' : 'bg-light text-muted border');
  });
  if (n === 2) updateSummary();
}

// ─── ACTUALIZAR UI SEGÚN TIPO ─────────────────────────────────
function updateTypeUI(type) {
  const termCol   = document.getElementById('term_col');
  const termInput = document.getElementById('term_months');
  const applyCol  = document.getElementById('apply_col');
  const typeBInfo = document.getElementById('typeBPreview');
  const liveWrap  = document.getElementById('liveTableWrap');

  if (type === 'B') {
    termCol.classList.remove('d-none');
    termInput.required = false;
    document.getElementById('term_star').style.display = 'none';
    applyCol.classList.add('d-none');
    typeBInfo.classList.remove('d-none');
    refreshTable();
  } else {
    termCol.classList.remove('d-none');
    termInput.required = true;
    document.getElementById('term_star').style.display = '';
    applyCol.classList.remove('d-none');
    typeBInfo.classList.add('d-none');
    refreshTable();
  }
}

// ─── TIPO DE TARJETA ──────────────────────────────────────────
document.querySelectorAll('.loan-type-radio').forEach(r => {
  r.addEventListener('change', () => {
    selectedType = r.value;   // ← actualizar variable global
    document.querySelectorAll('.type-card').forEach(c => {
      c.classList.remove('border-primary');
      c.classList.add('border-light');
    });
    document.querySelector('.type-card[data-type="'+r.value+'"]').classList.remove('border-light');
    document.querySelector('.type-card[data-type="'+r.value+'"]').classList.add('border-primary');
    updateTypeUI(r.value);
  });
});

// ─── EQUIVALENCIA DE PLAZO ───────────────────────────────────
const FREQ_LABELS = {
  weekly:'semana',biweekly:'quincena',monthly:'mes',
  bimonthly:'bimestre',quarterly:'trimestre',semiannual:'semestre',annual:'año'
};
const FREQ_DAYS = {weekly:7,biweekly:15,monthly:30,bimonthly:60,quarterly:90,semiannual:180,annual:365};

function updateTermEquiv() {
  const n    = parseInt(document.getElementById('term_months').value) || 0;
  const freq = document.getElementById('payment_frequency').value;
  const days = FREQ_DAYS[freq] * n;
  const lbl  = FREQ_LABELS[freq] || 'período';
  const months = Math.round(days / 30);
  const years  = (days / 365).toFixed(1);
  let txt = '';
  if (n > 0) txt = `${n} ${lbl}${n>1?'s':''} = aprox. ${months} meses / ${years} años`;
  document.getElementById('term_equiv').textContent = txt || '—';
  document.getElementById('term_label').textContent = n === 1 ? 'cuota' : 'cuotas';
}

// ─── TABLA EN VIVO ────────────────────────────────────────────
function fmt(n) { return CUR + ' ' + parseFloat(n||0).toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function round2(x) { return Math.round(x * 100) / 100; }

function addPeriods(baseDate, freq, periods) {
  let d = new Date(baseDate + 'T12:00:00'); // noon avoids DST issues
  for (let p = 0; p < periods; p++) {
    switch(freq) {
      case 'weekly':     d.setDate(d.getDate()+7); break;
      case 'biweekly':   d.setDate(d.getDate()+15); break;
      case 'monthly':    d.setMonth(d.getMonth()+1); break;
      case 'bimonthly':  d.setMonth(d.getMonth()+2); break;
      case 'quarterly':  d.setMonth(d.getMonth()+3); break;
      case 'semiannual': d.setMonth(d.getMonth()+6); break;
      case 'annual':     d.setFullYear(d.getFullYear()+1); break;
    }
  }
  return d;
}

function fmtDate(d) {
  return d.toLocaleDateString('es-HN',{day:'2-digit',month:'2-digit',year:'numeric'});
}

function buildRows() {
  const type     = selectedType;
  const P        = parseFloat(document.getElementById('principal').value) || 0;
  const rateRaw  = parseFloat(document.getElementById('interest_rate').value) || 0;
  const rateType = document.getElementById('rate_type').value;
  const n        = parseInt(document.getElementById('term_months').value) || 0;
  const freq     = document.getElementById('payment_frequency').value;
  const firstPay = document.getElementById('first_payment_date').value;
  const freqDays = FREQ_DAYS[freq] || 30;

  if (P <= 0 || rateRaw <= 0) return [];
  if ((type === 'A' || type === 'C') && n <= 0) return [];

  const monthlyRate = rateType === 'annual' ? rateRaw / 100 / 12 : rateRaw / 100;
  const periodRate  = monthlyRate * (freqDays / 30);
  const rows        = [];
  const baseDate    = firstPay || new Date().toISOString().slice(0,10);

  if (type === 'A') {
    const r   = periodRate;
    const pmt = r === 0 ? P/n : round2(P * (r * Math.pow(1+r,n)) / (Math.pow(1+r,n)-1));
    let balance = P;
    for (let i = 1; i <= n; i++) {
      const interest = round2(balance * r);
      const capital  = i === n ? round2(balance) : round2(pmt - interest);
      const total    = round2(capital + interest);
      balance = round2(Math.max(0, balance - capital));
      const dueDate  = addPeriods(baseDate, freq, i-1);
      rows.push({i, capital, interest, total, balance, dueDate});
    }
  } else if (type === 'C') {
    let balance = P;
    for (let i = 1; i <= n; i++) {
      const interest = round2(balance * periodRate);
      const isLast   = i === n;
      const capital  = isLast ? balance : 0;
      const total    = round2(capital + interest);
      const newBal   = isLast ? 0 : balance;
      const dueDate  = addPeriods(baseDate, freq, i-1);
      rows.push({i, capital, interest, total, balance: newBal, dueDate, isInterestOnly: !isLast});
    }
  } else if (type === 'B') {
    // Tipo B: abono de capital FIJO por período (P/n), interés sobre saldo decreciente
    // La cuota total VARÍA (baja cada período) porque el interés baja
    const capitalPerPeriod = round2(P / n);
    let balance = P;
    for (let i = 1; i <= n; i++) {
      const interest = round2(balance * periodRate);
      const isLast   = i === n;
      const capital  = isLast ? round2(balance) : capitalPerPeriod;
      const total    = round2(capital + interest);
      balance = round2(Math.max(0, balance - capital));
      const dueDate  = addPeriods(baseDate, freq, i-1);
      rows.push({i, capital, interest, total, balance, dueDate, isSimulation: true});
    }
  }
  return rows;
}

function renderTable() {
  const rows = allRows;
  if (!rows.length) return;

  const tbody = document.getElementById('liveTableBody');
  const tfoot = document.getElementById('liveTableFoot');

  const displayRows = showFull ? rows : (rows.length > PREVIEW_ROWS+4
    ? [...rows.slice(0, PREVIEW_ROWS), null, ...rows.slice(-2)]
    : rows);

  let html = '';
  let totCap = 0, totInt = 0, totPay = 0;

  displayRows.forEach(r => {
    if (r === null) {
      html += `<tr class="table-light"><td colspan="6" class="text-center text-muted py-1" style="font-size:.72rem">· · · ${rows.length - PREVIEW_ROWS - 2} cuotas intermedias · · ·</td></tr>`;
      return;
    }
    totCap += r.capital; totInt += r.interest; totPay += r.total;
    const isLast = r.i === rows.length;
    const rowBadge = r.isSimulation && r.isInterestOnly
      ? '<span class="badge bg-info text-dark ms-1" style="font-size:.6rem">sim.</span>'
      : (r.isInterestOnly ? '<span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">interés</span>' : '');
    html += `<tr ${isLast?'class="table-success"':''}>
      <td class="text-center fw-semibold">${r.i}</td>
      <td>${fmtDate(r.dueDate)}</td>
      <td class="text-end">${fmt(r.capital)}</td>
      <td class="text-end text-danger">${fmt(r.interest)}</td>
      <td class="text-end fw-bold">${fmt(r.total)}${rowBadge}</td>
      <td class="text-end text-muted">${fmt(r.balance)}</td>
    </tr>`;
  });
  tbody.innerHTML = html;

  // Use full totals for footer
  const fRows = allRows;
  const fCap  = fRows.reduce((a,r)=>a+r.capital,0);
  const fInt  = fRows.reduce((a,r)=>a+r.interest,0);
  const fPay  = fRows.reduce((a,r)=>a+r.total,0);
  tfoot.innerHTML = `<tr>
    <td colspan="2" class="text-center">TOTALES (${fRows.length} cuotas)</td>
    <td class="text-end">${fmt(fCap)}</td>
    <td class="text-end text-danger">${fmt(fInt)}</td>
    <td class="text-end fw-bold">${fmt(fPay)}</td>
    <td></td>
  </tr>`;

  document.getElementById('tableMoreNote').classList.toggle('d-none', showFull || rows.length <= PREVIEW_ROWS+4);
}

function refreshTable() {
  const type = selectedType;

  // Tipo B: actualizar info de interés diario (siempre, aunque no haya tabla aún)
  if (type === 'B') updateTypeBPreview();

  allRows = buildRows();

  if (!allRows.length) {
    document.getElementById('liveTableWrap').classList.add('d-none');
    return;
  }

  document.getElementById('liveTableWrap').classList.remove('d-none');

  const freq   = document.getElementById('payment_frequency').value;
  const fLabel = { weekly:'Semanal',biweekly:'Quincenal',monthly:'Mensual',bimonthly:'Bimensual',quarterly:'Trimestral',semiannual:'Semestral',annual:'Anual' }[freq];

  document.getElementById('liveTableBadge').textContent = type === 'B'
    ? `Tipo B · Simulación · ${fLabel}`
    : `Tipo ${type} · ${fLabel}`;

  const totalInt = allRows.reduce((a,r) => a + r.interest, 0);
  const totalPay = allRows.reduce((a,r) => a + r.total, 0);
  const firstPmt = allRows[0]?.total || 0;
  const lastDate = allRows[allRows.length-1]?.dueDate;

  document.getElementById('sum_payment').textContent  = fmt(firstPmt);
  document.getElementById('sum_interest').textContent = fmt(totalInt);
  document.getElementById('sum_total').textContent    = fmt(totalPay);
  document.getElementById('sum_lastdate').textContent = lastDate ? fmtDate(lastDate) : '—';

  renderTable();
}

function toggleFullTable() {
  showFull = !showFull;
  document.getElementById('tableExpandIcon').className = 'bi bi-' + (showFull ? 'arrows-collapse' : 'arrows-expand');
  document.getElementById('tableExpandText').textContent = showFull ? 'Colapsar' : 'Ver todo';
  renderTable();
}

function updateTypeBPreview() {
  const P        = parseFloat(document.getElementById('principal').value) || 0;
  const rateRaw  = parseFloat(document.getElementById('interest_rate').value) || 0;
  const rateType = document.getElementById('rate_type').value;
  const monthly  = rateType === 'annual' ? rateRaw / 12 : rateRaw;
  const daily    = round2(P * (monthly / 100) / 30);

  document.getElementById('typeb_principal').textContent = fmt(P);
  document.getElementById('typeb_rate').textContent      = monthly.toFixed(2);
  document.getElementById('typeb_daily').textContent     = fmt(daily) + '/día';
}

// ─── MORA EJEMPLO EN VIVO ────────────────────────────────────
function updateMoraExample() {
  const P        = parseFloat(document.getElementById('principal').value) || 10000;
  const latePct  = parseFloat(document.getElementById('late_fee_rate_input').value) || 5;
  const grace    = parseInt(document.getElementById('grace_days_input').value) || 3;
  const exDays   = 15;
  const efDays   = Math.max(0, exDays - grace);
  const mora     = round2(P * (latePct/100/30) * efDays);
  document.getElementById('moraExample').innerHTML =
    `<strong>Ejemplo:</strong> Saldo vencido <strong>${fmt(P)}</strong>, ` +
    `mora de <strong>${latePct}%/mes</strong>, vencido hace <strong>${exDays} días</strong> (${grace} de gracia):<br>` +
    `Días efectivos con mora: ${exDays} - ${grace} = <strong>${efDays} días</strong><br>` +
    `Cargo por mora = ${fmt(P)} × (${latePct}% ÷ 30) × ${efDays} = <strong>${fmt(mora)}</strong>`;
}

// ─── RESUMEN FINAL ───────────────────────────────────────────
function updateSummary() {
  const type   = selectedType;
  const P      = parseFloat(document.getElementById('principal').value) || 0;
  const rate   = parseFloat(document.getElementById('interest_rate').value) || 0;
  const rtype  = document.getElementById('rate_type').value;
  const n      = parseInt(document.getElementById('term_months').value) || 0;
  const freq   = document.getElementById('payment_frequency').value;
  const fLabel = { weekly:'Semanal',biweekly:'Quincenal',monthly:'Mensual',bimonthly:'Bimensual',quarterly:'Trimestral',semiannual:'Semestral',annual:'Anual' }[freq];
  const tLabel = {A:'Cuota Nivelada',B:'Variable por Días',C:'Interés Simple'}[type];
  const client = document.getElementById('client_id').options[document.getElementById('client_id').selectedIndex]?.text || 'Sin seleccionar';

  const rows     = type !== 'B' ? (allRows.length ? allRows : buildRows()) : [];
  const totalPay = rows.reduce((a,r) => a+r.total, 0);
  const totalInt = rows.reduce((a,r) => a+r.interest, 0);

  let html = `
    <strong>Tipo:</strong> ${type} – ${tLabel} &nbsp;|&nbsp;
    <strong>Cliente:</strong> ${client.split('·').pop()?.trim()||client} &nbsp;|&nbsp;
    <strong>Monto:</strong> ${fmt(P)} &nbsp;|&nbsp;
    <strong>Tasa:</strong> ${rate}%/${rtype==='annual'?'año':'mes'} &nbsp;|&nbsp;
    <strong>Frecuencia:</strong> ${fLabel}`;
  if (type !== 'B' && n > 0) html += ` &nbsp;|&nbsp; <strong>Cuotas:</strong> ${n}`;
  if (totalPay > 0) html += ` &nbsp;|&nbsp; <strong>Total a pagar:</strong> ${fmt(totalPay)} (interés: ${fmt(totalInt)})`;

  document.getElementById('finalSummary').innerHTML = '<i class="bi bi-check-circle me-2"></i>' + html;
  updateMoraExample();
}

// ─── LISTENERS ───────────────────────────────────────────────
['principal','interest_rate','term_months','first_payment_date'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', () => { refreshTable(); updateTermEquiv(); });
});
document.getElementById('rate_type').addEventListener('change', refreshTable);
document.getElementById('payment_frequency').addEventListener('change', () => { refreshTable(); updateTermEquiv(); });
document.getElementById('late_fee_rate_input')?.addEventListener('input', updateMoraExample);
document.getElementById('grace_days_input')?.addEventListener('input', updateMoraExample);

// Validate + prevent double submit
document.getElementById('loanForm').addEventListener('submit', function(e) {
  const clientId = document.getElementById('client_id');
  const principal = document.getElementById('principal');
  const rate      = document.getElementById('interest_rate');
  const term      = document.getElementById('term_months');
  let valid = true;

  if (!clientId.value) { valid = false; }
  if (!principal.value || parseFloat(principal.value) <= 0) { valid = false; }
  if (!rate.value || parseFloat(rate.value) <= 0) { valid = false; }
  if (selectedType !== 'B' && (!term.value || parseInt(term.value) <= 0)) { valid = false; }

  if (!valid) {
    e.preventDefault();
    alert('Por favor complete todos los campos obligatorios (Cliente, Monto, Tasa' + (selectedType !== 'B' ? ', Número de cuotas' : '') + ')');
    goStep(0);
    return;
  }

  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando préstamo...';
});

// Tipo cards hover
document.querySelectorAll('.type-card').forEach(c => {
  c.addEventListener('mouseenter', () => c.style.transform = 'translateY(-2px)');
  c.addEventListener('mouseleave', () => c.style.transform = '');
});

// Init
updateTypeUI(selectedType);
updateMoraExample();
</script>