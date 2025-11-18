<?php

namespace App\Modules\Auth\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Support\Auth;

class LoginController extends Controller
{
    public function show(Request $request): void
    {
        if (Auth::check()) {
            header('Location: ' . base_url('dashboard'));
            return;
        }

        $this->view('auth/login', [
            'error' => $_SESSION['auth_error'] ?? null,
            'redirect' => $request->input('redirect', '/dashboard'),
        ]);
        unset($_SESSION['auth_error']);
    }

    public function authenticate(Request $request): void
    {
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');
        $redirect = $request->input('redirect', '/dashboard');

        if (Auth::attempt($email, $password)) {
            // Update last_login_at timestamp
            $userRepo = new \App\Repositories\UserRepository();
            $user = $userRepo->findByEmail($email);
            if ($user) {
                db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute([
                    'id' => $user['id']
                ]);
            }
            
            header('Location: ' . base_url(ltrim($redirect, '/')));
            return;
        }

        $_SESSION['auth_error'] = 'Invalid credentials';
        header('Location: ' . base_url('login?redirect=' . urlencode($redirect)));
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . base_url('login'));
    }
}


