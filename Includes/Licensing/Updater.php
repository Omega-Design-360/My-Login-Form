<?php
/**
 * My Login Form - Self-Hosted Update Checker
 *
 * Gated by license validity: an expired or inactive license gets no update
 * information at all — no bug fixes, no security patches. This is the
 * enforcement that actually motivates renewals (see the store-only
 * my-login-form-licensing-backend project's licensing/README.md) —
 * everything else (feature gating, admin nags) is secondary to this.
 *
 * Requires a companion update-server endpoint you host separately
 * (MY_LOGIN_FORM_UPDATE_SERVER_URL) — this class only defines the contract
 * it expects that server to honor: a POST returning JSON shaped like
 * WordPress's own update_plugins transient entries (version, package,
 * slug, name, sections, etc. — the same shape EDD Software Licensing /
 * WooCommerce's plugin updater use). Building that server is out of scope
 * for the plugin itself; it's infrastructure you host wherever you like.
 *
 * @package MyLoginForm\Licensing
 */

namespace MyLoginForm\Licensing;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Updater {

    /**
     * Instance of this class
     *
     * @var Updater|null
     */
    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
    }

    /**
     * @param object $transient
     * @return object
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // The actual enforcement: no valid license, no update info surfaces
        // in wp-admin at all. The plugin keeps working — it just quietly
        // stops finding out about new versions.
        if (!License::get_instance()->is_active()) {
            return $transient;
        }

        $remote = $this->get_remote_info();
        if (!$remote || empty($remote->version)) {
            return $transient;
        }

        if (defined('MY_LOGIN_FORM_VERSION') && version_compare($remote->version, MY_LOGIN_FORM_VERSION, '>')) {
            $transient->response[MY_LOGIN_FORM_BASENAME] = $remote;
        } else {
            $transient->no_update[MY_LOGIN_FORM_BASENAME] = $remote;
        }

        return $transient;
    }

    /**
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object|array
     */
    public function plugin_info($result, $action, $args) {
        if ('plugin_information' !== $action || empty($args->slug) || $args->slug !== dirname(MY_LOGIN_FORM_BASENAME)) {
            return $result;
        }

        if (!License::get_instance()->is_active()) {
            return $result;
        }

        $remote = $this->get_remote_info();
        return $remote ?: $result;
    }

    /**
     * Fetches (and briefly caches) the latest-version info from the update
     * server. Returns null on any failure — a missing/unreachable update
     * server must never break the plugin, it just means no update shows up.
     *
     * @return object|null
     */
    private function get_remote_info() {
        $url = defined('MY_LOGIN_FORM_UPDATE_SERVER_URL') ? MY_LOGIN_FORM_UPDATE_SERVER_URL : '';
        if (!$url) {
            return null;
        }

        $license_key   = get_option('my_login_form_license_key', '');
        $transient_key = 'mlf_update_info_' . md5($url . $license_key);

        $cached = get_transient($transient_key);
        if ($cached !== false) {
            return $cached ?: null;
        }

        $response = wp_remote_post($url, [
            'timeout' => 10,
            'body'    => [
                'action'      => 'get_version',
                'slug'        => dirname(MY_LOGIN_FORM_BASENAME),
                'license_key' => $license_key,
                'domain'      => License::get_instance()->normalize_domain(home_url()),
                'version'     => defined('MY_LOGIN_FORM_VERSION') ? MY_LOGIN_FORM_VERSION : '',
            ],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            // Cache the miss briefly too, so an unreachable update server
            // doesn't slow down every single wp-admin page load.
            set_transient($transient_key, false, 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response));
        if (!$data || empty($data->version)) {
            set_transient($transient_key, false, 15 * MINUTE_IN_SECONDS);
            return null;
        }

        set_transient($transient_key, $data, 6 * HOUR_IN_SECONDS);
        return $data;
    }
}
