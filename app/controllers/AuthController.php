<?php
// app/controllers/AuthController.php

namespace App\Controllers;

use App\Core\{Controller, Auth, CSRF, Validator, View};
use App\Models\User;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->render('auth/login', ['title' => 'Iniciar Sesión'], 'auth');
    }

    public function loginPost(): void
    {
        CSRF::check();

        $email    = trim($this->post('email', ''));
        $password = $this->post('password', '');
        $remember = $this->post('remember') === '1';

        $v = Validator::make(['email' => $email, 'password' => $password], [
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($v->fails()) {
            View::flash('error', $v->firstError());
            $this->redirect('/login');
        }

        $user = User::verify($email, $password);
        if (!$user) {
            // Slight delay to deter brute-force
            usleep(random_int(300_000, 600_000));
            View::flash('error', 'Email o contraseña incorrectos.');
            $this->redirect('/login');
        }

        User::updateLastLogin($user['id']);
        Auth::login($user);

        // Redirect to intended URL or dashboard
        $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
        unset($_SESSION['redirect_after_login']);
        $this->redirect($redirect);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}