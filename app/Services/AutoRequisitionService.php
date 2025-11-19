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
     */
    public function checkAndCreateRequisition(int $itemId, int $locationId, float $newQuantity): ?int
    {
        // Get item details
        $item = $this->inventory->getItem($itemId);
        if (!$item) {
            return null;
        }

        $reorderPoint = (float)($item['reorder_point'] ?? 0);
        $minimumStock = (float)($item['minimum_stock'] ?? 0);
        
        // Use minimum_stock if set, otherwise use reorder_point
        $threshold = $minimumStock > 0 ? $minimumStock : $reorderPoint;
        
        if ($threshold <= 0) {
            return null; // No threshold set, skip
        }

        // Check if stock is below threshold
        if ($newQuantity > $threshold) {
            return null; // Still above threshold
        }

        // Check if there's already an unresolved auto requisition for this item
        $existingReq = $this->getUnresolvedAutoRequisition($itemId, $locationId);
        if ($existingReq) {
            return null; // Already has a pending requisition
        }

        // Calculate quantity needed (restock to 2x reorder point or minimum stock)
        $targetStock = max($reorderPoint * 2, $minimumStock * 2, $threshold * 2);
        $quantityNeeded = max($targetStock - $newQuantity, $threshold);

        // Determine urgency based on how low stock is
        $urgency = 'medium';
        if ($newQuantity <= ($threshold * 0.2)) {
            $urgency = 'urgent';
        } elseif ($newQuantity <= ($threshold * 0.5)) {
            $urgency = 'high';
        } elseif ($newQuantity <= ($threshold * 0.8)) {
            $urgency = 'medium';
        } else {
            $urgency = 'low';
        }

        // Create automatic requisition
        $requisitionId = $this->createAutoRequisition($itemId, $locationId, $quantityNeeded, $urgency, $newQuantity, $threshold);

        // Log the trigger
        $this->logTrigger($itemId, $locationId, $newQuantity, $threshold, $requisitionId);

        return $requisitionId;
    }

    /**
     * Create an automatic requisition
     */
    protected function createAutoRequisition(int $itemId, int $locationId, float $quantity, string $urgency, float $currentQty, float $threshold): int
    {
        $item = $this->inventory->getItem($itemId);
        $locationName = $this->inventory->getLocationName($locationId);

        $notes = sprintf(
            "Automatic requisition triggered. Current stock: %s %s (Location: %s). Threshold: %s %s. Quantity needed: %s %s.",
            number_format($currentQty, 2),
            $item['unit'] ?? '',
            $locationName ?? 'Unknown',
            number_format($threshold, 2),
            $item['unit'] ?? '',
            number_format($quantity, 2),
            $item['unit'] ?? ''
        );

        // Create requisition with type 'auto'
        $reference = 'AUTO-REQ-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $stmt = $this->db->prepare('
            INSERT INTO requisitions (reference, requested_by, status, type, urgency, notes)
            VALUES (:reference, NULL, :status, :type, :urgency, :notes)
        ');
        $stmt->execute([
            'reference' => $reference,
            'status' => 'pending',
            'type' => 'auto',
            'urgency' => $urgency,
            'notes' => $notes,
        ]);

        $requisitionId = (int)$this->db->lastInsertId();

        // Add item to requisition
        $preferredSupplierId = $item['preferred_supplier_id'] ?? null;
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

        return $requisitionId;
    }

    /**
     * Get unresolved auto requisition for an item
     */
    protected function getUnresolvedAutoRequisition(int $itemId, int $locationId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT r.*
            FROM requisitions r
            INNER JOIN requisition_items ri ON ri.requisition_id = r.id
            INNER JOIN auto_requisition_triggers art ON art.requisition_id = r.id
            WHERE r.type = "auto"
            AND ri.inventory_item_id = :item
            AND art.location_id = :location
            AND r.status IN ("pending", "approved")
            AND art.resolved_at IS NULL
            ORDER BY r.created_at DESC
            LIMIT 1
        ');
        $stmt->execute([
            'item' => $itemId,
            'location' => $locationId,
        ]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Log automatic requisition trigger
     */
    protected function logTrigger(int $itemId, int $locationId, float $currentQty, float $threshold, ?int $requisitionId): void
    {
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

