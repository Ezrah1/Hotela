<?php

namespace App\Modules\Dashboard\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Dashboard\RoleDashboard;
use App\Support\Auth;

class DashboardController extends Controller
{
    protected RoleDashboard $dashboards;

    public function __construct()
    {
        $this->dashboards = new RoleDashboard();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles();

        $user = Auth::user();
        $role = $user['role'];

        if ($request->input('role')) {
            Auth::setRole($request->input('role'));
            $user = Auth::user();
            $role = $user['role'];
        }

        $config = $this->dashboards->config($role);
        $view = $this->dashboards->view($role);
        $data = $this->dashboards->data($role);

        $this->view('dashboard/base', [
            'user' => $user,
            'roleConfig' => $config,
            'contentView' => $view,
            'contentData' => $data,
        ]);
    }
}


