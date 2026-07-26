<?php
/**
 * My Login Form - OTP Audit Log Database Class
 *
 * @package MyLoginForm\Database
 */

namespace MyLoginForm\Database;

// Prevent Direct Access
defined('ABSPATH') || exit;

/**
 * Audit trail for the Supabase-backed OTP flow (AuthAjax::send_supabase_email_otp() /
 * verify_supabase_email_otp()). Supabase's own Auth service generates and validates the
 * actual 6-digit codes — this plugin never sees or stores the code itself — so this table
 * only records that an attempt happened (sent / verified / failed), for rate-limiting,
 * debugging, and audit purposes.
 */
class OtpDatabase {

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * OTP log table name
     *
     * @var string
     */
    private $table;

    /**
     * Instance of this class
     *
     * @var OtpDatabase
     */
    private static $instance = null;

    /**
     * Constructor - creates table immediately
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb  = $wpdb;
        $this->table = $wpdb->prefix . 'my_login_otp_log';

        $this->create_table();
    }

    /**
     * Get singleton instance
     *
     * @return OtpDatabase
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
        add_action('my_login_form_cleanup', [$this, 'cleanup_old_entries']);
    }

    /**
     * Get OTP log table name
     *
     * @return string
     */
    public function get_table_name() {
        return $this->table;
    }

    /**
     * Create the OTP log table
     *
     * @return bool
     */
    private function create_table() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            context VARCHAR(20) NOT NULL,
            action VARCHAR(20) NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email_idx (email),
            KEY context_idx (context),
            KEY created_at_idx (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        if ($this->wpdb->last_error) {
            error_log('My Login Form OtpDatabase: Failed to create OTP log table - ' . $this->wpdb->last_error);
            return false;
        }

        return $this->table_exists();
    }

    /**
     * Check if the OTP log table exists
     *
     * @return bool
     */
    public function table_exists() {
        $table = $this->wpdb->get_var(
            $this->wpdb->prepare('SHOW TABLES LIKE %s', $this->table)
        );
        return $table === $this->table;
    }

    /**
     * Record an OTP event.
     *
     * @param string $email   The email address the code was sent to / verified for.
     * @param string $context 'register' | 'login' | 'forgot_password'
     * @param string $action  'sent' | 'send_failed' | 'verified' | 'failed'
     * @return bool
     */
    public function log($email, $context, $action) {
        if (!$this->table_exists()) {
            return false;
        }

        return (bool) $this->wpdb->insert($this->table, [
            'email'      => sanitize_email($email),
            'context'    => sanitize_key($context),
            'action'     => sanitize_key($action),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                ? substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 255)
                : '',
            'created_at' => current_time('mysql'),
        ]);
    }

    /**
     * Count matching events for an email within the last $minutes — for simple
     * rate-limiting (e.g. refusing to resend more than N codes per hour).
     *
     * @param string $email
     * @param string $action
     * @param int    $minutes
     * @return int
     */
    public function count_recent($email, $action = 'sent', $minutes = 60) {
        if (!$this->table_exists()) {
            return 0;
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE email = %s AND action = %s AND created_at >= %s",
            $email,
            $action,
            gmdate('Y-m-d H:i:s', strtotime("-{$minutes} minutes"))
        ));
    }

    /**
     * Delete log entries older than 30 days. Hooked to the plugin's existing
     * weekly 'my_login_form_cleanup' cron event.
     *
     * @return void
     */
    public function cleanup_old_entries() {
        if (!$this->table_exists()) {
            return;
        }

        $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM {$this->table} WHERE created_at < %s",
            gmdate('Y-m-d H:i:s', strtotime('-30 days'))
        ));
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}
