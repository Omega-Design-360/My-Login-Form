-- My Login Form — Licensing schema: licenses table
--
-- Run this FIRST (before 02_activations.sql, which references this table).
-- Paste into Supabase Dashboard → SQL Editor → New Query → Run.
--
-- One row per purchase. Holds the key, the buyer's email, which plan, when
-- it expires, and how many sites they're allowed.

create table if not exists public.licenses (
    id               bigint generated always as identity primary key,
    license_key      text not null,
    email            text not null,
    plan             text not null default '3-year', -- '6-month' | '1-year' | '3-year' | 'lifetime' (legacy, no longer sold)
    max_activations  int  not null default 1,

    -- Informational only — never trust this column alone to decide whether
    -- a license is valid. Always compare expires_at to now() at request
    -- time (see the Edge Function's validate/activate actions). A status
    -- column can go stale; a timestamp comparison can't.
    status           text not null default 'active',   -- 'active' | 'revoked'

    expires_at       timestamptz,        -- NULL = lifetime, never expires
    order_id         bigint,             -- WooCommerce order ID on the store site, for support lookups
    created_at       timestamptz not null default now(),
    updated_at       timestamptz not null default now()
);

create unique index if not exists licenses_license_key_key
    on public.licenses (license_key);

create index if not exists licenses_email_idx
    on public.licenses (email);

-- RLS enabled with zero policies for anon/authenticated means those roles
-- can't read or write a single row. Only the service_role key (used
-- exclusively inside the Edge Function) can touch this table at all,
-- because service_role bypasses RLS entirely.
alter table public.licenses enable row level security;

-- Belt-and-braces: explicitly revoke table privileges too, so even a future
-- accidental policy doesn't accomplish anything without someone also
-- explicitly re-granting these.
revoke all on public.licenses from anon, authenticated;
