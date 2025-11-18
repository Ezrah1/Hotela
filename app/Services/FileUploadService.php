<?php

namespace App\Services;

class FileUploadService
{
    protected string $uploadDir;
    protected array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    protected int $maxSize = 5242880; // 5MB

    public function __construct()
    {
        $this->uploadDir = base_path('public/assets/uploads');
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function uploadImage(array $file, string $subfolder = ''): ?string
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > $this->maxSize) {
            throw new \RuntimeException('File size exceeds maximum allowed size (5MB)');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes, true)) {
            throw new \RuntimeException('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
        }

        $extension = $this->getExtensionFromMime($mimeType);
        $filename = uniqid('img_', true) . '.' . $extension;
        
        $targetDir = $this->uploadDir;
        if ($subfolder) {
            $targetDir .= DIRECTORY_SEPARATOR . trim($subfolder, DIRECTORY_SEPARATOR);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        // Return relative path from public directory
        return 'assets/uploads' . ($subfolder ? '/' . $subfolder : '') . '/' . $filename;
    }

    public function deleteImage(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        // Remove 'assets/' prefix if present
        $path = str_replace('assets/', '', $path);
        $fullPath = base_path('public/assets/' . $path);

        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    protected function getExtensionFromMime(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$mimeType] ?? 'jpg';
    }
}

