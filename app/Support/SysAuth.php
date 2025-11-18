<?php

namespace App\Support;

class SysAuth
{
    public static function check(): bool
    {
        return !empty($_SESSION['sysadmin']);
    }

    public static void require(): void
    {
        if (!self::check()) {
            header('Location: ' . base_url('sysadmin/login'));
            exit;
        }
    }
}

