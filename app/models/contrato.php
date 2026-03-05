<?php
/**
 * VISTA: views/documents/contrato.php
 * Contrato de Préstamo - VERSIÓN PROFESIONAL
 * 
 * Características:
 * - Diseño profesional sin colores innecesarios
 * - Configuración de página CARTA o LEGAL
 * - Márgenes ajustables por settings
 * - Cláusula de protección de firma
 * - Monto total a pagar incluido
 * - Tabla de amortización como anexo
 * - Cuentas bancarias en Anexo II
 * - Imágenes de identidad centradas y ampliadas
 */
?>
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
      line-height: 1.6;
      counter-reset: page-num;
    }

    /* ═══════════════════════════════════════════════════════════ */
    /* ESCALADO AUTOMÁTICO POR TAMAÑO DE PÁGINA */
    /* ═══════════════════════════════════════════════════════════ */
    <?php 
      $pageSize = setting('contract_page_size', 'letter');
      if ($pageSize === 'letter'): 
    ?>
      /* LETTER: 8.5" x 11" - REDUCE LETRA 8% PARA FIRMAS */
      body { 
        font-size: 10.6pt; 
      }
      h1 { 
        font-size: 12.88pt; 
      }
      h2 { 
        font-size: 11.04pt; 
      }
      .clause-title { 
        font-size: 10.58pt; 
      }
      .subtitle {
        font-size: 9.2pt;
      }
      p { 
        margin-bottom: 8px; 
      }
      table.datos { 
        font-size: 9.66pt; 
      }
      table.datos td {
        padding: 5px 6px;
      }
      table.amortizacion { 
        font-size: 8.7pt; 
      }
      table.amortizacion th { 
        padding: 5px 3px; 
      }
      table.amortizacion td { 
        padding: 3px 4px; 
      }
      .info-box { 
        padding: 9px 10px; 
        margin: 8px 0; 
        font-size: 9.66pt;
      }
      .info-box li {
        margin: 3px 0;
      }
      .banco-item {
        margin: 6px 0;
        padding: 6px 0;
        font-size: 9.66pt;
      }
      .firmas {
        margin-top: 60px;
      }
      .firma-line {
        margin-top: 50px;
      }
      .firma-label {
        font-size: 9pt;
      }
      .firma-info {
        font-size: 9pt;
      }
      .huella-box {
        width: 60px;
        height: 60px;
      }
      .huella-label {
        font-size: 7.5pt;
      }
      .identity-img {
        max-width: 200px;
      }
    <?php elseif ($pageSize === 'legal'): ?>
      /* LEGAL: 8.5" x 14" - MANTIENE TAMAÑO NORMAL */
      /* Sin cambios */
    <?php elseif ($pageSize === 'a4'): ?>
      /* A4: 210mm x 297mm - REDUCE LETRA 5% */
      body { 
        font-size: 10.93pt; 
      }
      h1 { 
        font-size: 13.3pt; 
      }
      h2 { 
        font-size: 11.4pt; 
      }
      .clause-title { 
        font-size: 10.93pt; 
      }
      .subtitle {
        font-size: 9.5pt;
      }
      table.datos { 
        font-size: 10pt; 
      }
      table.amortizacion { 
        font-size: 9pt; 
      }
      .info-box { 
        font-size: 10pt;
      }
    <?php endif; ?>

    @media screen {
      body {
        padding: <?= setting('contract_margin_top', '2cm') ?> <?= setting('contract_margin_right', '2.5cm') ?>;
        background: #f5f5f5;
      }
    }

    body {
      counter-reset: page-counter;
    }

    .page {
      background: white;
      margin-bottom: 20px;
      padding: <?= setting('contract_margin_top', '2cm') ?> <?= setting('contract_margin_right', '2.5cm') ?>;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      counter-increment: page-counter;
      position: relative;
    }

    .page::after {
      content: "Página " counter(page-counter);
      position: absolute;
      bottom: 15px;
      right: <?= setting('contract_margin_right', '2.5cm') ?>;
      font-size: 9pt;
      color: #666;
      border-top: 1px solid #ddd;
      padding-top: 8px;
      width: calc(100% - <?= setting('contract_margin_right', '2.5cm') ?> - <?= setting('contract_margin_right', '2.5cm') ?>);
      text-align: right;
    }

    h1 {
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 4px;
      font-weight: bold;
    }

    h2 {
      text-align: center;
      margin-bottom: 18px;
      font-style: italic;
    }

    .subtitle {
      text-align: center;
      color: #444;
      margin-bottom: 20px;
      border-bottom: 1px solid #999;
      padding-bottom: 10px;
    }

    .clause-title {
      font-weight: bold;
      text-transform: uppercase;
      margin: 16px 0 8px;
      color: #000;
    }

    p {
      text-align: justify;
      line-height: 1.7;
      margin-bottom: 10px;
    }

    table.datos {
      width: 100%;
      border-collapse: collapse;
      margin: 12px 0;
    }

    table.datos td {
      border: 1px solid #999;
    }

    table.datos td:first-child {
      font-weight: bold;
      width: 38%;
      background: #f9f9f9;
    }

    table.amortizacion {
      width: 100%;
      border-collapse: collapse;
      margin: 12px 0;
    }

    table.amortizacion th {
      background: #f0f0f0;
      color: #000;
      text-align: center;
      font-weight: bold;
      border: 1px solid #999;
    }

    table.amortizacion td {
      border: 1px solid #ddd;
      text-align: right;
    }

    table.amortizacion td:first-child {
      text-align: center;
    }

    table.amortizacion tr:nth-child(even) {
      background: #fafafa;
    }

    .info-box {
      border: 1px solid #999;
      padding: 10px 12px;
      margin: 10px 0;
      border-radius: 0;
      background: #fff;
    }

    .info-box ul {
      margin: 6px 0 0 20px;
      padding: 0;
    }

    .info-box li {
      margin: 4px 0;
    }

    .banco-item {
      margin: 8px 0;
      padding: 8px 0;
      border-bottom: 1px solid #ddd;
    }

    .banco-item:last-child {
      border-bottom: none;
    }

    .firmas {
      margin-top: 80px;
      page-break-inside: avoid;
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
      margin-top: 70px;
      margin-bottom: 6px;
    }

    .firma-label {
      font-weight: bold;
    }

    .firma-info {
      margin-top: 4px;
      line-height: 1.4;
    }

    .huella-wrap {
      display: inline-block;
      text-align: center;
      margin-top: 8px;
    }

    .huella-box {
      border: 1px dashed #666;
      display: block;
    }

    .huella-label {
      color: #555;
      margin-top: 3px;
    }

    /* ESTILOS PARA IDENTIDADES - CENTRADAS Y UNA SOBRE OTRA */
    .identity-img {
      border: 1px solid #999;
      margin: 12px auto;
      border-radius: 0;
      max-width: 100%;
      width: 280px;
      height: auto;
      display: block;
    }

    .identity-grid {
      display: flex;
      flex-direction: column;
      gap: 20px;
      align-items: center;
      justify-content: center;
      padding: 20px 0;
    }

    .identity-card {
      width: 100%;
      text-align: center;
    }

    .identity-card p {
      text-align: center;
      margin-bottom: 10px;
    }

    @media print {
      body {
        padding: 0;
        background: white;
      }

      .page {
        margin: 0;
        padding: <?= setting('contract_margin_top', '1.5cm') ?> <?= setting('contract_margin_right', '2cm') ?> 3cm;
        box-shadow: none;
        page-break-after: always;
      }

      .page::after {
        position: fixed;
        bottom: 0.8cm;
        right: 2cm;
        font-size: 9pt;
        color: #666;
        border: none;
        padding: 0;
        width: auto;
        text-align: right;
      }

      .no-print {
        display: none !important;
      }

      @page {
        size: <?= setting('contract_page_size', 'letter') ?>;
        margin: <?= setting('contract_margin_top', '1.5cm') ?>;
      }

      .page-break {
        page-break-before: always;
      }

      .avoid-break {
        page-break-inside: avoid;
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

    .no-print {
      margin: 20px 0;
    }

    .page-break-before {
      page-break-before: always;
    }

    .avoid-break {
      page-break-inside: avoid;
    }
  </style>
</head>

<body>

  <?php
  // ─── VARIABLES ────────────────────────────────────────────────────
  
  $currency    = setting('app_currency', 'L');
  $companyName = setting('company_legal_name', setting('app_name', ''));
  $companyRTN  = setting('company_rtn', '');
  $companyAddr = setting('company_address', '');
  $companyCity = setting('company_city', '');
  $companyPhone = setting('company_phone', '');
  $repName     = setting('company_rep_name', '');
  $repIdentity = setting('company_rep_identity', '');
  $repNat      = setting('company_rep_nationality', 'Hondureña');
  $jurisdiction = setting('contract_jurisdiction', 'Juzgado de Letras de lo Civil');

  $client    = $client    ?? [];
  $loan      = $loan      ?? [];
  $aval      = $aval      ?? null;
  $guarantee = $guarantee ?? null;
  $installments = isset($installments) ? $installments : [];

  $clientName    = trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''));
  $clientMarital = match ($client['marital_status'] ?? 'soltero') {
    'casado'     => 'Casado/a',
    'divorciado' => 'Divorciado/a',
    'viudo'      => 'Viudo/a',
    default      => 'Soltero/a',
  };
  $clientProf  = trim($client['profession'] ?? '');
  if ($clientProf === '') $clientProf = trim($client['occupation'] ?? '');
  $clientCelular = $client['phone']  ?? '';
  $clientPhone2  = $client['phone2'] ?? '';
  $clientNat     = $client['nationality'] ?? 'Hondureña';
  $clientId      = $client['identity_number'] ?? '';
  $clientAddr    = $client['address'] ?? '';
  $clientCity    = $client['city'] ?? '';
  $clientEmail   = $client['email'] ?? '';

  $loanType  = $loan['loan_type'] ?? 'A';
  $loanTypes = ['A' => 'Cuota Nivelada', 'B' => 'Variable Decreciente', 'C' => 'Interés Simple Mensual'];
  $loanTypeLabel = $loanTypes[$loanType] ?? 'Cuota Nivelada';

  $amount      = (float)($loan['principal'] ?? 0);
  $amountFmt   = number_format($amount, 2);
  $intRate     = number_format((float)($loan['interest_rate'] ?? 0) * 100, 2);
  $lateFeeRate = number_format((float)($loan['late_fee_rate'] ?? 0) * 100, 2);
  $term        = $loan['term_months'] ?? 1;
  $freq        = match ($loan['payment_frequency'] ?? 'monthly') {
    'weekly'      => 'Semanal (7 días)',
    'biweekly'    => 'Quincenal (15 días)',
    'monthly'     => 'Mensual (30 días)',
    'bimonthly'   => 'Bimensual (60 días)',
    'quarterly'   => 'Trimestral (90 días)',
    'semiannual'  => 'Semestral (180 días)',
    'annual'      => 'Anual (365 días)',
    default       => 'Mensual',
  };
  $disbDate   = $loan['disbursement_date'] ? date('d/m/Y', strtotime($loan['disbursement_date'])) : '___/___/______';
  $firstPay   = $loan['first_payment_date'] ? date('d/m/Y', strtotime($loan['first_payment_date'])) : '___/___/______';
  $maturity   = $loan['maturity_date'] ? date('d/m/Y', strtotime($loan['maturity_date'])) : '___/___/______';
  $graceDays  = $loan['grace_days'] ?? 0;

  // Totales
  $totalPrincipal = array_sum(array_column($installments, 'principal_amount'));
  $totalInterest = array_sum(array_column($installments, 'interest_amount'));
  $totalPay = $totalPrincipal + $totalInterest;
  $totalPayFmt = number_format($totalPay, 2);

  // Meses en español
  $spanishMonths = [1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',
                     7=>'JULIO',8=>'AGOSTO',9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'];
  $today    = date('d');
  $month    = $spanishMonths[(int)date('n')];
  $year     = date('Y');

  // Métodos de pago
  $payMethods = [
    'cash'     => !empty($loan['payment_method_cash']),
    'transfer' => !empty($loan['payment_method_transfer']),
    'check'    => !empty($loan['payment_method_check']),
    'atm'      => !empty($loan['payment_method_atm']),
  ];

  // Cuentas bancarias
  $bankAccounts = array_filter([
    setting('bank_name_1') ? ['bank' => setting('bank_name_1'), 'account' => setting('bank_account_1'), 
                               'holder' => setting('bank_account_holder_1'), 'type' => setting('bank_account_type_1')] : null,
    setting('bank_name_2') ? ['bank' => setting('bank_name_2'), 'account' => setting('bank_account_2'),
                               'holder' => setting('bank_account_holder_2'), 'type' => setting('bank_account_type_2')] : null,
    setting('bank_name_3') ? ['bank' => setting('bank_name_3'), 'account' => setting('bank_account_3'),
                               'holder' => setting('bank_account_holder_3'), 'type' => setting('bank_account_type_3')] : null,
  ]);

  // Imágenes de identidad
  $identityFront = $client['identity_front_path'] ?? null;
  $identityBack  = $client['identity_back_path']  ?? null;
  $avalFront = $aval['identity_front_path'] ?? $aval['aval_identity_front_path'] ?? null;
  $avalBack  = $aval['identity_back_path']  ?? $aval['aval_identity_back_path'] ?? null;

  // Helper: número a palabras
  function numToWords(int $n): string {
    if ($n === 0) return 'CERO';
    $ones = ['','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE','DIEZ',
             'ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];
    $tens = ['','','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
    $hundreds = ['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS','SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];
    if ($n < 20) return $ones[$n];
    if ($n < 100) return $tens[intdiv($n, 10)] . ($n % 10 ? ' Y ' . $ones[$n % 10] : '');
    if ($n === 100) return 'CIEN';
    if ($n < 1000) return $hundreds[intdiv($n, 100)] . ($n % 100 ? ' ' . numToWords($n % 100) : '');
    if ($n < 2000) return 'MIL' . ($n % 1000 ? ' ' . numToWords($n % 1000) : '');
    if ($n < 1000000) return numToWords(intdiv($n, 1000)) . ' MIL' . ($n % 1000 ? ' ' . numToWords($n % 1000) : '');
    return numToWords(intdiv($n, 1000000)) . ' MILLONES' . ($n % 1000000 ? ' ' . numToWords($n % 1000000) : '');
  }
  ?>

  <button class="print-btn no-print" onclick="window.print()">🖨 Imprimir Contrato</button>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- PÁGINA 1: CONTRATO PRINCIPAL -->
  <!-- ═══════════════════════════════════════════════════════════════ -->

  <div class="page">
    <h1>Contrato de Préstamo</h1>
    <h2><?= e($companyName) ?></h2>
    <div class="subtitle">
      Número de Contrato: <strong><?= e($loan['loan_number'] ?? '') ?></strong> | 
      Fecha: <?= date('d/m/Y') ?>
      <?php if ($companyRTN): ?> | RTN: <strong><?= e($companyRTN) ?></strong><?php endif; ?>
    </div>

    <!-- I. PARTES -->
    <div class="clause-title">I. Partes del Contrato</div>
    <p>
      <strong>PARTE PRESTAMISTA (ACREEDOR):</strong>
      <?= e($companyName ?: '___________________________________') ?>,
      <?php if ($companyRTN): ?>con RTN <strong><?= e($companyRTN) ?></strong>,<?php endif; ?>
      con domicilio en <?= e($companyAddr ?: '___________________________') ?>,
      <?php if ($companyPhone): ?>teléfono: <?= e($companyPhone) ?>,<?php endif; ?>
      <?php if ($repName): ?>representada por <strong><?= e($repName) ?></strong>, 
      <?= e($repNat) ?>, Tarjeta de Identidad No. <strong><?= e($repIdentity) ?></strong>
      (en adelante <strong>"EL PRESTAMISTA"</strong>);<?php else: ?>(en adelante <strong>"EL PRESTAMISTA"</strong>);<?php endif; ?>
    </p>
    <p>
      <strong>PARTE PRESTATARIA (DEUDOR):</strong>
      <strong><?= e($clientName) ?></strong>, mayor de edad, estado civil: <strong><?= $clientMarital ?></strong>
      <?php if (!empty($client['spouse_name'])): ?>, 
      cónyuge: <strong><?= e($client['spouse_name']) ?></strong>
      <?php if (!empty($client['spouse_identity'])): ?>(Identidad: <?= e($client['spouse_identity']) ?>)<?php endif; ?>
      <?php endif; ?>,
      profesión: <strong><?= e($clientProf ?: '___________') ?></strong>,
      nacionalidad: <strong><?= e($clientNat) ?></strong>,
      Tarjeta de Identidad: <strong><?= e($clientId ?: '__________________________') ?></strong>,
      domicilio: <strong><?= e(trim($clientAddr . ' ' . $clientCity) ?: '______________________________') ?></strong>,
      celular: <strong><?= e($clientCelular ?: '____________') ?></strong>
      <?php if ($clientPhone2): ?>, teléfono: <strong><?= e($clientPhone2) ?></strong><?php endif; ?>
      <?php if ($clientEmail): ?>, correo: <strong><?= e($clientEmail) ?></strong><?php endif; ?>
      (en adelante <strong>"EL DEUDOR"</strong>).
    </p>

    <!-- II. OBJETO -->
    <div class="clause-title">II. Objeto del Contrato</div>
    <p>
      EL PRESTAMISTA otorga a EL DEUDOR un préstamo por la cantidad de
      <strong><?= strtoupper(numToWords((int)$amount)) ?> LEMPIRAS EXACTOS
      (<?= $currency ?> <?= $amountFmt ?>)</strong>,
      bajo la modalidad <strong><?= e($loanTypeLabel) ?></strong>,
      conforme a las condiciones establecidas en este contrato.
    </p>

    <!-- III. CONDICIONES FINANCIERAS -->
    <div class="clause-title">III. Condiciones Financieras</div>
    <table class="datos">
      <tr><td>Monto del Préstamo</td><td><?= $currency ?> <?= $amountFmt ?></td></tr>
      <tr><td>Tasa de Interés</td><td><?= $intRate ?>% mensual</td></tr>
      <tr><td>Tasa por Mora</td><td><?= $lateFeeRate ?>% mensual sobre saldo</td></tr>
      <tr><td>Modalidad de Pago</td><td><?= e($loanTypeLabel) ?></td></tr>
      <tr><td>Plazo</td><td><?= $term ?> cuota<?= $term > 1 ? 's' : '' ?> <?= $freq ?></td></tr>
      <tr><td>Fecha de Desembolso</td><td><?= $disbDate ?></td></tr>
      <tr><td>Primer Pago Vencimiento</td><td><?= $firstPay ?></td></tr>
      <tr><td>Último Pago Vencimiento</td><td><?= $maturity ?></td></tr>
      <tr><td>Días de Gracia</td><td><?= $graceDays ?> días</td></tr>
      <tr style="background: #f9f9f9; font-weight: bold;"><td>MONTO TOTAL A PAGAR</td><td><?= $currency ?> <?= $totalPayFmt ?></td></tr>
    </table>

    <!-- IV. OBLIGACIONES DEL DEUDOR -->
    <div class="clause-title">IV. Obligaciones del Deudor</div>
    <p>
      EL DEUDOR se obliga a: (a) cancelar las cuotas en las fechas pactadas y según los métodos indicados;
      (b) notificar cambios de domicilio o contacto dentro de 5 días hábiles;
      (c) mantener vigente cualquier garantía ofrecida;
      (d) permitir verificación del estado de bienes en garantía;
      (e) no contraer deudas que comprometan su capacidad de pago sin autorización escrita.
    </p>

    <!-- V. MORA -->
    <div class="clause-title">V. Mora e Intereses Moratorios</div>
    <p>
      El incumplimiento en el pago generará automáticamente un interés moratorio del
      <strong><?= $lateFeeRate ?>%</strong> mensual sobre el saldo pendiente, sin requerimiento previo,
      aplicable desde el primer día de atraso hasta la cancelación total.
      <?php if ($graceDays > 0): ?>Se establece un período de gracia de <strong><?= $graceDays ?> días</strong>.<?php endif; ?>
      Los pagos se aplicarán primero a gastos, luego a intereses (corrientes y moratorios), y finalmente a capital.
    </p>

    <!-- VI. MÉTODOS DE PAGO -->
    <div class="clause-title">VI. Métodos de Pago Aceptados</div>
    <div class="info-box avoid-break">
      <p style="margin: 0 0 8px; font-weight: bold;">EL DEUDOR puede realizar pagos mediante:</p>
      <ul>
        <?php if ($payMethods['cash']): ?><li>✓ Efectivo</li><?php endif; ?>
        <?php if ($payMethods['transfer']): ?><li>✓ Transferencia Bancaria</li><?php endif; ?>
        <?php if ($payMethods['check']): ?><li>✓ Cheque a nombre de <?= e($companyName) ?></li><?php endif; ?>
        <?php if ($payMethods['atm']): ?><li>✓ Depósito ATM</li><?php endif; ?>
      </ul>
    </div>

    <!-- VII. GARANTÍA -->
    <?php if ($guarantee): ?>
    <div class="clause-title">VII. Garantía Prendaria</div>
    <div class="info-box avoid-break">
      <p><strong>Tipo:</strong> <?= ucfirst(e($guarantee['guarantee_type'])) ?></p>
      <?php if ($guarantee['guarantee_type'] === 'vehiculo'): ?>
      <p style="margin: 6px 0;">
        <strong>Vehículo:</strong> <?= e($guarantee['brand'] ?? '') ?> <?= e($guarantee['model'] ?? '') ?> (<?= e($guarantee['year'] ?? '') ?>),
        Placa: <strong><?= e($guarantee['plate'] ?? '') ?></strong>,
        Color: <?= e($guarantee['color'] ?? '') ?>,
        Serie: <?= e($guarantee['serial'] ?? '') ?>.
      </p>
      <?php else: ?>
      <p style="margin: 6px 0;"><strong>Descripción:</strong> <?= e($guarantee['description'] ?? '') ?></p>
      <?php endif; ?>
      <?php if ($guarantee['estimated_value']): ?>
      <p style="margin: 6px 0;"><strong>Valor Estimado:</strong> <?= $currency ?> <?= number_format((float)$guarantee['estimated_value'], 2) ?></p>
      <?php endif; ?>
      <p style="font-size:10pt;margin-top:6px;border-top:1px solid #999;padding-top:6px">
        EL DEUDOR declara ser legítimo propietario del bien, libre de gravámenes, y no enajena,
        gravará ni deteriorará el mismo sin autorización escrita del prestamista.
      </p>
    </div>
    <?php endif; ?>

    <!-- AVAL -->
    <?php if ($aval): ?>
    <?php $avalClause = $guarantee ? 'VIII' : 'VII'; ?>
    <div class="clause-title"><?= $avalClause ?>. Aval y Fianza Solidaria</div>
    <div class="info-box avoid-break">
      <p>
        <strong><?= e($aval['full_name']) ?></strong>, mayor de edad, Tarjeta de Identidad:
        <strong><?= e($aval['identity_number'] ?? '___________________') ?></strong>,
        domicilio: <strong><?= e($aval['address'] ?? '___________________') ?></strong>,
        teléfono: <strong><?= e($aval['phone'] ?? '___________') ?></strong>,
        se constituye como <strong>AVAL Y FIADOR SOLIDARIO</strong> de <?= e($clientName) ?>.
      </p>
      <p style="margin-top:6px">
        El AVAL se obliga de forma <strong>solidaria, ilimitada e incondicional</strong> al cumplimiento de todas las
        obligaciones (capital, intereses corrientes, moratorios, gastos de cobranza y honorarios),
        hasta su cancelación total. <strong>Renuncia a beneficios de orden, excusión y división</strong>,
        aceptando que EL PRESTAMISTA exija el pago directamente, sin requerimiento previo al DEUDOR.
      </p>
    </div>
    <?php endif; ?>

    <!-- CLÁUSULA DE JURISDICCIÓN Y COMPETENCIA -->
    <div class="clause-title">VIII<?= $aval ? 'I' : '' ?>. Jurisdicción y Competencia</div>
    <p>
      Ambas partes se someten voluntariamente a la jurisdicción competente del <?= e($jurisdiction) ?> 
      correspondiente a la sede donde se ejecute este contrato. Se renuncia a cualquier otra jurisdicción 
      que pudiera corresponder por domicilio u otra razón, debiendo dirigirse cualquier demanda ante los 
      tribunales indicados. En caso de incumplimiento, este documento constituye título ejecutivo 
      y puede ser presentado ante los tribunales para ejecución directa.
    </p>

    <!-- CLÁUSULA DE PROTECCIÓN DE FIRMA -->
    <div class="clause-title">IX<?= $aval ? 'I' : '' ?>. Validez y Protección de Firmas</div>
    <p>
      Las firmas y huellas dactilares estampadas en este contrato son reconocidas como auténticas 
      por ambas partes. Ambas declaran haber leído y comprendido íntegramente el contenido de este 
      documento en sus términos, acepta sus obligaciones y derechos derivados. En caso de 
      repudiación o controversia respecto a autenticidad de firmas, el deudor se somete a 
      análisis grafológico a su costo. Cualquier enmienda o adición fuera del formato impreso 
      es nula y no vinculante.
    </p>

  </div>

  <!-- FIRMAS - PÁGINA SEPARADA PARA PROTEGER CORTES -->
  <div class="page page-break-before">
    <h1 style="font-size: 12pt; text-align: center; text-transform: uppercase; margin-bottom: 30px;">
      Firmas y Reconocimiento de Obligaciones
    </h1>

    <p style="text-align: center; font-size: 10pt; color: #666; margin-bottom: 30px;">
      Contrato No. <?= e($loan['loan_number']) ?>
    </p>

    <div class="firmas avoid-break">
      <div class="firmas-row">
        <div class="firma-box">
          <div class="firma-line"></div>
          <div class="firma-label">DEUDOR / CONTRATANTE</div>
          <div class="firma-info">
            <strong><?= e($clientName) ?></strong><br>
            Identidad: <?= e($clientId) ?>
          </div>
          <div style="margin-top: 20px; font-size: 10pt;">HUELLA DIGITAL:</div>
          <div class="huella-wrap">
            <div class="huella-box"></div>
            <div class="huella-label">Huella dedo índice derecho</div>
          </div>
        </div>
        <div class="firma-box">
          <div class="firma-line"></div>
          <div class="firma-label">PRESTAMISTA / APODERADO</div>
          <?php if ($repName): ?>
          <div class="firma-info">
            <strong><?= e($repName) ?></strong><br>
            Identidad: <?= e($repIdentity) ?>
          </div>
          <?php else: ?>
          <div class="firma-info">
            <strong><?= e($companyName) ?></strong><br>
            <?php if ($companyRTN): ?>RTN: <?= e($companyRTN) ?><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($aval): ?>
      <div class="firmas-row" style="margin-top: 80px;">
        <div class="firma-box">
          <div class="firma-line"></div>
          <div class="firma-label">AVAL / FIADOR SOLIDARIO</div>
          <div class="firma-info">
            <strong><?= e($aval['full_name']) ?></strong><br>
            Identidad: <?= e($aval['identity_number'] ?? '') ?>
          </div>
          <div style="margin-top: 20px; font-size: 10pt;">HUELLA DIGITAL:</div>
          <div class="huella-wrap">
            <div class="huella-box"></div>
            <div class="huella-label">Huella dedo índice derecho</div>
          </div>
        </div>
        <div class="firma-box"></div>
      </div>
      <?php endif; ?>
    </div>

    <p style="margin-top: 40px; font-size: 10pt; text-align: center; color: #666;">
      Firmado en <?= e($companyCity ?: '______________________________') ?>, 
      a los <?= $today ?> días del mes de <?= $month ?> del año <?= $year ?>.
    </p>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- PÁGINA 3: ANEXO I - TABLA DE AMORTIZACIÓN -->
  <!-- ═══════════════════════════════════════════════════════════════ -->

  <?php if (!empty($installments)): ?>
  <div class="page page-break-before">
    <h1 style="font-size: 12pt;">Anexo I – Tabla de Amortización Completa</h1>
    <p style="text-align: center; font-size: 10pt; color: #666; margin-bottom: 16px;">
      Contrato: <?= e($loan['loan_number']) ?> | Deudor: <?= e($clientName) ?>
    </p>

    <!-- Resumen Financiero -->
    <table class="datos" style="margin-bottom: 16px;">
      <tr>
        <td>Capital Prestado</td>
        <td><?= $currency ?> <?= number_format($totalPrincipal, 2) ?></td>
      </tr>
      <tr>
        <td>Interés Total</td>
        <td><?= $currency ?> <?= number_format($totalInterest, 2) ?></td>
      </tr>
      <tr style="font-weight: bold;">
        <td>TOTAL A PAGAR</td>
        <td><?= $currency ?> <?= number_format($totalPay, 2) ?></td>
      </tr>
    </table>

    <!-- Tabla de Amortización -->
    <table class="amortizacion">
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha Vence</th>
          <th>Capital</th>
          <th>Interés</th>
          <th>Total Cuota</th>
          <th>Saldo</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $displayRows = count($installments) > 20 
          ? array_merge(array_slice($installments, 0, 10), [null], array_slice($installments, -9))
          : $installments;

        foreach ($displayRows as $inst):
          if ($inst === null):
        ?>
        <tr>
          <td colspan="6" style="text-align: center; font-size: 9pt; color: #999;">
            ... (<?= count($installments) - 19 ?> cuotas intermedias) ...
          </td>
        </tr>
        <?php
            continue;
          endif;
        ?>
        <tr>
          <td><?= $inst['installment_number'] ?></td>
          <td><?= date('d/m/Y', strtotime($inst['due_date'])) ?></td>
          <td><?= $currency ?> <?= number_format($inst['principal_amount'], 2) ?></td>
          <td><?= $currency ?> <?= number_format($inst['interest_amount'], 2) ?></td>
          <td><?= $currency ?> <?= number_format($inst['total_amount'], 2) ?></td>
          <td><?= $currency ?> <?= number_format($inst['balance_after'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <p style="font-size: 9pt; color: #666; margin-top: 12px;">
      <em>Nota: Tabla de amortización proyectada. Los montos pueden variar por pagos anticipados, parciales o por mora.</em>
    </p>
  </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- PÁGINA 4: ANEXO II - CUENTAS BANCARIAS PARA TRANSFERENCIAS -->
  <!-- ═══════════════════════════════════════════════════════════════ -->

  <?php if ($payMethods['transfer'] && !empty($bankAccounts)): ?>
  <div class="page page-break-before avoid-break">
    <h1 style="font-size: 12pt;">Anexo II – Cuentas Bancarias para Transferencias</h1>
    <p style="text-align: center; font-size: 10pt; color: #666; margin-bottom: 20px;">
      Contrato: <?= e($loan['loan_number']) ?> | Deudor: <?= e($clientName) ?>
    </p>

    <?php foreach ($bankAccounts as $idx => $acc): ?>
    <div class="info-box avoid-break">
      <p style="margin: 0 0 8px; font-weight: bold;">Cuenta <?= $idx + 1 ?></p>
      <div class="banco-item" style="border-bottom: 1px solid #999;">
        <strong>Banco:</strong> <?= e($acc['bank']) ?>
      </div>
      <div class="banco-item">
        <strong>Titular de la Cuenta:</strong> <?= e($acc['holder']) ?>
      </div>
      <div class="banco-item">
        <strong>Número de Cuenta:</strong> <code style="font-family: monospace; background: #f9f9f9; padding: 2px 4px;"><?= e($acc['account']) ?></code>
      </div>
      <div class="banco-item">
        <strong>Tipo de Cuenta:</strong> <?= $acc['type'] === 'checking' ? 'Corriente' : 'Ahorros' ?>
      </div>
    </div>
    <?php endforeach; ?>

    <p style="font-size: 9.5pt; color: #666; margin-top: 16px; border-top: 1px solid #999; padding-top: 10px;">
      <strong>Instrucciones de Pago:</strong> Al realizar una transferencia bancaria, favor indicar 
      el número de contrato <strong><?= e($loan['loan_number']) ?></strong> en la referencia o concepto 
      de la transferencia para una correcta identificación del pago.
    </p>
  </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- PÁGINA 5: ANEXO III - COPIAS DE IDENTIDAD -->
  <!-- ═══════════════════════════════════════════════════════════════ -->

  <?php if ($identityFront || $identityBack || ($aval && ($avalFront || $avalBack))): ?>
  <div class="page page-break-before">
    <h1 style="font-size: 12pt; margin-bottom: 8px;">Anexo III – Copias de Documento de Identidad</h1>
    <p style="text-align: center; font-size: 10pt; color: #666; margin-bottom: 24px;">
      Contrato: <?= e($loan['loan_number']) ?>
    </p>

    <!-- DEUDOR -->
    <?php if ($identityFront || $identityBack): ?>
    <div style="margin-bottom: 32px; page-break-inside: avoid;">
      <p style="font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 20px; border-bottom: 1px solid #999; padding-bottom: 12px;">
        DEUDOR: <?= e($clientName) ?><br><span style="font-size: 10pt;">Identidad: <?= e($clientId) ?></span>
      </p>
      <div class="identity-grid">
        <?php if ($identityFront): ?>
        <div class="identity-card">
          <p style="font-size: 10pt; margin-bottom: 8px;"><strong>FRENTE</strong></p>
          <img src="<?= e(url('/' . ltrim($identityFront, '/'))) ?>" class="identity-img" alt="Identidad Deudor Frente">
        </div>
        <?php endif; ?>
        <?php if ($identityBack): ?>
        <div class="identity-card">
          <p style="font-size: 10pt; margin-bottom: 8px;"><strong>REVERSO</strong></p>
          <img src="<?= e(url('/' . ltrim($identityBack, '/'))) ?>" class="identity-img" alt="Identidad Deudor Reverso">
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- AVAL -->
    <?php if ($aval && ($avalFront || $avalBack)): ?>
    <div style="page-break-inside: avoid;">
      <p style="font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 20px; border-bottom: 1px solid #999; padding-bottom: 12px;">
        AVAL - FIADOR SOLIDARIO: <?= e($aval['full_name'] ?? '') ?><br><span style="font-size: 10pt;">Identidad: <?= e($aval['identity_number'] ?? '') ?></span>
      </p>
      <div class="identity-grid">
        <?php if ($avalFront): ?>
        <div class="identity-card">
          <p style="font-size: 10pt; margin-bottom: 8px;"><strong>FRENTE</strong></p>
          <img src="<?= e(url('/' . ltrim($avalFront, '/'))) ?>" class="identity-img" alt="Identidad Aval Frente">
        </div>
        <?php endif; ?>
        <?php if ($avalBack): ?>
        <div class="identity-card">
          <p style="font-size: 10pt; margin-bottom: 8px;"><strong>REVERSO</strong></p>
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