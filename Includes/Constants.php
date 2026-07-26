<?php
namespace MyLoginForm;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Constants {


    private static $instance = null;
    private function __construct() {}
    
    // Plugin capabilities
    const CAP_MANAGE_SETTINGS = 'manage_my_login_form_settings';
    const CAP_MANAGE_FORMS = 'manage_my_login_form_forms';
    const CAP_MANAGE_USERS = 'manage_my_login_form_users';
    const CAP_VIEW_LOGS = 'view_my_login_form_logs';
    const CAP_EXPORT_DATA = 'export_my_login_form_data';
    
    // Form types
    const FORM_TYPE_LOGIN = 'login';
    const FORM_TYPE_REGISTER = 'register';
    const FORM_TYPE_FORGOT_PASSWORD = 'forgot_password';
    const FORM_TYPE_RESET_PASSWORD = 'reset_password';
    const FORM_TYPE_PROFILE = 'profile';
    
    // Field types
    const FIELD_TYPE_TEXT = 'text';
    const FIELD_TYPE_EMAIL = 'email';
    const FIELD_TYPE_PASSWORD = 'password';
    const FIELD_TYPE_NUMBER = 'number';
    const FIELD_TYPE_TEL = 'tel';
    const FIELD_TYPE_URL = 'url';
    const FIELD_TYPE_DATE = 'date';
    const FIELD_TYPE_SELECT = 'select';
    const FIELD_TYPE_CHECKBOX = 'checkbox';
    const FIELD_TYPE_RADIO = 'radio';
    const FIELD_TYPE_TEXTAREA = 'textarea';
    const FIELD_TYPE_FILE = 'file';
    const FIELD_TYPE_HIDDEN = 'hidden';
    const FIELD_TYPE_CAPTCHA = 'captcha';
    
    // User statuses
    const USER_STATUS_ACTIVE = 'active';
    const USER_STATUS_PENDING = 'pending';
    const USER_STATUS_SUSPENDED = 'suspended';
    const USER_STATUS_DELETED = 'deleted';
    
    // Login methods
    const LOGIN_METHOD_WORDPRESS = 'wordpress';
    const LOGIN_METHOD_FIREBASE = 'firebase';
    const LOGIN_METHOD_SOCIAL = 'social';
    
    // Social providers
    const SOCIAL_GOOGLE = 'google';
    const SOCIAL_FACEBOOK = 'facebook';
    const SOCIAL_TWITTER = 'twitter';
    const SOCIAL_LINKEDIN = 'linkedin';
    const SOCIAL_GITHUB = 'github';
    
    // Email templates
    const EMAIL_WELCOME = 'welcome';
    const EMAIL_VERIFICATION = 'verification';
    const EMAIL_PASSWORD_RESET = 'password_reset';
    const EMAIL_ACCOUNT_ACTIVATED = 'account_activated';
    const EMAIL_ACCOUNT_SUSPENDED = 'account_suspended';
    
    // Session timeout in seconds
    const SESSION_TIMEOUT = 86400; // 24 hours
    
    // Maximum login attempts
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_ATTEMPT_LOCKOUT = 900; // 15 minutes in seconds
    
    // Password requirements
    const MIN_PASSWORD_LENGTH = 8;
    const REQUIRE_UPPERCASE = true;
    const REQUIRE_LOWERCASE = true;
    const REQUIRE_NUMBERS = true;
    const REQUIRE_SYMBOLS = true;
    
    /**
     * Get all form types
     *
     * @return array
     */
    public static function get_form_types() {
        return [
            self::FORM_TYPE_LOGIN => __('Login Form', 'my-login-form'),
            self::FORM_TYPE_REGISTER => __('Registration Form', 'my-login-form'),
            self::FORM_TYPE_FORGOT_PASSWORD => __('Forgot Password Form', 'my-login-form'),
            self::FORM_TYPE_RESET_PASSWORD => __('Reset Password Form', 'my-login-form'),
            self::FORM_TYPE_PROFILE => __('Profile Form', 'my-login-form'),
        ];
    }
    
    /**
     * Get all field types
     *
     * @return array
     */
    public static function get_field_types() {
        return [
            self::FIELD_TYPE_TEXT => __('Text', 'my-login-form'),
            self::FIELD_TYPE_EMAIL => __('Email', 'my-login-form'),
            self::FIELD_TYPE_PASSWORD => __('Password', 'my-login-form'),
            self::FIELD_TYPE_NUMBER => __('Number', 'my-login-form'),
            self::FIELD_TYPE_TEL => __('Telephone', 'my-login-form'),
            self::FIELD_TYPE_URL => __('URL', 'my-login-form'),
            self::FIELD_TYPE_DATE => __('Date', 'my-login-form'),
            self::FIELD_TYPE_SELECT => __('Dropdown', 'my-login-form'),
            self::FIELD_TYPE_CHECKBOX => __('Checkbox', 'my-login-form'),
            self::FIELD_TYPE_RADIO => __('Radio', 'my-login-form'),
            self::FIELD_TYPE_TEXTAREA => __('Textarea', 'my-login-form'),
            self::FIELD_TYPE_FILE => __('File Upload', 'my-login-form'),
            self::FIELD_TYPE_HIDDEN => __('Hidden', 'my-login-form'),
            self::FIELD_TYPE_CAPTCHA => __('Captcha', 'my-login-form'),
        ];
    }
    
    /**
     * Get all social providers
     *
     * @return array
     */
    public static function get_social_providers() {
        return [
            self::SOCIAL_GOOGLE => __('Google', 'my-login-form'),
            self::SOCIAL_FACEBOOK => __('Facebook', 'my-login-form'),
            self::SOCIAL_TWITTER => __('Twitter', 'my-login-form'),
            self::SOCIAL_LINKEDIN => __('LinkedIn', 'my-login-form'),
            self::SOCIAL_GITHUB => __('GitHub', 'my-login-form'),
        ];
    }
    
    /**
     * Get all user statuses
     *
     * @return array
     */
    public static function get_user_statuses() {
        return [
            self::USER_STATUS_ACTIVE => __('Active', 'my-login-form'),
            self::USER_STATUS_PENDING => __('Pending', 'my-login-form'),
            self::USER_STATUS_SUSPENDED => __('Suspended', 'my-login-form'),
            self::USER_STATUS_DELETED => __('Deleted', 'my-login-form'),
        ];
    }
    
    /**
     * Get default capabilities
     *
     * @return array
     */
    public static function get_default_capabilities() {
        return [
            'administrator' => [
                self::CAP_MANAGE_SETTINGS,
                self::CAP_MANAGE_FORMS,
                self::CAP_MANAGE_USERS,
                self::CAP_VIEW_LOGS,
                self::CAP_EXPORT_DATA,
            ],
            'editor' => [
                self::CAP_MANAGE_FORMS,
                self::CAP_MANAGE_USERS,
            ],
        ];
    }
}