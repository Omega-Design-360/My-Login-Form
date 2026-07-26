<?php
/**
 * My Login Form - License Gate
 *
 * Every other admin page and shortcode in this plugin is 100% premium —
 * this is the single checkpoint each of them calls through before doing
 * anything. The license activation card at the top of Settings
 * (Admin/Pages/partials/license-card.php) is the one piece of UI that
 * deliberately never goes through this gate — Settings itself always
 * renders so that card stays reachable on an unlicensed site.
 *
 * @package MyLoginForm\Licensing
 */

namespace MyLoginForm\Licensing;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Gate {

    /**
     * Whether this site currently has a valid, active license.
     *
     * @return bool
     */
    public static function is_active(): bool {
        return License::get_instance()->is_active();
    }

    /**
     * For admin page render callbacks (Admin/Menus.php): runs $render() only
     * when licensed; otherwise prints the "activate your license" screen and
     * never calls $render() at all.
     *
     * @param callable $render
     * @return void
     */
    public static function admin_gate(callable $render): void {
        if (self::is_active()) {
            $render();
            return;
        }
        self::render_gate_screen();
    }

    /**
     * For shortcode callbacks: returns $render()'s output only when
     * licensed; otherwise returns a "license required" notice string —
     * shortcodes must return their markup, never echo it directly.
     *
     * @param callable $render
     * @return string
     */
    public static function shortcode_gate(callable $render): string {
        if (self::is_active()) {
            return $render();
        }
        return self::gate_notice_html();
    }

    /**
     * The full-page "activate your license" screen shown in place of any
     * gated admin page.
     *
     * @return void
     */
    public static function render_gate_screen(): void {
        // License activation now lives as a card at the top of Settings
        // (Admin/Pages/partials/license-card.php), not its own menu page.
        $license_url = admin_url('admin.php?page=my-login-form-settings');
        $buy_url     = defined('MY_LOGIN_FORM_BUY_URL') ? MY_LOGIN_FORM_BUY_URL : '';
        ?>
        <div class="wrap" style="display:flex;justify-content:center;padding:60px 20px;">
            <div style="max-width:480px;text-align:center;background:#fff;border:1px solid #DCE8D6;border-radius:14px;padding:48px 36px;box-shadow:0 4px 18px rgba(0,0,0,0.06);">
                <div style="font-size:42px;color:#B35B00;margin-bottom:18px;">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 style="font-size:20px;margin:0 0 12px;color:#1a1a1a;">
                    <?php _e('This feature requires an active license', 'my-login-form'); ?>
                </h1>
                <p style="color:#444444;font-size:14px;line-height:1.6;margin:0 0 24px;">
                    <?php _e('My Login Form needs a valid license key to unlock its features. Activate your license if you already have a key, or purchase one if you don\'t.', 'my-login-form'); ?>
                </p>
                <a href="<?php echo esc_url($license_url); ?>"
                   style="display:inline-block;background:linear-gradient(135deg,#1FBB00,#0F5900);color:#fff;text-decoration:none;border-radius:8px;padding:12px 28px;font-weight:700;font-size:14px;margin-right:8px;">
                    <?php _e('Activate License', 'my-login-form'); ?>
                </a>
                <?php if ($buy_url): ?>
                <a href="<?php echo esc_url($buy_url); ?>" target="_blank"
                   style="display:inline-block;border:1.5px solid #DCE8D6;color:#2A2A2A;text-decoration:none;border-radius:8px;padding:12px 28px;font-weight:600;font-size:14px;">
                    <?php _e('Buy a License', 'my-login-form'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * The inline notice shortcodes return in place of their real markup
     * when unlicensed.
     *
     * @return string
     */
    public static function gate_notice_html(): string {
        return '<div class="my-login-license-gate-notice" style="padding:18px 20px;background:#FDECEC;border:1px solid #E3B3B3;border-radius:8px;color:#8A0000;font-size:14px;text-align:center;">'
            . esc_html__('This form is disabled until a valid My Login Form license is activated.', 'my-login-form')
            . '</div>';
    }
}
