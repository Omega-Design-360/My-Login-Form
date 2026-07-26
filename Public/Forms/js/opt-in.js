// Keep the Subscribe button disabled until the consent checkbox is checked.
// Dimmed/not-allowed look comes from the generated CSS (.my-login-submit-btn:disabled).
document.addEventListener('DOMContentLoaded', function () {
    var consent = document.querySelector('.my-login-wrap input[name="consent"]');
    var button  = document.querySelector('.my-login-wrap .my-login-submit-btn');
    if (!consent || !button) return;
    function sync() { button.disabled = !consent.checked; }
    consent.addEventListener('change', sync);
    sync();
});