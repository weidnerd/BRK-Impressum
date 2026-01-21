<?php
/**
 * BRK Impressum Tools - Super Admin Backend
 * 
 * Verwaltungsseite für Cache, Fehlerdiagnose und API-Tests
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRK_Impressum_Tools {
    
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
        // Nur in Multisite und nur im Network-Admin
        if (is_multisite()) {
            add_action('network_admin_menu', array($this, 'add_tools_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
            add_action('network_admin_edit_brk_clear_cache', array($this, 'handle_clear_cache'));
            add_action('network_admin_edit_brk_refresh_cache', array($this, 'handle_refresh_cache'));
        }
    }
    
    /**
     * Admin-Menü hinzufügen
     */
    public function add_tools_menu() {
        add_submenu_page(
            'settings.php',
            'BRK Impressum Tools',
            'BRK Impressum',
            'manage_network_options',
            'brk-impressum-tools',
            array($this, 'render_tools_page')
        );
    }
    
    /**
     * Assets laden
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'settings_page_brk-impressum-tools') {
            return;
        }
        
        wp_enqueue_style(
            'brk-impressum-tools',
            BRK_IMPRESSUM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BRK_IMPRESSUM_VERSION
        );
        
        wp_enqueue_script(
            'brk-impressum-tools',
            BRK_IMPRESSUM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            BRK_IMPRESSUM_VERSION,
            true
        );
        
        wp_localize_script('brk-impressum-tools', 'brkImpressum', array(
            'restUrl' => rest_url('brk-impressum/v1/'),
            'restNonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    /**
     * Cache löschen (Network Admin Action)
     */
    public function handle_clear_cache() {
        check_admin_referer('brk_clear_cache');
        
        delete_transient('brk_impressum_facilities');
        delete_transient('brk_impressum_last_error');
        
        wp_safe_redirect(add_query_arg(array(
            'page' => 'brk-impressum-tools',
            'message' => 'cache_cleared'
        ), network_admin_url('settings.php')));
        exit;
    }
    
    /**
     * Cache neu laden (Network Admin Action)
     */
    public function handle_refresh_cache() {
        check_admin_referer('brk_refresh_cache');
        
        delete_transient('brk_impressum_facilities');
        delete_transient('brk_impressum_last_error');
        
        $loader = BRK_Facilities_Loader::get_instance();
        $loader->refresh_cache();
        
        wp_safe_redirect(add_query_arg(array(
            'page' => 'brk-impressum-tools',
            'message' => 'cache_refreshed'
        ), network_admin_url('settings.php')));
        exit;
    }
    
    /**
     * Tools-Seite rendern
     */
    public function render_tools_page() {
        if (!current_user_can('manage_network_options')) {
            wp_die(__('Sie haben keine Berechtigung, auf diese Seite zuzugreifen.'));
        }
        
        $loader = BRK_Facilities_Loader::get_instance();
        $facilities = $loader->get_facilities();
        $last_error = $loader->get_last_error_info();
        
        // Cache-Informationen
        $cache_data = get_transient('brk_impressum_facilities');
        $cache_exists = $cache_data !== false;
        
        ?>
        <div class="wrap">
            <h1>🔧 BRK Impressum Tools</h1>
            <p class="description">Technische Verwaltung für Cache, API-Diagnose und Fehlerbehebung</p>
            
            <?php if (isset($_GET['message'])): ?>
                <?php if ($_GET['message'] === 'cache_cleared'): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><strong>✓ Cache gelöscht!</strong> Der Cache wurde erfolgreich geleert.</p>
                    </div>
                <?php elseif ($_GET['message'] === 'cache_refreshed'): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><strong>✓ Cache aktualisiert!</strong> Die Daten wurden neu von der API geladen.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="brk-tools-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                
                <!-- Cache-Verwaltung -->
                <div class="card">
                    <h2>💾 Cache-Verwaltung</h2>
                    
                    <table class="widefat">
                        <tr>
                            <th>Cache-Status:</th>
                            <td>
                                <?php if ($cache_exists): ?>
                                    <span style="color: #46b450;">✓ Aktiv</span>
                                <?php else: ?>
                                    <span style="color: #d63638;">✗ Leer</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Gespeicherte Facilities:</th>
                            <td><?php echo is_array($cache_data) ? count($cache_data) : 0; ?></td>
                        </tr>
                        <tr>
                            <th>Cache-Dauer:</th>
                            <td>24 Stunden</td>
                        </tr>
                    </table>
                    
                    <p style="margin-top: 15px;">
                        <a href="<?php echo wp_nonce_url(network_admin_url('edit.php?action=brk_clear_cache'), 'brk_clear_cache'); ?>" 
                           class="button button-secondary">
                            🗑️ Cache löschen
                        </a>
                        <a href="<?php echo wp_nonce_url(network_admin_url('edit.php?action=brk_refresh_cache'), 'brk_refresh_cache'); ?>" 
                           class="button button-primary" style="margin-left: 10px;">
                            🔄 Cache neu laden
                        </a>
                    </p>
                </div>
                
                <!-- API-Status -->
                <div class="card">
                    <h2>🌐 API-Status</h2>
                    
                    <table class="widefat">
                        <tr>
                            <th>API-URL:</th>
                            <td><code><?php echo esc_html(BRK_IMPRESSUM_FACILITIES_URL); ?></code></td>
                        </tr>
                        <tr>
                            <th>Verbindungsstatus:</th>
                            <td>
                                <?php if (!$last_error): ?>
                                    <span style="color: #46b450;">✓ Verbunden</span>
                                <?php else: ?>
                                    <span style="color: #d63638;">✗ Fehler</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Geladene Facilities:</th>
                            <td><?php echo is_array($facilities) ? count($facilities) : 0; ?></td>
                        </tr>
                    </table>
                    
                    <p style="margin-top: 15px;">
                        <button type="button" class="button button-primary" id="brk-test-api">
                            🧪 Verbindungstest durchführen
                        </button>
                    </p>
                    
                    <div id="brk-test-results" style="margin-top: 15px;"></div>
                </div>
            </div>
            
            <!-- Fehlerdiagnose -->
            <?php if ($last_error && is_array($last_error)): ?>
            <div class="card" style="margin-top: 20px; border-left: 4px solid #d63638;">
                <h2>⚠️ Fehlerdiagnose</h2>
                
                <table class="widefat">
                    <tr>
                        <th style="width: 200px;">Fehlertyp:</th>
                        <td><strong><?php echo esc_html($last_error['error_type'] ?? 'Unbekannt'); ?></strong></td>
                    </tr>
                    <?php if (isset($last_error['error_message'])): ?>
                    <tr>
                        <th>Fehlermeldung:</th>
                        <td><?php echo esc_html($last_error['error_message']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($last_error['http_code'])): ?>
                    <tr>
                        <th>HTTP-Statuscode:</th>
                        <td><?php echo esc_html($last_error['http_code']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($last_error['timestamp'])): ?>
                    <tr>
                        <th>Zeitstempel:</th>
                        <td><?php echo esc_html($last_error['timestamp']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <?php if (isset($last_error['response_body_preview']) || isset($last_error['body_preview'])): ?>
                <details style="margin-top: 15px;">
                    <summary style="cursor: pointer; font-weight: bold;">Antwort-Details anzeigen</summary>
                    <pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px; margin-top: 10px;"><?php 
                        echo esc_html($last_error['response_body_preview'] ?? $last_error['body_preview']); 
                    ?></pre>
                </details>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Facilities-Liste -->
            <div class="card" style="margin-top: 20px;">
                <h2>📋 Geladene Facilities (<?php echo is_array($facilities) ? count($facilities) : 0; ?>)</h2>
                
                <?php if (is_array($facilities) && !empty($facilities)): ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Ebene</th>
                            <th>Ort</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($facilities, 0, 20) as $facility): ?>
                        <tr>
                            <td><code><?php echo esc_html($facility['id']); ?></code></td>
                            <td><?php echo esc_html($facility['name']); ?></td>
                            <td><?php echo esc_html($facility['ebene'] ?? '-'); ?></td>
                            <td><?php echo esc_html($facility['anschrift']['ort'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($facilities) > 20): ?>
                <p style="margin-top: 10px; color: #666;">
                    <em>Es werden die ersten 20 von <?php echo count($facilities); ?> Einträgen angezeigt.</em>
                </p>
                <?php endif; ?>
                <?php else: ?>
                <p>Keine Facilities geladen.</p>
                <?php endif; ?>
            </div>
            
            <!-- System-Informationen -->
            <div class="card" style="margin-top: 20px;">
                <h2>ℹ️ System-Informationen</h2>
                
                <table class="widefat">
                    <tr>
                        <th style="width: 200px;">WordPress-Version:</th>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <th>PHP-Version:</th>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th>Plugin-Version:</th>
                        <td><?php echo BRK_IMPRESSUM_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th>Multisite:</th>
                        <td><?php echo is_multisite() ? 'Ja' : 'Nein'; ?></td>
                    </tr>
                    <tr>
                        <th>WP_DEBUG:</th>
                        <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Aktiv' : 'Inaktiv'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
}
