<?php
namespace MyLoginForm\Admin;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Notices {
    
    private static $instance = null;
    private $admin;

    private function __construct() {}
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init() {
        $this->admin = Admin::get_instance();
        
        // Display admin notices
        add_action('admin_notices', [$this, 'display_notices']);
        
        // Add notices based on conditions
        add_action('admin_init', [$this, 'check_configuration']);
    }
    
    public function display_notices() {
        $this->admin->display_notices();
    }
    
    public function check_configuration() {
        if (!$this->admin->is_admin_page()) {
            return;
        }
        
        // Check Firebase configuration
        $this->check_firebase_config();
        
        // Check social login configuration
        $this->check_social_config();
        
        // Check WooCommerce integration
        $this->check_woocommerce_config();
    }
    
    private function check_firebase_config() {
        $firebase_enabled = get_option('my_login_form_firebase_enabled', 0);
        $firebase_config = get_option('my_login_form_firebase_config', '');
        
        if ($firebase_enabled && empty($firebase_config)) {
            $this->admin->add_notice(
                sprintf(
                    __('⚠️ Firebase is enabled but not configured. %sConfigure now%s', 'my-login-form'),
                    '<a href="' . $this->admin->get_admin_url('firebase') . '">',
                    '</a>'
                ),
                'warning'
            );
        }
    }
    
    private function check_social_config() {
        $social_login = get_option('my_login_form_social_login', 0);
        $google_client_id = get_option('my_login_form_google_client_id', '');
        $facebook_app_id = get_option('my_login_form_facebook_app_id', '');
        
        if ($social_login && empty($google_client_id) && empty($facebook_app_id)) {
            $this->admin->add_notice(
                sprintf(
                    __('⚠️ Social login is enabled but no providers are configured. %sConfigure now%s', 'my-login-form'),
                    '<a href="' . $this->admin->get_admin_url('settings') . '">',
                    '</a>'
                ),
                'warning'
            );
        }
    }
    
    private function check_woocommerce_config() {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        $woo_integration = get_option('my_login_form_woocommerce_integration', 1);
        $profile_page_id = get_option('my_login_form_profile_page_id', 0);
        
        if ($woo_integration && $profile_page_id && get_option('woocommerce_myaccount_page_id') != $profile_page_id) {
            $this->admin->add_notice(
                sprintf(
                    __('ℹ️ WooCommerce integration is enabled. %sSet as My Account page%s', 'my-login-form'),
                    '<a href="' . admin_url('admin.php?page=my-login-form-settings&action=set_woocommerce_page') . '">',
                    '</a>'
                ),
                'info'
            );
        }
    }
}