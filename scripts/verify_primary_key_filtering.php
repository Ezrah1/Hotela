<?php

require __DIR__ . '/../bootstrap/app.php';

echo "Verifying Primary Key Filtering in Queries\n";
echo "==========================================\n\n";

// Test POS items query
$invRepo = new \App\Repositories\InventoryRepository();
$posCategories = $invRepo->posEnabledByCategory(false);

$posItemIds = [];
$posDuplicates = [];
foreach ($posCategories as $cat) {
    foreach ($cat['items'] as $item) {
        $id = (int)$item['id'];
        if (isset($posItemIds[$id])) {
            $posDuplicates[] = "POS Item ID $id ({$item['name']})";
        } else {
            $posItemIds[$id] = true;
        }
    }
}

echo "POS Items Query:\n";
echo "  Total items: " . array_sum(array_map(fn($c) => count($c['items']), $posCategories)) . "\n";
echo "  Unique item IDs: " . count($posItemIds) . "\n";
if (empty($posDuplicates)) {
    echo "  ✓ No duplicates - Primary key filtering working!\n";
} else {
    echo "  ✗ Duplicates found:\n";
    foreach ($posDuplicates as $dup) {
        echo "    - $dup\n";
    }
}

echo "\n";

// Test Website items query
$posRepo = new \App\Repositories\PosItemRepository();
$websiteCategories = $posRepo->categoriesWithItems();

$websiteItemIds = [];
$websiteDuplicates = [];
foreach ($websiteCategories as $cat) {
    foreach ($cat['items'] as $item) {
        $id = (int)$item['id'];
        if (isset($websiteItemIds[$id])) {
            $websiteDuplicates[] = "Website Item ID $id ({$item['name']})";
        } else {
            $websiteItemIds[$id] = true;
        }
    }
}

echo "Website Items Query:\n";
echo "  Total items: " . array_sum(array_map(fn($c) => count($c['items']), $websiteCategories)) . "\n";
echo "  Unique item IDs: " . count($websiteItemIds) . "\n";
if (empty($websiteDuplicates)) {
    echo "  ✓ No duplicates - Primary key filtering working!\n";
} else {
    echo "  ✗ Duplicates found:\n";
    foreach ($websiteDuplicates as $dup) {
        echo "    - $dup\n";
    }
}

echo "\n";
echo "Summary: Primary key (ID) filtering is " . (empty($posDuplicates) && empty($websiteDuplicates) ? "WORKING CORRECTLY" : "NOT WORKING") . "\n";

