<?php
/**
 * My Login Form - Users Database Class with Supabase
 *
 * @package MyLoginForm\Database
 */

namespace MyLoginForm\Database;

// Prevent Direct Access
defined('ABSPATH') || exit;

class UsersDatabase {
    
    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * Users table name
     *
     * @var string
     */
    private $users_table;

    /**
     * Sessions table name
     *
     * @var string
     */
    private $users_sessions_table;

    /**
     * Logs table name
     *
     * @var string
     */
    private $users_logs_table;

    /**
 * Get users table name
 *
 * @return string
 */
public function get_users_table_name() {
    return $this->users_table;
}



    /**
     * Instance of this class
     *
     * @var UsersDatabase
     */
    private static $instance = null;

    /**
     * Constructor - creates table immediately
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        $this->users_table = $wpdb->prefix . 'my_login_users_data';
        $this->users_sessions_table = $wpdb->prefix . 'my_login_users_sessions';
        $this->users_logs_table = $wpdb->prefix . 'my_login_users_logs';
        
        // CREATE TABLE IMMEDIATELY on construct
        $this->create_users_table();
    }

    /**
     * Get singleton instance
     *
     * @return UsersDatabase
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the database module
     *
     * @return void
     */
    public function init() {
        // Check and update table structure if needed
        $this->maybe_update_users_table();
        
        // Register cleanup cron job
        add_action('my_login_form_daily_maintenance', array($this, 'cleanup_old_data'));
    }

    /**
     * Create users table
     *
     * @return bool
     */
    public function create_users_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->users_table} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_login VARCHAR(100) NOT NULL,
            user_email VARCHAR(100) NOT NULL,
            user_password VARCHAR(255) NOT NULL,
            user_nicename VARCHAR(100),
            user_display_name VARCHAR(250),
            user_first_name VARCHAR(100),
            user_last_name VARCHAR(100),
            user_url VARCHAR(500),
            user_phone VARCHAR(20),
            user_gender VARCHAR(10),
            user_dob DATE,
            user_country VARCHAR(50),
            user_city VARCHAR(50),
            user_address TEXT,
            user_registered DATETIME,
            user_status INT(11) DEFAULT 0,
            user_activation_key VARCHAR(255),
            wp_user_id INT(11) DEFAULT NULL,
            supabase_user_id VARCHAR(100),
            social_provider VARCHAR(50),
            social_id VARCHAR(100),
            profile_picture VARCHAR(500),
            email_verified TINYINT(1) DEFAULT 0,
            phone_verified TINYINT(1) DEFAULT 0,
            two_factor_enabled TINYINT(1) DEFAULT 0,
            two_factor_secret VARCHAR(100),
            last_login DATETIME,
            login_count INT(11) DEFAULT 0,
            failed_login_attempts INT(11) DEFAULT 0,
            account_locked_until DATETIME,
            meta_data LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_email (user_email),
            UNIQUE KEY user_login (user_login),
            UNIQUE KEY supabase_user_id (supabase_user_id),
            UNIQUE KEY social_uid (social_provider, social_id),
            KEY wp_user_id (wp_user_id),
            KEY last_login (last_login),
            KEY status_idx (email_verified, account_locked_until),
            KEY user_status (user_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        if ($this->wpdb->last_error) {
            error_log('My Login Form UsersDatabase: Failed to create users table - ' . $this->wpdb->last_error);
            return false;
        }
        
        // Verify table was created
        if ($this->table_exists()) {
            return true;
        }

        error_log('My Login Form UsersDatabase: Users table creation verification failed');
        return false;
    }

    /**
     * Update table structure if needed
     *
     * @return void
     */
    private function maybe_update_users_table() {
        // Check if table exists first
        if (!$this->table_exists()) {
            return;
        }
        
        $columns = $this->wpdb->get_col("DESC {$this->users_table}");
        
        $missing_columns = array(
            'user_login' => "ADD user_login VARCHAR(100) NOT NULL AFTER id",
            'user_nicename' => "ADD user_nicename VARCHAR(100)",
            'user_display_name' => "ADD user_display_name VARCHAR(250)",
            'user_url' => "ADD user_url VARCHAR(500)",
            'user_registered' => "ADD user_registered DATETIME",
            'user_status' => "ADD user_status INT(11) DEFAULT 0",
            'user_activation_key' => "ADD user_activation_key VARCHAR(255)",
            'two_factor_enabled' => "ADD two_factor_enabled TINYINT(1) DEFAULT 0",
            'two_factor_secret' => "ADD two_factor_secret VARCHAR(100)",
            'supabase_user_id' => "ADD supabase_user_id VARCHAR(100)",
            'social_provider' => "ADD social_provider VARCHAR(50)",
            'social_id' => "ADD social_id VARCHAR(100)",
            'profile_picture' => "ADD profile_picture VARCHAR(500)",
            'account_locked_until' => "ADD account_locked_until DATETIME"
        );
        
        foreach ($missing_columns as $column => $sql) {
            if (!in_array($column, $columns)) {
                $this->wpdb->query("ALTER TABLE {$this->users_table} {$sql}");
                
                if ($column === 'supabase_user_id') {
                    $this->wpdb->query("ALTER TABLE {$this->users_table} ADD UNIQUE KEY supabase_user_id (supabase_user_id)");
                }
                if ($column === 'user_login') {
                    $this->wpdb->query("ALTER TABLE {$this->users_table} ADD UNIQUE KEY user_login (user_login)");
                }
                if ($column === 'social_provider') {
                    $this->wpdb->query("ALTER TABLE {$this->users_table} ADD UNIQUE KEY social_uid (social_provider, social_id)");
                }
            }
        }
    }

    /**
     * Insert a new user
     *
     * @param array $user_data User data
     * @return int|false User ID or false on error
     */
    public function insert_user($user_data) {
        if (!$this->table_exists()) {
            $this->create_users_table();
        }
        
        if (empty($user_data['user_email']) || empty($user_data['user_login']) || empty($user_data['user_password'])) {
            return false;
        }
        
        // Check if email already exists
        if ($this->email_exists($user_data['user_email'])) {
            return false;
        }
        
        // Check if login already exists
        if ($this->username_exists($user_data['user_login'])) {
            return false;
        }
        
        $defaults = array(
            'user_nicename' => sanitize_title($user_data['user_login']),
            'user_display_name' => $user_data['user_login'],
            'user_registered' => current_time('mysql'),
            'user_status' => 0,
            'email_verified' => 0,
            'phone_verified' => 0,
            'two_factor_enabled' => 0,
            'login_count' => 0,
            'failed_login_attempts' => 0
        );
        
        $user_data = array_merge($defaults, $user_data);
        $user_data['user_password'] = wp_hash_password($user_data['user_password']);
        
        $sanitized_data = $this->sanitize_user_data($user_data);
        
        $result = $this->wpdb->insert($this->users_table, $sanitized_data);
        
        if ($result) {
            $user_id = $this->wpdb->insert_id;
            $this->log_user_activity($user_id, 'user_created', array(
                'email' => $user_data['user_email'],
                'login' => $user_data['user_login']
            ));
            return $user_id;
        }
        
        return false;
    }

    /**
     * Update an existing user
     *
     * @param int $user_id User ID
     * @param array $user_data User data to update
     * @return bool
     */
    public function update_user($user_id, $user_data) {
        if (!$this->table_exists()) {
            return false;
        }
        
        if (!$this->get_user($user_id)) {
            return false;
        }
        
        if (isset($user_data['user_password']) && !empty($user_data['user_password'])) {
            $user_data['user_password'] = wp_hash_password($user_data['user_password']);
        }
        
        $sanitized_data = $this->sanitize_user_data($user_data);
        
        $result = $this->wpdb->update(
            $this->users_table,
            $sanitized_data,
            array('id' => $user_id)
        );
        
        if ($result !== false) {
            $this->log_user_activity($user_id, 'user_updated', array_keys($user_data));
            return true;
        }
        
        return false;
    }

    /**
     * Get user by ID
     *
     * @param int $user_id User ID
     * @return object|null
     */
    public function get_user($user_id) {
        if (!$this->table_exists()) {
            return null;
        }
        
        $user = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->users_table} WHERE id = %d", $user_id)
        );
        
        if ($user && isset($user->meta_data)) {
            $user->meta_data = maybe_unserialize($user->meta_data);
        }
        
        return $user;
    }

    /**
     * Get user by email
     *
     * @param string $email User email
     * @return object|null
     */
    public function get_user_by_email($email) {
        if (!$this->table_exists()) {
            return null;
        }
        
        $user = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->users_table} WHERE user_email = %s", $email)
        );
        
        if ($user && isset($user->meta_data)) {
            $user->meta_data = maybe_unserialize($user->meta_data);
        }
        
        return $user;
    }

    /**
     * Get user by username
     *
     * @param string $username Username
     * @return object|null
     */
    public function get_user_by_login($username) {
        if (!$this->table_exists()) {
            return null;
        }
        
        $user = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->users_table} WHERE user_login = %s", $username)
        );
        
        if ($user && isset($user->meta_data)) {
            $user->meta_data = maybe_unserialize($user->meta_data);
        }
        
        return $user;
    }

    /**
     * Get user by Supabase user ID
     *
     * @param string $supabase_user_id Supabase user ID
     * @return object|null
     */
    public function get_user_by_supabase_id($supabase_user_id) {
        if (!$this->table_exists()) {
            return null;
        }
        
        $user = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->users_table} WHERE supabase_user_id = %s", $supabase_user_id)
        );
        
        if ($user && isset($user->meta_data)) {
            $user->meta_data = maybe_unserialize($user->meta_data);
        }
        
        return $user;
    }

    /**
     * Get user by WordPress user ID
     *
     * @param int $wp_user_id WordPress user ID
     * @return object|null
     */
    public function get_user_by_wp_id($wp_user_id) {
        if (!$this->table_exists()) {
            return null;
        }
        
        $user = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->users_table} WHERE wp_user_id = %d", $wp_user_id)
        );
        
        if ($user && isset($user->meta_data)) {
            $user->meta_data = maybe_unserialize($user->meta_data);
        }
        
        return $user;
    }

    /**
     * Get user by social login ID
     *
     * @param string $provider Social provider (google, facebook, etc.)
     * @param string $social_id Social ID from provider
     * @return object|null
     */
    public function get_user_by_social_id($provider, $social_id) {
        if (!$this->table_exists()) {
            return null;
        }
        
        $user = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->users_table} WHERE social_provider = %s AND social_id = %s",
                $provider,
                $social_id
            )
        );
        
        if ($user && isset($user->meta_data)) {
            $user->meta_data = maybe_unserialize($user->meta_data);
        }
        
        return $user;
    }

    /**
     * Check if email exists
     *
     * @param string $email Email address
     * @return bool
     */
    public function email_exists($email) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->users_table} WHERE user_email = %s", $email)
        );
        return $count > 0;
    }

    /**
     * Check if username exists
     *
     * @param string $username Username
     * @return bool
     */
    public function username_exists($username) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->users_table} WHERE user_login = %s", $username)
        );
        return $count > 0;
    }

    /**
     * Authenticate user
     *
     * @param string $login Username or email
     * @param string $password Plain text password
     * @return object|false User object or false
     */
    public function authenticate($login, $password) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $user = null;
        
        // Check if login is email
        if (strpos($login, '@') !== false) {
            $user = $this->get_user_by_email($login);
        } else {
            $user = $this->get_user_by_login($login);
        }
        
        if (!$user) {
            return false;
        }
        
        // Check if account is locked
        $lock_check = $this->is_account_locked($user->id);
        if ($lock_check) {
            return false;
        }
        
        // Verify password
        if (!wp_check_password($password, $user->user_password)) {
            $this->record_failed_login($user->id);
            return false;
        }
        
        // Reset failed attempts on successful login
        $this->reset_failed_attempts($user->id);
        
        // Update last login
        $this->update_login_info($user->id);
        
        return $user;
    }

    /**
     * Update user login information
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function update_login_info($user_id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $data = array(
            'last_login' => current_time('mysql'),
            'login_count' => $this->wpdb->get_var(
                $this->wpdb->prepare("SELECT login_count FROM {$this->users_table} WHERE id = %d", $user_id)
            ) + 1
        );
        
        return $this->wpdb->update($this->users_table, $data, array('id' => $user_id));
    }

    /**
     * Record failed login attempt
     *
     * @param int $user_id User ID
     * @return int Number of failed attempts
     */
    public function record_failed_login($user_id) {
        if (!$this->table_exists()) {
            return 0;
        }
        
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return 0;
        }
        
        $failed_attempts = $user->failed_login_attempts + 1;
        $data = array('failed_login_attempts' => $failed_attempts);
        
        // Lock account after 5 failed attempts
        if ($failed_attempts >= 5) {
            $data['account_locked_until'] = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        }
        
        $this->wpdb->update($this->users_table, $data, array('id' => $user_id));
        
        return $failed_attempts;
    }

    /**
     * Reset failed login attempts
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function reset_failed_attempts($user_id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->users_table,
            array(
                'failed_login_attempts' => 0,
                'account_locked_until' => null
            ),
            array('id' => $user_id)
        );
    }

    /**
     * Check if account is locked
     *
     * @param int $user_id User ID
     * @return bool|string False if not locked, lock until date if locked
     */
    public function is_account_locked($user_id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $user = $this->get_user($user_id);
        
        if (!$user || !$user->account_locked_until) {
            return false;
        }
        
        $lock_until = strtotime($user->account_locked_until);
        $now = current_time('timestamp');
        
        if ($now < $lock_until) {
            return $user->account_locked_until;
        }
        
        // Lock expired, reset attempts
        $this->reset_failed_attempts($user_id);
        return false;
    }

    /**
     * Set email verification status
     *
     * @param int $user_id User ID
     * @param bool $verified Verification status
     * @return bool
     */
    public function set_email_verified($user_id, $verified = true) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->users_table,
            array('email_verified' => $verified ? 1 : 0),
            array('id' => $user_id)
        );
    }

    /**
     * Set phone verification status
     *
     * @param int $user_id User ID
     * @param bool $verified Verification status
     * @return bool
     */
    public function set_phone_verified($user_id, $verified = true) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->users_table,
            array('phone_verified' => $verified ? 1 : 0),
            array('id' => $user_id)
        );
    }

    /**
     * Enable or disable two-factor authentication
     *
     * @param int $user_id User ID
     * @param bool $enabled Enable status
     * @param string $secret 2FA secret
     * @return bool
     */
    public function set_two_factor($user_id, $enabled, $secret = '') {
        if (!$this->table_exists()) {
            return false;
        }
        
        $data = array('two_factor_enabled' => $enabled ? 1 : 0);
        
        if (!empty($secret)) {
            $data['two_factor_secret'] = $secret;
        }
        
        return $this->wpdb->update($this->users_table, $data, array('id' => $user_id));
    }

    /**
     * Update user password
     *
     * @param int $user_id User ID
     * @param string $new_password New password
     * @return bool
     */
    public function update_password($user_id, $new_password) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->users_table,
            array('user_password' => wp_hash_password($new_password)),
            array('id' => $user_id)
        );
    }

    /**
     * Reset user password using activation key
     *
     * @param string $activation_key Activation key
     * @param string $new_password New password
     * @return bool
     */
    public function reset_password($activation_key, $new_password) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $user = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->users_table} WHERE user_activation_key = %s",
                $activation_key
            )
        );
        
        if (!$user) {
            return false;
        }
        
        // Check if activation key is expired (24 hours)
        $key_created = strtotime($user->user_registered);
        if ((time() - $key_created) > 86400) {
            return false;
        }
        
        $result = $this->update_password($user->id, $new_password);
        
        if ($result) {
            // Clear activation key
            $this->wpdb->update(
                $this->users_table,
                array('user_activation_key' => ''),
                array('id' => $user->id)
            );
        }
        
        return $result;
    }

    /**
     * Generate password reset key
     *
     * @param int $user_id User ID
     * @return string|false Reset key or false
     */
    public function generate_reset_key($user_id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return false;
        }
        
        $reset_key = wp_generate_password(20, false);
        
        $result = $this->wpdb->update(
            $this->users_table,
            array('user_activation_key' => $reset_key),
            array('id' => $user_id)
        );
        
        return $result ? $reset_key : false;
    }

    /**
     * Link user to WordPress account
     *
     * @param int $user_id User ID
     * @param int $wp_user_id WordPress user ID
     * @return bool
     */
    public function link_wordpress_user($user_id, $wp_user_id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->users_table,
            array('wp_user_id' => $wp_user_id),
            array('id' => $user_id)
        );
    }

    /**
     * Link social account to user
     *
     * @param int $user_id User ID
     * @param string $provider Social provider
     * @param string $social_id Social ID
     * @return bool
     */
    public function link_social_account($user_id, $provider, $social_id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->users_table,
            array(
                'social_provider' => $provider,
                'social_id' => $social_id
            ),
            array('id' => $user_id)
        );
    }

    /**
     * Link Supabase account to user
     *
     * @param int $user_id User ID
     * @param string $supabase_user_id Supabase user ID
     * @return bool
     */
    public function link_supabase_account($user_id, $supabase_user_id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->users_table,
            array('supabase_user_id' => $supabase_user_id),
            array('id' => $user_id)
        );
    }

    /**
     * Update user profile picture
     *
     * @param int $user_id User ID
     * @param string $picture_url Profile picture URL
     * @return bool
     */
    public function update_profile_picture($user_id, $picture_url) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->update(
            $this->users_table,
            array('profile_picture' => esc_url_raw($picture_url)),
            array('id' => $user_id)
        );
    }

    /**
     * Update user meta data
     *
     * @param int $user_id User ID
     * @param array $meta_data Meta data to merge
     * @return bool
     */
    public function update_user_meta($user_id, $meta_data) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return false;
        }
        
        $current_meta = !empty($user->meta_data) ? (array) $user->meta_data : array();
        $new_meta = array_merge($current_meta, $meta_data);
        
        return $this->wpdb->update(
            $this->users_table,
            array('meta_data' => maybe_serialize($new_meta)),
            array('id' => $user_id)
        );
    }

    /**
     * Get user meta data
     *
     * @param int $user_id User ID
     * @param string $key Optional specific meta key
     * @return mixed
     */
    public function get_user_meta($user_id, $key = null) {
        if (!$this->table_exists()) {
            return $key ? null : array();
        }
        
        $user = $this->get_user($user_id);
        
        if (!$user || empty($user->meta_data)) {
            return $key ? null : array();
        }
        
        $meta = (array) $user->meta_data;
        
        if ($key) {
            return isset($meta[$key]) ? $meta[$key] : null;
        }
        
        return $meta;
    }

    /**
     * Delete user
     *
     * @param int $user_id User ID
     * @param bool $delete_wp_user Also delete WordPress user
     * @return bool
     */
    public function delete_user($user_id, $delete_wp_user = false) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return false;
        }
        
        // Delete WordPress user if requested
        if ($delete_wp_user && $user->wp_user_id) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user->wp_user_id);
        }
        
        $result = $this->wpdb->delete($this->users_table, array('id' => $user_id));
        
        if ($result) {
            $this->log_user_activity($user_id, 'user_deleted', array(
                'email' => $user->user_email,
                'login' => $user->user_login
            ));
        }
        
        return $result;
    }

    /**
     * Get all users with pagination
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_users($args = array()) {
        if (!$this->table_exists()) {
            return array();
        }
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search' => '',
            'email_verified' => null,
            'user_status' => null,
            'social_provider' => '',
            'date_from' => '',
            'date_to' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $query_params = array();
        
        if (!empty($args['search'])) {
            $where[] = '(user_email LIKE %s OR user_login LIKE %s OR user_first_name LIKE %s OR user_last_name LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $query_params[] = $search_term;
            $query_params[] = $search_term;
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }
        
        if ($args['email_verified'] !== null) {
            $where[] = 'email_verified = %d';
            $query_params[] = (int) $args['email_verified'];
        }
        
        if ($args['user_status'] !== null) {
            $where[] = 'user_status = %d';
            $query_params[] = (int) $args['user_status'];
        }
        
        if (!empty($args['social_provider'])) {
            $where[] = 'social_provider = %s';
            $query_params[] = $args['social_provider'];
        }
        
        if (!empty($args['date_from'])) {
            $where[] = 'user_registered >= %s';
            $query_params[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'user_registered <= %s';
            $query_params[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->users_table} WHERE {$where_clause}";
        
        $allowed_orderby = [
            'id', 'user_login', 'user_email', 'user_display_name',
            'user_first_name', 'user_last_name', 'user_registered',
            'user_status', 'last_login', 'login_count', 'created_at', 'updated_at',
        ];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$orderby} {$order}";
        
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d OFFSET %d";
            $query_params[] = (int) $args['limit'];
            $query_params[] = (int) $args['offset'];
        }
        
        if (!empty($query_params)) {
            $sql = $this->wpdb->prepare($sql, $query_params);
        }
        
        $users = $this->wpdb->get_results($sql);
        
        foreach ($users as $user) {
            if (isset($user->meta_data)) {
                $user->meta_data = maybe_unserialize($user->meta_data);
            }
        }
        
        return $users;
    }



/**
 * Get sessions table name
 *
 * @return string
 */
public function get_sessions_table_name() {
    return $this->sessions_table;
}

/**
 * Get logs table name
 *
 * @return string
 */
public function get_logs_table_name() {
    return $this->logs_table;
}

/**
 * Check if users table exists
 *
 * @return bool
 */
public function table_exists() {
    $table = $this->wpdb->get_var(
        $this->wpdb->prepare("SHOW TABLES LIKE %s", $this->users_table)
    );
    return $table === $this->users_table;
}

    /**
     * Get total users count
     *
     * @param array $args Filter arguments
     * @return int
     */
    public function get_users_count($args = array()) {
        if (!$this->table_exists()) {
            return 0;
        }
        
        $where = array('1=1');
        $query_params = array();
        
        if (!empty($args['email_verified'])) {
            $where[] = 'email_verified = %d';
            $query_params[] = (int) $args['email_verified'];
        }
        
        if (!empty($args['user_status'])) {
            $where[] = 'user_status = %d';
            $query_params[] = (int) $args['user_status'];
        }
        
        if (!empty($args['social_provider'])) {
            $where[] = 'social_provider = %s';
            $query_params[] = $args['social_provider'];
        }
        
        $where_clause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$this->users_table} WHERE {$where_clause}";
        
        if (!empty($query_params)) {
            $sql = $this->wpdb->prepare($sql, $query_params);
        }
        
        return (int) $this->wpdb->get_var($sql);
    }

    /**
     * Get user statistics
     *
     * @return array
     */
    public function get_user_statistics() {
        if (!$this->table_exists()) {
            return array(
                'total_users' => 0,
                'verified_emails' => 0,
                'verified_phones' => 0,
                'social_logins' => 0,
                'two_factor_enabled' => 0,
                'supabase_linked' => 0,
                'wp_linked' => 0,
                'active_users_last_30_days' => 0,
                'new_users_last_30_days' => 0
            );
        }
        
        $stats = array(
            'total_users' => 0,
            'verified_emails' => 0,
            'verified_phones' => 0,
            'social_logins' => 0,
            'two_factor_enabled' => 0,
            'supabase_linked' => 0,
            'wp_linked' => 0,
            'active_users_last_30_days' => 0,
            'new_users_last_30_days' => 0
        );
        
        $stats['total_users'] = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->users_table}");
        
        $stats['verified_emails'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->users_table} WHERE email_verified = 1"
        );
        
        $stats['verified_phones'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->users_table} WHERE phone_verified = 1"
        );
        
        $stats['social_logins'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->users_table} WHERE social_provider IS NOT NULL AND social_provider != ''"
        );
        
        $stats['two_factor_enabled'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->users_table} WHERE two_factor_enabled = 1"
        );
        
        $stats['supabase_linked'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->users_table} WHERE supabase_user_id IS NOT NULL AND supabase_user_id != ''"
        );
        
        $stats['wp_linked'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->users_table} WHERE wp_user_id IS NOT NULL AND wp_user_id > 0"
        );
        
        $stats['active_users_last_30_days'] = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->users_table} WHERE last_login >= %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
        
        $stats['new_users_last_30_days'] = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->users_table} WHERE created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
        
        return $stats;
    }

    /**
     * Sanitize user data
     *
     * @param array $data User data
     * @return array
     */
    private function sanitize_user_data($data) {
        $sanitized = array();
        
        // Basic user fields
        $text_fields = array(
            'user_login', 'user_email', 'user_nicename', 'user_display_name',
            'user_first_name', 'user_last_name', 'user_url', 'user_phone',
            'user_gender', 'user_country', 'user_city', 'user_activation_key',
            'supabase_user_id', 'social_provider', 'social_id', 'two_factor_secret'
        );
        
        foreach ($text_fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = sanitize_text_field($data[$field]);
            }
        }
        
        // Password
        if (isset($data['user_password'])) {
            $sanitized['user_password'] = $data['user_password'];
        }
        
        // URL fields
        if (isset($data['profile_picture'])) {
            $sanitized['profile_picture'] = esc_url_raw($data['profile_picture']);
        }
        
        // Textarea fields
        if (isset($data['user_address'])) {
            $sanitized['user_address'] = sanitize_textarea_field($data['user_address']);
        }
        
        // Date fields
        if (isset($data['user_dob'])) {
            $sanitized['user_dob'] = sanitize_text_field($data['user_dob']);
        }
        
        if (isset($data['user_registered'])) {
            $sanitized['user_registered'] = sanitize_text_field($data['user_registered']);
        }
        
        // Integer fields
        $int_fields = array(
            'wp_user_id', 'user_status', 'email_verified', 'phone_verified',
            'two_factor_enabled', 'login_count', 'failed_login_attempts'
        );
        
        foreach ($int_fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = (int) $data[$field];
            }
        }
        
        // DateTime field
        if (isset($data['account_locked_until'])) {
            $sanitized['account_locked_until'] = sanitize_text_field($data['account_locked_until']);
        }
        
        if (isset($data['last_login'])) {
            $sanitized['last_login'] = sanitize_text_field($data['last_login']);
        }
        
        // Serialized meta data
        if (isset($data['meta_data'])) {
            $sanitized['meta_data'] = maybe_serialize($data['meta_data']);
        }
        
        return $sanitized;
    }

    /**
     * Log user activity
     *
     * @param int $user_id User ID
     * @param string $action Action performed
     * @param array $details Additional details
     * @return bool
     */
    private function log_user_activity($user_id, $action, $details = array()) {
        $data = array(
            'log_type' => 'user',
            'log_action' => $action,
            'user_id' => $user_id,
            'wp_user_id' => get_current_user_id() ?: null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'details' => maybe_serialize($details),
            'created_at' => current_time('mysql')
        );
        
        return (bool) $this->wpdb->insert($this->logs_table, $data);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Cleanup old data (unverified users older than 7 days)
     *
     * @return void
     */
    public function cleanup_old_data() {
        if (!$this->table_exists()) {
            return;
        }
        
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->users_table} 
                WHERE email_verified = 0 
                AND user_registered < %s 
                AND wp_user_id IS NULL",
                date('Y-m-d H:i:s', strtotime('-7 days'))
            )
        );
        
        $this->log_user_activity(0, 'cleanup_old_users', array('deleted' => $this->wpdb->rows_affected));
    }


    /**
     * Search users by criteria
     *
     * @param string $search_term Search term
     * @param int $limit Maximum results
     * @return array
     */
    public function search_users($search_term, $limit = 20) {
        if (!$this->table_exists()) {
            return array();
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->users_table} 
            WHERE user_login LIKE %s 
            OR user_email LIKE %s 
            OR user_first_name LIKE %s 
            OR user_last_name LIKE %s 
            LIMIT %d",
            '%' . $this->wpdb->esc_like($search_term) . '%',
            '%' . $this->wpdb->esc_like($search_term) . '%',
            '%' . $this->wpdb->esc_like($search_term) . '%',
            '%' . $this->wpdb->esc_like($search_term) . '%',
            $limit
        );
        
        $users = $this->wpdb->get_results($sql);
        
        foreach ($users as $user) {
            if (isset($user->meta_data)) {
                $user->meta_data = maybe_unserialize($user->meta_data);
            }
        }
        
        return $users;
    }

    /**
     * Bulk insert users
     *
     * @param array $users Array of user data
     * @return array Insert results
     */
    public function bulk_insert_users($users) {
        if (!$this->table_exists()) {
            $this->create_users_table();
        }
        
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        foreach ($users as $user_data) {
            $user_id = $this->insert_user($user_data);
            
            if ($user_id) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = isset($user_data['user_email']) ? $user_data['user_email'] : 'Unknown';
            }
        }
        
        return $results;
    }

    /**
     * Export users to CSV
     *
     * @param array $fields Fields to export
     * @param array $args Query arguments
     * @return string CSV content
     */
    public function export_users_to_csv($fields = array(), $args = array()) {
        if (!$this->table_exists()) {
            return '';
        }
        
        if (empty($fields)) {
            $fields = array('id', 'user_login', 'user_email', 'user_first_name', 'user_last_name', 'user_registered', 'last_login', 'login_count', 'email_verified');
        }
        
        $users = $this->get_users($args);
        
        $output = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($output, $fields);
        
        // Add data
        foreach ($users as $user) {
            $row = array();
            foreach ($fields as $field) {
                $row[] = isset($user->$field) ? $user->$field : '';
            }
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}