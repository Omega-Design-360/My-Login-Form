<?php
/** Resend setup guide for the Mailing page. */
defined('ABSPATH') || exit;
?>
<section class="mlf-supabase-guide mlf-resend-guide" aria-labelledby="mlf-resend-guide-title">
    <header class="mlf-supabase-guide-header">
        <span class="mlf-supabase-guide-icon dashicons dashicons-email-alt" aria-hidden="true"></span>
        <div>
            <p class="mlf-supabase-guide-kicker"><?php esc_html_e('EMAIL DELIVERY SETUP', 'my-login-form'); ?></p>
            <h2 id="mlf-resend-guide-title"><?php esc_html_e('Connect your Resend account', 'my-login-form'); ?></h2>
            <p><?php esc_html_e('Set up a verified sender, add your API key, and send a test message.', 'my-login-form'); ?></p>
        </div>
    </header>

    <ol class="mlf-supabase-guide-steps">
        <li>
            <span class="mlf-supabase-guide-number">01</span>
            <div>
                <h3><?php esc_html_e('Create or open your Resend account', 'my-login-form'); ?></h3>
                <p><?php esc_html_e('Sign in to the account that will send your website email.', 'my-login-form'); ?></p>
                <a href="https://resend.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open Resend', 'my-login-form'); ?> <span aria-hidden="true">↗</span></a>
            </div>
        </li>
        <li>
            <span class="mlf-supabase-guide-number">02</span>
            <div>
                <h3><?php esc_html_e('Verify a sending domain', 'my-login-form'); ?></h3>
                <p><?php esc_html_e('Add a domain you own in Resend. Copy the DNS records Resend provides into your domain provider, then wait until sending is verified.', 'my-login-form'); ?></p>
                <a href="https://resend.com/domains" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open Resend Domains', 'my-login-form'); ?> <span aria-hidden="true">↗</span></a>
            </div>
        </li>
        <li>
            <span class="mlf-supabase-guide-number">03</span>
            <div>
                <h3><?php esc_html_e('Create an API key', 'my-login-form'); ?></h3>
                <p><?php esc_html_e('Create a key from API Keys and copy it when Resend displays it. This plugin uses the key to send mail and load your verified sender domains, so its permissions must allow both.', 'my-login-form'); ?></p>
                <a href="https://resend.com/api-keys" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open Resend API Keys', 'my-login-form'); ?> <span aria-hidden="true">↗</span></a>
            </div>
        </li>
        <li>
            <span class="mlf-supabase-guide-number">04</span>
            <div>
                <h3><?php esc_html_e('Save and test your sender', 'my-login-form'); ?></h3>
                <p><?php esc_html_e('Paste the API key in the Resend settings below. Enter a sender email using your verified domain, save the settings, then send a test message to yourself from the compose form.', 'my-login-form'); ?></p>
            </div>
        </li>
    </ol>

    <p class="mlf-supabase-guide-note"><span class="dashicons dashicons-lock" aria-hidden="true"></span> <?php esc_html_e('Keep the API key private. It is stored server-side and is not shown to visitors.', 'my-login-form'); ?></p>
</section>
