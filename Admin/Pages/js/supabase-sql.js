(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-mlf-copy-sql]');
        if (!button) return;

        var textarea = document.getElementById(button.getAttribute('data-mlf-copy-sql'));
        if (!textarea) return;

        var originalText = button.textContent;
        var fallbackCopy = function () {
            textarea.focus();
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);
            try { return document.execCommand('copy'); } catch (error) { return false; }
        };
        var copyPromise = navigator.clipboard && window.isSecureContext
            ? navigator.clipboard.writeText(textarea.value).then(function () { return true; }).catch(fallbackCopy)
            : Promise.resolve(fallbackCopy());

        copyPromise.then(function (success) {
            button.textContent = success ? 'Copied!' : 'Select and copy';
            window.setTimeout(function () { button.textContent = originalText; }, 1800);
        });
    });
}());
