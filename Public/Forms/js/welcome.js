// Subtle fade/slide-in for the welcome card.
// Styling lives in the generated CSS (.my-login-fade-in) — this only toggles a class.
document.addEventListener('DOMContentLoaded', function () {
    var card = document.querySelector('.my-login-wrap .my-login-main-container');
    if (!card) return;
    card.classList.add('my-login-fade-in');
    requestAnimationFrame(function () {
        requestAnimationFrame(function () { card.classList.add('is-visible'); });
    });
});