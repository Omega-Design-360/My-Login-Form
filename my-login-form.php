<?php

/**
  * Plugin Name: My Login Form
  * Plugin URI: https://example.com/my-login-form
  * Description: Advanced login/registration with Firebase, social login, WooCommerce integration, and form builder.
  * Version: 1.5.6
  * Requires at least: 5.6
  * Requires PHP: 7.4
  * Author: Amjad Shahzad
  * License: GPL v2 or later
  * Text Domain: my-login-form
  * Domain Path: /languages
  * WC requires at least: 5.0
  * WC tested up to: 8.0
  */

 // Prevent Direct Access
 defined('ABSPATH') || exit;

 // Define Only Essential Constants
 // Read straight from the "Version:" header above instead of hardcoding it
 // a second time here — this constant is what the updater and license
 // re-confirmation gate compare against, so a copy that could drift out of
 // sync with the header has bitten us more than once.
 define('MY_LOGIN_FORM_VERSION', get_file_data(__FILE__, ['Version' => 'Version'])['Version']);
 define('MY_LOGIN_FORM_FILE', __FILE__);
 define('MY_LOGIN_FORM_DIR', plugin_dir_path(__FILE__));
 define('MY_LOGIN_FORM_URL', plugin_dir_url(__FILE__));
 define('MY_LOGIN_FORM_BASENAME', plugin_basename(__FILE__));
 define('MY_LOGIN_FORM_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

 // Define Minimum Requirements
 define('MY_LOGIN_FORM_MIN_PHP', '7.4');
 define('MY_LOGIN_FORM_MIN_WP', '5.6');

 // ============================================================================
 // LICENSING — the plugin's only secret is a URL (see the store-only
 // my-login-form-licensing-backend project's licensing/README.md).
 // Baked in here so every shipped copy requires a real license from the
 // moment it's installed, with no bypass — Gate.php only skips enforcement
 // when this is blank, which a distributed copy should never be. Override
 // via wp-config.php only if you deliberately want a different licensing
 // backend than the one baked in below.
 // ============================================================================
 if (!defined('MY_LOGIN_FORM_LICENSE_API_URL')) {
     define('MY_LOGIN_FORM_LICENSE_API_URL', 'https://wpbeudynppqqizghovbj.supabase.co/functions/v1/license-activation');
 }
 if (!defined('MY_LOGIN_FORM_BUY_URL')) {
     // Shown as a "Buy a License" link on the gate screen and the Settings
     // license card.
     define('MY_LOGIN_FORM_BUY_URL', 'https://omegadesign.io/product/my-login-plugin/');
 }
 if (!defined('MY_LOGIN_FORM_GITHUB_REPO')) {
     // "owner/repo" on GitHub — Includes/Licensing/Updater.php reads new
     // versions straight from this repo's Releases (a tagged Release is
     // what makes a new version show up as an update in wp-admin, not
     // every push). Leave blank to disable update checks entirely.
     define('MY_LOGIN_FORM_GITHUB_REPO', 'Omega-Design-360/My-Login-Form');
 }

 include MY_LOGIN_FORM_DIR . 'Includes/Core/Core.php';

 // Not autoloadable (plain functions, no class) — the spl_autoload_register
 // callback in Core.php only fires for undefined *class* references, so
 // this must be required explicitly.
 require_once MY_LOGIN_FORM_DIR . 'Includes/Functions.php';

 // Resend SMTP for wp_mail() (Task 1) — plain drop-in, not autoloaded.
 // Bails out on its own (via a top-level `return`) if OMEGA_RESEND_API_KEY
 // isn't defined in wp-config.php, so this is always safe to include.
 require_once MY_LOGIN_FORM_DIR . 'Includes/Emails/ResendSmtp.php';

 // Both must be called unconditionally here, at the top level of the main
 // plugin file — NOT deferred to 'plugins_loaded' — otherwise WordPress
 // fires 'activate_{plugin}' before register_activation_hook() ever
 // registered a callback for it (and before the Loader class the callback
 // needs is even loadable), so Activator::activate() (which creates the
 // Login/Register/My Account pages, default options, etc.) silently never
 // runs.
 \MyLoginForm\Core\Core::register_autoloader();
 \MyLoginForm\Core\Core::register_plugin_hooks();

 // This plugin only ever reads WooCommerce's public status (class_exists
 // check) and, in the store-integration snippet, order status via the
 // standard wc_get_order()/CRUD API — never direct postmeta or $wpdb
 // queries on order data — so it's safe to declare compatibility with
 // both HPOS (custom order tables) and the Cart/Checkout blocks. Without
 // this, WooCommerce shows an "incompatible plugin" warning by default
 // for any plugin that doesn't explicitly declare either way.
 add_action('before_woocommerce_init', function () {
     if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
         \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', MY_LOGIN_FORM_FILE, true);
         \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', MY_LOGIN_FORM_FILE, true);
     }
 });

 // Include the public forms renderer
 require_once MY_LOGIN_FORM_DIR . 'Public/Forms/index.php';

 // Include the shortcode handler
 require_once MY_LOGIN_FORM_DIR . 'Includes/Shortcodes/FormsShortcodes.php';

 // Initialize shortcodes after Core (priority 10) is done
 add_action('plugins_loaded', function() {
     \MyLoginForm\Shortcodes\FormsShortcodes::get_instance();
 }, 11);

 // One-time migration: copy old mlf_ option names to my_login_ option names
 add_action('init', function() {
     if (get_option('my_login_option_migration_v1')) return;

     $scalar_map = [
         'mlf_supabase_enabled'     => 'my_login_supabase_enabled',
         'mlf_supabase_url'         => 'my_login_supabase_url',
         'mlf_supabase_anon_key'    => 'my_login_supabase_anon_key',
         'mlf_supabase_service_key' => 'my_login_supabase_service_key',
         'mlf_wc_auto_login'        => 'my_login_wc_auto_login',
         'mlf_wc_sync_users'        => 'my_login_wc_sync_users',
     ];
     foreach ($scalar_map as $old => $new) {
         $val = get_option($old);
         if ($val !== false && get_option($new) === false) {
             update_option($new, $val);
         }
     }

     foreach (['google','facebook','twitter','github','linkedin','apple','microsoft'] as $p) {
         $val = get_option('mlf_' . $p . '_login_enabled');
         if ($val !== false && get_option('my_login_' . $p . '_login_enabled') === false) {
             update_option('my_login_' . $p . '_login_enabled', $val);
         }
     }

     update_option('my_login_option_migration_v1', '1');
 }, 5);

 // One-time migration: flip autoload to 'no' for options that hold license
 // keys/emails and API secrets — these were previously written without an
 // explicit autoload argument (WordPress defaults new options to autoload
 // 'yes'), which meant they loaded into memory on every single request.
 // New writes to these options already pass autoload=false explicitly (see
 // Includes/Licensing/License.php, supabase/SupabaseAjax.php, Admin/Settings.php);
 // this just corrects any row that already existed from before that change.
 add_action('init', function() {
     if (get_option('my_login_option_autoload_migration_v1')) return;

     global $wpdb;
     $sensitive_options = [
         'my_login_form_license_key',
         'my_login_form_license_email',
         'my_login_form_license_plan',
         'my_login_form_license_expires',
         'my_login_form_license_status',
         'my_login_form_license_last_valid_at',
         'my_login_supabase_anon_key',
         'my_login_supabase_service_key',
         'my_login_form_resend_api_key',
         'my_login_form_recaptcha_secret_key',
         'my_login_form_firebase_api_key',
     ];
     foreach ($sensitive_options as $option_name) {
         $wpdb->update($wpdb->options, ['autoload' => 'no'], ['option_name' => $option_name]);
     }
     wp_cache_delete('alloptions', 'options');

     update_option('my_login_option_autoload_migration_v1', '1');
 }, 6);

