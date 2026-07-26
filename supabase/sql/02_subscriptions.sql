-- My Login Form — Supabase project schema: subscriptions table
--
-- Run this SECOND, after 01_users.sql — this table references
-- my_login_users.wp_user_id with a foreign key, so that table must exist first.
-- Paste into Supabase Dashboard → SQL Editor → New Query → Run.
--
-- Subscription plan tracking, synced from WooCommerce Subscriptions by
-- supabase/SupabaseSubscriptions.php whenever a subscription's status
-- changes (active, on-hold, cancelled, expired, pending-cancel, ...). That
-- separate WooCommerce add-on plugin isn't installed on this site yet, so
-- this table will simply stay empty until it is — the sync code is already
-- wired up and activates automatically the moment the plugin is added.

create table if not exists public.my_login_subscriptions (
    id                bigint generated always as identity primary key,
    wp_user_id        bigint not null references public.my_login_users (wp_user_id) on delete cascade,
    subscription_id   bigint not null,
    product_id        bigint,
    product_name      text,
    status            text not null,
    start_date        timestamptz,
    next_payment_date timestamptz,
    end_date          timestamptz,
    updated_at        timestamptz not null default now()
);

create unique index if not exists my_login_subscriptions_subscription_id_key
    on public.my_login_subscriptions (subscription_id);

create index if not exists my_login_subscriptions_wp_user_id_idx
    on public.my_login_subscriptions (wp_user_id);

alter table public.my_login_subscriptions enable row level security;

-- RLS enabled with zero policies for anon/authenticated — only the
-- service_role key (used server-side in supabase/SupabaseSubscriptions.php)
-- can touch this table. See the matching comment in sql/01_users.sql for why
-- the anon-role policies this file used to grant here were removed.
drop policy if exists "my_login_subscriptions anon insert" on public.my_login_subscriptions;
drop policy if exists "my_login_subscriptions anon update" on public.my_login_subscriptions;
drop policy if exists "my_login_subscriptions anon select" on public.my_login_subscriptions;

revoke all on public.my_login_subscriptions from anon, authenticated;
