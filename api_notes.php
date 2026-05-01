<?php
/**
 * Notes API Controller
 * Handles all note-related API endpoints
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/search.php';
require_once __DIR__ . '/export.php';
require_once __DIR__ . '/analytics.php';
require_once __DIR__ . '/settings.php';

class NotesAPI {
    private $db;
    private $security;
    private $categoryManager;
    private $searchEngine;
    private $exporter;
    private $analytics;
    private $settings;
    
    public function __construct() {
        $this->db = new Database();
        $this->security = SecurityManager::getInstance();
        $this->categoryManager = new CategoryTagManager($this->db);
        $this->searchEngine = new NoteSearch($this->db);
        $this->exporter = new NoteExporter($this->db);
        $this->analytics = new NoteAnalytics($this->db);
        $this->settings = new SettingsManager($this->db);
    }
    
    /**
     * Route request to appropriate handler
     */
    public function handleRequest() {
        $this->security->sendSecurityHeaders();
        header('Content-Type: application/json');
        
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = '/api';
        
        // Remove base path
        $endpoint = str_replace($basePath, '', $path);
        $endpoint = trim($endpoint, '/');
        
        // Check rate limit
        $rateLimit = $this->security->checkRateLimit($this->security->getClientIP());
        if (!$rateLimit['allowed']) {
            $this->jsonResponse(['error' => 'Rate limit exceeded'], 429);
            return;
        }
        
        // Route handling
        try {
            switch ($endpoint) {
                case 'notes':
                    $this->handleNotes($method);
                    break;
                    
                case 'categories':
                    $this->handleCategories($method);
                    break;
                    
                case 'tags':
                    $this->handleTags($method);
                    break;
                    
                case 'search':
                    $this->handleSearch($method);
                    break;
                    
                case 'export':
                    $this->handleExport($method);
                    break;
                    
                case 'import':
                    $this->handleImport($method);
                    break;
                    
                case 'analytics':
                    $this->handleAnalytics($method);
                    break;
                    
                case 'settings':
                    $this->handleSettings($method);
                    break;
                    
                default:
                    // Check for specific note operations
                    if (preg_match('/^notes\/(\d+)$/', $endpoint, $matches)) {
                        $this->handleSingleNote($method, $matches[1]);
                    } elseif (preg_match('/^notes\/(\d+)\/favorite$/', $endpoint, $matches)) {
                        $this->toggleFavorite($matches[1]);
                    } elseif (preg_match('/^notes\/(\d+)\/archive$/', $endpoint, $matches)) {
                        $this->toggleArchive($matches[1]);
                    } else {
                        $this->jsonResponse(['error' => 'Endpoint not found'], 404);
                    }
            }
        } catch (Exception $e) {
            $this->security->logSecurityEvent('api_error', ['message' => $e->getMessage()]);
            $this->jsonResponse(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Handle notes collection
     */
    private function handleNotes($method) {
        switch ($method) {
            case 'GET':
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $this->settings->get('notes_per_page', 20);
                $filter = $_GET['filter'] ?? 'all';
                
                $notes = $this->getNotesList($page, $limit, $filter);
                $this->jsonResponse(['success' => true, 'data' => $notes]);
                break;
                
            case 'POST':
                $this->verifyCSRF();
                $data = $this->getJSONInput();
                
                if (empty($data['title'])) {
                    $this->jsonResponse(['error' => 'Title is required'], 400);
                    return;
                }
                
                $noteId = $this->createNote($data);
                $this->jsonResponse(['success' => true, 'id' => $noteId], 201);
                break;
                
            default:
                $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * Handle single note operations
     */
    private function handleSingleNote($method, $id) {
        switch ($method) {
            case 'GET':
                $note = $this->db->queryOne("SELECT * FROM notes WHERE id = ?", [$id]);
                if (!$note) {
                    $this->jsonResponse(['error' => 'Note not found'], 404);
                    return;
                }
                $note['tags'] = $this->categoryManager->getTagsForNote($id);
                $this->jsonResponse(['success' => true, 'data' => $note]);
                break;
                
            case 'PUT':
                $this->verifyCSRF();
                $data = $this->getJSONInput();
                $this->updateNote($id, $data);
                $this->jsonResponse(['success' => true]);
                break;
                
            case 'DELETE':
                $this->verifyCSRF();
                $this->deleteNote($id);
                $this->jsonResponse(['success' => true]);
                break;
                
            default:
                $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * Handle categories
     */
    private function handleCategories($method) {
        switch ($method) {
            case 'GET':
                $categories = $this->categoryManager->getCategories();
                $this->jsonResponse(['success' => true, 'data' => $categories]);
                break;
                
            case 'POST':
                $this->verifyCSRF();
                $data = $this->getJSONInput();
                
                if (empty($data['name'])) {
                    $this->jsonResponse(['error' => 'Category name is required'], 400);
                    return;
                }
                
                $categoryId = $this->categoryManager->createCategory(
                    $data['name'],
                    $data['color'] ?? '#3b82f6',
                    $data['icon'] ?? 'folder'
                );
                $this->jsonResponse(['success' => true, 'id' => $categoryId], 201);
                break;
                
            default:
                $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * Handle tags
     */
    private function handleTags($method) {
        switch ($method) {
            case 'GET':
                $tags = $this->categoryManager->getTags();
                $this->jsonResponse(['success' => true, 'data' => $tags]);
                break;
                
            default:
                $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * Handle search
     */
    private function handleSearch($method) {
        if ($method !== 'GET') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        $query = $_GET['q'] ?? '';
        
        if (!empty($_GET['advanced'])) {
            $filters = [
                'title' => $_GET['title'] ?? '',
                'content' => $_GET['content'] ?? '',
                'category_id' => $_GET['category_id'] ?? null,
                'tag_id' => $_GET['tag_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'favorites' => isset($_GET['favorites']),
                'archived' => isset($_GET['archived'])
            ];
            $results = $this->searchEngine->advancedSearch($filters);
        } else {
            $results = $this->searchEngine->search($query);
        }
        
        $this->jsonResponse(['success' => true, 'data' => $results]);
    }
    
    /**
     * Handle export
     */
    private function handleExport($method) {
        if ($method !== 'GET') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        $format = $_GET['format'] ?? 'json';
        $noteId = $_GET['id'] ?? null;
        
        if ($noteId) {
            switch ($format) {
                case 'json':
                    $content = $this->exporter->exportNoteJSON($noteId);
                    $contentType = 'application/json';
                    break;
                case 'markdown':
                    $content = $this->exporter->exportNoteMarkdown($noteId);
                    $contentType = 'text/markdown';
                    break;
                case 'html':
                    $content = $this->exporter->exportNoteHTML($noteId);
                    $contentType = 'text/html';
                    break;
                case 'text':
                    $content = $this->exporter->exportNoteText($noteId);
                    $contentType = 'text/plain';
                    break;
                default:
                    $this->jsonResponse(['error' => 'Invalid format'], 400);
                    return;
            }
            
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="note-' . $noteId . '.' . $format . '"');
            echo $content;
        } else {
            // Export all notes
            $content = $this->exporter->exportAllJSON();
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="notes-backup.json"');
            echo $content;
        }
        exit;
    }
    
    /**
     * Handle import
     */
    private function handleImport($method) {
        if ($method !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        $this->verifyCSRF();
        
        $input = file_get_contents('php://input');
        $result = $this->exporter->importFromJSON($input);
        
        if ($result['success']) {
            $this->jsonResponse(['success' => true, 'imported' => $result['imported']]);
        } else {
            $this->jsonResponse(['error' => $result['error']], 400);
        }
    }
    
    /**
     * Handle analytics
     */
    private function handleAnalytics($method) {
        if ($method !== 'GET') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        $type = $_GET['type'] ?? 'overview';
        
        switch ($type) {
            case 'overview':
                $data = $this->analytics->getOverview();
                break;
            case 'daily':
                $data = $this->analytics->getNotesCreatedPerDay();
                break;
            case 'tags':
                $data = $this->analytics->getMostUsedTags();
                break;
            case 'categories':
                $data = $this->analytics->getCategoryDistribution();
                break;
            case 'storage':
                $data = $this->analytics->getStorageUsage();
                break;
            default:
                $data = $this->analytics->getOverview();
        }
        
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }
    
    /**
     * Handle settings
     */
    private function handleSettings($method) {
        switch ($method) {
            case 'GET':
                $settings = $this->settings->getAll();
                $this->jsonResponse(['success' => true, 'data' => $settings]);
                break;
                
            case 'PUT':
                $this->verifyCSRF();
                $data = $this->getJSONInput();
                $this->settings->bulkUpdate($data);
                $this->jsonResponse(['success' => true]);
                break;
                
            default:
                $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * Toggle favorite status
     */
    private function toggleFavorite($id) {
        $this->verifyCSRF();
        $note = $this->db->queryOne("SELECT is_favorite FROM notes WHERE id = ?", [$id]);
        if (!$note) {
            $this->jsonResponse(['error' => 'Note not found'], 404);
            return;
        }
        
        $newStatus = $note['is_favorite'] ? 0 : 1;
        $this->db->execute("UPDATE notes SET is_favorite = ?, updated_at = datetime('now') WHERE id = ?", [$newStatus, $id]);
        $this->jsonResponse(['success' => true, 'is_favorite' => (bool)$newStatus]);
    }
    
    /**
     * Toggle archive status
     */
    private function toggleArchive($id) {
        $this->verifyCSRF();
        $note = $this->db->queryOne("SELECT is_archived FROM notes WHERE id = ?", [$id]);
        if (!$note) {
            $this->jsonResponse(['error' => 'Note not found'], 404);
            return;
        }
        
        $newStatus = $note['is_archived'] ? 0 : 1;
        $this->db->execute("UPDATE notes SET is_archived = ?, updated_at = datetime('now') WHERE id = ?", [$newStatus, $id]);
        $this->jsonResponse(['success' => true, 'is_archived' => (bool)$newStatus]);
    }
    
    // Helper methods
    private function getNotesList($page, $limit, $filter) {
        $offset = ($page - 1) * $limit;
        $where = '';
        
        switch ($filter) {
            case 'favorites':
                $where = 'WHERE is_favorite = 1 AND is_archived = 0';
                break;
            case 'archived':
                $where = 'WHERE is_archived = 1';
                break;
            default:
                $where = 'WHERE is_archived = 0';
        }
        
        $sql = "SELECT * FROM notes $where ORDER BY updated_at DESC LIMIT ? OFFSET ?";
        $notes = $this->db->query($sql, [$limit, $offset]);
        
        foreach ($notes as &$note) {
            $note['tags'] = $this->categoryManager->getTagsForNote($note['id']);
        }
        
        return $notes;
    }
    
    private function createNote($data) {
        $this->db->execute(
            "INSERT INTO notes (title, content, category_id, created_at, updated_at) VALUES (?, ?, ?, datetime('now'), datetime('now'))",
            [$data['title'], $data['content'] ?? '', $data['category_id'] ?? null]
        );
        
        $noteId = $this->db->lastInsertId();
        
        if (!empty($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                $this->categoryManager->addTagToNote($noteId, $tag);
            }
        }
        
        return $noteId;
    }
    
    private function updateNote($id, $data) {
        $fields = [];
        $params = [];
        
        if (isset($data['title'])) {
            $fields[] = 'title = ?';
            $params[] = $data['title'];
        }
        if (isset($data['content'])) {
            $fields[] = 'content = ?';
            $params[] = $data['content'];
        }
        if (isset($data['category_id'])) {
            $fields[] = 'category_id = ?';
            $params[] = $data['category_id'];
        }
        
        $fields[] = 'updated_at = datetime(\'now\')';
        $params[] = $id;
        
        $sql = "UPDATE notes SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->execute($sql, $params);
        
        // Update tags if provided
        if (isset($data['tags'])) {
            // Remove existing tags
            $this->db->execute("DELETE FROM note_tags WHERE note_id = ?", [$id]);
            // Add new tags
            foreach ($data['tags'] as $tag) {
                $this->categoryManager->addTagToNote($id, $tag);
            }
        }
    }
    
    private function deleteNote($id) {
        $this->db->execute("DELETE FROM note_tags WHERE note_id = ?", [$id]);
        $this->db->execute("DELETE FROM notes WHERE id = ?", [$id]);
    }
    
    private function verifyCSRF() {
        $input = $this->getJSONInput();
        $token = $_POST['csrf_token'] ?? ($input['csrf_token'] ?? '');
        
        if (!$this->security->verifyCsrfToken($token)) {
            $this->security->logSecurityEvent('csrf_failure');
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
            exit;
        }
    }
    
    private function getJSONInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Handle the request
$api = new NotesAPI();
$api->handleRequest();
