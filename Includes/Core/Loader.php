<?php

/**
 * Plugin Loader Class
 * 
 * @package MyLoginForm\Core
 */

namespace MyLoginForm\Core;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Loader {

    /**
     * Singleton instance
     *
     * @var Loader|null
     */
    private static $instance = null;

    /**
     * Array of loaded modules
     * 
     * @var array
     */
    private $modules = [];

    /**
     * Array of configs
     * 
     * @var array
     */
    private $configs = [];

    /**
     * Array of services to initialize
     *
     * @var array
     */
    private $services = [];

    private function __construct() {
        $this->load_configs();
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): self {
        // Initialize services first
        $this->init_services();
        
        // Register hooks
        $this->register_hooks();
        
        // Load modules
        $this->load_modules();
        
        // Initialize admin and public
        if (is_admin()) {
            $this->init_admin();
        } else {
            $this->init_public();
        }

        do_action('my_login_form_loader_initialized', $this);

        return $this;
    }

    /**
     * Initialize core services
     */
    private function init_services() {
        $this->services = [
            'database'      => 'MyLoginForm\\Database\\Database',
            'security'      => 'MyLoginForm\\Security\\Nonce',
            'users'         => 'MyLoginForm\\Users\\Users',
            'shortcodes'    => 'MyLoginForm\\Shortcodes\\Shortcodes',
            'emails'        => 'MyLoginForm\\Emails\\Emails',
            // Unconditional (not just is_admin()) — the daily re-validation
            // cron and Gate::is_active() checks in shortcodes both need this
            // available on every request, front-end included.
            'licensing'     => 'MyLoginForm\\Licensing\\License',
        ];

        foreach ($this->services as $key => $class) {
            if (class_exists($class) && method_exists($class, 'get_instance')) {
                $instance = $class::get_instance();
                if (method_exists($instance, 'init')) {
                    $instance->init();
                }
                $this->services[$key] = $instance;
            }
        }
    }

    /**
     * Register all plugin hooks
     */
    private function register_hooks() {
        // Initialize Hooks singleton
        $hooks = \MyLoginForm\Core\Hooks::get_instance();
        
        // Call init method to register hooks
        if (method_exists($hooks, 'init')) {
            $hooks->init();
        }
    }

    /**
     * Initialize admin side
     */
    private function init_admin() {
        $admin_class = 'MyLoginForm\\Admin\\Admin';
        if (class_exists($admin_class)) {
            $admin = $admin_class::get_instance();
            if (method_exists($admin, 'init')) {
                $admin->init();
            }
        }

        // Update-check gating and renewal nags only matter in wp-admin.
        if (class_exists('MyLoginForm\\Licensing\\Updater')) {
            \MyLoginForm\Licensing\Updater::get_instance();
        }
        if (class_exists('MyLoginForm\\Licensing\\Notices')) {
            \MyLoginForm\Licensing\Notices::get_instance();
        }
    }

    /**
     * Initialize public side
     */
    private function init_public() {
        $public_class = 'MyLoginForm\\Public\\Public';
        if (class_exists($public_class)) {
            $public = $public_class::get_instance();
            if (method_exists($public, 'init')) {
                $public->init();
            }
        }
    }

    /**
     * Get service instance
     *
     * @param string $key
     * @return mixed|null
     */
    public function get_service($key) {
        return isset($this->services[$key]) ? $this->services[$key] : null;
    }

    /**
     * Activation hook handler
     */
    public static function activate() {
        $activator_file = MY_LOGIN_FORM_DIR . 'Includes/Lifecycle/Activator.php';
        if (file_exists($activator_file)) {
            require_once $activator_file;
            if (class_exists('MyLoginForm\\Lifecycle\\Activator')) {
                \MyLoginForm\Lifecycle\Activator::activate();
            }
        }
    }

    /**
     * Deactivation hook handler
     */
    public static function deactivate() {
        $deactivator_file = MY_LOGIN_FORM_DIR . 'Includes/Lifecycle/Deactivator.php';
        if (file_exists($deactivator_file)) {
            require_once $deactivator_file;
            if (class_exists('MyLoginForm\\Lifecycle\\Deactivator')) {
                \MyLoginForm\Lifecycle\Deactivator::deactivate();
            }
        }
    }

    /**
     * Uninstall hook handler
     */
    public static function uninstall() {
        $uninstaller_file = MY_LOGIN_FORM_DIR . 'Includes/Lifecycle/Uninstaller.php';
        if (file_exists($uninstaller_file)) {
            require_once $uninstaller_file;
            if (class_exists('MyLoginForm\\Lifecycle\\Uninstaller')) {
                \MyLoginForm\Lifecycle\Uninstaller::uninstall();
            }
        }
    }

    private function load_configs(): void {
        $this->configs = [
            'hooks' => [
                'class'    => \MyLoginForm\Core\Hooks::class,
                'priority' => 10,
                'required' => true,
                'deps'     => [],
                'enabled'  => true,
            ],
            'database' => [
                'class'    => \MyLoginForm\Database\Database::class,
                'priority' => 20,
                'required' => true,
                'deps'     => ['hooks'],
                'enabled'  => true,
            ],
            'security' => [
                'class'    => \MyLoginForm\Security\Security::class,
                'priority' => 30,
                'required' => false,
                'deps'     => ['hooks','database'],
                'enabled'  => true,
            ],
            'admin' => [
                'class'    => \MyLoginForm\Admin\Admin::class,
                'priority' => 35,
                'required' => false,
                'deps'     => ['hooks','database'],
                'enabled'  => is_admin(),
            ],
            'users' => [
                'class'    => \MyLoginForm\Users\Users::class,
                'priority' => 40,
                'required' => false,
                'deps'     => ['hooks','database'],
                'enabled'  => true,
            ],
            'forms' => [
                'class'    => \MyLoginForm\Forms\Forms::class,
                'priority' => 50,
                'required' => false,
                'deps'     => ['hooks','database'],
                'enabled'  => true,
            ],
            'shortcodes' => [
                'class'    => \MyLoginForm\Shortcodes\Shortcodes::class,
                'priority' => 60,
                'required' => false,
                'deps'     => ['hooks','database','forms'],
                'enabled'  => true,
            ],
            'ajax' => [
                'class'    => \MyLoginForm\Ajax\Ajax::class,
                'priority' => 70,
                'required' => false,
                'deps'     => ['hooks','database'],
                'enabled'  => true,
            ],
            'emails' => [
                'class'    => \MyLoginForm\Emails\Emails::class,
                'priority' => 80,
                'required' => false,
                'deps'     => ['hooks','database'],
                'enabled'  => true,
            ],
            // Task 2: login/register redirect integration. WooCommerce's own
            // plugin file requires its main class at top-level include time
            // (before 'plugins_loaded' fires for anyone), so class_exists()
            // here is safe regardless of plugin load order.
            'woocommerce' => [
                'class'    => \MyLoginForm\Integrations\Woocommerce::class,
                'priority' => 90,
                'required' => false,
                'deps'     => ['hooks','database'],
                'enabled'  => class_exists('WooCommerce'),
            ],
        ];
    }

    private function load_modules(): void {
        uasort($this->configs, function($a, $b) {
            return ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100);
        });

        foreach ($this->configs as $id => $config) {
            if (!$config['enabled']) {
                continue;
            }

            if (!$this->check_dependencies($config['deps'])) {
                if ($config['required']) {
                    $this->log_error("Required module {$id} dependencies missing.");
                    return; // Stop loading completely
                }
                continue;
            }

            $this->initialize_module($id, $config);
        }
    }

    private function initialize_module(string $id, array $config): void {
        $class = $config['class'];

        if (!class_exists($class)) {
            if ($config['required']) {
                $this->log_error("Required class not found: {$class}");
            }
            return;
        }

        try {
            $module = method_exists($class, 'get_instance')
                ? $class::get_instance()
                : new $class();

            $this->modules[$id] = $module;

            if (method_exists($module, 'init')) {
                $module->init();
            }

            $this->log_debug("Module {$id} loaded.");

        } catch (\Throwable $e) {
            $this->log_error("Module {$id} failed: " . $e->getMessage());
        }
    }

    private function check_dependencies(array $deps): bool {
        foreach ($deps as $dep) {
            if (!isset($this->modules[$dep])) {
                return false;
            }
        }
        return true;
    }

    public function get_module(string $id) {
        return $this->modules[$id] ?? null;
    }

    public function is_loaded(string $id): bool {
        return isset($this->modules[$id]);
    }

    public function get_loaded_modules(): array {
        return $this->modules;
    }

    private function log_debug(string $message): void {
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('[My Login Form] ' . $message);
        }
    }

    private function log_error(string $message): void {
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('[My Login Form ERROR] ' . $message);
        }
    }
}