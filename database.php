<?php
/**
 * Nexus Notes - Database Layer
 * SQLite Database Connection and Query Operations
 */

defined('APP_INIT') or define('APP_INIT', true);
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->connect();
        $this->initializeTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            if (!file_exists(DATA_PATH)) {
                mkdir(DATA_PATH, 0755, true);
            }
            
            $this->db = new PDO(
                'sqlite:' . DB_FILE,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => DB_TIMEOUT / 1000
                ]
            );
            
            // Optimize SQLite for performance
            $this->db->exec('PRAGMA journal_mode=WAL');
            $this->db->exec('PRAGMA synchronous=NORMAL');
            $this->db->exec('PRAGMA cache_size=10000');
            $this->db->exec('PRAGMA temp_store=MEMORY');
            $this->db->exec('PRAGMA busy_timeout=' . DB_TIMEOUT);
            
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    private function initializeTables() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL DEFAULT '',
                content TEXT NOT NULL DEFAULT '',
                folder_id INTEGER DEFAULT NULL,
                is_pinned INTEGER DEFAULT 0,
                is_archived INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                word_count INTEGER DEFAULT 0,
                char_count INTEGER DEFAULT 0
            )",
            
            "CREATE TABLE IF NOT EXISTS folders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                parent_id INTEGER DEFAULT NULL,
                color TEXT DEFAULT '#6B7280',
                icon TEXT DEFAULT 'folder',
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                color TEXT DEFAULT '#3B82F6',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS note_tags (
                note_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                PRIMARY KEY (note_id, tag_id),
                FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
                FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT,
                type TEXT DEFAULT 'string',
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS note_revisions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                note_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE
            )",
            
            "CREATE INDEX IF NOT EXISTS idx_notes_folder ON notes(folder_id)",
            "CREATE INDEX IF NOT EXISTS idx_notes_updated ON notes(updated_at DESC)",
            "CREATE INDEX IF NOT EXISTS idx_notes_pinned ON notes(is_pinned)",
            "CREATE INDEX IF NOT EXISTS idx_notes_search ON notes(title, content)",
            "CREATE INDEX IF NOT EXISTS idx_note_tags_note ON note_tags(note_id)",
            "CREATE INDEX IF NOT EXISTS idx_note_tags_tag ON note_tags(tag_id)"
        ];
        
        foreach ($queries as $query) {
            $this->db->exec($query);
        }
        
        // Insert default settings
        $defaults = [
            ['theme', 'light', 'string'],
            ['editor_mode', 'rich', 'string'],
            ['notes_per_page', (string)NOTES_PER_PAGE, 'integer'],
            ['default_folder', '1', 'integer']
        ];
        
        foreach ($defaults as $setting) {
            $stmt = $this->db->prepare(
                "INSERT OR IGNORE INTO settings (key, value, type) VALUES (?, ?, ?)"
            );
            $stmt->execute($setting);
        }
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function insert($table, $data) {
        $keys = array_keys($data);
        $placeholders = str_repeat('?, ', count($keys) - 1) . '?';
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $keys),
            $placeholders
        );
        
        $this->query($sql, array_values($data));
        return $this->db->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = ?";
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $set),
            $where
        );
        
        return $this->query($sql, array_merge(array_values($data), $whereParams));
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params);
    }
    
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    public function commit() {
        return $this->db->commit();
    }
    
    public function rollback() {
        return $this->db->rollBack();
    }
}
