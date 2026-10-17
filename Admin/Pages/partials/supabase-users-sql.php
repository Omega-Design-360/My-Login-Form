<?php
/** Shared copy-and-run SQL instructions for WordPress user sync. */
defined('ABSPATH') || exit;
?>
<details class="mlf-mailing-sql-setup">
    <summary><?php esc_html_e('Supabase users table SQL setup', 'my-login-form'); ?></summary>
    <div class="mlf-mailing-sql-content">
        <p><?php esc_html_e('In Supabase, open SQL Editor and choose New Query. Copy this complete script, paste it into the editor, and click Run. This creates the table used by WordPress user sync.', 'my-login-form'); ?></p>
        <?php if (!empty($supabase_users_sql)) : ?>
            <div class="mlf-mailing-sql-actions">
                <button type="button" class="button" data-mlf-copy-sql="mlf-supabase-users-sql"><?php esc_html_e('Copy SQL', 'my-login-form'); ?></button>
            </div>
            <textarea id="mlf-supabase-users-sql" readonly rows="14" aria-label="<?php esc_attr_e('Supabase users table SQL', 'my-login-form'); ?>"><?php echo esc_textarea($supabase_users_sql); ?></textarea>
        <?php else : ?>
            <p class="mlf-mailing-sql-missing"><?php esc_html_e('The SQL setup file is missing from this plugin installation.', 'my-login-form'); ?></p>
        <?php endif; ?>
    </div>
</details>
