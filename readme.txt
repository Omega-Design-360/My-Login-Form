=== My Login Form ===
Contributors: amjadshahzad
Tags: login, registration, form builder, social login, supabase
Requires at least: 5.6
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced login and registration forms with a drag-and-drop form builder, social login, Supabase integration, and WooCommerce compatibility.

== Description ==

My Login Form is a powerful, flexible login and registration plugin for WordPress. Build beautiful, custom login and registration forms using the drag-and-drop Form Designer — no coding required.

= Key Features =

* **Drag-and-Drop Form Designer** — Build fully custom login, registration, forgot-password, welcome, and opt-in forms with a visual editor.
* **Social Login** — Support for Google, Facebook, Twitter/X, GitHub, LinkedIn, Apple, and Microsoft OAuth providers.
* **Supabase Integration** — Sync users with Supabase for extended authentication and real-time features.
* **WooCommerce Compatible** — Automatically sync WooCommerce customers, trigger auto-login, and redirect after checkout.
* **Shortcodes** — Embed any form on any page or post with a simple shortcode. Display user data with user-data shortcodes.
* **Multi-Form Support** — Create and manage unlimited custom forms, each with its own CSS, JavaScript, and HTML output.
* **Form Containers** — Organize fields into main containers and sub-divs, with full control over layout, colors, spacing, and borders.
* **CSS/JS Editor** — Write custom CSS and JavaScript directly inside the form designer. Files are saved per-form.
* **User Data Shortcodes** — Display logged-in user information (first name, last name, email, etc.) anywhere on your site.

= Available Shortcodes =

**Form Shortcode:**
`[my_login_form id="1"]`

**Merge tags (no shortcode needed):**
Type these directly into any paragraph or heading in the page/post editor — they're replaced with the logged-in visitor's data automatically. The name tags (`{name}`, `{first_name}`, `{last_name}`, `{display_name}`, `{username}`, `{nickname}`) fall back to a "Default Guest Name" (configurable in Settings, "Sunshine" out of the box) for logged-out visitors instead of going blank:
`{name}` `{first_name}` `{last_name}` `{display_name}` `{username}` `{email}` `{nickname}` `{website}` `{user_registered}`
Example: `Hey, {first_name}, welcome back!`

**User Data Shortcodes:**
`[my_login_user_data field="first_name"]`
`[my_login_user_data field="last_name"]`
`[my_login_user_data field="email"]`
`[my_login_user_data field="display_name"]`
`[my_login_user_data field="username"]`
`[my_login_greeting logged_in_text="Hey, {username}!" logged_out_text="Please log in"]`
(supported tags: `{name}` `{first_name}` `{last_name}` `{display_name}` `{username}` `{email}` `{nickname}` `{website}` `{user_registered}`)
`[my_login_if_logged_in]Content for logged-in users[/my_login_if_logged_in]`
`[my_login_if_logged_out]Content for logged-out users[/my_login_if_logged_out]`

= External Services =

This plugin optionally connects to the following external services:

**Font Awesome (cdnjs.cloudflare.com)**
Social provider icons in the form designer and on the frontend use Font Awesome icons loaded from the Cloudflare CDN. This service is used to display icons for Google, Facebook, Twitter, GitHub, and other social login buttons.
- Service URL: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/
- Privacy Policy: https://www.cloudflare.com/privacypolicy/
- Terms of Service: https://www.cloudflare.com/website-terms/

**Supabase (optional)**
If you enable the Supabase integration, the plugin communicates with the Supabase API using your project URL and API keys. Supabase is used for extended user authentication and data storage. No data is sent to Supabase unless you explicitly configure and enable the integration.
- Service: https://supabase.com
- Privacy Policy: https://supabase.com/privacy
- Terms of Service: https://supabase.com/terms

== Installation ==

1. Upload the `my-login-form` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress admin **Plugins > Add New** screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **My Login Form > Dashboard** to get started.
4. Use the **Form Designer** to create and customize your login and registration forms.
5. Copy the shortcode shown on the Dashboard and paste it into any page or post.

= Supabase Setup (Optional) =

1. Create a free project at https://supabase.com.
2. Go to **My Login Form > Supabase** in the WordPress admin and enter your Project URL and API keys.
3. Follow the on-screen SQL instructions to create the required database table in your Supabase project.
4. Enable the desired OAuth providers (Google, Facebook, etc.) in your Supabase project's Authentication settings.

== Frequently Asked Questions ==

= How do I embed a login form on a page? =

Go to **My Login Form > Dashboard** and copy the shortcode for the form you want to display (e.g., `[my_login_form id="1"]`). Paste it into any page, post, or widget.

= Can I create multiple forms? =

Yes. Use the **Form Designer** to create as many forms as you need. Each form has its own unique shortcode.

= Does this work with WooCommerce? =

Yes. Enable the WooCommerce integration in **My Login Form > Settings** to sync customers and enable auto-login after checkout.

= Is Supabase required? =

No. Supabase is completely optional. The plugin works as a standard WordPress login/registration plugin without any Supabase configuration.

= How do I display a user's first name on a page? =

Use the shortcode `[my_login_user_data field="first_name"]`. The user must be logged in for this to display anything.

= Are social login buttons supported on the frontend? =

Yes. Add a Social Container to your form in the designer, then configure your OAuth credentials in **My Login Form > Supabase** settings.

= Where is my form CSS saved? =

Each form's CSS is saved as a file in `wp-content/plugins/my-login-form/Public/Forms/css/{form-key}.css`. The CSS is loaded inline on pages where the shortcode is used.

= Is the plugin translation-ready? =

Yes. The plugin is fully internationalized and uses the `my-login-form` text domain. A `.pot` file is included in the `languages/` directory.

== Screenshots ==

1. Dashboard — overview of all forms with shortcodes.
2. Form Designer — drag-and-drop builder for creating custom forms.
3. Layout & Customization panels — live property editor for fields and containers.
4. Supabase settings — OAuth and database configuration.
5. Frontend — example login form rendered on a page.

== Changelog ==

= 1.0.0 =
* Initial release.
* Drag-and-drop Form Designer with main containers, sub-divs, and social sections.
* Support for login, registration, forgot-password, welcome, and opt-in form types.
* Shortcode rendering with inline CSS per form.
* Supabase integration with OAuth provider support.
* WooCommerce compatibility.
* User data shortcodes for displaying profile information.
* Dashboard shortcode reference card.
* Security: nonce verification on all AJAX actions, capability checks, sanitized input, escaped output.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade required.
