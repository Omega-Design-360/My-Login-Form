/**
 * Replaces WooCommerce's plain-text "must be logged in" prompt — rendered
 * client-side by the woocommerce/checkout block (wc-block-must-login-prompt)
 * — with this plugin's own friendly login/register notice. The block's
 * markup only exists in a compiled JS bundle with no PHP template or filter
 * to override it, so a MutationObserver catches it once React mounts it.
 */
(function () {
    var settings = window.myLoginCheckoutAuth || {};

    function enhance(promptEl) {
        if (!promptEl || promptEl.dataset.myLoginEnhanced) {
            return;
        }
        promptEl.dataset.myLoginEnhanced = 'true';

        var existingLink = promptEl.querySelector('a');
        var loginHref = settings.loginUrl || (existingLink && existingLink.getAttribute('href')) || '#';

        var actions =
            '<a href="' + loginHref + '" class="my-login-checkout-notice__button my-login-checkout-notice__button--primary">' +
                settings.loginLabel +
            '</a>';

        if (settings.showRegister && settings.registerUrl) {
            actions +=
                '<a href="' + settings.registerUrl + '" class="my-login-checkout-notice__button my-login-checkout-notice__button--secondary">' +
                    settings.registerLabel +
                '</a>';
        }

        promptEl.className = 'my-login-checkout-notice';
        promptEl.innerHTML =
            '<div class="my-login-checkout-notice__icon" aria-hidden="true">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>' +
            '</div>' +
            '<p class="my-login-checkout-notice__title">' + settings.title + '</p>' +
            '<p class="my-login-checkout-notice__message">' + settings.message + '</p>' +
            '<div class="my-login-checkout-notice__actions">' + actions + '</div>';
    }

    function scan() {
        var el = document.querySelector('.wc-block-must-login-prompt');
        if (el) {
            enhance(el);
        }
    }

    function start() {
        scan();
        new MutationObserver(scan).observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
