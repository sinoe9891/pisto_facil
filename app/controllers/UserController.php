<?php
// app/controllers/UserController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, Validator, View, DB};
use App\Models\User;

class UserController extends Controller
{
    public function index(): void
    {
        $filters = [
            'search' => $this->get('search', ''),
            'role'   => $this->get('role', ''),
        ];
        $users = User::all($filters);
        $roles = DB::all("SELECT * FROM roles ORDER BY id");

        $this->render('users/index', [
            'title'   => 'Usuarios',
            'users'   => $users,
            'filters' => $filters,
            'roles'   => $roles,
        ]);
    }

    public function create(): void
    {
        $roles = DB::all("SELECT * FROM roles WHERE id <= ?", [Auth::isSuperAdmin() ? 4 : 3]);
        $this->render('users/form', [
            'title'    => 'Nuevo Usuario',
            'user'     => [],
            'roles'    => $roles,
            'editMode' => false,
        ]);
    }

    public function store(): void
    {
        CSRF::check();
        $data = $_POST;

        $v = Validator::make($data, [
            'name'                  => 'required|max:150',
            'email'                 => 'required|email|max:180|unique:users,email',
            'role_id'               => 'required|numeric|min_val:1',
            'password'              => 'required|min:8|password_strength|confirmed',
        ]);
        if ($v->fails()) {
            View::flash('error', $v->firstError());
            $this->redirect('/users/create');
        }

        // Prevent creating superadmin unless you are superadmin
        if ($data['role_id'] == 1 && !Auth::isSuperAdmin()) {
            $this->flashRedirect('/users/create', 'error', 'No autorizado para crear SuperAdmin.');
        }

        $id = User::create($data);

        DB::insert('audit_log', [
            'user_id'   => Auth::id(), 'action' => 'create',
            'entity' => 'users', 'entity_id' => $id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $this->flashRedirect('/users', 'success', 'Usuario creado exitosamente.');
    }

    public function edit(string $id): void
    {
        $user  = User::findById((int)$id);
        if (!$user) $this->redirect('/users');

        // Can't edit superadmin unless you're superadmin
        if ($user['role_slug'] === 'superadmin' && !Auth::isSuperAdmin()) {
            $this->flashRedirect('/users', 'error', 'No autorizado.');
        }

        $roles = DB::all("SELECT * FROM roles WHERE id <= ?", [Auth::isSuperAdmin() ? 4 : 3]);
        $this->render('users/form', [
            'title'    => 'Editar: ' . $user['name'],
            'user'     => $user,
            'roles'    => $roles,
            'editMode' => true,
        ]);
    }

    public function update(string $id): void
    {
        CSRF::check();
        $data = $_POST;

        $rules = [
            'name'    => 'required|max:150',
            'email'   => 'required|email|max:180|unique:users,email,' . $id,
            'role_id' => 'required|numeric|min_val:1',
        ];
        if (!empty($data['password'])) {
            $rules['password'] = 'min:8|password_strength|confirmed';
        }

        $v = Validator::make($data, $rules);
        if ($v->fails()) {
            View::flash('error', $v->firstError());
            $this->redirect("/users/$id/edit");
        }

        $update = ['name' => $data['name'], 'email' => $data['email'],
                   'role_id' => $data['role_id'], 'phone' => $data['phone'] ?? null];
        if (!empty($data['password'])) $update['password'] = $data['password'];

        // Don't allow downgrading own role
        if ($id == Auth::id() && $data['role_id'] != Auth::user()['role_id']) {
            $this->flashRedirect("/users/$id/edit", 'error', 'No puede cambiar su propio rol.');
        }

        User::update((int)$id, $update);
        $this->flashRedirect('/users', 'success', 'Usuario actualizado.');
    }

    public function toggle(string $id): void
    {
        if ($id == Auth::id()) {
            $this->flashRedirect('/users', 'error', 'No puede desactivarse a sí mismo.');
        }
        $user = User::findById((int)$id);
        if (!$user) $this->redirect('/users');

        User::update((int)$id, ['is_active' => $user['is_active'] ? 0 : 1]);
        $msg = $user['is_active'] ? 'Usuario desactivado.' : 'Usuario activado.';
        $this->flashRedirect('/users', 'success', $msg);
    }
}
