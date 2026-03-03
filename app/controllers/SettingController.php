<?php
/**
 * app/controllers/SettingController.php
 * VERSIÓN CORREGIDA - Guarda correctamente las cuentas bancarias
 */

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, View};
use App\Models\Setting;

class SettingController extends Controller
{
    public function index(): void
    {
        $groups   = ['general', 'loans', 'dashboard', 'documents', 'reports'];
        $settings = [];
        foreach ($groups as $g) {
            $settings[$g] = Setting::allByGroup($g);
        }
        $this->render('settings/index', [
            'title'    => 'Configuración del Sistema',
            'settings' => $settings,
            'groups'   => $groups,
        ]);
    }

    public function update(): void
    {
        CSRF::check();
        $data = $_POST;
        unset($data['_csrf']);

        // ─────────────────────────────────────────────────────
        // GUARDAR TODOS LOS SETTINGS (incluyendo vacíos)
        // ─────────────────────────────────────────────────────
        foreach ($data as $key => $value) {
            $key = trim($key);
            if (!empty($key)) {
                // Guarda TODOS los valores (vacíos o no)
                $value = is_array($value) ? json_encode($value) : trim($value);
                Setting::set($key, $value, Auth::id());
            }
        }

        // ─────────────────────────────────────────────────────
        // VALIDAR QUE MÍNIMO UNA CUENTA BANCARIA ESTÉ COMPLETA
        // ─────────────────────────────────────────────────────
        $hasValidAccount = false;
        for ($i = 1; $i <= 3; $i++) {
            $bankName = Setting::get("bank_name_$i", '');
            $bankAccount = Setting::get("bank_account_$i", '');
            
            if (!empty($bankName) && !empty($bankAccount)) {
                $hasValidAccount = true;
                break;
            }
        }

        Setting::clearCache();

        // Mensaje de éxito
        if ($hasValidAccount) {
            View::flash('success', 'Configuración guardada exitosamente. ✓');
        } else {
            View::flash('warning', 'Configuración guardada. Recuerda configurar al menos una cuenta bancaria con Banco y Número de Cuenta.');
        }

        $this->redirect('/settings');
    }
}