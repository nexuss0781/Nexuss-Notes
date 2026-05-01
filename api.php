<?php
/**
 * Nexus Notes - REST API
 * Handles all AJAX requests for note operations
 */

define('APP_INIT', true);
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($action) {
        case 'get_notes':
            // Fetch all notes with optional filters
            $folderId = $_GET['folder_id'] ?? null;
            $tagId = $_GET['tag_id'] ?? null;
            $search = $_GET['search'] ?? null;
            $archived = $_GET['archived'] ?? 0;
            
            $sql = "SELECT n.*, f.name as folder_name, f.color as folder_color
                    FROM notes n
                    LEFT JOIN folders f ON n.folder_id = f.id
                    WHERE n.is_archived = ?";
            $params = [(int)$archived];
            
            if ($folderId) {
                $sql .= " AND n.folder_id = ?";
                $params[] = (int)$folderId;
            }
            
            if ($tagId) {
                $sql .= " AND n.id IN (SELECT note_id FROM note_tags WHERE tag_id = ?)";
                $params[] = (int)$tagId;
            }
            
            if ($search) {
                $sql .= " AND (n.title LIKE ? OR n.content LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql .= " ORDER BY n.is_pinned DESC, n.updated_at DESC";
            
            $notes = $db->fetchAll($sql, $params);
            
            // Add tags to each note
            foreach ($notes as &$note) {
                $tags = $db->fetchAll(
                    "SELECT t.* FROM tags t 
                     JOIN note_tags nt ON t.id = nt.tag_id 
                     WHERE nt.note_id = ?",
                    [(int)$note['id']]
                );
                $note['tags'] = $tags;
            }
            
            jsonResponse(['success' => true, 'data' => $notes]);
            break;
            
        case 'get_note':
            $id = $_GET['id'] ?? 0;
            $note = $db->fetchOne("SELECT * FROM notes WHERE id = ?", [(int)$id]);
            
            if (!$note) {
                jsonResponse(['success' => false, 'error' => 'Note not found'], 404);
            }
            
            $tags = $db->fetchAll(
                "SELECT t.* FROM tags t 
                 JOIN note_tags nt ON t.id = nt.tag_id 
                 WHERE nt.note_id = ?",
                [(int)$id]
            );
            $note['tags'] = $tags;
            
            jsonResponse(['success' => true, 'data' => $note]);
            break;
            
        case 'create_note':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $noteData = [
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
                'folder_id' => $data['folder_id'] ?? null,
                'word_count' => wordCount($data['content'] ?? ''),
                'char_count' => charCount($data['content'] ?? '')
            ];
            
            $id = $db->insert('notes', $noteData);
            $note = $db->fetchOne("SELECT * FROM notes WHERE id = ?", [(int)$id]);
            
            // Handle tags
            if (!empty($data['tags'])) {
                foreach ($data['tags'] as $tagName) {
                    $tag = $db->fetchOne("SELECT * FROM tags WHERE name = ?", [$tagName]);
                    if (!$tag) {
                        $tagId = $db->insert('tags', ['name' => $tagName]);
                    } else {
                        $tagId = $tag['id'];
                    }
                    $db->insert('note_tags', ['note_id' => $id, 'tag_id' => $tagId]);
                }
            }
            
            clearCache();
            jsonResponse(['success' => true, 'data' => $note]);
            break;
            
        case 'update_note':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            
            $existingNote = $db->fetchOne("SELECT * FROM notes WHERE id = ?", [(int)$id]);
            if (!$existingNote) {
                jsonResponse(['success' => false, 'error' => 'Note not found'], 404);
            }
            
            $noteData = [
                'title' => $data['title'] ?? $existingNote['title'],
                'content' => $data['content'] ?? $existingNote['content'],
                'updated_at' => date('Y-m-d H:i:s'),
                'word_count' => wordCount($data['content'] ?? $existingNote['content']),
                'char_count' => charCount($data['content'] ?? $existingNote['content'])
            ];
            
            $db->update('notes', $noteData, 'id = ?', [(int)$id]);
            
            // Save revision
            $db->insert('note_revisions', [
                'note_id' => $id,
                'content' => $noteData['content']
            ]);
            
            $note = $db->fetchOne("SELECT * FROM notes WHERE id = ?", [(int)$id]);
            clearCache();
            
            jsonResponse(['success' => true, 'data' => $note]);
            break;
            
        case 'delete_note':
            $id = $_POST['id'] ?? $_GET['id'] ?? 0;
            $db->delete('notes', 'id = ?', [(int)$id]);
            clearCache();
            jsonResponse(['success' => true]);
            break;
            
        case 'toggle_pin':
            $id = $_POST['id'] ?? 0;
            $note = $db->fetchOne("SELECT is_pinned FROM notes WHERE id = ?", [(int)$id]);
            if ($note) {
                $db->update('notes', ['is_pinned' => !$note['is_pinned']], 'id = ?', [(int)$id]);
                clearCache();
            }
            jsonResponse(['success' => true]);
            break;
            
        case 'get_folders':
            $folders = $db->fetchAll("SELECT * FROM folders ORDER BY sort_order, name");
            
            // Add note count to each folder
            foreach ($folders as &$folder) {
                $count = $db->fetchOne(
                    "SELECT COUNT(*) as count FROM notes WHERE folder_id = ? AND is_archived = 0",
                    [(int)$folder['id']]
                );
                $folder['note_count'] = $count['count'];
            }
            
            jsonResponse(['success' => true, 'data' => $folders]);
            break;
            
        case 'create_folder':
            $data = json_decode(file_get_contents('php://input'), true);
            $folderData = [
                'name' => $data['name'] ?? '',
                'color' => $data['color'] ?? '#6B7280',
                'icon' => $data['icon'] ?? 'folder'
            ];
            $id = $db->insert('folders', $folderData);
            $folder = $db->fetchOne("SELECT * FROM folders WHERE id = ?", [(int)$id]);
            clearCache();
            jsonResponse(['success' => true, 'data' => $folder]);
            break;
            
        case 'get_tags':
            $tags = $db->fetchAll("SELECT * FROM tags ORDER BY name");
            
            // Add note count to each tag
            foreach ($tags as &$tag) {
                $count = $db->fetchOne(
                    "SELECT COUNT(*) as count FROM note_tags WHERE tag_id = ?",
                    [(int)$tag['id']]
                );
                $tag['note_count'] = $count['count'];
            }
            
            jsonResponse(['success' => true, 'data' => $tags]);
            break;
            
        case 'get_stats':
            $stats = [];
            $stats['total_notes'] = $db->fetchOne("SELECT COUNT(*) as count FROM notes WHERE is_archived = 0")['count'];
            $stats['total_folders'] = $db->fetchOne("SELECT COUNT(*) as count FROM folders")['count'];
            $stats['total_tags'] = $db->fetchOne("SELECT COUNT(*) as count FROM tags")['count'];
            $stats['pinned_notes'] = $db->fetchOne("SELECT COUNT(*) as count FROM notes WHERE is_pinned = 1")['count'];
            
            $recentActivity = $db->fetchAll(
                "SELECT * FROM notes ORDER BY updated_at DESC LIMIT 5"
            );
            $stats['recent_activity'] = $recentActivity;
            
            jsonResponse(['success' => true, 'data' => $stats]);
            break;
            
        default:
            jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Server error'], 500);
}
