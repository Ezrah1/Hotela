<?php

namespace App\Services;

use App\Repositories\InventoryRepository;
use App\Repositories\RequisitionRepository;
use PDO;

class AutoRequisitionService
{
    protected PDO $db;
    protected InventoryRepository $inventory;
    protected RequisitionRepository $requisitions;

    public function __construct()
    {
        $this->db = db();
        $this->inventory = new InventoryRepository();
        $this->requisitions = new RequisitionRepository();
    }

    /**
     * Check if item needs automatic requisition and create one if needed
     * Checks total stock across all locations, not just the specific location
     */
    public function checkAndCreateRequisition(int $itemId, int $locationId, float $newQuantity): ?int
    {
        try {
            // Get item details
            $item = $this->inventory->getItem($itemId);
            if (!$item) {
                error_log("Auto-requisition: Item {$itemId} not found");
                return null;
            }

            $reorderPoint = (float)($item['reorder_point'] ?? 0);
            $minimumStock = (float)($item['minimum_stock'] ?? 0);
            
            // Use minimum_stock if set, otherwise use reorder_point
            $threshold = $minimumStock > 0 ? $minimumStock : $reorderPoint;
            
            if ($threshold <= 0) {
                // No threshold set, skip silently (this is normal for items without reorder points)
                return null;
            }

        // Get total stock across all locations for this item
        // We need to check all locations, not just those with stock > 0
        $allLocations = $this->inventory->locations();
        $totalStock = 0;
        foreach ($allLocations as $loc) {
            $locStock = $this->inventory->level($itemId, (int)$loc['id']);
            $totalStock += $locStock;
        }

        // Check if total stock is below threshold
        if ($totalStock > $threshold) {
            return null; // Still above threshold across all locations
        }

        // Check if there's already an unresolved auto requisition for this item (any location)
        $existingReq = $this->getUnresolvedAutoRequisition($itemId);
        if ($existingReq) {
            return null; // Already has a pending requisition
        }

        // Calculate quantity needed (restock to 2x reorder point or minimum stock)
        $targetStock = max($reorderPoint * 2, $minimumStock * 2, $threshold * 2);
        $quantityNeeded = max($targetStock - $totalStock, $threshold);

        // Determine urgency based on how low total stock is
        $urgency = 'medium';
        if ($totalStock <= ($threshold * 0.2)) {
            $urgency = 'urgent';
        } elseif ($totalStock <= ($threshold * 0.5)) {
            $urgency = 'high';
        } elseif ($totalStock <= ($threshold * 0.8)) {
            $urgency = 'medium';
        } else {
            $urgency = 'low';
        }

        // Use the primary location (where stock was deducted)
        // This is used for logging purposes only - requisitions are item-based, not location-specific
        $primaryLocationId = $locationId;

            // Create automatic requisition
            $requisitionId = $this->createAutoRequisition($itemId, $primaryLocationId, $quantityNeeded, $urgency, $totalStock, $threshold);

            // Log the trigger
            $this->logTrigger($itemId, $primaryLocationId, $totalStock, $threshold, $requisitionId);

            error_log("Auto-requisition created: ID {$requisitionId} for item {$itemId} (stock: {$totalStock}, threshold: {$threshold})");
            return $requisitionId;
        } catch (\Exception $e) {
            error_log('Auto-requisition check failed: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Create an automatic requisition
     */
    protected function createAutoRequisition(int $itemId, int $locationId, float $quantity, string $urgency, float $currentQty, float $threshold): int
    {
        $item = $this->inventory->getItem($itemId);
        $locationName = $this->inventory->getLocationName($locationId);

        // Get all locations with stock for better context
        $allLocations = $this->inventory->getLocationsWithStock($itemId);
        $locationDetails = [];
        foreach ($allLocations as $loc) {
            $locationDetails[] = sprintf('%s: %s %s', $loc['location_name'], number_format((float)$loc['quantity'], 2), $item['unit'] ?? '');
        }
        $locationInfo = !empty($locationDetails) ? implode(', ', $locationDetails) : 'No stock at any location';
        
        $notes = sprintf(
            "Automatic requisition triggered. Total stock: %s %s (Threshold: %s %s). Stock by location: %s. Quantity needed: %s %s.",
            number_format($currentQty, 2),
            $item['unit'] ?? '',
            number_format($threshold, 2),
            $item['unit'] ?? '',
            $locationInfo,
            number_format($quantity, 2),
            $item['unit'] ?? ''
        );

        // Create requisition with type 'auto' - uses same workflow as manual requisitions
        $reference = 'AUTO-REQ-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        
        // Check if type column exists, if not use basic insert
        $typeColumnExists = $this->db->query("SHOW COLUMNS FROM requisitions LIKE 'type'")->fetch();
        $urgencyColumnExists = $this->db->query("SHOW COLUMNS FROM requisitions LIKE 'urgency'")->fetch();
        
        if ($typeColumnExists && $urgencyColumnExists) {
            $stmt = $this->db->prepare('
                INSERT INTO requisitions (reference, requested_by, status, type, urgency, notes)
                VALUES (:reference, NULL, :status, :type, :urgency, :notes)
            ');
            $stmt->execute([
                'reference' => $reference,
                'status' => 'pending', // Starts at pending, goes through same workflow
                'type' => 'auto',
                'urgency' => $urgency,
                'notes' => $notes,
            ]);
        } else {
            // Fallback if migration hasn't run
            error_log('Auto-requisition: type/urgency columns missing. Run migration: php scripts/migrate.php');
            $stmt = $this->db->prepare('
                INSERT INTO requisitions (reference, requested_by, status, notes)
                VALUES (:reference, NULL, :status, :notes)
            ');
            $stmt->execute([
                'reference' => $reference,
                'status' => 'pending',
                'notes' => $notes . ' [URGENCY: ' . $urgency . ']',
            ]);
        }
        
        // Auto-requisitions follow the same workflow as manual requisitions
        // Notification will be sent by RequisitionRepository::create() method
        // But since we're using direct INSERT, we need to send notification here
        $requisitionId = (int)$this->db->lastInsertId();
        $notificationService = new \App\Services\Notifications\NotificationService();
        $notificationService->notifyRole('operation_manager', 'Auto-Requisition Created', 
            sprintf('Automatic requisition %s created (%s urgency). Requires Ops verification.', 
                $reference, ucfirst($urgency)),
            ['requisition_id' => $requisitionId, 'reference' => $reference, 'type' => 'auto']
        );

        // Add item to requisition (requisitionId was set above)
        // Get suggested suppliers based on performance, price, and availability
        $supplierRepo = new \App\Repositories\SupplierRepository();
        $suggestedSuppliers = $supplierRepo->getSuggestedSuppliers($itemId, 3);
        
        // Use preferred supplier from item if available, otherwise use best suggested supplier
        $preferredSupplierId = $item['preferred_supplier_id'] ?? null;
        if (!$preferredSupplierId && !empty($suggestedSuppliers)) {
            $preferredSupplierId = (int)($suggestedSuppliers[0]['id'] ?? null);
        }
        
        $itemStmt = $this->db->prepare('
            INSERT INTO requisition_items (requisition_id, inventory_item_id, quantity, preferred_supplier_id)
            VALUES (:req, :item, :qty, :supplier)
        ');
        $itemStmt->execute([
            'req' => $requisitionId,
            'item' => $itemId,
            'qty' => $quantity,
            'supplier' => $preferredSupplierId,
        ]);

        // Update notes with supplier suggestions if available
        if (!empty($suggestedSuppliers)) {
            $supplierNames = array_map(function($s) {
                return $s['name'] . ($s['is_preferred'] ? ' (Preferred)' : '');
            }, array_slice($suggestedSuppliers, 0, 3));
            
            $supplierNote = "\n\nSuggested suppliers: " . implode(', ', $supplierNames);
            $updateStmt = $this->db->prepare('UPDATE requisitions SET notes = CONCAT(notes, :supplier_note) WHERE id = :id');
            $updateStmt->execute([
                'id' => $requisitionId,
                'supplier_note' => $supplierNote,
            ]);
        }

        return $requisitionId;
    }

    /**
     * Get unresolved auto requisition for an item (any location)
     */
    protected function getUnresolvedAutoRequisition(int $itemId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT r.*
            FROM requisitions r
            INNER JOIN requisition_items ri ON ri.requisition_id = r.id
            WHERE r.type = "auto"
            AND ri.inventory_item_id = :item
            AND r.status IN ("pending", "approved")
            ORDER BY r.created_at DESC
            LIMIT 1
        ');
        $stmt->execute([
            'item' => $itemId,
        ]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Log automatic requisition trigger
     */
    protected function logTrigger(int $itemId, int $locationId, float $currentQty, float $threshold, ?int $requisitionId): void
    {
        try {
            // Check if table exists first
            $tableExists = $this->db->query("SHOW TABLES LIKE 'auto_requisition_triggers'")->fetch();
            if (!$tableExists) {
                error_log('Auto-requisition: auto_requisition_triggers table does not exist. Run migration.');
                return;
            }
            
            $stmt = $this->db->prepare('
                INSERT INTO auto_requisition_triggers 
                (inventory_item_id, location_id, current_quantity, reorder_point, requisition_id)
                VALUES (:item, :location, :qty, :threshold, :req)
            ');
            $stmt->execute([
                'item' => $itemId,
                'location' => $locationId,
                'qty' => $currentQty,
                'threshold' => $threshold,
                'req' => $requisitionId,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail requisition creation
            error_log('Auto-requisition trigger log failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark trigger as resolved
     */
    public function resolveTrigger(int $requisitionId): void
    {
        $stmt = $this->db->prepare('
            UPDATE auto_requisition_triggers 
            SET resolved_at = NOW() 
            WHERE requisition_id = :req AND resolved_at IS NULL
        ');
        $stmt->execute(['req' => $requisitionId]);
    }
}

