<?php
/**
 * My Login Form - Form Database Class
 *
 * @package MyLoginForm\Database
 */

namespace MyLoginForm\Database;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Migrations {
    /**
     * Instance of this class
     *
     * @var Migrations
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Migrations
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }





}