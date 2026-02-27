<?php
// app/controllers/SettingController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, View};
use App\Models\Setting;

class SettingController extends Controller
{
    public function index(): void
    {
        $groups   = ['general','loans','dashboard','documents','reports'];
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

        foreach ($data as $key => $value) {
            if (!empty($key)) {
                Setting::set(trim($key), trim($value), Auth::id());
            }
        }

        Setting::clearCache();
        View::flash('success', 'Configuración guardada exitosamente.');
        $this->redirect('/settings');
    }
}
