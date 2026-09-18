<?php
/**
 * My Login Form - License Client
 *
 * Talks to the licensing Edge Function — its source is kept outside this
 * repo, in the store-only my-login-form-licensing-backend project (see
 * functions/license-api/index.ts there) — over HTTPS and caches the result
 * in WordPress options. This class is the
 * *only* thing on a customer's site that knows anything about licensing —
 * it never talks to Supabase's database directly and never sees the
 * service_role key, only the Edge Function's public URL
 * (MY_LOGIN_FORM_LICENSE_API_URL, defined in my-login-form.php).
 *
 * @package MyLoginForm\Licensing
 */

namespace MyLoginForm\Licensing;

// Prevent Direct Access
defined('ABSPATH') || exit;

class License {

    /**
     * Instance of this class
     *
     * @var License|null
     */
    private static $instance = null;

    /**
     * How long we'll keep trusting a previously-valid license after we stop
     * being able to reach the license server — a failed API call (host
     * blocking outbound requests, a bad five minutes on Supabase's end, a
     * wrong server clock) is a shrug, not a verdict. Only a *definitive*
     * rejection from the server (expired/revoked/domain not activated)
     * invalidates immediately, with no grace period.
     */
    const GRACE_PERIOD_SECONDS = 7 * DAY_IN_SECONDS;

    /**
     * Get singleton instance
     *
     * @return License
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Register the daily re-validation cron.
     *
     * @return void
     */
    public function init(): void {
        add_action('my_login_form_license_daily_check', [$this, 'validate_license']);

        if (!wp_next_scheduled('my_login_form_license_daily_check')) {
            wp_schedule_event(time(), 'daily', 'my_login_form_license_daily_check');
        }
    }

    /**
     * The licensing Edge Function URL. The my_login_form_license_api_url
     * option (set from the License admin page's Advanced section) wins if
     * present; the MY_LOGIN_FORM_LICENSE_API_URL constant in my-login-form.php
     * is the fallback for anyone who prefers baking it in at build time.
     *
     * @return string
     */
    public function get_api_url(): string {
        $option = get_option('my_login_form_license_api_url', '');
        if ($option) {
            return $option;
        }
        return defined('MY_LOGIN_FORM_LICENSE_API_URL') ? MY_LOGIN_FORM_LICENSE_API_URL : '';
    }

    /**
     * Whether this site currently has a valid, active license. This is the
     * single gate every premium code path (Includes/Licensing/Gate.php)
     * checks before doing anything.
     *
     * @return bool
     */
    public function is_active(): bool {
        // No API URL configured yet means licensing hasn't been set up on
        // this build at all (this is only ever true on a developer/test
        // copy — a real shipped copy always has the URL baked in before it
        // goes to a customer). Gating everything while there's no server to
        // even check against would just lock the developer out of their own
        // site, so treat "not configured" as "not enforced" rather than
        // "always invalid".
        if ($this->get_api_url() === '') {
            return true;
        }

        // Require key AND email together, not just key — a genuinely
        // successful activate() always sets both in the same atomic step
        // (see activate() below), so the two can only end up out of sync
        // if the stored options are incomplete/corrupted for some other
        // reason. Treating that as "not active" sends the License page back
        // to the plain activation form instead of showing a confusing
        // "Active" screen with blank fields.
        $key   = get_option('my_login_form_license_key', '');
        $email = get_option('my_login_form_license_email', '');
        if (!$key || !$email) {
            return false;
        }
        if (get_option('my_login_form_license_status', 'inactive') !== 'active') {
            return false;
        }
        // A version bump since the admin last confirmed this key holds
        // premium features off (same gate as everything else — see Gate.php)
        // until they explicitly re-confirm on the License card.
        return !$this->needs_reconfirmation();
    }

    /**
     * Whether the site has an active-per-the-server license, but the admin
     * hasn't yet re-confirmed it since the plugin was updated to its
     * current version. Checked independently of is_active() (which folds
     * this in) so the License card and admin notice can tell "needs
     * reconfirmation" apart from "never licensed at all".
     *
     * @return bool
     */
    public function needs_reconfirmation(): bool {
        if ($this->get_api_url() === '') {
            return false;
        }

        $key   = get_option('my_login_form_license_key', '');
        $email = get_option('my_login_form_license_email', '');
        if (!$key || !$email) {
            return false;
        }
        if (get_option('my_login_form_license_status', 'inactive') !== 'active') {
            return false;
        }

        $confirmed = get_option('my_login_form_license_confirmed_version', '');
        $current   = defined('MY_LOGIN_FORM_VERSION') ? MY_LOGIN_FORM_VERSION : '';

        if ($confirmed === '') {
            // No confirmation on record — either this site activated before
            // this feature existed, or activate() hasn't run yet for some
            // other reason. Self-heal to "confirmed at the current version"
            // rather than retroactively locking out an already-active
            // customer who did nothing wrong.
            update_option('my_login_form_license_confirmed_version', $current, false);
            return false;
        }

        return $confirmed !== $current;
    }

    /**
     * Normalize a domain/URL for comparison: strip protocol, "www.", and any
     * trailing slash, lowercase. Mirrors the Edge Function's normalizeDomain()
     * exactly (see the store-only my-login-form-licensing-backend project's
     * functions/license-api/index.ts) — normalizing identically on
     * both ends means a logic difference between them can never itself cause
     * a false "different site" mismatch.
     *
     * @param string $input
     * @return string
     */
    public function normalize_domain(string $input): string {
        $domain = strtolower(trim($input));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        return rtrim($domain, '/');
    }

    /**
     * Activate a license key for this site. Called from the License admin
     * screen when the buyer submits their email + key.
     *
     * @param string $email
     * @param string $key
     * @return array{success: bool, message: string}
     */
    public function activate(string $email, string $key): array {
        $key   = trim($key);
        $email = sanitize_email($email);

        if (!$key || !$email) {
            return ['success' => false, 'message' => __('Please enter your email and license key.', 'my-login-form')];
        }

        $response = $this->call_api('activate', [
            'license_key' => $key,
            'domain'      => $this->normalize_domain(home_url()),
            'email'       => $email,
            'product'     => 'my-login-form',
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => __('Could not reach the license server. Please try again in a moment.', 'my-login-form')];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300 && !empty($body['valid'])) {
            // autoload=false throughout — these hold the customer's license
            // key/email and shouldn't be loaded into memory on every request.
            update_option('my_login_form_license_key', $key, false);
            update_option('my_login_form_license_email', $body['email'] ?? $email, false);
            update_option('my_login_form_license_plan', $body['plan'] ?? '', false);
            update_option('my_login_form_license_expires', $body['expires_at'] ?? '', false);
            update_option('my_login_form_license_status', 'active', false);
            update_option('my_login_form_license_last_valid_at', time(), false);
            update_option('my_login_form_license_confirmed_version', defined('MY_LOGIN_FORM_VERSION') ? MY_LOGIN_FORM_VERSION : '', false);

            return ['success' => true, 'message' => __('License activated! All features are now unlocked.', 'my-login-form')];
        }

        $error = is_array($body) && !empty($body['error']) ? $body['error'] : __('This license key is not valid.', 'my-login-form');
        return ['success' => false, 'message' => $error];
    }

    /**
     * Re-confirm an already-active license after a plugin update. Unlike
     * validate_license() (the silent daily cron check), this always talks
     * to the server synchronously and only clears the reconfirmation gate
     * (needs_reconfirmation()) on a definitive "still valid" answer — a
     * failed/unreachable call leaves the gate up rather than guessing.
     *
     * @return array{success: bool, message: string}
     */
    public function reconfirm(): array {
        $key = get_option('my_login_form_license_key', '');
        if (!$key) {
            return ['success' => false, 'message' => __('No license key on file.', 'my-login-form')];
        }

        $response = $this->call_api('validate', [
            'license_key' => $key,
            'domain'      => $this->normalize_domain(home_url()),
            'product'     => 'my-login-form',
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => __('Could not reach the license server. Please try again in a moment.', 'my-login-form')];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300 && !empty($body['valid'])) {
            update_option('my_login_form_license_status', 'active', false);
            update_option('my_login_form_license_last_valid_at', time(), false);
            update_option('my_login_form_license_confirmed_version', defined('MY_LOGIN_FORM_VERSION') ? MY_LOGIN_FORM_VERSION : '', false);
            if (isset($body['expires_at'])) {
                update_option('my_login_form_license_expires', $body['expires_at'] ?? '', false);
            }
            return ['success' => true, 'message' => __('License re-confirmed. All features are unlocked.', 'my-login-form')];
        }

        if ($code >= 400 && $code < 500) {
            update_option('my_login_form_license_status', 'inactive', false);
        }

        $error = is_array($body) && !empty($body['error']) ? $body['error'] : __('This license could not be re-confirmed.', 'my-login-form');
        return ['success' => false, 'message' => $error];
    }

    /**
     * Free up this domain's activation slot and clear all locally-cached
     * license data. Best-effort against the server — even if that call
     * fails, local state is cleared anyway so the admin's explicit
     * "deactivate" request always takes effect on this site.
     *
     * @return array{success: bool, message: string}
     */
    public function deactivate(): array {
        $key = get_option('my_login_form_license_key', '');

        if ($key) {
            $this->call_api('deactivate', [
                'license_key' => $key,
                'domain'      => $this->normalize_domain(home_url()),
            ]);
        }

        delete_option('my_login_form_license_key');
        delete_option('my_login_form_license_email');
        delete_option('my_login_form_license_plan');
        delete_option('my_login_form_license_expires');
        delete_option('my_login_form_license_status');
        delete_option('my_login_form_license_last_valid_at');
        delete_option('my_login_form_license_confirmed_version');

        return ['success' => true, 'message' => __('License deactivated. This site no longer counts toward your activation limit.', 'my-login-form')];
    }

    /**
     * Daily WP-Cron re-check. Definitive server answers (valid, or a clear
     * expired/revoked/not-activated rejection) apply immediately. Anything
     * that looks like the server being unreachable falls back to the grace
     * period instead of instantly invalidating a paying customer's site.
     *
     * @return void
     */
    public function validate_license(): void {
        $key = get_option('my_login_form_license_key', '');
        if (!$key) {
            return;
        }

        $response = $this->call_api('validate', [
            'license_key' => $key,
            'domain'      => $this->normalize_domain(home_url()),
            'product'     => 'my-login-form',
        ]);

        if (is_wp_error($response)) {
            $this->maybe_expire_after_grace_period();
            return;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300 && !empty($body['valid'])) {
            update_option('my_login_form_license_status', 'active', false);
            update_option('my_login_form_license_last_valid_at', time(), false);
            if (isset($body['expires_at'])) {
                update_option('my_login_form_license_expires', $body['expires_at'] ?? '', false);
            }
            return;
        }

        if ($code >= 400 && $code < 500) {
            // The server answered clearly (expired/revoked/domain not
            // activated/key not found) — trust it immediately, no grace period.
            update_option('my_login_form_license_status', 'inactive', false);
            return;
        }

        // 5xx, timeout-as-non-WP_Error, or malformed body — treat the same
        // as unreachable.
        $this->maybe_expire_after_grace_period();
    }

    /**
     * @return void
     */
    private function maybe_expire_after_grace_period(): void {
        $last_valid_at = (int) get_option('my_login_form_license_last_valid_at', 0);

        if (!$last_valid_at) {
            // Never had a successful check to begin with — nothing to be
            // lenient about.
            update_option('my_login_form_license_status', 'inactive', false);
            return;
        }

        if ((time() - $last_valid_at) > self::GRACE_PERIOD_SECONDS) {
            update_option('my_login_form_license_status', 'inactive', false);
        }
        // Still within the grace period — leave the cached status as-is.
    }

    /**
     * Days remaining until expiry, for the renewal-nag admin notices
     * (30/14/7 days out). Null for a lifetime license (never expires) or
     * when there's no active license at all.
     *
     * @return int|null
     */
    public function days_until_expiry(): ?int {
        $expires = get_option('my_login_form_license_expires', '');
        if (!$expires) {
            return null;
        }
        $seconds = strtotime($expires) - time();
        return (int) ceil($seconds / DAY_IN_SECONDS);
    }

    /**
     * All locally-cached license fields, for the admin screen.
     *
     * @return array
     */
    public function get_status_data(): array {
        return [
            'key'              => get_option('my_login_form_license_key', ''),
            'email'            => get_option('my_login_form_license_email', ''),
            'plan'             => get_option('my_login_form_license_plan', ''),
            'expires'          => get_option('my_login_form_license_expires', ''),
            'status'           => get_option('my_login_form_license_status', 'inactive'),
            'last_valid_at'    => (int) get_option('my_login_form_license_last_valid_at', 0),
            'days_until_expiry' => $this->days_until_expiry(),
        ];
    }

    /**
     * A short, non-reversible hint for the license key — enough for an
     * admin to recognize "yes, this is my key", never enough to reconstruct
     * or reuse it if the admin screen is ever screenshotted.
     *
     * @param string $key
     * @return string
     */
    public function mask_key(string $key): string {
        if ($key === '') {
            return '';
        }
        $parts = explode('-', $key);
        if (count($parts) < 2) {
            return str_repeat('•', max(4, strlen($key) - 4)) . substr($key, -4);
        }
        $last = array_pop($parts);
        return str_repeat('XXXX-', count($parts)) . $last;
    }

    /**
     * @param string $action  'activate' | 'validate' | 'deactivate'
     * @param array  $payload
     * @return array|\WP_Error wp_remote_post()'s return value
     */
    private function call_api(string $action, array $payload) {
        $url = $this->get_api_url();

        if (!$url) {
            return new \WP_Error('no_license_api_url', __('License API URL is not configured.', 'my-login-form'));
        }

        $payload['action'] = $action;

        $args = [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($payload),
            'timeout' => 15,
        ];

        // Supabase's edge network can take a few minutes to fully propagate
        // a function's config (e.g. "Verify JWT" off) across every region
        // after a deploy. During that window some requests get rejected by
        // Supabase's own platform gateway — before ever reaching this
        // function's code — with a distinct {"code":"UNAUTHORIZED_NO_AUTH_HEADER",...}
        // shape, instead of this function's own {"valid":...}/{"license_key":...}
        // shape. That's a transient routing inconsistency, not a real
        // rejection, so retry a couple of times before treating it as a
        // hard failure — the same "a failed call is a shrug, not a verdict"
        // principle validate_license()'s grace period already applies.
        $max_attempts = 3;
        $response     = null;

        for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                return $response;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $is_gateway_rejection = is_array($body)
                && isset($body['code'])
                && !array_key_exists('valid', $body)
                && !array_key_exists('license_key', $body);

            if (!$is_gateway_rejection || $attempt === $max_attempts) {
                return $response;
            }

            usleep(300000); // 300ms before retrying
        }

        return $response;
    }
}
