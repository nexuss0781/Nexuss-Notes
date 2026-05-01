<?php
/**
 * Security Module
 * Handles CSRF protection, input sanitization, and security headers
 */

class SecurityManager {
    private static $instance = null;
    private $csrfToken;
    
    private function __construct() {
        $this->initializeCSRF();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize CSRF token
     */
    private function initializeCSRF() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->csrfToken = $_SESSION['csrf_token'];
    }
    
    /**
     * Get CSRF token
     */
    public function getCsrfToken() {
        return $this->csrfToken;
    }
    
    /**
     * Generate CSRF input field
     */
    public function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($this->csrfToken) . '">';
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken($token) {
        return hash_equals($this->csrfToken, $token);
    }
    
    /**
     * Regenerate CSRF token
     */
    public function regenerateCsrfToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $this->csrfToken = $_SESSION['csrf_token'];
        return $this->csrfToken;
    }
    
    /**
     * Sanitize string input
     */
    public function sanitizeString($input) {
        if ($input === null) {
            return null;
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Sanitize HTML (allow safe tags)
     */
    public function sanitizeHTML($input) {
        if ($input === null) {
            return null;
        }
        
        // Define allowed tags
        $allowedTags = '<p><br><strong><em><u><ul><ol><li><blockquote><code><pre><h1><h2><h3><h4><h5><h6><a><img><hr><span><div>';
        
        $input = strip_tags($input, $allowedTags);
        
        // Remove dangerous attributes
        $input = preg_replace('/on\w+="[^"]*"/i', '', $input);
        $input = preg_replace('/javascript:/i', '', $input);
        
        return $input;
    }
    
    /**
     * Sanitize integer
     */
    public function sanitizeInt($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize email
     */
    public function sanitizeEmail($input) {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Validate email
     */
    public function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize URL
     */
    public function sanitizeURL($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
    
    /**
     * Validate URL
     */
    public function isValidURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Sanitize filename
     */
    public function sanitizeFilename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return preg_replace('/\.+/', '.', $filename);
    }
    
    /**
     * Hash password
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure random token
     */
    public function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Rate limiting check
     */
    public function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
        $cacheFile = CACHE_DIR . '/ratelimit_' . md5($key) . '.json';
        
        $data = ['attempts' => 0, 'reset_time' => time() + $timeWindow];
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            
            if (time() > $data['reset_time']) {
                $data = ['attempts' => 0, 'reset_time' => time() + $timeWindow];
            }
        }
        
        $data['attempts']++;
        file_put_contents($cacheFile, json_encode($data));
        
        if ($data['attempts'] > $maxAttempts) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $data['reset_time']
            ];
        }
        
        return [
            'allowed' => true,
            'remaining' => $maxAttempts - $data['attempts'],
            'reset_time' => $data['reset_time']
        ];
    }
    
    /**
     * Send security headers
     */
    public function sendSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Type Sniffing Prevention
        header('X-Content-Type-Options: nosniff');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");
        
        // Permissions Policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
    
    /**
     * Check if request is AJAX
     */
    public function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get client IP address
     */
    public function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($event, $details = []) {
        $logFile = CACHE_DIR . '/security.log';
        $entry = [
            'timestamp' => date('c'),
            'event' => $event,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $logEntry = json_encode($entry) . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

// Helper functions
function csrf_token() {
    return SecurityManager::getInstance()->getCsrfToken();
}

function csrf_field() {
    return SecurityManager::getInstance()->csrfField();
}

function verify_csrf($token) {
    return SecurityManager::getInstance()->verifyCsrfToken($token);
}

function sanitize($input, $type = 'string') {
    $security = SecurityManager::getInstance();
    
    switch ($type) {
        case 'html':
            return $security->sanitizeHTML($input);
        case 'int':
            return $security->sanitizeInt($input);
        case 'email':
            return $security->sanitizeEmail($input);
        case 'url':
            return $security->sanitizeURL($input);
        default:
            return $security->sanitizeString($input);
    }
}
