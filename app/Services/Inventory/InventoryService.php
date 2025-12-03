<?php

namespace App\Services\Inventory;

use App\Repositories\InventoryRepository;
use App\Services\Notifications\NotificationService;

class InventoryService
{
    protected InventoryRepository $inventory;
    protected NotificationService $notifications;

    public function __construct(?InventoryRepository $inventory = null, ?NotificationService $notifications = null)
    {
        $this->inventory = $inventory ?? new InventoryRepository();
        $this->notifications = $notifications ?? new NotificationService();
    }

    public function deductStock(int $inventoryItemId, int $locationId, float $quantity, string $reference, string $notes = '', string $type = 'sale'): void
    {
        $this->inventory->deduct($inventoryItemId, $locationId, $quantity, $reference, $notes, $type);

        $level = $this->inventory->level($inventoryItemId, $locationId);
        $reorderPoint = $this->inventory->reorderPoint($inventoryItemId);

        if ($reorderPoint > 0 && $level <= $reorderPoint) {
            $item = $this->inventory->getItem($inventoryItemId);
            $location = $this->inventory->getLocationName($locationId);
            $message = sprintf(
                '%s at %s is low (%.2f %s remaining, reorder point %.2f).',
                $item['name'] ?? 'Inventory item',
                $location ?? 'store',
                $level,
                $item['unit'] ?? '',
                $reorderPoint
            );

            // Create workflow task for inventory requisition
            try {
                $workflowService = new \App\Services\Workflow\WorkflowService();
                $workflowService->createInventoryRequisition(
                    $inventoryItemId,
                    $item['name'] ?? 'Inventory Item',
                    (int)$level,
                    (int)$reorderPoint
                );
            } catch (\Exception $e) {
                // Log error but continue with notifications
                error_log('Failed to create inventory requisition task: ' . $e->getMessage());
            }

            foreach (['operation_manager', 'finance_manager', 'admin'] as $role) {
                $this->notifications->notifyRole($role, 'Low stock alert', $message, [
                    'item_id' => $inventoryItemId,
                    'location_id' => $locationId,
                    'remaining' => $level,
                ]);
            }
        }
    }

    public function locations(): array
    {
        return $this->inventory->locations();
    }

    public function receiveStock(int $inventoryItemId, int $locationId, float $quantity, string $reference, string $notes = 'Receipt'): void
    {
        $this->inventory->addStock($inventoryItemId, $locationId, $quantity, $reference, $notes, 'purchase');
    }

    public function lowStockItems(int $limit = 5): array
    {
        return $this->inventory->lowStockItems($limit);
    }

    public function valuation(): float
    {
        return $this->inventory->inventoryValuation();
    }

    public function convertQuantity(float $quantity, ?string $sourceUnit, ?string $targetUnit, float $conversionFactor = 1.0): float
    {
        return $this->inventory->convertQuantity($quantity, $sourceUnit, $targetUnit, $conversionFactor);
    }

    public function updateProductionCost(int $posItemId): void
    {
        $this->inventory->updateProductionCost($posItemId);
    }
}

