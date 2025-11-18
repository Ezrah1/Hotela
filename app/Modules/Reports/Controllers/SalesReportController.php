<?php

namespace App\Modules\Reports\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\SalesReportRepository;
use App\Support\Auth;

class SalesReportController extends Controller
{
	protected SalesReportRepository $reports;

	public function __construct()
	{
		$this->reports = new SalesReportRepository();
	}

	public function index(Request $request): void
	{
		Auth::requireRoles(['admin', 'finance_manager', 'operation_manager', 'director', 'cashier']);

		$start = $this->sanitizeDate($request->input('start')) ?? date('Y-m-01');
		$end = $this->sanitizeDate($request->input('end')) ?? date('Y-m-d');

		if (strtotime($end) < strtotime($start)) {
			$end = $start;
		}

		$summary = $this->reports->summary($start, $end);
		$payments = $this->reports->paymentBreakdown($start, $end);
		$trend = $this->reports->trend($start, $end);
		$topItems = $this->reports->topItems($start, $end, 5);
		$topStaff = $this->reports->topStaff($start, $end, 5);

		$this->view('dashboard/reports/sales', [
			'filters' => [
				'start' => $start,
				'end' => $end,
			],
			'summary' => $summary,
			'payments' => $payments,
			'trend' => $trend,
			'topItems' => $topItems,
			'topStaff' => $topStaff,
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
}


