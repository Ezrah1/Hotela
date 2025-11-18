<?php

namespace App\Modules\Tasks\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Support\Auth;

class TaskController extends Controller
{
	public function index(Request $request): void
	{
		Auth::requireRoles();

		$viewData = [
			'filters' => [
				'view' => $request->input('view', 'my'),
				'status' => $request->input('status', 'open'),
				'q' => $request->input('q', ''),
			],
			'tasks' => [], // Placeholder; to be wired to repository/service
		];

		$this->view('dashboard/tasks/index', $viewData);
	}

	public function create(Request $request): void
	{
		Auth::requireRoles();

		$this->view('dashboard/tasks/create', []);
	}

	public function getStaffByDepartment(Request $request): void
	{
		Auth::requireRoles();

		$department = $request->input('department');
		if (!$department) {
			header('Content-Type: application/json');
			echo json_encode(['staff' => []]);
			return;
		}

		$userRepo = new \App\Repositories\UserRepository();
		$staff = $userRepo->byDepartment($department);

		header('Content-Type: application/json');
		echo json_encode(['staff' => $staff]);
	}
}


