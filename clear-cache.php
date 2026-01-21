<?php
/**
 * BRK Impressum - Cache manuell löschen
 * 
 * Diese Datei einmal aufrufen, um den Cache zu löschen:
 * Beispiel: https://ihre-domain.de/wp-content/mu-plugins/clear-cache.php
 * 
 * WICHTIG: Nach dem Ausführen diese Datei wieder LÖSCHEN!
 */

// WordPress laden
require_once(__DIR__ . '/../../../wp-load.php');

// Cache löschen
delete_transient('brk_impressum_facilities');
delete_transient('brk_impressum_last_error');

// Bestätigung ausgeben
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRK Impressum - Cache gelöscht</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .success-box {
            background: #fff;
            border-left: 4px solid #46b450;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            color: #46b450;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 20px;
        }
        a {
            display: inline-block;
            background: #2271b1;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 3px;
            margin-top: 15px;
        }
        a:hover {
            background: #135e96;
        }
    </style>
</head>
<body>
    <div class="success-box">
        <h1>✓ Cache erfolgreich gelöscht!</h1>
        <p>Die folgenden Transients wurden gelöscht:</p>
        <ul>
            <li><code>brk_impressum_facilities</code></li>
            <li><code>brk_impressum_last_error</code></li>
        </ul>
        <p>Beim nächsten Aufruf werden die Facilities-Daten neu von der API geladen.</p>
        <a href="<?php echo admin_url('options-general.php?page=brk-impressum'); ?>">
            → Zur BRK Impressum Einstellungsseite
        </a>
    </div>
    
    <div class="warning">
        <strong>⚠️ Wichtig:</strong> Löschen Sie diese Datei jetzt aus Sicherheitsgründen:
        <br><code>wp-content/mu-plugins/clear-cache.php</code>
    </div>
</body>
</html>
