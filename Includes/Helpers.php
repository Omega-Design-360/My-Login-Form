<?php

// Prevent Direct Access
defined('ABSPATH') || exit;

/**
 * Sanitize array recursively
 */
function my_login_form_sanitize_array($array) {
    if (!is_array($array)) {
        return sanitize_text_field($array);
    }
    
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = my_login_form_sanitize_array($value);
        } else {
            $array[$key] = sanitize_text_field($value);
        }
    }
    
    return $array;
}

/**
 * Escape HTML for output
 */
function my_login_form_esc_html($string, $allowed_tags = []) {
    if (!empty($allowed_tags)) {
        return wp_kses($string, $allowed_tags);
    }
    return esc_html($string);
}

/**
 * Generate nonce field
 */
function my_login_form_nonce_field() {
    return wp_nonce_field(MY_LOGIN_FORM_NONCE_ACTION, 'my_login_form_nonce', true, false);
}

/**
 * Verify nonce
 */
function my_login_form_verify_nonce($nonce) {
    return wp_verify_nonce($nonce, MY_LOGIN_FORM_NONCE_ACTION);
}

/**
 * Get user IP address
 */
function my_login_form_get_user_ip() {
    $ip_keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key])) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }
    
    return '0.0.0.0';
}