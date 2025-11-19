<?php

namespace App\Modules\Payroll\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\UserRepository;
use App\Support\Auth;

class PayrollController extends Controller
{
    protected UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $period = $request->input('period', date('Y-m'));
        $status = $request->input('status', '');

        // Parse period (YYYY-MM)
        $periodParts = explode('-', $period);
        $year = (int)($periodParts[0] ?? date('Y'));
        $month = (int)($periodParts[1] ?? date('m'));

        $payrolls = $this->getPayrolls($year, $month, $status);

        $this->view('dashboard/payroll/index', [
            'payrolls' => $payrolls,
            'period' => $period,
            'status' => $status,
        ]);
    }

    public function generate(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $period = $request->input('period', date('Y-m'));
        $periodParts = explode('-', $period);
        $year = (int)($periodParts[0] ?? date('Y'));
        $month = (int)($periodParts[1] ?? date('m'));

        // Get all active employees
        $users = $this->users->all(null, 'active', null);

        $generated = 0;
        foreach ($users as $user) {
            // Check if payroll already exists
            $existing = $this->getPayrollForPeriod($user['id'], $year, $month);
            if ($existing) {
                continue;
            }

            // Generate payroll
            $this->createPayroll($user['id'], $year, $month);
            $generated++;
        }

        header('Location: ' . base_url('dashboard/payroll?period=' . urlencode($period) . '&success=' . $generated . '%20payrolls%20generated'));
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $payrollId = (int)$request->input('payroll_id');
        $basicSalary = (float)$request->input('basic_salary', 0);
        $allowances = (float)$request->input('allowances', 0);
        $deductions = (float)$request->input('deductions', 0);
        $notes = trim($request->input('notes', ''));

        if (!$payrollId || $basicSalary <= 0) {
            header('Location: ' . base_url('dashboard/payroll?error=Invalid%20payroll%20data'));
            return;
        }

        $payroll = $this->getPayroll($payrollId);
        if (!$payroll) {
            header('Location: ' . base_url('dashboard/payroll?error=Payroll%20not%20found'));
            return;
        }

        $netSalary = $basicSalary + $allowances - $deductions;

        $sql = "
            UPDATE payroll
            SET basic_salary = :basic_salary,
                allowances = :allowances,
                deductions = :deductions,
                net_salary = :net_salary,
                notes = :notes,
                status = 'processed',
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute([
            'id' => $payrollId,
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'net_salary' => $netSalary,
            'notes' => $notes,
        ]);

        header('Location: ' . base_url('dashboard/payroll?success=Payroll%20updated'));
    }

    public function edit(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager']);

        $payrollId = (int)$request->input('id');
        if (!$payrollId) {
            header('Location: ' . base_url('dashboard/payroll?error=Invalid%20payroll'));
            return;
        }

        $payroll = $this->getPayroll($payrollId);
        if (!$payroll) {
            header('Location: ' . base_url('dashboard/payroll?error=Payroll%20not%20found'));
            return;
        }

        $this->view('dashboard/payroll/edit', [
            'payroll' => $payroll,
        ]);
    }

    protected function getPayrolls(int $year, int $month, ?string $status = null): array
    {
        
        $params = [
            'year' => $year,
            'month' => $month,
        ];

        $sql = "
            SELECT payroll.*, users.name as employee_name, users.email as employee_email
            FROM payroll
            INNER JOIN users ON users.id = payroll.user_id
            WHERE YEAR(payroll.pay_period_end) = :year
            AND MONTH(payroll.pay_period_end) = :month
        ";

        if ($status) {
            $sql .= ' AND payroll.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY users.name ASC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    protected function getPayroll(int $payrollId): ?array
    {
        
        $params = ['id' => $payrollId];

        $sql = "
            SELECT payroll.*, users.name as employee_name, users.email as employee_email, users.role_key
            FROM payroll
            INNER JOIN users ON users.id = payroll.user_id
            WHERE payroll.id = :id
            LIMIT 1
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    protected function getPayrollForPeriod(int $userId, int $year, int $month): ?array
    {
        
        $params = [
            'user_id' => $userId,
            'year' => $year,
            'month' => $month,
        ];

        $sql = "
            SELECT * FROM payroll
            WHERE user_id = :user_id
            AND YEAR(pay_period_end) = :year
            AND MONTH(pay_period_end) = :month
        ";

        

        $sql .= ' LIMIT 1';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    protected function createPayroll(int $userId, int $year, int $month): void
    {
        

        // Calculate pay period dates
        $payPeriodStart = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
        $payPeriodEnd = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

        // Get user's base salary (would come from employee records or settings)
        $user = $this->users->find($userId);
        $baseSalary = 0; // Would be fetched from employee salary records

        $sql = "
            INSERT INTO payroll (user_id, pay_period_start, pay_period_end, basic_salary, allowances, deductions, net_salary, status, created_at, updated_at)
            VALUES (:user_id, :pay_period_start, :pay_period_end, :basic_salary, 0, 0, :basic_salary, 'pending', NOW(), NOW())
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'pay_period_start' => $payPeriodStart,
            'pay_period_end' => $payPeriodEnd,
            'basic_salary' => $baseSalary,
        ]);
    }
}

