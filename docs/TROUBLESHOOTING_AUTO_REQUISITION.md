# Troubleshooting Auto-Requisition System

## Quick Diagnostic

Run the diagnostic script to check your system:

```bash
php scripts/diagnose_auto_requisition.php
```

This will check:
- ✅ Database tables and columns exist
- ✅ Items have reorder points set
- ✅ Current stock levels
- ✅ Existing auto-requisitions
- ✅ Test auto-requisition creation

## Common Issues and Solutions

### Issue 1: No Requisitions Being Created

**Possible Causes:**

1. **Migration Not Run**
   - The `type` column may not exist in `requisitions` table
   - The `auto_requisition_triggers` table may not exist
   
   **Solution:**
   ```bash
   php scripts/migrate.php
   ```

2. **No Reorder Points Set**
   - Items don't have reorder points configured
   
   **Solution:**
   - Go to: Inventory → Edit Item
   - Set "Reorder Point" field (e.g., 20.000)
   - Save the item

3. **Stock Not Below Threshold**
   - Current stock is still above reorder point
   
   **Check:**
   ```sql
   SELECT ii.name, ii.reorder_point, 
          COALESCE(SUM(il.quantity), 0) as total_stock
   FROM inventory_items ii
   LEFT JOIN inventory_levels il ON il.item_id = ii.id
   WHERE ii.reorder_point > 0
   GROUP BY ii.id
   HAVING total_stock > ii.reorder_point;
   ```

4. **Existing Pending Requisition**
   - There's already a pending requisition for the item
   
   **Check:**
   ```sql
   SELECT r.*, ii.name
   FROM requisitions r
   INNER JOIN requisition_items ri ON ri.requisition_id = r.id
   INNER JOIN inventory_items ii ON ii.id = ri.inventory_item_id
   WHERE r.type = 'auto' 
   AND r.status IN ('pending', 'approved')
   AND ii.id = [ITEM_ID];
   ```

5. **Error in Auto-Requisition Logic**
   - Check PHP error logs for errors
   
   **Check Error Logs:**
   - Look for: "Auto requisition check failed"
   - Look for: "Auto-requisition trigger log failed"
   - Check your PHP error log location

### Issue 2: Requisitions Created But Not Visible

**Check:**
```sql
SELECT * FROM requisitions WHERE type = 'auto' ORDER BY created_at DESC;
```

**If they exist but not showing in UI:**
- Check requisitions view filter
- Verify user has permissions to view requisitions
- Check if status filter is hiding them

### Issue 3: Wrong Quantity Calculated

**Formula Used:**
```
targetStock = max(reorder_point * 2, minimum_stock * 2, threshold * 2)
quantityNeeded = max(targetStock - totalStock, threshold)
```

**Example:**
- Reorder point: 20
- Current stock: 15
- Target: 40 (2x reorder point)
- Quantity needed: 25 (40 - 15)

### Issue 4: Multiple Requisitions for Same Item

**This should NOT happen** - duplicate prevention is built in.

**If it happens:**
1. Check if `getUnresolvedAutoRequisition()` is working
2. Verify requisition statuses are correct
3. Check for race conditions (multiple simultaneous deductions)

## Manual Testing Steps

### Step 1: Verify Setup

```sql
-- Check tables exist
SHOW TABLES LIKE 'auto_requisition_triggers';
SHOW COLUMNS FROM requisitions LIKE 'type';

-- Check items with reorder points
SELECT id, name, reorder_point, minimum_stock 
FROM inventory_items 
WHERE reorder_point > 0 OR minimum_stock > 0;
```

### Step 2: Set Up Test Item

1. Create or edit an inventory item
2. Set reorder point: 20.000
3. Set initial stock: 25.000 (above threshold)
4. Save

### Step 3: Trigger Deduction

**Option A: Via POS**
- Make a POS sale with the item
- Deduct enough to go below reorder point (e.g., deduct 10 units)

**Option B: Manual SQL** (for testing only)
```sql
-- Get item and location IDs first
SELECT id FROM inventory_items WHERE name = 'Test Item' LIMIT 1;
SELECT id FROM inventory_locations LIMIT 1;

-- Deduct stock (replace IDs)
UPDATE inventory_levels 
SET quantity = quantity - 10 
WHERE item_id = [ITEM_ID] AND location_id = [LOCATION_ID];
```

### Step 4: Verify Requisition Created

```sql
SELECT r.*, ri.quantity, ii.name
FROM requisitions r
INNER JOIN requisition_items ri ON ri.requisition_id = r.id
INNER JOIN inventory_items ii ON ii.id = ri.inventory_item_id
WHERE r.type = 'auto'
ORDER BY r.created_at DESC
LIMIT 1;
```

### Step 5: Check Error Logs

Look in your PHP error log for:
- "Auto requisition check failed"
- "Auto-requisition created: ID X"
- Any database errors

## Debug Mode

To enable detailed logging, the system now logs:
- When requisitions are created (with details)
- When checks fail (with reasons)
- When triggers are logged

Check your PHP error log after making a sale or deducting stock.

## Database Queries for Debugging

### Check All Auto-Requisitions
```sql
SELECT r.id, r.reference, r.status, r.urgency, r.created_at,
       ri.inventory_item_id, ri.quantity,
       ii.name as item_name, ii.reorder_point
FROM requisitions r
INNER JOIN requisition_items ri ON ri.requisition_id = r.id
INNER JOIN inventory_items ii ON ii.id = ri.inventory_item_id
WHERE r.type = 'auto'
ORDER BY r.created_at DESC;
```

### Check Trigger Logs
```sql
SELECT art.*, ii.name as item_name
FROM auto_requisition_triggers art
INNER JOIN inventory_items ii ON ii.id = art.inventory_item_id
ORDER BY art.triggered_at DESC
LIMIT 20;
```

### Check Items That Should Trigger
```sql
SELECT ii.id, ii.name, ii.reorder_point, ii.minimum_stock,
       COALESCE(SUM(il.quantity), 0) as total_stock,
       CASE 
           WHEN ii.minimum_stock > 0 THEN ii.minimum_stock
           ELSE ii.reorder_point
       END as threshold
FROM inventory_items ii
LEFT JOIN inventory_levels il ON il.item_id = ii.id
WHERE (ii.reorder_point > 0 OR ii.minimum_stock > 0)
GROUP BY ii.id
HAVING total_stock <= threshold AND threshold > 0;
```

## Still Not Working?

1. **Run Diagnostic Script:**
   ```bash
   php scripts/diagnose_auto_requisition.php
   ```

2. **Check PHP Error Log:**
   - Look for any errors related to auto-requisition
   - Check for database connection issues
   - Verify table permissions

3. **Verify Code Integration:**
   - Ensure `InventoryRepository::deduct()` is being called
   - Check that `AutoRequisitionService` is instantiated correctly
   - Verify no exceptions are being swallowed

4. **Test Manually:**
   ```php
   // In a test script
   $autoReq = new \App\Services\AutoRequisitionService();
   $result = $autoReq->checkAndCreateRequisition(1, 1, 15.0);
   var_dump($result);
   ```

5. **Check Database:**
   - Verify all migrations have run
   - Check table structures match expected schema
   - Verify foreign key constraints are correct

---

**Last Updated:** 2025-01-22

