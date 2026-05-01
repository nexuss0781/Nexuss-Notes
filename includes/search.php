<?php
/**
 * Note Search Engine
 * Advanced full-text search functionality for notes
 */

class NoteSearch {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Initialize FTS virtual table
     */
    public function initSearchIndex() {
        $sql = "CREATE VIRTUAL TABLE IF NOT EXISTS notes_fts USING fts5(
            title, 
            content, 
            tags,
            tokenize='porter'"
        );
        $this->db->execute($sql);
        
        // Populate initial index
        $notes = $this->db->query("SELECT id, title, content FROM notes");
        foreach ($notes as $note) {
            $tags = $this->getNoteTagsString($note['id']);
            $this->db->execute(
                "INSERT OR REPLACE INTO notes_fts(rowid, title, content, tags) VALUES (?, ?, ?, ?)",
                [$note['id'], $note['title'], $note['content'], $tags]
            );
        }
    }
    
    /**
     * Get tags as comma-separated string
     */
    private function getNoteTagsString($noteId) {
        $tags = $this->db->query(
            "SELECT t.name FROM tags t 
             INNER JOIN note_tags nt ON t.id = nt.tag_id 
             WHERE nt.note_id = ?",
            [$noteId]
        );
        return implode(', ', array_column($tags, 'name'));
    }
    
    /**
     * Full-text search
     */
    public function search($query, $limit = 50) {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }
        
        // Escape special FTS characters
        $query = str_replace(['"', '*', '(', ')', ':'], '', $query);
        
        $sql = "SELECT n.*, 
                CASE 
                    WHEN n.title LIKE ? THEN 3
                    WHEN n.content LIKE ? THEN 2
                    ELSE 1
                END as relevance
                FROM notes n
                WHERE n.title LIKE ? OR n.content LIKE ?
                ORDER BY relevance DESC, n.updated_at DESC
                LIMIT ?";
        
        $searchTerm = "%$query%";
        return $this->db->query($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
    }
    
    /**
     * Advanced search with filters
     */
    public function advancedSearch($filters) {
        $conditions = [];
        $params = [];
        
        // Title search
        if (!empty($filters['title'])) {
            $conditions[] = "n.title LIKE ?";
            $params[] = "%" . $filters['title'] . "%";
        }
        
        // Content search
        if (!empty($filters['content'])) {
            $conditions[] = "n.content LIKE ?";
            $params[] = "%" . $filters['content'] . "%";
        }
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $conditions[] = "n.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        // Tag filter
        if (!empty($filters['tag_id'])) {
            $conditions[] = "nt.tag_id = ?";
            $params[] = $filters['tag_id'];
        }
        
        // Date range
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(n.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(n.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Favorites only
        if (!empty($filters['favorites'])) {
            $conditions[] = "n.is_favorite = 1";
        }
        
        // Archived only
        if (!empty($filters['archived'])) {
            $conditions[] = "n.is_archived = 1";
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $joinTag = !empty($filters['tag_id']) ? 'INNER JOIN note_tags nt ON n.id = nt.note_id' : '';
        
        $sql = "SELECT DISTINCT n.* FROM notes n 
                $joinTag
                $whereClause
                ORDER BY n.updated_at DESC
                LIMIT 50";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Search suggestions/autocomplete
     */
    public function getSuggestions($query, $limit = 5) {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }
        
        $sql = "SELECT DISTINCT title as suggestion, 'title' as type FROM notes 
                WHERE title LIKE ? 
                UNION 
                SELECT name as suggestion, 'tag' as type FROM tags 
                WHERE name LIKE ?
                LIMIT ?";
        
        $searchTerm = "$query%";
        return $this->db->query($sql, [$searchTerm, $searchTerm, $limit]);
    }
    
    /**
     * Get recent searches
     */
    public function getRecentSearches($userId = null, $limit = 10) {
        $sql = "SELECT query, COUNT(*) as count, MAX(searched_at) as last_searched 
                FROM search_history 
                WHERE user_id IS ? OR user_id IS NULL
                GROUP BY query 
                ORDER BY last_searched DESC 
                LIMIT ?";
        
        return $this->db->query($sql, [$userId, $limit]);
    }
    
    /**
     * Log search
     */
    public function logSearch($query, $userId = null) {
        $sql = "INSERT INTO search_history (query, user_id, searched_at) 
                VALUES (?, ?, datetime('now'))";
        return $this->db->execute($sql, [$query, $userId]);
    }
    
    /**
     * Update search index for a note
     */
    public function updateIndex($noteId, $title, $content) {
        $tags = $this->getNoteTagsString($noteId);
        $sql = "INSERT OR REPLACE INTO notes_fts(rowid, title, content, tags) VALUES (?, ?, ?, ?)";
        return $this->db->execute($sql, [$noteId, $title, $content, $tags]);
    }
    
    /**
     * Remove from search index
     */
    public function removeFromIndex($noteId) {
        $sql = "DELETE FROM notes_fts WHERE rowid = ?";
        return $this->db->execute($sql, [$noteId]);
    }
}
