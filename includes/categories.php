<?php
/**
 * Note Categories and Tags Management
 * Handles categorization and tagging system for notes
 */

class CategoryTagManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create a new category
     */
    public function createCategory($name, $color = '#3b82f6', $icon = 'folder') {
        $sql = "INSERT INTO categories (name, color, icon, created_at) VALUES (?, ?, ?, datetime('now'))";
        return $this->db->execute($sql, [$name, $color, $icon]);
    }
    
    /**
     * Get all categories
     */
    public function getCategories() {
        $sql = "SELECT * FROM categories ORDER BY name ASC";
        return $this->db->query($sql);
    }
    
    /**
     * Update category
     */
    public function updateCategory($id, $name, $color, $icon) {
        $sql = "UPDATE categories SET name = ?, color = ?, icon = ?, updated_at = datetime('now') WHERE id = ?";
        return $this->db->execute($sql, [$name, $color, $icon, $id]);
    }
    
    /**
     * Delete category
     */
    public function deleteCategory($id) {
        $sql = "DELETE FROM categories WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    /**
     * Add tag to note
     */
    public function addTagToNote($noteId, $tagName) {
        $tagId = $this->getOrCreateTag($tagName);
        $sql = "INSERT OR IGNORE INTO note_tags (note_id, tag_id) VALUES (?, ?)";
        return $this->db->execute($sql, [$noteId, $tagId]);
    }
    
    /**
     * Get or create tag
     */
    private function getOrCreateTag($name) {
        $name = strtolower(trim($name));
        $sql = "SELECT id FROM tags WHERE LOWER(name) = ?";
        $tag = $this->db->queryOne($sql, [$name]);
        
        if ($tag) {
            return $tag['id'];
        }
        
        $this->db->execute("INSERT INTO tags (name, created_at) VALUES (?, datetime('now'))", [$name]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get all tags
     */
    public function getTags() {
        $sql = "SELECT t.*, COUNT(nt.note_id) as note_count 
                FROM tags t 
                LEFT JOIN note_tags nt ON t.id = nt.tag_id 
                GROUP BY t.id 
                ORDER BY note_count DESC";
        return $this->db->query($sql);
    }
    
    /**
     * Remove tag from note
     */
    public function removeTagFromNote($noteId, $tagId) {
        $sql = "DELETE FROM note_tags WHERE note_id = ? AND tag_id = ?";
        return $this->db->execute($sql, [$noteId, $tagId]);
    }
    
    /**
     * Get tags for a note
     */
    public function getTagsForNote($noteId) {
        $sql = "SELECT t.* FROM tags t 
                INNER JOIN note_tags nt ON t.id = nt.tag_id 
                WHERE nt.note_id = ?";
        return $this->db->query($sql, [$noteId]);
    }
    
    /**
     * Get notes by category
     */
    public function getNotesByCategory($categoryId) {
        $sql = "SELECT n.* FROM notes n WHERE n.category_id = ? ORDER BY n.updated_at DESC";
        return $this->db->query($sql, [$categoryId]);
    }
    
    /**
     * Get notes by tag
     */
    public function getNotesByTag($tagId) {
        $sql = "SELECT n.* FROM notes n 
                INNER JOIN note_tags nt ON n.id = nt.note_id 
                WHERE nt.tag_id = ? 
                ORDER BY n.updated_at DESC";
        return $this->db->query($sql, [$tagId]);
    }
    
    /**
     * Assign category to note
     */
    public function assignCategoryToNote($noteId, $categoryId) {
        $sql = "UPDATE notes SET category_id = ?, updated_at = datetime('now') WHERE id = ?";
        return $this->db->execute($sql, [$categoryId, $noteId]);
    }
}
