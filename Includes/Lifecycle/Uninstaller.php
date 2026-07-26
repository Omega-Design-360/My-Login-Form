<?php
namespace MyLoginForm\Lifecycle;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Uninstaller {


    private static $instance = null;
    private function __construct() {}
    
    /**
     * Uninstall the plugin
     */
    public static function uninstall() {
        // Check if we should remove data
        if (get_option('my_login_form_remove_data_on_uninstall', false)) {
            self::remove_options();
            self::remove_tables();
            self::remove_user_meta();
            self::remove_transients();
            self::clear_cron_events();
        }
    }
    
    /**
     * Remove all plugin options
     */
    private static function remove_options() {
        global $wpdb;
        
        // Get all plugin options from database
        $options = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM $wpdb->options 
                WHERE option_name LIKE %s",
                $wpdb->esc_like('my_login_form_') . '%'
            )
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }
    
    /**
     * Remove all plugin tables
     */
    private static function remove_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'my_login_attempts',
            $wpdb->prefix . 'my_login_sessions',
            $wpdb->prefix . 'my_login_social_profiles',
            $wpdb->prefix . 'my_login_email_verifications',
            $wpdb->prefix . 'my_login_password_resets',
            $wpdb->prefix . 'my_login_form_builder',
            $wpdb->prefix . 'my_login_form_entries',
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
    
    /**
     * Remove all user meta data
     */
    private static function remove_user_meta() {
        global $wpdb;
        
        $meta_keys = array(
            'my_login_form_social_id',
            'my_login_form_verified',
            'my_login_form_verification_key',
            'my_login_form_last_login',
            'my_login_form_social_provider',
            'my_login_form_profile_picture',
        );
        
        foreach ($meta_keys as $meta_key) {
            $wpdb->delete(
                $wpdb->usermeta,
                array('meta_key' => $meta_key),
                array('%s')
            );
        }
    }
    
    /**
     * Remove all plugin transients
     */
    private static function remove_transients() {
        global $wpdb;
        
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
    
    /**
     * Clear all scheduled cron events
     */
    private static function clear_cron_events() {
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
}