<?php
namespace MyLoginForm\Admin;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Menus {
    
    private static $instance = null;

    private function __construct() {}
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init() {
        // Register admin menus
        add_action('admin_menu', [$this, 'register_menus']);
    
    }
    
    public function register_menus() {
        // Main menu page
        add_menu_page(
            __('My Login Form', 'my-login-form'),
            __('My Login Form', 'my-login-form'),
            'manage_options',
            'my-login-form-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-lock',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'my-login-form-dashboard',
            __('Dashboard', 'my-login-form'),
            __('Dashboard', 'my-login-form'),
            'manage_options',
            'my-login-form-dashboard',
            [$this, 'render_dashboard']
        );
        
        // Form Designer
        add_submenu_page(
            'my-login-form-dashboard',
            __('Form Designer', 'my-login-form'),
            __('Form Designer', 'my-login-form'),
            'manage_options',
            'my-login-form-designer',
            [$this, 'render_designer']
        );
        
        // User Data
        add_submenu_page(
            'my-login-form-dashboard',
            __('User Data', 'my-login-form'),
            __('Users Data', 'my-login-form'),
            'manage_options',
            'my-login-form-users-data',
            [$this, 'render_users_data']
        );
        
        // Supabase & Social
        add_submenu_page(
            'my-login-form-dashboard',
            __('Social Login Setup', 'my-login-form'),
            __('Social Login Setup', 'my-login-form'),
            'manage_options',
            'my-login-form-social-supabase',
            [$this, 'render_supabase']
        );
        
        // Settings — also where license activation lives now (a card at the
        // top, always shown regardless of license status; see
        // Admin/Pages/partials/license-card.php), rather than a separate
        // "License" menu item.
        add_submenu_page(
            'my-login-form-dashboard',
            __('Settings', 'my-login-form'),
            __('Settings', 'my-login-form'),
            'manage_options',
            'my-login-form-settings',
            [$this, 'render_settings']
        );
    }
    
    /**
     * Render Dashboard Page
     */
    public function render_dashboard() {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            \MyLoginForm\Licensing\Gate::render_gate_screen();
            return;
        }

        $dashboard_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/dashboard.php';
        
        if (file_exists($dashboard_file)) {
            // Initialize database globals if needed
            global $my_login_form_forms_db, $my_login_form_users_db;
            
            // Get stats for dashboard
            $form_stats = [];
            $user_stats = [];
            $recent_forms = [];
            $recent_users = [];
            $chart_data = ['labels' => [], 'data' => []];
            $default_forms_status = [];
            
            if (isset($my_login_form_forms_db) && $my_login_form_forms_db) {
                $form_stats = $my_login_form_forms_db->get_form_stats();
                $recent_forms = $my_login_form_forms_db->get_recent_forms_for_dashboard(5);
                $default_forms_status = $my_login_form_forms_db->get_default_forms_status();
            }
            
            if (isset($my_login_form_users_db) && $my_login_form_users_db) {
                $user_stats = $my_login_form_users_db->get_user_stats();
                $recent_users = $my_login_form_users_db->get_recent_users_for_dashboard(5);
                $chart_data = $my_login_form_users_db->get_registration_chart_data(7);
            }
            
            $stats = array_merge($form_stats, $user_stats);
            
            // Include the dashboard template
            include $dashboard_file;
        } else {
            $this->show_error('Dashboard template not found', $dashboard_file);
        }
    }
    
    /**
     * Render Designer Page
     */
    public function render_designer() {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            \MyLoginForm\Licensing\Gate::render_gate_screen();
            return;
        }

        $designer_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/designer.php';
        
        if (file_exists($designer_file)) {
            include $designer_file;
        } else {
            $this->show_error('Designer template not found', $designer_file);
        }
    }
    
    /**
     * Render Users Data Page
     */
    public function render_users_data() {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            \MyLoginForm\Licensing\Gate::render_gate_screen();
            return;
        }

        $users_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/users-data.php';
        
        if (!file_exists($users_file)) {
            $this->show_error('Users template not found', $users_file);
            return;
        }

        global $wpdb;

        // Pagination
        $per_page    = 20;
        $current_page = max(1, intval($_GET['paged'] ?? 1));
        $offset      = ($current_page - 1) * $per_page;
        $search      = sanitize_text_field($_GET['s'] ?? '');

        // ── Determine data source ─────────────────────────────
        // Use plugin's custom user table only if it actually has rows in it —
        // real registrations are created via wp_create_user() straight into
        // WordPress's own users table, so the plugin table (only ever
        // populated by the Supabase import / CSV bulk-import features) is
        // normally empty. Falling back to WP users whenever it's empty keeps
        // real registered users visible instead of showing a blank table.
        $plugin_table = $wpdb->prefix . 'my_login_users_data';
        $plugin_table_exists = (bool) $wpdb->get_var("SHOW TABLES LIKE '$plugin_table'");
        $use_plugin_table = $plugin_table_exists && (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table") > 0;

        if ($use_plugin_table) {
            // --- Plugin custom users ---
            $where  = '1=1';
            $params = array();
            if (!empty($search)) {
                $like   = '%' . $wpdb->esc_like($search) . '%';
                $where  .= $wpdb->prepare(' AND (user_email LIKE %s OR user_login LIKE %s OR user_first_name LIKE %s OR user_last_name LIKE %s)', $like, $like, $like, $like);
            }
            $total_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table WHERE $where");
            $total_pages = max(1, ceil($total_users / $per_page));
            $users_raw   = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $plugin_table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page, $offset
            ));

            // Normalise rows
            $users = array_map(function($u) use ($wpdb) {
                $u->display_name  = trim(($u->user_first_name ?? '') . ' ' . ($u->user_last_name ?? '')) ?: ($u->user_login ?? $u->user_email);
                $u->user_roles    = $u->wp_user_id ? implode(', ', array_map('ucfirst', (array)(get_userdata($u->wp_user_id)->roles ?? array()))) : '—';
                $u->source        = 'plugin';
                // There is no dedicated sync_status column; derive it from whether a Supabase link exists.
                $u->sync_status   = !empty($u->supabase_user_id) ? 'synced' : 'not_synced';
                return $u;
            }, $users_raw ?: array());

            // Stats
            $stats = array(
                'total'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table"),
                'synced'       => (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table WHERE supabase_user_id IS NOT NULL AND supabase_user_id != ''"),
                'today'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table WHERE DATE(created_at) = CURDATE()"),
                'social'       => (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table WHERE social_provider != '' AND social_provider IS NOT NULL"),
                'wp_users'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table WHERE wp_user_id IS NOT NULL AND wp_user_id > 0"),
                'pending_sync' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table WHERE supabase_user_id IS NULL OR supabase_user_id = ''"),
            );
            $stats['sync_rate'] = $stats['total'] > 0 ? round(($stats['synced'] / $stats['total']) * 100) : 0;

        } else {
            // --- WordPress native users ---
            $woo_active  = class_exists('WooCommerce');
            $wp_user_args = array(
                'number'  => $per_page,
                'offset'  => $offset,
                'orderby' => 'registered',
                'order'   => 'DESC',
            );
            if (!empty($search)) {
                $wp_user_args['search']         = '*' . $search . '*';
                $wp_user_args['search_columns'] = array('user_login', 'user_email', 'display_name');
            }
            $wp_users    = get_users($wp_user_args);
            $total_users = (int) count_users()['total_users'];
            if (!empty($search)) {
                // For accuracy with search, count again
                $count_args = array('search' => '*' . $search . '*', 'search_columns' => array('user_login', 'user_email', 'display_name'), 'fields' => 'ID');
                $total_users = count(get_users($count_args));
            }
            $total_pages = max(1, ceil($total_users / $per_page));

            $users = array_map(function($wp_user) use ($woo_active) {
                $u = new \stdClass();
                $u->id              = $wp_user->ID;
                $u->user_email      = $wp_user->user_email;
                $u->user_login      = $wp_user->user_login;
                $u->user_first_name = get_user_meta($wp_user->ID, 'first_name', true);
                $u->user_last_name  = get_user_meta($wp_user->ID, 'last_name', true);
                $u->display_name    = $wp_user->display_name;
                $u->user_phone      = get_user_meta($wp_user->ID, 'billing_phone', true) ?: get_user_meta($wp_user->ID, 'phone', true);
                $u->created_at      = $wp_user->user_registered;
                $u->wp_user_id      = $wp_user->ID;
                $u->user_roles      = implode(', ', array_map('ucfirst', (array)$wp_user->roles));
                $u->social_provider = get_user_meta($wp_user->ID, 'mlf_social_provider', true);
                $u->sync_status     = 'wp_native';
                $u->source          = 'wp';
                // WooCommerce extras
                if ($woo_active) {
                    $u->woo_orders   = wc_get_customer_order_count($wp_user->ID);
                    $u->woo_spent    = wc_price(wc_get_customer_total_spent($wp_user->ID));
                }
                return $u;
            }, $wp_users);

            $counts = count_users();
            $stats = array(
                'total'        => $counts['total_users'],
                'synced'       => 0,
                'today'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE DATE(user_registered) = CURDATE()"),
                'social'       => (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = 'mlf_social_provider' AND meta_value != ''"),
                'wp_users'     => $counts['total_users'],
                'pending_sync' => 0,
                'sync_rate'    => 100,
            );
        }

        $use_woo = class_exists('WooCommerce');

        include $users_file;
    }
    
    /**
     * Render Supabase Page
     */
    public function render_supabase() {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            \MyLoginForm\Licensing\Gate::render_gate_screen();
            return;
        }

        $supabase_file = MY_LOGIN_FORM_DIR . 'supabase/supabase.php';

        if (file_exists($supabase_file)) {
            include $supabase_file;
        } else {
            echo '<div class="wrap"><div class="notice notice-info"><p>' . __('Supabase integration coming soon!', 'my-login-form') . '</p></div></div>';
        }
    }
    
    /**
     * Render Settings Page
     *
     * Delegates to Admin\Settings, which loads the real saved my_login_form_*
     * options before including the template. A bare include here (the old
     * behaviour) leaves $settings/$system_info undefined, so the template's
     * own hardcoded fallback array wins every time — the page would always
     * display defaults and ignore whatever was actually saved.
     */
    public function render_settings() {
        // Deliberately NOT gated here — Settings now always renders, since
        // it's what shows the license activation card (Admin/Pages/partials/license-card.php)
        // for an unlicensed site. settings.php itself gates the rest of the
        // form (below that card) on license status.
        if (class_exists('MyLoginForm\\Admin\\Settings')) {
            \MyLoginForm\Admin\Settings::get_instance()->render_settings();
            return;
        }

        $settings_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/settings.php';
        if (file_exists($settings_file)) {
            include $settings_file;
        } else {
            $this->show_error('Settings template not found', $settings_file);
        }
    }

    /**
     * Show error message
     */
    private function show_error($message, $file_path = '') {
        echo '<div class="wrap">';
        echo '<div class="notice notice-error">';
        echo '<p><strong>' . __('My Login Form Error:', 'my-login-form') . '</strong> ' . esc_html($message) . '</p>';
        if ($file_path && defined('WP_DEBUG') && WP_DEBUG) {
            echo '<p><code>' . esc_html($file_path) . '</code></p>';
        }
        echo '</div>';
        echo '</div>';
    }

    // In Menus class
    public function add_action_links($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=my-login-form-designer') . '">' . __('Form Designer', 'my-login-form') . '</a>',
            '<a href="' . admin_url('admin.php?page=my-login-form-users-data') . '">' . __('Users', 'my-login-form') . '</a>',
            '<a href="' . admin_url('admin.php?page=my-login-form-settings') . '">' . __('Settings', 'my-login-form') . '</a>',
        ];

        return array_merge($plugin_links, $links);
    }
}