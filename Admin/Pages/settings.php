<?php
/**
 * Settings Template
 * 
 * @package MyLoginForm\Admin
 */

// Prevent Direct Access
defined('ABSPATH') || exit;

// Globalize WordPress database object
global $wpdb;

// Ensure variables are defined with defaults
$settings = $settings ?? array();
$system_info = $system_info ?? array();

// Set default values for all settings to prevent undefined array key warnings
$default_settings = array(
    'default_redirect' => 'my_profile',
    'default_redirect_registration' => 'my_profile',
    'default_redirect_url' => '',
    'default_redirect_registration_url' => '',
    'woocommerce_integration' => 0,
    'enable_recaptcha' => 0,
    'recaptcha_site_key' => '',
    'recaptcha_secret_key' => '',
    'enable_2fa' => 0,
    'max_login_attempts' => 5,
    'lockout_time' => 900,
    'session_timeout' => 3600,
    'email_verification' => 0,
    'welcome_email' => 1,
    'admin_notifications' => 1,
    'resend_api_key' => '',
    'resend_from_email' => '',
    'resend_from_name' => '',
    'custom_css' => '',
    'custom_js' => '',
    'delete_data_on_uninstall' => 0,
    'restrict_users_rest_api' => 1,
);

// Merge with existing settings to ensure all keys exist
$settings = wp_parse_args($settings, $default_settings);

// Masked hints (e.g. "re_a1••••••••••wxyz") for secrets Settings::render_settings()
// deliberately left blank in $settings — never the real value.
$secret_hints = $secret_hints ?? array();

// Get MySQL version safely
$mysql_version = 'Unknown';
if ($wpdb && method_exists($wpdb, 'get_var')) {
    $mysql_version = $wpdb->get_var("SELECT VERSION()");
    if (!$mysql_version) {
        $mysql_version = $wpdb->db_version(); // Fallback method
    }
}

// Set default system info
$default_system_info = array(
    'woocommerce_active' => class_exists('WooCommerce'),
    'plugin_version' => defined('MLF_VERSION') ? MLF_VERSION : '1.0.0',
    'wordpress_version' => get_bloginfo('version'),
    'php_version' => phpversion(),
    'mysql_version' => $mysql_version,
    'wp_memory_limit' => WP_MEMORY_LIMIT,
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'forms_count' => 0,
    'users_count' => count_users()['total_users'],
    'active_theme' => wp_get_theme()->get('Name'),
);

$system_info = wp_parse_args($system_info, $default_system_info);

// Show success message if settings were saved
if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'my-login-form') . '</p></div>';
}
?>

<div class="wrap my-login-form-settings">
    <div class="settings-header">
        <h1 class="wp-heading-inline"><i class="fas fa-cog"></i> <?php _e('Plugin Settings', 'my-login-form'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=my-login-form-dashboard'); ?>" class="page-title-action">
            <i class="fas fa-arrow-left"></i> <?php _e('Back to Dashboard', 'my-login-form'); ?>
        </a>
    </div>
    
    <hr class="wp-header-end">

    <?php
    // The card is rendered at the bottom of the page (see the closing
    // </div> below), but $is_active needs to be known now to gate the form.
    // Buffer its output here and echo the buffered markup down there instead
    // of including the file twice.
    ob_start();
    include MY_LOGIN_FORM_DIR . 'Admin/Pages/partials/license-card.php';
    $license_card_html = ob_get_clean();
    ?>

    <?php if (!$is_active): ?>
        <p style="color:#666666;font-size:13px;"><?php _e('Activate your license below to unlock the rest of these settings.', 'my-login-form'); ?></p>
    <?php else: ?>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('my_login_form_save_settings'); ?>
        <input type="hidden" name="action" value="my_login_form_save_settings">

        <div class="settings-layout">
            <!-- Main Settings -->
            <div class="settings-main">
                <!-- General Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-sliders-h"></i> <?php _e('General Settings', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-field">
                            <label for="default_redirect"><?php _e('Default Redirect After Login', 'my-login-form'); ?></label>
                            <select name="default_redirect" id="default_redirect" class="regular-text mlf-redirect-select">
                                <option value="home" <?php selected($settings['default_redirect'], 'home'); ?>><?php _e('Home Page', 'my-login-form'); ?></option>
                                <option value="my_profile" <?php selected($settings['default_redirect'], 'my_profile'); ?>><?php _e('My Account', 'my-login-form'); ?></option>
                                <option value="custom" <?php selected($settings['default_redirect'], 'custom'); ?>><?php _e('Custom URL', 'my-login-form'); ?></option>
                            </select>
                            <p class="description"><?php _e('Where users are redirected after successful login (used by any form that doesn\'t set its own redirect in the Form Designer)', 'my-login-form'); ?></p>
                            <input type="text" name="default_redirect_url" id="default_redirect_url" class="regular-text mlf-redirect-custom-url"
                                   value="<?php echo esc_attr($settings['default_redirect_url']); ?>"
                                   placeholder="https://example.com/welcome"
                                   style="<?php echo ($settings['default_redirect'] === 'custom') ? '' : 'display:none;'; ?>margin-top:8px;">
                        </div>

                        <div class="form-field">
                            <label for="default_redirect_registration"><?php _e('Redirect After Create New Account', 'my-login-form'); ?></label>
                            <select name="default_redirect_registration" id="default_redirect_registration" class="regular-text mlf-redirect-select">
                                <option value="home" <?php selected($settings['default_redirect_registration'], 'home'); ?>><?php _e('Home Page', 'my-login-form'); ?></option>
                                <option value="my_profile" <?php selected($settings['default_redirect_registration'], 'my_profile'); ?>><?php _e('My Account', 'my-login-form'); ?></option>
                                <option value="login" <?php selected($settings['default_redirect_registration'], 'login'); ?>><?php _e('Login Page', 'my-login-form'); ?></option>
                                <option value="custom" <?php selected($settings['default_redirect_registration'], 'custom'); ?>><?php _e('Custom URL', 'my-login-form'); ?></option>
                            </select>
                            <p class="description"><?php _e('Where users are redirected after successfully creating an account', 'my-login-form'); ?></p>
                            <input type="text" name="default_redirect_registration_url" id="default_redirect_registration_url" class="regular-text mlf-redirect-custom-url"
                                   value="<?php echo esc_attr($settings['default_redirect_registration_url']); ?>"
                                   placeholder="https://example.com/welcome"
                                   style="<?php echo ($settings['default_redirect_registration'] === 'custom') ? '' : 'display:none;'; ?>margin-top:8px;">
                        </div>

                        <div class="form-field">
                            <label for="guest_default_name"><?php _e('Default Guest Name', 'my-login-form'); ?></label>
                            <input type="text" name="guest_default_name" id="guest_default_name" class="regular-text"
                                   value="<?php echo esc_attr($settings['guest_default_name'] ?? 'Sunshine'); ?>"
                                   placeholder="Sunshine">
                            <p class="description"><?php _e('Shown in place of {name} (e.g. in the "Welcome, {name}!" greeting block) for visitors who aren\'t logged in yet. Logged-in users always see their own first name, last name, or display name instead.', 'my-login-form'); ?></p>
                        </div>

                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="woocommerce_integration" value="1"
                                       <?php checked($settings['woocommerce_integration'], 1); ?>
                                       <?php echo !$system_info['woocommerce_active'] ? 'disabled' : ''; ?>>
                                <span><?php _e('Enable WooCommerce Integration', 'my-login-form'); ?></span>
                            </label>
                            <?php if ($system_info['woocommerce_active']): ?>
                                <p class="description success"><i class="fas fa-check-circle"></i> <?php _e('WooCommerce is active. Integration enabled.', 'my-login-form'); ?></p>
                            <?php else: ?>
                                <p class="description warning"><i class="fas fa-exclamation-triangle"></i> <?php _e('WooCommerce is not installed or activated.', 'my-login-form'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-shield-alt"></i> <?php _e('Security Settings', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">

                        <!-- reCAPTCHA -->
                        <div class="mlf-toggle-row">
                            <div class="mlf-toggle-info">
                                <span class="mlf-toggle-title"><?php _e('Google reCAPTCHA', 'my-login-form'); ?></span>
                                <span class="mlf-toggle-desc"><?php _e('Block bots and spam submissions on all forms', 'my-login-form'); ?></span>
                            </div>
                            <label class="mlf-switch">
                                <input type="checkbox" name="enable_recaptcha" id="enable_recaptcha" value="1" <?php checked($settings['enable_recaptcha'], 1); ?>>
                                <span class="mlf-slider"></span>
                            </label>
                        </div>
                        <div class="mlf-sub-settings" id="recaptcha_fields" <?php echo !$settings['enable_recaptcha'] ? 'style="display:none"' : ''; ?>>
                            <div class="mlf-input-row">
                                <label><?php _e('Site Key', 'my-login-form'); ?></label>
                                <input type="text" name="recaptcha_site_key" id="recaptcha_site_key"
                                       value="<?php echo esc_attr($settings['recaptcha_site_key']); ?>"
                                       placeholder="6Le..." class="mlf-text-input">
                            </div>
                            <div class="mlf-input-row" style="margin-top:10px;">
                                <label><?php _e('Secret Key', 'my-login-form'); ?></label>
                                <input type="password" name="recaptcha_secret_key" id="recaptcha_secret_key"
                                       value=""
                                       placeholder="<?php echo !empty($secret_hints['recaptcha_secret_key']) ? esc_attr(sprintf(__('Saved: %s — leave blank to keep', 'my-login-form'), $secret_hints['recaptcha_secret_key'])) : '6Le...'; ?>"
                                       class="mlf-text-input">
                            </div>
                        </div>

                        <div class="mlf-divider"></div>

                        <!-- 2FA -->
                        <div class="mlf-toggle-row">
                            <div class="mlf-toggle-info">
                                <span class="mlf-toggle-title"><?php _e('Two-Factor Authentication (2FA)', 'my-login-form'); ?></span>
                                <span class="mlf-toggle-desc"><?php _e('Require a one-time code in addition to the password', 'my-login-form'); ?></span>
                            </div>
                            <label class="mlf-switch">
                                <input type="checkbox" name="enable_2fa" value="1" <?php checked($settings['enable_2fa'], 1); ?>>
                                <span class="mlf-slider"></span>
                            </label>
                        </div>

                        <div class="mlf-divider"></div>

                        <!-- REST API user data protection -->
                        <p class="mlf-section-sub"><i class="fas fa-user-shield" style="margin-right:6px;"></i><?php _e('API & Data Privacy', 'my-login-form'); ?></p>
                        <div class="mlf-toggle-row">
                            <div class="mlf-toggle-info">
                                <span class="mlf-toggle-title"><?php _e('Restrict User Data via REST API', 'my-login-form'); ?></span>
                                <span class="mlf-toggle-desc"><?php _e('Closes off the sensitive "edit" view of WordPress\'s built-in /wp-json/wp/v2/users REST endpoint (email, roles, and any extra fields other plugins attach) so only site administrators can see it, and everyone else can only ever fetch their own record that way. The normal public author info (name, avatar, bio) that WordPress itself already exposes — used by the block editor\'s author picker, embeds, etc. — is left untouched, so this won\'t affect the editor or front-end.', 'my-login-form'); ?></span>
                            </div>
                            <label class="mlf-switch">
                                <input type="checkbox" name="restrict_users_rest_api" value="1" <?php checked($settings['restrict_users_rest_api'], 1); ?>>
                                <span class="mlf-slider"></span>
                            </label>
                        </div>

                        <div class="mlf-divider"></div>

                        <!-- Brute-force protection -->
                        <p class="mlf-section-sub"><i class="fas fa-lock" style="margin-right:6px;"></i><?php _e('Brute Force Protection', 'my-login-form'); ?></p>
                        <div class="mlf-number-grid">
                            <div class="mlf-number-card">
                                <label><?php _e('Max Login Attempts', 'my-login-form'); ?></label>
                                <div class="mlf-number-wrap">
                                    <button type="button" class="mlf-num-btn" data-target="max_login_attempts" data-dir="-1">−</button>
                                    <input type="number" name="max_login_attempts" id="max_login_attempts"
                                           value="<?php echo esc_attr($settings['max_login_attempts']); ?>"
                                           min="1" max="20" class="mlf-num-input">
                                    <button type="button" class="mlf-num-btn" data-target="max_login_attempts" data-dir="1">+</button>
                                </div>
                                <span class="mlf-num-hint"><?php _e('Failed attempts before lockout', 'my-login-form'); ?></span>
                            </div>
                            <div class="mlf-number-card">
                                <label><?php _e('Lockout Duration', 'my-login-form'); ?></label>
                                <div class="mlf-duration-wrap">
                                    <input type="number" name="lockout_time_mins" id="lockout_time_mins"
                                           value="<?php echo esc_attr(round(intval($settings['lockout_time']) / 60)); ?>"
                                           min="1" max="1440" class="mlf-num-input" style="width:70px;">
                                    <span class="mlf-unit"><?php _e('min', 'my-login-form'); ?></span>
                                    <input type="hidden" name="lockout_time" id="lockout_time" value="<?php echo esc_attr($settings['lockout_time']); ?>">
                                </div>
                                <span class="mlf-num-hint"><?php _e('Lock duration after max attempts', 'my-login-form'); ?></span>
                            </div>
                            <div class="mlf-number-card">
                                <label><?php _e('Session Timeout', 'my-login-form'); ?></label>
                                <div class="mlf-duration-wrap">
                                    <input type="number" name="session_timeout_hrs" id="session_timeout_hrs"
                                           value="<?php echo esc_attr(round(intval($settings['session_timeout']) / 3600, 1)); ?>"
                                           min="0.5" max="720" step="0.5" class="mlf-num-input" style="width:70px;">
                                    <span class="mlf-unit"><?php _e('hrs', 'my-login-form'); ?></span>
                                    <input type="hidden" name="session_timeout" id="session_timeout" value="<?php echo esc_attr($settings['session_timeout']); ?>">
                                </div>
                                <span class="mlf-num-hint"><?php _e('Auto logout after inactivity', 'my-login-form'); ?></span>
                            </div>
                        </div>

                        <div class="mlf-divider"></div>

                        <!-- User / Registration Limitations -->
                        <p class="mlf-section-sub"><i class="fas fa-users" style="margin-right:6px;"></i><?php _e('User & Registration Limits', 'my-login-form'); ?></p>

                        <div class="mlf-toggle-row">
                            <div class="mlf-toggle-info">
                                <span class="mlf-toggle-title"><?php _e('Allow New Registrations', 'my-login-form'); ?></span>
                                <span class="mlf-toggle-desc"><?php _e('When off, registration forms display a "closed" notice', 'my-login-form'); ?></span>
                            </div>
                            <label class="mlf-switch">
                                <input type="checkbox" name="allow_registration" id="allow_registration" value="1"
                                       <?php checked(isset($settings['allow_registration']) ? $settings['allow_registration'] : get_option('users_can_register'), 1); ?>>
                                <span class="mlf-slider"></span>
                            </label>
                        </div>

                        <div class="mlf-input-row" style="margin-top:14px;">
                            <label><?php _e('Default Role for New Users', 'my-login-form'); ?></label>
                            <select name="default_role" id="default_role_select" class="mlf-select-input">
                                <?php
                                // These forms register front-end site visitors, not staff — the
                                // list is intentionally limited to Customer/Subscriber. This is a
                                // UI-level guardrail; AuthAjax::handle_register() enforces the same
                                // allow-list server-side regardless of what's saved here.
                                $saved_role = $settings['default_role'] ?? get_option('default_role', 'subscriber');
                                $all_roles = get_editable_roles();
                                $allowed_role_keys = array_values(array_filter(['customer', 'subscriber'], function ($r) use ($all_roles) {
                                    return isset($all_roles[$r]);
                                }));
                                if (!in_array($saved_role, $allowed_role_keys, true)) {
                                    $saved_role = 'subscriber';
                                }
                                foreach ($allowed_role_keys as $role_key) {
                                    $selected = selected($saved_role, $role_key, false);
                                    echo '<option value="' . esc_attr($role_key) . '" ' . $selected . '>' . esc_html($all_roles[$role_key]['name']) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Role assigned automatically after registration', 'my-login-form'); ?></p>
                        </div>

                        <div class="mlf-input-row" style="margin-top:14px;">
                            <label><?php _e('Allowed Email Domains', 'my-login-form'); ?>
                                <span class="mlf-badge-optional"><?php _e('optional', 'my-login-form'); ?></span>
                            </label>
                            <input type="text" name="allowed_email_domains" id="allowed_email_domains"
                                   value="<?php echo esc_attr($settings['allowed_email_domains'] ?? ''); ?>"
                                   placeholder="gmail.com, company.org"
                                   class="mlf-text-input">
                            <p class="description"><?php _e('Comma-separated list. Leave empty to allow any domain.', 'my-login-form'); ?></p>
                        </div>

                        <div class="mlf-input-row" style="margin-top:14px;">
                            <label><?php _e('Blocked IP Addresses', 'my-login-form'); ?>
                                <span class="mlf-badge-optional"><?php _e('optional', 'my-login-form'); ?></span>
                            </label>
                            <textarea name="blocked_ips" id="blocked_ips" rows="3" class="mlf-text-input"
                                      placeholder="192.168.1.1&#10;10.0.0.1"><?php echo esc_textarea($settings['blocked_ips'] ?? ''); ?></textarea>
                            <p class="description"><?php _e('One IP address per line. Blocked IPs cannot log in or register.', 'my-login-form'); ?></p>
                        </div>

                        <div class="mlf-input-row" style="margin-top:14px;">
                            <label><?php _e('Blocked Usernames / Emails', 'my-login-form'); ?>
                                <span class="mlf-badge-optional"><?php _e('optional', 'my-login-form'); ?></span>
                            </label>
                            <textarea name="blocked_users" id="blocked_users" rows="3" class="mlf-text-input"
                                      placeholder="spammer@example.com&#10;baduser"><?php echo esc_textarea($settings['blocked_users'] ?? ''); ?></textarea>
                            <p class="description"><?php _e('One username or email per line (case-insensitive). Blocked accounts cannot log in or register.', 'my-login-form'); ?></p>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="settings-sidebar">
                <!-- System Information -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> <?php _e('System Information', 'my-login-form'); ?></h3>
                    </div>
                    <div class="card-body">
                        <table class="system-info-table">
                            <tr>
                                <td><?php _e('Plugin Version', 'my-login-form'); ?>:</td>
                                <td><strong><?php echo esc_html($system_info['plugin_version']); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('WordPress', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['wordpress_version']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('PHP Version', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['php_version']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('MySQL Version', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['mysql_version']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Memory Limit', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['wp_memory_limit']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Max Execution Time', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['max_execution_time']); ?>s</strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Upload Limit', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['upload_max_filesize']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> <?php _e('Statistics', 'my-login-form'); ?></h3>
                    </div>
                    <div class="card-body">
                        <table class="system-info-table">
                            <tr>
                                <td><?php _e('Total Forms', 'my-login-form'); ?>:</td>
                                <td><strong><?php echo number_format_i18n($system_info['forms_count']); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Total Users', 'my-login-form'); ?>:</td>
                                <td><strong><?php echo number_format_i18n($system_info['users_count']); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Active Theme', 'my-login-form'); ?>:</strong></td>
                                <td><?php echo esc_html($system_info['active_theme']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> <?php _e('Quick Actions', 'my-login-form'); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <button type="button" onclick="clearPluginCache()" class="button button-block">
                                <i class="fas fa-trash-alt"></i> <?php _e('Clear Plugin Cache', 'my-login-form'); ?>
                            </button>
                            <button type="button" onclick="resetSettings()" class="button button-block button-warning">
                                <i class="fas fa-undo-alt"></i> <?php _e('Reset to Defaults', 'my-login-form'); ?>
                            </button>
                            <button type="button" onclick="exportSettings()" class="button button-block">
                                <i class="fas fa-download"></i> <?php _e('Export Settings', 'my-login-form'); ?>
                            </button>
                            <label for="import-file" class="button button-block">
                                <i class="fas fa-upload"></i> <?php _e('Import Settings', 'my-login-form'); ?>
                                <input type="file" id="import-file" style="display: none;" accept=".json">
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Documentation -->
                <div class="settings-card docs-card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> <?php _e('Documentation', 'my-login-form'); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="docs-links">
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-play-circle"></i> <?php _e('Getting Started Guide', 'my-login-form'); ?>
                            </a>
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-video"></i> <?php _e('Video Tutorials', 'my-login-form'); ?>
                            </a>
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-question-circle"></i> <?php _e('FAQ', 'my-login-form'); ?>
                            </a>
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-bug"></i> <?php _e('Report a Bug', 'my-login-form'); ?>
                            </a>
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-star"></i> <?php _e('Rate this Plugin', 'my-login-form'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Settings (full width) -->
        <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-envelope"></i> <?php _e('Email Settings', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="email_verification" value="1" 
                                       <?php checked($settings['email_verification'], 1); ?>>
                                <span><?php _e('Require Email Verification', 'my-login-form'); ?></span>
                            </label>
                            <p class="description"><?php _e('New users must enter a 6-digit code emailed to them (sent via Supabase) before they can log in. Requires a connected Supabase project, and its "Confirm signup" email template must use {{ .Token }} instead of the default magic link.', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="welcome_email" value="1" 
                                       <?php checked($settings['welcome_email'], 1); ?>>
                                <span><?php _e('Send Welcome Email', 'my-login-form'); ?></span>
                            </label>
                            <p class="description"><?php _e('Send a welcome email to new users after registration', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="admin_notifications" value="1"
                                       <?php checked($settings['admin_notifications'], 1); ?>>
                                <span><?php _e('Admin Notifications', 'my-login-form'); ?></span>
                            </label>
                            <p class="description"><?php _e('Notify admin when new users register', 'my-login-form'); ?></p>
                        </div>

                        <div class="mlf-divider"></div>

                        <p class="mlf-section-sub"><i class="fas fa-paper-plane" style="margin-right:6px;"></i><?php _e('Resend (Mail Delivery)', 'my-login-form'); ?></p>
                        <p class="description" style="margin-top:-6px;"><?php _e('Optional — routes every email this site sends (password resets, WooCommerce order emails, etc.) through Resend instead of your host\'s default mail server, which is often unreliable or ends up in spam. Leave the API key blank to keep using the default.', 'my-login-form'); ?></p>

                        <div class="form-field">
                            <label for="resend_api_key"><?php _e('Resend API Key', 'my-login-form'); ?></label>
                            <input type="password" name="resend_api_key" id="resend_api_key" class="regular-text"
                                   value=""
                                   placeholder="<?php echo !empty($secret_hints['resend_api_key']) ? esc_attr(sprintf(__('Saved: %s — leave blank to keep', 'my-login-form'), $secret_hints['resend_api_key'])) : 're_xxxxxxxxxxxxxxxxxxxxxxxx'; ?>"
                                   autocomplete="off">
                        </div>

                        <div class="form-field">
                            <label for="resend_from_email"><?php _e('From Email', 'my-login-form'); ?></label>
                            <input type="email" name="resend_from_email" id="resend_from_email" class="regular-text"
                                   value="<?php echo esc_attr($settings['resend_from_email']); ?>"
                                   placeholder="noreply@yourdomain.com">
                            <p class="description"><?php _e('Must be on a domain you\'ve verified in your Resend account.', 'my-login-form'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="resend_from_name"><?php _e('From Name', 'my-login-form'); ?></label>
                            <input type="text" name="resend_from_name" id="resend_from_name" class="regular-text"
                                   value="<?php echo esc_attr($settings['resend_from_name']); ?>"
                                   placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        </div>
                    </div>
                </div>

                <!-- Custom CSS & JS -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-code"></i> <?php _e('Custom Code', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-field">
                            <label for="custom_css"><?php _e('Global Custom CSS', 'my-login-form'); ?></label>
                            <textarea name="custom_css" id="custom_css" rows="10" class="code-editor" 
                                      placeholder="<?php _e('/* Add global CSS that applies to all forms */', 'my-login-form'); ?>"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                            <p class="description"><?php _e('CSS added here will be applied to all forms site-wide', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="custom_js"><?php _e('Global Custom JavaScript', 'my-login-form'); ?></label>
                            <textarea name="custom_js" id="custom_js" rows="10" class="code-editor" 
                                      placeholder="<?php _e('// Add global JavaScript for all forms', 'my-login-form'); ?>"><?php echo esc_textarea($settings['custom_js']); ?></textarea>
                            <p class="description"><?php _e('JavaScript added here will run on all forms site-wide', 'my-login-form'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-exclamation-triangle"></i> <?php _e('Advanced Settings', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="delete_data_on_uninstall" value="1" 
                                       <?php checked($settings['delete_data_on_uninstall'], 1); ?>>
                                <span><?php _e('Delete Data on Uninstall', 'my-login-form'); ?></span>
                            </label>
                            <p class="description warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <?php _e('Warning: All plugin data will be permanently deleted when the plugin is uninstalled.', 'my-login-form'); ?>
                            </p>
                        </div>
                    </div>
                </div>

        <!-- Submit Button -->
        <div class="settings-footer">
            <button type="submit" class="button button-primary button-hero">
                <i class="fas fa-save"></i> <?php _e('Save All Settings', 'my-login-form'); ?>
            </button>
            <p class="description"><?php _e('Settings will be applied immediately after saving', 'my-login-form'); ?></p>
        </div>
    </form>
    <?php endif; ?>

    <?php echo $license_card_html; ?>
</div>

<script>
jQuery(document).ready(function($){
    // reCAPTCHA toggle
    $('#enable_recaptcha').on('change', function(){
        $('#recaptcha_fields').toggle(this.checked);
    });

    // Show the matching "Custom URL" text field only when its select is set to "custom"
    $('.mlf-redirect-select').on('change', function(){
        $(this).closest('.form-field').find('.mlf-redirect-custom-url').toggle(this.value === 'custom');
    });

    // +/- buttons for brute force numbers
    $(document).on('click', '.mlf-num-btn', function(){
        var target = $(this).data('target');
        var dir    = parseInt($(this).data('dir'));
        var $inp   = $('#' + target);
        var val    = parseInt($inp.val()) || 0;
        var min    = parseInt($inp.attr('min')) || 1;
        var max    = parseInt($inp.attr('max')) || 9999;
        $inp.val(Math.min(max, Math.max(min, val + dir)));
    });

    // Sync lockout minutes → hidden seconds field on form submit
    $('form').on('submit', function(){
        var mins = parseInt($('#lockout_time_mins').val()) || 15;
        $('#lockout_time').val(mins * 60);
        var hrs = parseFloat($('#session_timeout_hrs').val()) || 1;
        $('#session_timeout').val(Math.round(hrs * 3600));
    });
});
</script>
