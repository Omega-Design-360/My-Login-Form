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

// Get all forms
$all_forms = $wpdb->get_results("SELECT * FROM $forms_table ORDER BY sort_order ASC, id DESC");
$forms = $all_forms;
$total_forms = is_array($forms) ? count($forms) : 0;

// Form Elements
$form_elements = [
    'main_container' => ['label' => __('Main Container', 'my-login-form'), 'icon' => '📦', 'type' => 'main_container'],
    'sub_div' => ['label' => __('Sub Div', 'my-login-form'), 'icon' => '🗂️', 'type' => 'sub_div'],
    'social_section' => ['label' => __('Social Section', 'my-login-form'), 'icon' => '🔗', 'type' => 'social_section'],
    'text' => ['label' => __('Text Field', 'my-login-form'), 'icon' => '📝', 'type' => 'text', 'html_type' => 'text'],
    'email' => ['label' => __('Email Field', 'my-login-form'), 'icon' => '📧', 'type' => 'email', 'html_type' => 'email'],
    'password' => ['label' => __('Password Field', 'my-login-form'), 'icon' => '🔒', 'type' => 'password', 'html_type' => 'password'],
    'textarea' => ['label' => __('Textarea', 'my-login-form'), 'icon' => '📄', 'type' => 'textarea', 'html_type' => 'textarea'],
    'number' => ['label' => __('Number Field', 'my-login-form'), 'icon' => '🔢', 'type' => 'number', 'html_type' => 'number'],
    'select' => ['label' => __('Dropdown', 'my-login-form'), 'icon' => '📋', 'type' => 'select', 'html_type' => 'select'],
    'checkbox' => ['label' => __('Checkbox', 'my-login-form'), 'icon' => '✅', 'type' => 'checkbox', 'html_type' => 'checkbox'],
    'radio' => ['label' => __('Radio Button', 'my-login-form'), 'icon' => '🔘', 'type' => 'radio', 'html_type' => 'radio'],
    'date' => ['label' => __('Date Field', 'my-login-form'), 'icon' => '📅', 'type' => 'date', 'html_type' => 'date'],
    'phone' => ['label' => __('Phone Field', 'my-login-form'), 'icon' => '📱', 'type' => 'phone', 'html_type' => 'tel'],
    'first_name' => ['label' => __('First Name', 'my-login-form'), 'icon' => '👤', 'type' => 'first_name', 'html_type' => 'text'],
    'last_name' => ['label' => __('Last Name', 'my-login-form'), 'icon' => '👥', 'type' => 'last_name', 'html_type' => 'text'],
    'username' => ['label' => __('Username', 'my-login-form'), 'icon' => '👤', 'type' => 'username', 'html_type' => 'text'],
];

// WooCommerce Fields
if (class_exists('WooCommerce')) {
    $form_elements['billing_first_name'] = ['label' => __('Billing First Name', 'my-login-form'), 'icon' => '👤', 'type' => 'billing_first_name', 'html_type' => 'text'];
    $form_elements['billing_last_name'] = ['label' => __('Billing Last Name', 'my-login-form'), 'icon' => '👥', 'type' => 'billing_last_name', 'html_type' => 'text'];
    $form_elements['billing_company'] = ['label' => __('Billing Company', 'my-login-form'), 'icon' => '🏢', 'type' => 'billing_company', 'html_type' => 'text'];
    $form_elements['billing_address'] = ['label' => __('Billing Address', 'my-login-form'), 'icon' => '🏠', 'type' => 'billing_address', 'html_type' => 'text'];
    $form_elements['billing_city'] = ['label' => __('Billing City', 'my-login-form'), 'icon' => '🌆', 'type' => 'billing_city', 'html_type' => 'text'];
    $form_elements['billing_postcode'] = ['label' => __('Billing Postcode', 'my-login-form'), 'icon' => '📮', 'type' => 'billing_postcode', 'html_type' => 'text'];
    $form_elements['billing_email'] = ['label' => __('Billing Email', 'my-login-form'), 'icon' => '📧', 'type' => 'billing_email', 'html_type' => 'email'];
    $form_elements['billing_phone'] = ['label' => __('Billing Phone', 'my-login-form'), 'icon' => '📱', 'type' => 'billing_phone', 'html_type' => 'tel'];
}

// Social providers
$social_providers = [
    'google' => ['label' => 'Google', 'color' => '#DB4437'],
    'facebook' => ['label' => 'Facebook', 'color' => '#4267B2'],
    'twitter' => ['label' => 'Twitter', 'color' => '#1DA1F2'],
    'github' => ['label' => 'GitHub', 'color' => '#333333'],
    'linkedin' => ['label' => 'LinkedIn', 'color' => '#0077B5'],
];

$current_form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$selected_social = array();
$custom_css = '';
$custom_js = '';
$form_settings = array(
    'btn_color' => '#0073aa',
    'button_text' => 'Submit',
    'show_labels' => false,
    'layout' => array(
        'container_width' => '100%',
        'container_padding' => '20px',
        'field_spacing' => '12px',
        'label_font_size' => '14px',
        'input_padding' => '10px',
        'border_radius' => '8px',
        'button_width' => 'auto',
        'button_padding' => '12px 30px',
    ),
    'customization' => array(
        'container_bg' => '#ffffff',
        'container_border_color' => '#667eea',
        'container_border_width' => '2px',
        'sub_div_bg' => '#f8f9fa',
        'sub_div_border_color' => '#f5576c',
        'field_bg' => '#ffffff',
        'field_border_color' => '#e0e0e0',
        'field_text_color' => '#333333',
        'label_color' => '#333333',
        'button_bg' => '#0073aa',
        'button_text_color' => '#ffffff',
        'button_hover_bg' => '#005a87',
    )
);

if ($current_form_id && isset($table_exists) && $table_exists) {
    $current_form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $current_form_id));
    if ($current_form) {
        $form_settings_data = json_decode($current_form->settings, true);
        if (is_array($form_settings_data)) {
            $form_settings = array_merge_recursive($form_settings, $form_settings_data);
            $selected_social = isset($form_settings_data['social_providers']) ? $form_settings_data['social_providers'] : array();
        }
        $custom_css = $current_form->css_file ? @file_get_contents(MY_LOGIN_FORM_DIR . 'Public/Forms/css/' . $current_form->css_file) : '';
        $custom_js = $current_form->js_file ? @file_get_contents(MY_LOGIN_FORM_DIR . 'Public/Forms/js/' . $current_form->js_file) : '';
    }
}

$create_nonce = wp_create_nonce('my_login_create_form');
$delete_nonce = wp_create_nonce('my_login_delete_form');
$duplicate_nonce = wp_create_nonce('my_login_duplicate_form');
$get_nonce = wp_create_nonce('my_login_get_form');
$save_nonce = wp_create_nonce('my_login_save_form_settings');

ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Designer - Selective Layout Customization</title>
    <?php wp_head(); ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        .wrap { margin: 20px 20px 0 0; }
        
        .designer-header {
            background: #fff;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .designer-header h1 { margin: 0; font-size: 24px; }
        .header-actions { display: flex; gap: 15px; }
        .designer-columns { display: flex; gap: 20px; }
        
        /* LEFT COLUMN */
        .left-column {
            width: 280px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow-y: auto;
            max-height: calc(100vh - 100px);
        }
        
        .form-list-section { padding: 15px; border-bottom: 1px solid #eee; }
        .form-list-section h3 { margin-bottom: 10px; font-size: 14px; }
        .form-list { max-height: 200px; overflow-y: auto; }
        
        .form-item {
            padding: 8px 10px;
            margin-bottom: 5px;
            background: #f8f9fa;
            border: 1px solid #e2e4e7;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .form-item:hover, .form-item.active { background: #e8f0fe; border-color: #0073aa; }
        .form-name { font-size: 13px; }
        .form-actions button { background: none; border: none; cursor: pointer; margin-left: 5px; }
        
        .fields-palette { padding: 15px; }
        .palette-category { margin-bottom: 20px; }
        .palette-category h4 { font-size: 12px; margin-bottom: 10px; color: #666; padding-bottom: 5px; border-bottom: 1px solid #eee; }
        
        .draggable-field {
            padding: 8px 10px;
            background: #f8f9fa;
            border: 1px solid #e2e4e7;
            border-radius: 6px;
            cursor: grab;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            margin-bottom: 6px;
            transition: all 0.2s;
        }
        .draggable-field:hover { background: #e8f0fe; border-color: #0073aa; }
        
        .main-draggable { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; }
        .subdiv-draggable { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; }
        .socialsection-draggable { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none; }
        
        /* RIGHT COLUMN */
        .right-column { flex: 1; display: flex; flex-direction: column; gap: 20px; }
        
        .form-builder-area {
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .builder-header {
            padding: 15px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .builder-header h2 { margin: 0; font-size: 18px; }
        
        #formBuilder { padding: 20px; min-height: 400px; overflow-y: auto; }
        .builder-placeholder { text-align: center; padding: 60px; color: #999; border: 2px dashed #ddd; border-radius: 8px; background: #fff; }
        
        /* Main Container */
        .main-container {
            background: #fff;
            border: 2px solid #667eea;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        /* Sub Div - nested support */
        .sub-div {
            background: #f8f9fa;
            border: 2px solid #f5576c;
            border-radius: 12px;
            padding: 15px;
            margin: 10px 0;
            position: relative;
            transition: all 0.3s ease;
        }
        
        /* Selected state for fields and sub divs */
        .form-field.selected, .sub-div.selected, .social-section.selected {
            outline: 3px solid #ff6b6b;
            outline-offset: 2px;
            box-shadow: 0 0 0 2px rgba(255, 107, 107, 0.3);
        }
        
        /* Nested Sub Div (Sub Div inside Sub Div) - different styling */
        .sub-div .sub-div {
            background: #fff5f5;
            border-color: #e94560;
            margin-left: 20px;
        }
        
        .sub-div .sub-div .sub-div {
            background: #ffe8e8;
            border-color: #c73e56;
            margin-left: 20px;
        }
        
        /* Social Section */
        .social-section {
            background: #fff;
            border: 2px solid #4facfe;
            border-radius: 12px;
            padding: 15px;
            margin: 10px 0;
            position: relative;
        }
        
        .section-title {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .container-controls {
            position: absolute;
            top: 8px;
            right: 8px;
            display: none;
            gap: 5px;
            z-index: 10;
        }
        
        .main-container:hover .container-controls,
        .sub-div:hover .container-controls,
        .social-section:hover .container-controls {
            display: flex;
        }
        
        .container-controls button {
            background: #fff;
            border: 1px solid #ddd;
            cursor: pointer;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .drop-zone { 
            min-height: 60px; 
            transition: all 0.3s;
        }
        .container-placeholder {
            text-align: center;
            padding: 30px;
            color: #999;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background: #fafafa;
            font-size: 12px;
        }
        
        /* Form Fields */
        .form-field { 
            margin-bottom: 12px; 
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .form-field:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        .form-field label { 
            display: block; 
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .form-field label:hover {
            background: #f0f7ff;
        }
        .form-field label.editing {
            background: #fff;
            outline: 2px solid #0073aa;
            padding: 4px 8px;
        }
        .form-field label[contenteditable="true"] {
            background: #fff;
            outline: 2px solid #0073aa;
            cursor: text;
        }
        .form-field input, .form-field textarea, .form-field select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            transition: all 0.3s ease;
        }
        .form-field input:focus, .form-field textarea:focus, .form-field select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Editable placeholder */
        .form-field input.editing-placeholder, 
        .form-field textarea.editing-placeholder,
        .form-field select.editing-placeholder {
            outline: 2px solid #0073aa;
            background: #fff;
        }
        
        /* Checkbox and Radio */
        .form-field.checkbox-field, .form-field.radio-field {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-field.checkbox-field input, .form-field.radio-field input { width: auto; }
        .form-field.checkbox-field label, .form-field.radio-field label { display: inline; margin-bottom: 0; }
        
        /* Social Buttons */
        .social-btn {
            display: inline-block;
            padding: 8px 20px;
            margin: 5px;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 13px;
            font-weight: 500;
            cursor: default;
            transition: all 0.3s ease;
        }
        
        /* Submit Button inside container */
        .container-submit {
            margin-top: 20px;
            text-align: center;
            padding: 10px;
        }
        .container-submit button {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: default;
            transition: all 0.3s ease;
        }
        .container-submit button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Edit/Remove buttons for fields */
        .field-remove, .field-edit, .field-drag {
            position: absolute;
            right: 5px;
            top: 5px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 10px;
            cursor: pointer;
            display: none;
            z-index: 10;
        }
        .field-edit { right: 45px; }
        .field-drag { right: 85px; cursor: grab; }
        .form-field:hover .field-remove, .form-field:hover .field-edit, .form-field:hover .field-drag { display: inline-block; }
        
        /* Sortable placeholder */
        .sortable-placeholder { background: #f0f7ff; border: 2px dashed #667eea; height: 50px; margin-bottom: 12px; border-radius: 8px; }
        .drag-over { background: rgba(102, 126, 234, 0.2); border: 2px dashed #667eea; }
        .sub-drag-over { background: rgba(245, 87, 108, 0.2); border: 2px dashed #f5576c; }
        .social-drag-over { background: rgba(79, 172, 254, 0.2); border: 2px dashed #4facfe; }
        .droppable-active { background: #f0f7ff; border: 2px dashed #667eea; }
        
        /* Nested container indicator */
        .nested-indicator {
            position: absolute;
            left: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 30px;
            background: #f5576c;
            border-radius: 2px;
        }
        
        /* Tabs */
        .tabs-container { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .tabs-header { 
            display: flex; 
            border-bottom: 1px solid #e0e0e0; 
            background: #f8f9fa;
            flex-wrap: wrap;
        }
        .tab-btn { 
            padding: 12px 20px; 
            background: none; 
            border: none; 
            cursor: pointer; 
            font-size: 13px; 
            font-weight: 500;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            background: #e8e8e8;
        }
        .tab-btn.active { 
            background: #fff; 
            border-bottom: 2px solid #667eea; 
            color: #667eea; 
        }
        .tab-content { display: none; padding: 20px; }
        .tab-content.active { display: block; }
        .code-editor { width: 100%; min-height: 200px; font-family: monospace; font-size: 12px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        
        /* Settings groups */
        .settings-group { margin-bottom: 20px; }
        .settings-group h4 { 
            margin-bottom: 10px; 
            font-size: 14px; 
            font-weight: 600;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .setting-field { margin-bottom: 12px; }
        .setting-field label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 500; color: #555; }
        .setting-field input[type="color"], 
        .setting-field input[type="text"], 
        .setting-field input[type="number"],
        .setting-field select { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
        }
        .setting-field input[type="range"] {
            width: 70%;
            margin-right: 10px;
        }
        .setting-field .range-value {
            display: inline-block;
            width: 50px;
            text-align: center;
            background: #f0f0f0;
            padding: 4px;
            border-radius: 4px;
            font-size: 12px;
        }
        .preset-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .preset-btn { 
            padding: 6px 12px; 
            border: 1px solid #ddd; 
            background: #fff; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 12px;
            transition: all 0.2s;
        }
        .preset-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        /* Layout preview */
        .layout-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #e0e0e0;
        }
        .layout-preview-item {
            margin-bottom: 10px;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border-left: 3px solid #667eea;
        }
        
        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100000;
        }
        .modal-content { background: #fff; border-radius: 12px; width: 400px; max-width: 90%; }
        .modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; font-weight: 600; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 15px 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
        .form-group input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; }
        button.button-primary { background: #0073aa; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; }
        .empty-state { text-align: center; padding: 30px; color: #999; }
        
        .drag-helper {
            background: #0073aa;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 10000;
        }
        
        /* Live edit hint */
        .edit-hint {
            font-size: 10px;
            color: #999;
            margin-left: 8px;
            display: inline-block;
        }
        .form-field:hover .edit-hint {
            color: #0073aa;
        }
        
        /* Two column layout for customization */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Selection info bar */
        .selection-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .selection-info .clear-selection {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .selection-info .clear-selection:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Disabled state for customization when no selection */
        .customization-disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="designer-header">
        <h1>🎨 Form Designer</h1>
        <div class="header-actions">
            <button id="createNewFormBtn" class="button-primary">+ Create New Form</button>
            <button onclick="saveCurrentForm()" class="button-primary">💾 Save Form</button>
            <span>📋 <?php echo $total_forms; ?> Forms</span>
        </div>
    </div>
    
    <div class="designer-columns">
        
        <!-- LEFT COLUMN - Field Palette -->
        <div class="left-column">
            <div class="form-list-section">
                <h3>📋 Form List</h3>
                <div id="formList">
                    <?php if (empty($forms)): ?>
                        <div class="empty-state">No forms created yet</div>
                    <?php else: ?>
                        <?php foreach ($forms as $form): ?>
                            <div class="form-item <?php echo ($current_form_id == $form->id) ? 'active' : ''; ?>" data-form-id="<?php echo $form->id; ?>">
                                <span class="form-name">📝 <?php echo esc_html($form->name); ?></span>
                                <div class="form-actions">
                                    <button class="copy-shortcode">📋</button>
                                    <?php if ($form->is_system != 1): ?>
                                        <button class="duplicate-form">📝</button>
                                        <button class="delete-form">🗑️</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="fields-palette">
                <div class="palette-category">
                    <h4>📦 Containers</h4>
                    <div class="draggable-field main-draggable" data-type="main_container">
                        <span>📦</span> <span>Main Container</span>
                    </div>
                    <div class="draggable-field subdiv-draggable" data-type="sub_div">
                        <span>🗂️</span> <span>Sub Div</span>
                    </div>
                    <div class="draggable-field socialsection-draggable" data-type="social_section">
                        <span>🔗</span> <span>Social Section</span>
                    </div>
                </div>
                
                <div class="palette-category">
                    <h4>📝 Form Fields</h4>
                    <?php foreach ($form_elements as $key => $field): ?>
                        <?php if (!in_array($key, ['main_container', 'sub_div', 'social_section'])): ?>
                            <div class="draggable-field" data-type="field" data-field-type="<?php echo $key; ?>" data-field-label="<?php echo esc_attr($field['label']); ?>" data-field-icon="<?php echo esc_attr($field['icon']); ?>" data-field-html-type="<?php echo esc_attr($field['html_type']); ?>">
                                <span><?php echo $field['icon']; ?></span> <span><?php echo $field['label']; ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="palette-category">
                    <h4>🔗 Social Login Providers</h4>
                    <?php foreach ($social_providers as $key => $provider): ?>
                        <div class="draggable-field social-draggable" data-type="social" data-social-provider="<?php echo $key; ?>" data-social-label="<?php echo esc_attr($provider['label']); ?>" data-social-color="<?php echo esc_attr($provider['color']); ?>">
                            <span>🔗</span> <span><?php echo $provider['label']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- RIGHT COLUMN -->
        <div class="right-column">
            <div class="form-builder-area">
                <div class="builder-header">
                    <h2>✏️ Form Builder</h2>
                    <button onclick="clearAllFields()" class="button-primary" style="background: rgba(255,255,255,0.2);">Clear All</button>
                </div>
                <div id="formBuilder">
                    <div class="builder-placeholder">📦 Drag a Main Container here to start</div>
                </div>
            </div>
            
            <div class="tabs-container">
                <!-- Selection Info Bar -->
                <div id="selectionInfo" class="selection-info" style="display: none;">
                    <span>📌 Selected: <strong id="selectedItemName">Nothing</strong></span>
                    <button class="clear-selection" onclick="clearSelection()">Clear Selection</button>
                </div>
                
                <div class="tabs-header">
                    <button class="tab-btn" data-tab="layout">📐 Layout</button>
                    <button class="tab-btn" data-tab="customization">🎨 Customization</button>
                    <button class="tab-btn active" data-tab="css">🎨 CSS</button>
                    <button class="tab-btn" data-tab="js">⚡ JavaScript</button>
                    <button class="tab-btn" data-tab="settings">⚙️ Settings</button>
                </div>
                
                <!-- Layout Tab -->
                <div class="tab-content" id="tab-layout">
                    <div id="layoutContent">
                        <!-- Layout customization will be shown here only when an item is selected -->
                    </div>
                </div>
                
                <!-- Customization Tab -->
                <div class="tab-content" id="tab-customization">
                    <div id="customizationContent">
                        <!-- Customization options will be shown here only when an item is selected -->
                    </div>
                </div>
                
                <!-- CSS Tab - Always visible for entire form -->
                <div class="tab-content active" id="tab-css">
                    <div class="settings-group">
                        <h4>🎨 Global CSS for Entire Form</h4>
                        <textarea id="customCSS" class="code-editor" placeholder="/* Your custom CSS for the entire form */"><?php echo esc_textarea($custom_css); ?></textarea>
                        <button onclick="saveCSS()" class="button-primary" style="margin-top: 10px; width: 100%;">💾 Save CSS</button>
                    </div>
                </div>
                
                <!-- JavaScript Tab - Always visible for entire form -->
                <div class="tab-content" id="tab-js">
                    <div class="settings-group">
                        <h4>⚡ Global JavaScript for Entire Form</h4>
                        <textarea id="customJS" class="code-editor" placeholder="// Your custom JavaScript for the entire form"><?php echo esc_textarea($custom_js); ?></textarea>
                        <button onclick="saveJS()" class="button-primary" style="margin-top: 10px; width: 100%;">💾 Save JavaScript</button>
                    </div>
                </div>
                
                <!-- Settings Tab - Form Level Settings -->
                <div class="tab-content" id="tab-settings">
                    <div class="settings-group">
                        <h4>🎨 Form Settings</h4>
                        <div class="setting-field"><label>Button Color</label><input type="color" id="btnColor" value="<?php echo esc_attr($form_settings['btn_color']); ?>"></div>
                        <div class="setting-field"><label>Button Text</label><input type="text" id="buttonText" value="<?php echo esc_attr($form_settings['button_text']); ?>"></div>
                        <div class="setting-field"><label><input type="checkbox" id="showLabels" <?php echo $form_settings['show_labels'] ? 'checked' : ''; ?>> Show field labels</label></div>
                    </div>
                    <div class="settings-group">
                        <h4>🎨 Presets</h4>
                        <div class="preset-buttons">
                            <button class="preset-btn" onclick="applyPreset('modern')">Modern</button>
                            <button class="preset-btn" onclick="applyPreset('minimal')">Minimal</button>
                            <button class="preset-btn" onclick="applyPreset('dark')">Dark</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Field Settings Modal -->
<div id="fieldSettingsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>⚙️ Field Settings</h3><button onclick="closeFieldModal()">&times;</button></div>
        <div class="modal-body">
            <input type="hidden" id="editingFieldId">
            <div class="form-group"><label>Placeholder</label><input type="text" id="fieldPlaceholder"></div>
            <div class="form-group"><label><input type="checkbox" id="fieldRequired"> Required field</label></div>
        </div>
        <div class="modal-footer"><button onclick="closeFieldModal()">Cancel</button><button onclick="saveFieldSettings()" class="button-primary">Save</button></div>
    </div>
</div>

<!-- Container Settings Modal -->
<div id="containerSettingsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>📦 Container Settings</h3><button onclick="closeContainerModal()">&times;</button></div>
        <div class="modal-body">
            <input type="hidden" id="editingContainerId">
            <div class="form-group"><label>Background Color</label><input type="color" id="containerBgColor" value="#ffffff"></div>
        </div>
        <div class="modal-footer"><button onclick="closeContainerModal()">Cancel</button><button onclick="saveContainerSettings()" class="button-primary">Save</button></div>
    </div>
</div>

<!-- Create Form Modal -->
<div id="createFormModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Create New Form</h3><button onclick="closeModal()">&times;</button></div>
        <div class="modal-body">
            <div class="form-group"><label>Form Name</label><input type="text" id="newFormName"></div>
            <div class="form-group"><label>Form Type</label><select id="newFormType"><option value="custom">Custom</option><option value="login">Login</option><option value="register">Register</option></select></div>
        </div>
        <div class="modal-footer"><button onclick="closeModal()">Cancel</button><button onclick="createNewForm()" class="button-primary">Create</button></div>
    </div>
</div>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
var my_login_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonces: {
        create_form: '<?php echo $create_nonce; ?>',
        delete_form: '<?php echo $delete_nonce; ?>',
        duplicate_form: '<?php echo $duplicate_nonce; ?>',
        get_form: '<?php echo $get_nonce; ?>',
        save_form: '<?php echo $save_nonce; ?>',
        save_css: '<?php echo wp_create_nonce('my_login_save_form_css'); ?>',
        save_js: '<?php echo wp_create_nonce('my_login_save_form_js'); ?>'
    }
};

let currentFormId = <?php echo $current_form_id ? $current_form_id : 'null'; ?>;
let mainContainers = {};
let elementCounter = 0;
let editingFieldId = null;
let editingContainerId = null;
let selectedElementId = null;
let selectedElementType = null;

// Recursive function to find and update nested items
function findAndUpdateItem(containerObj, fieldId, newData, action) {
    if (!containerObj.items) return false;
    
    for (let i = 0; i < containerObj.items.length; i++) {
        const item = containerObj.items[i];
        
        if (item.id === fieldId) {
            if (action === 'update') {
                Object.assign(item, newData);
            } else if (action === 'remove') {
                containerObj.items.splice(i, 1);
            }
            return true;
        }
        
        // Recursively search in sub divs
        if (item.type === 'sub' && item.items) {
            if (findAndUpdateItem(item, fieldId, newData, action)) {
                return true;
            }
        }
    }
    return false;
}

// Recursive function to find item
function findItem(containerObj, fieldId) {
    if (!containerObj.items) return null;
    
    for (const item of containerObj.items) {
        if (item.id === fieldId) {
            return item;
        }
        if (item.type === 'sub' && item.items) {
            const found = findItem(item, fieldId);
            if (found) return found;
        }
    }
    return null;
}

// Select an element (field or sub div)
function selectElement(elementId, elementType, elementName) {
    // Remove previous selection
    if (selectedElementId) {
        jQuery(`.form-field[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.sub-div[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.social-section[data-id="${selectedElementId}"]`).removeClass('selected');
    }
    
    selectedElementId = elementId;
    selectedElementType = elementType;
    
    // Add selection class
    jQuery(`.form-field[data-id="${elementId}"]`).addClass('selected');
    jQuery(`.sub-div[data-id="${elementId}"]`).addClass('selected');
    jQuery(`.social-section[data-id="${elementId}"]`).addClass('selected');
    
    // Update selection info bar
    jQuery('#selectedItemName').text(elementName);
    jQuery('#selectionInfo').show();
    
    // Load layout and customization options for the selected element
    loadLayoutOptionsForElement(elementId, elementType);
    loadCustomizationOptionsForElement(elementId, elementType);
}

// Clear selection
function clearSelection() {
    if (selectedElementId) {
        jQuery(`.form-field[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.sub-div[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.social-section[data-id="${selectedElementId}"]`).removeClass('selected');
    }
    selectedElementId = null;
    selectedElementType = null;
    jQuery('#selectionInfo').hide();
    
    // Clear layout and customization panels
    jQuery('#layoutContent').html('<div class="settings-group"><p style="text-align: center; color: #999; padding: 40px;">📌 Select a field or sub-div to enable layout customization</p></div>');
    jQuery('#customizationContent').html('<div class="settings-group"><p style="text-align: center; color: #999; padding: 40px;">🎨 Select a field or sub-div to enable style customization</p></div>');
}

// Load layout options for selected element
function loadLayoutOptionsForElement(elementId, elementType) {
    let html = '';
    
    if (elementType === 'field') {
        html = `
            <div class="settings-group">
                <h4>📏 Field Layout Settings</h4>
                <div class="setting-field">
                    <label>Field Width (%)</label>
                    <input type="range" id="field_width" min="50" max="100" step="5" value="100" onchange="updateFieldLayout('${elementId}', 'width', this.value)">
                    <span class="range-value" id="field_width_val">100%</span>
                </div>
                <div class="setting-field">
                    <label>Margin Bottom (px)</label>
                    <input type="range" id="field_margin" min="0" max="30" step="2" value="12" onchange="updateFieldLayout('${elementId}', 'marginBottom', this.value)">
                    <span class="range-value" id="field_margin_val">12px</span>
                </div>
                <div class="setting-field">
                    <label>Label Font Size (px)</label>
                    <input type="range" id="field_label_font" min="10" max="20" step="1" value="14" onchange="updateFieldLayout('${elementId}', 'labelFontSize', this.value)">
                    <span class="range-value" id="field_label_font_val">14px</span>
                </div>
                <div class="setting-field">
                    <label>Input Padding (px)</label>
                    <input type="range" id="field_input_padding" min="5" max="20" step="1" value="10" onchange="updateFieldLayout('${elementId}', 'inputPadding', this.value)">
                    <span class="range-value" id="field_input_padding_val">10px</span>
                </div>
            </div>
        `;
    } else if (elementType === 'sub') {
        html = `
            <div class="settings-group">
                <h4>🗂️ Sub Div Layout Settings</h4>
                <div class="setting-field">
                    <label>Sub Div Width (%)</label>
                    <input type="range" id="subdiv_width" min="70" max="100" step="5" value="100" onchange="updateSubDivLayout('${elementId}', 'width', this.value)">
                    <span class="range-value" id="subdiv_width_val">100%</span>
                </div>
                <div class="setting-field">
                    <label>Padding (px)</label>
                    <input type="range" id="subdiv_padding" min="5" max="30" step="2" value="15" onchange="updateSubDivLayout('${elementId}', 'padding', this.value)">
                    <span class="range-value" id="subdiv_padding_val">15px</span>
                </div>
                <div class="setting-field">
                    <label>Margin (px)</label>
                    <input type="range" id="subdiv_margin" min="0" max="20" step="2" value="10" onchange="updateSubDivLayout('${elementId}', 'margin', this.value)">
                    <span class="range-value" id="subdiv_margin_val">10px</span>
                </div>
                <div class="setting-field">
                    <label>Border Radius (px)</label>
                    <input type="range" id="subdiv_border_radius" min="0" max="30" step="2" value="12" onchange="updateSubDivLayout('${elementId}', 'borderRadius', this.value)">
                    <span class="range-value" id="subdiv_border_radius_val">12px</span>
                </div>
            </div>
        `;
    }
    
    jQuery('#layoutContent').html(html);
    
    // Add live value displays
    if (elementType === 'field') {
        jQuery('#field_width').on('input', function() { jQuery('#field_width_val').text($(this).val() + '%'); });
        jQuery('#field_margin').on('input', function() { jQuery('#field_margin_val').text($(this).val() + 'px'); });
        jQuery('#field_label_font').on('input', function() { jQuery('#field_label_font_val').text($(this).val() + 'px'); });
        jQuery('#field_input_padding').on('input', function() { jQuery('#field_input_padding_val').text($(this).val() + 'px'); });
    } else if (elementType === 'sub') {
        jQuery('#subdiv_width').on('input', function() { jQuery('#subdiv_width_val').text($(this).val() + '%'); });
        jQuery('#subdiv_padding').on('input', function() { jQuery('#subdiv_padding_val').text($(this).val() + 'px'); });
        jQuery('#subdiv_margin').on('input', function() { jQuery('#subdiv_margin_val').text($(this).val() + 'px'); });
        jQuery('#subdiv_border_radius').on('input', function() { jQuery('#subdiv_border_radius_val').text($(this).val() + 'px'); });
    }
}

// Load customization options for selected element
function loadCustomizationOptionsForElement(elementId, elementType) {
    let html = '';
    
    if (elementType === 'field') {
        html = `
            <div class="settings-group">
                <h4>🎨 Field Style Customization</h4>
                <div class="setting-field">
                    <label>Background Color</label>
                    <input type="color" id="field_bg" value="#ffffff" onchange="updateFieldStyle('${elementId}', 'backgroundColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Color</label>
                    <input type="color" id="field_border" value="#e0e0e0" onchange="updateFieldStyle('${elementId}', 'borderColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Text Color</label>
                    <input type="color" id="field_text" value="#333333" onchange="updateFieldStyle('${elementId}', 'textColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Label Color</label>
                    <input type="color" id="field_label_color" value="#333333" onchange="updateFieldStyle('${elementId}', 'labelColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Width (px)</label>
                    <input type="number" id="field_border_width" min="0" max="5" step="1" value="1" onchange="updateFieldStyle('${elementId}', 'borderWidth', this.value + 'px')">
                </div>
            </div>
        `;
    } else if (elementType === 'sub') {
        html = `
            <div class="settings-group">
                <h4>🎨 Sub Div Style Customization</h4>
                <div class="setting-field">
                    <label>Background Color</label>
                    <input type="color" id="subdiv_bg" value="#f8f9fa" onchange="updateSubDivStyle('${elementId}', 'backgroundColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Color</label>
                    <input type="color" id="subdiv_border" value="#f5576c" onchange="updateSubDivStyle('${elementId}', 'borderColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Width (px)</label>
                    <input type="number" id="subdiv_border_width" min="1" max="5" step="1" value="2" onchange="updateSubDivStyle('${elementId}', 'borderWidth', this.value + 'px')">
                </div>
                <div class="setting-field">
                    <label>Box Shadow</label>
                    <select id="subdiv_shadow" onchange="updateSubDivStyle('${elementId}', 'boxShadow', this.value)">
                        <option value="none">None</option>
                        <option value="0 2px 4px rgba(0,0,0,0.1)">Light</option>
                        <option value="0 4px 8px rgba(0,0,0,0.15)">Medium</option>
                        <option value="0 8px 16px rgba(0,0,0,0.2)">Strong</option>
                    </select>
                </div>
            </div>
        `;
    }
    
    jQuery('#customizationContent').html(html);
}

// Update field layout
function updateFieldLayout(fieldId, property, value) {
    const $field = jQuery(`.form-field[data-id="${fieldId}"]`);
    if (property === 'width') {
        $field.css('width', value + '%');
    } else if (property === 'marginBottom') {
        $field.css('margin-bottom', value + 'px');
    } else if (property === 'labelFontSize') {
        $field.find('label').css('font-size', value + 'px');
    } else if (property === 'inputPadding') {
        $field.find('input, textarea, select').css('padding', value + 'px');
    }
    autoSave();
}

// Update field style
function updateFieldStyle(fieldId, property, value) {
    const $field = jQuery(`.form-field[data-id="${fieldId}"]`);
    if (property === 'backgroundColor') {
        $field.css('background', value);
    } else if (property === 'borderColor') {
        $field.css('border-color', value);
    } else if (property === 'textColor') {
        $field.find('input, textarea, select').css('color', value);
    } else if (property === 'labelColor') {
        $field.find('label').css('color', value);
    } else if (property === 'borderWidth') {
        $field.css('border-width', value);
    }
    autoSave();
}

// Update sub div layout
function updateSubDivLayout(subId, property, value) {
    const $subDiv = jQuery(`.sub-div[data-id="${subId}"]`);
    if (property === 'width') {
        $subDiv.css('width', value + '%');
    } else if (property === 'padding') {
        $subDiv.css('padding', value + 'px');
    } else if (property === 'margin') {
        $subDiv.css('margin', value + 'px');
    } else if (property === 'borderRadius') {
        $subDiv.css('border-radius', value + 'px');
    }
    autoSave();
}

// Update sub div style
function updateSubDivStyle(subId, property, value) {
    const $subDiv = jQuery(`.sub-div[data-id="${subId}"]`);
    if (property === 'backgroundColor') {
        $subDiv.css('background', value);
    } else if (property === 'borderColor') {
        $subDiv.css('border-color', value);
    } else if (property === 'borderWidth') {
        $subDiv.css('border-width', value);
    } else if (property === 'boxShadow') {
        $subDiv.css('box-shadow', value);
    }
    autoSave();
}

jQuery(document).ready(function($) {
    // Tab switching
    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
    // Form level controls
    $('#layout_container_width, #layout_container_padding, #layout_border_radius, #layout_field_spacing, #layout_label_font_size, #layout_input_padding, #layout_button_width, #layout_button_padding').on('input change', function() {
        applyLayoutChanges();
        autoSave();
    });
    
    // Customization controls
    $('#custom_container_bg, #custom_container_border_color, #custom_sub_div_bg, #custom_sub_div_border_color, #custom_field_bg, #custom_field_border_color, #custom_field_text_color, #custom_label_color, #custom_button_bg, #custom_button_text_color, #custom_button_hover_bg').on('input change', function() {
        applyCustomizationChanges();
        autoSave();
    });
    
    // Range value displays
    $('#layout_container_padding').on('input', function() {
        $('#container_padding_value').text($(this).val() + 'px');
    });
    $('#layout_border_radius').on('input', function() {
        $('#border_radius_value').text($(this).val() + 'px');
    });
    $('#layout_field_spacing').on('input', function() {
        $('#field_spacing_value').text($(this).val() + 'px');
    });
    $('#layout_label_font_size').on('input', function() {
        $('#label_font_size_value').text($(this).val() + 'px');
    });
    $('#layout_input_padding').on('input', function() {
        $('#input_padding_value').text($(this).val() + 'px');
    });
    $('#layout_button_padding').on('input', function() {
        $('#button_padding_value').text($(this).val() + 'px');
    });
    
    function initDraggable() {
        $('.draggable-field').draggable({
            helper: 'clone',
            revert: 'invalid',
            cursor: 'move',
            opacity: 0.7,
            zIndex: 1000,
            appendTo: 'body',
            start: function(event, ui) {
                window.draggedItemData = {
                    type: $(this).data('type'),
                    fieldType: $(this).data('field-type'),
                    fieldLabel: $(this).data('field-label'),
                    fieldHtmlType: $(this).data('field-html-type'),
                    socialProvider: $(this).data('social-provider')
                };
            },
            stop: function() {
                window.draggedItemData = null;
            }
        });
    }
    
    function initMainDroppable() {
        $('#formBuilder').droppable({
            accept: '.main-draggable',
            tolerance: 'pointer',
            hoverClass: 'droppable-active',
            drop: function(event, ui) {
                addMainContainer();
                $('.builder-placeholder').remove();
                autoSave();
                return false;
            }
        });
    }
    
    initDraggable();
    initMainDroppable();
    
    $('#btnColor, #buttonText, #showLabels').on('input change', function() {
        updateAllSubmitButtons();
        updateAllFieldsLabels();
        applyCustomizationChanges();
        autoSave();
    });
    
    $('.form-item').on('click', function(e) {
        if (!$(e.target).closest('.form-actions').length) loadForm($(this).data('form-id'));
    });
    
    $('.copy-shortcode').on('click', function(e) {
        e.stopPropagation();
        const formId = $(this).closest('.form-item').data('form-id');
        navigator.clipboard.writeText('[my_login_form id="' + formId + '"]');
        alert('Shortcode copied!');
    });
    
    $('.duplicate-form').on('click', function(e) {
        e.stopPropagation();
        if (confirm('Duplicate this form?')) duplicateForm($(this).closest('.form-item').data('form-id'));
    });
    
    $('.delete-form').on('click', function(e) {
        e.stopPropagation();
        if (confirm('Delete this form?')) deleteForm($(this).closest('.form-item').data('form-id'));
    });
    
    $('#createNewFormBtn').on('click', function() { $('#createFormModal').show(); });
    
    if (currentFormId) loadForm(currentFormId);
});

function applyLayoutChanges() {
    const containerWidth = jQuery('#layout_container_width').val();
    const containerPadding = jQuery('#layout_container_padding').val();
    const borderRadius = jQuery('#layout_border_radius').val();
    const fieldSpacing = jQuery('#layout_field_spacing').val();
    const labelFontSize = jQuery('#layout_label_font_size').val();
    const inputPadding = jQuery('#layout_input_padding').val();
    const buttonPadding = jQuery('#layout_button_padding').val();
    const buttonWidth = jQuery('#layout_button_width').val();
    
    // Apply to main containers
    jQuery('.main-container').css({
        'width': containerWidth,
        'padding': containerPadding + 'px',
        'border-radius': borderRadius + 'px'
    });
    
    // Apply to sub divs
    jQuery('.sub-div').css({
        'border-radius': (parseInt(borderRadius) - 4) + 'px'
    });
    
    // Apply to fields (global)
    jQuery('.form-field').css('margin-bottom', fieldSpacing + 'px');
    jQuery('.form-field label').css('font-size', labelFontSize + 'px');
    jQuery('.form-field input, .form-field textarea, .form-field select').css('padding', inputPadding + 'px');
    
    // Apply to buttons
    jQuery('.container-submit button').css({
        'padding': buttonPadding + 'px',
        'width': buttonWidth
    });
}

function applyCustomizationChanges() {
    const containerBg = jQuery('#custom_container_bg').val();
    const containerBorderColor = jQuery('#custom_container_border_color').val();
    const subDivBg = jQuery('#custom_sub_div_bg').val();
    const subDivBorderColor = jQuery('#custom_sub_div_border_color').val();
    const fieldBg = jQuery('#custom_field_bg').val();
    const fieldBorderColor = jQuery('#custom_field_border_color').val();
    const fieldTextColor = jQuery('#custom_field_text_color').val();
    const labelColor = jQuery('#custom_label_color').val();
    const buttonBg = jQuery('#custom_button_bg').val();
    const buttonTextColor = jQuery('#custom_button_text_color').val();
    const buttonHoverBg = jQuery('#custom_button_hover_bg').val();
    
    // Apply to main containers
    jQuery('.main-container').css({
        'background': containerBg,
        'border-color': containerBorderColor
    });
    
    // Apply to sub divs
    jQuery('.sub-div').css({
        'background': subDivBg,
        'border-color': subDivBorderColor
    });
    
    // Apply to fields
    jQuery('.form-field input, .form-field textarea, .form-field select').css({
        'background': fieldBg,
        'border-color': fieldBorderColor,
        'color': fieldTextColor
    });
    jQuery('.form-field label').css('color', labelColor);
    
    // Apply to buttons
    jQuery('.container-submit button').css({
        'background': buttonBg,
        'color': buttonTextColor
    });
    
    // Add hover effect
    jQuery('<style>')
        .prop('type', 'text/css')
        .html('.container-submit button:hover { background: ' + buttonHoverBg + ' !important; transform: translateY(-2px); }')
        .appendTo('head');
}

function updateAllSubmitButtons() {
    const btnColor = jQuery('#btnColor').val();
    const btnText = jQuery('#buttonText').val();
    jQuery('.container-submit button').css('background', btnColor);
    jQuery('.container-submit button').text(btnText);
}

function updateAllFieldsLabels() {
    const showLabels = jQuery('#showLabels').is(':checked');
    jQuery('.form-field').each(function() {
        const $field = $(this);
        const $label = $field.find('label:first');
        if (showLabels && !$field.hasClass('checkbox-field') && !$field.hasClass('radio-field')) {
            if ($label.length === 0) {
                const labelText = $field.data('label') || 'Field';
                $field.prepend(`<label>${labelText}<span class="edit-hint">✏️ Click to edit</span></label>`);
            }
        } else if (!showLabels && !$field.hasClass('checkbox-field') && !$field.hasClass('radio-field')) {
            $label.remove();
        }
    });
}

// ============================================
// LIVE TEXT EDITING FUNCTIONS
// ============================================
function makeLabelEditable($label, fieldId) {
    $label.attr('contenteditable', 'true');
    $label.addClass('editing');
    $label.focus();
    
    const range = document.createRange();
    const sel = window.getSelection();
    range.selectNodeContents($label[0]);
    sel.removeAllRanges();
    sel.addRange(range);
    
    $label.on('blur', function() {
        saveLabelEdit($(this), fieldId);
    });
    
    $label.on('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).blur();
        }
    });
}

function saveLabelEdit($label, fieldId) {
    const newLabel = $label.text().replace('✏️ Click to edit', '').trim();
    $label.removeAttr('contenteditable');
    $label.removeClass('editing');
    $label.off('blur keypress');
    
    for (const containerId in mainContainers) {
        const field = findItem(mainContainers[containerId], fieldId);
        if (field && field.type === 'field') {
            field.label = newLabel;
            $label.html(newLabel + '<span class="edit-hint">✏️ Click to edit</span>');
            $label.data('label', newLabel);
            break;
        }
    }
    
    autoSave();
}

function makePlaceholderEditable($input, fieldId) {
    $input.addClass('editing-placeholder');
    $input.focus();
    
    $input.on('blur', function() {
        savePlaceholderEdit($(this), fieldId);
    });
    
    $input.on('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).blur();
        }
    });
}

function savePlaceholderEdit($input, fieldId) {
    const newPlaceholder = $input.val();
    $input.removeClass('editing-placeholder');
    $input.off('blur keypress');
    
    for (const containerId in mainContainers) {
        const field = findItem(mainContainers[containerId], fieldId);
        if (field && field.type === 'field') {
            field.placeholder = newPlaceholder;
            $input.attr('placeholder', newPlaceholder);
            break;
        }
    }
    
    autoSave();
}

// ============================================
// MAIN CONTAINER
// ============================================
function addMainContainer() {
    const id = 'main_' + Date.now() + '_' + (++elementCounter);
    const btnColor = jQuery('#btnColor').val();
    const btnText = jQuery('#buttonText').val();
    const html = `
        <div class="main-container" data-id="${id}" style="background: #ffffff;">
            <div class="container-controls">
                <button onclick="editContainer('${id}')" title="Settings">⚙️</button>
                <button onclick="removeContainer('${id}')" title="Remove">🗑️</button>
            </div>
            <div class="drop-zone main-drop-zone" data-parent="${id}" data-container-type="main">
                <div class="container-placeholder">📦 Drag items here (fields, sub divs, social section)</div>
            </div>
            <div class="container-submit">
                <button style="background: ${btnColor}">${btnText}</button>
            </div>
        </div>
    `;
    jQuery('#formBuilder').append(html);
    mainContainers[id] = { id: id, type: 'main', bgColor: '#ffffff', items: [] };
    makeContainerDroppable(id, '.main-drop-zone', 'main');
    makeContainerSortable(id);
    applyLayoutChanges();
    applyCustomizationChanges();
}

// ============================================
// MAKE ANY CONTAINER DROPPABLE (Main or Sub)
// ============================================
function makeContainerDroppable(containerId, dropZoneClass, containerType) {
    const $dropZone = jQuery(`[data-id="${containerId}"] ${dropZoneClass}`);
    
    $dropZone.droppable({
        accept: containerType === 'social' ? '.social-draggable' : '.draggable-field:not(.main-draggable)',
        tolerance: 'pointer',
        hoverClass: containerType === 'main' ? 'drag-over' : (containerType === 'social' ? 'social-drag-over' : 'sub-drag-over'),
        greedy: true,
        drop: function(event, ui) {
            event.preventDefault();
            event.stopPropagation();
            
            if (!window.draggedItemData) return false;
            
            const type = window.draggedItemData.type;
            
            if (type === 'sub_div' && containerType !== 'social') {
                addSubDiv(containerId);
            } else if (type === 'social_section' && containerType !== 'social') {
                addSocialSection(containerId);
            } else if (type === 'social' && containerType === 'social') {
                const provider = window.draggedItemData.socialProvider;
                addSocialButtonToSocialSection(containerId, provider);
            } else if (type === 'social' && containerType !== 'social') {
                const provider = window.draggedItemData.socialProvider;
                addSocialButtonToContainer(containerId, provider);
            } else if (type === 'field' && containerType !== 'social') {
                const fieldType = window.draggedItemData.fieldType;
                const fieldLabel = window.draggedItemData.fieldLabel;
                const fieldHtmlType = window.draggedItemData.fieldHtmlType;
                addFieldToContainer(containerId, fieldType, fieldLabel, fieldHtmlType);
            }
            
            if ($dropZone.find('.container-placeholder').length) {
                $dropZone.find('.container-placeholder').remove();
            }
            autoSave();
            return false;
        }
    });
}

// ============================================
// ADD SUB DIV (Nested Support)
// ============================================
function addSubDiv(parentId) {
    const id = 'sub_' + Date.now() + '_' + (++elementCounter);
    const html = `
        <div class="sub-div" data-id="${id}" style="background: #f8f9fa;" onclick="event.stopPropagation(); selectElement('${id}', 'sub', 'Sub Div')">
            <div class="container-controls">
                <button onclick="event.stopPropagation(); editSubDiv('${id}')" title="Settings">⚙️</button>
                <button onclick="event.stopPropagation(); removeSubDiv('${id}')" title="Remove">🗑️</button>
            </div>
            <div class="drop-zone sub-drop-zone" data-parent="${id}" data-container-type="sub">
                <div class="container-placeholder">📦 Drag fields, sub divs, or social buttons here</div>
            </div>
        </div>
    `;
    
    // Find the parent container and append
    let parentFound = false;
    
    // Check if parent is main container
    if (mainContainers[parentId]) {
        jQuery(`.main-container[data-id="${parentId}"] .main-drop-zone`).append(html);
        if (!mainContainers[parentId].items) mainContainers[parentId].items = [];
        mainContainers[parentId].items.push({ id: id, type: 'sub', bgColor: '#f8f9fa', items: [], socialButtons: [] });
        parentFound = true;
    } else {
        // Search in all main containers for the parent sub div
        for (const containerId in mainContainers) {
            const parentItem = findItem(mainContainers[containerId], parentId);
            if (parentItem && parentItem.type === 'sub') {
                jQuery(`.sub-div[data-id="${parentId}"] .sub-drop-zone`).append(html);
                if (!parentItem.items) parentItem.items = [];
                parentItem.items.push({ id: id, type: 'sub', bgColor: '#f8f9fa', items: [], socialButtons: [] });
                parentFound = true;
                break;
            }
        }
    }
    
    if (parentFound) {
        makeContainerDroppable(id, '.sub-drop-zone', 'sub');
        makeSubDivSortable(id);
        applyLayoutChanges();
        applyCustomizationChanges();
    }
}

// ============================================
// ADD SOCIAL SECTION
// ============================================
function addSocialSection(parentId) {
    const id = 'social_' + Date.now() + '_' + (++elementCounter);
    const html = `
        <div class="social-section" data-id="${id}" style="background: #ffffff;" onclick="event.stopPropagation(); selectElement('${id}', 'social', 'Social Section')">
            <div class="section-title">🔗 Social Login</div>
            <div class="container-controls">
                <button onclick="event.stopPropagation(); editSocialSection('${id}')" title="Settings">⚙️</button>
                <button onclick="event.stopPropagation(); removeSocialSection('${id}')" title="Remove">🗑️</button>
            </div>
            <div class="drop-zone social-drop-zone" data-parent="${id}" data-container-type="social">
                <div class="container-placeholder">📦 Drag social login buttons here</div>
            </div>
        </div>
    `;
    
    if (mainContainers[parentId]) {
        jQuery(`.main-container[data-id="${parentId}"] .main-drop-zone`).append(html);
        if (!mainContainers[parentId].items) mainContainers[parentId].items = [];
        mainContainers[parentId].items.push({ id: id, type: 'social_section', bgColor: '#ffffff', socialButtons: [] });
    } else {
        for (const containerId in mainContainers) {
            const parentItem = findItem(mainContainers[containerId], parentId);
            if (parentItem && parentItem.type === 'sub') {
                jQuery(`.sub-div[data-id="${parentId}"] .sub-drop-zone`).append(html);
                if (!parentItem.items) parentItem.items = [];
                parentItem.items.push({ id: id, type: 'social_section', bgColor: '#ffffff', socialButtons: [] });
                break;
            }
        }
    }
    
    makeContainerDroppable(id, '.social-drop-zone', 'social');
}

// ============================================
// ADD FIELD TO ANY CONTAINER
// ============================================
function addFieldToContainer(parentId, fieldType, fieldLabel, fieldHtmlType) {
    const fieldId = 'field_' + Date.now() + '_' + (++elementCounter);
    const showLabels = jQuery('#showLabels').is(':checked');
    const isCheckbox = fieldHtmlType === 'checkbox';
    const isRadio = fieldHtmlType === 'radio';
    const fieldClass = isCheckbox ? 'checkbox-field' : (isRadio ? 'radio-field' : '');
    const placeholder = 'Enter ' + fieldLabel.toLowerCase();
    
    let fieldHtml = '';
    if (fieldHtmlType === 'textarea') {
        fieldHtml = `<textarea placeholder="${placeholder}"></textarea>`;
    } else if (fieldHtmlType === 'select') {
        fieldHtml = `<select><option>Select option</option></select>`;
    } else if (fieldHtmlType === 'checkbox') {
        fieldHtml = `<input type="checkbox" id="chk_${fieldId}"> <label for="chk_${fieldId}">${fieldLabel}</label>`;
    } else if (fieldHtmlType === 'radio') {
        fieldHtml = `<input type="radio" name="radio_${fieldId}" id="rad_${fieldId}"> <label for="rad_${fieldId}">${fieldLabel}</label>`;
    } else {
        fieldHtml = `<input type="${fieldHtmlType}" placeholder="${placeholder}">`;
    }
    
    const labelHtml = (showLabels && !isCheckbox && !isRadio) ? `<label data-field-id="${fieldId}">${fieldLabel}<span class="edit-hint">✏️ Click to edit</span></label>` : '';
    
    const fullHtml = `
        <div class="form-field ${fieldClass}" data-id="${fieldId}" data-label="${fieldLabel}" onclick="event.stopPropagation(); selectElement('${fieldId}', 'field', '${fieldLabel}')">
            ${labelHtml}
            ${fieldHtml}
            <button class="field-drag" title="Drag to reorder" onclick="event.stopPropagation()">⋮⋮</button>
            <button class="field-edit" onclick="event.stopPropagation(); editField('${fieldId}')" title="Advanced Settings">⚙️</button>
            <button class="field-remove" onclick="event.stopPropagation(); removeField('${fieldId}')" title="Remove">🗑️</button>
        </div>
    `;
    
    // Find parent and append
    let parentFound = false;
    
    if (mainContainers[parentId]) {
        jQuery(`.main-container[data-id="${parentId}"] .main-drop-zone`).append(fullHtml);
        if (!mainContainers[parentId].items) mainContainers[parentId].items = [];
        mainContainers[parentId].items.push({
            id: fieldId, type: 'field', fieldType: fieldType, label: fieldLabel,
            html_type: fieldHtmlType, required: false, placeholder: placeholder
        });
        parentFound = true;
    } else {
        for (const containerId in mainContainers) {
            const parentItem = findItem(mainContainers[containerId], parentId);
            if (parentItem) {
                if (parentItem.type === 'sub') {
                    jQuery(`.sub-div[data-id="${parentId}"] .sub-drop-zone`).append(fullHtml);
                    if (!parentItem.items) parentItem.items = [];
                    parentItem.items.push({
                        id: fieldId, type: 'field', fieldType: fieldType, label: fieldLabel,
                        html_type: fieldHtmlType, required: false, placeholder: placeholder
                    });
                } else if (parentItem.type === 'social_section') {
                    // Can't add fields to social section
                    return false;
                }
                parentFound = true;
                break;
            }
        }
    }
    
    if (parentFound) {
        if (showLabels && !isCheckbox && !isRadio) {
            const $label = jQuery(`.form-field[data-id="${fieldId}"] label`);
            $label.on('dblclick', function(e) {
                e.stopPropagation();
                makeLabelEditable(jQuery(this), fieldId);
            });
        }
        
        const $input = jQuery(`.form-field[data-id="${fieldId}"] input, .form-field[data-id="${fieldId}"] textarea`);
        if ($input.length && fieldHtmlType !== 'checkbox' && fieldHtmlType !== 'radio') {
            $input.on('dblclick', function(e) {
                e.stopPropagation();
                makePlaceholderEditable(jQuery(this), fieldId);
            });
            $input.attr('title', 'Double-click to edit placeholder text');
        }
        
        applyLayoutChanges();
        applyCustomizationChanges();
    }
}

// ============================================
// ADD SOCIAL BUTTON TO ANY CONTAINER
// ============================================
function addSocialButtonToContainer(parentId, provider) {
    const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
    const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
    
    const buttonHtml = `<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`;
    
    if (mainContainers[parentId]) {
        jQuery(`.main-container[data-id="${parentId}"] .main-drop-zone`).append(buttonHtml);
        if (!mainContainers[parentId].socialButtons) mainContainers[parentId].socialButtons = [];
        if (!mainContainers[parentId].socialButtons.includes(provider)) {
            mainContainers[parentId].socialButtons.push(provider);
        }
    } else {
        for (const containerId in mainContainers) {
            const parentItem = findItem(mainContainers[containerId], parentId);
            if (parentItem) {
                if (parentItem.type === 'sub') {
                    jQuery(`.sub-div[data-id="${parentId}"] .sub-drop-zone`).append(buttonHtml);
                    if (!parentItem.socialButtons) parentItem.socialButtons = [];
                    if (!parentItem.socialButtons.includes(provider)) {
                        parentItem.socialButtons.push(provider);
                    }
                }
                break;
            }
        }
    }
}

// ============================================
// ADD SOCIAL BUTTON TO SOCIAL SECTION
// ============================================
function addSocialButtonToSocialSection(socialId, provider) {
    const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
    const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
    
    const buttonHtml = `<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`;
    jQuery(`.social-section[data-id="${socialId}"] .social-drop-zone`).append(buttonHtml);
    
    for (const containerId in mainContainers) {
        const socialSection = findItem(mainContainers[containerId], socialId);
        if (socialSection && socialSection.type === 'social_section') {
            if (!socialSection.socialButtons) socialSection.socialButtons = [];
            if (!socialSection.socialButtons.includes(provider)) {
                socialSection.socialButtons.push(provider);
            }
            break;
        }
    }
}

// ============================================
// SORTABLE FUNCTIONS
// ============================================
function makeContainerSortable(containerId) {
    jQuery(`.main-container[data-id="${containerId}"] .main-drop-zone`).sortable({
        placeholder: 'sortable-placeholder',
        items: '.form-field, .sub-div, .social-section',
        handle: '.field-drag',
        cancel: '.container-controls button',
        update: function() { autoSave(); }
    });
}

function makeSubDivSortable(subId) {
    jQuery(`.sub-div[data-id="${subId}"] .sub-drop-zone`).sortable({
        placeholder: 'sortable-placeholder',
        items: '.form-field, .sub-div, .social-section',
        handle: '.field-drag',
        cancel: '.container-controls button',
        update: function() { autoSave(); }
    });
}

// ============================================
// REMOVE FUNCTIONS
// ============================================
function removeField(fieldId) {
    jQuery(`.form-field[data-id="${fieldId}"]`).remove();
    
    for (const containerId in mainContainers) {
        findAndUpdateItem(mainContainers[containerId], fieldId, null, 'remove');
    }
    
    if (selectedElementId === fieldId) {
        clearSelection();
    }
    
    autoSave();
}

function removeSubDiv(subId) {
    jQuery(`.sub-div[data-id="${subId}"]`).remove();
    for (const containerId in mainContainers) {
        findAndUpdateItem(mainContainers[containerId], subId, null, 'remove');
    }
    
    if (selectedElementId === subId) {
        clearSelection();
    }
    
    autoSave();
}

function removeSocialSection(socialId) {
    jQuery(`.social-section[data-id="${socialId}"]`).remove();
    for (const containerId in mainContainers) {
        findAndUpdateItem(mainContainers[containerId], socialId, null, 'remove');
    }
    
    if (selectedElementId === socialId) {
        clearSelection();
    }
    
    autoSave();
}

function removeContainer(containerId) {
    delete mainContainers[containerId];
    jQuery(`.main-container[data-id="${containerId}"]`).remove();
    if (Object.keys(mainContainers).length === 0) {
        jQuery('#formBuilder').html('<div class="builder-placeholder">📦 Drag a Main Container here to start</div>');
        initMainDroppable();
    }
    
    if (selectedElementId === containerId) {
        clearSelection();
    }
    
    autoSave();
}

// ============================================
// EDIT FUNCTIONS
// ============================================
function editField(fieldId) {
    editingFieldId = fieldId;
    let field = null;
    
    for (const containerId in mainContainers) {
        field = findItem(mainContainers[containerId], fieldId);
        if (field) break;
    }
    
    if (field) {
        jQuery('#fieldPlaceholder').val(field.placeholder || '');
        jQuery('#fieldRequired').prop('checked', field.required || false);
        jQuery('#fieldSettingsModal').show();
    }
}

function saveFieldSettings() {
    if (!editingFieldId) return;
    const placeholder = jQuery('#fieldPlaceholder').val();
    const required = jQuery('#fieldRequired').is(':checked');
    
    for (const containerId in mainContainers) {
        const field = findItem(mainContainers[containerId], editingFieldId);
        if (field && field.type === 'field') {
            field.placeholder = placeholder;
            field.required = required;
            const $field = jQuery(`.form-field[data-id="${editingFieldId}"]`);
            $field.find('input, textarea, select').attr('placeholder', placeholder);
            if (required) {
                $field.find('input, textarea, select').attr('required', 'required');
            } else {
                $field.find('input, textarea, select').removeAttr('required');
            }
            break;
        }
    }
    
    closeFieldModal();
    autoSave();
}

function editContainer(containerId) {
    editingContainerId = containerId;
    jQuery('#containerBgColor').val(mainContainers[containerId]?.bgColor || '#ffffff');
    jQuery('#containerSettingsModal').show();
}

function editSubDiv(subId) {
    editingContainerId = subId;
    for (const containerId in mainContainers) {
        const subDiv = findItem(mainContainers[containerId], subId);
        if (subDiv && subDiv.type === 'sub') {
            jQuery('#containerBgColor').val(subDiv.bgColor || '#f8f9fa');
            break;
        }
    }
    jQuery('#containerSettingsModal').show();
}

function editSocialSection(socialId) {
    editingContainerId = socialId;
    for (const containerId in mainContainers) {
        const socialSection = findItem(mainContainers[containerId], socialId);
        if (socialSection && socialSection.type === 'social_section') {
            jQuery('#containerBgColor').val(socialSection.bgColor || '#ffffff');
            break;
        }
    }
    jQuery('#containerSettingsModal').show();
}

function saveContainerSettings() {
    if (!editingContainerId) return;
    const bgColor = jQuery('#containerBgColor').val();
    
    if (jQuery(`.main-container[data-id="${editingContainerId}"]`).length) {
        mainContainers[editingContainerId].bgColor = bgColor;
        jQuery(`.main-container[data-id="${editingContainerId}"]`).css('background', bgColor);
    } else if (jQuery(`.sub-div[data-id="${editingContainerId}"]`).length) {
        for (const containerId in mainContainers) {
            const subDiv = findItem(mainContainers[containerId], editingContainerId);
            if (subDiv && subDiv.type === 'sub') {
                subDiv.bgColor = bgColor;
                jQuery(`.sub-div[data-id="${editingContainerId}"]`).css('background', bgColor);
                break;
            }
        }
    } else if (jQuery(`.social-section[data-id="${editingContainerId}"]`).length) {
        for (const containerId in mainContainers) {
            const socialSection = findItem(mainContainers[containerId], editingContainerId);
            if (socialSection && socialSection.type === 'social_section') {
                socialSection.bgColor = bgColor;
                jQuery(`.social-section[data-id="${editingContainerId}"]`).css('background', bgColor);
                break;
            }
        }
    }
    closeContainerModal();
    autoSave();
}

// ============================================
// CSS, JS, SETTINGS
// ============================================
function saveCSS() {
    if (!currentFormId) { alert('Select a form first'); return; }
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_save_form_css', form_id: currentFormId, css_content: jQuery('#customCSS').val(), nonce: my_login_ajax.nonces.save_css },
        success: function(r) { if (r.success) alert('CSS saved!'); else alert('Error saving CSS'); }
    });
}

function saveJS() {
    if (!currentFormId) { alert('Select a form first'); return; }
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_save_form_js', form_id: currentFormId, js_content: jQuery('#customJS').val(), nonce: my_login_ajax.nonces.save_js },
        success: function(r) { if (r.success) alert('JavaScript saved!'); else alert('Error saving JavaScript'); }
    });
}

function applyPreset(preset) {
    const presets = {
        modern: { btn_color: '#667eea' },
        minimal: { btn_color: '#000000' },
        dark: { btn_color: '#4CAF50' }
    };
    const p = presets[preset];
    if (p) {
        jQuery('#btnColor').val(p.btn_color);
        updateAllSubmitButtons();
        autoSave();
    }
}

// ============================================
// MODAL FUNCTIONS
// ============================================
function closeFieldModal() { jQuery('#fieldSettingsModal').hide(); editingFieldId = null; }
function closeContainerModal() { jQuery('#containerSettingsModal').hide(); editingContainerId = null; }
function closeModal() { jQuery('#createFormModal').hide(); }

// ============================================
// BUILD FORM DATA
// ============================================
function buildFormData() {
    const data = {};
    for (const id in mainContainers) {
        data[id] = {
            type: 'main',
            bgColor: mainContainers[id].bgColor,
            items: mainContainers[id].items || [],
            socialButtons: mainContainers[id].socialButtons || []
        };
    }
    return {
        form_id: currentFormId,
        containers: data,
        settings: {
            btn_color: jQuery('#btnColor').val(),
            button_text: jQuery('#buttonText').val(),
            show_labels: jQuery('#showLabels').is(':checked'),
            layout: {
                container_width: jQuery('#layout_container_width').val(),
                container_padding: jQuery('#layout_container_padding').val(),
                border_radius: jQuery('#layout_border_radius').val(),
                field_spacing: jQuery('#layout_field_spacing').val(),
                label_font_size: jQuery('#layout_label_font_size').val(),
                input_padding: jQuery('#layout_input_padding').val(),
                button_width: jQuery('#layout_button_width').val(),
                button_padding: jQuery('#layout_button_padding').val()
            },
            customization: {
                container_bg: jQuery('#custom_container_bg').val(),
                container_border_color: jQuery('#custom_container_border_color').val(),
                sub_div_bg: jQuery('#custom_sub_div_bg').val(),
                sub_div_border_color: jQuery('#custom_sub_div_border_color').val(),
                field_bg: jQuery('#custom_field_bg').val(),
                field_border_color: jQuery('#custom_field_border_color').val(),
                field_text_color: jQuery('#custom_field_text_color').val(),
                label_color: jQuery('#custom_label_color').val(),
                button_bg: jQuery('#custom_button_bg').val(),
                button_text_color: jQuery('#custom_button_text_color').val(),
                button_hover_bg: jQuery('#custom_button_hover_bg').val()
            }
        }
    };
}

// ============================================
// SAVE & LOAD
// ============================================
function autoSave() { if (currentFormId) saveForm(); }

function saveForm() {
    if (!currentFormId) return;
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_save_form_settings', form_data: JSON.stringify(buildFormData()), nonce: my_login_ajax.nonces.save_form },
        success: function(r) { if (r.success) console.log('Saved'); else console.error('Save error:', r); }
    });
}

function saveCurrentForm() {
    if (!currentFormId) alert('Select a form first');
    else { saveForm(); alert('Form saved!'); }
}

// Recursive function to build nested HTML when loading
function buildNestedItemsHtml(items, settings, level = 0) {
    let html = '';
    for (const item of items) {
        if (item.type === 'sub') {
            html += `
                <div class="sub-div" data-id="${item.id}" style="background: ${item.bgColor || '#f8f9fa'};" onclick="event.stopPropagation(); selectElement('${item.id}', 'sub', 'Sub Div')">
                    <div class="container-controls">
                        <button onclick="event.stopPropagation(); editSubDiv('${item.id}')" title="Settings">⚙️</button>
                        <button onclick="event.stopPropagation(); removeSubDiv('${item.id}')" title="Remove">🗑️</button>
                    </div>
                    <div class="drop-zone sub-drop-zone" data-parent="${item.id}" data-container-type="sub">
                        <div class="container-placeholder">📦 Drag fields, sub divs, or social buttons here</div>
                    </div>
                </div>
            `;
            // Recursively build nested items
            if (item.items && item.items.length) {
                // Need to append after the sub div is created
                setTimeout(() => {
                    const $subZone = jQuery(`.sub-div[data-id="${item.id}"] .sub-drop-zone`);
                    const nestedHtml = buildNestedItemsHtml(item.items, settings, level + 1);
                    $subZone.append(nestedHtml);
                    // Initialize droppable for this sub div
                    makeContainerDroppable(item.id, '.sub-drop-zone', 'sub');
                    makeSubDivSortable(item.id);
                    // Add live editing for fields
                    addLiveEditingToFields(item.items, settings);
                }, 10);
            }
            if (item.socialButtons && item.socialButtons.length) {
                const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
                const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
                setTimeout(() => {
                    item.socialButtons.forEach(provider => {
                        jQuery(`.sub-div[data-id="${item.id}"] .sub-drop-zone`).append(`<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`);
                    });
                }, 10);
            }
        } else if (item.type === 'social_section') {
            html += `
                <div class="social-section" data-id="${item.id}" style="background: ${item.bgColor || '#ffffff'};" onclick="event.stopPropagation(); selectElement('${item.id}', 'social', 'Social Section')">
                    <div class="section-title">🔗 Social Login</div>
                    <div class="container-controls">
                        <button onclick="event.stopPropagation(); editSocialSection('${item.id}')" title="Settings">⚙️</button>
                        <button onclick="event.stopPropagation(); removeSocialSection('${item.id}')" title="Remove">🗑️</button>
                    </div>
                    <div class="drop-zone social-drop-zone" data-parent="${item.id}" data-container-type="social">
                        <div class="container-placeholder">📦 Drag social login buttons here</div>
                    </div>
                </div>
            `;
            if (item.socialButtons && item.socialButtons.length) {
                const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
                const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
                setTimeout(() => {
                    item.socialButtons.forEach(provider => {
                        jQuery(`.social-section[data-id="${item.id}"] .social-drop-zone`).append(`<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`);
                    });
                    makeContainerDroppable(item.id, '.social-drop-zone', 'social');
                }, 10);
            } else {
                setTimeout(() => {
                    makeContainerDroppable(item.id, '.social-drop-zone', 'social');
                }, 10);
            }
        } else if (item.type === 'field') {
            const showLabels = settings.show_labels || false;
            const isCheckbox = item.html_type === 'checkbox';
            const isRadio = item.html_type === 'radio';
            const fieldClass = isCheckbox ? 'checkbox-field' : (isRadio ? 'radio-field' : '');
            
            let fieldHtml = '';
            if (item.html_type === 'textarea') {
                fieldHtml = `<textarea placeholder="${item.placeholder || ''}" ${item.required ? 'required' : ''}></textarea>`;
            } else if (item.html_type === 'select') {
                fieldHtml = `<select ${item.required ? 'required' : ''}><option>Select option</option></select>`;
            } else if (item.html_type === 'checkbox') {
                fieldHtml = `<input type="checkbox" id="chk_${item.id}" ${item.required ? 'required' : ''}> <label for="chk_${item.id}">${item.label}</label>`;
            } else if (item.html_type === 'radio') {
                fieldHtml = `<input type="radio" name="radio_${item.id}" id="rad_${item.id}" ${item.required ? 'required' : ''}> <label for="rad_${item.id}">${item.label}</label>`;
            } else {
                fieldHtml = `<input type="${item.html_type}" placeholder="${item.placeholder || ''}" ${item.required ? 'required' : ''}>`;
            }
            
            const labelHtml = (showLabels && !isCheckbox && !isRadio) ? `<label data-field-id="${item.id}">${item.label}<span class="edit-hint">✏️ Click to edit</span></label>` : '';
            
            html += `
                <div class="form-field ${fieldClass}" data-id="${item.id}" data-label="${item.label}" onclick="event.stopPropagation(); selectElement('${item.id}', 'field', '${item.label}')">
                    ${labelHtml}
                    ${fieldHtml}
                    <button class="field-drag" onclick="event.stopPropagation()">⋮⋮</button>
                    <button class="field-edit" onclick="event.stopPropagation(); editField('${item.id}')" title="Advanced Settings">⚙️</button>
                    <button class="field-remove" onclick="event.stopPropagation(); removeField('${item.id}')" title="Remove">🗑️</button>
                </div>
            `;
        }
    }
    return html;
}

function addLiveEditingToFields(items, settings) {
    const showLabels = settings.show_labels || false;
    for (const item of items) {
        if (item.type === 'field') {
            const isCheckbox = item.html_type === 'checkbox';
            const isRadio = item.html_type === 'radio';
            
            if (showLabels && !isCheckbox && !isRadio) {
                const $label = jQuery(`.form-field[data-id="${item.id}"] label`);
                if ($label.length) {
                    $label.off('dblclick').on('dblclick', function(e) {
                        e.stopPropagation();
                        makeLabelEditable(jQuery(this), item.id);
                    });
                }
            }
            
            const $input = jQuery(`.form-field[data-id="${item.id}"] input, .form-field[data-id="${item.id}"] textarea`);
            if ($input.length && item.html_type !== 'checkbox' && item.html_type !== 'radio') {
                $input.off('dblclick').on('dblclick', function(e) {
                    e.stopPropagation();
                    makePlaceholderEditable(jQuery(this), item.id);
                });
                $input.attr('title', 'Double-click to edit placeholder text');
            }
        } else if (item.type === 'sub' && item.items) {
            addLiveEditingToFields(item.items, settings);
        }
    }
}

function loadForm(formId) {
    currentFormId = formId;
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_get_form', form_id: formId, nonce: my_login_ajax.nonces.get_form },
        success: function(r) {
            if (r.success && r.data) {
                mainContainers = {};
                jQuery('#formBuilder').empty();
                const data = r.data.containers || {};
                const settings = r.data.settings || {};
                
                jQuery('#btnColor').val(settings.btn_color || '#0073aa');
                jQuery('#buttonText').val(settings.button_text || 'Submit');
                jQuery('#showLabels').prop('checked', settings.show_labels || false);
                
                // Load layout settings
                if (settings.layout) {
                    jQuery('#layout_container_width').val(settings.layout.container_width || '100%');
                    jQuery('#layout_container_padding').val(settings.layout.container_padding || '20');
                    jQuery('#layout_border_radius').val(settings.layout.border_radius || '16');
                    jQuery('#layout_field_spacing').val(settings.layout.field_spacing || '12');
                    jQuery('#layout_label_font_size').val(settings.layout.label_font_size || '14');
                    jQuery('#layout_input_padding').val(settings.layout.input_padding || '10');
                    jQuery('#layout_button_width').val(settings.layout.button_width || 'auto');
                    jQuery('#layout_button_padding').val(settings.layout.button_padding || '12');
                    
                    jQuery('#container_padding_value').text(settings.layout.container_padding + 'px');
                    jQuery('#border_radius_value').text(settings.layout.border_radius + 'px');
                    jQuery('#field_spacing_value').text(settings.layout.field_spacing + 'px');
                    jQuery('#label_font_size_value').text(settings.layout.label_font_size + 'px');
                    jQuery('#input_padding_value').text(settings.layout.input_padding + 'px');
                    jQuery('#button_padding_value').text(settings.layout.button_padding + 'px');
                }
                
                // Load customization settings
                if (settings.customization) {
                    jQuery('#custom_container_bg').val(settings.customization.container_bg || '#ffffff');
                    jQuery('#custom_container_border_color').val(settings.customization.container_border_color || '#667eea');
                    jQuery('#custom_sub_div_bg').val(settings.customization.sub_div_bg || '#f8f9fa');
                    jQuery('#custom_sub_div_border_color').val(settings.customization.sub_div_border_color || '#f5576c');
                    jQuery('#custom_field_bg').val(settings.customization.field_bg || '#ffffff');
                    jQuery('#custom_field_border_color').val(settings.customization.field_border_color || '#e0e0e0');
                    jQuery('#custom_field_text_color').val(settings.customization.field_text_color || '#333333');
                    jQuery('#custom_label_color').val(settings.customization.label_color || '#333333');
                    jQuery('#custom_button_bg').val(settings.customization.button_bg || '#0073aa');
                    jQuery('#custom_button_text_color').val(settings.customization.button_text_color || '#ffffff');
                    jQuery('#custom_button_hover_bg').val(settings.customization.button_hover_bg || '#005a87');
                }
                
                updateAllSubmitButtons();
                
                for (const [id, containerData] of Object.entries(data)) {
                    const btnColor = settings.btn_color || '#0073aa';
                    const btnText = settings.button_text || 'Submit';
                    const mainHtml = `
                        <div class="main-container" data-id="${id}" style="background: ${containerData.bgColor || '#ffffff'};">
                            <div class="container-controls">
                                <button onclick="editContainer('${id}')" title="Settings">⚙️</button>
                                <button onclick="removeContainer('${id}')" title="Remove">🗑️</button>
                            </div>
                            <div class="drop-zone main-drop-zone" data-parent="${id}" data-container-type="main">
                                <div class="container-placeholder">📦 Drag items here</div>
                            </div>
                            <div class="container-submit">
                                <button style="background: ${btnColor}">${btnText}</button>
                            </div>
                        </div>
                    `;
                    jQuery('#formBuilder').append(mainHtml);
                    
                    mainContainers[id] = {
                        id: id,
                        type: 'main',
                        bgColor: containerData.bgColor,
                        items: containerData.items || [],
                        socialButtons: containerData.socialButtons || []
                    };
                    
                    // Build nested items
                    const $mainZone = jQuery(`.main-container[data-id="${id}"] .main-drop-zone`);
                    const nestedHtml = buildNestedItemsHtml(containerData.items || [], settings);
                    $mainZone.append(nestedHtml);
                    
                    // Add social buttons to main container
                    if (containerData.socialButtons) {
                        const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
                        const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
                        containerData.socialButtons.forEach(provider => {
                            jQuery(`.main-container[data-id="${id}"] .main-drop-zone`).append(`<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`);
                        });
                    }
                    
                    makeContainerDroppable(id, '.main-drop-zone', 'main');
                    makeContainerSortable(id);
                    
                    // Add live editing to fields in main container
                    addLiveEditingToFields(containerData.items || [], settings);
                }
                
                if (Object.keys(mainContainers).length === 0) {
                    jQuery('#formBuilder').html('<div class="builder-placeholder">📦 Drag a Main Container here to start</div>');
                    initMainDroppable();
                }
                
                updateAllFieldsLabels();
                applyLayoutChanges();
                applyCustomizationChanges();
                
                jQuery('.form-item').removeClass('active');
                jQuery(`.form-item[data-form-id="${formId}"]`).addClass('active');
                initDraggable();
                
                // Clear any existing selection
                clearSelection();
            }
        }
    });
}

function clearAllFields() {
    if (confirm('Clear all fields?')) {
        mainContainers = {};
        jQuery('#formBuilder').html('<div class="builder-placeholder">📦 Drag a Main Container here to start</div>');
        initMainDroppable();
        clearSelection();
        autoSave();
    }
}

function duplicateForm(formId) {
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_duplicate_form', form_id: formId, nonce: my_login_ajax.nonces.duplicate_form },
        success: function(r) { if (r.success) location.reload(); else alert('Error duplicating form'); }
    });
}

function deleteForm(formId) {
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_delete_form', form_id: formId, nonce: my_login_ajax.nonces.delete_form },
        success: function(r) { if (r.success) location.reload(); else alert('Error deleting form'); }
    });
}

function createNewForm() {
    const formName = jQuery('#newFormName').val();
    if (!formName) { alert('Enter form name'); return; }
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_create_form', form_name: formName, form_type: jQuery('#newFormType').val(), nonce: my_login_ajax.nonces.create_form },
        success: function(r) { if (r.success) location.reload(); else alert('Error creating form'); }
    });
}

function initMainDroppable() {
    jQuery('#formBuilder').droppable({
        accept: '.main-draggable',
        tolerance: 'pointer',
        hoverClass: 'droppable-active',
        drop: function(event, ui) {
            addMainContainer();
            jQuery('.builder-placeholder').remove();
            autoSave();
            return false;
        }
    });
}

function initDraggable() {
    jQuery('.draggable-field').draggable({
        helper: 'clone',
        revert: 'invalid',
        cursor: 'move',
        opacity: 0.7,
        zIndex: 1000,
        appendTo: 'body',
        start: function(event, ui) {
            window.draggedItemData = {
                type: $(this).data('type'),
                fieldType: $(this).data('field-type'),
                fieldLabel: $(this).data('field-label'),
                fieldHtmlType: $(this).data('field-html-type'),
                socialProvider: $(this).data('social-provider')
            };
        },
        stop: function() {
            window.draggedItemData = null;
        }
    });
}
</script>
</body>
</html>

<?php ob_end_flush(); ?>



























<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
var my_login_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonces: {
        create_form: '<?php echo $create_nonce; ?>',
        delete_form: '<?php echo $delete_nonce; ?>',
        duplicate_form: '<?php echo $duplicate_nonce; ?>',
        get_form: '<?php echo $get_nonce; ?>',
        save_form: '<?php echo $save_nonce; ?>',
        save_css: '<?php echo wp_create_nonce('my_login_save_form_css'); ?>',
        save_js: '<?php echo wp_create_nonce('my_login_save_form_js'); ?>'
    }
};

let currentFormId = <?php echo $current_form_id ? $current_form_id : 'null'; ?>;
let mainContainers = {};
let elementCounter = 0;
let editingFieldId = null;
let editingContainerId = null;
let selectedElementId = null;
let selectedElementType = null;
let draggedItemData = null;

// Helper functions
function findItemInContainer(items, fieldId) {
    if (!items) return null;
    
    for (const item of items) {
        if (item.id === fieldId) {
            return item;
        }
        if (item.type === 'sub' && item.items) {
            const found = findItemInContainer(item.items, fieldId);
            if (found) return found;
        }
        if (item.type === 'social_section' && item.items) {
            const found = findItemInContainer(item.items, fieldId);
            if (found) return found;
        }
    }
    return null;
}

function updateItemInContainer(items, fieldId, updates) {
    for (let i = 0; i < items.length; i++) {
        if (items[i].id === fieldId) {
            Object.assign(items[i], updates);
            return true;
        }
        if (items[i].type === 'sub' && items[i].items) {
            if (updateItemInContainer(items[i].items, fieldId, updates)) return true;
        }
        if (items[i].type === 'social_section' && items[i].items) {
            if (updateItemInContainer(items[i].items, fieldId, updates)) return true;
        }
    }
    return false;
}

function removeItemFromContainer(items, fieldId) {
    for (let i = 0; i < items.length; i++) {
        if (items[i].id === fieldId) {
            items.splice(i, 1);
            return true;
        }
        if (items[i].type === 'sub' && items[i].items) {
            if (removeItemFromContainer(items[i].items, fieldId)) return true;
        }
        if (items[i].type === 'social_section' && items[i].items) {
            if (removeItemFromContainer(items[i].items, fieldId)) return true;
        }
    }
    return false;
}

function findParentContainer(items, childId, parentPath = null) {
    for (const item of items) {
        if (item.id === childId) {
            return parentPath;
        }
        if (item.type === 'sub' && item.items) {
            const result = findParentContainer(item.items, childId, item);
            if (result) return result;
        }
        if (item.type === 'social_section' && item.items) {
            const result = findParentContainer(item.items, childId, item);
            if (result) return result;
        }
    }
    return null;
}

// Select element
function selectElement(elementId, elementType, elementName) {
    if (selectedElementId) {
        jQuery(`.form-field[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.sub-div[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.social-section[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.main-container[data-id="${selectedElementId}"]`).removeClass('selected');
    }
    
    selectedElementId = elementId;
    selectedElementType = elementType;
    
    jQuery(`.form-field[data-id="${elementId}"]`).addClass('selected');
    jQuery(`.sub-div[data-id="${elementId}"]`).addClass('selected');
    jQuery(`.social-section[data-id="${elementId}"]`).addClass('selected');
    jQuery(`.main-container[data-id="${elementId}"]`).addClass('selected');
    
    jQuery('#selectedItemName').text(elementName);
    jQuery('#selectionInfo').show();
    
    loadLayoutOptionsForElement(elementId, elementType);
    loadCustomizationOptionsForElement(elementId, elementType);
}

function clearSelection() {
    if (selectedElementId) {
        jQuery(`.form-field[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.sub-div[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.social-section[data-id="${selectedElementId}"]`).removeClass('selected');
        jQuery(`.main-container[data-id="${selectedElementId}"]`).removeClass('selected');
    }
    selectedElementId = null;
    selectedElementType = null;
    jQuery('#selectionInfo').hide();
    
    jQuery('#layoutContent').html('<div class="settings-group"><p style="text-align: center; color: #999; padding: 40px;">📌 Select a field, sub-div, or main container to enable layout customization</p></div>');
    jQuery('#customizationContent').html('<div class="settings-group"><p style="text-align: center; color: #999; padding: 40px;">🎨 Select a field, sub-div, or main container to enable style customization</p></div>');
}

// Load layout options
function loadLayoutOptionsForElement(elementId, elementType) {
    let html = '';
    
    if (elementType === 'field') {
        html = `
            <div class="settings-group">
                <h4>📏 Field Layout Settings</h4>
                <div class="setting-field">
                    <label>Field Width (%)</label>
                    <input type="range" id="field_width" min="50" max="100" step="5" value="100" onchange="updateFieldLayout('${elementId}', 'width', this.value)">
                    <span class="range-value" id="field_width_val">100%</span>
                </div>
                <div class="setting-field">
                    <label>Margin Bottom (px)</label>
                    <input type="range" id="field_margin" min="0" max="30" step="2" value="12" onchange="updateFieldLayout('${elementId}', 'marginBottom', this.value)">
                    <span class="range-value" id="field_margin_val">12px</span>
                </div>
                <div class="setting-field">
                    <label>Label Font Size (px)</label>
                    <input type="range" id="field_label_font" min="10" max="20" step="1" value="14" onchange="updateFieldLayout('${elementId}', 'labelFontSize', this.value)">
                    <span class="range-value" id="field_label_font_val">14px</span>
                </div>
                <div class="setting-field">
                    <label>Input Padding (px)</label>
                    <input type="range" id="field_input_padding" min="5" max="20" step="1" value="10" onchange="updateFieldLayout('${elementId}', 'inputPadding', this.value)">
                    <span class="range-value" id="field_input_padding_val">10px</span>
                </div>
            </div>
        `;
    } else if (elementType === 'sub') {
        html = `
            <div class="settings-group">
                <h4>🗂️ Sub Div Layout Settings</h4>
                <div class="setting-field">
                    <label>Sub Div Width (%)</label>
                    <input type="range" id="subdiv_width" min="70" max="100" step="5" value="100" onchange="updateSubDivLayout('${elementId}', 'width', this.value)">
                    <span class="range-value" id="subdiv_width_val">100%</span>
                </div>
                <div class="setting-field">
                    <label>Padding (px)</label>
                    <input type="range" id="subdiv_padding" min="5" max="30" step="2" value="15" onchange="updateSubDivLayout('${elementId}', 'padding', this.value)">
                    <span class="range-value" id="subdiv_padding_val">15px</span>
                </div>
                <div class="setting-field">
                    <label>Margin (px)</label>
                    <input type="range" id="subdiv_margin" min="0" max="20" step="2" value="10" onchange="updateSubDivLayout('${elementId}', 'margin', this.value)">
                    <span class="range-value" id="subdiv_margin_val">10px</span>
                </div>
                <div class="setting-field">
                    <label>Border Radius (px)</label>
                    <input type="range" id="subdiv_border_radius" min="0" max="30" step="2" value="12" onchange="updateSubDivLayout('${elementId}', 'borderRadius', this.value)">
                    <span class="range-value" id="subdiv_border_radius_val">12px</span>
                </div>
            </div>
        `;
    } else if (elementType === 'main') {
        html = `
            <div class="settings-group">
                <h4>📦 Main Container Layout Settings</h4>
                <div class="setting-field">
                    <label>Container Width (%)</label>
                    <input type="range" id="main_width" min="50" max="100" step="5" value="100" onchange="updateMainLayout('${elementId}', 'width', this.value)">
                    <span class="range-value" id="main_width_val">100%</span>
                </div>
                <div class="setting-field">
                    <label>Padding (px)</label>
                    <input type="range" id="main_padding" min="5" max="40" step="2" value="20" onchange="updateMainLayout('${elementId}', 'padding', this.value)">
                    <span class="range-value" id="main_padding_val">20px</span>
                </div>
                <div class="setting-field">
                    <label>Margin Bottom (px)</label>
                    <input type="range" id="main_margin" min="0" max="30" step="2" value="20" onchange="updateMainLayout('${elementId}', 'marginBottom', this.value)">
                    <span class="range-value" id="main_margin_val">20px</span>
                </div>
                <div class="setting-field">
                    <label>Border Radius (px)</label>
                    <input type="range" id="main_border_radius" min="0" max="40" step="2" value="16" onchange="updateMainLayout('${elementId}', 'borderRadius', this.value)">
                    <span class="range-value" id="main_border_radius_val">16px</span>
                </div>
            </div>
        `;
    }
    
    jQuery('#layoutContent').html(html);
    
    if (elementType === 'field') {
        jQuery('#field_width').on('input', function() { jQuery('#field_width_val').text(jQuery(this).val() + '%'); });
        jQuery('#field_margin').on('input', function() { jQuery('#field_margin_val').text(jQuery(this).val() + 'px'); });
        jQuery('#field_label_font').on('input', function() { jQuery('#field_label_font_val').text(jQuery(this).val() + 'px'); });
        jQuery('#field_input_padding').on('input', function() { jQuery('#field_input_padding_val').text(jQuery(this).val() + 'px'); });
    } else if (elementType === 'sub') {
        jQuery('#subdiv_width').on('input', function() { jQuery('#subdiv_width_val').text(jQuery(this).val() + '%'); });
        jQuery('#subdiv_padding').on('input', function() { jQuery('#subdiv_padding_val').text(jQuery(this).val() + 'px'); });
        jQuery('#subdiv_margin').on('input', function() { jQuery('#subdiv_margin_val').text(jQuery(this).val() + 'px'); });
        jQuery('#subdiv_border_radius').on('input', function() { jQuery('#subdiv_border_radius_val').text(jQuery(this).val() + 'px'); });
    } else if (elementType === 'main') {
        jQuery('#main_width').on('input', function() { jQuery('#main_width_val').text(jQuery(this).val() + '%'); });
        jQuery('#main_padding').on('input', function() { jQuery('#main_padding_val').text(jQuery(this).val() + 'px'); });
        jQuery('#main_margin').on('input', function() { jQuery('#main_margin_val').text(jQuery(this).val() + 'px'); });
        jQuery('#main_border_radius').on('input', function() { jQuery('#main_border_radius_val').text(jQuery(this).val() + 'px'); });
    }
}

// Load customization options
function loadCustomizationOptionsForElement(elementId, elementType) {
    let html = '';
    
    if (elementType === 'field') {
        html = `
            <div class="settings-group">
                <h4>🎨 Field Style Customization</h4>
                <div class="setting-field">
                    <label>Background Color</label>
                    <input type="color" id="field_bg" value="#ffffff" onchange="updateFieldStyle('${elementId}', 'backgroundColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Color</label>
                    <input type="color" id="field_border" value="#e0e0e0" onchange="updateFieldStyle('${elementId}', 'borderColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Text Color</label>
                    <input type="color" id="field_text" value="#333333" onchange="updateFieldStyle('${elementId}', 'textColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Label Color</label>
                    <input type="color" id="field_label_color" value="#333333" onchange="updateFieldStyle('${elementId}', 'labelColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Width (px)</label>
                    <input type="number" id="field_border_width" min="0" max="5" step="1" value="1" onchange="updateFieldStyle('${elementId}', 'borderWidth', this.value + 'px')">
                </div>
            </div>
        `;
    } else if (elementType === 'sub') {
        html = `
            <div class="settings-group">
                <h4>🎨 Sub Div Style Customization</h4>
                <div class="setting-field">
                    <label>Background Color</label>
                    <input type="color" id="subdiv_bg" value="#f8f9fa" onchange="updateSubDivStyle('${elementId}', 'backgroundColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Color</label>
                    <input type="color" id="subdiv_border" value="#f5576c" onchange="updateSubDivStyle('${elementId}', 'borderColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Width (px)</label>
                    <input type="number" id="subdiv_border_width" min="1" max="5" step="1" value="2" onchange="updateSubDivStyle('${elementId}', 'borderWidth', this.value + 'px')">
                </div>
                <div class="setting-field">
                    <label>Box Shadow</label>
                    <select id="subdiv_shadow" onchange="updateSubDivStyle('${elementId}', 'boxShadow', this.value)">
                        <option value="none">None</option>
                        <option value="0 2px 4px rgba(0,0,0,0.1)">Light</option>
                        <option value="0 4px 8px rgba(0,0,0,0.15)">Medium</option>
                        <option value="0 8px 16px rgba(0,0,0,0.2)">Strong</option>
                    </select>
                </div>
            </div>
        `;
    } else if (elementType === 'main') {
        html = `
            <div class="settings-group">
                <h4>🎨 Main Container Style Customization</h4>
                <div class="setting-field">
                    <label>Background Color</label>
                    <input type="color" id="main_bg" value="#ffffff" onchange="updateMainStyle('${elementId}', 'backgroundColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Color</label>
                    <input type="color" id="main_border" value="#667eea" onchange="updateMainStyle('${elementId}', 'borderColor', this.value)">
                </div>
                <div class="setting-field">
                    <label>Border Width (px)</label>
                    <input type="number" id="main_border_width" min="1" max="5" step="1" value="2" onchange="updateMainStyle('${elementId}', 'borderWidth', this.value + 'px')">
                </div>
                <div class="setting-field">
                    <label>Box Shadow</label>
                    <select id="main_shadow" onchange="updateMainStyle('${elementId}', 'boxShadow', this.value)">
                        <option value="none">None</option>
                        <option value="0 2px 4px rgba(0,0,0,0.1)">Light</option>
                        <option value="0 4px 8px rgba(0,0,0,0.15)">Medium</option>
                        <option value="0 8px 16px rgba(0,0,0,0.2)">Strong</option>
                    </select>
                </div>
            </div>
        `;
    }
    
    jQuery('#customizationContent').html(html);
}

// Update functions
function updateMainLayout(containerId, property, value) {
    const $container = jQuery(`.main-container[data-id="${containerId}"]`);
    if (property === 'width') {
        $container.css('width', value + '%');
    } else if (property === 'padding') {
        $container.css('padding', value + 'px');
    } else if (property === 'marginBottom') {
        $container.css('margin-bottom', value + 'px');
    } else if (property === 'borderRadius') {
        $container.css('border-radius', value + 'px');
    }
    autoSave();
}

function updateMainStyle(containerId, property, value) {
    const $container = jQuery(`.main-container[data-id="${containerId}"]`);
    if (property === 'backgroundColor') {
        $container.css('background', value);
    } else if (property === 'borderColor') {
        $container.css('border-color', value);
    } else if (property === 'borderWidth') {
        $container.css('border-width', value);
    } else if (property === 'boxShadow') {
        $container.css('box-shadow', value);
    }
    autoSave();
}

function updateFieldLayout(fieldId, property, value) {
    const $field = jQuery(`.form-field[data-id="${fieldId}"]`);
    if (property === 'width') {
        $field.css('width', value + '%');
    } else if (property === 'marginBottom') {
        $field.css('margin-bottom', value + 'px');
    } else if (property === 'labelFontSize') {
        $field.find('label').css('font-size', value + 'px');
    } else if (property === 'inputPadding') {
        $field.find('input, textarea, select').css('padding', value + 'px');
    }
    autoSave();
}

function updateFieldStyle(fieldId, property, value) {
    const $field = jQuery(`.form-field[data-id="${fieldId}"]`);
    if (property === 'backgroundColor') {
        $field.find('input, textarea, select').css('background', value);
    } else if (property === 'borderColor') {
        $field.find('input, textarea, select').css('border-color', value);
    } else if (property === 'textColor') {
        $field.find('input, textarea, select').css('color', value);
    } else if (property === 'labelColor') {
        $field.find('label').css('color', value);
    } else if (property === 'borderWidth') {
        $field.find('input, textarea, select').css('border-width', value);
    }
    autoSave();
}

function updateSubDivLayout(subId, property, value) {
    const $subDiv = jQuery(`.sub-div[data-id="${subId}"]`);
    if (property === 'width') {
        $subDiv.css('width', value + '%');
    } else if (property === 'padding') {
        $subDiv.css('padding', value + 'px');
    } else if (property === 'margin') {
        $subDiv.css('margin', value + 'px');
    } else if (property === 'borderRadius') {
        $subDiv.css('border-radius', value + 'px');
    }
    autoSave();
}

function updateSubDivStyle(subId, property, value) {
    const $subDiv = jQuery(`.sub-div[data-id="${subId}"]`);
    if (property === 'backgroundColor') {
        $subDiv.css('background', value);
    } else if (property === 'borderColor') {
        $subDiv.css('border-color', value);
    } else if (property === 'borderWidth') {
        $subDiv.css('border-width', value);
    } else if (property === 'boxShadow') {
        $subDiv.css('box-shadow', value);
    }
    autoSave();
}

function updateAllSubmitButtons() {
    const btnColor = jQuery('#btnColor').val();
    const btnText = jQuery('#buttonText').val();
    jQuery('.container-submit button').css('background', btnColor);
    jQuery('.container-submit button').text(btnText);
}

// Live text editing
function makeLabelEditable($label, fieldId) {
    $label.attr('contenteditable', 'true');
    $label.addClass('editing');
    $label.focus();
    
    const range = document.createRange();
    const sel = window.getSelection();
    range.selectNodeContents($label[0]);
    sel.removeAllRanges();
    sel.addRange(range);
    
    $label.on('blur', function() {
        const newLabel = $label.text().replace('✏️ Click to edit', '').trim();
        $label.removeAttr('contenteditable');
        $label.removeClass('editing');
        $label.html(newLabel + '<span class="edit-hint">✏️ Click to edit</span>');
        $label.off('blur keypress');
        
        for (const containerId in mainContainers) {
            const field = findItemInContainer(mainContainers[containerId].items, fieldId);
            if (field && field.type === 'field') {
                field.label = newLabel;
                break;
            }
        }
        autoSave();
    });
    
    $label.on('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $label.blur();
        }
    });
}

function makePlaceholderEditable($input, fieldId) {
    $input.addClass('editing-placeholder');
    $input.focus();
    
    $input.on('blur', function() {
        const newPlaceholder = $input.val();
        $input.removeClass('editing-placeholder');
        $input.off('blur keypress');
        
        for (const containerId in mainContainers) {
            const field = findItemInContainer(mainContainers[containerId].items, fieldId);
            if (field && field.type === 'field') {
                field.placeholder = newPlaceholder;
                $input.attr('placeholder', newPlaceholder);
                break;
            }
        }
        autoSave();
    });
    
    $input.on('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $input.blur();
        }
    });
}

// Add main container
function addMainContainer() {
    const id = 'main_' + Date.now() + '_' + (++elementCounter);
    const btnColor = jQuery('#btnColor').val();
    const btnText = jQuery('#buttonText').val();
    const html = `
        <div class="main-container" data-id="${id}" style="background: #ffffff;">
            <div class="container-controls">
                <button onclick="editContainer('${id}')" title="Settings">⚙️</button>
                <button onclick="removeContainer('${id}')" title="Remove">🗑️</button>
            </div>
            <div class="drop-zone main-drop-zone" data-parent="${id}" data-container-type="main">
                <div class="container-placeholder">📦 Drag items here (fields, sub divs, social section)</div>
            </div>
            <div class="container-submit">
                <button style="background: ${btnColor}">${btnText}</button>
            </div>
        </div>
    `;
    jQuery('#formBuilder').append(html);
    mainContainers[id] = { id: id, type: 'main', bgColor: '#ffffff', items: [], socialButtons: [], customStyles: {} };
    makeContainerDroppable(id, '.main-drop-zone', 'main');
    makeContainerSortable(id);
    autoSave();
}

// Make container droppable
function makeContainerDroppable(containerId, dropZoneClass, containerType) {
    const $dropZone = jQuery(`.main-container[data-id="${containerId}"] ${dropZoneClass}, .sub-div[data-id="${containerId}"] ${dropZoneClass}, .social-section[data-id="${containerId}"] ${dropZoneClass}`);
    
    $dropZone.droppable({
        accept: function(draggable) {
            const dragType = draggable.data('drag-type');
            if (containerType === 'social') {
                return dragType === 'social';
            } else {
                // Add 'social' here to allow social buttons in main containers and sub divs
                return dragType === 'field' || dragType === 'sub_div' || dragType === 'social_section' || dragType === 'social';
            }
        },
        tolerance: 'pointer',
        hoverClass: containerType === 'main' ? 'drag-over' : (containerType === 'social' ? 'social-drag-over' : 'sub-drag-over'),
        greedy: true,
        drop: function(event, ui) {
            if (!draggedItemData) return false;
            
            const dragType = draggedItemData.type;
            
            if (dragType === 'sub_div' && containerType !== 'social') {
                addSubDiv(containerId);
            } else if (dragType === 'social_section' && containerType !== 'social') {
                addSocialSection(containerId);
            } else if (dragType === 'social' && containerType === 'social') {
                const provider = draggedItemData.socialProvider;
                addSocialButtonToSocialSection(containerId, provider);
            } else if (dragType === 'social' && containerType !== 'social') {
                const provider = draggedItemData.socialProvider;
                addSocialButtonToContainer(containerId, provider);
            } else if (dragType === 'field' && containerType !== 'social') {
                const fieldType = draggedItemData.fieldType;
                const fieldLabel = draggedItemData.fieldLabel;
                const fieldHtmlType = draggedItemData.fieldHtmlType;
                addFieldToContainer(containerId, fieldType, fieldLabel, fieldHtmlType);
            }
            
            if ($dropZone.find('.container-placeholder').length) {
                $dropZone.find('.container-placeholder').remove();
            }
            autoSave();
            return false;
        }
    });
}

// Add sub div
function addSubDiv(parentId) {
    const id = 'sub_' + Date.now() + '_' + (++elementCounter);
    const html = `
        <div class="sub-div" data-id="${id}" style="background: #f8f9fa;">
            <div class="container-controls">
                <button onclick="editSubDiv('${id}')" title="Settings">⚙️</button>
                <button onclick="removeSubDiv('${id}')" title="Remove">🗑️</button>
            </div>
            <div class="drop-zone sub-drop-zone" data-parent="${id}" data-container-type="sub">
                <div class="container-placeholder">📦 Drag fields, sub divs, or social buttons here</div>
            </div>
        </div>
    `;
    
    let parentFound = false;
    
    if (mainContainers[parentId]) {
        jQuery(`.main-container[data-id="${parentId}"] .main-drop-zone`).append(html);
        if (!mainContainers[parentId].items) mainContainers[parentId].items = [];
        mainContainers[parentId].items.push({ id: id, type: 'sub', bgColor: '#f8f9fa', items: [], socialButtons: [], customStyles: {} });
        parentFound = true;
    } else {
        for (const containerId in mainContainers) {
            const parentItem = findItemInContainer(mainContainers[containerId].items, parentId);
            if (parentItem && parentItem.type === 'sub') {
                jQuery(`.sub-div[data-id="${parentId}"] .sub-drop-zone`).append(html);
                if (!parentItem.items) parentItem.items = [];
                parentItem.items.push({ id: id, type: 'sub', bgColor: '#f8f9fa', items: [], socialButtons: [], customStyles: {} });
                parentFound = true;
                break;
            }
        }
    }
    
    if (parentFound) {
        makeContainerDroppable(id, '.sub-drop-zone', 'sub');
        makeSubDivSortable(id);
    }
}

// Add social section
function addSocialSection(parentId) {
    const id = 'social_' + Date.now() + '_' + (++elementCounter);
    const html = `
        <div class="social-section" data-id="${id}" style="background: #ffffff;">
            <div class="section-title">🔗 Social Login</div>
            <div class="container-controls">
                <button onclick="editSocialSection('${id}')" title="Settings">⚙️</button>
                <button onclick="removeSocialSection('${id}')" title="Remove">🗑️</button>
            </div>
            <div class="drop-zone social-drop-zone" data-parent="${id}" data-container-type="social">
                <div class="container-placeholder">📦 Drag social login buttons here</div>
            </div>
        </div>
    `;
    
    if (mainContainers[parentId]) {
        jQuery(`.main-container[data-id="${parentId}"] .main-drop-zone`).append(html);
        if (!mainContainers[parentId].items) mainContainers[parentId].items = [];
        mainContainers[parentId].items.push({ id: id, type: 'social_section', bgColor: '#ffffff', socialButtons: [] });
    } else {
        for (const containerId in mainContainers) {
            const parentItem = findItemInContainer(mainContainers[containerId].items, parentId);
            if (parentItem && parentItem.type === 'sub') {
                jQuery(`.sub-div[data-id="${parentId}"] .sub-drop-zone`).append(html);
                if (!parentItem.items) parentItem.items = [];
                parentItem.items.push({ id: id, type: 'social_section', bgColor: '#ffffff', socialButtons: [] });
                break;
            }
        }
    }
    
    makeContainerDroppable(id, '.social-drop-zone', 'social');
}

// Add field
function addFieldToContainer(parentId, fieldType, fieldLabel, fieldHtmlType) {
    const fieldId = 'field_' + Date.now() + '_' + (++elementCounter);
    const showLabels = jQuery('#showLabels').is(':checked');
    const isCheckbox = fieldHtmlType === 'checkbox';
    const isRadio = fieldHtmlType === 'radio';
    const fieldClass = isCheckbox ? 'checkbox-field' : (isRadio ? 'radio-field' : '');
    const placeholder = 'Enter ' + fieldLabel.toLowerCase();
    
    let fieldHtml = '';
    if (fieldHtmlType === 'textarea') {
        fieldHtml = `<textarea placeholder="${placeholder}"></textarea>`;
    } else if (fieldHtmlType === 'select') {
        fieldHtml = `<select><option>Select option</option></select>`;
    } else if (fieldHtmlType === 'checkbox') {
        fieldHtml = `<input type="checkbox" id="chk_${fieldId}"> <label for="chk_${fieldId}">${fieldLabel}</label>`;
    } else if (fieldHtmlType === 'radio') {
        fieldHtml = `<input type="radio" name="radio_${fieldId}" id="rad_${fieldId}"> <label for="rad_${fieldId}">${fieldLabel}</label>`;
    } else {
        fieldHtml = `<input type="${fieldHtmlType}" placeholder="${placeholder}">`;
    }
    
    const labelHtml = (showLabels && !isCheckbox && !isRadio) ? `<label data-field-id="${fieldId}">${fieldLabel}<span class="edit-hint">✏️ Click to edit</span></label>` : '';
    
    const fullHtml = `
        <div class="form-field ${fieldClass}" data-id="${fieldId}" data-label="${fieldLabel}">
            ${labelHtml}
            ${fieldHtml}
            <button class="field-drag" title="Drag to reorder">⋮⋮</button>
            <button class="field-edit" onclick="editField('${fieldId}')" title="Advanced Settings">⚙️</button>
            <button class="field-remove" onclick="removeField('${fieldId}')" title="Remove">🗑️</button>
        </div>
    `;
    
    let parentFound = false;
    
    if (mainContainers[parentId]) {
        jQuery(`.main-container[data-id="${parentId}"] .main-drop-zone`).append(fullHtml);
        if (!mainContainers[parentId].items) mainContainers[parentId].items = [];
        mainContainers[parentId].items.push({
            id: fieldId, type: 'field', fieldType: fieldType, label: fieldLabel,
            html_type: fieldHtmlType, required: false, placeholder: placeholder, customStyles: {}
        });
        parentFound = true;
    } else {
        for (const containerId in mainContainers) {
            const parentItem = findItemInContainer(mainContainers[containerId].items, parentId);
            if (parentItem) {
                if (parentItem.type === 'sub') {
                    jQuery(`.sub-div[data-id="${parentId}"] .sub-drop-zone`).append(fullHtml);
                    if (!parentItem.items) parentItem.items = [];
                    parentItem.items.push({
                        id: fieldId, type: 'field', fieldType: fieldType, label: fieldLabel,
                        html_type: fieldHtmlType, required: false, placeholder: placeholder, customStyles: {}
                    });
                }
                parentFound = true;
                break;
            }
        }
    }
    
    if (parentFound) {
        if (showLabels && !isCheckbox && !isRadio) {
            const $label = jQuery(`.form-field[data-id="${fieldId}"] label`);
            $label.on('dblclick', function(e) {
                e.stopPropagation();
                makeLabelEditable(jQuery(this), fieldId);
            });
        }
        
        const $input = jQuery(`.form-field[data-id="${fieldId}"] input, .form-field[data-id="${fieldId}"] textarea`);
        if ($input.length && fieldHtmlType !== 'checkbox' && fieldHtmlType !== 'radio') {
            $input.on('dblclick', function(e) {
                e.stopPropagation();
                makePlaceholderEditable(jQuery(this), fieldId);
            });
            $input.attr('title', 'Double-click to edit placeholder text');
        }
        
        // Add click handler for selection
        jQuery(`.form-field[data-id="${fieldId}"]`).on('click', function(e) {
            if (!jQuery(e.target).hasClass('field-remove') && !jQuery(e.target).hasClass('field-edit') && !jQuery(e.target).hasClass('field-drag')) {
                e.stopPropagation();
                selectElement(fieldId, 'field', fieldLabel);
            }
        });
    }
}

// Add social button
function addSocialButtonToContainer(parentId, provider) {
    const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
    const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
    
    const buttonHtml = `<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`;
    
    if (mainContainers[parentId]) {
        jQuery(`.main-container[data-id="${parentId}"] .main-drop-zone`).append(buttonHtml);
        if (!mainContainers[parentId].socialButtons) mainContainers[parentId].socialButtons = [];
        if (!mainContainers[parentId].socialButtons.includes(provider)) {
            mainContainers[parentId].socialButtons.push(provider);
        }
    } else {
        for (const containerId in mainContainers) {
            const parentItem = findItemInContainer(mainContainers[containerId].items, parentId);
            if (parentItem && parentItem.type === 'sub') {
                jQuery(`.sub-div[data-id="${parentId}"] .sub-drop-zone`).append(buttonHtml);
                if (!parentItem.socialButtons) parentItem.socialButtons = [];
                if (!parentItem.socialButtons.includes(provider)) {
                    parentItem.socialButtons.push(provider);
                }
                break;
            }
        }
    }
}

function addSocialButtonToSocialSection(socialId, provider) {
    const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
    const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
    
    const buttonHtml = `<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`;
    jQuery(`.social-section[data-id="${socialId}"] .social-drop-zone`).append(buttonHtml);
    
    for (const containerId in mainContainers) {
        const socialSection = findItemInContainer(mainContainers[containerId].items, socialId);
        if (socialSection && socialSection.type === 'social_section') {
            if (!socialSection.socialButtons) socialSection.socialButtons = [];
            if (!socialSection.socialButtons.includes(provider)) {
                socialSection.socialButtons.push(provider);
            }
            break;
        }
    }
}

// Sortable functions
function makeContainerSortable(containerId) {
    jQuery(`.main-container[data-id="${containerId}"] .main-drop-zone`).sortable({
        placeholder: 'sortable-placeholder',
        items: '.form-field, .sub-div, .social-section',
        handle: '.field-drag',
        cancel: '.container-controls button, .social-btn',
        update: function() { autoSave(); }
    });
}

function makeSubDivSortable(subId) {
    jQuery(`.sub-div[data-id="${subId}"] .sub-drop-zone`).sortable({
        placeholder: 'sortable-placeholder',
        items: '.form-field, .sub-div, .social-section',
        handle: '.field-drag',
        cancel: '.container-controls button, .social-btn',
        update: function() { autoSave(); }
    });
}

// Remove functions
function removeField(fieldId) {
    jQuery(`.form-field[data-id="${fieldId}"]`).remove();
    
    for (const containerId in mainContainers) {
        removeItemFromContainer(mainContainers[containerId].items, fieldId);
    }
    
    if (selectedElementId === fieldId) clearSelection();
    autoSave();
}

function removeSubDiv(subId) {
    jQuery(`.sub-div[data-id="${subId}"]`).remove();
    for (const containerId in mainContainers) {
        removeItemFromContainer(mainContainers[containerId].items, subId);
    }
    
    if (selectedElementId === subId) clearSelection();
    autoSave();
}

function removeSocialSection(socialId) {
    jQuery(`.social-section[data-id="${socialId}"]`).remove();
    for (const containerId in mainContainers) {
        removeItemFromContainer(mainContainers[containerId].items, socialId);
    }
    
    if (selectedElementId === socialId) clearSelection();
    autoSave();
}

function removeContainer(containerId) {
    delete mainContainers[containerId];
    jQuery(`.main-container[data-id="${containerId}"]`).remove();
    if (Object.keys(mainContainers).length === 0) {
        jQuery('#formBuilder').html('<div class="builder-placeholder">📦 Drag a Main Container here to start</div>');
        initMainDroppable();
    }
    
    if (selectedElementId === containerId) clearSelection();
    autoSave();
}

// Edit functions
function editField(fieldId) {
    editingFieldId = fieldId;
    let field = null;
    
    for (const containerId in mainContainers) {
        field = findItemInContainer(mainContainers[containerId].items, fieldId);
        if (field) break;
    }
    
    if (field) {
        jQuery('#fieldPlaceholder').val(field.placeholder || '');
        jQuery('#fieldRequired').prop('checked', field.required || false);
        jQuery('#fieldSettingsModal').show();
    }
}

function saveFieldSettings() {
    if (!editingFieldId) return;
    const placeholder = jQuery('#fieldPlaceholder').val();
    const required = jQuery('#fieldRequired').is(':checked');
    
    for (const containerId in mainContainers) {
        const field = findItemInContainer(mainContainers[containerId].items, editingFieldId);
        if (field && field.type === 'field') {
            field.placeholder = placeholder;
            field.required = required;
            const $field = jQuery(`.form-field[data-id="${editingFieldId}"]`);
            $field.find('input, textarea, select').attr('placeholder', placeholder);
            if (required) {
                $field.find('input, textarea, select').attr('required', 'required');
            } else {
                $field.find('input, textarea, select').removeAttr('required');
            }
            break;
        }
    }
    
    closeFieldModal();
    autoSave();
}

function editContainer(containerId) {
    editingContainerId = containerId;
    jQuery('#containerBgColor').val(mainContainers[containerId]?.bgColor || '#ffffff');
    jQuery('#containerSettingsModal').show();
}

function editSubDiv(subId) {
    editingContainerId = subId;
    for (const containerId in mainContainers) {
        const subDiv = findItemInContainer(mainContainers[containerId].items, subId);
        if (subDiv && subDiv.type === 'sub') {
            jQuery('#containerBgColor').val(subDiv.bgColor || '#f8f9fa');
            break;
        }
    }
    jQuery('#containerSettingsModal').show();
}

function editSocialSection(socialId) {
    editingContainerId = socialId;
    for (const containerId in mainContainers) {
        const socialSection = findItemInContainer(mainContainers[containerId].items, socialId);
        if (socialSection && socialSection.type === 'social_section') {
            jQuery('#containerBgColor').val(socialSection.bgColor || '#ffffff');
            break;
        }
    }
    jQuery('#containerSettingsModal').show();
}

function saveContainerSettings() {
    if (!editingContainerId) return;
    const bgColor = jQuery('#containerBgColor').val();
    
    if (jQuery(`.main-container[data-id="${editingContainerId}"]`).length) {
        mainContainers[editingContainerId].bgColor = bgColor;
        jQuery(`.main-container[data-id="${editingContainerId}"]`).css('background', bgColor);
    } else if (jQuery(`.sub-div[data-id="${editingContainerId}"]`).length) {
        for (const containerId in mainContainers) {
            const subDiv = findItemInContainer(mainContainers[containerId].items, editingContainerId);
            if (subDiv && subDiv.type === 'sub') {
                subDiv.bgColor = bgColor;
                jQuery(`.sub-div[data-id="${editingContainerId}"]`).css('background', bgColor);
                break;
            }
        }
    } else if (jQuery(`.social-section[data-id="${editingContainerId}"]`).length) {
        for (const containerId in mainContainers) {
            const socialSection = findItemInContainer(mainContainers[containerId].items, editingContainerId);
            if (socialSection && socialSection.type === 'social_section') {
                socialSection.bgColor = bgColor;
                jQuery(`.social-section[data-id="${editingContainerId}"]`).css('background', bgColor);
                break;
            }
        }
    }
    closeContainerModal();
    autoSave();
}

// Save functions
function autoSave() {
    if (currentFormId) saveForm();
}

// Fixed Save Function
function saveForm() {
    if (!currentFormId) return;
    
    // Create a clean copy of containers data for saving
    const saveData = {};
    
    for (const [containerId, container] of Object.entries(mainContainers)) {
        saveData[containerId] = {
            id: container.id,
            type: container.type,
            bgColor: container.bgColor,
            items: saveItemsRecursively(container.items || []),
            socialButtons: container.socialButtons || [],
            customStyles: container.customStyles || {}
        };
    }
    
    const formData = {
        form_id: currentFormId,
        containers: saveData,
        settings: {
            btn_color: jQuery('#btnColor').val(),
            button_text: jQuery('#buttonText').val(),
            show_labels: jQuery('#showLabels').is(':checked')
        }
    };
    
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { 
            action: 'my_login_save_form_settings', 
            form_data: JSON.stringify(formData), 
            nonce: my_login_ajax.nonces.save_form 
        },
        success: function(r) { 
            if (!r.success) console.error('Save error:', r); 
            else console.log('Form saved successfully');
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

// Helper function to save items recursively
function saveItemsRecursively(items) {
    if (!items || items.length === 0) return [];
    
    return items.map(item => {
        const savedItem = {
            id: item.id,
            type: item.type
        };
        
        if (item.type === 'field') {
            savedItem.fieldType = item.fieldType;
            savedItem.label = item.label;
            savedItem.html_type = item.html_type;
            savedItem.required = item.required || false;
            savedItem.placeholder = item.placeholder || '';
            savedItem.customStyles = item.customStyles || {};
        } else if (item.type === 'sub') {
            savedItem.bgColor = item.bgColor;
            savedItem.items = saveItemsRecursively(item.items || []);
            savedItem.socialButtons = item.socialButtons || [];
            savedItem.customStyles = item.customStyles || {};
        } else if (item.type === 'social_section') {
            savedItem.bgColor = item.bgColor;
            savedItem.socialButtons = item.socialButtons || [];
            savedItem.customStyles = item.customStyles || {};
        }
        
        return savedItem;
    });
}

// Fixed Load Form Function
function loadForm(formId) {
    currentFormId = formId;
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_get_form', form_id: formId, nonce: my_login_ajax.nonces.get_form },
        success: function(r) {
            if (r.success && r.data) {
                mainContainers = {};
                jQuery('#formBuilder').empty();
                
                const data = r.data.containers || {};
                const settings = r.data.settings || {};
                
                // Apply settings
                jQuery('#btnColor').val(settings.btn_color || '#0073aa');
                jQuery('#buttonText').val(settings.button_text || 'Submit');
                jQuery('#showLabels').prop('checked', settings.show_labels || false);
                updateAllSubmitButtons();
                
                if (Object.keys(data).length === 0) {
                    jQuery('#formBuilder').html('<div class="builder-placeholder">📦 Drag a Main Container here to start</div>');
                    initMainDroppable();
                } else {
                    for (const [id, containerData] of Object.entries(data)) {
                        // Build main container HTML
                        const btnColor = settings.btn_color || '#0073aa';
                        const btnText = settings.button_text || 'Submit';
                        const mainHtml = `
                            <div class="main-container" data-id="${id}" style="background: ${containerData.bgColor || '#ffffff'};">
                                <div class="container-controls">
                                    <button onclick="editContainer('${id}')" title="Settings">⚙️</button>
                                    <button onclick="removeContainer('${id}')" title="Remove">🗑️</button>
                                </div>
                                <div class="drop-zone main-drop-zone" data-parent="${id}" data-container-type="main">
                                    <div class="container-placeholder" style="display: ${(containerData.items && containerData.items.length) ? 'none' : 'block'}">📦 Drag items here</div>
                                </div>
                                <div class="container-submit">
                                    <button style="background: ${btnColor}">${btnText}</button>
                                </div>
                            </div>
                        `;
                        jQuery('#formBuilder').append(mainHtml);
                        
                        // Store container data
                        mainContainers[id] = {
                            id: id,
                            type: 'main',
                            bgColor: containerData.bgColor || '#ffffff',
                            items: [],
                            socialButtons: containerData.socialButtons || [],
                            customStyles: containerData.customStyles || {}
                        };
                        
                        // Build all items
                        if (containerData.items && containerData.items.length) {
                            buildItemsFromData(id, containerData.items, settings);
                        }
                        
                        // Make droppable and sortable
                        makeContainerDroppable(id, '.main-drop-zone', 'main');
                        makeContainerSortable(id);
                    }
                }
                
                jQuery('.form-item').removeClass('active');
                jQuery(`.form-item[data-form-id="${formId}"]`).addClass('active');
                clearSelection();
            } else {
                console.error('Failed to load form:', r);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error loading form:', error);
        }
    });
}

// Fixed Build Items Function
function buildItemsFromData(parentContainerId, items, settings) {
    if (!items || items.length === 0) return;
    
    const $parentZone = jQuery(`.main-container[data-id="${parentContainerId}"] .main-drop-zone, .sub-div[data-id="${parentContainerId}"] .sub-drop-zone`);
    
    for (const item of items) {
        if (item.type === 'sub') {
            // Create sub div HTML
            const subHtml = `
                <div class="sub-div" data-id="${item.id}" style="background: ${item.bgColor || '#f8f9fa'};">
                    <div class="container-controls">
                        <button onclick="editSubDiv('${item.id}')" title="Settings">⚙️</button>
                        <button onclick="removeSubDiv('${item.id}')" title="Remove">🗑️</button>
                    </div>
                    <div class="drop-zone sub-drop-zone" data-parent="${item.id}" data-container-type="sub">
                        <div class="container-placeholder" style="display: ${(item.items && item.items.length) ? 'none' : 'block'}">📦 Drag fields, sub divs, or social buttons here</div>
                    </div>
                </div>
            `;
            $parentZone.append(subHtml);
            
            // Store sub div data
            for (const containerId in mainContainers) {
                const parentItem = findItemInContainer(mainContainers[containerId].items, parentContainerId);
                if (parentItem && parentItem.type === 'sub') {
                    if (!parentItem.items) parentItem.items = [];
                    parentItem.items.push({
                        id: item.id,
                        type: 'sub',
                        bgColor: item.bgColor || '#f8f9fa',
                        items: [],
                        socialButtons: item.socialButtons || [],
                        customStyles: item.customStyles || {}
                    });
                    break;
                } else if (mainContainers[parentContainerId]) {
                    mainContainers[parentContainerId].items.push({
                        id: item.id,
                        type: 'sub',
                        bgColor: item.bgColor || '#f8f9fa',
                        items: [],
                        socialButtons: item.socialButtons || [],
                        customStyles: item.customStyles || {}
                    });
                    break;
                }
            }
            
            makeContainerDroppable(item.id, '.sub-drop-zone', 'sub');
            makeSubDivSortable(item.id);
            
            // Recursively build nested items
            if (item.items && item.items.length) {
                buildItemsFromData(item.id, item.items, settings);
            }
            
            // Add social buttons to sub div
            if (item.socialButtons && item.socialButtons.length) {
                item.socialButtons.forEach(provider => {
                    jQuery(`.sub-div[data-id="${item.id}"] .sub-drop-zone`).append(`<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`);
                });
            }
            
        } else if (item.type === 'social_section') {
            // Create social section HTML
            const socialHtml = `
                <div class="social-section" data-id="${item.id}" style="background: ${item.bgColor || '#ffffff'};">
                    <div class="section-title">🔗 Social Login</div>
                    <div class="container-controls">
                        <button onclick="editSocialSection('${item.id}')" title="Settings">⚙️</button>
                        <button onclick="removeSocialSection('${item.id}')" title="Remove">🗑️</button>
                    </div>
                    <div class="drop-zone social-drop-zone" data-parent="${item.id}" data-container-type="social">
                        <div class="container-placeholder" style="display: ${(item.socialButtons && item.socialButtons.length) ? 'none' : 'block'}">📦 Drag social login buttons here</div>
                    </div>
                </div>
            `;
            $parentZone.append(socialHtml);
            
            // Store social section data
            for (const containerId in mainContainers) {
                const parentItem = findItemInContainer(mainContainers[containerId].items, parentContainerId);
                if (parentItem && parentItem.type === 'sub') {
                    if (!parentItem.items) parentItem.items = [];
                    parentItem.items.push({
                        id: item.id,
                        type: 'social_section',
                        bgColor: item.bgColor || '#ffffff',
                        socialButtons: item.socialButtons || [],
                        customStyles: item.customStyles || {}
                    });
                    break;
                } else if (mainContainers[parentContainerId]) {
                    mainContainers[parentContainerId].items.push({
                        id: item.id,
                        type: 'social_section',
                        bgColor: item.bgColor || '#ffffff',
                        socialButtons: item.socialButtons || [],
                        customStyles: item.customStyles || {}
                    });
                    break;
                }
            }
            
            makeContainerDroppable(item.id, '.social-drop-zone', 'social');
            
            // Add social buttons
            if (item.socialButtons && item.socialButtons.length) {
                item.socialButtons.forEach(provider => {
                    jQuery(`.social-section[data-id="${item.id}"] .social-drop-zone`).append(`<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`);
                });
            }
            
        } else if (item.type === 'field') {
            // Build field HTML (same as your existing field creation code)
            const showLabels = settings.show_labels || false;
            const isCheckbox = item.html_type === 'checkbox';
            const isRadio = item.html_type === 'radio';
            const fieldClass = isCheckbox ? 'checkbox-field' : (isRadio ? 'radio-field' : '');
            
            let fieldHtml = '';
            if (item.html_type === 'textarea') {
                fieldHtml = `<textarea placeholder="${item.placeholder || ''}" ${item.required ? 'required' : ''}></textarea>`;
            } else if (item.html_type === 'select') {
                fieldHtml = `<select ${item.required ? 'required' : ''}><option>Select option</option></select>`;
            } else if (item.html_type === 'checkbox') {
                fieldHtml = `<input type="checkbox" id="chk_${item.id}" ${item.required ? 'required' : ''}> <label for="chk_${item.id}">${item.label}</label>`;
            } else if (item.html_type === 'radio') {
                fieldHtml = `<input type="radio" name="radio_${item.id}" id="rad_${item.id}" ${item.required ? 'required' : ''}> <label for="rad_${item.id}">${item.label}</label>`;
            } else {
                fieldHtml = `<input type="${item.html_type}" placeholder="${item.placeholder || ''}" ${item.required ? 'required' : ''}>`;
            }
            
            const labelHtml = (showLabels && !isCheckbox && !isRadio) ? `<label data-field-id="${item.id}">${item.label}<span class="edit-hint">✏️ Click to edit</span></label>` : '';
            
            const fieldFullHtml = `
                <div class="form-field ${fieldClass}" data-id="${item.id}" data-label="${item.label}">
                    ${labelHtml}
                    ${fieldHtml}
                    <button class="field-drag" title="Drag to reorder">⋮⋮</button>
                    <button class="field-edit" onclick="editField('${item.id}')" title="Advanced Settings">⚙️</button>
                    <button class="field-remove" onclick="removeField('${item.id}')" title="Remove">🗑️</button>
                </div>
            `;
            
            $parentZone.append(fieldFullHtml);
            
            // Store field data
            const fieldData = {
                id: item.id,
                type: 'field',
                fieldType: item.fieldType,
                label: item.label,
                html_type: item.html_type,
                required: item.required || false,
                placeholder: item.placeholder || '',
                customStyles: item.customStyles || {}
            };
            
            for (const containerId in mainContainers) {
                const parentItem = findItemInContainer(mainContainers[containerId].items, parentContainerId);
                if (parentItem && parentItem.type === 'sub') {
                    if (!parentItem.items) parentItem.items = [];
                    parentItem.items.push(fieldData);
                    break;
                } else if (mainContainers[parentContainerId]) {
                    mainContainers[parentContainerId].items.push(fieldData);
                    break;
                }
            }
            
            // Add event handlers
            if (showLabels && !isCheckbox && !isRadio) {
                const $label = jQuery(`.form-field[data-id="${item.id}"] label`);
                $label.on('dblclick', function(e) {
                    e.stopPropagation();
                    makeLabelEditable(jQuery(this), item.id);
                });
            }
            
            const $input = jQuery(`.form-field[data-id="${item.id}"] input, .form-field[data-id="${item.id}"] textarea`);
            if ($input.length && item.html_type !== 'checkbox' && item.html_type !== 'radio') {
                $input.on('dblclick', function(e) {
                    e.stopPropagation();
                    makePlaceholderEditable(jQuery(this), item.id);
                });
                $input.attr('title', 'Double-click to edit placeholder text');
            }
            
            jQuery(`.form-field[data-id="${item.id}"]`).on('click', function(e) {
                if (!jQuery(e.target).hasClass('field-remove') && !jQuery(e.target).hasClass('field-edit') && !jQuery(e.target).hasClass('field-drag')) {
                    e.stopPropagation();
                    selectElement(item.id, 'field', item.label);
                }
            });
        }
    }
}

// Add socialColors and socialLabels at the top with other variables
const socialColors = { 
    google: '#DB4437', 
    facebook: '#4267B2', 
    twitter: '#1DA1F2', 
    github: '#333333', 
    linkedin: '#0077B5' 
};

const socialLabels = { 
    google: 'Google', 
    facebook: 'Facebook', 
    twitter: 'Twitter', 
    github: 'GitHub', 
    linkedin: 'LinkedIn' 
};

function saveCurrentForm() {
    if (!currentFormId) alert('Select a form first');
    else { saveForm(); alert('Form saved!'); }
}

function saveCSS() {
    if (!currentFormId) { alert('Select a form first'); return; }
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_save_form_css', form_id: currentFormId, css_content: jQuery('#customCSS').val(), nonce: my_login_ajax.nonces.save_css },
        success: function(r) { if (r.success) alert('CSS saved!'); else alert('Error saving CSS'); }
    });
}

function saveJS() {
    if (!currentFormId) { alert('Select a form first'); return; }
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_save_form_js', form_id: currentFormId, js_content: jQuery('#customJS').val(), nonce: my_login_ajax.nonces.save_js },
        success: function(r) { if (r.success) alert('JavaScript saved!'); else alert('Error saving JavaScript'); }
    });
}

function applyPreset(preset) {
    const presets = {
        modern: { btn_color: '#667eea' },
        minimal: { btn_color: '#000000' },
        dark: { btn_color: '#4CAF50' }
    };
    const p = presets[preset];
    if (p) {
        jQuery('#btnColor').val(p.btn_color);
        updateAllSubmitButtons();
        autoSave();
    }
}

// Load form
function loadForm(formId) {
    currentFormId = formId;
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_get_form', form_id: formId, nonce: my_login_ajax.nonces.get_form },
        success: function(r) {
            if (r.success && r.data) {
                mainContainers = {};
                jQuery('#formBuilder').empty();
                const data = r.data.containers || {};
                const settings = r.data.settings || {};
                
                jQuery('#btnColor').val(settings.btn_color || '#0073aa');
                jQuery('#buttonText').val(settings.button_text || 'Submit');
                jQuery('#showLabels').prop('checked', settings.show_labels || false);
                updateAllSubmitButtons();
                
                if (Object.keys(data).length === 0) {
                    jQuery('#formBuilder').html('<div class="builder-placeholder">📦 Drag a Main Container here to start</div>');
                    initMainDroppable();
                } else {
                    for (const [id, containerData] of Object.entries(data)) {
                        const btnColor = settings.btn_color || '#0073aa';
                        const btnText = settings.button_text || 'Submit';
                        const mainHtml = `
                            <div class="main-container" data-id="${id}" style="background: ${containerData.bgColor || '#ffffff'};">
                                <div class="container-controls">
                                    <button onclick="editContainer('${id}')" title="Settings">⚙️</button>
                                    <button onclick="removeContainer('${id}')" title="Remove">🗑️</button>
                                </div>
                                <div class="drop-zone main-drop-zone" data-parent="${id}" data-container-type="main">
                                    <div class="container-placeholder">📦 Drag items here</div>
                                </div>
                                <div class="container-submit">
                                    <button style="background: ${btnColor}">${btnText}</button>
                                </div>
                            </div>
                        `;
                        jQuery('#formBuilder').append(mainHtml);
                        
                        mainContainers[id] = {
                            id: id,
                            type: 'main',
                            bgColor: containerData.bgColor,
                            items: containerData.items || [],
                            socialButtons: containerData.socialButtons || [],
                            customStyles: containerData.customStyles || {}
                        };
                        
                        // Recursively build items
                        buildItemsRecursively(id, containerData.items || [], settings);
                        
                        makeContainerDroppable(id, '.main-drop-zone', 'main');
                        makeContainerSortable(id);
                        
                        // Add social buttons
                        if (containerData.socialButtons) {
                            const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
                            const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
                            containerData.socialButtons.forEach(provider => {
                                jQuery(`.main-container[data-id="${id}"] .main-drop-zone`).append(`<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`);
                            });
                        }
                    }
                }
                
                jQuery('.form-item').removeClass('active');
                jQuery(`.form-item[data-form-id="${formId}"]`).addClass('active');
                clearSelection();
            }
        }
    });
}

function buildItemsRecursively(parentContainerId, items, settings) {
    if (!items || items.length === 0) return;
    
    for (const item of items) {
        if (item.type === 'sub') {
            const subHtml = `
                <div class="sub-div" data-id="${item.id}" style="background: ${item.bgColor || '#f8f9fa'};">
                    <div class="container-controls">
                        <button onclick="editSubDiv('${item.id}')" title="Settings">⚙️</button>
                        <button onclick="removeSubDiv('${item.id}')" title="Remove">🗑️</button>
                    </div>
                    <div class="drop-zone sub-drop-zone" data-parent="${item.id}" data-container-type="sub">
                        <div class="container-placeholder">📦 Drag fields, sub divs, or social buttons here</div>
                    </div>
                </div>
            `;
            jQuery(`.main-container[data-id="${parentContainerId}"] .main-drop-zone, .sub-div[data-id="${parentContainerId}"] .sub-drop-zone`).append(subHtml);
            
            makeContainerDroppable(item.id, '.sub-drop-zone', 'sub');
            makeSubDivSortable(item.id);
            
            // Build nested items
            if (item.items && item.items.length) {
                buildItemsRecursively(item.id, item.items, settings);
            }
            
            // Add social buttons
            if (item.socialButtons) {
                const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
                const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
                item.socialButtons.forEach(provider => {
                    jQuery(`.sub-div[data-id="${item.id}"] .sub-drop-zone`).append(`<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`);
                });
            }
        } else if (item.type === 'social_section') {
            const socialHtml = `
                <div class="social-section" data-id="${item.id}" style="background: ${item.bgColor || '#ffffff'};">
                    <div class="section-title">🔗 Social Login</div>
                    <div class="container-controls">
                        <button onclick="editSocialSection('${item.id}')" title="Settings">⚙️</button>
                        <button onclick="removeSocialSection('${item.id}')" title="Remove">🗑️</button>
                    </div>
                    <div class="drop-zone social-drop-zone" data-parent="${item.id}" data-container-type="social">
                        <div class="container-placeholder">📦 Drag social login buttons here</div>
                    </div>
                </div>
            `;
            jQuery(`.main-container[data-id="${parentContainerId}"] .main-drop-zone, .sub-div[data-id="${parentContainerId}"] .sub-drop-zone`).append(socialHtml);
            makeContainerDroppable(item.id, '.social-drop-zone', 'social');
            
            if (item.socialButtons) {
                const socialColors = { google: '#DB4437', facebook: '#4267B2', twitter: '#1DA1F2', github: '#333333', linkedin: '#0077B5' };
                const socialLabels = { google: 'Google', facebook: 'Facebook', twitter: 'Twitter', github: 'GitHub', linkedin: 'LinkedIn' };
                item.socialButtons.forEach(provider => {
                    jQuery(`.social-section[data-id="${item.id}"] .social-drop-zone`).append(`<div class="social-btn" style="background: ${socialColors[provider]}">${socialLabels[provider]}</div>`);
                });
            }
        } else if (item.type === 'field') {
            const showLabels = settings.show_labels || false;
            const isCheckbox = item.html_type === 'checkbox';
            const isRadio = item.html_type === 'radio';
            const fieldClass = isCheckbox ? 'checkbox-field' : (isRadio ? 'radio-field' : '');
            
            let fieldHtml = '';
            if (item.html_type === 'textarea') {
                fieldHtml = `<textarea placeholder="${item.placeholder || ''}" ${item.required ? 'required' : ''}></textarea>`;
            } else if (item.html_type === 'select') {
                fieldHtml = `<select ${item.required ? 'required' : ''}><option>Select option</option></select>`;
            } else if (item.html_type === 'checkbox') {
                fieldHtml = `<input type="checkbox" id="chk_${item.id}" ${item.required ? 'required' : ''}> <label for="chk_${item.id}">${item.label}</label>`;
            } else if (item.html_type === 'radio') {
                fieldHtml = `<input type="radio" name="radio_${item.id}" id="rad_${item.id}" ${item.required ? 'required' : ''}> <label for="rad_${item.id}">${item.label}</label>`;
            } else {
                fieldHtml = `<input type="${item.html_type}" placeholder="${item.placeholder || ''}" ${item.required ? 'required' : ''}>`;
            }
            
            const labelHtml = (showLabels && !isCheckbox && !isRadio) ? `<label data-field-id="${item.id}">${item.label}<span class="edit-hint">✏️ Click to edit</span></label>` : '';
            
            const fieldFullHtml = `
                <div class="form-field ${fieldClass}" data-id="${item.id}" data-label="${item.label}">
                    ${labelHtml}
                    ${fieldHtml}
                    <button class="field-drag" title="Drag to reorder">⋮⋮</button>
                    <button class="field-edit" onclick="editField('${item.id}')" title="Advanced Settings">⚙️</button>
                    <button class="field-remove" onclick="removeField('${item.id}')" title="Remove">🗑️</button>
                </div>
            `;
            
            jQuery(`.main-container[data-id="${parentContainerId}"] .main-drop-zone, .sub-div[data-id="${parentContainerId}"] .sub-drop-zone`).append(fieldFullHtml);
            
            if (showLabels && !isCheckbox && !isRadio) {
                const $label = jQuery(`.form-field[data-id="${item.id}"] label`);
                $label.on('dblclick', function(e) {
                    e.stopPropagation();
                    makeLabelEditable(jQuery(this), item.id);
                });
            }
            
            const $input = jQuery(`.form-field[data-id="${item.id}"] input, .form-field[data-id="${item.id}"] textarea`);
            if ($input.length && item.html_type !== 'checkbox' && item.html_type !== 'radio') {
                $input.on('dblclick', function(e) {
                    e.stopPropagation();
                    makePlaceholderEditable(jQuery(this), item.id);
                });
                $input.attr('title', 'Double-click to edit placeholder text');
            }
            
            jQuery(`.form-field[data-id="${item.id}"]`).on('click', function(e) {
                if (!jQuery(e.target).hasClass('field-remove') && !jQuery(e.target).hasClass('field-edit') && !jQuery(e.target).hasClass('field-drag')) {
                    e.stopPropagation();
                    selectElement(item.id, 'field', item.label);
                }
            });
        }
    }
}

function clearAllFields() {
    if (confirm('Clear all fields?')) {
        mainContainers = {};
        jQuery('#formBuilder').html('<div class="builder-placeholder">📦 Drag a Main Container here to start</div>');
        initMainDroppable();
        clearSelection();
        autoSave();
    }
}

function duplicateForm(formId) {
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_duplicate_form', form_id: formId, nonce: my_login_ajax.nonces.duplicate_form },
        success: function(r) { if (r.success) location.reload(); else alert('Error duplicating form'); }
    });
}

function deleteForm(formId) {
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_delete_form', form_id: formId, nonce: my_login_ajax.nonces.delete_form },
        success: function(r) { if (r.success) location.reload(); else alert('Error deleting form'); }
    });
}

function createNewForm() {
    const formName = jQuery('#newFormName').val();
    if (!formName) { alert('Enter form name'); return; }
    jQuery.ajax({
        url: my_login_ajax.ajax_url,
        type: 'POST',
        data: { action: 'my_login_create_form', form_name: formName, form_type: jQuery('#newFormType').val(), nonce: my_login_ajax.nonces.create_form },
        success: function(r) { if (r.success) location.reload(); else alert('Error creating form'); }
    });
}

function initMainDroppable() {
    jQuery('#formBuilder').droppable({
        accept: '.main-draggable',
        tolerance: 'pointer',
        hoverClass: 'droppable-active',
        drop: function(event, ui) {
            addMainContainer();
            jQuery('.builder-placeholder').remove();
            autoSave();
            return false;
        }
    });
}

function initDraggable() {
    jQuery('.draggable-field').draggable({
        helper: 'clone',
        revert: 'invalid',
        cursor: 'move',
        opacity: 0.7,
        zIndex: 1000,
        appendTo: 'body',
        start: function(event, ui) {
            draggedItemData = {
                type: jQuery(this).data('drag-type') || jQuery(this).data('type'),
                fieldType: jQuery(this).data('field-type'),
                fieldLabel: jQuery(this).data('field-label'),
                fieldHtmlType: jQuery(this).data('field-html-type'),
                socialProvider: jQuery(this).data('social-provider')
            };
        },
        stop: function() {
            draggedItemData = null;
        }
    });
}

// Modal functions
function closeFieldModal() { jQuery('#fieldSettingsModal').hide(); editingFieldId = null; }
function closeContainerModal() { jQuery('#containerSettingsModal').hide(); editingContainerId = null; }
function closeModal() { jQuery('#createFormModal').hide(); }

// Document ready
jQuery(document).ready(function($) {
    // Tab switching
    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
    // Form level controls
    $('#btnColor, #buttonText, #showLabels').on('input change', function() {
        updateAllSubmitButtons();
        autoSave();
    });
    
    // Form list actions
    $('.form-item').on('click', function(e) {
        if (!$(e.target).closest('.form-actions').length) {
            loadForm($(this).data('form-id'));
        }
    });
    
    $('.copy-shortcode').on('click', function(e) {
        e.stopPropagation();
        const formId = $(this).data('form-id');
        navigator.clipboard.writeText('[my_login_form id="' + formId + '"]');
        alert('Shortcode copied!');
    });
    
    $('.duplicate-form').on('click', function(e) {
        e.stopPropagation();
        if (confirm('Duplicate this form?')) duplicateForm($(this).data('form-id'));
    });
    
    $('.delete-form').on('click', function(e) {
        e.stopPropagation();
        if (confirm('Delete this form?')) deleteForm($(this).data('form-id'));
    });
    
    $('#createNewFormBtn').on('click', function() { $('#createFormModal').show(); });
    
    // Initialize draggable and droppable
    initDraggable();
    initMainDroppable();
    
    // Load form if ID exists
    if (currentFormId) loadForm(currentFormId);
});
</script>