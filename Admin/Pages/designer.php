<?php
/**
 * Form Designer Template - With Nested Sub Divs Support & Selective Customization
 * 
 * @package MyLoginForm
 */

// Prevent direct access
defined('ABSPATH') || exit;

global $wpdb;
$forms_table = $wpdb->prefix . 'my_login_forms';

// ============================================
// 1. GET ALL FORMS
// ============================================
$all_forms = $wpdb->get_results("SELECT * FROM $forms_table ORDER BY sort_order ASC, id DESC");
$forms = $all_forms;
$total_forms = is_array($forms) ? count($forms) : 0;

// ============================================
// 2. DEFINE FORM CONTAINERS
// ============================================
$form_container = [
    'main_container' => [
        'label' => __('Main Container', 'my-login-form'),
        'icon' => '📦',
        'type' => 'main_container'
    ],
    'sub_container' => [
        'label' => __('Sub Div', 'my-login-form'),
        'icon' => '🗂️',
        'type' => 'sub_div'
    ],
    'social_container' => [
        'label' => __('Social Container', 'my-login-form'),
        'icon' => '🔗',
        'type' => 'social_container'
    ],
];

// ============================================
// 3. DEFINE FORM FIELDS
// ============================================
$all_fields = [
    'text' => [
        'label' => __('Text Field', 'my-login-form'),
        'icon' => '📝',
        'type' => 'text',
        'html_type' => 'text'
    ],
    'first_name' => [
        'label' => __('First Name', 'my-login-form'),
        'icon' => '👤',
        'type' => 'first_name',
        'html_type' => 'text'
    ],
    'last_name' => [
        'label' => __('Last Name', 'my-login-form'),
        'icon' => '👥',
        'type' => 'last_name',
        'html_type' => 'text'
    ],
    'email' => [
        'label' => __('Email Field', 'my-login-form'),
        'icon' => '📧',
        'type' => 'email',
        'html_type' => 'email'
    ],
    'password' => [
        'label' => __('Password Field', 'my-login-form'),
        'icon' => '🔒',
        'type' => 'password',
        'html_type' => 'password'
    ],
    'confirm_password' => [
        'label' => __('Confirm Password Field', 'my-login-form'),
        'icon' => '🔒',
        'type' => 'password',
        'html_type' => 'password'
    ],
    'username' => [
        'label' => __('Username', 'my-login-form'),
        'icon' => '👤',
        'type' => 'username',
        'html_type' => 'text'
    ],
    'number' => [
        'label' => __('Number Field', 'my-login-form'),
        'icon' => '🔢',
        'type' => 'number',
        'html_type' => 'number'
    ],
    'date' => [
        'label' => __('Date Field', 'my-login-form'),
        'icon' => '📅',
        'type' => 'date',
        'html_type' => 'date'
    ],
    'textarea' => [
        'label' => __('Textarea', 'my-login-form'),
        'icon' => '📄',
        'type' => 'textarea',
        'html_type' => 'textarea'
    ],
    'select' => [
        'label' => __('Dropdown', 'my-login-form'),
        'icon' => '📋',
        'type' => 'select',
        'html_type' => 'select'
    ],
    'checkbox' => [
        'label' => __('Checkbox', 'my-login-form'),
        'icon' => '✅',
        'type' => 'checkbox',
        'html_type' => 'checkbox'
    ],
    'radio' => [
        'label' => __('Radio Button', 'my-login-form'),
        'icon' => '🔘',
        'type' => 'radio',
        'html_type' => 'radio'
    ],
    'phone' => [
        'label' => __('Phone Field', 'my-login-form'),
        'icon' => '📱',
        'type' => 'phone',
        'html_type' => 'tel'
    ],
];

// ============================================
// 4. WOOCOMMERCE FIELDS (if active)
// ============================================
if (class_exists('WooCommerce')) {
    $all_fields['billing_first_name'] = ['label' => __('Billing First Name', 'my-login-form'), 'icon' => '👤', 'type' => 'billing_first_name', 'html_type' => 'text'];
    $all_fields['billing_last_name'] = ['label' => __('Billing Last Name', 'my-login-form'), 'icon' => '👥', 'type' => 'billing_last_name', 'html_type' => 'text'];
    $all_fields['billing_company'] = ['label' => __('Billing Company', 'my-login-form'), 'icon' => '🏢', 'type' => 'billing_company', 'html_type' => 'text'];
    $all_fields['billing_address'] = ['label' => __('Billing Address', 'my-login-form'), 'icon' => '🏠', 'type' => 'billing_address', 'html_type' => 'text'];
    $all_fields['billing_city'] = ['label' => __('Billing City', 'my-login-form'), 'icon' => '🌆', 'type' => 'billing_city', 'html_type' => 'text'];
    $all_fields['billing_postcode'] = ['label' => __('Billing Postcode', 'my-login-form'), 'icon' => '📮', 'type' => 'billing_postcode', 'html_type' => 'text'];
    $all_fields['billing_email'] = ['label' => __('Billing Email', 'my-login-form'), 'icon' => '📧', 'type' => 'billing_email', 'html_type' => 'email'];
    $all_fields['billing_phone'] = ['label' => __('Billing Phone', 'my-login-form'), 'icon' => '📱', 'type' => 'billing_phone', 'html_type' => 'tel'];
}

// ============================================
// 5. SOCIAL PROVIDERS — only show enabled ones
//    when Supabase is configured
// ============================================
$supabase_connected = get_option('my_login_supabase_enabled', 0)
    && get_option('my_login_supabase_url', '')
    && get_option('my_login_supabase_anon_key', '');

// Full provider definitions (login OAuth providers)
$all_social_providers = [
    'google'    => ['label' => 'Google',    'color' => '#DB4437', 'icon' => 'fab fa-google'],
    'facebook'  => ['label' => 'Facebook',  'color' => '#4267B2', 'icon' => 'fab fa-facebook-f'],
    'twitter'   => ['label' => 'Twitter / X','color' => '#1DA1F2','icon' => 'fab fa-twitter'],
    'github'    => ['label' => 'GitHub',    'color' => '#333333', 'icon' => 'fab fa-github'],
    'linkedin'  => ['label' => 'LinkedIn',  'color' => '#0077B5', 'icon' => 'fab fa-linkedin-in'],
    'apple'     => ['label' => 'Apple',     'color' => '#000000', 'icon' => 'fab fa-apple'],
    'microsoft' => ['label' => 'Microsoft', 'color' => '#00A4EF', 'icon' => 'fab fa-microsoft'],
];

// Filter to only enabled providers when Supabase is on
$social_providers = [];
if ($supabase_connected) {
    foreach ($all_social_providers as $key => $provider) {
        // Each provider has its own WP option: my_login_{provider}_login_enabled
        $option_key = 'my_login_' . $key . '_login_enabled';
        if (get_option($option_key, 0)) {
            $social_providers[$key] = $provider;
        }
    }
    // Fallback: if none configured yet, show all so designer can still work
    if (empty($social_providers)) {
        $social_providers = $all_social_providers;
    }
} else {
    // Supabase not configured — show providers but will warn in palette
    $social_providers = $all_social_providers;
}

// ============================================
// 6. CURRENT FORM ID & DATA
// ============================================
$current_form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$selected_social = [];
$html_content = "";
$css_content = '';
$js_content = '';

// ============================================
// 7. FORM SETTINGS DEFAULTS
// ============================================
$form_settings = [
    'global' => [
        'btn_color' => ['type' => 'color', 'label' => 'Button Color', 'default' => '#1FBB00'],
        'button_text' => ['type' => 'text', 'label' => 'Button Text', 'default' => 'Submit'],
        'show_labels' => ['type' => 'checkbox', 'label' => 'Show Field Labels', 'default' => false]
    ],
    'main_container' => [
        'layout' => [
            'display' => ['type' => 'select', 'label' => 'Display', 'options' => ['block', 'grid', 'flex'], 'default' => 'block', 'unit' => null],
            'width' => ['type' => 'slider', 'label' => 'Width', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 100, 'unit' => '%', 'units' => ['%', 'px', 'vw']],
            'padding' => ['type' => 'slider', 'label' => 'Padding', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 20, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'margin_bottom' => ['type' => 'slider', 'label' => 'Margin Bottom', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 20, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'border_radius' => ['type' => 'slider', 'label' => 'Border Radius', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 16, 'unit' => 'px', 'units' => ['px', '%']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#ffffff'],
            'border_color' => ['type' => 'color', 'label' => 'Border Color', 'default' => '#DCE8D6'],
            'border_width' => ['type' => 'slider', 'label' => 'Border Width', 'min' => 0, 'max' => 10, 'step' => 1, 'default' => 2, 'unit' => 'px'],
            'box_shadow' => ['type' => 'select', 'label' => 'Box Shadow', 'options' => ['none', 'light', 'medium', 'strong'], 'default' => 'none']
        ]
    ],
    'sub_container' => [
        'layout' => [
            'display' => ['type' => 'select', 'label' => 'Display', 'options' => ['block', 'grid', 'flex'], 'default' => 'block', 'unit' => null],
            'width' => ['type' => 'slider', 'label' => 'Width', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 100, 'unit' => '%', 'units' => ['%', 'px', 'vw']],
            'padding' => ['type' => 'slider', 'label' => 'Padding', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 15, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'margin' => ['type' => 'slider', 'label' => 'Margin', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 10, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'border_radius' => ['type' => 'slider', 'label' => 'Border Radius', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 12, 'unit' => 'px', 'units' => ['px', '%']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#F3FBF0'],
            'border_color' => ['type' => 'color', 'label' => 'Border Color', 'default' => '#E3F0DE'],
            'border_width' => ['type' => 'slider', 'label' => 'Border Width', 'min' => 0, 'max' => 10, 'step' => 1, 'default' => 2, 'unit' => 'px'],
            'box_shadow' => ['type' => 'select', 'label' => 'Box Shadow', 'options' => ['none', 'light', 'medium', 'strong'], 'default' => 'none']
        ]
    ],
    'social_container' => [
        'layout' => [
            'display' => ['type' => 'select', 'label' => 'Display', 'options' => ['block', 'grid', 'flex'], 'default' => 'block', 'unit' => null],
            'width' => ['type' => 'slider', 'label' => 'Width', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 100, 'unit' => '%', 'units' => ['%', 'px', 'vw']],
            'padding' => ['type' => 'slider', 'label' => 'Padding', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 15, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'margin' => ['type' => 'slider', 'label' => 'Margin', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 10, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'border_radius' => ['type' => 'slider', 'label' => 'Border Radius', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 12, 'unit' => 'px', 'units' => ['px', '%']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#ffffff'],
            'border_color' => ['type' => 'color', 'label' => 'Border Color', 'default' => '#DCE8D6'],
            'border_width' => ['type' => 'slider', 'label' => 'Border Width', 'min' => 0, 'max' => 10, 'step' => 1, 'default' => 2, 'unit' => 'px'],
            'box_shadow' => ['type' => 'select', 'label' => 'Box Shadow', 'options' => ['none', 'light', 'medium', 'strong'], 'default' => 'none']
        ]
    ],
    'field' => [
        'layout' => [
            'width' => ['type' => 'slider', 'label' => 'Width', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 100, 'unit' => '%', 'units' => ['%', 'px']],
            'margin_bottom' => ['type' => 'slider', 'label' => 'Margin Bottom', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 12, 'unit' => 'px', 'units' => ['px', 'rem']],
            'label_font_size' => ['type' => 'slider', 'label' => 'Label Font Size', 'min' => 10, 'max' => 24, 'step' => 1, 'default' => 14, 'unit' => 'px', 'units' => ['px', 'rem']],
            'input_padding' => ['type' => 'slider', 'label' => 'Input Padding', 'min' => 5, 'max' => 30, 'step' => 1, 'default' => 10, 'unit' => 'px', 'units' => ['px', 'rem']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#ffffff'],
            'border_color' => ['type' => 'color', 'label' => 'Border Color', 'default' => '#DCE8D6'],
            'border_width' => ['type' => 'slider', 'label' => 'Border Width', 'min' => 0, 'max' => 5, 'step' => 1, 'default' => 1, 'unit' => 'px'],
            'text_color' => ['type' => 'color', 'label' => 'Text Color', 'default' => '#2A2A2A'],
            'label_color' => ['type' => 'color', 'label' => 'Label Color', 'default' => '#2A2A2A']
        ]
    ],
    'button' => [
        'layout' => [
            'width' => ['type' => 'select', 'label' => 'Width', 'options' => ['auto', 'full'], 'default' => 'auto', 'unit' => null],
            'padding' => ['type' => 'slider', 'label' => 'Padding', 'min' => 5, 'max' => 40, 'step' => 1, 'default' => 12, 'unit' => 'px', 'units' => ['px', 'rem']],
            'margin_top' => ['type' => 'slider', 'label' => 'Margin Top', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 15, 'unit' => 'px', 'units' => ['px', 'rem']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#1FBB00'],
            'text_color' => ['type' => 'color', 'label' => 'Text Color', 'default' => '#ffffff'],
            'hover_background_color' => ['type' => 'color', 'label' => 'Hover Background Color', 'default' => '#0F5900'],
            'border_radius' => ['type' => 'slider', 'label' => 'Border Radius', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 8, 'unit' => 'px', 'units' => ['px', '%']]
        ]
    ]
];

// ============================================
// 8. LOAD EXISTING FORM DATA (if editing)
// ============================================
if ($current_form_id) {
    $current_form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $current_form_id));
    if ($current_form) {
        $form_settings_data = json_decode($current_form->settings, true);
        if (is_array($form_settings_data)) {
            $form_settings = array_merge_recursive($form_settings, $form_settings_data);
            $selected_social = isset($form_settings_data['social_providers']) ? $form_settings_data['social_providers'] : [];
        }
        
        // The CSS textarea holds only the admin's own custom CSS — the generated
        // base + colour rules are never round-tripped through it (they live in
        // build_form_css() and get regenerated on every save).
        $css_content = isset($form_settings_data['custom_css']) ? $form_settings_data['custom_css'] : '';
        $js_content = $current_form->js_file ? @file_get_contents(MY_LOGIN_FORM_DIR . 'Public/Forms/js/' . $current_form->js_file) : '';
        $html_content = $current_form->html_file ? @file_get_contents(MY_LOGIN_FORM_DIR . 'Public/Forms/html/' . $current_form->html_file) : '';
    }
}

// ============================================
// 9. EXTRACT VALUES FOR CLEAN HTML
// ============================================

// Global settings
$global_settings = $form_settings['global'];
$btn_color = $global_settings['btn_color']['default'];
$button_text = $global_settings['button_text']['default'];
$show_labels = $global_settings['show_labels']['default'];

// Where to send visitors after they log in / create an account. Read straight
// from the form's own settings JSON (not the $form_settings defaults array,
// which only models the Layout/Customization panels) — resolved into an
// actual URL by FormsShortcodes::resolve_redirect_url() at render time.
$redirect_after_login        = isset($form_settings_data['redirect_after_login']) ? $form_settings_data['redirect_after_login'] : 'home';
$redirect_after_registration = isset($form_settings_data['redirect_after_registration']) ? $form_settings_data['redirect_after_registration'] : 'home';
$custom_redirect_url         = isset($form_settings_data['custom_redirect_url']) ? $form_settings_data['custom_redirect_url'] : '';

// File contents
$css_file_content = $css_content;
$js_file_content = $js_content;
$html_file_content = $html_content;

// Get current form key if a form is selected
$current_form_key = '';
if ($current_form_id && isset($current_form)) {
    $current_form_key = $current_form->form_key;
}

// ============================================
// 10. CREATE NONCES & URLS (DEFINE FIRST)
// ============================================
$create_nonce = wp_create_nonce('my_login_create_form');
$delete_nonce = wp_create_nonce('my_login_delete_form');
$duplicate_nonce = wp_create_nonce('my_login_duplicate_form');
$get_nonce = wp_create_nonce('my_login_get_form');
$save_nonce = wp_create_nonce('my_login_save_form_settings');
$save_css_nonce = wp_create_nonce('my_login_save_form_css');
$save_js_nonce = wp_create_nonce('my_login_save_form_js');
$save_html_nonce = wp_create_nonce('my_login_save_form_html');

// URLs
$admin_ajax_url = admin_url('admin-ajax.php');

// WordPress's native CodeMirror-based code editor (same one used for Custom
// CSS in the Customizer / plugin file editor) for the CSS and JS tabs. Actual
// enqueuing happens in Hooks::enqueue_admin_assets() (must run before wp_head());
// calling this again here is a harmless no-op that just returns the settings
// array we need to hand off to designer.js.
// Returns false if the user has disabled syntax highlighting in their profile.
$css_editor_settings = wp_enqueue_code_editor(['type' => 'text/css']);
$js_editor_settings  = wp_enqueue_code_editor(['type' => 'text/javascript']);

// ============================================
// 11. PREPARE DATA FOR JAVASCRIPT
// ============================================
$designer_data = [
    'ajax_url' => $admin_ajax_url,
    'nonces' => [
        'create_form' => $create_nonce,
        'delete_form' => $delete_nonce,
        'duplicate_form' => $duplicate_nonce,
        'get_form' => $get_nonce,
        'save_form' => $save_nonce,
        'save_css' => $save_css_nonce,
        'save_js' => $save_js_nonce,
        'save_html' => $save_html_nonce,
    ],
    'current_form_id' => $current_form_id,
    'current_form_key' => $current_form_key,
    'form_settings' => $form_settings,
    'social_providers' => $social_providers,
    'all_social_providers' => $all_social_providers,
    'supabase_connected' => (bool) $supabase_connected,
    'all_fields' => $all_fields,
    'form_container' => $form_container,
    'total_forms' => $total_forms,
    'forms' => $forms,
    'css_editor_settings' => $css_editor_settings,
    'js_editor_settings'  => $js_editor_settings,
    'strings' => [
        'select_form' => __('Select a form first', 'my-login-form'),
        'form_saved' => __('Form saved!', 'my-login-form'),
        'css_saved' => __('CSS saved!', 'my-login-form'),
        'js_saved' => __('JavaScript saved!', 'my-login-form'),
        'duplicate_confirm' => __('Duplicate this form?', 'my-login-form'),
        'delete_confirm' => __('Delete this form?', 'my-login-form'),
        'clear_confirm' => __('Clear all fields?', 'my-login-form'),
    ]
];
?>

<?php
// This is a normal WP admin page callback (add_submenu_page) — WordPress's
// own admin-header.php has already opened <html><head>...wp_head()...</head><body>
// before this file runs, and admin-footer.php will close it afterwards. This
// file must therefore only output page CONTENT, like every sibling page
// (dashboard.php, settings.php, ...) — never its own <!DOCTYPE>/<html>/<head>/
// <body>. Assets (designer.css/js, jQuery UI, Font Awesome, the CodeMirror
// code editor) are all enqueued from Hooks::enqueue_admin_assets(), which runs
// early enough (admin_enqueue_scripts) to print correctly in the real wp_head().
?>
<div class="wrap">
    <!-- HEADER -->
    <div class="designer-header">
        <h1><i class="fas fa-swatchbook"></i> <?php _e('Form Designer', 'my-login-form'); ?></h1>
        <div class="header-actions">
            <button id="createNewFormBtn" class="button-primary"><i class="fas fa-plus"></i> <?php _e('Create New Form', 'my-login-form'); ?></button>
            <button id="saveFormBtn" class="button-primary"><i class="fas fa-floppy-disk"></i> <?php _e('Save Form', 'my-login-form'); ?></button>
            <span id="saveIndicator"></span>
            <span id="currentFormTitle"><?php if ($current_form_id && $forms): foreach ($forms as $f) { if ($f->id == $current_form_id) { echo esc_html($f->name); break; } } endif; ?></span>
            <span class="designer-form-count"><i class="fas fa-file-lines"></i> <?php echo (int) $total_forms; ?> <?php _e('Forms', 'my-login-form'); ?></span>
        </div>
    </div>
    


    <div class="designer-columns">
        
        <!-- LEFT COLUMN -->
        <div class="left-column">
            
            <!-- FORM LIST -->
            <div class="form-list-section">
                <h3><i class="fas fa-list"></i> <?php _e('Form List', 'my-login-form'); ?></h3>
                <div id="formList">
                    <?php if (empty($forms)): ?>
                        <div class="empty-state"><?php _e('No forms created yet', 'my-login-form'); ?></div>
                    <?php else: ?>
                        <?php foreach ($forms as $form): ?>
                            <div class="form-item <?php echo ($current_form_id == $form->id) ? 'active' : ''; ?>" data-form-id="<?php echo (int) $form->id; ?>">
                                <span class="form-name"><i class="fas fa-file-lines"></i> <?php echo esc_html($form->name); ?></span>
                                <div class="form-actions">
                                    <button class="copy-shortcode" data-form-id="<?php echo (int) $form->id; ?>" title="<?php esc_attr_e('Copy shortcode', 'my-login-form'); ?>"><i class="fas fa-copy"></i></button>
                                    <?php if ($form->is_system != 1): ?>
                                        <button class="duplicate-form" data-form-id="<?php echo (int) $form->id; ?>" title="<?php esc_attr_e('Duplicate', 'my-login-form'); ?>"><i class="fas fa-clone"></i></button>
                                        <button class="delete-form" data-form-id="<?php echo (int) $form->id; ?>" title="<?php esc_attr_e('Delete', 'my-login-form'); ?>"><i class="fas fa-trash-alt"></i></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FIELDS PALETTE -->
            <div class="fields-palette">
                <div class="palette-category">
                    <h4><i class="fas fa-box"></i> <?php _e('Containers', 'my-login-form'); ?></h4>
                    <div class="draggable-field palette-main-draggable" data-drag-type="main_container">
                        <span>📦</span> <span><?php _e('Main Container', 'my-login-form'); ?></span>
                    </div>
                    <div class="draggable-field" data-drag-type="sub_div">
                        <span>🗂️</span> <span><?php _e('Sub Div', 'my-login-form'); ?></span>
                    </div>
                    <div class="draggable-field" data-drag-type="social_section">
                        <span>🔗</span> <span><?php _e('Social Section', 'my-login-form'); ?></span>
                    </div>
                    <div class="draggable-field" data-drag-type="greeting">
                        <span>👋</span> <span><?php _e('Greeting', 'my-login-form'); ?></span>
                    </div>
                </div>

                <div class="palette-category">
                    <h4><i class="fas fa-pen-to-square"></i> <?php _e('Form Fields', 'my-login-form'); ?></h4>
                    <?php foreach ($all_fields as $key => $field): ?>
                        <div class="draggable-field" data-drag-type="field" data-field-type="<?php echo esc_attr($key); ?>" data-field-label="<?php echo esc_attr($field['label']); ?>" data-field-html-type="<?php echo esc_attr($field['html_type']); ?>">
                            <span><?php echo esc_html($field['icon']); ?></span> <span><?php echo esc_html($field['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="palette-category" id="socialPaletteSection">
                    <h4><i class="fas fa-share-nodes"></i> <?php _e('Social Login', 'my-login-form'); ?></h4>
                    <?php if (!$supabase_connected): ?>
                        <div class="palette-warning">
                            <span><i class="fas fa-triangle-exclamation"></i></span>
                            <div>
                                <strong><?php _e('Supabase not connected', 'my-login-form'); ?></strong>
                                <p><?php _e('Social login buttons need Supabase. Configure it in Integration Hub first.', 'my-login-form'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=my-login-form-social-supabase'); ?>" class="palette-warning-link"><?php _e('Set up Supabase', 'my-login-form'); ?> <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($social_providers as $key => $provider): ?>
                        <div class="draggable-field social-draggable"
                             data-drag-type="social"
                             data-social-provider="<?php echo esc_attr($key); ?>"
                             style="border-left: 3px solid <?php echo esc_attr($provider['color']); ?>;"
                             <?php echo !$supabase_connected ? 'data-no-supabase="1"' : ''; ?>>
                            <span style="color:<?php echo esc_attr($provider['color']); ?>;">
                                <i class="<?php echo esc_attr($provider['icon']); ?>"></i>
                            </span>
                            <span><?php echo esc_html($provider['label']); ?></span>
                            <?php if ($supabase_connected): ?>
                                <span class="provider-badge-enabled"><i class="fas fa-check"></i></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($supabase_connected && empty($social_providers)): ?>
                        <p class="palette-empty-note"><?php _e('No providers enabled yet. Go to Integration Hub → Supabase to enable them.', 'my-login-form'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- RIGHT COLUMN -->
        <div class="right-column">
            
            <!-- FORM BUILDER AREA -->
            <div class="form-builder-area">
                <div class="builder-header">
                    <h2><i class="fas fa-pen-ruler"></i> <?php _e('Form Builder', 'my-login-form'); ?></h2>
                    <button id="clearAllBtn" class="button-primary"><i class="fas fa-broom"></i> <?php _e('Clear All', 'my-login-form'); ?></button>
                </div>
                <div id="formBuilder">
                    <div class="builder-placeholder"><i class="fas fa-box"></i> <?php _e('Drag a Main Container here to start', 'my-login-form'); ?></div>
                </div>
            </div>

            <!-- TABS CONTAINER -->
            <div class="tabs-container">
                <div id="selectionInfo" class="selection-info" style="display: none;">
                    <span><i class="fas fa-thumbtack"></i> <?php _e('Selected:', 'my-login-form'); ?> <strong id="selectedItemName"><?php _e('Nothing', 'my-login-form'); ?></strong></span>
                    <button id="clearSelectionBtn" class="clear-selection"><?php _e('Clear Selection', 'my-login-form'); ?></button>
                </div>

                <div class="tabs-header">
                    <button class="tab-btn active" data-tab="layout"><i class="fas fa-ruler-combined"></i> <?php _e('Layout', 'my-login-form'); ?></button>
                    <button class="tab-btn" data-tab="customization"><i class="fas fa-palette"></i> <?php _e('Customization', 'my-login-form'); ?></button>
                    <button class="tab-btn" data-tab="css"><i class="fas fa-brush"></i> <?php _e('CSS', 'my-login-form'); ?></button>
                    <button class="tab-btn" data-tab="js"><i class="fas fa-bolt"></i> <?php _e('JavaScript', 'my-login-form'); ?></button>
                    <button class="tab-btn" data-tab="settings"><i class="fas fa-gear"></i> <?php _e('Settings', 'my-login-form'); ?></button>
                </div>

                <!-- LAYOUT TAB -->
                <div class="tab-content active" id="tab-layout">
                    <div id="layoutContent">
                        <p class="placeholder-message"><i class="fas fa-thumbtack"></i> <?php _e('Click the pencil icon on any field or container to edit its properties here', 'my-login-form'); ?></p>
                    </div>
                </div>

                <!-- CUSTOMIZATION TAB -->
                <div class="tab-content" id="tab-customization">
                    <div id="customizationContent">
                        <p class="placeholder-message"><i class="fas fa-palette"></i> <?php _e('Click the pencil icon on any element to edit its colors and styles here', 'my-login-form'); ?></p>
                    </div>
                </div>

                <!-- CSS TAB -->
                <div class="tab-content" id="tab-css">
                    <div class="settings-group">
                        <h4><i class="fas fa-brush"></i> <?php _e('Global CSS for Entire Form', 'my-login-form'); ?></h4>
                        <textarea id="customCSS" class="code-editor" placeholder="/* <?php _e('Your custom CSS for the entire form', 'my-login-form'); ?> */"><?php echo esc_textarea($css_file_content); ?></textarea>
                        <button id="saveCSSBtn" class="button-primary"><i class="fas fa-floppy-disk"></i> <?php _e('Save CSS', 'my-login-form'); ?></button>
                    </div>
                </div>

                <!-- JAVASCRIPT TAB -->
                <div class="tab-content" id="tab-js">
                    <div class="settings-group">
                        <h4><i class="fas fa-bolt"></i> <?php _e('Global JavaScript for Entire Form', 'my-login-form'); ?></h4>
                        <textarea id="customJS" class="code-editor" placeholder="// <?php _e('Your custom JavaScript for the entire form', 'my-login-form'); ?>"><?php echo esc_textarea($js_file_content); ?></textarea>
                        <button id="saveJSBtn" class="button-primary"><i class="fas fa-floppy-disk"></i> <?php _e('Save JavaScript', 'my-login-form'); ?></button>
                    </div>
                </div>

                <!-- SETTINGS TAB -->
                <div class="tab-content" id="tab-settings">
                    <div class="settings-group">
                        <h4><i class="fas fa-gear"></i> <?php _e('Form Settings', 'my-login-form'); ?></h4>
                        <div class="setting-field">
                            <label><?php _e('Button Color', 'my-login-form'); ?></label>
                            <input type="color" id="btnColor" value="<?php echo esc_attr($btn_color); ?>">
                        </div>
                        <div class="setting-field">
                            <label><?php _e('Button Text', 'my-login-form'); ?></label>
                            <input type="text" id="buttonText" value="<?php echo esc_attr($button_text); ?>">
                        </div>
                        <div class="setting-field">
                            <label>
                                <input type="checkbox" id="showLabels" <?php echo $show_labels ? 'checked' : ''; ?>>
                                <?php _e('Show field labels', 'my-login-form'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="settings-group">
                        <h4>↪️ <?php _e('Redirects', 'my-login-form'); ?></h4>
                        <div class="setting-field">
                            <label><?php _e('Default Redirect After Login', 'my-login-form'); ?></label>
                            <select id="redirectAfterLogin">
                                <option value="home" <?php selected($redirect_after_login, 'home'); ?>><?php _e('Home Page', 'my-login-form'); ?></option>
                                <option value="my-profile" <?php selected($redirect_after_login, 'my-profile'); ?>><?php _e('My Account', 'my-login-form'); ?></option>
                                <option value="custom" <?php selected($redirect_after_login, 'custom'); ?>><?php _e('Custom URL', 'my-login-form'); ?></option>
                            </select>
                        </div>
                        <div class="setting-field">
                            <label><?php _e('Redirect After Create New Account', 'my-login-form'); ?></label>
                            <select id="redirectAfterRegistration">
                                <option value="home" <?php selected($redirect_after_registration, 'home'); ?>><?php _e('Home Page', 'my-login-form'); ?></option>
                                <option value="my-profile" <?php selected($redirect_after_registration, 'my-profile'); ?>><?php _e('My Account', 'my-login-form'); ?></option>
                                <option value="login" <?php selected($redirect_after_registration, 'login'); ?>><?php _e('Login Page', 'my-login-form'); ?></option>
                                <option value="custom" <?php selected($redirect_after_registration, 'custom'); ?>><?php _e('Custom URL', 'my-login-form'); ?></option>
                            </select>
                        </div>
                        <div class="setting-field" id="customRedirectUrlField" style="<?php echo (in_array('custom', [$redirect_after_login, $redirect_after_registration], true)) ? '' : 'display:none;'; ?>">
                            <label><?php _e('Custom URL', 'my-login-form'); ?></label>
                            <input type="text" id="customRedirectUrl" placeholder="https://example.com/welcome" value="<?php echo esc_attr($custom_redirect_url); ?>">
                        </div>
                    </div>
                    <div class="settings-group">
                        <h4><i class="fas fa-swatchbook"></i> <?php _e('Presets', 'my-login-form'); ?></h4>
                        <div class="preset-buttons">
                            <button class="preset-btn" data-preset="modern"><?php _e('Modern', 'my-login-form'); ?></button>
                            <button class="preset-btn" data-preset="minimal"><?php _e('Minimal', 'my-login-form'); ?></button>
                            <button class="preset-btn" data-preset="dark"><?php _e('Dark', 'my-login-form'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALS -->
<div id="fieldEditModal" class="my-login-modal modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-pen-to-square"></i> <?php _e('Edit Field', 'my-login-form'); ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="fieldEditId">
            <div class="form-group">
                <label><?php _e('Label', 'my-login-form'); ?></label>
                <input type="text" id="fieldEditLabel" class="widefat">
            </div>
            <div class="form-group">
                <label><?php _e('Placeholder', 'my-login-form'); ?></label>
                <input type="text" id="fieldEditPlaceholder" class="widefat">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="fieldEditRequired">
                    <?php _e('Required field', 'my-login-form'); ?>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="cancel-modal button"><?php _e('Cancel', 'my-login-form'); ?></button>
            <button id="saveFieldEditBtn" class="button-primary"><?php _e('Save', 'my-login-form'); ?></button>
        </div>
    </div>
</div>

<!-- Legacy alias so nothing breaks -->
<div id="fieldSettingsModal" class="my-login-modal modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-gear"></i> <?php _e('Field Settings', 'my-login-form'); ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editingFieldId">
        </div>
        <div class="modal-footer">
            <button class="cancel-modal button"><?php _e('Cancel', 'my-login-form'); ?></button>
        </div>
    </div>
</div>

<div id="containerSettingsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-box"></i> <?php _e('Container Settings', 'my-login-form'); ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editingContainerId">
            <div class="form-group">
                <label><?php _e('Background Color', 'my-login-form'); ?></label>
                <input type="color" id="containerBgColor" value="#ffffff">
            </div>
        </div>
        <div class="modal-footer">
            <button class="cancel-modal button"><?php _e('Cancel', 'my-login-form'); ?></button>
            <button id="saveContainerSettingsBtn" class="button-primary"><?php _e('Save', 'my-login-form'); ?></button>
        </div>
    </div>
</div>

<div id="createFormModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Create New Form', 'my-login-form'); ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label><?php _e('Form Name', 'my-login-form'); ?></label>
                <input type="text" id="newFormName">
            </div>
            <div class="form-group">
                <label><?php _e('Form Type', 'my-login-form'); ?></label>
                <select id="newFormType">
                    <option value="custom"><?php _e('Custom', 'my-login-form'); ?></option>
                    <option value="login"><?php _e('Login', 'my-login-form'); ?></option>
                    <option value="register"><?php _e('Register', 'my-login-form'); ?></option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="cancel-modal button"><?php _e('Cancel', 'my-login-form'); ?></button>
            <button id="createFormSubmitBtn" class="button-primary"><?php _e('Create', 'my-login-form'); ?></button>
        </div>
    </div>
</div>
<?php
// jQuery, jQuery UI (draggable/droppable/sortable), and designer.js itself are
// all enqueued via Hooks::enqueue_admin_assets() — never load duplicate copies
// here. A second jQuery from a CDN would silently break WP's own admin scripts
// and desync which jQuery instance designer.js's UI widgets are bound to.
?>
<script>
var MyLoginDesigner = <?php echo json_encode($designer_data); ?>;
</script>