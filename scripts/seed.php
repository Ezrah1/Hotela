<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

$pdo = Database::connection();

$roles = [
    ['key' => 'director', 'name' => 'Director', 'description' => 'View all reports & strategic settings'],
    ['key' => 'admin', 'name' => 'Administrator', 'description' => 'Full system control'],
    ['key' => 'tech', 'name' => 'Technical Admin', 'description' => 'Systems & infrastructure'],
    ['key' => 'finance_manager', 'name' => 'Finance Manager', 'description' => 'Finance & supplier control'],
    ['key' => 'operation_manager', 'name' => 'Operations Manager', 'description' => 'Hotel operations'],
    ['key' => 'cashier', 'name' => 'Cashier', 'description' => 'POS & folios'],
    ['key' => 'service_agent', 'name' => 'Service Agent', 'description' => 'Front desk & reservations'],
    ['key' => 'kitchen', 'name' => 'Kitchen', 'description' => 'Kitchen operations'],
    ['key' => 'housekeeping', 'name' => 'Housekeeping', 'description' => 'Room cleaning'],
    ['key' => 'ground', 'name' => 'Ground & Maintenance', 'description' => 'Maintenance tasks'],
    ['key' => 'security', 'name' => 'Security', 'description' => 'Attendance & incidents'],
];

$roleStmt = $pdo->prepare('INSERT INTO roles (`key`, name, description) VALUES (:key, :name, :description)
    ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)');

foreach ($roles as $role) {
    $roleStmt->execute($role);
}

$tenantDomain = env('TENANT_DOMAIN', 'hotela.local');
$tenantStmt = $pdo->prepare('INSERT INTO tenants (name, domain, status)
    VALUES (:name, :domain, :status)
    ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status)');
$tenantStmt->execute([
    'name' => 'Demo Hotel',
    'domain' => $tenantDomain,
    'status' => 'active',
]);
$tenantId = (int)$pdo->lastInsertId();
if ($tenantId === 0) {
    $tenantId = (int)$pdo->query("SELECT id FROM tenants WHERE domain = " . $pdo->quote($tenantDomain) . " LIMIT 1")->fetchColumn();
}

$users = [
    [
        'name' => 'System Admin',
        'email' => 'admin@hotela.test',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'role_key' => 'admin',
    ],
    [
        'name' => 'Finance Lead',
        'email' => 'finance@hotela.test',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'role_key' => 'finance_manager',
    ],
    [
        'name' => 'Operations Lead',
        'email' => 'ops@hotela.test',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'role_key' => 'operation_manager',
    ],
];

$userStmt = $pdo->prepare('INSERT INTO users (tenant_id, name, email, password, role_key)
    VALUES (:tenant_id, :name, :email, :password, :role_key)
    ON DUPLICATE KEY UPDATE name = VALUES(name), role_key = VALUES(role_key)');

foreach ($users as $user) {
    $userStmt->execute($user + ['tenant_id' => $tenantId]);
}

$settingGroups = [
    'branding' => [
        'name' => 'Joyce Resorts',
        'logo' => 'assets/img/Joyce logo.png',
        'admin_logo' => 'assets/img/Joyce logo.png',
        'contact_email' => 'info@joyceresorts.com',
        'contact_phone' => '0115879655',
        'tagline' => 'Machakos - Where comfort meets tranquility.',
    ],
    'pos' => [
        'currency' => 'KES',
        'tax_rate' => 0.16,
        'currency_symbol' => 'KES',
    ],
    'website' => [
        'booking_enabled' => true,
        'order_enabled' => true,
        'food_orders_enabled' => false,
        'primary_color' => '#14532d',
        'secondary_color' => '#0f172a',
        'accent_color' => '#d97706',
        'meta_title' => 'Joyce Resorts, Machakos - Where comfort meets tranquility.',
        'meta_description' => 'Discover a peaceful escape where comfort meets nature. Joyce Resorts offers cozy guest rooms, delicious dining, elegant event spaces, and lush gardens perfect for weddings, meetings, and retreats - all in the heart of Machakos.',
        'meta_keywords' => 'Joyce Resorts, Machakos hotel, Machakos wedding venue, Joyce Resorts Kenya',
        'hero_heading' => 'Joyce Resorts - Machakos, Kenya',
        'hero_tagline' => 'Where comfort meets tranquility.',
        'hero_cta_text' => 'Book Your Stay',
        'hero_cta_link' => '/booking',
        'hero_background_image' => 'assets/img/Joyce banner.png',
        'highlight_one_title' => 'Comfortable Stays',
        'highlight_one_text' => 'Garden-view rooms fitted with plush bedding and curated amenities.',
        'highlight_two_title' => 'Signature Dining',
        'highlight_two_text' => 'Restaurant & outdoor lounge serving seasonal Kenyan cuisine.',
        'highlight_three_title' => 'Events & Retreats',
        'highlight_three_text' => 'Elegant event gardens, conference spaces, and on-site coordination.',
        'pages' => [
            'rooms' => true,
            'food' => true,
            'about' => true,
            'contact' => true,
            'order' => true,
        ],
        'intro_tagline' => 'Joyce Resorts | Machakos',
        'intro_heading' => 'Where comfort meets tranquility.',
        'intro_copy' => 'Discover a peaceful escape where comfort meets nature. Joyce Resorts offers cozy guest rooms, delicious dining, elegant event spaces, and lush gardens perfect for weddings, meetings, and retreats - all in the heart of Machakos.',
        'rooms_intro' => 'Suites and guest rooms inspired by breezy coastlines, curated for restful nights in Machakos.',
        'food_intro' => 'Restaurant & outdoor lounge featuring fresh, comforting Kenyan cuisine.',
        'about_content' => 'Joyce Resorts blends Machakos natural calm with thoughtful hospitality. From guest rooms to event lawns, every stay is guided by warmth, detail, and digital convenience.',
        'contact_message' => 'Call, WhatsApp, or email us for stays, dining, and event planning support.',
        'promo_message' => 'Discover a peaceful escape where comfort meets nature in the heart of Machakos.',
        'contact_whatsapp' => '+254115879655',
        'contact_address' => 'Machakos-Wote Road, Machakos, Kenya',
        'contact_map_embed' => '',
        'room_display_mode' => 'both',
        'restaurant_tagline' => 'Restaurant & Outdoor Lounge',
        'restaurant_title' => 'Sun-drenched dining in Machakos.',
        'restaurant_cta_text' => 'Explore the Menu',
        'restaurant_image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1400&q=80',
        'amenities' => [
            ['title' => 'Comfortable Guest Rooms', 'description' => 'Spacious, airy rooms with plush bedding and natural textures.'],
            ['title' => 'Restaurant & Outdoor Lounge', 'description' => 'All-day dining plus sundowner-ready outdoor lounge seating.'],
            ['title' => 'Conference Facilities', 'description' => 'Flexible meeting rooms supported by modern AV and on-site planners.'],
            ['title' => 'Event Gardens', 'description' => 'Lush lawns tailored for weddings, retreats, and creative shoots.'],
            ['title' => 'Ample Parking', 'description' => 'Secure on-site parking for guests, day visitors, and coaches.'],
            ['title' => '24-Hour Security', 'description' => 'Round-the-clock security and concierge presence for peace of mind.'],
        ],
    ],
];

$settingStmt = $pdo->prepare('INSERT INTO settings (tenant_id, namespace, `key`, value)
    VALUES (:tenant_id, :namespace, :key, :value)
    ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP');

foreach ($settingGroups as $namespace => $groupValues) {
    foreach ($groupValues as $key => $value) {
    $settingStmt->execute([
        'tenant_id' => null,
            'namespace' => $namespace,
            'key' => $key,
            'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    }
}

$joyceRooms = [
    ['name' => 'Casselberry', 'rate' => 10000],
    ['name' => 'Boca Raton', 'rate' => 3500],
    ['name' => 'Atlantic Beach', 'rate' => 3500],
    ['name' => 'Heathrow', 'rate' => 5000],
    ['name' => 'TitusVille', 'rate' => 3500],
    ['name' => 'Venice', 'rate' => 5000],
    ['name' => 'Anna Maria', 'rate' => 3500],
    ['name' => 'Tavares', 'rate' => 3500],
    ['name' => 'Jupiter', 'rate' => 10000],
    ['name' => 'Kissimee', 'rate' => 3500],
    ['name' => 'Amelia Island', 'rate' => 5000],
    ['name' => 'Miami', 'rate' => 5000],
    ['name' => 'Longwood', 'rate' => 3500],
    ['name' => 'Melbourne', 'rate' => 10000],
    ['name' => 'Key West', 'rate' => 3500],
    ['name' => 'Largo', 'rate' => 3500],
];

$roomTypeStmt = $pdo->prepare('INSERT INTO room_types (tenant_id, name, description, max_guests, base_rate, amenities)
    VALUES (:tenant_id, :name, :description, :max_guests, :base_rate, :amenities)
    ON DUPLICATE KEY UPDATE description = VALUES(description), max_guests = VALUES(max_guests), base_rate = VALUES(base_rate), amenities = VALUES(amenities)');

$deleteReservationsStmt = $pdo->prepare('DELETE FROM reservations WHERE tenant_id = :tenant_id');
$deleteRoomsStmt = $pdo->prepare('DELETE FROM rooms WHERE tenant_id = :tenant_id');
$deleteRoomTypesStmt = $pdo->prepare('DELETE FROM room_types WHERE tenant_id = :tenant_id');

$deleteReservationsStmt->execute(['tenant_id' => $tenantId]);
$deleteRoomsStmt->execute(['tenant_id' => $tenantId]);
$deleteRoomTypesStmt->execute(['tenant_id' => $tenantId]);

$roomTypeLookupStmt = $pdo->prepare('SELECT id FROM room_types WHERE tenant_id = :tenant_id AND name = :name LIMIT 1');

$roomStmt = $pdo->prepare('INSERT INTO rooms (tenant_id, room_number, display_name, room_type_id, floor, status)
    VALUES (:tenant_id, :room_number, :display_name, :room_type_id, :floor, :status)
    ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), room_type_id = VALUES(room_type_id), floor = VALUES(floor), status = VALUES(status)');

$roomLookupStmt = $pdo->prepare('SELECT id FROM rooms WHERE tenant_id = :tenant_id AND room_number = :room_number LIMIT 1');

$roomTypeIds = [];
$roomIds = [];

foreach ($joyceRooms as $index => $roomMeta) {
    $isSuite = $roomMeta['rate'] >= 10000;
    $amenities = [
        'Plush bedding & premium linens',
        'Garden or courtyard views',
        'Fast Wi-Fi + workspace',
        'Complimentary breakfast',
    ];
    if ($isSuite) {
        $amenities[] = 'Evening turndown with local treats';
    }

    $roomTypeStmt->execute([
        'tenant_id' => $tenantId,
        'name' => $roomMeta['name'],
        'description' => $roomMeta['name'] . ' is a serene guest room staged for retreats, meetings, and restful nights in Machakos.',
        'max_guests' => $isSuite ? 3 : 2,
        'base_rate' => $roomMeta['rate'],
        'amenities' => json_encode($amenities, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    $roomTypeId = (int)$pdo->lastInsertId();
    if ($roomTypeId === 0) {
        $roomTypeLookupStmt->execute([
            'tenant_id' => $tenantId,
            'name' => $roomMeta['name'],
        ]);
        $roomTypeId = (int)$roomTypeLookupStmt->fetchColumn();
    }
    $roomTypeIds[$roomMeta['name']] = $roomTypeId;

    $roomNumber = sprintf('JR-%02d', $index + 1);
    $roomStmt->execute([
        'tenant_id' => $tenantId,
        'room_number' => $roomNumber,
        'display_name' => $roomMeta['name'],
        'room_type_id' => $roomTypeId,
        'floor' => $isSuite ? 'Garden Wing' : 'Courtyard Wing',
        'status' => 'available',
    ]);

    $roomId = (int)$pdo->lastInsertId();
    if ($roomId === 0) {
        $roomLookupStmt->execute([
            'tenant_id' => $tenantId,
            'room_number' => $roomNumber,
        ]);
        $roomId = (int)$roomLookupStmt->fetchColumn();
    }

    $roomIds[] = [
        'id' => $roomId,
        'room_type_id' => $roomTypeId,
    ];
}

$reservationStmt = $pdo->prepare('INSERT INTO reservations (
        tenant_id, reference, guest_name, guest_email, guest_phone, check_in, check_out,
        adults, children, room_type_id, room_id, source, status, total_amount,
        payment_status, check_in_status, room_status
    )
    VALUES (
        :tenant_id, :reference, :guest_name, :guest_email, :guest_phone, :check_in, :check_out,
        :adults, :children, :room_type_id, :room_id, :source, :status, :total_amount,
        :payment_status, :check_in_status, :room_status
    )
    ON DUPLICATE KEY UPDATE status = VALUES(status), room_id = VALUES(room_id), total_amount = VALUES(total_amount), check_in_status = VALUES(check_in_status), room_status = VALUES(room_status)');

$primaryRoom = $roomIds[0] ?? null;
$secondaryRoom = $roomIds[1] ?? null;

if ($primaryRoom) {
$reservationStmt->execute([
    'tenant_id' => $tenantId,
        'reference' => 'JR-1001',
    'guest_name' => 'Grace Wanjiru',
    'guest_email' => 'grace@example.com',
    'guest_phone' => '+254711111111',
    'check_in' => date('Y-m-d', strtotime('+1 day')),
    'check_out' => date('Y-m-d', strtotime('+4 days')),
    'adults' => 2,
    'children' => 0,
        'room_type_id' => $primaryRoom['room_type_id'],
        'room_id' => $primaryRoom['id'],
    'source' => 'website',
    'status' => 'confirmed',
    'total_amount' => 28500,
    'payment_status' => 'partial',
    'check_in_status' => 'checked_in',
    'room_status' => 'in_house',
]);
}

if ($secondaryRoom) {
$reservationStmt->execute([
    'tenant_id' => $tenantId,
        'reference' => 'JR-1002',
    'guest_name' => 'Mwangi & Co',
    'guest_email' => 'bookings@mwangi.co.ke',
    'guest_phone' => '+254722222222',
    'check_in' => date('Y-m-d', strtotime('+3 days')),
    'check_out' => date('Y-m-d', strtotime('+6 days')),
    'adults' => 3,
    'children' => 1,
        'room_type_id' => $secondaryRoom['room_type_id'],
    'room_id' => null,
    'source' => 'corporate',
    'status' => 'pending',
    'total_amount' => 43500,
    'payment_status' => 'unpaid',
    'check_in_status' => 'scheduled',
    'room_status' => 'pending',
]);
}

$notificationStmt = $pdo->prepare('INSERT INTO notifications (tenant_id, role_key, title, message, payload)
    VALUES (:tenant_id, :role_key, :title, :message, :payload)');

$notificationStmt->execute([
    'tenant_id' => $tenantId,
    'role_key' => 'housekeeping',
    'title' => 'Suite turnover needed',
    'message' => 'HTL-1001 checking out tomorrow. Prepare Room 201.',
    'payload' => json_encode(['reference' => 'HTL-1001', 'room' => '201']),
]);

$categoryStmt = $pdo->prepare('INSERT INTO pos_categories (tenant_id, name) VALUES (:tenant_id, :name)
    ON DUPLICATE KEY UPDATE name = VALUES(name)');
$categories = ['Breakfast', 'Lunch', 'Dinner', 'Snacks', 'Soft Drinks', 'Alcohol', 'Specials'];
foreach ($categories as $cat) {
    $categoryStmt->execute(['tenant_id' => $tenantId, 'name' => $cat]);
}

$itemsStmt = $pdo->prepare('INSERT INTO pos_items (tenant_id, category_id, name, price, sku, tracked)
    VALUES (:tenant_id, :category_id, :name, :price, :sku, :tracked)
    ON DUPLICATE KEY UPDATE price = VALUES(price), tracked = VALUES(tracked)');

// Map category names to their DB IDs
$catIdStmt = $pdo->prepare('SELECT id FROM pos_categories WHERE tenant_id = :tenant_id AND name = :name LIMIT 1');
$catToId = [];
foreach ($categories as $catName) {
    $catIdStmt->execute(['tenant_id' => $tenantId, 'name' => $catName]);
    $catToId[$catName] = (int)$catIdStmt->fetchColumn();
}

$items = [
    // Breakfast
    ['category' => 'Breakfast', 'name' => 'Full English Breakfast', 'price' => 1200, 'sku' => 'BF-ENG', 'tracked' => 0],
    ['category' => 'Breakfast', 'name' => 'Pancakes & Syrup', 'price' => 800, 'sku' => 'BF-PAN', 'tracked' => 0],
    // Lunch
    ['category' => 'Lunch', 'name' => 'Grilled Chicken & Chips', 'price' => 950, 'sku' => 'LN-GCC', 'tracked' => 0],
    ['category' => 'Lunch', 'name' => 'Veggie Wrap', 'price' => 700, 'sku' => 'LN-VW', 'tracked' => 0],
    // Dinner
    ['category' => 'Dinner', 'name' => 'Beef Steak (300g)', 'price' => 1600, 'sku' => 'DN-STK', 'tracked' => 0],
    ['category' => 'Dinner', 'name' => 'Tilapia Fillet', 'price' => 1350, 'sku' => 'DN-TLP', 'tracked' => 0],
    // Snacks
    ['category' => 'Snacks', 'name' => 'Chicken Wings (6pc)', 'price' => 750, 'sku' => 'SN-WNG', 'tracked' => 0],
    ['category' => 'Snacks', 'name' => 'Fries', 'price' => 350, 'sku' => 'SN-FRY', 'tracked' => 0],
    // Soft Drinks
    ['category' => 'Soft Drinks', 'name' => 'Bottled Water 500ml', 'price' => 150, 'sku' => 'SD-WTR', 'tracked' => 1],
    ['category' => 'Soft Drinks', 'name' => 'Fresh Juice', 'price' => 350, 'sku' => 'SD-JCE', 'tracked' => 0],
    // Alcohol
    ['category' => 'Alcohol', 'name' => 'Local Beer 500ml', 'price' => 300, 'sku' => 'AL-BEER', 'tracked' => 1],
    ['category' => 'Alcohol', 'name' => 'House Wine Glass', 'price' => 500, 'sku' => 'AL-WINE', 'tracked' => 0],
    // Specials
    ['category' => 'Specials', 'name' => 'Chef’s Platter', 'price' => 2200, 'sku' => 'SP-PLT', 'tracked' => 0],
    ['category' => 'Specials', 'name' => 'Weekend Brunch', 'price' => 1800, 'sku' => 'SP-BRN', 'tracked' => 0],
];

foreach ($items as $item) {
    $categoryId = $catToId[$item['category']] ?? null;
    if (!$categoryId) continue;
    $itemsStmt->execute([
        'tenant_id' => $tenantId,
        'category_id' => $categoryId,
        'name' => $item['name'],
        'price' => $item['price'],
        'sku' => $item['sku'],
        'tracked' => $item['tracked'],
    ]);
}

$tillStmt = $pdo->prepare('INSERT INTO pos_tills (tenant_id, name, location) VALUES (:tenant_id, :name, :location)
    ON DUPLICATE KEY UPDATE location = VALUES(location)');
$tillStmt->execute(['tenant_id' => $tenantId, 'name' => 'Front Desk Till', 'location' => 'Lobby']);

$locationStmt = $pdo->prepare('INSERT INTO inventory_locations (tenant_id, name, description) VALUES (:tenant_id, :name, :description)
    ON DUPLICATE KEY UPDATE description = VALUES(description)');
$locationStmt->execute(['tenant_id' => $tenantId, 'name' => 'Main Store', 'description' => 'Central storage']);
$locationStmt->execute(['tenant_id' => $tenantId, 'name' => 'Kitchen', 'description' => 'Kitchen stockroom']);


$inventoryStmt = $pdo->prepare('
    INSERT INTO inventory_items (tenant_id, sku, name, unit, category, reorder_point, avg_cost)
    VALUES (:tenant_id, :sku, :name, :unit, :category, :reorder_point, :avg_cost)
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        unit = VALUES(unit),
        category = VALUES(category),
        reorder_point = VALUES(reorder_point),
        avg_cost = VALUES(avg_cost)
');

$inventoryItems = [
    ['sku' => 'INV-CHK', 'name' => 'Chicken Portions', 'unit' => 'kg', 'category' => 'Kitchen', 'reorder_point' => 20, 'avg_cost' => 450],
    ['sku' => 'INV-OIL', 'name' => 'Cooking Oil', 'unit' => 'L', 'category' => 'Kitchen', 'reorder_point' => 10, 'avg_cost' => 300],
    ['sku' => 'INV-SPICE', 'name' => 'Spice Mix', 'unit' => 'kg', 'category' => 'Kitchen', 'reorder_point' => 5, 'avg_cost' => 200],
    ['sku' => 'INV-BEER', 'name' => 'Bottled Beer', 'unit' => 'bottle', 'category' => 'Bar', 'reorder_point' => 30, 'avg_cost' => 120],
    ['sku' => 'INV-WATER', 'name' => 'Water Bottle', 'unit' => 'bottle', 'category' => 'Bar', 'reorder_point' => 40, 'avg_cost' => 40],
];

foreach ($inventoryItems as $item) {
    $inventoryStmt->execute($item + ['tenant_id' => $tenantId]);
}

$levelStmt = $pdo->prepare('
    INSERT INTO inventory_levels (item_id, location_id, quantity)
    VALUES (
        (SELECT id FROM inventory_items WHERE tenant_id = :tenant_id AND sku = :sku LIMIT 1),
        (SELECT id FROM inventory_locations WHERE tenant_id = :tenant_id AND name = :location LIMIT 1),
        :quantity
    )
    ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
');

$stockSeed = [
    ['sku' => 'INV-CHK', 'location' => 'Kitchen', 'quantity' => 50],
    ['sku' => 'INV-OIL', 'location' => 'Kitchen', 'quantity' => 25],
    ['sku' => 'INV-SPICE', 'location' => 'Kitchen', 'quantity' => 15],
    ['sku' => 'INV-BEER', 'location' => 'Main Store', 'quantity' => 120],
    ['sku' => 'INV-WATER', 'location' => 'Main Store', 'quantity' => 200],
];

foreach ($stockSeed as $stock) {
    $levelStmt->execute($stock + ['tenant_id' => $tenantId]);
}

$componentStmt = $pdo->prepare('
    INSERT INTO pos_item_components (pos_item_id, inventory_item_id, quantity_per_sale)
    VALUES (
        (SELECT id FROM pos_items WHERE tenant_id = :tenant_id AND name = :pos_item LIMIT 1),
        (SELECT id FROM inventory_items WHERE tenant_id = :tenant_id AND sku = :inventory_sku LIMIT 1),
        :quantity
    )
');

$components = [
    ['pos_item' => 'Club Sandwich', 'inventory_sku' => 'INV-CHK', 'quantity' => 0.2],
    ['pos_item' => 'Club Sandwich', 'inventory_sku' => 'INV-OIL', 'quantity' => 0.05],
    ['pos_item' => 'Club Sandwich', 'inventory_sku' => 'INV-SPICE', 'quantity' => 0.02],
    ['pos_item' => 'Bottled Water', 'inventory_sku' => 'INV-WATER', 'quantity' => 1],
    ['pos_item' => 'Fresh Juice', 'inventory_sku' => 'INV-WATER', 'quantity' => 0.2],
];

foreach ($components as $component) {
    $componentStmt->execute($component + ['tenant_id' => $tenantId]);
}

$reqStmt = $pdo->prepare('INSERT INTO requisitions (reference, requested_by, status, notes)
    VALUES (:reference, :requested_by, :status, :notes)
    ON DUPLICATE KEY UPDATE status = VALUES(status)');
$reqStmt->execute([
    'reference' => 'REQ-001',
    'requested_by' => 1,
    'status' => 'approved',
    'notes' => 'Initial kitchen restock',
]);

$reqItemStmt = $pdo->prepare('INSERT INTO requisition_items (requisition_id, inventory_item_id, quantity)
    VALUES (
        (SELECT id FROM requisitions WHERE reference = :reference),
        (SELECT id FROM inventory_items WHERE sku = :sku),
        :quantity
    )');
$reqItemStmt->execute(['reference' => 'REQ-001', 'sku' => 'INV-CHK', 'quantity' => 30]);
$reqItemStmt->execute(['reference' => 'REQ-001', 'sku' => 'INV-OIL', 'quantity' => 10]);

$poStmt = $pdo->prepare('INSERT INTO purchase_orders (requisition_id, supplier_name, status, expected_date)
    VALUES (
        (SELECT id FROM requisitions WHERE reference = :reference),
        :supplier,
        :status,
        :expected_date
    )');
$poStmt->execute([
    'reference' => 'REQ-001',
    'supplier' => 'Fresh Farms Ltd',
    'status' => 'sent',
    'expected_date' => date('Y-m-d', strtotime('+5 days')),
]);

$poItemStmt = $pdo->prepare('INSERT INTO purchase_order_items (purchase_order_id, inventory_item_id, quantity, unit_cost)
    VALUES (
        (SELECT id FROM purchase_orders WHERE supplier_name = :supplier ORDER BY id DESC LIMIT 1),
        (SELECT id FROM inventory_items WHERE sku = :sku),
        :quantity,
        :cost
    )');
$poItemStmt->execute(['supplier' => 'Fresh Farms Ltd', 'sku' => 'INV-CHK', 'quantity' => 30, 'cost' => 450]);
$poItemStmt->execute(['supplier' => 'Fresh Farms Ltd', 'sku' => 'INV-OIL', 'quantity' => 10, 'cost' => 300]);

echo "Seeding complete.\n";






