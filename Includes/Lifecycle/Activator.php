<?php
namespace MyLoginForm\Lifecycle;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Activator {


    private static $instance = null;

    private function __construct() {}
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        // Set default options
        self::set_default_options();

        // Create required public directories
        self::create_form_directories();

        // Create Login / Register pages if the site doesn't already have them
        self::create_default_pages();

        // Schedule cron events
        self::schedule_events();

        // Set activation timestamp
        update_option('my_login_form_activated', current_time('mysql'));

        // Clear any cached data
        flush_rewrite_rules();
    }

    private static function create_form_directories() {
        if (!defined('MY_LOGIN_FORM_DIR')) return;

        $htaccess = "# Allow only form asset files\n"
            . "<IfModule mod_authz_core.c>\n"
            . "    Require all denied\n"
            . "    <FilesMatch \"\.(css|js|html)$\">\n"
            . "        Require all granted\n"
            . "    </FilesMatch>\n"
            . "</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n"
            . "    Order Deny,Allow\n"
            . "    Deny from all\n"
            . "    <FilesMatch \"\.(css|js|html)$\">\n"
            . "        Allow from all\n"
            . "    </FilesMatch>\n"
            . "</IfModule>";

        foreach (['css', 'js', 'html'] as $sub) {
            $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/' . $sub . '/';
            if (!file_exists($dir)) wp_mkdir_p($dir);
            if (!file_exists($dir . '.htaccess'))
                file_put_contents($dir . '.htaccess', $htaccess);
            if (!file_exists($dir . 'index.php'))
                file_put_contents($dir . 'index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * Create a "Login", "Register", "Forgot Password", "Reset Password", and
     * "My Account" page, each embedding the matching built-in form's
     * shortcode, so the plugin is usable immediately after activation with
     * no manual page setup. Never runs again once a page is tracked (or
     * found) for a given slot — activating twice, or reactivating after
     * deactivation, will not create duplicates.
     */
    private static function create_default_pages() {
        self::create_default_page(
            'login',
            __( 'Login', 'my-login-form' ),
            '[my_login_form key="login"]',
            'my_login_form_login_page_id'
        );
        self::create_default_page(
            'register',
            __( 'Register', 'my-login-form' ),
            '[my_login_form key="register"]',
            'my_login_form_register_page_id'
        );
        self::create_default_page(
            'forgot-password',
            __( 'Forgot Password', 'my-login-form' ),
            '[my_login_form key="forgot-password"]',
            'my_login_form_forgot_password_page_id'
        );
        self::create_default_page(
            'reset-password',
            __( 'Reset Password', 'my-login-form' ),
            '[my_login_form key="reset-password"]',
            'my_login_form_reset_password_page_id'
        );
        self::create_my_account_page();
    }

    /**
     * "My Account" needs special handling that the other four pages don't:
     * WooCommerce creates its own "My account" page (tracked in
     * woocommerce_myaccount_page_id) with its own [woocommerce_my_account]
     * shortcode, and its account endpoints (orders, addresses, payment
     * methods...) are hard-wired to whichever page that option points at.
     * If WooCommerce is already active with that page set up, reuse it —
     * just add our own [my_login_profile] shortcode to it — instead of
     * creating a second, competing "My Account" page.
     *
     * The reverse order (WooCommerce installed/activated *after* this
     * plugin already created its own My Account page) is handled by
     * Activator::maybe_merge_woocommerce_my_account(), hooked to
     * 'admin_init' — see Hooks::init_asset_hooks().
     */
    private static function create_my_account_page() {
        $wc_page_id = self::get_valid_page_id( get_option( 'woocommerce_myaccount_page_id' ) );
        if ( $wc_page_id ) {
            self::ensure_shortcode_on_page( $wc_page_id, '[my_login_profile]' );
            update_option( 'my_login_form_my_account_page_id', $wc_page_id );
            return;
        }

        self::create_default_page(
            'my-account',
            __( 'My Account', 'my-login-form' ),
            '[my_login_profile]',
            'my_login_form_my_account_page_id',
            '%[my_login_profile%'
        );
    }

    /**
     * Reconciles our My Account page with WooCommerce's when WooCommerce is
     * installed/activated *after* this plugin already created its own My
     * Account page (the create_my_account_page() check above only catches
     * the opposite order). Hooked to 'admin_init' — cheap (a couple of
     * get_option() calls) on every request, so it self-heals as soon as
     * WooCommerce shows up, without needing WooCommerce's own activation
     * hook to cooperate.
     */
    public static function maybe_merge_woocommerce_my_account() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $wc_page_id = self::get_valid_page_id( get_option( 'woocommerce_myaccount_page_id' ) );
        if ( ! $wc_page_id ) {
            return; // WooCommerce hasn't set up its My Account page yet.
        }

        $our_page_id = self::get_valid_page_id( get_option( 'my_login_form_my_account_page_id' ) );

        if ( $our_page_id === $wc_page_id ) {
            return; // Already the same page.
        }

        // Consolidate onto WooCommerce's page — its account endpoints are
        // hard-wired to whichever page woocommerce_myaccount_page_id points
        // at, so that's the one that has to survive.
        self::ensure_shortcode_on_page( $wc_page_id, '[my_login_profile]' );
        update_option( 'my_login_form_my_account_page_id', $wc_page_id );

        // Only ever auto-trash a page this plugin created and that nobody
        // has customized since (content is still exactly the bare
        // shortcode) — never touch a page with different/extra content.
        if ( $our_page_id ) {
            $our_page = get_post( $our_page_id );
            if ( $our_page && trim( $our_page->post_content ) === '[my_login_profile]' ) {
                wp_trash_post( $our_page_id );
            }
        }
    }

    /**
     * @param int|string $page_id
     * @return int The page ID if it's a real, non-trashed page; 0 otherwise.
     */
    private static function get_valid_page_id( $page_id ) {
        $page_id = (int) $page_id;
        if ( ! $page_id ) {
            return 0;
        }
        $page = get_post( $page_id );
        if ( $page && 'page' === $page->post_type && 'trash' !== $page->post_status ) {
            return $page_id;
        }
        return 0;
    }

    /**
     * Appends $shortcode to a page's content, unless it's already there.
     */
    private static function ensure_shortcode_on_page( $page_id, $shortcode ) {
        $page = get_post( $page_id );
        if ( ! $page || false !== strpos( $page->post_content, $shortcode ) ) {
            return;
        }
        wp_update_post( array(
            'ID'           => $page_id,
            'post_content' => rtrim( $page->post_content ) . "\n\n" . $shortcode,
        ) );
    }

    /**
     * @param string      $form_key    Shortcode's key="" value (matches form_key in the forms table).
     * @param string      $title       Page title if a new page needs to be created.
     * @param string      $shortcode   Content for a newly created page.
     * @param string      $option_name Option that tracks the resulting page ID.
     * @param string|null $search_like LIKE pattern used to find a hand-built page already
     *                                 embedding this shortcode. Defaults to the
     *                                 '[my_login_form key="..."]' pattern shared by the
     *                                 login/register/password forms; pass explicitly for
     *                                 shortcodes that don't follow that pattern (e.g. My Account).
     */
    private static function create_default_page( $form_key, $title, $shortcode, $option_name, $search_like = null ) {
        // Already tracked and the page still exists (not trashed)?
        $existing_id = (int) get_option( $option_name );
        if ( $existing_id ) {
            $existing = get_post( $existing_id );
            if ( $existing && 'page' === $existing->post_type && 'trash' !== $existing->post_status ) {
                return;
            }
        }

        // Not tracked — maybe a page already embeds this shortcode (hand-built
        // before activation, or the option was lost). Adopt it instead of
        // creating a duplicate.
        global $wpdb;
        if ( $search_like === null ) {
            $search_like = '%[my_login_form key="' . $form_key . '"%';
        }
        $found = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status != 'trash' AND post_content LIKE %s LIMIT 1",
            $search_like
        ) );
        if ( $found ) {
            update_option( $option_name, (int) $found );
            return;
        }

        $page_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_content' => $shortcode,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( $option_name, $page_id );
        }
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $default_options = array(
            'my_login_form_db_version' => '1.0.0',
            'my_login_form_version' => MY_LOGIN_FORM_VERSION,
            'my_login_form_installed' => current_time('mysql'),
            'my_login_form_default_form_style' => 'default',
            'my_login_form_enable_custom_styles' => 1,
            'my_login_form_allow_registration' => get_option('users_can_register', 0),
            'my_login_form_require_email_confirmation' => 0,
            'my_login_form_enable_google_login' => 0,
            'my_login_form_enable_facebook_login' => 0,
            'my_login_form_recaptcha_enabled' => 0,
            'my_login_form_recaptcha_site_key' => '',
            'my_login_form_recaptcha_secret_key' => '',
            'my_login_form_firebase_api_key' => '',
            'my_login_form_firebase_auth_domain' => '',
            'my_login_form_firebase_project_id' => '',
            'my_login_form_firebase_storage_bucket' => '',
            'my_login_form_firebase_messaging_sender_id' => '',
            'my_login_form_firebase_app_id' => '',
        );
        
        // Secrets should never be autoloaded (WordPress otherwise loads them
        // into memory on every single request, front-end and back-end alike).
        $non_autoloaded = ['my_login_form_recaptcha_secret_key', 'my_login_form_firebase_api_key'];

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value, '', in_array($option, $non_autoloaded, true) ? false : true);
            }
        }
    }
    
    /**
     * Schedule cron events
     */
    private static function schedule_events() {
        // Schedule daily maintenance
        if (!wp_next_scheduled('my_login_form_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'my_login_form_daily_maintenance');
        }
        
        // Schedule cleanup for old login attempts (weekly)
        if (!wp_next_scheduled('my_login_form_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'my_login_form_cleanup');
        }
    }
    
    /**
     * Add custom cron schedule intervals
     */
    public static function add_cron_schedules($schedules) {
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'my-login-form')
        );
        
        $schedules['monthly'] = array(
            'interval' => MONTH_IN_SECONDS,
            'display' => __('Once Monthly', 'my-login-form')
        );
        
        return $schedules;
    }
}

// Add custom cron schedules
add_filter('cron_schedules', array('MyLoginForm\\Lifecycle\\Activator', 'add_cron_schedules'));