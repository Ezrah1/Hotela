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

    public function uploadDocument(array $file, string $subfolder = ''): ?string
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $maxSize = 10485760; // 10MB for documents
        if ($file['size'] > $maxSize) {
            throw new \RuntimeException('File size exceeds maximum allowed size (10MB)');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Allow images and PDFs
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($mimeType, $allowedTypes, true)) {
            throw new \RuntimeException('Invalid file type. Only images, PDFs, and Word documents are allowed.');
        }

        $extension = $this->getExtensionFromMime($mimeType);
        $filename = uniqid('doc_', true) . '.' . $extension;
        
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

    protected function getExtensionFromMime(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];

        return $map[$mimeType] ?? 'pdf';
    }
}

