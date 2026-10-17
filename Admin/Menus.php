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
        add_action('admin_post_my_login_form_send_mailing', [$this, 'handle_mailing_send']);
        add_action('admin_post_my_login_form_save_mailing_config', [$this, 'handle_mailing_config_save']);
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

        // Mailing: visible in the admin menu, with sending enabled only when
        // both Supabase and Resend are configured.
        add_submenu_page(
            'my-login-form-dashboard',
            __('Mailing', 'my-login-form'),
            __('Mailing', 'my-login-form'),
            'manage_options',
            'my-login-form-mailing',
            [$this, 'render_mailing']
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
                $wp_sync_time     = !empty($u->wp_user_id) ? get_user_meta((int) $u->wp_user_id, '_my_login_form_supabase_synced_at', true) : '';
                $wp_sync_error    = !empty($u->wp_user_id) ? get_user_meta((int) $u->wp_user_id, '_my_login_form_supabase_sync_error', true) : '';
                $u->sync_status   = (!empty($u->supabase_user_id) || $wp_sync_time) ? 'synced' : ($wp_sync_error ? 'failed' : 'pending');
                return $u;
            }, $users_raw ?: array());

            // Stats
            $plugin_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table");
            $synced_plugin_rows = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $plugin_table p WHERE (p.supabase_user_id IS NOT NULL AND p.supabase_user_id != '') OR EXISTS (SELECT 1 FROM {$wpdb->usermeta} um WHERE um.user_id = p.wp_user_id AND um.meta_key = %s)",
                '_my_login_form_supabase_synced_at'
            ));
            $stats = array(
                'total'        => $plugin_total,
                'synced'       => $synced_plugin_rows,
                'today'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table WHERE DATE(created_at) = CURDATE()"),
                'social'       => (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table WHERE social_provider != '' AND social_provider IS NOT NULL"),
                'wp_users'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM $plugin_table WHERE wp_user_id IS NOT NULL AND wp_user_id > 0"),
                'pending_sync' => max(0, $plugin_total - $synced_plugin_rows),
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
                $sync_time = get_user_meta($wp_user->ID, '_my_login_form_supabase_synced_at', true);
                $sync_error = get_user_meta($wp_user->ID, '_my_login_form_supabase_sync_error', true);
                $u->sync_status     = $sync_time ? 'synced' : ($sync_error ? 'failed' : 'pending');
                $u->source          = 'wp';
                // WooCommerce extras
                if ($woo_active) {
                    $u->woo_orders   = wc_get_customer_order_count($wp_user->ID);
                    $u->woo_spent    = wc_price(wc_get_customer_total_spent($wp_user->ID));
                }
                return $u;
            }, $wp_users);

            $counts = count_users();
            $synced_wp_users = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s",
                '_my_login_form_supabase_synced_at'
            ));
            $wp_total = (int) $counts['total_users'];
            $stats = array(
                'total'        => $wp_total,
                'synced'       => min($wp_total, $synced_wp_users),
                'today'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE DATE(user_registered) = CURDATE()"),
                'social'       => (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = 'mlf_social_provider' AND meta_value != ''"),
                'wp_users'     => $counts['total_users'],
                'pending_sync' => max(0, $wp_total - $synced_wp_users),
                'sync_rate'    => $wp_total > 0 ? round((min($wp_total, $synced_wp_users) / $wp_total) * 100) : 0,
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

    /** Render the Mailing page and its integration status. */
    public function render_mailing(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to access this page.', 'my-login-form'));
        }
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            \MyLoginForm\Licensing\Gate::render_gate_screen();
            return;
        }

        $supabase_ready = (bool) get_option('my_login_supabase_enabled', 0)
            && (bool) get_option('my_login_supabase_url', '')
            && (bool) get_option('my_login_supabase_anon_key', '');
        $resend_key = trim((string) get_option('my_login_form_resend_api_key', ''));
        $resend_from = sanitize_email((string) get_option('my_login_form_resend_from_email', ''));
        $resend_ready = $resend_key !== '' && is_email($resend_from);
        $mailing_ready = $supabase_ready && $resend_ready;
        $resend_domain_result = $resend_key !== '' ? $this->get_resend_verified_domains($resend_key) : ['domains' => [], 'error' => ''];
        $resend_domains = $resend_domain_result['domains'];
        $resend_domains_error = $resend_domain_result['error'];
        $resend_sender_domain = is_email($resend_from) ? strtolower(substr(strrchr($resend_from, '@'), 1)) : '';
        $resend_guide_done = $resend_ready && in_array($resend_sender_domain, $resend_domains, true);
        $supabase_users_sync_complete = (bool) get_option('my_login_form_supabase_user_sync_complete', 0);
        $default_sender_local = is_email($resend_from) ? substr($resend_from, 0, strpos($resend_from, '@')) : '';
        $supabase_url_value = (string) get_option('my_login_supabase_url', '');
        $supabase_anon_hint = $this->mask_mailing_secret((string) get_option('my_login_supabase_anon_key', ''));
        $supabase_service_hint = $this->mask_mailing_secret((string) get_option('my_login_supabase_service_key', ''));
        $resend_key_hint = $this->mask_mailing_secret($resend_key);
        $resend_from_name = (string) get_option('my_login_form_resend_from_name', '');
        $users_sql_file = MY_LOGIN_FORM_DIR . 'supabase/sql/04_users.sql';
        $supabase_users_sql = is_readable($users_sql_file) ? (string) file_get_contents($users_sql_file) : '';

        include MY_LOGIN_FORM_DIR . 'Admin/Pages/mailing.php';
    }

    /** Save Supabase or Resend credentials from the Mailing page. */
    public function handle_mailing_config_save(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to change mailing settings.', 'my-login-form'));
        }
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            wp_die(esc_html__('An active plugin license is required to change mailing settings.', 'my-login-form'));
        }

        $type_raw = $_POST['config_type'] ?? '';
        $type = is_string($type_raw) ? sanitize_key(wp_unslash($type_raw)) : '';
        check_admin_referer('my_login_form_save_mailing_' . $type);

        if ($type === 'supabase') {
            $url_raw = $_POST['supabase_url'] ?? '';
            $anon_raw = $_POST['supabase_anon_key'] ?? '';
            $service_raw = $_POST['supabase_service_key'] ?? '';
            $url = is_string($url_raw) ? esc_url_raw(wp_unslash($url_raw)) : '';
            $anon_key = is_string($anon_raw) ? sanitize_text_field(wp_unslash($anon_raw)) : '';
            $service_key = is_string($service_raw) ? sanitize_text_field(wp_unslash($service_raw)) : '';
            if ($url === '') {
                $url = (string) get_option('my_login_supabase_url', '');
            }
            if ($anon_key === '') {
                $anon_key = (string) get_option('my_login_supabase_anon_key', '');
            }
            if ($service_key === '') {
                $service_key = (string) get_option('my_login_supabase_service_key', '');
            }

            $previous_supabase_url = (string) get_option('my_login_supabase_url', '');
            if (untrailingslashit($previous_supabase_url) !== untrailingslashit($url)) {
                delete_option('my_login_form_supabase_user_sync_complete');
            }

            $enable = isset($_POST['enable_supabase']) ? 1 : 0;
            if ($enable && ($url === '' || $anon_key === '' || !in_array(wp_parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true))) {
                $this->redirect_mailing(['config_error' => 'supabase_required']);
            }

            update_option('my_login_supabase_url', $url);
            update_option('my_login_supabase_anon_key', $anon_key, false);
            update_option('my_login_supabase_service_key', $service_key, false);
            update_option('my_login_supabase_enabled', $enable);
        } elseif ($type === 'resend') {
            $api_key_raw = $_POST['resend_api_key'] ?? '';
            $from_email_raw = $_POST['resend_from_email'] ?? '';
            $from_name_raw = $_POST['resend_from_name'] ?? '';
            $api_key = is_string($api_key_raw) ? sanitize_text_field(wp_unslash($api_key_raw)) : '';
            $from_email = is_string($from_email_raw) ? sanitize_email(wp_unslash($from_email_raw)) : '';
            $from_name = is_string($from_name_raw) ? sanitize_text_field(wp_unslash($from_name_raw)) : '';

            if (!is_email($from_email)) {
                $this->redirect_mailing(['config_error' => 'resend_email']);
            }
            if ($api_key !== '') {
                update_option('my_login_form_resend_api_key', $api_key, false);
            }
            update_option('my_login_form_resend_from_email', $from_email);
            update_option('my_login_form_resend_from_name', $from_name);
        } elseif ($type === 'email_preferences') {
            foreach (['email_verification', 'welcome_email', 'admin_notifications'] as $setting) {
                update_option('my_login_form_' . $setting, isset($_POST[$setting]) ? 1 : 0);
            }
        } else {
            $this->redirect_mailing(['config_error' => 'invalid_section']);
        }

        $this->redirect_mailing(['config_saved' => $type]);
    }

    private function mask_mailing_secret(string $secret): string {
        if ($secret === '') {
            return '';
        }
        if (strlen($secret) <= 8) {
            return str_repeat('*', strlen($secret));
        }
        return substr($secret, 0, 4) . str_repeat('*', 10) . substr($secret, -4);
    }

    /** Fetch and briefly cache domains that Resend permits for sending. */
    private function get_resend_verified_domains(string $api_key): array {
        $cache_key = 'my_login_form_resend_domains_' . substr(hash('sha256', $api_key), 0, 20);
        $cached = get_transient($cache_key);
        if (is_array($cached) && isset($cached['domains'])) {
            return $cached;
        }

        $response = wp_remote_get('https://api.resend.com/domains?limit=100', [
            'headers' => ['Authorization' => 'Bearer ' . $api_key],
            'timeout' => 12,
        ]);
        if (is_wp_error($response)) {
            return ['domains' => [], 'error' => $response->get_error_message()];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300 || !is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
            $result = ['domains' => [], 'error' => __('Resend did not return a domain list. Check the API key and try refreshing.', 'my-login-form')];
            set_transient($cache_key, $result, 2 * MINUTE_IN_SECONDS);
            return $result;
        }

        $domains = [];
        foreach ($payload['data'] as $domain) {
            if (!is_array($domain) || ($domain['status'] ?? '') !== 'verified') {
                continue;
            }
            $sending = $domain['capabilities']['sending'] ?? 'enabled';
            $name = strtolower(trim((string) ($domain['name'] ?? '')));
            if ($sending === 'enabled' && $name !== '' && preg_match('/^[a-z0-9.-]+$/', $name)) {
                $domains[] = $name;
            }
        }

        $result = ['domains' => array_values(array_unique($domains)), 'error' => ''];
        set_transient($cache_key, $result, 10 * MINUTE_IN_SECONDS);
        return $result;
    }

    /** Send one email per recipient through Resend. */
    public function handle_mailing_send(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to send email.', 'my-login-form'));
        }
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            wp_die(esc_html__('An active plugin license is required to send email.', 'my-login-form'));
        }
        check_admin_referer('my_login_form_send_mailing');

        $supabase_ready = (bool) get_option('my_login_supabase_enabled', 0)
            && (bool) get_option('my_login_supabase_url', '')
            && (bool) get_option('my_login_supabase_anon_key', '');
        $api_key = trim((string) get_option('my_login_form_resend_api_key', ''));
        $from_email = sanitize_email((string) get_option('my_login_form_resend_from_email', ''));
        if (!$supabase_ready || $api_key === '' || !is_email($from_email)) {
            $this->redirect_mailing(['mail_error' => 'not_configured']);
        }

        $source_raw = $_POST['recipient_source'] ?? 'manual';
        $source = is_string($source_raw) ? sanitize_key(wp_unslash($source_raw)) : 'manual';
        if ($source === 'manual') {
            $recipients_raw = $_POST['recipients'] ?? '';
            $raw_recipients = is_string($recipients_raw) ? sanitize_textarea_field(wp_unslash($recipients_raw)) : '';
            $candidates = preg_split('/[\s,;]+/', $raw_recipients, -1, PREG_SPLIT_NO_EMPTY);
            $recipients = array_map('sanitize_email', $candidates);
            if ($recipients && count(array_filter($recipients, 'is_email')) !== count($recipients)) {
                $this->redirect_mailing(['mail_error' => 'recipient_count']);
            }
        } elseif ($source === 'import') {
            if (empty($_FILES['mailing_list']['name'])) {
                $this->redirect_mailing(['mail_error' => 'source_missing']);
            }
            $recipients = $this->read_mailing_list_upload($_FILES['mailing_list']);
            if (is_wp_error($recipients)) {
                $this->redirect_mailing(['mail_error' => $recipients->get_error_code()]);
            }
        } elseif ($source === 'wp_users') {
            $recipients = [];
            foreach (get_users(['number' => -1, 'fields' => 'all']) as $wp_user) {
                $recipients[] = sanitize_email($wp_user->user_email ?? '');
            }
            $recipients = array_values(array_filter($recipients, 'is_email'));
        } else {
            $this->redirect_mailing(['mail_error' => 'source_missing']);
        }

        $recipients = array_values(array_unique(array_filter($recipients, 'is_email')));
        if (!$recipients) {
            $this->redirect_mailing(['mail_error' => 'recipient_count']);
        }

        $limit_raw = $_POST['recipient_limit'] ?? '100';
        $limit_choice = is_string($limit_raw) ? sanitize_key(wp_unslash($limit_raw)) : '100';
        if ($limit_choice === 'custom') {
            $custom_limit_raw = $_POST['recipient_limit_custom'] ?? '';
            $recipient_limit = is_scalar($custom_limit_raw) ? absint($custom_limit_raw) : 0;
        } else {
            $recipient_limit = in_array($limit_choice, ['50', '100', '250', '500', '1000'], true) ? absint($limit_choice) : 0;
        }
        if ($recipient_limit < 1) {
            $this->redirect_mailing(['mail_error' => 'recipient_limit']);
        }
        if (count($recipients) > $recipient_limit) {
            $this->redirect_mailing(['mail_error' => 'recipient_limit']);
        }

        $from_email = sanitize_email((string) get_option('my_login_form_resend_from_email', ''));
        $sender_domain_raw = $_POST['sender_domain'] ?? '';
        $sender_domain = is_string($sender_domain_raw) ? strtolower(sanitize_text_field(wp_unslash($sender_domain_raw))) : '';
        if ($sender_domain !== '') {
            $sender_local_raw = $_POST['sender_local'] ?? '';
            $sender_local = is_string($sender_local_raw) ? trim(sanitize_text_field(wp_unslash($sender_local_raw))) : '';
            $domain_result = $this->get_resend_verified_domains($api_key);
            if (!in_array($sender_domain, $domain_result['domains'], true) || $sender_local === '' || strpos($sender_local, '@') !== false) {
                $this->redirect_mailing(['mail_error' => 'sender_invalid']);
            }
            $from_email = sanitize_email($sender_local . '@' . $sender_domain);
        }
        if (!is_email($from_email)) {
            $this->redirect_mailing(['mail_error' => 'sender_invalid']);
        }

        $subject_raw = $_POST['subject'] ?? '';
        $message_raw = $_POST['message'] ?? '';
        $subject = is_string($subject_raw) ? sanitize_text_field(wp_unslash($subject_raw)) : '';
        $message = is_string($message_raw) ? sanitize_textarea_field(wp_unslash($message_raw)) : '';
        if ($subject === '' || $message === '') {
            $this->redirect_mailing(['mail_error' => 'missing_content']);
        }

        $from_name = sanitize_text_field((string) get_option('my_login_form_resend_from_name', ''));
        $from = $from_name !== '' ? sprintf('%s <%s>', $from_name, $from_email) : $from_email;
        $sent = 0;
        foreach ($recipients as $recipient) {
            $response = wp_remote_post('https://api.resend.com/emails', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body' => wp_json_encode([
                    'from' => $from,
                    'to' => [$recipient],
                    'subject' => $subject,
                    'text' => $message,
                ]),
                'timeout' => 15,
            ]);

            if (!is_wp_error($response)) {
                $code = (int) wp_remote_retrieve_response_code($response);
                if ($code >= 200 && $code < 300) {
                    $sent++;
                }
            }
        }

        $this->redirect_mailing([
            'mail_sent' => $sent,
            'mail_failed' => count($recipients) - $sent,
        ]);
    }

    /** Read email addresses from a CSV or Excel XLSX mailing list. */
    private function read_mailing_list_upload(array $upload) {
        if (!isset($upload['error'], $upload['tmp_name']) || (int) $upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            return new \WP_Error('upload_failed', __('The mailing list file could not be uploaded. Check the server upload size limit and try again.', 'my-login-form'));
        }

        $extension = strtolower(pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            $handle = fopen($upload['tmp_name'], 'r');
            if (!$handle) {
                return new \WP_Error('invalid_file', __('The CSV file could not be read.', 'my-login-form'));
            }
            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        } elseif ($extension === 'xlsx') {
            if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
                return new \WP_Error('xlsx_unavailable', __('This server cannot read XLSX files. Save the list as CSV and upload it instead.', 'my-login-form'));
            }
            $zip = new \ZipArchive();
            if ($zip->open($upload['tmp_name']) !== true) {
                return new \WP_Error('invalid_file', __('The XLSX file could not be opened.', 'my-login-form'));
            }
            $shared_strings = [];
            $shared_xml = $zip->getFromName('xl/sharedStrings.xml');
            if ($shared_xml !== false) {
                $shared = @simplexml_load_string($shared_xml, 'SimpleXMLElement', LIBXML_NONET);
                if ($shared !== false) {
                    foreach ($shared->xpath('//*[local-name()="si"]') ?: [] as $item) {
                        $shared_strings[] = implode('', array_map('strval', $item->xpath('.//*[local-name()="t"]') ?: []));
                    }
                }
            }
            $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            $sheet = $sheet_xml !== false ? @simplexml_load_string($sheet_xml, 'SimpleXMLElement', LIBXML_NONET) : false;
            if ($sheet === false) {
                return new \WP_Error('invalid_file', __('The XLSX file has no readable first worksheet.', 'my-login-form'));
            }
            $rows = [];
            foreach ($sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $sheet_row) {
                $row = [];
                foreach ($sheet_row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                    $value = '';
                    if ((string) $cell['t'] === 's') {
                        $cell_value = $cell->xpath('./*[local-name()="v"]');
                        $index = (int) ($cell_value[0] ?? 0);
                        $value = $shared_strings[$index] ?? '';
                    } elseif ((string) $cell['t'] === 'inlineStr') {
                        $value = implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]') ?: []));
                    } else {
                        $cell_value = $cell->xpath('./*[local-name()="v"]');
                        $value = (string) ($cell_value[0] ?? '');
                    }
                    $column = preg_match('/^([A-Z]+)/', (string) $cell['r'], $match) ? $match[1] : '';
                    if ($column !== '') {
                        $column_number = 0;
                        foreach (str_split($column) as $letter) {
                            $column_number = ($column_number * 26) + ord($letter) - 64;
                        }
                        $row[$column_number - 1] = $value;
                    }
                }
                if ($row) {
                    ksort($row);
                    $rows[] = $row;
                }
            }
        } else {
            return new \WP_Error('invalid_file', __('Upload a CSV or XLSX file.', 'my-login-form'));
        }

        if (!$rows) {
            return new \WP_Error('empty_file', __('The mailing list file is empty.', 'my-login-form'));
        }

        $email_column = null;
        foreach ($rows[0] as $index => $value) {
            $header = strtolower(preg_replace('/[^a-z]/', '', (string) $value));
            if (in_array($header, ['email', 'emailaddress', 'mail', 'mailaddress'], true)) {
                $email_column = $index;
                break;
            }
        }

        $emails = [];
        foreach ($rows as $row_index => $row) {
            $values = $email_column !== null ? [($row[$email_column] ?? '')] : $row;
            if ($email_column !== null && $row_index === 0) {
                continue;
            }
            foreach ($values as $value) {
                $email = sanitize_email(trim((string) $value));
                if (is_email($email)) {
                    $emails[] = $email;
                }
            }
        }

        $emails = array_values(array_unique($emails));
        return $emails ?: new \WP_Error('empty_file', __('The mailing list file contains no valid email addresses.', 'my-login-form'));
    }

    /** Redirect back to Mailing with a small, non-sensitive result message. */
    private function redirect_mailing(array $args): void {
        $url = add_query_arg(array_merge(['page' => 'my-login-form-mailing'], $args), admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
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
