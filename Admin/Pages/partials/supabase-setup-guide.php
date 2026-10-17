<?php
/** Shared Supabase setup guide for the Mailing and Social Login setup pages. */
defined('ABSPATH') || exit;
?>
<section class="mlf-supabase-guide" aria-labelledby="mlf-supabase-guide-title">
    <header class="mlf-supabase-guide-header">
        <span class="mlf-supabase-guide-icon dashicons dashicons-database-import" aria-hidden="true"></span>
        <div>
            <p class="mlf-supabase-guide-kicker"><?php esc_html_e('QUICK SETUP', 'my-login-form'); ?></p>
            <h2 id="mlf-supabase-guide-title"><?php esc_html_e('Connect your Supabase project', 'my-login-form'); ?></h2>
            <p><?php esc_html_e('Follow these steps, then use the connection form below.', 'my-login-form'); ?></p>
        </div>
    </header>

    <ol class="mlf-supabase-guide-steps">
        <li>
            <span class="mlf-supabase-guide-number">01</span>
            <div>
                <h3><?php esc_html_e('Create a Supabase account', 'my-login-form'); ?></h3>
                <p><?php esc_html_e('Sign up for a free account or sign in to your existing account.', 'my-login-form'); ?></p>
                <a href="https://supabase.com/dashboard/sign-up" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Sign up for Supabase', 'my-login-form'); ?> <span aria-hidden="true">↗</span></a>
            </div>
        </li>
        <li>
            <span class="mlf-supabase-guide-number">02</span>
            <div>
                <h3><?php esc_html_e('Create a project', 'my-login-form'); ?></h3>
                <p><?php esc_html_e('Choose New Project, enter a project name and strong database password, select a region close to your visitors, and wait for setup to finish.', 'my-login-form'); ?></p>
            </div>
        </li>
        <li>
            <span class="mlf-supabase-guide-number">03</span>
            <div>
                <h3><?php esc_html_e('Copy your project URL and API key', 'my-login-form'); ?></h3>
                <p><?php esc_html_e('In Project Settings → API, copy the Project URL and the anon / publishable project API key.', 'my-login-form'); ?></p>
                <a href="https://supabase.com/dashboard" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open Supabase Dashboard', 'my-login-form'); ?> <span aria-hidden="true">↗</span></a>
            </div>
        </li>
        <li>
            <span class="mlf-supabase-guide-number">04</span>
            <div>
                <h3><?php esc_html_e('Test and save the connection', 'my-login-form'); ?></h3>
                <p><?php esc_html_e('Paste the URL and key into the form below, click Test Connection, then enable Supabase and save. For WordPress user sync, add the service role key and run the SQL script below. Keep the service role key private.', 'my-login-form'); ?></p>
            </div>
        </li>
    </ol>

    <p class="mlf-supabase-guide-note"><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span> <?php esc_html_e('A project URL or key with an extra space can cause connection errors. Copy each value carefully.', 'my-login-form'); ?></p>
</section>
