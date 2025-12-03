<?php

namespace App\Modules\Website\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\GalleryRepository;
use App\Services\FileUploadService;
use App\Support\Auth;

class GalleryController extends Controller
{
    protected GalleryRepository $gallery;

    public function __construct()
    {
        $this->gallery = new GalleryRepository();
    }

    /**
     * Admin: List all gallery items
     */
    public function index(): void
    {
        Auth::requireRoles(['admin', 'director']);

        $items = $this->gallery->all();

        $this->view('dashboard/gallery/index', [
            'items' => $items,
            'pageTitle' => 'Gallery Management | Hotela',
        ]);
    }

    /**
     * Admin: Show create form
     */
    public function create(): void
    {
        Auth::requireRoles(['admin', 'director']);

        $this->view('dashboard/gallery/form', [
            'item' => null,
            'pageTitle' => 'Add Gallery Item | Hotela',
        ]);
    }

    /**
     * Admin: Store new gallery item
     */
    public function store(Request $request): void
    {
        Auth::requireRoles(['admin', 'director']);

        $title = trim((string)$request->input('title', ''));
        $description = trim((string)$request->input('description', ''));
        $displayOrder = (int)$request->input('display_order', 0);
        $status = $request->input('status', 'published');

        if ($title === '') {
            header('Location: ' . base_url('staff/dashboard/gallery/create?error=' . urlencode('Title is required')));
            return;
        }

        // Handle image upload
        $imageUrl = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadService = new FileUploadService();
                $imagePath = $uploadService->uploadImage($_FILES['image'], 'gallery');
                if ($imagePath) {
                    $imageUrl = asset($imagePath);
                }
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/gallery/create?error=' . urlencode('Image upload failed: ' . $e->getMessage())));
                return;
            }
        } else {
            // Allow URL input if no file uploaded
            $imageUrl = trim((string)$request->input('image_url', ''));
        }

        if ($imageUrl === '') {
            header('Location: ' . base_url('staff/dashboard/gallery/create?error=' . urlencode('Image is required')));
            return;
        }

        $this->gallery->create([
            'title' => $title,
            'description' => $description ?: null,
            'image_url' => $imageUrl,
            'display_order' => $displayOrder,
            'status' => $status,
        ]);

        header('Location: ' . base_url('staff/dashboard/gallery?success=' . urlencode('Gallery item created successfully')));
    }

    /**
     * Admin: Show edit form
     */
    public function edit(Request $request): void
    {
        Auth::requireRoles(['admin', 'director']);

        $id = (int)$request->input('id');
        $item = $this->gallery->findById($id);

        if (!$item) {
            header('Location: ' . base_url('staff/dashboard/gallery?error=' . urlencode('Gallery item not found')));
            return;
        }

        $this->view('dashboard/gallery/form', [
            'item' => $item,
            'pageTitle' => 'Edit Gallery Item | Hotela',
        ]);
    }

    /**
     * Admin: Update gallery item
     */
    public function update(Request $request): void
    {
        Auth::requireRoles(['admin', 'director']);

        $id = (int)$request->input('id');
        $item = $this->gallery->findById($id);

        if (!$item) {
            header('Location: ' . base_url('staff/dashboard/gallery?error=' . urlencode('Gallery item not found')));
            return;
        }

        $title = trim((string)$request->input('title', ''));
        $description = trim((string)$request->input('description', ''));
        $displayOrder = (int)$request->input('display_order', 0);
        $status = $request->input('status', 'published');

        if ($title === '') {
            header('Location: ' . base_url('staff/dashboard/gallery/edit?id=' . $id . '&error=' . urlencode('Title is required')));
            return;
        }

        $updateData = [
            'title' => $title,
            'description' => $description ?: null,
            'display_order' => $displayOrder,
            'status' => $status,
        ];

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadService = new FileUploadService();
                $imagePath = $uploadService->uploadImage($_FILES['image'], 'gallery');
                if ($imagePath) {
                    $updateData['image_url'] = asset($imagePath);
                }
            } catch (\Exception $e) {
                header('Location: ' . base_url('staff/dashboard/gallery/edit?id=' . $id . '&error=' . urlencode('Image upload failed: ' . $e->getMessage())));
                return;
            }
        } elseif ($request->input('image_url')) {
            // Allow URL input if no file uploaded
            $updateData['image_url'] = trim((string)$request->input('image_url'));
        }

        $this->gallery->update($id, $updateData);

        header('Location: ' . base_url('staff/dashboard/gallery?success=' . urlencode('Gallery item updated successfully')));
    }

    /**
     * Admin: Delete gallery item
     */
    public function delete(Request $request): void
    {
        Auth::requireRoles(['admin', 'director']);

        $id = (int)$request->input('id');
        $item = $this->gallery->findById($id);

        if (!$item) {
            header('Location: ' . base_url('staff/dashboard/gallery?error=' . urlencode('Gallery item not found')));
            return;
        }

        $this->gallery->delete($id);

        header('Location: ' . base_url('staff/dashboard/gallery?success=' . urlencode('Gallery item deleted successfully')));
    }
}

