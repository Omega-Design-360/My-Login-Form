-- My Login Form — Licensing schema: activations table
--
-- Run this SECOND, after 01_licenses.sql — this table references licenses.id
-- with a foreign key, so licenses must exist first.
-- Paste into Supabase Dashboard → SQL Editor → New Query → Run.
--
-- One row per (license, domain) pair. A key with max_activations = 1 that
-- already has a row here can't add a second domain.

create table if not exists public.activations (
    id             bigint generated always as identity primary key,
    license_id     bigint not null references public.licenses (id) on delete cascade,

    -- Normalized domain: no protocol, no "www.", no trailing slash, lowercase.
    -- The plugin normalizes before sending; the Edge Function normalizes
    -- again before storing/comparing, so a client-side bug can't cause a
    -- false "already activated elsewhere" for what's really the same site.
    domain         text not null,

    activated_at   timestamptz not null default now(),
    last_check_at  timestamptz
);

create unique index if not exists activations_license_domain_key
    on public.activations (license_id, domain);

create index if not exists activations_license_id_idx
    on public.activations (license_id);

-- Same lockdown as licenses — RLS on, zero policies, explicit revoke.
-- Only the service_role key (Edge Function only) can touch this table.
alter table public.activations enable row level security;

revoke all on public.activations from anon, authenticated;
