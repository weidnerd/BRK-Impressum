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
        // Detailliertes Logging aktivieren
        $debug_info = array();
        $debug_info['url'] = BRK_IMPRESSUM_FACILITIES_URL;
        $debug_info['timestamp'] = current_time('mysql');
        
        $response = wp_remote_get(BRK_IMPRESSUM_FACILITIES_URL, array(
            'timeout' => 30,
            'sslverify' => true,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'BRK-Impressum-Plugin/1.0.0 WordPress/' . get_bloginfo('version')
            )
        ));
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $debug_info['error_type'] = 'WP_Error';
            $debug_info['error_message'] = $error_message;
            $debug_info['error_code'] = $response->get_error_code();
            
            error_log('BRK Impressum API Error: ' . print_r($debug_info, true));
            set_transient('brk_impressum_last_error', $debug_info, 3600);
            
            return $this->load_fallback_data($debug_info);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_message = wp_remote_retrieve_response_message($response);
        
        $debug_info['http_code'] = $response_code;
        $debug_info['http_message'] = $response_message;
        
        if ($response_code !== 200) {
            $debug_info['error_type'] = 'HTTP_Error';
            $debug_info['response_headers'] = wp_remote_retrieve_headers($response);
            $debug_info['response_body_preview'] = substr(wp_remote_retrieve_body($response), 0, 500);
            
            error_log('BRK Impressum HTTP Error: ' . print_r($debug_info, true));
            set_transient('brk_impressum_last_error', $debug_info, 3600);
            
            return $this->load_fallback_data($debug_info);
        }
        
        $body = wp_remote_retrieve_body($response);
        $debug_info['body_length'] = strlen($body);
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $debug_info['error_type'] = 'JSON_Error';
            $debug_info['error_message'] = json_last_error_msg();
            $debug_info['json_error_code'] = json_last_error();
            $debug_info['body_preview'] = substr($body, 0, 500);
            
            error_log('BRK Impressum JSON Error: ' . print_r($debug_info, true));
            set_transient('brk_impressum_last_error', $debug_info, 3600);
            
            return $this->load_fallback_data($debug_info);
        }
        
        if (!is_array($data)) {
            $debug_info['error_type'] = 'Format_Error';
            $debug_info['error_message'] = 'Daten sind kein Array';
            $debug_info['data_type'] = gettype($data);
            
            error_log('BRK Impressum Format Error: ' . print_r($debug_info, true));
            set_transient('brk_impressum_last_error', $debug_info, 3600);
            
            return $this->load_fallback_data($debug_info);
        }
        
        // Prüfen, ob die Daten verschachtelt sind (Array mit einem Element, das wiederum das Array enthält)
        if (count($data) === 1 && isset($data[0]) && is_array($data[0])) {
            // Prüfen ob das erste Element ein Array von Facilities ist
            $first_item = $data[0];
            if (is_array($first_item) && isset($first_item[0]) && is_array($first_item[0])) {
                // Daten sind verschachtelt - inneres Array extrahieren
                error_log('BRK Impressum: Detected nested array structure, extracting inner array');
                $data = $first_item;
            }
        }
        
        // Erfolg! Fehler-Transient löschen
        delete_transient('brk_impressum_last_error');
        
        $debug_info['success'] = true;
        $debug_info['facilities_count'] = count($data);
        error_log('BRK Impressum Success: Loaded ' . count($data) . ' facilities');
        
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
        
        if (!is_array($facilities)) {
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
    
    /**
     * Lokale Fallback-Daten laden (falls API nicht erreichbar)
     * 
     * @param array $debug_info Debug-Informationen vom fehlgeschlagenen Request
     * @return array|WP_Error
     */
    private function load_fallback_data($debug_info = array()) {
        // Versuche lokale Example-Datei zu laden
        $example_file = BRK_IMPRESSUM_PLUGIN_DIR . 'facilities-example.json';
        
        if (file_exists($example_file)) {
            $json_data = file_get_contents($example_file);
            $data = json_decode($json_data, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                // Cache setzen mit kürzerer Laufzeit (1 Stunde)
                set_transient(self::CACHE_KEY, $data, 3600);
                error_log('BRK Impressum: Fallback-Daten aus lokaler Datei geladen');
                return $data;
            }
        }
        
        // Hardcoded Fallback-Daten als letzter Ausweg
        $fallback_data = $this->get_hardcoded_fallback_data();
        set_transient(self::CACHE_KEY, $fallback_data, 3600);
        error_log('BRK Impressum: Hardcoded Fallback-Daten verwendet');
        return $fallback_data;
    }
    
    /**
     * Hardcoded Fallback-Daten
     * 
     * @return array
     */
    private function get_hardcoded_fallback_data() {
        return array(
            array(
                'id' => '000',
                'ebene' => 'Landesverband',
                'name' => 'BRK Landesverband Bayern',
                'anschrift' => array(
                    'strasse' => 'Garmischer Straße 19-21',
                    'plz' => '81373',
                    'ort' => 'München'
                ),
                'kontakt' => array(
                    'telefon' => '089 9241-0',
                    'email' => 'info@brk.de'
                ),
                'vorstand' => array(
                    'funktion' => 'Präsident',
                    'name' => 'Dr. Beispiel Präsident'
                ),
                'geschaeftsfuehrung' => array(
                    'funktion' => 'Generalsekretär',
                    'name' => 'Max Mustermann',
                    'email' => 'generalsekretaer@brk.de'
                )
            ),
            array(
                'id' => '001',
                'ebene' => 'Kreisverband',
                'name' => 'BRK Beispiel-Kreisverband',
                'anschrift' => array(
                    'strasse' => 'Beispielstraße 1',
                    'plz' => '80000',
                    'ort' => 'München'
                ),
                'kontakt' => array(
                    'telefon' => '089 12345-0',
                    'email' => 'info@beispiel.brk.de'
                ),
                'vorstand' => array(
                    'funktion' => 'Vorsitzender',
                    'name' => 'Erika Mustermann'
                ),
                'geschaeftsfuehrung' => array(
                    'funktion' => 'Geschäftsführer',
                    'name' => 'Hans Beispiel',
                    'email' => 'geschaeftsfuehrung@beispiel.brk.de'
                )
            )
        );
    }
    
    /**
     * Letzte Fehlerinformationen abrufen
     * 
     * @return array|false
     */
    public function get_last_error_info() {
        return get_transient('brk_impressum_last_error');
    }
    
    /**
     * API-Verbindung testen
     * 
     * @return array Test-Ergebnis mit Details
     */
    public function test_api_connection() {
        $result = array(
            'timestamp' => current_time('mysql'),
            'url' => BRK_IMPRESSUM_FACILITIES_URL,
            'success' => false
        );
        
        $start_time = microtime(true);
        
        $response = wp_remote_get(BRK_IMPRESSUM_FACILITIES_URL, array(
            'timeout' => 10,
            'sslverify' => true,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        $result['duration'] = round((microtime(true) - $start_time) * 1000, 2) . ' ms';
        
        if (is_wp_error($response)) {
            $result['error'] = $response->get_error_message();
            $result['error_code'] = $response->get_error_code();
            return $result;
        }
        
        $result['http_code'] = wp_remote_retrieve_response_code($response);
        $result['http_message'] = wp_remote_retrieve_response_message($response);
        $headers = wp_remote_retrieve_headers($response);
        $result['content_type'] = isset($headers['content-type']) ? $headers['content-type'] : 'unknown';
        
        $body = wp_remote_retrieve_body($response);
        $result['body_length'] = strlen($body);
        
        if ($result['http_code'] === 200) {
            $data = json_decode($body, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $result['success'] = true;
                $result['facilities_count'] = count($data);
                $result['sample_ids'] = array_slice(array_column($data, 'id'), 0, 5);
            } else {
                $result['json_error'] = json_last_error_msg();
                $result['body_preview'] = substr($body, 0, 200);
            }
        } else {
            $result['body_preview'] = substr($body, 0, 500);
        }
        
        return $result;
    }
}
