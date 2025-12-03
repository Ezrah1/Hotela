# Inventory & POS System Overview

## Inventory System

**Purpose:** Track and manage physical stock of items (ingredients, supplies, products)

**Key Features:**
- **Stock Management**: Track quantities per location (Main Store, Kitchen, Bar, etc.)
- **Multi-Location Support**: Stock levels tracked separately for each location
- **Categories**: Organize items by category (excludes departments like "Bar", "Kitchen")
- **Reorder Points**: Set minimum stock levels to trigger automatic requisitions
- **Stock Movements**: Log all stock changes (purchases, sales, adjustments, transfers)
- **Valuation**: Calculate total inventory value based on average cost
- **Auto-Requisition**: Automatically creates purchase requisitions when stock drops below reorder point

**Main Components:**
- `inventory_items` - Item master data (name, SKU, unit, category, reorder point)
- `inventory_levels` - Stock quantities per item per location
- `inventory_locations` - Storage locations (Main Store, Kitchen, Bar, etc.)
- `inventory_movements` - Audit trail of all stock changes

**Workflow:**
1. Items added to inventory with SKU, unit, category
2. Stock received at locations (purchases, transfers)
3. Stock deducted when sold (POS sales, website orders)
4. System tracks stock levels and triggers alerts/requisitions when low

---

## POS System

**Purpose:** Point of Sale for processing sales (restaurant, bar, retail)

**Key Features:**
- **Item Catalog**: Food, drinks, and products organized by categories
- **Quick Sales**: Fast checkout with multiple payment methods (cash, M-Pesa, card, room charge)
- **Customer Types**: Walk-in, reservation-linked, room charge
- **Till Management**: Multiple tills for different locations/stations
- **Sales Tracking**: Complete sales history and reporting
- **Inventory Integration**: Automatically deducts inventory when items are sold

**Main Components:**
- `pos_items` - Items for sale (name, price, category)
- `pos_categories` - Item categories (Food, Drinks, etc.)
- `pos_sales` - Sales transactions
- `pos_tills` - Cash register/till management

**Workflow:**
1. Staff selects items from catalog
2. Customer chooses payment method
3. Sale processed and recorded
4. Inventory automatically deducted (if item is tracked)
5. Receipt generated

---

## How They Work Together

**Integration:**
- POS items can be mapped to inventory items via `pos_item_components`
- When a POS sale is made, the system:
  1. Records the sale in `pos_sales`
  2. Looks up inventory components for the POS item
  3. Deducts stock from inventory automatically
  4. Triggers auto-requisition if stock drops below reorder point

**Example:**
- **POS Item**: "House Wine Glass" (sold for KES 500)
- **Inventory Component**: "Wine Glass" (1 unit per sale)
- **When sold**: 
  - POS sale recorded
  - 1 "Wine Glass" deducted from inventory
  - If stock ≤ reorder point → auto-requisition created

**Benefits:**
- ✅ Real-time stock tracking
- ✅ Automatic stock deduction on sales
- ✅ Low stock alerts and auto-requisitions
- ✅ Accurate inventory valuation
- ✅ Complete audit trail

---

## Key Differences

| Aspect | Inventory | POS |
|--------|-----------|-----|
| **Purpose** | Track physical stock | Process sales |
| **Focus** | Quantities, locations, costs | Prices, sales, payments |
| **Items** | Raw materials, supplies | Finished products for sale |
| **Tracking** | Stock levels, movements | Sales transactions, revenue |
| **Users** | Managers, warehouse staff | Cashiers, waitstaff |

---

## Common Workflows

### Adding New Product
1. **Inventory**: Create inventory item (e.g., "Wine Glass", SKU: INV-WINE-001)
2. **POS**: Create POS item (e.g., "House Wine Glass", Price: KES 500)
3. **Mapping**: Link POS item to inventory item (1 wine glass per sale)
4. **Stock**: Receive initial stock at location
5. **Ready**: Item available for sale in POS

### Daily Operations
1. **Sales**: Staff process sales through POS
2. **Auto-Deduction**: Inventory automatically decreases
3. **Alerts**: System notifies when stock is low
4. **Requisition**: Auto-requisition created if below threshold
5. **Restock**: Items received, stock updated

### Reporting
- **Inventory**: Stock levels, valuation, movements, low stock items
- **POS**: Sales reports, revenue, popular items, customer types

---

**Last Updated:** 2025-01-22

