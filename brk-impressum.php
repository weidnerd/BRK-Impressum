<?php
/**
 * Plugin Name: BRK Impressum Generator
 * Plugin URI: https://github.com/weidnerd/BRK-Impressum
 * Description: Automatische Impressum-Generierung für WordPress Multisite-Unterseiten basierend auf BRK Facilities-Daten
 * Author: Daniel Weidner, AG IT der Wasserwacht Bayern (+AI)
 * Author URI: https://minicms.wasserwacht.de/
 * Version: 1.2.3
 * Network: true
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('BRK_IMPRESSUM_VERSION', '1.2.3');
define('BRK_IMPRESSUM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BRK_IMPRESSUM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BRK_IMPRESSUM_PLUGIN_FILE', __FILE__);
define('BRK_IMPRESSUM_FACILITIES_URL', 'https://mein.brk.de/data/facilities.json');

/**
 * Hauptklasse für das BRK Impressum Plugin
 */
class BRK_Impressum {
    
    /**
     * Singleton-Instanz
     */
    private static $instance = null;
    
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
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Abhängigkeiten laden
     */
    private function load_dependencies() {
        require_once BRK_IMPRESSUM_PLUGIN_DIR . 'includes/class-brk-facilities-loader.php';
        require_once BRK_IMPRESSUM_PLUGIN_DIR . 'includes/class-brk-impressum-generator.php';
        require_once BRK_IMPRESSUM_PLUGIN_DIR . 'includes/class-brk-impressum-settings.php';
        require_once BRK_IMPRESSUM_PLUGIN_DIR . 'admin/class-brk-impressum-admin.php';
        require_once BRK_IMPRESSUM_PLUGIN_DIR . 'admin/class-brk-impressum-tools.php';
    }
    
    /**
     * WordPress-Hooks initialisieren
     */
    private function init_hooks() {
        // Admin-Interface initialisieren
        if (is_admin()) {
            BRK_Impressum_Admin::get_instance();
            BRK_Impressum_Tools::get_instance();
        }
        
        // Activation Hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Deactivation Hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Shortcode registrieren
        add_shortcode('brk_impressum', array($this, 'impressum_shortcode'));
        
        // REST API Endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Frontend-Styles laden
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Cron-Job für regelmäßige Datenaktualisierung
        add_action('brk_impressum_daily_update', array($this, 'refresh_facilities_cache'));
        
        // Hook nach Cache-Refresh
        add_action('brk_impressum_cache_refreshed', array($this, 'update_all_impressum_pages'));

        // Sicherstellen, dass der Update-Cron korrekt geplant ist
        $this->ensure_update_cron_schedule();
    }

    /**
     * Sicherstellen, dass der Cron-Job täglich geplant ist
     */
    private function ensure_update_cron_schedule() {
        $timestamp = wp_next_scheduled('brk_impressum_daily_update');

        if (!$timestamp) {
            wp_schedule_event(time(), 'daily', 'brk_impressum_daily_update');
            return;
        }

        if (function_exists('wp_get_scheduled_event')) {
            $event = wp_get_scheduled_event('brk_impressum_daily_update');
            if ($event && isset($event->schedule) && $event->schedule !== 'daily') {
                wp_unschedule_event($timestamp, 'brk_impressum_daily_update');
                wp_schedule_event(time(), 'daily', 'brk_impressum_daily_update');
            }
        }
    }
    
    /**
     * Frontend-Assets laden
     * 
     * Deaktiviert: Das Theme übernimmt die Darstellung des Impressums.
     * Bei Bedarf wieder aktivieren, um BRK-spezifische Styles zu laden.
     */
    public function enqueue_frontend_assets() {
        // CSS-Laden deaktiviert - Theme-Styles werden verwendet
        // if (is_page('impressum') || has_shortcode(get_post()->post_content ?? '', 'brk_impressum')) {
        //     wp_enqueue_style(
        //         'brk-impressum-frontend',
        //         BRK_IMPRESSUM_PLUGIN_URL . 'assets/css/frontend.css',
        //         array(),
        //         BRK_IMPRESSUM_VERSION
        //     );
        // }
    }
    
    /**
     * Plugin-Aktivierung
     */
    public function activate() {
        // Optionen initialisieren falls nicht vorhanden
        if (is_multisite()) {
            $sites = get_sites();
            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);
                $this->init_site_options();
                restore_current_blog();
            }
        } else {
            $this->init_site_options();
        }
        
        // Facilities-Cache initial laden
        BRK_Facilities_Loader::get_instance()->refresh_cache();
        
        // Regelmäßigen Cron-Job registrieren
        if (!wp_next_scheduled('brk_impressum_daily_update')) {
            wp_schedule_event(time(), 'daily', 'brk_impressum_daily_update');
        }
    }
    
    /**
     * Site-Optionen initialisieren
     */
    private function init_site_options() {
        if (!get_option('brk_impressum_settings')) {
            add_option('brk_impressum_settings', array(
                'facility_id' => '',
                'responsible_name' => '',
                'responsible_function' => '',
                'responsible_email' => '',
                'last_updated' => current_time('mysql')
            ));
        }
    }
    
    /**
     * Plugin-Deaktivierung
     */
    public function deactivate() {
        // Cache löschen
        delete_transient('brk_facilities_data');
        delete_transient('brk_facilities_hash');
        
        // Cron-Job entfernen
        $timestamp = wp_next_scheduled('brk_impressum_daily_update');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'brk_impressum_daily_update');
        }
    }

    /**
     * Facilities-Cache aktualisieren
     *
     * Hinweis: Die Aktualisierung der Impressum-Seiten erfolgt über den
     * Hook 'brk_impressum_cache_refreshed' nur bei echten Datenänderungen.
     */
    public function refresh_facilities_cache() {
        BRK_Facilities_Loader::get_instance()->refresh_cache();
    }
    
    /**
     * REST API Routen registrieren
     */
    public function register_rest_routes() {
        register_rest_route('brk-impressum/v1', '/facilities', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_facilities_endpoint'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        register_rest_route('brk-impressum/v1', '/preview', array(
            'methods' => 'POST',
            'callback' => array($this, 'preview_impressum_endpoint'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        register_rest_route('brk-impressum/v1', '/save', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_impressum_endpoint'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        register_rest_route('brk-impressum/v1', '/test-connection', array(
            'methods' => 'GET',
            'callback' => array($this, 'test_connection_endpoint'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }
    
    /**
     * REST: API-Verbindung testen
     */
    public function test_connection_endpoint($request) {
        $loader = BRK_Facilities_Loader::get_instance();
        $result = $loader->test_api_connection();
        
        return rest_ensure_response($result);
    }
    
    /**
     * REST: Facilities abrufen
     */
    public function get_facilities_endpoint($request) {
        $loader = BRK_Facilities_Loader::get_instance();
        
        // Wenn refresh=true, Cache löschen und neu laden
        if ($request->get_param('refresh') === 'true' || $request->get_param('refresh') === true) {
            delete_transient('brk_impressum_facilities');
            delete_transient('brk_impressum_last_error');
            $facilities = $loader->refresh_cache();
        } else {
            $facilities = $loader->get_facilities();
        }
        
        if (is_wp_error($facilities)) {
            return new WP_Error('facilities_error', $facilities->get_error_message(), array('status' => 500));
        }
        
        return rest_ensure_response($facilities);
    }
    
    /**
     * REST: Impressum-Vorschau
     */
    public function preview_impressum_endpoint($request) {
        $params = $request->get_json_params();
        
        $generator = BRK_Impressum_Generator::get_instance();
        $html = $generator->generate_impressum(
            $params['facility_id'],
            $params['responsible_name'],
            $params['responsible_email']
        );
        
        if (is_wp_error($html)) {
            return new WP_Error('generation_error', $html->get_error_message(), array('status' => 500));
        }
        
        return rest_ensure_response(array('html' => $html));
    }
    
    /**
     * REST: Impressum speichern
     */
    public function save_impressum_endpoint($request) {
        $params = $request->get_json_params();
        
        // Einstellungen speichern
        $settings = array(
            'facility_id' => sanitize_text_field($params['facility_id']),
            'responsible_name' => sanitize_text_field($params['responsible_name']),
            'responsible_email' => sanitize_email($params['responsible_email']),
            'last_updated' => current_time('mysql')
        );
        
        update_option('brk_impressum_settings', $settings);
        
        // Impressum-Seite erstellen oder aktualisieren
        $page_id = $this->create_or_update_impressum_page($params);
        
        if (is_wp_error($page_id)) {
            return new WP_Error('save_error', $page_id->get_error_message(), array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'page_id' => $page_id,
            'message' => 'Impressum erfolgreich gespeichert'
        ));
    }
    
    /**
     * Impressum-Seite erstellen oder aktualisieren
     */
    private function create_or_update_impressum_page($params) {
        $generator = BRK_Impressum_Generator::get_instance();
        $content = $generator->generate_impressum(
            $params['facility_id'],
            $params['responsible_name'],
            $params['responsible_email']
        );
        
        if (is_wp_error($content)) {
            return $content;
        }
        
        // Prüfen, ob bereits eine Impressum-Seite existiert
        $page = get_page_by_path('impressum');
        
        $page_data = array(
            'post_title' => 'Impressum',
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'impressum'
        );
        
        if ($page) {
            // Seite aktualisieren
            $page_data['ID'] = $page->ID;
            $page_id = wp_update_post($page_data);
        } else {
            // Neue Seite erstellen
            $page_id = wp_insert_post($page_data);
        }
        
        return $page_id;
    }
    
    /**
     * Shortcode für Impressum
     */
    public function impressum_shortcode($atts) {
        $settings = get_option('brk_impressum_settings');
        
        if (empty($settings['facility_id'])) {
            return '<p><em>Impressum wurde noch nicht konfiguriert.</em></p>';
        }
        
        // Sicherstellen, dass facility_id als String vorliegt
        $facility_id = strval($settings['facility_id']);
        
        $generator = BRK_Impressum_Generator::get_instance();
        $html = $generator->generate_impressum(
            $facility_id,
            $settings['responsible_name'],
            $settings['responsible_email']
        );
        
        if (is_wp_error($html)) {
            return '<p><em>Fehler beim Laden des Impressums.</em></p>';
        }
        
        return $html;
    }
    
    /**
     * Alle Impressum-Seiten im Netzwerk aktualisieren
     * Wird täglich via Cron und nach Cache-Refresh ausgeführt
     */
    public function update_all_impressum_pages() {
        if (is_multisite()) {
            // Alle Sites im Netzwerk durchgehen
            $sites = get_sites(array('number' => 1000)); // Max 1000 Sites
            
            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);
                $this->update_single_impressum_page();
                restore_current_blog();
            }
        } else {
            // Einzelne WordPress-Installation
            $this->update_single_impressum_page();
        }
    }
    
    /**
     * Einzelne Impressum-Seite aktualisieren
     */
    private function update_single_impressum_page() {
        $settings = get_option('brk_impressum_settings');
        
        // Nur aktualisieren wenn Impressum konfiguriert ist
        if (empty($settings['facility_id'])) {
            return;
        }
        
        // Prüfen ob Impressum-Seite existiert
        $page = get_page_by_path('impressum');
        if (!$page) {
            return; // Keine Seite vorhanden, nichts zu aktualisieren
        }
        
        // Seite aktualisieren
        $params = array(
            'facility_id' => $settings['facility_id'],
            'responsible_name' => $settings['responsible_name'],
            'responsible_email' => $settings['responsible_email']
        );
        
        $this->create_or_update_impressum_page($params);
    }
}

/**
 * Plugin initialisieren
 */
function brk_impressum_init() {
    return BRK_Impressum::get_instance();
}

// Plugin starten
add_action('plugins_loaded', 'brk_impressum_init');
