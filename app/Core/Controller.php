<?php

namespace App\Core;

class Controller
{
    protected function view(string $path, array $data = []): void
    {
        $viewFile = view_path($path . '.php');

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found.';
            return;
        }

        extract($data, EXTR_SKIP);
        include $viewFile;
    }
}


