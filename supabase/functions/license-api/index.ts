// My Login Form — Licensing Edge Function
//
// Deploy from your Supabase project root:
//   supabase functions new license-api        (creates supabase/functions/license-api/)
//   # copy this file to supabase/functions/license-api/index.ts
//   supabase secrets set LICENSE_GENERATE_SECRET=<a long random string>
//   supabase functions deploy license-api --no-verify-jwt
//
// SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY are provided automatically by
// the Supabase platform inside every Edge Function — do not set them
// yourself. The service role key is what lets this function read/write
// licenses/activations despite RLS blocking everyone else (see schema.sql).
//
// --no-verify-jwt is required because the plugin (an anonymous WordPress
// site) has no Supabase Auth session to present a JWT with. Authorization
// here is instead: the "generate"/"renew" actions require a shared secret
// header only your store server knows (x-generate-secret); "activate",
// "validate", and "deactivate" are scoped to a specific license_key, which
// only someone who already has that key (i.e. bought the license) would have.
//
// Five actions, dispatched by body.action:
//   generate    - store only. Mints a new license for a purchase.
//   renew       - store only. Pushes expires_at forward on an existing key.
//   activate    - plugin calls once, when the buyer pastes their key.
//   validate    - plugin calls daily (WP-Cron) to re-check the key is still good.
//   deactivate  - plugin calls when the buyer wants to free up this domain.

import { createClient } from 'https://esm.sh/@supabase/supabase-js@2';

const SUPABASE_URL = Deno.env.get('SUPABASE_URL')!;
const SERVICE_ROLE_KEY = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!;
const GENERATE_SECRET = Deno.env.get('LICENSE_GENERATE_SECRET') ?? '';

const supabase = createClient(SUPABASE_URL, SERVICE_ROLE_KEY, {
  auth: { persistSession: false },
});

function json(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

// Strip protocol, "www.", trailing slash; lowercase. Mirrors
// License::normalize_domain() on the WordPress side exactly — normalizing
// on both ends means a difference in normalization logic can never itself
// cause a false "different site" mismatch.
function normalizeDomain(input: string): string {
  return input
    .trim()
    .toLowerCase()
    .replace(/^https?:\/\//, '')
    .replace(/^www\./, '')
    .replace(/\/+$/, '');
}

// XXXX-XXXX-XXXX-XXXX-XXXX using an alphabet with no ambiguous characters
// (no 0/O, 1/I/l) so a support agent reading it back over email/phone
// never has to guess which character someone meant.
function generateKey(): string {
  const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  const groups: string[] = [];
  for (let g = 0; g < 5; g++) {
    let group = '';
    for (let i = 0; i < 4; i++) {
      group += alphabet[Math.floor(Math.random() * alphabet.length)];
    }
    groups.push(group);
  }
  return groups.join('-');
}

function planToExpiry(plan: string, from: Date = new Date()): string | null {
  const d = new Date(from);
  switch (plan) {
    case '6-month':
      d.setMonth(d.getMonth() + 6);
      return d.toISOString();
    case '1-year':
      d.setFullYear(d.getFullYear() + 1);
      return d.toISOString();
    case '3-year':
      d.setFullYear(d.getFullYear() + 3);
      return d.toISOString();
    case 'lifetime':
    default:
      return null;
  }
}

Deno.serve(async (req: Request) => {
  if (req.method !== 'POST') {
    return json({ error: 'POST only' }, 405);
  }

  let body: Record<string, unknown>;
  try {
    body = await req.json();
  } catch {
    return json({ error: 'Invalid JSON body' }, 400);
  }

  switch (body?.action) {
    case 'generate':   return handleGenerate(req, body);
    case 'renew':      return handleRenew(req, body);
    case 'activate':   return handleActivate(body);
    case 'validate':   return handleValidate(body);
    case 'deactivate': return handleDeactivate(body);
    default:
      return json({ error: 'Unknown action' }, 400);
  }
});

function requireGenerateSecret(req: Request): boolean {
  const provided = req.headers.get('x-generate-secret') ?? '';
  return GENERATE_SECRET !== '' && provided === GENERATE_SECRET;
}

async function handleGenerate(req: Request, body: Record<string, unknown>) {
  if (!requireGenerateSecret(req)) {
    return json({ error: 'Unauthorized' }, 401);
  }

  const email = String(body?.email ?? '').trim().toLowerCase();
  const plan = String(body?.plan ?? '3-year');
  const maxActivations = Number.isInteger(body?.max_activations) ? (body.max_activations as number) : 1;
  const orderId = body?.order_id ?? null;

  if (!email || !email.includes('@')) {
    return json({ error: 'A valid email is required' }, 400);
  }
  // 'lifetime' is accepted here only for legacy back-compat (e.g. a store
  // config that hasn't been updated yet) — it's no longer a sold plan.
  if (!['6-month', '1-year', '3-year', 'lifetime'].includes(plan)) {
    return json({ error: 'Invalid plan' }, 400);
  }

  let licenseKey = generateKey();
  // Astronomically unlikely collision given the keyspace, but a purchase
  // is worth guarding anyway — retry a few times rather than ever risk
  // silently overwriting an existing license.
  for (let attempt = 0; attempt < 5; attempt++) {
    const { data: existing } = await supabase
      .from('licenses')
      .select('id')
      .eq('license_key', licenseKey)
      .maybeSingle();
    if (!existing) break;
    licenseKey = generateKey();
  }

  const { data, error } = await supabase
    .from('licenses')
    .insert({
      license_key: licenseKey,
      email,
      plan,
      max_activations: maxActivations,
      status: 'active',
      expires_at: planToExpiry(plan),
      order_id: orderId,
    })
    .select()
    .single();

  if (error) {
    return json({ error: error.message }, 500);
  }

  return json({
    license_key: data.license_key,
    email: data.email,
    plan: data.plan,
    expires_at: data.expires_at,
    max_activations: data.max_activations,
  });
}

async function handleRenew(req: Request, body: Record<string, unknown>) {
  if (!requireGenerateSecret(req)) {
    return json({ error: 'Unauthorized' }, 401);
  }

  const licenseKey = String(body?.license_key ?? '').trim();
  const plan = String(body?.plan ?? '');

  if (!licenseKey) {
    return json({ error: 'license_key is required' }, 400);
  }
  if (!['6-month', '1-year', '3-year'].includes(plan)) {
    return json({ error: "Invalid plan for renewal (lifetime licenses don't need renewing)" }, 400);
  }

  const { data: license } = await supabase
    .from('licenses')
    .select('*')
    .eq('license_key', licenseKey)
    .maybeSingle();

  if (!license) {
    return json({ error: 'License key not found' }, 404);
  }

  // Extend from whichever is later: their current expiry, or right now.
  // Renewing early carries the remaining time forward (fair to the buyer).
  // Renewing late doesn't backdate the new period to their old expiry, so
  // they don't get a free gap for the time they went unrenewed.
  const now = new Date();
  const currentExpiry = license.expires_at ? new Date(license.expires_at) : now;
  const base = currentExpiry.getTime() > now.getTime() ? currentExpiry : now;

  const newExpiry = new Date(base);
  if (plan === '6-month') newExpiry.setMonth(newExpiry.getMonth() + 6);
  if (plan === '1-year') newExpiry.setFullYear(newExpiry.getFullYear() + 1);
  if (plan === '3-year') newExpiry.setFullYear(newExpiry.getFullYear() + 3);

  const { error } = await supabase
    .from('licenses')
    .update({ expires_at: newExpiry.toISOString(), plan, status: 'active', updated_at: now.toISOString() })
    .eq('id', license.id);

  if (error) {
    return json({ error: error.message }, 500);
  }

  // Activations are untouched by design — the buyer's already-activated
  // site(s) just keep working, nothing to redo.
  return json({ license_key: license.license_key, expires_at: newExpiry.toISOString() });
}

async function handleActivate(body: Record<string, unknown>) {
  const licenseKey = String(body?.license_key ?? '').trim();
  const domain = normalizeDomain(String(body?.domain ?? ''));

  if (!licenseKey || !domain) {
    return json({ valid: false, error: 'license_key and domain are required' }, 400);
  }

  const { data: license } = await supabase
    .from('licenses')
    .select('*')
    .eq('license_key', licenseKey)
    .maybeSingle();

  if (!license) {
    return json({ valid: false, error: 'License key not found' }, 404);
  }
  if (license.status === 'revoked') {
    return json({ valid: false, error: 'This license has been revoked' }, 403);
  }
  // Never trust the status column alone — compare the timestamp directly,
  // every time, so a stale status can't let an expired key back in.
  if (license.expires_at && new Date(license.expires_at).getTime() < Date.now()) {
    return json({ valid: false, error: 'This license has expired' }, 403);
  }

  const { data: existingActivation } = await supabase
    .from('activations')
    .select('*')
    .eq('license_id', license.id)
    .eq('domain', domain)
    .maybeSingle();

  if (existingActivation) {
    // Re-activating the same, already-activated domain (e.g. after
    // reinstalling the plugin) is idempotent — it must never burn a
    // second activation slot for what is really the same site.
    await supabase
      .from('activations')
      .update({ last_check_at: new Date().toISOString() })
      .eq('id', existingActivation.id);
  } else {
    const { count } = await supabase
      .from('activations')
      .select('*', { count: 'exact', head: true })
      .eq('license_id', license.id);

    if ((count ?? 0) >= license.max_activations) {
      return json({
        valid: false,
        error: `This license is already active on the maximum of ${license.max_activations} site(s) allowed for this plan.`,
      }, 403);
    }

    const { error: insertError } = await supabase
      .from('activations')
      .insert({ license_id: license.id, domain, last_check_at: new Date().toISOString() });

    if (insertError) {
      return json({ valid: false, error: insertError.message }, 500);
    }
  }

  return json({
    valid: true,
    email: license.email,
    plan: license.plan,
    expires_at: license.expires_at,
  });
}

async function handleValidate(body: Record<string, unknown>) {
  const licenseKey = String(body?.license_key ?? '').trim();
  const domain = normalizeDomain(String(body?.domain ?? ''));

  if (!licenseKey || !domain) {
    return json({ valid: false, error: 'license_key and domain are required' }, 400);
  }

  const { data: license } = await supabase
    .from('licenses')
    .select('*')
    .eq('license_key', licenseKey)
    .maybeSingle();

  if (!license) {
    return json({ valid: false, error: 'License key not found' }, 404);
  }
  if (license.status === 'revoked') {
    return json({ valid: false, error: 'This license has been revoked' }, 403);
  }
  if (license.expires_at && new Date(license.expires_at).getTime() < Date.now()) {
    return json({ valid: false, error: 'This license has expired', expires_at: license.expires_at }, 403);
  }

  const { data: activation } = await supabase
    .from('activations')
    .select('*')
    .eq('license_id', license.id)
    .eq('domain', domain)
    .maybeSingle();

  if (!activation) {
    return json({ valid: false, error: 'This domain is not activated for this license' }, 403);
  }

  await supabase
    .from('activations')
    .update({ last_check_at: new Date().toISOString() })
    .eq('id', activation.id);

  return json({
    valid: true,
    email: license.email,
    plan: license.plan,
    expires_at: license.expires_at,
  });
}

async function handleDeactivate(body: Record<string, unknown>) {
  const licenseKey = String(body?.license_key ?? '').trim();
  const domain = normalizeDomain(String(body?.domain ?? ''));

  if (!licenseKey || !domain) {
    return json({ ok: false, error: 'license_key and domain are required' }, 400);
  }

  const { data: license } = await supabase
    .from('licenses')
    .select('id')
    .eq('license_key', licenseKey)
    .maybeSingle();

  if (!license) {
    return json({ ok: false, error: 'License key not found' }, 404);
  }

  await supabase
    .from('activations')
    .delete()
    .eq('license_id', license.id)
    .eq('domain', domain);

  return json({ ok: true });
}
