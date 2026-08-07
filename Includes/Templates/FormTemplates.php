<?php

/**
 * Built-in, ready-to-use visual templates offered in the "Create New Form"
 * picker. Each entry is just data — a settings/custom_css pair that gets fed
 * straight into DesignerAjax::build_form_css(), the same pipeline that
 * already turns an admin's hand-written Designer CSS into a form's .css
 * file. No new rendering path, no schema change: picking a template only
 * pre-fills what an admin could otherwise have pasted into the CSS tab
 * themselves.
 *
 * @package MyLoginForm\Templates
 */

namespace MyLoginForm\Templates;

defined('ABSPATH') || exit;

class FormTemplates {

    /**
     * @return array<string,array{name:string,description:string,icon:string,swatch:string,accent:string,border_color:string,custom_css:string}>
     */
    public static function get_all(): array {
        return [
            'aurora_glass'    => self::aurora_glass(),
            'violet_glass'    => self::violet_glass(),
            'sunset_gradient' => self::sunset_gradient(),
            'midnight_neon'   => self::midnight_neon(),
            'minimal_mono'    => self::minimal_mono(),
            'ocean_breeze'    => self::ocean_breeze(),
        ];
    }

    public static function get(string $key): ?array {
        $all = self::get_all();
        return $all[$key] ?? null;
    }

    private static function aurora_glass(): array {
        return [
            'name'         => __('Aurora Glass', 'my-login-form'),
            'description'  => __('Frosted glassmorphism card floating over a soft brand-green glow.', 'my-login-form'),
            'icon'         => '🌌',
            'swatch'       => 'linear-gradient(135deg,#EAFBEA 0%,#E3F8E8 45%,#D7F3E0 100%)',
            'accent'       => '#1FBB00',
            'border_color' => '#CFF0CB',
            'preview'      => [
                'title'       => '#173A0E',
                'input_bg'    => 'rgba(255,255,255,.65)',
                'input_border'=> 'rgba(31,187,0,.3)',
                'button'      => 'linear-gradient(135deg,#1FBB00 0%,#0F5900 100%)',
            ],
            'custom_css'   => <<<'CSS'
/* ===== Aurora Glass ===== */
.my-login-wrap{
  --mlf-primary: var(--wp--preset--color--primary, #1FBB00);
  --mlf-primary-dark: var(--wp--preset--color--primary-dark, #39E75F);
}
.my-login-wrap,.my-login-wrap-1,.my-login-wrap-2,.my-login-wrap-3,.my-login-wrap-4,.my-login-wrap-5,.my-login-wrap-6,#my-login-wrap-1{
  width:100% !important;max-width:420px !important;min-width:280px !important;margin:48px auto !important;
  padding:0 0 28px !important;box-sizing:border-box;position:relative;overflow:hidden;border-radius:24px;
  background:linear-gradient(180deg,rgba(255,255,255,.72),rgba(255,255,255,.55));
  -webkit-backdrop-filter:blur(22px) saturate(180%);backdrop-filter:blur(22px) saturate(180%);
  border:1px solid rgba(255,255,255,.7);
  box-shadow:0 1px 0 rgba(255,255,255,.9) inset,0 30px 60px -20px rgba(10,40,10,.35),0 0 80px -30px rgba(31,187,0,.45);
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
}
.my-login-wrap::before{content:"";position:absolute;top:-90px;left:-70px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(31,187,0,.32),transparent 70%);pointer-events:none;}
.my-login-wrap::after{content:"";position:absolute;bottom:-80px;right:-60px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(57,231,95,.32),transparent 70%);pointer-events:none;}
.my-login-wrap>*{position:relative;z-index:1;}
.my-login-wrap .my-login-main-container,.my-login-wrap form,.my-login-wrap fieldset{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 !important;border-radius:0 !important;max-width:none !important;width:auto !important;}
.my-login-greeting{padding:36px 32px 24px;text-align:center;}
.my-login-greeting-title{margin:0 0 6px !important;font-size:28px;font-weight:800;letter-spacing:-.5px;color:#173A0E !important;}
.my-login-greeting-subtitle{margin:0 !important;font-size:14px;color:#4C6B48 !important;}
.my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:32px !important;padding-right:32px !important;}
.my-login-wrap .my-login-main-container{padding-top:22px !important;}
.my-login-form-field{margin-bottom:16px !important;}
.my-login-wrap .my-login-label{display:block;margin-bottom:7px;font-size:13px;font-weight:600;color:#33502F !important;}
.my-login-req{color:#E5484D !important;margin-left:3px;}
.my-login-wrap input:not([type=checkbox]):not([type=radio]){
  width:100% !important;box-sizing:border-box;padding:14px 16px !important;font-size:15px;line-height:1.4;
  color:#1B3317 !important;background:rgba(255,255,255,.55) !important;border:1px solid rgba(31,187,0,.28) !important;
  border-radius:14px !important;outline:none;box-shadow:0 1px 0 rgba(255,255,255,.8) inset !important;
  transition:border-color .2s,box-shadow .2s,background .2s;
}
.my-login-wrap input::placeholder{color:#7C9678 !important;opacity:1;}
.my-login-wrap input:focus{background:rgba(255,255,255,.78) !important;border-color:var(--mlf-primary) !important;box-shadow:0 0 0 4px rgba(31,187,0,.22) !important;}
.my-login-wrap input:-webkit-autofill,.my-login-wrap input:-webkit-autofill:hover,.my-login-wrap input:-webkit-autofill:focus,.my-login-wrap input:-webkit-autofill:active{
  -webkit-text-fill-color:#1B3317;-webkit-box-shadow:0 0 0 1000px #EAF7EC inset;box-shadow:0 0 0 1000px #EAF7EC inset;caret-color:#1B3317;transition:background-color 5000s ease-in-out 0s;
}
.my-login-password-wrap{position:relative;}
.my-login-password-wrap input{padding-right:48px !important;}
.my-login-wrap .my-login-password-toggle{position:absolute;top:50%;right:9px;transform:translateY(-50%);width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:0 !important;border-radius:9px;background:transparent !important;color:#7C9678 !important;cursor:pointer;box-shadow:none !important;transition:background .15s,color .15s;}
.my-login-wrap .my-login-password-toggle i{color:inherit !important;}
.my-login-wrap .my-login-password-toggle:hover{background:rgba(31,187,0,.14) !important;color:var(--mlf-primary) !important;}
.my-login-caps-hint{display:none;margin-top:6px;font-size:12px;color:#B35B00;}
.my-login-caps-hint.is-visible{display:block;}
.my-login-options-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 22px;font-size:13.5px;}
.my-login-remember{display:flex;align-items:center;gap:8px;color:#33502F !important;cursor:pointer;font-weight:500;margin:0 !important;}
.my-login-remember input{accent-color:var(--mlf-primary);width:15px !important;height:15px !important;margin:0 !important;cursor:pointer;}
.my-login-options-row a{color:var(--mlf-primary) !important;text-decoration:none;font-weight:600;}
.my-login-options-row a:hover{color:var(--mlf-primary-dark) !important;}
.my-login-form-submit{margin-top:4px !important;}
.my-login-wrap .my-login-submit-btn,.my-login-wrap button[type=submit]{
  position:relative;width:100% !important;padding:15px 20px !important;font-size:15.5px;font-weight:700;letter-spacing:.3px;
  color:#fff !important;border:0 !important;border-radius:14px !important;cursor:pointer;overflow:hidden;
  background:var(--wp--preset--gradient--primary-gradient, linear-gradient(135deg, #1FBB00 0%, #0F5900 100%)) !important;
  box-shadow:0 1px 0 rgba(255,255,255,.35) inset,0 12px 26px -10px rgba(31,187,0,.55) !important;
  transition:transform .15s,box-shadow .2s,filter .2s;
}
.my-login-wrap .my-login-submit-btn:hover{filter:brightness(1.06);box-shadow:0 1px 0 rgba(255,255,255,.35) inset,0 16px 32px -10px rgba(31,187,0,.65) !important;}
.my-login-wrap .my-login-submit-btn:active{transform:translateY(1px);}
.my-login-wrap .my-login-submit-btn:disabled{opacity:.55;cursor:not-allowed;filter:none;}
.my-login-msg:empty{display:none;}
.my-login-wrap .my-login-msg{margin:22px 0 0 !important;padding:12px 14px !important;border-radius:12px;font-size:13.5px;background:rgba(229,72,77,.1);border:1px solid rgba(229,72,77,.3);color:#B3261E;}
.my-login-wrap .my-login-msg.success{background:rgba(31,187,0,.1);border-color:rgba(31,187,0,.3);color:var(--mlf-primary);}
.my-login-otp-form{display:none;}
.my-login-otp-form.is-visible{display:block;padding-top:22px !important;}
.my-login-otp-form input[name=token]{text-align:center;font-size:26px !important;font-weight:700;letter-spacing:10px;padding:15px 10px !important;}
.my-login-wrap .my-login-otp-resend{display:block;width:100%;margin-top:14px;padding:11px;font-size:13.5px;font-weight:600;color:var(--mlf-primary) !important;background:rgba(31,187,0,.08) !important;border:1px solid rgba(31,187,0,.25) !important;border-radius:11px !important;cursor:pointer;box-shadow:none !important;transition:background .15s;}
.my-login-wrap .my-login-otp-resend:hover{background:rgba(31,187,0,.16) !important;}
.my-login-wrap .my-login-extra-links{margin-top:26px !important;padding-top:20px !important;border-top:1px solid rgba(31,187,0,.18);text-align:center;font-size:13.5px;color:#4C6B48 !important;}
.my-login-wrap .my-login-extra-links a{color:#1B3317 !important;text-decoration:none;font-weight:700;}
.my-login-wrap .my-login-extra-links a:hover{color:var(--mlf-primary) !important;}
.my-login-wrap .my-login-extra-links i{opacity:.75;}
@media (max-width:480px){
  .my-login-wrap{margin:24px 16px !important;border-radius:20px;}
  .my-login-greeting{padding:28px 24px 20px;}
  .my-login-greeting-title{font-size:24px;}
  .my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:24px !important;padding-right:24px !important;}
}
CSS,
        ];
    }

    private static function violet_glass(): array {
        return [
            'name'         => __('Violet Glass', 'my-login-form'),
            'description'  => __('Frosted glassmorphism card floating over a soft violet-cyan glow.', 'my-login-form'),
            'icon'         => '🔮',
            'swatch'       => 'linear-gradient(135deg,#EEF2FF 0%,#E0E7FF 40%,#CFFAFE 100%)',
            'accent'       => '#6D5EF7',
            'border_color' => '#D9D3FB',
            'preview'      => [
                'title'        => '#1E1B4B',
                'input_bg'     => 'rgba(255,255,255,.65)',
                'input_border' => 'rgba(109,94,247,.3)',
                'button'       => 'linear-gradient(100deg,#6D5EF7 0%,#8B7CFA 55%,#22D3EE 100%)',
            ],
            'custom_css'   => <<<'CSS'
/* ===== Violet Glass ===== */
.my-login-wrap,.my-login-wrap-1,.my-login-wrap-2,.my-login-wrap-3,.my-login-wrap-4,.my-login-wrap-5,.my-login-wrap-6,#my-login-wrap-1{
  width:100% !important;max-width:420px !important;min-width:280px !important;margin:48px auto !important;
  padding:0 0 28px !important;box-sizing:border-box;position:relative;overflow:hidden;border-radius:24px;
  background:linear-gradient(180deg,rgba(255,255,255,.72),rgba(255,255,255,.55));
  -webkit-backdrop-filter:blur(22px) saturate(180%);backdrop-filter:blur(22px) saturate(180%);
  border:1px solid rgba(255,255,255,.7);
  box-shadow:0 1px 0 rgba(255,255,255,.9) inset,0 30px 60px -20px rgba(76,70,117,.35),0 0 80px -30px rgba(109,94,247,.45);
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
}
.my-login-wrap::before{content:"";position:absolute;top:-90px;left:-70px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(109,94,247,.35),transparent 70%);pointer-events:none;}
.my-login-wrap::after{content:"";position:absolute;bottom:-80px;right:-60px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(34,211,238,.35),transparent 70%);pointer-events:none;}
.my-login-wrap>*{position:relative;z-index:1;}
.my-login-wrap .my-login-main-container,.my-login-wrap form,.my-login-wrap fieldset{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 !important;border-radius:0 !important;max-width:none !important;width:auto !important;}
.my-login-greeting{padding:36px 32px 24px;text-align:center;}
.my-login-greeting-title{margin:0 0 6px !important;font-size:28px;font-weight:800;letter-spacing:-.5px;color:#1E1B4B !important;}
.my-login-greeting-subtitle{margin:0 !important;font-size:14px;color:#5B5580 !important;}
.my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:32px !important;padding-right:32px !important;}
.my-login-wrap .my-login-main-container{padding-top:22px !important;}
.my-login-form-field{margin-bottom:16px !important;}
.my-login-wrap .my-login-label{display:block;margin-bottom:7px;font-size:13px;font-weight:600;color:#3F3A66 !important;}
.my-login-req{color:#E5484D !important;margin-left:3px;}
.my-login-wrap input:not([type=checkbox]):not([type=radio]){
  width:100% !important;box-sizing:border-box;padding:14px 16px !important;font-size:15px;line-height:1.4;
  color:#241F45 !important;background:rgba(255,255,255,.55) !important;border:1px solid rgba(109,94,247,.28) !important;
  border-radius:14px !important;outline:none;box-shadow:0 1px 0 rgba(255,255,255,.8) inset !important;
  transition:border-color .2s,box-shadow .2s,background .2s;
}
.my-login-wrap input::placeholder{color:#8B85B3 !important;opacity:1;}
.my-login-wrap input:focus{background:rgba(255,255,255,.78) !important;border-color:#6D5EF7 !important;box-shadow:0 0 0 4px rgba(109,94,247,.22) !important;}
.my-login-wrap input:-webkit-autofill,.my-login-wrap input:-webkit-autofill:hover,.my-login-wrap input:-webkit-autofill:focus,.my-login-wrap input:-webkit-autofill:active{
  -webkit-text-fill-color:#241F45;-webkit-box-shadow:0 0 0 1000px #F1EEFF inset;box-shadow:0 0 0 1000px #F1EEFF inset;caret-color:#241F45;transition:background-color 5000s ease-in-out 0s;
}
.my-login-password-wrap{position:relative;}
.my-login-password-wrap input{padding-right:48px !important;}
.my-login-wrap .my-login-password-toggle{position:absolute;top:50%;right:9px;transform:translateY(-50%);width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:0 !important;border-radius:9px;background:transparent !important;color:#8B85B3 !important;cursor:pointer;box-shadow:none !important;transition:background .15s,color .15s;}
.my-login-wrap .my-login-password-toggle i{color:inherit !important;}
.my-login-wrap .my-login-password-toggle:hover{background:rgba(109,94,247,.14) !important;color:#6D5EF7 !important;}
.my-login-caps-hint{display:none;margin-top:6px;font-size:12px;color:#B35B00;}
.my-login-caps-hint.is-visible{display:block;}
.my-login-options-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 22px;font-size:13.5px;}
.my-login-remember{display:flex;align-items:center;gap:8px;color:#3F3A66 !important;cursor:pointer;font-weight:500;margin:0 !important;}
.my-login-remember input{accent-color:#6D5EF7;width:15px !important;height:15px !important;margin:0 !important;cursor:pointer;}
.my-login-options-row a{color:#6D5EF7 !important;text-decoration:none;font-weight:600;}
.my-login-options-row a:hover{color:#4C3FD6 !important;}
.my-login-form-submit{margin-top:4px !important;}
.my-login-wrap .my-login-submit-btn,.my-login-wrap button[type=submit]{
  position:relative;width:100% !important;padding:15px 20px !important;font-size:15.5px;font-weight:700;letter-spacing:.3px;
  color:#fff !important;border:0 !important;border-radius:14px !important;cursor:pointer;overflow:hidden;
  background:linear-gradient(100deg,#6D5EF7 0%,#8B7CFA 55%,#22D3EE 100%) !important;
  box-shadow:0 1px 0 rgba(255,255,255,.35) inset,0 12px 26px -10px rgba(109,94,247,.55) !important;
  transition:transform .15s,box-shadow .2s,filter .2s;
}
.my-login-wrap .my-login-submit-btn:hover{filter:brightness(1.06);box-shadow:0 1px 0 rgba(255,255,255,.35) inset,0 16px 32px -10px rgba(109,94,247,.65) !important;}
.my-login-wrap .my-login-submit-btn:active{transform:translateY(1px);}
.my-login-wrap .my-login-submit-btn:disabled{opacity:.55;cursor:not-allowed;filter:none;}
.my-login-msg:empty{display:none;}
.my-login-wrap .my-login-msg{margin:22px 0 0 !important;padding:12px 14px !important;border-radius:12px;font-size:13.5px;background:rgba(229,72,77,.1);border:1px solid rgba(229,72,77,.3);color:#B3261E;}
.my-login-wrap .my-login-msg.success{background:rgba(109,94,247,.1);border-color:rgba(109,94,247,.3);color:#4C3FD6;}
.my-login-otp-form{display:none;}
.my-login-otp-form.is-visible{display:block;padding-top:22px !important;}
.my-login-otp-form input[name=token]{text-align:center;font-size:26px !important;font-weight:700;letter-spacing:10px;padding:15px 10px !important;}
.my-login-wrap .my-login-otp-resend{display:block;width:100%;margin-top:14px;padding:11px;font-size:13.5px;font-weight:600;color:#4C3FD6 !important;background:rgba(109,94,247,.08) !important;border:1px solid rgba(109,94,247,.25) !important;border-radius:11px !important;cursor:pointer;box-shadow:none !important;transition:background .15s;}
.my-login-wrap .my-login-otp-resend:hover{background:rgba(109,94,247,.16) !important;}
.my-login-wrap .my-login-extra-links{margin-top:26px !important;padding-top:20px !important;border-top:1px solid rgba(109,94,247,.18);text-align:center;font-size:13.5px;color:#5B5580 !important;}
.my-login-wrap .my-login-extra-links a{color:#241F45 !important;text-decoration:none;font-weight:700;}
.my-login-wrap .my-login-extra-links a:hover{color:#6D5EF7 !important;}
.my-login-wrap .my-login-extra-links i{opacity:.75;}
@media (max-width:480px){
  .my-login-wrap{margin:24px 16px !important;border-radius:20px;}
  .my-login-greeting{padding:28px 24px 20px;}
  .my-login-greeting-title{font-size:24px;}
  .my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:24px !important;padding-right:24px !important;}
}
CSS,
        ];
    }

    private static function sunset_gradient(): array {
        return [
            'name'         => __('Sunset Gradient', 'my-login-form'),
            'description'  => __('Warm coral-to-orange gradient card with pill-shaped inputs.', 'my-login-form'),
            'icon'         => '🌅',
            'swatch'       => 'linear-gradient(135deg,#FF5F6D 0%,#FFA34D 100%)',
            'accent'       => '#FF6B4A',
            'border_color' => '#FFD3B0',
            'preview'      => [
                'title'        => 'rgba(255,255,255,.95)',
                'input_bg'     => 'rgba(255,255,255,.2)',
                'input_border' => 'rgba(255,255,255,.4)',
                'button'       => 'linear-gradient(100deg,#ffffff 0%,#FFF3E8 100%)',
            ],
            'custom_css'   => <<<'CSS'
/* ===== Sunset Gradient ===== */
.my-login-wrap,.my-login-wrap-1,.my-login-wrap-2,.my-login-wrap-3,.my-login-wrap-4,.my-login-wrap-5,.my-login-wrap-6,#my-login-wrap-1{
  width:100% !important;max-width:400px !important;min-width:280px !important;margin:48px auto !important;
  padding:0 0 26px !important;box-sizing:border-box;position:relative;overflow:hidden;border-radius:22px;
  background:linear-gradient(160deg,#FF5F6D 0%,#FF8A5C 50%,#FFB347 100%);
  border:1px solid rgba(255,255,255,.25);
  box-shadow:0 1px 0 rgba(255,255,255,.25) inset,0 30px 60px -18px rgba(120,30,10,.45),0 0 60px -20px rgba(255,140,80,.5);
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
}
.my-login-wrap::after{content:"";position:absolute;bottom:-60px;left:-60px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.22),transparent 70%);pointer-events:none;}
.my-login-wrap>*{position:relative;z-index:1;}
.my-login-wrap .my-login-main-container,.my-login-wrap form,.my-login-wrap fieldset{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 !important;border-radius:0 !important;max-width:none !important;width:auto !important;}
.my-login-greeting{padding:34px 32px 24px;text-align:center;}
.my-login-greeting-title{margin:0 0 6px !important;font-size:29px;font-weight:800;letter-spacing:-.5px;color:#fff !important;text-shadow:0 2px 14px rgba(120,30,10,.4);}
.my-login-greeting-subtitle{margin:0 !important;font-size:14px;color:rgba(255,255,255,.85) !important;}
.my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:32px !important;padding-right:32px !important;}
.my-login-wrap .my-login-main-container{padding-top:24px !important;}
.my-login-form-field{margin-bottom:16px !important;}
.my-login-wrap .my-login-label{display:block;margin-bottom:7px;font-size:13px;font-weight:600;color:rgba(255,255,255,.92) !important;}
.my-login-req{color:#FFE082 !important;margin-left:3px;}
.my-login-wrap input:not([type=checkbox]):not([type=radio]){
  width:100% !important;box-sizing:border-box;padding:14px 18px !important;font-size:15px;line-height:1.4;
  color:#fff !important;background:rgba(255,255,255,.16) !important;border:1px solid rgba(255,255,255,.35) !important;
  border-radius:999px !important;outline:none;box-shadow:0 1px 0 rgba(255,255,255,.15) inset !important;
  transition:border-color .2s,box-shadow .2s,background .2s;
}
.my-login-wrap input::placeholder{color:rgba(255,255,255,.7) !important;opacity:1;}
.my-login-wrap input:focus{background:rgba(255,255,255,.26) !important;border-color:#fff !important;box-shadow:0 0 0 4px rgba(255,255,255,.22) !important;}
.my-login-wrap input:-webkit-autofill,.my-login-wrap input:-webkit-autofill:hover,.my-login-wrap input:-webkit-autofill:focus,.my-login-wrap input:-webkit-autofill:active{
  -webkit-text-fill-color:#fff;-webkit-box-shadow:0 0 0 1000px #FF8154 inset;box-shadow:0 0 0 1000px #FF8154 inset;caret-color:#fff;transition:background-color 5000s ease-in-out 0s;
}
.my-login-password-wrap{position:relative;}
.my-login-password-wrap input{padding-right:48px !important;}
.my-login-wrap .my-login-password-toggle{position:absolute;top:50%;right:12px;transform:translateY(-50%);width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:0 !important;border-radius:50%;background:transparent !important;color:rgba(255,255,255,.75) !important;cursor:pointer;box-shadow:none !important;transition:background .15s,color .15s;}
.my-login-wrap .my-login-password-toggle i{color:inherit !important;}
.my-login-wrap .my-login-password-toggle:hover{background:rgba(255,255,255,.2) !important;color:#fff !important;}
.my-login-caps-hint{display:none;margin-top:6px;font-size:12px;color:#FFE082;}
.my-login-caps-hint.is-visible{display:block;}
.my-login-options-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 22px;font-size:13.5px;}
.my-login-remember{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.9) !important;cursor:pointer;font-weight:500;margin:0 !important;}
.my-login-remember input{accent-color:#fff;width:15px !important;height:15px !important;margin:0 !important;cursor:pointer;}
.my-login-options-row a{color:#fff !important;text-decoration:underline;font-weight:600;}
.my-login-options-row a:hover{color:#FFE082 !important;}
.my-login-form-submit{margin-top:4px !important;}
.my-login-wrap .my-login-submit-btn,.my-login-wrap button[type=submit]{
  position:relative;width:100% !important;padding:15px 20px !important;font-size:15.5px;font-weight:700;letter-spacing:.3px;
  color:#D8420C !important;border:0 !important;border-radius:999px !important;cursor:pointer;overflow:hidden;
  background:linear-gradient(100deg,#ffffff 0%,#FFF3E8 100%) !important;
  box-shadow:0 1px 0 rgba(255,255,255,.6) inset,0 12px 26px -10px rgba(120,30,10,.5) !important;
  transition:transform .15s,box-shadow .2s,filter .2s;
}
.my-login-wrap .my-login-submit-btn:hover{filter:brightness(1.03);box-shadow:0 1px 0 rgba(255,255,255,.6) inset,0 16px 32px -10px rgba(120,30,10,.6) !important;}
.my-login-wrap .my-login-submit-btn:active{transform:translateY(1px);}
.my-login-wrap .my-login-submit-btn:disabled{opacity:.55;cursor:not-allowed;filter:none;}
.my-login-msg:empty{display:none;}
.my-login-wrap .my-login-msg{margin:22px 0 0 !important;padding:12px 16px !important;border-radius:999px;font-size:13.5px;background:rgba(0,0,0,.16);border:1px solid rgba(255,255,255,.3);color:#fff;text-align:center;}
.my-login-wrap .my-login-msg.success{background:rgba(0,0,0,.16);border-color:rgba(255,255,255,.4);color:#fff;}
.my-login-otp-form{display:none;}
.my-login-otp-form.is-visible{display:block;padding-top:24px !important;}
.my-login-otp-form input[name=token]{text-align:center;font-size:26px !important;font-weight:700;letter-spacing:10px;padding:15px 10px !important;border-radius:18px !important;}
.my-login-wrap .my-login-otp-resend{display:block;width:100%;margin-top:14px;padding:11px;font-size:13.5px;font-weight:600;color:#fff !important;background:rgba(255,255,255,.16) !important;border:1px solid rgba(255,255,255,.3) !important;border-radius:999px !important;cursor:pointer;box-shadow:none !important;transition:background .15s;}
.my-login-wrap .my-login-otp-resend:hover{background:rgba(255,255,255,.28) !important;}
.my-login-wrap .my-login-extra-links{margin-top:26px !important;padding-top:20px !important;border-top:1px solid rgba(255,255,255,.25);text-align:center;font-size:13.5px;color:rgba(255,255,255,.85) !important;}
.my-login-wrap .my-login-extra-links a{color:#fff !important;text-decoration:underline;font-weight:700;}
.my-login-wrap .my-login-extra-links a:hover{color:#FFE082 !important;}
.my-login-wrap .my-login-extra-links i{opacity:.85;}
@media (max-width:480px){
  .my-login-wrap{margin:24px 16px !important;border-radius:18px;}
  .my-login-greeting{padding:28px 24px 20px;}
  .my-login-greeting-title{font-size:24px;}
  .my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:24px !important;padding-right:24px !important;}
}
CSS,
        ];
    }

    private static function midnight_neon(): array {
        return [
            'name'         => __('Midnight Neon', 'my-login-form'),
            'description'  => __('Near-black card with glowing cyan-to-magenta neon accents.', 'my-login-form'),
            'icon'         => '⚡',
            'swatch'       => 'linear-gradient(135deg,#0A0A12 0%,#151425 60%,#1E1230 100%)',
            'accent'       => '#00E5FF',
            'border_color' => '#2A2A3D',
            'preview'      => [
                'title'        => '#EAF6FF',
                'input_bg'     => 'rgba(255,255,255,.06)',
                'input_border' => 'rgba(0,229,255,.35)',
                'button'       => 'linear-gradient(100deg,#00E5FF 0%,#7CF2FF 50%,#FF2EC4 100%)',
            ],
            'custom_css'   => <<<'CSS'
/* ===== Midnight Neon ===== */
.my-login-wrap,.my-login-wrap-1,.my-login-wrap-2,.my-login-wrap-3,.my-login-wrap-4,.my-login-wrap-5,.my-login-wrap-6,#my-login-wrap-1{
  width:100% !important;max-width:400px !important;min-width:280px !important;margin:48px auto !important;
  padding:0 0 26px !important;box-sizing:border-box;position:relative;overflow:hidden;border-radius:20px;
  background:linear-gradient(165deg,#0D0D18 0%,#14131F 45%,#0A0A12 100%);
  border:1px solid rgba(0,229,255,.25);
  box-shadow:0 1px 0 rgba(255,255,255,.05) inset,0 30px 60px -18px rgba(0,0,0,.85),0 0 70px -25px rgba(255,46,196,.35),0 0 50px -25px rgba(0,229,255,.4);
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
}
.my-login-wrap::before{content:"";position:absolute;top:-1px;left:10%;right:10%;height:1px;background:linear-gradient(90deg,transparent,#00E5FF,#FF2EC4,transparent);opacity:.7;pointer-events:none;}
.my-login-wrap::after{content:"";position:absolute;bottom:-70px;right:-50px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(255,46,196,.28),transparent 70%);pointer-events:none;}
.my-login-wrap>*{position:relative;z-index:1;}
.my-login-wrap .my-login-main-container,.my-login-wrap form,.my-login-wrap fieldset{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 !important;border-radius:0 !important;max-width:none !important;width:auto !important;}
.my-login-greeting{padding:34px 32px 24px;text-align:center;}
.my-login-greeting-title{margin:0 0 6px !important;font-size:28px;font-weight:800;letter-spacing:-.3px;color:#fff !important;text-shadow:0 0 18px rgba(0,229,255,.5);}
.my-login-greeting-subtitle{margin:0 !important;font-size:14px;color:#8B8FA8 !important;}
.my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:32px !important;padding-right:32px !important;}
.my-login-wrap .my-login-main-container{padding-top:24px !important;}
.my-login-form-field{margin-bottom:16px !important;}
.my-login-wrap .my-login-label{display:block;margin-bottom:7px;font-size:13px;font-weight:600;color:#B7BAD1 !important;}
.my-login-req{color:#FF2EC4 !important;margin-left:3px;}
.my-login-wrap input:not([type=checkbox]):not([type=radio]){
  width:100% !important;box-sizing:border-box;padding:14px 16px !important;font-size:15px;line-height:1.4;
  color:#EAF6FF !important;background:rgba(255,255,255,.04) !important;border:1px solid rgba(0,229,255,.22) !important;
  border-radius:12px !important;outline:none;box-shadow:0 1px 0 rgba(255,255,255,.04) inset !important;
  transition:border-color .2s,box-shadow .2s,background .2s;
}
.my-login-wrap input::placeholder{color:#5C6079 !important;opacity:1;}
.my-login-wrap input:focus{background:rgba(0,229,255,.06) !important;border-color:#00E5FF !important;box-shadow:0 0 0 4px rgba(0,229,255,.18),0 0 20px -6px rgba(0,229,255,.5) !important;}
.my-login-wrap input:-webkit-autofill,.my-login-wrap input:-webkit-autofill:hover,.my-login-wrap input:-webkit-autofill:focus,.my-login-wrap input:-webkit-autofill:active{
  -webkit-text-fill-color:#EAF6FF;-webkit-box-shadow:0 0 0 1000px #12111C inset;box-shadow:0 0 0 1000px #12111C inset;caret-color:#00E5FF;transition:background-color 5000s ease-in-out 0s;
}
.my-login-password-wrap{position:relative;}
.my-login-password-wrap input{padding-right:48px !important;}
.my-login-wrap .my-login-password-toggle{position:absolute;top:50%;right:9px;transform:translateY(-50%);width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:0 !important;border-radius:9px;background:transparent !important;color:#5C6079 !important;cursor:pointer;box-shadow:none !important;transition:background .15s,color .15s;}
.my-login-wrap .my-login-password-toggle i{color:inherit !important;}
.my-login-wrap .my-login-password-toggle:hover{background:rgba(0,229,255,.14) !important;color:#00E5FF !important;}
.my-login-caps-hint{display:none;margin-top:6px;font-size:12px;color:#FFC857;}
.my-login-caps-hint.is-visible{display:block;}
.my-login-options-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 22px;font-size:13.5px;}
.my-login-remember{display:flex;align-items:center;gap:8px;color:#B7BAD1 !important;cursor:pointer;font-weight:500;margin:0 !important;}
.my-login-remember input{accent-color:#00E5FF;width:15px !important;height:15px !important;margin:0 !important;cursor:pointer;}
.my-login-options-row a{color:#00E5FF !important;text-decoration:none;font-weight:600;}
.my-login-options-row a:hover{color:#FF2EC4 !important;}
.my-login-form-submit{margin-top:4px !important;}
.my-login-wrap .my-login-submit-btn,.my-login-wrap button[type=submit]{
  position:relative;width:100% !important;padding:15px 20px !important;font-size:15.5px;font-weight:700;letter-spacing:.3px;
  color:#0A0A12 !important;border:0 !important;border-radius:12px !important;cursor:pointer;overflow:hidden;
  background:linear-gradient(100deg,#00E5FF 0%,#7CF2FF 50%,#FF2EC4 100%) !important;
  box-shadow:0 1px 0 rgba(255,255,255,.4) inset,0 0 30px -8px rgba(0,229,255,.6),0 0 30px -10px rgba(255,46,196,.5) !important;
  transition:transform .15s,box-shadow .2s,filter .2s;
}
.my-login-wrap .my-login-submit-btn:hover{filter:brightness(1.08);box-shadow:0 1px 0 rgba(255,255,255,.4) inset,0 0 40px -6px rgba(0,229,255,.75),0 0 40px -8px rgba(255,46,196,.6) !important;}
.my-login-wrap .my-login-submit-btn:active{transform:translateY(1px);}
.my-login-wrap .my-login-submit-btn:disabled{opacity:.5;cursor:not-allowed;filter:none;}
.my-login-msg:empty{display:none;}
.my-login-wrap .my-login-msg{margin:22px 0 0 !important;padding:12px 14px !important;border-radius:10px;font-size:13.5px;background:rgba(255,46,196,.1);border:1px solid rgba(255,46,196,.35);color:#FF7EDC;}
.my-login-wrap .my-login-msg.success{background:rgba(0,229,255,.08);border-color:rgba(0,229,255,.35);color:#7CF2FF;}
.my-login-otp-form{display:none;}
.my-login-otp-form.is-visible{display:block;padding-top:24px !important;}
.my-login-otp-form input[name=token]{text-align:center;font-size:26px !important;font-weight:700;letter-spacing:10px;padding:15px 10px !important;}
.my-login-wrap .my-login-otp-resend{display:block;width:100%;margin-top:14px;padding:11px;font-size:13.5px;font-weight:600;color:#7CF2FF !important;background:rgba(0,229,255,.08) !important;border:1px solid rgba(0,229,255,.25) !important;border-radius:10px !important;cursor:pointer;box-shadow:none !important;transition:background .15s;}
.my-login-wrap .my-login-otp-resend:hover{background:rgba(0,229,255,.16) !important;}
.my-login-wrap .my-login-extra-links{margin-top:26px !important;padding-top:20px !important;border-top:1px solid rgba(0,229,255,.16);text-align:center;font-size:13.5px;color:#8B8FA8 !important;}
.my-login-wrap .my-login-extra-links a{color:#fff !important;text-decoration:none;font-weight:700;}
.my-login-wrap .my-login-extra-links a:hover{color:#00E5FF !important;}
.my-login-wrap .my-login-extra-links i{opacity:.75;}
@media (max-width:480px){
  .my-login-wrap{margin:24px 16px !important;border-radius:16px;}
  .my-login-greeting{padding:28px 24px 20px;}
  .my-login-greeting-title{font-size:24px;}
  .my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:24px !important;padding-right:24px !important;}
}
CSS,
        ];
    }

    private static function minimal_mono(): array {
        return [
            'name'         => __('Minimal Mono', 'my-login-form'),
            'description'  => __('Crisp white card, black accents, generous whitespace.', 'my-login-form'),
            'icon'         => '◻️',
            'swatch'       => 'linear-gradient(135deg,#FFFFFF 0%,#F2F2F2 100%)',
            'accent'       => '#111111',
            'border_color' => '#E5E5E5',
            'preview'      => [
                'title'        => '#111111',
                'input_bg'     => '#FFFFFF',
                'input_border' => '#E5E5E5',
                'button'       => '#111111',
            ],
            'custom_css'   => <<<'CSS'
/* ===== Minimal Mono ===== */
.my-login-wrap,.my-login-wrap-1,.my-login-wrap-2,.my-login-wrap-3,.my-login-wrap-4,.my-login-wrap-5,.my-login-wrap-6,#my-login-wrap-1{
  width:100% !important;max-width:400px !important;min-width:280px !important;margin:48px auto !important;
  padding:0 0 30px !important;box-sizing:border-box;position:relative;overflow:hidden;border-radius:16px;
  background:#FFFFFF;border:1px solid #E5E5E5;
  box-shadow:0 1px 2px rgba(0,0,0,.04),0 20px 44px -24px rgba(0,0,0,.18);
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
}
.my-login-wrap .my-login-main-container,.my-login-wrap form,.my-login-wrap fieldset{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 !important;border-radius:0 !important;max-width:none !important;width:auto !important;}
.my-login-greeting{padding:40px 34px 22px;text-align:center;}
.my-login-greeting-title{margin:0 0 8px !important;font-size:26px;font-weight:700;letter-spacing:-.3px;color:#111111 !important;}
.my-login-greeting-subtitle{margin:0 !important;font-size:13.5px;color:#767676 !important;}
.my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:34px !important;padding-right:34px !important;}
.my-login-wrap .my-login-main-container{padding-top:8px !important;}
.my-login-form-field{margin-bottom:18px !important;}
.my-login-wrap .my-login-label{display:block;margin-bottom:7px;font-size:12.5px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;color:#3A3A3A !important;}
.my-login-req{color:#C62828 !important;margin-left:3px;}
.my-login-wrap input:not([type=checkbox]):not([type=radio]){
  width:100% !important;box-sizing:border-box;padding:13px 14px !important;font-size:15px;line-height:1.4;
  color:#111111 !important;background:#FFFFFF !important;border:1.5px solid #E5E5E5 !important;
  border-radius:8px !important;outline:none;box-shadow:none !important;
  transition:border-color .15s,box-shadow .15s;
}
.my-login-wrap input::placeholder{color:#B3B3B3 !important;opacity:1;}
.my-login-wrap input:focus{background:#FFFFFF !important;border-color:#111111 !important;box-shadow:0 0 0 3px rgba(17,17,17,.08) !important;}
.my-login-wrap input:-webkit-autofill,.my-login-wrap input:-webkit-autofill:hover,.my-login-wrap input:-webkit-autofill:focus,.my-login-wrap input:-webkit-autofill:active{
  -webkit-text-fill-color:#111111;-webkit-box-shadow:0 0 0 1000px #FFFFFF inset;box-shadow:0 0 0 1000px #FFFFFF inset;caret-color:#111111;transition:background-color 5000s ease-in-out 0s;
}
.my-login-password-wrap{position:relative;}
.my-login-password-wrap input{padding-right:44px !important;}
.my-login-wrap .my-login-password-toggle{position:absolute;top:50%;right:8px;transform:translateY(-50%);width:30px;height:30px;display:flex;align-items:center;justify-content:center;border:0 !important;border-radius:6px;background:transparent !important;color:#B3B3B3 !important;cursor:pointer;box-shadow:none !important;transition:background .15s,color .15s;}
.my-login-wrap .my-login-password-toggle i{color:inherit !important;}
.my-login-wrap .my-login-password-toggle:hover{background:#F2F2F2 !important;color:#111111 !important;}
.my-login-caps-hint{display:none;margin-top:6px;font-size:12px;color:#B35B00;}
.my-login-caps-hint.is-visible{display:block;}
.my-login-options-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:2px 0 24px;font-size:13px;}
.my-login-remember{display:flex;align-items:center;gap:8px;color:#3A3A3A !important;cursor:pointer;font-weight:500;margin:0 !important;}
.my-login-remember input{accent-color:#111111;width:15px !important;height:15px !important;margin:0 !important;cursor:pointer;}
.my-login-options-row a{color:#111111 !important;text-decoration:underline;font-weight:500;}
.my-login-options-row a:hover{color:#555555 !important;}
.my-login-form-submit{margin-top:4px !important;}
.my-login-wrap .my-login-submit-btn,.my-login-wrap button[type=submit]{
  position:relative;width:100% !important;padding:14px 20px !important;font-size:14.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  color:#fff !important;border:0 !important;border-radius:8px !important;cursor:pointer;overflow:hidden;
  background:#111111 !important;box-shadow:none !important;transition:transform .12s,background .15s;
}
.my-login-wrap .my-login-submit-btn:hover{background:#2B2B2B !important;}
.my-login-wrap .my-login-submit-btn:active{transform:scale(.99);}
.my-login-wrap .my-login-submit-btn:disabled{opacity:.4;cursor:not-allowed;}
.my-login-msg:empty{display:none;}
.my-login-wrap .my-login-msg{margin:22px 0 0 !important;padding:11px 14px !important;border-radius:8px;font-size:13px;background:#FBEAEA;border:1px solid #F0C6C6;color:#B3261E;}
.my-login-wrap .my-login-msg.success{background:#EDF7ED;border-color:#C8E6C9;color:#1E7A2E;}
.my-login-otp-form{display:none;}
.my-login-otp-form.is-visible{display:block;padding-top:20px !important;}
.my-login-otp-form input[name=token]{text-align:center;font-size:24px !important;font-weight:700;letter-spacing:9px;padding:13px 10px !important;}
.my-login-wrap .my-login-otp-resend{display:block;width:100%;margin-top:14px;padding:10px;font-size:13px;font-weight:600;color:#111111 !important;background:#F5F5F5 !important;border:1px solid #E5E5E5 !important;border-radius:8px !important;cursor:pointer;box-shadow:none !important;transition:background .15s;}
.my-login-wrap .my-login-otp-resend:hover{background:#EDEDED !important;}
.my-login-wrap .my-login-extra-links{margin-top:26px !important;padding-top:20px !important;border-top:1px solid #E5E5E5;text-align:center;font-size:13px;color:#767676 !important;}
.my-login-wrap .my-login-extra-links a{color:#111111 !important;text-decoration:underline;font-weight:600;}
.my-login-wrap .my-login-extra-links a:hover{color:#555555 !important;}
.my-login-wrap .my-login-extra-links i{opacity:.7;}
@media (max-width:480px){
  .my-login-wrap{margin:24px 16px !important;border-radius:14px;}
  .my-login-greeting{padding:32px 24px 18px;}
  .my-login-greeting-title{font-size:22px;}
  .my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:24px !important;padding-right:24px !important;}
}
CSS,
        ];
    }

    private static function ocean_breeze(): array {
        return [
            'name'         => __('Ocean Breeze', 'my-login-form'),
            'description'  => __('Teal-to-blue glass card with a soft wave glow.', 'my-login-form'),
            'icon'         => '🌊',
            'swatch'       => 'linear-gradient(135deg,#0EA5A5 0%,#0284C7 100%)',
            'accent'       => '#0EA5A5',
            'border_color' => '#BFEFF0',
            'preview'      => [
                'title'        => 'rgba(255,255,255,.95)',
                'input_bg'     => 'rgba(255,255,255,.18)',
                'input_border' => 'rgba(255,255,255,.4)',
                'button'       => 'linear-gradient(100deg,#ffffff 0%,#E9FBFB 100%)',
            ],
            'custom_css'   => <<<'CSS'
/* ===== Ocean Breeze ===== */
.my-login-wrap,.my-login-wrap-1,.my-login-wrap-2,.my-login-wrap-3,.my-login-wrap-4,.my-login-wrap-5,.my-login-wrap-6,#my-login-wrap-1{
  width:100% !important;max-width:400px !important;min-width:280px !important;margin:48px auto !important;
  padding:0 0 26px !important;box-sizing:border-box;position:relative;overflow:hidden;border-radius:22px;
  background:linear-gradient(160deg,#0EA5A5 0%,#0EA5C7 50%,#0284C7 100%);
  border:1px solid rgba(255,255,255,.28);
  box-shadow:0 1px 0 rgba(255,255,255,.25) inset,0 30px 60px -18px rgba(2,40,55,.5),0 0 60px -20px rgba(14,165,165,.5);
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
}
.my-login-wrap::after{content:"";position:absolute;bottom:-70px;right:-50px;width:230px;height:230px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.2),transparent 70%);pointer-events:none;}
.my-login-wrap::before{content:"";position:absolute;top:-60px;left:-40px;width:170px;height:170px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.16),transparent 70%);pointer-events:none;}
.my-login-wrap>*{position:relative;z-index:1;}
.my-login-wrap .my-login-main-container,.my-login-wrap form,.my-login-wrap fieldset{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 !important;border-radius:0 !important;max-width:none !important;width:auto !important;}
.my-login-greeting{padding:34px 32px 24px;text-align:center;}
.my-login-greeting-title{margin:0 0 6px !important;font-size:29px;font-weight:800;letter-spacing:-.5px;color:#fff !important;text-shadow:0 2px 14px rgba(2,40,55,.4);}
.my-login-greeting-subtitle{margin:0 !important;font-size:14px;color:rgba(255,255,255,.85) !important;}
.my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:32px !important;padding-right:32px !important;}
.my-login-wrap .my-login-main-container{padding-top:24px !important;}
.my-login-form-field{margin-bottom:16px !important;}
.my-login-wrap .my-login-label{display:block;margin-bottom:7px;font-size:13px;font-weight:600;color:rgba(255,255,255,.92) !important;}
.my-login-req{color:#FFE082 !important;margin-left:3px;}
.my-login-wrap input:not([type=checkbox]):not([type=radio]){
  width:100% !important;box-sizing:border-box;padding:14px 17px !important;font-size:15px;line-height:1.4;
  color:#fff !important;background:rgba(255,255,255,.14) !important;border:1px solid rgba(255,255,255,.32) !important;
  border-radius:13px !important;outline:none;box-shadow:0 1px 0 rgba(255,255,255,.12) inset !important;
  transition:border-color .2s,box-shadow .2s,background .2s;
}
.my-login-wrap input::placeholder{color:rgba(255,255,255,.68) !important;opacity:1;}
.my-login-wrap input:focus{background:rgba(255,255,255,.24) !important;border-color:#fff !important;box-shadow:0 0 0 4px rgba(255,255,255,.2) !important;}
.my-login-wrap input:-webkit-autofill,.my-login-wrap input:-webkit-autofill:hover,.my-login-wrap input:-webkit-autofill:focus,.my-login-wrap input:-webkit-autofill:active{
  -webkit-text-fill-color:#fff;-webkit-box-shadow:0 0 0 1000px #0B7B95 inset;box-shadow:0 0 0 1000px #0B7B95 inset;caret-color:#fff;transition:background-color 5000s ease-in-out 0s;
}
.my-login-password-wrap{position:relative;}
.my-login-password-wrap input{padding-right:48px !important;}
.my-login-wrap .my-login-password-toggle{position:absolute;top:50%;right:9px;transform:translateY(-50%);width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:0 !important;border-radius:9px;background:transparent !important;color:rgba(255,255,255,.75) !important;cursor:pointer;box-shadow:none !important;transition:background .15s,color .15s;}
.my-login-wrap .my-login-password-toggle i{color:inherit !important;}
.my-login-wrap .my-login-password-toggle:hover{background:rgba(255,255,255,.2) !important;color:#fff !important;}
.my-login-caps-hint{display:none;margin-top:6px;font-size:12px;color:#FFE082;}
.my-login-caps-hint.is-visible{display:block;}
.my-login-options-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 22px;font-size:13.5px;}
.my-login-remember{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.9) !important;cursor:pointer;font-weight:500;margin:0 !important;}
.my-login-remember input{accent-color:#fff;width:15px !important;height:15px !important;margin:0 !important;cursor:pointer;}
.my-login-options-row a{color:#fff !important;text-decoration:none;font-weight:600;}
.my-login-options-row a:hover{color:#FFE082 !important;}
.my-login-form-submit{margin-top:4px !important;}
.my-login-wrap .my-login-submit-btn,.my-login-wrap button[type=submit]{
  position:relative;width:100% !important;padding:15px 20px !important;font-size:15.5px;font-weight:700;letter-spacing:.3px;
  color:#04586B !important;border:0 !important;border-radius:13px !important;cursor:pointer;overflow:hidden;
  background:linear-gradient(100deg,#ffffff 0%,#E9FBFB 100%) !important;
  box-shadow:0 1px 0 rgba(255,255,255,.6) inset,0 12px 26px -10px rgba(2,40,55,.5) !important;
  transition:transform .15s,box-shadow .2s,filter .2s;
}
.my-login-wrap .my-login-submit-btn:hover{filter:brightness(1.03);box-shadow:0 1px 0 rgba(255,255,255,.6) inset,0 16px 32px -10px rgba(2,40,55,.6) !important;}
.my-login-wrap .my-login-submit-btn:active{transform:translateY(1px);}
.my-login-wrap .my-login-submit-btn:disabled{opacity:.55;cursor:not-allowed;filter:none;}
.my-login-msg:empty{display:none;}
.my-login-wrap .my-login-msg{margin:22px 0 0 !important;padding:12px 14px !important;border-radius:12px;font-size:13.5px;background:rgba(0,0,0,.14);border:1px solid rgba(255,255,255,.3);color:#fff;}
.my-login-wrap .my-login-msg.success{background:rgba(0,0,0,.14);border-color:rgba(255,255,255,.4);color:#fff;}
.my-login-otp-form{display:none;}
.my-login-otp-form.is-visible{display:block;padding-top:24px !important;}
.my-login-otp-form input[name=token]{text-align:center;font-size:26px !important;font-weight:700;letter-spacing:10px;padding:15px 10px !important;}
.my-login-wrap .my-login-otp-resend{display:block;width:100%;margin-top:14px;padding:11px;font-size:13.5px;font-weight:600;color:#fff !important;background:rgba(255,255,255,.14) !important;border:1px solid rgba(255,255,255,.3) !important;border-radius:11px !important;cursor:pointer;box-shadow:none !important;transition:background .15s;}
.my-login-wrap .my-login-otp-resend:hover{background:rgba(255,255,255,.26) !important;}
.my-login-wrap .my-login-extra-links{margin-top:26px !important;padding-top:20px !important;border-top:1px solid rgba(255,255,255,.25);text-align:center;font-size:13.5px;color:rgba(255,255,255,.85) !important;}
.my-login-wrap .my-login-extra-links a{color:#fff !important;text-decoration:none;font-weight:700;}
.my-login-wrap .my-login-extra-links a:hover{color:#FFE082 !important;}
.my-login-wrap .my-login-extra-links i{opacity:.85;}
@media (max-width:480px){
  .my-login-wrap{margin:24px 16px !important;border-radius:18px;}
  .my-login-greeting{padding:28px 24px 20px;}
  .my-login-greeting-title{font-size:24px;}
  .my-login-wrap .my-login-main-container,.my-login-wrap .my-login-otp-form,.my-login-wrap .my-login-extra-links,.my-login-wrap .my-login-msg{padding-left:24px !important;padding-right:24px !important;}
}
CSS,
        ];
    }
}
