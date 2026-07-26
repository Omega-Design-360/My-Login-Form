<?php
/**
 * My Login Form - Shortcodes
 *
 * @package MyLoginForm
 */

namespace MyLoginForm\Shortcodes;

// Prevent Direct Access
defined('ABSPATH') || exit;

/**
 * Shortcodes handler class
 */
class Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        // 'my_login_form' is registered by Shortcodes\FormsShortcodes, which reads
        // the form-designer schema; render_form() below is legacy and unused.
        add_shortcode('my_login_profile', array($this, 'render_profile'));
    }
    
    /**
     * Render form shortcode
     */
    public function render_form($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'form_id' => 0,
            'type' => ''
        ), $atts, 'my_login_form');
    
        // Get form ID from either 'id' or 'form_id' attribute
        $form_id = intval($atts['id'] ?: $atts['form_id']);
    
        // If no ID but type is specified, get default form of that type
        if (!$form_id && !empty($atts['type'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'my_login_forms';
            $form = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE form_type = %s AND status = 'active' ORDER BY id LIMIT 1",
                $atts['type']
            ));
        
            if ($form) {
                $form_id = $form->id;
            }
        }
    
        if (!$form_id) {
            return '<p class="my-login-form-error">' . __('Form not found. Please specify a valid form ID.', 'my-login-form') . '</p>';
        }
    
        // Get form data from database
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND status = 'active'", $form_id));
    
        if (!$form) {
            return '<p class="my-login-form-error">' . __('Form not found.', 'my-login-form') . '</p>';
        }
    
        // Parse form data
        $fields = json_decode($form->fields, true) ?: array();
        $settings = json_decode($form->settings, true) ?: array();
    
        // Check if user is logged in for login/register forms
        if (is_user_logged_in() && in_array($form->form_type, array('login', 'register'))) {
            $current_user = wp_get_current_user();
            return $this->render_logged_in_state($current_user);
        }
    
        // Enqueue assets for this specific form
        $this->enqueue_form_assets($form);
    
        // Render form HTML
        ob_start();
        $this->render_form_html($form, $fields, $settings);
        return ob_get_clean();
    }
    
    /**
     * Enqueue form-specific CSS and JS files
     */
    private function enqueue_form_assets($form) {
        $form_id = $form->id;
        $plugin_url = MY_LOGIN_FORM_PLUGIN_URL;
        $plugin_dir = MY_LOGIN_FORM_PLUGIN_DIR;
        
        // Define file paths
        $css_file_path = $plugin_dir . 'public/forms/css/' . $form_id . '.css';
        $js_file_path = $plugin_dir . 'public/forms/js/' . $form_id . '.js';
        
        $css_file_url = $plugin_url . 'public/forms/css/' . $form_id . '.css';
        $js_file_url = $plugin_url . 'public/forms/js/' . $form_id . '.js';
        
        // Enqueue form-specific CSS if file exists
        if (file_exists($css_file_path)) {
            wp_enqueue_style(
                'my-login-form-' . $form_id,
                $css_file_url,
                array(),
                filemtime($css_file_path) // Use file modified time as version
            );
        } else {
            // If no file exists, use the CSS from database as inline style
            if (!empty($form->css)) {
                wp_add_inline_style('wp-block-library', $form->css);
            }
        }
        
        // Enqueue form-specific JS if file exists
        if (file_exists($js_file_path)) {
            wp_enqueue_script(
                'my-login-form-' . $form_id,
                $js_file_url,
                array('jquery'),
                filemtime($js_file_path),
                true
            );
        } else {
            // If no file exists, use the JS from database as inline script
            if (!empty($form->js)) {
                wp_add_inline_script('jquery', $form->js);
            }
        }
        
        // Always enqueue Font Awesome for social icons
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
        
        // Localize script for AJAX
        wp_localize_script('jquery', 'myLoginForm_' . $form_id, array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_login_form_nonce'),
            'form_id' => $form_id,
            'form_type' => $form->form_type,
        ));
        
        // Add form submission handler inline (always include for AJAX)
        wp_add_inline_script('jquery', $this->get_form_submission_script($form_id));
    }
    
    /**
     * Get form submission JavaScript
     */
    private function get_form_submission_script($form_id) {
        ob_start();
        ?>
        jQuery(document).ready(function($) {
            var $form = $('#mlf-form-<?php echo esc_attr($form_id); ?>');
            if ($form.length) {
                var $submitBtn = $form.find('button[type="submit"]');
                var $messageDiv = $form.find('.mlf-form-message');
                var originalText = $submitBtn.text();
                
                $form.on('submit', function(e) {
                    e.preventDefault();
                    
                    $submitBtn.prop('disabled', true).text('<?php _e('Submitting...', 'my-login-form'); ?>');
                    $messageDiv.hide().removeClass('success error');
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: $form.serialize(),
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $messageDiv.addClass('success')
                                    .html(response.data.message || '<?php _e('Form submitted successfully!', 'my-login-form'); ?>')
                                    .show();
                                
                                if (response.data.clear_form) {
                                    $form[0].reset();
                                }
                                
                                if (response.data.redirect) {
                                    setTimeout(function() {
                                        window.location.href = response.data.redirect;
                                    }, 1500);
                                }
                            } else {
                                var errorMsg = response.data && response.data.message ? response.data.message : '<?php _e('Error submitting form. Please try again.', 'my-login-form'); ?>';
                                $messageDiv.addClass('error').html(errorMsg).show();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Form error:', error);
                            $messageDiv.addClass('error')
                                .html('<?php _e('An error occurred. Please try again.', 'my-login-form'); ?>')
                                .show();
                        },
                        complete: function() {
                            $submitBtn.prop('disabled', false).text(originalText);
                            setTimeout(function() {
                                $messageDiv.fadeOut();
                            }, 5000);
                        }
                    });
                });
            }
        });
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render form HTML
     */
    private function render_form_html($form, $fields, $settings) {
        $social_providers = isset($settings['social_providers']) ? $settings['social_providers'] : array();
        $form_id = $form->id;
        $form_type = $form->form_type;
        ?>
        <div class="my-login-form-wrapper my-login-form-<?php echo esc_attr($form_id); ?> my-login-form-type-<?php echo esc_attr($form_type); ?>">
            <?php if (!empty($form->custom_html)): ?>
                <div class="my-login-form-custom-html">
                    <?php echo $form->custom_html; ?>
                </div>
            <?php endif; ?>
            
            <div class="my-login-form-container">
                <form method="post" class="my-login-form" id="mlf-form-<?php echo esc_attr($form_id); ?>" data-form-id="<?php echo esc_attr($form_id); ?>">
                    <?php wp_nonce_field('my_login_form_nonce', 'my_login_form_nonce'); ?>
                    <input type="hidden" name="form_id" value="<?php echo esc_attr($form->id); ?>">
                    <input type="hidden" name="action" value="my_login_form_submit">
                    
                    <?php if (!empty($settings['show_form_title']) && !empty($form->name)): ?>
                        <h3 class="my-login-form-title"><?php echo esc_html($form->name); ?></h3>
                    <?php endif; ?>
                    
                    <div class="my-login-form-fields">
                        <?php foreach ($fields as $field_name => $field): 
                            $required = isset($field['required']) && $field['required'] ? 'required' : '';
                            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
                            $class = isset($field['class']) ? $field['class'] : 'form-control';
                            $field_id = 'field_' . $field_name . '_' . $form_id;
                            $value = isset($_POST[$field_name]) ? esc_attr($_POST[$field_name]) : '';
                        ?>
                            <div class="form-group mlf-field-<?php echo esc_attr($field_name); ?>">
                                <?php if (isset($settings['show_labels']) && $settings['show_labels'] !== false && $field['type'] !== 'checkbox' && $field['type'] !== 'hidden'): ?>
                                    <label for="<?php echo esc_attr($field_id); ?>">
                                        <?php echo esc_html($field['label']); ?>
                                        <?php if ($required): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endif; ?>
                                
                                <?php if ($field['type'] === 'textarea'): ?>
                                    <textarea 
                                        id="<?php echo esc_attr($field_id); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($class); ?>"
                                        placeholder="<?php echo esc_attr($placeholder); ?>"
                                        rows="<?php echo isset($field['rows']) ? $field['rows'] : 4; ?>"
                                        <?php echo $required; ?>
                                    ><?php echo $value; ?></textarea>
                                    
                                <?php elseif ($field['type'] === 'checkbox'): ?>
                                    <label class="checkbox-label">
                                        <input 
                                            type="checkbox"
                                            name="<?php echo esc_attr($field_name); ?>"
                                            class="<?php echo esc_attr($class); ?>"
                                            value="1"
                                            <?php checked($value, '1'); ?>
                                            <?php echo $required; ?>
                                        >
                                        <?php echo esc_html($field['label']); ?>
                                        <?php if ($required): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </label>
                                    
                                <?php elseif ($field['type'] === 'select'): ?>
                                    <select 
                                        id="<?php echo esc_attr($field_id); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($class); ?>"
                                        <?php echo $required; ?>
                                    >
                                        <option value=""><?php _e('Select', 'my-login-form'); ?></option>
                                        <?php if (isset($field['options']) && is_array($field['options'])): ?>
                                            <?php foreach ($field['options'] as $option_value => $option_label): ?>
                                                <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                                                    <?php echo esc_html($option_label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="male" <?php selected($value, 'male'); ?>><?php _e('Male', 'my-login-form'); ?></option>
                                            <option value="female" <?php selected($value, 'female'); ?>><?php _e('Female', 'my-login-form'); ?></option>
                                            <option value="other" <?php selected($value, 'other'); ?>><?php _e('Other', 'my-login-form'); ?></option>
                                        <?php endif; ?>
                                    </select>
                                    
                                <?php elseif ($field['type'] === 'hidden'): ?>
                                    <input 
                                        type="hidden"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        value="<?php echo esc_attr($field['value'] ?? ''); ?>"
                                    >
                                    
                                <?php else: ?>
                                    <input 
                                        type="<?php echo esc_attr($field['type']); ?>"
                                        id="<?php echo esc_attr($field_id); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($class); ?>"
                                        placeholder="<?php echo esc_attr($placeholder); ?>"
                                        value="<?php echo $value; ?>"
                                        <?php echo $required; ?>
                                    >
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($social_providers)): ?>
                        <div class="social-login-section">
                            <div class="social-login-title">
                                <span><?php _e('Or continue with', 'my-login-form'); ?></span>
                            </div>
                            <div class="social-buttons">
                                <?php foreach ($social_providers as $provider): ?>
                                    <button type="button" class="social-button social-<?php echo esc_attr($provider); ?>" 
                                            data-provider="<?php echo esc_attr($provider); ?>">
                                        <i class="fab fa-<?php echo esc_attr($provider); ?>"></i>
                                        <?php echo ucfirst($provider); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($form_type === 'login'): ?>
                        <div class="login-extra-links">
                            <?php if (!empty($settings['show_lost_password'])): ?>
                                <a href="<?php echo esc_url(my_login_form_lostpassword_url()); ?>" class="lost-password-link">
                                    <?php _e('Lost your password?', 'my-login-form'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($settings['show_register_link'])): ?>
                                <a href="<?php echo esc_url(my_login_form_registration_url()); ?>" class="register-link">
                                    <?php _e('Register', 'my-login-form'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-submit">
                        <button type="submit" class="btn btn-primary">
                            <?php echo esc_html($settings['button_text'] ?? __('Submit', 'my-login-form')); ?>
                        </button>
                    </div>
                    
                    <div class="mlf-form-message" style="display: none;"></div>
                </form>
            </div>
        </div>
        
        <style>
            /* Basic styles (fallback if CSS file doesn't load) */
            .my-login-form-wrapper {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .my-login-form-container {
                background: <?php echo esc_attr($settings['bg_color'] ?? '#ffffff'); ?>;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                border: 1px solid <?php echo esc_attr($settings['border_color'] ?? '#e0e0e0'); ?>;
            }
            .my-login-form .form-group {
                margin-bottom: 20px;
            }
            .my-login-form label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: <?php echo esc_attr($settings['text_color'] ?? '#555555'); ?>;
            }
            .my-login-form .required {
                color: #dc3545;
                margin-left: 4px;
            }
            .my-login-form .form-control {
                width: 100%;
                padding: 12px;
                border: 1px solid <?php echo esc_attr($settings['border_color'] ?? '#dddddd'); ?>;
                border-radius: 4px;
                font-size: 14px;
            }
            .my-login-form .btn {
                display: inline-block;
                width: 100%;
                padding: 12px 24px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                font-weight: 500;
            }
            .my-login-form .btn-primary {
                background-color: <?php echo esc_attr($settings['btn_color'] ?? '#0073aa'); ?>;
                color: #ffffff;
            }
            .mlf-form-message {
                margin-top: 20px;
                padding: 12px;
                border-radius: 4px;
                text-align: center;
            }
            .mlf-form-message.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .mlf-form-message.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        </style>
        <?php
    }
    
    /**
     * Render logged in state
     */
    private function render_logged_in_state($user) {
        ob_start();
        ?>
        <div class="my-login-form-logged-in">
            <div class="logged-in-header">
                <i class="fas fa-user-circle"></i>
                <h3><?php echo __('Welcome back,', 'my-login-form') . ' ' . esc_html($user->display_name); ?></h3>
                <p><?php echo __('You are already logged in.', 'my-login-form'); ?></p>
            </div>
            <div class="logged-in-actions">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="button button-logout">
                    <i class="fas fa-sign-out-alt"></i> <?php _e('Logout', 'my-login-form'); ?>
                </a>
                <a href="<?php echo get_edit_profile_url($user->ID); ?>" class="button button-primary">
                    <i class="fas fa-user"></i> <?php _e('My Profile', 'my-login-form'); ?>
                </a>
            </div>
        </div>
        <style>
        .my-login-form-logged-in {
            max-width: 500px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .logged-in-header i {
            font-size: 60px;
            color: #0073aa;
            margin-bottom: 15px;
        }
        .logged-in-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 25px;
        }
        .logged-in-actions .button {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
        }
        .logged-in-actions .button-logout {
            background: #dc3545;
            color: #fff;
        }
        .logged-in-actions .button-primary {
            background: #0073aa;
            color: #fff;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render profile shortcode
     */
    public function render_profile($atts) {
        if (!\MyLoginForm\Licensing\Gate::is_active()) {
            return \MyLoginForm\Licensing\Gate::gate_notice_html();
        }

        if (!is_user_logged_in()) {
            return '<p class="my-login-form-error">' . __('Please login to view your profile.', 'my-login-form') . '</p>';
        }
        
        $current_user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="my-login-form-profile">
            <h2><?php _e('My Profile', 'my-login-form'); ?></h2>
            <div class="profile-info">
                <div class="profile-avatar">
                    <?php echo get_avatar($current_user->ID, 128); ?>
                </div>
                <div class="profile-details">
                    <p><strong><?php _e('Username:', 'my-login-form'); ?></strong> <?php echo esc_html($current_user->user_login); ?></p>
                    <p><strong><?php _e('Email:', 'my-login-form'); ?></strong> <?php echo esc_html($current_user->user_email); ?></p>
                    <p><strong><?php _e('Display Name:', 'my-login-form'); ?></strong> <?php echo esc_html($current_user->display_name); ?></p>
                    <p><strong><?php _e('Member Since:', 'my-login-form'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($current_user->user_registered)); ?></p>
                </div>
            </div>
            <div class="profile-actions">
                <a href="<?php echo get_edit_profile_url($current_user->ID); ?>" class="button">
                    <?php _e('Edit Profile', 'my-login-form'); ?>
                </a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="button button-logout">
                    <?php _e('Logout', 'my-login-form'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}