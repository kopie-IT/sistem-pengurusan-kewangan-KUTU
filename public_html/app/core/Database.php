<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\Config;
use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        return self::getConnection();
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = Config::getInstance();

            $host = $config->get('DB_HOST', 'localhost');
            $port = $config->get('DB_PORT', '3306');
            $database = $config->get('DB_DATABASE', '');
            $charset = $config->get('DB_CHARSET', 'utf8mb4');
            $username = $config->get('DB_USERNAME', '');
            $password = $config->get('DB_PASSWORD', '');

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $host,
                $port,
                $database,
                $charset
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$connection = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new \RuntimeException('Database connection failed. Please try again later.');
            }
        }

        return self::$connection;
    }
}
