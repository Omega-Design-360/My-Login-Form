<?php
/**
 * My Login Form - Settings Class
 *
 * @package MyLoginForm\Admin
 */

namespace MyLoginForm\Admin;

// Prevent Direct Access
defined('ABSPATH') || exit;

/**
 * Settings handler class
 */
class Settings {
    
    /**
     * Singleton instance
     *
     * @var self
     */
    private static $instance = null;
    
    /**
     * Database instance
     *
     * @var \MyLoginForm\Database\Database
     */
    private $database;
    
    /**
     * Option keys with defaults
     */
    private $options = [
        'default_redirect' => 'my_profile',
        'default_redirect_registration' => 'my_profile',
        'default_redirect_url' => '',
        'default_redirect_registration_url' => '',
        'woocommerce_integration' => 1,
        'custom_css' => '',
        'custom_js' => '',
        'enable_recaptcha' => 0,
        'recaptcha_site_key' => '',
        'recaptcha_secret_key' => '',
        'enable_2fa' => 0,
        'session_timeout' => 3600,
        'max_login_attempts' => 5,
        'lockout_time' => 900,
        'email_verification' => 1,
        'welcome_email' => 1,
        'admin_notifications' => 1,
        'resend_api_key' => '',
        'resend_from_email' => '',
        'resend_from_name' => '',
        'delete_data_on_uninstall' => 0,
        'blocked_ips'   => '',
        'blocked_users' => '',
        'allow_registration' => 1,
        'default_role' => 'subscriber',
        'allowed_email_domains' => '',
        'guest_default_name' => 'Sunshine',
    ];

     /**
     * Constructor
     */
    private function __construct() {
        // Get database instance from loader
        $loader = \MyLoginForm\Core\Loader::get_instance();
        $this->database = $loader->get_module('database');
        
        // Handle form submission
        add_action('admin_post_my_login_form_save_settings', [$this, 'save_settings']);
        
        // Handle AJAX actions
        add_action('wp_ajax_mlf_clear_cache', [$this, 'ajax_clear_cache']);
        add_action('wp_ajax_mlf_reset_settings', [$this, 'ajax_reset_settings']);
        add_action('wp_ajax_mlf_export_settings', [$this, 'ajax_export_settings']);
        add_action('wp_ajax_mlf_import_settings', [$this, 'ajax_import_settings']);
    }
    
    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
   
    
    /**
     * Render settings page
     */
    public function render_settings(): void {
        // Get current settings
        $settings = [];
        foreach ($this->options as $key => $default) {
            $settings[$key] = get_option('my_login_form_' . $key, $default);
        }

        // Secrets never round-trip into the page's HTML source as a
        // value="" attribute — type="password" only hides them on screen,
        // not from View Source/devtools. Blank the real value and hand the
        // template a masked hint instead (same pattern already used for the
        // Supabase keys in supabase/supabase.php's setup form).
        $secret_hints = [];
        foreach (['resend_api_key', 'recaptcha_secret_key'] as $secret_key) {
            $secret_hints[$secret_key] = $this->mask_secret($settings[$secret_key]);
            $settings[$secret_key] = '';
        }

        // Get system information
        $system_info = $this->get_system_info();

        // Include the template with all variables
        include MY_LOGIN_FORM_DIR . 'Admin/Pages/settings.php';
    }

    /**
     * First 4 + last 4 characters, everything else replaced — enough for an
     * admin to recognize "yes, this is the key I saved" without the full
     * secret ever appearing in the page source.
     */
    private function mask_secret(string $key): string {
        if ($key === '') {
            return '';
        }
        if (strlen($key) <= 8) {
            return str_repeat('•', strlen($key));
        }
        return substr($key, 0, 4) . str_repeat('•', 10) . substr($key, -4);
    }
    
    /**
     * Get system information
     */
    private function get_system_info(): array {
        global $wpdb;
        
        return [
            'plugin_version' => MY_LOGIN_FORM_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'mysql_version' => $wpdb->get_var("SELECT VERSION()"),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'wp_memory_limit' => WP_MEMORY_LIMIT,
            'wp_max_memory_limit' => WP_MAX_MEMORY_LIMIT,
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'woocommerce_active' => class_exists('WooCommerce'),
            'firebase_active' => (bool) get_option('my_login_form_firebase_enabled', false),
            'social_login_active' => (bool) get_option('my_login_form_social_login', false),
            'recaptcha_active' => (bool) get_option('my_login_form_enable_recaptcha', false),
            'two_factor_active' => (bool) get_option('my_login_form_enable_2fa', false),
            'forms_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}my_login_forms"),
            'users_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}my_login_users_data"),
            'active_theme' => wp_get_theme()->get('Name'),
            'active_plugins' => get_option('active_plugins'),
        ];
    }
    
    /**
     * Save settings
     */
    public function save_settings(): void {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'my_login_form_save_settings')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Secrets should never be autoloaded (WordPress otherwise loads them
        // into memory on every single request, front-end and back-end alike).
        $non_autoloaded = ['resend_api_key', 'recaptcha_secret_key'];

        // Save each option
        foreach ($this->options as $key => $default) {
            $option_name = 'my_login_form_' . $key;

            // The settings form always submits these two fields blank (the
            // template never echoes the real secret back — see
            // render_settings()) unless the admin actually typed a new
            // value, so a blank submission here means "leave it as-is," not
            // "clear it."
            if (in_array($key, $non_autoloaded, true) && trim((string) ($_POST[$key] ?? '')) === '') {
                continue;
            }

            $value = isset($_POST[$key]) ? $this->sanitize_setting($key, $_POST[$key]) : $default;
            update_option($option_name, $value, !in_array($key, $non_autoloaded, true));
        }
        
        // Special handling for WooCommerce integration
        if (isset($_POST['woocommerce_integration']) && class_exists('WooCommerce')) {
            update_option('my_login_form_woocommerce_integration', 1);
        } elseif (!isset($_POST['woocommerce_integration'])) {
            update_option('my_login_form_woocommerce_integration', 0);
        }
        
        // Log the action
        if ($this->database && $this->database->admin()) {
            $this->database->admin()->log_admin_activity(get_current_user_id(), 'settings', 'updated', [
                'timestamp' => current_time('mysql')
            ]);
        }

        // Redirect back with success message
        wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
        exit;
    }
    
    /**
     * Sanitize setting based on type
     */
    private function sanitize_setting($key, $value) {
        switch ($key) {
            case 'default_redirect':
                // 'dashboard' (wp-admin) intentionally not offered — these forms
                // are for front-end customers/subscribers, not wp-admin users.
                return in_array($value, ['home', 'my_profile', 'custom'], true) ? $value : 'my_profile';

            case 'default_redirect_registration':
                // 'login' only makes sense here (send a brand-new account to log
                // in) — redirecting an already-logged-in user back to Login after
                // a successful login wouldn't make sense, so it's not offered there.
                return in_array($value, ['home', 'my_profile', 'login', 'custom'], true) ? $value : 'my_profile';

            case 'default_redirect_url':
            case 'default_redirect_registration_url':
                return esc_url_raw($value);

            case 'default_role':
                // Hard allow-list — these forms register front-end site visitors,
                // never staff. AuthAjax::handle_register() re-validates this same
                // list independently, so this can't be bypassed even if this
                // option were ever corrupted or edited outside the settings form.
                $allowed = array_values(array_filter(['customer', 'subscriber'], function ($r) {
                    return get_role($r) !== null;
                }));
                return in_array($value, $allowed, true) ? $value : 'subscriber';

            case 'allow_registration':
                return $value ? 1 : 0;

            case 'allowed_email_domains':
                return sanitize_text_field($value);

            case 'guest_default_name':
                // {name} merge tag falls back to this for logged-out visitors
                // (and for logged-in users with no name field set at all) —
                // never let it save blank, or the tag would go blank too.
                $value = sanitize_text_field($value);
                return $value !== '' ? $value : 'Sunshine';

            case 'custom_css':
            case 'custom_js':
                return wp_kses_post($value);
                
            case 'recaptcha_site_key':
            case 'recaptcha_secret_key':
            case 'resend_api_key':
                return sanitize_text_field($value);

            case 'resend_from_email':
                return sanitize_email($value);

            case 'resend_from_name':
                return sanitize_text_field($value);
                
            case 'session_timeout':
            case 'max_login_attempts':
            case 'lockout_time':
                return absint($value);
                
            case 'enable_recaptcha':
            case 'enable_2fa':
            case 'email_verification':
            case 'welcome_email':
            case 'admin_notifications':
            case 'delete_data_on_uninstall':
                return $value ? 1 : 0;
                
            case 'blocked_ips':
            case 'blocked_users':
                return sanitize_textarea_field($value);

            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * AJAX: Clear plugin cache
     */
    public function ajax_clear_cache(): void {
        check_ajax_referer('my_login_form_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_my_login_form_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_my_login_form_%'");
        
        // Clear cache
        wp_cache_flush();
        
        // Log the action
        if ($this->database && $this->database->admin()) {
            $this->database->admin()->log_admin_activity(get_current_user_id(), 'settings', 'cache_cleared');
        }

        wp_send_json_success(['message' => __('Cache cleared successfully!', 'my-login-form')]);
    }
    
    /**
     * AJAX: Reset settings to defaults
     */
    public function ajax_reset_settings(): void {
        check_ajax_referer('my_login_form_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Reset all options
        foreach ($this->options as $key => $default) {
            update_option('my_login_form_' . $key, $default);
        }
        
        // Log the action
        if ($this->database && $this->database->admin()) {
            $this->database->admin()->log_admin_activity(get_current_user_id(), 'settings', 'reset_to_defaults');
        }

        wp_send_json_success(['message' => __('Settings reset to defaults!', 'my-login-form')]);
    }
    
    /**
     * AJAX: Export settings
     */
    public function ajax_export_settings(): void {
        check_ajax_referer('my_login_form_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $settings = [];
        foreach ($this->options as $key => $default) {
            $settings[$key] = get_option('my_login_form_' . $key, $default);
        }

        // Never let a settings export leak live secrets.
        foreach (['resend_api_key', 'recaptcha_secret_key'] as $secret_key) {
            if (isset($settings[$secret_key])) {
                $settings[$secret_key] = '';
            }
        }

        // Add plugin info
        $export = [
            'plugin' => 'My Login Form',
            'version' => MY_LOGIN_FORM_VERSION,
            'export_date' => current_time('mysql'),
            'settings' => $settings
        ];
        
        $json = json_encode($export, JSON_PRETTY_PRINT);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="my-login-form-settings-' . date('Y-m-d') . '.json"');
        header('Content-Length: ' . strlen($json));
        
        echo $json;
        exit;
    }
    
    /**
     * AJAX: Import settings
     */
    public function ajax_import_settings(): void {
        check_ajax_referer('my_login_form_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No file uploaded or upload error', 'my-login-form'));
        }
        
        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $data = json_decode($file_content, true);
        
        if (!$data || !isset($data['settings'])) {
            wp_send_json_error(__('Invalid settings file', 'my-login-form'));
        }
        
        // Import settings — sanitize each value the same way save_settings()
        // does, rather than trusting the uploaded file's values as-is.
        foreach ($data['settings'] as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                update_option('my_login_form_' . $key, $this->sanitize_setting($key, $value));
            }
        }
        
        // Log the action
        if ($this->database && $this->database->admin()) {
            $this->database->admin()->log_admin_activity(get_current_user_id(), 'settings', 'imported', [
                'source_file' => $_FILES['import_file']['name']
            ]);
        }

        wp_send_json_success(['message' => __('Settings imported successfully!', 'my-login-form')]);
    }
}