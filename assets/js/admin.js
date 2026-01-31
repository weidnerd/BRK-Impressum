/**
 * BRK Impressum - Admin JavaScript
 */

(function($) {
    'use strict';
    
    const BRKImpressumAdmin = {
        
        /**
         * Initialisierung
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },
        
        /**
         * Event-Listener binden
         */
        bindEvents: function() {
            console.log('bindEvents wird ausgeführt');
            console.log('Footer-Button existiert:', $('#brk-update-footer-btn').length);
            
            $('#brk-preview-btn').on('click', this.showPreview.bind(this));
            $('#brk-save-btn').on('click', this.saveImpressum.bind(this));
            
            // Footer-Button mit Event-Delegation
            $(document).on('click', '#brk-update-footer-btn', this.updateFooterLink.bind(this));
            
            $('#brk-refresh-cache').on('click', this.refreshCache.bind(this));
            $('#brk-test-api').on('click', this.testApiConnection.bind(this));
            $('#brk-impressum-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Facility-Auswahl
            $('#facility_id').on('change', function() {
                $('#brk-impressum-preview').html(
                    '<p class="description">Klicken Sie auf "Vorschau anzeigen" um das Impressum zu sehen.</p>'
                );
            });
        },
        
        /**
         * Formular-Submit verhindern und speichern
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            this.saveImpressum(e);
            return false;
        },
        
        /**
         * Vorschau anzeigen
         */
        showPreview: function(e) {
            e.preventDefault();
            
            const facilityId = $('#facility_id').val();
            const responsibleName = $('#responsible_name').val();
            const responsibleEmail = $('#responsible_email').val();
            
            // Validierung
            if (!facilityId || !responsibleName || !responsibleEmail) {
                this.showMessage('error', 'Bitte füllen Sie alle Pflichtfelder aus.');
                return;
            }
            
            if (!this.validateEmail(responsibleEmail)) {
                this.showMessage('error', 'Bitte geben Sie eine gültige E-Mail-Adresse ein.');
                return;
            }
            
            // Loading-Status
            this.showMessage('info', brkImpressum.strings.preview);
            $('#brk-impressum-preview').html('<div class="brk-loading">Vorschau wird geladen...</div>');
            
            // API-Aufruf
            $.ajax({
                url: brkImpressum.restUrl + 'preview',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', brkImpressum.restNonce);
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    facility_id: facilityId,
                    responsible_name: responsibleName,
                    responsible_email: responsibleEmail
                }),
                success: function(response) {
                    $('#brk-impressum-preview').html(response.html);
                    this.showMessage('success', 'Vorschau erfolgreich geladen.');
                }.bind(this),
                error: function(xhr) {
                    let errorMsg = 'Fehler beim Laden der Vorschau.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ' ' + xhr.responseJSON.message;
                    }
                    this.showMessage('error', errorMsg);
                    $('#brk-impressum-preview').html(
                        '<div class="notice notice-error"><p>' + errorMsg + '</p></div>'
                    );
                }.bind(this)
            });
        },
        
        /**
         * Impressum speichern
         */
        saveImpressum: function(e) {
            e.preventDefault();
            
            const facilityId = $('#facility_id').val();
            const responsibleName = $('#responsible_name').val();
            const responsibleEmail = $('#responsible_email').val();
            
            // Validierung
            if (!facilityId || !responsibleName || !responsibleEmail) {
                this.showMessage('error', 'Bitte füllen Sie alle Pflichtfelder aus.');
                return;
            }
            
            if (!this.validateEmail(responsibleEmail)) {
                this.showMessage('error', 'Bitte geben Sie eine gültige E-Mail-Adresse ein.');
                return;
            }
            
            // Bestätigung
            if (!confirm('Möchten Sie das Impressum wirklich übernehmen? Eine Seite wird erstellt/aktualisiert.')) {
                return;
            }
            
            // Loading-Status
            $('#brk-save-btn').prop('disabled', true).text('Speichert...');
            this.showMessage('info', brkImpressum.strings.loading);
            
            // API-Aufruf
            $.ajax({
                url: brkImpressum.restUrl + 'save',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', brkImpressum.restNonce);
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    facility_id: facilityId,
                    responsible_name: responsibleName,
                    responsible_email: responsibleEmail
                }),
                success: function(response) {
                    this.showMessage('success', brkImpressum.strings.saved);
                    
                    // Seite neu laden nach 1.5 Sekunden
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                }.bind(this),
                error: function(xhr) {
                    let errorMsg = 'Fehler beim Speichern.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ' ' + xhr.responseJSON.message;
                    }
                    this.showMessage('error', errorMsg);
                    $('#brk-save-btn').prop('disabled', false).text('Impressum übernehmen');
                }.bind(this)
            });
        },
        
        /**
         * Cache aktualisieren
         */
        refreshCache: function(e) {
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Aktualisiere...');
            
            $.ajax({
                url: brkImpressum.restUrl + 'facilities',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', brkImpressum.restNonce);
                },
                data: {
                    refresh: true
                },
                success: function() {
                    this.showMessage('success', 'Cache erfolgreich aktualisiert. Seite wird neu geladen...');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                }.bind(this),
                error: function(xhr) {
                    let errorMsg = 'Fehler beim Aktualisieren des Caches.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ' ' + xhr.responseJSON.message;
                    }
                    this.showMessage('error', errorMsg);
                    $btn.prop('disabled', false).text(originalText);
                }.bind(this)
            });
        },
        
        /**
         * API-Verbindung testen
         */
        testApiConnection: function(e) {
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('🔄 Teste...');
            
            this.showMessage('info', 'Verbindungstest läuft...');
            
            $.ajax({
                url: brkImpressum.restUrl + 'test-connection',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', brkImpressum.restNonce);
                },
                success: function(response) {
                    let message = '<strong>🔍 Verbindungstest abgeschlossen:</strong><br><br>';
                    
                    if (response.success) {
                        message += '✅ <strong>Erfolgreich!</strong><br><br>';
                        message += '<strong>Details:</strong><br>';
                        message += 'HTTP Status: ' + response.http_code + '<br>';
                        message += 'Antwortzeit: ' + response.duration + '<br>';
                        message += 'Facilities gefunden: ' + response.facilities_count + '<br>';
                        message += 'Content-Type: ' + response.content_type + '<br>';
                        if (response.sample_ids && response.sample_ids.length > 0) {
                            message += 'Beispiel-IDs: ' + response.sample_ids.join(', ') + '<br>';
                        }
                        message += '<br><em>Die API funktioniert korrekt!</em>';
                        this.showMessage('success', message);
                    } else {
                        message += '❌ <strong>Verbindung fehlgeschlagen</strong><br><br>';
                        message += '<strong>Details:</strong><br>';
                        message += 'URL: <code>' + response.url + '</code><br>';
                        
                        if (response.error) {
                            message += '<br><strong>Fehler:</strong> ' + response.error + '<br>';
                            if (response.error_code) {
                                message += 'Code: ' + response.error_code + '<br>';
                            }
                        }
                        
                        if (response.http_code) {
                            message += '<br><strong>HTTP Status:</strong> ' + response.http_code;
                            if (response.http_message) {
                                message += ' - ' + response.http_message;
                            }
                            message += '<br>';
                        }
                        
                        if (response.json_error) {
                            message += '<br><strong>JSON Fehler:</strong> ' + response.json_error + '<br>';
                        }
                        
                        if (response.content_type) {
                            message += '<br><strong>Content-Type:</strong> ' + response.content_type + '<br>';
                        }
                        
                        if (response.body_length) {
                            message += '<strong>Antwortgröße:</strong> ' + response.body_length + ' Bytes<br>';
                        }
                        
                        if (response.body_preview) {
                            message += '<br><strong>Antwort-Vorschau:</strong><br>';
                            message += '<pre style="background:#f5f5f5;padding:10px;max-height:150px;overflow:auto;font-size:11px;">' + 
                                       this.escapeHtml(response.body_preview) + '</pre>';
                        }
                        
                        if (response.duration) {
                            message += '<br><strong>Antwortzeit:</strong> ' + response.duration;
                        }
                        
                        this.showMessage('error', message);
                    }
                    
                    $btn.prop('disabled', false).text(originalText);
                }.bind(this),
                error: function(xhr) {
                    let errorMsg = '❌ <strong>Fehler beim Verbindungstest</strong><br><br>';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += xhr.responseJSON.message;
                    } else {
                        errorMsg += 'Ein unerwarteter Fehler ist aufgetreten.';
                    }
                    this.showMessage('error', errorMsg);
                    $btn.prop('disabled', false).text(originalText);
                }.bind(this)
            });
        },
        
        /**
         * Nachricht anzeigen
         */
        showMessage: function(type, message) {
            const $statusDiv = $('#brk-status-message');
            
            let cssClass = 'notice';
            switch(type) {
                case 'success':
                    cssClass += ' notice-success';
                    break;
                case 'error':
                    cssClass += ' notice-error';
                    break;
                case 'warning':
                    cssClass += ' notice-warning';
                    break;
                default:
                    cssClass += ' notice-info';
            }
            
            $statusDiv
                .removeClass()
                .addClass(cssClass)
                .html('<p>' + message + '</p>')
                .slideDown();
            
            // Nach 5 Sekunden ausblenden (außer bei Fehlern)
            if (type !== 'error') {
                setTimeout(function() {
                    $statusDiv.slideUp();
                }, 5000);
            }
        },
        
        /**
         * E-Mail validieren
         */
        validateEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        /**
         * HTML escapen für sichere Ausgabe
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },
        
        /**
         * Tooltips initialisieren
         */
        initTooltips: function() {
            // Falls WordPress Tooltips unterstützt
            if (typeof $.fn.tooltip !== 'undefined') {
                $('.description').tooltip();
            }
        },
        
        /**
         * Footer-Link aktualisieren
         */
        updateFooterLink: function(e) {
            console.log('updateFooterLink wurde aufgerufen');
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            const originalText = $btn.text();
            
            console.log('Button:', $btn);
            console.log('brkImpressum:', brkImpressum);
            
            if (!confirm('Möchten Sie den Impressum-Link im Footer wirklich aktualisieren?')) {
                console.log('Benutzer hat abgebrochen');
                return;
            }
            
            console.log('Starte AJAX-Request...');
            $btn.prop('disabled', true).text('Aktualisiere...');
            
            $.ajax({
                url: brkImpressum.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'brk_update_footer_link',
                    nonce: brkImpressum.nonce
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    if (response.success) {
                        this.showMessage('success', response.data || 'Footer-Link wurde erfolgreich aktualisiert');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.showMessage('error', response.data || 'Fehler beim Aktualisieren des Footer-Links');
                        $btn.prop('disabled', false).text(originalText);
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr, status, error);
                    this.showMessage('error', 'Fehler beim Aktualisieren des Footer-Links: ' + error);
                    $btn.prop('disabled', false).text(originalText);
                }.bind(this)
            });
        }
    };
    
    /**
     * Beim DOM-Ready initialisieren
     */
    $(document).ready(function() {
        BRKImpressumAdmin.init();
    });
    
})(jQuery);
