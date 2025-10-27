<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Simple PDO Database Helper
 * 
 * Provides a clean, reusable PDO connection and query helpers
 * to avoid repeating connection code throughout controllers.
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        // Read database config
        require APPPATH . 'config/database.php';
        $db = $database['main'];
        
        $dsn = "{$db['driver']}:host={$db['hostname']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}";
        
        try {
            $this->pdo = new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get raw PDO connection
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Execute a query and return all rows
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return array
     */
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute a query and return single row
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return array|null
     */
    public function queryOne($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Execute insert/update/delete query
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return int Affected rows or last insert ID
     */
    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        // Return last insert ID for INSERT, affected rows otherwise
        $lastId = $this->pdo->lastInsertId();
        return $lastId ?: $stmt->rowCount();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }
}
