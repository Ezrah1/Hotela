<?php

require __DIR__ . '/../bootstrap/app.php';

$repo = new \App\Repositories\PosItemRepository();
$categories = $repo->categoriesWithItems();

echo "Categories found: " . count($categories) . "\n\n";

$totalItems = 0;
$itemIds = [];
$duplicates = [];

foreach ($categories as $cat) {
    foreach ($cat['items'] as $item) {
        $totalItems++;
        if (isset($itemIds[$item['id']])) {
            $duplicates[] = "Item ID {$item['id']} ({$item['name']}) in category {$cat['name']}";
        } else {
            $itemIds[$item['id']] = true;
        }
    }
}

echo "Total items: $totalItems\n";
echo "Unique items: " . count($itemIds) . "\n";

if (!empty($duplicates)) {
    echo "\nDUPLICATES FOUND:\n";
    foreach ($duplicates as $dup) {
        echo "  - $dup\n";
    }
} else {
    echo "\n✓ No duplicates found - deduplication working correctly!\n";
}

