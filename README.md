# My Login Form

**Advanced login, registration, and account management for WordPress** — a drag‑and‑drop form designer, social/OAuth login via Supabase, WooCommerce integration, email OTP verification, and a full user‑management dashboard, all in one plugin.

| | |
|---|---|
| **Version** | 1.0.0 |
| **Requires WordPress** | 5.6+ |
| **Requires PHP** | 7.4+ |
| **Tested up to** | WordPress 6.5 / WooCommerce 8.0 |
| **License** | GPLv2 or later |
| **Text Domain** | `my-login-form` |
| **Author** | Amjad Shahzad |

---

## Table of contents

1. [Who is this plugin for?](#who-is-this-plugin-for)
2. [Key features](#key-features)
3. [How the plugin is organized](#how-the-plugin-is-organized)
4. [Installation](#installation)
5. [Getting started](#getting-started)
6. [The Form Designer](#the-form-designer)
7. [Shortcodes reference](#shortcodes-reference)
8. [Admin dashboard pages](#admin-dashboard-pages)
9. [Settings reference](#settings-reference)
10. [Supabase integration (social login & OTP)](#supabase-integration-social-login--otp)
11. [WooCommerce integration](#woocommerce-integration)
12. [Email delivery (Resend)](#email-delivery-resend)
13. [Licensing](#licensing)
14. [Security notes](#security-notes)
15. [Database tables created](#database-tables-created)
16. [Known limitations / incomplete features](#known-limitations--incomplete-features)
17. [FAQ](#faq)
18. [Support](#support)

---

## Who is this plugin for?

- **WordPress site owners / agencies** who want a fully custom‑branded login, registration, forgot‑password, and reset‑password experience without hand‑coding templates or fighting `wp-login.php`.
- **WooCommerce store owners** who want customers to register/log in through a branded form (instead of the default WooCommerce My Account UI), with billing fields captured at registration and auto‑login after checkout.
- **Membership / community sites** that need social login (Google, Facebook, X/Twitter, GitHub, LinkedIn, Apple, Microsoft), email OTP verification, and a searchable, exportable list of registered users.
- **Developers/freelancers building client sites** who need a form builder they can hand off to non‑technical editors — forms are configured visually and dropped in with a single shortcode.
- **Anyone who wants to keep using core WordPress accounts** — the plugin works standalone (no external services required) and only turns on Supabase/social login/OTP/WooCommerce features if you explicitly configure them.

This is a **commercial, license‑gated plugin** (see [Licensing](#licensing)): all premium features (Dashboard, Form Designer, Users Data, Integration Hub, and every shortcode except the plain merge tags) require an active license key. The **Settings** page — including license activation — is always reachable, even before you activate.

---

## Key features

- **Drag‑and‑drop Form Designer** — build Login, Registration, Forgot Password, Reset Password, Welcome, Opt‑in, and fully custom forms visually. Compose forms from Main Containers, nestable Sub Containers, a Social login section, and a Greeting/text block.
- **14 field types** — text, email, password, number, phone, URL, date, dropdown, checkbox, radio, textarea, file upload, hidden, captcha — plus name/username/confirm‑password conveniences, and (when WooCommerce is active) billing address fields.
- **Full visual styling** — per‑element layout (width, padding, margin, border‑radius, display mode) and appearance (colors, borders, shadows, hover states) for containers, fields, and the submit button — no CSS required, though a built‑in CSS/JS code editor (CodeMirror) is available per form for advanced customization.
- **Social / OAuth login** — Google, Facebook, Twitter/X, GitHub, LinkedIn, Apple, and Microsoft, powered by your own Supabase project's Authentication providers.
- **Email OTP verification (2FA‑style)** — 6‑digit email codes for registration, login, and password‑reset confirmation, delegated to Supabase Auth, with a full OTP audit log in the admin.
- **WooCommerce integration** — redirects logged‑out visitors from the WooCommerce My Account page to your custom login form, replaces the WooCommerce checkout login prompt, auto‑fills WooCommerce billing meta on registration, optional auto‑login after registration, and optional customer data sync — declared compatible with HPOS and Cart/Checkout Blocks.
- **User Data dashboard** — searchable/paginated user list (works against core WordPress users, or an imported/synced data set), CSV export, bulk Supabase sync, bulk WordPress‑account creation from imported records, per‑user detail and delete.
- **Merge tags & conditional content** — drop `{first_name}`, `{email}`, etc. straight into page/post content (with a configurable guest fallback name), and wrap content in `[my_login_if_logged_in]` / `[my_login_if_logged_out]` blocks.
- **Resend‑powered transactional email** — optional integration with [Resend](https://resend.com) via HTTP API or SMTP relay, so password‑reset and account emails land reliably.
- **Security by default** — nonce verification on every AJAX action, capability checks, sanitized input / escaped output, anti‑enumeration error messages, configurable IP/user blocklists, login attempt lockouts, and reCAPTCHA support.
- **Translation‑ready** — full `my-login-form` text domain, `.pot` file included under `languages/`.

---

## How the plugin is organized

```
my-login-form/
├── my-login-form.php          # Bootstrap: constants, autoloader, hooks, HPOS compatibility
├── Admin/                     # Admin UI: menus, dashboard, settings, form designer, assets
├── Includes/
│   ├── Ajax/                  # AJAX handlers (auth, designer, dashboard, users, settings, licensing, onboarding)
│   ├── Core/                  # Autoloader + hook registration
│   ├── Database/              # Custom table schemas (forms, users, logs, OTP, admin sessions)
│   ├── Emails/                # wp_mail overrides / Resend integration
│   ├── Integrations/          # WooCommerce integration
│   ├── Licensing/             # License gate, activation client, renewal notices, update checker
│   ├── Shortcodes/            # Public-facing shortcode + merge-tag rendering
│   └── Lifecycle/              # Activation / deactivation / uninstall routines
├── Public/Forms/               # Generated per-form CSS/JS/HTML assets + renderer
├── supabase/                   # Supabase "Integration Hub" admin page, REST client, OTP/social login backend
└── languages/                  # Translation files (.pot)
```

---

## Installation

1. Copy the `my-login-form` folder into `/wp-content/plugins/`, or upload the plugin zip via **Plugins → Add New → Upload Plugin**.
2. Activate **My Login Form** from the **Plugins** screen. Activation automatically creates dedicated Login, Register, and My Account pages and sets sensible default options.
3. Go to **My Login Form → Settings** and activate your license (email + license key). Every feature besides Settings is gated behind an active license.
4. Go to **My Login Form → Dashboard** for an overview, or straight to **Form Designer** to build your first form.
5. Copy the shortcode for a form (shown on the Dashboard and inside the Designer) and paste it into any page, post, or widget.

---

## Getting started

1. **Build a form** — open **Form Designer**, choose a form type (Login / Register / Forgot Password / Reset Password / Welcome / Opt‑in / Custom), drag in containers and fields, and style them in the Layout/Customization side panel.
2. **Set redirects** — configure "Redirect after login" and "Redirect after registration" per form (home page, profile page, or a custom URL).
3. **Publish it** — save the form, copy its shortcode (e.g. `[my_login_form id="1"]`), and paste it onto a page.
4. **(Optional) Turn on social login and OTP** — connect a Supabase project under **Social Login Setup**, enter your Project URL and API keys, run the provided SQL snippet in your Supabase project, then toggle on the social providers you've enabled in Supabase's own Authentication settings.
5. **(Optional) Connect WooCommerce** — enable the WooCommerce toggle in Settings to redirect account‑page visitors to your custom login form and sync billing fields at registration.
6. **Review users** — check **Users Data** for registration stats, sync status, and CSV export.

---

## The Form Designer

Located at **My Login Form → Form Designer**.

**Structural elements** (drag from the palette): Main Container, Sub Div (nestable inside a Main Container), Social Section (renders the enabled OAuth buttons), Greeting (title/subtitle text block).

**Field types**: Text, First Name, Last Name, Email, Password, Confirm Password, Username, Number, Date, Dropdown (Select), Checkbox, Radio, Textarea, Phone — plus, once WooCommerce is active, Billing First/Last Name, Company, Address, City, Postcode, Email, and Phone.

**Per‑element settings**:
- *Layout*: width, padding, margin, border‑radius, display mode (block/grid/flex depending on element).
- *Customization*: background color, border color/width, box‑shadow, text/label color, hover states, button colors.
- *Global*: primary button color and text, whether field labels are shown.

**Per‑form settings**: redirect after login, redirect after registration (home / profile / login / custom URL), which social providers to show, and a code editor tab for form‑specific CSS/JS.

Each saved form generates its own `Public/Forms/css/{form_key}.css`, `Public/Forms/js/{form_key}.js`, and `Public/Forms/html/{form_key}.html` — assets are only loaded on pages where that form's shortcode actually appears.

---

## Shortcodes reference

| Shortcode | Description |
|---|---|
| `[my_login_form id="1"]` | Renders a form built in the Designer. Accepts `id`, `key`, or `type` (`login`, `register`, `forgot_password`, `reset_password`, etc.) to select which form to display. |
| `[my_login_user_data field="first_name"]` | Prints one field of the logged‑in user's profile. Valid `field` values: `first_name`, `last_name`, `display_name`, `username`, `email`, `nickname`, `description`, `website`, `user_registered`. Optional `default="…"` and `format="ucfirst\|upper\|lower"`. |
| `[my_login_greeting logged_in_text="Hey, {username}!" logged_out_text="Please log in"]` | Prints one message for logged‑in visitors and another for logged‑out visitors, with merge‑tag support in the logged‑in text. |
| `[my_login_if_logged_in]…[/my_login_if_logged_in]` | Only renders the wrapped content to logged‑in visitors. |
| `[my_login_if_logged_out]…[/my_login_if_logged_out]` | Only renders the wrapped content to logged‑out visitors. |
| `[my_login_profile]` | Renders a compact profile card for the logged‑in user (avatar, name, email, member‑since, edit‑profile/logout links). |
| `[my_login_social_share url="" title=""]` | Renders social‑share buttons for the current (or a given) page — configured from the Social Media tab of the Integration Hub. |

**Merge tags** — type these directly into page/post content, no shortcode needed: `{name}` `{first_name}` `{last_name}` `{display_name}` `{username}` `{email}` `{nickname}` `{website}` `{user_registered}`. For logged‑out visitors, the name‑shaped tags fall back to a configurable "Default Guest Name" (Settings → General, default `Sunshine`) instead of printing nothing.

Example: `Hey, {first_name}, welcome back!`

---

## Admin dashboard pages

All pages live under the **My Login Form** top‑level menu (`manage_options` capability required) and, aside from Settings, require an active license:

| Page | What it's for |
|---|---|
| **Dashboard** | At‑a‑glance stats: total forms, total users, recent registrations, a registration‑over‑time chart, and quick shortcode references for each of your forms. |
| **Form Designer** | The visual drag‑and‑drop form builder described above. |
| **Users Data** | Searchable, paginated user list with sync status, export, and management actions (see below). |
| **Social Login Setup** *(Integration Hub)* | Supabase connection wizard, per‑provider social login toggles, social‑sharing settings, analytics, the WooCommerce tab, and the OTP audit log. |
| **Settings** | All plugin configuration plus the license‑activation card — always reachable regardless of license status. |

### Users Data page

- Lists real WordPress users, or — once you've imported/synced records via Supabase or CSV — the plugin's own user table, whichever has data.
- Stats shown: total users, synced‑to‑Supabase count, registrations today, social‑login signups, WordPress‑linked accounts, pending sync, and sync rate.
- Shows WooCommerce order count/spend per user when WooCommerce is active.
- Actions: view full user detail, delete a user (self‑deletion is blocked), export all users to CSV, bulk‑sync unsynced users to Supabase, bulk‑create/link WordPress accounts for imported records.

---

## Settings reference

**My Login Form → Settings**, organized into tabs:

- **General** — default redirect after login/registration, WooCommerce integration toggle, allow‑registration toggle, default role for new self‑registered users (locked to Customer/Subscriber — never an elevated role), allowed email domains, default guest name for merge tags.
- **Security** — reCAPTCHA site/secret keys, email‑verification (OTP) toggle, session timeout, max login attempts and lockout duration, IP blocklist and user blocklist (one per line).
- **Email** — welcome‑email toggle, admin new‑registration notification toggle, Resend API key and From name/email (HTTP‑API integration — see below).
- **Custom Code** — site‑wide custom CSS and JavaScript.
- **Advanced** — delete‑data‑on‑uninstall toggle, a System Information panel (versions, active integrations, form/user counts), and Quick Actions (clear cache, reset settings, export/import settings as JSON).

Secret values (API keys, reCAPTCHA secret) are never redisplayed in full — only a masked hint — and are stored with `autoload` disabled so they don't load into memory on every page request.

---

## Supabase integration (social login & OTP)

Supabase is **entirely optional** — the plugin works as a standard WordPress login/registration tool without it. Connecting a [Supabase](https://supabase.com) project (free tier is sufficient) unlocks:

- **Social/OAuth login** — Google, Facebook, Twitter/X, GitHub, LinkedIn, Apple, Microsoft. The plugin renders the buttons and hands off to Supabase Auth's hosted OAuth flow; you enable each provider inside your own Supabase project's Authentication → Providers settings.
- **Email OTP verification** — 6‑digit codes sent via Supabase Auth for registration confirmation, login 2FA, and password‑reset identity confirmation. Every send/verify attempt is logged in the OTP Log tab. If Supabase isn't configured, OTP is silently skipped and the plugin falls back to core WordPress email flows.
- **One‑way user sync** — profile data (never passwords) is pushed to a `public.my_login_users` table in your Supabase project via the service‑role key, for use in your own external tooling/analytics.

**Setup:**
1. Create a free project at [supabase.com](https://supabase.com).
2. Go to **My Login Form → Social Login Setup**, and enter your Project URL, anon key, and service role key.
3. Run the SQL snippet shown on the Setup tab in your Supabase project's SQL editor to create the `my_login_users` table and its RLS policies.
4. Enable your desired OAuth providers inside Supabase's own Authentication settings, then toggle the matching providers on in the Social Media tab.

---

## WooCommerce integration

Enable under **Settings → General → WooCommerce Integration**. Once on:

- Logged‑out visitors hitting the WooCommerce **My Account** page are redirected to your custom login form instead (preserving the return URL).
- The inline login/register toggle on the WooCommerce checkout page is replaced with a link to your custom login form.
- `wp_login_url()` / `wp_loginout()` throughout the site point at your custom login page instead of `wp-login.php`.
- New registrations populate WooCommerce billing meta (name, email) so accounts look native to WooCommerce from the start; the Designer's field palette gains WooCommerce billing‑address fields.
- Optional: auto‑login immediately after registration, and one‑way customer data sync.
- Declares compatibility with WooCommerce **HPOS** (custom order tables) and **Cart/Checkout Blocks**.

---

## Email delivery (Resend)

Two independent, optional ways to route outgoing WordPress email through [Resend](https://resend.com) (only configure one — if both are active, the HTTP API integration wins):

1. **HTTP API** — set a Resend API key in **Settings → Email**. All `wp_mail()` calls are sent via Resend's HTTP API, with attachments and headers (From/CC/BCC/Reply‑To) forwarded. Falls back to normal WordPress mail on any failure.
2. **SMTP relay** — define `OMEGA_RESEND_API_KEY` in `wp-config.php` to route all mail through Resend's SMTP relay instead (useful if you want it applied even before the plugin's own settings are configured). Optionally also define `MY_LOGIN_FORM_RESEND_FROM_EMAIL` / `MY_LOGIN_FORM_RESEND_FROM_NAME` to control the From header.

Without either configured, the plugin sends plain WordPress email as normal (password‑reset links, etc.).

---

## Licensing

My Login Form is license‑gated: every premium feature (Dashboard, Form Designer, Users Data, Integration Hub, and all shortcodes except plain merge tags) checks for an active license before rendering.

- **Activate** your license under **Settings** (this page is always reachable, licensed or not) using the email and key from your purchase.
- License status is re‑validated daily in the background. A brief server outage doesn't lock you out — there's a 7‑day grace period before an unreachable license server is treated as invalid.
- A renewal reminder appears in `wp-admin` starting 30 days before expiry.
- Without an active license, gated admin pages show an "Activate License / Buy a License" screen, and gated shortcodes render a short notice instead of the form. The plugin never breaks your site outright — Settings, activation, and deactivation always work.
- An active license is also required to receive plugin updates.

Need a license? See the **Buy a License** link on the gate screen or Settings page.

---

## Security notes

- Nonce verification on every AJAX action; capability checks (`manage_options` / custom capabilities) on every admin action.
- All input sanitized, all output escaped.
- Login error messages are generic to avoid username/email enumeration; OTP resend responds identically whether or not an account exists.
- Self‑registration can never be assigned an elevated role — hardcoded to Customer/Subscriber regardless of misconfiguration.
- Configurable IP and user blocklists, login‑attempt lockouts, and optional reCAPTCHA.
- Secrets (license keys, API keys, Supabase service‑role key) are stored with `autoload` disabled and are never redisplayed in full in the admin UI.

---

## Database tables created

| Table | Purpose |
|---|---|
| `wp_my_login_forms` | Every Designer‑built form: settings, field/container structure, generated asset paths, redirects. |
| `wp_my_login_users_data` | Imported/synced user records (native WordPress registrations stay in `wp_users`). |
| `wp_my_login_users_sessions` | Session tracking for plugin‑table users. |
| `wp_my_login_users_logs` | User activity logs. |
| `wp_my_login_logs` | General plugin logging. |
| `wp_my_login_admin_sessions` | Admin login session tracking. |
| `wp_my_login_admin_activity` | Admin audit log. |
| `wp_my_login_otp_log` | OTP send/verify audit trail (auto‑purged after 30 days). |
| `wp_my_login_supabase_settings` | Local cache of your Supabase connection settings. |
| `wp_my_login_social_analytics` | Click tracking for the social‑share shortcode. |

Uninstalling the plugin only removes these tables and options if you've explicitly enabled "Delete data on uninstall" in **Settings → Advanced**.

---

## Known limitations / incomplete features

In the interest of accurate documentation:

- **Firebase** is referenced in a couple of internal labels and notices, but there is currently no working admin UI or AJAX path for it — treat it as not yet available. Supabase is the supported OAuth/verification backend.
- A standalone Form Designer prototype file exists in the codebase but isn't linked from any menu — it has no effect on the plugin you interact with in `wp-admin`.

---

## FAQ

**How do I embed a login form on a page?**
Go to **My Login Form → Dashboard**, copy the shortcode for the form you want (e.g. `[my_login_form id="1"]`), and paste it into any page, post, or widget.

**Can I create multiple forms?**
Yes — build as many as you need in the Form Designer; each gets its own shortcode and assets.

**Does this work with WooCommerce?**
Yes — enable WooCommerce integration in Settings to redirect account‑page visitors to your custom login form and sync billing fields.

**Is Supabase required?**
No. The plugin is a complete standalone WordPress login/registration solution without it. Supabase only adds social login and email OTP.

**How do I show a user's first name on a page?**
`[my_login_user_data field="first_name"]`, or the merge tag `{first_name}` directly in your content. The visitor must be logged in.

**Where is my form's CSS saved?**
`wp-content/plugins/my-login-form/Public/Forms/css/{form-key}.css`, loaded automatically only on pages using that form's shortcode.

**Is the plugin translation‑ready?**
Yes — fully internationalized under the `my-login-form` text domain, with a `.pot` file in `languages/`.

---

## Support

For license, billing, or support questions, use the links on the **Settings** page or the license gate screen inside `wp-admin`.
