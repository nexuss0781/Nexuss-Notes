<?php
/**
 * Nexus Notes - Configuration File
 * Premium Note-Taking Application
 * 
 * @version 1.0.0
 * @author Elite Developer
 */

// Prevent direct access
defined('APP_INIT') or define('APP_INIT', true);

// Application Settings
define('APP_NAME', 'Nexus Notes');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://nexuss-notes.gt.tc');

// Paths
define('BASE_PATH', dirname(__FILE__));
define('DATA_PATH', BASE_PATH . '/data');
define('CACHE_PATH', BASE_PATH . '/cache');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Database Configuration
define('DB_FILE', DATA_PATH . '/nexus_notes.db');
define('DB_TIMEOUT', 5000);

// Cache Settings (Aggressive Caching)
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 hour
define('CACHE_TTL_STATIC', 86400); // 24 hours for static content
define('CACHE_COMPRESS', true);

// Security Settings
define('CSRF_ENABLED', true);
define('SESSION_LIFETIME', 7200); // 2 hours

// Feature Flags
define('FEATURE_PWA', true);
define('FEATURE_EXPORT', true);
define('FEATURE_SEARCH', true);
define('FEATURE_TAGS', true);
define('FEATURE_FOLDERS', true);

// Timezone Settings
define('DEFAULT_TIMEZONE', 'UTC');
define('ETHIOPIAN_OFFSET', '+9'); // UTC+9 for Ethiopian time display
define('ARABIA_OFFSET', '+3');    // UTC+3 for Arabia time display

// Editor Settings
define('EDITOR_AUTOSAVE_INTERVAL', 30000); // 30 seconds
define('EDITOR_MAX_REVISIONS', 50);

// Pagination
define('NOTES_PER_PAGE', 20);

// Error Reporting (Production: 0, Development: E_ALL)
error_reporting(0);
ini_set('display_errors', 0);

// Performance Optimizations
ini_set('zlib.output_compression', 'On');
ini_set('output_buffering', 'On');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '30');

// MIME Types for caching
$mime_types = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon'
];
