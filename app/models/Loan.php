<?php
/**
 * APP: app/models/Loan.php
 * MODELO COMPLETO DE PRÉSTAMOS (SIN deleted_at)
 */

namespace App\Models;

use App\Core\DB;

class Loan
{
    /**
     * ENCONTRAR UN PRÉSTAMO POR ID
     */
    public static function find(int $id): ?array
    {
        return DB::row(
            "SELECT l.*, 
                    c.first_name, c.last_name, c.phone, c.phone2, c.email,
                    c.address, c.city, c.identity_number, c.nationality,
                    c.profession, c.occupation, c.marital_status, c.spouse_name,
                    CONCAT(c.first_name,' ',c.last_name) as client_name,
                    u.name as assigned_name, 
                    cb.name as created_by_name
             FROM loans l
             JOIN clients c ON c.id = l.client_id
             LEFT JOIN users u  ON u.id = l.assigned_to
             LEFT JOIN users cb ON cb.id = l.created_by
             WHERE l.id = ?
             LIMIT 1",
            [$id]
        );
    }

    /**
     * LISTAR PRÉSTAMOS CON PAGINACIÓN Y FILTROS
     */
    public static function all(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        // ─── FILTRO: STATUS ────────────────────────────────────
        if (!empty($filters['status'])) {
            $where[] = 'l.status = ?';
            $params[] = $filters['status'];
        }

        // ─── FILTRO: TIPO DE PRÉSTAMO ──────────────────────────
        if (!empty($filters['loan_type'])) {
            $where[] = 'l.loan_type = ?';
            $params[] = $filters['loan_type'];
        }

        // ─── FILTRO: BÚSQUEDA (número o cliente) ────────────────
        if (!empty($filters['search'])) {
            $s = "%{$filters['search']}%";
            $where[]  = "(l.loan_number LIKE ? OR CONCAT(c.first_name,' ',c.last_name) LIKE ?)";
            $params[] = $s;
            $params[] = $s;
        }

        // ─── FILTRO: ASESOR ASIGNADO ───────────────────────────
        if (!empty($filters['assigned_to'])) {
            $where[] = 'l.assigned_to = ?';
            $params[] = $filters['assigned_to'];
        }

        // ─── FILTRO: CLIENTE ───────────────────────────────────
        if (!empty($filters['client_id'])) {
            $where[] = 'l.client_id = ?';
            $params[] = $filters['client_id'];
        }

        // ─── FILTRO: VENCIDOS ──────────────────────────────────
        if (!empty($filters['filter']) && $filters['filter'] === 'overdue') {
            $where[] = "EXISTS (
                SELECT 1 FROM loan_installments li 
                WHERE li.loan_id = l.id 
                AND li.due_date < CURDATE() 
                AND li.status IN ('pending','partial')
            )";
        }

        // ─── FILTRO: PRÓXIMOS A VENCER ─────────────────────────
        if (!empty($filters['filter']) && $filters['filter'] === 'upcoming') {
            $alertDays = (int)setting('alert_days_upcoming', 7);
            $where[] = "EXISTS (
                SELECT 1 FROM loan_installments li 
                WHERE li.loan_id = l.id 
                AND li.due_date BETWEEN CURDATE() 
                AND DATE_ADD(CURDATE(), INTERVAL $alertDays DAY) 
                AND li.status IN ('pending','partial')
            )";
        }

        // ─── CONSTRUIR QUERY ────────────────────────────────────
        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $sql = "SELECT 
                    l.id, l.loan_number, l.loan_type, l.principal, l.balance, 
                    l.interest_rate, l.term_months, l.status, l.disbursement_date, 
                    l.created_at, l.payment_method_cash, l.payment_method_transfer,
                    l.payment_method_check, l.payment_method_atm,
                    CONCAT(c.first_name,' ',c.last_name) as client_name, 
                    c.id as client_id,
                    u.name as assigned_name
                FROM loans l
                JOIN clients c ON c.id = l.client_id
                LEFT JOIN users u ON u.id = l.assigned_to
                WHERE $whereStr
                ORDER BY l.created_at DESC
                LIMIT $perPage OFFSET $offset";

        $countSql = "SELECT COUNT(*) as total FROM loans l 
                    JOIN clients c ON c.id = l.client_id 
                    WHERE $whereStr";
        $countResult = DB::row($countSql, $params);
        $count = (int)($countResult['total'] ?? 0);

        $data = DB::all($sql, $params);

        return [
            'data'         => $data,
            'total'        => $count,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($count / $perPage))
        ];
    }

    /**
     * CREAR NUEVO PRÉSTAMO
     */
    public static function create(array $data, int $createdBy): int
    {
        // ─── GENERAR NÚMERO DE PRÉSTAMO ────────────────────────
        $prefix = setting('loan_number_prefix', 'PRES-');
        $last   = DB::row(
            "SELECT MAX(CAST(SUBSTRING(loan_number, ?) AS UNSIGNED)) as n 
             FROM loans 
             WHERE loan_number LIKE ?",
            [strlen($prefix) + 1, $prefix . '%']
        );
        $number = $prefix . str_pad((string)(($last['n'] ?? 0) + 1), 6, '0', STR_PAD_LEFT);

        // ─── HELPERS PARA NORMALIZAR DATOS ─────────────────────
        $nullableInt = fn($v) => (isset($v) && $v !== '' && (int)$v > 0) ? (int)$v : null;
        $nullableStr = fn($v) => (isset($v) && trim((string)$v) !== '') ? trim($v) : null;

        // ─── INSERTAR ───────────────────────────────────────────
        return (int)DB::insert('loans', [
            'client_id'              => (int)$data['client_id'],
            'assigned_to'            => $nullableInt($data['assigned_to'] ?? null),
            'created_by'             => $createdBy,
            'loan_number'            => $number,
            'loan_type'              => $data['loan_type'],
            'principal'              => (float)$data['principal'],
            'interest_rate'          => (float)$data['interest_rate'],
            'rate_type'              => $data['rate_type'] ?? 'monthly',
            'term_months'            => $nullableInt($data['term_months'] ?? null),
            'payment_frequency'      => $data['payment_frequency'] ?? 'monthly',
            'apply_payment_to'       => $data['apply_payment_to'] ?? 'interest_first',
            'late_fee_rate'          => (float)($data['late_fee_rate'] ?? setting('default_late_fee_rate', 0.05)),
            'grace_days'             => (int)($data['grace_days'] ?? setting('grace_days', 3)),
            'disbursement_date'      => $data['disbursement_date'],
            'first_payment_date'     => $data['first_payment_date'],
            'maturity_date'          => $nullableStr($data['maturity_date'] ?? null),
            'status'                 => 'active',
            'balance'                => (float)$data['principal'],
            'notes'                  => $nullableStr($data['notes'] ?? null),
            // ─── MÉTODOS DE PAGO ───────────────────────────────
            'payment_method_cash'    => (int)($data['payment_method_cash'] ?? 0),
            'payment_method_transfer' => (int)($data['payment_method_transfer'] ?? 0),
            'payment_method_check'   => (int)($data['payment_method_check'] ?? 0),
            'payment_method_atm'     => (int)($data['payment_method_atm'] ?? 0),
        ]);
    }

    /**
     * OBTENER TABLA DE AMORTIZACIÓN
     */
    public static function getInstallments(int $loanId): array
    {
        return DB::all(
            "SELECT * FROM loan_installments 
             WHERE loan_id = ? 
             ORDER BY installment_number ASC",
            [$loanId]
        );
    }

    /**
     * OBTENER PAGOS REGISTRADOS
     */
    public static function getPayments(int $loanId): array
    {
        return DB::all(
            "SELECT p.*, u.name as registered_by_name
             FROM payments p 
             JOIN users u ON u.id = p.registered_by
             WHERE p.loan_id = ? AND p.voided = 0
             ORDER BY p.payment_date DESC",
            [$loanId]
        );
    }

    /**
     * OBTENER MÉTODOS DE PAGO DE UN PRÉSTAMO
     */
    public static function getPaymentMethods(int $id): array
    {
        $loan = self::find($id);
        if (!$loan) return [];
        
        return [
            'cash'     => (bool)$loan['payment_method_cash'],
            'transfer' => (bool)$loan['payment_method_transfer'],
            'check'    => (bool)$loan['payment_method_check'],
            'atm'      => (bool)$loan['payment_method_atm'],
        ];
    }

    /**
     * OBTENER TODAS LAS CUENTAS BANCARIAS CONFIGURADAS
     */
    public static function getBankAccounts(): array
    {
        $accounts = [];
        
        for ($i = 1; $i <= 3; $i++) {
            $bankName = setting("bank_name_$i");
            if (!empty($bankName)) {
                $accounts[] = [
                    'id'       => $i,
                    'bank'     => $bankName,
                    'account'  => setting("bank_account_$i"),
                    'holder'   => setting("bank_account_holder_$i"),
                    'type'     => setting("bank_account_type_$i"),
                    'iban'     => setting("bank_account_iban_$i"),
                ];
            }
        }
        
        return $accounts;
    }

    /**
     * OBTENER CUENTAS BANCARIAS HABILITADAS PARA UN PRÉSTAMO
     */
    public static function getEnabledBankAccounts(int $loanId): array
    {
        $loan = self::find($loanId);
        if (!$loan || !$loan['payment_method_transfer']) {
            return [];
        }
        
        return self::getBankAccounts();
    }

    /**
     * OBTENER RESUMEN COMPLETO PARA DOCUMENTOS
     */
    public static function getSummaryForDocument(int $id): array
    {
        $loan = self::find($id);
        if (!$loan) return [];
        
        $installments = self::getInstallments($id);
        $totalPrincipal = (float)$loan['principal'];
        $totalInterest = (float)array_sum(array_column($installments, 'interest_amount'));
        
        return [
            'loan'           => $loan,
            'installments'   => $installments,
            'totalPrincipal' => $totalPrincipal,
            'totalInterest'  => $totalInterest,
            'totalToPay'     => $totalPrincipal + $totalInterest,
            'paymentMethods' => self::getPaymentMethods($id),
            'bankAccounts'   => self::getEnabledBankAccounts($id),
            'currency'       => setting('app_currency', 'L'),
        ];
    }

    /**
     * ACTUALIZAR SALDO DEL PRÉSTAMO
     */
    public static function updateBalance(int $id, float $newBalance, array $extras = []): void
    {
        $data = ['balance' => max(0, $newBalance), ...$extras];
        DB::update('loans', $data, 'id = ?', [$id]);
    }

    /**
     * MARCAR COMO PAGADO SI BALANCE ES CERO
     */
    public static function markPaidIfComplete(int $id): void
    {
        $loan = self::find($id);
        if (!$loan) return;
        
        if ($loan['balance'] <= 0.01) {
            DB::update('loans', [
                'status' => 'paid',
                'last_payment_date' => date('Y-m-d')
            ], 'id = ?', [$id]);
        }
    }

    /**
     * ACTUALIZAR ESTADO DEL PRÉSTAMO
     */
    public static function updateStatus(int $id, string $status): void
    {
        $allowed = ['active', 'paid', 'defaulted', 'cancelled', 'restructured'];
        if (!in_array($status, $allowed, true)) return;
        
        DB::update('loans', ['status' => $status], 'id = ?', [$id]);
    }

    /**
     * OBTENER PRÉSTAMOS VENCIDOS (CUOTAS ATRASADAS)
     */
    public static function getOverdue(int $days = 0): array
    {
        $dateStr = $days > 0 ? "DATE_SUB(CURDATE(), INTERVAL $days DAY)" : 'CURDATE()';
        
        return DB::all(
            "SELECT DISTINCT l.* 
             FROM loans l
             JOIN loan_installments li ON li.loan_id = l.id
             WHERE l.status = 'active'
             AND li.due_date < $dateStr
             AND li.status IN ('pending', 'partial')
             ORDER BY li.due_date ASC"
        );
    }

    /**
     * OBTENER PRÉSTAMOS PRÓXIMOS A VENCER
     */
    public static function getUpcoming(int $days = 7): array
    {
        return DB::all(
            "SELECT DISTINCT l.* 
             FROM loans l
             JOIN loan_installments li ON li.loan_id = l.id
             WHERE l.status = 'active'
             AND li.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND li.status IN ('pending', 'partial')
             ORDER BY li.due_date ASC",
            [$days]
        );
    }

    /**
     * OBTENER TOTAL DE INTERÉS PAGADO
     */
    public static function getTotalInterestPaid(int $id): float
    {
        $result = DB::row(
            "SELECT SUM(paid_interest) as total FROM loan_installments WHERE loan_id = ?",
            [$id]
        );
        return (float)($result['total'] ?? 0);
    }

    /**
     * OBTENER TOTAL DE MORA PAGADA
     */
    public static function getTotalLateFeesPaid(int $id): float
    {
        $result = DB::row(
            "SELECT SUM(paid_late_fee) as total FROM loan_installments WHERE loan_id = ?",
            [$id]
        );
        return (float)($result['total'] ?? 0);
    }

    /**
     * OBTENER PRÓXIMA CUOTA VENCIDA
     */
    public static function getNextDueInstallment(int $id): ?array
    {
        return DB::row(
            "SELECT * FROM loan_installments 
             WHERE loan_id = ? 
             AND status IN ('pending', 'partial')
             ORDER BY due_date ASC
             LIMIT 1",
            [$id]
        );
    }

    /**
     * OBTENER CUOTA POR NÚMERO
     */
    public static function getInstallment(int $loanId, int $installmentNumber): ?array
    {
        return DB::row(
            "SELECT * FROM loan_installments 
             WHERE loan_id = ? AND installment_number = ?
             LIMIT 1",
            [$loanId, $installmentNumber]
        );
    }

    /**
     * OBTENER ESTADO ACTUAL DEL PRÉSTAMO
     */
    public static function getCurrentStatus(int $id): array
    {
        $loan = self::find($id);
        if (!$loan) return [];

        $installments = self::getInstallments($id);
        $totalInstallments = count($installments);
        $paidInstallments = count(array_filter($installments, fn($i) => $i['status'] === 'paid'));
        $pendingInstallments = count(array_filter($installments, fn($i) => in_array($i['status'], ['pending', 'partial'])));
        
        $totalDue = array_sum(array_column($installments, 'total_amount'));
        $totalPaid = array_sum(array_column($installments, 'paid_amount'));
        $totalPending = $totalDue - $totalPaid;

        return [
            'loan'                  => $loan,
            'total_installments'    => $totalInstallments,
            'paid_installments'     => $paidInstallments,
            'pending_installments'  => $pendingInstallments,
            'total_due'             => $totalDue,
            'total_paid'            => $totalPaid,
            'total_pending'         => $totalPending,
            'payment_percentage'    => $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 2) : 0,
        ];
    }

    /**
     * REGISTRAR EVENTO DE PRÉSTAMO
     */
    public static function logEvent(int $loanId, string $eventType, string $description, ?array $meta = null, int $userId = 0): void
    {
        if (!$userId) {
            $user = auth();
            $userId = $user ? $user['id'] : 0;
        }

        DB::insert('loan_events', [
            'loan_id'     => $loanId,
            'user_id'     => $userId,
            'event_type'  => $eventType,
            'description' => $description,
            'meta'        => $meta ? json_encode($meta) : null,
        ]);
    }

    /**
     * OBTENER EVENTOS DE UN PRÉSTAMO
     */
    public static function getEvents(int $id): array
    {
        return DB::all(
            "SELECT le.*, u.name as user_name
             FROM loan_events le
             LEFT JOIN users u ON u.id = le.user_id
             WHERE le.loan_id = ?
             ORDER BY le.created_at DESC",
            [$id]
        );
    }

    /**
     * VERIFICAR SI UN PRÉSTAMO EXISTE Y ESTÁ ACTIVO
     */
    public static function isActive(int $id): bool
    {
        $loan = self::find($id);
        return $loan && $loan['status'] === 'active';
    }

    /**
     * OBTENER RESUMEN ESTADÍSTICO DE PRÉSTAMOS
     */
    public static function getStats(): array
    {
        $stats = DB::row(
            "SELECT 
                COUNT(*) as total_loans,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_loans,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_loans,
                SUM(CASE WHEN status = 'defaulted' THEN 1 ELSE 0 END) as defaulted_loans,
                SUM(principal) as total_principal,
                SUM(balance) as total_outstanding,
                AVG(interest_rate * 100) as average_interest_rate
             FROM loans"
        );

        return $stats ?? [];
    }
}