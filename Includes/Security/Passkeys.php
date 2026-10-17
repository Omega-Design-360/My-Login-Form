<?php
/**
 * WebAuthn passkeys for front-end login forms.
 *
 * @package MyLoginForm\Security
 */

namespace MyLoginForm\Security;

defined('ABSPATH') || exit;

use lbuchs\WebAuthn\WebAuthn;

class Passkeys {
    private const META_KEY = '_mlf_passkeys';
    private const TRANSIENT_PREFIX = 'my_login_form_passkey_';
    private const MAX_CREDENTIALS = 10;

    private static $instance;

    public static function get_instance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('my_login_passkeys', [$this, 'render_manager']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_ajax_mlf_passkey_create_options', [$this, 'create_options']);
        add_action('wp_ajax_mlf_passkey_create_verify', [$this, 'create_verify']);
        add_action('wp_ajax_mlf_passkey_list', [$this, 'list_credentials']);
        add_action('wp_ajax_mlf_passkey_delete', [$this, 'delete_credential']);
        add_action('wp_ajax_nopriv_mlf_passkey_login_options', [$this, 'login_options']);
        add_action('wp_ajax_mlf_passkey_login_options', [$this, 'login_options']);
        add_action('wp_ajax_nopriv_mlf_passkey_login_verify', [$this, 'login_verify']);
        add_action('wp_ajax_mlf_passkey_login_verify', [$this, 'login_verify']);
    }

    public function enqueue_styles(): void {
        if (self::enabled() && self::secure_context()) {
            wp_enqueue_style('mlf-passkeys', MY_LOGIN_FORM_URL . 'Public/Assets/css/passkeys.css', [], MY_LOGIN_FORM_VERSION);
        }
    }

    public static function enabled(): bool {
        if (!(bool) get_option('my_login_form_enable_passkeys', 0)) {
            return false;
        }
        if (is_readable(MY_LOGIN_FORM_DIR . 'vendor/autoload.php')) {
            require_once MY_LOGIN_FORM_DIR . 'vendor/autoload.php';
        }
        return class_exists(WebAuthn::class)
            && extension_loaded('openssl')
            && extension_loaded('mbstring');
    }

    public static function secure_context(): bool {
        $host = strtolower(trim((string) wp_parse_url(home_url('/'), PHP_URL_HOST), '[]'));
        return is_ssl() || in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    private static function rp_id(): string {
        return strtolower(trim((string) wp_parse_url(home_url('/'), PHP_URL_HOST), '[]'));
    }

    private static function webauthn(): WebAuthn {
        require_once MY_LOGIN_FORM_DIR . 'vendor/autoload.php';
        return new WebAuthn(get_bloginfo('name') ?: 'Website', self::rp_id(), ['none'], true);
    }

    private static function assert_available(): void {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            wp_send_json_error(['message' => __('This feature requires an active My Login Form license.', 'my-login-form')]);
        }
        if (!self::enabled()) {
            wp_send_json_error(['message' => __('Passkey login is currently unavailable.', 'my-login-form')]);
        }
        if (!self::secure_context()) {
            wp_send_json_error(['message' => __('Passkeys require HTTPS. Localhost is supported for development.', 'my-login-form')]);
        }
    }

    private static function check_nonce(): void {
        if (!check_ajax_referer('my_login_form_submit', 'my_login_form_nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed. Refresh the page and try again.', 'my-login-form')]);
        }
    }

    private static function new_flow(string $kind, int $user_id, $challenge): string {
        $token = bin2hex(random_bytes(24));
        set_transient(self::TRANSIENT_PREFIX . hash('sha256', $token), [
            'kind' => $kind,
            'user_id' => $user_id,
            'challenge' => $challenge,
        ], 5 * MINUTE_IN_SECONDS);
        return $token;
    }

    private static function consume_flow(string $token, string $kind): array {
        $token = sanitize_text_field($token);
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            wp_send_json_error(['message' => __('This passkey request expired. Please try again.', 'my-login-form')]);
        }
        $key = self::TRANSIENT_PREFIX . hash('sha256', $token);
        $flow = get_transient($key);
        delete_transient($key);
        if (!is_array($flow) || ($flow['kind'] ?? '') !== $kind || empty($flow['challenge'])) {
            wp_send_json_error(['message' => __('This passkey request expired. Please try again.', 'my-login-form')]);
        }
        return $flow;
    }

    private static function decode(string $value): string {
        $value = strtr($value, '-_', '+/');
        $decoded = base64_decode($value . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false || $decoded === '') {
            throw new \RuntimeException('Invalid passkey response.');
        }
        return $decoded;
    }

    private static function response_data(): array {
        $data = !empty($_POST) ? wp_unslash($_POST) : json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($data)) {
            wp_send_json_error(['message' => __('Invalid passkey response.', 'my-login-form')]);
        }
        return $data;
    }

    private static function decode_client_origin(string $client_data_json): void {
        $client = json_decode($client_data_json, true);
        $expected_parts = wp_parse_url(home_url('/'));
        $origin_parts = wp_parse_url($client['origin'] ?? '');
        $expected_host = strtolower(trim((string) ($expected_parts['host'] ?? ''), '[]'));
        $origin_host = strtolower(trim((string) ($origin_parts['host'] ?? ''), '[]'));
        $local = in_array($expected_host, ['localhost', '127.0.0.1', '::1'], true);
        $expected_scheme = strtolower((string) ($expected_parts['scheme'] ?? ''));
        $origin_scheme = strtolower((string) ($origin_parts['scheme'] ?? ''));
        $expected_port = (int) ($expected_parts['port'] ?? 0);
        $origin_port = (int) ($origin_parts['port'] ?? 0);
        $valid_origin = $origin_host === $expected_host
            && $origin_port === $expected_port
            && ($origin_scheme === 'https' || ($local && $expected_scheme === 'http' && $origin_scheme === 'http'));
        if (empty($client['type']) || !empty($client['crossOrigin']) || !$valid_origin) {
            throw new \RuntimeException('Passkey origin does not match this website.');
        }
    }

    private static function credentials(int $user_id): array {
        $items = get_user_meta($user_id, self::META_KEY, true);
        return is_array($items) ? $items : [];
    }

    private static function user_handle(int $user_id): string {
        return hash('sha256', home_url('/') . '|my-login-form|' . $user_id, true);
    }

    private static function user_blocked(\WP_User $user): bool {
        $ip = function_exists('my_login_form_client_ip') ? my_login_form_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
        $blocked_ips = array_filter(array_map('trim', explode("\n", (string) get_option('my_login_form_blocked_ips', ''))));
        $blocked_users = array_map('strtolower', array_filter(array_map('trim', explode("\n", (string) get_option('my_login_form_blocked_users', '')))));
        return ($ip && in_array($ip, $blocked_ips, true))
            || in_array(strtolower($user->user_login), $blocked_users, true)
            || in_array(strtolower($user->user_email), $blocked_users, true);
    }

    public static function login_button_html(int $form_id): string {
        if (!self::enabled() || !self::secure_context()) {
            return '';
        }
        return '<div class="mlf-passkey-actions" data-form-id="' . esc_attr($form_id) . '"><button type="button" class="mlf-passkey-login">' . esc_html__('Sign in with a passkey', 'my-login-form') . '</button></div>';
    }

    public function render_manager(): string {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            return \MyLoginForm\Licensing\Gate::gate_notice_html();
        }
        if (!self::enabled()) {
            return '<p>' . esc_html__('Passkey login is currently disabled.', 'my-login-form') . '</p>';
        }
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Sign in to your account to add or manage passkeys.', 'my-login-form') . '</p>';
        }
        if (!self::secure_context()) {
            return '<p>' . esc_html__('Passkeys require HTTPS. Localhost is supported for development.', 'my-login-form') . '</p>';
        }

        wp_enqueue_script('mlf-passkeys', MY_LOGIN_FORM_URL . 'Public/Assets/js/passkeys.js', [], MY_LOGIN_FORM_VERSION, true);
        wp_enqueue_style('mlf-passkeys', MY_LOGIN_FORM_URL . 'Public/Assets/css/passkeys.css', [], MY_LOGIN_FORM_VERSION);
        wp_localize_script('mlf-passkeys', 'mlfPasskeys', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_login_form_submit'),
            'loggedIn' => true,
            'i18n' => self::strings(),
        ]);

        ob_start(); ?>
        <div class="mlf-passkey-manager" data-nonce="<?php echo esc_attr(wp_create_nonce('my_login_form_submit')); ?>">
            <h3><?php esc_html_e('Passkeys', 'my-login-form'); ?></h3>
            <p><?php esc_html_e('Add a passkey to sign in with your device, fingerprint, face, or password manager.', 'my-login-form'); ?></p>
            <label class="mlf-passkey-label" for="mlf-passkey-name"><?php esc_html_e('Passkey name', 'my-login-form'); ?></label>
            <input id="mlf-passkey-name" class="mlf-passkey-name" type="text" maxlength="60" placeholder="<?php esc_attr_e('e.g. My phone', 'my-login-form'); ?>">
            <button type="button" class="mlf-passkey-add"><?php esc_html_e('Add a passkey', 'my-login-form'); ?></button>
            <p class="mlf-passkey-status" aria-live="polite"></p>
            <ul class="mlf-passkey-list"></ul>
        </div>
        <?php return (string) ob_get_clean();
    }

    private static function strings(): array {
        return [
            'unsupported' => __('This browser does not support passkeys.', 'my-login-form'),
            'identifier' => __('Enter your username or email first.', 'my-login-form'),
            'failed' => __('Passkey sign-in failed. Please try again or use your password.', 'my-login-form'),
            'login' => __('Signing in…', 'my-login-form'),
            'added' => __('Passkey added.', 'my-login-form'),
            'removed' => __('Passkey removed.', 'my-login-form'),
            'remove' => __('Remove', 'my-login-form'),
            'empty' => __('No passkeys have been added yet.', 'my-login-form'),
        ];
    }

    public function create_options(): void {
        self::check_nonce(); self::assert_available();
        if (!is_user_logged_in()) wp_send_json_error(['message' => __('Please sign in first.', 'my-login-form')]);
        try {
            $user = wp_get_current_user();
            $records = self::credentials($user->ID);
            if (count($records) >= self::MAX_CREDENTIALS) wp_send_json_error(['message' => __('You have reached the passkey limit for this account.', 'my-login-form')]);
            $wa = self::webauthn();
            $exclude = [];
            foreach ($records as $record) $exclude[] = self::decode((string) $record['credentialId']);
            $args = $wa->getCreateArgs(self::user_handle((int) $user->ID), $user->user_login, $user->display_name ?: $user->user_login, 300, true, 'preferred', null, $exclude);
            wp_send_json_success(['options' => $args->publicKey, 'token' => self::new_flow('create', $user->ID, $wa->getChallenge())]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => __('Could not start passkey setup. Please try again.', 'my-login-form')]);
        }
    }

    public function create_verify(): void {
        self::check_nonce(); self::assert_available();
        if (!is_user_logged_in()) wp_send_json_error(['message' => __('Please sign in first.', 'my-login-form')]);
        $data = self::response_data();
        $flow = self::consume_flow((string) ($data['token'] ?? ''), 'create');
        if ((int) $flow['user_id'] !== get_current_user_id()) wp_send_json_error(['message' => __('Security check failed.', 'my-login-form')]);
        try {
            $client = self::decode((string) ($data['clientDataJSON'] ?? ''));
            self::decode_client_origin($client);
            $wa = self::webauthn();
            $result = $wa->processCreate($client, self::decode((string) ($data['attestationObject'] ?? '')), $flow['challenge'], false, true, false);
            $records = self::credentials(get_current_user_id());
            if (count($records) >= self::MAX_CREDENTIALS) wp_send_json_error(['message' => __('You have reached the passkey limit for this account.', 'my-login-form')]);
            $credential_id = rtrim(strtr(base64_encode($result->credentialId), '+/', '-_'), '=');
            $label = sanitize_text_field((string) ($data['name'] ?? ''));
            $records[hash('sha256', $result->credentialId)] = [
                'credentialId' => $credential_id,
                'credentialPublicKey' => $result->credentialPublicKey,
                'signatureCounter' => (int) $result->signatureCounter,
                'name' => $label ?: __('Passkey', 'my-login-form'),
                'created' => current_time('mysql', true),
            ];
            update_user_meta(get_current_user_id(), self::META_KEY, $records);
            wp_send_json_success(['message' => __('Passkey added.', 'my-login-form')]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => __('Passkey registration could not be verified. Please try again.', 'my-login-form')]);
        }
    }

    public function list_credentials(): void {
        self::check_nonce(); self::assert_available();
        if (!is_user_logged_in()) wp_send_json_error(['message' => __('Please sign in first.', 'my-login-form')]);
        $list = [];
        foreach (self::credentials(get_current_user_id()) as $key => $record) {
            $list[] = ['key' => $key, 'name' => sanitize_text_field($record['name'] ?? __('Passkey', 'my-login-form')), 'created' => sanitize_text_field($record['created'] ?? '')];
        }
        wp_send_json_success(['credentials' => $list]);
    }

    public function delete_credential(): void {
        self::check_nonce(); self::assert_available();
        if (!is_user_logged_in()) wp_send_json_error(['message' => __('Please sign in first.', 'my-login-form')]);
        $data = self::response_data();
        $key = sanitize_text_field($data['key'] ?? '');
        $records = self::credentials(get_current_user_id());
        if (!isset($records[$key])) wp_send_json_error(['message' => __('Passkey not found.', 'my-login-form')]);
        unset($records[$key]);
        update_user_meta(get_current_user_id(), self::META_KEY, $records);
        wp_send_json_success(['message' => __('Passkey removed.', 'my-login-form')]);
    }

    public function login_options(): void {
        self::check_nonce(); self::assert_available();
        $identifier = sanitize_text_field(wp_unslash($_POST['identifier'] ?? ''));
        if ($identifier === '') wp_send_json_error(['message' => __('Enter your username or email first.', 'my-login-form')]);
        if (!my_login_form_rate_limit('passkey_options', my_login_form_client_ip() . '|' . strtolower($identifier), 10, 15 * MINUTE_IN_SECONDS)) {
            wp_send_json_error(['message' => __('Too many attempts. Please try again in a few minutes.', 'my-login-form')]);
        }
        $user = is_email($identifier) ? get_user_by('email', $identifier) : get_user_by('login', $identifier);
        $records = $user ? self::credentials((int) $user->ID) : [];
        if (!$user || !$records) wp_send_json_error(['message' => __('No passkey sign-in is available for that account.', 'my-login-form')]);
        try {
            $ids = array_map(static function ($record) { return self::decode((string) $record['credentialId']); }, $records);
            $wa = self::webauthn();
            $args = $wa->getGetArgs($ids, 300, true, true, true, true, true, 'preferred');
            wp_send_json_success(['options' => $args->publicKey, 'token' => self::new_flow('login', (int) $user->ID, $wa->getChallenge())]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => __('Could not start passkey sign-in. Please try again.', 'my-login-form')]);
        }
    }

    public function login_verify(): void {
        self::check_nonce(); self::assert_available();
        $data = self::response_data();
        $flow = self::consume_flow((string) ($data['token'] ?? ''), 'login');
        $user = get_user_by('id', (int) $flow['user_id']);
        if (!$user) wp_send_json_error(['message' => __('Passkey sign-in failed.', 'my-login-form')]);
        try {
            if (self::user_blocked($user)) wp_send_json_error(['message' => __('Access denied.', 'my-login-form')]);
            $credential_id = self::decode((string) ($data['credentialId'] ?? ''));
            $records = self::credentials((int) $user->ID);
            $key = hash('sha256', $credential_id);
            if (empty($records[$key])) throw new \RuntimeException('Unknown credential.');
            $client = self::decode((string) ($data['clientDataJSON'] ?? ''));
            self::decode_client_origin($client);
            if (!empty($data['userHandle'])) {
                $expected_handle = self::user_handle((int) $user->ID);
                if (!hash_equals($expected_handle, self::decode((string) $data['userHandle']))) {
                    throw new \RuntimeException('Passkey user handle mismatch.');
                }
            }
            $wa = self::webauthn();
            $wa->processGet($client, self::decode((string) ($data['authenticatorData'] ?? '')), self::decode((string) ($data['signature'] ?? '')), $records[$key]['credentialPublicKey'], $flow['challenge'], (int) ($records[$key]['signatureCounter'] ?? 0), false, true);
            $records[$key]['signatureCounter'] = $wa->getSignatureCounter();
            update_user_meta($user->ID, self::META_KEY, $records);
            $auth_user = apply_filters('wp_authenticate_user', $user, '');
            if (is_wp_error($auth_user)) wp_send_json_error(['message' => $auth_user->get_error_message()]);
            if ($auth_user instanceof \WP_User) $user = $auth_user;
            \MyLoginForm\Ajax\AuthAjax::get_instance()->complete_passkey_login($user, (string) ($data['redirect'] ?? ''), !empty($data['remember']));
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => __('Passkey sign-in failed. Please try again or use your password.', 'my-login-form')]);
        }
    }
}
