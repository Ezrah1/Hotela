# Quick Fix: No Auto-Requisitions Being Created

## Most Common Issue: Migration Not Run

The auto-requisition system requires database migrations to be run. If you're not seeing requisitions, **run the migration first**:

```bash
php scripts/migrate.php
```

This will create:
- `type` column in `requisitions` table
- `urgency` column in `requisitions` table  
- `auto_requisition_triggers` table
- `minimum_stock` column in `inventory_items` table

## Quick Diagnostic

Run this to check your system:

```bash
php scripts/diagnose_auto_requisition.php
```

## Step-by-Step Fix

### 1. Run Migration
```bash
php scripts/migrate.php
```

### 2. Set Reorder Points
- Go to: Inventory → Edit Item
- Set "Reorder Point" (e.g., 20.000)
- Save

### 3. Verify Stock is Below Threshold
- Check current stock in inventory list
- Ensure stock is below reorder point

### 4. Make a Sale/Deduct Stock
- Make a POS sale with the item
- OR manually adjust stock to go below reorder point

### 5. Check for Requisition
- Go to: Inventory → Requisitions
- Filter by type: "auto"
- Should see new requisition

## If Still Not Working

1. **Check PHP Error Log:**
   - Look for: "Auto requisition check failed"
   - Look for: "type/urgency columns missing"

2. **Verify Database:**
   ```sql
   SHOW COLUMNS FROM requisitions LIKE 'type';
   SHOW TABLES LIKE 'auto_requisition_triggers';
   ```

3. **Check Items Have Reorder Points:**
   ```sql
   SELECT id, name, reorder_point 
   FROM inventory_items 
   WHERE reorder_point > 0;
   ```

4. **Test Manually:**
   ```sql
   -- Find an item with reorder point
   SELECT id FROM inventory_items WHERE reorder_point > 0 LIMIT 1;
   
   -- Check its stock
   SELECT SUM(quantity) as total 
   FROM inventory_levels 
   WHERE item_id = [ITEM_ID];
   ```

## Need More Help?

See: `docs/TROUBLESHOOTING_AUTO_REQUISITION.md` for detailed troubleshooting.

