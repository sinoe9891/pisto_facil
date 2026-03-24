<?php
// app/controllers/FiscalCaiController.php
// Gestión de CAI (Código de Autorización de Impresión) del SAR Honduras

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, View, DB};

class FiscalCaiController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['superadmin', 'admin']);

        $cais  = DB::all(
            "SELECT fc.*, u.name as created_by_name
         FROM fiscal_cai fc
         LEFT JOIN users u ON u.id = fc.created_by
         ORDER BY fc.is_active DESC, fc.created_at DESC"
        );

        $today = date('Y-m-d');
        foreach ($cais as &$cai) {
            // Contar facturas por separado
            $r = DB::row(
                "SELECT COUNT(*) as n FROM invoices
             WHERE cai_id = ? AND status = 'active'",
                [$cai['id']]
            );
            $cai['invoices_count'] = (int)($r['n'] ?? 0);

            // Calcular rango total desde los últimos 8 dígitos
            $fromN = (int)substr(str_replace('-', '', $cai['range_from']), -8);
            $toN   = (int)substr(str_replace('-', '', $cai['range_to']),   -8);
            $cai['total_range'] = max(0, $toN - $fromN + 1);
            $cai['used_count']  = (int)$cai['current_counter'];

            // Días restantes
            if ($cai['limit_date'] < $today) {
                $cai['days_left'] = -1;
            } else {
                $cai['days_left'] = (int)(new \DateTime($today))
                    ->diff(new \DateTime($cai['limit_date']))->days;
            }

            // % usado
            $cai['pct_used'] = $cai['total_range'] > 0
                ? round($cai['used_count'] / $cai['total_range'] * 100, 1)
                : 0;
        }
        unset($cai);

        $this->render('fiscal/index', [
            'title' => 'Información Fiscal — CAI',
            'cais'  => $cais,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        $this->render('fiscal/form', [
            'title' => 'Registrar CAI',
            'cai'   => null,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        CSRF::check();

        $data = [
            'cai_code'        => strtoupper(trim($_POST['cai_code'] ?? '')),
            'emission_type'   => trim($_POST['emission_type'] ?? 'Factura'),
            'range_from'      => trim($_POST['range_from'] ?? ''),
            'range_to'        => trim($_POST['range_to'] ?? ''),
            'limit_date'      => $_POST['limit_date'] ?? '',
            'bmt_rtn'         => trim($_POST['bmt_rtn'] ?? '') ?: null,
            'bmt_name'        => trim($_POST['bmt_name'] ?? '') ?: null,
            'cert_number'     => trim($_POST['cert_number'] ?? '') ?: null,
            'establishment'   => trim($_POST['establishment'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'current_counter' => 0,
            'is_active'       => 1,
            'created_by'      => Auth::id(),
        ];

        if (!$data['cai_code'] || !$data['range_from'] || !$data['range_to'] || !$data['limit_date']) {
            View::flash('error', 'CAI, rango y fecha límite son obligatorios.');
            $this->redirect('/fiscal/create');
        }

        // Validar que rango_from y rango_to tengan formato correcto
        if (!$this->validRange($data['range_from']) || !$this->validRange($data['range_to'])) {
            View::flash('error', 'El rango debe tener formato: 000-001-01-00001351');
            $this->redirect('/fiscal/create');
        }

        try {
            DB::insert('fiscal_cai', $data);
            $this->flashRedirect('/fiscal', 'success', 'CAI registrado correctamente.');
        } catch (\Throwable $e) {
            View::flash('error', 'Error al guardar: ' . $e->getMessage());
            $this->redirect('/fiscal/create');
        }
    }

    public function edit(string $id): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        $cai = DB::row("SELECT * FROM fiscal_cai WHERE id = ?", [(int)$id]);
        if (!$cai) $this->flashRedirect('/fiscal', 'error', 'CAI no encontrado.');

        $this->render('fiscal/form', [
            'title' => 'Editar CAI',
            'cai'   => $cai,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        CSRF::check();

        $cai = DB::row("SELECT * FROM fiscal_cai WHERE id = ?", [(int)$id]);
        if (!$cai) $this->flashRedirect('/fiscal', 'error', 'CAI no encontrado.');

        // Verificar contraseña admin para editar
        $password = $_POST['confirm_password'] ?? '';
        if (!$this->verifyAdminPassword($password)) {
            View::flash('error', 'Contraseña incorrecta. No se guardaron los cambios.');
            $this->redirect("/fiscal/$id/edit");
        }

        $data = [
            'cai_code'      => strtoupper(trim($_POST['cai_code'] ?? $cai['cai_code'])),
            'emission_type' => trim($_POST['emission_type'] ?? $cai['emission_type']),
            'range_from'    => trim($_POST['range_from'] ?? $cai['range_from']),
            'range_to'      => trim($_POST['range_to']   ?? $cai['range_to']),
            'limit_date'    => $_POST['limit_date']      ?? $cai['limit_date'],
            'bmt_rtn'       => trim($_POST['bmt_rtn']    ?? '') ?: null,
            'bmt_name'      => trim($_POST['bmt_name']   ?? '') ?: null,
            'cert_number'   => trim($_POST['cert_number'] ?? '') ?: null,
            'establishment' => trim($_POST['establishment'] ?? '') ?: null,
            'notes'         => trim($_POST['notes']       ?? '') ?: null,
        ];

        DB::update('fiscal_cai', $data, 'id = ?', [(int)$id]);
        $this->flashRedirect('/fiscal', 'success', 'CAI actualizado.');
    }

    public function toggle(string $id): void
    {
        Auth::requireRole(['superadmin', 'admin']);
        $cai = DB::row("SELECT * FROM fiscal_cai WHERE id = ?", [(int)$id]);
        if (!$cai) $this->flashRedirect('/fiscal', 'error', 'CAI no encontrado.');

        DB::update('fiscal_cai', ['is_active' => $cai['is_active'] ? 0 : 1], 'id = ?', [(int)$id]);
        $msg = $cai['is_active'] ? 'CAI desactivado.' : 'CAI activado.';
        $this->flashRedirect('/fiscal', 'success', $msg);
    }

    // ─── HELPERS ────────────────────────────────────────────────
    private function validRange(string $range): bool
    {
        // Formato: 000-001-01-00001351
        return (bool)preg_match('/^\d{3}-\d{3}-\d{2}-\d{8}$/', $range);
    }

    private function verifyAdminPassword(string $password): bool
    {
        if (empty($password)) return false;
        $user = DB::row("SELECT password FROM users WHERE id = ?", [Auth::id()]);
        return $user && password_verify($password, $user['password']);
    }

    /**
     * Obtener el CAI activo y vigente para emitir facturas
     */
    public static function getActiveCai(): ?array
    {
        return DB::row(
            "SELECT * FROM fiscal_cai
             WHERE is_active = 1
               AND limit_date >= CURDATE()
               AND CAST(SUBSTRING_INDEX(range_to, '-', -1) AS UNSIGNED) > current_counter
             ORDER BY limit_date ASC
             LIMIT 1"
        );
    }

    /**
     * Generar el siguiente correlativo de factura
     */
    public static function nextInvoiceNumber(int $caiId): string
    {
        $cai = DB::row("SELECT * FROM fiscal_cai WHERE id = ? FOR UPDATE", [$caiId]);
        if (!$cai) throw new \RuntimeException('CAI no encontrado.');

        $newCounter = $cai['current_counter'] + 1;

        // Extraer el prefijo del range_from (todo menos los últimos 8 dígitos)
        $prefix = substr($cai['range_from'], 0, -8); // "000-001-01-"
        $number = str_pad((string)$newCounter, 8, '0', STR_PAD_LEFT);
        $invoiceNumber = $prefix . $number;

        // Verificar que está dentro del rango
        $maxNum = (int)substr($cai['range_to'], -8);
        if ($newCounter > $maxNum) {
            throw new \RuntimeException('El CAI ha alcanzado el límite de su rango autorizado.');
        }

        // Verificar fecha límite
        if ($cai['limit_date'] < date('Y-m-d')) {
            throw new \RuntimeException('El CAI ha vencido. Fecha límite: ' . $cai['limit_date']);
        }

        // Actualizar contador
        DB::update('fiscal_cai', ['current_counter' => $newCounter], 'id = ?', [$caiId]);

        return $invoiceNumber;
    }
}
