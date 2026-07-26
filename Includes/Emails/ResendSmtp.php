<?php

/**
 * My Login Form - Resend SMTP for wp_mail() (Task 1)
 *
 * Routes every WordPress/WooCommerce email — order confirmations, download
 * links, password resets, new-account notices, everything that goes through
 * wp_mail() — via Resend's SMTP relay, using the API key already hardcoded
 * in wp-config.php. No settings page: this is a pure drop-in.
 *
 * IMPORTANT - this plugin already has a SEPARATE Resend integration at
 * Includes/Emails/Emails.php, which sends via Resend's HTTP API and is
 * driven by the Settings page option 'my_login_form_resend_api_key'. That
 * class hooks 'pre_wp_mail', and if it returns non-null (i.e. that option
 * is set to a real key), wp_mail() short-circuits BEFORE PHPMailer is ever
 * built - so 'phpmailer_init' below would never fire and this file would
 * silently do nothing. Leave the Settings page Resend API key field empty
 * if this SMTP-based file is the one you want actually sending mail.
 *
 * Setup:
 *   1. This file lives at Includes/Emails/ResendSmtp.php - add one line to
 *      my-login-form.php, next to the other require_once calls:
 *        require_once MY_LOGIN_FORM_DIR . 'Includes/Emails/ResendSmtp.php';
 *   2. In wp-config.php, above "That's all, stop editing!", make sure you
 *      have:
 *        define('OMEGA_RESEND_API_KEY', 're_xxxxxxxxxxxxxxxxxxxxxxxx');
 *   3. Below, change MY_LOGIN_FORM_RESEND_FROM_EMAIL to an address on the
 *      domain you verified in Resend (Resend rejects sends from unverified
 *      domains outright, regardless of SMTP auth succeeding).
 *
 * @package MyLoginForm\Emails
 */

// Prevent Direct Access
defined('ABSPATH') || exit;

// ----------------------------------------------------------------------
// TODO: change these to your verified sending domain / display name.
// Wrapped in defined() checks so you can instead set them in wp-config.php
// if you'd rather keep every environment-specific value in one place.
// ----------------------------------------------------------------------
if (!defined('MY_LOGIN_FORM_RESEND_FROM_EMAIL')) {
    define('MY_LOGIN_FORM_RESEND_FROM_EMAIL', 'noreply@your-verified-domain.com');
}
if (!defined('MY_LOGIN_FORM_RESEND_FROM_NAME')) {
    define('MY_LOGIN_FORM_RESEND_FROM_NAME', 'Omega'); // shown as the "from" display name
}

// Bail gracefully: constant not defined (or blank) in wp-config.php - don't
// touch wp_mail() at all, WordPress keeps using its normal mail transport.
if (!defined('OMEGA_RESEND_API_KEY') || trim((string) OMEGA_RESEND_API_KEY) === '') {
    return;
}

/**
 * Force the From address/name on every outgoing email to the verified
 * Resend sender, regardless of what wp_mail() or WooCommerce's own Settings
 * > Emails "From" fields were called with. Priority 999 so this wins over
 * WooCommerce's WC_Emails, which hooks these same core filters at the
 * default priority (10).
 */
add_filter('wp_mail_from', function () {
    return MY_LOGIN_FORM_RESEND_FROM_EMAIL;
}, 999);

add_filter('wp_mail_from_name', function () {
    return MY_LOGIN_FORM_RESEND_FROM_NAME;
}, 999);

/**
 * Configure PHPMailer to relay through Resend's SMTP endpoint.
 *
 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer Passed by reference by WP.
 */
add_action('phpmailer_init', function ($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.resend.com';
    $phpmailer->Port       = 465;
    $phpmailer->SMTPSecure = 'ssl'; // implicit TLS on connect (PHPMailer::ENCRYPTION_SMTPS)
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = 'resend';
    $phpmailer->Password   = OMEGA_RESEND_API_KEY;

    // Fallback if your host firewalls outbound 465 (some shared/managed
    // hosts do): switch to STARTTLS on 587 instead.
    //   $phpmailer->Port       = 587;
    //   $phpmailer->SMTPSecure = 'tls'; // PHPMailer::ENCRYPTION_STARTTLS

    // Belt-and-braces: also set the envelope From here directly, in case
    // something calls wp_mail() with its own 'From' header (which normally
    // wins over the wp_mail_from filters above).
    $phpmailer->setFrom(MY_LOGIN_FORM_RESEND_FROM_EMAIL, MY_LOGIN_FORM_RESEND_FROM_NAME, false);
});
