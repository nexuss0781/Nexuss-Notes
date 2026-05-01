<?php
/**
 * Cache Manager
 * Aggressive caching implementation for optimal performance
 */

class CacheManager {
    private static $instance = null;
    private $cacheDir;
    private $enabled;
    private $defaultTTL;
    
    private function __construct() {
        $this->cacheDir = CACHE_DIR;
        $this->enabled = defined('CACHE_ENABLED') ? CACHE_ENABLED : true;
        $this->defaultTTL = defined('CACHE_TTL') ? CACHE_TTL : 3600;
        
        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get item from cache
     */
    public function get($key) {
        if (!$this->enabled) {
            return false;
        }
        
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        if (!$data) {
            return false;
        }
        
        // Check expiration
        if (isset($data['expires']) && time() > $data['expires']) {
            $this->delete($key);
            return false;
        }
        
        return $data['value'];
    }
    
    /**
     * Set item in cache
     */
    public function set($key, $value, $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $ttl = $ttl ?? $this->defaultTTL;
        $file = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }
    
    /**
     * Delete item from cache
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);
        
        if (file_exists($file)) {
            unlink($file);
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '/cache_*.json');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        return $this->cacheDir . '/cache_' . md5($key) . '.json';
    }
    
    /**
     * Remember - get from cache or execute callback
     */
    public function remember($key, $callback, $ttl = null) {
        $cached = $this->get($key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Send cache headers for HTTP caching
     */
    public function sendHeaders($maxAge = null) {
        $maxAge = $maxAge ?? $this->defaultTTL;
        
        header('Cache-Control: public, max-age=' . $maxAge);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        header('Pragma: cache');
        
        // Handle conditional requests
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            
            if ($ifModifiedSince >= time() - $maxAge) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        }
        
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    }
    
    /**
     * Cache API response
     */
    public function cacheApiResponse($endpoint, $data, $ttl = null) {
        $key = 'api_' . $endpoint;
        $this->set($key, $data, $ttl);
    }
    
    /**
     * Get cached API response
     */
    public function getCachedApiResponse($endpoint) {
        return $this->get('api_' . $endpoint);
    }
    
    /**
     * Invalidate cache by pattern
     */
    public function invalidatePattern($pattern) {
        $files = glob($this->cacheDir . '/cache_*.json');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            
            if ($data && strpos(json_encode($data), $pattern) !== false) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cacheDir . '/cache_*.json');
        $totalSize = 0;
        $count = 0;
        $expired = 0;
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $count++;
                $totalSize += filesize($file);
                
                $data = json_decode(file_get_contents($file), true);
                if ($data && isset($data['expires']) && time() > $data['expires']) {
                    $expired++;
                }
            }
        }
        
        return [
            'count' => $count,
            'size_bytes' => $totalSize,
            'size_kb' => round($totalSize / 1024, 2),
            'expired' => $expired,
            'hit_rate' => $this->calculateHitRate()
        ];
    }
    
    /**
     * Calculate cache hit rate (simplified)
     */
    private function calculateHitRate() {
        $statsFile = $this->cacheDir . '/cache_stats.json';
        
        if (!file_exists($statsFile)) {
            return 0;
        }
        
        $stats = json_decode(file_get_contents($statsFile), true);
        
        if (!$stats || $stats['total'] === 0) {
            return 0;
        }
        
        return round(($stats['hits'] / $stats['total']) * 100, 2);
    }
    
    /**
     * Record cache access
     */
    public function recordAccess($hit) {
        $statsFile = $this->cacheDir . '/cache_stats.json';
        $stats = ['hits' => 0, 'misses' => 0, 'total' => 0];
        
        if (file_exists($statsFile)) {
            $stats = json_decode(file_get_contents($statsFile), true);
        }
        
        $stats['total']++;
        if ($hit) {
            $stats['hits']++;
        } else {
            $stats['misses']++;
        }
        
        file_put_contents($statsFile, json_encode($stats), LOCK_EX);
    }
}

// Helper functions
function cache_get($key) {
    return CacheManager::getInstance()->get($key);
}

function cache_set($key, $value, $ttl = null) {
    return CacheManager::getInstance()->set($key, $value, $ttl);
}

function cache_delete($key) {
    return CacheManager::getInstance()->delete($key);
}

function cache_clear() {
    return CacheManager::getInstance()->clear();
}

function cache_remember($key, $callback, $ttl = null) {
    return CacheManager::getInstance()->remember($key, $callback, $ttl);
}

function cache_headers($maxAge = null) {
    return CacheManager::getInstance()->sendHeaders($maxAge);
}
