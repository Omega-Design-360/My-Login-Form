# Supabase Email Templates (OTP)

Paste-ready templates for **Authentication → Emails → Templates** in your
Supabase dashboard. Each template below has a **Subject** and a **Message
body** — copy each into the matching field for that template.

## Why only two templates, not three

The plugin sends every OTP (registration, login 2FA, forgot password) through
the *same* Supabase endpoint — `POST /auth/v1/otp` (see
`send_supabase_email_otp()` in `Includes/Ajax/AuthAjax.php`). Supabase itself
decides which of **its** templates to fire based only on whether the email
already has an account:

| Plugin flow | Supabase Auth user state | Template Supabase sends |
|---|---|---|
| Register (first OTP for a brand-new account) | doesn't exist yet, `create_user: true` creates it | **Confirm signup** |
| Login (2nd factor after password check passes) | already exists | **Magic Link** |
| Forgot password (proves inbox ownership) | already exists | **Magic Link** |

So "Confirm signup" only ever fires once per user (at registration); every
later code — login or password-reset — goes through "Magic Link". That's why
the Magic Link template's wording below is deliberately generic ("your
verification code") rather than saying "login" specifically — Supabase can't
tell the two apart at send time, and the plugin's own UI already tells the
visitor what they're doing when they land on the code-entry step.

**Do not** leave the default `{{ .ConfirmationURL }}` button in either
template — the plugin never handles Supabase's own confirmation/magic-link
redirect, it only reads the 6-digit code the visitor types into its own
form. A visible `{{ .Token }}` is what actually makes this work; a
link-only template would leave visitors with no code to type in.

**There is no separate "OTP" slot in the Supabase dashboard.** Per
Supabase's own docs, `signInWithOtp` (what `/auth/v1/otp` maps to) always
fires through the template Supabase *labels* "Magic Link" — OTP vs.
magic-link isn't a different template, it's just whether that template's
body shows `{{ .Token }}` (a code) or `{{ .ConfirmationURL }}` (a clickable
link). Template 2 below already does the former — it's fully OTP, not a
magic link — you're just pasting it into the field Supabase happens to
call "Magic Link". There's nowhere else to put it.

---

## 1. Confirm signup

**Supabase field:** Authentication → Emails → Templates → **Confirm signup**
**Covers:** Registration (`handle_register()`)

**Subject:**
```
Confirm your email — your verification code
```

**Message body:**
```html
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>Confirm your email</title>
<style>
  body { margin:0; padding:0; background:#f4f5f7; }
  .wrapper { width:100%; background:#f4f5f7; padding:32px 16px; }
  .card { max-width:480px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; }
  .header { background:#1FBB00; padding:24px 32px; }
  .header h1 { margin:0; font-size:18px; color:#ffffff; font-weight:600; }
  .body { padding:32px; color:#2A2A2A; }
  .body p { margin:0 0 16px; font-size:15px; line-height:1.6; }
  .code-box { display:block; text-align:center; background:#f4f5f7; border:1px solid #DCE8D6; border-radius:10px; padding:20px; margin:24px 0; }
  .code { font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace; font-size:32px; font-weight:700; letter-spacing:8px; color:#1FBB00; }
  .muted { color:#8A8F98; font-size:13px; line-height:1.6; }
  .footer { padding:20px 32px; border-top:1px solid #EEEEEE; }
  @media (prefers-color-scheme: dark) {
    body, .wrapper { background:#121212 !important; }
    .card { background:#1E1E1E !important; }
    .body, .header h1 { color:#F1F1F1 !important; }
    .code-box { background:#262626 !important; border-color:#333333 !important; }
    .muted { color:#9A9A9A !important; }
    .footer { border-top-color:#2A2A2A !important; }
  }
</style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <div class="header">
        <h1>Confirm your email</h1>
      </div>
      <div class="body">
        <p>Welcome! Use the verification code below to finish creating your account.</p>
        <div class="code-box">
          <span class="code">{{ .Token }}</span>
        </div>
        <p class="muted">This code expires shortly and can only be used once. If you didn't request this, you can safely ignore this email.</p>
      </div>
      <div class="footer">
        <p class="muted" style="margin:0;">Sent to {{ .Email }}</p>
      </div>
    </div>
  </div>
</body>
</html>
```

---

## 2. OTP code (pasted into Supabase's "Magic Link" field)

**Supabase field:** Authentication → Emails → Templates → **Magic Link**
(this is the only slot `signInWithOtp` uses — see note above; the body
below is configured for a code, not a link, so it behaves as pure OTP)
**Covers:** Login 2FA (`handle_login()`) and Forgot Password (`handle_forgot_password()`)

**Subject:**
```
Your verification code
```

**Message body:**
```html
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>Your verification code</title>
<style>
  body { margin:0; padding:0; background:#f4f5f7; }
  .wrapper { width:100%; background:#f4f5f7; padding:32px 16px; }
  .card { max-width:480px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; }
  .header { background:#1FBB00; padding:24px 32px; }
  .header h1 { margin:0; font-size:18px; color:#ffffff; font-weight:600; }
  .body { padding:32px; color:#2A2A2A; }
  .body p { margin:0 0 16px; font-size:15px; line-height:1.6; }
  .code-box { display:block; text-align:center; background:#f4f5f7; border:1px solid #DCE8D6; border-radius:10px; padding:20px; margin:24px 0; }
  .code { font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace; font-size:32px; font-weight:700; letter-spacing:8px; color:#1FBB00; }
  .muted { color:#8A8F98; font-size:13px; line-height:1.6; }
  .footer { padding:20px 32px; border-top:1px solid #EEEEEE; }
  @media (prefers-color-scheme: dark) {
    body, .wrapper { background:#121212 !important; }
    .card { background:#1E1E1E !important; }
    .body, .header h1 { color:#F1F1F1 !important; }
    .code-box { background:#262626 !important; border-color:#333333 !important; }
    .muted { color:#9A9A9A !important; }
    .footer { border-top-color:#2A2A2A !important; }
  }
</style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <div class="header">
        <h1>Your verification code</h1>
      </div>
      <div class="body">
        <p>Enter this code to continue — it's needed either to finish signing in or to confirm a password reset, whichever you just requested.</p>
        <div class="code-box">
          <span class="code">{{ .Token }}</span>
        </div>
        <p class="muted">This code expires shortly and can only be used once. If you didn't request this, you can safely ignore this email — your account is still secure.</p>
      </div>
      <div class="footer">
        <p class="muted" style="margin:0;">Sent to {{ .Email }}</p>
      </div>
    </div>
  </div>
</body>
</html>
```

---

## After pasting

- **Code expiry:** Authentication → Providers → Email → "Email OTP
  Expiration" controls how long `{{ .Token }}` stays valid (Supabase default
  is 1 hour). Shorten it if you want codes to expire faster.
- **Rate limits:** Authentication → Rate Limits governs how often
  `/auth/v1/otp` can be called per email/IP — worth checking if you expect
  a lot of "Resend code" clicks.
- **Sender identity:** these templates don't control the *From* address —
  that's set separately under Authentication → Emails → SMTP Settings (or
  Supabase's own default sender if you haven't configured custom SMTP
  there). Unrelated to the Resend SMTP file this plugin uses for
  WordPress/WooCommerce mail (`Includes/Emails/ResendSmtp.php`) — Supabase
  sends its own auth emails independently of that.
