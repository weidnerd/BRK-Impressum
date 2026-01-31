<?php
/**
 * BRK Impressum Admin Interface
 * 
 * Admin-Bereich für das Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRK_Impressum_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_brk_update_footer_link', array($this, 'ajax_update_footer_link'));
    }
    
    /**
     * Admin-Menü hinzufügen
     */
    public function add_admin_menu() {
        add_options_page(
            'BRK Impressum',
            'BRK Impressum',
            'manage_options',
            'brk-impressum',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Admin-Assets laden
     */
    public function enqueue_admin_assets($hook) {
        // Nur auf unserer Plugin-Seite laden
        if ($hook !== 'settings_page_brk-impressum') {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'brk-impressum-admin',
            BRK_IMPRESSUM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BRK_IMPRESSUM_VERSION . '.' . time()
        );
        
        // JavaScript
        wp_enqueue_script(
            'brk-impressum-admin',
            BRK_IMPRESSUM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-api'),
            BRK_IMPRESSUM_VERSION . '.' . time(),
            true
        );
        
        // Lokalisierung für JavaScript
        wp_localize_script('brk-impressum-admin', 'brkImpressum', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('brk_impressum_nonce'),
            'restUrl' => rest_url('brk-impressum/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'strings' => array(
                'loading' => __('Laden...', 'brk-impressum'),
                'error' => __('Ein Fehler ist aufgetreten.', 'brk-impressum'),
                'success' => __('Erfolgreich gespeichert!', 'brk-impressum'),
                'preview' => __('Vorschau wird generiert...', 'brk-impressum'),
                'saved' => __('Impressum wurde gespeichert und Seite erstellt/aktualisiert.', 'brk-impressum')
            )
        ));
    }
    
    /**
     * Einstellungen registrieren
     */
    public function register_settings() {
        register_setting('brk_impressum_settings_group', 'brk_impressum_settings');
    }
    
    /**
     * Admin-Seite rendern
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben keine Berechtigung, auf diese Seite zuzugreifen.'));
        }
        
        $settings = BRK_Impressum_Settings::get_instance()->get_settings();
        $loader = BRK_Facilities_Loader::get_instance();
        $facilities = $loader->get_facilities_for_select();
        
        ?>
        <div class="wrap brk-impressum-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="brk-impressum-container">
                <div class="brk-impressum-form-section">
                    <div class="card">
                        <h2>BRK-Impressum-Konfiguration</h2>
                        
                        <form id="brk-impressum-form" method="post">
                            <?php wp_nonce_field('brk_impressum_save', 'brk_impressum_nonce'); ?>
                            
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label for="facility_id">Verband
                                                 *</label>
                                        </th>
                                        <td>
                                            <?php if (empty($facilities)): ?>
                                                <p class="description" style="color: #d63638;">
                                                    <strong>⚠️ Keine Verbände verfügbar.</strong><br>
                                                    Bitte kontaktieren Sie Ihren Administrator.
                                                </p>
                                            <?php else: ?>
                                            <select name="facility_id" id="facility_id" class="regular-text" required>
                                                <option value="">-- Bitte wählen --</option>
                                                <?php foreach ($facilities as $id => $name): ?>
                                                    <option value="<?php echo esc_attr($id); ?>" 
                                                            <?php selected($settings['facility_id'], $id); ?>>
                                                        <?php echo esc_html($id . ' - ' . $name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="description">
                                                Wählen Sie Ihren Verband oder Ihre Einrichtung aus.
                                            </p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="responsible_name">Technischer Kontakt (Name) *</label>
                                        </th>
                                        <td>
                                            <input type="text" 
                                                   name="responsible_name" 
                                                   id="responsible_name" 
                                                   class="regular-text" 
                                                   value="<?php echo esc_attr($settings['responsible_name']); ?>" 
                                                   required>
                                            <p class="description">
                                                Name des Webmasters
                                            </p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="responsible_email">E-Mail-Adresse *</label>
                                        </th>
                                        <td>
                                            <input type="email" 
                                                   name="responsible_email" 
                                                   id="responsible_email" 
                                                   class="regular-text" 
                                                   value="<?php echo esc_attr($settings['responsible_email']); ?>" 
                                                   required>
                                            <p class="description">
                                                Kontakt-E-Mail des Webmasters
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <!-- Footer-Link Status -->
                            <?php
                            $impressum_page = get_page_by_path('impressum');
                            $footer_link_status = $this->check_footer_link($impressum_page);
                            ?>
                            <div style="padding: 15px; margin: 20px 0; background: #f0f0f1; border-left: 4px solid #72aee6; border-radius: 3px;">
                                <p style="margin: 0 0 10px 0;">
                                    <strong>Impressum im Footer:</strong>
                                    <?php if ($footer_link_status === 'correct'): ?>
                                        <span style="color: #46b450;">✓ Ja</span> - Der Footer-Link zeigt auf das Impressum
                                    <?php elseif ($footer_link_status === 'wrong'): ?>
                                        <span style="color: #d63638;">✗ Falsch</span> - Der Footer-Link zeigt auf eine falsche Seite
                                    <?php else: ?>
                                        <span style="color: #d63638;">✗ Nein</span> - Kein Impressum-Link im Footer gefunden
                                    <?php endif; ?>
                                </p>
                                <button type="button" id="brk-update-footer-btn" class="button button-secondary">
                                    Impressum in Footer übernehmen
                                </button>
                            </div>
                            
                            <p class="submit">
                                <button type="button" id="brk-preview-btn" class="button button-secondary">
                                    Vorschau anzeigen
                                </button>
                                <button type="submit" id="brk-save-btn" class="button button-primary">
                                    Impressum übernehmen
                                </button>
                                <?php if ($impressum_page): ?>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $impressum_page->ID . '&action=edit')); ?>" 
                                   class="button button-secondary" 
                                   style="margin-left: 10px;">
                                    Impressum bearbeiten
                                </a>
                                <?php endif; ?>
                            </p>
                            
                            <div id="brk-status-message" style="display: none;"></div>
                        </form>
                        
                        <?php if (!empty($settings['last_updated'])): ?>
                            <p class="description">
                                <em>Zuletzt aktualisiert: <?php echo esc_html($settings['last_updated']); ?></em>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card">
                        <h3>Hinweise</h3>
                        <ul>
                            <li>Wählen Sie zuerst Ihren Verband/Ihre Einrichtung aus der Liste</li>
                            <li>Geben Sie die Kontaktdaten des Seiten-Verantwortlichen ein</li>
                            <li>Klicken Sie auf "Vorschau anzeigen" um das Impressum zu überprüfen</li>
                            <li>Mit "Impressum übernehmen" wird eine Seite erstellt/aktualisiert</li>
                            <li>Sie können das Impressum auch per Shortcode <code>[brk_impressum]</code> einbinden</li>
                        </ul>
                    </div>
                </div>
                
                <div class="brk-impressum-preview-section">
                    <div class="card">
                        <h2>Vorschau</h2>
                        <div id="brk-impressum-preview" class="brk-preview-container">
                            <p class="description">
                                Wählen Sie einen Verband und füllen Sie die Felder aus, 
                                dann klicken Sie auf "Vorschau anzeigen".
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Prüfe Footer-Link Status
     */
    private function check_footer_link($impressum_page) {
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
        
        // Prüfe YooTheme Builder Widgets
        $builder_widgets = get_option('widget_builderwidget', array());
        
        foreach ($bottom_widgets as $widget_id) {
            if (strpos($widget_id, 'builderwidget-') === 0) {
                $widget_number = (int) str_replace('builderwidget-', '', $widget_id);
                
                if (isset($builder_widgets[$widget_number]) && is_array($builder_widgets[$widget_number])) {
                    $widget = $builder_widgets[$widget_number];
                    
                    if (isset($widget['content'])) {
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
            if (($key === 'link' || $key === 'url' || $key === 'href') && is_string($value)) {
                if (stripos($value, 'impressum') !== false) {
                    $link_path = trim(parse_url($value, PHP_URL_PATH), '/');
                    return ($link_path === $expected_path) ? 'correct' : 'wrong';
                }
            }
            
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
     * AJAX: Footer-Link aktualisieren
     */
    public function ajax_update_footer_link() {
        check_ajax_referer('brk_impressum_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }
        
        $impressum_page = get_page_by_path('impressum');
        if (!$impressum_page) {
            wp_send_json_error('Keine Impressum-Seite gefunden');
        }
        
        $expected_path = '/impressum';
        
        // Finde Bottom-Sidebar Widget
        $sidebars_widgets = get_option('sidebars_widgets', array());
        $bottom_widgets = isset($sidebars_widgets['bottom']) ? $sidebars_widgets['bottom'] : array();
        
        $builder_widgets = get_option('widget_builderwidget', array());
        $updated = false;
        $found_widgets = 0;
        $updated_links = 0;
        
        foreach ($bottom_widgets as $widget_id) {
            if (strpos($widget_id, 'builderwidget-') === 0) {
                $widget_number = (int) str_replace('builderwidget-', '', $widget_id);
                
                if (isset($builder_widgets[$widget_number]) && is_array($builder_widgets[$widget_number])) {
                    $found_widgets++;
                    
                    if (isset($builder_widgets[$widget_number]['content'])) {
                        $decoded = json_decode($builder_widgets[$widget_number]['content'], true);
                        
                        if (is_array($decoded)) {
                            // Zähle vorher Links
                            $count_before = $this->count_impressum_links($decoded);
                            
                            // Aktualisiere alle "link" Felder die "impressum" enthalten
                            $this->update_impressum_links($decoded, $expected_path);
                            
                            // Zähle nachher Links
                            $count_after = $this->count_impressum_links($decoded);
                            
                            if ($count_before > 0) {
                                // Speichere zurück
                                $builder_widgets[$widget_number]['content'] = json_encode($decoded);
                                $updated = true;
                                $updated_links += $count_before;
                            }
                        }
                    }
                }
            }
        }
        
        if ($updated) {
            update_option('widget_builderwidget', $builder_widgets);
            wp_send_json_success("Footer-Link wurde aktualisiert ($updated_links Link(s) in $found_widgets Widget(s))");
        } else {
            if ($found_widgets > 0) {
                wp_send_json_error("$found_widgets Builder-Widget(s) gefunden, aber kein Impressum-Link darin");
            } else {
                wp_send_json_error('Kein Builder-Widget in der Bottom-Sidebar gefunden');
            }
        }
    }
    
    /**
     * Zähle Impressum-Links in Datenstruktur
     */
    private function count_impressum_links($data) {
        if (!is_array($data)) {
            return 0;
        }
        
        $count = 0;
        
        foreach ($data as $key => $value) {
            if (($key === 'link' || $key === 'url' || $key === 'href') && is_string($value)) {
                if (stripos($value, 'impressum') !== false) {
                    $count++;
                }
            }
            
            if (is_array($value)) {
                $count += $this->count_impressum_links($value);
            }
        }
        
        return $count;
    }
    
    /**
     * Rekursiv alle Impressum-Links aktualisieren
     */
    private function update_impressum_links(&$data, $new_path) {
        if (!is_array($data)) {
            return;
        }
        
        foreach ($data as $key => &$value) {
            if (($key === 'link' || $key === 'url' || $key === 'href') && is_string($value)) {
                if (stripos($value, 'impressum') !== false) {
                    $value = $new_path;
                }
            }
            
            if (is_array($value)) {
                $this->update_impressum_links($value, $new_path);
            }
        }
    }
}
