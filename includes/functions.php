<?php
/**
 * Nexus Notes - Helper Functions
 * Utility functions for the application
 */

defined('APP_INIT') or define('APP_INIT', true);

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize output for HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Convert Gregorian date to Ethiopian date
 */
function gregorianToEthiopian($gregorianDate = null) {
    if ($gregorianDate === null) {
        $gregorianDate = time();
    }
    
    // Ethiopian calendar constants
    $ethiopianEpoch = gregoriantojd(8, 27, 5509); // Ethiopian epoch in JD
    $gregorianJD = gregoriantojd(
        date('n', $gregorianDate),
        date('j', $gregorianDate),
        date('Y', $gregorianDate)
    );
    
    $ethiopianYear = floor(($gregorianJD - $ethiopianEpoch) / 365.25);
    $remainingDays = $gregorianJD - $ethiopianEpoch - ($ethiopianYear * 365.25);
    
    // Adjust for leap years
    $ethiopianMonth = floor($remainingDays / 30) + 1;
    $ethiopianDay = floor($remainingDays % 30) + 1;
    
    // Ethiopian year starts in September
    $ethiopianYear += 8;
    if (date('n', $gregorianDate) < 9) {
        $ethiopianYear--;
    }
    
    $months = [
        'Meskerem', 'Tikimt', 'Hidar', 'Tahsas', 'Tir', 'Yekatit',
        'Megabit', 'Miyazia', 'Ginbot', 'Sene', 'Hamle', 'Nehase', 'Pagumen'
    ];
    
    return [
        'year' => $ethiopianYear,
        'month' => $months[min($ethiopianMonth - 1, 12)],
        'day' => $ethiopianDay,
        'formatted' => sprintf('%s %d, %d', $months[min($ethiopianMonth - 1, 12)], $ethiopianDay, $ethiopianYear)
    ];
}

/**
 * Format time for specific timezone
 */
function formatTimeForTimezone($timestamp = null, $offset = 0) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $adjustedTime = $timestamp + ($offset * 3600);
    return date('H:i:s', $adjustedTime);
}

/**
 * Get formatted date with both calendars
 */
function getDualCalendarDisplay($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $gregorian = date('F j, Y', $timestamp);
    $ethiopian = gregorianToEthiopian($timestamp);
    $timeUTC9 = formatTimeForTimezone($timestamp, 9);
    $timeUTC3 = formatTimeForTimezone($timestamp, 3);
    
    return [
        'gregorian' => $gregorian,
        'ethiopian' => $ethiopian['formatted'],
        'utc9' => $timeUTC9,
        'utc3' => $timeUTC3,
        'timestamp' => $timestamp
    ];
}

/**
 * Word count from text
 */
function wordCount($text) {
    return str_word_count(strip_tags($text));
}

/**
 * Character count from text
 */
function charCount($text) {
    return strlen(strip_tags($text));
}

/**
 * Create excerpt from content
 */
function createExcerpt($content, $length = 150) {
    $text = strip_tags($content);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Generate unique ID
 */
function generateId($prefix = '') {
    return $prefix . bin2hex(random_bytes(8));
}

/**
 * Cache helper functions
 */
function getCacheKey($prefix, $identifier) {
    return sprintf('%s_%s_%s', APP_NAME, $prefix, md5($identifier));
}

function getCachedData($key) {
    if (!CACHE_ENABLED) return false;
    
    $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
    
    if (file_exists($cacheFile)) {
        $age = time() - filemtime($cacheFile);
        if ($age < CACHE_TTL) {
            $data = file_get_contents($cacheFile);
            return CACHE_COMPRESS ? unserialize(gzuncompress($data)) : unserialize($data);
        }
        unlink($cacheFile);
    }
    return false;
}

function setCachedData($key, $data, $ttl = null) {
    if (!CACHE_ENABLED) return false;
    
    $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
    $serialized = CACHE_COMPRESS ? gzcompress(serialize($data)) : serialize($data);
    
    return file_put_contents($cacheFile, $serialized) !== false;
}

function clearCache($pattern = '*') {
    $files = glob(CACHE_PATH . '/' . $pattern . '.cache');
    foreach ($files as $file) {
        unlink($file);
    }
    return count($files);
}

/**
 * JSON response helper
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Redirect helper
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Get client IP address
 */
function getClientIp() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            return explode(',', $_SERVER[$key])[0];
        }
    }
    return '127.0.0.1';
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
}

/**
 * Time ago formatter
 */
function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}
