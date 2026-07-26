<?php
/**
 * My Login Form - License Renewal Nag
 *
 * Most people aren't refusing to renew, they just forgot — so this nags
 * (not blocks) starting 30 days before expiry, getting more prominent as
 * the 14 and 7 day marks pass. A lifetime license or one with no active
 * license at all (Gate.php already covers that case on every page) shows
 * nothing here.
 *
 * @package MyLoginForm\Licensing
 */

namespace MyLoginForm\Licensing;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Notices {

    /**
     * Instance of this class
     *
     * @var Notices|null
     */
    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_notices', [$this, 'maybe_show_renewal_nag']);
    }

    /**
     * @return void
     */
    public function maybe_show_renewal_nag(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $license = License::get_instance();
        if (!$license->is_active()) {
            // No license at all, or already expired — Gate.php's full-page
            // screen already covers that on every gated page; this notice
            // is specifically the "renew soon" nag for a still-valid license.
            return;
        }

        $days = $license->days_until_expiry();
        if ($days === null || $days < 0 || $days > 30) {
            return; // lifetime license, or outside the 30-day nag window
        }

        // License renewal lives as a card at the top of Settings now, not
        // its own menu page.
        $license_url = admin_url('admin.php?page=my-login-form-settings');
        $urgency     = $days <= 7 ? 'notice-error' : 'notice-warning';

        $message = sprintf(
            /* translators: %d: days remaining */
            _n(
                'Your My Login Form license expires in %d day.',
                'Your My Login Form license expires in %d days.',
                max($days, 1),
                'my-login-form'
            ),
            $days
        );

        printf(
            '<div class="notice %1$s"><p>%2$s <a href="%3$s">%4$s</a></p></div>',
            esc_attr($urgency),
            esc_html($message),
            esc_url($license_url),
            esc_html__('Renew now to keep receiving updates and support.', 'my-login-form')
        );
    }
}
