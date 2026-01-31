# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [1.0.0] - 2026-01-21

### Hinzugefügt
- Initiales Release des BRK Impressum Generators
- Automatisches Laden von Verband-Daten aus BRK Facilities API
- Backend-Konfigurationsseite mit intuitivem Formular
- Live-Vorschau-Funktion für Impressum vor Übernahme
- Ein-Klick-Übernahme zur Erstellung/Aktualisierung der Impressum-Seite
- Shortcode `[brk_impressum]` für flexible Einbindung
- REST API Endpunkte:
  - `GET /brk-impressum/v1/facilities` - Facilities abrufen
  - `POST /brk-impressum/v1/preview` - Vorschau generieren
  - `POST /brk-impressum/v1/save` - Impressum speichern
- Intelligentes Caching-System (24 Stunden)
- Manuelle Cache-Aktualisierung im Backend
- WordPress Multisite Unterstützung
- Responsive Admin-Interface
- Frontend-Styling mit BRK-Branding
- Umfassende Fehlerbehandlung und Validierung
- Vollständige deutsche Lokalisierung
- Dokumentation (README.md, INSTALLATION.md)
- Beispiel-JSON für Datenstruktur
- Composer-Unterstützung
- PHP Coding Standards Konfiguration

### Sicherheit
- Nonce-Validierung für alle Formulare
- Capability-Checks für Admin-Zugriff
- Sanitization aller Eingaben
- Prepared Statements für Datenbankzugriffe
- XSS-Schutz durch esc_*-Funktionen

## [1.1.0] - 2026-01-31

### Hinzugefügt
- Footer-Link-Erkennung für YooTheme Builder Widgets
- Automatische Überprüfung ob Impressum-Link im Footer vorhanden ist
- Status-Anzeige "Impressum im Footer" (✓ Ja / ✗ Nein / ✗ Falsch)
- Ein-Klick-Button "Impressum in Footer übernehmen" zum automatischen Aktualisieren
- Intelligente Link-Erkennung: Aktualisiert nur Links mit "Impressum"-Text
- Debug-Ansicht in Network Admin Tools für Widget-Analyse
- Event-Delegation für robustes JavaScript-Event-Handling
- Cache-Busting mit Zeitstempel für Asset-Versionierung

### Geändert
- Footer-Link-Status jetzt innerhalb der Konfigurations-Box integriert
- Verbesserte AJAX-Fehlerbehandlung mit detaillierten Meldungen
- Optimierte Widget-Suche: Nur Bottom-Sidebar, nur Builder-Widgets

### Behoben
- PHP Warning bei undefinierter Variable $impressum_page
- JavaScript Event-Binding-Problem mit Event-Delegation gelöst

## [Unveröffentlicht]

### Geplant
- Unit Tests mit PHPUnit
- Mehrsprachige Unterstützung (i18n)
- Export-Funktion für Impressum als PDF
- Automatische Benachrichtigung bei veralteten Daten
- Integration mit WordPress Gutenberg Block
- DSGVO-Erweiterungen
- Backup-Funktion für Einstellungen
- Import/Export von Konfigurationen
- Dashboard-Widget mit Schnellübersicht
- WP-CLI Commands

---

## Version-Schema

- **Major**: Breaking Changes, große neue Features
- **Minor**: Neue Features, abwärtskompatibel
- **Patch**: Bugfixes, kleine Verbesserungen

## Links

- [GitHub Repository](https://github.com/weidnerd/BRK-Impressum)
- [Issue Tracker](https://github.com/weidnerd/BRK-Impressum/issues)
