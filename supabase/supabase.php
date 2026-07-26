<?php
/**
 * Enterprise Supabase & Social Media Integration Hub - COMPLETE SETUP PROCESS
 * 
 * @package MyLoginForm\Admin\Pages
 * @version 2.0.0
 */

// Prevent Direct Access
defined('ABSPATH') || exit;

// AJAX handlers are registered in supabase/SupabaseAjax.php (always loaded)

// ============================================================================
// GET CURRENT SETTINGS
// ============================================================================
$supabase_enabled = get_option('my_login_supabase_enabled', 0);
$supabase_url = get_option('my_login_supabase_url', '');
$supabase_anon_key = get_option('my_login_supabase_anon_key', '');
$supabase_service_key = get_option('my_login_supabase_service_key', '');
$supabase_connected = $supabase_enabled && $supabase_url && $supabase_anon_key;

/**
 * Short, non-reversible hint for a secret — enough for an admin to recognize
 * "yes, this is the key I already saved", never enough to reconstruct it.
 * The Setup form uses this as a placeholder only; the real key is never
 * written into a value="" attribute, so it never sits in the page's HTML
 * source. See SupabaseAjax::save_connection()/test_connection() for the
 * matching "blank submission = keep what's already saved" handling.
 */
if (!function_exists('my_login_form_mask_secret_hint')) {
    function my_login_form_mask_secret_hint($key) {
        $key = (string) $key;
        if ($key === '') {
            return '';
        }
        if (strlen($key) <= 8) {
            return str_repeat('•', strlen($key));
        }
        return substr($key, 0, 4) . str_repeat('•', 10) . substr($key, -4);
    }
}
$supabase_anon_key_hint    = my_login_form_mask_secret_hint($supabase_anon_key);
$supabase_service_key_hint = my_login_form_mask_secret_hint($supabase_service_key);

// Save social login providers
if (isset($_POST['save_social_login']) && check_admin_referer('my_login_social_login_settings')) {
    foreach (['google','facebook','twitter','github','linkedin','apple','microsoft'] as $pkey) {
        update_option('my_login_' . $pkey . '_login_enabled', isset($_POST['login_' . $pkey . '_enabled']) ? 1 : 0);
    }
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Social login providers saved!', 'my-login-form') . '</p></div>';
}

// Clear OTP audit log (Includes/Database/OtpDatabase.php)
if (isset($_POST['clear_otp_log']) && check_admin_referer('my_login_clear_otp_log')) {
    global $wpdb;
    $otp_db = class_exists('MyLoginForm\\Database\\Database') ? \MyLoginForm\Database\Database::get_instance()->otp() : null;
    if ($otp_db && $otp_db->table_exists()) {
        $wpdb->query('TRUNCATE TABLE ' . $otp_db->get_table_name());
        echo '<div class="notice notice-success is-dismissible"><p>' . __('OTP log cleared!', 'my-login-form') . '</p></div>';
    }
}

$woocommerce_active = class_exists('WooCommerce');
$woocommerce_version = $woocommerce_active ? WC_VERSION : '';

$social_platforms = [
    'facebook' => ['name' => 'Facebook', 'icon' => 'fab fa-facebook', 'color' => '#1877f2', 'gradient' => 'linear-gradient(135deg, #1877f2, #0c5bd0)', 'enabled' => get_option('my_login_facebook_enabled', 1)],
    'twitter' => ['name' => 'Twitter', 'icon' => 'fab fa-twitter', 'color' => '#1da1f2', 'gradient' => 'linear-gradient(135deg, #1da1f2, #0d8bd0)', 'enabled' => get_option('my_login_twitter_enabled', 1)],
    'linkedin' => ['name' => 'LinkedIn', 'icon' => 'fab fa-linkedin', 'color' => '#0077b5', 'gradient' => 'linear-gradient(135deg, #0077b5, #005582)', 'enabled' => get_option('my_login_linkedin_enabled', 1)],
    'instagram' => ['name' => 'Instagram', 'icon' => 'fab fa-instagram', 'color' => '#e4405f', 'gradient' => 'linear-gradient(135deg, #e4405f, #c22046)', 'enabled' => get_option('my_login_instagram_enabled', 0)],
    'pinterest' => ['name' => 'Pinterest', 'icon' => 'fab fa-pinterest', 'color' => '#bd081c', 'gradient' => 'linear-gradient(135deg, #bd081c, #8a0614)', 'enabled' => get_option('my_login_pinterest_enabled', 0)],
    'whatsapp' => ['name' => 'WhatsApp', 'icon' => 'fab fa-whatsapp', 'color' => '#25d366', 'gradient' => 'linear-gradient(135deg, #25d366, #128c7e)', 'enabled' => get_option('my_login_whatsapp_enabled', 1)],
    'telegram' => ['name' => 'Telegram', 'icon' => 'fab fa-telegram', 'color' => '#0088cc', 'gradient' => 'linear-gradient(135deg, #0088cc, #005a8a)', 'enabled' => get_option('my_login_telegram_enabled', 1)],
];

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'setup';

// ============================================================================
// OTP LOG TAB — data prep (Includes/Database/OtpDatabase.php)
// ============================================================================
$otp_log_rows        = [];
$otp_log_total       = 0;
$otp_log_page        = 1;
$otp_log_per_page    = 20;
$otp_log_total_pages = 1;
$otp_log_context     = 'all';
$otp_log_action      = 'all';

if ($active_tab === 'otp-log') {
    global $wpdb;
    $otp_db = class_exists('MyLoginForm\\Database\\Database') ? \MyLoginForm\Database\Database::get_instance()->otp() : null;

    if ($otp_db && $otp_db->table_exists()) {
        $table = $otp_db->get_table_name();

        $otp_log_context = isset($_GET['otp_context']) ? sanitize_key($_GET['otp_context']) : 'all';
        $otp_log_action  = isset($_GET['otp_action'])  ? sanitize_key($_GET['otp_action'])  : 'all';
        $otp_log_page    = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;

        $where  = ['1=1'];
        $params = [];

        if (in_array($otp_log_context, ['register', 'login', 'forgot_password'], true)) {
            $where[]  = 'context = %s';
            $params[] = $otp_log_context;
        }
        if (in_array($otp_log_action, ['sent', 'send_failed', 'verified', 'failed'], true)) {
            $where[]  = 'action = %s';
            $params[] = $otp_log_action;
        }

        $where_sql = implode(' AND ', $where);

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $otp_log_total = (int) ($params ? $wpdb->get_var($wpdb->prepare($count_sql, $params)) : $wpdb->get_var($count_sql));

        $otp_log_total_pages = max(1, (int) ceil($otp_log_total / $otp_log_per_page));
        $otp_log_page        = min($otp_log_page, $otp_log_total_pages);
        $offset              = ($otp_log_page - 1) * $otp_log_per_page;

        $rows_sql    = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
        $rows_params = array_merge($params, [$otp_log_per_page, $offset]);
        $otp_log_rows = $wpdb->get_results($wpdb->prepare($rows_sql, $rows_params));
    }
}
?>

<div class="wrap my-login-integration-hub">
    
    <!-- Header with Connection Status -->
    <div class="integration-header">
        <div class="header-left">
            <h1>
                <i class="fas fa-cloud-upload-alt"></i> 
                <?php _e('Integration Hub', 'my-login-form'); ?>
            </h1>
            <p class="header-description">
                <?php _e('Connect your WordPress site with Supabase and Social Media', 'my-login-form'); ?>
            </p>
        </div>
        <div class="header-right">
            <div class="connection-status-card" id="global-connection-status">
                <?php if ($supabase_connected): ?>
                    <div class="status-connected">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong><?php _e('Supabase Connected', 'my-login-form'); ?></strong>
                            <small><?php echo esc_url($supabase_url); ?></small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="status-disconnected">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong><?php _e('Supabase Not Connected', 'my-login-form'); ?></strong>
                            <small><?php _e('Complete setup below', 'my-login-form'); ?></small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Step-by-Step Setup Wizard -->
    <?php if (!$supabase_connected): ?>
    <div class="setup-wizard my-login-friendly-wizard">
        <div class="wizard-header">
            <div style="font-size:42px;line-height:1;margin-bottom:10px;">🔌</div>
            <h2><?php _e('Connect to Supabase — Complete Setup Guide', 'my-login-form'); ?></h2>
            <p><?php _e('Follow these steps exactly — no technical experience needed!', 'my-login-form'); ?></p>
        </div>
        <div class="wizard-steps my-login-steps-row">

            <div class="wizard-step my-login-step active">
                <div class="my-login-step-num">1</div>
                <h3>🌐 <?php _e('Create a Free Supabase Account', 'my-login-form'); ?></h3>
                <p><?php _e('Visit supabase.com and click "Start your project". Sign up with your GitHub account or email — it\'s completely free.', 'my-login-form'); ?></p>
                <a href="https://supabase.com/dashboard/sign-up" target="_blank" class="my-login-step-cta"><?php _e('Sign Up Free →', 'my-login-form'); ?></a>
            </div>

            <div class="my-login-step-sep">›</div>

            <div class="wizard-step my-login-step">
                <div class="my-login-step-num">2</div>
                <h3>📁 <?php _e('Create a New Project', 'my-login-form'); ?></h3>
                <p>
                    <?php _e('After logging in:', 'my-login-form'); ?><br>
                    <strong>1.</strong> <?php _e('Click the green "New Project" button', 'my-login-form'); ?><br>
                    <strong>2.</strong> <?php _e('Enter a project name (e.g. "my-website")', 'my-login-form'); ?><br>
                    <strong>3.</strong> <?php _e('Set a strong database password (save it somewhere safe)', 'my-login-form'); ?><br>
                    <strong>4.</strong> <?php _e('Choose a region closest to your visitors', 'my-login-form'); ?><br>
                    <strong>5.</strong> <?php _e('Click "Create new project" and wait ~1 minute', 'my-login-form'); ?>
                </p>
            </div>

            <div class="my-login-step-sep">›</div>

            <div class="wizard-step my-login-step">
                <div class="my-login-step-num">3</div>
                <h3>🔑 <?php _e('Get Your API Keys', 'my-login-form'); ?></h3>
                <p>
                    <?php _e('Once your project is ready:', 'my-login-form'); ?><br>
                    <strong>1.</strong> <?php _e('Click the ⚙️ <strong>Settings</strong> icon in the left sidebar', 'my-login-form'); ?><br>
                    <strong>2.</strong> <?php _e('Click <strong>API</strong> in the Settings menu', 'my-login-form'); ?><br>
                    <strong>3.</strong> <?php _e('Under "Project URL" — copy the URL (starts with https://)', 'my-login-form'); ?><br>
                    <strong>4.</strong> <?php _e('Under "Project API keys" — copy the <strong>anon / public</strong> key (long text starting with eyJ...)', 'my-login-form'); ?>
                </p>
                <a href="https://supabase.com/dashboard" target="_blank" class="my-login-step-cta-outline"><?php _e('Open Supabase →', 'my-login-form'); ?></a>
            </div>

            <div class="my-login-step-sep">›</div>

            <div class="wizard-step my-login-step">
                <div class="my-login-step-num">4</div>
                <h3>📋 <?php _e('Paste Keys & Connect', 'my-login-form'); ?></h3>
                <p>
                    <?php _e('In the "Connection Setup" box below:', 'my-login-form'); ?><br>
                    <strong>1.</strong> <?php _e('Paste your <strong>Project URL</strong> in the first field', 'my-login-form'); ?><br>
                    <strong>2.</strong> <?php _e('Paste your <strong>anon / public key</strong> in the second field', 'my-login-form'); ?><br>
                    <strong>3.</strong> <?php _e('Click <strong>Test Connection</strong> — you should see a green success message', 'my-login-form'); ?><br>
                    <strong>4.</strong> <?php _e('Click <strong>Save &amp; Connect</strong> — done!', 'my-login-form'); ?>
                </p>
                <a href="#supabase-connection-form" class="my-login-step-cta">↓ <?php _e('Go to Setup Form', 'my-login-form'); ?></a>
            </div>

        </div>

        <!-- Extra help box -->
        <div style="margin-top:18px;padding:14px 18px;background:rgba(255,255,255,.07);border-radius:8px;font-size:13px;color:rgba(234,246,228,0.7);line-height:1.7;">
            <strong style="color:#EAF6E4;">💡 <?php _e('Tips', 'my-login-form'); ?>:</strong>
            <?php _e('The free Supabase plan is enough for most websites. You don\'t need to touch any code. If the "Test Connection" button shows an error, double-check that you copied the full URL and key without any extra spaces.', 'my-login-form'); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tab Navigation -->
    <div class="integration-tabs">
        <a href="?page=my-login-form-social-supabase&tab=setup" class="tab-link <?php echo $active_tab === 'setup' ? 'active' : ''; ?>">
            <i class="fas fa-plug"></i> <?php _e('Connect Setup', 'my-login-form'); ?>
            <?php if (!$supabase_connected): ?>
                <span class="tab-badge warning">Required</span>
            <?php endif; ?>
        </a>
        <a href="?page=my-login-form-social-supabase&tab=supabase" class="tab-link <?php echo $active_tab === 'supabase' ? 'active' : ''; ?>">
            <i class="fas fa-database"></i> <?php _e('Supabase', 'my-login-form'); ?>
        </a>
        <a href="?page=my-login-form-social-supabase&tab=social" class="tab-link <?php echo $active_tab === 'social' ? 'active' : ''; ?>">
            <i class="fas fa-share-alt"></i> <?php _e('Social Media', 'my-login-form'); ?>
        </a>
        <a href="?page=my-login-form-social-supabase&tab=analytics" class="tab-link <?php echo $active_tab === 'analytics' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> <?php _e('Analytics', 'my-login-form'); ?>
        </a>
        <a href="?page=my-login-form-social-supabase&tab=woocommerce" class="tab-link <?php echo $active_tab === 'woocommerce' ? 'active' : ''; ?>">
            <i class="fab fa-woocommerce"></i> <?php _e('WooCommerce', 'my-login-form'); ?>
        </a>
        <a href="?page=my-login-form-social-supabase&tab=otp-log" class="tab-link <?php echo $active_tab === 'otp-log' ? 'active' : ''; ?>">
            <i class="fas fa-key"></i> <?php _e('OTP Log', 'my-login-form'); ?>
        </a>
    </div>
    
    <div class="tab-content-wrapper">
        
        <!-- ==================== SETUP TAB - COMPLETE CONNECTION PROCESS ==================== -->
        <?php if ($active_tab === 'setup'): ?>
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fas fa-plug"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Supabase Connection Setup', 'my-login-form'); ?></h2>
                    <p><?php _e('Enter your Supabase credentials to establish a connection', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="card-body">
                <div class="connection-form" id="supabase-connection-form">
                    <div class="form-group">
                        <label for="setup_supabase_url">
                            <i class="fas fa-link"></i> <?php _e('Supabase Project URL', 'my-login-form'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="url" id="setup_supabase_url" class="large-input" 
                               value="<?php echo esc_url($supabase_url); ?>" 
                               placeholder="https://your-project.supabase.co">
                        <p class="description"><?php _e('Your Supabase project endpoint (e.g., https://xxxxx.supabase.co)', 'my-login-form'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="setup_supabase_anon_key">
                            <i class="fas fa-key"></i> <?php _e('Anon / Public Key', 'my-login-form'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" id="setup_supabase_anon_key" class="large-input"
                               value=""
                               placeholder="<?php echo $supabase_anon_key_hint !== '' ? esc_attr(sprintf(__('Saved: %s — leave blank to keep', 'my-login-form'), $supabase_anon_key_hint)) : 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'; ?>"
                               autocomplete="off">
                        <p class="description"><?php _e('Your anonymous API key from Project Settings > API. For security this is never displayed again after saving — leave blank to keep the current key.', 'my-login-form'); ?></p>
                    </div>

                    <div class="form-group">
                        <label for="setup_supabase_service_key">
                            <i class="fas fa-shield-alt"></i> <?php _e('Service Role Key', 'my-login-form'); ?>
                            <span class="optional">(<?php _e('Optional', 'my-login-form'); ?>)</span>
                        </label>
                        <input type="password" id="setup_supabase_service_key" class="large-input"
                               value=""
                               placeholder="<?php echo $supabase_service_key_hint !== '' ? esc_attr(sprintf(__('Saved: %s — leave blank to keep', 'my-login-form'), $supabase_service_key_hint)) : ''; ?>"
                               autocomplete="off">
                        <p class="description warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php _e('This key bypasses all database security rules — never share it. For security it is never displayed again after saving; leave blank to keep the current key.', 'my-login-form'); ?>
                        </p>
                    </div>
                    
                    <!-- Real-time Connection Status -->
                    <div id="connection-test-status" class="connection-test-status">
                        <div class="status-message">
                            <i class="fas fa-info-circle"></i>
                            <span><?php _e('Fill in the fields above and click "Test Connection"', 'my-login-form'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons-group">
                        <button type="button" id="test-connection-btn" class="button-secondary button-large">
                            <i class="fas fa-plug"></i> <?php _e('Test Connection', 'my-login-form'); ?>
                        </button>
                        <button type="button" id="save-connection-btn" class="button-primary button-large" disabled>
                            <i class="fas fa-save"></i> <?php _e('Save & Connect', 'my-login-form'); ?>
                        </button>
                    </div>
                    
                    <!-- Connection Help -->
                    <div class="connection-help">
                        <h4><i class="fas fa-question-circle"></i> <?php _e('Where to find your credentials?', 'my-login-form'); ?></h4>
                        <div class="help-steps">
                            <div class="help-step">
                                <span class="step-icon">1</span>
                                <div>
                                    <strong><?php _e('Login to Supabase', 'my-login-form'); ?></strong>
                                    <p><?php _e('Go to <a href="https://app.supabase.com" target="_blank">app.supabase.com</a> and login', 'my-login-form'); ?></p>
                                </div>
                            </div>
                            <div class="help-step">
                                <span class="step-icon">2</span>
                                <div>
                                    <strong><?php _e('Select Your Project', 'my-login-form'); ?></strong>
                                    <p><?php _e('Choose the project you want to connect', 'my-login-form'); ?></p>
                                </div>
                            </div>
                            <div class="help-step">
                                <span class="step-icon">3</span>
                                <div>
                                    <strong><?php _e('Go to Settings > API', 'my-login-form'); ?></strong>
                                    <p><?php _e('Navigate to Project Settings > API', 'my-login-form'); ?></p>
                                </div>
                            </div>
                            <div class="help-step">
                                <span class="step-icon">4</span>
                                <div>
                                    <strong><?php _e('Copy Credentials', 'my-login-form'); ?></strong>
                                    <p><?php _e('Copy "Project URL" and "anon public" key', 'my-login-form'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ── Database Setup Instructions ── -->
        <div class="enterprise-card" style="margin-top:20px;">
            <div class="card-header">
                <div class="header-icon" style="background:var(--mlf-gradient-surface);">
                    <i class="fas fa-database"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Supabase Database Setup', 'my-login-form'); ?></h2>
                    <p><?php _e('Run this SQL once inside your Supabase project to create the required table', 'my-login-form'); ?></p>
                </div>
            </div>
            <div class="card-body">

                <!-- Step 1: Create table -->
                <div class="mlf-db-step">
                    <div class="mlf-db-step-num">1</div>
                    <div class="mlf-db-step-body">
                        <h4><?php _e('Open the Supabase SQL Editor', 'my-login-form'); ?></h4>
                        <p><?php _e('In your Supabase project dashboard, click <strong>SQL Editor</strong> in the left sidebar, then click <strong>+ New Query</strong>.', 'my-login-form'); ?></p>
                    </div>
                </div>

                <div class="mlf-db-step">
                    <div class="mlf-db-step-num">2</div>
                    <div class="mlf-db-step-body">
                        <h4><?php _e('Paste &amp; run the table creation SQL', 'my-login-form'); ?></h4>
                        <p><?php _e('Copy the SQL below, paste it into the editor, and click <strong>Run</strong>.', 'my-login-form'); ?></p>
                        <div class="mlf-code-block">
                            <pre id="mlf-sql-snippet">-- Users table synced from WordPress
CREATE TABLE IF NOT EXISTS public.my_login_users (
  id              bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  wp_user_id      bigint UNIQUE NOT NULL,
  email           text UNIQUE NOT NULL,
  username        text,
  display_name    text,
  first_name      text,
  last_name       text,
  updated_at      timestamptz DEFAULT now()
);

-- Enable Row Level Security
ALTER TABLE public.my_login_users ENABLE ROW LEVEL SECURITY;

-- Allow the anon key to read rows (adjust to taste)
CREATE POLICY "Allow anon read"
  ON public.my_login_users FOR SELECT
  USING (true);

-- Allow the service role to insert / update
CREATE POLICY "Allow service insert"
  ON public.my_login_users FOR INSERT
  WITH CHECK (true);

CREATE POLICY "Allow service update"
  ON public.my_login_users FOR UPDATE
  USING (true);</pre>
                            <button class="mlf-copy-sql" onclick="mlfCopySQL()">
                                <i class="fas fa-copy"></i> <?php _e('Copy SQL', 'my-login-form'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mlf-db-step">
                    <div class="mlf-db-step-num">3</div>
                    <div class="mlf-db-step-body">
                        <h4><?php _e('(Optional) Enable Social OAuth Providers', 'my-login-form'); ?></h4>
                        <p><?php _e('If you want Google / GitHub / Facebook login, enable them in Supabase:', 'my-login-form'); ?></p>
                        <ol style="font-size:13px;color:var(--mlf-text);line-height:1.9;margin:8px 0 0 18px;">
                            <li><?php _e('In the left sidebar click <strong>Authentication</strong> → <strong>Providers</strong>', 'my-login-form'); ?></li>
                            <li><?php _e('Find the provider you want (Google, GitHub, Facebook…)', 'my-login-form'); ?></li>
                            <li><?php _e('Toggle <strong>Enable provider</strong> on', 'my-login-form'); ?></li>
                            <li><?php _e('Enter your OAuth <strong>Client ID</strong> and <strong>Client Secret</strong> from that provider\'s developer console', 'my-login-form'); ?></li>
                            <li><?php _e('Copy the <strong>Callback URL</strong> shown and paste it into the provider\'s OAuth settings', 'my-login-form'); ?></li>
                            <li><?php _e('Click <strong>Save</strong> in Supabase', 'my-login-form'); ?></li>
                        </ol>
                        <p style="margin-top:10px;font-size:12px;color:var(--mlf-text-muted);">
                            <?php _e('Then enable the same providers in this plugin\'s <strong>Social Media</strong> tab so they appear in your form designer palette.', 'my-login-form'); ?>
                        </p>
                    </div>
                </div>

                <div class="mlf-db-step">
                    <div class="mlf-db-step-num">4</div>
                    <div class="mlf-db-step-body">
                        <h4><?php _e('Verify &amp; connect', 'my-login-form'); ?></h4>
                        <p><?php _e('Go back to the <strong>Connection Setup</strong> box above, paste your Project URL and anon key, click <strong>Test Connection</strong>, then <strong>Save &amp; Connect</strong>. Done!', 'my-login-form'); ?></p>
                    </div>
                </div>

            </div><!-- /.card-body -->
        </div><!-- /.enterprise-card (db setup) -->

        <!-- Connection Success Preview -->
        <div id="connection-success-preview" style="display: none;">
            <div class="enterprise-card success-card">
                <div class="card-body">
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h3><?php _e('Connection Successful!', 'my-login-form'); ?></h3>
                            <p><?php _e('Your WordPress site is now connected to Supabase. You can now use all features.', 'my-login-form'); ?></p>
                            <div class="next-steps">
                                <a href="?page=my-login-form-social-supabase&tab=social" class="button-primary">
                                    <i class="fas fa-share-alt"></i> <?php _e('Setup Social Media', 'my-login-form'); ?>
                                </a>
                                <a href="?page=my-login-form-social-supabase&tab=analytics" class="button-secondary">
                                    <i class="fas fa-chart-line"></i> <?php _e('View Analytics', 'my-login-form'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ==================== SUPABASE TAB (After Connection) ==================== -->
        <?php if ($active_tab === 'supabase'): ?>
            <?php if (!$supabase_connected): ?>
                <div class="notice notice-warning">
                    <p><i class="fas fa-exclamation-triangle"></i> <?php _e('Supabase is not connected. Please go to the <a href="?page=my-login-form-social-supabase&tab=setup">Setup tab</a> to complete the connection.', 'my-login-form'); ?></p>
                </div>
            <?php else: ?>
            <div class="enterprise-card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fas fa-check-circle" style="color: var(--mlf-success);"></i>
                    </div>
                    <div class="header-text">
                        <h2><?php _e('Supabase Connected', 'my-login-form'); ?></h2>
                        <p><?php _e('Your connection is active and ready to use', 'my-login-form'); ?></p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="connection-details">
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Project URL:', 'my-login-form'); ?></span>
                            <span class="detail-value"><?php echo esc_url($supabase_url); ?></span>
                            <button class="copy-detail" data-copy="<?php echo esc_url($supabase_url); ?>">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Status:', 'my-login-form'); ?></span>
                            <span class="detail-value status-active">
                                <i class="fas fa-circle"></i> <?php _e('Active', 'my-login-form'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" id="refresh-connection" class="button-secondary">
                            <i class="fas fa-sync-alt"></i> <?php _e('Refresh Connection', 'my-login-form'); ?>
                        </button>
                        <button type="button" id="disconnect-supabase" class="button-secondary">
                            <i class="fas fa-trash-alt"></i> <?php _e('Disconnect', 'my-login-form'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="enterprise-card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fas fa-table"></i>
                    </div>
                    <div class="header-text">
                        <h2><?php _e('Database Tables', 'my-login-form'); ?></h2>
                        <p><?php _e('Required tables for full functionality', 'my-login-form'); ?></p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="tables-list">
                        <div class="table-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <strong>my_login_users</strong>
                                <p><?php _e('Syncs WordPress users with Supabase', 'my-login-form'); ?></p>
                            </div>
                            <span class="table-status created">✓ Created</span>
                        </div>
                        <div class="table-item">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <strong>my_login_social_shares</strong>
                                <p><?php _e('Tracks social media shares', 'my-login-form'); ?></p>
                            </div>
                            <span class="table-status created">✓ Created</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- ==================== SOCIAL MEDIA TAB ==================== -->
        <?php if ($active_tab === 'social'): ?>

        <!-- Social Login Providers -->
        <div class="enterprise-card" style="margin-bottom:24px;">
            <div class="card-header">
                <div class="header-icon" style="background:var(--mlf-gradient-accent);">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Social Login Providers', 'my-login-form'); ?></h2>
                    <p><?php _e('Enable OAuth login for your forms. Enabled providers appear in the Form Designer palette.', 'my-login-form'); ?></p>
                </div>
            </div>
            <div class="card-body">
            <?php if (!$supabase_connected): ?>
                <div class="notice notice-warning inline" style="padding:12px 16px;border-radius:6px;">
                    <p>⚠️ <?php _e('Supabase is not connected. Social login requires Supabase OAuth.', 'my-login-form'); ?>
                    <a href="?page=my-login-form-social-supabase&tab=setup" style="margin-left:6px;font-weight:600;"><?php _e('Set up Supabase →', 'my-login-form'); ?></a></p>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <?php wp_nonce_field('my_login_social_login_settings'); ?>
                    <input type="hidden" name="save_social_login" value="1">
                    <p style="color:#444444;font-size:13px;margin:0 0 16px;"><?php _e('Toggle providers on/off. Only enabled providers show in the Form Designer.', 'my-login-form'); ?></p>
                    <?php
                    $login_providers = [
                        'google'    => ['name' => 'Google',     'icon' => 'fab fa-google',     'color' => '#DB4437'],
                        'facebook'  => ['name' => 'Facebook',   'icon' => 'fab fa-facebook-f',  'color' => '#4267B2'],
                        'twitter'   => ['name' => 'Twitter / X','icon' => 'fab fa-twitter',     'color' => '#1DA1F2'],
                        'github'    => ['name' => 'GitHub',     'icon' => 'fab fa-github',      'color' => '#333333'],
                        'linkedin'  => ['name' => 'LinkedIn',   'icon' => 'fab fa-linkedin-in', 'color' => '#0077B5'],
                        'apple'     => ['name' => 'Apple',      'icon' => 'fab fa-apple',       'color' => '#000000'],
                        'microsoft' => ['name' => 'Microsoft',  'icon' => 'fab fa-microsoft',   'color' => '#00A4EF'],
                    ];
                    ?>
                    <div class="platforms-grid">
                        <?php foreach ($login_providers as $key => $p):
                            $enabled = get_option('my_login_' . $key . '_login_enabled', 0); ?>
                        <div class="platform-card" style="border-left:3px solid <?php echo esc_attr($p['color']); ?>;">
                            <div class="platform-icon" style="background:<?php echo esc_attr($p['color']); ?>;">
                                <i class="<?php echo esc_attr($p['icon']); ?>"></i>
                            </div>
                            <div class="platform-info">
                                <h4><?php echo esc_html($p['name']); ?></h4>
                                <small style="color:#666666;"><?php _e('OAuth login', 'my-login-form'); ?></small>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="login_<?php echo esc_attr($key); ?>_enabled" value="1" <?php checked($enabled, 1); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:18px;">
                        <button type="submit" class="button-primary">
                            <i class="fas fa-save"></i> <?php _e('Save Login Providers', 'my-login-form'); ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            </div>
        </div>

        <!-- Social Sharing Platforms -->
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fas fa-share-alt"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Social Media Platforms', 'my-login-form'); ?></h2>
                    <p><?php _e('Enable social sharing for your content', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (!$supabase_connected): ?>
                    <div class="notice notice-warning">
                        <p><i class="fas fa-exclamation-triangle"></i> <?php _e('Connect Supabase first to enable social analytics tracking.', 'my-login-form'); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <?php wp_nonce_field('my_login_social_settings'); ?>
                    <div class="platforms-grid">
                        <?php foreach ($social_platforms as $key => $platform): ?>
                        <div class="platform-card">
                            <div class="platform-icon" style="background: <?php echo $platform['gradient']; ?>">
                                <i class="<?php echo $platform['icon']; ?>"></i>
                            </div>
                            <div class="platform-info">
                                <h4><?php echo $platform['name']; ?></h4>
                            </div>
                            <label class="toggle-switch-mini">
                                <input type="checkbox" name="<?php echo $key; ?>_enabled" value="1" <?php checked($platform['enabled'], 1); ?>>
                                <span class="toggle-slider-mini"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" name="save_social" class="button-primary">
                            <i class="fas fa-save"></i> <?php _e('Save Settings', 'my-login-form'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fas fa-code"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Share Button Shortcode', 'my-login-form'); ?></h2>
                    <p><?php _e('Add this shortcode to any page or post', 'my-login-form'); ?></p>
                </div>
            </div>
            <div class="card-body">
                <div class="shortcode-box">
                    <code>[my_login_social_share]</code>
                    <button class="copy-shortcode" data-code="[my_login_social_share]"><?php _e('Copy', 'my-login-form'); ?></button>
                </div>
                <p class="description"><?php _e('Displays share buttons for the current page', 'my-login-form'); ?></p>
                
                <h4><?php _e('With Custom URL:', 'my-login-form'); ?></h4>
                <div class="shortcode-box">
                    <code>[my_login_social_share url="https://example.com" title="Check this out!"]</code>
                    <button class="copy-shortcode" data-code='[my_login_social_share url="https://example.com" title="Check this out!"]'><?php _e('Copy', 'my-login-form'); ?></button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ==================== ANALYTICS TAB ==================== -->
        <?php if ($active_tab === 'analytics'): ?>
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Social Sharing Analytics', 'my-login-form'); ?></h2>
                    <p><?php _e('Track and measure your social media performance', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (!$supabase_connected): ?>
                    <div class="notice notice-warning">
                        <p><i class="fas fa-exclamation-triangle"></i> <?php _e('Connect Supabase first to track analytics.', 'my-login-form'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="analytics-stats" id="analytics-stats">
                        <div class="stat-card">
                            <div class="stat-value" id="total-shares">0</div>
                            <div class="stat-label"><?php _e('Total Shares', 'my-login-form'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="unique-users">0</div>
                            <div class="stat-label"><?php _e('Unique Users', 'my-login-form'); ?></div>
                        </div>
                    </div>
                    <button id="refresh-stats" class="button-secondary">
                        <i class="fas fa-sync-alt"></i> <?php _e('Refresh Stats', 'my-login-form'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ==================== WOOCOMMERCE TAB ==================== -->
        <?php if ($active_tab === 'woocommerce'): ?>
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fab fa-woocommerce"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('WooCommerce Integration', 'my-login-form'); ?></h2>
                    <p><?php _e('Connect with WooCommerce for enhanced functionality', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="card-body">
                <?php if ($woocommerce_active): ?>
                    <div class="woo-status success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php _e('WooCommerce is active!', 'my-login-form'); ?> (v<?php echo $woocommerce_version; ?>)</span>
                    </div>
                    
                    <div class="integration-options">
                        <div class="option-item">
                            <label class="toggle-switch-mini">
                                <input type="checkbox" id="wc-auto-login" <?php checked(get_option('my_login_wc_auto_login', 1), 1); ?>>
                                <span class="toggle-slider-mini"></span>
                            </label>
                            <div class="option-info">
                                <strong><?php _e('Auto Login After Registration', 'my-login-form'); ?></strong>
                                <p><?php _e('Automatically log in users after WooCommerce registration', 'my-login-form'); ?></p>
                            </div>
                        </div>
                        <div class="option-item">
                            <label class="toggle-switch-mini">
                                <input type="checkbox" id="wc-sync-users" <?php checked(get_option('my_login_wc_sync_users', 1), 1); ?>>
                                <span class="toggle-slider-mini"></span>
                            </label>
                            <div class="option-info">
                                <strong><?php _e('Sync User Data', 'my-login-form'); ?></strong>
                                <p><?php _e('Sync user profiles between systems', 'my-login-form'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button id="save-wc-settings" class="button-primary">
                            <i class="fas fa-save"></i> <?php _e('Save Settings', 'my-login-form'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="woo-status error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php _e('WooCommerce is not installed or activated', 'my-login-form'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==================== OTP LOG TAB ==================== -->
        <?php if ($active_tab === 'otp-log'): ?>
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon" style="background:var(--mlf-gradient-accent);">
                    <i class="fas fa-key"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('OTP Verification Log', 'my-login-form'); ?></h2>
                    <p><?php _e('Audit trail of every 6-digit code sent and verified through Supabase — register, login, and forgot-password. Supabase generates and checks the codes themselves; this only records that an attempt happened.', 'my-login-form'); ?></p>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$otp_db || !$otp_db->table_exists()): ?>
                    <div class="notice notice-warning inline" style="padding:12px 16px;border-radius:6px;">
                        <p style="color:#444444;">⚠️ <?php _e('The OTP log table has not been created yet. It is created automatically the next time the plugin initializes.', 'my-login-form'); ?></p>
                    </div>
                <?php else: ?>

                <form method="get" class="mlf-otp-log-filters">
                    <input type="hidden" name="page" value="my-login-form-social-supabase">
                    <input type="hidden" name="tab" value="otp-log">
                    <select name="otp_context">
                        <option value="all" <?php selected($otp_log_context, 'all'); ?>><?php _e('All contexts', 'my-login-form'); ?></option>
                        <option value="register" <?php selected($otp_log_context, 'register'); ?>><?php _e('Register', 'my-login-form'); ?></option>
                        <option value="login" <?php selected($otp_log_context, 'login'); ?>><?php _e('Login', 'my-login-form'); ?></option>
                        <option value="forgot_password" <?php selected($otp_log_context, 'forgot_password'); ?>><?php _e('Forgot Password', 'my-login-form'); ?></option>
                    </select>
                    <select name="otp_action">
                        <option value="all" <?php selected($otp_log_action, 'all'); ?>><?php _e('All actions', 'my-login-form'); ?></option>
                        <option value="sent" <?php selected($otp_log_action, 'sent'); ?>><?php _e('Sent', 'my-login-form'); ?></option>
                        <option value="send_failed" <?php selected($otp_log_action, 'send_failed'); ?>><?php _e('Send failed', 'my-login-form'); ?></option>
                        <option value="verified" <?php selected($otp_log_action, 'verified'); ?>><?php _e('Verified', 'my-login-form'); ?></option>
                        <option value="failed" <?php selected($otp_log_action, 'failed'); ?>><?php _e('Failed', 'my-login-form'); ?></option>
                    </select>
                    <button type="submit" class="button-secondary"><?php _e('Filter', 'my-login-form'); ?></button>
                </form>

                <?php if (empty($otp_log_rows)): ?>
                    <p style="color:#444444;margin-top:16px;"><?php _e('No OTP events recorded yet.', 'my-login-form'); ?></p>
                <?php else: ?>
                <div style="overflow-x:auto;margin-top:16px;">
                    <table class="mlf-otp-log-table">
                        <thead>
                            <tr>
                                <th><?php _e('Email', 'my-login-form'); ?></th>
                                <th><?php _e('Context', 'my-login-form'); ?></th>
                                <th><?php _e('Action', 'my-login-form'); ?></th>
                                <th><?php _e('IP Address', 'my-login-form'); ?></th>
                                <th><?php _e('Date', 'my-login-form'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($otp_log_rows as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row->email); ?></td>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $row->context))); ?></td>
                                <td>
                                    <span class="mlf-otp-badge mlf-otp-badge-<?php echo esc_attr($row->action); ?>">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $row->action))); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($row->ip_address); ?></td>
                                <td><?php echo esc_html(get_date_from_gmt($row->created_at, 'Y-m-d H:i:s')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($otp_log_total_pages > 1): ?>
                <div class="mlf-otp-log-pagination">
                    <?php for ($p = 1; $p <= $otp_log_total_pages; $p++): ?>
                        <a href="<?php echo esc_url(add_query_arg(['paged' => $p, 'otp_context' => $otp_log_context, 'otp_action' => $otp_log_action])); ?>"
                           class="<?php echo $p === $otp_log_page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <p style="color:#666666;font-size:12px;margin-top:10px;">
                    <?php printf(__('%d total entries. Entries are automatically cleaned up after 30 days.', 'my-login-form'), $otp_log_total); ?>
                </p>
                <?php endif; ?>

                <form method="post" style="margin-top:18px;" onsubmit="return confirm('<?php echo esc_js(__('Clear the entire OTP log? This cannot be undone.', 'my-login-form')); ?>');">
                    <?php wp_nonce_field('my_login_clear_otp_log'); ?>
                    <button type="submit" name="clear_otp_log" value="1" class="button-secondary">
                        <i class="fas fa-trash-alt"></i> <?php _e('Clear Log', 'my-login-form'); ?>
                    </button>
                </form>

                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<style>
/* Integration Hub Styles */

/* Safety net: plain <p> tags with no specific class (e.g. inside notices)
   otherwise inherit whatever default text color the browser/admin theme
   happens to use, which can land on top of this page's own themed card
   backgrounds and become unreadable. Every <p> below that already sets its
   own color (.header-text p, .description, .help-step p, etc.) has equal
   selector specificity and appears later in this file, so it still wins. */
.my-login-integration-hub p {
    color: #2A2A2A;
}

.my-login-integration-hub {
    max-width: 1400px;
    margin: 20px auto;
}

/* Header */
.integration-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px;
    background: var(--mlf-gradient-secondary);
    border-radius: 15px;
    color: #fff;
    margin-bottom: 25px;
}

.header-left h1 {
    margin: 0 0 10px;
    font-size: 28px;
    color: #fff;
}

.header-description {
    margin: 0;
    opacity: 0.9;
}

.connection-status-card {
    background: rgba(255,255,255,0.2);
    padding: 12px 20px;
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.status-connected, .status-disconnected {
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-connected i {
    font-size: 24px;
    color: var(--mlf-primary);
}

.status-disconnected i {
    font-size: 24px;
    color: var(--mlf-accent);
}

/* Setup Wizard */
.setup-wizard {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 25px;
    overflow: hidden;
}

.wizard-header {
    background: linear-gradient(135deg, var(--mlf-accent), var(--mlf-accent-dark));
    color: #fff;
    padding: 20px 25px;
}

.wizard-header h2 {
    margin: 0 0 5px;
    color: #fff;
}

.wizard-steps {
    display: flex;
    padding: 30px;
    gap: 20px;
    background: var(--mlf-surface);
}

.wizard-step {
    flex: 1;
    text-align: center;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    position: relative;
}

.wizard-step.active {
    border: 2px solid var(--mlf-accent);
    background: rgba(179,91,0,0.06);
}

.step-number {
    width: 40px;
    height: 40px;
    background: var(--mlf-accent);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-weight: bold;
    font-size: 18px;
}

.wizard-step h3 {
    margin: 0 0 10px;
    font-size: 16px;
}

.wizard-step p {
    margin: 0;
    font-size: 13px;
    color: var(--mlf-text-muted);
}

/* Tabs */
.integration-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 25px;
    background: var(--mlf-bg);
    padding: 6px;
    border-radius: 12px;
    box-shadow: var(--mlf-shadow);
    border: 1px solid var(--mlf-border-light);
}

.tab-link {
    padding: 11px 22px;
    text-decoration: none;
    color: var(--mlf-text-muted);
    font-weight: 600;
    font-size: 13.5px;
    border-radius: 8px;
    transition: background 0.2s, color 0.2s;
    position: relative;
}

.tab-link i {
    margin-right: 8px;
}

.tab-link:hover {
    background: var(--mlf-surface-2);
    color: var(--mlf-text);
}

.tab-link.active {
    background: var(--mlf-gradient-primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(31,187,0,0.3);
}

.tab-badge {
    background: var(--mlf-accent);
    color: #fff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 8px;
}

.tab-badge.warning {
    background: var(--mlf-accent);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Cards */
.enterprise-card {
    background: var(--mlf-bg);
    border-radius: 15px;
    box-shadow: var(--mlf-shadow);
    border: 1px solid var(--mlf-border-light);
    margin-bottom: 25px;
    overflow: hidden;
    transition: box-shadow 0.25s ease, border-color 0.25s ease;
}

.enterprise-card:hover {
    box-shadow: var(--mlf-shadow-lg);
    border-color: var(--mlf-border);
}

.card-header {
    display: flex;
    gap: 20px;
    padding: 25px;
    background: var(--mlf-surface);
    border-bottom: 1px solid var(--mlf-border);
}

.header-icon {
    width: 50px;
    height: 50px;
    background: var(--mlf-gradient-secondary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-icon i {
    font-size: 24px;
    color: #fff;
}

.header-text h2 {
    margin: 0 0 8px;
    font-size: 20px;
    color: var(--mlf-heading);
}

.header-text p {
    margin: 0;
    color: var(--mlf-text-muted);
}

.card-body {
    padding: 25px;
}

/* Form */
.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    color: var(--mlf-text);
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

.large-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--mlf-border);
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.large-input:focus {
    border-color: var(--mlf-primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(31,187,0,0.12);
}

.required {
    color: var(--mlf-danger);
}

.optional {
    color: var(--mlf-text-light);
    font-weight: normal;
}

.description {
    margin: 5px 0 0;
    font-size: 12px;
    color: var(--mlf-text-muted);
}

.description.warning {
    color: var(--mlf-accent);
}

/* Connection Test Status */
.connection-test-status {
    margin: 20px 0;
    padding: 15px;
    border-radius: 10px;
    background: var(--mlf-surface);
}

.status-message {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-message i {
    font-size: 20px;
}

.status-message.testing i {
    color: var(--mlf-accent);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.status-message.success i {
    color: var(--mlf-primary);
}

.status-message.error i {
    color: var(--mlf-danger);
}

/* Action Buttons */
.action-buttons-group {
    display: flex;
    gap: 15px;
    margin: 25px 0;
}

.button-large {
    padding: 12px 24px !important;
    height: auto !important;
    font-size: 14px !important;
}

/* Connection Help */
.connection-help {
    margin-top: 30px;
    padding: 20px;
    background: var(--mlf-surface);
    border-radius: 10px;
    border: 1px solid var(--mlf-border);
}

.connection-help h4 {
    margin: 0 0 15px;
    color: var(--mlf-heading);
}

.help-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.help-step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.step-icon {
    width: 24px;
    height: 24px;
    background: var(--mlf-primary);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.help-step strong {
    display: block;
    color: var(--mlf-text);
    margin-bottom: 5px;
    font-size: 13px;
}

.help-step p {
    margin: 0;
    font-size: 12px;
    color: var(--mlf-text-muted);
}

/* Success Card */
.success-card {
    border: 2px solid var(--mlf-primary);
}

.success-message {
    display: flex;
    align-items: center;
    gap: 20px;
    text-align: left;
}

.success-message i {
    font-size: 48px;
    color: var(--mlf-primary);
}

.success-message h3 {
    margin: 0 0 10px;
    color: var(--mlf-heading);
}

.next-steps {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

/* Platforms Grid */
.platforms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.platform-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: var(--mlf-surface);
    border-radius: 12px;
    transition: all 0.3s;
}

.platform-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.platform-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.platform-icon i {
    font-size: 24px;
    color: #fff;
}

.platform-info {
    flex: 1;
}

.platform-info h4 {
    margin: 0;
    color: var(--mlf-text);
}

/* Toggle Switch */
.toggle-switch-mini {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.toggle-switch-mini input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider-mini {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--mlf-border);
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider-mini:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider-mini {
    background-color: var(--mlf-primary);
}

input:checked + .toggle-slider-mini:before {
    transform: translateX(20px);
}

/* Shortcode Box */
.shortcode-box {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 12px 15px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 15px 0;
    font-family: monospace;
}

.shortcode-box code {
    background: none;
    color: #d4d4d4;
    font-size: 13px;
}

/* Connection Details */
.connection-details {
    margin-bottom: 25px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: var(--mlf-surface);
    border-radius: 8px;
    margin-bottom: 10px;
}

.detail-label {
    font-weight: 600;
    min-width: 100px;
    color: var(--mlf-text);
}

.detail-value {
    flex: 1;
    font-family: monospace;
    color: var(--mlf-text);
}

.detail-value.status-active {
    color: var(--mlf-primary);
}

.detail-value.status-active i {
    font-size: 10px;
}

.copy-detail {
    background: var(--mlf-border);
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}

/* Tables List */
.tables-list {
    display: grid;
    gap: 15px;
}

.table-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: var(--mlf-surface);
    border-radius: 10px;
}

.table-item i {
    font-size: 24px;
    color: var(--mlf-primary);
}

.table-item div {
    flex: 1;
}

.table-item strong {
    display: block;
    color: var(--mlf-text);
    margin-bottom: 5px;
}

.table-item p {
    margin: 0;
    font-size: 12px;
    color: var(--mlf-text-muted);
}

.table-status {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 5px;
}

.table-status.created {
    background: var(--mlf-success-tint);
    color: var(--mlf-success);
}

/* WooCommerce */
.woo-status {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.woo-status.success {
    background: var(--mlf-success-tint);
    color: var(--mlf-success);
}

.woo-status.error {
    background: rgba(138,0,0,0.12);
    color: var(--mlf-danger);
}

.option-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: var(--mlf-surface);
    border-radius: 10px;
    margin-bottom: 10px;
}

.option-info {
    flex: 1;
}

.option-info strong {
    display: block;
    color: var(--mlf-text);
    margin-bottom: 5px;
}

.option-info p {
    margin: 0;
    font-size: 12px;
    color: var(--mlf-text-muted);
}

/* Analytics Stats */
.analytics-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background: var(--mlf-gradient-secondary);
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    color: #fff;
}

.stat-value {
    font-size: 36px;
    font-weight: bold;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

/* Responsive */
@media (max-width: 768px) {
    .integration-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .wizard-steps {
        flex-direction: column;
    }
    
    .integration-tabs {
        flex-wrap: wrap;
    }
    
    .action-buttons-group {
        flex-direction: column;
    }
    
    .help-steps {
        grid-template-columns: 1fr;
    }
    
    .success-message {
        flex-direction: column;
        text-align: center;
    }
}
/* ── Friendly Wizard ── */
.my-login-friendly-wizard {
    background: var(--mlf-gradient-surface);
    border-radius:14px; padding:28px 24px; margin-bottom:24px; color:#fff;
}
.my-login-friendly-wizard .wizard-header { text-align:center; margin-bottom:24px; }
.my-login-friendly-wizard .wizard-header h2 { color:#fff; margin:0 0 6px; font-size:20px; }
.my-login-friendly-wizard .wizard-header p  { color:rgba(234,246,228,0.8); margin:0; }
.my-login-steps-row {
    display:flex; align-items:flex-start; gap:8px; flex-wrap:wrap;
}
.my-login-step {
    flex:1; min-width:160px; background:#ffffff;
    border:1px solid #E3E3E3; border-radius:10px; padding:16px;
}
.my-login-step.active { border-color:var(--mlf-primary); }
.my-login-step-num {
    width:28px; height:28px; border-radius:50%;
    background:var(--mlf-primary); color:#fff; font-weight:800; font-size:14px;
    display:flex; align-items:center; justify-content:center; margin-bottom:10px;
}
.my-login-step h3 { color:#1a1a1a; font-size:13px; margin:0 0 6px; }
.my-login-step p  { color:#444444; font-size:12px; margin:0 0 10px; line-height:1.5; }
.my-login-step-cta {
    display:inline-block; background:var(--mlf-primary); color:#fff !important;
    padding:6px 14px; border-radius:6px; font-size:12px; font-weight:700;
    text-decoration:none !important;
}
.my-login-step-cta:hover { background:var(--mlf-primary-dark); }
.my-login-step-cta-outline {
    display:inline-block; border:1px solid var(--mlf-primary); color:var(--mlf-primary) !important;
    padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600;
    text-decoration:none !important;
}
.my-login-step-sep { font-size:20px; color:#999999; align-self:center; padding:0 2px; }
@media(max-width:800px) { .my-login-steps-row { flex-direction:column; } .my-login-step-sep { display:none; } }

/* ── Database Setup Steps ── */
.mlf-db-step {
    display: flex; gap: 14px; align-items: flex-start;
    padding: 14px 0; border-bottom: 1px solid var(--mlf-border-light);
}
.mlf-db-step:last-child { border-bottom: none; }
.mlf-db-step-num {
    flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%;
    background: var(--mlf-primary); color: #fff; font-weight: 700; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
}
.mlf-db-step-body { flex: 1; }
.mlf-db-step-body h4 { margin: 0 0 5px; font-size: 14px; color: var(--mlf-heading); }
.mlf-db-step-body p  { margin: 0; font-size: 13px; color: var(--mlf-text-muted); line-height: 1.6; }
.mlf-code-block {
    position: relative; background: var(--mlf-secondary); border-radius: 8px;
    padding: 14px 16px; margin-top: 10px;
}
.mlf-code-block pre {
    margin: 0; color: #94d9b3; font-size: 12px; line-height: 1.7;
    white-space: pre-wrap; word-break: break-word; font-family: monospace;
    max-height: 220px; overflow-y: auto;
}
.mlf-copy-sql {
    position: absolute; top: 10px; right: 10px;
    background: rgba(31,187,0,.15); color: var(--mlf-primary); border: 1px solid rgba(31,187,0,.3);
    padding: 5px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;
    transition: background .2s;
}
.mlf-copy-sql:hover { background: var(--mlf-primary); color: #fff; }

/* ── OTP Log Tab ── */
.mlf-otp-log-filters {
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
}
.mlf-otp-log-filters select {
    padding: 7px 10px; border: 1px solid #DCE8D6; border-radius: 6px;
    background: #ffffff; color: #2A2A2A; font-size: 13px;
}
.mlf-otp-log-table {
    width: 100%; border-collapse: collapse; font-size: 13px;
}
.mlf-otp-log-table th,
.mlf-otp-log-table td {
    padding: 10px 12px; text-align: left; border-bottom: 1px solid #E3E3E3;
    color: #2A2A2A; white-space: nowrap;
}
.mlf-otp-log-table th {
    background: #F4F8F1; color: #1a1a1a; font-weight: 700;
}
.mlf-otp-log-table tbody tr:hover { background: #FAFCF8; }
.mlf-otp-badge {
    display: inline-block; padding: 3px 10px; border-radius: 12px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
}
.mlf-otp-badge-sent        { background: #E3F2E1; color: #0F5900; }
.mlf-otp-badge-verified    { background: #E3F2E1; color: #0F5900; }
.mlf-otp-badge-send_failed { background: #FDECEC; color: #8A0000; }
.mlf-otp-badge-failed      { background: #FDECEC; color: #8A0000; }
.mlf-otp-log-pagination {
    display: flex; gap: 6px; margin-top: 14px; flex-wrap: wrap;
}
.mlf-otp-log-pagination a {
    padding: 5px 11px; border: 1px solid #DCE8D6; border-radius: 6px;
    color: #2A2A2A; text-decoration: none; font-size: 12px;
}
.mlf-otp-log-pagination a.active {
    background: var(--mlf-primary); color: #fff; border-color: var(--mlf-primary);
}
</style>

<script>
jQuery(document).ready(function($) {
    let isConnected = <?php echo $supabase_connected ? 'true' : 'false'; ?>;
    
    // Test Connection Function
    $('#test-connection-btn').on('click', function() {
        var url = $('#setup_supabase_url').val();
        var anonKey = $('#setup_supabase_anon_key').val();

        // The Anon Key field is intentionally left blank when a key is
        // already saved (the real key is never echoed back into the page)
        // — an empty anonKey here means "test the saved key", not "no key
        // was entered". The server falls back to the stored key and
        // reports its own error if none exists yet.
        if (!url || (!anonKey && <?php echo $supabase_anon_key_hint === '' ? 'true' : 'false'; ?>)) {
            showConnectionStatus('error', 'Please enter both URL and Anon Key');
            return;
        }
        
        showConnectionStatus('testing', 'Testing connection to Supabase...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'my_login_supabase_test',
                url: url,
                anon_key: anonKey,
                nonce: '<?php echo wp_create_nonce('my_login_supabase_test'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showConnectionStatus('success', response.data.message);
                    $('#save-connection-btn').prop('disabled', false);
                    isConnected = true;
                } else {
                    showConnectionStatus('error', response.data.message);
                    $('#save-connection-btn').prop('disabled', true);
                }
            },
            error: function() {
                showConnectionStatus('error', 'Connection failed. Please check your credentials.');
            }
        });
    });
    
    // Save Connection
    $('#save-connection-btn').on('click', function() {
        var url = $('#setup_supabase_url').val();
        var anonKey = $('#setup_supabase_anon_key').val();
        var serviceKey = $('#setup_supabase_service_key').val();
        
        showConnectionStatus('testing', 'Saving connection settings...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'my_login_save_supabase_connection',
                url: url,
                anon_key: anonKey,
                service_key: serviceKey,
                nonce: '<?php echo wp_create_nonce('my_login_supabase_save'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showConnectionStatus('success', response.data.message);
                    $('#connection-success-preview').fadeIn();
                    $('#global-connection-status').html(`
                        <div class="status-connected">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Supabase Connected</strong>
                                <small>${url}</small>
                            </div>
                        </div>
                    `);
                    setTimeout(function() {
                        window.location.href = '?page=my-login-form-social-supabase&tab=supabase';
                    }, 2000);
                }
            }
        });
    });
    
    // Refresh Connection
    $('#refresh-connection').on('click', function() {
        location.reload();
    });
    
    // Disconnect
    $('#disconnect-supabase').on('click', function() {
        if (confirm('Are you sure you want to disconnect Supabase? This will disable all features that depend on it.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_login_disconnect_supabase',
                    nonce: '<?php echo wp_create_nonce('my_login_disconnect'); ?>'
                },
                success: function() {
                    location.reload();
                }
            });
        }
    });
    
    // Save WooCommerce Settings
    $('#save-wc-settings').on('click', function() {
        var autoLogin = $('#wc-auto-login').is(':checked') ? 1 : 0;
        var syncUsers = $('#wc-sync-users').is(':checked') ? 1 : 0;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'my_login_save_wc_settings',
                auto_login: autoLogin,
                sync_users: syncUsers,
                nonce: '<?php echo wp_create_nonce('my_login_wc_settings'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('WooCommerce settings saved!');
                }
            }
        });
    });
    
    // Copy Shortcode
    $('.copy-shortcode').on('click', function() {
        var code = $(this).data('code');
        copyToClipboard(code);
        var original = $(this).html();
        $(this).html('<i class="fas fa-check"></i> Copied!');
        setTimeout(function() {
            $(this).html(original);
        }.bind(this), 2000);
    });
    
    // Copy Detail
    $('.copy-detail').on('click', function() {
        var text = $(this).data('copy');
        copyToClipboard(text);
        $(this).html('<i class="fas fa-check"></i>');
        setTimeout(function() {
            $(this).html('<i class="fas fa-copy"></i>');
        }.bind(this), 2000);
    });
    
    // Refresh Stats
    $('#refresh-stats').on('click', function() {
        $('#total-shares').text(Math.floor(Math.random() * 100));
        $('#unique-users').text(Math.floor(Math.random() * 50));
    });
    
    // Helper Functions
    function showConnectionStatus(type, message) {
        var icon = '';
        var color = '';
        
        switch(type) {
            case 'testing':
                icon = '<i class="fas fa-spinner fa-pulse"></i>';
                color = 'var(--mlf-accent)';
                break;
            case 'success':
                icon = '<i class="fas fa-check-circle"></i>';
                color = 'var(--mlf-success)';
                break;
            case 'error':
                icon = '<i class="fas fa-exclamation-circle"></i>';
                color = 'var(--mlf-danger)';
                break;
            default:
                icon = '<i class="fas fa-info-circle"></i>';
                color = 'var(--mlf-text-muted)';
        }
        
        $('#connection-test-status').html(`
            <div class="status-message" style="color: ${color}">
                ${icon}
                <span>${message}</span>
            </div>
        `);
    }
    
    function copyToClipboard(text) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
    
    // Removed auto-test on keystroke — user must click "Test Connection" manually
});

function mlfCopySQL() {
    var text = document.getElementById('mlf-sql-snippet').innerText;
    navigator.clipboard.writeText(text).then(function() {
        var btn = document.querySelector('.mlf-copy-sql');
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(function(){ btn.innerHTML = '<i class="fas fa-copy"></i> Copy SQL'; }, 2500);
    });
}
</script>

<?php
// Shortcode function
if (!function_exists('my_login_social_share_buttons')) {
function my_login_social_share_buttons($atts = []) {
    if (!\MyLoginForm\Licensing\Gate::is_active()) {
        return \MyLoginForm\Licensing\Gate::gate_notice_html();
    }

    $atts = shortcode_atts(['url' => '', 'title' => ''], $atts);
    $url = $atts['url'] ?: get_permalink();
    $title = $atts['title'] ?: get_the_title();
    
    $platforms = [
        'facebook' => ['icon' => 'fab fa-facebook', 'url' => "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($url), 'color' => '#1877f2'],
        'twitter' => ['icon' => 'fab fa-twitter', 'url' => "https://twitter.com/intent/tweet?url=" . urlencode($url) . "&text=" . urlencode($title), 'color' => '#1da1f2'],
        'linkedin' => ['icon' => 'fab fa-linkedin', 'url' => "https://www.linkedin.com/sharing/share-offsite/?url=" . urlencode($url), 'color' => '#0077b5'],
        'whatsapp' => ['icon' => 'fab fa-whatsapp', 'url' => "https://wa.me/?text=" . urlencode($title . ' ' . $url), 'color' => '#25d366'],
        'telegram' => ['icon' => 'fab fa-telegram', 'url' => "https://t.me/share/url?url=" . urlencode($url) . "&text=" . urlencode($title), 'color' => '#0088cc']
    ];
    
    $html = '<div class="my-login-social-buttons" style="display: flex; gap: 10px; flex-wrap: wrap;">';
    foreach ($platforms as $key => $platform) {
        if (get_option("my_login_{$key}_enabled", 1)) {
            $html .= '<a href="' . esc_url($platform['url']) . '" target="_blank" class="my-login-share-btn" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: ' . $platform['color'] . '; color: #fff; text-decoration: none; border-radius: 8px; transition: transform 0.2s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'translateY(0)\'">';
            $html .= '<i class="' . $platform['icon'] . '"></i>';
            $html .= '<span>' . ucfirst($key) . '</span>';
            $html .= '</a>';
        }
    }
    $html .= '</div>';
    
    // Track share in analytics if Supabase is connected
    if (get_option('my_login_supabase_enabled')) {
        $html .= '<script>
        document.querySelectorAll(".my-login-share-btn").forEach(btn => {
            btn.addEventListener("click", function(e) {
                var platform = this.querySelector("span").innerText.toLowerCase();
                fetch(ajaxurl, {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"},
                    body: "action=my_login_track_share&platform=" + platform + "&url=" + encodeURIComponent(window.location.href) + "&nonce=' . wp_create_nonce('my_login_social_nonce') . '"
                });
            });
        });
        </script>';
    }
    
    return $html;
}
} // end function_exists: my_login_social_share_buttons
add_shortcode('my_login_social_share', 'my_login_social_share_buttons');

// Track share handler
add_action('wp_ajax_my_login_track_share', 'my_login_track_share_handler');
add_action('wp_ajax_nopriv_my_login_track_share', 'my_login_track_share_handler');
if (!function_exists('my_login_track_share_handler')) {
function my_login_track_share_handler() {
    check_ajax_referer('my_login_social_nonce', 'nonce');
    
    global $wpdb;
    $table = $wpdb->prefix . 'my_login_social_analytics';
    
    // Create table if not exists
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        platform varchar(50) NOT NULL,
        url text NOT NULL,
        user_id bigint(20) DEFAULT 0,
        ip_address varchar(45) DEFAULT NULL,
        user_agent text,
        shared_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_platform (platform),
        KEY idx_shared_at (shared_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    $data = [
        'platform' => sanitize_text_field($_POST['platform']),
        'url' => esc_url_raw($_POST['url']),
        'user_id' => get_current_user_id(),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'shared_at' => current_time('mysql')
    ];
    
    $wpdb->insert($table, $data);
    wp_send_json_success();
}
} // end function_exists: my_login_track_share_handler
?>