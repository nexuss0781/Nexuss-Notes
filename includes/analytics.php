<?php
/**
 * Analytics Module
 * Provides statistics and insights about notes usage
 */

class NoteAnalytics {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get overall statistics
     */
    public function getOverview() {
        $stats = [];
        
        // Total notes
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM notes");
        $stats['total_notes'] = $result['count'];
        
        // Active notes (not archived)
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM notes WHERE is_archived = 0");
        $stats['active_notes'] = $result['count'];
        
        // Archived notes
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM notes WHERE is_archived = 1");
        $stats['archived_notes'] = $result['count'];
        
        // Favorites
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM notes WHERE is_favorite = 1");
        $stats['favorites'] = $result['count'];
        
        // Total categories
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM categories");
        $stats['total_categories'] = $result['count'];
        
        // Total tags
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM tags");
        $stats['total_tags'] = $result['count'];
        
        // Notes created today
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM notes WHERE DATE(created_at) = DATE('now')");
        $stats['created_today'] = $result['count'];
        
        // Notes created this week
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM notes WHERE DATE(created_at) >= DATE('now', '-7 days')");
        $stats['created_this_week'] = $result['count'];
        
        // Notes created this month
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM notes WHERE DATE(created_at) >= DATE('now', '-30 days')");
        $stats['created_this_month'] = $result['count'];
        
        return $stats;
    }
    
    /**
     * Get notes created per day for the last 30 days
     */
    public function getNotesCreatedPerDay() {
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM notes 
                WHERE DATE(created_at) >= DATE('now', '-30 days')
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        return $this->db->query($sql);
    }
    
    /**
     * Get most used tags
     */
    public function getMostUsedTags($limit = 10) {
        $sql = "SELECT t.name, t.color, COUNT(nt.note_id) as usage_count 
                FROM tags t 
                INNER JOIN note_tags nt ON t.id = nt.tag_id 
                GROUP BY t.id 
                ORDER BY usage_count DESC 
                LIMIT ?";
        
        return $this->db->query($sql, [$limit]);
    }
    
    /**
     * Get category distribution
     */
    public function getCategoryDistribution() {
        $sql = "SELECT c.name, c.color, c.icon, COUNT(n.id) as note_count 
                FROM categories c 
                LEFT JOIN notes n ON c.id = n.category_id 
                GROUP BY c.id 
                ORDER BY note_count DESC";
        
        return $this->db->query($sql);
    }
    
    /**
     * Get average note length
     */
    public function getAverageNoteLength() {
        $result = $this->db->queryOne("SELECT AVG(LENGTH(content)) as avg_length FROM notes");
        return $result['avg_length'] ?? 0;
    }
    
    /**
     * Get longest notes
     */
    public function getLongestNotes($limit = 5) {
        $sql = "SELECT id, title, LENGTH(content) as content_length, created_at 
                FROM notes 
                ORDER BY content_length DESC 
                LIMIT ?";
        
        return $this->db->query($sql, [$limit]);
    }
    
    /**
     * Get most recently updated notes
     */
    public function getRecentlyUpdated($limit = 10) {
        $sql = "SELECT id, title, updated_at, is_favorite 
                FROM notes 
                WHERE is_archived = 0 
                ORDER BY updated_at DESC 
                LIMIT ?";
        
        return $this->db->query($sql, [$limit]);
    }
    
    /**
     * Get notes without tags
     */
    public function getUntaggedNotes() {
        $sql = "SELECT n.id, n.title, n.created_at 
                FROM notes n 
                LEFT JOIN note_tags nt ON n.id = nt.note_id 
                WHERE nt.note_id IS NULL AND n.is_archived = 0";
        
        return $this->db->query($sql);
    }
    
    /**
     * Get notes without category
     */
    public function getUncategorizedNotes() {
        $sql = "SELECT id, title, created_at 
                FROM notes 
                WHERE category_id IS NULL AND is_archived = 0";
        
        return $this->db->query($sql);
    }
    
    /**
     * Get activity heatmap data (day of week vs hour)
     */
    public function getActivityHeatmap() {
        $sql = "SELECT 
                    CAST(strftime('%w', created_at) AS INTEGER) as day_of_week,
                    CAST(strftime('%H', created_at) AS INTEGER) as hour,
                    COUNT(*) as count
                FROM notes
                GROUP BY day_of_week, hour
                ORDER BY day_of_week, hour";
        
        return $this->db->query($sql);
    }
    
    /**
     * Get storage usage estimate
     */
    public function getStorageUsage() {
        $result = $this->db->queryOne("SELECT SUM(LENGTH(content) + LENGTH(title)) as total_bytes FROM notes");
        $bytes = $result['total_bytes'] ?? 0;
        
        return [
            'bytes' => $bytes,
            'kilobytes' => round($bytes / 1024, 2),
            'megabytes' => round($bytes / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Get word frequency in all notes
     */
    public function getWordFrequency($limit = 20) {
        $notes = $this->db->query("SELECT content FROM notes WHERE is_archived = 0");
        $wordCount = [];
        
        foreach ($notes as $note) {
            $words = preg_split('/[\s,\.\!\?]+/', strtolower($note['content']));
            foreach ($words as $word) {
                $word = trim($word, " \t\n\r\0\x0B\"'");
                if (strlen($word) > 3 && !is_numeric($word)) {
                    if (!isset($wordCount[$word])) {
                        $wordCount[$word] = 0;
                    }
                    $wordCount[$word]++;
                }
            }
        }
        
        arsort($wordCount);
        return array_slice(array_map(function($word, $count) {
            return ['word' => $word, 'count' => $count];
        }, array_keys($wordCount), $wordCount), 0, $limit);
    }
    
    /**
     * Get monthly summary
     */
    public function getMonthlySummary() {
        $sql = "SELECT 
                    strftime('%Y-%m', created_at) as month,
                    COUNT(*) as notes_created,
                    SUM(CASE WHEN is_favorite = 1 THEN 1 ELSE 0 END) as favorites_created
                FROM notes
                GROUP BY month
                ORDER BY month DESC
                LIMIT 12";
        
        return $this->db->query($sql);
    }
}
