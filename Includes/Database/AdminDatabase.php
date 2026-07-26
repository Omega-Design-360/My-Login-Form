<?php
/**
 * My Login Form - Admin Database Class
 *
 * @package MyLoginForm\Database
 */

namespace MyLoginForm\Database;

// Prevent Direct Access
defined('ABSPATH') || exit;

class AdminDatabase {

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * Admin sessions table name
     *
     * @var string
     */
    private $admin_sessions_table;

    /**
     * Logs table name
     *
     * @var string
     */
    private $logs_table;
    
    /**
     * Options table name
     *
     * @var string
     */
    private $options_table;
    
    /**
     * Admin activity table name
     *
     * @var string
     */
    private $admin_activity_table;

    /**
     * Instance of this class
     *
     * @var AdminDatabase
     */
    private static $instance = null;

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        $this->logs_table = $wpdb->prefix . 'my_login_logs';
        $this->admin_sessions_table = $wpdb->prefix . 'my_login_admin_sessions';
        $this->options_table = $wpdb->prefix . 'options';
        $this->admin_activity_table = $wpdb->prefix . 'my_login_admin_activity';
    }

    /**
     * Get singleton instance
     *
     * @return AdminDatabase
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the admin database module
     *
     * @return void
     */
    public function init() {
        // Create/update tables immediately. init() itself normally runs from
        // inside a 'plugins_loaded' callback (see Loader::init()), so hooking
        // this to 'plugins_loaded' here would register it after that action
        // has already reached this priority for the current request — it
        // would never fire. Call it directly instead, same as the other
        // *Database classes.
        $this->check_admin_tables();

        // Register cleanup cron job
        add_action('my_login_form_daily_maintenance', array($this, 'cleanup_old_data'));

        // Log admin login/logout
        add_action('wp_login', array($this, 'log_admin_login'), 10, 2);
        add_action('wp_logout', array($this, 'log_admin_logout'));
    }

    /**
     * Check and create admin tables if they don't exist
     *
     * @return void
     */
    public function check_admin_tables() {
        $installed_version = get_option('my_login_form_admin_db_version', '0');

        // Ensure tables exist
        $this->create_admin_tables();

        // Run column fixes
        $this->maybe_update_admin_tables();

        if (version_compare($installed_version, MY_LOGIN_FORM_VERSION, '<')) {
            update_option('my_login_form_admin_db_version', MY_LOGIN_FORM_VERSION);
        }
    }

    /**
     * Create admin database tables
     *
     * @return bool
     */
    public function create_admin_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Admin sessions table
        $admin_sessions_sql = "CREATE TABLE IF NOT EXISTS {$this->admin_sessions_table} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(100) NOT NULL,
            user_id INT(11) NOT NULL,
            admin_username VARCHAR(100),
            admin_email VARCHAR(100),
            ip_address VARCHAR(45),
            user_agent TEXT,
            login_time DATETIME NOT NULL,
            last_activity DATETIME NOT NULL,
            logout_time DATETIME,
            session_status VARCHAR(20) DEFAULT 'active',
            session_duration INT(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY session_status (session_status),
            KEY login_time (login_time),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        // Admin activity table
        $admin_activity_sql = "CREATE TABLE IF NOT EXISTS {$this->admin_activity_table} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            activity_id VARCHAR(100) NOT NULL,
            user_id INT(11) NOT NULL,
            admin_username VARCHAR(100),
            admin_email VARCHAR(100),
            activity_type VARCHAR(50) NOT NULL,
            activity_action VARCHAR(100) NOT NULL,
            activity_target VARCHAR(255),
            target_id INT(11),
            details LONGTEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            severity VARCHAR(20) DEFAULT 'info',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY activity_id (activity_id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY activity_action (activity_action),
            KEY created_at (created_at),
            KEY severity (severity)
        ) $charset_collate;";
        
        // Main logs table — written to by log_to_main_table() after every
        // successful activity log, so it needs to exist alongside the two
        // tables above.
        $logs_sql = "CREATE TABLE IF NOT EXISTS {$this->logs_table} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            log_type VARCHAR(50) NOT NULL,
            log_action VARCHAR(100) NOT NULL,
            user_id INT(11),
            wp_user_id INT(11),
            ip_address VARCHAR(45),
            user_agent TEXT,
            details LONGTEXT,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY log_type (log_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($admin_sessions_sql);
        dbDelta($admin_activity_sql);
        dbDelta($logs_sql);

        if ($this->wpdb->last_error) {
            error_log('My Login Form: Failed to create admin tables - ' . $this->wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Update table structure if needed
     *
     * @return void
     */
    private function maybe_update_admin_tables() {
        // Update admin sessions table
        $sessions_columns = $this->wpdb->get_col("DESC {$this->admin_sessions_table}");
        
        $missing_sessions_columns = array(
            'session_duration' => "ADD session_duration INT(11) DEFAULT 0",
            'admin_username' => "ADD admin_username VARCHAR(100)",
            'admin_email' => "ADD admin_email VARCHAR(100)"
        );
        
        foreach ($missing_sessions_columns as $column => $sql) {
            if (!in_array($column, $sessions_columns)) {
                $this->wpdb->query("ALTER TABLE {$this->admin_sessions_table} {$sql}");
            }
        }
        
        // Update admin activity table
        $activity_columns = $this->wpdb->get_col("DESC {$this->admin_activity_table}");
        
        $missing_activity_columns = array(
            'severity' => "ADD severity VARCHAR(20) DEFAULT 'info'",
            'target_id' => "ADD target_id INT(11)",
            'admin_username' => "ADD admin_username VARCHAR(100)",
            'admin_email' => "ADD admin_email VARCHAR(100)"
        );
        
        foreach ($missing_activity_columns as $column => $sql) {
            if (!in_array($column, $activity_columns)) {
                $this->wpdb->query("ALTER TABLE {$this->admin_activity_table} {$sql}");
            }
        }
    }

    /**
     * Create admin session
     *
     * @param int $user_id User ID
     * @param string $session_id Session ID
     * @param string $ip_address IP address
     * @param string $user_agent User agent
     * @return int|false Session ID or false
     */
    public function create_admin_session($user_id, $session_id, $ip_address = null, $user_agent = null) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        $data = array(
            'session_id' => sanitize_text_field($session_id),
            'user_id' => intval($user_id),
            'admin_username' => $user->user_login,
            'admin_email' => $user->user_email,
            'ip_address' => $ip_address ?: $this->get_client_ip(),
            'user_agent' => $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'login_time' => current_time('mysql'),
            'last_activity' => current_time('mysql'),
            'session_status' => 'active',
            'session_duration' => 0
        );
        
        $result = $this->wpdb->insert($this->admin_sessions_table, $data);
        
        if ($result) {
            $session_db_id = $this->wpdb->insert_id;
            $this->log_admin_activity($user_id, 'session', 'admin_login', array(
                'session_id' => $session_id,
                'ip_address' => $data['ip_address']
            ));
            return $session_db_id;
        }
        
        return false;
    }

    /**
     * Update admin session activity
     *
     * @param string $session_id Session ID
     * @return bool
     */
    public function update_session_activity($session_id) {
        $session = $this->get_admin_session($session_id);
        
        if (!$session) {
            return false;
        }
        
        $login_time = strtotime($session->login_time);
        $current_time = current_time('timestamp');
        $duration = $current_time - $login_time;
        
        return $this->wpdb->update(
            $this->admin_sessions_table,
            array(
                'last_activity' => current_time('mysql'),
                'session_duration' => $duration
            ),
            array('session_id' => $session_id)
        );
    }

    /**
     * Close admin session (logout)
     *
     * @param string $session_id Session ID
     * @return bool
     */
    public function close_admin_session($session_id) {
        $session = $this->get_admin_session($session_id);
        
        if (!$session) {
            return false;
        }
        
        $login_time = strtotime($session->login_time);
        $logout_time = current_time('timestamp');
        $duration = $logout_time - $login_time;
        
        $result = $this->wpdb->update(
            $this->admin_sessions_table,
            array(
                'logout_time' => current_time('mysql'),
                'session_status' => 'closed',
                'session_duration' => $duration
            ),
            array('session_id' => $session_id)
        );
        
        if ($result) {
            $this->log_admin_activity($session->user_id, 'session', 'admin_logout', array(
                'session_id' => $session_id,
                'duration' => $duration
            ));
        }
        
        return $result;
    }

    /**
     * Get admin session by session ID
     *
     * @param string $session_id Session ID
     * @return object|null
     */
    public function get_admin_session($session_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->admin_sessions_table} WHERE session_id = %s",
                $session_id
            )
        );
    }

    /**
     * Get active admin sessions
     *
     * @param int $limit Limit results
     * @return array
     */
    public function get_active_sessions($limit = 100) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->admin_sessions_table} 
                WHERE session_status = 'active' 
                ORDER BY last_activity DESC 
                LIMIT %d",
                $limit
            )
        );
    }

    /**
     * Get user admin sessions
     *
     * @param int $user_id User ID
     * @param int $limit Limit results
     * @return array
     */
    public function get_user_sessions($user_id, $limit = 50) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->admin_sessions_table} 
                WHERE user_id = %d 
                ORDER BY login_time DESC 
                LIMIT %d",
                $user_id,
                $limit
            )
        );
    }

    /**
     * Terminate all user sessions except current
     *
     * @param int $user_id User ID
     * @param string $current_session_id Current session ID to keep
     * @return int Number of terminated sessions
     */
    public function terminate_user_sessions($user_id, $current_session_id = null) {
        $sql = "UPDATE {$this->admin_sessions_table} 
                SET session_status = 'terminated', 
                    logout_time = %s 
                WHERE user_id = %d 
                AND session_status = 'active'";
        
        $params = array(current_time('mysql'), $user_id);
        
        if ($current_session_id) {
            $sql .= " AND session_id != %s";
            $params[] = $current_session_id;
        }
        
        $result = $this->wpdb->query($this->wpdb->prepare($sql, $params));
        
        if ($result) {
            $this->log_admin_activity($user_id, 'session', 'terminate_sessions', array(
                'terminated_count' => $result,
                'kept_session' => $current_session_id
            ));
        }
        
        return $result;
    }

    /**
     * Log admin activity
     *
     * @param int $user_id User ID
     * @param string $activity_type Activity type (form, user, settings, session, etc.)
     * @param string $activity_action Action performed
     * @param array $details Additional details
     * @param string $severity Severity level (info, warning, error)
     * @return int|false Activity ID or false
     */
    public function log_admin_activity($user_id, $activity_type, $activity_action, $details = array(), $severity = 'info') {
        $user = get_userdata($user_id);
        
        $data = array(
            'activity_id' => uniqid('act_', true),
            'user_id' => intval($user_id),
            'admin_username' => $user ? $user->user_login : 'Unknown',
            'admin_email' => $user ? $user->user_email : '',
            'activity_type' => sanitize_text_field($activity_type),
            'activity_action' => sanitize_text_field($activity_action),
            'activity_target' => isset($details['target']) ? sanitize_text_field($details['target']) : null,
            'target_id' => isset($details['target_id']) ? intval($details['target_id']) : null,
            'details' => maybe_serialize($details),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'severity' => sanitize_text_field($severity),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert($this->admin_activity_table, $data);
        
        if ($result) {
            // Also log to main logs table
            $this->log_to_main_table($user_id, $activity_type, $activity_action, $details);
            return $this->wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Log to main logs table
     *
     * @param int $user_id User ID
     * @param string $type Log type
     * @param string $action Log action
     * @param array $details Details
     * @return bool
     */
    private function log_to_main_table($user_id, $type, $action, $details = array()) {
        $data = array(
            'log_type' => 'admin_' . sanitize_text_field($type),
            'log_action' => sanitize_text_field($action),
            'user_id' => $user_id,
            'wp_user_id' => $user_id,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'details' => maybe_serialize($details),
            'created_at' => current_time('mysql')
        );
        
        return (bool) $this->wpdb->insert($this->logs_table, $data);
    }

    /**
     * Get admin activities
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_admin_activities($args = array()) {
        $defaults = array(
            'user_id' => null,
            'activity_type' => '',
            'activity_action' => '',
            'severity' => '',
            'date_from' => '',
            'date_to' => '',
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $query_params = array();
        
        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $query_params[] = intval($args['user_id']);
        }
        
        if (!empty($args['activity_type'])) {
            $where[] = 'activity_type = %s';
            $query_params[] = $args['activity_type'];
        }
        
        if (!empty($args['activity_action'])) {
            $where[] = 'activity_action = %s';
            $query_params[] = $args['activity_action'];
        }
        
        if (!empty($args['severity'])) {
            $where[] = 'severity = %s';
            $query_params[] = $args['severity'];
        }
        
        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $query_params[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $query_params[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->admin_activity_table} WHERE {$where_clause}";
        
        $allowed_orderby = ['id', 'user_id', 'activity_type', 'activity_action', 'severity', 'created_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$orderby} {$order}";
        
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d OFFSET %d";
            $query_params[] = intval($args['limit']);
            $query_params[] = intval($args['offset']);
        }
        
        if (!empty($query_params)) {
            $sql = $this->wpdb->prepare($sql, $query_params);
        }
        
        $activities = $this->wpdb->get_results($sql);
        
        foreach ($activities as $activity) {
            if (isset($activity->details)) {
                $activity->details = maybe_unserialize($activity->details);
            }
        }
        
        return $activities;
    }

    /**
     * Get admin activity count
     *
     * @param array $args Filter arguments
     * @return int
     */
    public function get_admin_activity_count($args = array()) {
        $where = array('1=1');
        $query_params = array();
        
        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $query_params[] = intval($args['user_id']);
        }
        
        if (!empty($args['activity_type'])) {
            $where[] = 'activity_type = %s';
            $query_params[] = $args['activity_type'];
        }
        
        if (!empty($args['severity'])) {
            $where[] = 'severity = %s';
            $query_params[] = $args['severity'];
        }
        
        $where_clause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$this->admin_activity_table} WHERE {$where_clause}";
        
        if (!empty($query_params)) {
            $sql = $this->wpdb->prepare($sql, $query_params);
        }
        
        return (int) $this->wpdb->get_var($sql);
    }

    /**
     * Get admin statistics
     *
     * @return array
     */
    public function get_admin_statistics() {
        $stats = array(
            'total_admin_logins' => 0,
            'active_sessions' => 0,
            'total_activities' => 0,
            'activities_by_type' => array(),
            'activities_last_24h' => 0,
            'activities_last_7d' => 0,
            'activities_last_30d' => 0,
            'error_activities' => 0,
            'warning_activities' => 0,
            'unique_admin_users' => 0
        );
        
        // Total admin logins
        $stats['total_admin_logins'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->admin_sessions_table}"
        );
        
        // Active sessions
        $stats['active_sessions'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->admin_sessions_table} WHERE session_status = 'active'"
        );
        
        // Total activities
        $stats['total_activities'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->admin_activity_table}"
        );
        
        // Activities by type
        $activities_by_type = $this->wpdb->get_results(
            "SELECT activity_type, COUNT(*) as count FROM {$this->admin_activity_table} GROUP BY activity_type"
        );
        
        foreach ($activities_by_type as $item) {
            $stats['activities_by_type'][$item->activity_type] = (int) $item->count;
        }
        
        // Activities by time period
        $stats['activities_last_24h'] = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->admin_activity_table} WHERE created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-24 hours'))
            )
        );
        
        $stats['activities_last_7d'] = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->admin_activity_table} WHERE created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-7 days'))
            )
        );
        
        $stats['activities_last_30d'] = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->admin_activity_table} WHERE created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
        
        // Error and warning activities
        $stats['error_activities'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->admin_activity_table} WHERE severity = 'error'"
        );
        
        $stats['warning_activities'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->admin_activity_table} WHERE severity = 'warning'"
        );
        
        // Unique admin users
        $stats['unique_admin_users'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$this->admin_sessions_table}"
        );
        
        return $stats;
    }

    /**
     * Get admin dashboard summary
     *
     * @return array
     */
    public function get_admin_dashboard_summary() {
        $stats = $this->get_admin_statistics();
        
        // Recent activities
        $recent_activities = $this->get_admin_activities(array(
            'limit' => 10,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ));
        
        // Active sessions
        $active_sessions = $this->get_active_sessions(10);
        
        // Top admin users by activity
        $top_admins = $this->wpdb->get_results(
            "SELECT user_id, admin_username, COUNT(*) as activity_count 
            FROM {$this->admin_activity_table} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY user_id, admin_username 
            ORDER BY activity_count DESC 
            LIMIT 10"
        );
        
        return array(
            'statistics' => $stats,
            'recent_activities' => $recent_activities,
            'active_sessions' => $active_sessions,
            'top_admins' => $top_admins
        );
    }

    /**
     * Log admin login (hook callback)
     *
     * @param string $user_login User login
     * @param WP_User $user User object
     * @return void
     */
    public function log_admin_login($user_login, $user) {
        if (user_can($user, 'manage_options')) {
            $session_id = wp_get_session_token();
            if ($session_id) {
                $this->create_admin_session(
                    $user->ID,
                    $session_id,
                    $this->get_client_ip(),
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );
            }
        }
    }

    /**
     * Log admin logout (hook callback)
     *
     * @return void
     */
    public function log_admin_logout() {
        $session_id = wp_get_session_token();
        if ($session_id) {
            $this->close_admin_session($session_id);
        }
    }

    /**
     * Cleanup old data
     *
     * @param int $days Number of days to keep data
     * @return array Cleanup results
     */
    public function cleanup_old_data($days = 30) {
        $results = array(
            'sessions_deleted' => 0,
            'activities_deleted' => 0
        );
        
        // Delete old closed sessions (older than $days days)
        $results['sessions_deleted'] = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->admin_sessions_table} 
                WHERE session_status != 'active' 
                AND login_time < %s",
                date('Y-m-d H:i:s', strtotime("-{$days} days"))
            )
        );
        
        // Delete old activities (older than $days days)
        $results['activities_deleted'] = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->admin_activity_table} 
                WHERE created_at < %s",
                date('Y-m-d H:i:s', strtotime("-{$days} days"))
            )
        );
        
        // Log cleanup
        $this->log_admin_activity(0, 'system', 'cleanup_old_data', $results, 'info');
        
        return $results;
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
     * Export admin activities to CSV
     *
     * @param array $args Query arguments
     * @return string CSV content
     */
    public function export_activities_to_csv($args = array()) {
        $activities = $this->get_admin_activities($args);
        
        if (empty($activities)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Headers
        $headers = array('ID', 'Username', 'Email', 'Type', 'Action', 'Target', 'Severity', 'IP Address', 'Created At');
        fputcsv($output, $headers);
        
        // Data
        foreach ($activities as $activity) {
            $row = array(
                $activity->id,
                $activity->admin_username,
                $activity->admin_email,
                $activity->activity_type,
                $activity->activity_action,
                $activity->activity_target,
                $activity->severity,
                $activity->ip_address,
                $activity->created_at
            );
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Get activity types for filtering
     *
     * @return array
     */
    public function get_activity_types() {
        return $this->wpdb->get_col(
            "SELECT DISTINCT activity_type FROM {$this->admin_activity_table} ORDER BY activity_type"
        );
    }

    /**
     * Get severity levels for filtering
     *
     * @return array
     */
    public function get_severity_levels() {
        return $this->wpdb->get_col(
            "SELECT DISTINCT severity FROM {$this->admin_activity_table} ORDER BY severity"
        );
    }

    /**
     * Check if admin session is active
     *
     * @param string $session_id Session ID
     * @return bool
     */
    public function is_session_active($session_id) {
        $session = $this->get_admin_session($session_id);
        
        if (!$session || $session->session_status !== 'active') {
            return false;
        }
        
        // Check if session expired (inactivity for 1 hour)
        $last_activity = strtotime($session->last_activity);
        if ((time() - $last_activity) > 3600) {
            $this->close_admin_session($session_id);
            return false;
        }
        
        return true;
    }

    /**
     * Get admin user activity summary
     *
     * @param int $user_id User ID
     * @return array
     */
    public function get_user_activity_summary($user_id) {
        return array(
            'total_logins' => (int) $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->admin_sessions_table} WHERE user_id = %d",
                    $user_id
                )
            ),
            'total_activities' => (int) $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->admin_activity_table} WHERE user_id = %d",
                    $user_id
                )
            ),
            'last_login' => $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT login_time FROM {$this->admin_sessions_table} WHERE user_id = %d ORDER BY login_time DESC LIMIT 1",
                    $user_id
                )
            ),
            'last_activity' => $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT created_at FROM {$this->admin_activity_table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
                    $user_id
                )
            )
        );
    }
}