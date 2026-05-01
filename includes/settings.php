<?php
/**
 * Settings and Preferences Module
 * Manages application settings and user preferences
 */

class SettingsManager {
    private $db;
    private $cache = [];
    
    public function __construct($database) {
        $this->db = $database;
        $this->initSettingsTable();
    }
    
    /**
     * Initialize settings table
     */
    private function initSettingsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type TEXT DEFAULT 'string',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->execute($sql);
        
        // Insert default settings if not exists
        $defaults = [
            ['app_name', 'Nexus Notes', 'string'],
            ['app_description', 'Premium Note Taking Application', 'string'],
            ['default_timezone', 'Africa/Addis_Ababa', 'string'],
            ['date_format', 'YYYY-MM-DD', 'string'],
            ['time_format', '24h', 'string'],
            ['calendar_primary', 'ethiopian', 'string'],
            ['calendar_secondary', 'gregorian', 'string'],
            ['theme_mode', 'auto', 'string'],
            ['editor_mode', 'rich', 'string'],
            ['notes_per_page', '20', 'integer'],
            ['auto_save_interval', '30', 'integer'],
            ['enable_notifications', '1', 'boolean'],
            ['enable_offline_mode', '1', 'boolean'],
            ['backup_frequency', 'weekly', 'string'],
            ['export_format', 'json', 'string']
        ];
        
        foreach ($defaults as $setting) {
            $exists = $this->db->queryOne("SELECT id FROM settings WHERE setting_key = ?", [$setting[0]]);
            if (!$exists) {
                $this->db->execute(
                    "INSERT INTO settings (setting_key, setting_value, setting_type, updated_at) VALUES (?, ?, ?, datetime('now'))",
                    $setting
                );
            }
        }
    }
    
    /**
     * Get setting value
     */
    public function get($key, $default = null) {
        // Check cache first
        if (isset($this->cache[$key])) {
            return $this->castValue($this->cache[$key]['value'], $this->cache[$key]['type']);
        }
        
        $result = $this->db->queryOne("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?", [$key]);
        
        if (!$result) {
            return $default;
        }
        
        // Cache the result
        $this->cache[$key] = [
            'value' => $result['setting_value'],
            'type' => $result['setting_type']
        ];
        
        return $this->castValue($result['setting_value'], $result['setting_type']);
    }
    
    /**
     * Set setting value
     */
    public function set($key, $value, $type = 'string') {
        $stringValue = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
        
        $sql = "INSERT OR REPLACE INTO settings (setting_key, setting_value, setting_type, updated_at) 
                VALUES (?, ?, ?, datetime('now'))";
        
        $result = $this->db->execute($sql, [$key, $stringValue, $type]);
        
        // Update cache
        $this->cache[$key] = [
            'value' => $stringValue,
            'type' => $type
        ];
        
        return $result;
    }
    
    /**
     * Cast value to appropriate type
     */
    private function castValue($value, $type) {
        switch ($type) {
            case 'integer':
                return (int)$value;
            case 'boolean':
                return (bool)$value;
            case 'float':
                return (float)$value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
    
    /**
     * Get all settings
     */
    public function getAll() {
        $settings = $this->db->query("SELECT * FROM settings ORDER BY setting_key ASC");
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $this->castValue($setting['setting_value'], $setting['setting_type']);
        }
        
        return $result;
    }
    
    /**
     * Get settings by category
     */
    public function getByCategory($category) {
        $prefix = $category . '_';
        $settings = $this->db->query(
            "SELECT * FROM settings WHERE setting_key LIKE ? ORDER BY setting_key ASC",
            [$prefix . '%']
        );
        
        $result = [];
        foreach ($settings as $setting) {
            $key = str_replace($prefix, '', $setting['setting_key']);
            $result[$key] = $this->castValue($setting['setting_value'], $setting['setting_type']);
        }
        
        return $result;
    }
    
    /**
     * Reset setting to default
     */
    public function reset($key) {
        $defaults = [
            'app_name' => 'Nexus Notes',
            'default_timezone' => 'Africa/Addis_Ababa',
            'calendar_primary' => 'ethiopian',
            'theme_mode' => 'auto',
            'editor_mode' => 'rich',
            'notes_per_page' => 20,
            'auto_save_interval' => 30
        ];
        
        if (isset($defaults[$key])) {
            return $this->set($key, $defaults[$key]);
        }
        
        return false;
    }
    
    /**
     * Bulk update settings
     */
    public function bulkUpdate($settings) {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
        return true;
    }
    
    /**
     * Clear settings cache
     */
    public function clearCache() {
        $this->cache = [];
    }
    
    /**
     * Export settings
     */
    public function exportSettings() {
        $settings = $this->getAll();
        return json_encode([
            'version' => '1.0',
            'exported_at' => date('c'),
            'settings' => $settings
        ], JSON_PRETTY_PRINT);
    }
    
    /**
     * Import settings
     */
    public function importSettings($jsonData) {
        $data = json_decode($jsonData, true);
        if (!$data || !isset($data['settings'])) {
            return false;
        }
        
        return $this->bulkUpdate($data['settings']);
    }
    
    /**
     * Get theme configuration
     */
    public function getThemeConfig() {
        return [
            'mode' => $this->get('theme_mode', 'auto'),
            'primary_color' => $this->get('primary_color', '#3b82f6'),
            'font_size' => $this->get('font_size', 'medium'),
            'line_height' => $this->get('line_height', 'relaxed')
        ];
    }
    
    /**
     * Get editor configuration
     */
    public function getEditorConfig() {
        return [
            'mode' => $this->get('editor_mode', 'rich'),
            'autoSave' => $this->get('auto_save_interval', 30),
            'spellcheck' => $this->get('enable_spellcheck', true),
            'toolbar' => $this->get('editor_toolbar', 'full')
        ];
    }
    
    /**
     * Get calendar configuration
     */
    public function getCalendarConfig() {
        return [
            'primary' => $this->get('calendar_primary', 'ethiopian'),
            'secondary' => $this->get('calendar_secondary', 'gregorian'),
            'timezone' => $this->get('default_timezone', 'Africa/Addis_Ababa')
        ];
    }
}
