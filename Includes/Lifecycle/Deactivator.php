<?php
namespace MyLoginForm\Lifecycle;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Deactivator {



    private static $instance = null;
    private function __construct() {}
    
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clear all scheduled tasks
        self::clear_scheduled_events();
        
        // Clear any transients
        self::clear_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Optional: Log deactivation time (for debugging)
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('My Login Form deactivated at: ' . current_time('mysql'));
        }
    }
    
    /**
     * Clear all scheduled cron events
     */
    private static function clear_scheduled_events() {
        $cron_hooks = array(
            'my_login_form_daily_maintenance',
            'my_login_form_cleanup',
            'my_login_form_weekly_report',
            'my_login_form_session_cleanup'
        );
        
        foreach ($cron_hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
            wp_clear_scheduled_hook($hook);
        }
    }
    
    /**
     * Clear plugin transients
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Delete all plugin transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                $wpdb->esc_like('_transient_my_login_form_') . '%',
                $wpdb->esc_like('_transient_timeout_my_login_form_') . '%'
            )
        );
    }
}