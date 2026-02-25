# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [1.0.0] - 2026-01-21

### Hinzugefügt (1.0.0)

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

### Hinzugefügt (1.1.0)

- Footer-Link-Erkennung für YooTheme Builder Widgets
- Automatische Überprüfung ob Impressum-Link im Footer vorhanden ist
- Status-Anzeige "Impressum im Footer" (✓ Ja / ✗ Nein / ✗ Falsch)
- Ein-Klick-Button "Impressum in Footer übernehmen" zum automatischen Aktualisieren
- Intelligente Link-Erkennung: Aktualisiert nur Links mit "Impressum"-Text
- Debug-Ansicht in Network Admin Tools für Widget-Analyse
- Event-Delegation für robustes JavaScript-Event-Handling
- Cache-Busting mit Zeitstempel für Asset-Versionierung

### Geändert (1.1.0)

- Footer-Link-Status jetzt innerhalb der Konfigurations-Box integriert
- Verbesserte AJAX-Fehlerbehandlung mit detaillierten Meldungen
- Optimierte Widget-Suche: Nur Bottom-Sidebar, nur Builder-Widgets

### Behoben (1.1.0)

- PHP Warning bei undefinierter Variable $impressum_page
- JavaScript Event-Binding-Problem mit Event-Delegation gelöst

## [1.2.0] - 2026-02-11

### Geändert (1.2.0)

- Impressum-Struktur komplett überarbeitet:
  - Landesverband-Angaben erscheinen jetzt zuerst als Hauptanbieter
  - Neuer Abschnitt "Ansprechpartner vor Ort" für lokale Verbände
  - "vertreten durch das Präsidium" als Vertretungsangabe
  - USt-Id.-Nr. (DE129523533) fest integriert
- Neue Felder für Kontaktdaten: Fax (`kontakt.fax`) und Internet (`kontakt.internet`)
- Überschrift von "Anbieterkennung nach § 5 TMG" zu "Anbieterkennung" vereinfacht
- Statischer Impressum-Text aktualisiert:
  - Einleitungsabsatz zusammengefasst, Hinweis auf Facebook/soziale Netzwerke ergänzt
  - Ministerium aktualisiert: "für Sport und Integration" (statt "für Bau und Verkehr")
  - Satzungsdatum aktualisiert auf 25.11.2023 (Bekanntmachung vom 29.11.2024)
  - Reihenfolge: Vertretungsberechtigte vor Vereinsregistereintrag
  - Verbraucherschlichtung ohne eigene Überschrift

### Entfernt (1.2.0)

- Abschnitt "Angaben zum Landesverband" (jetzt im Hauptblock)
- Abschnitt "Verantwortlich i.S.d. § 5 TMG" (ersetzt durch neue Struktur)
- Freitext über Aufsichtsbehörde
- Absatz "Gewährleistung Vollständigkeit, Richtigkeit und Aktualität"
- Absatz "Lob und/oder Beschwerden"

## [1.2.1] - 2026-02-11

### Geändert (1.2.1)

- Frontend-CSS-Laden deaktiviert: Impressum übernimmt nun die Styles des aktiven WordPress-Themes

### Entfernt (1.2.1)

- Inhalt von `assets/css/frontend.css` entfernt (Datei als Platzhalter beibehalten)

## [1.2.2] - 2026-02-12

### Entfernt (1.2.2)

- Impressum-Vorschau-Styles (`.brk-impressum`) aus `admin.css` entfernt (Vorschau nutzt WordPress-Admin-Styles)
- Ungenutzten CSS-Abschnitt "Loading State" (`.button.loading`) aus `admin.css` entfernt

## [1.2.3] - 2026-02-25

### Geändert (1.2.3)

- DRK-Vertretungsangabe im statischen Impressumsteil ist jetzt dynamisch aus Facility `id=999`:
  - `geschaeftsfuehrung.name`
  - `geschaeftsfuehrung.funktion`
- Automatische Aktualisierung bei Datenänderungen optimiert:
  - JSON-Hash-Vergleich vor dem Auslösen von `brk_impressum_cache_refreshed`
  - Netzwerkweite Seitenaktualisierung nur bei echten Inhaltsänderungen
- Cron-Planung vereinheitlicht auf **1x täglich** (`daily`) mit automatischer Korrektur bestehender abweichender Schedules

  ### Behoben (1.2.3)

- Verhindert unnötige Re-Generierung aller Impressum-Seiten, wenn sich die JSON-Inhalte nicht geändert haben

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
