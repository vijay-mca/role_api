<?php

namespace Config;

use PDO;
use PDOException;

class Database
{
    /**
     * @var Database|null Singleton instance
     */
    private static $instance = null;

    /**
     * @var string Database host
     */
    private $host;

    /**
     * @var string Database name
     */
    private $db;

    /**
     * @var string Database username
     */
    private $user;

    /**
     * @var string Database password
     */
    private $pass;

    /**
     * @var string Character set for connection
     */
    private $charset;

    /**
     * @var PDO|null PDO connection instance
     */
    private $pdo;

    /**
     * Constructor: Initializes DB config from environment variables and connects
     */
    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? '';
        $this->db = $_ENV['DB_NAME'] ?? '';
        $this->user = $_ENV['DB_USER'] ?? '';
        $this->pass = $_ENV['DB_PASS'] ?? '';
        $this->charset = 'utf8mb4';
        $this->connect();
    }

    /**
     * Establishes PDO connection if not already connected
     *
     * @return PDO Returns PDO instance
     * @throws PDOException If connection fails
     */
    public function connect()
    {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage(), (int)$e->getCode());
            }
        }

        return $this->pdo;
    }

    /**
     * Executes a SELECT query and returns all rows as associative arrays
     *
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind in query
     * @return array Result set as array of associative arrays
     * @throws PDOException On query failure
     */
    public function select(string $query, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Executes a SELECT query and returns a single row as an object
     *
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind in query
     * @return object|false Single result as object or false if none found
     * @throws PDOException On query failure
     */
    public function selectOne(string $query, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Executes an INSERT query and returns the last inserted ID
     *
     * @param string $query SQL insert query with placeholders
     * @param array $params Parameters to bind in query
     * @return string Last inserted ID
     * @throws PDOException On query failure
     */
    public function insert(string $query, array $params = []): string
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Executes an UPDATE query and returns number of affected rows
     *
     * @param string $query SQL update query with placeholders
     * @param array $params Parameters to bind in query
     * @return int Number of affected rows
     * @throws PDOException On query failure
     */
    public function update(string $query, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Executes a DELETE query and returns number of affected rows
     *
     * @param string $query SQL delete query with placeholders
     * @param array $params Parameters to bind in query
     * @return int Number of affected rows
     * @throws PDOException On query failure
     */
    public function delete(string $query, array $params = []): int
    {
        return $this->update($query, $params);
    }

    /**
     * Begins a database transaction
     *
     * @return bool True on success
     * @throws PDOException On failure
     */
    public function beginTransaction(): bool
    {
        try {
            return $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Commits the current database transaction
     *
     * @return bool True on success
     * @throws PDOException On failure
     */
    public function commit(): bool
    {
        try {
            return $this->pdo->commit();
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Rolls back the current database transaction if active
     *
     * @return bool|null True if rollback performed, null if no transaction active
     * @throws PDOException On failure
     */
    public function rollBack(): ?bool
    {
        try {
            if ($this->pdo->inTransaction()) {
                return $this->pdo->rollBack();
            }
            return null;
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Returns the internal PDO instance
     *
     * @return PDO|null PDO connection instance or null if not connected
     */
    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Returns the last inserted ID from the database connection
     *
     * @return string Last inserted ID
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
