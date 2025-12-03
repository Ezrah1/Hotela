# Inventory Auto-Requisition - Quick Reference

## How It Works

1. **Stock is deducted** (from POS sale, website order, etc.)
2. **System checks** total stock across all locations
3. **If stock ≤ reorder point**, auto-requisition is created
4. **Requisition includes**:
   - Stock breakdown by location
   - Calculated quantity needed (2x threshold)
   - Urgency level (urgent/high/medium/low)

## Key Settings

### Reorder Point
- **Location**: Inventory → Edit Item → "Reorder Point"
- **Purpose**: Minimum stock level before reordering
- **Example**: 20.000 units

### Minimum Stock (Optional)
- **Location**: Inventory → Edit Item → "Minimum Stock"
- **Purpose**: Alternative threshold (takes priority over reorder point)
- **Use Case**: When you want a different threshold than reorder point

## Viewing Stock

### Inventory List
- **URL**: `/staff/dashboard/inventory`
- **Shows**:
  - Total stock per item
  - Stock breakdown by location
  - Low stock indicators (⚠)

### Stock Display Format
```
Total: 30.00 Unit
Main Store: 15.00, Kitchen: 10.00, Bar: 5.00
```

## Viewing Auto-Requisitions

1. Navigate to: **Inventory → Requisitions**
2. Filter by type: **"auto"**
3. View details:
   - Reference (starts with `AUTO-REQ-`)
   - Status (pending/approved/rejected)
   - Urgency (urgent/high/medium/low)
   - Notes (includes stock breakdown)

## Urgency Levels

| Stock Level | Urgency | Description |
|------------|---------|-------------|
| ≤20% of threshold | `urgent` | Critical - stock very low |
| ≤50% of threshold | `high` | Stock low - needs attention |
| ≤80% of threshold | `medium` | Stock below threshold |
| >80% of threshold | `low` | Just below threshold |

## Common Scenarios

### Scenario 1: Item Drops Below Reorder Point
- **Action**: Make a sale that reduces stock below reorder point
- **Result**: Auto-requisition created automatically
- **Check**: Inventory → Requisitions → Filter by "auto"

### Scenario 2: Multiple Locations
- **Setup**: Item has stock at Main Store (10), Kitchen (8), Bar (5)
- **Total**: 23 units
- **If reorder point = 20**: No requisition (23 > 20)
- **If stock drops to 18**: Requisition created (18 < 20)
- **Notes show**: "Main Store: 10.00, Kitchen: 8.00, Bar: 5.00"

### Scenario 3: Duplicate Prevention
- **First deduction**: Creates requisition
- **Second deduction**: Uses existing requisition (no duplicate)
- **Only creates new** when previous is resolved/rejected

## Troubleshooting

### Requisition Not Created?
1. Check reorder point is set (not 0)
2. Check total stock is below threshold
3. Check for existing pending requisition
4. Check PHP error logs

### Stock Not Showing?
1. Verify item has stock at locations
2. Check `inventory_levels` table
3. Verify locations exist in `inventory_locations`

### Wrong Quantity Calculated?
- System calculates: `max(2x threshold - current stock, threshold)`
- Example: threshold=20, stock=15 → quantity needed = 25

## Database Queries

### Check Item Stock
```sql
SELECT il.location_id, il.quantity, loc.name
FROM inventory_levels il
INNER JOIN inventory_locations loc ON loc.id = il.location_id
WHERE il.item_id = [ITEM_ID];
```

### View Auto-Requisitions
```sql
SELECT r.*, ri.quantity, ii.name
FROM requisitions r
INNER JOIN requisition_items ri ON ri.requisition_id = r.id
INNER JOIN inventory_items ii ON ii.id = ri.inventory_item_id
WHERE r.type = 'auto'
ORDER BY r.created_at DESC;
```

### Check Trigger Logs
```sql
SELECT art.*, ii.name
FROM auto_requisition_triggers art
INNER JOIN inventory_items ii ON ii.id = art.inventory_item_id
ORDER BY art.created_at DESC
LIMIT 10;
```

## Integration Points

Auto-requisitions are triggered when stock is deducted from:
- ✅ POS Sales
- ✅ Website Orders
- ✅ M-Pesa Payment Confirmations
- ✅ Requisition Releases
- ✅ Manual Stock Adjustments (if implemented)

---

**For detailed testing documentation, see:** `docs/INVENTORY_AUTO_REQUISITION_TESTING.md`

