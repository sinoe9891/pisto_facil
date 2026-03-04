<?php
namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, View};
use App\Models\Setting;

class SettingController extends Controller
{
    private static array $excludeFromGeneral = [
        'bank_name_1', 'bank_name_2', 'bank_name_3',
        'bank_account_1', 'bank_account_2', 'bank_account_3',
        'bank_account_type_1', 'bank_account_type_2', 'bank_account_type_3',
        'bank_account_holder_1', 'bank_account_holder_2', 'bank_account_holder_3',
        'bank_account_iban_1', 'bank_account_iban_2', 'bank_account_iban_3',
    ];

    private static array $excludeFromDocuments = [
        'contract_page_size', 'contract_margin_top', 'contract_margin_right', 'contract_jurisdiction',
        'pagare_page_size', 'pagare_margin_top', 'pagare_margin_right', 'pagare_jurisdiction', 'pagare_city',
    ];

    public function index(): void
    {
        $groups   = ['general', 'loans', 'dashboard', 'documents', 'reports'];
        $settings = [];
        
        foreach ($groups as $g) {
            $allSettings = Setting::allByGroup($g);
            
            // Excluir campos bancarios de "general"
            if ($g === 'general') {
                $allSettings = array_filter($allSettings, function($item) {
                    return !in_array($item['setting_key'], self::$excludeFromGeneral);
                });
            }
            
            // Excluir campos de documentos legales que tienen sección dedicada
            if ($g === 'documents') {
                $allSettings = array_filter($allSettings, function($item) {
                    return !in_array($item['setting_key'], self::$excludeFromDocuments);
                });
            }
            
            $settings[$g] = array_values($allSettings);
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

        foreach ($data as $key => $value) {
            $key = trim($key);
            if (!empty($key)) {
                $value = is_array($value) ? json_encode($value) : trim($value);
                Setting::set($key, $value, Auth::id());
            }
        }

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

        if ($hasValidAccount) {
            View::flash('success', 'Configuración guardada exitosamente. ✓');
        } else {
            View::flash('warning', 'Configuración guardada. Recuerda configurar al menos una cuenta bancaria.');
        }

        $this->redirect('/settings');
    }
}