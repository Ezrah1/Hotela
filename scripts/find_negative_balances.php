<?php
/**
 * Script to find folios with negative balances
 * Run: php scripts/find_negative_balances.php
 */

require_once __DIR__ . '/../app/Support/helpers.php';
require_once __DIR__ . '/../bootstrap/app.php';

$db = db();

echo "Searching for folios with negative balances...\n\n";

$sql = '
    SELECT 
        folios.id,
        folios.reservation_id,
        folios.guest_name,
        folios.guest_email,
        folios.guest_phone,
        folios.balance,
        folios.status,
        folios.created_at,
        folios.updated_at,
        reservations.reference AS reservation_reference,
        reservations.guest_name AS reservation_guest_name,
        rooms.display_name AS room_display,
        rooms.room_number
    FROM folios
    LEFT JOIN reservations ON reservations.id = folios.reservation_id
    LEFT JOIN rooms ON rooms.id = reservations.room_id
    WHERE folios.balance < 0
    ORDER BY folios.balance ASC, folios.updated_at DESC
';

$stmt = $db->query($sql);
$folios = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($folios)) {
    echo "✓ No folios with negative balances found.\n";
    exit(0);
}

echo "Found " . count($folios) . " folio(s) with negative balances:\n\n";

foreach ($folios as $folio) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Folio ID: " . $folio['id'] . "\n";
    echo "Balance: KES " . number_format((float)$folio['balance'], 2) . "\n";
    echo "Status: " . ($folio['status'] ?? 'N/A') . "\n";
    echo "Guest: " . ($folio['guest_name'] ?? $folio['reservation_guest_name'] ?? 'N/A') . "\n";
    echo "Email: " . ($folio['guest_email'] ?? 'N/A') . "\n";
    echo "Phone: " . ($folio['guest_phone'] ?? 'N/A') . "\n";
    echo "Reservation: " . ($folio['reservation_reference'] ?? 'N/A') . "\n";
    echo "Room: " . ($folio['room_display'] ?? $folio['room_number'] ?? 'N/A') . "\n";
    echo "Created: " . ($folio['created_at'] ?? 'N/A') . "\n";
    echo "Updated: " . ($folio['updated_at'] ?? 'N/A') . "\n";
    echo "\n";
    $baseUrl = rtrim(str_replace('\\', '/', dirname(dirname(__DIR__))), '/');
    echo "View folio: " . $baseUrl . "/staff/dashboard/folios/view?folio_id=" . $folio['id'] . "\n";
    if ($folio['reservation_id']) {
        echo "View booking: " . $baseUrl . "/staff/dashboard/bookings/folio?reservation_id=" . $folio['reservation_id'] . "\n";
    }
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\nTo fix negative balances, you can:\n";
echo "1. Add a payment entry to the folio to bring the balance to zero or positive\n";
echo "2. Add a charge entry if the balance should be positive\n";
echo "3. Review the folio entries to identify the cause of the negative balance\n";

