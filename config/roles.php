<?php

return [
	'super_admin' => [
		'label' => 'Super Admin',
		'dashboard_view' => 'sysadmin/dashboard',
		'permissions' => ['*'],
	],
    'director' => [
        'label' => 'Director',
        'dashboard_view' => 'dashboard/roles/director',
        'permissions' => [
            'reports.all',
            'settings.strategic',
            'notifications.read',
        ],
    ],
    'admin' => [
        'label' => 'Administrator',
        'dashboard_view' => 'dashboard/roles/admin',
        'permissions' => ['*'],
    ],
    'tech' => [
        'label' => 'Technical Administrator',
        'dashboard_view' => 'dashboard/roles/tech',
        'permissions' => [
            'systems.health',
            'backups.manage',
            'logs.view',
        ],
    ],
    'finance_manager' => [
        'label' => 'Finance Manager',
        'dashboard_view' => 'dashboard/roles/finance_manager',
        'permissions' => [
            'finance.read',
            'finance.write',
            'inventory.costs',
            'reports.finance',
        ],
    ],
    'operation_manager' => [
        'label' => 'Operations Manager',
        'dashboard_view' => 'dashboard/roles/operation_manager',
        'permissions' => [
            'operations.read',
            'housekeeping.manage',
            'maintenance.manage',
            'attendance.view',
        ],
    ],
	'receptionist' => [
		'label' => 'Receptionist',
		'dashboard_view' => 'dashboard/roles/service_agent',
		'permissions' => [
			'reservations.manage',
			'reservations.checkin',
			'reservations.checkout',
			'guest.invoices',
			'guest.messaging',
		],
	],
    'cashier' => [
        'label' => 'Cashier',
        'dashboard_view' => 'dashboard/roles/cashier',
        'permissions' => [
            'pos.sales',
            'reservations.checkin',
            'reservations.checkout',
        ],
    ],
    'service_agent' => [
        'label' => 'Front Desk / Service',
        'dashboard_view' => 'dashboard/roles/service_agent',
        'permissions' => [
            'reservations.manage',
            'rooms.assign',
            'guest.communication',
        ],
    ],
    'kitchen' => [
        'label' => 'Kitchen',
        'dashboard_view' => 'dashboard/roles/kitchen',
        'permissions' => [
            'kitchen.orders',
            'inventory.usage',
        ],
    ],
    'housekeeping' => [
        'label' => 'Housekeeping',
        'dashboard_view' => 'dashboard/roles/housekeeping',
        'permissions' => [
            'housekeeping.rooms',
            'housekeeping.tasks',
        ],
    ],
    'ground' => [
        'label' => 'Ground & Maintenance',
        'dashboard_view' => 'dashboard/roles/ground',
        'permissions' => [
            'maintenance.tasks',
            'equipment.logs',
        ],
    ],
    'security' => [
        'label' => 'Security',
        'dashboard_view' => 'dashboard/roles/security',
        'permissions' => [
            'attendance.clock',
            'security.incidents',
            'visitor.logs',
        ],
    ],
];

