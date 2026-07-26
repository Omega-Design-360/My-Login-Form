<?php
/**
 * Firebase Authentication Class
 * 
 * @package MyLoginForm
 */

namespace MyLoginForm\Firebase;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Firebase {
    
    private static $instance = null;
    private $api_key;
    private $project_id;
    private $auth_domain;
    private $database_url;
    private $storage_bucket;
    private $messaging_sender_id;
    private $app_id;
    private $service_account_json;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor for singleton
     */
    private function __construct() {
        $this->load_settings();
    }
    
    /**
     * Load Firebase settings from WordPress options
     */
    private function load_settings() {
        $this->api_key              = get_option('mlf_firebase_api_key', '');
        $this->project_id           = get_option('mlf_firebase_project_id', '');
        $this->auth_domain          = get_option('mlf_firebase_auth_domain', '');
        $this->database_url         = get_option('mlf_firebase_database_url', '');
        $this->storage_bucket       = get_option('mlf_firebase_storage_bucket', '');
        $this->messaging_sender_id  = get_option('mlf_firebase_messaging_sender_id', '');
        $this->app_id               = get_option('mlf_firebase_app_id', '');
        $this->service_account_json = get_option('mlf_firebase_service_account', '');
    }
    
    /**
     * Check if Firebase is configured
     */
    public function is_configured() {
        return !empty($this->api_key) && !empty($this->project_id);
    }
    
    /**
     * Get Firebase configuration for frontend
     */
    public function get_frontend_config() {
        return [
            'apiKey'            => $this->api_key,
            'authDomain'        => $this->auth_domain,
            'projectId'         => $this->project_id,
            'storageBucket'     => $this->storage_bucket,
            'messagingSenderId' => $this->messaging_sender_id,
            'appId'             => $this->app_id,
            'databaseURL'       => $this->database_url
        ];
    }
    
    /**
     * Get enabled social providers
     */
    public function get_social_providers() {
        $providers = [];
        $enabled_providers = get_option('mlf_firebase_social_providers', []);
        
        $provider_configs = [
            'google' => [
                'name'  => __('Google', 'my-login-form'),
                'icon'  => 'fab fa-google',
                'color' => '#DB4437'
            ],
            'facebook' => [
                'name'  => __('Facebook', 'my-login-form'),
                'icon'  => 'fab fa-facebook',
                'color' => '#4267B2'
            ],
            'twitter' => [
                'name'  => __('Twitter', 'my-login-form'),
                'icon'  => 'fab fa-twitter',
                'color' => '#fdfdfd'
            ],
            'github' => [
                'name'  => __('GitHub', 'my-login-form'),
                'icon'  => 'fab fa-github',
                'color' => '#fdfdfd'
            ],
            'microsoft' => [
                'name'  => __('Microsoft', 'my-login-form'),
                'icon'  => 'fab fa-microsoft',
                'color' => '#fdfdfd'
            ],
            'apple' => [
                'name'  => __('Apple', 'my-login-form'),
                'icon'  => 'fab fa-apple',
                'color' => '#fdfdfd'
            ]
        ];
        
        foreach ($enabled_providers as $provider) {
            if (isset($provider_configs[$provider])) {
                $providers[$provider] = $provider_configs[$provider];
            }
        }
        
        return $providers;
    }
    
    /**
     * Get Firebase settings
     */
    public function get_settings() {
        return [
            'api_key'               => $this->api_key,
            'project_id'            => $this->project_id,
            'auth_domain'           => $this->auth_domain,
            'database_url'          => $this->database_url,
            'storage_bucket'        => $this->storage_bucket,
            'messaging_sender_id'   => $this->messaging_sender_id,
            'app_id'                => $this->app_id
        ];
    }
    
    /**
     * Register user with Firebase
     * 
     * @param string $email User email
     * @param string $password User password
     * @param array $user_data Additional user data
     * @return array|WP_Error
     */
    public function register_user($email, $password, $user_data = []) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$this->api_key}";
        
        $body = [
            'email'             => $email,
            'password'          => $password,
            'returnSecureToken' => true
        ];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        // Store additional user data in Firebase Realtime Database
        if (!empty($user_data) && !empty($body['localId'])) {
            $this->store_user_data($body['localId'], $user_data);
        }
        
        return [
            'success' => true,
            'user_id' => $body['localId'],
            'id_token' => $body['idToken'],
            'refresh_token' => $body['refreshToken'],
            'email' => $body['email']
        ];
    }
    
    /**
     * Login user with Firebase
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|WP_Error
     */
    public function login_user($email, $password) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$this->api_key}";
        
        $body = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
        ];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        return [
            'success'       => true,
            'user_id'       => $body['localId'],
            'id_token'      => $body['idToken'],
            'refresh_token' => $body['refreshToken'],
            'email'         => $body['email'],
            'display_name'  => isset($body['displayName']) ? $body['displayName'] : ''
        ];
    }
    
    /**
     * Get user data from Firebase
     * 
     * @param string $user_id Firebase user ID
     * @return array|WP_Error
     */
    public function get_user_data($user_id) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$this->api_key}";
        
        $body = ['idToken' => $user_id];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        if (!empty($body['users'][0])) {
            $user = $body['users'][0];
            
            // Get additional data from Realtime Database
            $additional_data = $this->get_user_additional_data($user['localId']);
            
            return array_merge($user, $additional_data);
        }
        
        return new \WP_Error('user_not_found', __('User not found', 'my-login-form'));
    }
    
    /**
     * Store additional user data in Firebase Realtime Database
     * 
     * @param string $user_id Firebase user ID
     * @param array $data User data to store
     * @return bool|WP_Error
     */
    public function store_user_data($user_id, $data) {
        if (empty($this->database_url)) {
            return new \WP_Error('database_not_configured', __('Realtime Database not configured', 'my-login-form'));
        }
        
        $url = "{$this->database_url}/users/{$user_id}.json";
        
        $response = wp_remote_patch($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($data),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return true;
    }
    
    /**
     * Get user additional data from Realtime Database
     * 
     * @param string $user_id Firebase user ID
     * @return array
     */
    private function get_user_additional_data($user_id) {
        if (empty($this->database_url)) {
            return [];
        }
        
        $url = "{$this->database_url}/users/{$user_id}.json";
        
        $response = wp_remote_get($url, ['timeout' => 30]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return is_array($data) ? $data : [];
    }
    
    /**
     * Send password reset email via Firebase
     * 
     * @param string $email User email
     * @return bool|WP_Error
     */
    public function send_password_reset($email) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode?key={$this->api_key}";
        
        $body = [
            'email'       => $email,
            'requestType' => 'PASSWORD_RESET'
        ];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        return true;
    }
    
    /**
     * Verify Firebase ID token
     * 
     * @param string $id_token Firebase ID token
     * @return array|WP_Error
     */
    public function verify_id_token($id_token) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$this->api_key}";
        
        $body = ['idToken' => $id_token];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        if (!empty($body['users'][0])) {
            return $body['users'][0];
        }
        
        return new \WP_Error('invalid_token', __('Invalid token', 'my-login-form'));
    }
    
    /**
     * Social login with Firebase
     * 
     * @param string $provider Social provider (google, facebook, twitter, github)
     * @param string $id_token OAuth ID token
     * @return array|WP_Error
     */
    public function social_login($provider, $id_token) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithIdp?key={$this->api_key}";
        
        $body = [
            'postBody'          => "id_token={$id_token}&providerId={$provider}",
            'requestUri'        => home_url(),
            'returnSecureToken' => true
        ];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        return [
            'display_name' => isset($body['displayName']) ? $body['displayName'] : '',
            'email'         => $body['email'],
            'id_token'      => $body['idToken'],
            'refresh_token' => $body['refreshToken'],
            'success'       => true,
            'user_id'       => $body['localId']
            
        ];
    }
    
    /**
     * Delete user from Firebase
     * 
     * @param string $id_token User ID token
     * @return bool|WP_Error
     */
    public function delete_user($id_token) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:delete?key={$this->api_key}";
        
        $body = ['idToken' => $id_token];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        return true;
    }
    
    /**
     * Update user email
     * 
     * @param string $id_token User ID token
     * @param string $new_email New email address
     * @return array|WP_Error
     */
    public function update_email($id_token, $new_email) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:update?key={$this->api_key}";
        
        $body = [
            'idToken'           => $id_token,
            'email'             => $new_email,
            'returnSecureToken' => true
        ];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        return [
            'success' => true,
            'id_token' => $body['idToken'],
            'refresh_token' => $body['refreshToken']
        ];
    }
    
    /**
     * Update user password
     * 
     * @param string $id_token User ID token
     * @param string $new_password New password
     * @return array|WP_Error
     */
    public function update_password($id_token, $new_password) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:update?key={$this->api_key}";
        
        $body = [
            'idToken'           => $id_token,
            'password'          => $new_password,
            'returnSecureToken' => true
        ];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        return [
            'success' => true,
            'id_token' => $body['idToken'],
            'refresh_token' => $body['refreshToken']
        ];
    }
    
    /**
     * Refresh ID token
     * 
     * @param string $refresh_token Refresh token
     * @return array|WP_Error
     */
    public function refresh_token($refresh_token) {
        if (!$this->is_configured()) {
            return new \WP_Error('firebase_not_configured', __('Firebase is not configured', 'my-login-form'));
        }
        
        $url = "https://securetoken.googleapis.com/v1/token?key={$this->api_key}";
        
        $body = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token
        ];
        
        $response = wp_remote_post($url, [
            'headers'   => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'      => http_build_query($body),
            'timeout'   => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new \WP_Error($body['error']['code'], $body['error']['message']);
        }
        
        return [
            'success'       => true,
            'id_token'      => $body['id_token'],
            'refresh_token' => $body['refresh_token'],
            'expires_in'    => $body['expires_in']
        ];
    }
}