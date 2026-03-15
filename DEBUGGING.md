# 🔍 Erweiterte Fehlerdiagnose - BRK Impressum Plugin

## ✅ Neue Features implementiert

### 1. **Detaillierte Fehlerausgabe**
- Alle API-Fehler werden jetzt im Backend angezeigt
- Fehlertyp, Fehlermeldung, HTTP-Status, Zeitstempel
- Vorschau der Serverantwort (erste 500 Zeichen)
- Alle Informationen werden im PHP Error-Log gespeichert

### 2. **Interaktiver Verbindungstest**
- **Button "🧪 Verbindungstest durchführen"** im Admin-Backend
- Zeigt detaillierte Diagnose-Informationen:
  - ✅ Erfolg/Fehler Status
  - HTTP Status-Code und Message
  - Antwortzeit in Millisekunden
  - Content-Type der Antwort
  - Anzahl gefundener Facilities
  - Beispiel-IDs der ersten 5 Facilities
  - JSON-Parsing-Fehler (falls vorhanden)
  - Antwort-Vorschau bei Fehlern

### 3. **Debug-Transient**
- Letzte Fehlerinformationen werden für 1 Stunde gespeichert
- Werden im Backend automatisch angezeigt
- Helfen bei der Fehlerdiagnose

### 4. **Verbessertes Logging**
- Alle API-Requests werden geloggt
- Success: "Loaded X facilities"
- Error: Vollständige Debug-Informationen

## 🧪 So testen Sie die API-Verbindung

### Im WordPress-Backend:

1. **Gehen Sie zu: Einstellungen > BRK Impressum**

2. **Klicken Sie auf: "🧪 Verbindungstest durchführen"**

3. **Sie sehen eine der folgenden Meldungen:**

#### ✅ Erfolg:
```
🔍 Verbindungstest abgeschlossen:

✅ Erfolgreich!

Details:
HTTP Status: 200
Antwortzeit: 234.56 ms
Facilities gefunden: 95
Content-Type: application/json
Beispiel-IDs: 000, 001, 002, 003, 004

Die API funktioniert korrekt!
```

#### ❌ Fehler:
```
🔍 Verbindungstest abgeschlossen:

❌ Verbindung fehlgeschlagen

Details:
URL: https://api.brk.id/api/v1/assets/facility.json

Fehler: cURL error 6: Could not resolve host: api.brk.id
Code: http_request_failed

Antwortzeit: 0.12 ms
```

oder bei HTTP-Fehler:
```
HTTP Status: 404 - Not Found

Content-Type: text/html

Antwortgröße: 1234 Bytes

Antwort-Vorschau:
<!DOCTYPE html>
<html>
<head><title>404 Not Found</title></head>
...
```

oder bei JSON-Fehler:
```
HTTP Status: 200

JSON Fehler: Syntax error

Content-Type: text/plain

Antwort-Vorschau:
This is not JSON content, just plain text...
```

## 🔍 Fehlerdetails auslesen

### Im Backend:

Wenn ein Fehler vorliegt, sehen Sie eine **gelbe Warnbox**:

```
⚠️ Hinweis: Fallback-Daten werden verwendet, da die Live-API nicht erreichbar ist.

API-URL: https://api.brk.id/api/v1/assets/facility.json

🔍 Fehlerdetails anzeigen ▼
```

Klicken Sie auf "Fehlerdetails anzeigen" um zu sehen:
- Fehlertyp (WP_Error, HTTP_Error, JSON_Error, Format_Error)
- Fehlermeldung
- Fehlercode
- HTTP Status
- Zeitpunkt
- Antwortgröße
- Antwort-Vorschau

### Im PHP Error-Log:

```bash
# Logs ansehen
tail -f /pfad/zu/php-error.log

# oder WordPress Debug-Log
tail -f wp-content/debug.log
```

Sie sehen Einträge wie:
```
[21-Jan-2026 12:34:56 UTC] BRK Impressum API Error: Array
(
    [url] => https://api.brk.id/api/v1/assets/facility.json
    [timestamp] => 2026-01-21 13:34:56
    [error_type] => WP_Error
    [error_message] => cURL error 6: Could not resolve host: api.brk.id
    [error_code] => http_request_failed
)
```

## 📊 Mögliche Fehlertypen und Lösungen

### 1. **WP_Error** - WordPress konnte Request nicht senden

**Symptome:**
```
Fehlertyp: WP_Error
Fehlermeldung: cURL error 6: Could not resolve host
```

**Ursachen:**
- DNS-Problem
- Server hat kein Internet
- Firewall blockiert ausgehende Requests

**Lösung:**
```bash
# DNS testen
ping api.brk.id

# cURL direkt testen
curl -v https://api.brk.id/api/v1/assets/facility.json

# In wp-config.php Proxy konfigurieren (falls nötig)
define('WP_PROXY_HOST', 'proxy.example.com');
define('WP_PROXY_PORT', '8080');
```

### 2. **HTTP_Error** - Server antwortet mit Fehler

**Symptome:**
```
Fehlertyp: HTTP_Error
HTTP Status: 404 - Not Found
```

**Ursachen:**
- URL ist falsch
- Datei existiert nicht
- Zugriff verweigert (401, 403)

**Lösung:**
```bash
# URL im Browser testen
# Sollte JSON zurückgeben

# Oder mit wget/curl:
wget -O - https://api.brk.id/api/v1/assets/facility.json
```

**Falls URL stimmt aber 403/401:**
```php
// In wp-config.php - API Key hinzufügen (falls nötig)
define('BRK_API_KEY', 'ihr-api-key');

// Dann in class-brk-facilities-loader.php:
'headers' => array(
    'Accept' => 'application/json',
    'Authorization' => 'Bearer ' . BRK_API_KEY
)
```

### 3. **JSON_Error** - Antwort ist kein gültiges JSON

**Symptome:**
```
Fehlertyp: JSON_Error
JSON Fehler: Syntax error
Antwort-Vorschau: <!DOCTYPE html>...
```

**Ursachen:**
- Server gibt HTML statt JSON zurück
- JSON ist fehlerhaft formatiert
- Server-Fehlerseite (500, 502, 503)

**Lösung:**
```bash
# Antwort prüfen
curl -s https://api.brk.id/api/v1/assets/facility.json | head -c 500

# JSON validieren
curl -s https://api.brk.id/api/v1/assets/facility.json | python -m json.tool
```

### 4. **Format_Error** - Daten haben falsches Format

**Symptome:**
```
Fehlertyp: Format_Error
Daten sind kein Array
data_type: string
```

**Ursachen:**
- JSON ist valide, enthält aber weder ein direktes Array noch die erwarteten Keys
- Falsches oder unerwartetes Datenformat

**Lösung:**
- Prüfen Sie die JSON-Struktur
- Unterstützte Formate sind:
    - Direktes Array: `[{...}, {...}]`
    - Objekt mit Key `data`, `facilities` oder `brk_facilities`

## 🛠️ Manuelle API-Tests

### 1. Browser-Test
```
https://api.brk.id/api/v1/assets/facility.json
```
**Erwartung:** Download einer JSON-Datei oder Anzeige im Browser

### 2. cURL-Test
```bash
curl -v https://api.brk.id/api/v1/assets/facility.json
```
**Erwartung:** Status 200, JSON-Daten

### 3. WordPress-Test (WP-CLI)
```bash
wp eval '
$response = wp_remote_get("https://api.brk.id/api/v1/assets/facility.json");
if (is_wp_error($response)) {
    echo "Fehler: " . $response->get_error_message() . "\n";
} else {
    echo "Status: " . wp_remote_retrieve_response_code($response) . "\n";
    echo "Content-Type: " . wp_remote_retrieve_header($response, "content-type") . "\n";
    echo "Body Length: " . strlen(wp_remote_retrieve_body($response)) . " bytes\n";
}
'
```

### 4. PHP-Test-Script
```php
<?php
// test-api.php im WordPress-Root
require_once('wp-load.php');

$loader = BRK_Facilities_Loader::get_instance();
$result = $loader->test_api_connection();

echo "Test-Ergebnis:\n";
print_r($result);

echo "\n\nFacilities laden:\n";
$facilities = $loader->get_facilities();

if (is_wp_error($facilities)) {
    echo "Fehler: " . $facilities->get_error_message() . "\n";
} else {
    echo "Erfolg! Anzahl: " . count($facilities) . "\n";
    echo "Erste 3 IDs: " . implode(', ', array_slice(array_column($facilities, 'id'), 0, 3)) . "\n";
}
?>
```

Ausführen:
```bash
php test-api.php
```

## 📝 Checkliste für Support-Anfragen

Wenn Sie den BRK-Support kontaktieren, geben Sie bitte an:

- [ ] **Fehlertyp:** (aus "Fehlerdetails anzeigen")
- [ ] **Fehlermeldung:** (aus "Fehlerdetails anzeigen")
- [ ] **HTTP Status:** (falls vorhanden)
- [ ] **Zeitpunkt:** (wann trat der Fehler auf)
- [ ] **Verbindungstest-Ergebnis:** (Screenshot oder Text)
- [ ] **PHP Version:** (aus Debug-Informationen)
- [ ] **WordPress Version:** (aus Debug-Informationen)
- [ ] **Server-Typ:** (Apache/Nginx, Hoster-Name)
- [ ] **Firewall/Proxy:** (Ja/Nein/Unbekannt)
- [ ] **PHP Error-Log:** (relevante Zeilen)

## 🎯 Zusammenfassung

**Das Plugin zeigt jetzt:**
1. ✅ Alle Fehlerdetails im Backend
2. ✅ Interaktiven Verbindungstest
3. ✅ Detaillierte Logs
4. ✅ Hilfreiche Fehlermeldungen
5. ✅ Antwort-Vorschauen

**Sie können jetzt:**
- Sofort sehen, warum die API nicht funktioniert
- Die Verbindung testen ohne Code zu schreiben
- Support mit präzisen Informationen kontaktieren
- Selbstständig Probleme diagnostizieren

---

**Version:** 1.2.3 mit erweiterter Fehlerdiagnose und Footer-Link-Features  
**Datum:** 11. Februar 2026

## 🔗 Footer-Link Debugging (YooTheme)

### Problem: "Kein Impressum-Link im Footer gefunden"

**Schritt 1: Debug-Ansicht öffnen**
1. Gehen Sie zu **Netzwerkverwaltung > BRK Impressum Tools**
2. In der Nutzungs-Tabelle: Klick auf "Debug" in der Footer-Link-Spalte

**Schritt 2: Widget-Struktur prüfen**
Die Debug-Ansicht zeigt:
- Alle YooTheme Builder Widgets in der Bottom-Sidebar
- JSON-Inhalt der Widgets
- Gefundene Link-Strukturen
- Status der Link-Prüfung

**Schritt 3: Häufige Probleme**

#### Problem: Widget nicht in "bottom" Sidebar
- **Lösung**: Verschieben Sie das Footer-Widget in die "Bottom"-Sidebar
- Plugin prüft nur diese Sidebar

#### Problem: Kein Text "Impressum" gefunden
- **Prüfen**: Hat das Navigations-Element den Text "Impressum"?
- Plugin sucht in Feldern: `content`, `text`, `title`, `label`
- **Lösung**: Text muss "Impressum" enthalten (Groß-/Kleinschreibung egal)

#### Problem: Link ist kein YooTheme Builder Widget
- Plugin arbeitet nur mit `builderwidget` Typ
- **Lösung**: Erstellen Sie den Footer mit YooTheme Builder

### Browser-Console verwenden

Öffnen Sie die Browser-Console (F12) beim Klick auf "Impressum in Footer übernehmen":

**Erwartete Ausgaben:**
```javascript
bindEvents wird ausgeführt
Footer-Button existiert: 1
updateFooterLink wurde aufgerufen
Button: [object Object]
brkImpressum: {ajaxUrl: "...", nonce: "..."}
Starte AJAX-Request...
AJAX Success: {success: true, data: "Footer-Link wurde aktualisiert (1 Link(s) in 1 Widget(s))"}
```

**Fehlermeldungen interpretieren:**
- `0 Builder-Widget(s) gefunden` → Widget nicht in bottom Sidebar oder nicht vom Typ builderwidget
- `1 Builder-Widget(s) gefunden, aber kein Impressum-Link darin` → Link hat keinen "Impressum"-Text oder falsches Feld-Format
- `Debug: [...]` → Zeigt alle gefundenen Links im Widget
