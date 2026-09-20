<?php
/**
 * My Login Form - Self-Hosted Update Checker (GitHub push-based)
 *
 * Gated by license validity: an expired or inactive license gets no update
 * information at all — no bug fixes, no security patches. This is the
 * enforcement that actually motivates renewals — everything else (feature
 * gating, admin nags) is secondary to this.
 *
 * Mirrors the Omega Design theme's github_updater.php: no separate update
 * server, and no GitHub Release/tag required. Bumping the Version header in
 * my-login-form.php and pushing to MY_LOGIN_FORM_GITHUB_BRANCH is enough —
 * this reads that exact commit's plugin header for the version and builds
 * an install package pinned to that same commit SHA, so the version WP
 * shows and the zip it actually installs can never drift apart even if the
 * branch moves on between the check and the eventual "Update Now" click.
 *
 * "Automatically available after a push" has two layers here:
 *  - Polling: the check result is cached for CACHE_TTL, so it's re-read
 *    from GitHub at most once per that window — same model WP.org updates
 *    use. Visiting any wp-admin page (or clicking "Check again" on the
 *    Updates screen) after that window has passed is enough to see it.
 *  - Instant refresh: register_webhook_endpoint() exposes a REST route a
 *    GitHub webhook (Settings > Webhooks on the repo) can hit on every
 *    push, which just clears the cache — so the very next admin page load
 *    re-checks GitHub instead of waiting out the cache window. Wiring the
 *    actual webhook up on GitHub's side (payload URL + secret, matching
 *    MY_LOGIN_FORM_GITHUB_WEBHOOK_SECRET) is optional; without it, updates
 *    still surface within CACHE_TTL.
 *
 * @package MyLoginForm\Licensing
 */

namespace MyLoginForm\Licensing;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Updater {

    const CACHE_KEY = 'mlf_github_update';
    const CACHE_TTL = 6 * HOUR_IN_SECONDS;
    const REST_NS   = 'my-login-form/v1';

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
        add_action('admin_init', [$this, 'maybe_bust_cache_on_manual_check']);
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
        add_action('upgrader_process_complete', [$this, 'clear_cache_after_update'], 10, 2);
    }

    /**
     * WP's own "Check again" button on the Updates screen sets force-check=1
     * and would otherwise still show our cached (possibly stale) result,
     * since that button only forces WP.org's own update_plugins check to
     * bypass its transient — it has no way to know about our separate cache.
     */
    public function maybe_bust_cache_on_manual_check() {
        if (!empty($_GET['force-check']) && current_user_can('update_plugins')) {
            delete_site_transient(self::CACHE_KEY);
        }
    }

    public function clear_cache_after_update($upgrader, $hook_extra) {
        if (isset($hook_extra['action'], $hook_extra['type']) && 'update' === $hook_extra['action'] && 'plugin' === $hook_extra['type']) {
            delete_site_transient(self::CACHE_KEY);
        }
    }

    /**
     * A GitHub webhook (content type application/json, secret shared with
     * MY_LOGIN_FORM_GITHUB_WEBHOOK_SECRET) can POST here on every push so
     * the next admin page load re-checks GitHub immediately instead of
     * waiting out CACHE_TTL. Only clears the cache — it never triggers the
     * actual update, which still needs an admin to click "Update Now" (or
     * WP's own auto-update system, if enabled for this plugin).
     */
    public function register_webhook_endpoint() {
        register_rest_route(self::REST_NS, '/deploy-webhook', [
            'methods'             => ['GET', 'POST'],
            'callback'            => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_webhook(\WP_REST_Request $request) {
        if (!defined('MY_LOGIN_FORM_GITHUB_WEBHOOK_SECRET') || '' === MY_LOGIN_FORM_GITHUB_WEBHOOK_SECRET) {
            return new \WP_REST_Response(['error' => 'Webhook secret not configured on this site.'], 403);
        }

        $signature = $request->get_header('x-hub-signature-256');
        $token     = $request->get_param('token');

        $authorized = false;

        if ($signature) {
            // GitHub's own webhook signing: HMAC-SHA256 of the raw body,
            // keyed with the same secret entered in the repo's webhook
            // settings — verified this way instead of a bare token so the
            // payload itself can't be forged even if the URL leaks.
            $expected = 'sha256=' . hash_hmac('sha256', $request->get_body(), MY_LOGIN_FORM_GITHUB_WEBHOOK_SECRET);
            $authorized = hash_equals($expected, $signature);
        } elseif ($token) {
            // Fallback for a manual/non-GitHub trigger (e.g. a CI step that
            // just curls this URL with ?token=... after deploying).
            $authorized = hash_equals(MY_LOGIN_FORM_GITHUB_WEBHOOK_SECRET, (string) $token);
        }

        if (!$authorized) {
            return new \WP_REST_Response(['error' => 'Invalid signature or token.'], 403);
        }

        delete_site_transient(self::CACHE_KEY);

        return new \WP_REST_Response(['cleared' => true], 200);
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
     * A GitHub archive zip — whether the zipball endpoint or a hand-built
     * asset — almost never extracts to a directory named "my-login-form",
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
     * Fetches (and briefly caches) info about the latest commit on
     * MY_LOGIN_FORM_GITHUB_BRANCH: that commit's SHA, the plugin Version
     * header read from that exact commit, and a package URL pinned to the
     * same SHA. Returns null on any failure — repo unreachable, no Version
     * header found, rate-limited, etc. must never break the plugin, it just
     * means no update shows up.
     *
     * @return object|null
     */
    private function get_remote_info() {
        $repo = defined('MY_LOGIN_FORM_GITHUB_REPO') ? trim(MY_LOGIN_FORM_GITHUB_REPO) : '';
        if (!$repo) {
            return null;
        }

        $cached = get_site_transient(self::CACHE_KEY);
        if ($cached !== false) {
            return $cached ?: null;
        }

        $branch = (defined('MY_LOGIN_FORM_GITHUB_BRANCH') && trim(MY_LOGIN_FORM_GITHUB_BRANCH))
            ? trim(MY_LOGIN_FORM_GITHUB_BRANCH)
            : 'main';

        $user_agent = 'MyLoginForm-Updater/' . (defined('MY_LOGIN_FORM_VERSION') ? MY_LOGIN_FORM_VERSION : '1.0');

        $commit_response = wp_remote_get(
            sprintf('https://api.github.com/repos/%s/commits/%s', $repo, $branch),
            [
                'timeout' => 10,
                'headers' => [
                    'Accept'     => 'application/vnd.github+json',
                    // GitHub's API rejects requests with no User-Agent.
                    'User-Agent' => $user_agent,
                ],
            ]
        );

        if (is_wp_error($commit_response) || wp_remote_retrieve_response_code($commit_response) !== 200) {
            // Cache the miss briefly too (repo unreachable, rate-limited,
            // branch renamed, ...) so a bad response doesn't slow down
            // every single wp-admin page load.
            set_site_transient(self::CACHE_KEY, false, 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $commit = json_decode(wp_remote_retrieve_body($commit_response), true);
        $sha    = $commit['sha'] ?? null;
        if (!$sha) {
            set_site_transient(self::CACHE_KEY, false, 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $main_file      = basename(MY_LOGIN_FORM_FILE);
        $header_response = wp_remote_get(
            sprintf('https://raw.githubusercontent.com/%s/%s/%s', $repo, $sha, $main_file),
            [
                'timeout' => 10,
                'headers' => ['User-Agent' => $user_agent],
            ]
        );

        if (is_wp_error($header_response) || wp_remote_retrieve_response_code($header_response) !== 200) {
            set_site_transient(self::CACHE_KEY, false, 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $version = null;
        if (preg_match('/^[ \t]*\*?[ \t]*Version:[ \t]*(.+)$/mi', wp_remote_retrieve_body($header_response), $m)) {
            $version = trim($m[1]);
        }

        if (!$version) {
            set_site_transient(self::CACHE_KEY, false, 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $remote = $this->build_remote_object($repo, $sha, $version);

        set_site_transient(self::CACHE_KEY, $remote, self::CACHE_TTL);
        return $remote;
    }

    /**
     * Maps a resolved commit (repo + sha + version) onto the shape
     * WordPress expects from the update_plugins transient / plugins_api
     * (version, package, slug, name, sections, etc.).
     *
     * @param string $repo
     * @param string $sha
     * @param string $version
     * @return object
     */
    private function build_remote_object(string $repo, string $sha, string $version) {
        $short_sha = substr($sha, 0, 7);

        $remote           = new \stdClass();
        $remote->name     = 'My Login Form';
        $remote->slug     = dirname(MY_LOGIN_FORM_BASENAME);
        $remote->plugin   = MY_LOGIN_FORM_BASENAME;
        $remote->version  = $version;
        $remote->url      = 'https://github.com/' . $repo;
        // Not https://github.com/{repo}/archive/{sha}.zip — for a private
        // repo that 302s to codeload.github.com, and WP's HTTP client (like
        // most, including plain curl) strips the Authorization header on a
        // cross-host redirect, so the follow-up request 404s even with a
        // valid token. The REST zipball endpoint instead redirects to a
        // codeload URL with a short-lived signed token already in its query
        // string, so the follow-up needs no header at all.
        $remote->package  = sprintf('https://api.github.com/repos/%s/zipball/%s', $repo, $sha);
        $remote->author   = '<a href="https://omegadesign.io">Omega Design</a>';
        $remote->sections = [
            'description' => wpautop(sprintf(
                /* translators: %s: short commit SHA */
                esc_html__('Latest code from the repository at commit %s.', 'my-login-form'),
                '<code>' . esc_html($short_sha) . '</code>'
            )),
            'changelog'   => wpautop(sprintf(
                /* translators: 1: commit URL, 2: short commit SHA */
                wp_kses_post(__('See <a href="%1$s">commit %2$s</a> on GitHub for what changed.', 'my-login-form')),
                esc_url('https://github.com/' . $repo . '/commit/' . $sha),
                esc_html($short_sha)
            )),
        ];

        return $remote;
    }
}
