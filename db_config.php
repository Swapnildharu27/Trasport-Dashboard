<?php
/**
 * Database connection settings.
 * Update these four values to match your MySQL server.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'transport_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Emulated prepares (not native) are required here because several
                // queries reuse the same named placeholder multiple times in one
                // statement (e.g. ":search" appearing in several OR'd LIKE clauses).
                // MySQL's native prepared-statement protocol doesn't support that —
                // it throws "SQLSTATE[HY093]: Invalid parameter number". Emulated
                // mode handles repeated named placeholders correctly and still uses
                // proper parameter binding (safe from SQL injection).
                PDO::ATTR_EMULATE_PREPARES   => true,
            ]);
        } catch (PDOException $e) {
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
