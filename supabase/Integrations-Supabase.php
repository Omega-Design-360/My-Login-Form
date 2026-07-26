<?php
/**
 * Supabase Authentication Class
 * 
 * @package MyLoginForm
 */

namespace MyLoginForm\Integrations;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Supabase {
    
    
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor for singleton
     */
    private function __construct() {
    }
    
    
}