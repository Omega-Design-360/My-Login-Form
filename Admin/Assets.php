<?php
namespace MyLoginForm\Admin;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Assets {

    /**
     * Get singleton instance
     *
     * @return Assets
     */
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
        
        // // Enqueue scripts and styles
        // add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        // add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Add CodeMirror for form designer
        add_action('admin_enqueue_scripts', [$this, 'enqueue_codemirror']);
    }
    
    // public function enqueue_styles($hook) {
    //     // Only load on our plugin pages
    //     if (!$this->admin->is_admin_page($hook)) {
    //         return;
    //     }
        
    //     // Font Awesome
    //     wp_enqueue_style(
    //         'font-awesome',
    //         'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    //         [],
    //         '6.4.0'
    //     );
        
    //     // Admin styles
    //     wp_enqueue_style(
    //         'my-login-form-admin',
    //         $this->admin->get_asset_url('css', 'admin-style.css'),
    //         ['font-awesome'],
    //         MY_LOGIN_FORM_VERSION
    //     );
    // }
    
    // public function enqueue_scripts($hook) {
    //     // Only load on our plugin pages
    //     if (!$this->admin->is_admin_page($hook)) {
    //         return;
    //     }
        
    //     // Admin script
    //     wp_enqueue_script(
    //         'my-login-form-admin',
    //         $this->admin->get_asset_url('js', 'admin-script.js'),
    //         ['jquery', 'wp-codemirror'],
    //         MY_LOGIN_FORM_VERSION,
    //         true
    //     );
        
    //     // Localize script
    //     wp_localize_script('my-login-form-admin', 'myLoginForm', [
    //         'ajax_url'          => admin_url('admin-ajax.php'),
    //         'nonce'             => wp_create_nonce('my_login_form_nonce'),
    //         'plugin_url'        => MY_LOGIN_FORM_URL,
    //         'is_admin'          => true,
    //         'strings'           => [
    //         'save_success'      => __('Form saved successfully!', 'my-login-form'),
    //         'save_error'        => __('Error saving form.', 'my-login-form'),
    //         'confirm_delete'    => __('Are you sure you want to delete this form?', 'my-login-form'),
    //         'copy_success'      => __('Shortcode copied to clipboard!', 'my-login-form'),
    //         'copy_error'        => __('Failed to copy shortcode.', 'my-login-form')
    //         ]
    //     ]);
    // }
    
    public function enqueue_codemirror($hook) {
        // Only load on form designer page
        if ($hook !== 'my-login-form_page_my-login-form-designer') {
            return;
        }
        
        // Enqueue CodeMirror for CSS, JS, and HTML editing
        wp_enqueue_code_editor(['type' => 'text/css']);
        wp_enqueue_code_editor(['type' => 'application/javascript']);
        wp_enqueue_code_editor(['type' => 'text/html']);
    }
}