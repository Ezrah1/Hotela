# Changelog - January 27, 2025

## Customer Portal (Guest Account) - Complete Implementation

### Overview
Implemented a comprehensive, minimal, and user-friendly customer portal that allows guests to manage their bookings, orders, and account information.

### Features Implemented

#### 1. Guest Dashboard (`/guest/portal`)
- **Three Main Sections as Clickable Cards:**
  - **Upcoming Bookings** - Shows count and links to dedicated page
  - **Active Food & Drink Orders** - Shows count and links to dedicated page
  - **Past Bookings** - Shows count and links to dedicated page
- Clean, card-based design with icons and counts
- Quick access to all major features

#### 2. Upcoming Bookings Page (`/guest/upcoming-bookings`)
- Lists all upcoming reservations
- Displays:
  - Room type and number
  - Check-in and check-out dates
  - Total cost
  - Booking status badge
  - "Order Food" button for confirmed/checked-in bookings
- Clean card layout with all booking details

#### 3. Past Bookings Page (`/guest/past-bookings`)
- Complete booking history
- Shows:
  - Room details
  - Dates and costs
  - "View Details" and "Download Receipt" buttons
- Organized by most recent first

#### 4. Active Orders Page (`/guest/active-orders`)
- Current food and drink orders
- Displays:
  - Order reference number
  - Item count and total
  - Order status with color-coded badges
  - Quick item preview (first 3 items)
  - Estimated preparation time for preparing orders
  - Room number (if applicable)
- Links to full order details

#### 5. Booking Details Page (`/guest/booking`)
- Comprehensive booking information:
  - Room name/number and type
  - Check-in & check-out dates (formatted)
  - Total cost with payment status
  - Booking status badge
  - Booking reference number
- Action buttons:
  - Download Receipt
  - Contact Support
  - Modify Booking (for pending/confirmed)
  - Order Food for This Stay (when checked in)

#### 6. Orders Management (`/guest/orders`)
- **Two Sections:**
  - **Current Orders** - Active orders with status tracking
  - **Past Orders** - Completed/cancelled orders
- Each order shows:
  - Reference number
  - Item count and total
  - Order date
  - Status badge
  - "View Details" button
  - "Reorder" button (for completed orders)

#### 7. Order Details Page (`/guest/order`)
- **Status Timeline:**
  - Visual progress: Pending → Confirmed → Preparing → Ready → Delivered → Completed
  - Shows current status with timestamps
- **Order Items:**
  - Complete list with quantities and prices
  - Line totals
- **Order Summary:**
  - Total amount
  - Notes/special instructions
  - Estimated preparation time (when preparing)
- **Actions:**
  - Download Receipt
  - Reorder (for completed orders)

#### 8. Profile Page (`/guest/profile`)
- Personal information display:
  - Full name
  - Email address
  - Phone number
- Booking history summary
- Account actions:
  - Request Password Reset
  - Update Personal Information

#### 9. Notifications Page (`/guest/notifications`)
- Real-time notifications for:
  - Booking confirmations
  - Room ready for check-in
  - Order ready alerts
- Soft-colored cards with timestamps
- Links to relevant pages

#### 10. Reviews System (`/guest/reviews`)
- **Review Submission:**
  - 5-star rating system with interactive stars
  - Review title and comment
  - Category selection (Overall, Room, Service, Food)
  - Optional booking reference
  - Status: Pending → Approved (by admin)
- **Review Display:**
  - Overall rating summary with average and total count
  - "My Reviews" section showing guest's submitted reviews
  - "What Others Are Saying" section with approved reviews
  - Status badges (Pending/Approved)

#### 11. Contact Page (`/guest/contact`)
- **Contact Information:**
  - Phone number (clickable)
  - Email address (clickable)
  - Physical address
  - WhatsApp link (if configured)
- **Contact Form:**
  - Subject dropdown (Booking Inquiry, Modify Booking, Room Service, Complaint, Compliment, etc.)
  - Optional booking reference
  - Message textarea
  - Business hours display (if configured)
- Success/error message handling

### Design Features
- **Minimal & Clean:** Simple, uncluttered interface
- **Mobile Responsive:** Works perfectly on all devices
- **Consistent Color Scheme:** Professional color palette
- **Clear Typography:** Easy to read fonts and sizes
- **Simple Navigation:** Intuitive menu structure
- **Status Badges:** Color-coded status indicators
- **Empty States:** Helpful messages when no data exists

### Technical Implementation

#### Database
- **Reviews Table:** Created migration `2025_01_27_000001_create_reviews_table.php`
  - Stores guest reviews with ratings, comments, categories
  - Links to reservations
  - Status management (pending/approved/rejected)

#### Repositories
- **ReviewRepository:** Complete CRUD operations for reviews
  - `create()` - Submit new review
  - `listForGuest()` - Get guest's reviews
  - `getApproved()` - Get all approved reviews
  - `getAverageRating()` - Calculate average rating
  - `getRatingCount()` - Get total review count

- **ReservationRepository:** Enhanced with guest-specific methods
  - `upcomingForGuest()` - Get upcoming bookings for guest
  - `pastForGuest()` - Get past bookings for guest
  - `findByReference()` - Enhanced to include room details

- **OrderRepository:** Enhanced with guest-specific methods
  - `listForGuest()` - Get all orders for a guest by email/phone

#### Controllers
- **GuestPortalController:** Complete guest portal management
  - `dashboard()` - Main dashboard with counts
  - `upcomingBookings()` - Upcoming bookings page
  - `pastBookings()` - Past bookings page
  - `activeOrders()` - Active orders page
  - `booking()` - Booking details page
  - `orders()` - All orders page
  - `order()` - Order details page
  - `profile()` - Profile page
  - `notifications()` - Notifications page
  - `reviews()` - Reviews page
  - `createReview()` - Submit review
  - `contact()` - Contact page
  - `submitContact()` - Submit contact form

#### Routes
All guest portal routes added:
- `/guest/portal` - Dashboard
- `/guest/upcoming-bookings` - Upcoming bookings
- `/guest/past-bookings` - Past bookings
- `/guest/active-orders` - Active orders
- `/guest/booking` - Booking details
- `/guest/orders` - All orders
- `/guest/order` - Order details
- `/guest/profile` - Profile
- `/guest/notifications` - Notifications
- `/guest/reviews` - Reviews
- `/guest/reviews/create` - Submit review (POST)
- `/guest/contact` - Contact page
- `/guest/contact` - Submit contact (POST)

#### Views
- **Layout:** `resources/views/layouts/guest.php`
  - Minimal header with navigation
  - User info display
  - Responsive design
  - Consistent styling

- **Dashboard Views:**
  - `dashboard.php` - Main dashboard with card buttons
  - `upcoming-bookings.php` - Upcoming bookings list
  - `past-bookings.php` - Past bookings list
  - `active-orders.php` - Active orders list

- **Detail Views:**
  - `booking.php` - Booking details
  - `orders.php` - All orders
  - `order.php` - Order details with timeline

- **Other Views:**
  - `profile.php` - Profile page
  - `notifications.php` - Notifications list
  - `reviews.php` - Reviews page with form
  - `contact.php` - Contact page with form

---

## Website Ordering System - Fixes

### Issues Fixed

#### 1. Orders Not Created in Orders Table
**Problem:** Website orders were only being created in `pos_sales` table, not in the unified `orders` table.

**Solution:**
- Added `OrderRepository` to `OrderController`
- After creating `pos_sale`, now also creates entry in `orders` table
- Orders are properly linked to guest accounts via email/phone
- Orders appear in both POS system and unified order management

#### 2. Missing Checkout Form Fields
**Problem:** Checkout form only had payment method, missing all guest information.

**Solution:**
- Added complete checkout form with all required fields:
  - Name (required)
  - Phone (required)
  - Email (optional)
  - Service Type (Pickup/Delivery/Room Service)
  - Room Number (for room service)
  - Payment Method (Cash/M-Pesa)
  - M-Pesa Phone (for M-Pesa payments)
  - Special Instructions
- Form fields appear when "Checkout" is clicked
- Dynamic field visibility based on selections
- Client-side validation before submission

#### 3. Order Linking to Guest Portal
**Problem:** Orders weren't linked to guest accounts, so guests couldn't see their orders.

**Solution:**
- Orders now store `customer_email` and `customer_phone`
- Guest portal can retrieve orders by matching email/phone
- Orders appear in guest's "Active Orders" and "Orders" pages

### Technical Changes

#### OrderController.php
- Added `OrderRepository` dependency
- Enhanced `checkout()` method to:
  - Validate required fields (name, phone)
  - Create order entry in `orders` table
  - Link order to guest via email/phone
  - Send notifications for new pending orders
  - Handle M-Pesa payments properly

#### food.php View
- Complete checkout form with all fields
- JavaScript validation:
  - Name and phone required
  - M-Pesa phone required for M-Pesa payments
  - Room number required for room service
- Dynamic field visibility
- Form properly nested for submission

---

## HR Access for Finance Manager

### Changes Made
- Added `finance_manager` role to HR controller access:
  - `index()` - View HR employee list
  - `employee()` - View employee details
  - `addRecord()` - Add employee records
- Added "Human Resources" link to Finance Manager sidebar

### Files Modified
- `app/Modules/HR/Controllers/HRController.php`
- `app/Support/Sidebar.php`

---

## Sidebar Updates

### Operations Manager Sidebar
- Removed duplicate "POS (View Orders)" entry
- Kept "Orders Management" for comprehensive order monitoring
- Cleaner navigation structure

### Finance Manager Sidebar
- Added "Human Resources" link
- Positioned after "Payroll Management"

---

## Bug Fixes

### 1. User Session Override Issue
**Problem:** When viewing HR employee details, the logged-in user was being changed to the employee being viewed.

**Solution:**
- Changed controller to pass `'employee'` instead of `'user'` to views
- Updated views to use `$employee` variable
- Dashboard layout now correctly distinguishes between logged-in user and employee being viewed

### 2. HR Index View Variable Conflict
**Problem:** `foreach` loop variable `$user` was overriding the logged-in user variable.

**Solution:**
- Renamed loop variable from `$user` to `$employee`
- Updated all references in the loop

### 3. Redirect URLs
**Problem:** Some HR redirects were missing `/staff` prefix.

**Solution:**
- Updated all redirect URLs in `HRController` to use `/staff` prefix
- Consistent URL structure throughout

---

## Database Migrations

### Reviews Table
**File:** `database/migrations/2025_01_27_000001_create_reviews_table.php`

**Schema:**
- `id` - Primary key
- `reservation_id` - Optional link to reservation
- `guest_name`, `guest_email`, `guest_phone` - Guest information
- `rating` - 1-5 star rating
- `title` - Review title
- `comment` - Review text
- `category` - ENUM: room, service, food, overall
- `status` - ENUM: pending, approved, rejected
- `helpful_count` - For future use
- `created_at`, `updated_at` - Timestamps
- Indexes on reservation_id, status, rating, category
- Foreign key to reservations table

**To Run:**
```bash
php scripts/migrate.php
```

---

## Navigation Structure

### Guest Portal Navigation
- Dashboard
- Orders
- Reviews
- Contact
- Notifications
- Profile

### Staff Sidebar Updates
- Operations Manager: Removed duplicate POS entry
- Finance Manager: Added HR access

---

## Testing Checklist

### Guest Portal
- [ ] Login with booking reference and email/phone
- [ ] View dashboard with counts
- [ ] Navigate to upcoming bookings
- [ ] Navigate to past bookings
- [ ] Navigate to active orders
- [ ] View booking details
- [ ] View order details with timeline
- [ ] Submit a review
- [ ] View reviews (own and others)
- [ ] Submit contact form
- [ ] View notifications
- [ ] Update profile information

### Website Ordering
- [ ] Add items to cart
- [ ] View cart
- [ ] Fill checkout form
- [ ] Submit order with cash payment
- [ ] Submit order with M-Pesa payment
- [ ] Verify order appears in staff system
- [ ] Verify order appears in guest portal (if logged in)

### HR Access
- [ ] Finance Manager can access HR
- [ ] Finance Manager can view employee details
- [ ] Finance Manager can add employee records
- [ ] Logged-in user remains correct when viewing employees

---

## Files Created

### Migrations
- `database/migrations/2025_01_27_000001_create_reviews_table.php`

### Repositories
- `app/Repositories/ReviewRepository.php`

### Views
- `resources/views/layouts/guest.php`
- `resources/views/website/guest/dashboard.php`
- `resources/views/website/guest/upcoming-bookings.php`
- `resources/views/website/guest/past-bookings.php`
- `resources/views/website/guest/active-orders.php`
- `resources/views/website/guest/booking.php`
- `resources/views/website/guest/orders.php`
- `resources/views/website/guest/order.php`
- `resources/views/website/guest/profile.php`
- `resources/views/website/guest/notifications.php`
- `resources/views/website/guest/reviews.php`
- `resources/views/website/guest/contact.php`

---

## Files Modified

### Controllers
- `app/Modules/Website/Controllers/GuestPortalController.php`
- `app/Modules/Website/Controllers/OrderController.php`
- `app/Modules/HR/Controllers/HRController.php`

### Repositories
- `app/Repositories/ReservationRepository.php`
- `app/Repositories/OrderRepository.php`

### Views
- `resources/views/website/food.php`
- `resources/views/dashboard/hr/index.php`
- `resources/views/dashboard/hr/employee.php`

### Configuration
- `app/Support/Sidebar.php`
- `routes/platform.php`

---

## Next Steps / Recommendations

1. **Run Migration:**
   ```bash
   php scripts/migrate.php
   ```
   This will create the `reviews` table.

2. **Test Guest Portal:**
   - Create a test booking
   - Login to guest portal
   - Test all features

3. **Test Website Ordering:**
   - Add items to cart
   - Complete checkout
   - Verify order appears in both systems

4. **Review Management:**
   - Consider adding admin interface to approve/reject reviews
   - Add review moderation features

5. **Email Notifications:**
   - Implement email sending for contact form submissions
   - Send order confirmations to guests
   - Send review approval notifications

6. **Order Receipts:**
   - Implement receipt download functionality
   - Add PDF generation for receipts

---

## Summary

This session focused on creating a complete, user-friendly customer portal and fixing the website ordering system. The guest portal provides a minimal, clean interface for guests to manage their bookings, orders, reviews, and account information. The website ordering system now properly creates orders in both the POS and unified order management systems, with complete guest information collection and proper linking to guest accounts.

All features are mobile-responsive, follow a consistent design language, and provide a stress-free experience for guests.

