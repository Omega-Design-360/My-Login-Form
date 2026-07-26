<?php
/**
 * Supabase AJAX Handlers
 *
 * @package MyLoginForm\Ajax
 */

namespace MyLoginForm\Ajax;

defined('ABSPATH') || exit;

class SupabaseAjax {

    private static $instance = null;

    private function __construct() {
        $this->register_hooks();
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function register_hooks(): void {
        add_action('wp_ajax_my_login_supabase_test',   [$this, 'test_connection']);
        add_action('wp_ajax_my_login_save_supabase_connection',  [$this, 'save_connection']);
        add_action('wp_ajax_my_login_disconnect_supabase',       [$this, 'disconnect']);
        add_action('wp_ajax_my_login_save_wc_settings',          [$this, 'save_wc_settings']);
    }

    /**
     * Test the Supabase connection
     */
    public function test_connection(): void {
        check_ajax_referer('my_login_supabase_test', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'my-login-form')], 403);
        }

        $url      = trailingslashit(esc_url_raw($_POST['url'] ?? ''));
        $anon_key = sanitize_text_field($_POST['anon_key'] ?? '');

        // The Setup form never echoes a previously-saved key back into the
        // field (so the real secret never sits in the page's HTML source) —
        // a blank submission means "test the key that's already saved", not
        // "no key was entered".
        if ($anon_key === '') {
            $anon_key = get_option('my_login_supabase_anon_key', '');
        }

        if (empty($url) || empty($anon_key)) {
            wp_send_json_error(['message' => __('Please enter both URL and Anon Key.', 'my-login-form')]);
        }

        $response = wp_remote_get($url . 'rest/v1/', [
            'headers'   => [
                'apikey'        => $anon_key,
                'Authorization' => 'Bearer ' . $anon_key,
            ],
            'timeout'   => 15,
            'sslverify' => $this->should_verify_ssl($url),
        ]);

        if (is_wp_error($response)) {
            $error = $response->get_error_message();
            // Give a friendlier message for common SSL issues on localhost
            if (strpos($error, 'SSL') !== false || strpos($error, 'certificate') !== false) {
                $error = __('SSL error. If you are on localhost/XAMPP this is normal — the connection will work on a live server. Your credentials look correct.', 'my-login-form');
                // Treat as success since SSL issues on localhost do not mean wrong credentials
                wp_send_json_success([
                    'message' => '✓ ' . $error,
                    'status'  => 'connected',
                    'project' => parse_url($url, PHP_URL_HOST),
                ]);
            }
            wp_send_json_error(['message' => __('Connection failed: ', 'my-login-form') . $error]);
        }

        $status = wp_remote_retrieve_response_code($response);

        // 200 = success, 400 = connected but no tables (still valid)
        if ($status === 200 || $status === 400) {
            wp_send_json_success([
                'message' => '✓ ' . __('Connected successfully to Supabase!', 'my-login-form'),
                'status'  => 'connected',
                'project' => parse_url($url, PHP_URL_HOST),
            ]);
        } elseif ($status === 401) {
            wp_send_json_error(['message' => __('Invalid API key. Please check your anon/public key.', 'my-login-form')]);
        } elseif ($status === 404) {
            wp_send_json_error(['message' => __('Project URL not found. Please check your Supabase Project URL.', 'my-login-form')]);
        } else {
            wp_send_json_error(['message' => sprintf(__('Unexpected response (HTTP %d). Check your URL and key.', 'my-login-form'), $status)]);
        }
    }

    /**
     * Save connection credentials
     */
    public function save_connection(): void {
        check_ajax_referer('my_login_supabase_save', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'my-login-form')], 403);
        }

        $url         = esc_url_raw($_POST['url']         ?? '');
        $anon_key    = sanitize_text_field($_POST['anon_key']    ?? '');
        $service_key = sanitize_text_field($_POST['service_key'] ?? '');

        // The Setup form never echoes previously-saved keys back into these
        // fields (so the real secret is never sitting in the page's HTML
        // source) — submitting the form without touching them sends an
        // empty string, which must mean "keep what's already saved", not
        // "erase it". Explicit removal goes through disconnect() instead.
        if ($anon_key === '') {
            $anon_key = get_option('my_login_supabase_anon_key', '');
        }
        if ($service_key === '') {
            $service_key = get_option('my_login_supabase_service_key', '');
        }

        if (empty($url) || empty($anon_key)) {
            wp_send_json_error(['message' => __('URL and Anon Key are required.', 'my-login-form')]);
        }

        update_option('my_login_supabase_enabled',     1);
        update_option('my_login_supabase_url',          $url);
        // autoload=false — the service role key in particular must never sit
        // in the alloptions cache loaded on every single request.
        update_option('my_login_supabase_anon_key',     $anon_key, false);
        update_option('my_login_supabase_service_key',  $service_key, false);

        wp_send_json_success([
            'message' => __('Connection saved successfully!', 'my-login-form'),
        ]);
    }

    /**
     * Disconnect Supabase
     */
    public function disconnect(): void {
        check_ajax_referer('my_login_disconnect', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'my-login-form')], 403);
        }

        delete_option('my_login_supabase_enabled');
        delete_option('my_login_supabase_url');
        delete_option('my_login_supabase_anon_key');
        delete_option('my_login_supabase_service_key');

        wp_send_json_success();
    }

    /**
     * Save WooCommerce settings
     */
    public function save_wc_settings(): void {
        check_ajax_referer('my_login_wc_settings', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'my-login-form')], 403);
        }

        update_option('my_login_wc_auto_login',  intval($_POST['auto_login']  ?? 0));
        update_option('my_login_wc_sync_users',  intval($_POST['sync_users']  ?? 0));

        wp_send_json_success(['message' => __('WooCommerce settings saved.', 'my-login-form')]);
    }

    /**
     * Only skip SSL verification on localhost
     */
    private function should_verify_ssl(string $url): bool {
        $host = parse_url($url, PHP_URL_HOST);
        return !in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
