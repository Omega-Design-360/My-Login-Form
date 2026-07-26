<?php
/**
 * Mirrors WooCommerce Subscriptions status changes into the
 * my_login_subscriptions table in the site's Supabase project (see
 * supabase/schema.sql), keyed by the WordPress user id already synced there
 * by AuthAjax::sync_to_supabase().
 *
 * Entirely inert unless the separate WooCommerce Subscriptions plugin is
 * active — see the class_exists('WC_Subscriptions') guard in
 * Includes/Core/Core.php that constructs this class. It is not active on
 * this site today, so nothing here runs until it's installed.
 *
 * @package MyLoginForm\Supabase
 */

namespace MyLoginForm\Supabase;

defined('ABSPATH') || exit;

class SupabaseSubscriptions {

    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Fires on every status transition (active, on-hold, cancelled,
        // expired, pending-cancel, ...) and once more on initial creation.
        add_action('woocommerce_subscription_status_updated', [$this, 'sync_subscription'], 10, 1);
        add_action('woocommerce_checkout_subscription_created', [$this, 'sync_subscription'], 10, 1);
    }

    /**
     * @param \WC_Subscription $subscription
     */
    public function sync_subscription($subscription): void {
        if (!($subscription instanceof \WC_Subscription)) {
            return;
        }

        $wp_user_id = (int) $subscription->get_user_id();
        if (!$wp_user_id) {
            return;
        }

        $url     = get_option('my_login_supabase_url', '');
        // service_role, not anon — my_login_subscriptions' RLS (see
        // supabase/sql/02_subscriptions.sql) grants the anon role nothing;
        // only the server-side-only service_role key can write here.
        $service_key = get_option('my_login_supabase_service_key', '');
        $enabled     = get_option('my_login_supabase_enabled', 0);
        if (!$enabled || !$url || !$service_key) {
            return;
        }

        $items      = $subscription->get_items();
        $first_item = $items ? reset($items) : null;
        $product    = $first_item ? $first_item->get_product() : null;

        $payload = [
            'wp_user_id'        => $wp_user_id,
            'subscription_id'   => $subscription->get_id(),
            'product_id'        => $product ? $product->get_id() : null,
            'product_name'      => $product ? $product->get_name() : null,
            'status'            => $subscription->get_status(),
            'start_date'        => $this->to_iso($subscription->get_date_created()),
            'next_payment_date' => $this->to_iso($subscription->get_date('next_payment')),
            'end_date'          => $this->to_iso($subscription->get_date('end')),
            'updated_at'        => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        $host = parse_url($url, PHP_URL_HOST);
        $ssl  = !in_array($host, ['localhost', '127.0.0.1', '::1'], true);

        wp_remote_post(trailingslashit($url) . 'rest/v1/my_login_subscriptions', [
            'headers' => [
                'apikey'        => $service_key,
                'Authorization' => 'Bearer ' . $service_key,
                'Content-Type'  => 'application/json',
                'Prefer'        => 'resolution=merge-duplicates',
            ],
            'body'      => wp_json_encode($payload),
            'timeout'   => 10,
            'sslverify' => $ssl,
        ]);
    }

    /**
     * WC_Subscription date accessors return either a WC_DateTime object
     * (get_date_created()) or a MySQL "Y-m-d H:i:s" string (get_date()) —
     * normalize both to the same ISO-8601 UTC format sync_to_supabase()
     * already uses elsewhere.
     *
     * @param \WC_DateTime|string|null $value
     */
    private function to_iso($value): ?string {
        if (empty($value)) {
            return null;
        }
        if (is_object($value) && method_exists($value, 'getTimestamp')) {
            return gmdate('Y-m-d\TH:i:s\Z', $value->getTimestamp());
        }
        $timestamp = strtotime($value . ' UTC');
        return $timestamp ? gmdate('Y-m-d\TH:i:s\Z', $timestamp) : null;
    }
}
