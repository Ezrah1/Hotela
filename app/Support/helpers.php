<?php

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);

        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $base;
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        $base = base_path('config');

        return $path ? $base . DIRECTORY_SEPARATOR . $path : $base;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        static $configCache = [];

        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (!isset($configCache[$file])) {
            $path = config_path($file . '.php');
            $configCache[$file] = file_exists($path) ? require $path : [];
        }

        $value = $configCache[$file];

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('db')) {
    function db(): \PDO
    {
        return \App\Core\Database::connection();
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        return $default;
    }
}

if (!function_exists('view_path')) {
    function view_path(string $path = ''): string
    {
        $base = base_path('resources' . DIRECTORY_SEPARATOR . 'views');

        return $path ? $base . DIRECTORY_SEPARATOR . $path : $base;
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $base = base_path('storage');

        return $path ? $base . DIRECTORY_SEPARATOR . $path : $base;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }

        if ($path === '') {
            return $basePath ?: '/';
        }

        return ($basePath ?: '') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return base_url(ltrim($path, '/'));
        }

        if (str_starts_with($path, 'assets/')) {
            return base_url($path);
        }

        return base_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('load_settings_cache')) {
    function load_settings_cache(bool $force = false): array
    {
        if (!$force && isset($GLOBALS['__hotela_settings'])) {
            return $GLOBALS['__hotela_settings'];
        }

        try {
            $pdo = db();
            $tenantId = \App\Support\Tenant::id();
            $stmt = $pdo->prepare('SELECT tenant_id, namespace, `key`, value FROM settings WHERE tenant_id IS NULL OR tenant_id = :tenant');
            $stmt->execute(['tenant' => $tenantId]);

            $global = [];
            $tenantSettings = [];
            while ($row = $stmt->fetch()) {
                $value = json_decode($row['value'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $value = $row['value'];
                }
                if ($row['tenant_id'] === null) {
                    $global[$row['namespace']][$row['key']] = $value;
                } else {
                    $tenantSettings[$row['namespace']][$row['key']] = $value;
                }
            }

            $merged = array_replace_recursive($global, $tenantSettings);
            $GLOBALS['__hotela_settings'] = $merged;
        } catch (\Throwable $e) {
            $GLOBALS['__hotela_settings'] = [];
        }

        return $GLOBALS['__hotela_settings'];
    }
}

if (!function_exists('settings')) {
    function settings(?string $key = null, $default = null)
    {
        $cache = load_settings_cache();

        if ($key === null) {
            return $cache;
        }

        $segments = explode('.', $key);
        $value = $cache;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('settings_set_cache')) {
    function settings_set_cache(array $data): void
    {
        $GLOBALS['__hotela_settings'] = $data;
    }
}

if (!function_exists('format_currency')) {
    function format_currency(float $amount, ?string $currency = null, int $decimals = 0): string
    {
        $symbol = $currency
            ?? settings('pos.currency_symbol')
            ?? settings('branding.currency_symbol')
            ?? settings('pos.currency', 'KES');

        return trim($symbol . ' ' . number_format($amount, $decimals));
    }
}

if (!function_exists('show_message')) {
    function show_message(string $type, string $title, string $message, ?string $redirect = null, int $delay = 5): void
    {
        $viewFile = view_path('message.php');
        
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'Message view not found.';
            return;
        }
        
        extract([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'redirect' => $redirect,
            'delay' => $delay,
        ], EXTR_SKIP);
        
        include $viewFile;
    }
}


