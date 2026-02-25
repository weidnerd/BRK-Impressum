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
- ✅ Footer-Link-Erkennung und automatische Aktualisierung (YooTheme Builder)
- ✅ Status-Anzeige für Impressum-Link im Footer
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

### Footer-Link-Verwaltung (YooTheme)

Das Plugin erkennt automatisch Impressum-Links in YooTheme Builder Widgets:

1. **Status-Anzeige**: Zeigt an ob ein korrekter Impressum-Link im Footer vorhanden ist
   - ✓ Ja - Der Footer-Link zeigt auf das Impressum
   - ✗ Falsch - Der Footer-Link zeigt auf eine falsche Seite
   - ✗ Nein - Kein Impressum-Link im Footer gefunden

2. **Automatische Aktualisierung**: Klicken Sie auf "Impressum in Footer übernehmen"
   - Sucht nach Navigations-Elementen mit dem Text "Impressum"
   - Aktualisiert nur diesen spezifischen Link auf `/impressum`
   - Lässt alle anderen Links (Datenschutz, externe Links etc.) unberührt

**Hinweis**: Diese Funktion arbeitet nur mit YooTheme Builder Widgets in der "Bottom"-Sidebar.

### Impressum anzeigen

Das Impressum wird automatisch unter `/impressum` verfügbar gemacht.

### Alternative: Shortcode

Sie können das Impressum auch auf jeder beliebigen Seite mit dem Shortcode einbinden:

```text
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
      "fax": "089 9241-199",
      "email": "info@brk.de",
      "internet": "https://brk.de"
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

### Automatische Updates

Das Plugin aktualisiert **automatisch alle Impressum-Seiten**, wenn sich die Facilities-Daten ändern:

1. **Tägliche Aktualisierung**: Ein WP-Cron-Job lädt die Facilities-Daten einmal pro Tag neu
2. **Änderungserkennung**: Nur wenn sich die JSON-Inhalte wirklich geändert haben (Hash-Vergleich), werden die Impressum-Seiten im Netzwerk neu generiert
3. **Nach manuellem Refresh**: Auch bei "Daten jetzt aktualisieren" gilt dieselbe Änderungserkennung

Dies stellt sicher, dass alle Unterseiten immer die aktuellsten Daten aus `https://mein.brk.de/data/facilities.json` verwenden.

## Hooks & Filter

### Actions

```php
// Nach Cache-Aktualisierung (nur bei echten Datenänderungen)
do_action('brk_impressum_cache_refreshed');

// Täglicher WP-Cron-Refresh der Facilities-Daten
do_action('brk_impressum_daily_update');
```

**Hinweis**: `brk_impressum_daily_update` startet den Daten-Refresh. Die eigentliche Seitenaktualisierung (`update_all_impressum_pages()`) läuft über `brk_impressum_cache_refreshed` und nur bei erkannten Datenänderungen.

## Systemanforderungen

- WordPress 5.0 oder höher
- PHP 7.4 oder höher
- Multisite (optional, aber empfohlen)

## Verzeichnisstruktur

```text
brk-impressum/
├── brk-impressum.php           # Haupt-Plugin-Datei
├── admin/
│   ├── class-brk-impressum-admin.php
│   └── class-brk-impressum-tools.php
├── includes/
│   ├── class-brk-facilities-loader.php
│   ├── class-brk-impressum-generator.php
│   └── class-brk-impressum-settings.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
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

- GitHub Issues: [https://github.com/weidnerd/BRK-Impressum/issues](https://github.com/weidnerd/BRK-Impressum/issues)
- BRK Support: [support@brk.de](mailto:support@brk.de)

## Lizenz

GPL v2 oder höher

## Changelog

### Version 1.2.3

- DRK-Vertretungsangabe wird dynamisch aus Facility ID 999 (`geschaeftsfuehrung.name` und `geschaeftsfuehrung.funktion`) übernommen
- Datenänderungen werden per JSON-Hash erkannt, Seitenaktualisierung erfolgt nur bei echten Änderungen
- WP-Cron-Planung auf tägliche Aktualisierung vereinheitlicht

### Version 1.2.2

- Ungenutzte CSS-Styles aus admin.css entfernt (Vorschau-Styles, Loading State)

### Version 1.2.1

- Frontend-CSS-Laden deaktiviert: Impressum nutzt Theme-Styles

### Version 1.2.0

- Impressum-Struktur komplett überarbeitet (Landesverband zuerst, Ansprechpartner vor Ort)
- Neue Felder: Fax, Internet, USt-Id.-Nr.
- Statische Texte aktualisiert (Ministerium, Satzungsdatum, soziale Netzwerke)

### Version 1.1.0

- Footer-Link-Erkennung für YooTheme Builder
- Status-Anzeige und automatische Footer-Link-Aktualisierung
- Debug-Ansicht in Network Admin Tools
- Verbesserte AJAX-Fehlerbehandlung

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
