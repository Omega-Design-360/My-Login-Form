<?php

/**
 * My Login Form - WooCommerce Login Integration (Task 2)
 *
 * WooCommerce's My Account page stays exactly as WooCommerce's own default
 * (dashboard, orders, addresses, downloads, payment methods) for logged-in
 * customers. The ONLY thing this class changes is what a LOGGED-OUT visitor
 * sees: instead of WooCommerce's in-place login/register form, they're
 * redirected to this plugin's own dedicated login page (redirect approach,
 * not a form swap - faster and keeps auth logic in one place).
 *
 * Where redirect_to is read back out on the login page: see
 * Includes/Shortcodes/FormsShortcodes.php, render_from_containers(), the
 * block right after `$redirect_url = $this->resolve_redirect_url(...)` -
 * it reads $_GET['redirect_to'] and feeds it into the same hidden
 * <input name="redirect_to"> that AuthAjax::handle_login() already posts
 * back to the server on submit.
 *
 * @package MyLoginForm\Integrations
 */

namespace MyLoginForm\Integrations;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Woocommerce {

    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        // 1) Logged-out visitor hits My Account (or any of its endpoints)?
        //    Send them to our login page instead of Woo's inline form.
        add_action('template_redirect', [$this, 'redirect_logged_out_my_account'], 5);

        // 2) Checkout's "Returning customer? Click here to login" box is an
        //    inline JS-toggled form, not a link - swap it for a real link to
        //    our login page.
        add_action('wp', [$this, 'maybe_replace_checkout_login_form']);

        // 3) Catch-all: anything that builds a login link the normal
        //    WordPress way (wp_login_url() / wp_loginout(), e.g. a theme's
        //    "Log in" menu link) gets pointed at our page too.
        add_filter('login_url', [$this, 'filter_login_url'], 10, 2);

        // 4) Checkout page, guest checkout disabled + account required:
        //    restyle WooCommerce's "must be logged in" prompt (both the
        //    classic template's plain message and the checkout block's
        //    client-side one) to match this plugin's own login/register pages.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_checkout_auth_notice']);
    }

    /**
     * Redirect logged-out visitors away from My Account to our login page,
     * preserving where they were headed via `redirect_to`.
     *
     * Deliberately narrow, to avoid any redirect loop:
     *  - Only fires when is_account_page() is true AND the visitor is
     *    logged out. Logged-in visitors always keep seeing Woo's own page.
     *  - Skips the `customer-logout` endpoint outright - that's a
     *    nonce-verified action URL (?customer-logout=<nonce>) that
     *    WooCommerce itself processes and redirects away from immediately;
     *    intercepting it here would race that redirect.
     *  - Skips the `lost-password` endpoint outright - WooCommerce's
     *    password-reset emails link back to this exact endpoint (with a
     *    reset key in the query string). This plugin has its own separate
     *    Forgot/Reset Password page, but Woo's own reset links must keep
     *    resolving on Woo's page, not ours.
     */
    public function redirect_logged_out_my_account(): void {
        if (is_user_logged_in()) {
            return;
        }
        if (!function_exists('is_account_page') || !is_account_page()) {
            return;
        }
        if (
            function_exists('is_wc_endpoint_url')
            && (is_wc_endpoint_url('customer-logout') || is_wc_endpoint_url('lost-password'))
        ) {
            return;
        }

        $login_url = $this->find_login_page_url();
        if (!$login_url) {
            return; // No login page configured yet - don't redirect into a 404.
        }

        wp_safe_redirect(add_query_arg('redirect_to', rawurlencode($this->current_url()), $login_url));
        exit;
    }

    /**
     * Replace WooCommerce's inline checkout login form with a link to our
     * login page. Hooked on 'wp' (after the main query, before template
     * output) so is_checkout() is reliable and we're in time to unhook
     * WooCommerce's own callback before it renders.
     */
    public function maybe_replace_checkout_login_form(): void {
        if (is_user_logged_in() || !function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        // woocommerce_checkout_login_form() is the stable, public function
        // name WooCommerce core hooks here (wc-template-functions.php).
        remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
        add_action('woocommerce_before_checkout_form', [$this, 'render_checkout_login_prompt'], 10);
    }

    public function render_checkout_login_prompt(): void {
        $login_url = $this->find_login_page_url();
        if (!$login_url) {
            return;
        }

        $href = add_query_arg('redirect_to', rawurlencode($this->current_url()), $login_url);

        printf(
            '<div class="woocommerce-info woocommerce-form-login-toggle">%s</div>',
            sprintf(
                /* translators: %s: URL of the plugin's login page */
                wp_kses_post(__('Already have an account? <a href="%s">Log in</a> to check out faster.', 'my-login-form')),
                esc_url($href)
            )
        );
    }

    /**
     * Point wp_login_url() (and anything built on it, e.g. wp_loginout())
     * at our login page instead of wp-login.php.
     *
     * @param string $login_url    The complete login URL WordPress built.
     * @param string $redirect     Where core wants the visitor sent back to after login.
     */
    public function filter_login_url(string $login_url, string $redirect): string {
        $our_login_url = $this->find_login_page_url();
        if (!$our_login_url) {
            return $login_url; // No login page configured - leave wp-login.php alone.
        }

        return $redirect
            ? add_query_arg('redirect_to', rawurlencode($redirect), $our_login_url)
            : $our_login_url;
    }

    /**
     * True when a guest hitting checkout has no way to complete the order
     * without an account — i.e. guest checkout is off and registration is
     * mandatory. Same condition WooCommerce's own checkout template checks
     * before printing its plain-text "must be logged in" message.
     */
    public function checkout_requires_login(): bool {
        if (is_user_logged_in() || !function_exists('WC') || !WC()->checkout()) {
            return false;
        }
        $checkout = WC()->checkout();
        return !$checkout->is_registration_enabled() && $checkout->is_registration_required();
    }

    /**
     * Restyle WooCommerce's "must be logged in" checkout prompt to match
     * this plugin's own login/register pages instead of WooCommerce's (or
     * the active theme's) default look.
     *
     * Handles both checkout flavors:
     *  - Classic (shortcode) checkout renders the message server-side, so
     *    it's just CSS here — see render_checkout_login_prompt() note below.
     *  - The Checkout block renders it entirely client-side from a compiled
     *    JS bundle with no PHP template or filter to hook, so
     *    Public/Assets/js/checkout-auth-notice.js patches the DOM once it mounts.
     */
    public function enqueue_checkout_auth_notice(): void {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }
        if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url()) {
            return;
        }
        if (!$this->checkout_requires_login()) {
            return;
        }

        wp_enqueue_style(
            'my-login-form-checkout-auth-notice',
            MY_LOGIN_FORM_URL . 'Public/Assets/css/checkout-auth-notice.css',
            [],
            filemtime(MY_LOGIN_FORM_DIR . 'Public/Assets/css/checkout-auth-notice.css')
        );

        wp_enqueue_script(
            'my-login-form-checkout-auth-notice',
            MY_LOGIN_FORM_URL . 'Public/Assets/js/checkout-auth-notice.js',
            [],
            filemtime(MY_LOGIN_FORM_DIR . 'Public/Assets/js/checkout-auth-notice.js'),
            true
        );

        $login_url = wp_login_url($this->current_url());

        wp_localize_script('my-login-form-checkout-auth-notice', 'myLoginCheckoutAuth', [
            'loginUrl'      => $login_url,
            'registerUrl'   => my_login_form_registration_url(),
            'showRegister'  => true,
            'title'         => __('Please log in to continue', 'my-login-form'),
            'message'       => __("You'll need to log in to complete your order. Sign in if you already have an account, or create a new one — it only takes a minute.", 'my-login-form'),
            'loginLabel'    => __('Log in now', 'my-login-form'),
            'registerLabel' => __('Create an account', 'my-login-form'),
        ]);
    }

    /**
     * Absolute URL of the page/request currently being served, including
     * its query string. Used as the `redirect_to` value so a visitor lands
     * back where they started after logging in.
     */
    private function current_url(): string {
        global $wp;
        return home_url(add_query_arg([], $wp->request));
    }

    /**
     * Resolve this plugin's login page URL by slug "login" — same page
     * Activator::create_default_pages() creates/tracks on activation via
     * the 'my_login_form_login_page_id' option (mirrors the fast path in
     * FormsShortcodes::find_login_page_url()).
     */
    private function find_login_page_url(): string {
        $page_id = (int) get_option('my_login_form_login_page_id');
        if ($page_id) {
            $page = get_post($page_id);
            if ($page && 'page' === $page->post_type && 'trash' !== $page->post_status) {
                return get_permalink($page_id);
            }
        }

        // Fallback: resolve by slug directly, in case the tracked option
        // was ever lost (page recreated by hand, option deleted, etc.).
        $page = get_page_by_path('login');
        return $page ? get_permalink($page) : '';
    }
}
