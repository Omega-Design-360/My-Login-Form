<?php
/**
 * My Login Form - Form Database Class
 *
 * @package MyLoginForm\Database
 */

namespace MyLoginForm\Database;

// Prevent Direct Access
defined('ABSPATH') || exit;

class FormsDatabase {

    private $wpdb;
    private $forms_table;
    
    /**
     * Form types
     */
    const FORM_TYPES = [ 'custom', 'login', 'register', 'forgot_password', 'reset_password', 'welcome', 'opt_in' ];

    /**
     * Form status
     */
    const FORM_STATUS = [ 'active', 'inactive', 'draft', 'trash' ];

    /**
     * Redirect after login options
     */
    const REDIRECT_OPTIONS = [ 'dashboard', 'profile', 'home', 'custom' ];

    /**
     * Social login providers
     */
    private $social_providers = [ 'google', 'facebook', 'twitter', 'github', 'linkedin' ];
    
    /**
     * Layout options
     */
    private $layout_options = [ 'grid', 'flex' ];

    private static $instance = null;

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->forms_table = $wpdb->prefix . 'my_login_forms';
        
        // Create table immediately on construct
        $this->create_forms_table();
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        // Check for updates and default forms
        $this->maybe_update_forms_table();
        $this->create_default_forms_if_not_exist();
    }

    public function create_forms_table() {
    $charset_collate = $this->wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$this->forms_table} (
        id INT(11) NOT NULL AUTO_INCREMENT,
        form_key VARCHAR(100) NOT NULL,
        form_type VARCHAR(50) NOT NULL DEFAULT 'custom',
        name VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        settings LONGTEXT DEFAULT NULL,
        fields LONGTEXT DEFAULT NULL,
        css_file VARCHAR(255) DEFAULT '',
        js_file VARCHAR(255) DEFAULT '',
        html_file VARCHAR(255) DEFAULT '',
        redirect_url VARCHAR(500) DEFAULT '',
        social_login TINYINT(1) NOT NULL DEFAULT 0,
        social_providers TEXT DEFAULT NULL,
        redirect_after_login VARCHAR(50) DEFAULT 'dashboard',
        redirect_after_logout VARCHAR(50) DEFAULT 'home',
        status VARCHAR(20) DEFAULT 'active',
        is_default TINYINT(1) DEFAULT 0,
        is_system TINYINT(1) DEFAULT 0,
        is_builtin TINYINT(1) DEFAULT 0,
        sort_order INT(11) DEFAULT 0,
        views_count BIGINT(20) DEFAULT 0,
        submissions_count BIGINT(20) DEFAULT 0,
        form_containers LONGTEXT DEFAULT NULL,
        form_layout LONGTEXT DEFAULT NULL,
        form_styles LONGTEXT DEFAULT NULL,
        created_by INT(11) DEFAULT NULL,
        updated_by INT(11) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_form_key (form_key),
        KEY form_type (form_type),
        KEY status (status),
        KEY is_default (is_default),
        KEY sort_order (sort_order)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    return $this->table_exists();
}

    private function create_default_forms_if_not_exist() {
        if (!$this->table_exists()) return;
        $count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->forms_table}");
        if ($count == 0) {
            $this->ensure_form_directories();
            $this->create_default_forms();
        }
    }

    private function ensure_form_directories() {
        if (!defined('MY_LOGIN_FORM_DIR')) return;
        $htaccess = "# Allow form asset files\n<IfModule mod_authz_core.c>\n    Require all denied\n    <FilesMatch \"\.(css|js|html)$\">\n        Require all granted\n    </FilesMatch>\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order Deny,Allow\n    Deny from all\n    <FilesMatch \"\.(css|js|html)$\">\n        Allow from all\n    </FilesMatch>\n</IfModule>";
        foreach (['css', 'js', 'html'] as $sub) {
            $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/' . $sub . '/';
            if (!file_exists($dir)) wp_mkdir_p($dir);
            if (!file_exists($dir . '.htaccess'))  file_put_contents($dir . '.htaccess', $htaccess);
            if (!file_exists($dir . 'index.php'))  file_put_contents($dir . 'index.php', '<?php // Silence is golden');
        }
    }

    private function get_default_form_css() {
        // Kept in sync with DesignerAjax::get_default_form_css() — this copy only
        // runs the very first time the forms table is seeded (or reseeded after
        // being dropped), the other runs on every create/save from the Designer.
        return '.my-login-main-container{margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;box-sizing:border-box;box-shadow:0 10px 40px rgba(4,18,0,.08),0 2px 8px rgba(4,18,0,.06)}'
             . '.my-login-form-field{margin-bottom:16px}'
             . '.my-login-label{display:block;font-size:13px;font-weight:600;color:#2A2A2A;margin-bottom:5px}'
             . '.my-login-req{color:#8A0000;margin-left:2px}'
             . '.my-login-main-container input[type=text],.my-login-main-container input[type=email],.my-login-main-container input[type=password],.my-login-main-container input[type=tel],.my-login-main-container input[type=number],.my-login-main-container textarea,.my-login-main-container select{display:block;width:100%;padding:10px 14px;font-size:14px;color:#2A2A2A;background:#fff;border:1.5px solid #DCE8D6;border-radius:8px;box-sizing:border-box;transition:border-color .15s,box-shadow .15s}'
             . '.my-login-main-container input:focus,.my-login-main-container textarea:focus,.my-login-main-container select:focus{outline:none;border-color:#1FBB00;box-shadow:0 0 0 3px rgba(31,187,0,.15)}'
             . '.my-login-sub-div{border-radius:10px;margin:10px 0;padding:12px;border:1px solid #E3F0DE}'
             . '.my-login-submit-btn{display:block;width:100%;padding:12px;font-size:15px;font-weight:600;color:#fff;border:none;border-radius:8px;cursor:pointer;transition:transform .15s ease,box-shadow .15s ease,filter .15s ease}'
             . '.my-login-submit-btn:hover{transform:translateY(-1px);filter:brightness(.94)}'
             . '.my-login-submit-btn:active{transform:translateY(0) scale(.99);filter:brightness(.9)}'
             . '.my-login-social-section{text-align:center;padding:14px 0;margin-top:10px;border-top:1px solid #E3F0DE}'
             . '.my-login-social-divider{font-size:12px;color:#6B8064;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em}'
             . '.my-login-social-buttons{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}'
             . '.my-login-social-btn{background:linear-gradient(135deg,#051A00 0%,#093400 100%);padding:9px 18px;border:none;border-radius:6px;color:#fff;cursor:pointer;font-size:13px;transition:opacity .2s,transform .15s ease}'
             . '.my-login-social-btn:hover{opacity:.85;transform:translateY(-1px)}'
             . '.my-login-greeting{margin-bottom:18px;text-align:center}'
             . '.my-login-greeting-title{margin:0 0 4px;font-size:22px;font-weight:700;color:#1A2E05}'
             . '.my-login-greeting-subtitle{margin:0;font-size:13px;color:#6B8064}';
    }

    private function build_html_from_containers($containers, $settings = []) {
        $btn_color   = isset($settings['btn_color'])    ? $settings['btn_color']   : '#1FBB00';
        $button_text = isset($settings['button_text'])  ? $settings['button_text'] : 'Submit';
        $show_labels = isset($settings['show_labels'])  ? (bool) $settings['show_labels'] : true;
        $html = '';
        foreach ($containers as $c) {
            $bg    = isset($c['bgColor']) ? esc_attr($c['bgColor']) : '#ffffff';
            $inner = $this->build_items_html_php($c['items'] ?? [], $show_labels);
            $html .= '<div class="my-login-main-container" style="background:' . $bg . ';padding:28px;border-radius:12px;">'
                   . $inner
                   . '<div class="my-login-form-submit" style="margin-top:18px;">'
                   . '<button type="submit" class="my-login-submit-btn" style="background:' . esc_attr($btn_color) . ';">'
                   . esc_html($button_text) . '</button></div></div>';
        }
        return $html;
    }

    private function build_items_html_php($items, $show_labels) {
        $html = '';
        foreach ($items as $item) {
            $type = $item['type'] ?? '';
            if ($type === 'field') {
                $html .= $this->build_field_html_php($item, $show_labels);
            } elseif ($type === 'sub') {
                $bg   = esc_attr($item['bgColor'] ?? '#f8f9fa');
                $kids = $this->build_items_html_php($item['items'] ?? [], $show_labels);
                $html .= '<div class="my-login-sub-div" style="background:' . $bg . ';">' . $kids . '</div>';
            } elseif ($type === 'greeting') {
                $html .= $this->build_greeting_html_php($item);
            }
        }
        return $html;
    }

    private function build_greeting_html_php($item) {
        $title    = $item['title']    ?? '';
        $subtitle = $item['subtitle'] ?? '';
        $html = '<div class="my-login-greeting">';
        if ($title !== '') {
            $html .= '<h2 class="my-login-greeting-title">' . esc_html($title) . '</h2>';
        }
        if ($subtitle !== '') {
            $html .= '<p class="my-login-greeting-subtitle">' . esc_html($subtitle) . '</p>';
        }
        return $html . '</div>';
    }

    private function build_field_html_php($f, $show_labels) {
        $fieldType   = $f['fieldType']   ?? 'text';
        $htmlType    = $f['htmlType']    ?? 'text';
        $label       = $f['label']       ?? ucwords(str_replace('_', ' ', $fieldType));
        $placeholder = $f['placeholder'] ?? '';
        $required    = !empty($f['required']);
        $req         = $required ? ' required' : '';
        $req_mark    = $required ? '<span class="my-login-req">*</span>' : '';
        $uid         = 'my_login_' . sanitize_key($fieldType);

        $html = '<div class="my-login-form-field">';
        if ($show_labels && !in_array($htmlType, ['checkbox', 'radio'], true)) {
            $html .= '<label class="my-login-label" for="' . esc_attr($uid) . '">' . esc_html($label) . $req_mark . '</label>';
        }
        if ($htmlType === 'textarea') {
            $html .= '<textarea id="' . esc_attr($uid) . '" name="' . esc_attr($fieldType) . '" placeholder="' . esc_attr($placeholder) . '" rows="4"' . $req . '></textarea>';
        } elseif (in_array($htmlType, ['checkbox', 'radio'], true)) {
            $html .= '<label><input type="' . esc_attr($htmlType) . '" name="' . esc_attr($fieldType) . '" id="' . esc_attr($uid) . '" value="1"' . $req . '> ' . esc_html($label) . $req_mark . '</label>';
        } else {
            $html .= '<input type="' . esc_attr($htmlType) . '" id="' . esc_attr($uid) . '" name="' . esc_attr($fieldType) . '" placeholder="' . esc_attr($placeholder) . '"' . $req . '>';
        }
        return $html . '</div>';
    }

    private function create_default_forms() {
        $default_css = $this->get_default_form_css();

        // Login form containers
        $login_containers = [
            'main_default_login' => [
                'id' => 'main_default_login', 'type' => 'main', 'bgColor' => '#ffffff', 'styles' => [],
                'items' => [
                    ['id'=>'greeting_login', 'type'=>'greeting','title'=>'Welcome Back','subtitle'=>'Please sign in to continue','styles'=>[]],
                    ['id'=>'fld_login_email',   'type'=>'field','fieldType'=>'email',   'label'=>'Email Address','htmlType'=>'email',   'placeholder'=>'Enter your email',    'required'=>true],
                    ['id'=>'fld_login_password','type'=>'field','fieldType'=>'password','label'=>'Password',     'htmlType'=>'password','placeholder'=>'Enter your password','required'=>true],
                ],
            ],
        ];
        $login_settings = ['btn_color'=>'#1FBB00','button_text'=>'Log In','show_labels'=>true,'redirect_after_login'=>'home'];

        // Register form containers
        $register_containers = [
            'main_default_register' => [
                'id' => 'main_default_register', 'type' => 'main', 'bgColor' => '#ffffff', 'styles' => [],
                'items' => [
                    ['id'=>'sub_reg_names','type'=>'sub','bgColor'=>'#F3FBF0','items'=>[
                        ['id'=>'fld_reg_first','type'=>'field','fieldType'=>'first_name','label'=>'First Name','htmlType'=>'text','placeholder'=>'First name','required'=>false],
                        ['id'=>'fld_reg_last', 'type'=>'field','fieldType'=>'last_name', 'label'=>'Last Name', 'htmlType'=>'text','placeholder'=>'Last name', 'required'=>false],
                    ]],
                    ['id'=>'fld_reg_email',   'type'=>'field','fieldType'=>'email',           'label'=>'Email Address',    'htmlType'=>'email',   'placeholder'=>'Enter your email',      'required'=>true],
                    ['id'=>'fld_reg_pass',    'type'=>'field','fieldType'=>'password',         'label'=>'Password',         'htmlType'=>'password','placeholder'=>'Choose a strong password','required'=>true],
                    ['id'=>'fld_reg_confirm', 'type'=>'field','fieldType'=>'confirm_password', 'label'=>'Confirm Password', 'htmlType'=>'password','placeholder'=>'Confirm your password', 'required'=>true],
                ],
            ],
        ];
        $register_settings = ['btn_color'=>'#0F5900','button_text'=>'Create Account','show_labels'=>true,'redirect_after_registration'=>'my-profile'];

        // Forgot password containers
        $forgot_containers = [
            'main_default_forgot' => [
                'id' => 'main_default_forgot', 'type' => 'main', 'bgColor' => '#ffffff', 'styles' => [],
                'items' => [
                    ['id'=>'fld_forgot_email','type'=>'field','fieldType'=>'email','label'=>'Email Address','htmlType'=>'email','placeholder'=>'Enter your registered email','required'=>true],
                ],
            ],
        ];
        $forgot_settings = ['btn_color'=>'#B35B00','button_text'=>'Reset Password','show_labels'=>true];

        // Reset password containers — the page a "Forgot Password" email link
        // lands on. No email field: the visitor is identified by the key/login
        // pair AuthAjax::handle_forgot_password() puts in the emailed link,
        // which render_from_containers() reads from $_GET and injects as
        // hidden fields around this Designer-editable field set.
        $reset_containers = [
            'main_default_reset' => [
                'id' => 'main_default_reset', 'type' => 'main', 'bgColor' => '#ffffff', 'styles' => [],
                'items' => [
                    ['id'=>'fld_reset_pass',    'type'=>'field','fieldType'=>'password',         'label'=>'New Password',     'htmlType'=>'password','placeholder'=>'Choose a strong password','required'=>true],
                    ['id'=>'fld_reset_confirm', 'type'=>'field','fieldType'=>'confirm_password', 'label'=>'Confirm New Password','htmlType'=>'password','placeholder'=>'Confirm your new password','required'=>true],
                ],
            ],
        ];
        $reset_settings = ['btn_color'=>'#0F5900','button_text'=>'Set New Password','show_labels'=>true,'redirect_after_login'=>'login'];

        // Welcome form containers — a post-registration "tell us about yourself"
        // profile-completion step, not another login/password prompt.
        $welcome_containers = [
            'main_default_welcome' => [
                'id' => 'main_default_welcome', 'type' => 'main', 'bgColor' => '#ffffff', 'styles' => [],
                'items' => [
                    ['id'=>'sub_wel_names','type'=>'sub','bgColor'=>'#F3FBF0','items'=>[
                        ['id'=>'fld_wel_first','type'=>'field','fieldType'=>'first_name','label'=>'First Name','htmlType'=>'text','placeholder'=>'Your first name','required'=>true],
                        ['id'=>'fld_wel_last', 'type'=>'field','fieldType'=>'last_name', 'label'=>'Last Name', 'htmlType'=>'text','placeholder'=>'Your last name', 'required'=>false],
                    ]],
                ],
            ],
        ];
        $welcome_settings = ['btn_color'=>'#1FBB00','button_text'=>'Continue','show_labels'=>true];

        // Opt-in form containers — newsletter/consent signup: name, email, and a
        // required consent checkbox. No password; this isn't an account form.
        $optin_containers = [
            'main_default_optin' => [
                'id' => 'main_default_optin', 'type' => 'main', 'bgColor' => '#ffffff', 'styles' => [],
                'items' => [
                    ['id'=>'fld_optin_first',  'type'=>'field','fieldType'=>'first_name','label'=>'First Name',   'htmlType'=>'text',    'placeholder'=>'Your first name', 'required'=>false],
                    ['id'=>'fld_optin_email',  'type'=>'field','fieldType'=>'email',     'label'=>'Email Address','htmlType'=>'email',   'placeholder'=>'Enter your email','required'=>true],
                    ['id'=>'fld_optin_consent','type'=>'field','fieldType'=>'consent',   'label'=>'I agree to receive updates and offers via email', 'htmlType'=>'checkbox', 'placeholder'=>'', 'required'=>true],
                ],
            ],
        ];
        $optin_settings = ['btn_color'=>'#1FBB00','button_text'=>'Subscribe','show_labels'=>true];

        // A commented starter example, pre-filled into every default form's CSS
        // tab. It's appended after the generated base/colour styles (see
        // build_wrap_css()), so anything uncommented here can override them.
        $css_starter = "\n/* Add your own CSS here — it's layered on top of the generated styles\n   above, so it can override anything. Example:\n\n.my-login-submit-btn {\n    text-transform: uppercase;\n    letter-spacing: .05em;\n}\n\n.my-login-label {\n    font-style: italic;\n}\n*/";

        $forms_def = [
            'login' => [
                'form_key' => 'login', 'form_type' => 'login',
                'name' => 'Login Form', 'sort_order' => 1,
                'containers' => $login_containers,   'settings' => $login_settings,
                'btn_text' => 'Log In',
                'fields' => ['email'=>['type'=>'email','label'=>'Email Address','required'=>true,'placeholder'=>'Enter your email','class'=>'form-control'],'password'=>['type'=>'password','label'=>'Password','required'=>true,'placeholder'=>'Enter your password','class'=>'form-control']],
                'js' => "// Show a \"Caps Lock is on\" hint while typing the password.\n"
                      . "// Styling lives in the generated CSS (.my-login-caps-hint) — this only toggles a class.\n"
                      . "document.addEventListener('DOMContentLoaded', function () {\n"
                      . "    var pwd = document.querySelector('.my-login-wrap input[type=\"password\"]');\n"
                      . "    if (!pwd) return;\n"
                      . "    var hint = document.createElement('div');\n"
                      . "    hint.className = 'my-login-caps-hint';\n"
                      . "    hint.textContent = 'Caps Lock is on';\n"
                      . "    pwd.insertAdjacentElement('afterend', hint);\n"
                      . "    pwd.addEventListener('keyup', function (e) {\n"
                      . "        var caps = e.getModifierState && e.getModifierState('CapsLock');\n"
                      . "        hint.classList.toggle('is-visible', !!caps);\n"
                      . "    });\n"
                      . "});",
            ],
            'register' => [
                'form_key' => 'register', 'form_type' => 'register',
                'name' => 'Registration Form', 'sort_order' => 2,
                'containers' => $register_containers, 'settings' => $register_settings,
                'btn_text' => 'Create Account',
                'fields' => ['first_name'=>['type'=>'text','label'=>'First Name','required'=>false,'placeholder'=>'First name','class'=>'form-control'],'last_name'=>['type'=>'text','label'=>'Last Name','required'=>false,'placeholder'=>'Last name','class'=>'form-control'],'email'=>['type'=>'email','label'=>'Email Address','required'=>true,'placeholder'=>'Enter your email','class'=>'form-control'],'password'=>['type'=>'password','label'=>'Password','required'=>true,'placeholder'=>'Choose a password','class'=>'form-control'],'confirm_password'=>['type'=>'password','label'=>'Confirm Password','required'=>true,'placeholder'=>'Confirm your password','class'=>'form-control']],
                'js' => "// Live \"passwords match\" feedback between Password and Confirm Password.\n"
                      . "// Styling lives in the generated CSS (.my-login-field-match / .my-login-field-mismatch).\n"
                      . "document.addEventListener('DOMContentLoaded', function () {\n"
                      . "    var pwd = document.querySelector('.my-login-wrap input[name=\"password\"]');\n"
                      . "    var confirmField = document.querySelector('.my-login-wrap input[name=\"confirm_password\"]');\n"
                      . "    if (!pwd || !confirmField) return;\n"
                      . "    var wrap = confirmField.closest('.my-login-form-field');\n"
                      . "    function check() {\n"
                      . "        wrap.classList.remove('my-login-field-match', 'my-login-field-mismatch');\n"
                      . "        if (!confirmField.value) return;\n"
                      . "        wrap.classList.add(confirmField.value === pwd.value ? 'my-login-field-match' : 'my-login-field-mismatch');\n"
                      . "    }\n"
                      . "    pwd.addEventListener('input', check);\n"
                      . "    confirmField.addEventListener('input', check);\n"
                      . "});",
            ],
            'forgot-password' => [
                'form_key' => 'forgot-password', 'form_type' => 'forgot_password',
                'name' => 'Forgot Password Form', 'sort_order' => 3,
                'containers' => $forgot_containers,  'settings' => $forgot_settings,
                'btn_text' => 'Reset Password',
                'fields' => ['email'=>['type'=>'email','label'=>'Email Address','required'=>true,'placeholder'=>'Enter your email','class'=>'form-control']],
                'js' => "// Auto-focus the email field so visitors can start typing immediately.\n"
                      . "document.addEventListener('DOMContentLoaded', function () {\n"
                      . "    var email = document.querySelector('.my-login-wrap input[type=\"email\"]');\n"
                      . "    if (email) email.focus();\n"
                      . "});",
            ],
            'reset-password' => [
                'form_key' => 'reset-password', 'form_type' => 'reset_password',
                'name' => 'Reset Password Form', 'sort_order' => 4,
                'containers' => $reset_containers,  'settings' => $reset_settings,
                'btn_text' => 'Set New Password',
                'fields' => ['password'=>['type'=>'password','label'=>'New Password','required'=>true,'placeholder'=>'Choose a strong password','class'=>'form-control'],'confirm_password'=>['type'=>'password','label'=>'Confirm New Password','required'=>true,'placeholder'=>'Confirm your new password','class'=>'form-control']],
                'js' => "// Live \"passwords match\" feedback between New Password and Confirm New Password.\n"
                      . "// Styling lives in the generated CSS (.my-login-field-match / .my-login-field-mismatch).\n"
                      . "document.addEventListener('DOMContentLoaded', function () {\n"
                      . "    var pwd = document.querySelector('.my-login-wrap input[name=\"password\"]');\n"
                      . "    var confirmField = document.querySelector('.my-login-wrap input[name=\"confirm_password\"]');\n"
                      . "    if (!pwd || !confirmField) return;\n"
                      . "    var wrap = confirmField.closest('.my-login-form-field');\n"
                      . "    function check() {\n"
                      . "        wrap.classList.remove('my-login-field-match', 'my-login-field-mismatch');\n"
                      . "        if (!confirmField.value) return;\n"
                      . "        wrap.classList.add(confirmField.value === pwd.value ? 'my-login-field-match' : 'my-login-field-mismatch');\n"
                      . "    }\n"
                      . "    pwd.addEventListener('input', check);\n"
                      . "    confirmField.addEventListener('input', check);\n"
                      . "});",
            ],
            'welcome' => [
                'form_key' => 'welcome', 'form_type' => 'welcome',
                'name' => 'Welcome Form', 'sort_order' => 5,
                'containers' => $welcome_containers, 'settings' => $welcome_settings,
                'btn_text' => 'Continue',
                'fields' => ['first_name'=>['type'=>'text','label'=>'First Name','required'=>true,'placeholder'=>'Your first name','class'=>'form-control'],'last_name'=>['type'=>'text','label'=>'Last Name','required'=>false,'placeholder'=>'Your last name','class'=>'form-control']],
                'js' => "// Subtle fade/slide-in for the welcome card.\n"
                      . "// Styling lives in the generated CSS (.my-login-fade-in) — this only toggles a class.\n"
                      . "document.addEventListener('DOMContentLoaded', function () {\n"
                      . "    var card = document.querySelector('.my-login-wrap .my-login-main-container');\n"
                      . "    if (!card) return;\n"
                      . "    card.classList.add('my-login-fade-in');\n"
                      . "    requestAnimationFrame(function () {\n"
                      . "        requestAnimationFrame(function () { card.classList.add('is-visible'); });\n"
                      . "    });\n"
                      . "});",
            ],
            'opt-in' => [
                'form_key' => 'opt-in', 'form_type' => 'opt-in',
                'name' => 'Opt-in Form', 'sort_order' => 6,
                'containers' => $optin_containers,   'settings' => $optin_settings,
                'btn_text' => 'Subscribe',
                'fields' => ['first_name'=>['type'=>'text','label'=>'First Name','required'=>false,'placeholder'=>'Your first name','class'=>'form-control'],'email'=>['type'=>'email','label'=>'Email Address','required'=>true,'placeholder'=>'Enter your email','class'=>'form-control'],'consent'=>['type'=>'checkbox','label'=>'I agree to receive updates and offers via email','required'=>true,'placeholder'=>'','class'=>'form-check-input']],
                'js' => "// Keep the Subscribe button disabled until the consent checkbox is checked.\n"
                      . "// Dimmed/not-allowed look comes from the generated CSS (.my-login-submit-btn:disabled).\n"
                      . "document.addEventListener('DOMContentLoaded', function () {\n"
                      . "    var consent = document.querySelector('.my-login-wrap input[name=\"consent\"]');\n"
                      . "    var button  = document.querySelector('.my-login-wrap .my-login-submit-btn');\n"
                      . "    if (!consent || !button) return;\n"
                      . "    function sync() { button.disabled = !consent.checked; }\n"
                      . "    consent.addEventListener('change', sync);\n"
                      . "    sync();\n"
                      . "});",
            ],
        ];

        foreach ($forms_def as $fd) {
            if ($this->get_form_by_key($fd['form_key'])) continue;

            $settings = array_merge($fd['settings'], ['show_remember_me'=>false,'enable_validation'=>true,'border_color'=>'#DCE8D6','custom_css'=>$css_starter]);
            $html_content = $this->build_html_from_containers($fd['containers'], $settings);

            $row = [
                'form_key'         => $fd['form_key'],
                'form_type'        => $fd['form_type'],
                'name'             => $fd['name'],
                'is_default'       => 1,
                'is_system'        => 0,
                'is_builtin'       => 0,
                'sort_order'       => $fd['sort_order'],
                'status'           => 'active',
                'settings'         => wp_json_encode($settings),
                'fields'           => wp_json_encode($fd['fields']),
                'form_containers'  => wp_json_encode($fd['containers']),
                'css_file'         => $fd['form_key'] . '.css',
                'html_file'        => $fd['form_key'] . '.html',
                'js_file'          => $fd['form_key'] . '.js',
            ];

            $this->wpdb->insert($this->forms_table, $row);
            $new_id = $this->wpdb->insert_id;

            if (defined('MY_LOGIN_FORM_DIR')) {
                $css_dir  = MY_LOGIN_FORM_DIR . 'Public/Forms/css/';
                $html_dir = MY_LOGIN_FORM_DIR . 'Public/Forms/html/';
                $js_dir   = MY_LOGIN_FORM_DIR . 'Public/Forms/js/';
                if (!file_exists($css_dir))  wp_mkdir_p($css_dir);
                if (!file_exists($html_dir)) wp_mkdir_p($html_dir);
                if (!file_exists($js_dir))   wp_mkdir_p($js_dir);
                // Delegate to DesignerAjax::build_form_css() — the single source of
                // truth for generated CSS (base styles, button gradient, and every
                // container/field Layout+Customization rule). Every subsequent save
                // from the Designer runs through this same method; seeding through
                // a second, separate implementation is exactly what caused the
                // colour scheme and missing-button-background bugs earlier.
                $css = class_exists('MyLoginForm\\Ajax\\DesignerAjax')
                    ? \MyLoginForm\Ajax\DesignerAjax::get_instance()->build_form_css($new_id, $settings, $css_starter, $fd['containers'])
                    : $default_css;
                file_put_contents($css_dir  . $fd['form_key'] . '.css',  $css);
                file_put_contents($html_dir . $fd['form_key'] . '.html', $html_content);
                file_put_contents($js_dir   . $fd['form_key'] . '.js',   $fd['js'] ?? '');
            }
        }
    }

    private function maybe_update_forms_table() {
        $columns = $this->wpdb->get_col("DESC {$this->forms_table}");
        
        // Add form_key if missing
        if (!in_array('form_key', $columns)) {
            $this->wpdb->query("ALTER TABLE {$this->forms_table} ADD form_key VARCHAR(100) NOT NULL AFTER id");
            $this->wpdb->query("ALTER TABLE {$this->forms_table} ADD UNIQUE KEY unique_form_key (form_key)");
        }
        
        // Add html_file if missing
        if (!in_array('html_file', $columns)) {
            $this->wpdb->query("ALTER TABLE {$this->forms_table} ADD html_file VARCHAR(255) DEFAULT '' AFTER js_file");
        }
        
        // Add new columns if missing
        $new_columns = [
            'description' => "ADD description TEXT DEFAULT NULL AFTER name",
            'social_providers' => "ADD social_providers TEXT DEFAULT NULL AFTER social_login",
            'form_containers' => "ADD form_containers LONGTEXT DEFAULT NULL AFTER submissions_count",
            'form_layout' => "ADD form_layout LONGTEXT DEFAULT NULL AFTER form_containers",
            'form_styles' => "ADD form_styles LONGTEXT DEFAULT NULL AFTER form_layout",
        ];
        
        foreach ($new_columns as $column => $sql) {
            if (!in_array($column, $columns)) {
                $this->wpdb->query("ALTER TABLE {$this->forms_table} {$sql}");
            }
        }
    }

    public function get_form($id) {
        if (!$this->table_exists()) {
            return null;
        }
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->forms_table} WHERE id = %d", $id)
        );
    }

    public function get_form_by_key($form_key) {
        if (!$this->table_exists()) {
            return null;
        }
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->forms_table} WHERE form_key = %s", $form_key)
        );
    }

    public function increment_views_count($id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->forms_table} SET views_count = views_count + 1 WHERE id = %d",
                $id
            )
        );
    }

    public function get_all_forms($args = array()) {
        if (!$this->table_exists()) {
            return array();
        }
        
        $defaults = array(
            'status' => 'active',
            'orderby' => 'sort_order',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE status = 'active'";
        
        if (!empty($args['form_type'])) {
            $where .= $this->wpdb->prepare(" AND form_type = %s", $args['form_type']);
        }
        
        $allowed_orderby = ['id', 'name', 'form_type', 'sort_order', 'views_count', 'submissions_count', 'created_at', 'updated_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'sort_order';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM {$this->forms_table} {$where} ORDER BY {$orderby} {$order}";
        
        return $this->wpdb->get_results($sql);
    }

    public function get_default_form($form_type = 'login') {
        if (!$this->table_exists()) {
            return null;
        }
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->forms_table} 
                WHERE form_type = %s AND status = 'active' 
                ORDER BY is_default DESC, sort_order ASC LIMIT 1",
                $form_type
            )
        );
    }

    public function update_form($id, $data) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $sanitized = array();
        
        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['description'])) {
            $sanitized['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['settings'])) {
            $sanitized['settings'] = is_array($data['settings']) ? wp_json_encode($data['settings']) : $data['settings'];
        }
        if (isset($data['fields'])) {
            $sanitized['fields'] = is_array($data['fields']) ? wp_json_encode($data['fields']) : $data['fields'];
        }
        if (isset($data['html_file'])) {
            $sanitized['html_file'] = sanitize_text_field($data['html_file']);
        }
        if (isset($data['css_file'])) {
            $sanitized['css_file'] = sanitize_text_field($data['css_file']);
        }
        if (isset($data['js_file'])) {
            $sanitized['js_file'] = sanitize_text_field($data['js_file']);
        }
        if (isset($data['social_providers'])) {
            $sanitized['social_providers'] = is_array($data['social_providers']) ? wp_json_encode($data['social_providers']) : $data['social_providers'];
        }
        if (isset($data['form_containers'])) {
            $sanitized['form_containers'] = is_array($data['form_containers']) ? wp_json_encode($data['form_containers']) : $data['form_containers'];
        }
        if (isset($data['form_layout'])) {
            $sanitized['form_layout'] = is_array($data['form_layout']) ? wp_json_encode($data['form_layout']) : $data['form_layout'];
        }
        if (isset($data['form_styles'])) {
            $sanitized['form_styles'] = is_array($data['form_styles']) ? wp_json_encode($data['form_styles']) : $data['form_styles'];
        }
        if (isset($data['status'])) {
            $sanitized['status'] = sanitize_text_field($data['status']);
        }
        if (isset($data['redirect_after_login'])) {
            $sanitized['redirect_after_login'] = sanitize_text_field($data['redirect_after_login']);
        }
        if (isset($data['redirect_after_logout'])) {
            $sanitized['redirect_after_logout'] = sanitize_text_field($data['redirect_after_logout']);
        }
        if (isset($data['sort_order'])) {
            $sanitized['sort_order'] = intval($data['sort_order']);
        }
        
        return $this->wpdb->update(
            $this->forms_table,
            $sanitized,
            array('id' => $id)
        );
    }

    public function delete_form($id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        // Allow deletion of any form (including defaults since they're not system)
        return $this->wpdb->delete($this->forms_table, array('id' => $id));
    }

    public function table_exists() {
        $table = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $this->forms_table)
        );
        return $table === $this->forms_table;
    }

    public function get_forms_count() {
        if (!$this->table_exists()) {
            return 0;
        }
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->forms_table}");
    }

    public function duplicate_form($id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $form = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->forms_table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        if (!$form) {
            return false;
        }
        
        unset($form['id']);
        unset($form['created_at']);
        unset($form['updated_at']);
        
        $form['name'] = $form['name'] . ' (Copy)';
        $form['form_key'] = $form['form_key'] . '_copy_' . time();
        $form['is_default'] = 0;
        $form['is_system'] = 0;
        $form['is_builtin'] = 0;
        $form['views_count'] = 0;
        $form['submissions_count'] = 0;
        
        $this->wpdb->insert($this->forms_table, $form);
        return $this->wpdb->insert_id;
    }

    // ========== GETTER METHODS ==========

    /**
     * Get social providers
     */
    public function get_social_providers() {
        return $this->social_providers;
    }

    /**
     * Get layout options
     */
    public function get_layout_options() {
        return $this->layout_options;
    }

    /**
     * Add social provider dynamically
     */
    public function add_social_provider($provider) {
        if (!in_array($provider, $this->social_providers)) {
            $this->social_providers[] = $provider;
        }
        return $this;
    }

    /**
     * Remove social provider
     */
    public function remove_social_provider($provider) {
        $key = array_search($provider, $this->social_providers);
        if ($key !== false) {
            unset($this->social_providers[$key]);
            $this->social_providers = array_values($this->social_providers);
        }
        return $this;
    }

    /**
     * Check if form type is valid
     */
    public function is_valid_form_type($type) {
        return in_array($type, self::FORM_TYPES);
    }

    /**
     * Check if status is valid
     */
    public function is_valid_status($status) {
        return in_array($status, self::FORM_STATUS);
    }

    /**
     * Check if redirect option is valid
     */
    public function is_valid_redirect($redirect) {
        return in_array($redirect, self::REDIRECT_OPTIONS);
    }
}