# Schnelltest-Anleitung - BRK Impressum Generator

## ✅ Problem gelöst: Fallback-System implementiert

Das Plugin funktioniert jetzt auch **ohne Zugang zur Live-API**!

### Was wurde hinzugefügt?

1. **Automatischer Fallback-Mechanismus**
   - Wenn die API nicht erreichbar ist, werden automatisch lokale Daten verwendet
   - Keine Fehlermeldung mehr, sondern nur ein Hinweis
   - Das Plugin bleibt voll funktionsfähig

2. **3-stufiges Fallback-System:**
   ```
   1. Versuch: Live-API (https://api.brk.id/api/v1/assets/facility.json)
                ↓ (bei Fehler)
   2. Versuch: Lokale Datei (facilities-example.json)
                ↓ (bei Fehler)
   3. Versuch: Hardcoded Demo-Daten im Code
   ```

3. **Debug-Tools**
   - API-Verbindungstest direkt im Backend
   - Detaillierte Debug-Informationen
   - Bessere Fehlermeldungen im Log

### Sofort loslegen (ohne API-Zugang)

Das Plugin ist **sofort einsatzbereit**:

1. **Plugin installieren**
   ```bash
   # Dateien nach wp-content/plugins/brk-impressum/ hochladen
   ```

2. **Plugin aktivieren**
   - In WordPress: Plugins > Installierte Plugins > BRK Impressum aktivieren

3. **Konfigurieren**
   - Gehen Sie zu: Einstellungen > BRK Impressum
   - Sie sehen eine **Warnung** (gelb), dass Fallback-Daten verwendet werden
   - Das ist **normal** und kein Fehler!

4. **Verband auswählen**
   - Dropdown zeigt Demo-Verbände:
     - `000 - BRK Landesverband Bayern`
     - `001 - BRK Beispiel-Kreisverband`

5. **Verantwortlichen eingeben**
   ```
   Name: Max Mustermann
   Funktion: Webmaster
   E-Mail: max.mustermann@beispiel.brk.de
   ```

6. **Vorschau anzeigen**
   - Klick auf "Vorschau anzeigen"
   - Rechts erscheint das vollständige Impressum

7. **Impressum übernehmen**
   - Klick auf "Impressum übernehmen"
   - Seite wird unter `/impressum` erstellt
   - ✓ Fertig!

8. **Footer-Link aktualisieren (YooTheme)**
   - Wenn Sie YooTheme verwenden, prüft das Plugin automatisch Ihren Footer
   - Status wird angezeigt: ✓ Ja / ✗ Nein / ✗ Falsch
   - Klick auf "Impressum in Footer übernehmen" aktualisiert den Link automatisch
   - Nur der Link mit "Impressum"-Text wird geändert, andere Links bleiben unberührt

### API-Verbindung testen

Im Backend unter "Fehlerbehebung":

1. **Debug-Informationen aufklappen**
2. **"API-Verbindung testen" klicken**
3. Ergebnis:
   - ✓ API erreichbar → Live-Daten werden verwendet
   - ✗ API nicht erreichbar → Fallback-Daten bleiben aktiv

### Für Produktionsumgebung: API einrichten

**Wenn Sie später zur Live-API wechseln möchten:**

1. **API-Zugang bei BRK anfordern**
   - Kontakt: support@brk.de
   - Berechtigung für `https://api.brk.id/api/v1/assets/facility.json`

2. **Im Plugin nichts ändern**
   - Das Plugin erkennt automatisch, wenn die API erreichbar ist
   - Es wechselt automatisch von Fallback zu Live-Daten

3. **Cache aktualisieren**
   - Button "Daten jetzt aktualisieren" klicken
   - Plugin lädt neue Daten von der API

### Eigene Facilities-Daten verwenden

**Option 1: Lokale JSON-Datei aktualisieren**

Bearbeiten Sie `facilities-example.json` mit Ihren echten Daten:

```json
[
  {
    "id": "123",
    "ebene": "Kreisverband",
    "name": "BRK Kreisverband Ihr-Ort",
    "anschrift": {
      "strasse": "Ihre Straße 1",
      "plz": "12345",
      "ort": "Ihr Ort"
    },
    "kontakt": {
      "telefon": "012345 67890",
      "email": "info@ihr-kv.brk.de"
    },
    "vorstand": {
      "funktion": "Vorsitzender",
      "name": "Ihr Name"
    },
    "geschaeftsfuehrung": {
      "funktion": "Geschäftsführer",
      "name": "Name Geschäftsführer",
      "email": "gf@ihr-kv.brk.de"
    }
  }
]
```

**Option 2: Eigene API-URL verwenden**

```php
// In wp-config.php oder functions.php
define('BRK_IMPRESSUM_FACILITIES_URL', 'https://ihre-eigene-api.de/facilities.json');
```

### Testen der Funktionen

#### Test 1: Vorschau generieren
```
1. Verband auswählen: "000 - BRK Landesverband Bayern"
2. Name: "Test User"
3. Funktion: "Tester"
4. E-Mail: "test@example.com"
5. "Vorschau anzeigen" → Sollte vollständiges Impressum zeigen
```

#### Test 2: Seite erstellen
```
1. Nach Vorschau: "Impressum übernehmen" klicken
2. Erfolgs-Meldung erscheint
3. Seite öffnen: https://ihre-domain.de/impressum
4. ✓ Impressum sollte sichtbar sein
```

#### Test 3: Shortcode testen
```
1. Neue Seite erstellen: Seiten > Erstellen
2. Titel: "Test Impressum"
3. Inhalt: [brk_impressum]
4. Veröffentlichen
5. Seite ansehen → Impressum sollte angezeigt werden
```

### Häufige Fragen (FAQ)

**Q: Ist die gelbe Warnung ein Fehler?**
A: Nein! Es ist nur ein Hinweis, dass Demo-Daten verwendet werden. Das Plugin funktioniert trotzdem.

**Q: Kann ich das Plugin produktiv nutzen ohne API?**
A: Ja! Bearbeiten Sie einfach `facilities-example.json` mit Ihren echten Daten.

**Q: Wann brauche ich die API?**
A: Nur wenn Sie automatische Updates von der BRK-Zentrale möchten. Für einzelne Sites reicht die lokale JSON-Datei.

**Q: Funktioniert das mit Multisite?**
A: Ja! Jede Site kann einen eigenen Verband auswählen (aus den gleichen Facilities).

**Q: Wie oft werden Daten aktualisiert?**
A: Automatisch alle 24 Stunden. Manuell per "Daten jetzt aktualisieren" Button.

### Debug-Checkliste

Bei Problemen prüfen:

- [ ] Plugin aktiviert?
- [ ] WordPress-Version ≥ 5.0?
- [ ] PHP-Version ≥ 7.4?
- [ ] Datei `facilities-example.json` vorhanden?
- [ ] Benutzerrechte `manage_options` vorhanden?
- [ ] JavaScript-Fehler in Browser-Konsole (F12)?
- [ ] PHP-Fehler in `wp-content/debug.log`?

### Support

**Funktioniert nicht?**
1. Öffnen Sie Backend > Einstellungen > BRK Impressum
2. Klappen Sie "Debug-Informationen" auf
3. Kopieren Sie alle Informationen
4. Senden Sie an: support@brk.de

**Alles funktioniert?**
🎉 Herzlichen Glückwunsch! Ihr Impressum ist eingerichtet.

### Nächste Schritte

- [ ] Impressum im Menü verlinken (Design > Menüs)
- [ ] Im Footer einbinden
- [ ] Datenschutzerklärung erstellen (separates Plugin/Seite)
- [ ] Regelmäßig auf Updates prüfen

---

**Version:** 1.2.3 mit Fallback-System  
**Datum:** 12. Februar 2026  
**Status:** ✅ Produktionsbereit
