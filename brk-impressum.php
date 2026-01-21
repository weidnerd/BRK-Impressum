<?php
/**
 * Plugin Name: BRK Impressum Generator
 * Plugin URI: https://github.com/weidnerd/BRK-Impressum
 * Description: Automatische Impressum-Generierung für WordPress Multisite-Unterseiten basierend auf BRK Facilities-Daten
 * Version: 1.0.0
 * Author: BRK
 * Author URI: https://brk.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: true
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('BRK_IMPRESSUM_VERSION', '1.0.0');
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
    }
    
    /**
     * Frontend-Assets laden
     */
    public function enqueue_frontend_assets() {
        // Nur laden wenn Shortcode oder Impressum-Seite
        if (is_page('impressum') || has_shortcode(get_post()->post_content ?? '', 'brk_impressum')) {
            wp_enqueue_style(
                'brk-impressum-frontend',
                BRK_IMPRESSUM_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                BRK_IMPRESSUM_VERSION
            );
        }
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
            $params['responsible_function'],
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
        
        // Debug-Logging
        error_log('BRK Impressum: save_impressum_endpoint called');
        error_log('BRK Impressum: Request params: ' . print_r($params, true));
        error_log('BRK Impressum: facility_id value: [' . $params['facility_id'] . '] type: ' . gettype($params['facility_id']));
        
        // Einstellungen speichern
        $settings = array(
            'facility_id' => sanitize_text_field($params['facility_id']),
            'responsible_name' => sanitize_text_field($params['responsible_name']),
            'responsible_function' => sanitize_text_field($params['responsible_function']),
            'responsible_email' => sanitize_email($params['responsible_email']),
            'last_updated' => current_time('mysql')
        );
        
        error_log('BRK Impressum: Saving settings: ' . print_r($settings, true));
        update_option('brk_impressum_settings', $settings);
        
        // Impressum-Seite erstellen oder aktualisieren
        $page_id = $this->create_or_update_impressum_page($params);
        
        if (is_wp_error($page_id)) {
            return new WP_Error('save_error', $page_id->get_error_message(), array('status' => 500));
        }
        
        error_log('BRK Impressum: Page saved with ID: ' . $page_id);
        
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
        // Debug-Logging
        error_log('BRK Impressum: create_or_update_impressum_page called with facility_id: [' . $params['facility_id'] . ']');
        
        $generator = BRK_Impressum_Generator::get_instance();
        $content = $generator->generate_impressum(
            $params['facility_id'],
            $params['responsible_name'],
            $params['responsible_function'],
            $params['responsible_email']
        );
        
        if (is_wp_error($content)) {
            return $content;
        }
        
        // Debug: Prüfe den generierten Inhalt
        error_log('BRK Impressum: Generated content length: ' . strlen($content) . ' bytes');
        
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
            $settings['responsible_function'],
            $settings['responsible_email']
        );
        
        if (is_wp_error($html)) {
            return '<p><em>Fehler beim Laden des Impressums.</em></p>';
        }
        
        return $html;
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
