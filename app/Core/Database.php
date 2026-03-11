<?php

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            require __DIR__ . '/../../config/db.php';
            self::$pdo = $pdo;
        }
        return self::$pdo;
    }
}
