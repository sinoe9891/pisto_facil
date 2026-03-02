<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Contrato de Préstamo – <?= htmlspecialchars($loan['loan_number'] ?? '') ?></title>
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
      font-size: 14pt;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 4px;
    }

    h2 {
      font-size: 12pt;
      text-align: center;
      margin-bottom: 18px;
      font-style: italic;
    }

    .subtitle {
      text-align: center;
      font-size: 10pt;
      color: #444;
      margin-bottom: 20px;
    }

    .clause-title {
      font-size: 11.5pt;
      font-weight: bold;
      text-transform: uppercase;
      margin: 16px 0 6px;
    }

    p {
      text-align: justify;
      line-height: 1.7;
      margin-bottom: 8px;
    }

    table.datos {
      width: 100%;
      border-collapse: collapse;
      margin: 10px 0;
      font-size: 10.5pt;
    }

    table.datos td {
      padding: 5px 8px;
      border: 1px solid #ccc;
    }

    table.datos td:first-child {
      font-weight: bold;
      width: 38%;
      background: #f9f9f9;
    }

    .firmas {
      margin-top: 60px;
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

    .garantia-box {
      border: 1px solid #dc2626;
      padding: 12px 16px;
      border-radius: 4px;
      margin: 10px 0;
      background: #fff5f5;
    }

    .aval-box {
      border: 1px solid #d97706;
      padding: 12px 16px;
      border-radius: 4px;
      margin: 10px 0;
      background: #fffbeb;
    }

    .identity-img {
      max-width: 220px;
      border: 1px solid #ccc;
      margin: 6px 4px;
      border-radius: 4px;
    }

    .identity-section {
      margin-top: 8px;
    }

    .page-break {
      page-break-before: always;
    }

    @media print {
      body {
        padding: 1.5cm 2cm;
      }

      .no-print {
        display: none !important;
      }

      .identity-page {
        padding-top: 4cm;
        /* mismo valor */
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

    .identity-page {
      padding-top: 2cm;
      /* ajusta: 0.8cm / 1cm / 1.5cm */
    }

    /* Página exclusiva para el anexo */
    .identity-page {
      page-break-before: always;
      break-before: page;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    /* Evita cortes internos */
    .identity-section,
    .identity-grid,
    .identity-card {
      page-break-inside: avoid;
      break-inside: avoid;
    }

    /* Layout: dos columnas (frente / reverso) para que quepa */
    .identity-grid {
      display: flex;
      gap: 12px;
      align-items: flex-start;
    }

    .identity-card {
      width: 50%;
    }

    /* Imagen: que SIEMPRE quepa en una hoja oficio */
    .identity-img {
      width: 100%;
      max-height: 18cm;
      /* <-- ajusta si deseas más/menos grande */
      object-fit: contain;
      border: 1px solid #ccc;
      border-radius: 4px;
      margin: 6px 0;
    }

    .identity-page.has-aval .identity-img {
      max-height: 8.5cm;
      /* ajusta: 8cm / 9cm / 10cm */
    }

    .identity-label {
      font-size: 9pt;
      color: #555;
      margin-bottom: 4px;
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

    /* Firmas: evitar que se partan */
    .firmas,
    .firmas-row,
    .aval-box,
    .garantia-box,
    table.datos {
      break-inside: avoid;
      page-break-inside: avoid;
    }

    /* Si quieres “forzar” que aval vaya siempre a otra página */
    .force-next-page {
      break-before: page;
      page-break-before: always;
    }
  </style>
</head>

<body>

  <?php
  $currency    = setting('app_currency', 'L');
  $companyName = setting('company_legal_name', setting('app_name', ''));
  $companyRTN  = setting('company_rtn', '');
  $companyAddr = setting('company_address', '');
  $companyCity = setting('company_city', '');
  $companyPhone = setting('company_phone', '');
  $repName     = setting('company_rep_name', '');
  $repIdentity = setting('company_rep_identity', '');
  $repNat      = setting('company_rep_nationality', 'Hondureña');
  $jurisdiction = setting('pagare_jurisdiction', 'Juzgado de Letras de lo Civil');
  $signCity    = setting('pagare_city', $companyCity);

  $client    = $client    ?? [];
  $loan      = $loan      ?? [];
  $aval      = $aval      ?? null;
  $guarantee = $guarantee ?? null;

  $clientName  = trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''));
  $clientMarital = match ($client['marital_status'] ?? 'soltero') {
    'casado'     => 'Casado/a',
    'divorciado' => 'Divorciado/a',
    'viudo'      => 'Viudo/a',
    default      => 'Soltero/a',
  };
  // Profesión: campo profession primero, luego occupation como fallback
  $clientProf = trim($client['profession'] ?? '');
  if ($clientProf === '') $clientProf = trim($client['occupation'] ?? '');

  // phone = celular, phone2 = teléfono fijo
  $clientCelular = $client['phone']  ?? '';
  $clientPhone2  = $client['phone2'] ?? '';

  $loanType  = $loan['loan_type'] ?? 'A';
  $loanTypes = ['A' => 'Cuota Nivelada', 'B' => 'Variable Decreciente', 'C' => 'Interés Simple Mensual'];
  $loanTypeLabel = $loanTypes[$loanType] ?? 'Cuota Nivelada';

  $amount      = (float)($loan['principal'] ?? 0);
  $amountFmt   = number_format($amount, 2);
  $intRate     = number_format((float)($loan['interest_rate'] ?? 0) * 100, 2);
  $lateFeeRate = number_format((float)($loan['late_fee_rate'] ?? 0) * 100, 2);
  $term        = $loan['term_months'] ?? 1;
  $freq        = match ($loan['payment_frequency'] ?? 'monthly') {
    'weekly'      => 'Semanal',
    'biweekly'    => 'Quincenal',
    'monthly'     => 'Mensual',
    'quarterly'   => 'Trimestral',
    'semiannual'  => 'Semestral',
    'annual'      => 'Anual',
    default       => 'Mensual',
  };
  $disbDate   = $loan['disbursement_date'] ? date('d/m/Y', strtotime($loan['disbursement_date'])) : '___/___/______';
  $firstPay   = $loan['first_payment_date'] ? date('d/m/Y', strtotime($loan['first_payment_date'])) : '___/___/______';
  $maturity   = $loan['maturity_date'] ? date('d/m/Y', strtotime($loan['maturity_date'])) : '___/___/______';
  $graceDays  = $loan['grace_days'] ?? 0;

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

  // Método de pago
  $payMethod      = $loan['payment_method'] ?? 'cash';
  $payLocation    = $loan['payment_location'] ?? $companyAddr;
  $payMethodLabel = match ($payMethod) {
    'transfer' => 'Transferencia bancaria',
    'deposit'  => 'Depósito en cuenta',
    default    => 'Efectivo en caja',
  };

  // Imágenes de identidad del cliente (rutas relativas desde webroot)
  $identityFront = $client['identity_front_path'] ?? null;
  $identityBack  = $client['identity_back_path']  ?? null;

  // Imágenes de identidad del AVAL (compatibles con distintos nombres de llave)
  $avalFront = $aval['identity_front_path']
    ?? $aval['aval_identity_front_path']
    ?? null;

  $avalBack  = $aval['identity_back_path']
    ?? $aval['aval_identity_back_path']
    ?? null;

  // Número a palabras
  function numWords2(float $a): string
  {
    return numToWords2((int)$a);
  }
  function numToWords2(int $n): string
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
    $hunds = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    if ($n < 20) return $ones[$n];
    if ($n < 100) return $tens[intdiv($n, 10)] . ($n % 10 ? ' Y ' . $ones[$n % 10] : '');
    if ($n === 100) return 'CIEN';
    if ($n < 1000) return $hunds[intdiv($n, 100)] . ($n % 100 ? ' ' . numToWords2($n % 100) : '');
    if ($n < 2000) return 'MIL' . ($n % 1000 ? ' ' . numToWords2($n % 1000) : '');
    if ($n < 1000000) return numToWords2(intdiv($n, 1000)) . ' MIL' . ($n % 1000 ? ' ' . numToWords2($n % 1000) : '');
    return numToWords2(intdiv($n, 1000000)) . ' MILLONES' . ($n % 1000000 ? ' ' . numToWords2($n % 1000000) : '');
  }
  ?>

  <button class="print-btn no-print" onclick="window.print()">🖨 Imprimir Contrato</button>

  <h1>Contrato de Préstamo</h1>
  <h2><?= htmlspecialchars($companyName) ?></h2>
  <div class="subtitle">
    No. Contrato / Préstamo: <strong><?= htmlspecialchars($loan['loan_number'] ?? '') ?></strong> &nbsp;|&nbsp;
    Fecha: <?= date('d/m/Y') ?>
    <?php if ($companyRTN): ?>&nbsp;|&nbsp; RTN: <strong><?= htmlspecialchars($companyRTN) ?></strong><?php endif; ?>
  </div>

  <!-- I. PARTES -->
  <div class="clause-title">I. Partes del Contrato</div>
  <p>
    <strong>PARTE PRESTAMISTA (EL ACREEDOR):</strong>
    <?= htmlspecialchars($companyName ?: '___________________________________') ?>,
    <?php if ($companyRTN): ?>con RTN <strong><?= htmlspecialchars($companyRTN) ?></strong>, <?php endif; ?>
  con domicilio en <?= htmlspecialchars($companyAddr ?: '___________________________') ?>,
  <?php if ($companyPhone): ?>teléfono: <?= htmlspecialchars($companyPhone) ?>,<?php endif; ?>
  <?php if ($repName): ?>representada en este acto por <strong><?= htmlspecialchars($repName) ?></strong>,
  mayor de edad, de nacionalidad <?= htmlspecialchars($repNat) ?>,
  Tarjeta de Identidad No. <strong><?= htmlspecialchars($repIdentity) ?></strong>
  (en adelante <strong>"EL PRESTAMISTA"</strong> o <strong>"EL ACREEDOR"</strong>);<?php else: ?>(en adelante <strong>"EL PRESTAMISTA"</strong>);<?php endif; ?>
  </p>
  <p>
    <strong>PARTE PRESTATARIA (EL DEUDOR / CONTRATANTE):</strong>
    <strong><?= htmlspecialchars($clientName) ?></strong>,
    mayor de edad, estado civil: <strong><?= $clientMarital ?></strong>
    <?php if (!empty($client['spouse_name'])): ?>
      , cónyuge: <strong><?= htmlspecialchars($client['spouse_name']) ?></strong>
      <?php if (!empty($client['spouse_identity'])): ?>(Identidad: <?= htmlspecialchars($client['spouse_identity']) ?>)<?php endif; ?>
      <?php endif; ?>,
      profesión u oficio: <strong><?= htmlspecialchars($clientProf ?: '___________') ?></strong>,
      nacionalidad: <strong><?= htmlspecialchars($client['nationality'] ?? 'Hondureña') ?></strong>,
      Tarjeta de Identidad No. <strong><?= htmlspecialchars($client['identity_number'] ?? '__________________________') ?></strong>,
      domicilio: <strong><?= htmlspecialchars(trim(($client['address'] ?? '') . ' ' . ($client['city'] ?? '')) ?: '______________________________') ?></strong>,
      celular: <strong><?= htmlspecialchars($clientCelular ?: '____________') ?></strong>
      <?php if ($clientPhone2): ?>, teléfono fijo: <strong><?= htmlspecialchars($clientPhone2) ?></strong><?php endif; ?>
    <?php if ($client['email'] ?? ''): ?>, correo: <strong><?= htmlspecialchars($client['email']) ?></strong><?php endif; ?>
  (en adelante <strong>"EL DEUDOR"</strong> o <strong>"EL CONTRATANTE"</strong>).
  </p>

  <!-- II. OBJETO -->
  <div class="clause-title">II. Objeto del Contrato</div>
  <p>
    EL PRESTAMISTA otorga a EL DEUDOR un préstamo personal por la cantidad de
    <strong><?= strtoupper(numWords2($amount)) ?> LEMPIRAS EXACTOS
      (<?= $currency ?> <?= $amountFmt ?>)</strong>,
    bajo la modalidad <strong><?= htmlspecialchars($loanTypeLabel) ?></strong>,
    conforme a las condiciones establecidas en el presente instrumento.
  </p>

  <!-- III. CONDICIONES FINANCIERAS -->
  <div class="clause-title">III. Condiciones Financieras</div>
  <table class="datos">
    <tr>
      <td>Monto del Préstamo</td>
      <td><?= $currency ?> <?= $amountFmt ?></td>
    </tr>
    <tr>
      <td>Tasa de Interés</td>
      <td><?= $intRate ?>% mensual</td>
    </tr>
    <tr>
      <td>Tasa por Mora</td>
      <td><?= $lateFeeRate ?>% mensual sobre saldo</td>
    </tr>
    <tr>
      <td>Modalidad</td>
      <td><?= htmlspecialchars($loanTypeLabel) ?></td>
    </tr>
    <tr>
      <td>Plazo</td>
      <td><?= $term ?> cuota<?= $term > 1 ? 's' : '' ?></td>
    </tr>
    <tr>
      <td>Frecuencia de Pago</td>
      <td><?= $freq ?></td>
    </tr>
    <tr>
      <td>Fecha de Desembolso</td>
      <td><?= $disbDate ?></td>
    </tr>
    <tr>
      <td>Fecha Primer Pago</td>
      <td><?= $firstPay ?></td>
    </tr>
    <tr>
      <td>Fecha de Vencimiento</td>
      <td><?= $maturity ?></td>
    </tr>
    <tr>
      <td>Días de Gracia</td>
      <td><?= $graceDays ?> días</td>
    </tr>
    <tr>
      <td>Forma de Pago</td>
      <td><?= htmlspecialchars($payMethodLabel) ?></td>
    </tr>
    <tr>
      <td>Lugar de Pago</td>
      <td><?= htmlspecialchars($payLocation ?: $companyAddr) ?></td>
    </tr>
  </table>

  <!-- IV. OBLIGACIONES -->
  <div class="clause-title">IV. Obligaciones del Deudor</div>
  <p>
    EL DEUDOR se obliga a: (a) cancelar las cuotas en las fechas pactadas en el lugar o cuenta
    indicada; (b) notificar cambios de domicilio o trabajo dentro de los 5 días hábiles siguientes;
    (c) mantener vigente la garantía ofrecida durante toda la vigencia del préstamo;
    (d) permitir al prestamista verificar el estado de los bienes dados en garantía;
    (e) no contraer nuevas deudas que comprometan su capacidad de pago sin autorización escrita del
    prestamista.
  </p>

  <!-- V. MORA -->
  <div class="clause-title">V. Mora e Intereses Moratorios</div>
  <p>
    El incumplimiento en el pago generará automáticamente un interés moratorio del
    <strong><?= $lateFeeRate ?>%</strong> mensual sobre el saldo pendiente, sin necesidad
    de requerimiento previo, aplicable desde el primer día de atraso hasta la cancelación total.
    Los pagos realizados se aplicarán primero a gastos de cobranza, luego a intereses
    (corrientes y moratorios) y finalmente a capital.
  </p>
  <?php if ($graceDays > 0): ?>
    <p>Se establece un período de gracia de <strong><?= $graceDays ?> días</strong> a partir del
      vencimiento antes de aplicar cargos por mora.</p>
  <?php endif; ?>

  <!-- VI. VENCIMIENTO ANTICIPADO -->
  <div class="clause-title">VI. Vencimiento Anticipado</div>
  <p>
    EL PRESTAMISTA podrá dar por vencido anticipadamente el préstamo en caso de: (a) mora
    de dos o más cuotas consecutivas; (b) falsedad en los datos proporcionados;
    (c) deterioro significativo de la garantía; (d) inicio de procesos concursales contra EL DEUDOR;
    (e) incumplimiento de cualquier cláusula del presente contrato.
  </p>

  <!-- VII. MEDIDAS DE COBRANZA -->
  <div class="clause-title">VII. Medidas de Cobranza y Ejecución</div>
  <p>
    En caso de incumplimiento, EL ACREEDOR queda facultado para: (a) gestionar el cobro
    extrajudicialmente a través de las vías administrativas disponibles; (b) ceder el crédito a terceros
    o empresas de cobranza; (c) ejecutar judicialmente el presente instrumento como título ejecutivo
    ante los tribunales competentes, siendo todos los gastos judiciales y honorarios de abogado por
    cuenta exclusiva de EL DEUDOR.
  </p>

  <?php if ($guarantee): ?>
    <!-- VIII. GARANTÍA -->
    <div class="clause-title">VIII. Garantía</div>
    <div class="garantia-box">
      <p><strong>Tipo de Garantía:</strong>
        <?= ucfirst(htmlspecialchars($guarantee['guarantee_type'])) ?></p>
      <?php if ($guarantee['guarantee_type'] === 'vehiculo'): ?>
        <p>
          <strong>Vehículo:</strong>
          <?= htmlspecialchars($guarantee['brand'] ?? '') ?>
          <?= htmlspecialchars($guarantee['model'] ?? '') ?>
          (<?= htmlspecialchars($guarantee['year'] ?? '') ?>),
          Placa: <strong><?= htmlspecialchars($guarantee['plate'] ?? '') ?></strong>,
          Color: <?= htmlspecialchars($guarantee['color'] ?? '') ?>,
          Serie: <?= htmlspecialchars($guarantee['serial'] ?? '') ?>.
        </p>
      <?php else: ?>
        <p><strong>Descripción:</strong> <?= htmlspecialchars($guarantee['description'] ?? '') ?></p>
      <?php endif; ?>
      <?php if ($guarantee['estimated_value']): ?>
        <p><strong>Valor Estimado:</strong> <?= $currency ?> <?= number_format((float)$guarantee['estimated_value'], 2) ?></p>
      <?php endif; ?>
      <p style="font-size:10pt;margin-top:6px">
        EL DEUDOR declara ser el legítimo propietario del bien dado en garantía, libre de gravámenes
        y se compromete a no enajenar, gravar ni deteriorar el mismo sin autorización escrita del prestamista.
      </p>
    </div>
  <?php endif; ?>

  <?php if ($aval): ?>
    <!-- AVAL -->
    <?php $avalClause = $guarantee ? 'IX' : 'VIII'; ?>

    <!-- Si querés forzar que la sección AVAL pase a nueva página: usa force-next-page -->
    <div class="avoid-break">
      <div class="clause-title"><?= $avalClause ?>. Aval y Fianza Solidaria</div>

      <div class="aval-box avoid-break">
        <p>
          Comparece <strong><?= htmlspecialchars($aval['full_name']) ?></strong>,
          mayor de edad, con Tarjeta de Identidad No.
          <strong><?= htmlspecialchars($aval['identity_number'] ?? '___________________') ?></strong>,
          con domicilio en <strong><?= htmlspecialchars($aval['address'] ?? '___________________') ?></strong>,
          teléfono <strong><?= htmlspecialchars($aval['phone'] ?? '___________') ?></strong>,
          quien se constituye como <strong>AVAL Y FIADOR SOLIDARIO</strong> de
          <strong><?= htmlspecialchars($clientName) ?></strong> respecto del presente contrato.
        </p>

        <p style="margin-top:6px">
          El AVAL se obliga de forma <strong>solidaria, ilimitada e incondicional</strong> al cumplimiento de todas las
          obligaciones derivadas de este préstamo, incluyendo <strong>capital</strong> por
          <strong><?= $currency ?> <?= $amountFmt ?></strong>,
          <strong>intereses corrientes</strong>, <strong>intereses moratorios</strong>,
          <strong>gastos de cobranza</strong>, <strong>honorarios profesionales</strong> y cualquier cargo permitido por este contrato,
          hasta su cancelación total.
        </p>

        <p style="margin-top:6px">
          El AVAL <strong>renuncia expresamente</strong> a los beneficios de orden, excusión y división,
          aceptando que EL PRESTAMISTA pueda exigirle el pago <strong>directamente</strong>,
          sin necesidad de requerimiento previo al DEUDOR. Asimismo, declara conocer las condiciones financieras,
          lugar y forma de pago establecidas en la Sección III del presente contrato.
        </p>
      </div>
    </div>
  <?php endif; ?>

  <!-- JURISDICCIÓN -->
  <?php
  $jClause = 'IX';
  if ($guarantee && $aval) $jClause = 'X';
  elseif ($guarantee || $aval) $jClause = 'IX';
  ?>
  <div class="clause-title"><?= $jClause ?>. Jurisdicción y Domicilio Especial</div>
  <p>
    Para todos los efectos legales del presente contrato, las partes señalan como domicilio especial
    la ciudad de <strong><?= htmlspecialchars($signCity ?: '______________________') ?></strong>
    y se someten expresamente a la jurisdicción del
    <strong><?= htmlspecialchars($jurisdiction) ?></strong> correspondiente,
    renunciando a su fuero domiciliar.
  </p>

  <!-- ACEPTACIÓN -->
  <?php $xClause = (int)$jClause + 1; ?>
  <div class="clause-title"><?= ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI'][$xClause] ?>. Aceptación</div>
  <p>
    Las partes declaran haber leído y entendido el presente contrato en todas sus partes,
    manifestando su conformidad al suscribirlo en la ciudad de
    <strong><?= htmlspecialchars($signCity ?: '______________________') ?></strong>,
    a los <strong><?= $today ?></strong> días del mes de <strong><?= $month ?></strong>
    del año <strong><?= $year ?></strong>.
  </p>

  <!-- ── FIRMAS ─────────────────────────────────────────────── -->
  <div class="firmas">
    <div class="firmas-row">
      <!-- Deudor -->
      <div class="firma-box">
        <div class="firma-line"></div>
        <div class="firma-label"><strong>EL DEUDOR / CONTRATANTE</strong></div>
        <div style="margin-top:6px;font-size:10pt">Nombre: <strong><?= htmlspecialchars($clientName) ?></strong></div>
        <div style="font-size:10pt">No. Identidad: <strong><?= htmlspecialchars($client['identity_number'] ?? '') ?></strong></div>
        <div style="margin-top:10px;font-size:10pt">HUELLA DIGITAL:</div>
        <div class="huella-wrap">
          <div class="huella-box"></div>
          <div class="huella-label">Huella</div>
        </div>
      </div>
      <!-- Prestamista / Apoderado -->
      <div class="firma-box">
        <div class="firma-line"></div>
        <div class="firma-label"><strong>EL PRESTAMISTA / APODERADO LEGAL</strong></div>
        <?php if ($repName): ?>
          <div style="margin-top:6px;font-size:10pt">Nombre: <strong><?= htmlspecialchars($repName) ?></strong></div>
          <div style="font-size:10pt">No. Identidad: <strong><?= htmlspecialchars($repIdentity) ?></strong></div>
        <?php else: ?>
          <div style="margin-top:6px;font-size:10pt"><strong><?= htmlspecialchars($companyName) ?></strong></div>
          <?php if ($companyRTN): ?><div style="font-size:10pt">RTN: <?= htmlspecialchars($companyRTN) ?></div><?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($aval): ?>
      <!-- Firma del Aval -->
      <div class="firmas-row" style="margin-top:50px">
        <div class="firma-box">
          <div class="firma-line"></div>
          <div class="firma-label"><strong>AVAL / FIADOR SOLIDARIO</strong></div>
          <div style="margin-top:6px;font-size:10pt">Nombre: <strong><?= htmlspecialchars($aval['full_name']) ?></strong></div>
          <div style="font-size:10pt">No. Identidad: <strong><?= htmlspecialchars($aval['identity_number'] ?? '') ?></strong></div>
          <div style="margin-top:10px;font-size:10pt">HUELLA DIGITAL:</div>
          <div class="huella-wrap">
            <div class="huella-box"></div>
            <div class="huella-label">Huella</div>
          </div>
        </div>
        <div class="firma-box"></div>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── ANEXO: COPIA DE IDENTIDAD ─────────────────────────── -->
  <!-- ── ANEXO: COPIA DE IDENTIDAD (DEUDOR + AVAL) ─────────── -->
  <?php
  $hasAvalImgs = ($aval && ($avalFront || $avalBack));
  ?>

  <?php if ($identityFront || $identityBack || $hasAvalImgs): ?>
    <div class="identity-page avoid-break <?= $hasAvalImgs ? 'has-aval' : '' ?>">

      <h1 style="font-size:12pt;margin-bottom:10px">Anexo: Copia de Documento de Identidad</h1>

      <!-- DEUDOR -->
      <?php if ($identityFront || $identityBack): ?>
        <p style="font-size:10pt;margin-bottom:6px">
          <strong>DEUDOR:</strong> <strong><?= e($clientName) ?></strong> &nbsp;|&nbsp;
          Identidad: <strong><?= e($client['identity_number'] ?? '') ?></strong>
        </p>

        <div class="identity-section" style="margin-bottom:12px">
          <div class="identity-grid">
            <?php if ($identityFront): ?>
              <div class="identity-card">
                <div class="identity-label">Frente:</div>
                <img src="<?= e(url('/' . ltrim($identityFront, '/'))) ?>" class="identity-img" alt="Identidad Deudor Frente">
              </div>
            <?php endif; ?>

            <?php if ($identityBack): ?>
              <div class="identity-card">
                <div class="identity-label">Reverso:</div>
                <img src="<?= e(url('/' . ltrim($identityBack, '/'))) ?>" class="identity-img" alt="Identidad Deudor Reverso">
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- AVAL -->
      <?php if ($hasAvalImgs): ?>
        <p style="font-size:10pt;margin:0 0 6px">
          <strong>AVAL:</strong> <strong><?= e($aval['full_name'] ?? '') ?></strong> &nbsp;|&nbsp;
          Identidad: <strong><?= e($aval['identity_number'] ?? '') ?></strong>
        </p>

        <div class="identity-section">
          <div class="identity-grid">
            <?php if ($avalFront): ?>
              <div class="identity-card">
                <div class="identity-label">Frente:</div>
                <img src="<?= e(url('/' . ltrim($avalFront, '/'))) ?>" class="identity-img" alt="Identidad Aval Frente">
              </div>
            <?php endif; ?>

            <?php if ($avalBack): ?>
              <div class="identity-card">
                <div class="identity-label">Reverso:</div>
                <img src="<?= e(url('/' . ltrim($avalBack, '/'))) ?>" class="identity-img" alt="Identidad Aval Reverso">
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  <?php endif; ?>

</body>

</html>