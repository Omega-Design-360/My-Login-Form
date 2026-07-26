<?php

/**
 * Main Admin Class for My Login Form
 * 
 * Handles all admin area functionality including menu registration,
 * asset loading, settings pages, and admin notices.
 */
namespace MyLoginForm\Admin;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Admin {
    
    /**
     * Instance of this class
     *
     * @var Admin|null
     */
    private static $instance = null;
    
    /**
     * Admin components
     *
     * @var array
     */
    private $components = [];
    
    /**
     * Admin page hooks
     *
     * @var array
     */
    private $page_hooks = [];
    
    /**
     * Constructor (private for singleton)
     */
    private function __construct() {}
    
    /**
     * Get singleton instance
     *
     * @return Admin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize admin
     *
     * @return void
     */
    public function init() {
        // Only initialize in admin area
        if (!is_admin()) {
            return;
        }
        
        // Check user capabilities
        if (!$this->check_admin_capabilities()) {
            return;
        }
        
        
        // Initialize components
        $this->init_components();
        add_action('current_screen', [$this, 'capture_page_hooks']);
        
        // Register admin initialization hook
        do_action('my_login_form_admin_init', $this);
    }
    
    /**
     * Check if current user has admin capabilities
     *
     * @return bool
     */
    private function check_admin_capabilities() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        return true;
    }
    
    /**
     * Initialize components
     *
     * @return void
     */
    private function init_components() {
        // Define components to load
        $components_to_load = [
            'assets'    => 'MyLoginForm\\Admin\\Assets',
            'dashboard' => 'MyLoginForm\\Admin\\Dashboard',
            'menus'     => 'MyLoginForm\\Admin\\Menus',
            'notices'   => 'MyLoginForm\\Admin\\Notices',
            'settings'  => 'MyLoginForm\\Admin\\Settings'
        ];
        
        // Initialize each component
        foreach ($components_to_load as $name => $class) {
            $this->init_component($name, $class);
        }
        
        // Trigger action after all components initialized
        do_action('my_login_form_admin_components_initialized', $this->components);
    }
    
    /**
     * Initialize a single component
     *
     * @param string $name Component name
     * @param string $class_name Full class name
     * @return void
     */
    private function init_component($name, $class_name) {
        try {
            // Check if class exists
            if (!class_exists($class_name)) {
                $this->log_error("Admin class not found: {$class_name}");
                return;
            }
            
            // Check for singleton pattern
            if (method_exists($class_name, 'get_instance')) {
                $component = call_user_func([$class_name, 'get_instance']);
            } else {
                $component = new $class_name();
            }
            
            // Initialize component if method exists
            if (method_exists($component, 'init')) {
                $component->init();
            }
            
            $this->components[$name] = $component;
            $this->log_debug("Component {$name} initialized successfully");
            
        } catch (\Exception $e) {
            $this->log_error("Failed to initialize component {$name}: " . $e->getMessage());
        }
    }
    
    /**
     * Get component
     *
     * @param string $name Component name
     * @return mixed|null
     */
    public function get_component($name) {
        return $this->components[$name] ?? null;
    }
    
    /**
     * Get all components
     *
     * @return array
     */
    public function get_components() {
        return $this->components;
    }
    
    /**
     * Check if component is loaded
     *
     * @param string $name Component name
     * @return bool
     */
    public function has_component($name) {
        return isset($this->components[$name]) && $this->components[$name] !== null;
    }
    
    /**
     * Capture WordPress page hooks for our admin pages
     *
     * @return void
     */
    public function capture_page_hooks() {
        global $admin_page_hooks;
        
        if (isset($admin_page_hooks['my-login-form-dashboard'])) {
    $this->page_hooks['main'] = 'toplevel_page_my-login-form-dashboard';
}
        
        // Common admin page hooks
        $possible_pages = [
            'dashboard'     => 'my-login-form_page_my-login-form-dashboard',
            'designer'      => 'my-login-form_page_my-login-form-designer',
            'users'         => 'my-login-form_page_my-login-form-users-data',
            'social'      => 'my-login-form_page_my-login-form-social-supabase',
            'settings'      => 'my-login-form_page_my-login-form-settings'
        ];
        
        foreach ($possible_pages as $key => $hook) {
            if ($this->is_admin_page($hook)) {
                $this->page_hooks[$key] = $hook;
            }
        }
    }
    
    /**
     * Check if current page is our admin page
     *
     * @param string $hook Optional specific hook to check
     * @return bool
     */
    public function is_admin_page($hook = '') {
        if (!function_exists('get_current_screen')) {
            return false;
        }
        
        $screen = get_current_screen();
        
        if (!$screen) {
            return false;
        }
        
        if (!empty($hook)) {
            return $screen->id === $hook;
        }
        
        return strpos($screen->id, 'my-login-form') !== false;
    }
    
    /**
     * Get admin page URL
     *
     * @param string $page Page slug (designer, users, firebase, settings)
     * @param array $args Additional query arguments
     * @return string
     */
    public function get_admin_url($page = '', $args = []) {

    $map = [
        'dashboard' => 'my-login-form-dashboard',
        'designer'  => 'my-login-form-designer',
        'users'     => 'my-login-form-users-data',
        'social'  => 'my-login-form-social-supabase',
        'settings'  => 'my-login-form-settings',
    ];

    $slug = $map[$page] ?? 'my-login-form-dashboard';

    $url = admin_url('admin.php?page=' . $slug);

    if (!empty($args)) {
        $url = add_query_arg($args, $url);
    }

    return $url;
}
    
  
    
    /**
     * Get asset URL
     *
     * @param string $type Asset type (css or js)
     * @param string $file File name
     * @return string
     */
    public function get_asset_url($type, $file) {
        return MY_LOGIN_FORM_URL . "admin/{$type}/{$file}";
    }
    
    /**
     * Add admin notice
     *
     * @param string $message Notice message
     * @param string $type Notice type (success, error, warning, info)
     * @param bool $dismissible Whether notice is dismissible
     * @return void
     */
    public function add_notice($message, $type = 'info', $dismissible = true) {
        $notices = get_transient('my_login_form_admin_notices');
        
        if (!is_array($notices)) {
            $notices = [];
        }
        
        $notices[] = [
            'message'       => $message,
            'type'          => $type,
            'dismissible'   => $dismissible,
            'time'          => time()
        ];
        
        set_transient('my_login_form_admin_notices', $notices, HOUR_IN_SECONDS);
    }
    
    /**
     * Display admin notices
     *
     * @return void
     */
    public function display_notices() {
        $notices = get_transient('my_login_form_admin_notices');
        
        if (empty($notices)) {
            return;
        }
        
        foreach ($notices as $notice) {
            $dismissible_class = $notice['dismissible'] ? 'is-dismissible' : '';
            ?>
            <div class="notice notice-<?php echo esc_attr($notice['type']); ?> <?php echo esc_attr($dismissible_class); ?>">
                <p><?php echo wp_kses_post($notice['message']); ?></p>
            </div>
            <?php
        }
        
        // Clear notices
        delete_transient('my_login_form_admin_notices');
    }
    
    /**
     * Log debug message
     *
     * @param string $message Debug message
     * @return void
     */
    private function log_debug($message) {
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('[My Login Form Admin DEBUG] ' . $message);
        }
    }
    
    /**
     * Log error message
     *
     * @param string $message Error message
     * @return void
     */
    private function log_error($message) {
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('[My Login Form Admin ERROR] ' . $message);
        }
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}