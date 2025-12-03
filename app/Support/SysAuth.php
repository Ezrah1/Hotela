<?php

namespace App\Support;

class SysAuth
{
    public static function check(): bool
    {
        return !empty($_SESSION['sysadmin']);
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: ' . base_url('sysadmin/login'));
            exit;
        }
    }
}

