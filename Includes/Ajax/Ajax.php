<?php
/**
 * Main AJAX Loader
 *
 * @package MyLoginForm\Ajax
 */

namespace MyLoginForm\Ajax;

defined('ABSPATH') || exit;

class Ajax {

    private static $instance = null;

    private function __construct() {
        $this->load_ajax();
    }


    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    
    /**
     * Load all AJAX handlers
     * @return void
     */
    private function load_ajax(): void {

        if (class_exists('MyLoginForm\Ajax\AuthAjax')) {
            AuthAjax::get_instance();
        }

        if (class_exists('MyLoginForm\Ajax\DashboardAjax')) {
            DashboardAjax::get_instance();
        }
        
        
        if (class_exists('MyLoginForm\Ajax\DesignerAjax')) {
            DesignerAjax::get_instance();
        }

        // // Only load classes that actually exist
        // if (class_exists('MyLoginForm\Ajax\OnboardingAjax')) {
        //     OnboardingAjax::get_instance();
        // }
        
        if (class_exists('MyLoginForm\Ajax\SupabaseAjax')) {
            SupabaseAjax::get_instance();
        }
        
        if (class_exists('MyLoginForm\Ajax\SettingsAjax')) {
            SettingsAjax::get_instance();
        }
        
        if (class_exists('MyLoginForm\Ajax\UsersdataAjax')) {
            UsersdataAjax::get_instance();
        }

        if (class_exists('MyLoginForm\Ajax\LicenseAjax')) {
            LicenseAjax::get_instance();
        }
    }
}