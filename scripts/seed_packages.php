<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;
use App\Repositories\LicensePackageRepository;

$pdo = Database::connection();
$packageRepo = new LicensePackageRepository($pdo);

$packages = [
    [
        'name' => 'Starter',
        'description' => 'Perfect for small hotels and guesthouses just getting started',
        'price' => 99.00,
        'currency' => 'USD',
        'duration_months' => 12,
        'features' => [
            'Up to 20 rooms',
            'Basic booking management',
            'Guest management',
            'Email support',
            'Monthly reports',
        ],
        'is_active' => 1,
        'sort_order' => 1,
    ],
    [
        'name' => 'Professional',
        'description' => 'Ideal for growing hotels with multiple departments',
        'price' => 299.00,
        'currency' => 'USD',
        'duration_months' => 12,
        'features' => [
            'Unlimited rooms',
            'Full booking management',
            'POS system integration',
            'Inventory management',
            'Staff management',
            'Housekeeping module',
            'Priority email support',
            'Advanced analytics',
            'Mobile app access',
        ],
        'is_active' => 1,
        'sort_order' => 2,
    ],
    [
        'name' => 'Enterprise',
        'description' => 'Complete solution for large hotel chains and resorts',
        'price' => 799.00,
        'currency' => 'USD',
        'duration_months' => 12,
        'features' => [
            'Unlimited everything',
            'Multi-property management',
            'Advanced POS system',
            'Full inventory & procurement',
            'HR & payroll management',
            'Maintenance tracking',
            '24/7 phone support',
            'Custom integrations',
            'Dedicated account manager',
            'White-label options',
            'API access',
        ],
        'is_active' => 1,
        'sort_order' => 3,
    ],
    [
        'name' => 'Monthly Starter',
        'description' => 'Pay monthly for small operations',
        'price' => 15.00,
        'currency' => 'USD',
        'duration_months' => 1,
        'features' => [
            'Up to 10 rooms',
            'Basic booking management',
            'Email support',
        ],
        'is_active' => 1,
        'sort_order' => 4,
    ],
];

echo "Creating license packages...\n";

foreach ($packages as $package) {
    // Check if package already exists
    $existing = $pdo->prepare('SELECT id FROM license_packages WHERE name = :name');
    $existing->execute(['name' => $package['name']]);
    if ($existing->fetch()) {
        echo "Package '{$package['name']}' already exists, skipping...\n";
        continue;
    }
    
    $packageId = $packageRepo->create($package);
    echo "✓ Created package: {$package['name']} (ID: $packageId)\n";
}

echo "Done!\n";

