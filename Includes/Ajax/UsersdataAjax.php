<?php
/**
 * User Data AJAX Handler
 *
 * @package MyLoginForm\Ajax
 */
namespace MyLoginForm\Ajax;

use MyLoginForm\Database\UsersDatabase;
use MyLoginForm\Database\SupabaseDatabase;

defined('ABSPATH') || exit;

class UsersdataAjax {

    private static $instance = null;

    private function __construct() {
        add_action('wp_ajax_my_login_form_get_user',        [$this, 'get_user']);
        add_action('wp_ajax_my_login_form_update_user',      [$this, 'update_user']);
        add_action('wp_ajax_my_login_form_delete_user',      [$this, 'delete_user']);
        add_action('wp_ajax_my_login_form_bulk_delete',      [$this, 'bulk_delete']);
        add_action('wp_ajax_my_login_form_export_csv',       [$this, 'export_csv']);
        add_action('wp_ajax_my_login_form_sync_supabase',    [$this, 'sync_supabase']);
        add_action('wp_ajax_my_login_form_create_wp_users',  [$this, 'create_wp_users']);
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function guard(): void {
        check_ajax_referer('my_login_form_dashboard_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'my-login-form'), 403);
        }
    }

    /**
     * Return details for a single user, from either the plugin's
     * custom users table or native WordPress users.
     */
    public function get_user(): void {
        $this->guard();

        $user_id = intval($_POST['user_id'] ?? 0);
        $source  = sanitize_key($_POST['source'] ?? 'plugin');

        if (!$user_id) {
            wp_send_json_error(__('Invalid user ID.', 'my-login-form'));
        }

        if ($source === 'wp') {
            $wp_user = get_userdata($user_id);
            if (!$wp_user) {
                wp_send_json_error(__('User not found.', 'my-login-form'));
            }

            wp_send_json_success([
                'first_name'      => get_user_meta($user_id, 'first_name', true),
                'last_name'       => get_user_meta($user_id, 'last_name', true),
                'email'           => $wp_user->user_email,
                'phone'           => get_user_meta($user_id, 'billing_phone', true) ?: get_user_meta($user_id, 'phone', true),
                'gender'          => get_user_meta($user_id, 'gender', true),
                'dob'             => get_user_meta($user_id, 'dob', true),
                'country'         => get_user_meta($user_id, 'billing_country', true),
                'city'            => get_user_meta($user_id, 'billing_city', true),
                'address'         => get_user_meta($user_id, 'billing_address_1', true),
                'created_at'      => $wp_user->user_registered,
                'updated_at'      => $wp_user->user_registered,
                'last_login'      => get_user_meta($user_id, 'mlf_last_login', true),
                'login_count'     => (int) get_user_meta($user_id, 'mlf_login_count', true),
                'email_verified'  => true,
                'phone_verified'  => false,
                'wp_user_id'      => $user_id,
                'supabase_uid'    => '',
                'social_provider' => get_user_meta($user_id, 'mlf_social_provider', true),
                'social_id'       => '',
                'profile_picture' => get_avatar_url($user_id),
            ]);
        }

        $users_db = UsersDatabase::get_instance();
        $user     = $users_db->get_user($user_id);

        if (!$user) {
            wp_send_json_error(__('User not found.', 'my-login-form'));
        }

        wp_send_json_success([
            'first_name'      => $user->user_first_name,
            'last_name'       => $user->user_last_name,
            'email'           => $user->user_email,
            'phone'           => $user->user_phone,
            'gender'          => $user->user_gender,
            'dob'             => $user->user_dob,
            'country'         => $user->user_country,
            'city'            => $user->user_city,
            'address'         => $user->user_address,
            'created_at'      => $user->created_at,
            'updated_at'      => $user->updated_at,
            'last_login'      => $user->last_login,
            'login_count'     => (int) $user->login_count,
            'email_verified'  => (bool) $user->email_verified,
            'phone_verified'  => (bool) $user->phone_verified,
            'wp_user_id'      => $user->wp_user_id,
            'supabase_uid'    => $user->supabase_user_id,
            'social_provider' => $user->social_provider,
            'social_id'       => $user->social_id,
            'profile_picture' => $user->profile_picture,
        ]);
    }

    /**
     * Update basic profile fields for a user, plugin table or native WP.
     */
    public function update_user(): void {
        $this->guard();

        $user_id = intval($_POST['user_id'] ?? 0);
        $source  = sanitize_key($_POST['source'] ?? 'plugin');

        if (!$user_id) {
            wp_send_json_error(__('Invalid user ID.', 'my-login-form'));
        }

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
        $email      = sanitize_email($_POST['email'] ?? '');
        $phone      = sanitize_text_field($_POST['phone'] ?? '');

        if (!is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'my-login-form'));
        }

        if ($source === 'wp') {
            if (!get_userdata($user_id)) {
                wp_send_json_error(__('User not found.', 'my-login-form'));
            }

            $result = wp_update_user([
                'ID'         => $user_id,
                'user_email' => $email,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            ]);

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }

            update_user_meta($user_id, 'phone', $phone);
        } else {
            $users_db = UsersDatabase::get_instance();

            if (!$users_db->get_user($user_id)) {
                wp_send_json_error(__('User not found.', 'my-login-form'));
            }

            $result = $users_db->update_user($user_id, [
                'user_first_name' => $first_name,
                'user_last_name'  => $last_name,
                'user_email'      => $email,
                'user_phone'      => $phone,
            ]);

            if (!$result) {
                wp_send_json_error(__('Failed to update user.', 'my-login-form'));
            }
        }

        wp_send_json_success(['message' => __('User updated successfully.', 'my-login-form')]);
    }

    /**
     * Delete a user from the plugin's custom table, or a native WP user.
     */
    public function delete_user(): void {
        $this->guard();

        $user_id = intval($_POST['user_id'] ?? 0);
        $source  = sanitize_key($_POST['source'] ?? 'plugin');

        if (!$user_id) {
            wp_send_json_error(__('Invalid user ID.', 'my-login-form'));
        }

        if ($source === 'wp') {
            if ($user_id === get_current_user_id()) {
                wp_send_json_error(__('You cannot delete your own account.', 'my-login-form'));
            }
            require_once ABSPATH . 'wp-admin/includes/user.php';
            $result = wp_delete_user($user_id);
        } else {
            $users_db = UsersDatabase::get_instance();
            $result   = $users_db->delete_user($user_id, false);
        }

        if (!$result) {
            wp_send_json_error(__('Failed to delete user.', 'my-login-form'));
        }

        wp_send_json_success(['message' => __('User deleted successfully.', 'my-login-form')]);
    }

    /**
     * Delete multiple users at once (Bulk Actions on the Users Data page).
     * All selected rows come from the same table (plugin or native WP), so
     * a single $source applies to the whole batch.
     */
    public function bulk_delete(): void {
        $this->guard();

        $user_ids = array_filter(array_map('intval', (array) ($_POST['user_ids'] ?? [])));
        $source   = sanitize_key($_POST['source'] ?? 'plugin');

        if (empty($user_ids)) {
            wp_send_json_error(__('No users selected.', 'my-login-form'));
        }

        $deleted = 0;
        $failed  = 0;

        foreach ($user_ids as $user_id) {
            if ($source === 'wp') {
                if ($user_id === get_current_user_id()) {
                    $failed++;
                    continue;
                }
                require_once ABSPATH . 'wp-admin/includes/user.php';
                $result = wp_delete_user($user_id);
            } else {
                $users_db = UsersDatabase::get_instance();
                $result   = $users_db->delete_user($user_id, false);
            }

            if ($result) {
                $deleted++;
            } else {
                $failed++;
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                /* translators: 1: number deleted, 2: number failed */
                __('%1$d user(s) deleted, %2$d failed.', 'my-login-form'),
                $deleted,
                $failed
            ),
        ]);
    }

    /**
     * Stream a CSV export of all users. Falls back to native WP users
     * when the plugin's custom table has no rows.
     */
    public function export_csv(): void {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'my_login_form_dashboard_nonce')) {
            wp_die(esc_html__('Security check failed.', 'my-login-form'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied.', 'my-login-form'));
        }

        $users_db = UsersDatabase::get_instance();
        $csv      = $users_db->export_users_to_csv();

        if (empty($csv)) {
            $csv = $this->export_wp_users_to_csv();
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="my-login-form-users-' . gmdate('Y-m-d') . '.csv"');
        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private function export_wp_users_to_csv(): string {
        $fields = ['id', 'user_login', 'user_email', 'first_name', 'last_name', 'user_registered'];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $fields);

        foreach (get_users() as $wp_user) {
            fputcsv($output, [
                $wp_user->ID,
                $wp_user->user_login,
                $wp_user->user_email,
                get_user_meta($wp_user->ID, 'first_name', true),
                get_user_meta($wp_user->ID, 'last_name', true),
                $wp_user->user_registered,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Bulk-sync users that have not yet been linked to a Supabase record.
     */
    public function sync_supabase(): void {
        $this->guard();

        if (!get_option('my_login_supabase_enabled')) {
            wp_send_json_error(__('Supabase integration is not enabled.', 'my-login-form'));
        }

        if (!class_exists(SupabaseDatabase::class)) {
            wp_send_json_error(__('Supabase integration is unavailable.', 'my-login-form'));
        }

        $users_db     = UsersDatabase::get_instance();
        $supabase_db  = SupabaseDatabase::get_instance();
        $pending      = $users_db->get_users(['limit' => 100]);

        $synced = 0;
        $failed = 0;

        foreach ($pending as $user) {
            if (!empty($user->supabase_user_id)) {
                continue;
            }

            $result = $supabase_db->sync_user_to_supabase($user->id, (array) $user);

            if (!empty($result['success'])) {
                $synced++;
            } else {
                $failed++;
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                /* translators: 1: number synced, 2: number failed */
                __('%1$d user(s) synced, %2$d failed.', 'my-login-form'),
                $synced,
                $failed
            ),
        ]);
    }

    /**
     * Bulk-create (or link) WordPress accounts for plugin users that don't have one yet.
     */
    public function create_wp_users(): void {
        $this->guard();

        $users_db   = UsersDatabase::get_instance();
        $candidates = $users_db->get_users(['limit' => 200]);

        $created = 0;
        $skipped = 0;

        foreach ($candidates as $user) {
            if (!empty($user->wp_user_id)) {
                $skipped++;
                continue;
            }

            $existing = get_user_by('email', $user->user_email);
            if ($existing) {
                $users_db->link_wordpress_user($user->id, $existing->ID);
                $created++;
                continue;
            }

            $login = $user->user_login ?: sanitize_user(current(explode('@', $user->user_email)), true);
            $wp_user_id = wp_create_user($login, wp_generate_password(16, true), $user->user_email);

            if (is_wp_error($wp_user_id)) {
                $skipped++;
                continue;
            }

            $users_db->link_wordpress_user($user->id, $wp_user_id);

            if (!empty($user->user_first_name)) {
                update_user_meta($wp_user_id, 'first_name', $user->user_first_name);
            }
            if (!empty($user->user_last_name)) {
                update_user_meta($wp_user_id, 'last_name', $user->user_last_name);
            }

            $created++;
        }

        wp_send_json_success([
            'message' => sprintf(
                /* translators: 1: number created/linked, 2: number skipped */
                __('%1$d WordPress account(s) created/linked, %2$d skipped.', 'my-login-form'),
                $created,
                $skipped
            ),
        ]);
    }
}
