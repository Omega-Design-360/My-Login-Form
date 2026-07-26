# My Login Form — Licensing System

Three pieces, each with one job:

- **`sql/01_licenses.sql` + `sql/02_activations.sql`** — the two Supabase tables (`licenses`, `activations`). Paste into your Supabase project's SQL Editor once, in that order.
- **The Edge Function** — the only thing allowed to read/write those tables. Its actual deployable source lives at `../functions/license-api/index.ts` (i.e. `supabase/functions/license-api/index.ts`, one level up from this `licensing/` folder) — the plugin's own `supabase/` folder doubles as the Supabase CLI project root, so there's no separate nested `supabase/supabase/` folder just for this.
- **The plugin** (`Includes/Licensing/License.php` and related files) — installed on customer sites. Knows nothing except the Edge Function's URL; asks it questions and believes the answers.

## 1. Deploy the database schema

Supabase Dashboard → your project → **SQL Editor** → **New Query**. Paste and
run `sql/01_licenses.sql` first, then a fresh query with `sql/02_activations.sql`
— the order matters, since `activations` has a foreign key into `licenses`.

## 2. Deploy the Edge Function

The CLI project scaffold already exists at the plugin root — see
`DEPLOY_COMMANDS.md` in this same folder for the exact commands (they're run
from the **plugin root**, not from inside `licensing/`, since that's where
the CLI expects to find `supabase/config.toml`):

```bash
cd path/to/my-login-form-v2
npx supabase login
npx supabase link --project-ref <your-project-ref>
npx supabase secrets set LICENSE_GENERATE_SECRET=<a long random string — save it, your store needs it too>
npx supabase functions deploy license-api --no-verify-jwt
```

`--no-verify-jwt` is required — the plugin has no Supabase Auth session to present a JWT with. `SUPABASE_URL` and `SUPABASE_SERVICE_ROLE_KEY` are injected automatically by Supabase; don't set those yourself.

After deploying, your function URL is:

```
https://<project-ref>.supabase.co/functions/v1/license-api
```

That URL is the **only** secret the plugin itself needs to know. Not the service role key, not the anon key, not `LICENSE_GENERATE_SECRET` — just this URL. Set it as `MY_LOGIN_FORM_LICENSE_API_URL` (see `Includes/Licensing/License.php`).

## 3. Wire up your store

`store-integration/woocommerce-license-generator.php` in this folder is a **separate, small snippet meant for your WooCommerce store site — not the customer's site**. Do not bundle it into the plugin ZIP you sell. See the comments at the top of that file for setup (it needs `MY_LOGIN_FORM_LICENSE_API_URL` and `LICENSE_GENERATE_SECRET` as constants/env on the store site only).

## Actions the Edge Function exposes

| action       | caller           | protected by                          |
|--------------|------------------|----------------------------------------|
| `generate`   | store only       | `x-generate-secret` header             |
| `renew`      | store only       | `x-generate-secret` header             |
| `activate`   | plugin           | scoped to a specific `license_key`     |
| `validate`   | plugin (daily)   | scoped to a specific `license_key`     |
| `deactivate` | plugin           | scoped to a specific `license_key`     |

## Design notes worth remembering

- **Expiry is a timestamp comparison, never a status flag.** `status` on the `licenses` row is informational only — every check compares `expires_at` to `now()` directly, so a stale column can never let an expired key back in.
- **The clock starts at purchase, not at activation.** If it started at activation, an unactivated key sitting in someone's inbox for eight months would quietly grant eight free months.
- **Renewals extend from `max(current expiry, now)`.** Early renewal carries over remaining time (fair). Late renewal doesn't backdate to the old expiry (no free gap).
- **A failed validation call is a shrug, not a verdict.** The plugin's daily re-check uses a grace period (default 7 days of failed contact) before treating a license as invalid — a customer's host blocking outbound requests, or a bad five minutes on Supabase's end, must never brick a paying customer's site.
- **Domain normalization happens on both ends** (plugin and Edge Function) — strip protocol, `www.`, trailing slash, lowercase — so `https://www.site.com/` and `site.com` are always recognized as the same activation.
