<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Pagaré – <?= htmlspecialchars($loan['loan_number'] ?? '') ?></title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Times New Roman', serif;
      font-size: 11.5pt;
      color: #000;
      background: #fff;
    }

    @media screen {
      body {
        padding: 2cm 2.5cm;
      }
    }

    h1 {
      font-size: 15pt;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 6px;
    }

    h2 {
      font-size: 12pt;
      text-align: center;
      margin-bottom: 20px;
      font-weight: normal;
    }

    .monto-header {
      font-size: 18pt;
      font-weight: bold;
      text-align: center;
      border: 2px solid #000;
      padding: 8px 20px;
      display: inline-block;
      margin-bottom: 20px;
    }

    .monto-wrap {
      text-align: center;
      margin-bottom: 18px;
    }

    p {
      text-align: justify;
      line-height: 1.7;
      margin-bottom: 10px;
    }

    .clausulas {
      margin: 14px 0;
    }

    .clausulas ol {
      padding-left: 20px;
    }

    .clausulas li {
      margin-bottom: 6px;
      line-height: 1.6;
    }

    .firmas {
      margin-top: 50px;
    }

    .firmas-row {
      display: flex;
      justify-content: space-between;
      gap: 40px;
    }

    .firma-box {
      flex: 1;
      text-align: center;
    }

    .firma-line {
      border-top: 1px solid #000;
      margin-top: 60px;
      margin-bottom: 6px;
    }

    .firma-label {
      font-size: 10pt;
    }

    /* ── HUELLA: caja vacía + etiqueta DEBAJO ── */
    .huella-wrap {
      display: inline-block;
      text-align: center;
      margin-top: 10px;
    }

    .huella-box {
      width: 80px;
      height: 80px;
      border: 1px dashed #666;
      display: block;
    }

    .huella-label {
      font-size: 8pt;
      color: #555;
      margin-top: 3px;
    }

    .nota {
      font-size: 9pt;
      margin-top: 30px;
      border-top: 1px solid #ccc;
      padding-top: 10px;
      color: #333;
    }

    @media print {
      body {
        padding: 1.5cm 2cm;
      }

      .no-print {
        display: none !important;
      }

      @page {
        size: legal;
        margin: 1.5cm;
      }
    }

    .print-btn {
      position: fixed;
      top: 10px;
      right: 10px;
      background: #2563eb;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 11pt;
      z-index: 999;
    }

    /* ── Control de saltos de página ───────────────────────── */
    .page-break-before {
      break-before: page;
      page-break-before: always;
      /* compatibilidad */
    }

    .avoid-break {
      break-inside: avoid;
      page-break-inside: avoid;
    }
  </style>
</head>

<body>

  <?php
  $currency    = setting('app_currency', 'L');
  $companyName = setting('company_legal_name', setting('app_name', ''));
  $companyRTN  = setting('company_rtn', '');
  $companyAddr = setting('company_address', '');
  $companyCity = setting('pagare_city', setting('company_city', ''));
  $repName     = setting('company_rep_name', '');
  $repIdentity = setting('company_rep_identity', '');
  $repNat      = setting('company_rep_nationality', 'Hondureña');
  $jurisdiction = setting('pagare_jurisdiction', 'Juzgado de Letras de lo Civil');
  $lateFeeRate = isset($loan['late_fee_rate']) ? number_format((float)$loan['late_fee_rate'] * 100, 2) : '0.00';

  $client  = $client  ?? [];
  $loan    = $loan    ?? [];
  $aval    = $aval    ?? null;

  $clientName    = trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''));
  $clientMarital = match ($client['marital_status'] ?? 'soltero') {
    'casado'     => 'Casado/a',
    'divorciado' => 'Divorciado/a',
    'viudo'      => 'Viudo/a',
    default      => 'Soltero/a',
  };
  // Profesión: campo profession primero, luego occupation como fallback
  $clientProf    = trim($client['profession'] ?? '');
  if ($clientProf === '') $clientProf = trim($client['occupation'] ?? '');

  $clientNat     = $client['nationality'] ?? 'Hondureña';
  $clientId      = $client['identity_number'] ?? '';
  $clientAddr    = $client['address'] ?? '';
  $clientCity    = $client['city'] ?? '';
  // phone = celular (principal), phone2 = teléfono fijo
  $clientCelular = $client['phone']  ?? '';
  $clientPhone2  = $client['phone2'] ?? '';

  $amount        = (float)($loan['principal'] ?? 0);
  $amountFmt     = number_format($amount, 2);
  $dueDate       = $loan['maturity_date'] ?? $loan['first_payment_date'] ?? '';
  $dueDateParts  = $dueDate ? explode('-', $dueDate) : ['____', '__', '__'];

  // ── FIX: strftime() deprecated → array manual en español ──────────────────
  $spanishMonths = [
    1 => 'ENERO',
    2 => 'FEBRERO',
    3 => 'MARZO',
    4 => 'ABRIL',
    5 => 'MAYO',
    6 => 'JUNIO',
    7 => 'JULIO',
    8 => 'AGOSTO',
    9 => 'SEPTIEMBRE',
    10 => 'OCTUBRE',
    11 => 'NOVIEMBRE',
    12 => 'DICIEMBRE'
  ];
  $today    = date('d');
  $month    = $spanishMonths[(int)date('n')];
  $year     = date('Y');
  $signCity = $companyCity ?: '______________________________';

  // ── Método de pago del préstamo ────────────────────────────────────────────
  $payMethod   = $loan['payment_method'] ?? 'cash';
  $payLocation = $loan['payment_location'] ?? $companyAddr;
  $payMethodLabel = match ($payMethod) {
    'transfer' => 'Transferencia bancaria',
    'deposit'  => 'Depósito en cuenta',
    'cash'     => 'Efectivo en ' . ($payLocation ?: $companyAddr),
    default    => 'Efectivo',
  };

  // Helper: número a palabras
  function number_in_words(float $amount): string
  {
    $int  = (int)$amount;
    $dec  = round(($amount - $int) * 100);
    $words = numToWords($int);
    $str  = strtoupper($words);
    if ($dec > 0) $str .= ' CON ' . str_pad($dec, 2, '0', STR_PAD_LEFT) . '/100';
    return $str;
  }
  function numToWords(int $n): string
  {
    if ($n === 0) return 'CERO';
    $ones = [
      '',
      'UNO',
      'DOS',
      'TRES',
      'CUATRO',
      'CINCO',
      'SEIS',
      'SIETE',
      'OCHO',
      'NUEVE',
      'DIEZ',
      'ONCE',
      'DOCE',
      'TRECE',
      'CATORCE',
      'QUINCE',
      'DIECISÉIS',
      'DIECISIETE',
      'DIECIOCHO',
      'DIECINUEVE'
    ];
    $tens = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $hundreds = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    if ($n < 0) return 'MENOS ' . numToWords(-$n);
    if ($n < 20) return $ones[$n];
    if ($n < 100) return $tens[intdiv($n, 10)] . ($n % 10 ? ' Y ' . $ones[$n % 10] : '');
    if ($n === 100) return 'CIEN';
    if ($n < 1000) return $hundreds[intdiv($n, 100)] . ($n % 100 ? ' ' . numToWords($n % 100) : '');
    if ($n < 2000) return 'MIL' . ($n % 1000 ? ' ' . numToWords($n % 1000) : '');
    if ($n < 1000000) return numToWords(intdiv($n, 1000)) . ' MIL' . ($n % 1000 ? ' ' . numToWords($n % 1000) : '');
    if ($n < 2000000) return 'UN MILLÓN' . ($n % 1000000 ? ' ' . numToWords($n % 1000000) : '');
    return numToWords(intdiv($n, 1000000)) . ' MILLONES' . ($n % 1000000 ? ' ' . numToWords($n % 1000000) : '');
  }
  $amountWords = number_in_words($amount);
  ?>

  <button class="print-btn no-print" onclick="window.print()">🖨 Imprimir Pagaré</button>

  <div style="text-align:right;font-size:9pt;color:#555;margin-bottom:10px">
    Préstamo: <strong><?= htmlspecialchars($loan['loan_number'] ?? '') ?></strong>
  </div>

  <h1>Pagaré</h1>
  <div class="monto-wrap">
    <span class="monto-header">POR <?= $currency ?> <?= $amountFmt ?></span>
  </div>

  <p>
    Yo, <strong><?= htmlspecialchars($clientName) ?></strong>, mayor de edad, estado civil:
    <strong><?= $clientMarital ?></strong><?php if (!empty($client['spouse_name'])): ?>,
    cónyuge: <strong><?= htmlspecialchars($client['spouse_name']) ?></strong><?php endif; ?>,
    profesión u oficio: <strong><?= htmlspecialchars($clientProf ?: '___________________') ?></strong>,
    nacionalidad: <strong><?= htmlspecialchars($clientNat) ?></strong>,
    Tarjeta de Identidad: <strong><?= htmlspecialchars($clientId ?: '____________________________') ?></strong>,
    con domicilio en: <strong><?= htmlspecialchars(trim($clientAddr . ' ' . $clientCity) ?: '____________________________________________________________') ?></strong>,
    celular: <strong><?= htmlspecialchars($clientCelular ?: '____________________') ?></strong>
    <?php if ($clientPhone2): ?> y teléfono: <strong><?= htmlspecialchars($clientPhone2) ?></strong><?php endif; ?>.
  </p>

  <p>
    Por el presente <strong>PAGARÉ</strong>, <strong>HAGO CONSTAR</strong> que <strong>DEBO Y CANCELARÉ</strong>
    sin ningún requerimiento legal a:
    <strong><?= htmlspecialchars($companyName ?: '_______________________________________________') ?></strong><?php if ($repName): ?>,
    representada por <strong><?= htmlspecialchars($repName) ?></strong>,
    Tarjeta de Identidad No. <strong><?= htmlspecialchars($repIdentity) ?></strong>,
    nacionalidad: <strong><?= htmlspecialchars($repNat) ?></strong><?php endif; ?>;
    por la cantidad de <strong><?= $amountWords ?> LEMPIRAS EXACTOS
      (<?= $currency ?> <?= $amountFmt ?>)</strong>,
    a pagar el día
    <strong><?= $dueDateParts[2] ?? '__' ?> / <?= $dueDateParts[1] ?? '__' ?> / <?= $dueDateParts[0] ?? '____' ?></strong>.
  </p>

  <p>
    En caso de incumplimiento, me someto a la jurisdicción del
    <strong><?= htmlspecialchars($jurisdiction) ?></strong>
    competente y autorizo el cobro judicial como título extrajudicial.
  </p>

  <div class="clausulas">
    <p><strong>Cláusulas adicionales:</strong></p>
    <ol>
      <li>
        <strong>Lugar de pago:</strong>
        <?= htmlspecialchars($payLocation ?: $companyAddr ?: '____________________________________________________________') ?>
        (<?= htmlspecialchars($payMethodLabel) ?>).
      </li>
      <li>
        <strong>Interés moratorio por mora:</strong>
        <strong><?= $lateFeeRate ?>%</strong> mensual sobre saldo, hasta cancelación total.
      </li>
      <li>
        <strong>Gastos de cobro:</strong> asumo todos los gastos de cobranza y honorarios de abogado
        en caso de gestión judicial o extrajudicial.
      </li>
      <li>
        <strong>Abonos parciales:</strong> los pagos se aplicarán primero a gastos y comisiones de cobro,
        luego a intereses (corrientes y moratorios), y finalmente a capital, salvo pacto escrito en contrario.
      </li>
      <li>
        <strong>Domicilio para notificaciones:</strong>
        <?= htmlspecialchars(trim($clientAddr . ' ' . $clientCity) ?: '____________________________________________________________') ?>
        <?php if ($client['email'] ?? ''): ?>
          (correo: <strong><?= htmlspecialchars($client['email']) ?></strong>)
          <?php endif; ?>.
      </li>
    </ol>
  </div>

  <p>
    Para constancia, firmo y estampo mi huella en
    <strong><?= htmlspecialchars($signCity ?: '______________________________') ?></strong>,
    a los <strong><?= $today ?></strong> días del mes de <strong><?= $month ?></strong>
    del año <strong><?= $year ?></strong>.
  </p>

  <!-- ── FIRMAS ─────────────────────────────────────────────── -->
  <div class="firmas avoid-break">
    <div class="firmas-row">

      <!-- Deudor -->
      <div class="firma-box">
        <div class="firma-line"></div>
        <div class="firma-label"><strong>DEUDOR (Firma)</strong></div>
        <div style="margin-top:8px;font-size:10pt">Nombre: <strong><?= htmlspecialchars($clientName) ?></strong></div>
        <div style="font-size:10pt">No. Identidad: <strong><?= htmlspecialchars($clientId) ?></strong></div>
        <div style="margin-top:10px;font-size:10pt">HUELLA DIGITAL:</div>
        <div class="huella-wrap">
          <div class="huella-box"></div>
          <div class="huella-label">Huella</div>
        </div>
      </div>

      <!-- Acreedor / Apoderado -->
      <div class="firma-box">
        <div class="firma-line"></div>
        <div class="firma-label"><strong>ACREEDOR / APODERADO LEGAL (Firma)</strong></div>
        <?php if ($repName): ?>
          <div style="margin-top:8px;font-size:10pt">Nombre: <strong><?= htmlspecialchars($repName) ?></strong></div>
          <div style="font-size:10pt">No. Identidad: <strong><?= htmlspecialchars($repIdentity) ?></strong></div>
        <?php else: ?>
          <div style="margin-top:8px;font-size:10pt">Nombre: <strong><?= htmlspecialchars($companyName) ?></strong></div>
          <?php if ($companyRTN): ?><div style="font-size:10pt">RTN: <strong><?= htmlspecialchars($companyRTN) ?></strong></div><?php endif; ?>
        <?php endif; ?>
      </div>

    </div>

    <?php if ($aval): ?>
      <!-- ── AVAL ─────────────────────────────────────────────── -->
      <div class="page-break-before avoid-break" style="margin-top:50px">
        <p style="font-size:10pt;text-align:center;font-weight:bold;border-top:1px solid #999;padding-top:16px">
          ── SECCIÓN DEL AVAL / FIADOR SOLIDARIO ──
        </p>
        <p style="font-size:10.5pt;margin-top:10px">
          Yo, <strong><?= htmlspecialchars($aval['full_name']) ?></strong>, mayor de edad, Tarjeta de Identidad:
          <strong><?= htmlspecialchars($aval['identity_number'] ?? '______________________') ?></strong>,
          con domicilio en: <strong><?= htmlspecialchars($aval['address'] ?? '______________________') ?></strong>,
          teléfono: <strong><?= htmlspecialchars($aval['phone'] ?? '___________') ?></strong>,
          por este acto me constituyo como <strong>AVAL Y FIADOR SOLIDARIO</strong> del deudor
          <strong><?= htmlspecialchars($clientName) ?></strong> en relación con el presente pagaré por la suma de
          <strong><?= $currency ?> <?= $amountFmt ?></strong> (<strong><?= $amountWords ?> LEMPIRAS EXACTOS</strong>),
          incluyendo capital, intereses corrientes y moratorios, así como gastos de cobranza y honorarios profesionales
          que se generen por incumplimiento.
          <br><br>
          En consecuencia, <strong>me obligo solidariamente</strong> a pagar la totalidad de la deuda si el deudor principal
          no lo hiciere a su vencimiento, <strong>renunciando expresamente</strong> a los beneficios de orden, excusión y división,
          aceptando que el acreedor pueda exigir el pago <strong>directamente</strong> al aval, sin necesidad de requerimiento previo
          al deudor principal. Declaro conocer las condiciones del préstamo y el lugar/forma de pago indicados en este documento.
        </p>
        <div class="firmas-row" style="margin-top:40px">
          <div class="firma-box">
            <div class="firma-line"></div>
            <div class="firma-label"><strong>AVAL (Firma)</strong></div>
            <div style="margin-top:8px;font-size:10pt">Nombre: <strong><?= htmlspecialchars($aval['full_name']) ?></strong></div>
            <div style="font-size:10pt">No. Identidad: <strong><?= htmlspecialchars($aval['identity_number'] ?? '') ?></strong></div>
            <div style="margin-top:10px;font-size:10pt">HUELLA DIGITAL:</div>
            <div class="huella-wrap">
              <div class="huella-box"></div>
              <div class="huella-label">Huella</div>
            </div>
          </div>
          <div class="firma-box"></div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- NOTA LEGAL -->
  <div class="nota">
    <p><strong>Nota (Mora, Abonos e Interés):</strong>
      En caso de mora, el interés moratorio se aplicará únicamente sobre el saldo pendiente.
      Si el deudor ha realizado abonos, el interés se calculará sobre el monto restante.
      <em>Ejemplo:</em> si la deuda inicial es de <?= $currency ?> 10,000.00 y el deudor abonó
      <?= $currency ?> 4,000.00, el saldo pendiente será <?= $currency ?> 6,000.00.
      Si el interés moratorio es del <?= $lateFeeRate ?>% mensual, el recargo por un mes de atraso
      será de <?= $currency ?> <?= number_format(6000 * (float)str_replace(',', '.', $lateFeeRate) / 100, 2) ?>,
      sin incluir gastos de cobro.
    </p>
  </div>

</body>

</html>