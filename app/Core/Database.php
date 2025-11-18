<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    protected static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = config('app.db');

        try {
            self::$connection = self::createConnection($config);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown database')) {
                self::createDatabase($config);
                self::$connection = self::createConnection($config);
            } else {
                http_response_code(500);
                echo 'Database connection failed: ' . htmlspecialchars($e->getMessage());
                exit;
            }
        }

        return self::$connection;
    }

    protected static function createConnection(array $config): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'] ?? 'mysql',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 3306,
            $config['database'] ?? 'hotela',
            $config['charset'] ?? 'utf8mb4'
        );

        return new PDO(
            $dsn,
            $config['username'] ?? 'root',
            $config['password'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false,
            ]
        );
    }

    protected static function createDatabase(array $config): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;charset=%s',
            $config['driver'] ?? 'mysql',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 3306,
            $config['charset'] ?? 'utf8mb4'
        );

        $pdo = new PDO(
            $dsn,
            $config['username'] ?? 'root',
            $config['password'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );

        $dbName = $config['database'] ?? 'hotela';
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';

        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s;',
            $dbName,
            $charset,
            $collation
        ));
    }
}


