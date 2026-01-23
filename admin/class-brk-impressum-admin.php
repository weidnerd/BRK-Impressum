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
            BRK_IMPRESSUM_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'brk-impressum-admin',
            BRK_IMPRESSUM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-api'),
            BRK_IMPRESSUM_VERSION,
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
                            
                            <p class="submit">
                                <button type="button" id="brk-preview-btn" class="button button-secondary">
                                    Vorschau anzeigen
                                </button>
                                <button type="submit" id="brk-save-btn" class="button button-primary">
                                    Impressum übernehmen
                                </button>
                                <?php 
                                $impressum_page = get_page_by_path('impressum');
                                if ($impressum_page): 
                                ?>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $impressum_page->ID . '&action=edit')); ?>" 
                                   class="button button-secondary" 
                                   style="margin-left: 10px;">
                                    Impressum-Seite bearbeiten
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
}
