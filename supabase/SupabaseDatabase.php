<?php
/**
 * My Login Form - Supabase Database Class
 *
 * @package MyLoginForm\Database
 */

namespace MyLoginForm\Database;

// Prevent Direct Access
defined('ABSPATH') || exit;

class SupabaseDatabase {

    /**
     * Supabase API URL
     *
     * @var string
     */
    private $supabase_url;

    /**
     * Supabase Project ID
     *
     * @var string
     */
    private $supabase_project_id;

    /**
     * Supabase Anon Key
     *
     * @var string
     */
    private $supabase_anon_key;

    /**
     * Supabase Service Role Key
     *
     * @var string
     */
    private $supabase_service_role_key;

    /**
     * Supabase is enabled
     *
     * @var bool
     */
    private $supabase_enabled;

    /**
     * Supabase users table name
     *
     * @var string
     */
    private $supabase_users_table;

    /**
     * Supabase sessions table name
     *
     * @var string
     */
    private $supabase_sessions_table;

    /**
     * Supabase logs table name
     *
     * @var string
     */
    private $supabase_logs_table;

    /**
     * Sync direction
     *
     * @var string
     */
    private $sync_direction;

    /**
     * Instance of this class
     *
     * @var SupabaseDatabase
     */
    private static $instance = null;

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Settings table name
     *
     * @var string
     */
    private $settings_table;

    /**
     * Constructor - loads settings immediately
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->settings_table = $wpdb->prefix . 'my_login_supabase_settings';
        
        // Load settings immediately on construct
        $this->load_settings();

        if ($this->supabase_enabled && defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('My Login Form Supabase: Initialized with URL: ' . $this->supabase_url);
        }
    }

    /**
     * Get singleton instance
     *
     * @return SupabaseDatabase
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the Supabase module
     *
     * @return void
     */
    public function init() {
        if ($this->supabase_enabled) {
            add_action('my_login_form_user_created', array($this, 'sync_user_to_supabase'), 10, 2);
            add_action('my_login_form_user_updated', array($this, 'sync_user_to_supabase'), 10, 2);
            add_action('my_login_form_user_deleted', array($this, 'delete_user_from_supabase'), 10, 1);
        }
    }

    /**
     * Create Supabase settings table in WordPress
     *
     * @return bool
     */
    public function create_settings_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->settings_table} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            project_url VARCHAR(500) NOT NULL DEFAULT '',
            project_id VARCHAR(255) NOT NULL DEFAULT '',
            anon_key TEXT NOT NULL DEFAULT '',
            service_role_key TEXT NOT NULL DEFAULT '',
            users_table VARCHAR(100) DEFAULT 'users',
            sessions_table VARCHAR(100) DEFAULT 'user_sessions',
            logs_table VARCHAR(100) DEFAULT 'user_logs',
            sync_direction VARCHAR(20) DEFAULT 'both',
            is_enabled TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert default row if not exists
        $exists = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->settings_table}");
        if ($exists == 0) {
            $this->wpdb->insert($this->settings_table, array(
                'project_url' => '',
                'project_id' => '',
                'anon_key' => '',
                'service_role_key' => '',
                'users_table' => 'users',
                'sessions_table' => 'user_sessions',
                'logs_table' => 'user_logs',
                'sync_direction' => 'both',
                'is_enabled' => 0
            ));
        }
        
        return true;
    }

    /**
     * Load Supabase settings from WordPress table
     *
     * @return void
     */
    private function load_settings() {
        // Check if table exists
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->settings_table}'");
        
        if (!$table_exists) {
            $this->create_settings_table();
        }
        
        $settings = $this->wpdb->get_row("SELECT * FROM {$this->settings_table} WHERE id = 1", ARRAY_A);
        
        if ($settings) {
            $this->supabase_url = $settings['project_url'];
            $this->supabase_project_id = $settings['project_id'];
            $this->supabase_anon_key = $settings['anon_key'];
            $this->supabase_service_role_key = $settings['service_role_key'];
            $this->supabase_users_table = $settings['users_table'];
            $this->supabase_sessions_table = $settings['sessions_table'];
            $this->supabase_logs_table = $settings['logs_table'];
            $this->sync_direction = $settings['sync_direction'];
            $this->supabase_enabled = (bool) $settings['is_enabled'];
        } else {
            $this->supabase_url = '';
            $this->supabase_project_id = '';
            $this->supabase_anon_key = '';
            $this->supabase_service_role_key = '';
            $this->supabase_users_table = 'users';
            $this->supabase_sessions_table = 'user_sessions';
            $this->supabase_logs_table = 'user_logs';
            $this->sync_direction = 'both';
            $this->supabase_enabled = false;
        }
    }

    /**
     * Update Supabase settings
     *
     * @param array $settings Supabase settings
     * @return array Result with status and message
     */
    public function update_settings($settings) {
        $data = array(
            'project_url' => !empty($settings['project_url']) ? esc_url_raw(rtrim($settings['project_url'], '/')) : '',
            'project_id' => !empty($settings['project_id']) ? sanitize_text_field($settings['project_id']) : '',
            'anon_key' => !empty($settings['anon_key']) ? sanitize_text_field($settings['anon_key']) : '',
            'service_role_key' => !empty($settings['service_role_key']) ? sanitize_text_field($settings['service_role_key']) : '',
            'users_table' => !empty($settings['users_table']) ? sanitize_text_field($settings['users_table']) : 'users',
            'sessions_table' => !empty($settings['sessions_table']) ? sanitize_text_field($settings['sessions_table']) : 'user_sessions',
            'logs_table' => !empty($settings['logs_table']) ? sanitize_text_field($settings['logs_table']) : 'user_logs',
            'sync_direction' => !empty($settings['sync_direction']) ? sanitize_text_field($settings['sync_direction']) : 'both',
            'is_enabled' => !empty($settings['is_enabled']) ? 1 : 0
        );
        
        $result = $this->wpdb->update(
            $this->settings_table,
            $data,
            array('id' => 1)
        );
        
        // Reload settings
        $this->load_settings();
        
        // Test connection if enabled
        if ($this->supabase_enabled) {
            $test_result = $this->test_connection();
            if (!$test_result['success']) {
                return array(
                    'success' => false,
                    'message' => __('Supabase settings saved but connection failed: ', 'my-login-form') . $test_result['message']
                );
            }
        }
        
        return array(
            'success' => true,
            'message' => __('Supabase settings saved successfully!', 'my-login-form')
        );
    }

    /**
     * Get Supabase settings
     *
     * @return array
     */
    public function get_settings() {
        return array(
            'project_url' => $this->supabase_url,
            'project_id' => $this->supabase_project_id,
            'anon_key' => $this->mask_api_key($this->supabase_anon_key),
            'service_role_key' => $this->mask_api_key($this->supabase_service_role_key),
            'users_table' => $this->supabase_users_table,
            'sessions_table' => $this->supabase_sessions_table,
            'logs_table' => $this->supabase_logs_table,
            'sync_direction' => $this->sync_direction,
            'is_enabled' => $this->supabase_enabled
        );
    }

    /**
     * Get raw settings (unmasked) for internal use
     *
     * @return array
     */
    public function get_raw_settings() {
        return array(
            'project_url' => $this->supabase_url,
            'project_id' => $this->supabase_project_id,
            'anon_key' => $this->supabase_anon_key,
            'service_role_key' => $this->supabase_service_role_key,
            'users_table' => $this->supabase_users_table,
            'sessions_table' => $this->supabase_sessions_table,
            'logs_table' => $this->supabase_logs_table,
            'sync_direction' => $this->sync_direction,
            'is_enabled' => $this->supabase_enabled
        );
    }

    /**
     * Mask API key for display
     *
     * @param string $key API key
     * @return string
     */
    private function mask_api_key($key) {
        if (empty($key)) {
            return '';
        }
        $length = strlen($key);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        return substr($key, 0, 4) . str_repeat('*', $length - 8) . substr($key, -4);
    }

    /**
     * Test Supabase connection
     *
     * @return array Result with success and message
     */
    public function test_connection() {
        if (empty($this->supabase_url) || empty($this->supabase_anon_key)) {
            return array(
                'success' => false,
                'message' => __('Supabase URL and Anon Key are required', 'my-login-form')
            );
        }
        
        // The anon role has zero table grants on my_login_users (see
        // supabase/sql/01_users.sql) — a plain anon-keyed request would fail
        // even with a perfectly valid anon key. Test with the service role
        // key when one's been entered (the same key everything else here
        // actually reads/writes with); only fall back to the anon key if no
        // service key has been saved yet, so a fresh setup still gets some
        // signal before the service key is filled in.
        $response = $this->make_request(
            'GET',
            '/rest/v1/' . $this->supabase_users_table . '?limit=1',
            array(),
            !empty($this->supabase_service_role_key)
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => __('Supabase connection successful!', 'my-login-form')
            );
        } elseif ($status_code === 401) {
            return array(
                'success' => false,
                'message' => __('Invalid API key. Please check your Anon Key.', 'my-login-form')
            );
        } elseif ($status_code === 404) {
            return array(
                'success' => false,
                'message' => sprintf(__('Table "%s" not found. Please check your table name.', 'my-login-form'), $this->supabase_users_table)
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('Connection failed with status code: %d', 'my-login-form'), $status_code)
            );
        }
    }

    /**
     * Make request to Supabase API
     *
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param bool $use_service_role Use service role key instead of anon key
     * @return array|WP_Error Response
     */
    private function make_request($method, $endpoint, $data = array(), $use_service_role = false) {
        if (!$this->supabase_enabled) {
            return new \WP_Error('supabase_disabled', __('Supabase integration is disabled', 'my-login-form'));
        }
        
        if (empty($this->supabase_url)) {
            return new \WP_Error('missing_url', __('Supabase URL is not configured', 'my-login-form'));
        }
        
        $api_key = $use_service_role ? $this->supabase_service_role_key : $this->supabase_anon_key;
        
        if (empty($api_key)) {
            return new \WP_Error('missing_api_key', __('Supabase API key is missing', 'my-login-form'));
        }
        
        $url = $this->supabase_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'apikey' => $api_key,
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ),
            'timeout' => 30,
            'sslverify' => true
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        return $response;
    }

    /**
     * Handle API response
     *
     * @param array|WP_Error $response Response from wp_remote_request
     * @return array Formatted response
     */
    private function handle_response($response) {
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message(),
                'data' => null
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code >= 200 && $status_code < 300) {
            return array(
                'success' => true,
                'error' => null,
                'data' => $data,
                'status_code' => $status_code
            );
        }
        
        $error_message = isset($data['message']) ? $data['message'] : 'Unknown error occurred';
        
        return array(
            'success' => false,
            'error' => $error_message,
            'data' => $data,
            'status_code' => $status_code
        );
    }

    /**
     * Create table in Supabase (via SQL API)
     *
     * @param string $table_name Table name
     * @param array $columns Column definitions
     * @return array Result
     */
    public function create_table($table_name, $columns) {
        if (empty($this->supabase_service_role_key)) {
            return array(
                'success' => false,
                'error' => __('Service role key is required for table creation', 'my-login-form')
            );
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (";
        $sql_parts = array();
        
        foreach ($columns as $column) {
            $sql_parts[] = "{$column['name']} {$column['type']}" . (isset($column['constraints']) ? ' ' . $column['constraints'] : '');
        }
        
        $sql .= implode(', ', $sql_parts);
        $sql .= ");";
        
        $response = $this->make_request('POST', '/rest/v1/rpc/exec_sql', array('query' => $sql), true);
        
        return $this->handle_response($response);
    }

    /**
     * Get user from Supabase
     *
     * @param string $email User email
     * @return array User data or error
     */
    public function get_user($email) {
        $response = $this->make_request(
            'GET',
            '/rest/v1/' . $this->supabase_users_table,
            array('email' => 'eq.' . $email),
            true
        );
        
        $result = $this->handle_response($response);
        
        if ($result['success'] && !empty($result['data'])) {
            return array(
                'success' => true,
                'user' => $result['data'][0]
            );
        }
        
        return array(
            'success' => false,
            'error' => $result['error'] ?? 'User not found'
        );
    }

    /**
     * Get user by ID from Supabase
     *
     * @param string $user_id Supabase user ID
     * @return array User data or error
     */
    public function get_user_by_id($user_id) {
        $response = $this->make_request(
            'GET',
            '/rest/v1/' . $this->supabase_users_table,
            array('id' => 'eq.' . $user_id),
            true
        );
        
        $result = $this->handle_response($response);
        
        if ($result['success'] && !empty($result['data'])) {
            return array(
                'success' => true,
                'user' => $result['data'][0]
            );
        }
        
        return array(
            'success' => false,
            'error' => $result['error'] ?? 'User not found'
        );
    }

    /**
     * Create user in Supabase
     *
     * @param array $user_data User data
     * @return array Created user or error
     */
    public function create_user($user_data) {
        if (empty($user_data['email'])) {
            return array(
                'success' => false,
                'error' => __('Email is required', 'my-login-form')
            );
        }
        
        $supabase_user = array(
            'email' => $user_data['email'],
            'first_name' => $user_data['first_name'] ?? '',
            'last_name' => $user_data['last_name'] ?? '',
            'phone' => $user_data['phone'] ?? '',
            'country' => $user_data['country'] ?? '',
            'city' => $user_data['city'] ?? '',
            'email_verified' => $user_data['email_verified'] ?? false,
            'phone_verified' => $user_data['phone_verified'] ?? false,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        if (!empty($user_data['password'])) {
            $supabase_user['password'] = $user_data['password'];
        }
        
        $response = $this->make_request(
            'POST',
            '/rest/v1/' . $this->supabase_users_table,
            $supabase_user,
            true
        );
        
        $result = $this->handle_response($response);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'user' => $result['data'],
                'message' => __('User created successfully in Supabase', 'my-login-form')
            );
        }
        
        return array(
            'success' => false,
            'error' => $result['error'] ?? 'Failed to create user'
        );
    }

    /**
     * Update user in Supabase
     *
     * @param string $user_id Supabase user ID
     * @param array $user_data User data to update
     * @return array Result
     */
    public function update_user($user_id, $user_data) {
        $response = $this->make_request(
            'PATCH',
            '/rest/v1/' . $this->supabase_users_table . '?id=eq.' . $user_id,
            $user_data,
            true
        );
        
        $result = $this->handle_response($response);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'user' => $result['data'],
                'message' => __('User updated successfully in Supabase', 'my-login-form')
            );
        }
        
        return array(
            'success' => false,
            'error' => $result['error'] ?? 'Failed to update user'
        );
    }

    /**
     * Delete user from Supabase
     *
     * @param string $user_id Supabase user ID
     * @return array Result
     */
    public function delete_user_from_supabase($user_id) {
        $response = $this->make_request(
            'DELETE',
            '/rest/v1/' . $this->supabase_users_table . '?id=eq.' . $user_id,
            array(),
            true
        );
        
        $result = $this->handle_response($response);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => __('User deleted successfully from Supabase', 'my-login-form')
            );
        }
        
        return array(
            'success' => false,
            'error' => $result['error'] ?? 'Failed to delete user'
        );
    }

    /**
     * List users from Supabase with pagination
     *
     * @param int $limit Number of users per page
     * @param int $offset Offset for pagination
     * @param array $filters Additional filters
     * @return array Users list
     */
    public function list_users($limit = 100, $offset = 0, $filters = array()) {
        $params = array(
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'created_at.desc'
        );
        
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $params[$key] = 'eq.' . $value;
            }
        }
        
        $response = $this->make_request(
            'GET',
            '/rest/v1/' . $this->supabase_users_table,
            $params,
            true
        );

        $result = $this->handle_response($response);

        if ($result['success']) {
            return array(
                'success' => true,
                'users' => $result['data'],
                'count' => count($result['data'])
            );
        }

        return array(
            'success' => false,
            'error' => $result['error'] ?? 'Failed to fetch users'
        );
    }

    /**
     * Get user count from Supabase
     *
     * @param array $filters Filters to apply
     * @return array Count result
     */
    public function get_user_count($filters = array()) {
        $params = array('select' => 'count');
        
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $params[$key] = 'eq.' . $value;
            }
        }
        
        $response = $this->make_request(
            'GET',
            '/rest/v1/' . $this->supabase_users_table,
            $params,
            true
        );

        $result = $this->handle_response($response);

        if ($result['success']) {
            $count = isset($result['data'][0]['count']) ? $result['data'][0]['count'] : count($result['data']);
            return array(
                'success' => true,
                'count' => $count
            );
        }
        
        return array(
            'success' => false,
            'error' => $result['error'] ?? 'Failed to get user count'
        );
    }

    /**
     * Create session in Supabase
     *
     * @param array $session_data Session data
     * @return array Result
     */
    public function create_session($session_data) {
        $supabase_session = array(
            'session_id' => $session_data['session_id'],
            'user_id' => $session_data['user_id'],
            'ip_address' => $session_data['ip_address'] ?? '',
            'user_agent' => $session_data['user_agent'] ?? '',
            'expires_at' => $session_data['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+1 hour')),
            'created_at' => current_time('mysql')
        );
        
        $response = $this->make_request(
            'POST',
            '/rest/v1/' . $this->supabase_sessions_table,
            $supabase_session,
            true
        );
        
        return $this->handle_response($response);
    }

    /**
     * Delete session from Supabase
     *
     * @param string $session_id Session ID
     * @return array Result
     */
    public function delete_session($session_id) {
        $response = $this->make_request(
            'DELETE',
            '/rest/v1/' . $this->supabase_sessions_table . '?session_id=eq.' . $session_id,
            array(),
            true
        );
        
        return $this->handle_response($response);
    }

    /**
     * Clean expired sessions from Supabase
     *
     * @return array Result
     */
    public function clean_expired_sessions() {
        $current_time = current_time('mysql');
        
        $response = $this->make_request(
            'DELETE',
            '/rest/v1/' . $this->supabase_sessions_table . '?expires_at=lt.' . urlencode($current_time),
            array(),
            true
        );
        
        return $this->handle_response($response);
    }

    /**
     * Log activity in Supabase
     *
     * @param array $log_data Log data
     * @return array Result
     */
    public function log_activity($log_data) {
        $supabase_log = array(
            'log_type' => $log_data['log_type'],
            'log_action' => $log_data['log_action'],
            'user_id' => $log_data['user_id'] ?? null,
            'ip_address' => $log_data['ip_address'] ?? '',
            'details' => isset($log_data['details']) ? json_encode($log_data['details']) : null,
            'created_at' => current_time('mysql')
        );
        
        $response = $this->make_request(
            'POST',
            '/rest/v1/' . $this->supabase_logs_table,
            $supabase_log,
            true
        );
        
        return $this->handle_response($response);
    }

    /**
     * Get logs from Supabase
     *
     * @param array $filters Filters to apply
     * @param int $limit Number of logs
     * @param int $offset Offset
     * @return array Logs
     */
    public function get_logs($filters = array(), $limit = 100, $offset = 0) {
        $params = array(
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'created_at.desc'
        );
        
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($key === 'date_from') {
                    $params['created_at'] = 'gte.' . $value;
                } elseif ($key === 'date_to') {
                    $params['created_at'] = 'lte.' . $value;
                } else {
                    $params[$key] = 'eq.' . $value;
                }
            }
        }
        
        $response = $this->make_request(
            'GET',
            '/rest/v1/' . $this->supabase_logs_table,
            $params,
            true
        );

        $result = $this->handle_response($response);

        if ($result['success']) {
            return array(
                'success' => true,
                'logs' => $result['data'],
                'count' => count($result['data'])
            );
        }
        
        return array(
            'success' => false,
            'error' => $result['error'] ?? 'Failed to fetch logs'
        );
    }

    /**
     * Execute raw SQL query on Supabase (requires service role)
     *
     * @param string $sql SQL query
     * @return array Result
     */
    public function execute_sql($sql) {
        if (empty($this->supabase_service_role_key)) {
            return array(
                'success' => false,
                'error' => __('Service role key is required for SQL execution', 'my-login-form')
            );
        }
        
        $response = $this->make_request(
            'POST',
            '/rest/v1/rpc/exec_sql',
            array('query' => $sql),
            true
        );
        
        return $this->handle_response($response);
    }

    /**
     * Sync user from local database to Supabase
     *
     * @param int $user_id Local user ID
     * @param array $user_data User data
     * @return array Result
     */
    public function sync_user_to_supabase($user_id, $user_data) {
        if (!$this->supabase_enabled) {
            return array(
                'success' => false,
                'error' => __('Supabase is not enabled', 'my-login-form')
            );
        }
        
        if (!in_array($this->sync_direction, array('local_to_supabase', 'both'))) {
            return array(
                'success' => false,
                'error' => __('Sync direction does not include local to Supabase', 'my-login-form')
            );
        }
        
        $supabase_user = $this->get_user($user_data['user_email']);
        
        $supabase_data = array(
            'email' => $user_data['user_email'],
            'first_name' => $user_data['user_first_name'] ?? '',
            'last_name' => $user_data['user_last_name'] ?? '',
            'phone' => $user_data['user_phone'] ?? '',
            'country' => $user_data['user_country'] ?? '',
            'city' => $user_data['user_city'] ?? '',
            'email_verified' => (bool) ($user_data['email_verified'] ?? false),
            'phone_verified' => (bool) ($user_data['phone_verified'] ?? false),
            'last_login' => $user_data['last_login'] ?? null,
            'login_count' => $user_data['login_count'] ?? 0,
            'updated_at' => current_time('mysql')
        );
        
        if ($supabase_user['success']) {
            $supabase_id = $supabase_user['user']['id'];
            $result = $this->update_user($supabase_id, $supabase_data);
            
            if ($result['success']) {
                $users_db = UsersDatabase::get_instance();
                $users_db->link_supabase_account($user_id, $supabase_id);
            }
            
            return $result;
        } else {
            $result = $this->create_user($supabase_data);
            
            if ($result['success'] && isset($result['user'][0]['id'])) {
                $users_db = UsersDatabase::get_instance();
                $users_db->link_supabase_account($user_id, $result['user'][0]['id']);
            }
            
            return $result;
        }
    }

    /**
     * Sync user from Supabase to local database
     *
     * @param string $email User email
     * @return array Result
     */
    public function sync_user_from_supabase($email) {
        if (!$this->supabase_enabled) {
            return array(
                'success' => false,
                'error' => __('Supabase is not enabled', 'my-login-form')
            );
        }
        
        if (!in_array($this->sync_direction, array('supabase_to_local', 'both'))) {
            return array(
                'success' => false,
                'error' => __('Sync direction does not include Supabase to local', 'my-login-form')
            );
        }
        
        $supabase_user = $this->get_user($email);
        
        if (!$supabase_user['success']) {
            return array(
                'success' => false,
                'error' => __('User not found in Supabase', 'my-login-form')
            );
        }
        
        $user_data = $supabase_user['user'];
        
        $local_user_data = array(
            'user_email' => $user_data['email'],
            'user_login' => sanitize_user($user_data['email']),
            'user_first_name' => $user_data['first_name'] ?? '',
            'user_last_name' => $user_data['last_name'] ?? '',
            'user_phone' => $user_data['phone'] ?? '',
            'user_country' => $user_data['country'] ?? '',
            'user_city' => $user_data['city'] ?? '',
            'email_verified' => $user_data['email_verified'] ? 1 : 0,
            'phone_verified' => $user_data['phone_verified'] ? 1 : 0,
            'last_login' => $user_data['last_login'] ?? null,
            'login_count' => $user_data['login_count'] ?? 0,
            'supabase_user_id' => $user_data['id']
        );
        
        $users_db = UsersDatabase::get_instance();
        $existing_user = $users_db->get_user_by_email($email);
        
        if ($existing_user) {
            $result = $users_db->update_user($existing_user->id, $local_user_data);
            
            return array(
                'success' => $result,
                'user_id' => $existing_user->id,
                'message' => __('User updated from Supabase', 'my-login-form')
            );
        } else {
            $random_password = wp_generate_password();
            $local_user_data['user_password'] = $random_password;
            $user_id = $users_db->insert_user($local_user_data);
            
            return array(
                'success' => (bool) $user_id,
                'user_id' => $user_id,
                'message' => __('User created from Supabase', 'my-login-form')
            );
        }
    }

    /**
     * Bulk sync all users to Supabase
     *
     * @param array $args Sync arguments
     * @return array Sync results
     */
    public function bulk_sync_to_supabase($args = array()) {
        $defaults = array(
            'limit' => 100,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $results = array(
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        $users_db = UsersDatabase::get_instance();
        $users = $users_db->get_users(array(
            'limit' => $args['limit'],
            'offset' => $args['offset']
        ));
        
        $results['total'] = count($users);
        
        foreach ($users as $user) {
            $user_array = (array) $user;
            $sync_result = $this->sync_user_to_supabase($user->id, $user_array);
            
            if ($sync_result['success']) {
                $results['synced']++;
            } else {
                $results['failed']++;
                $results['errors'][] = array(
                    'user_id' => $user->id,
                    'email' => $user->user_email,
                    'error' => $sync_result['error'] ?? 'Unknown error'
                );
            }
        }
        
        return $results;
    }

    /**
     * Bulk sync users from Supabase
     *
     * @param array $args Sync arguments
     * @return array Sync results
     */
    public function bulk_sync_from_supabase($args = array()) {
        $defaults = array(
            'limit' => 100,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $results = array(
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        $supabase_users = $this->list_users($args['limit'], $args['offset']);
        
        if (!$supabase_users['success']) {
            $results['errors'][] = array(
                'error' => $supabase_users['error']
            );
            return $results;
        }
        
        $results['total'] = count($supabase_users['users']);
        
        foreach ($supabase_users['users'] as $supabase_user) {
            $sync_result = $this->sync_user_from_supabase($supabase_user['email']);
            
            if ($sync_result['success']) {
                $results['synced']++;
            } else {
                $results['failed']++;
                $results['errors'][] = array(
                    'email' => $supabase_user['email'],
                    'error' => $sync_result['error'] ?? 'Unknown error'
                );
            }
        }
        
        return $results;
    }

    /**
     * Get sync status
     *
     * @return array Sync status
     */
    public function get_sync_status() {
        $users_db = UsersDatabase::get_instance();
        
        $total_local_users = $users_db->get_users_count();
        
        $supabase_linked_users = 0;
        if ($users_db->table_exists()) {
            $users_table_name = $users_db->get_users_table_name();
            $supabase_linked_users = (int) $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$users_table_name} WHERE supabase_user_id IS NOT NULL AND supabase_user_id != ''"
            );
        }
        
        $supabase_status = $this->test_connection();
        
        return array(
            'supabase_enabled' => $this->supabase_enabled,
            'supabase_connected' => $supabase_status['success'],
            'sync_direction' => $this->sync_direction,
            'total_local_users' => $total_local_users,
            'supabase_linked_users' => $supabase_linked_users,
            'unsynced_users' => $total_local_users - $supabase_linked_users
        );
    }

    /**
     * Create Supabase tables (users, sessions, logs)
     *
     * @return array Result for each table
     */
    public function create_supabase_tables() {
        if (empty($this->supabase_service_role_key)) {
            return array(
                'success' => false,
                'error' => __('Service role key is required for creating tables', 'my-login-form')
            );
        }
        
        $results = array();
        
        // Users table
        $users_columns = array(
            array('name' => 'id', 'type' => 'UUID', 'constraints' => 'DEFAULT gen_random_uuid() PRIMARY KEY'),
            array('name' => 'email', 'type' => 'VARCHAR(255)', 'constraints' => 'UNIQUE NOT NULL'),
            array('name' => 'first_name', 'type' => 'VARCHAR(100)'),
            array('name' => 'last_name', 'type' => 'VARCHAR(100)'),
            array('name' => 'phone', 'type' => 'VARCHAR(20)'),
            array('name' => 'country', 'type' => 'VARCHAR(100)'),
            array('name' => 'city', 'type' => 'VARCHAR(100)'),
            array('name' => 'email_verified', 'type' => 'BOOLEAN', 'constraints' => 'DEFAULT FALSE'),
            array('name' => 'phone_verified', 'type' => 'BOOLEAN', 'constraints' => 'DEFAULT FALSE'),
            array('name' => 'last_login', 'type' => 'TIMESTAMP'),
            array('name' => 'login_count', 'type' => 'INTEGER', 'constraints' => 'DEFAULT 0'),
            array('name' => 'created_at', 'type' => 'TIMESTAMP', 'constraints' => 'DEFAULT NOW()'),
            array('name' => 'updated_at', 'type' => 'TIMESTAMP', 'constraints' => 'DEFAULT NOW()')
        );
        
        $results['users'] = $this->create_table($this->supabase_users_table, $users_columns);
        
        // Sessions table
        $sessions_columns = array(
            array('name' => 'id', 'type' => 'UUID', 'constraints' => 'DEFAULT gen_random_uuid() PRIMARY KEY'),
            array('name' => 'session_id', 'type' => 'VARCHAR(255)', 'constraints' => 'UNIQUE NOT NULL'),
            array('name' => 'user_id', 'type' => 'UUID', 'constraints' => 'REFERENCES ' . $this->supabase_users_table . '(id) ON DELETE CASCADE'),
            array('name' => 'ip_address', 'type' => 'VARCHAR(45)'),
            array('name' => 'user_agent', 'type' => 'TEXT'),
            array('name' => 'expires_at', 'type' => 'TIMESTAMP'),
            array('name' => 'created_at', 'type' => 'TIMESTAMP', 'constraints' => 'DEFAULT NOW()')
        );
        
        $results['sessions'] = $this->create_table($this->supabase_sessions_table, $sessions_columns);
        
        // Logs table
        $logs_columns = array(
            array('name' => 'id', 'type' => 'UUID', 'constraints' => 'DEFAULT gen_random_uuid() PRIMARY KEY'),
            array('name' => 'log_type', 'type' => 'VARCHAR(50)', 'constraints' => 'NOT NULL'),
            array('name' => 'log_action', 'type' => 'VARCHAR(100)', 'constraints' => 'NOT NULL'),
            array('name' => 'user_id', 'type' => 'UUID', 'constraints' => 'REFERENCES ' . $this->supabase_users_table . '(id) ON DELETE SET NULL'),
            array('name' => 'ip_address', 'type' => 'VARCHAR(45)'),
            array('name' => 'details', 'type' => 'JSONB'),
            array('name' => 'created_at', 'type' => 'TIMESTAMP', 'constraints' => 'DEFAULT NOW()')
        );
        
        $results['logs'] = $this->create_table($this->supabase_logs_table, $logs_columns);
        
        return $results;
    }

    /**
     * Check if Supabase is configured and enabled
     *
     * @return bool
     */
    public function is_configured() {
        return $this->supabase_enabled && !empty($this->supabase_url) && !empty($this->supabase_anon_key);
    }

    /**
     * Check if Supabase is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        return $this->supabase_enabled;
    }

    /**
     * Get Supabase URL
     *
     * @return string
     */
    public function get_supabase_url() {
        return $this->supabase_url;
    }

    /**
     * Get Supabase Project ID
     *
     * @return string
     */
    public function get_project_id() {
        return $this->supabase_project_id;
    }

    /**
     * Get Supabase users table name
     *
     * @return string
     */
    public function get_users_table() {
        return $this->supabase_users_table;
    }

    /**
     * Get sync direction
     *
     * @return string
     */
    public function get_sync_direction() {
        return $this->sync_direction;
    }
}