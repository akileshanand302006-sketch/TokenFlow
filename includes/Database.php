<?php
/**
 * TokenFlow Pro — Database Helper Class
 * Wraps PDO for convenient query execution
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;
    
    private function __construct() {
        $this->pdo = getDBConnection();
    }
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getPDO(): PDO {
        return $this->pdo;
    }
    
    /**
     * Execute a query and return all results
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute a query and return single row
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Execute a query and return single column value
     */
    public function fetchColumn(string $sql, array $params = [], int $column = 0) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($column);
    }
    
    /**
     * Alias for fetchOne
     */
    public function fetch(string $sql, array $params = []): ?array {
        return $this->fetchOne($sql, $params);
    }
    
    /**
     * Alias for execute
     */
    public function query(string $sql, array $params = []): int {
        return $this->execute($sql, $params);
    }

    /**
     * Execute INSERT/UPDATE/DELETE and return affected rows
     */
    public function execute(string $sql, array $params = []): int {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Execute INSERT and return last insert ID
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_map(fn($col) => "`$col`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Execute UPDATE with conditions
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setClauses = implode(', ', array_map(fn($col) => "`$col` = ?", array_keys($data)));
        
        $sql = "UPDATE `$table` SET $setClauses WHERE $where";
        $params = array_merge(array_values($data), $whereParams);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Execute DELETE with conditions
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Count rows with conditions
     */
    public function count(string $table, string $where = '1=1', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM `$table` WHERE $where";
        return (int) $this->fetchColumn($sql, $params);
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit(): bool {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback(): bool {
        return $this->pdo->rollBack();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
}
