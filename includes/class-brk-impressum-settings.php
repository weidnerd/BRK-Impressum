<?php
/**
 * BRK Impressum Settings
 * 
 * Verwaltet die Plugin-Einstellungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRK_Impressum_Settings {
    
    /**
     * Singleton-Instanz
     */
    private static $instance = null;
    
    /**
     * Options-Key
     */
    const OPTION_KEY = 'brk_impressum_settings';
    
    /**
     * Singleton-Instanz abrufen
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konstruktor
     */
    private function __construct() {
        // Konstruktor leer halten
    }
    
    /**
     * Alle Einstellungen abrufen
     */
    public function get_settings() {
        $defaults = array(
            'facility_id' => '',
            'responsible_name' => '',
            'responsible_function' => '',
            'responsible_email' => '',
            'last_updated' => ''
        );
        
        $settings = get_option(self::OPTION_KEY, $defaults);
        
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Einzelne Einstellung abrufen
     */
    public function get_setting($key, $default = '') {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Einstellungen speichern
     */
    public function save_settings($new_settings) {
        $current = $this->get_settings();
        $updated = array_merge($current, $new_settings);
        $updated['last_updated'] = current_time('mysql');
        
        return update_option(self::OPTION_KEY, $updated);
    }
    
    /**
     * Einstellung validieren
     */
    public function validate_settings($settings) {
        $validated = array();
        
        if (isset($settings['facility_id'])) {
            $validated['facility_id'] = sanitize_text_field($settings['facility_id']);
        }
        
        if (isset($settings['responsible_name'])) {
            $validated['responsible_name'] = sanitize_text_field($settings['responsible_name']);
        }
        
        if (isset($settings['responsible_function'])) {
            $validated['responsible_function'] = sanitize_text_field($settings['responsible_function']);
        }
        
        if (isset($settings['responsible_email'])) {
            $validated['responsible_email'] = sanitize_email($settings['responsible_email']);
        }
        
        return $validated;
    }
    
    /**
     * Einstellungen zurücksetzen
     */
    public function reset_settings() {
        return delete_option(self::OPTION_KEY);
    }
}
