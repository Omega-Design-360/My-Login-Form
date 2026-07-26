// Show a "Caps Lock is on" hint while typing the password.
// Styling lives in the generated CSS (.my-login-caps-hint) — this only toggles a class.
document.addEventListener('DOMContentLoaded', function () {
    var pwd = document.querySelector('.my-login-wrap input[type="password"]');
    if (!pwd) return;
    var hint = document.createElement('div');
    hint.className = 'my-login-caps-hint';
    hint.textContent = 'Caps Lock is on';
    pwd.insertAdjacentElement('afterend', hint);
    pwd.addEventListener('keyup', function (e) {
        var caps = e.getModifierState && e.getModifierState('CapsLock');
        hint.classList.toggle('is-visible', !!caps);
    });
});