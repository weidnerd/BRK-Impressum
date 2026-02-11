# Entwickler-Dokumentation - BRK Impressum Generator

## Architektur

### Plugin-Struktur

```
brk-impressum/
├── brk-impressum.php                    # Hauptdatei, Plugin-Bootstrap
├── admin/                               # Admin-Interface
│   ├── class-brk-impressum-admin.php    # Admin-Seite und Einstellungen
│   └── class-brk-impressum-tools.php    # Network Admin Tools
├── includes/                            # Core-Funktionalität
│   ├── class-brk-facilities-loader.php  # API-Anbindung und Caching
│   ├── class-brk-impressum-generator.php # HTML-Generierung
│   └── class-brk-impressum-settings.php  # Einstellungsverwaltung
└── assets/                              # Frontend-Ressourcen
    ├── css/
    │   ├── admin.css                    # Admin-Styles
    │   └── frontend.css                 # Frontend-Styles
    └── js/
        └── admin.js                     # Admin-JavaScript
```

### Klassenübersicht

#### `BRK_Impressum` (Hauptklasse)
- Singleton-Pattern
- Plugin-Initialisierung
- Hook-Management
- REST API Routing

#### `BRK_Facilities_Loader`
- Lädt Facilities-Daten von API
- Caching-Management (24h)
- Hilfsfunktionen für Datenzugriff

#### `BRK_Impressum_Generator`
- Generiert Impressum-HTML
- Template-Rendering
- Datenmapping

#### `BRK_Impressum_Admin`
- Admin-Interface
- Asset-Loading
- Formular-Rendering
- Footer-Link-Erkennung (YooTheme Builder)
- AJAX-Handler für Footer-Link-Updates

#### `BRK_Impressum_Tools`
- Network Admin Tools
- Debug-Ansicht für Widget-Analyse
- Nutzungsübersicht aller Sites

#### `BRK_Impressum_Settings`
- Einstellungsverwaltung
- Validierung
- Getter/Setter

## API-Referenz

### REST API Endpunkte

#### GET `/wp-json/brk-impressum/v1/facilities`

Gibt alle Facilities zurück.

**Request:**
```bash
curl -X GET https://ihre-domain.de/wp-json/brk-impressum/v1/facilities \
  -H "X-WP-Nonce: YOUR_NONCE"
```

**Response:**
```json
[
  {
    "id": "000",
    "ebene": "Landesverband",
    "name": "BRK Landesverband Bayern",
    "anschrift": { ... },
    "kontakt": { "telefon": "...", "fax": "...", "email": "...", "internet": "..." },
    "vorstand": { ... },
    "geschaeftsfuehrung": { ... }
  }
]
```

#### POST `/wp-json/brk-impressum/v1/preview`

Generiert Impressum-Vorschau.

**Request:**
```bash
curl -X POST https://ihre-domain.de/wp-json/brk-impressum/v1/preview \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -d '{
    "facility_id": "001",
    "responsible_name": "Max Mustermann",
    "responsible_function": "Webmaster",
    "responsible_email": "max@example.com"
  }'
```

**Response:**
```json
{
  "html": "<div class=\"brk-impressum\">...</div>"
}
```

#### POST `/wp-json/brk-impressum/v1/save`

Speichert Impressum und erstellt Seite.

**Request:** Wie `/preview`

**Response:**
```json
{
  "success": true,
  "page_id": 123,
  "message": "Impressum erfolgreich gespeichert"
}
```

### PHP-API

#### Facilities-Daten abrufen

```php
$loader = BRK_Facilities_Loader::get_instance();

// Alle Facilities
$facilities = $loader->get_facilities();

// Einzelne Facility
$facility = $loader->get_facility_by_id('001');

// Landesverband
$lv = $loader->get_landesverband();

// Für Select-Dropdown
$options = $loader->get_facilities_for_select();

// Cache aktualisieren
$loader->refresh_cache();

// Cache löschen
$loader->clear_cache();
```

#### Impressum generieren

```php
$generator = BRK_Impressum_Generator::get_instance();

$html = $generator->generate_impressum(
    '001',                    // Facility-ID
    'Max Mustermann',        // Name
    'max@example.com'        // E-Mail
);

if (is_wp_error($html)) {
    echo $html->get_error_message();
} else {
    echo $html;
}
```

#### Einstellungen verwalten

```php
$settings = BRK_Impressum_Settings::get_instance();

// Alle Einstellungen
$all = $settings->get_settings();

// Einzelne Einstellung
$facility_id = $settings->get_setting('facility_id');

// Einstellungen speichern
$settings->save_settings([
    'facility_id' => '001',
    'responsible_name' => 'Max Mustermann',
    'responsible_function' => 'Webmaster',
    'responsible_email' => 'max@example.com'
]);

// Einstellungen zurücksetzen
$settings->reset_settings();
```

## Hooks & Filter

### Actions

```php
// Nach Cache-Aktualisierung
add_action('brk_impressum_refresh_cache', function() {
    // Ihre Logik
});

// Nach Plugin-Aktivierung
register_activation_hook(__FILE__, function() {
    // Ihre Logik
});

// Vor Plugin-Deaktivierung
register_deactivation_hook(__FILE__, function() {
    // Ihre Logik
});
```

### Filter

```php
// Cache-Dauer ändern (in Sekunden)
add_filter('brk_impressum_cache_duration', function($duration) {
    return 3600; // 1 Stunde statt 24 Stunden
});

// Facilities-URL ändern
add_filter('brk_impressum_facilities_url', function($url) {
    return 'https://custom-api.example.com/facilities.json';
});

// Impressum-HTML modifizieren
add_filter('brk_impressum_html', function($html, $facility, $responsible) {
    // HTML anpassen
    return $html;
}, 10, 3);

// Admin-Capability ändern
add_filter('brk_impressum_admin_capability', function($capability) {
    return 'edit_pages'; // Standard: manage_options
});
```

## Shortcodes

### `[brk_impressum]`

Gibt das konfigurierte Impressum aus.

**Verwendung:**
```
[brk_impressum]
```

**Attribute:**
- Keine (verwendet gespeicherte Einstellungen)

**Programmatische Verwendung:**
```php
echo do_shortcode('[brk_impressum]');
```

## JavaScript-API

### Admin-Interface

```javascript
// In admin.js verfügbare Funktionen

// Vorschau anzeigen
BRKImpressumAdmin.showPreview();

// Impressum speichern
BRKImpressumAdmin.saveImpressum();

// Cache aktualisieren
BRKImpressumAdmin.refreshCache();

// Nachricht anzeigen
BRKImpressumAdmin.showMessage('success', 'Nachricht');

// E-Mail validieren
BRKImpressumAdmin.validateEmail('test@example.com');
```

### Verfügbare globale Variablen

```javascript
// brkImpressum Objekt
brkImpressum = {
    ajaxUrl: '...',
    nonce: '...',
    restUrl: '...',
    restNonce: '...',
    strings: {
        loading: 'Laden...',
        error: 'Ein Fehler ist aufgetreten.',
        success: 'Erfolgreich gespeichert!',
        // ...
    }
};
```

## Datenbankstruktur

### Options-Tabelle

```sql
-- Plugin-Einstellungen pro Site
wp_options.option_name = 'brk_impressum_settings'
wp_options.option_value = '{
  "facility_id": "001",
  "responsible_name": "Max Mustermann",
  "responsible_function": "Webmaster",
  "responsible_email": "max@example.com",
  "last_updated": "2026-01-21 12:00:00"
}'

-- Cache (Transient)
wp_options.option_name = '_transient_brk_facilities_data'
wp_options.option_value = '[{facility_data}]'

-- Cache-Expiration
wp_options.option_name = '_transient_timeout_brk_facilities_data'
wp_options.option_value = '1737550800' -- Unix Timestamp
```

## Testing

### Manuelle Tests

```bash
# Plugin aktivieren
wp plugin activate brk-impressum

# Facilities abrufen
wp eval 'print_r(BRK_Facilities_Loader::get_instance()->get_facilities());'

# Impressum generieren
wp eval 'echo BRK_Impressum_Generator::get_instance()->generate_impressum("001", "Test", "test@example.com");'

# Cache löschen
wp eval 'BRK_Facilities_Loader::get_instance()->clear_cache();'
```

### Unit Tests (geplant)

```bash
# PHPUnit Tests ausführen
composer test

# Mit Coverage
composer test -- --coverage-html coverage/
```

## Debugging

### Debug-Modus aktivieren

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Logging

```php
// In Ihrem Code
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('BRK Impressum Debug: ' . print_r($data, true));
}
```

### REST API Debug

```bash
# Facilities abrufen
curl -v https://ihre-domain.de/wp-json/brk-impressum/v1/facilities

# Mit Authentifizierung
curl -v https://ihre-domain.de/wp-json/brk-impressum/v1/facilities \
  -H "X-WP-Nonce: $(wp eval 'echo wp_create_nonce("wp_rest");')"
```

## Performance-Optimierung

### Cache-Strategien

```php
// Längerer Cache für Produktionsumgebung
add_filter('brk_impressum_cache_duration', function() {
    return defined('WP_ENV') && WP_ENV === 'production' ? 86400 : 3600;
});
```

### Lazy Loading

```php
// Frontend-CSS nur bei Bedarf laden
add_action('wp_enqueue_scripts', function() {
    if (is_page('impressum')) {
        wp_enqueue_style('brk-impressum-frontend', ...);
    }
});
```

## Sicherheit

### Nonce-Validierung

```php
// In Admin-Forms
wp_nonce_field('brk_impressum_save', 'brk_impressum_nonce');

// Bei Verarbeitung
if (!wp_verify_nonce($_POST['brk_impressum_nonce'], 'brk_impressum_save')) {
    wp_die('Sicherheitscheck fehlgeschlagen');
}
```

### Capability-Checks

```php
// Berechtigung prüfen
if (!current_user_can('manage_options')) {
    wp_die('Keine Berechtigung');
}
```

### Input-Sanitization

```php
$facility_id = sanitize_text_field($_POST['facility_id']);
$email = sanitize_email($_POST['email']);
```

### Output-Escaping

```php
echo esc_html($text);
echo esc_attr($attribute);
echo esc_url($url);
```

## Erweiterung

### Custom Template

```php
// Eigenes Impressum-Template
add_filter('brk_impressum_template', function($default_template) {
    $custom = get_template_directory() . '/templates/impressum.php';
    return file_exists($custom) ? $custom : $default_template;
});
```

### Zusätzliche Felder

```php
// Neue Felder hinzufügen
add_filter('brk_impressum_settings_fields', function($fields) {
    $fields['custom_field'] = [
        'label' => 'Mein Feld',
        'type' => 'text',
        'default' => ''
    ];
    return $fields;
});
```

## Best Practices

1. **Immer Nonces verwenden** bei Formularen
2. **Capabilities prüfen** vor geschützten Aktionen
3. **Input sanitizen** und **Output escapen**
4. **WP_Error verwenden** für Fehlerbehandlung
5. **Transients nutzen** für Caching
6. **Hooks dokumentieren** für andere Entwickler
7. **Coding Standards einhalten** (WPCS)

## Support & Contribution

- **Issues:** https://github.com/weidnerd/BRK-Impressum/issues
- **Pull Requests:** Willkommen!
- **Coding Standards:** WordPress Coding Standards
- **Tests:** PHPUnit (geplant)

## Lizenz

GPL v2 oder höher - siehe LICENSE Datei
