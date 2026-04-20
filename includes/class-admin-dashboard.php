<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Dashboard - Simplified admin interface for administrators
 */
class RT_Admin_Dashboard_V2 {
    
    public function __construct() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_kunden_form'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on settings page - check if we're on the settings page
        if (isset($_GET['page']) && $_GET['page'] === 'staff-manager-settings') {
            // Enqueue WordPress media uploader scripts
            wp_enqueue_media();
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Employee Manager V2', 'staff-manager'),
            __('Employee Manager V2', 'staff-manager'),
            'manage_options',
            'staff-manager-admin',
            array($this, 'admin_page'),
            'dashicons-businessman',
            25
        );
        
        add_submenu_page(
            'staff-manager-admin',
            __('Kunden verwalten', 'staff-manager'),
            __('Kunden', 'staff-manager'),
            'manage_options',
            'staff-manager-kunden',
            array($this, 'kunden_page')
        );
        
        add_submenu_page(
            'staff-manager-admin',
            __('Einstellungen', 'staff-manager'),
            __('Einstellungen', 'staff-manager'),
            'manage_options',
            'staff-manager-settings',
            array($this, 'settings_page')
        );
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        $total_employees = wp_count_posts('angestellte_v2')->publish;
        $total_clients = count(get_users(array('role' => 'kunden_v2')));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Staff Manager - Admin Dashboard', 'staff-manager'); ?></h1>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin: 20px 0;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3 style="margin-top: 0;"><?php _e('Mitarbeiter Gesamt', 'staff-manager'); ?></h3>
                    <p style="font-size: 24px; font-weight: bold; color: #0073aa; margin: 0;">
                        <?php echo esc_html($total_employees); ?>
                    </p>
                </div>
                
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3 style="margin-top: 0;"><?php _e('Kunden Gesamt', 'staff-manager'); ?></h3>
                    <p style="font-size: 24px; font-weight: bold; color: #46b450; margin: 0;">
                        <?php echo esc_html($total_clients); ?>
                    </p>
                </div>
                
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3 style="margin-top: 0;"><?php _e('Plugin Version', 'staff-manager'); ?></h3>
                    <p style="font-size: 24px; font-weight: bold; color: #666; margin: 0;">
                        <?php echo esc_html(RT_EMPLOYEE_V2_VERSION); ?>
                    </p>
                </div>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                <h3><?php _e('Schnellaktionen', 'staff-manager'); ?></h3>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=angestellte_v2'); ?>" class="button button-primary">
                        <?php _e('Alle Mitarbeiter verwalten', 'staff-manager'); ?>
                    </a>
                    <a href="<?php echo admin_url('users.php?role=kunden_v2'); ?>" class="button">
                        <?php _e('Kunden verwalten', 'staff-manager'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=staff-manager-settings'); ?>" class="button">
                        <?php _e('Einstellungen', 'staff-manager'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Kunden management page
     */
    public function kunden_page() {
        $message = '';
        if (isset($_GET['created']) && $_GET['created'] === '1') {
            $message = '<div class="notice notice-success"><p>' . __('Kunde wurde erfolgreich erstellt.', 'staff-manager') . '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Kunden verwalten', 'staff-manager'); ?></h1>
            
            <?php echo $message; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <!-- Create New Customer -->
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3><?php _e('Neuen Kunden erstellen', 'staff-manager'); ?></h3>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('create_kunde_v2', 'kunde_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="company_name"><?php _e('Firmenname', 'staff-manager'); ?> *</label></th>
                                <td><input type="text" name="company_name" id="company_name" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th><label for="contact_name"><?php _e('Ansprechpartner', 'staff-manager'); ?></label></th>
                                <td><input type="text" name="contact_name" id="contact_name" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label for="email"><?php _e('E-Mail', 'staff-manager'); ?> *</label></th>
                                <td><input type="email" name="email" id="email" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th><label for="phone"><?php _e('Telefon', 'staff-manager'); ?></label></th>
                                <td><input type="tel" name="phone" id="phone" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label for="uid_number"><?php _e('UID-Nummer', 'staff-manager'); ?></label></th>
                                <td><input type="text" name="uid_number" id="uid_number" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label for="address"><?php _e('Adresse', 'staff-manager'); ?> *</label></th>
                                <td><input type="text" name="address" id="address" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th><label for="postal_code"><?php _e('PLZ', 'staff-manager'); ?> *</label></th>
                                <td><input type="text" name="postal_code" id="postal_code" class="regular-text" maxlength="10" required /></td>
                            </tr>
                            <tr>
                                <th><label for="city"><?php _e('Ort', 'staff-manager'); ?></label></th>
                                <td><input type="text" name="city" id="city" class="regular-text" /></td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="create_kunde" class="button-primary" value="<?php _e('Kunde erstellen', 'staff-manager'); ?>" />
                        </p>
                    </form>
                </div>
                
                <!-- Existing Customers -->
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3><?php _e('Vorhandene Kunden', 'staff-manager'); ?></h3>
                    <?php $this->display_existing_kunden(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle kunden form submission
     */
    public function handle_kunden_form() {
        if (!isset($_POST['create_kunde']) || !isset($_POST['kunde_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['kunde_nonce'], 'create_kunde_v2')) {
            wp_die(__('Sicherheitsfehler.', 'staff-manager'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung.', 'staff-manager'));
        }
        
        // Validate required fields
        $required_fields = array('company_name', 'email', 'address', 'postal_code');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_die(sprintf(__('Feld "%s" ist erforderlich.', 'staff-manager'), $field));
            }
        }
        
        $company_name = sanitize_text_field($_POST['company_name']);
        $contact_name = sanitize_text_field($_POST['contact_name'] ?? '');
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $uid_number = sanitize_text_field($_POST['uid_number'] ?? '');
        $address = sanitize_text_field($_POST['address']);
        $postal_code = sanitize_text_field($_POST['postal_code']);
        $city = sanitize_text_field($_POST['city'] ?? '');
        
        // Check if email already exists
        if (email_exists($email)) {
            wp_die(__('Diese E-Mail-Adresse ist bereits registriert.', 'staff-manager'));
        }
        
        // Generate random password
        $password = wp_generate_password(12, true, true);
        
        // Create user account
        $username = sanitize_user($email);
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_die(__('Fehler beim Erstellen des Benutzerkontos: ', 'staff-manager') . $user_id->get_error_message());
        }
        
        // Set user role to kunden_v2
        $user = new WP_User($user_id);
        $user->set_role('kunden_v2');
        
        // Set user meta
        update_user_meta($user_id, 'company_name', $company_name);
        update_user_meta($user_id, 'contact_name', $contact_name);
        update_user_meta($user_id, 'phone', $phone);
        update_user_meta($user_id, 'uid_number', $uid_number);
        update_user_meta($user_id, 'company_address', $address);
        update_user_meta($user_id, 'company_postal_code', $postal_code);
        update_user_meta($user_id, 'company_city', $city);
        update_user_meta($user_id, 'created_by_admin', true);
        update_user_meta($user_id, 'created_at', current_time('mysql'));
        
        // Update user display name
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $company_name,
            'first_name' => $contact_name,
        ));
        
        // Get user object for password reset
        $user = new WP_User($user_id);
        
        // Send password reset link instead of password
        $reset_key = get_password_reset_key($user);
        $reset_link = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($username), 'login');
        
        // Send welcome email with password reset link
        $subject = sprintf(__('Ihr Konto bei %s wurde erstellt', 'staff-manager'), get_bloginfo('name'));
        $message = sprintf(
            __("Hallo %s,\n\nIhr Konto für %s wurde erfolgreich erstellt.\n\nBitte setzen Sie Ihr Passwort über folgenden Link:\n%s\n\nSie können sich danach hier anmelden:\n%s\n\nViele Grüße", 'staff-manager'),
            $contact_name ?: $company_name,
            $company_name,
            $reset_link,
            wp_login_url()
        );
        
        wp_mail($email, $subject, $message);
        
        // Redirect with success message
        wp_redirect(add_query_arg('created', '1', admin_url('admin.php?page=staff-manager-kunden')));
        exit;
    }
    
    /**
     * Display existing kunden
     */
    private function display_existing_kunden() {
        $kunden = get_users(array('role' => 'kunden_v2'));
        
        if (empty($kunden)) {
            echo '<p>' . __('Noch keine Kunden erstellt.', 'staff-manager') . '</p>';
            return;
        }
        
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Unternehmen', 'staff-manager') . '</th>';
        echo '<th>' . __('Ansprechpartner', 'staff-manager') . '</th>';
        echo '<th>' . __('E-Mail', 'staff-manager') . '</th>';
        echo '<th>' . __('Mitarbeiter', 'staff-manager') . '</th>';
        echo '<th>' . __('Aktionen', 'staff-manager') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($kunden as $kunde) {
            $company_name = get_user_meta($kunde->ID, 'company_name', true);
            $contact_name = get_user_meta($kunde->ID, 'contact_name', true);
            $employee_count = $this->get_employee_count_for_kunde($kunde->ID);
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($company_name) . '</strong></td>';
            echo '<td>' . esc_html($contact_name) . '</td>';
            echo '<td>' . esc_html($kunde->user_email) . '</td>';
            echo '<td>' . esc_html($employee_count) . '</td>';
            echo '<td>';
            echo '<a href="' . get_edit_user_link($kunde->ID) . '">' . __('Bearbeiten', 'staff-manager') . '</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Get employee count for kunde
     */
    private function get_employee_count_for_kunde($user_id) {
        $args = array(
            'post_type' => 'angestellte_v2',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'employer_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            )
        );

        return count(get_posts($args));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('rt_employee_v2_settings', 'rt_employee_v2_buchhaltung_email');
        register_setting('rt_employee_v2_settings', 'rt_employee_v2_pdf_template_header');
        register_setting('rt_employee_v2_settings', 'rt_employee_v2_pdf_template_footer');
        register_setting('rt_employee_v2_settings', 'rt_employee_v2_email_subject_template');
        register_setting('rt_employee_v2_settings', 'rt_employee_v2_email_body_template');
        register_setting('rt_employee_v2_settings', 'rt_employee_v2_email_sender_name');
        register_setting('rt_employee_v2_settings', 'rt_employee_v2_email_sender_email');
        register_setting('rt_employee_v2_settings', 'rt_employee_v2_pdf_logo');
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('rt_employee_v2_buchhaltung_email', sanitize_email($_POST['buchhaltung_email']));
            update_option('rt_employee_v2_pdf_template_header', sanitize_textarea_field($_POST['pdf_template_header']));
            update_option('rt_employee_v2_pdf_template_footer', sanitize_textarea_field($_POST['pdf_template_footer']));
            update_option('rt_employee_v2_email_subject_template', sanitize_text_field($_POST['email_subject_template']));
            update_option('rt_employee_v2_email_body_template', wp_kses_post($_POST['email_body_template']));
            update_option('rt_employee_v2_email_sender_name', sanitize_text_field($_POST['email_sender_name']));
            update_option('rt_employee_v2_email_sender_email', sanitize_email($_POST['email_sender_email']));
            update_option('rt_employee_v2_pdf_logo', intval($_POST['pdf_logo']));
            echo '<div class="notice notice-success"><p>' . __('Einstellungen gespeichert!', 'staff-manager') . '</p></div>';
        }

        $buchhaltung_email = get_option('rt_employee_v2_buchhaltung_email', '');
        $pdf_header = get_option('rt_employee_v2_pdf_template_header', '');
        $pdf_footer = get_option('rt_employee_v2_pdf_template_footer', '');
        $email_subject = get_option('rt_employee_v2_email_subject_template', 'Mitarbeiterdaten: {FIRSTNAME} {LASTNAME} - {KUNDE}');
        $email_body = get_option('rt_employee_v2_email_body_template', '');
        $email_sender_name = get_option('rt_employee_v2_email_sender_name', '');
        $email_sender_email = get_option('rt_employee_v2_email_sender_email', '');
        $pdf_logo_id = get_option('rt_employee_v2_pdf_logo', 0);
        $pdf_logo_url = $pdf_logo_id ? wp_get_attachment_image_url($pdf_logo_id, 'full') : '';
        ?>
        <div class="wrap">
            <h1><?php _e('Staff Manager - Einstellungen', 'staff-manager'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('rt_employee_v2_settings', 'rt_employee_v2_nonce'); ?>
                
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
                    <h2><?php _e('E-Mail Konfiguration', 'staff-manager'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="buchhaltung_email"><?php _e('Buchhaltung E-Mail-Adresse', 'staff-manager'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="buchhaltung_email" name="buchhaltung_email"
                                       value="<?php echo esc_attr($buchhaltung_email); ?>" class="regular-text" />
                                <p class="description"><?php _e('E-Mail-Adresse der Buchhaltung für PDF-Versand', 'staff-manager'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_sender_name"><?php _e('Absender Name', 'staff-manager'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="email_sender_name" name="email_sender_name"
                                       value="<?php echo esc_attr($email_sender_name); ?>" class="regular-text" />
                                <p class="description"><?php _e('Name des Absenders (z.B. "RT Buchhaltung"). Leer lassen für Standard WordPress Absender.', 'staff-manager'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_sender_email"><?php _e('Absender E-Mail-Adresse', 'staff-manager'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="email_sender_email" name="email_sender_email"
                                       value="<?php echo esc_attr($email_sender_email); ?>" class="regular-text" />
                                <p class="description"><?php _e('E-Mail-Adresse des Absenders (z.B. "info@rt-buchhaltung.at"). Leer lassen für Standard WordPress Absender.', 'staff-manager'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
                    <h2><?php _e('E-Mail Template', 'staff-manager'); ?></h2>
                    
                    <?php
                    // Get placeholder list from PDF generator
                    $pdf_generator = new RT_PDF_Generator_V2();
                    $placeholders_list = $pdf_generator->get_email_placeholders_list();
                    ?>
                    
                    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
                        <strong><?php _e('Verfügbare Platzhalter:', 'staff-manager'); ?></strong>
                        <div style="margin-top: 10px; font-size: 12px;">
                            <?php foreach ($placeholders_list as $category => $placeholders): ?>
                                <div style="margin-bottom: 10px;">
                                    <strong><?php echo esc_html($category); ?>:</strong>
                                    <?php foreach ($placeholders as $placeholder => $description): ?>
                                        <code style="background: #fff; padding: 2px 6px; margin: 2px; display: inline-block;" title="<?php echo esc_attr($description); ?>"><?php echo esc_html($placeholder); ?></code>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="email_subject_template"><?php _e('E-Mail Betreff', 'staff-manager'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="email_subject_template" name="email_subject_template"
                                       value="<?php echo esc_attr($email_subject); ?>" class="large-text" />
                                <p class="description"><?php _e('Standard: Mitarbeiterdaten: {FIRSTNAME} {LASTNAME} - {KUNDE}', 'staff-manager'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_body_template"><?php _e('E-Mail Text', 'staff-manager'); ?></label>
                            </th>
                            <td>
                                <textarea id="email_body_template" name="email_body_template" rows="10" class="large-text"><?php echo esc_textarea($email_body); ?></textarea>
                                <p class="description"><?php _e('Der hier eingegebene Text überschreibt die Standard-Nachricht. Platzhalter werden automatisch ersetzt. Leer lassen für Standard-Nachricht.', 'staff-manager'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
                    <h2><?php _e('PDF Template Anpassung', 'staff-manager'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="pdf_logo"><?php _e('Logo für PDF', 'staff-manager'); ?></label>
                            </th>
                            <td>
                                <input type="hidden" id="pdf_logo" name="pdf_logo" value="<?php echo esc_attr($pdf_logo_id); ?>" />
                                <div id="pdf_logo_preview" style="margin-bottom: 10px;">
                                    <?php if ($pdf_logo_url): ?>
                                        <img src="<?php echo esc_url($pdf_logo_url); ?>" style="max-height: 80px; max-width: 200px; border: 1px solid #ddd; padding: 5px; background: #fff;" />
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button" id="pdf_logo_upload_btn"><?php echo $pdf_logo_url ? __('Logo ändern', 'staff-manager') : __('Logo auswählen', 'staff-manager'); ?></button>
                                <button type="button" class="button" id="pdf_logo_remove_btn" style="<?php echo $pdf_logo_url ? '' : 'display:none;'; ?>"><?php _e('Logo entfernen', 'staff-manager'); ?></button>
                                <p class="description"><?php _e('Logo wird oben links im PDF angezeigt (max. Höhe: 120px)', 'staff-manager'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="pdf_template_header"><?php _e('PDF Header Text (Top Right)', 'staff-manager'); ?></label>
                            </th>
                            <td>
                                <textarea id="pdf_template_header" name="pdf_template_header" rows="3" class="large-text"><?php echo esc_textarea($pdf_header); ?></textarea>
                                <p class="description"><?php _e('Text wird oben rechts im PDF angezeigt (z.B. "Mitarbeiterdatenblatt | Interne Personalunterlage" oder "Mitarbeiterverwaltung"). Mehrzeilig möglich.', 'staff-manager'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="pdf_template_footer"><?php _e('PDF Footer Text', 'staff-manager'); ?></label>
                            </th>
                            <td>
                                <textarea id="pdf_template_footer" name="pdf_template_footer" rows="4" class="large-text"><?php echo esc_textarea($pdf_footer); ?></textarea>
                                <p class="description"><?php _e('Text wird im Footer am Ende des PDFs angezeigt (z.B. "Die Verarbeitung der personenbezogenen Daten erfolgt gemäß DSGVO."). Mehrzeilig möglich.', 'staff-manager'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <script>
                jQuery(document).ready(function($) {
                    var mediaUploader;
                    
                    $('#pdf_logo_upload_btn').on('click', function(e) {
                        e.preventDefault();
                        
                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }
                        
                        mediaUploader = wp.media({
                            title: '<?php _e('Logo auswählen', 'staff-manager'); ?>',
                            button: {
                                text: '<?php _e('Logo verwenden', 'staff-manager'); ?>'
                            },
                            multiple: false
                        });
                        
                        mediaUploader.on('select', function() {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            $('#pdf_logo').val(attachment.id);
                            $('#pdf_logo_preview').html('<img src="' + attachment.url + '" style="max-height: 80px; max-width: 200px; border: 1px solid #ddd; padding: 5px; background: #fff;" />');
                            $('#pdf_logo_upload_btn').text('<?php _e('Logo ändern', 'staff-manager'); ?>');
                            $('#pdf_logo_remove_btn').show();
                        });
                        
                        mediaUploader.open();
                    });
                    
                    $('#pdf_logo_remove_btn').on('click', function(e) {
                        e.preventDefault();
                        $('#pdf_logo').val('');
                        $('#pdf_logo_preview').html('');
                        $('#pdf_logo_upload_btn').text('<?php _e('Logo auswählen', 'staff-manager'); ?>');
                        $('#pdf_logo_remove_btn').hide();
                    });
                });
                </script>
                
                <?php submit_button(__('Einstellungen speichern', 'staff-manager')); ?>
            </form>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-top: 20px;">
                <h2><?php _e('System Information', 'staff-manager'); ?></h2>
                <table class="widefat">
                    <tr>
                        <td><strong><?php _e('Plugin Version', 'staff-manager'); ?></strong></td>
                        <td><?php echo esc_html(RT_EMPLOYEE_V2_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('WordPress Version', 'staff-manager'); ?></strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Buchhaltung E-Mail', 'staff-manager'); ?></strong></td>
                        <td><?php echo esc_html($buchhaltung_email ?: 'Nicht konfiguriert'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
}