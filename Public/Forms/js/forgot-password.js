// Auto-focus the email field so visitors can start typing immediately.
document.addEventListener('DOMContentLoaded', function () {
    var email = document.querySelector('.my-login-wrap input[type="email"]');
    if (email) email.focus();
});