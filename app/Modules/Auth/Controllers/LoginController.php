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
        $username = trim($request->input('username', ''));
        $password = $request->input('password', '');
        $redirect = $request->input('redirect', '/staff/dashboard');

        if (empty($username)) {
            $_SESSION['auth_error'] = 'Username is required';
            header('Location: ' . base_url('staff/login?redirect=' . urlencode($redirect)));
            return;
        }

        if (!Auth::attempt($username, $password)) {
            $_SESSION['auth_error'] = 'Invalid credentials';
            header('Location: ' . base_url('staff/login?redirect=' . urlencode($redirect)));
            return;
        }

        // Get user after successful authentication
        $userRepo = new \App\Repositories\UserRepository();
        $user = $userRepo->findByUsernameOrEmail($username);
        
        if (!$user) {
            Auth::logout();
            $_SESSION['auth_error'] = 'User not found';
            header('Location: ' . base_url('staff/login?redirect=' . urlencode($redirect)));
            return;
        }

        // Check attendance requirements (only for non-exempt roles)
        $role = $user['role_key'] ?? '';
        $exemptRoles = ['admin', 'director', 'tech_admin', 'security']; // Admin, Director, Tech Admin, and Security staff don't need check-in
        
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
        }

        // Update last_login_at timestamp
        db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute([
            'id' => $user['id']
        ]);
        
        header('Location: ' . base_url(ltrim($redirect, '/')));
    }

    public function logout(): void
    {
        // Clear tenant session
        Auth::logout();
        
        // Also clear any system admin session if present (shouldn't be, but be thorough)
        if (isset($_SESSION['system_admin_id'])) {
            unset($_SESSION['system_admin_id']);
        }
        
        // Destroy session completely for security
        session_destroy();
        session_start();
        
        header('Location: ' . base_url('staff/login?logged_out=1'));
        exit;
    }
}


