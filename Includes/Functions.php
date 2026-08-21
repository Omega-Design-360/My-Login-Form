<?php

// Prevent Direct Access
defined('ABSPATH') || exit;

/**
 * Get plugin option
 */
function my_login_form_get_option($key, $default = '') {
    $options = get_option(MY_LOGIN_FORM_OPTION_SETTINGS, []);
    return $options[$key] ?? $default;
}

/**
 * Update plugin option
 */
function my_login_form_update_option($key, $value) {
    $options = get_option(MY_LOGIN_FORM_OPTION_SETTINGS, []);
    $options[$key] = $value;
    return update_option(MY_LOGIN_FORM_OPTION_SETTINGS, $options);
}

/**
 * Check if feature is enabled
 */
function my_login_form_is_feature_enabled($feature) {
    $enabled_features = my_login_form_get_option('enabled_features', []);
    return in_array($feature, (array)$enabled_features);
}

/**
 * Lightweight transient-based rate limiter — no external dependencies (uses
 * an object cache automatically if the site has one, wp_options otherwise).
 * $subject is whatever the limit should be scoped to (an IP, an email, a
 * combination) — the same $bucket+$subject pair shares one counter.
 *
 * Returns true (and counts this call) if still under the limit, false if
 * $subject has already hit $max_attempts within $window_seconds. Callers
 * that only want to count failures should call this AFTER confirming
 * failure, not before attempting the action.
 */
function my_login_form_rate_limit(string $bucket, string $subject, int $max_attempts, int $window_seconds): bool {
    $key   = 'mlf_rl_' . $bucket . '_' . md5($subject);
    $count = (int) get_transient($key);
    if ($count >= $max_attempts) {
        return false;
    }
    set_transient($key, $count + 1, $window_seconds);
    return true;
}

/**
 * Client IP for rate-limiting purposes. Deliberately only trusts
 * REMOTE_ADDR, not X-Forwarded-For/X-Real-IP — those are trivially spoofed
 * by the client unless the host's proxy is known to overwrite them, which
 * this plugin can't assume across every deployment.
 */
function my_login_form_client_ip(): string {
    return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
}

/**
 * Get template file path
 */
function my_login_form_locate_template($template_name) {
    // Check theme directory first
    $template = locate_template(['my-login-form/' . $template_name . '.php']);
    
    // Fallback to plugin templates
    if (!$template) {
        $template = MY_LOGIN_FORM_TEMPLATES_DIR . $template_name . '.php';
    }
    
    return apply_filters('my_login_form_template_path', $template, $template_name);
}

/**
 * Load template
 */
function my_login_form_get_template($template_name, $args = []) {
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    $template_file = my_login_form_locate_template($template_name);
    
    if (file_exists($template_file)) {
        include $template_file;
    }
}

/**
 * Log debug message
 */
function my_login_form_log($message, $type = 'debug') {
    if (!MY_LOGIN_FORM_DEBUG) {
        return;
    }
    
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    $log_file = MY_LOGIN_FORM_PLUGIN_DIR . 'debug.log';
    $message = '[' . current_time('mysql') . '] [' . strtoupper($type) . '] ' . $message . PHP_EOL;
    
    @file_put_contents($log_file, $message, FILE_APPEND);
}

/**
 * Check if WooCommerce is active
 */
function my_login_form_is_woocommerce_active() {
    return class_exists('WooCommerce');
}

/**
 * URL for the plugin's own "Forgot Password" page — used instead of
 * wp_lostpassword_url(), which WooCommerce filters to point at its own
 * My Account "lost password" screen whenever WooCommerce is active,
 * bypassing this plugin's OTP-based reset flow entirely.
 */
function my_login_form_lostpassword_url() {
    $page_id = (int) get_option('my_login_form_forgot_password_page_id');
    if ($page_id && get_post_status($page_id) === 'publish') {
        return get_permalink($page_id);
    }
    return wp_lostpassword_url();
}

/**
 * URL for the plugin's own "Register" page — used instead of
 * wp_registration_url(), which sends visitors to wp-login.php?action=register
 * (WordPress's bare default form, not this plugin's Register page/flow).
 */
function my_login_form_registration_url() {
    $page_id = (int) get_option('my_login_form_register_page_id');
    if ($page_id && get_post_status($page_id) === 'publish') {
        return get_permalink($page_id);
    }
    return wp_registration_url();
}

/**
 * URL for the plugin's own "Login" page — used instead of wp_login_url(),
 * which sends visitors to wp-login.php (WordPress's bare default form, not
 * this plugin's Login page/flow). Mirrors my_login_form_registration_url()
 * and my_login_form_lostpassword_url() above so every form type can link
 * back to whichever of the other two flows it needs.
 */
function my_login_form_login_url() {
    $page_id = (int) get_option('my_login_form_login_page_id');
    if ($page_id && get_post_status($page_id) === 'publish') {
        return get_permalink($page_id);
    }
    return wp_login_url();
}