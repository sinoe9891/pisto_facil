<?php
// app/controllers/InvoiceController.php
// Gestión de Facturas Fiscales — SAR Honduras

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, View, DB};

class InvoiceController extends Controller
{
    public function index(): void
    {
        $search = $this->get('search', '');
        $from   = $this->get('from', date('Y-m-01'));
        $to     = $this->get('to', date('Y-m-d'));
        $status = $this->get('status', '');

        $sql = "SELECT i.*, 
                       fc.cai_code,
                       CONCAT(c.first_name,' ',c.last_name) as client_full_name,
                       l.loan_number,
                       p.payment_number,
                       u.name as created_by_name
                FROM invoices i
                JOIN fiscal_cai fc ON fc.id = i.cai_id
                JOIN clients c     ON c.id  = i.client_id
                JOIN loans l       ON l.id  = i.loan_id
                LEFT JOIN payments p ON p.id = i.payment_id
                JOIN users u        ON u.id  = i.created_by
                WHERE i.invoice_date BETWEEN ? AND ?";
        $params = [$from, $to];

        if ($search) {
            $sql .= " AND (i.invoice_number LIKE ? OR i.client_name LIKE ? OR l.loan_number LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        if ($status) {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY i.invoice_date DESC, i.id DESC";
        $invoices = DB::all($sql, $params);

        $this->render('fiscal/invoices/index', [
            'title'    => 'Facturas',
            'invoices' => $invoices,
            'search'   => $search,
            'from'     => $from,
            'to'       => $to,
            'status'   => $status,
        ]);
    }

    /**
     * Crear factura desde un pago existente
     */
    public function fromPayment(string $id): void
    {
        $payment = DB::row(
            "SELECT p.*, l.loan_number, l.loan_type, l.balance, l.principal,
                l.interest_rate, l.late_fee_rate,
                CONCAT(c.first_name,' ',c.last_name) as client_name,
                c.identity_number as client_identity,
                c.address as client_address, c.phone as client_phone,
                c.email as client_email, c.id as client_id_val,
                l.id as loan_id_val
         FROM payments p
         JOIN loans l   ON l.id = p.loan_id
         JOIN clients c ON c.id = l.client_id
         WHERE p.id = ?",
            [(int)$id]
        );

        if (!$payment) $this->flashRedirect('/payments', 'error', 'Pago no encontrado.');

        // Verificar si ya tiene factura
        $existing = DB::row(
            "SELECT id FROM invoices WHERE payment_id = ? AND status = 'active'",
            [(int)$id]
        );
        if ($existing) {
            $this->redirect("/invoices/{$existing['id']}");
        }

        // Obtener desglose del pago
        $items = DB::all(
            "SELECT * FROM payment_items WHERE payment_id = ?",
            [(int)$id]
        );

        $capital  = 0;
        $interest = 0;
        $lateFee  = 0;
        foreach ($items as $item) {
            match ($item['item_type']) {
                'capital'  => $capital  += $item['amount'],
                'interest' => $interest += $item['amount'],
                'late_fee' => $lateFee  += $item['amount'],
                default    => null,
            };
        }

        $saldoAnterior = round($payment['balance'] + $capital, 2);

        $cai = FiscalCaiController::getActiveCai();
        if (!$cai) {
            $this->flashRedirect(
                "/payments/$id",
                'error',
                'No hay CAI activo y vigente. Registre uno en Información Fiscal.'
            );
        }

        $this->render('fiscal/invoices/create', [
            'title'         => 'Emitir Factura',
            'payment'       => $payment,
            'items'         => $items,
            'capital'       => $capital,
            'interest'      => $interest,
            'lateFee'       => $lateFee,
            'saldoAnterior' => $saldoAnterior,
            'cai'           => $cai,
        ]);
    }

    public function store(): void
    {
        CSRF::check();
        Auth::requireRole(['superadmin', 'admin', 'asesor']);

        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $caiId     = (int)($_POST['cai_id']     ?? 0);

        $payment = DB::row(
            "SELECT p.*, l.id as loan_id_val, l.balance,
                    c.id as client_id_val,
                    CONCAT(c.first_name,' ',c.last_name) as client_name_full,
                    c.identity_number, c.address, c.phone, c.email
             FROM payments p
             JOIN loans l   ON l.id = p.loan_id
             JOIN clients c ON c.id = l.client_id
             WHERE p.id = ?",
            [$paymentId]
        );

        if (!$payment) $this->flashRedirect('/payments', 'error', 'Pago no encontrado.');

        $cai = DB::row("SELECT * FROM fiscal_cai WHERE id = ? AND is_active = 1", [$caiId]);
        if (!$cai) $this->flashRedirect("/payments/$paymentId/invoice", 'error', 'CAI no válido.');

        DB::beginTransaction();
        try {
            // Generar correlativo
            $invoiceNumber = FiscalCaiController::nextInvoiceNumber($caiId);

            // Montos del formulario
            $saldoAnterior    = round((float)($_POST['saldo_anterior']    ?? 0), 2);
            $interesCorriente = round((float)($_POST['interes_corriente'] ?? 0), 2);
            $interesMoreatorio = round((float)($_POST['interes_moratorio'] ?? 0), 2);
            $otrosCargos      = round((float)($_POST['otros_cargos']      ?? 0), 2);
            $abonoCapital     = round((float)($_POST['abono_capital']     ?? 0), 2);
            $nuevoSaldo       = round((float)($_POST['nuevo_saldo']       ?? 0), 2);

            // En Honduras, intereses de préstamos = EXENTOS de ISV
            // Solo otros_cargos podría ser gravado si aplica
            $total      = round($abonoCapital + $interesCorriente + $interesMoreatorio + $otrosCargos, 2);
            $exempt     = round($interesCorriente + $interesMoreatorio + $abonoCapital, 2);
            $taxable15  = round($otrosCargos, 2); // cargos admin gravados 15% si aplica
            $isv15      = round($taxable15 * 0.15, 2);
            $grandTotal = round($total + $isv15, 2);

            $invoiceData = [
                'cai_id'            => $caiId,
                'payment_id'        => $paymentId,
                'loan_id'           => $payment['loan_id_val'],
                'client_id'         => $payment['client_id_val'],
                'invoice_number'    => $invoiceNumber,
                'invoice_date'      => $_POST['invoice_date'] ?? date('Y-m-d'),
                'due_date_cai'      => $cai['limit_date'],

                'client_name'       => $_POST['client_name']    ?? $payment['client_name_full'],
                'client_rtn'        => $_POST['client_rtn']     ?? null,
                'client_address'    => $_POST['client_address'] ?? $payment['address'],
                'client_phone'      => $_POST['client_phone']   ?? $payment['phone'],
                'client_email'      => $_POST['client_email']   ?? $payment['email'],

                'saldo_anterior'    => $saldoAnterior,
                'interes_corriente' => $interesCorriente,
                'interes_moratorio' => $interesMoreatorio,
                'otros_cargos'      => $otrosCargos,
                'abono_capital'     => $abonoCapital,
                'nuevo_saldo'       => $nuevoSaldo,

                'subtotal'          => $total,
                'exempt_amount'     => $exempt,
                'taxable_15'        => $taxable15,
                'taxable_18'        => 0.00,
                'isv_15'            => $isv15,
                'isv_18'            => 0.00,
                'total'             => $grandTotal,
                'total_letras'      => self::numToWords((int)$grandTotal),

                'notes'             => trim($_POST['notes'] ?? '') ?: null,
                'status'            => 'active',
                'created_by'        => Auth::id(),
            ];

            $invoiceId = DB::insert('invoices', $invoiceData);

            DB::insert('audit_log', [
                'user_id'    => Auth::id(),
                'action'     => 'create',
                'entity'     => 'invoices',
                'entity_id'  => $invoiceId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            DB::commit();
            $this->flashRedirect("/invoices/$invoiceId", 'success', "Factura $invoiceNumber emitida.");
        } catch (\Throwable $e) {
            DB::rollback();
            View::flash('error', 'Error al emitir factura: ' . $e->getMessage());
            $this->redirect("/payments/$paymentId/invoice");
        }
    }

    public function show(string $id): void
    {
        $invoice = $this->findInvoice((int)$id);
        if (!$invoice) $this->flashRedirect('/invoices', 'error', 'Factura no encontrada.');

        $cai     = DB::row("SELECT * FROM fiscal_cai WHERE id = ?", [$invoice['cai_id']]);
        $payment = $invoice['payment_id']
            ? DB::row("SELECT * FROM payments WHERE id = ?", [$invoice['payment_id']])
            : null;

        $this->render('fiscal/invoices/show', [
            'title'   => 'Factura ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'cai'     => $cai,
            'payment' => $payment,
        ], null); // sin layout — página imprimible
    }

    /**
     * ANULAR FACTURA — Solo admin/superadmin con verificación de contraseña
     */
    public function void(string $id): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        CSRF::check();

        $invoice = $this->findInvoice((int)$id);
        if (!$invoice || $invoice['status'] === 'voided') {
            $this->flashRedirect('/invoices', 'error', 'Factura no encontrada o ya anulada.');
        }

        // Verificar contraseña
        $password = $_POST['confirm_password'] ?? '';
        $reason   = trim($_POST['void_reason'] ?? '');
        $comment  = trim($_POST['void_comment'] ?? '');

        if (!$this->verifyPassword($password)) {
            View::flash('error', 'Contraseña incorrecta. No se anuló la factura.');
            $this->redirect("/invoices/$id");
        }

        if (strlen($reason) < 5) {
            View::flash('error', 'El motivo de anulación es obligatorio (mínimo 5 caracteres).');
            $this->redirect("/invoices/$id");
        }

        DB::beginTransaction();
        try {
            DB::update('invoices', [
                'status'       => 'voided',
                'voided_by'    => Auth::id(),
                'voided_at'    => date('Y-m-d H:i:s'),
                'void_reason'  => $reason,
                'void_comment' => $comment ?: null,
            ], 'id = ?', [(int)$id]);

            DB::insert('audit_log', [
                'user_id'    => Auth::id(),
                'action'     => 'void',
                'entity'     => 'invoices',
                'entity_id'  => (int)$id,
                'new_data'   => json_encode(['reason' => $reason, 'comment' => $comment]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            DB::commit();
            $this->flashRedirect(
                "/invoices/$id",
                'success',
                'Factura ' . $invoice['invoice_number'] . ' anulada correctamente.'
            );
        } catch (\Throwable $e) {
            DB::rollback();
            View::flash('error', 'Error al anular: ' . $e->getMessage());
            $this->redirect("/invoices/$id");
        }
    }

    // ─── HELPERS ────────────────────────────────────────────────
    private function findInvoice(int $id): ?array
    {
        return DB::row(
            "SELECT i.*,
                    CONCAT(c.first_name,' ',c.last_name) as client_full_name,
                    c.identity_number as client_identity,
                    l.loan_number, l.loan_type,
                    u.name as created_by_name,
                    vb.name as voided_by_name
             FROM invoices i
             JOIN clients c  ON c.id = i.client_id
             JOIN loans l    ON l.id = i.loan_id
             JOIN users u    ON u.id = i.created_by
             LEFT JOIN users vb ON vb.id = i.voided_by
             WHERE i.id = ?",
            [$id]
        );
    }

    private function verifyPassword(string $password): bool
    {
        if (empty($password)) return false;
        $user = DB::row("SELECT password FROM users WHERE id = ?", [Auth::id()]);
        return $user && password_verify($password, $user['password']);
    }

    private static function numToWords(int $n): string
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
        $hundreds = [
            '',
            'CIENTO',
            'DOSCIENTOS',
            'TRESCIENTOS',
            'CUATROCIENTOS',
            'QUINIENTOS',
            'SEISCIENTOS',
            'SETECIENTOS',
            'OCHOCIENTOS',
            'NOVECIENTOS'
        ];
        if ($n < 20) return $ones[$n];
        if ($n < 100) return $tens[intdiv($n, 10)] . ($n % 10 ? ' Y ' . $ones[$n % 10] : '');
        if ($n === 100) return 'CIEN';
        if ($n < 1000) return $hundreds[intdiv($n, 100)] . ($n % 100 ? ' ' . self::numToWords($n % 100) : '');
        if ($n < 2000) return 'MIL' . ($n % 1000 ? ' ' . self::numToWords($n % 1000) : '');
        if ($n < 1000000) return self::numToWords(intdiv($n, 1000)) . ' MIL' . ($n % 1000 ? ' ' . self::numToWords($n % 1000) : '');
        return self::numToWords(intdiv($n, 1000000)) . ' MILLONES' . ($n % 1000000 ? ' ' . self::numToWords($n % 1000000) : '');
    }
}
