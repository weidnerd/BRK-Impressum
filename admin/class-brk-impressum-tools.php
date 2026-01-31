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
        
        delete_transient('brk_facilities_data');
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
        
        delete_transient('brk_facilities_data');
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
        
        // Debug-Ansicht für spezifische Site
        if (isset($_GET['debug_site'])) {
            $this->render_debug_view(intval($_GET['debug_site']));
            return;
        }
        
        $loader = BRK_Facilities_Loader::get_instance();
        $facilities = $loader->get_facilities();
        $last_error = $loader->get_last_error_info();
        
        // Cache-Informationen
        $cache_data = get_transient('brk_facilities_data');
        $cache_exists = $cache_data !== false;
        
        ?>
        <div class="wrap" style="max-width: none;">
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
            
            <!-- 2-spaltiger Bereich: Cache-Verwaltung & API-Status -->
            <div class="brk-tools-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 20px;">
                
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
                
            </div><!-- Ende 2-spaltiger Bereich -->
            
            <!-- Einspaltiger Bereich: Plugin-Verwendung im Netzwerk -->
            <div class="card" style="margin-top: 25px; width: 100% !important; max-width: none !important; clear: both; box-sizing: border-box;">
                <h2>🌐 Plugin-Verwendung im Netzwerk</h2>
                <?php $this->render_network_usage(); ?>
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
        </div>
        <?php
    }
    
    /**
     * Debug-Ansicht für eine spezifische Site
     */
    private function render_debug_view($site_id) {
        $site = get_site($site_id);
        if (!$site) {
            echo '<div class="wrap"><h1>Site nicht gefunden</h1></div>';
            return;
        }
        
        switch_to_blog($site_id);
        
        $settings = get_option('brk_impressum_settings');
        $impressum_page = get_page_by_path('impressum');
        $expected_url = $impressum_page ? get_permalink($impressum_page->ID) : '';
        $expected_path = $expected_url ? trim(parse_url($expected_url, PHP_URL_PATH), '/') : '';
        
        ?>
        <div class="wrap" style="max-width: none;">
            <h1>🐛 Debug: <?php echo esc_html(get_bloginfo('name')); ?></h1>
            <p>
                <a href="<?php echo esc_url(network_admin_url('settings.php?page=brk-impressum-tools')); ?>" class="button">
                    ← Zurück zur Übersicht
                </a>
            </p>
            
            <!-- Erwartete Daten -->
            <div class="card" style="margin-top: 20px;">
                <h2>📋 Erwartete Daten</h2>
                <table class="widefat">
                    <tr>
                        <th style="width: 250px;">Site URL:</th>
                        <td><code><?php echo esc_html(get_site_url()); ?></code></td>
                    </tr>
                    <tr>
                        <th>Impressum-Seite existiert:</th>
                        <td><?php echo $impressum_page ? '✓ Ja (ID: ' . $impressum_page->ID . ')' : '✗ Nein'; ?></td>
                    </tr>
                    <tr>
                        <th>Erwartete URL:</th>
                        <td><code><?php echo esc_html($expected_url); ?></code></td>
                    </tr>
                    <tr>
                        <th>Erwarteter Pfad (normalisiert):</th>
                        <td><code><?php echo esc_html($expected_path); ?></code></td>
                    </tr>
                </table>
            </div>
            
            <!-- YooTheme Builder Widgets -->
            <div class="card" style="margin-top: 20px;">
                <h2>🎨 YooTheme Builder Widgets</h2>
                <?php
                $builder_widgets = get_option('widget_builderwidget', array());
                $sidebars_widgets = get_option('sidebars_widgets', array());
                ?>
                
                <h3>Sidebars mit Builder Widgets:</h3>
                <table class="widefat" style="margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th>Sidebar</th>
                            <th>Widgets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sidebars_widgets as $sidebar_id => $widgets): ?>
                            <?php if (is_array($widgets)): ?>
                                <?php foreach ($widgets as $widget_id): ?>
                                    <?php if (strpos($widget_id, 'builderwidget') === 0): ?>
                                        <tr>
                                            <td><code><?php echo esc_html($sidebar_id); ?></code></td>
                                            <td><code><?php echo esc_html($widget_id); ?></code></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <h3>Builder Widget Daten:</h3>
                <?php if (empty($builder_widgets)): ?>
                    <p>Keine Builder Widgets gefunden.</p>
                <?php else: ?>
                    <?php foreach ($builder_widgets as $widget_id => $widget): ?>
                        <?php if (is_array($widget) && isset($widget['content'])): ?>
                            <div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-left: 4px solid #2271b1;">
                                <h4>Widget #<?php echo esc_html($widget_id); ?>: <?php echo esc_html($widget['title'] ?? 'Ohne Titel'); ?></h4>
                                <details>
                                    <summary style="cursor: pointer; padding: 10px; background: #fff; margin-top: 10px;">
                                        JSON-Content anzeigen
                                    </summary>
                                    <pre style="background: #fff; padding: 10px; overflow: auto; max-height: 400px; margin-top: 10px;"><?php 
                                        $decoded = json_decode($widget['content'], true);
                                        if (json_last_error() === JSON_ERROR_NONE) {
                                            print_r($decoded);
                                        } else {
                                            echo esc_html($widget['content']);
                                        }
                                    ?></pre>
                                </details>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Link-Prüfung Ergebnis -->
            <div class="card" style="margin-top: 20px; border-left: 4px solid #2271b1;">
                <h2>✅ Link-Prüfung Ergebnis</h2>
                <?php
                $footer_link_status = $this->check_footer_impressum_link($impressum_page);
                ?>
                <table class="widefat">
                    <tr>
                        <th style="width: 250px;">Status:</th>
                        <td>
                            <strong style="font-size: 16px;">
                                <?php
                                if ($footer_link_status === 'correct') {
                                    echo '<span style="color: #46b450;">✓ CORRECT</span> - Link gefunden und korrekt';
                                } elseif ($footer_link_status === 'wrong') {
                                    echo '<span style="color: #d63638;">✗ WRONG</span> - Link gefunden, aber falsche URL';
                                } elseif ($footer_link_status === 'missing') {
                                    echo '<span style="color: #999;">○ MISSING</span> - Kein Impressum-Link gefunden';
                                } else {
                                    echo '<span style="color: #999;">- NO PAGE</span> - Keine Impressum-Seite';
                                }
                                ?>
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
        
        restore_current_blog();
    }
    
    /**
     * Prüfe ob Footer-Widgets einen korrekten Impressum-Link enthalten
     */
    private function check_footer_impressum_link($impressum_page) {
        if (!$impressum_page) {
            return 'no_page';
        }
        
        $expected_url = get_permalink($impressum_page->ID);
        $expected_path = trim(parse_url($expected_url, PHP_URL_PATH), '/');
        
        // Nur "bottom" Sidebar prüfen
        $sidebars_widgets = get_option('sidebars_widgets', array());
        $bottom_widgets = isset($sidebars_widgets['bottom']) ? $sidebars_widgets['bottom'] : array();
        
        if (!is_array($bottom_widgets)) {
            return 'missing';
        }
        
        // Prüfe nur YooTheme Builder Widgets in der "bottom" Sidebar
        $builder_widgets = get_option('widget_builderwidget', array());
        
        foreach ($bottom_widgets as $widget_id) {
            if (strpos($widget_id, 'builderwidget-') === 0) {
                // Extrahiere die Widget-Nummer (z.B. "builderwidget-2" -> 2)
                $widget_number = (int) str_replace('builderwidget-', '', $widget_id);
                
                if (isset($builder_widgets[$widget_number]) && is_array($builder_widgets[$widget_number])) {
                    $widget = $builder_widgets[$widget_number];
                    
                    if (isset($widget['content'])) {
                        // Content ist JSON
                        $decoded = json_decode($widget['content'], true);
                        if (is_array($decoded)) {
                            $result = $this->search_yootheme_data($decoded, $expected_path);
                            if ($result !== null) {
                                return $result;
                            }
                        }
                    }
                }
            }
        }
        
        return 'missing';
    }
    
    /**
     * Rekursive Suche in YooTheme-Datenstrukturen
     */
    private function search_yootheme_data($data, $expected_path) {
        if (!is_array($data)) {
            return null;
        }
        
        foreach ($data as $key => $value) {
            // Suche nach Link-Feldern
            if (($key === 'link' || $key === 'url' || $key === 'href') && is_string($value)) {
                if (stripos($value, 'impressum') !== false) {
                    $link_path = trim(parse_url($value, PHP_URL_PATH), '/');
                    return ($link_path === $expected_path) ? 'correct' : 'wrong';
                }
            }
            
            // Rekursiv in Arrays/Objekten suchen
            if (is_array($value)) {
                $result = $this->search_yootheme_data($value, $expected_path);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Netzwerk-Nutzung anzeigen
     */
    private function render_network_usage() {
        if (!is_multisite()) {
            echo '<p>Dieses Feature ist nur in Multisite-Installationen verfügbar.</p>';
            return;
        }
        
        $sites = get_sites(array('number' => 1000));
        $usage_data = array();
        
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            
            $settings = get_option('brk_impressum_settings');
            $impressum_page = get_page_by_path('impressum');
            
            $is_configured = !empty($settings['facility_id']);
            $has_page = $impressum_page ? true : false;
            
            // Site-Info speichern (auch für inaktive Sites)
            $site_name = get_bloginfo('name');
            $site_url = get_site_url();
            
            // Prüfe Footer-Widgets auf Impressum-Link
            $footer_link_status = $this->check_footer_impressum_link($impressum_page);
            
            $usage_data[] = array(
                'site_id' => $site->blog_id,
                'site_name' => $site_name ?: 'Unbenannte Site',
                'site_url' => $site_url ?: home_url(),
                'facility_id' => $settings['facility_id'] ?? '',
                'responsible_name' => $settings['responsible_name'] ?? '',
                'responsible_email' => $settings['responsible_email'] ?? '',
                'last_updated' => $settings['last_updated'] ?? '',
                'page_exists' => $has_page,
                'page_url' => $impressum_page ? get_permalink($impressum_page->ID) : '',
                'is_configured' => $is_configured,
                'status' => $is_configured ? 'active' : 'inactive',
                'footer_link' => $footer_link_status,
            );
            
            restore_current_blog();
        }
        
        // Sortieren: Aktive zuerst
        usort($usage_data, function($a, $b) {
            if ($a['status'] === $b['status']) {
                return strcmp($a['site_name'], $b['site_name']);
            }
            return $a['status'] === 'active' ? -1 : 1;
        });
        
        $active_count = count(array_filter($usage_data, function($d) { return $d['is_configured']; }));
        ?>
        
        <p style="margin-bottom: 15px;">
            <strong><?php echo $active_count; ?> von <?php echo count($sites); ?> Unterseiten</strong> verwenden das Plugin.
        </p>
        
        <table class="widefat striped" style="width: 100%; table-layout: auto;">
            <thead>
                <tr>
                    <th style="width: 60px;">Status</th>
                    <th>Site</th>
                    <th style="width: 100px;">Facility ID</th>
                    <th>Verantwortlicher</th>
                    <th style="width: 120px;">Impressum-Seite</th>
                    <th style="width: 100px;">Footer-Link</th>
                    <th style="width: 140px;">Letzte Aktualisierung</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usage_data as $data): ?>
                <tr style="<?php echo !$data['is_configured'] ? 'opacity: 0.6;' : ''; ?>">
                    <td style="text-align: center;">
                        <?php if ($data['is_configured']): ?>
                            <span style="color: #46b450; font-size: 18px;" title="Plugin aktiv">✓</span>
                        <?php else: ?>
                            <span style="color: #d63638; font-size: 18px;" title="Plugin nicht konfiguriert">○</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($data['site_name']); ?></strong><br>
                        <small style="color: #666;">
                            <a href="<?php echo esc_url($data['site_url']); ?>" target="_blank">
                                <?php echo esc_html(str_replace(array('http://', 'https://'), '', $data['site_url'])); ?>
                            </a>
                        </small>
                    </td>
                    <td>
                        <?php if ($data['facility_id']): ?>
                            <code><?php echo esc_html($data['facility_id']); ?></code>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($data['responsible_name']): ?>
                            <?php echo esc_html($data['responsible_name']); ?><br>
                            <?php if ($data['responsible_email']): ?>
                                <small style="color: #666;">
                                    <a href="mailto:<?php echo esc_attr($data['responsible_email']); ?>">
                                        <?php echo esc_html($data['responsible_email']); ?>
                                    </a>
                                </small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($data['page_exists']): ?>
                            <span style="color: #46b450;">✓ Erstellt</span><br>
                            <small>
                                <a href="<?php echo esc_url($data['page_url']); ?>" target="_blank">Seite anzeigen</a>
                            </small>
                        <?php else: ?>
                            <span style="color: #d63638;">✗ Nicht erstellt</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($data['footer_link'] === 'correct'): ?>
                            <span style="color: #46b450; font-size: 16px;" title="Footer-Link korrekt">✓</span>
                        <?php elseif ($data['footer_link'] === 'wrong'): ?>
                            <span style="color: #d63638; font-size: 16px;" title="Footer-Link falsch">✗</span>
                        <?php elseif ($data['footer_link'] === 'missing'): ?>
                            <span style="color: #999; font-size: 16px;" title="Kein Impressum-Link">○</span>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                        <br>
                        <small>
                            <a href="<?php echo esc_url(network_admin_url('settings.php?page=brk-impressum-tools&debug_site=' . $data['site_id'])); ?>" 
                               style="color: #2271b1; text-decoration: none;">
                                Debug
                            </a>
                        </small>
                    </td>
                    <td>
                        <?php if ($data['last_updated']): ?>
                            <?php 
                            $updated = strtotime($data['last_updated']);
                            echo esc_html(date_i18n('d.m.Y H:i', $updated));
                            ?>
                            <br>
                            <small style="color: #666;">
                                <?php echo human_time_diff($updated, current_time('timestamp')); ?> her
                            </small>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php
    }
}
