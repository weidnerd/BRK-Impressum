# BRK Impressum Generator

WordPress Multisite (MU) Plugin für die automatische Generierung von Impressum-Seiten für BRK-Unterseiten.

## Beschreibung

Dieses Plugin ermöglicht es WordPress Multisite-Unterseiten, automatisch ein vollständiges Impressum zu erstellen, basierend auf:

- Daten aus der BRK Facilities API (`https://mein.brk.de/data/facilities.json`)
- Individuellen Angaben zum Seiten-Verantwortlichen

## Features

- ✅ Automatisches Laden von Verband/Einrichtungs-Daten
- ✅ Einfaches Backend-Formular für Konfiguration
- ✅ Live-Vorschau des generierten Impressums
- ✅ Ein-Klick-Übernahme zur Erstellung/Aktualisierung der Impressum-Seite
- ✅ Shortcode-Unterstützung `[brk_impressum]`
- ✅ Caching für bessere Performance
- ✅ REST API für flexible Integration
- ✅ Multisite-ready

## Installation

### Als MU-Plugin (empfohlen)

1. Laden Sie den gesamten Plugin-Ordner in `wp-content/mu-plugins/` hoch
2. Das Plugin wird automatisch aktiviert und steht allen Sites zur Verfügung

### Als normales Plugin

1. Laden Sie den Plugin-Ordner in `wp-content/plugins/` hoch
2. Aktivieren Sie das Plugin im WordPress-Backend
3. Bei Multisite: Netzwerkweit aktivieren

## Verwendung

### Backend-Konfiguration

1. Gehen Sie zu **Einstellungen > BRK Impressum**
2. Wählen Sie Ihren Verband/Ihre Einrichtung aus der Dropdown-Liste
3. Geben Sie die Daten des Seiten-Verantwortlichen ein:
   - Name (Pflichtfeld)
   - Funktion (optional)
   - E-Mail-Adresse (Pflichtfeld)
4. Klicken Sie auf **"Vorschau anzeigen"** um das Impressum zu prüfen
5. Klicken Sie auf **"Impressum übernehmen"** um die Seite zu erstellen/aktualisieren

### Impressum anzeigen

Das Impressum wird automatisch unter `/impressum` verfügbar gemacht.

**Alternative: Shortcode**

Sie können das Impressum auch auf jeder beliebigen Seite mit dem Shortcode einbinden:

```
[brk_impressum]
```

## Datenstruktur

Das Plugin erwartet folgende Struktur in der `facilities.json`:

```json
[
  {
    "id": "000",
    "ebene": "Landesverband",
    "name": "BRK Landesverband Bayern",
    "anschrift": {
      "strasse": "Garmischer Straße 19-21",
      "plz": "81373",
      "ort": "München"
    },
    "kontakt": {
      "telefon": "089 9241-0",
      "email": "info@brk.de"
    },
    "vorstand": {
      "funktion": "Präsident",
      "name": "Max Mustermann"
    },
    "geschaeftsfuehrung": {
      "funktion": "Geschäftsführer",
      "name": "Erika Musterfrau",
      "email": "geschaeftsfuehrung@brk.de"
    }
  }
]
```

## API-Endpunkte

Das Plugin stellt folgende REST API Endpunkte bereit:

### GET `/wp-json/brk-impressum/v1/facilities`

Gibt alle verfügbaren Facilities zurück.

**Berechtigung:** `manage_options`

### POST `/wp-json/brk-impressum/v1/preview`

Generiert eine Vorschau des Impressums.

**Berechtigung:** `manage_options`

**Parameter:**
```json
{
  "facility_id": "123",
  "responsible_name": "Max Mustermann",
  "responsible_function": "Webmaster",
  "responsible_email": "max@example.com"
}
```

### POST `/wp-json/brk-impressum/v1/save`

Speichert die Einstellungen und erstellt/aktualisiert die Impressum-Seite.

**Berechtigung:** `manage_options`

**Parameter:** Wie bei `/preview`

## Cache-Verwaltung

Die Facilities-Daten werden für **24 Stunden** gecacht. Sie können den Cache manuell aktualisieren:

- Im Backend über den Button "Daten jetzt aktualisieren"
- Programmatisch: `BRK_Facilities_Loader::get_instance()->refresh_cache()`

## Hooks & Filter

### Actions

```php
// Nach Cache-Aktualisierung
do_action('brk_impressum_refresh_cache');
```

## Systemanforderungen

- WordPress 5.0 oder höher
- PHP 7.4 oder höher
- Multisite (optional, aber empfohlen)

## Verzeichnisstruktur

```
brk-impressum/
├── brk-impressum.php           # Haupt-Plugin-Datei
├── admin/
│   └── class-brk-impressum-admin.php
├── includes/
│   ├── class-brk-facilities-loader.php
│   ├── class-brk-impressum-generator.php
│   └── class-brk-impressum-settings.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
└── README.md
```

## Fehlerbehebung

### Facilities-Daten können nicht geladen werden

**Problem:** "Die Facilities-Daten konnten nicht geladen werden"

**Lösung:**

Das Plugin verwendet automatisch **Fallback-Daten**, wenn die API nicht erreichbar ist:

1. **Automatischer Fallback**: Das Plugin lädt lokale Beispieldaten aus `facilities-example.json`
2. **Hardcoded-Fallback**: Falls keine lokale Datei existiert, werden minimale Demo-Daten verwendet
3. **Testen**: Klicken Sie auf "API-Verbindung testen" in den Debug-Informationen

**Für Produktivumgebungen:**
1. Überprüfen Sie die Verbindung zu `https://mein.brk.de/data/facilities.json`
2. Stellen Sie sicher, dass SSL-Zertifikate korrekt konfiguriert sind
3. Prüfen Sie die PHP-Fehlerlog-Datei: `tail -f /pfad/zu/php-error.log`
4. Versuchen Sie, den Cache zu aktualisieren
5. Kontaktieren Sie den BRK-Administrator für API-Zugang

**Debug-Modus:**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```
Prüfen Sie dann `wp-content/debug.log` auf Fehler.

### Vorschau wird nicht angezeigt

**Problem:** Die Vorschau bleibt leer

**Lösung:**
1. Öffnen Sie die Browser-Konsole (F12) und prüfen Sie auf JavaScript-Fehler
2. Stellen Sie sicher, dass alle Pflichtfelder ausgefüllt sind
3. Prüfen Sie, ob REST API funktioniert: `/wp-json/brk-impressum/v1/facilities`

### Seite wird nicht erstellt

**Problem:** "Impressum übernehmen" funktioniert nicht

**Lösung:**
1. Prüfen Sie die Benutzerberechtigungen (`manage_options`)
2. Stellen Sie sicher, dass WordPress Seiten erstellen kann
3. Prüfen Sie die Datenbankverbindung

## Entwicklung

### PHP-Coding-Standards

Das Plugin folgt den WordPress PHP Coding Standards.

### JavaScript

- ES5-kompatibel für maximale Browserunterstützung
- jQuery als Abhängigkeit

### CSS

- BEM-ähnliche Namenskonvention
- Mobile-first responsive Design

## Support & Kontakt

Bei Fragen oder Problemen wenden Sie sich bitte an:

- GitHub Issues: https://github.com/weidnerd/BRK-Impressum/issues
- BRK Support: support@brk.de

## Lizenz

GPL v2 oder höher

## Changelog

### Version 1.0.0
- Initiales Release
- Basis-Funktionalität für Impressum-Generierung
- Admin-Interface mit Live-Vorschau
- REST API Integration
- Caching-System
- Multisite-Unterstützung

## Credits

Entwickelt für das Bayerische Rote Kreuz (BRK)

## Mitwirken

Contributions sind willkommen! Bitte erstellen Sie einen Pull Request oder öffnen Sie ein Issue auf GitHub.