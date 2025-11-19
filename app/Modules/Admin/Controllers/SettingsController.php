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

        // Handle image uploads for website settings
        if ($group === 'website') {
            $uploadService = new \App\Services\FileUploadService();
            
            // Handle hero background image
            if (isset($_FILES['hero_background_image']) && $_FILES['hero_background_image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $imagePath = $uploadService->uploadImage($_FILES['hero_background_image'], 'website');
                    if ($imagePath) {
                        $payload['hero_background_image'] = asset($imagePath);
                    }
                } catch (\Exception $e) {
                    // Silently fail, keep existing value
                }
            }
            
            // Handle restaurant image
            if (isset($_FILES['restaurant_image']) && $_FILES['restaurant_image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $imagePath = $uploadService->uploadImage($_FILES['restaurant_image'], 'website');
                    if ($imagePath) {
                        $payload['restaurant_image'] = asset($imagePath);
                    }
                } catch (\Exception $e) {
                    // Silently fail, keep existing value
                }
            }
        }

        $sanitized = $this->sanitizeValues($payload);
        
        // Handle nested arrays (e.g., payslip[enabled] should be saved to payslip namespace)
        // Exception: pages array should be saved as part of website group, not as separate namespace
        $mainGroup = [];
        $nestedGroups = [];
        
        foreach ($sanitized as $key => $value) {
            if (is_array($value)) {
                // Special case: pages array should be part of website group
                if ($group === 'website' && $key === 'pages') {
                    $mainGroup[$key] = $value;
                } else {
                    // This is a nested group (e.g., payslip[enabled])
                    $nestedGroups[$key] = $value;
                }
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

        header('Location: ' . base_url('staff/admin/settings?tab=' . urlencode($group) . '&success=' . urlencode('Settings updated successfully')));
    }

    public function uploadImage(Request $request): void
    {
        Auth::requireRoles(['admin']);
        
        header('Content-Type: application/json');
        
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No image file uploaded']);
            return;
        }

        try {
            $uploadService = new \App\Services\FileUploadService();
            $imagePath = $uploadService->uploadImage($_FILES['image'], 'website');
            
            if ($imagePath) {
                $url = asset($imagePath);
                echo json_encode(['success' => true, 'url' => $url, 'path' => $imagePath]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to upload image']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
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


