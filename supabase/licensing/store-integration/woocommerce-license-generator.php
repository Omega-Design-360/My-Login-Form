<?php
/**
 * My Login Form — WooCommerce License Generator (STORE SITE ONLY)
 *
 * ============================================================================
 * IMPORTANT: This file belongs on YOUR WooCommerce store site — the site
 * where you SELL the plugin — never inside the plugin ZIP you distribute to
 * customers. It holds LICENSE_GENERATE_SECRET, which must never leave your
 * own server.
 * ============================================================================
 *
 * Install: drop this file into your store site's wp-content/mu-plugins/
 * folder (create the folder if it doesn't exist — anything in mu-plugins
 * loads automatically, no activation needed), or wrap it as a tiny normal
 * plugin if you prefer being able to deactivate it.
 *
 * Setup, before this does anything useful:
 *   1. Define these in your store's wp-config.php (never commit them to a repo):
 *        define('MY_LOGIN_FORM_LICENSE_API_URL', 'https://<project-ref>.supabase.co/functions/v1/license-api');
 *        define('LICENSE_GENERATE_SECRET', '<the same secret you set with `supabase secrets set`>');
 *   2. Set MLF_PRODUCT_PLAN_MAP below to map your WooCommerce product IDs to
 *      plans ('6-month' | '1-year' | '3-year'). 'lifetime' is still accepted
 *      by the Edge Function for legacy licenses, but is no longer sold.
 *   3. Set MLF_RENEWAL_PRODUCT_IDS below to whichever product IDs represent
 *      a *renewal* purchase (as opposed to a first-time purchase), if you
 *      sell those as separate products.
 *   4. Optional but recommended — also in wp-config.php, so license emails
 *      send with a proper From address instead of WordPress's default
 *      wordpress@<domain>:
 *        define('MLF_RESEND_API_KEY', '<your Resend API key>');
 *        define('MLF_RESEND_FROM_EMAIL', 'noreply@omegadesign.io');
 *        define('MLF_RESEND_FROM_NAME', 'OmegaDesign');
 *      Leave MLF_RESEND_API_KEY undefined/blank to keep using wp_mail() —
 *      Resend is additive here, never a hard dependency.
 *
 * What it does:
 *   - On order completion, for each line item that's a licensed product,
 *     calls the Edge Function's `generate` (first purchase) or `renew`
 *     (renewal product) action, saves the key on the order, and emails it
 *     to the buyer.
 *   - Renewal purchases look up the buyer's existing key from their own
 *     WooCommerce account (user meta) — set the moment their original key
 *     was generated — so they don't have to dig up their key at checkout.
 *     Guest checkouts with no matching stored key fall back to generating a
 *     brand-new key instead of failing the order.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// CONFIGURE THESE for your store
// ============================================================================

// WooCommerce product ID => plan sold by that product.
// A plain array constant (supported by define() since PHP 7.0) — not
// serialize()/unserialize(), which some hosts' WAFs flag and block on save
// as a PHP-object-injection pattern, even though this usage is safe.
if (!defined('MLF_PRODUCT_PLAN_MAP')) {
    define('MLF_PRODUCT_PLAN_MAP', [
        35 => '6-month', // "My Login Plugin" — https://omegadesign.io/product/my-login-plugin-6-months/
        65 => '1-year',  // still in preview/draft — https://omegadesign.io/?post_type=product&p=65&preview=true
        67 => '3-year',  // still in preview/draft — https://omegadesign.io/?post_type=product&p=67&preview=true
    ]);
}

// Product IDs that represent a *renewal* rather than a first-time purchase.
// Leave empty if you don't sell renewals as a separate product.
if (!defined('MLF_RENEWAL_PRODUCT_IDS')) {
    define('MLF_RENEWAL_PRODUCT_IDS', [
        // 130 => '6-month',
        // 131 => '1-year',
    ]);
}

const MLF_LICENSE_KEY_USER_META = '_my_login_active_license_key';
const MLF_LICENSE_KEY_ORDER_META = '_my_login_license_key';

add_action('woocommerce_order_status_completed', 'mlf_generate_or_renew_license_for_order');

/**
 * @param int $order_id
 * @return void
 */
function mlf_generate_or_renew_license_for_order($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        mlf_log("order #{$order_id}: wc_get_order() returned nothing — order doesn't exist?", 'error');
        return;
    }

    mlf_log("order #{$order_id}: woocommerce_order_status_completed fired, checking for a licensed product.");

    // Don't double-issue a key if this order was already processed (e.g. a
    // status change bouncing through "completed" more than once).
    if ($order->get_meta(MLF_LICENSE_KEY_ORDER_META)) {
        mlf_log("order #{$order_id}: already has a license key on file (" . $order->get_meta(MLF_LICENSE_KEY_ORDER_META) . ") — skipping, no license/email work done. If the email genuinely never arrived the first time, resend it manually (this guard blocks automatic retries on purpose, to avoid double-issuing keys).", 'notice');
        return;
    }

    $plan_map    = is_array(MLF_PRODUCT_PLAN_MAP) ? MLF_PRODUCT_PLAN_MAP : [];
    $renewal_map = is_array(MLF_RENEWAL_PRODUCT_IDS) ? MLF_RENEWAL_PRODUCT_IDS : [];

    $seen_product_ids = [];
    foreach ($order->get_items() as $item) {
        $product_id          = $item->get_product_id();
        $seen_product_ids[]  = $product_id;

        if (isset($renewal_map[$product_id])) {
            mlf_log("order #{$order_id}: product #{$product_id} matched MLF_RENEWAL_PRODUCT_IDS ({$renewal_map[$product_id]}) — processing as a renewal.");
            mlf_process_renewal($order, $renewal_map[$product_id]);
            return;
        }

        if (isset($plan_map[$product_id])) {
            mlf_log("order #{$order_id}: product #{$product_id} matched MLF_PRODUCT_PLAN_MAP ({$plan_map[$product_id]}) — processing as a new purchase.");
            mlf_process_new_purchase($order, $plan_map[$product_id]);
            return;
        }
    }

    // Nothing in the order matched either map — this is the #1 cause of
    // "no email, no error, nothing in any log": the code never even calls
    // the license API or the mail functions, because it has no idea this
    // order contains a licensed product. Check the product ID(s) logged
    // below against MLF_PRODUCT_PLAN_MAP / MLF_RENEWAL_PRODUCT_IDS above.
    mlf_log("order #{$order_id}: no line item matched MLF_PRODUCT_PLAN_MAP or MLF_RENEWAL_PRODUCT_IDS. Product IDs in this order: " . implode(', ', $seen_product_ids) . '. No license was generated and no email was attempted.', 'warning');
}

/**
 * Write to WooCommerce's own logger (WooCommerce → Status → Logs, source
 * "my-login-license") instead of PHP's error_log — visible from wp-admin
 * without server/FTP access, and doesn't depend on WP_DEBUG being on.
 *
 * @param string $message
 * @param string $level 'info' | 'notice' | 'warning' | 'error'
 * @return void
 */
function mlf_log(string $message, string $level = 'info'): void {
    if (!function_exists('wc_get_logger')) {
        return;
    }
    wc_get_logger()->log($level, $message, ['source' => 'my-login-license']);
}

/**
 * @param \WC_Order $order
 * @param string    $plan
 * @return void
 */
function mlf_process_new_purchase(\WC_Order $order, string $plan) {
    $email = $order->get_billing_email();

    $result = mlf_call_license_api('generate', [
        'email'    => $email,
        'plan'     => $plan,
        'order_id' => $order->get_id(),
    ], true);

    if (is_wp_error($result) || empty($result['license_key'])) {
        $reason = is_wp_error($result) ? $result->get_error_message() : 'no license_key in response';
        $order->add_order_note(sprintf('My Login Form: license generation FAILED — %s', $reason));
        mlf_log("order #{$order->get_id()}: license generation FAILED — {$reason}. No email was attempted.", 'error');
        return;
    }

    $order->update_meta_data(MLF_LICENSE_KEY_ORDER_META, $result['license_key']);
    $order->add_order_note(sprintf('My Login Form: license key generated (%s)', $result['license_key']));
    $order->save();
    mlf_log("order #{$order->get_id()}: license key generated ({$result['license_key']}), proceeding to email it.");

    // Remember this as "the buyer's current key" so a future renewal
    // purchase by this same account doesn't need them to look it up.
    $customer_id = $order->get_customer_id();
    if ($customer_id) {
        update_user_meta($customer_id, MLF_LICENSE_KEY_USER_META, $result['license_key']);
    }

    mlf_email_license_to_buyer($email, $result['license_key'], $result['expires_at'] ?? null, false, mlf_get_order_download_links($order));
}

/**
 * @param \WC_Order $order
 * @param string    $plan
 * @return void
 */
function mlf_process_renewal(\WC_Order $order, string $plan) {
    $email       = $order->get_billing_email();
    $customer_id = $order->get_customer_id();

    $existing_key = $customer_id ? get_user_meta($customer_id, MLF_LICENSE_KEY_USER_META, true) : '';

    if (!$existing_key) {
        // Guest checkout, or we never captured their key — don't fail the
        // order over it. Issue a brand-new key instead and say so in the
        // email, rather than leaving a paying customer with nothing.
        $order->add_order_note('My Login Form: no existing license key on file for this customer — issuing a new key instead of renewing.');
        mlf_process_new_purchase($order, $plan);
        return;
    }

    $result = mlf_call_license_api('renew', [
        'license_key' => $existing_key,
        'plan'        => $plan,
    ], true);

    if (is_wp_error($result) || empty($result['expires_at'])) {
        $reason = is_wp_error($result) ? $result->get_error_message() : 'unexpected response';
        $order->add_order_note(sprintf('My Login Form: license renewal FAILED — %s', $reason));
        mlf_log("order #{$order->get_id()}: license renewal FAILED — {$reason}. No email was attempted.", 'error');
        return;
    }

    $order->update_meta_data(MLF_LICENSE_KEY_ORDER_META, $existing_key);
    $order->add_order_note(sprintf('My Login Form: license renewed, now expires %s', $result['expires_at']));
    $order->save();
    mlf_log("order #{$order->get_id()}: license renewed, expires {$result['expires_at']}, proceeding to email it.");

    mlf_email_license_to_buyer($email, $existing_key, $result['expires_at'], true, mlf_get_order_download_links($order));
}

/**
 * Get the buyer's per-order download URL(s) for whichever downloadable
 * products are in this order, forcing WooCommerce to grant download
 * permissions first if it hasn't already.
 *
 * WooCommerce grants download permissions via its own callback on the same
 * 'woocommerce_order_status_completed' hook we use. Since this file loads as
 * an mu-plugin (before regular plugins, including WooCommerce), our callback
 * can run first — so we can't assume permissions already exist by the time
 * we get here and must force-grant them ourselves.
 *
 * @param \WC_Order $order
 * @return array<int, array{name: string, url: string}>
 */
function mlf_get_order_download_links(\WC_Order $order): array {
    if (function_exists('wc_downloadable_product_permissions')) {
        wc_downloadable_product_permissions($order->get_id(), true);
    }

    $links = [];
    foreach ($order->get_downloadable_items() as $item) {
        if (empty($item['download_url'])) {
            continue;
        }
        $links[] = [
            'name' => $item['download_name'] ?: $item['product_name'],
            'url'  => $item['download_url'],
        ];
    }

    return $links;
}

/**
 * @param string      $action  'generate' | 'renew'
 * @param array       $payload
 * @param bool        $with_generate_secret
 * @return array|\WP_Error
 */
function mlf_call_license_api(string $action, array $payload, bool $with_generate_secret) {
    // Same resolution order as License::get_api_url() on the customer side —
    // the my_login_form_license_api_url option wins if present, so this
    // store snippet works out of the box on a site that's already running
    // the plugin itself, without needing the URL defined a second time.
    $url = get_option('my_login_form_license_api_url', '');
    if (!$url) {
        $url = defined('MY_LOGIN_FORM_LICENSE_API_URL') ? MY_LOGIN_FORM_LICENSE_API_URL : '';
    }
    if (!$url) {
        return new WP_Error('no_api_url', 'Licensing API URL is not configured (define MY_LOGIN_FORM_LICENSE_API_URL in wp-config.php).');
    }

    $headers = ['Content-Type' => 'application/json'];
    if ($with_generate_secret) {
        $secret = defined('LICENSE_GENERATE_SECRET') ? LICENSE_GENERATE_SECRET : '';
        if (!$secret) {
            return new WP_Error('no_secret', 'LICENSE_GENERATE_SECRET is not defined.');
        }
        $headers['x-generate-secret'] = $secret;
    }

    $payload['action'] = $action;

    $response = wp_remote_post($url, [
        'headers' => $headers,
        'body'    => wp_json_encode($payload),
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code < 200 || $code >= 300) {
        return new WP_Error('api_error', is_array($body) && !empty($body['error']) ? $body['error'] : ('HTTP ' . $code));
    }

    return $body;
}

/**
 * @param string                                    $email
 * @param string                                    $license_key
 * @param string|null                               $expires_at
 * @param bool                                      $is_renewal
 * @param array<int, array{name: string, url: string}> $download_items
 * @return void
 */
function mlf_email_license_to_buyer(string $email, string $license_key, ?string $expires_at, bool $is_renewal, array $download_items = []) {
    $site_name = get_bloginfo('name');
    $subject   = $is_renewal
        ? sprintf('[%s] Your license has been renewed', $site_name)
        : sprintf('[%s] Your license key', $site_name);

    $expiry_line = $expires_at
        ? sprintf("This license is valid until %s.\n", date_i18n(get_option('date_format'), strtotime($expires_at)))
        : "This is a lifetime license — it never expires.\n";

    $base_message = $is_renewal
        ? sprintf(
            "Your license has been renewed.\n\nLicense key: %s\n%s\nActivate it in your WordPress admin under My Login Form → Settings if it isn't already active.",
            $license_key,
            $expiry_line
        )
        : sprintf(
            "Thanks for your purchase!\n\nYour license key: %s\n%s\nTo activate it, install the My Login Form plugin on your WordPress site, go to My Login Form → Settings, and enter this email address and key.",
            $license_key,
            $expiry_line
        );

    // Built in parallel: plain-text lines with raw URLs for the wp_mail()
    // fallback, a styled button per item for the Resend HTML version.
    $download_text = '';
    $download_html = '';
    foreach ($download_items as $item) {
        $download_text .= sprintf("- Download My Login Form: %s\n", $item['url']);
        $download_html .= sprintf(
            '<p style="text-align:center;margin:24px 0;"><a href="%s" style="display:inline-block;background:#1FBB00;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:14px 28px;border-radius:8px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">%s</a></p>',
            esc_url($item['url']),
            esc_html('Download My Login Form')
        );
    }
    if ($download_text !== '') {
        $download_text = "\n" . $download_text;
    }

    $message = $base_message . $download_text;
    $html    = nl2br(esc_html($base_message)) . $download_html;

    $sent = mlf_send_via_resend($email, $subject, $html);
    if ($sent) {
        mlf_log("license email to {$email} sent via Resend.");
        return;
    }

    // Either MLF_RESEND_API_KEY isn't defined (mlf_send_via_resend() returned
    // false immediately, no HTTP request made — this is expected and won't
    // show up in Resend's own dashboard log) or the Resend request itself
    // failed. Either way, fall back to wp_mail() — and actually check what
    // it returns, unlike before, so a silent server-level mail failure
    // shows up here instead of nowhere.
    $wp_mail_sent = wp_mail($email, $subject, $message);
    mlf_log(
        "license email to {$email}: Resend not used (no key configured, or the request failed) — fell back to wp_mail(), which returned "
            . ($wp_mail_sent ? 'true (accepted by the mail transport).' : 'FALSE (rejected before even leaving the server — check the mail transport/SMTP setup on this site).'),
        $wp_mail_sent ? 'notice' : 'error'
    );
}

/**
 * Send a license email directly through the Resend API, bypassing wp_mail()
 * entirely. This mu-plugin runs on the *store* site, which — unlike the
 * customer-facing plugin — has no Includes/Emails/Emails.php Resend
 * integration to fall back on, so without this, license emails go out
 * through wp_mail() and inherit WordPress's default wordpress@<domain> From
 * address whenever the store has no SMTP plugin configured.
 *
 * @param string $to
 * @param string $subject
 * @param string $html
 * @return bool True if Resend accepted the email; false to let the caller
 *              fall back to wp_mail() (no API key configured, or the
 *              request failed).
 */
function mlf_send_via_resend(string $to, string $subject, string $html): bool {
    $api_key = defined('MLF_RESEND_API_KEY') ? trim((string) MLF_RESEND_API_KEY) : '';
    if ($api_key === '') {
        return false;
    }

    $from_email = defined('MLF_RESEND_FROM_EMAIL') && MLF_RESEND_FROM_EMAIL ? MLF_RESEND_FROM_EMAIL : 'noreply@omegadesign.io';
    $from_name  = defined('MLF_RESEND_FROM_NAME') && MLF_RESEND_FROM_NAME ? MLF_RESEND_FROM_NAME : 'OmegaDesign';

    $response = wp_remote_post('https://api.resend.com/emails', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => wp_json_encode([
            'from'    => sprintf('%s <%s>', $from_name, $from_email),
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $html,
        ]),
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    return $code >= 200 && $code < 300;
}
