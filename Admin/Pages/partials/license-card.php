<?php
/**
 * License Card — embedded at the top of the Settings page
 * (Admin/Pages/settings.php), not a standalone menu item.
 *
 * Deliberately always rendered regardless of license status — this is the
 * one piece of Settings that must stay reachable even when unlicensed, so
 * a fresh install can actually activate. Settings.php gates everything
 * BELOW this card on $is_active, not this card itself.
 *
 * @package MyLoginForm\Admin
 */

// Prevent Direct Access
defined('ABSPATH') || exit;

$license     = \MyLoginForm\Licensing\License::get_instance();
$status_data = $license->get_status_data();
$is_active   = $license->is_active();
$buy_url     = defined('MY_LOGIN_FORM_BUY_URL') ? MY_LOGIN_FORM_BUY_URL : '';
$dashboard_url = admin_url('admin.php?page=my-login-form-dashboard');
?>
<div id="mlf-license-card" style="max-width:700px;margin-bottom:24px;background:#ffffff;border:1px solid #DCE8D6;border-radius:14px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.06);">

    <?php if ($is_active): ?>
        <div style="display:flex;gap:16px;align-items:center;padding:24px;background:#F4F8F1;border-bottom:1px solid #DCE8D6;">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#1FBB00,#0F5900);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-check-circle" style="color:#fff;font-size:22px;"></i>
            </div>
            <div>
                <h2 style="margin:0 0 4px;font-size:18px;color:#1a1a1a;"><?php _e('License Active', 'my-login-form'); ?></h2>
                <p style="margin:0;color:#444444;font-size:13px;"><?php _e('All features are unlocked on this site.', 'my-login-form'); ?></p>
            </div>
        </div>
        <div style="padding:24px;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px;">
                <tr>
                    <th style="text-align:left;padding:8px 0;color:#666666;width:160px;"><?php _e('License Key', 'my-login-form'); ?></th>
                    <td style="padding:8px 0;color:#2A2A2A;font-family:monospace;"><?php echo esc_html($license->mask_key($status_data['key'])); ?></td>
                </tr>
                <tr>
                    <th style="text-align:left;padding:8px 0;color:#666666;"><?php _e('Email', 'my-login-form'); ?></th>
                    <td style="padding:8px 0;color:#2A2A2A;"><?php echo esc_html($status_data['email']); ?></td>
                </tr>
                <tr>
                    <th style="text-align:left;padding:8px 0;color:#666666;"><?php _e('Plan', 'my-login-form'); ?></th>
                    <td style="padding:8px 0;color:#2A2A2A;"><?php echo esc_html(ucwords(str_replace('-', ' ', $status_data['plan']))); ?></td>
                </tr>
                <tr>
                    <th style="text-align:left;padding:8px 0;color:#666666;"><?php _e('Expires', 'my-login-form'); ?></th>
                    <td style="padding:8px 0;color:#2A2A2A;">
                        <?php echo $status_data['expires']
                            ? esc_html(date_i18n(get_option('date_format'), strtotime($status_data['expires'])))
                            : esc_html__('Never (lifetime)', 'my-login-form'); ?>
                    </td>
                </tr>
                <?php if ($status_data['days_until_expiry'] !== null): ?>
                <tr>
                    <th style="text-align:left;padding:8px 0;color:#666666;"><?php _e('Days Remaining', 'my-login-form'); ?></th>
                    <td style="padding:8px 0;color:#2A2A2A;"><?php echo esc_html((string) $status_data['days_until_expiry']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <button id="mlf-license-deactivate" style="background:#fff;color:#8A0000;border:1.5px solid #8A0000;border-radius:8px;padding:10px 20px;font-weight:600;cursor:pointer;">
                <i class="fas fa-unlink"></i> <?php _e('Deactivate License', 'my-login-form'); ?>
            </button>
            <div id="mlf-license-message" style="display:none;margin-top:14px;font-size:13px;"></div>
        </div>

    <?php else: ?>
        <div style="display:flex;gap:16px;align-items:center;padding:24px;background:#F4F8F1;border-bottom:1px solid #DCE8D6;">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#B35B00,#8A4700);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-lock" style="color:#fff;font-size:20px;"></i>
            </div>
            <div>
                <h2 style="margin:0 0 4px;font-size:18px;color:#1a1a1a;"><?php _e('Activate Your License', 'my-login-form'); ?></h2>
                <p style="margin:0;color:#444444;font-size:13px;"><?php _e('Enter the email and license key from your purchase confirmation to unlock all features.', 'my-login-form'); ?></p>
            </div>
        </div>
        <div style="padding:24px;">
            <div style="margin-bottom:18px;">
                <label for="mlf-license-email" style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#2A2A2A;"><?php _e('Email Address', 'my-login-form'); ?></label>
                <input type="email" id="mlf-license-email" placeholder="you@example.com"
                       style="width:100%;padding:10px 12px;border:1.5px solid #DCE8D6;border-radius:8px;font-size:14px;color:#2A2A2A;background:#fff;">
            </div>
            <div style="margin-bottom:18px;">
                <label for="mlf-license-key" style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#2A2A2A;"><?php _e('License Key', 'my-login-form'); ?></label>
                <input type="text" id="mlf-license-key" placeholder="XXXX-XXXX-XXXX-XXXX-XXXX"
                       style="width:100%;padding:10px 12px;border:1.5px solid #DCE8D6;border-radius:8px;font-size:14px;color:#2A2A2A;background:#fff;font-family:monospace;">
            </div>
            <button id="mlf-license-activate" style="background:linear-gradient(135deg,#1FBB00,#0F5900);color:#fff;border:none;border-radius:8px;padding:12px 26px;font-weight:700;font-size:14px;cursor:pointer;">
                <i class="fas fa-unlock"></i> <?php _e('Activate License', 'my-login-form'); ?>
            </button>
            <div id="mlf-license-message" style="display:none;margin-top:14px;font-size:13px;"></div>

            <?php if ($buy_url): ?>
            <p style="margin-top:22px;font-size:13px;color:#666666;">
                <?php printf(
                    __('Don\'t have a license key yet? %sPurchase one here%s.', 'my-login-form'),
                    '<a href="' . esc_url($buy_url) . '" target="_blank" style="color:#0F5900;font-weight:600;">',
                    '</a>'
                ); ?>
            </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    var nonce = '<?php echo esc_js(wp_create_nonce('my_login_license_nonce')); ?>';
    var $msg  = $('#mlf-license-message');

    function showMessage(text, isSuccess) {
        $msg.show().css('color', isSuccess ? '#0F5900' : '#8A0000').text(text);
    }

    $('#mlf-license-activate').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        $.post(ajaxurl, {
            action: 'my_login_license_activate',
            nonce: nonce,
            email: $('#mlf-license-email').val(),
            license_key: $('#mlf-license-key').val()
        }).done(function(r) {
            showMessage((r && r.data && r.data.message) ? r.data.message : '<?php echo esc_js(__('Something went wrong.', 'my-login-form')); ?>', !!(r && r.success));
            if (r && r.success) {
                // Verified — go straight to the Dashboard instead of
                // reloading back onto this same Settings page.
                setTimeout(function(){ window.location.href = '<?php echo esc_js($dashboard_url); ?>'; }, 1200);
            } else {
                $btn.prop('disabled', false);
            }
        }).fail(function() {
            showMessage('<?php echo esc_js(__('Network error. Please try again.', 'my-login-form')); ?>', false);
            $btn.prop('disabled', false);
        });
    });

    $('#mlf-license-deactivate').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Deactivate this license? Premium features will be disabled on this site.', 'my-login-form')); ?>')) {
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.post(ajaxurl, {
            action: 'my_login_license_deactivate',
            nonce: nonce
        }).done(function(r) {
            showMessage((r && r.data && r.data.message) ? r.data.message : '', true);
            setTimeout(function(){ location.reload(); }, 1200);
        }).fail(function() {
            showMessage('<?php echo esc_js(__('Network error. Please try again.', 'my-login-form')); ?>', false);
            $btn.prop('disabled', false);
        });
    });
});
</script>
