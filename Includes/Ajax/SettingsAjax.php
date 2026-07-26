<?php
/**
 * Settings AJAX Handler
 *
 * @package MyLoginForm\Ajax
 */
namespace MyLoginForm\Ajax;

defined('ABSPATH') || exit;

class SettingsAjax {

    private static $instance = null;
    private $settings_key = 'my_login_form_settings';

    private function __construct() {
        // Clear cache action
        add_action('wp_ajax_my_login_clear_plugin_cache', array($this, 'clear_plugin_cache'));
        
        // Reset settings action
        add_action('wp_ajax_my_login_reset_settings', array($this, 'reset_settings'));
        
        // Export settings action
        add_action('wp_ajax_my_login_export_settings', array($this, 'export_settings'));
        
        // Import settings action
        add_action('wp_ajax_my_login_import_settings', array($this, 'import_settings'));
        
        // Test connection action
        add_action('wp_ajax_my_login_test_connection', array($this, 'test_connection'));
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Clear plugin cache
     */
    public function clear_plugin_cache() {
        // Verify nonce
        $this->verify_nonce('my_login_clear_cache');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'my-login-form')));
        }
        
        try {
            // Delete transients
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_my_login_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_my_login_%'");
            
            // Clear any other cache if needed
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            // Trigger action for other plugins to clear their cache
            do_action('my_login_cache_cleared');
            
            wp_send_json_success(array(
                'message' => __('Plugin cache cleared successfully!', 'my-login-form'),
                'reload' => false
            ));
            
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to clear cache: ', 'my-login-form') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Reset settings to default values
     */
    public function reset_settings() {
        // Verify nonce
        $this->verify_nonce('my_login_reset_settings');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'my-login-form')));
        }
        
        // Confirm action
        if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'yes') {
            wp_send_json_error(array('message' => __('Confirmation required to reset settings.', 'my-login-form')));
        }
        
        try {
            // Default settings
            $default_settings = array(
                'default_redirect' => 'home',
                'woocommerce_integration' => 0,
                'enable_recaptcha' => 0,
                'recaptcha_site_key' => '',
                'recaptcha_secret_key' => '',
                'enable_2fa' => 0,
                'max_login_attempts' => 5,
                'lockout_time' => 900,
                'session_timeout' => 3600,
                'email_verification' => 0,
                'welcome_email' => 1,
                'admin_notifications' => 1,
                'custom_css' => '',
                'custom_js' => '',
                'delete_data_on_uninstall' => 0,
            );
            
            // Save default settings
            update_option($this->settings_key, $default_settings);
            
            // Trigger action
            do_action('my_login_settings_reset', $default_settings);
            
            wp_send_json_success(array(
                'message' => __('Settings reset to default values successfully!', 'my-login-form'),
                'reload' => true
            ));
            
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to reset settings: ', 'my-login-form') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Export settings as JSON
     */
    public function export_settings() {
        // Verify nonce
        $this->verify_nonce('my_login_export_settings');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'my-login-form')));
        }
        
        try {
            // Get current settings
            $settings = get_option($this->settings_key, array());
            
            // Add export metadata
            $export_data = array(
                'export_date' => current_time('mysql'),
                'plugin_version' => MY_LOGIN_VERSION ?? '1.0.0',
                'site_url' => home_url(),
                'settings' => $settings
            );
            
            // Never let a settings export leak live secrets — same list
            // Admin\Settings::ajax_export_settings() redacts, plus the
            // Supabase keys in case this legacy combined-settings option
            // ever ends up holding them too.
            foreach (['recaptcha_secret_key', 'resend_api_key', 'supabase_anon_key', 'supabase_service_key', 'firebase_api_key'] as $secret_key) {
                if (isset($export_data['settings'][$secret_key])) {
                    $export_data['settings'][$secret_key] = '';
                }
            }
            
            // Send JSON file
            $filename = 'my-login-settings-export-' . date('Y-m-d') . '.json';
            $json_data = json_encode($export_data, JSON_PRETTY_PRINT);
            
            // Send headers for file download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($json_data));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            echo $json_data;
            exit;
            
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to export settings: ', 'my-login-form') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Import settings from JSON
     */
    public function import_settings() {
        // Verify nonce
        $this->verify_nonce('my_login_import_settings');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'my-login-form')));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Please select a valid JSON file to import.', 'my-login-form')));
        }
        
        // Check file type
        $file_type = wp_check_filetype($_FILES['import_file']['name']);
        if ($file_type['ext'] !== 'json' || $file_type['type'] !== 'application/json') {
            wp_send_json_error(array('message' => __('Only JSON files are allowed for import.', 'my-login-form')));
        }
        
        // Check file size (max 2MB)
        if ($_FILES['import_file']['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array('message' => __('File size exceeds 2MB limit.', 'my-login-form')));
        }
        
        try {
            // Read and decode JSON file
            $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
            $import_data = json_decode($file_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(__('Invalid JSON format.', 'my-login-form'));
            }
            
            // Validate import data structure
            if (!isset($import_data['settings']) || !is_array($import_data['settings'])) {
                throw new \Exception(__('Invalid settings format in import file.', 'my-login-form'));
            }
            
            // Merge with current settings to preserve sensitive data
            $current_settings = get_option($this->settings_key, array());
            $new_settings = wp_parse_args($import_data['settings'], $current_settings);
            
            // Prevent overwriting secret keys if not provided
            if (empty($new_settings['recaptcha_secret_key']) && !empty($current_settings['recaptcha_secret_key'])) {
                $new_settings['recaptcha_secret_key'] = $current_settings['recaptcha_secret_key'];
            }
            
            // Validate settings
            $new_settings = $this->validate_settings($new_settings);
            
            // Save imported settings
            update_option($this->settings_key, $new_settings);
            
            // Trigger action
            do_action('my_login_settings_imported', $new_settings);
            
            wp_send_json_success(array(
                'message' => __('Settings imported successfully!', 'my-login-form'),
                'reload' => true
            ));
            
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to import settings: ', 'my-login-form') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Test connection or functionality
     */
    public function test_connection() {
        // Verify nonce
        $this->verify_nonce('my_login_test_connection');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'my-login-form')));
        }
        
        $test_type = isset($_POST['test_type']) ? sanitize_text_field($_POST['test_type']) : 'general';
        
        try {
            $results = array();
            
            switch ($test_type) {
                case 'recaptcha':
                    $results = $this->test_recaptcha();
                    break;
                case 'email':
                    $results = $this->test_email();
                    break;
                case 'database':
                    $results = $this->test_database();
                    break;
                default:
                    $results = $this->test_general();
                    break;
            }
            
            wp_send_json_success(array(
                'message' => __('Test completed successfully!', 'my-login-form'),
                'results' => $results
            ));
            
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => __('Test failed: ', 'my-login-form') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Verify AJAX nonce
     */
    private function verify_nonce($action) {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $action)) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'my-login-form')));
        }
    }
    
    /**
     * Validate settings before saving
     */
    private function validate_settings($settings) {
        $validated = array();
        
        // General settings
        $validated['default_redirect'] = in_array($settings['default_redirect'], ['home', 'my_profile']) 
            ? $settings['default_redirect'] : 'home';
        
        $validated['woocommerce_integration'] = (int) !empty($settings['woocommerce_integration']);
        
        // Security settings
        $validated['enable_recaptcha'] = (int) !empty($settings['enable_recaptcha']);
        $validated['recaptcha_site_key'] = sanitize_text_field($settings['recaptcha_site_key'] ?? '');
        $validated['recaptcha_secret_key'] = sanitize_text_field($settings['recaptcha_secret_key'] ?? '');
        $validated['enable_2fa'] = (int) !empty($settings['enable_2fa']);
        $validated['max_login_attempts'] = min(20, max(1, (int) ($settings['max_login_attempts'] ?? 5)));
        $validated['lockout_time'] = min(86400, max(60, (int) ($settings['lockout_time'] ?? 900)));
        $validated['session_timeout'] = min(86400, max(60, (int) ($settings['session_timeout'] ?? 3600)));
        
        // Email settings
        $validated['email_verification'] = (int) !empty($settings['email_verification']);
        $validated['welcome_email'] = (int) !empty($settings['welcome_email']);
        $validated['admin_notifications'] = (int) !empty($settings['admin_notifications']);
        
        // Custom code
        $validated['custom_css'] = wp_kses_post($settings['custom_css'] ?? '');
        $validated['custom_js'] = wp_kses_post($settings['custom_js'] ?? '');
        
        // Advanced settings
        $validated['delete_data_on_uninstall'] = (int) !empty($settings['delete_data_on_uninstall']);
        
        return $validated;
    }
    
    /**
     * Test reCAPTCHA connection
     */
    private function test_recaptcha() {
        $settings = get_option($this->settings_key, array());
        
        if (empty($settings['recaptcha_site_key']) || empty($settings['recaptcha_secret_key'])) {
            return array(
                'status' => 'warning',
                'message' => __('reCAPTCHA keys are not configured.', 'my-login-form')
            );
        }
        
        return array(
            'status' => 'success',
            'message' => __('reCAPTCHA keys are configured.', 'my-login-form'),
            'site_key' => substr($settings['recaptcha_site_key'], 0, 10) . '...'
        );
    }
    
    /**
     * Test email functionality
     */
    private function test_email() {
        $result = array();
        
        // Test wp_mail function
        if (function_exists('wp_mail')) {
            $result['wp_mail'] = __('Available', 'my-login-form');
        } else {
            $result['wp_mail'] = __('Not available', 'my-login-form');
        }
        
        // Get SMTP settings if any plugin is active
        $smtp_plugins = array(
            'WP Mail SMTP' => function_exists('wp_mail_smtp'),
            'Easy WP SMTP' => defined('EASY_WP_SMTP_VERSION'),
            'Post SMTP' => defined('POST_SMTP_VER'),
        );
        
        $active_smtp = array_filter($smtp_plugins);
        if (!empty($active_smtp)) {
            $result['active_smtp'] = implode(', ', array_keys($active_smtp));
        }
        
        return $result;
    }
    
    /**
     * Test database connection
     */
    private function test_database() {
        global $wpdb;
        
        $result = array(
            'connected' => $wpdb->dbh ? 'Yes' : 'No',
            'database' => DB_NAME,
            'prefix' => $wpdb->prefix,
        );
        
        // Test query
        $test_query = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}options LIMIT 1");
        if (!is_null($test_query)) {
            $result['query_test'] = __('Successful', 'my-login-form');
        } else {
            $result['query_test'] = __('Failed', 'my-login-form');
        }
        
        return $result;
    }
    
    /**
     * Test general WordPress functionality
     */
    private function test_general() {
        global $wp_version;
        
        return array(
            'wordpress_version' => $wp_version,
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
            'wp_cron' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'Disabled' : 'Enabled',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        );
    }
}