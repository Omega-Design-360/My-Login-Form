<?php
namespace MyLoginForm\Core;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Hooks {

    /**
     * Instance of this class
     *
     * @var Hooks|null
     */
    private static ?self $instance = null;

    private array $actions = [];
    private array $filters = [];
    private array $shortcodes = [];
    private array $ajax_handlers = [];
    private array $rest_routes = [];

    /**
     * @var bool
     */
    private bool $registered = false;

    private function __construct() {
        // Initialize hooks in constructor
        $this->init_asset_hooks();
    }

    /**
     * Initialize asset hooks separately
     */
    private function init_asset_hooks(): void {
        // Admin assets - must be added directly, not through the hook system
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets'], 10, 1);
        add_action('wp_enqueue_scripts', [$this, 'my_login_form_enqueue_assets'], 10);

        // Self-heals the "My Account" page when WooCommerce is installed
        // after this plugin already created its own — see
        // Activator::maybe_merge_woocommerce_my_account() for why this can't
        // just be handled once at our own activation time.
        add_action('admin_init', [\MyLoginForm\Lifecycle\Activator::class, 'maybe_merge_woocommerce_my_account']);
    }

    /**
     * Get singleton instance
     *
     * @return Hooks
     */
    public static function get_instance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        // Register hooks (this will register actions/filters added via add_action/add_filter)
        $this->register_hooks();
        
        // Register REST routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    private function register_hooks(): void {
        if ($this->registered) {
            return;
        }

        $this->register_actions();
        $this->register_filters();
        $this->register_shortcodes();
        $this->register_ajax_handlers();

        $this->registered = true;
    }

    /* ------------------------------------------------------------------
     * Add Methods
     * ------------------------------------------------------------------ */

    public function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): self {
        $this->actions[] = compact('hook','callback','priority','accepted_args');
        return $this;
    }

    public function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1): self {
        $this->filters[] = compact('hook','callback','priority','accepted_args');
        return $this;
    }

    public function add_shortcode(string $tag, $callback): self {
        $this->shortcodes[$tag] = $callback;
        return $this;
    }

    public function add_ajax_handler(string $action, $callback, bool $nopriv = false): self {
        $this->ajax_handlers[] = compact('action','callback','nopriv');
        return $this;
    }

    public function add_rest_route(string $namespace, string $route, array $args): self {
        $this->rest_routes[] = compact('namespace','route','args');
        return $this;
    }

    /**
     * Register Internals
     */
    private function register_actions(): void {
        foreach ($this->actions as $action) {
            if ($cb = $this->resolve_callback($action['callback'])) {
                add_action($action['hook'], $cb, $action['priority'], $action['accepted_args']);
            }
        }
    }

    private function register_filters(): void {
        foreach ($this->filters as $filter) {
            if ($cb = $this->resolve_callback($filter['callback'])) {
                add_filter($filter['hook'], $cb, $filter['priority'], $filter['accepted_args']);
            }
        }
    }

    private function register_shortcodes(): void {
        foreach ($this->shortcodes as $tag => $callback) {
            if ($cb = $this->resolve_callback($callback)) {
                add_shortcode($tag, $cb);
            }
        }
    }

    private function register_ajax_handlers(): void {
        foreach ($this->ajax_handlers as $handler) {
            if (!$cb = $this->resolve_callback($handler['callback'])) {
                continue;
            }

            add_action('wp_ajax_' . $handler['action'], $cb);

            if ($handler['nopriv']) {
                add_action('wp_ajax_nopriv_' . $handler['action'], $cb);
            }
        }
    }

    public function register_rest_routes(): void {
        foreach ($this->rest_routes as $route) {
            if (empty($route['namespace']) || empty($route['route']) || empty($route['args']['callback'])) {
                continue;
            }

            register_rest_route(
                $route['namespace'],
                $route['route'],
                $route['args']
            );
        }
    }

    /* ------------------------------------------------------------------
     * Callback Resolver (Singleton Safe)
     * ------------------------------------------------------------------ */

    private function resolve_callback($callback): ?callable {
        if (is_callable($callback)) {
            return $callback;
        }

        if (is_array($callback) && count($callback) === 2) {
            [$class, $method] = $callback;

            if (is_object($class) && method_exists($class, $method)) {
                return [$class, $method];
            }

            if (is_string($class) && class_exists($class)) {
                // Try singleton first
                if (method_exists($class, 'get_instance')) {
                    $instance = $class::get_instance();
                    return method_exists($instance, $method) ? [$instance, $method] : null;
                }
                
                // Try static method
                if (method_exists($class, $method)) {
                    return [$class, $method];
                }
            }
        }

        $this->log_error('Invalid callback: ' . print_r($callback, true));
        return null;
    }

    /* ------------------------------------------------------------------
     * Utility Methods
     * ------------------------------------------------------------------ */

    public function do_action(string $hook, ...$args): void {
        do_action('my_login_form_' . $hook, ...$args);
    }

    public function apply_filters(string $hook, $value, ...$args) {
        return apply_filters('my_login_form_' . $hook, $value, ...$args);
    }

    public function get_registered_hooks(): array {
        return [
            'actions' => $this->actions,
            'filters' => $this->filters,
            'shortcodes' => array_keys($this->shortcodes),
            'ajax' => array_column($this->ajax_handlers, 'action'),
            'rest_routes' => $this->rest_routes
        ];
    }

    private function log_error(string $message): void {
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('[My Login Form Hooks] ' . $message);
        }
    }

    // ------------------------------------------------------------------
    // Clear cache AJAX handler
    // ------------------------------------------------------------------ 
    public function my_login_clear_cache(): void {
        // Verify nonce
        if (!check_ajax_referer('my_login_clear_cache', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_my_login_form_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_my_login_form_%'");

        // Clear cache directory if exists
        $cache_dir = WP_CONTENT_DIR . '/cache/my-login-form/';
        if (file_exists($cache_dir)) {
            array_map('unlink', glob($cache_dir . '*'));
            rmdir($cache_dir);
        }

        wp_send_json_success(['message' => 'Cache cleared successfully']);
    }

    // ------------------------------------------------------------------
    // Enqueue admin assets and localize scripts
    // ------------------------------------------------------------------ 

public function enqueue_admin_assets($hook): void {
    // Debug: Log the current hook
    if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
        error_log('Current admin hook: ' . $hook);
    }
    
    // Get current page from query string
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    
    // Check if this is one of our pages
    if (empty($current_page) || strpos($current_page, 'my-login-form') === false) {
        return;
    }
    
    // Map page slugs to identifiers
    $page_map = [
        'my-login-form-dashboard'        => 'dashboard',
        'my-login-form-designer'         => 'designer',
        'my-login-form-users-data'       => 'users-data',
        'my-login-form-supabase'         => 'supabase',
        'my-login-form-social-supabase'  => 'supabase',
        'my-login-form-settings'         => 'settings',
        'my-login-form-onboarding'       => 'onboarding',
    ];
    
    // Get page identifier (default to dashboard)
    $page_id = isset($page_map[$current_page]) ? $page_map[$current_page] : 'dashboard';
    
    // Define paths — the Supabase page's assets live in their own top-level
    // supabase/ folder (grouped with SupabaseAjax.php/SupabaseDatabase.php)
    // instead of Admin/Pages/, so it gets its own base URL/dir here.
    if ($page_id === 'supabase') {
        $css_dir  = MY_LOGIN_FORM_DIR . 'supabase/';
        $js_dir   = MY_LOGIN_FORM_DIR . 'supabase/';
        $css_url  = MY_LOGIN_FORM_URL . 'supabase/';
        $js_url   = MY_LOGIN_FORM_URL . 'supabase/';
    } else {
        $css_dir  = MY_LOGIN_FORM_DIR . 'Admin/Pages/css/';
        $js_dir   = MY_LOGIN_FORM_DIR . 'Admin/Pages/js/';
        $css_url  = MY_LOGIN_FORM_URL . 'Admin/Pages/css/';
        $js_url   = MY_LOGIN_FORM_URL . 'Admin/Pages/js/';
    }

    // Try page-specific files first
    $css_file = $css_dir . $page_id . '.css';
    $js_file = $js_dir . $page_id . '.js';
    
    // For users page, try 'users-data.js' if no dedicated js file
    if ($page_id === 'users-data' && !file_exists($js_file)) {
        $alt_js = $js_dir . 'users.js';
        if (file_exists($alt_js)) {
            $js_file = $alt_js;
        }
    }

    $js_deps = ['jquery'];

    // Every My Login Form admin page uses Font Awesome icons in its markup
    // (headers, buttons, badges, stat cards) — not just the Designer.
    wp_enqueue_style('my-login-form-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', [], '6.5.0');

    // Shared color/gradient/button design tokens (:root vars), loaded once
    // and declared as a dependency of every page's own stylesheet below so
    // the cascade order is guaranteed regardless of enqueue call order.
    // Always lives in Admin/Pages/css/ regardless of $css_dir — the Supabase
    // page's own $css_dir points elsewhere, but it still needs these tokens.
    $vars_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/css/variables.css';
    if (file_exists($vars_file)) {
        wp_enqueue_style(
            'my-login-form-admin-vars',
            MY_LOGIN_FORM_URL . 'Admin/Pages/css/variables.css',
            [],
            filemtime($vars_file)
        );
    }

    if ($page_id === 'designer') {
        // Drag-and-drop for the Form Builder — WordPress's own bundled jQuery UI
        // modules instead of loading a duplicate copy from a CDN.
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('jquery-ui-sortable');
        $js_deps = ['jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'];

        wp_enqueue_style('my-login-form-jquery-ui-theme', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1');

        // WordPress's native CodeMirror code editor for the CSS/JS tabs. This
        // MUST happen here (on admin_enqueue_scripts, before admin-header.php's
        // wp_head() runs) — code-editor is a head script, so enqueuing it later
        // from inside designer.php itself would be too late to ever be printed.
        wp_enqueue_code_editor(['type' => 'text/css']);
        wp_enqueue_code_editor(['type' => 'text/javascript']);
    }

    // Enqueue CSS if file exists
    if (file_exists($css_file)) {
        wp_enqueue_style(
            'my-login-form-' . $page_id,
            $css_url . basename($css_file),
            ['my-login-form-admin-vars'],
            filemtime($css_file)
        );
        
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('CSS enqueued for page: ' . $page_id . ' from: ' . basename($css_file));
        }
    } else if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
        error_log('CSS file not found for page ' . $page_id . ': ' . $css_file);
    }
    
    // Enqueue JS if file exists
    if (file_exists($js_file)) {
        wp_enqueue_script(
            'my-login-form-' . $page_id,
            $js_url . basename($js_file),
            $js_deps,
            filemtime($js_file),
            true
        );

        // users-data.js is a static asset (no PHP is processed in it), so any
        // dynamic values it needs (ajax URL, nonce) must be localized here.
        if ($page_id === 'users-data') {
            wp_localize_script('my-login-form-' . $page_id, 'myLoginFormUsersData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('my_login_form_dashboard_nonce'),
            ]);
        }

        // settings.js's Quick Actions (Clear Cache / Reset / Export / Import)
        // call Admin\Settings's AJAX handlers, which all check this nonce.
        if ($page_id === 'settings') {
            wp_localize_script('my-login-form-' . $page_id, 'myLoginFormAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('my_login_form_dashboard_nonce'),
            ]);
        }

        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('JS enqueued for page: ' . $page_id . ' from: ' . basename($js_file));
        }
    } else if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
        error_log('JS file not found for page ' . $page_id . ': ' . $js_file);
    }
}

    // ------------------------------------------------------------------
    // Enqueue form assets on frontend from Public/Forms folder.
    // Runs on wp_enqueue_scripts (before wp_head) so CSS lands in <head>.
    // CSS files are self-contained — base styles are baked in at save time
    // by DesignerAjax::build_form_css(). No inline <style> output anywhere.
    // ------------------------------------------------------------------
    public function my_login_form_enqueue_assets(): void {
        global $wpdb, $post;

        $table = $wpdb->prefix . 'my_login_forms';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
            return;
        }

        // Scan post content for [my_login_form ...] to target only the forms that
        // appear on this page. Every occurrence is resolved id -> key -> type,
        // mirroring FormsShortcodes::render_form()'s own precedence exactly, so
        // whichever form the shortcode actually renders is the one whose CSS/JS
        // gets pre-loaded (real-world usage on this site is [my_login_form
        // key="..."], never id=, which a plain id-only regex would always miss).
        // Falls back to all active forms for widget areas, page-builder contexts,
        // and archive pages where the shortcode isn't in $post->post_content at all.
        $form_ids = array();
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'my_login_form' ) ) {
            preg_match_all( '/\[my_login_form([^\]]*)\]/i', $post->post_content, $shortcode_matches );
            foreach ( $shortcode_matches[1] as $attr_string ) {
                $atts = shortcode_parse_atts( $attr_string );
                $form_id = 0;

                if ( ! empty( $atts['id'] ) ) {
                    $form_id = (int) $atts['id'];
                } elseif ( ! empty( $atts['key'] ) ) {
                    $form_id = (int) $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM {$table} WHERE form_key = %s", sanitize_text_field( $atts['key'] )
                    ) );
                } else {
                    $type = ! empty( $atts['type'] ) ? sanitize_text_field( $atts['type'] ) : 'login';
                    $form_id = (int) $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM {$table} WHERE form_type = %s AND status = 'active' LIMIT 1", $type
                    ) );
                }

                if ( $form_id ) {
                    $form_ids[] = $form_id;
                }
            }
            $form_ids = array_unique( $form_ids );
        }

        // No shortcode found in post_content at all — most pages on the site
        // (home, shop, blog, ...) never contain a login form, so there is
        // nothing to lazy-load here. The rare page-builder/widget placement
        // that legitimately exists but wasn't detectable above is still
        // handled at actual render time by
        // FormsShortcodes::enqueue_form_assets(), which prints its own
        // <link>/<script> for just that one form instead of loading all of
        // them "just in case".
        if ( empty( $form_ids ) ) {
            return;
        }

        $in    = implode( ',', $form_ids ); // safe: all values cast to int above
        $forms = $wpdb->get_results( "SELECT id, css_file, js_file FROM {$table} WHERE id IN ({$in}) AND status = 'active'" );

        if ( ! $forms ) {
            return;
        }

        $plugin_url = MY_LOGIN_FORM_URL;
        $plugin_dir = MY_LOGIN_FORM_DIR;

        // Font Awesome for social icons — lands in <head>.
        wp_enqueue_style(
            'my-login-form-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
            array(),
            '6.5.0'
        );

        foreach ( $forms as $form ) {
            $id = (int) $form->id;

            // CSS — one file per form, contains base + custom styles.
            if ( ! empty( $form->css_file ) ) {
                $css_path = $plugin_dir . 'Public/Forms/css/' . $form->css_file;
                if ( file_exists( $css_path ) && filesize( $css_path ) > 0 ) {
                    wp_enqueue_style(
                        'my-login-form-css-' . $id,
                        $plugin_url . 'Public/Forms/css/' . $form->css_file,
                        array(),
                        filemtime( $css_path )
                    );
                }
            }

            // JS — loaded in footer so it never blocks rendering.
            if ( ! empty( $form->js_file ) ) {
                $js_path = $plugin_dir . 'Public/Forms/js/' . $form->js_file;
                if ( file_exists( $js_path ) && filesize( $js_path ) > 0 ) {
                    wp_enqueue_script(
                        'my-login-form-js-' . $id,
                        $plugin_url . 'Public/Forms/js/' . $form->js_file,
                        array( 'jquery' ),
                        filemtime( $js_path ),
                        true
                    );
                }
            }
        }

        // Shared AJAX data available to all form scripts via window.my_login_form_ajax.
        wp_localize_script( 'jquery', 'my_login_form_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'my_login_form_nonce' ),
        ) );
    }
}