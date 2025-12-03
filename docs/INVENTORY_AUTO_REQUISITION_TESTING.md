# Inventory Auto-Requisition System - Testing & Documentation

## Overview

The Inventory Auto-Requisition System automatically creates requisition requests when inventory items drop below their reorder point. The system also displays stock levels per location in the inventory management interface.

## Components

### 1. AutoRequisitionService (`app/Services/AutoRequisitionService.php`)
- **Purpose**: Automatically creates requisitions when stock drops below threshold
- **Key Method**: `checkAndCreateRequisition(int $itemId, int $locationId, float $newQuantity): ?int`

### 2. InventoryRepository (`app/Repositories/InventoryRepository.php`)
- **Purpose**: Manages inventory data and stock levels
- **Key Methods**:
  - `deduct()`: Deducts stock and triggers auto-requisition check
  - `itemsWithStock()`: Returns items with stock breakdown by location
  - `getLocationsWithStock()`: Gets stock levels per location for an item

### 3. InventoryService (`app/Services/Inventory/InventoryService.php`)
- **Purpose**: Service layer for inventory operations
- **Key Method**: `deductStock()`: Wrapper that calls repository and sends notifications

## Functionality Flow

### Auto-Requisition Trigger Flow

```
1. Stock Deduction Event
   ↓
2. InventoryRepository::deduct() called
   ↓
3. Stock updated in database
   ↓
4. AutoRequisitionService::checkAndCreateRequisition() called
   ↓
5. Check total stock across ALL locations
   ↓
6. Compare with reorder_point or minimum_stock
   ↓
7. If below threshold AND no existing requisition:
   - Calculate quantity needed (2x threshold)
   - Determine urgency level
   - Create requisition with type 'auto'
   - Log trigger in auto_requisition_triggers table
```

### Stock Display Flow

```
1. User views inventory list
   ↓
2. InventoryController::index() called
   ↓
3. InventoryRepository::itemsWithStock() called
   ↓
4. For each item:
   - Get total stock (SUM across locations)
   - Get stock breakdown per location
   ↓
5. Display in inventory table with:
   - Total stock with unit
   - Stock per location badges
   - Low stock indicators
```

## Test Cases

### Test Case 1: Basic Auto-Requisition Creation

**Setup:**
- Item: "House Wine Glass" (ID: 1)
- Reorder Point: 20.000
- Current Stock: 25.000 (across all locations)
- Location: Main Store (ID: 1)

**Steps:**
1. Deduct 10 units from Main Store
2. New stock: 15.000 (below reorder point of 20.000)

**Expected Result:**
- ✅ Auto-requisition created with type 'auto'
- ✅ Reference starts with 'AUTO-REQ-'
- ✅ Status: 'pending'
- ✅ Urgency: 'medium' (15.000 is 75% of 20.000)
- ✅ Quantity needed: ~40.000 (2x reorder point - current stock)
- ✅ Notes include stock breakdown by location
- ✅ Entry in `auto_requisition_triggers` table

**Test Query:**
```sql
-- Check requisition was created
SELECT * FROM requisitions WHERE type = 'auto' ORDER BY created_at DESC LIMIT 1;

-- Check requisition items
SELECT * FROM requisition_items WHERE requisition_id = [LAST_REQ_ID];

-- Check trigger log
SELECT * FROM auto_requisition_triggers WHERE requisition_id = [LAST_REQ_ID];
```

### Test Case 2: Duplicate Prevention

**Setup:**
- Item: "House Wine Glass" (ID: 1)
- Reorder Point: 20.000
- Current Stock: 15.000
- Existing requisition: Pending auto-requisition for this item

**Steps:**
1. Deduct 5 more units
2. New stock: 10.000 (still below threshold)

**Expected Result:**
- ✅ NO new requisition created
- ✅ Existing requisition remains unchanged
- ✅ System logs that duplicate was prevented

**Test Query:**
```sql
-- Count auto-requisitions for this item
SELECT COUNT(*) FROM requisitions r
INNER JOIN requisition_items ri ON ri.requisition_id = r.id
WHERE r.type = 'auto' 
AND ri.inventory_item_id = 1
AND r.status IN ('pending', 'approved');
-- Should return 1
```

### Test Case 3: Urgency Levels

**Test 3a: Urgent (≤20% of threshold)**
- Stock: 4.000, Threshold: 20.000
- Expected: urgency = 'urgent'

**Test 3b: High (≤50% of threshold)**
- Stock: 10.000, Threshold: 20.000
- Expected: urgency = 'high'

**Test 3c: Medium (≤80% of threshold)**
- Stock: 15.000, Threshold: 20.000
- Expected: urgency = 'medium'

**Test 3d: Low (>80% of threshold)**
- Stock: 18.000, Threshold: 20.000
- Expected: urgency = 'low' (but requisition still created if ≤ threshold)

### Test Case 4: Multi-Location Stock Calculation

**Setup:**
- Item: "House Wine Glass" (ID: 1)
- Reorder Point: 20.000
- Main Store: 10.000
- Kitchen: 8.000
- Bar: 5.000
- **Total: 23.000**

**Steps:**
1. Deduct 5 units from Main Store
2. New totals: Main Store: 5.000, Total: 18.000

**Expected Result:**
- ✅ System calculates TOTAL stock (18.000) across all locations
- ✅ Compares total (18.000) with threshold (20.000)
- ✅ Creates requisition because total < threshold
- ✅ Notes include breakdown: "Main Store: 5.00, Kitchen: 8.00, Bar: 5.00"

### Test Case 5: Stock Display Per Location

**Setup:**
- Item: "House Wine Glass" (ID: 1)
- Main Store: 15.000
- Kitchen: 10.000
- Bar: 5.000

**Steps:**
1. Navigate to Inventory page
2. View item in table

**Expected Result:**
- ✅ Total stock displayed: "30.00 Unit"
- ✅ Location badges shown:
  - "Main Store: 15.00"
  - "Kitchen: 10.00"
  - "Bar: 5.00"
- ✅ Badges styled with gray background
- ✅ Stock values in bold with primary color

### Test Case 6: No Stock Available

**Setup:**
- Item: "House Wine Glass" (ID: 1)
- All locations: 0.000

**Steps:**
1. Navigate to Inventory page
2. View item in table

**Expected Result:**
- ✅ Total stock: "0.00 Unit"
- ✅ Location info: "No stock available" (italic, gray)
- ✅ Low stock indicator shown (⚠)

### Test Case 7: No Reorder Point Set

**Setup:**
- Item: "House Wine Glass" (ID: 1)
- Reorder Point: 0.000 (not set)
- Current Stock: 5.000

**Steps:**
1. Deduct stock
2. Stock drops to 2.000

**Expected Result:**
- ✅ NO auto-requisition created
- ✅ System skips check (threshold = 0)
- ✅ No error thrown

### Test Case 8: Minimum Stock vs Reorder Point

**Setup:**
- Item: "House Wine Glass" (ID: 1)
- Reorder Point: 20.000
- Minimum Stock: 15.000
- Current Stock: 18.000

**Steps:**
1. Deduct stock
2. Stock drops to 10.000

**Expected Result:**
- ✅ System uses minimum_stock (15.000) as threshold (higher priority)
- ✅ Creates requisition because 10.000 < 15.000

## Integration Points

### Where Stock is Deducted

1. **POS Sales** (`app/Modules/POS/Controllers/POSController.php`)
   - Line 370: `$this->inventoryService->deductStock()`
   - Triggers: When POS sale is completed

2. **Website Orders** (`app/Modules/Website/Controllers/OrderController.php`)
   - Line 219: `$this->inventory->deductStock()`
   - Triggers: When website order is placed

3. **M-Pesa Callbacks** (`app/Modules/PaymentGateway/Controllers/MpesaCallbackController.php`)
   - Line 186: `$invService->deductStock()`
   - Triggers: When M-Pesa payment is confirmed

4. **Requisition Releases** (`app/Modules/Inventory/Controllers/InventoryController.php`)
   - Line 224: `$this->inventoryService->deductStock()`
   - Triggers: When requisition is released to staff

## Database Schema

### Tables Used

1. **requisitions**
   - `type`: 'auto' for automatic requisitions
   - `status`: 'pending', 'approved', 'rejected', 'ordered', 'received'
   - `urgency`: 'low', 'medium', 'high', 'urgent'

2. **requisition_items**
   - Links requisitions to inventory items
   - Stores quantity needed
   - Includes preferred_supplier_id

3. **auto_requisition_triggers**
   - Logs when auto-requisitions are triggered
   - Tracks: item_id, location_id, current_quantity, reorder_point, requisition_id
   - `resolved_at`: NULL until requisition is fulfilled

4. **inventory_levels**
   - Stores stock per item per location
   - Updated when stock is deducted/added

5. **inventory_items**
   - `reorder_point`: Minimum stock before reordering
   - `minimum_stock`: Alternative threshold (takes priority)

## Manual Testing Steps

### Test Auto-Requisition Creation

1. **Prepare Test Data:**
   ```sql
   -- Set up test item
   UPDATE inventory_items 
   SET reorder_point = 20.000 
   WHERE id = 1;
   
   -- Set initial stock
   UPDATE inventory_levels 
   SET quantity = 25.000 
   WHERE item_id = 1 AND location_id = 1;
   ```

2. **Trigger Deduction:**
   - Make a POS sale with the item
   - OR manually deduct stock via inventory adjustment
   - Deduct enough to go below reorder point (e.g., deduct 10 units)

3. **Verify Requisition:**
   ```sql
   SELECT r.*, ri.quantity, ii.name 
   FROM requisitions r
   INNER JOIN requisition_items ri ON ri.requisition_id = r.id
   INNER JOIN inventory_items ii ON ii.id = ri.inventory_item_id
   WHERE r.type = 'auto'
   ORDER BY r.created_at DESC
   LIMIT 1;
   ```

4. **Check Trigger Log:**
   ```sql
   SELECT * FROM auto_requisition_triggers 
   ORDER BY created_at DESC 
   LIMIT 1;
   ```

### Test Stock Display

1. **Navigate to:** `/staff/dashboard/inventory`
2. **Verify:**
   - Total stock is displayed for each item
   - Location breakdown shows for items with stock
   - Low stock items are highlighted (red background)
   - Warning icon (⚠) appears for low stock items

### Test Duplicate Prevention

1. **Create initial requisition** (as above)
2. **Deduct more stock** (should NOT create new requisition)
3. **Verify:**
   ```sql
   SELECT COUNT(*) as req_count
   FROM requisitions r
   INNER JOIN requisition_items ri ON ri.requisition_id = r.id
   WHERE r.type = 'auto'
   AND ri.inventory_item_id = 1
   AND r.status IN ('pending', 'approved');
   -- Should be 1
   ```

## Error Handling

### Expected Behaviors

1. **Database Error:**
   - Auto-requisition failure is logged but doesn't break stock deduction
   - Error logged to PHP error log

2. **Missing Item:**
   - If item doesn't exist, auto-requisition check returns null
   - No error thrown

3. **No Threshold Set:**
   - If reorder_point = 0 and minimum_stock = 0, check is skipped
   - No requisition created

4. **Missing Locations:**
   - If no locations exist, total stock = 0
   - System still works correctly

## Performance Considerations

1. **Stock Calculation:**
   - Total stock is calculated by querying all locations
   - Consider caching if performance becomes an issue

2. **Duplicate Check:**
   - Checks for existing requisitions on every deduction
   - Index on `requisitions.type` and `requisition_items.inventory_item_id` recommended

3. **Location Breakdown:**
   - Stock per location is fetched for each item in list view
   - Consider pagination for large inventories

## Configuration

### Setting Reorder Points

1. Navigate to: Inventory → Edit Item
2. Set "Reorder Point" field
3. Optionally set "Minimum Stock" (takes priority)

### Viewing Auto-Requisitions

1. Navigate to: Inventory → Requisitions
2. Filter by type: "auto"
3. View details including:
   - Stock breakdown by location
   - Calculated quantity needed
   - Urgency level

## Troubleshooting

### Requisitions Not Being Created

1. **Check reorder point is set:**
   ```sql
   SELECT id, name, reorder_point, minimum_stock 
   FROM inventory_items 
   WHERE id = [ITEM_ID];
   ```

2. **Check current stock:**
   ```sql
   SELECT il.location_id, il.quantity, loc.name
   FROM inventory_levels il
   INNER JOIN inventory_locations loc ON loc.id = il.location_id
   WHERE il.item_id = [ITEM_ID];
   ```

3. **Check for existing requisitions:**
   ```sql
   SELECT r.* FROM requisitions r
   INNER JOIN requisition_items ri ON ri.requisition_id = r.id
   WHERE ri.inventory_item_id = [ITEM_ID]
   AND r.status IN ('pending', 'approved');
   ```

4. **Check error logs:**
   - PHP error log for "Auto requisition check failed" messages

### Stock Display Issues

1. **Check data structure:**
   ```sql
   SELECT ii.id, ii.name, 
          SUM(il.quantity) as total_stock,
          COUNT(DISTINCT il.location_id) as location_count
   FROM inventory_items ii
   LEFT JOIN inventory_levels il ON il.item_id = ii.id
   WHERE ii.id = [ITEM_ID]
   GROUP BY ii.id;
   ```

2. **Verify locations exist:**
   ```sql
   SELECT * FROM inventory_locations;
   ```

## Future Enhancements

1. **Batch Processing:**
   - Run periodic check for all items (not just on deduction)
   - Useful for items that don't get sold frequently

2. **Notification Integration:**
   - Send email/SMS when auto-requisition is created
   - Notify managers of urgent requisitions

3. **Smart Quantity Calculation:**
   - Consider sales velocity when calculating quantity needed
   - Factor in lead time from suppliers

4. **Multi-Item Requisitions:**
   - Group multiple low-stock items into single requisition
   - Reduce number of requisitions to process

## Summary

The Auto-Requisition System:
- ✅ Automatically creates requisitions when stock drops below threshold
- ✅ Prevents duplicate requisitions
- ✅ Calculates total stock across all locations
- ✅ Sets appropriate urgency levels
- ✅ Includes detailed stock breakdown in requisition notes
- ✅ Displays stock per location in inventory view
- ✅ Handles errors gracefully without breaking stock operations

---

**Last Updated:** 2025-01-22
**Version:** 1.0

