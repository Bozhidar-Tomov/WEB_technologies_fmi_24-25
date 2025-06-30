<?php

namespace App;

class Database
{
    private static ?\PDO $pdo = null;

    public static function getConnection(): \PDO
    {
        if (self::$pdo === null) {
            $configPath = dirname(__DIR__, 2) . '/config/database.php';
            if (!file_exists($configPath)) {
                throw new \RuntimeException('Database configuration file not found.');
            }
            $config = require $configPath;

            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
                $config['host'] ?? '127.0.0.1',
                $config['database'] ?? ''
            );

            self::$pdo = new \PDO(
                $dsn,
                $config['user'] ?? 'root',
                $config['password'] ?? '',
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }

        return self::$pdo;
    }
} 