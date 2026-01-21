# Installation und Einrichtung - BRK Impressum Generator

## Schnellstart

### Schritt 1: Plugin hochladen

#### Variante A: Als MU-Plugin (empfohlen für Multisite)
```bash
# Per FTP oder SSH
cd /pfad/zu/wordpress/wp-content/mu-plugins/
# Laden Sie den kompletten Ordner "brk-impressum" hier hoch
```

#### Variante B: Als normales Plugin
```bash
# Per FTP oder SSH
cd /pfad/zu/wordpress/wp-content/plugins/
# Laden Sie den kompletten Ordner "brk-impressum" hier hoch
```

### Schritt 2: Plugin aktivieren (nur bei Variante B)

1. Gehen Sie zu **Plugins > Installierte Plugins**
2. Suchen Sie "BRK Impressum Generator"
3. Klicken Sie auf **Aktivieren**
4. Bei Multisite: Klicken Sie auf **Netzwerkaktivierung**

### Schritt 3: Plugin konfigurieren

1. Gehen Sie zu **Einstellungen > BRK Impressum**
2. Die Seite lädt automatisch die Verbände aus `https://mein.brk.de/data/facilities.json`

## Detaillierte Konfiguration

### Backend-Einstellungen

#### 1. Verband auswählen
- Wählen Sie aus der Dropdown-Liste Ihren Verband/Ihre Einrichtung
- Die Liste wird automatisch aus der Facilities-API geladen
- Format: `ID - Name` (z.B. "001 - BRK Kreisverband München")

#### 2. Seiten-Verantwortlicher
Geben Sie die Kontaktdaten des Website-Verantwortlichen ein:

- **Name** (Pflicht): z.B. "Max Mustermann"
- **Funktion** (Optional): z.B. "Webmaster" oder "Pressesprecher"
- **E-Mail** (Pflicht): z.B. "max.mustermann@beispiel.brk.de"

#### 3. Vorschau generieren
- Klicken Sie auf **"Vorschau anzeigen"**
- Das Impressum wird rechts im Vorschaubereich angezeigt
- Prüfen Sie alle Angaben auf Richtigkeit

#### 4. Impressum übernehmen
- Klicken Sie auf **"Impressum übernehmen"**
- Eine Seite mit dem Slug `/impressum` wird erstellt bzw. aktualisiert
- Die Seite ist sofort öffentlich verfügbar

## Verwendung

### Automatische Impressum-Seite

Nach der Übernahme ist das Impressum unter folgender URL verfügbar:
```
https://ihre-domain.de/impressum
```

### Shortcode einbinden

Sie können das Impressum auch auf anderen Seiten einbinden:

1. Öffnen Sie eine beliebige Seite im Editor
2. Fügen Sie folgenden Shortcode ein:
   ```
   [brk_impressum]
   ```
3. Speichern Sie die Seite

Das Impressum wird automatisch an dieser Stelle angezeigt.

### In Theme-Templates

Für Entwickler: Das Impressum kann auch direkt im Template ausgegeben werden:

```php
<?php
// In Ihrem Theme-Template
if (function_exists('brk_impressum_init')) {
    $impressum = BRK_Impressum::get_instance();
    echo do_shortcode('[brk_impressum]');
}
?>
```

## Multisite-Konfiguration

### Für Netzwerk-Administratoren

1. **Plugin netzwerkweit aktivieren**
   - Gehen Sie zu **Netzwerkverwaltung > Plugins**
   - Aktivieren Sie "BRK Impressum Generator" netzwerkweit

2. **Jede Unterseite konfiguriert individuell**
   - Jede Unterseite kann einen eigenen Verband auswählen
   - Die Einstellungen werden pro Site gespeichert
   - Der Cache wird global geteilt (Performance-Optimierung)

### Für Site-Administratoren

1. Wechseln Sie zu Ihrer Unterseite
2. Gehen Sie zu **Einstellungen > BRK Impressum**
3. Konfigurieren Sie wie oben beschrieben

## Cache-Verwaltung

### Automatischer Cache

- Facilities-Daten werden 24 Stunden gecacht
- Reduziert Ladezeiten und API-Anfragen
- Cache wird bei Plugin-Aktivierung initial geladen

### Manuelles Cache-Update

**Im Backend:**
1. Gehen Sie zu **Einstellungen > BRK Impressum**
2. Klicken Sie auf **"Daten jetzt aktualisieren"**
3. Die Seite lädt neu mit aktuellen Daten

**Per Code:**
```php
BRK_Facilities_Loader::get_instance()->refresh_cache();
```

### Cache löschen

**Per Code:**
```php
BRK_Facilities_Loader::get_instance()->clear_cache();
```

## Problemlösung

### Problem: "Facilities-Daten konnten nicht geladen werden"

**Ursache:** Verbindung zur API fehlgeschlagen

**Lösungen:**
1. Prüfen Sie die Internetverbindung des Servers
2. Testen Sie die URL: https://mein.brk.de/data/facilities.json
3. Prüfen Sie Firewall-Einstellungen
4. Prüfen Sie SSL-Zertifikate: `openssl s_client -connect mein.brk.de:443`

**Temporäre Lösung:**
```bash
# PHP-Fehlerlog prüfen
tail -f /var/log/php/error.log
```

### Problem: Vorschau wird nicht angezeigt

**Ursache:** JavaScript-Fehler oder REST API-Problem

**Lösungen:**
1. Öffnen Sie Browser-Konsole (F12)
2. Prüfen Sie auf JavaScript-Fehler
3. Testen Sie REST API: `https://ihre-domain.de/wp-json/brk-impressum/v1/facilities`
4. Stellen Sie sicher, dass REST API aktiviert ist

### Problem: Impressum wird nicht gespeichert

**Ursache:** Berechtigungsproblem oder Datenbankfehler

**Lösungen:**
1. Prüfen Sie Benutzerrechte (benötigt `manage_options`)
2. Prüfen Sie Datenbankverbindung
3. Prüfen Sie WordPress Debug-Log

**Debug aktivieren:**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Problem: Layout sieht kaputt aus

**Ursache:** CSS-Konflikt mit Theme

**Lösungen:**
1. Prüfen Sie auf Theme-CSS-Konflikte
2. Fügen Sie eigene CSS-Anpassungen hinzu:
   ```css
   /* In Ihrem Theme oder Custom CSS */
   .brk-impressum {
       /* Ihre Anpassungen */
   }
   ```

## Erweiterte Konfiguration

### Custom REST API Timeout

```php
// In wp-config.php oder functions.php
add_filter('http_request_timeout', function($timeout) {
    return 60; // 60 Sekunden Timeout
});
```

### Eigene Impressum-Template

```php
// In Ihrem Theme: functions.php
add_filter('brk_impressum_template', function($template) {
    return get_template_directory() . '/templates/impressum.php';
});
```

## Deinstallation

### Plugin vollständig entfernen

1. **Deaktivieren Sie das Plugin**
   - Gehen Sie zu **Plugins > Installierte Plugins**
   - Klicken Sie auf **Deaktivieren**

2. **Löschen Sie das Plugin**
   - Klicken Sie auf **Löschen**
   - Bestätigen Sie die Löschung

3. **Datenbank-Cleanup (optional)**
   ```sql
   -- Einstellungen löschen
   DELETE FROM wp_options WHERE option_name = 'brk_impressum_settings';
   
   -- Bei Multisite für alle Sites:
   DELETE FROM wp_1_options WHERE option_name = 'brk_impressum_settings';
   DELETE FROM wp_2_options WHERE option_name = 'brk_impressum_settings';
   -- usw.
   ```

4. **Cache löschen (optional)**
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE '_transient_brk_facilities%';
   ```

## Support

### Hilfe erhalten

- **GitHub Issues:** https://github.com/weidnerd/BRK-Impressum/issues
- **BRK Support:** support@brk.de
- **Dokumentation:** Siehe README.md

### Logs sammeln für Support

```bash
# WordPress Debug-Log
tail -f wp-content/debug.log

# PHP Error-Log
tail -f /var/log/php/error.log

# Apache Error-Log
tail -f /var/log/apache2/error.log
```

## Checkliste für die Einrichtung

- [ ] Plugin hochgeladen und aktiviert
- [ ] Facilities-Daten werden geladen (keine Fehler)
- [ ] Verband aus Liste ausgewählt
- [ ] Verantwortlichen-Daten eingetragen
- [ ] Vorschau erfolgreich angezeigt
- [ ] Impressum übernommen
- [ ] Impressum-Seite öffentlich erreichbar
- [ ] Layout passt zum Theme
- [ ] Bei Multisite: Für alle relevanten Sites konfiguriert

## Weitere Ressourcen

- [WordPress Codex](https://codex.wordpress.org/)
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [BRK Website](https://www.brk.de/)
