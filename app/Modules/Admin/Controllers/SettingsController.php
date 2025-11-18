<?php

namespace App\Modules\Admin\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Settings\SettingStore;
use App\Support\Auth;

class SettingsController extends Controller
{
    protected SettingStore $store;

    public function __construct()
    {
        $this->store = new SettingStore();
    }

    public function index(): void
    {
        Auth::requireRoles(['admin']);
        $this->view('admin/settings', [
            'settings' => $this->store->all(),
            'pageTitle' => 'Admin Settings | Hotela',
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['admin']);
        $group = $request->input('group');
        $payload = $request->all();
        unset($payload['group']);

        if (!$group) {
            http_response_code(400);
            echo 'Invalid group.';
            return;
        }

        $sanitized = $this->sanitizeValues($payload);
        
        // Handle nested arrays (e.g., payslip[enabled] should be saved to payslip namespace)
        $mainGroup = [];
        $nestedGroups = [];
        
        foreach ($sanitized as $key => $value) {
            if (is_array($value)) {
                // This is a nested group (e.g., payslip[enabled])
                $nestedGroups[$key] = $value;
            } else {
                // This belongs to the main group
                $mainGroup[$key] = $value;
            }
        }
        
        // Save main group
        if (!empty($mainGroup)) {
            $this->store->updateGroup($group, $mainGroup);
        }
        
        // Save nested groups to their own namespaces
        foreach ($nestedGroups as $nestedKey => $nestedValues) {
            $this->store->updateGroup($nestedKey, $nestedValues);
        }

        header('Location: ' . base_url('admin/settings?tab=' . urlencode($group)));
    }

    protected function sanitizeValues(array $values): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->sanitizeValues($value);
            }

            return is_string($value) ? trim($value) : $value;
        }, $values);
    }
}


