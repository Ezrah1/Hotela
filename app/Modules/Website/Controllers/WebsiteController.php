<?php

namespace App\Modules\Website\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\Settings\SettingStore;
use App\Support\Auth;

class WebsiteController extends Controller
{
    protected SettingStore $settings;

    public function __construct()
    {
        $this->settings = new SettingStore();
    }

    public function index(Request $request): void
    {
        Auth::requireRoles(['admin']);

        $websiteSettings = $this->settings->group('website');
        $brandingSettings = $this->settings->group('branding');

        $this->view('dashboard/website/index', [
            'website' => $websiteSettings,
            'branding' => $brandingSettings,
            'pageTitle' => 'Website Management | Hotela',
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requireRoles(['admin']);

        $group = $request->input('group', 'website');
        $payload = $request->all();
        unset($payload['group']);

        $sanitized = $this->sanitizeValues($payload);
        
        // Handle nested arrays
        $mainGroup = [];
        $nestedGroups = [];
        
        foreach ($sanitized as $key => $value) {
            if (is_array($value)) {
                $nestedGroups[$key] = $value;
            } else {
                $mainGroup[$key] = $value;
            }
        }
        
        // Save main group
        if (!empty($mainGroup)) {
            $this->settings->updateGroup($group, $mainGroup);
        }
        
        // Save nested groups
        foreach ($nestedGroups as $nestedKey => $nestedValues) {
            $this->settings->updateGroup($nestedKey, $nestedValues);
        }

        header('Location: ' . base_url('dashboard/website?success=' . urlencode('Website settings updated successfully')));
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

