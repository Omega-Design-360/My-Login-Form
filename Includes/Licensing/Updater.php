<?php
/**
 * My Login Form - Self-Hosted Update Checker (GitHub Releases)
 *
 * Gated by license validity: an expired or inactive license gets no update
 * information at all — no bug fixes, no security patches. This is the
 * enforcement that actually motivates renewals — everything else (feature
 * gating, admin nags) is secondary to this.
 *
 * Reads new versions straight from the plugin's GitHub repo
 * (MY_LOGIN_FORM_GITHUB_REPO, "owner/repo") via the public Releases API —
 * no separate update server to host. A tagged GitHub Release (not just a
 * push to the branch) is what makes a new version appear in wp-admin: the
 * release's tag name (e.g. "v1.2.0") becomes the version WordPress compares
 * against MY_LOGIN_FORM_VERSION. If the release has a .zip asset attached,
 * that's installed as-is; otherwise the release's source zipball is used
 * and re-packaged into the correct folder name during install.
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
        add_filter('upgrader_source_selection', [$this, 'fix_source_folder'], 10, 4);
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
     * A GitHub release's zip — whether the auto-generated source zipball
     * or a hand-attached asset that wasn't built with the right top-level
     * folder — almost never extracts to a directory named "my-login-form",
     * which is what WP's upgrader needs to overwrite the existing plugin
     * folder instead of installing a second, differently-named copy
     * alongside it. This renames the extracted source directory to match
     * right before WP copies it into place.
     *
     * @param string       $source
     * @param string       $remote_source
     * @param \WP_Upgrader $upgrader
     * @param array        $hook_extra
     * @return string|\WP_Error
     */
    public function fix_source_folder($source, $remote_source, $upgrader, $hook_extra = []) {
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== MY_LOGIN_FORM_BASENAME) {
            return $source;
        }

        $expected_slug = dirname(MY_LOGIN_FORM_BASENAME);
        $current_slug  = basename(untrailingslashit($source));

        if ($current_slug === $expected_slug) {
            return $source;
        }

        global $wp_filesystem;
        $corrected = trailingslashit($remote_source) . $expected_slug . '/';

        if ($wp_filesystem->move($source, $corrected, true)) {
            return $corrected;
        }

        return new \WP_Error(
            'mlf_update_rename_failed',
            __('Could not rename the downloaded update to the plugin folder name.', 'my-login-form')
        );
    }

    /**
     * Fetches (and briefly caches) the latest-release info from GitHub.
     * Returns null on any failure — no releases published yet, repo
     * unreachable, rate-limited, etc. must never break the plugin, it just
     * means no update shows up.
     *
     * @return object|null
     */
    private function get_remote_info() {
        $repo = defined('MY_LOGIN_FORM_GITHUB_REPO') ? trim(MY_LOGIN_FORM_GITHUB_REPO) : '';
        if (!$repo) {
            return null;
        }

        $transient_key = 'mlf_update_info_' . md5($repo);

        $cached = get_transient($transient_key);
        if ($cached !== false) {
            return $cached ?: null;
        }

        $response = wp_remote_get('https://api.github.com/repos/' . $repo . '/releases/latest', [
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                // GitHub's API rejects requests with no User-Agent.
                'User-Agent' => 'MyLoginForm-Updater/' . (defined('MY_LOGIN_FORM_VERSION') ? MY_LOGIN_FORM_VERSION : '1.0'),
            ],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            // Cache the miss briefly too (no release published yet, repo
            // unreachable, rate-limited, ...) so a bad response doesn't slow
            // down every single wp-admin page load.
            set_transient($transient_key, false, 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $release = json_decode(wp_remote_retrieve_body($response));
        if (!$release || empty($release->tag_name)) {
            set_transient($transient_key, false, 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $remote = $this->build_remote_object($repo, $release);

        set_transient($transient_key, $remote, 6 * HOUR_IN_SECONDS);
        return $remote;
    }

    /**
     * Maps a GitHub release API response onto the shape WordPress expects
     * from the update_plugins transient / plugins_api (version, package,
     * slug, name, sections, etc.).
     *
     * @param string   $repo
     * @param \stdClass $release
     * @return object
     */
    private function build_remote_object(string $repo, \stdClass $release) {
        $package = $release->zipball_url ?? '';

        // Prefer a hand-attached .zip release asset over the auto-generated
        // source zipball — an asset can be built to contain just the
        // plugin's files, while the zipball is the entire repo at that tag.
        if (!empty($release->assets) && is_array($release->assets)) {
            foreach ($release->assets as $asset) {
                if (!empty($asset->browser_download_url) && preg_match('/\.zip$/i', $asset->name ?? '')) {
                    $package = $asset->browser_download_url;
                    break;
                }
            }
        }

        $remote           = new \stdClass();
        $remote->name     = 'My Login Form';
        $remote->slug     = dirname(MY_LOGIN_FORM_BASENAME);
        $remote->plugin   = MY_LOGIN_FORM_BASENAME;
        $remote->version  = ltrim($release->tag_name, 'vV');
        $remote->url      = $release->html_url ?? ('https://github.com/' . $repo);
        $remote->package  = $package;
        $remote->author   = '<a href="https://omegadesign.io">Omega Design</a>';
        $remote->sections = [
            'description' => wpautop(esc_html($release->name ?? $remote->version)),
            'changelog'   => wpautop(wp_kses_post($release->body ?? '')),
        ];

        return $remote;
    }
}
