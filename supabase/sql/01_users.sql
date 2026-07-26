-- My Login Form — Supabase project schema: users table
--
-- Run this FIRST (before 02_subscriptions.sql, which references this table).
-- Paste into Supabase Dashboard → SQL Editor → New Query → Run.
--
-- Creates the table that AuthAjax::sync_to_supabase() (in
-- Includes/Ajax/AuthAjax.php) upserts into after every login/registration/
-- password reset, via:
--
--   POST {url}/rest/v1/my_login_users
--   Prefer: resolution=merge-duplicates
--
-- PostgREST resolves "merge-duplicates" against the table's primary key, so
-- wp_user_id (the one field that's always the same for a given WordPress
-- user) has to be that primary key — that's what makes the upsert update the
-- existing row instead of erroring on a duplicate.
--
-- Email OTP verification (Settings -> "Require Email Verification") needs no
-- table of its own: it's handled entirely by Supabase's built-in Auth
-- service (auth.users), configured via Authentication -> Email Templates in
-- your project dashboard, not by anything in this file. WordPress-side OTP
-- attempts (sent/verified/failed) are logged separately in the
-- wp_my_login_otp_log MySQL table (Includes/Database/OtpDatabase.php) — not
-- a Supabase table at all, so there's nothing to run here for that.
--
-- To send those OTP emails through Resend instead of Supabase's own limited
-- built-in sender (see the rate-limit issue this session hit testing it):
--   1. Create an API key in Resend, and verify a sending domain there.
--   2. In the Supabase dashboard: Project Settings -> Authentication ->
--      SMTP Settings -> enable "Custom SMTP" and fill in Resend's SMTP
--      credentials (host smtp.resend.com, port 465, username "resend",
--      password = your Resend API key).
--   3. Save. Supabase now relays every Auth email — including OTP codes —
--      through Resend, with no code changes needed on the WordPress side;
--      AuthAjax.php already only ever talks to Supabase's /auth/v1/otp and
--      /auth/v1/verify endpoints, never an email provider directly.

create table if not exists public.my_login_users (
    wp_user_id       bigint primary key,
    email            text not null,
    display_name     text,
    first_name       text,
    last_name        text,
    username         text,
    -- Extra profile detail, synced when available — never anything
    -- sensitive (no password hashes, 2FA secrets, or reset keys leave
    -- WordPress; see AuthAjax::sync_to_supabase() for exactly what's sent).
    phone            text,
    country          text,
    city             text,
    address          text,
    social_provider  text,
    profile_picture  text,
    email_verified   boolean not null default true,
    updated_at       timestamptz not null default now()
);

-- Safe to re-run on a table created by an older version of this file —
-- adds each column only if it isn't already there.
alter table public.my_login_users add column if not exists phone           text;
alter table public.my_login_users add column if not exists country         text;
alter table public.my_login_users add column if not exists city            text;
alter table public.my_login_users add column if not exists address         text;
alter table public.my_login_users add column if not exists social_provider text;
alter table public.my_login_users add column if not exists profile_picture text;
alter table public.my_login_users add column if not exists email_verified  boolean not null default true;

create unique index if not exists my_login_users_email_key
    on public.my_login_users (email);

alter table public.my_login_users enable row level security;

-- RLS enabled with zero policies for anon/authenticated means those roles
-- can't read or write a single row — only the service_role key (used
-- exclusively server-side, in Includes/Ajax/AuthAjax.php's sync_to_supabase()
-- and supabase/SupabaseDatabase.php) can touch this table, because
-- service_role bypasses RLS entirely. This mirrors sql/01_licenses.sql.
--
-- Earlier versions of this file granted the anon role unrestricted
-- select/insert/update here (using (true)/with check (true)), since the
-- plugin has no logged-in Supabase Auth session to scope a policy against.
-- That meant anyone who ever obtained the anon key could read or write
-- every customer's email/address/profile data directly against Supabase's
-- REST API, bypassing WordPress and its nonce/capability checks entirely.
-- Routing writes through the service_role key instead closes that without
-- needing a per-row scoping policy.
drop policy if exists "my_login_users anon insert" on public.my_login_users;
drop policy if exists "my_login_users anon update" on public.my_login_users;
drop policy if exists "my_login_users anon select" on public.my_login_users;

-- Belt-and-braces: explicitly revoke table privileges too, so even a future
-- accidental policy doesn't accomplish anything without someone also
-- explicitly re-granting these.
revoke all on public.my_login_users from anon, authenticated;
