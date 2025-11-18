<?php

namespace App\Repositories;

use PDO;

class SalesReportRepository
{
	protected PDO $db;
	protected ?int $tenantId;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? db();
		$this->tenantId = \App\Support\Tenant::id();
	}

	protected function tenantFilter(string $column, array &$params): string
	{
		if ($this->tenantId === null) {
			return '';
		}

		$key = $this->paramKey($column . '_tenant_' . count($params));
		$params[$key] = $this->tenantId;

		return " AND {$column} = :{$key}";
	}

	protected function dateFilter(string $column, ?string $start, ?string $end, array &$params): string
	{
		$clause = '';

		if ($start) {
			$key = $this->paramKey($column . '_start_' . count($params));
			$params[$key] = $start;
			$clause .= " AND DATE({$column}) >= :{$key}";
		}

		if ($end) {
			$key = $this->paramKey($column . '_end_' . count($params));
			$params[$key] = $end;
			$clause .= " AND DATE({$column}) <= :{$key}";
		}

		return $clause;
	}

	protected function paramKey(string $raw): string
	{
		return preg_replace('/[^a-zA-Z0-9_]/', '_', $raw);
	}

	public function summary(?string $start, ?string $end): array
	{
		$params = [];
		$sql = 'SELECT COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue, COALESCE(AVG(total),0) AS avg_order
		        FROM pos_sales WHERE 1=1';
		$sql .= $this->tenantFilter('pos_sales.tenant_id', $params);
		$sql .= $this->dateFilter('pos_sales.created_at', $start, $end, $params);

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		$row = $stmt->fetch() ?: ['orders' => 0, 'revenue' => 0, 'avg_order' => 0];

		$bestDay = $this->bestDay($start, $end);

		return [
			'orders' => (int)$row['orders'],
			'revenue' => (float)$row['revenue'],
			'avg_order' => (float)$row['avg_order'],
			'best_day' => $bestDay,
		];
	}

	protected function bestDay(?string $start, ?string $end): array
	{
		$params = [];
		$sql = '
			SELECT DATE(created_at) AS day, SUM(total) AS total
			FROM pos_sales
			WHERE 1=1
		';
		$sql .= $this->tenantFilter('pos_sales.tenant_id', $params);
		$sql .= $this->dateFilter('pos_sales.created_at', $start, $end, $params);
		$sql .= ' GROUP BY DATE(created_at) ORDER BY total DESC LIMIT 1';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		$row = $stmt->fetch();

		if (!$row) {
			return ['day' => null, 'total' => 0];
		}

		return [
			'day' => $row['day'],
			'total' => (float)$row['total'],
		];
	}

	public function paymentBreakdown(?string $start, ?string $end): array
	{
		$params = [];
		$sql = '
			SELECT payment_type, SUM(total) AS total, COUNT(*) AS orders
			FROM pos_sales
			WHERE 1=1
		';
		$sql .= $this->tenantFilter('pos_sales.tenant_id', $params);
		$sql .= $this->dateFilter('pos_sales.created_at', $start, $end, $params);
		$sql .= ' GROUP BY payment_type ORDER BY total DESC';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll() ?: [];
	}

	public function trend(?string $start, ?string $end): array
	{
		$params = [];
		$sql = '
			SELECT DATE(created_at) AS day, SUM(total) AS total, COUNT(*) AS orders
			FROM pos_sales
			WHERE 1=1
		';
		$sql .= $this->tenantFilter('pos_sales.tenant_id', $params);
		$sql .= $this->dateFilter('pos_sales.created_at', $start, $end, $params);
		$sql .= ' GROUP BY DATE(created_at) ORDER BY day ASC';

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll() ?: [];
	}

	public function topItems(?string $start, ?string $end, int $limit = 5): array
	{
		$params = [];
		$sql = '
			SELECT COALESCE(pos_items.name, CONCAT(\'Item #\', pos_sale_items.item_id)) AS item_name,
			       SUM(pos_sale_items.quantity) AS quantity,
			       SUM(pos_sale_items.line_total) AS revenue
			FROM pos_sale_items
			INNER JOIN pos_sales ON pos_sales.id = pos_sale_items.sale_id
			LEFT JOIN pos_items ON pos_items.id = pos_sale_items.item_id
			WHERE 1=1
		';
		$sql .= $this->tenantFilter('pos_sales.tenant_id', $params);
		$sql .= $this->dateFilter('pos_sales.created_at', $start, $end, $params);
		$sql .= ' GROUP BY pos_sale_items.item_id, item_name ORDER BY revenue DESC LIMIT ' . (int)$limit;

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll() ?: [];
	}

	public function topStaff(?string $start, ?string $end, int $limit = 5): array
	{
		$params = [];
		$sql = '
			SELECT COALESCE(users.name, \'Unassigned\') AS staff_name,
			       COUNT(pos_sales.id) AS orders,
			       SUM(pos_sales.total) AS revenue
			FROM pos_sales
			LEFT JOIN users ON users.id = pos_sales.user_id
			WHERE 1=1
		';
		$sql .= $this->tenantFilter('pos_sales.tenant_id', $params);
		$sql .= $this->dateFilter('pos_sales.created_at', $start, $end, $params);
		$sql .= ' GROUP BY staff_name ORDER BY revenue DESC LIMIT ' . (int)$limit;

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll() ?: [];
	}

	public function topCategories(?string $start, ?string $end, int $limit = 5): array
	{
		$params = [];
		$sql = '
			SELECT COALESCE(pos_categories.name, \'Uncategorized\') AS category_name,
			       SUM(pos_sale_items.line_total) AS revenue
			FROM pos_sale_items
			INNER JOIN pos_sales ON pos_sales.id = pos_sale_items.sale_id
			LEFT JOIN pos_items ON pos_items.id = pos_sale_items.item_id
			LEFT JOIN pos_categories ON pos_categories.id = pos_items.category_id
			WHERE 1=1
		';
		$sql .= $this->tenantFilter('pos_sales.tenant_id', $params);
		$sql .= $this->dateFilter('pos_sales.created_at', $start, $end, $params);
		$sql .= ' GROUP BY category_name ORDER BY revenue DESC LIMIT ' . (int)$limit;

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll() ?: [];
	}
}

