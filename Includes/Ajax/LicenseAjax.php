<?php
/**
 * License Activation AJAX Handler
 *
 * @package MyLoginForm\Ajax
 */

namespace MyLoginForm\Ajax;

// Prevent Direct Access
defined('ABSPATH') || exit;

class LicenseAjax {

    private static $instance = null;

    private function __construct() {
        add_action('wp_ajax_my_login_license_activate',   [$this, 'activate']);
        add_action('wp_ajax_my_login_license_deactivate', [$this, 'deactivate']);
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Activate a license key for this site.
     */
    public function activate(): void {
        check_ajax_referer('my_login_license_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'my-login-form')], 403);
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $key   = sanitize_text_field($_POST['license_key'] ?? '');

        $result = \MyLoginForm\Licensing\License::get_instance()->activate($email, $key);

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        }
        wp_send_json_error(['message' => $result['message']]);
    }

    /**
     * Free up this domain's activation slot and clear local license state.
     */
    public function deactivate(): void {
        check_ajax_referer('my_login_license_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'my-login-form')], 403);
        }

        $result = \MyLoginForm\Licensing\License::get_instance()->deactivate();
        wp_send_json_success(['message' => $result['message']]);
    }
}
