<?php

namespace App\Core;

class Response
{
    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function view(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require $template;
    }
}


