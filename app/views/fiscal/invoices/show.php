<?php
// app/views/fiscal/invoices/show.php
// Factura Fiscal — SAR Honduras — Diseño para impresión/PDF
// Sin layout (renderiza standalone)

$currency    = setting('app_currency', 'L');
$companyName = setting('company_legal_name', setting('app_name', ''));
$companyRTN  = setting('company_rtn', '');
$companyAddr = setting('company_address', '') . ' ' . setting('company_city', '');
$companyPhone = setting('company_phone', '');
$repName     = setting('company_rep_name', '');

$isVoided    = $invoice['status'] === 'voided';
$isAdmin     = \App\Core\Auth::isAdmin();

// Número en letras (solo parte entera + centavos)
$totalInt  = (int)$invoice['total'];
$totalCent = round(($invoice['total'] - $totalInt) * 100);
$totalLetras = ($invoice['total_letras'] ?? '') ?: 'VER TOTAL';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Factura <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
        }

        @media screen {
            body {
                background: #e5e7eb;
                padding: 20px;
            }

            .invoice-wrap {
                max-width: 820px;
                margin: 0 auto;
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: letter;
                margin: 1cm;
            }
        }

        .invoice-wrap {
            background: #fff;
            border: 1px solid #ccc;
            padding: 14px 16px;
        }

        /* HEADER */
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .inv-company {
            flex: 1;
        }

        .inv-company h2 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .inv-company p {
            font-size: 8.5pt;
            line-height: 1.5;
        }

        .inv-title-box {
            text-align: right;
            min-width: 140px;
        }

        .inv-title-box .factura-label {
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #1a3a5c;
        }

        .inv-title-box .inv-num {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 4px;
        }

        .inv-title-box .inv-date {
            font-size: 9pt;
        }

        /* DATOS FISCALES */
        .cai-box {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }

        .cai-box span {
            font-family: monospace;
            font-weight: bold;
        }

        /* CLIENTE */
        .client-section {
            border: 1px solid #999;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 9pt;
        }

        .client-section .client-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px 20px;
        }

        .client-section .label {
            font-weight: bold;
        }

        /* TABLA PRINCIPAL */
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9pt;
        }

        .inv-table th {
            background: #1a3a5c;
            color: #fff;
            padding: 5px 8px;
            text-align: center;
            font-size: 8.5pt;
        }

        .inv-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #eee;
        }

        .inv-table td.text-right {
            text-align: right;
        }

        .inv-table tr.total-row td {
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            background: #f8f9fa;
        }

        .inv-table tr.saldo-row td {
            font-weight: bold;
            color: #1a3a5c;
            background: #e8f0fe;
        }

        /* FOOTER FISCAL */
        .inv-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 6px;
        }

        .letras-box {
            border: 1px solid #999;
            padding: 6px 10px;
            font-size: 8.5pt;
        }

        .letras-box .letras-title {
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
            color: #666;
        }

        .letras-box .letras-text {
            font-style: italic;
            margin-top: 3px;
        }

        .totales-box {
            border: 1px solid #999;
            padding: 0;
        }

        .totales-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }

        .totales-box td {
            padding: 3px 8px;
            border-bottom: 1px solid #eee;
        }

        .totales-box td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .totales-box .gran-total td {
            font-weight: bold;
            font-size: 10pt;
            background: #1a3a5c;
            color: #fff;
            border-bottom: none;
        }

        /* BMT */
        .bmt-section {
            border-top: 1px solid #ddd;
            margin-top: 8px;
            padding-top: 6px;
            font-size: 7.5pt;
            color: #666;
            text-align: center;
        }

        /* ANULADO */
        .voided-stamp {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 64pt;
            font-weight: 900;
            color: rgba(220, 38, 38, 0.15);
            pointer-events: none;
            z-index: 999;
            letter-spacing: 4px;
        }

        /* ACCIONES */
        .action-bar {
            background: #fff;
            border-bottom: 1px solid #ddd;
            padding: 10px 16px;
            display: flex;
            gap: 8px;
            align-items: center;
            max-width: 820px;
            margin: 0 auto 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 14px;
            border-radius: 5px;
            font-size: 9pt;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-secondary {
            background: #6b7280;
            color: #fff;
        }

        .btn-danger {
            background: #ef4444;
            color: #fff;
        }

        .btn-outline {
            background: #fff;
            color: #374151;
            border-color: #d1d5db;
        }
    </style>
</head>

<body>

    <?php if ($isVoided): ?>
        <div class="voided-stamp">ANULADA</div>
    <?php endif; ?>

    <!-- BARRA DE ACCIONES (no se imprime) -->
    <div class="action-bar no-print">
        <a href="<?= url('/invoices') ?>" class="btn btn-outline">
            ← Volver
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            🖨 Imprimir / PDF
        </button>
        <?php if ($invoice['payment_id']): ?>
            <a href="<?= url('/payments/' . $invoice['payment_id']) ?>" class="btn btn-outline">
                Ver Pago
            </a>
        <?php endif; ?>
        <?php if ($isAdmin && !$isVoided): ?>
            <button onclick="showVoidModal()" class="btn btn-danger">
                ✕ Anular Factura
            </button>
        <?php endif; ?>
        <?php if ($isVoided): ?>
            <span style="color:#ef4444;font-weight:bold;font-size:9pt">
                ⚠ FACTURA ANULADA — <?= date('d/m/Y H:i', strtotime($invoice['voided_at'])) ?>
                — <?= htmlspecialchars($invoice['void_reason'] ?? '') ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="invoice-wrap">

        <!-- ═══ ENCABEZADO ════════════════════════════════════════════ -->
        <div class="inv-header">
            <div class="inv-company">
                <h2><?= htmlspecialchars($companyName) ?></h2>
                <p>
                    RTN: <strong><?= htmlspecialchars($companyRTN) ?></strong><br>
                    <?= htmlspecialchars(trim($companyAddr)) ?><br>
                    <?php if ($companyPhone): ?>Tel: <?= htmlspecialchars($companyPhone) ?><br><?php endif; ?>
                <?php if ($repName): ?>Rep. Legal: <?= htmlspecialchars($repName) ?><?php endif; ?>
                </p>
            </div>
            <div class="inv-title-box">
                <div class="factura-label">FACTURA</div>
                <div class="inv-num">N° <?= htmlspecialchars($invoice['invoice_number']) ?></div>
                <div class="inv-date">Fecha: <?= date('d/m/Y', strtotime($invoice['invoice_date'])) ?></div>
            </div>
        </div>

        <!-- ═══ DATOS FISCALES (CAI) ══════════════════════════════════ -->
        <div class="cai-box">
            <strong>CAI:</strong> <span><?= htmlspecialchars($cai['cai_code'] ?? '') ?></span>
            &nbsp;&nbsp;&nbsp;
            <strong>Fecha Límite de Emisión:</strong>
            <?= $cai ? date('d/m/Y', strtotime($cai['limit_date'])) : '—' ?>
            <br>
            <strong>Rango Autorizado:</strong>
            <span><?= htmlspecialchars($cai['range_from'] ?? '') ?></span>
            AL
            <span><?= htmlspecialchars($cai['range_to'] ?? '') ?></span>
        </div>

        <!-- ═══ DATOS DEL CLIENTE ════════════════════════════════════ -->
        <div class="client-section">
            <div class="client-grid">
                <div>
                    <span class="label">Cliente:</span>
                    <?= htmlspecialchars($invoice['client_name']) ?>
                </div>
                <div>
                    <span class="label">RTN:</span>
                    <?= htmlspecialchars($invoice['client_rtn'] ?? 'Consumidor Final') ?>
                </div>
                <div>
                    <span class="label">Dirección:</span>
                    <?= htmlspecialchars($invoice['client_address'] ?? '—') ?>
                </div>
                <div>
                    <span class="label">Teléfono:</span>
                    <?= htmlspecialchars($invoice['client_phone'] ?? '—') ?>
                </div>
                <div>
                    <span class="label">Préstamo:</span>
                    <?= htmlspecialchars($invoice['loan_number']) ?>
                </div>
                <div>
                    <span class="label">Referencia pago:</span>
                    <?= $payment ? htmlspecialchars($payment['payment_number']) : '—' ?>
                </div>
            </div>
        </div>

        <!-- ═══ TABLA PRINCIPAL ═══════════════════════════════════════ -->
        <table class="inv-table">
            <thead>
                <tr>
                    <th style="width:50%;text-align:left">DESCRIPCIÓN</th>
                    <th style="width:16%">P. UNIT.</th>
                    <th style="width:16%">EXENTA</th>
                    <th style="width:18%">GRAVADA</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($invoice['saldo_anterior'] > 0): ?>
                    <tr>
                        <td>Saldo Anterior</td>
                        <td class="text-right"><?= $currency ?> <?= number_format($invoice['saldo_anterior'], 2) ?></td>
                        <td class="text-right"><?= number_format($invoice['saldo_anterior'], 2) ?></td>
                        <td class="text-right"></td>
                    </tr>
                <?php endif; ?>

                <?php if ($invoice['interes_corriente'] > 0): ?>
                    <tr>
                        <td>Más Intereses Corrientes</td>
                        <td class="text-right"><?= $currency ?> <?= number_format($invoice['interes_corriente'], 2) ?></td>
                        <td class="text-right"><?= number_format($invoice['interes_corriente'], 2) ?></td>
                        <td class="text-right"></td>
                    </tr>
                <?php endif; ?>

                <?php if ($invoice['interes_moratorio'] > 0): ?>
                    <tr>
                        <td>Intereses Moratorios</td>
                        <td class="text-right"><?= $currency ?> <?= number_format($invoice['interes_moratorio'], 2) ?></td>
                        <td class="text-right"><?= number_format($invoice['interes_moratorio'], 2) ?></td>
                        <td class="text-right"></td>
                    </tr>
                <?php endif; ?>

                <?php if ($invoice['otros_cargos'] > 0): ?>
                    <tr>
                        <td>Otros Cargos</td>
                        <td class="text-right"><?= $currency ?> <?= number_format($invoice['otros_cargos'], 2) ?></td>
                        <td class="text-right"></td>
                        <td class="text-right"><?= number_format($invoice['otros_cargos'], 2) ?></td>
                    </tr>
                <?php endif; ?>

                <!-- Filas vacías para dar espacio (como factura física) -->
                <tr>
                    <td colspan="4" style="height:18px"></td>
                </tr>
                <tr>
                    <td colspan="4" style="height:18px"></td>
                </tr>

                <tr class="total-row">
                    <td>Total</td>
                    <td class="text-right"><?= $currency ?> <?= number_format($invoice['subtotal'], 2) ?></td>
                    <td class="text-right"><?= number_format($invoice['exempt_amount'], 2) ?></td>
                    <td class="text-right"><?= number_format($invoice['taxable_15'], 2) ?></td>
                </tr>
                <tr>
                    <td><strong>Abono a Capital</strong></td>
                    <td class="text-right"><strong><?= $currency ?>
                            <?= number_format($invoice['abono_capital'], 2) ?></strong></td>
                    <td class="text-right"><?= number_format($invoice['abono_capital'], 2) ?></td>
                    <td></td>
                </tr>
                <tr class="saldo-row">
                    <td><strong>Nuevo Saldo</strong></td>
                    <td class="text-right"><strong><?= $currency ?>
                            <?= number_format($invoice['nuevo_saldo'], 2) ?></strong></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- ═══ FOOTER FISCAL ════════════════════════════════════════ -->
        <div class="inv-footer">
            <!-- Cantidad en letras -->
            <div class="letras-box">
                <div class="letras-title">Cantidad en Letras:</div>
                <div class="letras-text">
                    <?= htmlspecialchars($invoice['total_letras'] ?? '') ?>
                    <?php if ($totalCent > 0): ?> CON <?= str_pad($totalCent, 2, '0', STR_PAD_LEFT) ?>/100<?php else: ?>
                        CON 00/100<?php endif; ?>
                        LEMPIRAS
                </div>
                <?php if ($invoice['notes']): ?>
                    <div style="margin-top:6px;font-size:8pt;color:#666">
                        <strong>Notas:</strong> <?= htmlspecialchars($invoice['notes']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($isVoided): ?>
                    <div style="margin-top:8px;padding:6px;background:#fee2e2;border:1px solid #ef4444;font-size:8pt">
                        <strong style="color:#ef4444">⚠ FACTURA ANULADA</strong><br>
                        Motivo: <?= htmlspecialchars($invoice['void_reason'] ?? '—') ?><br>
                        <?php if ($invoice['void_comment']): ?>
                            Comentario: <?= htmlspecialchars($invoice['void_comment']) ?><br>
                        <?php endif; ?>
                        Anulada por: <?= htmlspecialchars($invoice['voided_by_name'] ?? '—') ?>
                        el <?= date('d/m/Y H:i', strtotime($invoice['voided_at'])) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Totales fiscales -->
            <div class="totales-box">
                <table>
                    <tr>
                        <td>Descuentos / Rebajas Otorgados</td>
                        <td><?= $currency ?> 0.00</td>
                    </tr>
                    <tr>
                        <td>Importe Exonerado L.</td>
                        <td>0.00</td>
                    </tr>
                    <tr>
                        <td>Importe Exento L.</td>
                        <td><?= number_format($invoice['exempt_amount'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Importe Gravado 15% L.</td>
                        <td><?= number_format($invoice['taxable_15'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Importe Gravado 18% L.</td>
                        <td><?= number_format($invoice['taxable_18'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Sub - Total L.</td>
                        <td><?= number_format($invoice['subtotal'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>15% I.S.V. L.</td>
                        <td><?= number_format($invoice['isv_15'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>18% I.S.V. L.</td>
                        <td><?= number_format($invoice['isv_18'] ?? 0, 2) ?></td>
                    </tr>
                    <tr class="gran-total">
                        <td>GRAN TOTAL L.</td>
                        <td><?= number_format($invoice['total'], 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ═══ BMT / IMPRENTA ═══════════════════════════════════════ -->
        <div class="bmt-section">
            <?php if ($cai && $cai['bmt_name']): ?>
                Facturado por: <strong><?= htmlspecialchars($cai['bmt_name']) ?></strong>
                &nbsp;|&nbsp;
            <?php endif; ?>
            <?php if ($cai && $cai['bmt_rtn']): ?>
                RTN: <strong><?= htmlspecialchars($cai['bmt_rtn']) ?></strong>
                &nbsp;|&nbsp;
            <?php endif; ?>
            <?php if ($cai && $cai['cert_number']): ?>
                No. Certificado: <strong><?= htmlspecialchars($cai['cert_number']) ?></strong>
                &nbsp;|&nbsp;
            <?php endif; ?>
            Emitida: <?= date('d/m/Y H:i', strtotime($invoice['created_at'])) ?>
            &nbsp;|&nbsp;
            <?= htmlspecialchars($invoice['created_by_name'] ?? '') ?>
        </div>

    </div><!-- /invoice-wrap -->

    <!-- ═══ MODAL ANULACIÓN ════════════════════════════════════════ -->
    <?php if ($isAdmin && !$isVoided): ?>
        <div id="voidModal"
            style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
            <div
                style="background:#fff;border-radius:10px;padding:24px;max-width:420px;width:90%;box-shadow:0 20px 40px rgba(0,0,0,.3)">
                <h5 style="margin-bottom:16px;color:#ef4444">⚠ Anular Factura
                    <?= htmlspecialchars($invoice['invoice_number']) ?></h5>
                <form method="POST" action="<?= url('/invoices/' . $invoice['id'] . '/void') ?>">
                    <?= \App\Core\CSRF::field() ?>
                    <div style="margin-bottom:12px">
                        <label style="font-size:9pt;font-weight:600;display:block;margin-bottom:4px">
                            Tu contraseña (verificación) *
                        </label>
                        <input type="password" name="confirm_password" required
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;font-size:10pt">
                    </div>
                    <div style="margin-bottom:12px">
                        <label style="font-size:9pt;font-weight:600;display:block;margin-bottom:4px">
                            Motivo de anulación *
                        </label>
                        <input type="text" name="void_reason" required minlength="5" maxlength="200"
                            placeholder="Ej: Error en datos del cliente"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;font-size:10pt">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="font-size:9pt;font-weight:600;display:block;margin-bottom:4px">
                            Comentario adicional (opcional)
                        </label>
                        <textarea name="void_comment" rows="3" maxlength="500"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;font-size:10pt;resize:vertical"></textarea>
                    </div>
                    <div style="display:flex;gap:8px;justify-content:flex-end">
                        <button type="button" onclick="closeVoidModal()"
                            style="padding:8px 16px;background:#6b7280;color:#fff;border:none;border-radius:5px;cursor:pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            style="padding:8px 16px;background:#ef4444;color:#fff;border:none;border-radius:5px;cursor:pointer;font-weight:600">
                            Confirmar Anulación
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            function showVoidModal() {
                document.getElementById('voidModal').style.display = 'flex';
            }

            function closeVoidModal() {
                document.getElementById('voidModal').style.display = 'none';
            }
        </script>
    <?php endif; ?>

</body>

</html>