<?php
/**
 * Note Export/Import Module
 * Handles exporting notes to various formats and importing from backups
 */

class NoteExporter {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Export note as JSON
     */
    public function exportNoteJSON($noteId) {
        $note = $this->db->queryOne("SELECT * FROM notes WHERE id = ?", [$noteId]);
        if (!$note) {
            return null;
        }
        
        $tags = $this->db->query(
            "SELECT t.name FROM tags t 
             INNER JOIN note_tags nt ON t.id = nt.tag_id 
             WHERE nt.note_id = ?",
            [$noteId]
        );
        
        $category = null;
        if ($note['category_id']) {
            $category = $this->db->queryOne("SELECT * FROM categories WHERE id = ?", [$note['category_id']]);
        }
        
        $export = [
            'id' => $note['id'],
            'title' => $note['title'],
            'content' => $note['content'],
            'created_at' => $note['created_at'],
            'updated_at' => $note['updated_at'],
            'is_favorite' => (bool)$note['is_favorite'],
            'is_archived' => (bool)$note['is_archived'],
            'tags' => array_column($tags, 'name'),
            'category' => $category ? $category['name'] : null,
            'exported_at' => date('c')
        ];
        
        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Export all notes as JSON
     */
    public function exportAllJSON() {
        $notes = $this->db->query("SELECT * FROM notes ORDER BY created_at DESC");
        $export = [];
        
        foreach ($notes as $note) {
            $tags = $this->db->query(
                "SELECT t.name FROM tags t 
                 INNER JOIN note_tags nt ON t.id = nt.tag_id 
                 WHERE nt.note_id = ?",
                [$note['id']]
            );
            
            $category = null;
            if ($note['category_id']) {
                $category = $this->db->queryOne("SELECT name FROM categories WHERE id = ?", [$note['category_id']]);
            }
            
            $export[] = [
                'id' => $note['id'],
                'title' => $note['title'],
                'content' => $note['content'],
                'created_at' => $note['created_at'],
                'updated_at' => $note['updated_at'],
                'is_favorite' => (bool)$note['is_favorite'],
                'is_archived' => (bool)$note['is_archived'],
                'tags' => array_column($tags, 'name'),
                'category' => $category ? $category['name'] : null
            ];
        }
        
        return json_encode([
            'version' => '1.0',
            'exported_at' => date('c'),
            'total_notes' => count($export),
            'notes' => $export
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Export note as Markdown
     */
    public function exportNoteMarkdown($noteId) {
        $note = $this->db->queryOne("SELECT * FROM notes WHERE id = ?", [$noteId]);
        if (!$note) {
            return null;
        }
        
        $tags = $this->db->query(
            "SELECT t.name FROM tags t 
             INNER JOIN note_tags nt ON t.id = nt.tag_id 
             WHERE nt.note_id = ?",
            [$noteId]
        );
        
        $markdown = "# {$note['title']}\n\n";
        $markdown .= "**Created:** " . date('Y-m-d H:i:s', strtotime($note['created_at'])) . "\n";
        $markdown .= "**Updated:** " . date('Y-m-d H:i:s', strtotime($note['updated_at'])) . "\n";
        
        if (!empty($tags)) {
            $tagNames = array_column($tags, 'name');
            $markdown .= "**Tags:** " . implode(', ', $tagNames) . "\n";
        }
        
        $markdown .= "\n---\n\n";
        $markdown .= $note['content'];
        
        return $markdown;
    }
    
    /**
     * Export note as plain text
     */
    public function exportNoteText($noteId) {
        $note = $this->db->queryOne("SELECT * FROM notes WHERE id = ?", [$noteId]);
        if (!$note) {
            return null;
        }
        
        $text = "{$note['title']}\n";
        $text .= str_repeat('=', strlen($note['title'])) . "\n\n";
        $text .= "Created: " . date('Y-m-d H:i:s', strtotime($note['created_at'])) . "\n";
        $text .= "Updated: " . date('Y-m-d H:i:s', strtotime($note['updated_at'])) . "\n\n";
        $text .= $note['content'];
        
        return $text;
    }
    
    /**
     * Export notes as HTML
     */
    public function exportNoteHTML($noteId) {
        $note = $this->db->queryOne("SELECT * FROM notes WHERE id = ?", [$noteId]);
        if (!$note) {
            return null;
        }
        
        $tags = $this->db->query(
            "SELECT t.name FROM tags t 
             INNER JOIN note_tags nt ON t.id = nt.tag_id 
             WHERE nt.note_id = ?",
            [$noteId]
        );
        
        $html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
        $html .= "<meta charset=\"UTF-8\">\n";
        $html .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        $html .= "<title>{$note['title']}</title>\n";
        $html .= "<style>\n";
        $html .= "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; line-height: 1.6; }\n";
        $html .= "h1 { color: #1a1a1a; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }\n";
        $html .= ".meta { color: #666; font-size: 0.9em; margin-bottom: 20px; }\n";
        $html .= ".tags { display: flex; gap: 8px; flex-wrap: wrap; margin: 15px 0; }\n";
        $html .= ".tag { background: #e0e7ff; color: #4338ca; padding: 4px 12px; border-radius: 16px; font-size: 0.85em; }\n";
        $html .= ".content { white-space: pre-wrap; }\n";
        $html .= "</style>\n</head>\n<body>\n";
        $html .= "<h1>" . htmlspecialchars($note['title']) . "</h1>\n";
        $html .= "<div class=\"meta\">\n";
        $html .= "<p><strong>Created:</strong> " . date('Y-m-d H:i:s', strtotime($note['created_at'])) . "</p>\n";
        $html .= "<p><strong>Updated:</strong> " . date('Y-m-d H:i:s', strtotime($note['updated_at'])) . "</p>\n";
        $html .= "</div>\n";
        
        if (!empty($tags)) {
            $html .= "<div class=\"tags\">\n";
            foreach ($tags as $tag) {
                $html .= "<span class=\"tag\">" . htmlspecialchars($tag['name']) . "</span>\n";
            }
            $html .= "</div>\n";
        }
        
        $html .= "<div class=\"content\">" . htmlspecialchars($note['content']) . "</div>\n";
        $html .= "</body></html>";
        
        return $html;
    }
    
    /**
     * Import notes from JSON
     */
    public function importFromJSON($jsonData) {
        $data = json_decode($jsonData, true);
        if (!$data) {
            return ['success' => false, 'error' => 'Invalid JSON data'];
        }
        
        // Handle both single note and multiple notes
        $notes = isset($data['notes']) ? $data['notes'] : [$data];
        $imported = 0;
        $errors = [];
        
        foreach ($notes as $note) {
            try {
                // Get or create category
                $categoryId = null;
                if (!empty($note['category'])) {
                    $cat = $this->db->queryOne("SELECT id FROM categories WHERE name = ?", [$note['category']]);
                    if (!$cat) {
                        $this->db->execute(
                            "INSERT INTO categories (name, color, icon, created_at) VALUES (?, ?, ?, datetime('now'))",
                            [$note['category'], '#3b82f6', 'folder']
                        );
                        $categoryId = $this->db->lastInsertId();
                    } else {
                        $categoryId = $cat['id'];
                    }
                }
                
                // Insert note
                $this->db->execute(
                    "INSERT INTO notes (title, content, category_id, is_favorite, is_archived, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $note['title'],
                        $note['content'],
                        $categoryId,
                        !empty($note['is_favorite']) ? 1 : 0,
                        !empty($note['is_archived']) ? 1 : 0,
                        $note['created_at'] ?? date('Y-m-d H:i:s'),
                        $note['updated_at'] ?? date('Y-m-d H:i:s')
                    ]
                );
                
                $noteId = $this->db->lastInsertId();
                
                // Add tags
                if (!empty($note['tags'])) {
                    foreach ($note['tags'] as $tagName) {
                        $tag = $this->db->queryOne("SELECT id FROM tags WHERE name = ?", [$tagName]);
                        if (!$tag) {
                            $this->db->execute("INSERT INTO tags (name, created_at) VALUES (?, datetime('now'))", [$tagName]);
                            $tagId = $this->db->lastInsertId();
                        } else {
                            $tagId = $tag['id'];
                        }
                        $this->db->execute("INSERT OR IGNORE INTO note_tags (note_id, tag_id) VALUES (?, ?)", [$noteId, $tagId]);
                    }
                }
                
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Error importing note '{$note['title']}': " . $e->getMessage();
            }
        }
        
        return [
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ];
    }
}
