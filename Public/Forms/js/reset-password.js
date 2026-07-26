// Live "passwords match" feedback between New Password and Confirm New Password.
// Styling lives in the generated CSS (.my-login-field-match / .my-login-field-mismatch).
document.addEventListener('DOMContentLoaded', function () {
    var pwd = document.querySelector('.my-login-wrap input[name="password"]');
    var confirmField = document.querySelector('.my-login-wrap input[name="confirm_password"]');
    if (!pwd || !confirmField) return;
    var wrap = confirmField.closest('.my-login-form-field');
    function check() {
        wrap.classList.remove('my-login-field-match', 'my-login-field-mismatch');
        if (!confirmField.value) return;
        wrap.classList.add(confirmField.value === pwd.value ? 'my-login-field-match' : 'my-login-field-mismatch');
    }
    pwd.addEventListener('input', check);
    confirmField.addEventListener('input', check);
});