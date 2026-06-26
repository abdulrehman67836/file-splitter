<?php
namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;

    /**
     * Get the PDO connection instance (Singleton pattern).
     *
     * @return PDO
     * @throws Exception
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $root = dirname(dirname(__DIR__));
            
            // Ensure env is loaded
            Env::load($root . '/.env');
            
            $config = require $root . '/config/database.php';
            
            $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Helper to run a parameterized query.
     *
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     * @throws Exception
     */
    public static function query(string $sql, array $params = []): \PDOStatement {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query execution failed: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    /**
     * Fetch a single row from database.
     *
     * @param string $sql
     * @param array $params
     * @return array|null
     * @throws Exception
     */
    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows matching the query.
     *
     * @param string $sql
     * @param array $params
     * @return array
     * @throws Exception
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Start a transaction.
     *
     * @return bool
     * @throws Exception
     */
    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit a transaction.
     *
     * @return bool
     * @throws Exception
     */
    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    /**
     * Rollback a transaction.
     *
     * @return bool
     * @throws Exception
     */
    public static function rollback(): bool {
        return self::getConnection()->rollBack();
    }

    /**
     * Get the last inserted ID.
     *
     * @param string|null $name Name of the sequence object (important in PostgreSQL)
     * @return string
     * @throws Exception
     */
    public static function lastInsertId(?string $name = null): string {
        return self::getConnection()->lastInsertId($name);
    }
}
