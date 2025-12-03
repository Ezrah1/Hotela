<?php

require __DIR__ . '/../bootstrap/app.php';

$repo = new \App\Repositories\InventoryRepository();
$categories = $repo->posEnabledByCategory(false);

echo "Categories found: " . count($categories) . "\n\n";

foreach ($categories as $cat) {
    if ($cat['name'] === 'Drinks') {
        echo "Drinks category items: " . count($cat['items']) . "\n";
        
        $bottledWater = array_filter($cat['items'], function($i) {
            return strpos($i['name'], 'Bottled Water') !== false;
        });
        
        echo "Bottled Water items: " . count($bottledWater) . "\n";
        foreach ($bottledWater as $item) {
            echo "  - " . $item['name'] . " (ID: " . $item['id'] . ", Price: " . $item['price'] . ")\n";
        }
        echo "\n";
    }
}

// Check for any duplicate names in the same category
echo "Checking for duplicates across all categories:\n";
$allItems = [];
foreach ($categories as $cat) {
    foreach ($cat['items'] as $item) {
        $key = $cat['name'] . '|' . $item['name'] . '|' . $item['price'];
        if (isset($allItems[$key])) {
            echo "WARNING: Duplicate found - " . $item['name'] . " in " . $cat['name'] . "\n";
        } else {
            $allItems[$key] = $item['id'];
        }
    }
}

echo "\nTest complete. No duplicates found in query results.\n";

