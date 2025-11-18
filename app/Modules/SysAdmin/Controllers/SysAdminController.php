<?php

namespace App\Modules\SysAdmin\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Support\SysAuth;

class SysAdminController extends Controller
{
    public function dashboard(): void
    {
        SysAuth::require();

        $this->view('sysadmin/dashboard', [
            'pageTitle' => 'System Control Center',
            'stats' => [
                'hotels' => 3,
                'uptime' => '99.97%',
                'pending_updates' => 2,
            ],
        ]);
    }

    public function login(Request $request): void
    {
        if ($request->method() === 'POST') {
            $password = $request->input('password');
            if ($password && hash_equals($this->storedPasswordHash(), hash('sha256', $password))) {
                $_SESSION['sysadmin'] = true;
                header('Location: ' . base_url('sysadmin/dashboard'));
                return;
            }
            $error = 'Invalid credentials';
        }

        $this->view('sysadmin/login', [
            'error' => $error ?? null,
        ]);
    }

    public function logout(): void
    {
        unset($_SESSION['sysadmin']);
        header('Location: ' . base_url('sysadmin/login'));
    }

    protected function storedPasswordHash(): string
    {
        // In production this would come from env/secure storage
        return hash('sha256', 'super-secret-password');
    }
}

