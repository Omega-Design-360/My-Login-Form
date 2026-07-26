<?php
/**
 * Dashboard AJAX Handler
 *
 * @package MyLoginForm\Ajax
 */
namespace MyLoginForm\Ajax;

// Prevent direct access
defined('ABSPATH') || exit;

class DashboardAjax {

    /**
     * Instance of this class
     *
     * @var DashboardAjax
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return DashboardAjax
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Dashboard AJAX handlers
        add_action('wp_ajax_my_login_get_dashboard_stats', array($this, 'get_dashboard_stats'));
        add_action('wp_ajax_my_login_get_registration_chart', array($this, 'get_registration_chart'));
        add_action('wp_ajax_my_login_get_recent_users', array($this, 'get_recent_users'));
        add_action('wp_ajax_my_login_get_recent_forms', array($this, 'get_recent_forms'));
        add_action('wp_ajax_my_login_get_system_status', array($this, 'get_system_status'));
        add_action('wp_ajax_my_login_refresh_dashboard', array($this, 'refresh_dashboard'));
        add_action('wp_ajax_my_login_export_stats', array($this, 'export_stats'));
    }

    /**
     * Get all dashboard stats
     */
    public function get_dashboard_stats() {
        check_ajax_referer('my_login_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }

        global $wpdb;
        
        // Get total forms
        $forms_table = $wpdb->prefix . 'my_login_forms';
        $total_forms = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table WHERE is_system = 0");
        $active_forms = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table WHERE status = 'active' AND is_system = 0");
        
        // Get form types distribution
        $form_types = $wpdb->get_results("
            SELECT form_type, COUNT(*) as count 
            FROM $forms_table 
            WHERE is_system = 0 
            GROUP BY form_type
        ");
        
        $form_types_data = [];
        foreach ($form_types as $type) {
            $form_types_data[$type->form_type] = intval($type->count);
        }
        
        // Get total users
        $users_table = $wpdb->prefix . 'my_login_users';
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM $users_table");
        
        // Get today's registrations
        $today = date('Y-m-d 00:00:00');
        $today_users = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $users_table WHERE created_at >= %s",
            $today
        ));
        
        // Get active users (logged in within last 30 days)
        $active_users = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->prefix}my_login_sessions 
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Get social login users
        $social_users = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->prefix}my_login_social_profiles
        ");
        
        // Get synced users (Firebase)
        $synced_users = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $users_table 
            WHERE firebase_uid IS NOT NULL AND firebase_uid != ''
        ");
        
        $sync_rate = $total_users > 0 ? round(($synced_users / $total_users) * 100) : 0;
        
        // Get total views and submissions
        $total_views = $wpdb->get_var("SELECT SUM(views_count) FROM $forms_table");
        $total_submissions = $wpdb->get_var("SELECT SUM(submissions_count) FROM $forms_table");
        
        $stats = array(
            'total_forms' => intval($total_forms),
            'active_forms' => intval($active_forms),
            'total_users' => intval($total_users),
            'today_users' => intval($today_users),
            'active_users' => intval($active_users),
            'social_users' => intval($social_users),
            'synced_users' => intval($synced_users),
            'sync_rate' => $sync_rate,
            'total_views' => intval($total_views),
            'total_submissions' => intval($total_submissions),
            'form_types' => $form_types_data
        );
        
        wp_send_json_success($stats);
    }

    /**
     * Get registration chart data
     */
    public function get_registration_chart() {
        check_ajax_referer('my_login_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }

        $days = intval($_POST['days'] ?? 7);
        $chart_type = sanitize_text_field($_POST['chart_type'] ?? 'registrations');
        
        global $wpdb;
        $users_table = $wpdb->prefix . 'my_login_users';
        
        $labels = [];
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date_i18n('M j', strtotime($date));
            
            if ($chart_type === 'registrations') {
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $users_table WHERE DATE(created_at) = %s",
                    $date
                ));
            } else {
                // Form submissions
                $submissions_table = $wpdb->prefix . 'my_login_submissions';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $submissions_table WHERE DATE(submitted_at) = %s",
                    $date
                ));
            }
            
            $data[] = intval($count);
        }
        
        wp_send_json_success(array(
            'labels' => $labels,
            'data' => $data,
            'chart_type' => $chart_type
        ));
    }

    /**
     * Get recent users
     */
    public function get_recent_users() {
        check_ajax_referer('my_login_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }

        $limit = intval($_POST['limit'] ?? 10);
        
        global $wpdb;
        $users_table = $wpdb->prefix . 'my_login_users';
        
        $users = $wpdb->get_results($wpdb->prepare("
            SELECT id, user_email, user_first_name, user_last_name, 
                   email_verified, created_at 
            FROM $users_table 
            ORDER BY created_at DESC 
            LIMIT %d
        ", $limit));
        
        foreach ($users as $user) {
            $user->display_name = trim($user->user_first_name . ' ' . $user->user_last_name);
            if (empty($user->display_name)) {
                $user->display_name = explode('@', $user->user_email)[0];
            }
            $user->avatar_url = get_avatar_url($user->user_email, ['size' => 32]);
            $user->created_date = date_i18n(get_option('date_format'), strtotime($user->created_at));
        }
        
        wp_send_json_success($users);
    }

    /**
     * Get recent forms
     */
    public function get_recent_forms() {
        check_ajax_referer('my_login_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }

        $limit = intval($_POST['limit'] ?? 10);
        
        global $wpdb;
        $forms_table = $wpdb->prefix . 'my_login_forms';
        
        $forms = $wpdb->get_results($wpdb->prepare("
            SELECT id, name, form_key, form_type, status, 
                   views_count, submissions_count, created_at 
            FROM $forms_table 
            WHERE is_system = 0 
            ORDER BY created_at DESC 
            LIMIT %d
        ", $limit));
        
        foreach ($forms as $form) {
            $form->shortcode = '[my_login_form id="' . $form->id . '"]';
            $form->created_date = date_i18n(get_option('date_format'), strtotime($form->created_at));
            $form->type_label = ucfirst(str_replace('_', ' ', $form->form_type));
            
            // Set icon based on form type
            switch($form->form_type) {
                case 'login': $form->icon = '🔐'; break;
                case 'register': $form->icon = '📝'; break;
                case 'welcome': $form->icon = '👋'; break;
                case 'forgot_password': $form->icon = '🔑'; break;
                case 'popup': $form->icon = '💬'; break;
                case 'contact': $form->icon = '✉️'; break;
                default: $form->icon = '📄';
            }
        }
        
        wp_send_json_success($forms);
    }

    /**
     * Get system status
     */
    public function get_system_status() {
        check_ajax_referer('my_login_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }

        global $wpdb;
        
        // Check WordPress version
        $wp_version = get_bloginfo('version');
        $wp_status = version_compare($wp_version, '5.6', '>=') ? 'good' : 'error';
        
        // Check PHP version
        $php_version = PHP_VERSION;
        $php_status = version_compare($php_version, '7.4', '>=') ? 'good' : 'error';
        
        // Check MySQL version
        $mysql_version = $wpdb->get_var("SELECT VERSION()");
        $mysql_status = version_compare($mysql_version, '5.6', '>=') ? 'good' : 'warning';
        
        // Check database tables
        $required_tables = [
            'my_login_forms',
            'my_login_users',
            'my_login_sessions',
            'my_login_submissions',
            'my_login_social_profiles'
        ];
        
        $missing_tables = [];
        foreach ($required_tables as $table) {
            $full_table = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$full_table'") !== $full_table) {
                $missing_tables[] = $table;
            }
        }
        
        $tables_status = empty($missing_tables) ? 'good' : 'error';
        $tables_value = empty($missing_tables) ? __('All tables present', 'my-login-form') : sprintf(__('Missing: %s', 'my-login-form'), implode(', ', $missing_tables));
        
        // Check if profile page exists
        $profile_page_id = get_option('my_login_profile_page_id');
        $profile_page_status = ($profile_page_id && get_post_status($profile_page_id)) ? 'good' : 'warning';
        $profile_page_value = $profile_page_status === 'good' ? get_the_title($profile_page_id) : __('Not configured', 'my-login-form');
        
        // Check Firebase integration
        $firebase_enabled = get_option('my_login_firebase_enabled', false);
        $firebase_status = $firebase_enabled ? 'good' : 'warning';
        $firebase_value = $firebase_enabled ? __('Connected', 'my-login-form') : __('Not configured', 'my-login-form');
        
        // Check Social Login
        $social_login_enabled = get_option('my_login_social_login_enabled', false);
        $social_status = $social_login_enabled ? 'good' : 'warning';
        $social_value = $social_login_enabled ? __('Enabled', 'my-login-form') : __('Disabled', 'my-login-form');
        
        // Check WooCommerce integration
        $woocommerce_active = class_exists('WooCommerce');
        $woo_status = $woocommerce_active ? 'good' : 'warning';
        $woo_value = $woocommerce_active ? __('Active', 'my-login-form') : __('Not installed', 'my-login-form');
        
        $system_status = [
            'wordpress' => [
                'name' => __('WordPress Version', 'my-login-form'),
                'value' => $wp_version,
                'status' => $wp_status
            ],
            'php' => [
                'name' => __('PHP Version', 'my-login-form'),
                'value' => $php_version,
                'status' => $php_status
            ],
            'mysql' => [
                'name' => __('MySQL Version', 'my-login-form'),
                'value' => $mysql_version,
                'status' => $mysql_status
            ],
            'tables' => [
                'name' => __('Database Tables', 'my-login-form'),
                'value' => $tables_value,
                'status' => $tables_status
            ],
            'profile_page' => [
                'name' => __('Profile Page', 'my-login-form'),
                'value' => $profile_page_value,
                'status' => $profile_page_status
            ],
            'firebase' => [
                'name' => __('Firebase Integration', 'my-login-form'),
                'value' => $firebase_value,
                'status' => $firebase_status
            ],
            'social_login' => [
                'name' => __('Social Login', 'my-login-form'),
                'value' => $social_value,
                'status' => $social_status
            ],
            'woocommerce' => [
                'name' => __('WooCommerce', 'my-login-form'),
                'value' => $woo_value,
                'status' => $woo_status
            ]
        ];
        
        // Get default forms status
        $forms_table = $wpdb->prefix . 'my_login_forms';
        $default_forms = ['login', 'register', 'forgot_password'];
        $default_forms_status = [];
        
        foreach ($default_forms as $form_type) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $forms_table WHERE form_type = %s AND (is_system = 1 OR is_builtin = 1)",
                $form_type
            ));
            
            $default_forms_status[] = [
                'name' => ucfirst(str_replace('_', ' ', $form_type)),
                'exists' => $exists > 0,
                'status' => $exists > 0 ? 'good' : 'warning'
            ];
        }
        
        wp_send_json_success([
            'system_status' => $system_status,
            'default_forms_status' => $default_forms_status
        ]);
    }

    /**
     * Refresh all dashboard data
     */
    public function refresh_dashboard() {
        check_ajax_referer('my_login_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }

        // Clear dashboard cache
        wp_cache_delete('my_login_dashboard_stats');
        wp_cache_delete('my_login_registration_chart');
        wp_cache_delete('my_login_recent_users');
        wp_cache_delete('my_login_recent_forms');
        
        // Get fresh data
        $this->get_dashboard_stats();
    }

    /**
     * Export dashboard statistics
     */
    public function export_stats() {
        check_ajax_referer('my_login_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }

        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        
        global $wpdb;
        
        // Get all stats
        $forms_table = $wpdb->prefix . 'my_login_forms';
        $users_table = $wpdb->prefix . 'my_login_users';
        
        $stats = [
            'export_date' => current_time('mysql'),
            'site_name' => get_bloginfo('name'),
            'total_forms' => $wpdb->get_var("SELECT COUNT(*) FROM $forms_table WHERE is_system = 0"),
            'total_users' => $wpdb->get_var("SELECT COUNT(*) FROM $users_table"),
            'total_views' => $wpdb->get_var("SELECT SUM(views_count) FROM $forms_table"),
            'total_submissions' => $wpdb->get_var("SELECT SUM(submissions_count) FROM $forms_table"),
        ];
        
        if ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="my-login-stats-' . date('Y-m-d') . '.json"');
            echo json_encode($stats, JSON_PRETTY_PRINT);
            exit;
        } else {
            // CSV format
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="my-login-stats-' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, array_keys($stats));
            fputcsv($output, $stats);
            fclose($output);
            exit;
        }
    }
}