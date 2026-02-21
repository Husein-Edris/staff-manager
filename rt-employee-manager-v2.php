<?php

/**
 * Plugin Name: Personal Manager
 * Plugin URI: https://edrishusein.com
 * Description: Simplified employee management system for Austrian accounting firms with minimal dependencies
 * Version: 2.3.0
 * Author: Edris Husein
 * Text Domain: rt-employee-manager-v2
 */

// Don't allow direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('RT_EMPLOYEE_V2_VERSION', '2.2.0');
define('RT_EMPLOYEE_V2_PLUGIN_FILE', __FILE__);
define('RT_EMPLOYEE_V2_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RT_EMPLOYEE_V2_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RT_EMPLOYEE_V2_LICENSE_API', 'https://edrishusein.com/product-licenses/employee-manager/api/license-verify.php');

/**
 * Main plugin class
 */
class RT_Employee_Manager_V2
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));

        // License AJAX handlers must always be registered
        add_action('wp_ajax_rt_activate_license_v2', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_rt_deactivate_license_v2', array($this, 'ajax_deactivate_license'));
    }


    public function init()
    {
        // Load Composer autoloader for DomPDF
        $vendor_autoload = RT_EMPLOYEE_V2_PLUGIN_DIR . 'vendor/autoload.php';
        if (file_exists($vendor_autoload)) {
            require_once $vendor_autoload;
        }

        // License gate — if invalid, show activation notice and stop
        if (!$this->is_license_valid()) {
            add_action('admin_notices', array($this, 'render_license_notice'));
            return;
        }

        // PDFs can be memory hogs, bump the limit if we can
        if (function_exists('ini_get') && function_exists('ini_set')) {
            $current_limit = ini_get('memory_limit');
            if (intval($current_limit) < 512) {
                @ini_set('memory_limit', '512M');
            }
        }

        $this->load_classes();

        new RT_Employee_Post_Type_V2();
        new RT_Employee_Meta_Boxes_V2();
        new RT_User_Roles_V2();
        new RT_Kuendigung_Post_Type_V2();

        if (is_admin()) {
            new RT_Admin_Dashboard_V2();
            new RT_Kuendigung_Handler_V2();
        }

        // PDF stuff
        new RT_PDF_Generator_V2();

        // Handle capability updates when plugin version changes
        $this->maybe_update_capabilities();
    }

    /**
     * Check if current site has a valid license (cached for 24h)
     */
    public function is_license_valid()
    {
        // Local development bypass
        $site_url = get_site_url();
        if (strpos($site_url, 'rt-buchhaltung.local') !== false || strpos($site_url, 'localhost') !== false) {
            return true;
        }

        $status = get_option('rt_employee_v2_license_status', '');
        $last_check = (int) get_option('rt_employee_v2_license_checked', 0);
        $key = get_option('rt_employee_v2_license_key', '');

        // No key entered yet
        if (empty($key)) {
            return false;
        }

        // Cache valid for 24 hours
        if ($status === 'valid' && (time() - $last_check) < DAY_IN_SECONDS) {
            return true;
        }

        // Re-validate with server
        return $this->validate_license_with_server($key);
    }

    /**
     * Call the license server and cache the result
     */
    public function validate_license_with_server($key)
    {
        $domain = preg_replace('#^https?://#', '', get_site_url());
        $domain = rtrim($domain, '/');

        $response = wp_remote_post(RT_EMPLOYEE_V2_LICENSE_API, array(
            'timeout' => 15,
            'body' => array(
                'license_key' => $key,
                'domain' => $domain,
            ),
        ));

        if (is_wp_error($response)) {
            // Network error — keep current status if we had one, don't lock out
            $existing = get_option('rt_employee_v2_license_status', '');
            if ($existing === 'valid') {
                update_option('rt_employee_v2_license_checked', time());
                return true;
            }
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $valid = isset($body['valid']) && $body['valid'] === true;

        update_option('rt_employee_v2_license_key', sanitize_text_field($key));
        update_option('rt_employee_v2_license_status', $valid ? 'valid' : 'invalid');
        update_option('rt_employee_v2_license_checked', time());

        return $valid;
    }

    /**
     * AJAX: Activate a license key
     */
    public function ajax_activate_license()
    {
        check_ajax_referer('rt_license_v2', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung.');
        }

        $key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        if (empty($key)) {
            wp_send_json_error('Bitte Lizenzschlüssel eingeben.');
        }

        $valid = $this->validate_license_with_server($key);

        if ($valid) {
            wp_send_json_success(array('message' => 'Lizenz erfolgreich aktiviert.'));
        } else {
            wp_send_json_error('Ungültiger Lizenzschlüssel oder Domain nicht autorisiert.');
        }
    }

    /**
     * AJAX: Deactivate/remove the license key
     */
    public function ajax_deactivate_license()
    {
        check_ajax_referer('rt_license_v2', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung.');
        }

        delete_option('rt_employee_v2_license_key');
        delete_option('rt_employee_v2_license_status');
        delete_option('rt_employee_v2_license_checked');

        wp_send_json_success(array('message' => 'Lizenz deaktiviert.'));
    }

    /**
     * Show license activation notice when plugin is unlicensed
     */
    public function render_license_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $key = get_option('rt_employee_v2_license_key', '');
        $status = get_option('rt_employee_v2_license_status', '');
        ?>
<div class="notice notice-error" style="padding: 20px;">
    <h2 style="margin-top: 0;">RT Employee Manager V2 — Lizenzaktivierung erforderlich</h2>
    <p>Bitte geben Sie Ihren Lizenzschlüssel ein, um das Plugin zu aktivieren.</p>

    <?php if ($status === 'invalid' && !empty($key)): ?>
    <div style="background: #fef0f0; border-left: 4px solid #dc3232; padding: 10px 15px; margin-bottom: 15px;">
        Ungültiger Lizenzschlüssel oder Domain nicht autorisiert.
    </div>
    <?php endif; ?>

    <div style="display: flex; gap: 10px; align-items: center;">
        <input type="text" id="rt-license-key-input" value="<?php echo esc_attr($key); ?>"
            placeholder="XXXX-XXXX-XXXX-XXXX" style="width: 350px; padding: 8px; font-size: 14px;" />
        <button type="button" id="rt-activate-license-btn" class="button button-primary">
            Lizenz aktivieren
        </button>
        <span id="rt-license-spinner" class="spinner" style="float: none;"></span>
    </div>
    <p id="rt-license-message" style="margin-top: 10px;"></p>
</div>
<script>
jQuery(document).ready(function($) {
    $('#rt-activate-license-btn').on('click', function() {
        var key = $('#rt-license-key-input').val().trim();
        if (!key) return;

        var $btn = $(this);
        var $spinner = $('#rt-license-spinner');
        var $msg = $('#rt-license-message');

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $msg.text('');

        $.post(ajaxurl, {
            action: 'rt_activate_license_v2',
            license_key: key,
            nonce: '<?php echo wp_create_nonce('rt_license_v2'); ?>'
        }, function(response) {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');

            if (response.success) {
                $msg.css('color', '#46b450').text(response.data.message);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                $msg.css('color', '#dc3232').text(response.data);
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            $msg.css('color', '#dc3232').text(
                'Verbindungsfehler. Bitte versuchen Sie es erneut.');
        });
    });
});
</script>
<?php
    }

    /**
     * Load all class files
     */
    private function load_classes()
    {
        $classes = array(
            'class-employee-post-type.php',
            'class-employee-meta-boxes.php',
            'class-user-roles.php',
            'class-admin-dashboard.php',
            'class-pdf-generator.php',
            'class-kuendigung-post-type.php',
            'class-kuendigung-handler.php',
            'class-kuendigung-pdf-generator.php'
        );

        foreach ($classes as $class) {
            $file = RT_EMPLOYEE_V2_PLUGIN_DIR . 'includes/' . $class;
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Run when plugin gets activated
     */
    public function activate()
    {
        // Set up the user roles we need
        $this->create_roles();

        // Make sure post type capabilities are registered
        $this->init_post_types_for_activation();

        // WordPress needs this after registering post types
        flush_rewrite_rules();

        // Track what version we're running
        add_option('rt_employee_v2_version', RT_EMPLOYEE_V2_VERSION);

        error_log('RT Employee Manager V2: Plugin activated successfully');
    }

    /**
     * Load post types during activation so capabilities get set properly
     */
    private function init_post_types_for_activation()
    {
        // Need to load this manually during activation
        require_once RT_EMPLOYEE_V2_PLUGIN_DIR . 'includes/class-employee-post-type.php';
        require_once RT_EMPLOYEE_V2_PLUGIN_DIR . 'includes/class-kuendigung-post-type.php';
        $employee_post_type = new RT_Employee_Post_Type_V2();
        $kuendigung_post_type = new RT_Kuendigung_Post_Type_V2();
        // The capabilities get registered when these classes initialize
    }

    /**
     * Clean up when plugin gets deactivated
     */
    public function deactivate()
    {
        flush_rewrite_rules();
        error_log('RT Employee Manager V2: Plugin deactivated');
    }

    /**
     * Set up the kunden user role for clients
     */
    private function create_roles()
    {
        // Clean slate - remove the old role if it's hanging around
        remove_role('kunden_v2');

        // Create the client role with basic permissions
        add_role('kunden_v2', __('Kunden', 'rt-employee-manager-v2'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'upload_files' => true,
        ));

        error_log('RT Employee Manager V2: Created kunden_v2 role');
    }

    /**
     * Set up German translations
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'rt-employee-manager-v2',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }


    /**
     * Check if we need to update capabilities when plugin version changes
     */
    private function maybe_update_capabilities()
    {
        $current_version = get_option('rt_employee_v2_capabilities_version', '0');

        if ($current_version !== RT_EMPLOYEE_V2_VERSION) {
            // Plugin version changed, refresh the capabilities
            $this->init_post_types_for_activation();
            update_option('rt_employee_v2_capabilities_version', RT_EMPLOYEE_V2_VERSION);
            error_log('RT Employee Manager V2: Updated capabilities for version ' . RT_EMPLOYEE_V2_VERSION);
        }
    }
}

// Initialize plugin
RT_Employee_Manager_V2::get_instance();