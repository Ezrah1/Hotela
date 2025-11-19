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
            header('Location: ' . base_url('staff/dashboard'));
            return;
        }

        $this->view('auth/login', [
            'error' => $_SESSION['auth_error'] ?? null,
            'redirect' => $request->input('redirect', '/staff/dashboard'),
        ]);
        unset($_SESSION['auth_error']);
    }

    public function authenticate(Request $request): void
    {
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');
        $redirect = $request->input('redirect', '/staff/dashboard');

        if (!Auth::attempt($email, $password)) {
            $_SESSION['auth_error'] = 'Invalid credentials';
            header('Location: ' . base_url('staff/login?redirect=' . urlencode($redirect)));
            return;
        }

        // Get user after successful authentication
        $userRepo = new \App\Repositories\UserRepository();
        $user = $userRepo->findByEmail($email);
        
        if (!$user) {
            Auth::logout();
            $_SESSION['auth_error'] = 'User not found';
            header('Location: ' . base_url('staff/login?redirect=' . urlencode($redirect)));
            return;
        }

        // Check attendance requirements
        $role = $user['role_key'] ?? '';
        $exemptRoles = ['admin', 'director', 'tech_admin'];
        
        if (!in_array($role, $exemptRoles, true)) {
            // Regular employees must have attendance check-in
            $attendanceRepo = new \App\Repositories\AttendanceRepository();
            $overrideRepo = new \App\Repositories\LoginOverrideRepository();
            
            // Check if checked in today
            $isCheckedIn = $attendanceRepo->isCheckedIn($user['id']);
            
            // Check if checked out today
            $isCheckedOut = $attendanceRepo->isCheckedOut($user['id']);
            
            // Check for active override
            $override = $overrideRepo->getActiveOverride($user['id']);
            
            if ($isCheckedOut && !$override) {
                // User has checked out and no override
                Auth::logout();
                $_SESSION['auth_error'] = 'You have already ended your shift today. Please contact an administrator for access.';
                header('Location: ' . base_url('staff/login?redirect=' . urlencode($redirect)));
                return;
            }
            
            if (!$isCheckedIn && !$override) {
                // User has not checked in and no override
                Auth::logout();
                $_SESSION['auth_error'] = 'You must check in at the security desk before logging in.';
                header('Location: ' . base_url('staff/login?redirect=' . urlencode($redirect)));
                return;
            }
            
            // Mark override as used if it exists
            if ($override) {
                $overrideRepo->markAsUsed($override['id']);
            }
        }

        // Update last_login_at timestamp
        db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute([
            'id' => $user['id']
        ]);
        
        header('Location: ' . base_url(ltrim($redirect, '/')));
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . base_url('staff/login'));
    }
}


