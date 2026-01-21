<?php
/**
 * BRK Facilities Loader
 * 
 * Lädt und cached die Facilities-Daten von der BRK API
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRK_Facilities_Loader {
    
    /**
     * Singleton-Instanz
     */
    private static $instance = null;
    
    /**
     * Cache-Schlüssel für Transients
     */
    const CACHE_KEY = 'brk_facilities_data';
    
    /**
     * Cache-Dauer in Sekunden (24 Stunden)
     */
    const CACHE_DURATION = 86400;
    
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
        // Hook für Cache-Aktualisierung
        add_action('brk_impressum_refresh_cache', array($this, 'refresh_cache'));
    }
    
    /**
     * Facilities-Daten abrufen (mit Cache)
     * 
     * @return array|WP_Error Array mit Facilities oder WP_Error bei Fehler
     */
    public function get_facilities() {
        // Versuche aus Cache zu laden
        $cached_data = get_transient(self::CACHE_KEY);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Daten von API laden
        return $this->refresh_cache();
    }
    
    /**
     * Cache aktualisieren - Daten von API neu laden
     * 
     * @return array|WP_Error Array mit Facilities oder WP_Error bei Fehler
     */
    public function refresh_cache() {
        $response = wp_remote_get(BRK_IMPRESSUM_FACILITIES_URL, array(
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error(
                'facilities_fetch_error',
                'Fehler beim Abrufen der Facilities-Daten: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error(
                'facilities_http_error',
                'HTTP-Fehler beim Abrufen der Facilities-Daten: ' . $response_code
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'facilities_json_error',
                'Fehler beim Parsen der JSON-Daten: ' . json_last_error_msg()
            );
        }
        
        if (!is_array($data)) {
            return new WP_Error(
                'facilities_format_error',
                'Ungültiges Datenformat der Facilities'
            );
        }
        
        // Daten im Cache speichern
        set_transient(self::CACHE_KEY, $data, self::CACHE_DURATION);
        
        return $data;
    }
    
    /**
     * Einzelne Facility nach ID abrufen
     * 
     * @param string $facility_id Die Facility-ID
     * @return array|WP_Error Array mit Facility-Daten oder WP_Error
     */
    public function get_facility_by_id($facility_id) {
        $facilities = $this->get_facilities();
        
        if (is_wp_error($facilities)) {
            return $facilities;
        }
        
        foreach ($facilities as $facility) {
            if (isset($facility['id']) && $facility['id'] === $facility_id) {
                return $facility;
            }
        }
        
        return new WP_Error(
            'facility_not_found',
            'Facility mit ID "' . $facility_id . '" nicht gefunden'
        );
    }
    
    /**
     * Landesverband (ID: 000) abrufen
     * 
     * @return array|WP_Error Array mit Landesverband-Daten oder WP_Error
     */
    public function get_landesverband() {
        return $this->get_facility_by_id('000');
    }
    
    /**
     * Facilities als Options-Array für Select-Dropdown formatieren
     * 
     * @return array Array mit ID => Name Paaren
     */
    public function get_facilities_for_select() {
        $facilities = $this->get_facilities();
        
        if (is_wp_error($facilities)) {
            return array();
        }
        
        $options = array();
        
        foreach ($facilities as $facility) {
            if (isset($facility['id']) && isset($facility['name'])) {
                $options[$facility['id']] = $facility['name'];
            }
        }
        
        // Nach Name sortieren
        asort($options);
        
        return $options;
    }
    
    /**
     * Sicherer Zugriff auf verschachtelte Array-Werte
     * 
     * @param array $array Das Array
     * @param string $path Pfad mit Punktnotation (z.B. "anschrift.strasse")
     * @param mixed $default Standardwert falls nicht gefunden
     * @return mixed Der Wert oder Standardwert
     */
    public function get_nested_value($array, $path, $default = '') {
        $keys = explode('.', $path);
        $value = $array;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }
        
        return $value !== '' ? $value : $default;
    }
    
    /**
     * Cache manuell löschen
     */
    public function clear_cache() {
        delete_transient(self::CACHE_KEY);
    }
}
