<?php
/**
 * Notifications System
 * Real-time notifications for note activities
 */

class NotificationManager {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        $this->cache = CacheManager::getInstance();
        $this->initTable();
    }
    
    /**
     * Initialize notifications table
     */
    private function initTable() {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            type TEXT NOT NULL,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            data TEXT,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->execute($sql);
        
        // Create index for performance
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, is_read)");
    }
    
    /**
     * Create notification
     */
    public function create($type, $title, $message, $data = [], $userId = null) {
        $sql = "INSERT INTO notifications (user_id, type, title, message, data, created_at) 
                VALUES (?, ?, ?, ?, ?, datetime('now'))";
        
        $this->db->execute($sql, [
            $userId,
            $type,
            $title,
            $message,
            json_encode($data)
        ]);
        
        $notificationId = $this->db->lastInsertId();
        
        // Clear unread count cache
        $this->cache->delete('unread_count_' . ($userId ?? 'all'));
        
        return $notificationId;
    }
    
    /**
     * Get notifications for user
     */
    public function getForUser($userId = null, $limit = 20, $unreadOnly = false) {
        $where = 'WHERE user_id IS ? OR user_id IS NULL';
        if ($unreadOnly) {
            $where .= ' AND is_read = 0';
        }
        
        $sql = "SELECT * FROM notifications $where 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $notifications = $this->db->query($sql, [$userId, $limit]);
        
        foreach ($notifications as &$notif) {
            $notif['data'] = json_decode($notif['data'], true);
        }
        
        return $notifications;
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId = null) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id IS ? OR user_id IS NULL";
        $result = $this->db->execute($sql, [$userId]);
        
        // Clear cache
        $this->cache->delete('unread_count_' . ($userId ?? 'all'));
        
        return $result;
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($userId = null) {
        return $this->cache->remember('unread_count_' . ($userId ?? 'all'), function() use ($userId) {
            $sql = "SELECT COUNT(*) as count FROM notifications 
                    WHERE (user_id IS ? OR user_id IS NULL) AND is_read = 0";
            $result = $this->db->queryOne($sql, [$userId]);
            return $result['count'] ?? 0;
        }, 60);
    }
    
    /**
     * Delete notification
     */
    public function delete($id) {
        $sql = "DELETE FROM notifications WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    /**
     * Delete old notifications
     */
    public function cleanupOlderThan($days = 30) {
        $sql = "DELETE FROM notifications WHERE created_at < datetime('now', '-$days days')";
        return $this->db->execute($sql);
    }
    
    /**
     * Send note creation notification
     */
    public function notifyNoteCreated($noteId, $noteTitle) {
        return $this->create(
            'note_created',
            'Note Created',
            "New note created: $noteTitle",
            ['note_id' => $noteId, 'title' => $noteTitle]
        );
    }
    
    /**
     * Send note updated notification
     */
    public function notifyNoteUpdated($noteId, $noteTitle) {
        return $this->create(
            'note_updated',
            'Note Updated',
            "Note updated: $noteTitle",
            ['note_id' => $noteId, 'title' => $noteTitle]
        );
    }
    
    /**
     * Send reminder notification
     */
    public function notifyReminder($noteId, $noteTitle, $reminderTime) {
        return $this->create(
            'reminder',
            'Reminder',
            "Reminder for: $noteTitle",
            ['note_id' => $noteId, 'title' => $noteTitle, 'reminder_time' => $reminderTime]
        );
    }
    
    /**
     * Get notification types
     */
    public function getTypes() {
        return [
            'note_created' => ['icon' => 'file-plus', 'color' => 'blue'],
            'note_updated' => ['icon' => 'edit', 'color' => 'green'],
            'note_deleted' => ['icon' => 'trash', 'color' => 'red'],
            'reminder' => ['icon' => 'bell', 'color' => 'yellow'],
            'favorite' => ['icon' => 'star', 'color' => 'purple'],
            'archive' => ['icon' => 'archive', 'color' => 'gray'],
            'share' => ['icon' => 'share', 'color' => 'indigo'],
            'comment' => ['icon' => 'message-circle', 'color' => 'pink']
        ];
    }
    
    /**
     * Render notification HTML
     */
    public function renderNotification($notification) {
        $types = $this->getTypes();
        $type = $types[$notification['type']] ?? ['icon' => 'bell', 'color' => 'gray'];
        
        $unreadClass = !$notification['is_read'] ? 'bg-blue-50 dark:bg-blue-900/20' : '';
        
        return "
            <div class=\"notification-item p-4 $unreadClass border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer\" 
                 data-notification-id=\"{$notification['id']}\" onclick=\"markNotificationRead({$notification['id']})\">
                <div class=\"flex items-start gap-3\">
                    <div class=\"p-2 rounded-lg bg-{$type['color']}-100 dark:bg-{$type['color']}-900/30\">
                        <i data-lucide=\"{$type['icon']}\" class=\"w-5 h-5 text-{$type['color']}-600 dark:text-{$type['color']}-400\"></i>
                    </div>
                    <div class=\"flex-1 min-w-0\">
                        <div class=\"flex items-center justify-between mb-1\">
                            <h4 class=\"font-medium text-gray-900 dark:text-white truncate\">{$notification['title']}</h4>
                            <span class=\"text-xs text-gray-500\">" . $this->timeAgo($notification['created_at']) . "</span>
                        </div>
                        <p class=\"text-sm text-gray-600 dark:text-gray-400 truncate\">{$notification['message']}</p>
                    </div>
                    {!$notification['is_read'] ? '<span class=\"w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2\"></span>' : ''}
                </div>
            </div>
        ";
    }
    
    /**
     * Time ago helper
     */
    private function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . 'd ago';
        } else {
            return date('M j', $timestamp);
        }
    }
}
