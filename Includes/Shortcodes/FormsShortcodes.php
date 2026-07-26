<?php
/**
 * Form Shortcode Handler - Fixed to read saved fields from database
 *
 * @package MyLoginForm\Shortcodes
 */

namespace MyLoginForm\Shortcodes;

// Prevent Direct Access
defined('ABSPATH') || exit;

class FormsShortcodes {
    
    private static $instance = null;
    
    private function __construct() {
        $this->init();
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init() {
        add_shortcode('my_login_form',        [$this, 'render_form']);
        add_shortcode('my_login_user_data',   [$this, 'shortcode_user_data']);
        add_shortcode('my_login_greeting',    [$this, 'shortcode_greeting']);
        add_shortcode('my_login_if_logged_in',  [$this, 'shortcode_if_logged_in']);
        add_shortcode('my_login_if_logged_out', [$this, 'shortcode_if_logged_out']);

        // Let {display_name}, {username}, etc. work as plain merge tags typed
        // directly into any paragraph or heading — no shortcode wrapper needed.
        add_filter('the_content', [$this, 'replace_user_data_tags'], 20);
    }

    /**
     * Merge-tag => value map for the current user. Shared by the greeting
     * shortcode and the bare {tag} content replacement below.
     */
    private function get_user_merge_tags($user) {
        $first    = get_user_meta($user->ID, 'first_name', true);
        $last     = get_user_meta($user->ID, 'last_name', true);
        $nickname = get_user_meta($user->ID, 'nickname', true);

        // Same fallback chain {name} has always used, now shared by every
        // name-shaped tag below — an unset first/last/nickname or a blank
        // display/username still shows a real name instead of empty text.
        $name_fallback = $first ?: ($last ?: ($user->display_name ?: $this->get_default_guest_name()));

        return [
            '{name}'            => $name_fallback,
            '{first_name}'      => $first ?: $name_fallback,
            '{last_name}'       => $last ?: $name_fallback,
            '{display_name}'    => $user->display_name ?: $name_fallback,
            '{username}'        => $user->user_login ?: $name_fallback,
            '{email}'           => $user->user_email,
            '{nickname}'        => $nickname ?: $name_fallback,
            '{website}'         => $user->user_url,
            '{user_registered}' => mysql2date(get_option('date_format'), $user->user_registered),
        ];
    }

    /**
     * Admin-configurable fallback for {name} (Settings → General → "Default
     * Guest Name"), defaulting to "Sunshine" if never set.
     */
    private function get_default_guest_name() {
        return get_option('my_login_form_guest_default_name', 'Sunshine');
    }

    /**
     * Fallback values for the same merge tags when nobody is logged in.
     * Every name-shaped tag — {name}, {first_name}, {last_name},
     * {display_name}, {username}, {nickname} — defaults to the configured
     * guest name instead of going blank, so "Welcome, {username}!" reads
     * naturally for a visitor too. Tags that only make sense for a real
     * account ({email}, {website}, {user_registered}) stay blank.
     */
    private function get_guest_merge_tags() {
        $guest_name = $this->get_default_guest_name();
        return [
            '{name}'            => $guest_name,
            '{first_name}'      => $guest_name,
            '{last_name}'       => $guest_name,
            '{display_name}'    => $guest_name,
            '{username}'        => $guest_name,
            '{nickname}'        => $guest_name,
            '{email}'           => '',
            '{website}'         => '',
            '{user_registered}' => '',
        ];
    }

    /**
     * Replace bare {display_name} / {username} / etc. directly inside post
     * and page content (paragraphs, headings — anywhere in the block editor).
     * Only this fixed whitelist of tags is touched, so it can't clash with
     * curly braces used by other plugins or in code samples.
     */
    public function replace_user_data_tags($content) {
        if (!is_string($content) || strpos($content, '{') === false) {
            return $content;
        }

        $known_tags = ['{name}', '{first_name}', '{last_name}', '{display_name}', '{username}', '{email}', '{nickname}', '{website}', '{user_registered}'];

        $present = array_filter($known_tags, function ($tag) use ($content) {
            return strpos($content, $tag) !== false;
        });
        if (empty($present)) {
            return $content;
        }

        if (!is_user_logged_in()) {
            $guest_tags   = $this->get_guest_merge_tags();
            $replacements = array_map(function ($tag) use ($guest_tags) {
                return $guest_tags[$tag];
            }, $present);
            return str_replace($present, $replacements, $content);
        }

        $tags = $this->get_user_merge_tags(wp_get_current_user());

        return str_replace(array_keys($tags), array_map('esc_html', array_values($tags)), $content);
    }

    // ── [my_login_user_data field="first_name"] ───────────────
    public function shortcode_user_data($atts) {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            return \MyLoginForm\Licensing\Gate::gate_notice_html();
        }

        $atts = shortcode_atts([
            'field'   => 'display_name',
            'default' => '',
            'format'  => '',   // ucfirst | upper | lower
        ], $atts, 'my_login_user_data');

        if (!is_user_logged_in()) return esc_html($atts['default']);

        $user = wp_get_current_user();
        // 'role' is intentionally not exposed here — role is never
        // user/visitor-facing in this plugin; only an admin (manage_options)
        // can view or change it, from wp-admin.
        $allowed = [
            'first_name', 'last_name', 'display_name', 'username',
            'email', 'nickname', 'description', 'website',
            'user_registered',
        ];

        $field = sanitize_key($atts['field']);
        if (!in_array($field, $allowed, true)) return '';

        $value = '';
        switch ($field) {
            case 'first_name':    $value = get_user_meta($user->ID, 'first_name', true); break;
            case 'last_name':     $value = get_user_meta($user->ID, 'last_name',  true); break;
            case 'nickname':      $value = get_user_meta($user->ID, 'nickname',   true); break;
            case 'description':   $value = get_user_meta($user->ID, 'description', true); break;
            case 'display_name':  $value = $user->display_name; break;
            case 'username':      $value = $user->user_login;   break;
            case 'email':         $value = $user->user_email;   break;
            case 'website':       $value = $user->user_url;     break;
            case 'user_registered': $value = mysql2date(get_option('date_format'), $user->user_registered); break;
        }

        if (empty($value)) $value = $atts['default'];

        switch ($atts['format']) {
            case 'ucfirst': $value = ucfirst($value); break;
            case 'upper':   $value = strtoupper($value); break;
            case 'lower':   $value = strtolower($value); break;
        }

        return esc_html($value);
    }

    // ── [my_login_greeting] ───────────────────────────────────
    // Supports the same merge tags as [my_login_user_data field="..."], e.g.
    // [my_login_greeting logged_in_text="Hey, {username}!"]
    public function shortcode_greeting($atts) {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            return \MyLoginForm\Licensing\Gate::gate_notice_html();
        }

        $atts = shortcode_atts([
            'logged_in_text'  => 'Hello, {name}!',
            'logged_out_text' => '',
        ], $atts, 'my_login_greeting');

        if (!is_user_logged_in()) {
            $tags = $this->get_guest_merge_tags();
            $greeting = str_replace(array_keys($tags), array_values($tags), $atts['logged_out_text']);
            return wp_kses_post($greeting);
        }

        $tags = $this->get_user_merge_tags(wp_get_current_user());
        $greeting = str_replace(array_keys($tags), array_map('esc_html', array_values($tags)), $atts['logged_in_text']);

        return wp_kses_post($greeting);
    }

    // ── [my_login_if_logged_in]...[/my_login_if_logged_in] ───
    public function shortcode_if_logged_in($atts, $content = '') {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            return \MyLoginForm\Licensing\Gate::gate_notice_html();
        }
        return is_user_logged_in() ? do_shortcode($content) : '';
    }

    // ── [my_login_if_logged_out]...[/my_login_if_logged_out] ─
    public function shortcode_if_logged_out($atts, $content = '') {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            return \MyLoginForm\Licensing\Gate::gate_notice_html();
        }
        return !is_user_logged_in() ? do_shortcode($content) : '';
    }
    
    public function render_form($atts) {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            return \MyLoginForm\Licensing\Gate::gate_notice_html();
        }

        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'key' => '',
            'type' => 'login',
        ), $atts, 'my_login_form');
        
        // Get form by ID or key from database directly
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
        
        if (!empty($atts['id'])) {
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND status = 'active'", intval($atts['id'])));
        } elseif (!empty($atts['key'])) {
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE form_key = %s AND status = 'active'", sanitize_text_field($atts['key'])));
        } else {
            // Get default form by type
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE form_type = %s AND status = 'active' LIMIT 1", sanitize_text_field($atts['type'])));
        }
        
        if (!$form) {
            return '<div class="my-login-form-error">' . __('Form not found. Please check the shortcode ID or key.', 'my-login-form') . '</div>';
        }
        
        // Start output buffering
        ob_start();
        
        // Render the form
        $this->render_form_html($form);
        
        return ob_get_clean();
    }
    
    /**
     * Render form HTML - Uses saved containers (drag-drop) or legacy fields
     */
    private function render_form_html($form) {
        // Decode saved fields and settings
        $fields     = json_decode($form->fields, true);
        $settings   = json_decode($form->settings, true);
        $containers = !empty($form->form_containers) ? json_decode($form->form_containers, true) : null;
        
        // Ensure arrays
        if (!is_array($fields))   $fields   = array();
        if (!is_array($settings)) $settings = array();

        // If drag-drop containers exist and have content, render from them
        if (!empty($containers) && is_array($containers) && count($containers) > 0) {
            $this->enqueue_form_assets($form);
            echo $this->render_from_containers($form, $containers, $settings);
            return;
        }
        
        // Generate unique form ID
        $form_instance_id = 'my-login-form-' . $form->id . '-' . uniqid();
        
        // Enqueue assets
        $this->enqueue_form_assets($form);
        
        // Extract settings with defaults
        $show_labels = isset($settings['show_labels']) ? (bool)$settings['show_labels'] : true;
        $button_text = isset($settings['button_text']) ? $settings['button_text'] : __('Submit', 'my-login-form');
        $button_class = isset($settings['button_class']) ? $settings['button_class'] : 'button button-primary';
        $bg_color = isset($settings['bg_color']) ? $settings['bg_color'] : '#ffffff';
        $text_color = isset($settings['text_color']) ? $settings['text_color'] : '#2A2A2A';
        $btn_color = isset($settings['btn_color']) ? $settings['btn_color'] : '#1FBB00';
        $border_color = isset($settings['border_color']) ? $settings['border_color'] : '#DCE8D6';
        $social_providers = isset($settings['social_providers']) ? $settings['social_providers'] : array();
        $show_remember_me = isset($settings['show_remember_me']) ? (bool)$settings['show_remember_me'] : false;
        $enable_password_strength = isset($settings['enable_password_strength']) ? (bool)$settings['enable_password_strength'] : false;
        $redirect_after_login = isset($settings['redirect_after_login']) ? $settings['redirect_after_login'] : '';
        $custom_redirect_url = isset($settings['custom_redirect_url']) ? $settings['custom_redirect_url'] : '';
        
        ?>
        
        <style>
            .my-login-form-<?php echo $form->id; ?> {
                background-color: <?php echo esc_attr($bg_color); ?>;
                color: <?php echo esc_attr($text_color); ?>;
                border: 1px solid <?php echo esc_attr($border_color); ?>;
                padding: 20px;
                border-radius: 8px;
                max-width: 500px;
                margin: 20px auto;
            }
            .my-login-form-<?php echo $form->id; ?> .form-group {
                margin-bottom: 15px;
            }
            .my-login-form-<?php echo $form->id; ?> label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                color: <?php echo esc_attr($text_color); ?>;
            }
            .my-login-form-<?php echo $form->id; ?> .required {
                color: #dc3545;
                margin-left: 4px;
            }
            .my-login-form-<?php echo $form->id; ?> input:not([type="checkbox"]),
            .my-login-form-<?php echo $form->id; ?> select,
            .my-login-form-<?php echo $form->id; ?> textarea {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid <?php echo esc_attr($border_color); ?>;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .my-login-form-<?php echo $form->id; ?> .my-login-password-wrap {
                position: relative;
            }
            .my-login-form-<?php echo $form->id; ?> .my-login-password-wrap input {
                padding-right: 40px;
            }
            .my-login-form-<?php echo $form->id; ?> .my-login-password-toggle {
                position: absolute;
                top: 50%;
                right: 10px;
                transform: translateY(-50%);
                background: none;
                border: none;
                padding: 4px;
                margin: 0;
                cursor: pointer;
                color: #666;
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }
            .my-login-form-<?php echo $form->id; ?> button[type="submit"] {
                background-color: <?php echo esc_attr($btn_color); ?>;
                color: #ffffff;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
                font-size: 16px;
            }
            .my-login-form-<?php echo $form->id; ?> button[type="submit"]:hover {
                opacity: 0.9;
            }
            .my-login-form-<?php echo $form->id; ?> .checkbox-label {
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            }
            .my-login-form-<?php echo $form->id; ?> .form-footer {
                margin-top: 20px;
                text-align: center;
            }
            .my-login-form-<?php echo $form->id; ?> .form-footer a {
                color: <?php echo esc_attr($btn_color); ?>;
                text-decoration: none;
            }
            .my-login-form-<?php echo $form->id; ?> .message {
                padding: 10px;
                margin-bottom: 15px;
                border-radius: 4px;
            }
            .my-login-form-<?php echo $form->id; ?> .message.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .my-login-form-<?php echo $form->id; ?> .message.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .social-login-section {
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid <?php echo esc_attr($border_color); ?>;
            }
            .social-login-title {
                text-align: center;
                margin-bottom: 15px;
                color: #666;
                font-size: 14px;
            }
            .social-buttons {
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .social-button {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                color: white;
                cursor: pointer;
                font-size: 14px;
                transition: opacity 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .social-button:hover {
                opacity: 0.9;
            }
            <?php if (!empty($form->css_file)): ?>
            /* Custom CSS file loaded separately */
            <?php endif; ?>
        </style>
        
        <div class="my-login-form-container my-login-form-<?php echo $form->id; ?>" data-form-id="<?php echo $form->id; ?>">
            
            <?php if (!empty($form->html_file)): ?>
                <div class="my-login-form-custom-html">
                    <?php 
                    $html_path = MY_LOGIN_FORM_DIR . 'Public/Forms/html/' . $form->html_file;
                    if (file_exists($html_path)) {
                        include $html_path;
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div id="form-message-<?php echo $form->id; ?>" class="message" style="display: none;"></div>
            
            <form method="post" class="my-login-form" data-form-id="<?php echo $form->id; ?>">
                <?php wp_nonce_field('my_login_form_submit', 'my_login_form_nonce'); ?>
                <input type="hidden" name="action" value="my_login_form_submit">
                <input type="hidden" name="form_id" value="<?php echo $form->id; ?>">
                <input type="hidden" name="form_type" value="<?php echo esc_attr($form->form_type); ?>">
                <?php if (!empty($redirect_after_login)): ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_after_login === 'custom' ? $custom_redirect_url : $redirect_after_login); ?>">
                <?php endif; ?>
                
                <?php if (!empty($fields)): ?>
                    <?php foreach ($fields as $field_name => $field_config): 
                        // Handle field configuration
                        if (is_array($field_config)) {
                            $field_type = isset($field_config['type']) ? $field_config['type'] : 'text';
                            $field_label = isset($field_config['label']) ? $field_config['label'] : ucfirst(str_replace('_', ' ', $field_name));
                            $field_required = isset($field_config['required']) ? (bool)$field_config['required'] : false;
                            $field_placeholder = isset($field_config['placeholder']) ? $field_config['placeholder'] : '';
                            $field_class = isset($field_config['class']) ? $field_config['class'] : '';
                        } else {
                            // Simple string format
                            $field_type = 'text';
                            $field_label = ucfirst(str_replace('_', ' ', $field_name));
                            $field_required = false;
                            $field_placeholder = '';
                            $field_class = '';
                        }
                    ?>
                        <div class="form-group my-login-form-group-<?php echo esc_attr($field_name); ?>">
                            <?php if ($show_labels && $field_type !== 'checkbox'): ?>
                                <label for="field_<?php echo esc_attr($field_name); ?>">
                                    <?php echo esc_html($field_label); ?>
                                    <?php if ($field_required): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                            <?php endif; ?>
                            
                            <?php if ($field_type === 'textarea'): ?>
                                <textarea id="field_<?php echo esc_attr($field_name); ?>"
                                          name="<?php echo esc_attr($field_name); ?>"
                                          class="<?php echo esc_attr($field_class); ?>"
                                          placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                          rows="4"
                                          <?php echo $field_required ? 'required' : ''; ?>></textarea>
                                
                            <?php elseif ($field_type === 'checkbox'): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox"
                                           name="<?php echo esc_attr($field_name); ?>"
                                           class="<?php echo esc_attr($field_class); ?>"
                                           value="1"
                                           <?php echo $field_required ? 'required' : ''; ?>>
                                    <?php echo esc_html($field_label); ?>
                                    <?php if ($field_required): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                
                            <?php elseif ($field_type === 'select'): ?>
                                <select id="field_<?php echo esc_attr($field_name); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($field_class); ?>"
                                        <?php echo $field_required ? 'required' : ''; ?>>
                                    <option value=""><?php _e('Select', 'my-login-form'); ?></option>
                                    <option value="option1">Option 1</option>
                                    <option value="option2">Option 2</option>
                                </select>
                                
                            <?php elseif ($field_type === 'password'): ?>
                                <div class="my-login-password-wrap">
                                    <input type="password"
                                           id="field_<?php echo esc_attr($field_name); ?>"
                                           name="<?php echo esc_attr($field_name); ?>"
                                           class="<?php echo esc_attr($field_class); ?>"
                                           placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                           <?php echo $field_required ? 'required' : ''; ?>>
                                    <button type="button" class="my-login-password-toggle" aria-label="<?php esc_attr_e('Show password', 'my-login-form'); ?>" aria-pressed="false" tabindex="-1"><i class="fas fa-eye"></i></button>
                                </div>

                            <?php else: ?>
                                <input type="<?php echo esc_attr($field_type); ?>"
                                       id="field_<?php echo esc_attr($field_name); ?>"
                                       name="<?php echo esc_attr($field_name); ?>"
                                       class="<?php echo esc_attr($field_class); ?>"
                                       placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                       <?php echo $field_required ? 'required' : ''; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="form-group">
                        <p style="color: #856404; background-color: #fff3cd; padding: 10px; border-radius: 4px;">
                            <?php _e('No fields configured for this form. Please go to Form Designer, select this form, and add some fields.', 'my-login-form'); ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_remember_me && $form->form_type === 'login'): ?>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" value="1">
                        <?php _e('Remember Me', 'my-login-form'); ?>
                    </label>
                </div>
                <?php endif; ?>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="<?php echo esc_attr($button_class); ?>">
                        <?php echo esc_html($button_text); ?>
                    </button>
                </div>
            </form>
            
            <!-- SOCIAL LOGIN SECTION - Moved outside form but still in container -->
            <?php if (!empty($social_providers)): ?>
            <div class="social-login-section">
                <div class="social-login-title">
                    <?php _e('Or continue with', 'my-login-form'); ?>
                </div>
                <div class="social-buttons">
                    <?php foreach ($social_providers as $provider): 
                        $provider = strtolower(trim($provider));
                    ?>
                        <button type="button" class="social-button social-<?php echo esc_attr($provider); ?>"
                                data-provider="<?php echo esc_attr($provider); ?>"
                                style="background: <?php echo esc_attr($this->get_provider_color($provider)); ?>;">
                            <i class="fab fa-<?php echo esc_attr($this->get_social_icon($provider)); ?>"></i>
                            <?php echo ucfirst($provider); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($form->form_type === 'login'): ?>
            <div class="form-footer" style="margin-top: 20px; text-align: center;">
                <a href="<?php echo esc_url(my_login_form_lostpassword_url()); ?>">
                    <?php _e('Lost your password?', 'my-login-form'); ?>
                </a>
                <?php // Same resolution AuthAjax::handle_register() uses — the
                // plugin's own toggle wins if set, falling back to core WP's
                // "Anyone can register" only when the plugin has no opinion. ?>
                <?php if (get_option('my_login_form_allow_registration', get_option('users_can_register'))): ?>
                    <span style="margin: 0 10px;">|</span>
                    <a href="<?php echo esc_url(my_login_form_registration_url()); ?>">
                        <?php _e('Register', 'my-login-form'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($form->js_file)): ?>
            <!-- Custom JS file loaded separately -->
        <?php endif; ?>
        
        <script>
        jQuery(document).ready(function($) {
            $('.my-login-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $container = $form.closest('.my-login-form-container');
                var $message = $container.find('.message');
                var formData = $form.serialize();
                
                $message.removeClass('error success').hide();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $message.addClass('success').html(response.data.message).show();
                            if (response.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect;
                                }, 2000);
                            } else if (response.data.clear_form) {
                                $form[0].reset();
                            }
                        } else {
                            $message.addClass('error').html(response.data).show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $message.addClass('error').html('An error occurred. Please try again.').show();
                        console.error('AJAX Error:', error);
                    }
                });
            });
            
            $('.social-button').on('click', function() {
                var provider = $(this).data('provider');
                window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=my_login_social_login&provider=' + provider;
            });

            $('.my-login-password-toggle').on('click', function() {
                var $btn   = $(this);
                var $input = $btn.siblings('input');
                var toText = $input.attr('type') === 'password';
                $input.attr('type', toText ? 'text' : 'password');
                $btn.find('i').toggleClass('fa-eye fa-eye-slash');
                $btn.attr('aria-pressed', toText ? 'true' : 'false');
                $btn.attr('aria-label', toText ? '<?php echo esc_js(__('Hide password', 'my-login-form')); ?>' : '<?php echo esc_js(__('Show password', 'my-login-form')); ?>');
            });
        });
        </script>
        
        <?php
    }
    
    /**
     * Enqueue form assets (CSS/JS from Public/Forms folders)
     */
    private function enqueue_form_assets($form) {
        // Font Awesome — already enqueued by Hooks::my_login_form_enqueue_assets() in
        // wp_enqueue_scripts (before wp_head). Register here as a safety net for
        // placements that bypass that hook (e.g. some page builders).
        if ( ! wp_style_is( 'my-login-form-font-awesome', 'enqueued' ) ) {
            wp_enqueue_style(
                'my-login-form-font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
                array(),
                '6.5.0'
            );
        }

        // CSS — normally already enqueued in <head> by Hooks.php's pre-scan, in
        // which case this is a no-op (wp_style_is() below is already true).
        // Only reached for real when the shortcode wasn't in $post->post_content
        // for the pre-scan to find (widget areas, page builders storing it in
        // their own postmeta) — Hooks.php no longer falls back to loading every
        // form's CSS "just in case", so this is the actual fallback now. By the
        // time a shortcode renders, wp_head has always already fired, so
        // wp_enqueue_style() here would silently never reach <head>; print the
        // tag directly instead so this specific form's CSS still loads.
        if ( ! empty( $form->css_file ) ) {
            $handle = 'my-login-form-css-' . $form->id;
            if ( ! wp_style_is( $handle, 'enqueued' ) && ! wp_style_is( $handle, 'done' ) ) {
                $css_path = MY_LOGIN_FORM_DIR . 'Public/Forms/css/' . $form->css_file;
                if ( file_exists( $css_path ) && filesize( $css_path ) > 0 ) {
                    $css_url = MY_LOGIN_FORM_URL . 'Public/Forms/css/' . $form->css_file . '?ver=' . filemtime( $css_path );
                    if ( did_action( 'wp_head' ) ) {
                        printf(
                            '<link rel="stylesheet" id="%1$s-css" href="%2$s" media="all">' . "\n",
                            esc_attr( $handle ),
                            esc_url( $css_url )
                        );
                    } else {
                        wp_enqueue_style( $handle, MY_LOGIN_FORM_URL . 'Public/Forms/css/' . $form->css_file, array(), filemtime( $css_path ) );
                    }
                }
            }
        }

        // JS — enqueued in footer (in_footer=true), so calling from a shortcode
        // (after wp_head) is fine; it will be printed by wp_footer().
        if ( ! empty( $form->js_file ) ) {
            $js_path = MY_LOGIN_FORM_DIR . 'Public/Forms/js/' . $form->js_file;
            if ( file_exists( $js_path ) && filesize( $js_path ) > 0 ) {
                wp_enqueue_script(
                    'my-login-form-js-' . $form->id,
                    MY_LOGIN_FORM_URL . 'Public/Forms/js/' . $form->js_file,
                    array( 'jquery' ),
                    filemtime( $js_path ),
                    true
                );
            }
        }

        wp_localize_script( 'jquery', 'my_login_form_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'my_login_form_nonce' ),
        ) );
    }
    
    /**
     * Get provider color for social buttons
     */
    private function get_provider_color($provider) {
        $colors = array(
            'google' => '#DB4437',
            'facebook' => '#4267B2',
            'twitter' => '#1DA1F2',
            'github' => '#333333',
            'linkedin' => '#0077B5',
            'instagram' => '#E4405F',
            'microsoft' => '#00A4EF',
            'apple' => '#000000',
            'amazon' => '#FF9900',
        );
        return isset($colors[$provider]) ? $colors[$provider] : '#666666';
    }
    
    /**
     * Get social icon class
     */
    private function get_social_icon($provider) {
        $icons = array(
            'google' => 'google',
            'facebook' => 'facebook-f',
            'twitter' => 'twitter',
            'github' => 'github',
            'linkedin' => 'linkedin-in',
            'instagram' => 'instagram',
            'microsoft' => 'windows',
            'apple' => 'apple',
            'amazon' => 'amazon',
        );
        return isset($icons[$provider]) ? $icons[$provider] : $provider;
    }

    // ============================================
    // RENDER FROM DRAG-DROP CONTAINERS
    // ============================================

    /**
     * Render form from saved drag-drop container structure
     */

    // ============================================================
    // RENDER FROM DRAG-DROP CONTAINERS (frontend shortcode output)
    // ============================================================

    private function get_social_providers_map() {
        return array(
            'google'    => array('label' => 'Google',     'color' => '#DB4437'),
            'facebook'  => array('label' => 'Facebook',   'color' => '#4267B2'),
            'twitter'   => array('label' => 'Twitter',    'color' => '#1DA1F2'),
            'github'    => array('label' => 'GitHub',     'color' => '#333333'),
            'linkedin'  => array('label' => 'LinkedIn',   'color' => '#0077B5'),
            'apple'     => array('label' => 'Apple',      'color' => '#000000'),
            'microsoft' => array('label' => 'Microsoft',  'color' => '#00A4EF'),
        );
    }

    /**
     * The Plugin Settings page (Admin\Settings) stores its redirect option
     * using an underscore ("my_profile"); the Designer's per-form setting and
     * resolve_redirect_url() both use a hyphen ("my-profile"). Normalize here
     * rather than pick one and silently break the other's stored values.
     */
    private function map_global_redirect_key($value) {
        return 'my_profile' === $value ? 'my-profile' : $value;
    }

    /**
     * Turn a Designer "Redirect After ..." keyword into an actual URL.
     * Read by AuthAjax::handle_login()/handle_register() via the hidden
     * redirect_to field this class emits in render_from_containers().
     */
    private function resolve_redirect_url($keyword, $custom_url = '') {
        switch ($keyword) {
            case 'custom':
                return $custom_url ? esc_url_raw($custom_url) : '';
            case 'my-profile':
                // These forms are for front-end customers/subscribers, not
                // wp-admin users — never send them to wp-admin/profile.php.
                // WooCommerce's My Account page if available; otherwise there's
                // no non-admin profile page in core WP, so just go home.
                if (function_exists('wc_get_page_permalink')) {
                    $url = wc_get_page_permalink('myaccount');
                    if ($url) {
                        return $url;
                    }
                }
                return home_url();
            case 'login':
                $url = $this->find_login_page_url();
                return $url ? $url : home_url();
            case 'dashboard': // legacy value, no longer offered in the UI
            case 'home':
            default:
                return home_url();
        }
    }

    /**
     * Find a published page that actually embeds a login-type form, for the
     * "Login Page" redirect option. This plugin has no fixed login URL (forms
     * are shortcode-embedded wherever an admin puts them), so the only
     * reliable way to find "the" login page is to look for one that uses it.
     * Cached briefly since this runs on every registration, not every page load.
     */
    private function find_login_page_url() {
        // Fast path: Activator::create_default_pages() tracks the page it
        // created (or adopted) on activation — use it directly if it's still
        // a real, non-trashed page.
        $tracked_id = (int) get_option( 'my_login_form_login_page_id' );
        if ( $tracked_id ) {
            $tracked = get_post( $tracked_id );
            if ( $tracked && 'page' === $tracked->post_type && 'trash' !== $tracked->post_status ) {
                return get_permalink( $tracked_id );
            }
        }

        $cached = get_transient( 'my_login_form_login_page_url' );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
        $login_forms = $wpdb->get_results( "SELECT id, form_key FROM $table WHERE form_type = 'login' AND status = 'active'" );

        $url = '';
        foreach ( $login_forms as $lf ) {
            $post_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND (post_content LIKE %s OR post_content LIKE %s) LIMIT 1",
                '%[my_login_form id="' . $lf->id . '"%',
                '%[my_login_form key="' . $lf->form_key . '"%'
            ) );
            if ( $post_id ) {
                $url = get_permalink( $post_id );
                break;
            }
        }

        set_transient( 'my_login_form_login_page_url', $url, HOUR_IN_SECONDS );
        return $url;
    }

    private function render_from_containers($form, $containers, $settings) {
        $form_id      = (int) $form->id;
        $btn_color    = isset($settings['btn_color'])    ? $settings['btn_color']    : '#1FBB00';
        $button_text  = isset($settings['button_text'])  ? $settings['button_text']  : __('Submit', 'my-login-form');
        $show_labels  = !empty($settings['show_labels']);
        $border_color = isset($settings['border_color']) ? $settings['border_color'] : '#DCE8D6';

        // Where AuthAjax::handle_login()/handle_register() should send the visitor
        // afterwards. Per-form setting (Designer → Settings tab → "Default
        // Redirect After Login" / "Redirect After Create New Account") wins;
        // falls back to the site-wide default (Plugin Settings page) when the
        // form has no explicit choice of its own. Emitted as a hidden field the
        // AJAX handlers already read ($_POST['redirect_to']).
        if ( 'register' === $form->form_type ) {
            $redirect_key = $settings['redirect_after_registration']
                ?? $this->map_global_redirect_key( get_option( 'my_login_form_default_redirect_registration', 'my_profile' ) );
            $custom_url   = $settings['custom_redirect_url'] ?? get_option( 'my_login_form_default_redirect_registration_url', '' );
        } else {
            $redirect_key = $settings['redirect_after_login']
                ?? $this->map_global_redirect_key( get_option( 'my_login_form_default_redirect', 'my_profile' ) );
            $custom_url   = $settings['custom_redirect_url'] ?? get_option( 'my_login_form_default_redirect_url', '' );
        }
        $redirect_url = $this->resolve_redirect_url( $redirect_key, $custom_url );

        // Task 2 (WooCommerce integration): a `redirect_to` query param on
        // this page's own URL wins over the Designer's configured default.
        // Includes/Integrations/Woocommerce.php sends logged-out visitors
        // here from My Account, checkout, and wp_login_url() calls with
        // `redirect_to` set to where they should land after logging in;
        // it's fed into the hidden field below, and AuthAjax::handle_login()
        // reads it back out of $_POST['redirect_to'] once they submit.
        // wp_validate_redirect() falls back to the Designer's URL if the
        // query param is missing, empty, or points off-site.
        if ( 'login' === $form->form_type && ! empty( $_GET['redirect_to'] ) ) {
            $redirect_url = wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ), $redirect_url );
        }

        // CSS and JS are enqueued in <head> by Hooks::my_login_form_enqueue_assets()
        // (hooked to wp_enqueue_scripts, which runs before wp_head).
        // No inline file-reading needed here.

        // Social login: only enabled providers when Supabase is connected
        $supabase_on   = get_option('my_login_supabase_enabled', 0)
                         && get_option('my_login_supabase_url', '')
                         && get_option('my_login_supabase_anon_key', '');
        $provider_map  = $this->get_social_providers_map();
        $enabled_map   = array();
        foreach ($provider_map as $key => $p) {
            $opt = get_option('my_login_' . $key . '_login_enabled', 0);
            if (!$supabase_on || $opt) {
                $enabled_map[$key] = $p;   // show all when supabase not set up yet
            }
        }
        // Same fallback the Designer palette already applies (Admin/Pages/designer.php):
        // right after connecting Supabase, no per-provider toggle has been saved yet
        // (Admin\Tabs "Social Login Providers" defaults every option to 0), so without
        // this the buttons an admin already dragged into a saved form would vanish on
        // the front end the moment Supabase gets connected. Show everything until the
        // admin explicitly curates the list on the Social tab.
        if ($supabase_on && empty($enabled_map)) {
            $enabled_map = $provider_map;
        }

        // Only render the OTP-entry step when Supabase is actually configured —
        // that's the only way a code can ever be sent, so there's nothing for
        // this markup/JS to do on sites that haven't connected it. Covers all
        // three OTP-gated flows — AuthAjax::handle_verify_otp() branches on
        // the 'context' hidden field below to know which one this is.
        $otp_capable = $supabase_on && in_array($form->form_type, ['login', 'register', 'forgot_password'], true);

        // Reset Password page: the visitor is identified by the key/login pair
        // AuthAjax::handle_forgot_password() put in the emailed link, read here
        // from the URL and passed through as hidden fields — no email field on
        // this form at all. Without both, there's nothing valid to submit.
        $is_reset_password = ('reset_password' === $form->form_type);
        $reset_key         = $is_reset_password && isset($_GET['key'])   ? sanitize_text_field(wp_unslash($_GET['key']))   : '';
        $reset_login       = $is_reset_password && isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';
        $reset_link_valid  = !$is_reset_password || ($reset_key !== '' && $reset_login !== '');

        ob_start();
        ?>
        <style>
            #my-login-wrap-<?php echo $form_id; ?> .my-login-password-wrap {
                position: relative;
            }
            #my-login-wrap-<?php echo $form_id; ?> .my-login-password-wrap input[type="password"],
            #my-login-wrap-<?php echo $form_id; ?> .my-login-password-wrap input[type="text"] {
                padding-right: 40px;
                box-sizing: border-box;
            }
            #my-login-wrap-<?php echo $form_id; ?> .my-login-password-toggle {
                position: absolute;
                top: 50%;
                right: 10px;
                transform: translateY(-50%);
                background: none;
                border: none;
                padding: 4px;
                margin: 0;
                cursor: pointer;
                color: #6B8064;
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }
            #my-login-wrap-<?php echo $form_id; ?> .my-login-password-toggle:hover {
                color: #2A2A2A;
            }
        </style>
        <?php if ($otp_capable): ?>
        <style>
            #my-login-otp-<?php echo $form_id; ?> { display: none; }
            #my-login-otp-<?php echo $form_id; ?> input[name="token"] {
                letter-spacing: 4px; font-size: 18px; text-align: center;
            }
            #my-login-otp-<?php echo $form_id; ?> .my-login-otp-resend {
                background: none; border: none; padding: 0; margin-top: 8px;
                color: var(--mlf-link, #1FBB00); text-decoration: underline;
                cursor: pointer; font-size: 13px;
            }
            #my-login-otp-<?php echo $form_id; ?> .my-login-otp-resend:disabled {
                opacity: 0.6; cursor: not-allowed; text-decoration: none;
            }
        </style>
        <?php endif; ?>
        <div class="my-login-wrap my-login-wrap-<?php echo $form_id; ?>" id="my-login-wrap-<?php echo $form_id; ?>">
            <div class="my-login-msg" id="my-login-msg-<?php echo $form_id; ?>"></div>

            <?php if (!$reset_link_valid): ?>
            <?php
            // No key/login in the URL at all — either just redirected here
            // from a successful Forgot Password submission (AuthAjax::handle_forgot_password()
            // sends visitors here while the real, keyed link goes to their
            // inbox), or the page was opened directly/bookmarked. A genuinely
            // invalid/expired/tampered link still carries *some* key+login
            // values (just wrong ones) and is instead caught on submit by
            // check_password_reset_key() in handle_reset_password() — so this
            // branch never actually means "expired", only "no link followed yet".
            ?>
            <div class="my-login-msg success" style="display:block;">
                <?php _e('Check your email for the password reset link we just sent.', 'my-login-form'); ?>
                <?php $forgot_page_id = (int) get_option('my_login_form_forgot_password_page_id'); if ($forgot_page_id) : ?>
                    <a href="<?php echo esc_url(get_permalink($forgot_page_id)); ?>"><?php _e('Request a new one', 'my-login-form'); ?></a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <form class="my-login-form" data-form-id="<?php echo $form_id; ?>" novalidate>
                <?php wp_nonce_field('my_login_form_submit', 'my_login_form_nonce'); ?>
                <input type="hidden" name="action"    value="my_login_form_submit">
                <input type="hidden" name="form_id"   value="<?php echo $form_id; ?>">
                <input type="hidden" name="form_type" value="<?php echo esc_attr($form->form_type); ?>">
                <?php if ($redirect_url): ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>">
                <?php endif; ?>
                <?php if ($is_reset_password): ?>
                <input type="hidden" name="key"   value="<?php echo esc_attr($reset_key); ?>">
                <input type="hidden" name="login" value="<?php echo esc_attr($reset_login); ?>">
                <?php endif; ?>

                <?php foreach ($containers as $container):
                    // All Layout/Customization styling for this container now lives
                    // in the generated CSS file (DesignerAjax::build_containers_css()),
                    // scoped to this stable per-element class — no style="" here.
                    $c_eid   = isset($container['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $container['id']) : '';
                    $c_class = 'my-login-main-container' . ($c_eid ? ' mlf-el-' . $c_eid : '');
                ?>
                    <div class="<?php echo esc_attr($c_class); ?>">
                        <?php echo $this->render_items_html($container['items'] ?? array(), $show_labels, $form_id, $enabled_map, $form->form_type); ?>
                        <div class="my-login-form-submit">
                            <button type="submit" class="my-login-submit-btn"><?php echo esc_html($button_text); ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>
            <?php endif; ?>

            <?php if ($otp_capable): ?>
            <form class="my-login-otp-form" id="my-login-otp-<?php echo $form_id; ?>" novalidate>
                <?php wp_nonce_field('my_login_form_submit', 'my_login_form_nonce'); ?>
                <input type="hidden" name="email" value="">
                <input type="hidden" name="context" value="<?php echo esc_attr($form->form_type); ?>">
                <?php if ($redirect_url): ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>">
                <?php endif; ?>
                <div class="my-login-form-field">
                    <label class="my-login-label"><?php _e('Verification Code', 'my-login-form'); ?></label>
                    <input type="text" name="token" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000" required>
                </div>
                <div class="my-login-form-submit">
                    <button type="submit" class="my-login-submit-btn"><?php _e('Verify Code', 'my-login-form'); ?></button>
                </div>
                <button type="button" class="my-login-otp-resend"><?php _e('Resend code', 'my-login-form'); ?></button>
            </form>
            <?php endif; ?>

            <?php if ($form->form_type === 'login'): ?>
            <div class="my-login-extra-links">
                <a href="<?php echo esc_url(my_login_form_lostpassword_url()); ?>"><?php _e('Lost your password?', 'my-login-form'); ?></a>
                <?php // Same resolution AuthAjax::handle_register() uses — the
                // plugin's own toggle wins if set, falling back to core WP's
                // "Anyone can register" only when the plugin has no opinion. ?>
                <?php if (get_option('my_login_form_allow_registration', get_option('users_can_register'))): ?>
                    &nbsp;|&nbsp;<a href="<?php echo esc_url(my_login_form_registration_url()); ?>"><?php _e('Register', 'my-login-form'); ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <script>
        (function($){
            $(function(){
                var fid     = <?php echo $form_id; ?>;
                var $w      = $('#my-login-wrap-' + fid);
                var $frm    = $w.find('.my-login-form');
                var $msg    = $w.find('#my-login-msg-' + fid);
                var $otpFrm = $w.find('#my-login-otp-' + fid);

                function showOtpStep(email, message){
                    $frm.hide();
                    $otpFrm.find('[name="email"]').val(email);
                    $otpFrm.show();
                    if (message) {
                        $msg.removeClass('error success').addClass('success').html(message).show();
                    }
                }

                $frm.on('submit', function(e){
                    e.preventDefault();
                    $msg.hide().removeClass('error success');

                    // Instant feedback for a "Confirm Password" field, ahead
                    // of the round-trip to AuthAjax's own server-side check.
                    var $pass    = $frm.find('[name="password"]');
                    var $confirm = $frm.find('[name="confirm_password"]');
                    if ($confirm.length && $pass.val() !== $confirm.val()) {
                        $msg.addClass('error').html('<?php _e("Passwords do not match.", "my-login-form"); ?>').show();
                        return;
                    }

                    var $btn = $frm.find('.my-login-submit-btn').prop('disabled', true);
                    $.ajax({
                        url:  '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'POST',
                        data: $frm.serialize(),
                        success: function(r){
                            if (r && r.success) {
                                if (r.data.otp_required) {
                                    showOtpStep(r.data.email, r.data.message);
                                    return;
                                }
                                $msg.addClass('success').html(r.data.message || '<?php _e("Success!", "my-login-form"); ?>').show();
                                if (r.data.redirect) {
                                    setTimeout(function(){ window.location.href = r.data.redirect; }, 1200);
                                }
                            } else if (r && r.data && typeof r.data === 'object') {
                                $msg.addClass('error').html(r.data.message || '<?php _e("An error occurred", "my-login-form"); ?>').show();
                                if (r.data.otp_required) {
                                    showOtpStep(r.data.email, '');
                                }
                            } else {
                                $msg.addClass('error').html((r && r.data) ? r.data : '<?php _e("An error occurred", "my-login-form"); ?>').show();
                            }
                        },
                        error: function(){
                            $msg.addClass('error').html('<?php _e("Network error. Please try again.", "my-login-form"); ?>').show();
                        },
                        complete: function(){ $btn.prop('disabled', false); }
                    });
                });

                $otpFrm.on('submit', function(e){
                    e.preventDefault();
                    $msg.hide().removeClass('error success');
                    var $btn = $otpFrm.find('.my-login-submit-btn').prop('disabled', true);
                    $.ajax({
                        url:  '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'POST',
                        data: $otpFrm.serialize() + '&action=my_login_form_verify_otp',
                        success: function(r){
                            if (r && r.success) {
                                $msg.addClass('success').html(r.data.message || '<?php _e("Success!", "my-login-form"); ?>').show();
                                if (r.data.redirect) {
                                    setTimeout(function(){ window.location.href = r.data.redirect; }, 1200);
                                }
                            } else {
                                $msg.addClass('error').html((r && r.data && r.data.message) ? r.data.message : ((r && r.data) ? r.data : '<?php _e("An error occurred", "my-login-form"); ?>')).show();
                            }
                        },
                        error: function(){
                            $msg.addClass('error').html('<?php _e("Network error. Please try again.", "my-login-form"); ?>').show();
                        },
                        complete: function(){ $btn.prop('disabled', false); }
                    });
                });

                $otpFrm.on('click', '.my-login-otp-resend', function(){
                    var $resend = $(this).prop('disabled', true);
                    $.ajax({
                        url:  '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'POST',
                        data: {
                            action: 'my_login_form_resend_otp',
                            my_login_form_nonce: $otpFrm.find('[name="my_login_form_nonce"]').val(),
                            email: $otpFrm.find('[name="email"]').val(),
                            context: $otpFrm.find('[name="context"]').val()
                        },
                        success: function(r){
                            var text = (r && r.data && r.data.message) ? r.data.message : ((r && r.data) ? r.data : '');
                            if (text) {
                                $msg.removeClass('error success').addClass('success').html(text).show();
                            }
                        },
                        complete: function(){
                            setTimeout(function(){ $resend.prop('disabled', false); }, 15000);
                        }
                    });
                });

                // Social login buttons
                $w.on('click', '.my-login-social-btn', function(){
                    var provider = $(this).data('provider');
                    window.location.href = '<?php echo admin_url("admin-ajax.php"); ?>?action=my_login_social_login&provider=' + provider + '&form_id=' + fid + '&nonce=<?php echo wp_create_nonce("my_login_social_login"); ?>';
                });

                // Show/hide password toggle
                $w.on('click', '.my-login-password-toggle', function(){
                    var $btn   = $(this);
                    var $input = $btn.siblings('input');
                    var toText = $input.attr('type') === 'password';
                    $input.attr('type', toText ? 'text' : 'password');
                    $btn.find('i').toggleClass('fa-eye fa-eye-slash');
                    $btn.attr('aria-pressed', toText ? 'true' : 'false');
                    $btn.attr('aria-label', toText ? '<?php echo esc_js(__('Hide password', 'my-login-form')); ?>' : '<?php echo esc_js(__('Show password', 'my-login-form')); ?>');
                });
            });
        })(jQuery);
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render items array recursively
     */
    private function render_items_html($items, $show_labels, $form_id, $enabled_map, $form_type = '') {
        $html = '';
        foreach ($items as $item) {
            $type = isset($item['type']) ? $item['type'] : '';
            if ($type === 'field') {
                $html .= $this->render_single_field($item, $show_labels, $form_id, $form_type);
            } elseif ($type === 'sub') {
                // Default look comes from the generated .my-login-sub-div base rule;
                // per-sub-div Customization (if any) comes from its own mlf-el-{id}
                // rule in build_containers_css() — no style="" needed either way.
                $eid  = isset($item['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $item['id']) : '';
                $cls  = 'my-login-sub-div' . ($eid ? ' mlf-el-' . $eid : '');
                $kids = $this->render_items_html($item['items'] ?? array(), $show_labels, $form_id, $enabled_map, $form_type);
                $html .= '<div class="' . esc_attr($cls) . '">' . $kids . '</div>';
            } elseif ($type === 'social_section') {
                $html .= $this->render_social_section($item, $enabled_map);
            } elseif ($type === 'greeting') {
                $html .= $this->render_greeting($item);
            }
        }
        return $html;
    }

    /**
     * Render a greeting block (title + subtitle). Mirrors buildGreetingHTML()
     * in Admin/Pages/js/designer.js so the live form matches the Designer preview.
     */
    private function render_greeting($item) {
        $title    = isset($item['title'])    ? $item['title']    : '';
        $subtitle = isset($item['subtitle']) ? $item['subtitle'] : '';
        if ($title === '' && $subtitle === '') return '';

        $s   = is_array($item['styles'] ?? null) ? $item['styles'] : array();
        $eid = isset($item['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $item['id']) : '';
        $cls = 'my-login-greeting' . ($eid ? ' mlf-el-' . $eid : '');

        $inner = '';
        if ($title !== '') {
            $title_style = !empty($s['title_color']) ? ' style="color:' . esc_attr($s['title_color']) . '"' : '';
            $inner .= '<h2 class="my-login-greeting-title"' . $title_style . '>' . esc_html($title) . '</h2>';
        }
        if ($subtitle !== '') {
            $subtitle_style = !empty($s['subtitle_color']) ? ' style="color:' . esc_attr($s['subtitle_color']) . '"' : '';
            $inner .= '<p class="my-login-greeting-subtitle"' . $subtitle_style . '>' . esc_html($subtitle) . '</p>';
        }

        $align = !empty($s['align']) ? $s['align'] : 'center';
        return '<div class="' . esc_attr($cls) . '" style="text-align:' . esc_attr($align) . '">' . $inner . '</div>';
    }

    /**
     * Render a social login section
     */
    private function render_social_section($item, $enabled_map) {
        $btns_html = '';
        $buttons   = isset($item['socialButtons']) ? $item['socialButtons'] : array();
        foreach ($buttons as $btn) {
            $provider = isset($btn['provider']) ? $btn['provider'] : '';
            if (!$provider || !isset($enabled_map[$provider])) continue;
            $p = $enabled_map[$provider];
            $btns_html .= '<button type="button" class="my-login-social-btn" data-provider="' . esc_attr($provider) . '" style="background:' . esc_attr($p['color']) . ';">'
                . esc_html($p['label'])
                . '</button>';
        }
        if (!$btns_html) return '';
        return '<div class="my-login-social-section">'
            . '<div class="my-login-social-divider">' . __('— or continue with —', 'my-login-form') . '</div>'
            . '<div class="my-login-social-buttons">' . $btns_html . '</div>'
            . '</div>';
    }

    /**
     * Render a single field
     */
    private function render_single_field($f, $show_labels, $form_id, $form_type = '') {
        $field_type  = isset($f['fieldType'])   ? $f['fieldType']   : 'text';
        $html_type   = isset($f['htmlType'])     ? $f['htmlType']    : 'text';
        $label       = isset($f['label'])        ? $f['label']       : ucwords(str_replace('_', ' ', $field_type));
        $placeholder = isset($f['placeholder'])  ? $f['placeholder'] : '';
        $required    = !empty($f['required']);
        $req         = $required ? ' required' : '';
        $req_mark    = $required ? '<span class="my-login-req">*</span>' : '';
        $uid         = 'my_login_' . $form_id . '_' . sanitize_key($field_type) . '_' . substr(md5($f['id'] ?? uniqid()), 0, 6);

        // Mirrors AuthAjax::handle_register()/handle_reset_password()'s 8-character
        // minimum — only on forms that SET a new password. Never on the login
        // form's password field: an existing account's real password could
        // predate this rule, and login must never reject a correct password.
        $min_length = ($html_type === 'password' && in_array($form_type, ['register', 'reset_password', 'reset-password'], true))
            ? ' minlength="8"'
            : '';

        // Customization-tab settings (background, border, colors, focus colour,
        // width, ...) are rendered entirely via CSS now — build_containers_css()
        // emits a .mlf-el-{id} rule for this exact field when any of them are
        // set, so no style="" attribute is needed here at all.
        $eid   = isset($f['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $f['id']) : '';
        $field_class = 'my-login-form-field' . ($eid ? ' mlf-el-' . $eid : '');

        $html = '<div class="' . esc_attr($field_class) . '">';

        if ($html_type === 'checkbox') {
            $html .= '<label><input type="checkbox" name="' . esc_attr($field_type) . '" id="' . esc_attr($uid) . '" value="1"' . $req . '> ' . esc_html($label) . $req_mark . '</label>';
        } elseif ($html_type === 'radio') {
            $html .= '<label><input type="radio" name="' . esc_attr($field_type) . '" id="' . esc_attr($uid) . '" value="1"' . $req . '> ' . esc_html($label) . $req_mark . '</label>';
        } elseif ($html_type === 'textarea') {
            $lbl = $show_labels ? '<label class="my-login-label" for="' . esc_attr($uid) . '">' . esc_html($label) . $req_mark . '</label>' : '';
            $html .= $lbl . '<textarea id="' . esc_attr($uid) . '" name="' . esc_attr($field_type) . '" placeholder="' . esc_attr($placeholder) . '" rows="4"' . $req . '></textarea>';
        } elseif ($html_type === 'select') {
            $lbl = $show_labels ? '<label class="my-login-label" for="' . esc_attr($uid) . '">' . esc_html($label) . $req_mark . '</label>' : '';
            $html .= $lbl . '<select id="' . esc_attr($uid) . '" name="' . esc_attr($field_type) . '"' . $req . '>'
                . '<option value="">' . __('Select…', 'my-login-form') . '</option>'
                . '</select>';
        } elseif ($html_type === 'password') {
            $lbl = $show_labels ? '<label class="my-login-label" for="' . esc_attr($uid) . '">' . esc_html($label) . $req_mark . '</label>' : '';
            $html .= $lbl . '<div class="my-login-password-wrap">'
                . '<input type="password" id="' . esc_attr($uid) . '" name="' . esc_attr($field_type) . '" placeholder="' . esc_attr($placeholder) . '"' . $req . $min_length . '>'
                . '<button type="button" class="my-login-password-toggle" aria-label="' . esc_attr__('Show password', 'my-login-form') . '" aria-pressed="false" tabindex="-1"><i class="fas fa-eye"></i></button>'
                . '</div>';
        } else {
            $lbl = $show_labels ? '<label class="my-login-label" for="' . esc_attr($uid) . '">' . esc_html($label) . $req_mark . '</label>' : '';
            $html .= $lbl . '<input type="' . esc_attr($html_type) . '" id="' . esc_attr($uid) . '" name="' . esc_attr($field_type) . '" placeholder="' . esc_attr($placeholder) . '"' . $req . $min_length . '>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get social icon class (legacy, kept for compat)
     */
}
