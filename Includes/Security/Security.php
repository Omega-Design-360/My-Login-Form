<?php
/**
 * Security hardening module.
 *
 * @package MyLoginForm\Security
 */

namespace MyLoginForm\Security;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Security {

    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_filter('rest_pre_dispatch', [$this, 'guard_users_endpoint'], 10, 3);
    }

    /**
     * WordPress core's /wp/v2/users endpoint is already safe in its default
     * "view" context (name, avatar, bio, link — nothing sensitive) and core
     * itself deliberately leaves that context open to anonymous requests and
     * to the ?who=authors listing any edit_posts role (Author/Editor/Shop
     * Manager) uses for the Gutenberg "Change author" picker, plus ?_embed
     * author lookups on posts/comments — none of that is touched here.
     *
     * The actual risk is "edit" context: that field set can include email,
     * roles, and whatever extra fields other plugins (WooCommerce, etc.)
     * attach via rest_prepare_user, and core only meant it for accounts that
     * can list_users. This is a hard backstop for that boundary — anyone
     * without list_users requesting context=edit gets nothing but their own
     * record (via /wp/v2/users/me or their own numeric id); every other
     * account is refused outright. Toggle: Settings -> Security -> "Restrict
     * User Data via REST API" (Admin/Pages/settings.php), default on.
     */
    public function guard_users_endpoint($result, $server, $request) {
        if (!get_option('my_login_form_restrict_users_rest_api', 1)) {
            return $result;
        }

        if (!preg_match('#^/wp/v2/users(?:/(\d+|me))?/?$#', $request->get_route(), $matches)) {
            return $result;
        }

        if (current_user_can('list_users')) {
            return $result;
        }

        // Only "edit" context carries anything sensitive — leave every other
        // request (view context, ?who=authors, ?_embed) exactly as core
        // already handles it.
        if ('edit' !== $request->get_param('context')) {
            return $result;
        }

        $current_id = get_current_user_id();

        if (!$current_id) {
            return new \WP_Error(
                'my_login_form_rest_forbidden',
                __('Sorry, you are not allowed to do that.', 'my-login-form'),
                ['status' => 401]
            );
        }

        $id_part = $matches[1] ?? null;

        if ($id_part === null || ($id_part !== 'me' && (int) $id_part !== $current_id)) {
            return new \WP_Error(
                'my_login_form_rest_forbidden',
                __('Sorry, you are not allowed to view this user.', 'my-login-form'),
                ['status' => 403]
            );
        }

        return $result;
    }
}
