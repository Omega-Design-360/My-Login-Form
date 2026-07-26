-- My Login Form — WordPress-side OTP audit log table (MySQL, NOT Postgres)
--
-- Reference only — WordPress creates this table automatically via dbDelta()
-- the moment the plugin runs (see Includes/Database/OtpDatabase.php,
-- create_table()). You never need to run this file by hand; it exists so
-- the schema is visible/inspectable without reading PHP, and so it can be
-- imported directly (e.g. into a local dev database) if you ever need the
-- table without loading WordPress at all.
--
-- Records every OTP event (sent / send_failed / verified / failed) from the
-- Supabase-backed OTP flow in Includes/Ajax/AuthAjax.php — register, login,
-- and forgot-password. Supabase's own Auth service generates and validates
-- the actual 6-digit codes; this plugin never sees or stores the code
-- itself, only that an attempt happened, for rate-limiting/debugging/audit.
--
-- Replace {wp_prefix} with your site's actual table prefix (default "wp_").

CREATE TABLE IF NOT EXISTS `{wp_prefix}my_login_otp_log` (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    context VARCHAR(20) NOT NULL,        -- 'register' | 'login' | 'forgot_password'
    action VARCHAR(20) NOT NULL,         -- 'sent' | 'send_failed' | 'verified' | 'failed'
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY email_idx (email),
    KEY context_idx (context),
    KEY created_at_idx (created_at)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Entries older than 30 days are deleted automatically by
-- OtpDatabase::cleanup_old_entries(), hooked to the plugin's existing
-- weekly 'my_login_form_cleanup' cron event — no manual maintenance needed.
