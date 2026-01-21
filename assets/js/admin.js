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
            $('#brk-preview-btn').on('click', this.showPreview.bind(this));
            $('#brk-save-btn').on('click', this.saveImpressum.bind(this));
            $('#brk-refresh-cache').on('click', this.refreshCache.bind(this));
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
            const responsibleFunction = $('#responsible_function').val();
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
                    responsible_function: responsibleFunction,
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
            const responsibleFunction = $('#responsible_function').val();
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
                    responsible_function: responsibleFunction,
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
                    $btn.prop('disabled', false).text('Daten jetzt aktualisieren');
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
         * Tooltips initialisieren
         */
        initTooltips: function() {
            // Falls WordPress Tooltips unterstützt
            if (typeof $.fn.tooltip !== 'undefined') {
                $('.description').tooltip();
            }
        }
    };
    
    /**
     * Beim DOM-Ready initialisieren
     */
    $(document).ready(function() {
        BRKImpressumAdmin.init();
    });
    
})(jQuery);
