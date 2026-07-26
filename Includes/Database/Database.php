<?php
/**
 * My Login Form - Main Database Class
 *
 * @package MyLoginForm\Database
 */

namespace MyLoginForm\Database;

// Prevent Direct Access
defined('ABSPATH') || exit;

/**
 * Main Database handler for My Login Form
 * This class acts as a central manager for all database operations
 */
class Database {

    /**
     * Instance of this class
     *
     * @var Database
     */
    private static $instance = null;

    /**
     * Registered database instances
     *
     * @var array
     */
    private $databases = array();

    /**
     * Get singleton instance
     *
     * @return Database
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_databases();
    }

    /**
     * Load and initialize all database classes
     *
     * @return void
     */
    private function load_databases(): void {
        // Initialize Admin Database
        if (class_exists('MyLoginForm\Database\AdminDatabase')) {
            $this->databases['admin'] = AdminDatabase::get_instance();
            $this->databases['admin']->init();
        }

        // Initialize Forms Database
        if (class_exists('MyLoginForm\Database\FormsDatabase')) {
            $this->databases['forms'] = FormsDatabase::get_instance();
            $this->databases['forms']->init();
        }

        // Initialize Users Database
        if (class_exists('MyLoginForm\Database\UsersDatabase')) {
            $this->databases['users'] = UsersDatabase::get_instance();
            $this->databases['users']->init();
        }

        // Initialize Supabase Database
        if (class_exists('MyLoginForm\Database\SupabaseDatabase')) {
            $this->databases['supabase'] = SupabaseDatabase::get_instance();
            $this->databases['supabase']->init();
        }

        // Initialize OTP audit log
        if (class_exists('MyLoginForm\Database\OtpDatabase')) {
            $this->databases['otp'] = OtpDatabase::get_instance();
            $this->databases['otp']->init();
        }
    }

    /**
     * Get a specific database instance
     *
     * @param string $type Database type (forms, users, admin, supabase)
     * @return object|null
     */
    public function get_database($type) {
        return isset($this->databases[$type]) ? $this->databases[$type] : null;
    }

    /**
     * Get forms database
     *
     * @return FormsDatabase|null
     */
    public function forms() {
        return $this->get_database('forms');
    }

    /**
     * Get users database
     *
     * @return UsersDatabase|null
     */
    public function users() {
        return $this->get_database('users');
    }

    /**
     * Get admin database
     *
     * @return AdminDatabase|null
     */
    public function admin() {
        return $this->get_database('admin');
    }

    /**
     * Get supabase database
     *
     * @return SupabaseDatabase|null
     */
    public function supabase() {
        return $this->get_database('supabase');
    }

    /**
     * Get OTP audit log database
     *
     * @return OtpDatabase|null
     */
    public function otp() {
        return $this->get_database('otp');
    }
}