<?php

/**
 * VISTA: views/documents/pagare.php
 * Pagaré - VERSIÓN AUTO-DETECCIÓN
 * - ✅ Detecta automáticamente las claves del array
 * - ✅ Calcula correctamente sin importar estructura
 * - ✅ Muestra tabla de amortización
 * - ✅ Firmas en PRIMERA PÁGINA
 */
?>
<!DOCTYPE html>
<html lang="es">
<!-- <div style="font-family: monospace; font-size: 11px; background:#fff3cd; padding:8px; margin-bottom:10px;">
  DEBUG: installments_type=<?= gettype($installments ?? null) ?> |
  installments_count=<?= is_countable($installments ?? null) ? count($installments) : -1 ?> |
  loan_interest_rate=<?= (float)($loan['interest_rate'] ?? 0) ?> |
  loan_term=<?= (int)($loan['term_months'] ?? 0) ?> |
  loan_principal=<?= (float)($loan['principal'] ?? 0) ?>
</div> -->

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
      font-size: 12pt;
      color: #000;
      background: #fff;
      line-height: 1.6;
    }

    /* ═══════════════════════════════════════════════════════════ */
    /* ESCALADO AUTOMÁTICO POR TAMAÑO DE PÁGINA */
    /* ═══════════════════════════════════════════════════════════ */
    <?php
    $pageSize = setting('pagare_page_size', 'letter');
    if ($pageSize === 'letter'):
    ?>

    /* LETTER: 8.5" x 11" - REDUCE LETRA 8% */
    body {
      font-size: 11pt;
    }

    h1 {
      font-size: 16.56pt;
    }

    .header-info {
      font-size: 9.2pt;
      margin-bottom: 12px;
      padding-bottom: 6px;
    }

    .monto-header {
      font-size: 14.72pt;
      padding: 10px 16px;
      margin: 0 auto 12px;
    }

    .monto-wrap {
      margin-bottom: 12px;
    }

    p {
      margin-bottom: 8px;
      font-size: 10.8pt;
    }

    .info-box {
      padding: 10px;
      margin: 10px 0;
    }

    .info-box h4 {
      margin: 0 0 6px;
      font-size: 9.8pt;
    }

    .clausulas {
      margin: 12px 0;
    }

    .clausulas li {
      margin: 5px 0;
      font-size: 9.8pt;
      line-height: 1.4;
    }

    .firmas {
      margin-top: 20px;
      page-break-inside: avoid;
    }

    .firma-line {
      margin-top: 35px;
      margin-bottom: 4px;
    }

    .firma-label {
      font-size: 8.5pt;
      margin-bottom: 2px;
    }

    .huella-box {
      width: 55px;
      height: 55px;
    }

    .huella-label {
      font-size: 6.5pt;
    }

    .firma-info {
      font-size: 8.5pt;
      margin-top: 2px;
      line-height: 1.2;
    }

    .firma-info div {
      margin: 1px 0;
      font-size: 8pt;
    }

    table.amortizacion {
      font-size: 8.2pt;
      margin: 10px 0;
    }

    table.amortizacion th {
      padding: 4px 2px;
    }

    table.amortizacion td {
      padding: 2px 3px;
    }

    <?php elseif ($pageSize === 'legal'): ?>

    /* LEGAL: más espacio para firmar */
    .firmas {
      margin-top: 85px;
    }

    /* separa la sección de firmas del texto */
    .firmas-fecha {
      margin-top: 28px;
    }
    .huella-wrap {
      display: inline-block;
      text-align: center;
      margin-top: 80px;
    }
    /* separa la fecha de las firmas */
    /* LEGAL: 8.5" x 14" - MANTIENE TAMAÑO NORMAL */
    /* Sin cambios - mantiene valores originales */
    <?php elseif ($pageSize === 'a4'): ?>

    /* A4: 210mm x 297mm - REDUCE LETRA 5% */
    body {
      font-size: 11.4pt;
    }

    h1 {
      font-size: 17.1pt;
    }

    .header-info {
      font-size: 9.5pt;
      margin-bottom: 13px;
      padding-bottom: 7px;
    }

    .monto-header {
      font-size: 15.2pt;
      padding: 11px 18px;
      margin: 0 auto 15px;
    }

    .monto-wrap {
      margin-bottom: 15px;
    }

    p {
      margin-bottom: 9px;
    }

    .info-box {
      padding: 11px;
      margin: 11px 0;
    }

    .clausulas {
      margin: 14px 0;
    }

    .clausulas li {
      margin: 6px 0;
      font-size: 10.45pt;
      line-height: 1.5;
    }

    .firmas {
      margin-top: 25px;
    }

    .firma-line {
      margin-top: 40px;
      margin-bottom: 5px;
    }

    .firma-label {
      font-size: 9.2pt;
    }

    .huella-box {
      width: 62px;
      height: 62px;
    }

    .huella-label {
      font-size: 7.2pt;
    }

    .firma-info {
      font-size: 9.2pt;
      margin-top: 3px;
      line-height: 1.3;
    }

    .firma-info div {
      margin: 1.5px 0;
      font-size: 8.5pt;
    }

    table.amortizacion {
      font-size: 9pt;
      margin: 11px 0;
    }

    table.amortizacion th {
      padding: 5px 3px;
    }

    table.amortizacion td {
      padding: 3px 4px;
    }

    <?php endif; ?>@media screen {
      body {
        padding: <?= setting('pagare_margin_top', '2cm') ?> <?= setting('pagare_margin_right', '2.5cm') ?>;
        background: #f5f5f5;
      }
    }

    .page {
      background: white;
      margin-bottom: 20px;
      padding: <?= setting('pagare_margin_top', '2cm') ?> <?= setting('pagare_margin_right', '2.5cm') ?>;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    h1 {
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 3px;
      margin-bottom: 8px;
      font-weight: bold;
    }

    .header-info {
      text-align: right;
      color: #555;
      margin-bottom: 16px;
      border-bottom: 1px solid #999;
      padding-bottom: 8px;
    }

    .monto-header {
      font-weight: bold;
      text-align: center;
      border: 2px solid #333;
      background: #fafafa;
      width: 100%;
    }

    .monto-wrap {
      text-align: center;
      margin-bottom: 20px;
    }

    p {
      text-align: justify;
      line-height: 1.75;
      margin-bottom: 10px;
    }

    .highlight {
      font-weight: bold;
    }

    .info-box {
      border: 1px solid #999;
      border-radius: 0;
      background: #fff;
    }

    .info-box h4 {
      margin: 0 0 8px;
      color: #000;
      font-weight: bold;
    }

    .info-box .item {
      line-height: 1.5;
    }

    .banco-item {
      margin: 8px 0;
      padding: 8px 0;
      border-bottom: 1px solid #ddd;
      font-size: 10.5pt;
    }

    .banco-item:last-child {
      border-bottom: none;
    }

    .clausulas {
      margin: 16px 0;
    }

    .clausulas ol {
      padding-left: 20px;
      margin: 0;
    }

    .clausulas li {
      line-height: 1.6;
    }

    .firmas {
      page-break-inside: avoid;
    }

    .firmas-row {
      display: flex;
      justify-content: space-between;
      gap: 30px;
    }

    .firma-box {
      flex: 1;
      text-align: center;
    }

    .firma-line {
      border-top: 1px solid #000;
      margin-bottom: 6px;
    }

    .firma-label {
      font-weight: bold;
    }

    .huella-wrap {
      display: inline-block;
      text-align: center;
      margin-top: 80px;
    }

    .huella-box {
      border: 1px dashed #666;
      display: block;
    }

    .huella-label {
      color: #555;
      margin-top: 2px;
    }

    .firma-info {
      margin-top: 6px;
    }

    .firma-info div {
      margin: 2px 0;
    }

    .firmas-fecha {
      text-align: center;
      margin-top: 20px;
      font-size: 10pt;
      color: #666;
    }

    table.amortizacion {
      width: 100%;
      border-collapse: collapse;
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

    @media print {
      body {
        padding: 0;
        background: white;
      }

      .page {
        margin: 0;
        padding: <?= setting('pagare_margin_top', '1.5cm') ?> <?= setting('pagare_margin_right', '2cm') ?>;
        box-shadow: none;
        page-break-after: always;
      }

      .no-print {
        display: none !important;
      }

      @page {
        size: <?= setting('pagare_page_size', 'letter') ?>;
        margin: <?= setting('pagare_margin_top', '1.5cm') ?>;
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

    .page-break {
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
  $repName     = setting('company_rep_name', '');
  $repIdentity = setting('company_rep_identity', '');
  $repNat      = setting('company_rep_nationality', 'Hondureña');
  $jurisdiction = setting('pagare_jurisdiction', 'Juzgado de Letras de lo Civil');

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
  $clientProf    = trim($client['profession'] ?? '') ?: trim($client['occupation'] ?? '');
  $clientNat     = $client['nationality'] ?? 'Hondureña';
  $clientId      = $client['identity_number'] ?? '';
  $clientAddr    = $client['address'] ?? '';
  $clientCity    = $client['city'] ?? '';
  $clientCelular = $client['phone']  ?? '';
  $clientPhone2  = $client['phone2'] ?? '';
  $clientEmail   = $client['email'] ?? '';

  $amount        = (float)($loan['principal'] ?? 0);
  $amountFmt     = number_format($amount, 2);
  $dueDate       = $loan['maturity_date'] ?? $loan['first_payment_date'] ?? '';
  $dueDateParts  = $dueDate ? explode('-', $dueDate) : ['____', '__', '__'];
  $lateFeeRate   = number_format((float)($loan['late_fee_rate'] ?? 0) * 100, 2);

  $installments = isset($installments) ? (array)$installments : [];

  $sumField = function (array $rows, array $keys): float {
    $sum = 0.0;
    foreach ($rows as $r) {
      if (!is_array($r)) continue;
      $val = null;
      foreach ($keys as $k) {
        if (isset($r[$k]) && $r[$k] !== '') {
          $val = $r[$k];
          break;
        }
      }
      $sum += (float)($val ?? 0);
    }
    return $sum;
  };

  // Totales (soporta cualquier estructura)
  $totalPrincipal = $sumField($installments, ['principal_amount', 'principal', 'capital']);
  $totalInterest  = $sumField($installments, ['interest_amount', 'interest', 'interes']);
  $totalToPay     = $sumField($installments, ['total_amount', 'total', 'cuota_total']);

  // echo "<!-- Debug Totales: principal=$totalPrincipal, interest=$totalInterest, total=$totalToPay -->";

  // Si no viene total por cuota, lo armamos
  if ($totalToPay <= 0 && ($totalPrincipal > 0 || $totalInterest > 0)) {
    $totalToPay = $totalPrincipal + $totalInterest;
  }

  // Fallback SOLO si NO hay cuotas reales
  if ($totalToPay <= 0 || empty($installments)) {
    $totalPrincipal = (float)$amount;
    $interestRate   = (float)($loan['interest_rate'] ?? 0);
    $termMonths     = (int)($loan['term_months'] ?? 1);

    // Si tu interest_rate viniera como 15 en vez de 0.15, descomenta esto:
    // if ($interestRate > 1) $interestRate = $interestRate / 100;

    $totalInterest  = $totalPrincipal * $interestRate * $termMonths;
    $totalToPay     = $totalPrincipal + $totalInterest;
  }

  // Meses en español
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
  $signCity = setting('pagare_city', setting('company_city', ''));

  // Métodos de pago
  $payMethods = [
    'cash'     => !empty($loan['payment_method_cash']),
    'transfer' => !empty($loan['payment_method_transfer']),
    'check'    => !empty($loan['payment_method_check']),
    'atm'      => !empty($loan['payment_method_atm']),
  ];

  // Cuentas bancarias
  $bankAccounts = array_filter([
    setting('bank_name_1') ? [
      'bank' => setting('bank_name_1'),
      'account' => setting('bank_account_1'),
      'holder' => setting('bank_account_holder_1'),
      'type' => setting('bank_account_type_1'),
    ] : null,
    setting('bank_name_2') ? [
      'bank' => setting('bank_name_2'),
      'account' => setting('bank_account_2'),
      'holder' => setting('bank_account_holder_2'),
      'type' => setting('bank_account_type_2'),
    ] : null,
    setting('bank_name_3') ? [
      'bank' => setting('bank_name_3'),
      'account' => setting('bank_account_3'),
      'holder' => setting('bank_account_holder_3'),
      'type' => setting('bank_account_type_3'),
    ] : null,
  ]);

  // Helper: número a palabras
  function numToWords(int $n): string
  {
    if ($n === 0) return 'CERO';
    $ones = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    $tens = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $hundreds = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    if ($n < 20) return $ones[$n];
    if ($n < 100) return $tens[intdiv($n, 10)] . ($n % 10 ? ' Y ' . $ones[$n % 10] : '');
    if ($n === 100) return 'CIEN';
    if ($n < 1000) return $hundreds[intdiv($n, 100)] . ($n % 100 ? ' ' . numToWords($n % 100) : '');
    if ($n < 2000) return 'MIL' . ($n % 1000 ? ' ' . numToWords($n % 1000) : '');
    if ($n < 1000000) return numToWords(intdiv($n, 1000)) . ' MIL' . ($n % 1000 ? ' ' . numToWords($n % 1000) : '');
    return numToWords(intdiv($n, 1000000)) . ' MILLONES' . ($n % 1000000 ? ' ' . numToWords($n % 1000000) : '');
  }
  $totalWords = strtoupper(numToWords((int)$totalToPay));
  ?>

  <button class="print-btn no-print" onclick="window.print()">🖨 Imprimir Pagaré</button>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- PÁGINA 1: PAGARÉ PRINCIPAL CON FIRMAS -->
  <!-- ═══════════════════════════════════════════════════════════════ -->

  <div class="page">
    <div class="header-info">
      Préstamo: <strong><?= e($loan['loan_number'] ?? '') ?></strong> |
      Fecha: <?= date('d/m/Y') ?>
    </div>

    <h1>PAGARÉ</h1>

    <div class="monto-wrap">
      <div class="monto-header">POR <?= $currency ?> <?= number_format($totalToPay, 2) ?></div>
    </div>

    <!-- DATOS DEL DEUDOR -->
    <p>
      Yo, <span class="highlight"><?= e($clientName) ?></span>, mayor de edad, estado civil:
      <strong><?= $clientMarital ?></strong>
      <?php if (!empty($client['spouse_name'])): ?>,
      cónyuge: <strong><?= e($client['spouse_name']) ?></strong>
      <?php endif; ?>,
      profesión u oficio: <strong><?= e($clientProf ?: '___________________') ?></strong>,
      nacionalidad: <strong><?= e($clientNat) ?></strong>,
      Tarjeta de Identidad No.: <strong><?= e($clientId ?: '____________________________') ?></strong>,
      con domicilio en: <strong><?= e(trim($clientAddr . ' ' . $clientCity) ?: '____________________________________________________________') ?></strong>,
      celular: <strong><?= e($clientCelular ?: '____________________') ?></strong>
      <?php if ($clientPhone2): ?>, teléfono: <strong><?= e($clientPhone2) ?></strong><?php endif; ?>.
    </p>

    <!-- CUERPO DEL PAGARÉ -->
    <p style="margin-top: 12px;">
      Por el presente <strong>PAGARÉ</strong>, <strong>HAGO CONSTAR</strong> que <strong>DEBO Y CANCELARÉ</strong>
      sin ningún requerimiento legal a:
      <strong><?= e($companyName ?: '_______________________________________________') ?></strong>
      <?php if ($repName): ?>,
      representada por <strong><?= e($repName) ?></strong>,
      Tarjeta de Identidad No. <strong><?= e($repIdentity) ?></strong>,
      nacionalidad: <strong><?= e($repNat) ?></strong>
      <?php endif; ?>;
      por la cantidad de <span class="highlight"><?= $totalWords ?> LEMPIRAS EXACTOS
        (<?= $currency ?> <?= number_format($totalToPay, 2) ?>)</span>,
      a pagar el día <strong><?= $dueDateParts[2] ?? '__' ?> / <?= $dueDateParts[1] ?? '__' ?> / <?= $dueDateParts[0] ?? '____' ?></strong>.
    </p>

    <p>
      En caso de incumplimiento, me someto a la jurisdicción del <strong><?= e($jurisdiction) ?></strong>
      competente y autorizo el cobro judicial como título ejecutivo extrajudicial.
    </p>

    <!-- CLÁUSULAS Y CONDICIONES -->
    <div class="clausulas avoid-break">
      <strong>Cláusulas y Condiciones:</strong>
      <ol>
        <li><strong>Interés Moratorio:</strong> <?= $lateFeeRate ?>% mensual sobre saldo pendiente.</li>
        <li><strong>Gastos de Cobro:</strong> El deudor asume todos los gastos, honorarios y costas judicales.</li>
        <li><strong>Aplicación de Pagos:</strong> Primero gastos, luego intereses, finalmente capital.</li>
        <li><strong>Notificaciones:</strong> Vía teléfono, SMS, domicilio y correo del deudor.</li>
      </ol>
    </div>

    <!-- ✅ FIRMAS EN PRIMERA PÁGINA -->
    <div class="firmas avoid-break">
      <div class="firmas-row">
        <div class="firma-box">
          <div class="firma-line"></div>
          <div class="firma-label">DEUDOR (Firma)</div>
          <div class="firma-info">
            <div><?= e($clientName) ?></div>
            <div>Identidad: <?= e($clientId) ?></div>
          </div>
          <div style="margin-top: 20px; font-size: 9pt;">HUELLA:</div>
          <div class="huella-wrap">
            <div class="huella-box"></div>
            <div class="huella-label">Índice derecho</div>
          </div>
        </div>

        <div class="firma-box">
          <div class="firma-line"></div>
          <div class="firma-label">ACREEDOR (Firma)</div>
          <div class="firma-info">
            <?php if ($repName): ?>
              <div><?= e($repName) ?></div>
              <div>Identidad: <?= e($repIdentity) ?></div>
            <?php else: ?>
              <div><?= e($companyName) ?></div>
              <?php if ($companyRTN): ?><div>RTN: <?= e($companyRTN) ?></div><?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="firmas-fecha">
        Firmado en <?= e($signCity ?: '______________________________') ?>,
        a los <?= $today ?> de <?= $month ?> de <?= $year ?>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- PÁGINA 2: AVAL (SI EXISTE) -->
  <!-- ═══════════════════════════════════════════════════════════════ -->

  <?php if ($aval): ?>
    <div class="page page-break avoid-break">
      <h1 style="font-size: 14pt; margin-bottom: 20px;">SECCIÓN DEL AVAL / FIADOR SOLIDARIO</h1>

      <p>
        Yo, <span class="highlight"><?= e($aval['full_name']) ?></span>, mayor de edad,
        Tarjeta de Identidad No.: <strong><?= e($aval['identity_number'] ?? '______________________') ?></strong>,
        con domicilio en: <strong><?= e($aval['address'] ?? '______________________') ?></strong>,
        teléfono: <strong><?= e($aval['phone'] ?? '___________') ?></strong>,
        por este acto <strong>me constituyo como AVAL Y FIADOR SOLIDARIO</strong> del deudor
        <strong><?= e($clientName) ?></strong> en relación con el presente pagaré por la suma de
        <span class="highlight"><?= $totalWords ?> LEMPIRAS EXACTOS (<?= $currency ?> <?= number_format($totalToPay, 2) ?>)</span>.
      </p>

      <p style="margin-top: 12px;">
        <strong>En consecuencia, me obligo solidariamente</strong> a pagar la totalidad de la deuda incluyendo:
        capital, intereses corrientes, intereses moratorios al <?= $lateFeeRate ?>% mensual, gastos y honorarios.
      </p>

      <p style="margin-top: 12px;">
        <strong>Renuncio expresamente</strong> a los beneficios de orden, excusión y división,
        aceptando que el acreedor pueda exigirme el pago directamente sin requerimiento al deudor principal.
      </p>

      <div class="firmas avoid-break" style="margin-top: 80px;">
        <div class="firmas-row">
          <div class="firma-box">
            <div class="firma-line"></div>
            <div class="firma-label">AVAL / FIADOR SOLIDARIO (Firma)</div>
            <div class="firma-info">
              <div><?= e($aval['full_name']) ?></div>
              <div>Identidad: <?= e($aval['identity_number'] ?? '') ?></div>
            </div>
            <div style="margin-top: 4px; font-size: 9pt;">HUELLA:</div>
            <div class="huella-wrap">
              <div class="huella-box"></div>
              <div class="huella-label">Índice derecho</div>
            </div>
          </div>
          <div class="firma-box"></div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- PÁGINA 3: ANEXO I - CUENTAS BANCARIAS PARA TRANSFERENCIAS -->
  <!-- ═══════════════════════════════════════════════════════════════ -->

  <?php if ($payMethods['transfer'] && !empty($bankAccounts)): ?>
    <div class="page page-break avoid-break">
      <h1 style="font-size: 12pt;">Anexo I – Cuentas Bancarias para Transferencias</h1>
      <p style="text-align: center; font-size: 10pt; color: #666; margin-bottom: 20px;">
        Pagaré: <?= e($loan['loan_number']) ?> | Deudor: <?= e($clientName) ?>
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
    </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- PÁGINA 4: ANEXO II - TABLA DE AMORTIZACIÓN -->
  <!-- ═══════════════════════════════════════════════════════════════ -->

  <?php if (count($installments) > 0): ?>
    <div class="page page-break avoid-break">
      <h1 style="font-size: 12pt;">Anexo II – Tabla de Amortización</h1>
      <p style="text-align: center; font-size: 10pt; color: #555; margin-bottom: 12px;">
        Pagaré: <?= e($loan['loan_number']) ?> | Deudor: <?= e($clientName) ?>
      </p>

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
            <?php continue;
            endif; ?>
            <tr>
              <td><?= $inst['installment_number'] ?? $inst['numero_cuota'] ?? '' ?></td>
              <td><?= date('d/m/Y', strtotime($inst['due_date'] ?? $inst['fecha_vencimiento'] ?? '')) ?></td>
              <td><?= $currency ?> <?= number_format($inst['principal_amount'] ?? $inst['principal'] ?? 0, 2) ?></td>
              <td><?= $currency ?> <?= number_format($inst['interest_amount'] ?? $inst['interest'] ?? $inst['interes'] ?? 0, 2) ?></td>
              <td><?= $currency ?> <?= number_format($inst['total_amount'] ?? $inst['total'] ?? 0, 2) ?></td>
              <td><?= $currency ?> <?= number_format($inst['balance_after'] ?? $inst['saldo'] ?? 0, 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <p style="font-size: 9pt; color: #555; margin-top: 12px; border-top: 1px solid #999; padding-top: 8px;">
        <strong>Totales:</strong> Capital: <?= $currency ?> <?= number_format($totalPrincipal, 2) ?> |
        Interés: <?= $currency ?> <?= number_format($totalInterest, 2) ?> |
        <strong>Total a Pagar: <?= $currency ?> <?= number_format($totalToPay, 2) ?></strong>
      </p>
    </div>
  <?php endif; ?>

</body>

</html>