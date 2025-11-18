<?php

namespace App\Modules\Reports\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\SalesReportRepository;
use App\Support\Auth;

class FinanceReportController extends Controller
{
    protected SalesReportRepository $reports;

    public function __construct()
    {
        $this->reports = new SalesReportRepository();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin', 'finance_manager', 'director']);

        $start = $this->sanitizeDate($request->input('start')) ?? date('Y-m-01');
        $end = $this->sanitizeDate($request->input('end')) ?? date('Y-m-d');

        if (strtotime($end) < strtotime($start)) {
            $end = $start;
        }

        // Get POS sales summary
        $posSummary = $this->reports->summary($start, $end);
        $posPayments = $this->reports->paymentBreakdown($start, $end);
        $posTrend = $this->reports->trend($start, $end);

        // Get booking revenue
        $bookingRevenue = $this->getBookingRevenue($start, $end);
        $bookingPayments = $this->getBookingPaymentBreakdown($start, $end);
        $outstandingBalance = $this->getOutstandingBalance();

        // Combined totals
        $totalRevenue = ($posSummary['revenue'] ?? 0) + ($bookingRevenue['total'] ?? 0);
        $totalBookings = $bookingRevenue['count'] ?? 0;
        $totalOrders = $posSummary['orders'] ?? 0;

        $this->view('dashboard/reports/finance', [
            'filters' => [
                'start' => $start,
                'end' => $end,
            ],
            'posSummary' => $posSummary,
            'posPayments' => $posPayments,
            'posTrend' => $posTrend,
            'bookingRevenue' => $bookingRevenue,
            'bookingPayments' => $bookingPayments,
            'outstandingBalance' => $outstandingBalance,
            'totalRevenue' => $totalRevenue,
            'totalBookings' => $totalBookings,
            'totalOrders' => $totalOrders,
        ]);
    }

    protected function sanitizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    protected function getBookingRevenue(string $start, string $end): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'start' => $start,
            'end' => $end,
        ];

        $sql = "
            SELECT
                COUNT(*) as count,
                SUM(total_amount) as total,
                AVG(total_amount) as avg_amount,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_total,
                SUM(CASE WHEN payment_status = 'partial' THEN total_amount ELSE 0 END) as partial_total,
                SUM(CASE WHEN payment_status = 'unpaid' THEN total_amount ELSE 0 END) as unpaid_total
            FROM reservations
            WHERE check_in >= :start AND check_in <= :end
            AND status IN ('confirmed', 'checked_in', 'checked_out')
        ";

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch() ?: [];

        return [
            'count' => (int)($result['count'] ?? 0),
            'total' => (float)($result['total'] ?? 0),
            'avg_amount' => (float)($result['avg_amount'] ?? 0),
            'paid_total' => (float)($result['paid_total'] ?? 0),
            'partial_total' => (float)($result['partial_total'] ?? 0),
            'unpaid_total' => (float)($result['unpaid_total'] ?? 0),
        ];
    }

    protected function getBookingPaymentBreakdown(string $start, string $end): array
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [
            'start' => $start,
            'end' => $end,
        ];

        $sql = "
            SELECT
                payment_status,
                COUNT(*) as count,
                SUM(total_amount) as total
            FROM reservations
            WHERE check_in >= :start AND check_in <= :end
            AND status IN ('confirmed', 'checked_in', 'checked_out')
        ";

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $sql .= ' GROUP BY payment_status';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    protected function getOutstandingBalance(): float
    {
        $tenantId = \App\Support\Tenant::id();
        $params = [];

        $sql = 'SELECT SUM(balance) as total FROM folios WHERE balance > 0 AND status = "open"';

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (float)($result['total'] ?? 0);
    }
}

