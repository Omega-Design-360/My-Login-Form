<?php
/**
 * Mailing page.
 *
 * @package MyLoginForm\Admin
 */
defined('ABSPATH') || exit;

$mail_sent = isset($_GET['mail_sent']) && is_scalar($_GET['mail_sent']) ? absint(wp_unslash($_GET['mail_sent'])) : null;
$mail_failed = isset($_GET['mail_failed']) && is_scalar($_GET['mail_failed']) ? absint(wp_unslash($_GET['mail_failed'])) : null;
$mail_error = isset($_GET['mail_error']) && is_scalar($_GET['mail_error']) ? sanitize_key(wp_unslash($_GET['mail_error'])) : '';
$config_saved = isset($_GET['config_saved']) && is_scalar($_GET['config_saved']) ? sanitize_key(wp_unslash($_GET['config_saved'])) : '';
$config_error = isset($_GET['config_error']) && is_scalar($_GET['config_error']) ? sanitize_key(wp_unslash($_GET['config_error'])) : '';
?>
<div class="wrap mlf-mailing-page">
    <header class="mlf-mailing-heading">
        <div>
            <p class="mlf-mailing-eyebrow"><?php esc_html_e('COMMUNICATIONS', 'my-login-form'); ?></p>
            <h1><?php esc_html_e('Mailing', 'my-login-form'); ?></h1>
            <p class="mlf-mailing-intro"><?php esc_html_e('Send a one-to-one message or a private bulk email through your Resend account.', 'my-login-form'); ?></p>
        </div>
        <span class="mlf-mailing-heading-icon" aria-hidden="true"><span class="dashicons dashicons-email-alt"></span></span>
    </header>

    <?php if ($mail_sent !== null || $mail_failed !== null) : ?>
        <div class="notice <?php echo $mail_failed ? 'notice-warning' : 'notice-success'; ?> is-dismissible"><p>
            <?php
            printf(
                esc_html__('Email delivery finished: %1$d sent, %2$d failed.', 'my-login-form'),
                absint($mail_sent ?? 0),
                absint($mail_failed ?? 0)
            );
            ?>
        </p></div>
    <?php elseif ($mail_error !== '') : ?>
        <div class="notice notice-error"><p>
            <?php
            if ($mail_error === 'not_configured') {
                esc_html_e('Sending is unavailable. Configure both Supabase and Resend first.', 'my-login-form');
            } elseif ($mail_error === 'recipient_count') {
                esc_html_e('Add at least one valid recipient. Check the email addresses you entered and try again.', 'my-login-form');
            } elseif ($mail_error === 'recipient_limit') {
                esc_html_e('The recipient list exceeds the selected per-send limit. Choose a higher limit or reduce the list.', 'my-login-form');
            } elseif ($mail_error === 'source_missing') {
                esc_html_e('Choose a recipient source and provide its list or file.', 'my-login-form');
            } elseif ($mail_error === 'xlsx_unavailable') {
                esc_html_e('This server cannot read XLSX files. Save the list as CSV and upload it instead.', 'my-login-form');
            } elseif ($mail_error === 'invalid_file') {
                esc_html_e('Upload a readable CSV or XLSX file.', 'my-login-form');
            } elseif ($mail_error === 'empty_file') {
                esc_html_e('The uploaded mailing list contains no email addresses.', 'my-login-form');
            } elseif ($mail_error === 'upload_failed') {
                esc_html_e('The mailing list file could not be uploaded. Check your server upload size limit and try again.', 'my-login-form');
            } elseif ($mail_error === 'sender_invalid') {
                esc_html_e('Choose a verified Resend sending domain and enter a valid mailbox name, or use the saved sender address.', 'my-login-form');
            } else {
                esc_html_e('Enter a subject and message before sending.', 'my-login-form');
            }
            ?>
        </p></div>
    <?php endif; ?>

    <?php if (in_array($config_saved, ['supabase', 'resend', 'email_preferences'], true)) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Mailing integration settings saved.', 'my-login-form'); ?></p></div>
    <?php elseif ($config_error !== '') : ?>
        <div class="notice notice-error"><p>
            <?php
            if ($config_error === 'supabase_required') {
                esc_html_e('To enable Supabase, provide a valid HTTP/HTTPS project URL and anon key.', 'my-login-form');
            } elseif ($config_error === 'resend_email') {
                esc_html_e('Enter a valid sender email address.', 'my-login-form');
            } else {
                esc_html_e('The integration settings could not be saved.', 'my-login-form');
            }
            ?>
        </p></div>
    <?php endif; ?>

    <?php if (!$resend_guide_done) : ?>
        <?php include MY_LOGIN_FORM_DIR . 'Admin/Pages/partials/resend-setup-guide.php'; ?>
    <?php endif; ?>

    <section class="mlf-mailing-status mlf-mailing-provider-setup" aria-labelledby="mlf-mailing-resend-title">
        <div class="mlf-mailing-section-heading">
            <div>
                <p class="mlf-mailing-eyebrow"><?php esc_html_e('EMAIL DELIVERY', 'my-login-form'); ?></p>
                <h2 id="mlf-mailing-resend-title"><?php esc_html_e('Resend setup', 'my-login-form'); ?></h2>
            </div>
            <?php if ($resend_ready) : ?>
                <span class="mlf-mailing-ready-label"><?php esc_html_e('Ready to send', 'my-login-form'); ?></span>
            <?php else : ?>
                <span class="mlf-mailing-wait-label"><?php esc_html_e('Setup required', 'my-login-form'); ?></span>
            <?php endif; ?>
        </div>
        <div class="mlf-mailing-config-card mlf-mailing-resend-config">
            <div class="mlf-mailing-status-card <?php echo $resend_ready ? 'is-ready' : 'is-pending'; ?>">
                <span class="mlf-mailing-status-mark" aria-hidden="true"><?php echo $resend_ready ? '&#10003;' : '1'; ?></span>
                <div><h3><?php esc_html_e('Resend', 'my-login-form'); ?></h3><p><?php echo $resend_ready ? esc_html__('Sender configured', 'my-login-form') : esc_html__('API key and sender needed', 'my-login-form'); ?></p></div>
            </div>
            <form class="mlf-mailing-config-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="my_login_form_save_mailing_config">
                <input type="hidden" name="config_type" value="resend">
                <?php wp_nonce_field('my_login_form_save_mailing_resend'); ?>
                <div class="mlf-mailing-field">
                    <label for="mlf-resend-key"><?php esc_html_e('Resend API key', 'my-login-form'); ?></label>
                    <input id="mlf-resend-key" name="resend_api_key" type="password" autocomplete="new-password" placeholder="<?php echo esc_attr($resend_key_hint ?: 're_xxxxxxxxxxxxxxxxxxxxxxxxxx'); ?>">
                    <p><?php esc_html_e('Leave blank to keep the saved key.', 'my-login-form'); ?></p>
                </div>
                <div class="mlf-mailing-field">
                    <label for="mlf-resend-from"><?php esc_html_e('Verified sender email', 'my-login-form'); ?></label>
                    <input id="mlf-resend-from" name="resend_from_email" type="email" value="<?php echo esc_attr($resend_from); ?>" placeholder="noreply@yourdomain.com" required>
                </div>
                <div class="mlf-mailing-field">
                    <label for="mlf-resend-name"><?php esc_html_e('Sender name (optional)', 'my-login-form'); ?></label>
                    <input id="mlf-resend-name" name="resend_from_name" type="text" value="<?php echo esc_attr($resend_from_name); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                </div>
                <?php submit_button(__('Save Resend settings', 'my-login-form'), 'secondary', 'submit', false, ['class' => 'mlf-mailing-config-save']); ?>
            </form>
        </div>
    </section>

    <?php if (!$supabase_ready) : ?>
        <?php include MY_LOGIN_FORM_DIR . 'Admin/Pages/partials/supabase-setup-guide.php'; ?>
    <?php endif; ?>

    <section class="mlf-mailing-status mlf-mailing-provider-setup" aria-labelledby="mlf-mailing-supabase-title">
        <div class="mlf-mailing-section-heading">
            <div>
                <p class="mlf-mailing-eyebrow"><?php esc_html_e('USER DATA CONNECTION', 'my-login-form'); ?></p>
                <h2 id="mlf-mailing-supabase-title"><?php esc_html_e('Supabase setup', 'my-login-form'); ?></h2>
            </div>
            <?php if ($supabase_ready) : ?>
                <span class="mlf-mailing-ready-label"><?php esc_html_e('Connected', 'my-login-form'); ?></span>
            <?php else : ?>
                <span class="mlf-mailing-wait-label"><?php esc_html_e('Setup required', 'my-login-form'); ?></span>
            <?php endif; ?>
        </div>
        <div class="mlf-mailing-config-card mlf-mailing-supabase-config">
            <div class="mlf-mailing-status-card <?php echo $supabase_ready ? 'is-ready' : 'is-pending'; ?>">
                <span class="mlf-mailing-status-mark" aria-hidden="true"><?php echo $supabase_ready ? '&#10003;' : '2'; ?></span>
                <div><h3><?php esc_html_e('Supabase', 'my-login-form'); ?></h3><p><?php echo $supabase_ready ? esc_html__('Connected and enabled', 'my-login-form') : esc_html__('Setup needed', 'my-login-form'); ?></p></div>
            </div>
            <form class="mlf-mailing-config-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="my_login_form_save_mailing_config">
                <input type="hidden" name="config_type" value="supabase">
                <?php wp_nonce_field('my_login_form_save_mailing_supabase'); ?>
                <div class="mlf-mailing-field">
                    <label for="mlf-supabase-url"><?php esc_html_e('Project URL', 'my-login-form'); ?></label>
                    <input id="mlf-supabase-url" name="supabase_url" type="url" value="<?php echo esc_attr($supabase_url_value); ?>" placeholder="https://your-project.supabase.co" required>
                </div>
                <div class="mlf-mailing-field">
                    <label for="mlf-supabase-anon"><?php esc_html_e('Anon / Publishable key', 'my-login-form'); ?></label>
                    <input id="mlf-supabase-anon" name="supabase_anon_key" type="password" autocomplete="new-password" placeholder="<?php echo esc_attr($supabase_anon_hint ?: __('Paste your Supabase anon key', 'my-login-form')); ?>">
                    <p><?php esc_html_e('Leave blank to keep the saved key.', 'my-login-form'); ?></p>
                </div>
                <div class="mlf-mailing-field">
                    <label for="mlf-supabase-service"><?php esc_html_e('Service role key (required for WordPress user sync)', 'my-login-form'); ?></label>
                    <input id="mlf-supabase-service" name="supabase_service_key" type="password" autocomplete="new-password" placeholder="<?php echo esc_attr($supabase_service_hint ?: __('Paste service role key', 'my-login-form')); ?>">
                    <p><?php esc_html_e('Required for the Users page sync action. Keep it secret; it is stored server-side and never sent to visitors. Leave blank to keep the saved key.', 'my-login-form'); ?></p>
                </div>
                <div id="mlf-mailing-supabase-test-status" class="mlf-mailing-supabase-test-status" aria-live="polite"></div>
                <label class="mlf-mailing-enable"><input type="checkbox" name="enable_supabase" value="1" <?php checked((bool) get_option('my_login_supabase_enabled', 0)); ?>> <span><?php esc_html_e('Enable Supabase connection', 'my-login-form'); ?></span></label>
                <div class="mlf-mailing-config-actions">
                    <button type="button" class="button" id="mlf-mailing-test-supabase"><?php esc_html_e('Test Connection', 'my-login-form'); ?></button>
                    <?php submit_button(__('Save Supabase settings', 'my-login-form'), 'secondary', 'submit', false, ['class' => 'mlf-mailing-config-save']); ?>
                </div>
            </form>
        </div>
    </section>

    <?php if (!$supabase_users_sync_complete) : ?>
        <?php include MY_LOGIN_FORM_DIR . 'Admin/Pages/partials/supabase-users-sql.php'; ?>
    <?php endif; ?>

    <section class="mlf-mailing-instructions" aria-label="<?php esc_attr_e('Mailing instructions', 'my-login-form'); ?>">
        <div class="mlf-mailing-instructions-icon" aria-hidden="true"><span class="dashicons dashicons-info-outline"></span></div>
        <div>
            <h2><?php esc_html_e('Mailing tips', 'my-login-form'); ?></h2>
            <p class="mlf-mailing-privacy"><?php esc_html_e('Bulk messages are sent separately to each address, keeping recipient addresses private. Imported addresses are used only for this send and are not added to WordPress users.', 'my-login-form'); ?></p>
            <p class="mlf-mailing-privacy"><?php esc_html_e('Resend provides verified sending domains through its API, not a list of individual mailbox addresses. Choose a domain in the sender dropdown and enter a mailbox name (for example, support) to form the From address.', 'my-login-form'); ?></p>
        </div>
    </section>

    <section class="mlf-mailing-email-preferences" aria-labelledby="mlf-mailing-email-preferences-title">
        <div class="mlf-mailing-section-heading">
            <div>
                <p class="mlf-mailing-eyebrow"><?php esc_html_e('SITE EMAILS', 'my-login-form'); ?></p>
                <h2 id="mlf-mailing-email-preferences-title"><?php esc_html_e('Email settings', 'my-login-form'); ?></h2>
            </div>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="my_login_form_save_mailing_config">
            <input type="hidden" name="config_type" value="email_preferences">
            <?php wp_nonce_field('my_login_form_save_mailing_email_preferences'); ?>
            <div class="mlf-mailing-email-options">
                <label class="mlf-mailing-email-option">
                    <input type="checkbox" name="email_verification" value="1" <?php checked((bool) get_option('my_login_form_email_verification', 1)); ?>>
                    <span><strong><?php esc_html_e('Require email verification', 'my-login-form'); ?></strong><small><?php esc_html_e('Require a six-digit email code before login. Supabase must be connected.', 'my-login-form'); ?></small></span>
                </label>
                <label class="mlf-mailing-email-option">
                    <input type="checkbox" name="welcome_email" value="1" <?php checked((bool) get_option('my_login_form_welcome_email', 1)); ?>>
                    <span><strong><?php esc_html_e('Send welcome email', 'my-login-form'); ?></strong><small><?php esc_html_e('Email new users after registration.', 'my-login-form'); ?></small></span>
                </label>
                <label class="mlf-mailing-email-option">
                    <input type="checkbox" name="admin_notifications" value="1" <?php checked((bool) get_option('my_login_form_admin_notifications', 1)); ?>>
                    <span><strong><?php esc_html_e('Admin notifications', 'my-login-form'); ?></strong><small><?php esc_html_e('Notify the site administrator when someone registers.', 'my-login-form'); ?></small></span>
                </label>
            </div>
            <?php submit_button(__('Save email settings', 'my-login-form'), 'primary', 'submit', false, ['class' => 'mlf-mailing-config-save']); ?>
        </form>
    </section>

    <?php if ($mailing_ready) : ?>
        <form class="mlf-mailing-compose" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="my_login_form_send_mailing">
            <?php wp_nonce_field('my_login_form_send_mailing'); ?>
            <div class="mlf-mailing-compose-heading">
                <div><p class="mlf-mailing-eyebrow"><?php esc_html_e('NEW MESSAGE', 'my-login-form'); ?></p><h2><?php esc_html_e('Compose email', 'my-login-form'); ?></h2></div>
                <span class="dashicons dashicons-edit" aria-hidden="true"></span>
            </div>
            <div class="mlf-mailing-field">
                <label for="mlf-mail-recipient-source"><?php esc_html_e('Recipient source', 'my-login-form'); ?></label>
                <select id="mlf-mail-recipient-source" name="recipient_source">
                    <option value="manual"><?php esc_html_e('Enter email addresses', 'my-login-form'); ?></option>
                    <option value="import"><?php esc_html_e('Import CSV or XLSX list', 'my-login-form'); ?></option>
                    <option value="wp_users"><?php esc_html_e('Use WordPress users', 'my-login-form'); ?></option>
                </select>
                <p><?php esc_html_e('Choose one source for this send. Imported contacts stay separate from your website user accounts.', 'my-login-form'); ?></p>
            </div>
            <div class="mlf-mailing-field" data-mlf-recipient-source="manual">
                <label for="mlf-mail-recipients"><?php esc_html_e('Email addresses', 'my-login-form'); ?></label>
                <textarea id="mlf-mail-recipients" name="recipients" rows="5" placeholder="name@example.com&#10;another@example.com"></textarea>
                <p><?php esc_html_e('Separate addresses with commas or new lines. Each person receives a private copy.', 'my-login-form'); ?></p>
            </div>
            <div class="mlf-mailing-field" data-mlf-recipient-source="import" hidden>
                <label for="mlf-mailing-list-file"><?php esc_html_e('Import mailing list', 'my-login-form'); ?></label>
                <input id="mlf-mailing-list-file" name="mailing_list" type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                <p><?php esc_html_e('Upload a CSV or Excel XLSX file. Use a column named Email or Email Address; if there is no email header, valid email addresses are collected from the sheet.', 'my-login-form'); ?></p>
            </div>
            <div class="mlf-mailing-field" data-mlf-recipient-source="wp_users" hidden>
                <p><?php printf(esc_html__('This will send to all %d WordPress user accounts with valid email addresses.', 'my-login-form'), (int) count_users()['total_users']); ?></p>
            </div>
            <div class="mlf-mailing-field">
                <label for="mlf-mail-recipient-limit"><?php esc_html_e('Maximum recipients for this send', 'my-login-form'); ?></label>
                <select id="mlf-mail-recipient-limit" name="recipient_limit">
                    <option value="50">50</option>
                    <option value="100" selected>100</option>
                    <option value="250">250</option>
                    <option value="500">500</option>
                    <option value="1000">1000</option>
                    <option value="custom"><?php esc_html_e('Custom number', 'my-login-form'); ?></option>
                </select>
                <div class="mlf-mailing-custom-limit" hidden>
                    <label for="mlf-mail-recipient-limit-custom"><?php esc_html_e('Custom recipient limit', 'my-login-form'); ?></label>
                    <input id="mlf-mail-recipient-limit-custom" name="recipient_limit_custom" type="number" min="1" step="1" inputmode="numeric" placeholder="250">
                </div>
                <p><?php esc_html_e('The selected cap applies to this send. You can choose a preset or enter any positive number.', 'my-login-form'); ?></p>
            </div>
            <div class="mlf-mailing-field">
                <label for="mlf-mail-subject"><?php esc_html_e('Subject', 'my-login-form'); ?></label>
                <input id="mlf-mail-subject" name="subject" type="text" maxlength="250" required placeholder="<?php esc_attr_e('Write a clear subject', 'my-login-form'); ?>">
            </div>
            <div class="mlf-mailing-field">
                <label for="mlf-mail-sender-domain"><?php esc_html_e('Send from', 'my-login-form'); ?></label>
                <select id="mlf-mail-sender-domain" name="sender_domain">
                    <option value=""><?php echo esc_html(sprintf(__('Default sender: %s', 'my-login-form'), $resend_from)); ?></option>
                    <?php foreach ($resend_domains as $domain) : ?>
                        <option value="<?php echo esc_attr($domain); ?>"><?php echo esc_html(sprintf(__('Use a sender on %s', 'my-login-form'), $domain)); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($resend_domains) : ?>
                    <label class="mlf-mailing-local-label" for="mlf-mail-sender-local"><?php esc_html_e('Mailbox name for the selected domain', 'my-login-form'); ?></label>
                    <div class="mlf-mailing-sender-address"><input id="mlf-mail-sender-local" name="sender_local" type="text" value="<?php echo esc_attr($default_sender_local); ?>" placeholder="support"><span>@<?php esc_html_e('selected domain', 'my-login-form'); ?></span></div>
                    <p><?php printf(esc_html__('Loaded %d verified sending domain(s) from Resend. This list refreshes automatically every 10 minutes.', 'my-login-form'), count($resend_domains)); ?></p>
                <?php elseif ($resend_domains_error !== '') : ?>
                    <p><?php esc_html_e('Could not load verified domains from Resend. You can still use the saved default sender address.', 'my-login-form'); ?></p>
                <?php else : ?>
                    <p><?php esc_html_e('No verified sending domains were found. You can still use the saved default sender address.', 'my-login-form'); ?></p>
                <?php endif; ?>
            </div>
            <div class="mlf-mailing-field">
                <label for="mlf-mail-message"><?php esc_html_e('Message', 'my-login-form'); ?></label>
                <textarea id="mlf-mail-message" name="message" rows="10" required placeholder="<?php esc_attr_e('Write your message here...', 'my-login-form'); ?>"></textarea>
                <p><?php esc_html_e('Sent as plain text through your Resend account.', 'my-login-form'); ?></p>
            </div>
            <div class="mlf-mailing-compose-footer">
                <span><?php esc_html_e('Please verify your recipient list before sending.', 'my-login-form'); ?></span>
                <?php submit_button(__('Send email', 'my-login-form'), 'primary', 'submit', false, ['class' => 'mlf-mailing-send']); ?>
            </div>
        </form>
    <?php else : ?>
        <p class="description" style="margin-top: 20px;"><?php esc_html_e('The mailing form will become available once both integrations are configured.', 'my-login-form'); ?></p>
    <?php endif; ?>
</div>
