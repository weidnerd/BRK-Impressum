<?php
/**
 * BRK Impressum Generator
 * 
 * Generiert das Impressum HTML basierend auf Facility-Daten und Verantwortlichen-Informationen
 */

if (!defined('ABSPATH')) {
    exit;
}

class BRK_Impressum_Generator {
    
    /**
     * Singleton-Instanz
     */
    private static $instance = null;
    
    /**
     * Facilities Loader Instanz
     */
    private $loader;
    
    /**
     * Singleton-Instanz abrufen
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konstruktor
     */
    private function __construct() {
        $this->loader = BRK_Facilities_Loader::get_instance();
    }
    
    /**
     * Impressum generieren
     * 
     * @param string $facility_id Facility-ID
     * @param string $responsible_name Name des Verantwortlichen
     * @param string $responsible_function Funktion des Verantwortlichen
     * @param string $responsible_email E-Mail des Verantwortlichen
     * @return string|WP_Error HTML-String des Impressums oder WP_Error
     */
    public function generate_impressum($facility_id, $responsible_name, $responsible_function, $responsible_email) {
        // Facility ID IMMER als String behandeln
        $facility_id = strval($facility_id);
        
        // Debug-Logging
        error_log('BRK Impressum: Generating impressum for facility_id: [' . $facility_id . '] (type: ' . gettype($facility_id) . ')');
        error_log('BRK Impressum: Comparison result ($facility_id !== "000"): ' . var_export($facility_id !== '000', true));
        
        // Facility-Daten laden
        $facility = $this->loader->get_facility_by_id($facility_id);
        
        if (is_wp_error($facility)) {
            return $facility;
        }
        
        // Landesverband-Daten laden
        $landesverband = $this->loader->get_landesverband();
        
        if (is_wp_error($landesverband)) {
            return $landesverband;
        }
        
        // HTML generieren
        $html = $this->build_impressum_html(
            $facility_id,
            $facility,
            $landesverband,
            $responsible_name,
            $responsible_function,
            $responsible_email
        );
        
        return $html;
    }
    
    /**
     * Impressum HTML aufbauen
     */
    private function build_impressum_html($facility_id, $facility, $landesverband, $responsible_name, $responsible_function, $responsible_email) {
        $domain = get_site_url();
        $facility_ebene = $this->loader->get_nested_value($facility, 'ebene', 'Verband');
        $facility_name = $this->loader->get_nested_value($facility, 'name', '');
        
        ob_start();
        ?>
        <div class="brk-impressum">
            <!-- DEBUG: facility_id=[<?php echo esc_html($facility_id); ?>], comparison=(<?php echo var_export($facility_id !== '000', true); ?>) -->
            <h2>Anbieterkennung nach § 5 TMG</h2>
            
            <p>Redakteur und Webmaster für die Planung, Realisierung und Betreuung der Internetinhalte sowie für die Administration der Domain <strong><?php echo esc_html($domain); ?></strong> ist:</p>
            
            <?php if (!empty($responsible_name)): ?>
            <p>
                <strong><?php echo esc_html($responsible_name); ?></strong><br>
                <?php if (!empty($responsible_function)): ?>
                    <?php echo esc_html($responsible_function); ?><br>
                <?php endif; ?>
                <?php if (!empty($responsible_email)): ?>
                    E-Mail: <a href="mailto:<?php echo esc_attr($responsible_email); ?>"><?php echo esc_html($responsible_email); ?></a>
                <?php endif; ?>
            </p>
            <?php endif; ?>
            
            <?php if ($facility_id !== '000'): // Nur bei anderen Verbänden anzeigen, nicht beim Landesverband ?>
            <h3>Angaben zum <?php echo esc_html($facility_ebene); ?>:</h3>
            
            <p>
                <strong>Bayerisches Rotes Kreuz</strong><br>
                Körperschaft des öffentlichen Rechts<br>
                <strong><?php echo esc_html($facility_name); ?></strong><br>
                <?php 
                $strasse = $this->loader->get_nested_value($facility, 'anschrift.strasse');
                if (!empty($strasse)): 
                ?>
                    <?php echo esc_html($strasse); ?><br>
                <?php endif; ?>
                <?php 
                $plz = $this->loader->get_nested_value($facility, 'anschrift.plz');
                $ort = $this->loader->get_nested_value($facility, 'anschrift.ort');
                if (!empty($plz) || !empty($ort)): 
                ?>
                    <?php echo esc_html($plz . ' ' . $ort); ?><br>
                <?php endif; ?>
            </p>
            
            <?php
            $telefon = $this->loader->get_nested_value($facility, 'kontakt.telefon');
            $email = $this->loader->get_nested_value($facility, 'kontakt.email');
            if (!empty($telefon) || !empty($email)):
            ?>
            <p>
                <?php if (!empty($telefon)): ?>
                    Telefon: <?php echo esc_html($telefon); ?><br>
                <?php endif; ?>
                <?php if (!empty($email)): ?>
                    E-Mail: <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                <?php endif; ?>
            </p>
            <?php endif; ?>
            
            <?php
            $vorstand_funktion = $this->loader->get_nested_value($facility, 'vorstand.funktion');
            $vorstand_name = $this->loader->get_nested_value($facility, 'vorstand.name');
            $gf_funktion = $this->loader->get_nested_value($facility, 'geschaeftsfuehrung.funktion');
            $gf_name = $this->loader->get_nested_value($facility, 'geschaeftsfuehrung.name');
            
            if (!empty($vorstand_funktion) || !empty($gf_funktion)):
            ?>
            <p>
                <?php if (!empty($vorstand_funktion) && !empty($vorstand_name)): ?>
                    <?php echo esc_html($vorstand_funktion); ?>: <?php echo esc_html($vorstand_name); ?><br>
                <?php endif; ?>
                <?php if (!empty($gf_funktion) && !empty($gf_name)): ?>
                    <?php echo esc_html($gf_funktion); ?>: <?php echo esc_html($gf_name); ?>
                <?php endif; ?>
            </p>
            <?php endif; ?>
            
            <?php if (!empty($gf_funktion) && !empty($gf_name)): ?>
            <p>
                <strong>Verantwortlich i.S.d. § 5 TMG:</strong><br>
                <?php echo esc_html($gf_funktion); ?>: <?php echo esc_html($gf_name); ?>
                <?php 
                $gf_email = $this->loader->get_nested_value($facility, 'geschaeftsfuehrung.email');
                if (!empty($gf_email)): 
                ?>
                    <br>E-Mail: <a href="mailto:<?php echo esc_attr($gf_email); ?>"><?php echo esc_html($gf_email); ?></a>
                <?php endif; ?>
            </p>
            <?php endif; ?>
            <?php endif; // Ende der Bedingung für facility_id !== '000' ?>
            
            <h3>Angaben zum Landesverband:</h3>
            
            <p>Das Bayerische Rote Kreuz ist eine Körperschaft des öffentlichen Rechts. Zuständige Aufsichtsbehörde ist das Bayerische Staatsministerium des Inneren.</p>
            
            <p>
                <strong>Bayerisches Rotes Kreuz</strong><br>
                Körperschaft des öffentlichen Rechts<br>
                Landesgeschäftsstelle<br>
                <?php 
                $lv_strasse = $this->loader->get_nested_value($landesverband, 'anschrift.strasse');
                if (!empty($lv_strasse)): 
                ?>
                    <?php echo esc_html($lv_strasse); ?><br>
                <?php endif; ?>
                <?php 
                $lv_plz = $this->loader->get_nested_value($landesverband, 'anschrift.plz');
                $lv_ort = $this->loader->get_nested_value($landesverband, 'anschrift.ort');
                if (!empty($lv_plz) || !empty($lv_ort)): 
                ?>
                    <?php echo esc_html($lv_plz . ' ' . $lv_ort); ?><br>
                <?php endif; ?>
            </p>
            
            <?php
            $lv_telefon = $this->loader->get_nested_value($landesverband, 'kontakt.telefon');
            $lv_email = $this->loader->get_nested_value($landesverband, 'kontakt.email');
            if (!empty($lv_telefon) || !empty($lv_email)):
            ?>
            <p>
                <?php if (!empty($lv_telefon)): ?>
                    Telefon: <?php echo esc_html($lv_telefon); ?><br>
                <?php endif; ?>
                <?php if (!empty($lv_email)): ?>
                    E-Mail: <a href="mailto:<?php echo esc_attr($lv_email); ?>"><?php echo esc_html($lv_email); ?></a>
                <?php endif; ?>
            </p>
            <?php endif; ?>
            
            <?php
            $lv_vorstand_funktion = $this->loader->get_nested_value($landesverband, 'vorstand.funktion');
            $lv_vorstand_name = $this->loader->get_nested_value($landesverband, 'vorstand.name');
            $lv_gf_funktion = $this->loader->get_nested_value($landesverband, 'geschaeftsfuehrung.funktion');
            $lv_gf_name = $this->loader->get_nested_value($landesverband, 'geschaeftsfuehrung.name');
            
            if (!empty($lv_vorstand_funktion) || !empty($lv_gf_funktion)):
            ?>
            <p>
                <?php if (!empty($lv_vorstand_funktion) && !empty($lv_vorstand_name)): ?>
                    <?php echo esc_html($lv_vorstand_funktion); ?>: <?php echo esc_html($lv_vorstand_name); ?><br>
                <?php endif; ?>
                <?php if (!empty($lv_gf_funktion) && !empty($lv_gf_name)): ?>
                    <?php echo esc_html($lv_gf_funktion); ?>: <?php echo esc_html($lv_gf_name); ?>
                <?php endif; ?>
            </p>
            <?php endif; ?>
            
            <?php echo $this->get_static_content(); ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Statischen Inhalt des Impressums zurückgeben
     */
    private function get_static_content() {
        ob_start();
        ?>
        <p>Das Bayerische Rote Kreuz stellt seine Inhalte im Internet sorgfältig zusammen, übernimmt jedoch keine Haftung für mögliche Übertragungsfehler von Schriftstücken oder Fehler auf Grund unbefugter Veränderungen durch Dritte.</p>
        
        <p>Eine Gewährleistung für die Vollständigkeit, Richtigkeit und Aktualität kann nicht übernommen werden.</p>
        
        <p>Die hier enthaltenen Informationen enthalten die gesetzlich vorgesehenen Pflichtangaben zur Anbieterkennzeichnung zur Internetpräsenz des Bayerischen Roten Kreuzes.</p>
        
        <h3>Rechtsstellung und Vertretungsberechtigte des Bayerischen Roten Kreuzes (BRK)</h3>
        
        <p>Das Bayerische Rote Kreuz ist gemäß Gesetz über die Rechtsstellung des Bayerischen Roten Kreuzes vom 16.07.1986 (Gesetz über die Rechtsstellung des Bayerischen Roten Kreuzes vom 16.7.1986, Bayerisches Gesetz- und Verordnungsblatt Nr. 13/1986, 281-1-I, zuletzt geändert durch Gesetz vom 27.12.1999) eine Körperschaft des öffentlichen Rechts mit Sitz in München. Die besonderen Regelungen unterliegende Rechtsaufsicht führt das Bayerische Staatsministerium des Inneren, für Bau und Verkehr, Odeonsplatz 3, 80539 München.</p>
        
        <p>Weiteres wird in der BRK-Satzung vom 21.7.2001 (Bekanntmachung vom 8.11.2001, StAnz Nr. 47), zuletzt geändert am 7.12.2013 (Bekanntmachung vom 26.2.2014, StAnz Nr. 10) geregelt.</p>
        
        <p>Lob und/oder Beschwerden können Sie hier (auch anonym) mitteilen.</p>
        
        <h3>Das Bayerische Rote Kreuz ist ein Mitgliedsverband des Deutschen Roten Kreuzes e.V.</h3>
        
        <h4>Vereinsregistereintrag des DRK e.V.</h4>
        
        <p>Das Deutsche Rote Kreuz e.V. ist im Vereinsregister beim Amtsgericht Berlin-Charlottenburg unter der Registernummer 95 VR 590 B eingetragen.</p>
        
        <h4>Vertretungsberechtigte des DRK e.V.</h4>
        
        <p>Das Deutsche Rote Kreuz e.V. wird gesetzlich vertreten durch Christian Reuter, Generalsekretär und Vorsitzender des Vorstands.</p>
        
        <h3>Urheberrecht</h3>
        
        <p>Der Landesverband des BRK ist bestrebt, in allen Publikationen die Urheberrechte der verwendeten Grafiken, Tondokumente, Videosequenzen und Texte zu beachten, von ihm selbst erstellte Grafiken, Tondokumente, Videosequenzen und Texte zu nutzen oder auf lizenzfreie Grafiken, Tondokumente, Videosequenzen und Texte zurückzugreifen.</p>
        
        <p>Das Copyright für veröffentlichte, selbst erstellte Objekte bleibt allein beim BRK-Landesverband. Eine Vervielfältigung oder Verwendung solcher Grafiken, Tondokumente, Videosequenzen und Texte in anderen elektronischen oder gedruckten Publikationen ist ohne ausdrückliche Zustimmung des BRK-Landesverbands nicht gestattet.</p>
        
        <h3>Verbraucherschlichtung</h3>
        
        <p>Das BRK nimmt derzeit nicht an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teil.</p>
        <?php
        
        return ob_get_clean();
    }
}
