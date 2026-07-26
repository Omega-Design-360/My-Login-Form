# Licensing Backend — Deployment Commands

Run these yourself, in order, from a terminal (PowerShell or Git Bash) —
they need your Supabase login, which has to go through your browser.

**Working directory for all commands below — the plugin root, NOT the
`supabase/` folder itself:**
```
c:\xampp\htdocs\newsite\wp-content\plugins\my-login-form-v2
```

The Supabase CLI always expects `<project-root>/supabase/config.toml` and
`<project-root>/supabase/functions/...` — rather than create a second,
confusingly-nested `supabase/supabase/` folder just for the CLI, the
plugin's own existing `supabase/` folder *is* the CLI project root:

```
my-login-form-v2/                    <- run all commands from here
  supabase/
    config.toml                      <- CLI config
    functions/
      license-api/
        index.ts                     <- the Edge Function code
    supabase.php, SupabaseAjax.php,  <- unrelated plugin PHP files —
    schema.sql, etc.                    harmless to have alongside
    licensing/                       <- this doc, schema.sql, sql/, store-integration/
```

No global install needed — `npx` runs the Supabase CLI on demand (confirmed
working: `npx supabase --version` → 2.109.1).

---

## 1. Log in (opens your browser)

```
npx supabase login
```

## 2. Link this folder to your licensing Supabase project

Find your **project ref** in the Supabase dashboard URL:
`https://supabase.com/dashboard/project/<project-ref>` — or on the project's
Settings → General page ("Reference ID"). Not secret, just an identifier.

```
npx supabase link --project-ref <your-project-ref>
```

## 3. Run the database schema

Paste the two files in `supabase/licensing/sql/` into the Supabase
Dashboard's SQL Editor, in order (`01_licenses.sql` then
`02_activations.sql`) — simplest. Or push via CLI:

```
npx supabase db push
```

(if you go the CLI route, the schema needs to be copied into
`supabase/migrations/` first as a timestamped migration file — the Dashboard
SQL Editor paste is more direct for a one-off schema like this)

## 4. Set the generate secret

Generate your own long random value — **treat it like a password**, it's
what protects the `generate`/`renew` actions from anyone else minting free
licenses. Never commit the real value to a file; this doc intentionally
shows only a placeholder:

```
npx supabase secrets set LICENSE_GENERATE_SECRET=<a long random string>
```

Save that value somewhere safe (password manager) — the WooCommerce store
snippet (`supabase/licensing/store-integration/woocommerce-license-generator.php`)
needs the exact same value defined as `LICENSE_GENERATE_SECRET` on your store site.

## 5. Deploy the Edge Function

```
npx supabase functions deploy license-api --no-verify-jwt
```

`--no-verify-jwt` is required — the plugin has no Supabase Auth session to
present a JWT with.

## 6. Get your function URL

After deploying, the CLI prints the function's URL, or find it in the
Dashboard under **Edge Functions → license-api**. It looks like:

```
https://<project-ref>.supabase.co/functions/v1/license-api
```

## 7. Paste that URL into the plugin

wp-admin → **My Login Form → License → Advanced** → paste the URL → **Save URL**.

Or tell me the URL and I'll verify the connection works from the WordPress side.

---

## Quick sanity test (after deploying, before touching WordPress)

From any terminal with `curl`:

```bash
curl -X POST https://<project-ref>.supabase.co/functions/v1/license-api \
  -H "Content-Type: application/json" \
  -H "x-generate-secret: <the same value you set with `supabase secrets set` above>" \
  -d '{"action":"generate","email":"test@example.com","plan":"3-year"}'
```

Expected response: a JSON object with `license_key`, `email`, `plan`,
`expires_at` (~3 years out). That confirms the whole
chain — schema, function, secret — is working before you ever open
WordPress. Use that returned `license_key` + email to test the Activate
License form.
