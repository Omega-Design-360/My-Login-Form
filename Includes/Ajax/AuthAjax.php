<?php
/**
 * Authentication AJAX Handler
 *
 * @package MyLoginForm\Ajax
 */

namespace MyLoginForm\Ajax;

defined('ABSPATH') || exit;

class AuthAjax {

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
        add_action('wp_ajax_nopriv_my_login_form_submit', [$this, 'handle_form_submit']);
        add_action('wp_ajax_my_login_form_submit',        [$this, 'handle_form_submit']);
        add_action('wp_ajax_nopriv_my_login_form_verify_otp', [$this, 'handle_verify_otp']);
        add_action('wp_ajax_my_login_form_verify_otp',        [$this, 'handle_verify_otp']);
        add_action('wp_ajax_nopriv_my_login_form_resend_otp', [$this, 'handle_resend_otp']);
        add_action('wp_ajax_my_login_form_resend_otp',        [$this, 'handle_resend_otp']);

        // Every session started through this plugin (password login, OTP
        // verify, registration auto-login, password-reset auto-login) should
        // hold for a week to ten days instead of WP core's default 2-day
        // cookie — "remember me" being unchecked/hidden shouldn't mean
        // re-logging in every few hours.
        add_filter('auth_cookie_expiration', [$this, 'extend_auth_cookie_expiration'], 10, 3);

        // Tracks last-login time and login count for every user (not just
        // ones signing in through this plugin's own form) — the Users Data
        // "View Details" modal and the Dashboard's Active Users stat both
        // read these mlf_last_login/mlf_login_count meta keys already.
        add_action('wp_login', [$this, 'track_user_login'], 10, 2);
    }

    public function extend_auth_cookie_expiration($expiration, $user_id, $remember) {
        return $remember ? 10 * DAY_IN_SECONDS : 7 * DAY_IN_SECONDS;
    }

    public function track_user_login(string $user_login, \WP_User $user): void {
        update_user_meta($user->ID, 'mlf_last_login', current_time('mysql'));
        update_user_meta($user->ID, 'mlf_login_count', (int) get_user_meta($user->ID, 'mlf_login_count', true) + 1);
    }

    /**
     * Records an OTP event (sent/send_failed/verified/failed) in the audit
     * log table (Includes/Database/OtpDatabase.php). Never lets a logging
     * failure interrupt the actual auth flow.
     *
     * @param string $email
     * @param string $context 'register' | 'login' | 'forgot_password'
     * @param string $action  'sent' | 'send_failed' | 'verified' | 'failed'
     */
    private function log_otp_event(string $email, string $context, string $action): void {
        $otp_db = \MyLoginForm\Database\Database::get_instance()->otp();
        if ($otp_db) {
            $otp_db->log($email, $context, $action);
        }
    }

    private function check_blocklists(): void {
        // Check IP blocklist
        $blocked_ips = get_option('my_login_form_blocked_ips', '');
        if ($blocked_ips) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $list = array_filter(array_map('trim', explode("\n", $blocked_ips)));
            if ($ip && in_array($ip, $list, true)) {
                wp_send_json_error(__('Access denied.', 'my-login-form'));
            }
        }

        // Check username/email blocklist
        $blocked_users = get_option('my_login_form_blocked_users', '');
        if ($blocked_users) {
            $list    = array_filter(array_map(function($v) { return strtolower(trim($v)); }, explode("\n", $blocked_users)));
            $subject = strtolower(
                $_POST['username']   ??
                $_POST['user_login'] ??
                $_POST['email']      ??
                $_POST['user_email'] ??
                ''
            );
            if ($subject && in_array($subject, $list, true)) {
                wp_send_json_error(__('Access denied.', 'my-login-form'));
            }
        }
    }

    private function sync_to_supabase(int $user_id): void {
        $url     = get_option('my_login_supabase_url', '');
        // Writes here use the service_role key, not anon — the my_login_users
        // table's RLS policies (supabase/sql/01_users.sql) grant the anon
        // role nothing at all, so an anon-keyed request would just fail.
        // Only service_role (kept server-side, never sent to the browser)
        // can write to it.
        $service_key = get_option('my_login_supabase_service_key', '');
        $enabled     = get_option('my_login_supabase_enabled', 0);

        if (!$enabled || !$url || !$service_key) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) return;

        // Extra profile detail (supabase/sql/01_users.sql) — never anything
        // sensitive; password hashes, 2FA secrets, and reset keys never
        // leave WordPress.
        $phone = get_user_meta($user_id, 'billing_phone', true) ?: get_user_meta($user_id, 'phone', true);

        $payload = [
            'wp_user_id'      => $user_id,
            'email'           => $user->user_email,
            'display_name'    => $user->display_name,
            'first_name'      => get_user_meta($user_id, 'first_name', true),
            'last_name'       => get_user_meta($user_id, 'last_name', true),
            'username'        => $user->user_login,
            'phone'           => $phone,
            'country'         => get_user_meta($user_id, 'billing_country', true),
            'city'            => get_user_meta($user_id, 'billing_city', true),
            'address'         => get_user_meta($user_id, 'billing_address_1', true),
            'social_provider' => get_user_meta($user_id, 'mlf_social_provider', true),
            'profile_picture' => get_avatar_url($user_id),
            'email_verified'  => get_user_meta($user_id, 'mlf_email_verified', true) !== '0',
            'updated_at'      => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        $host = parse_url($url, PHP_URL_HOST);
        $ssl  = !in_array($host, ['localhost', '127.0.0.1', '::1'], true);

        wp_remote_post(trailingslashit($url) . 'rest/v1/my_login_users', [
            'headers' => [
                'apikey'        => $service_key,
                'Authorization' => 'Bearer ' . $service_key,
                'Content-Type'  => 'application/json',
                'Prefer'        => 'resolution=merge-duplicates',
            ],
            'body'      => wp_json_encode($payload),
            'timeout'   => 10,
            'sslverify' => $ssl,
        ]);
    }

    /**
     * Whether new registrations must verify their email with a Supabase-sent
     * OTP before they can log in. Requires the "Require Email Verification"
     * setting AND a fully configured Supabase connection (enabled, with a
     * URL and anon key actually saved) — without Supabase there is no way to
     * send the code, so OTP is treated as unavailable rather than blocking
     * registration outright.
     */
    private function otp_required(): bool {
        if (!get_option('my_login_form_email_verification', 1)) {
            return false;
        }
        return (bool) get_option('my_login_supabase_enabled', 0)
            && (bool) get_option('my_login_supabase_url', '')
            && (bool) get_option('my_login_supabase_anon_key', '');
    }

    /**
     * Ask Supabase Auth to email a 6-digit OTP code to $email, creating a
     * matching Supabase Auth user if one doesn't exist yet. Requires the
     * project's "Confirm signup" email template to use {{ .Token }} —
     * otherwise Supabase sends a magic link instead of a code.
     */
    private function send_supabase_email_otp(string $email): bool {
        $url  = get_option('my_login_supabase_url', '');
        $anon = get_option('my_login_supabase_anon_key', '');
        if (!$url || !$anon) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $ssl  = !in_array($host, ['localhost', '127.0.0.1', '::1'], true);

        $response = wp_remote_post(trailingslashit($url) . 'auth/v1/otp', [
            'headers'   => [
                'apikey'       => $anon,
                'Content-Type' => 'application/json',
            ],
            'body'      => wp_json_encode([
                'email'       => $email,
                'create_user' => true,
            ]),
            'timeout'   => 10,
            'sslverify' => $ssl,
        ]);

        if (is_wp_error($response)) {
            if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
                error_log('My Login Form OTP: send failed - ' . $response->get_error_message());
            }
            return false;
        }
        $code = wp_remote_retrieve_response_code($response);
        if (($code < 200 || $code >= 300) && defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('My Login Form OTP: Supabase returned ' . $code . ' - ' . wp_remote_retrieve_body($response));
        }
        return $code >= 200 && $code < 300;
    }

    /**
     * Verify a code the user typed in against Supabase Auth.
     */
    private function verify_supabase_email_otp(string $email, string $token): bool {
        $url  = get_option('my_login_supabase_url', '');
        $anon = get_option('my_login_supabase_anon_key', '');
        if (!$url || !$anon) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $ssl  = !in_array($host, ['localhost', '127.0.0.1', '::1'], true);

        $response = wp_remote_post(trailingslashit($url) . 'auth/v1/verify', [
            'headers'   => [
                'apikey'       => $anon,
                'Content-Type' => 'application/json',
            ],
            'body'      => wp_json_encode([
                'email' => $email,
                'token' => $token,
                'type'  => 'email',
            ]),
            'timeout'   => 10,
            'sslverify' => $ssl,
        ]);

        if (is_wp_error($response)) {
            return false;
        }
        $code = wp_remote_retrieve_response_code($response);
        return $code >= 200 && $code < 300;
    }

    /**
     * Where the Login page lives, for flows that intentionally do NOT
     * auto-login (registration completion, password reset) and instead
     * send the visitor to sign in fresh. Falls back to wp-login.php if no
     * Login page is tracked.
     */
    private function resolve_login_page_url(): string {
        $login_page_id = (int) get_option('my_login_form_login_page_id');
        if ($login_page_id) {
            $existing = get_post($login_page_id);
            if ($existing && 'page' === $existing->post_type && 'trash' !== $existing->post_status) {
                return get_permalink($login_page_id);
            }
        }
        return wp_login_url();
    }

    /**
     * Verify the OTP code typed in for one of three flows, distinguished by
     * the 'context' field the OTP form submits alongside it:
     *
     *  - register: finishes account verification. Does NOT log the user in —
     *    sends them to the Login page to sign in fresh (which itself now
     *    requires its own OTP — see handle_login()).
     *  - login: the credential check already passed in handle_login(); this
     *    is the second factor. Establishes the session now.
     *  - forgot_password: proves the visitor owns the inbox. Generates a
     *    core password-reset key (get_password_reset_key()) — the same
     *    mechanism the old emailed-link flow used — and sends them straight
     *    to the Reset Password page with it, skipping the "wait for an
     *    email" step since the OTP already proved ownership.
     */
    public function handle_verify_otp(): void {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            wp_send_json_error(__('This feature requires an active My Login Form license.', 'my-login-form'));
        }

        if (
            !isset($_POST['my_login_form_nonce']) ||
            !wp_verify_nonce($_POST['my_login_form_nonce'], 'my_login_form_submit')
        ) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'my-login-form'));
        }

        $email   = sanitize_email($_POST['email'] ?? '');
        $token   = sanitize_text_field($_POST['token'] ?? '');
        $context = sanitize_text_field($_POST['context'] ?? 'login');

        if (empty($email) || empty($token)) {
            wp_send_json_error(__('Please enter the verification code.', 'my-login-form'));
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error(__('We couldn\'t find an account for this email.', 'my-login-form'));
        }

        if (!$this->verify_supabase_email_otp($email, $token)) {
            $this->log_otp_event($email, $context, 'failed');
            wp_send_json_error(__('That code is incorrect or has expired. Please try again or resend the code.', 'my-login-form'));
        }

        $this->log_otp_event($email, $context, 'verified');

        if ('forgot_password' === $context) {
            $key = get_password_reset_key($user);
            if (is_wp_error($key)) {
                wp_send_json_error(__('Something went wrong generating your reset link. Please try again.', 'my-login-form'));
            }

            $reset_page_id = (int) get_option('my_login_form_reset_password_page_id');
            $reset_url     = $reset_page_id ? get_permalink($reset_page_id) : network_site_url('wp-login.php?action=rp', 'login');
            $reset_url     = add_query_arg([
                'key'   => $key,
                'login' => rawurlencode($user->user_login),
            ], $reset_url);

            wp_send_json_success([
                'message'  => __('Code verified! Please choose a new password.', 'my-login-form'),
                'redirect' => $reset_url,
            ]);
        }

        update_user_meta($user->ID, 'mlf_email_verified', 1);

        if ('register' === $context) {
            $this->sync_to_supabase($user->ID);
            wp_send_json_success([
                'message'  => __('Email verified! Please log in to continue.', 'my-login-form'),
                'redirect' => $this->resolve_login_page_url(),
            ]);
        }

        // 'login' (or anything else): this OTP is the second factor after an
        // already-correct password — establish the session now.
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        $this->sync_to_supabase($user->ID);

        $redirect = wp_validate_redirect(esc_url_raw($_POST['redirect_to'] ?? ''), home_url());

        wp_send_json_success([
            'message'  => __('Login successful! Redirecting…', 'my-login-form'),
            'redirect' => $redirect,
        ]);
    }

    /**
     * Resend the OTP code. Responds with the same success message whether
     * or not the account exists, so this can't be used to enumerate
     * registered emails.
     */
    public function handle_resend_otp(): void {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            wp_send_json_error(__('This feature requires an active My Login Form license.', 'my-login-form'));
        }

        if (
            !isset($_POST['my_login_form_nonce']) ||
            !wp_verify_nonce($_POST['my_login_form_nonce'], 'my_login_form_submit')
        ) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'my-login-form'));
        }

        $email   = sanitize_email($_POST['email'] ?? '');
        $context = sanitize_text_field($_POST['context'] ?? 'login');
        if (empty($email)) {
            wp_send_json_error(__('Missing email address.', 'my-login-form'));
        }

        $user = get_user_by('email', $email);
        // Rate-limited per target email, not per IP — an attacker rotating
        // IPs must not be able to email-bomb the same inbox. The response
        // stays identical whether this account doesn't exist, was already
        // rate-limited, or a code was actually just sent, so none of those
        // are distinguishable from the outside (keeps the existing
        // no-enumeration behavior below intact).
        if ($user && my_login_form_rate_limit('otp_resend', $email, 3, 10 * MINUTE_IN_SECONDS)) {
            $sent = $this->send_supabase_email_otp($email);
            $this->log_otp_event($email, $context, $sent ? 'sent' : 'send_failed');
        }

        wp_send_json_success(['message' => __('If that account needs verification, a new code has been sent.', 'my-login-form')]);
    }

    public function handle_form_submit(): void {
        // Defense in depth: the shortcode itself already refuses to render a
        // usable form when unlicensed (Includes/Licensing/Gate.php), but this
        // stops a direct POST to admin-ajax.php from bypassing that.
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            wp_send_json_error(__('This feature requires an active My Login Form license.', 'my-login-form'));
        }

        if (
            !isset($_POST['my_login_form_nonce']) ||
            !wp_verify_nonce($_POST['my_login_form_nonce'], 'my_login_form_submit')
        ) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'my-login-form'));
        }

        $this->check_blocklists();

        $form_id   = intval($_POST['form_id']   ?? 0);
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');

        if (!$form_id) {
            wp_send_json_error(__('Invalid form.', 'my-login-form'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
        $form  = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id));

        if (!$form) {
            wp_send_json_error(__('Form not found.', 'my-login-form'));
        }

        $form_type = $form->form_type ?: $form_type;

        switch ($form_type) {
            case 'login':
                $this->handle_login();
                break;
            case 'register':
                $this->handle_register();
                break;
            case 'forgot_password':
                $this->handle_forgot_password();
                break;
            case 'reset_password':
                $this->handle_reset_password();
                break;
            default:
                $this->handle_custom_form($form);
                break;
        }
    }

    private function handle_login(): void {
        $username = sanitize_text_field(
            $_POST['username']   ??
            $_POST['user_login'] ??
            $_POST['email']      ??
            ''
        );
        $password = $_POST['password'] ?? $_POST['user_pass'] ?? '';
        $remember = !empty($_POST['remember_me']);

        if (empty($username) || empty($password)) {
            wp_send_json_error(__('Please enter your username/email and password.', 'my-login-form'));
        }

        // Brute-force guard: keyed on IP + the submitted username/email so
        // one attacker IP can't grind through a single account's password
        // space, without locking out everyone behind a shared/NAT'd IP for
        // an unrelated account. Counts every attempt (not just failures) —
        // 10 per 15 minutes is generous enough that no legitimate user
        // mistyping a password a few times will ever notice it.
        $attempt_key = my_login_form_client_ip() . '|' . strtolower($username);
        if (!my_login_form_rate_limit('login_attempt', $attempt_key, 10, 15 * MINUTE_IN_SECONDS)) {
            wp_send_json_error(__('Too many login attempts. Please try again in a few minutes.', 'my-login-form'));
        }

        // Allow login with email address
        if (is_email($username)) {
            $user_obj = get_user_by('email', $username);
            if ($user_obj) {
                $username = $user_obj->user_login;
            }
        }

        $user = wp_signon([
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ], is_ssl());

        if (is_wp_error($user)) {
            $code = $user->get_error_code();
            // Keep error message generic to avoid leaking valid usernames
            if (in_array($code, ['invalid_username', 'invalid_email', 'incorrect_password'], true)) {
                wp_send_json_error(__('Incorrect username/email or password.', 'my-login-form'));
            }
            wp_send_json_error($user->get_error_message());
        }

        // Password check passed. When Supabase/OTP is configured, every
        // login (not just first-time/unverified accounts) requires a second
        // factor — undo the session wp_signon() just started and make them
        // prove the code before it's re-established in handle_verify_otp().
        if ($this->otp_required()) {
            wp_logout();
            // Shares the same per-email limit as the explicit "Resend code"
            // button, so an attacker can't get around one by using the other.
            $sent = my_login_form_rate_limit('otp_resend', $user->user_email, 3, 10 * MINUTE_IN_SECONDS)
                ? $this->send_supabase_email_otp($user->user_email)
                : false;
            $this->log_otp_event($user->user_email, 'login', $sent ? 'sent' : 'send_failed');
            wp_send_json_error([
                'message'      => __('Enter the verification code we just emailed you to finish logging in.', 'my-login-form'),
                'otp_required' => true,
                'email'        => $user->user_email,
            ]);
        }

        $redirect = wp_validate_redirect(esc_url_raw($_POST['redirect_to'] ?? ''), '');
        if (!$redirect) {
            // Task 2: no redirect_to posted - admins still go to wp-admin,
            // but everyone else lands on WooCommerce's My Account page
            // (Woo's own default dashboard/orders/addresses) when Woo is
            // active, falling back to the home page otherwise.
            if (user_can($user, 'manage_options')) {
                $redirect = admin_url();
            } elseif (function_exists('wc_get_page_permalink') && wc_get_page_permalink('myaccount')) {
                $redirect = wc_get_page_permalink('myaccount');
            } else {
                $redirect = home_url();
            }
        }

        $this->sync_to_supabase($user->ID);

        wp_send_json_success([
            'message'  => __('Login successful! Redirecting…', 'my-login-form'),
            'redirect' => $redirect,
        ]);
    }

    private function handle_register(): void {
        if (!get_option('my_login_form_allow_registration', get_option('users_can_register'))) {
            wp_send_json_error(__('User registration is currently disabled.', 'my-login-form'));
        }

        $email     = sanitize_email($_POST['email'] ?? $_POST['user_email'] ?? '');
        $username  = sanitize_user($_POST['username'] ?? $_POST['user_login'] ?? '');
        $password  = $_POST['password'] ?? $_POST['user_pass'] ?? '';
        $firstname = sanitize_text_field($_POST['first_name'] ?? '');
        $lastname  = sanitize_text_field($_POST['last_name']  ?? '');

        // The form builder offers draggable "Billing First/Last Name" and
        // "Billing Email" fields (Admin/Pages/designer.php) for sites that
        // want WooCommerce customer data collected at registration — fall
        // back to the plain name/email fields above when a form doesn't
        // include the billing-specific ones.
        $billing_firstname = sanitize_text_field($_POST['billing_first_name'] ?? $firstname);
        $billing_lastname  = sanitize_text_field($_POST['billing_last_name']  ?? $lastname);
        $billing_email     = sanitize_email($_POST['billing_email'] ?? $email);

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'my-login-form'));
        }

        if (!$this->has_real_email_domain($email)) {
            wp_send_json_error(__('That email domain doesn\'t look like it can receive mail. Please check for a typo.', 'my-login-form'));
        }

        if (email_exists($email)) {
            wp_send_json_error(__('An account with this email already exists.', 'my-login-form'));
        }

        // Auto-generate username from email if not supplied
        if (empty($username)) {
            $username = sanitize_user(current(explode('@', $email)));
        }

        // Ensure username is unique
        $base = $username;
        $i    = 1;
        while (username_exists($username)) {
            $username = $base . $i++;
        }

        // Only validate a password the visitor actually typed — one left
        // blank is auto-generated below, so there's nothing of theirs to
        // check length/match against.
        if ($password !== '') {
            if (strlen($password) < 8) {
                wp_send_json_error(__('Password must be at least 8 characters long.', 'my-login-form'));
            }

            // The register form's "Confirm Password" field was never
            // actually checked server-side — a mismatched confirmation
            // silently registered the account with just $password anyway.
            $confirm = $_POST['confirm_password'] ?? $_POST['password_confirm'] ?? null;
            if ($confirm !== null && $password !== $confirm) {
                wp_send_json_error(__('Passwords do not match.', 'my-login-form'));
            }
        }

        // Auto-generate password if not supplied
        if (empty($password)) {
            $password = wp_generate_password(12, true);
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        // This is a public, unauthenticated registration form — it must never
        // be able to create an elevated account. Force the role to customer
        // (WooCommerce) or subscriber only, regardless of what the plugin's
        // "Default Role for New Users" setting or WP's own default_role option
        // is set to; both are validated against this same allow-list, but this
        // is the hard backstop if either is ever misconfigured.
        $allowed_roles = array_values(array_filter(['customer', 'subscriber'], function ($r) {
            return get_role($r) !== null;
        }));
        $requested_role = get_option('my_login_form_default_role', 'subscriber');
        $safe_role = in_array($requested_role, $allowed_roles, true) ? $requested_role : 'subscriber';
        (new \WP_User($user_id))->set_role($safe_role);

        if ($firstname) {
            update_user_meta($user_id, 'first_name', $firstname);
        }
        if ($lastname) {
            update_user_meta($user_id, 'last_name', $lastname);
        }

        // Write WooCommerce's own customer meta keys so this account is
        // indistinguishable from one WooCommerce's native checkout/My
        // Account registration would have created — WC_Customer reads these
        // usermeta keys directly, no WC API call needed.
        if (class_exists('WooCommerce')) {
            if ($billing_firstname) {
                update_user_meta($user_id, 'billing_first_name', $billing_firstname);
            }
            if ($billing_lastname) {
                update_user_meta($user_id, 'billing_last_name', $billing_lastname);
            }
            if ($billing_email) {
                update_user_meta($user_id, 'billing_email', $billing_email);
            }
        }

        // Email/password signups can be gated behind a Supabase-sent OTP code
        // (Settings → "Require Email Verification"). Social sign-ins never go
        // through handle_register() at all — the provider already verified
        // the email — so this only ever applies here.
        if ($this->otp_required()) {
            $sent = $this->send_supabase_email_otp($email);
            $this->log_otp_event($email, 'register', $sent ? 'sent' : 'send_failed');

            if ($sent) {
                update_user_meta($user_id, 'mlf_email_verified', 0);
                wp_send_json_success([
                    'otp_required' => true,
                    'email'        => $email,
                    'message'      => __('We\'ve sent a 6-digit verification code to your email. Enter it below to finish creating your account.', 'my-login-form'),
                ]);
            }
        }

        // OTP disabled, or Supabase couldn't be reached to send it — don't let
        // an external-service hiccup block registration outright.
        update_user_meta($user_id, 'mlf_email_verified', 1);

        // Auto-login after registration
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        $this->sync_to_supabase($user_id);

        $redirect = wp_validate_redirect(esc_url_raw($_POST['redirect_to'] ?? ''), home_url());

        wp_send_json_success([
            'message'  => __('Registration successful! Welcome!', 'my-login-form'),
            'redirect' => $redirect,
        ]);
    }

    /**
     * "Forgot Password" step. When Supabase/OTP is configured (the normal
     * case), sends a 6-digit code and lets handle_verify_otp() (context
     * 'forgot_password') take it from there — no emailed link at all.
     * Without Supabase there's no way to send a code, so this falls back to
     * the old emailed reset-link (get_password_reset_key()), landing on the
     * same Reset Password page, so the "Forgot Password" form still works
     * on sites that haven't connected Supabase.
     */
    private function handle_forgot_password(): void {
        $email = sanitize_email($_POST['email'] ?? $_POST['user_email'] ?? '');

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'my-login-form'));
        }

        // Always respond the same way whether or not the account exists —
        // this can't be used to check which emails are registered.
        $generic_message = __('If an account exists for that email, a verification code has been sent.', 'my-login-form');

        if ($this->otp_required()) {
            $user = get_user_by('email', $email);
            if ($user) {
                $sent = $this->send_supabase_email_otp($email);
                $this->log_otp_event($email, 'forgot_password', $sent ? 'sent' : 'send_failed');
            }

            wp_send_json_success([
                'otp_required' => true,
                'email'        => $email,
                'message'      => $generic_message,
            ]);
        }

        // --- Fallback: no Supabase/OTP configured — emailed link ---
        $user = get_user_by('email', $email);

        $link_message   = __('If an account exists for that email, a password reset link has been sent.', 'my-login-form');
        $reset_page_id  = (int) get_option('my_login_form_reset_password_page_id');
        $reset_page_url = $reset_page_id ? get_permalink($reset_page_id) : '';

        if (!$user) {
            wp_send_json_success(['message' => $link_message, 'redirect' => $reset_page_url]);
        }

        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            wp_send_json_success(['message' => $link_message, 'redirect' => $reset_page_url]);
        }

        $reset_url = $reset_page_url;
        if (!$reset_url) {
            // No Reset Password page tracked — fall back to WordPress's own
            // reset screen rather than emailing a broken link.
            $reset_url = network_site_url('wp-login.php?action=rp', 'login');
        }
        $reset_url = add_query_arg([
            'key'   => $key,
            'login' => rawurlencode($user->user_login),
        ], $reset_url);

        $site_name = get_bloginfo('name');
        $subject   = sprintf(__('[%s] Password Reset Request', 'my-login-form'), $site_name);
        $message   = sprintf(__("Someone requested a password reset for your account on %s.\n\nIf this was you, click the link below to choose a new password:\n%s\n\nIf you didn't request this, you can safely ignore this email — your password won't change.", 'my-login-form'), $site_name, $reset_url);

        wp_mail($email, $subject, $message);

        wp_send_json_success(['message' => $link_message, 'redirect' => $reset_page_url]);
    }

    /**
     * "Reset Password" step: reached either via the OTP flow above (key
     * generated server-side right after OTP verification) or via the
     * fallback emailed link. Either way the key/login pair is validated
     * with the same core function wp-login.php itself uses.
     *
     * Whether this logs the user in directly mirrors whether OTP is in the
     * picture at all: with Supabase/OTP configured, login itself now always
     * demands its own OTP (see handle_login()) — auto-logging in here would
     * let a password reset silently bypass that, so instead this sends them
     * to the Login page to sign in fresh. Without Supabase/OTP configured,
     * this plugin's login has no second factor to bypass, so it behaves
     * exactly like a standard WordPress reset: log in immediately and
     * continue to the normal post-login destination.
     */
    private function handle_reset_password(): void {
        $key      = sanitize_text_field($_POST['key']   ?? '');
        $login    = sanitize_text_field($_POST['login'] ?? '');
        $password = $_POST['password'] ?? $_POST['user_pass'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($key) || empty($login)) {
            wp_send_json_error(__('This password reset link is invalid or has expired. Please request a new one.', 'my-login-form'));
        }

        if (empty($password)) {
            wp_send_json_error(__('Please enter a new password.', 'my-login-form'));
        }

        if (strlen($password) < 8) {
            wp_send_json_error(__('Password must be at least 8 characters long.', 'my-login-form'));
        }

        if (isset($_POST['confirm_password']) && $password !== $confirm) {
            wp_send_json_error(__('Passwords do not match.', 'my-login-form'));
        }

        $user = check_password_reset_key($key, $login);
        if (is_wp_error($user)) {
            wp_send_json_error(__('This password reset link is invalid or has expired. Please request a new one.', 'my-login-form'));
        }

        reset_password($user, $password);

        if ($this->otp_required()) {
            wp_send_json_success([
                'message'  => __('Your password has been reset. Please log in with your new password.', 'my-login-form'),
                'redirect' => $this->resolve_login_page_url(),
            ]);
        }

        // Supabase/OTP not configured — standard WordPress-style behavior:
        // log in immediately and continue to the normal destination.
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        $redirect = wp_validate_redirect(esc_url_raw($_POST['redirect_to'] ?? ''), home_url());

        wp_send_json_success([
            'message'  => __('Your password has been reset. Redirecting…', 'my-login-form'),
            'redirect' => $redirect,
        ]);
    }

    /**
     * Whether an email's domain has any mail server configured at all (MX,
     * falling back to A per RFC 5321 §5.1). Doesn't prove the visitor owns
     * the inbox — only a clicked confirmation link could — but it catches
     * typo'd/made-up domains like "test@test123.com" with no added friction.
     * Fails open (returns true) when checkdnsrr() isn't available, since
     * some hosts disable it — better to accept an unverifiable address than
     * block real signups/submissions because of a host restriction.
     */
    private function has_real_email_domain(string $email): bool {
        if (!function_exists('checkdnsrr')) {
            return true;
        }
        $domain = substr(strrchr($email, '@'), 1);
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    private function handle_custom_form($form): void {
        // Now that submissions actually email the admin, an unthrottled
        // endpoint would let anyone script-flood that inbox or burn through
        // the site's Resend sending quota. Generous enough (10/10min per IP)
        // that no real visitor filling out a form by hand will ever hit it.
        if (!my_login_form_rate_limit('custom_form_submit', my_login_form_client_ip(), 10, 10 * MINUTE_IN_SECONDS)) {
            wp_send_json_error(__('Too many submissions. Please try again in a few minutes.', 'my-login-form'));
        }

        $fields = json_decode($form->fields, true) ?: array();

        // Validate required fields
        $errors = array();
        foreach ($fields as $field_name => $field) {
            if (!empty($field['required']) && empty($_POST[$field_name])) {
                $errors[] = sprintf(__('%s is required', 'my-login-form'), $field['label'] ?? $field_name);
            }
        }

        // Reject obviously fake email-type fields: malformed addresses, and
        // addresses whose domain has no mail server configured at all (typo'd
        // or made-up domains like "test@test123.com"). This doesn't prove the
        // visitor owns the inbox — only a clicked confirmation link could —
        // but it catches the common case with no added friction.
        foreach ($fields as $field_name => $field) {
            if (($field['type'] ?? '') !== 'email' || empty($_POST[$field_name])) {
                continue;
            }
            $email = sanitize_text_field(wp_unslash($_POST[$field_name]));
            if (!is_email($email)) {
                $errors[] = sprintf(__('%s is not a valid email address', 'my-login-form'), $field['label'] ?? $field_name);
                continue;
            }
            if (!$this->has_real_email_domain($email)) {
                $errors[] = sprintf(__('%s doesn\'t look like a real email domain', 'my-login-form'), $field['label'] ?? $field_name);
            }
        }

        if (!empty($errors)) {
            wp_send_json_error(implode('<br>', $errors));
        }

        // Build an admin notification email out of the submitted field values.
        $lines = array();
        $reply_to_email = '';
        foreach ($fields as $field_name => $field) {
            if (!isset($_POST[$field_name]) || $_POST[$field_name] === '') {
                continue;
            }
            $label = $field['label'] ?? $field_name;
            $type  = $field['type'] ?? 'text';
            $value = $type === 'textarea'
                ? sanitize_textarea_field(wp_unslash($_POST[$field_name]))
                : sanitize_text_field(wp_unslash($_POST[$field_name]));

            if ($type === 'email' && is_email($value)) {
                $reply_to_email = $value;
            }

            $lines[] = $label . ': ' . $value;
        }

        $site_name = get_bloginfo('name');
        $subject   = sprintf(__('[%1$s] New submission: %2$s', 'my-login-form'), $site_name, $form->name);
        $message   = implode("\n", $lines);
        $headers   = $reply_to_email !== '' ? array('Reply-To: ' . $reply_to_email) : array();
        $to        = get_option('admin_email');

        $mail_failure_reason = null;
        $capture_failure = function ($wp_error) use (&$mail_failure_reason) {
            $mail_failure_reason = $wp_error->get_error_message();
        };
        add_action('wp_mail_failed', $capture_failure);
        $sent = wp_mail($to, $subject, $message, $headers);
        remove_action('wp_mail_failed', $capture_failure);

        error_log(sprintf(
            'My Login Form: contact submission (form #%d) wp_mail() to %s -> %s%s',
            $form->id,
            $to,
            $sent ? 'sent' : 'FAILED',
            $mail_failure_reason ? ' (' . $mail_failure_reason . ')' : ''
        ));

        wp_send_json_success([
            'message'    => __('Form submitted successfully!', 'my-login-form'),
            'clear_form' => true,
        ]);
    }
}
