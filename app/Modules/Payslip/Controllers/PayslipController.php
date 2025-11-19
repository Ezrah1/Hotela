<?php

namespace App\Modules\Payslip\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Settings\SettingStore;
use App\Support\Auth;

class PayslipController extends Controller
{
    protected SettingStore $settings;

    public function __construct()
    {
        $this->settings = new SettingStore();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles();

        // Check if payslip is enabled
        $payslipSettings = $this->settings->group('payslip');
        if (empty($payslipSettings['enabled'])) {
            $this->view('message', [
                'type' => 'error',
                'title' => 'Payslip Feature Disabled',
                'message' => 'Payslip feature is currently disabled. Please contact your administrator.',
                'redirect' => base_url('dashboard'),
                'delay' => 5,
            ]);
            return;
        }

        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        // Get user's payslips
        $payslips = $this->getPayslips($userId);

        $this->view('dashboard/payslip/index', [
            'payslips' => $payslips,
            'user' => $user,
        ]);
    }

    public function show(Request $request): void
    {
        Auth::requireRoles();

        // Check if payslip is enabled
        $payslipSettings = $this->settings->group('payslip');
        if (empty($payslipSettings['enabled'])) {
            $this->view('message', [
                'type' => 'error',
                'title' => 'Payslip Feature Disabled',
                'message' => 'Payslip feature is currently disabled. Please contact your administrator.',
                'redirect' => base_url('dashboard'),
                'delay' => 5,
            ]);
            return;
        }

        $payslipId = (int)$request->input('id');
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);

        // Get payslip
        $payslip = $this->getPayslip($payslipId, $userId);
        if (!$payslip) {
            header('Location: ' . base_url('dashboard/payslip?error=Payslip%20not%20found'));
            return;
        }

        $this->view('dashboard/payslip/view', [
            'payslip' => $payslip,
            'user' => $user,
        ]);
    }

    protected function getPayslips(int $userId): array
    {
        $params = ['user_id' => $userId];

        $sql = "
            SELECT payroll.*, users.name as employee_name, users.email as employee_email
            FROM payroll
            INNER JOIN users ON users.id = payroll.user_id
            WHERE payroll.user_id = :user_id
            ORDER BY payroll.pay_period_end DESC LIMIT 12
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    protected function getPayslip(int $payslipId, int $userId): ?array
    {
        $params = [
            'id' => $payslipId,
            'user_id' => $userId,
        ];

        $sql = "
            SELECT payroll.*, users.name as employee_name, users.email as employee_email, users.role_key
            FROM payroll
            INNER JOIN users ON users.id = payroll.user_id
            WHERE payroll.id = :id AND payroll.user_id = :user_id
            LIMIT 1
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ?: null;
    }
}

