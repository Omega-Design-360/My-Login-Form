(function () {
    'use strict';

    function updateRecipientControls() {
        var sourceSelect = document.getElementById('mlf-mail-recipient-source');
        if (sourceSelect) {
            document.querySelectorAll('[data-mlf-recipient-source]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-mlf-recipient-source') !== sourceSelect.value;
            });
        }

        var templateSelect = document.getElementById('mlf-mail-template');
        if (templateSelect) {
            var useTemplate = templateSelect.value !== '';
            var recipientGrid = document.querySelector('.mlf-mailing-recipient-grid');
            if (recipientGrid) recipientGrid.classList.toggle('is-template-mode', useTemplate);
            document.querySelectorAll('[data-mlf-template-mode]').forEach(function (panel) {
                var isTemplatePanel = panel.getAttribute('data-mlf-template-mode') === 'template';
                panel.hidden = isTemplatePanel !== useTemplate;
                panel.querySelectorAll('input, textarea, select').forEach(function (field) {
                    if (field.id === 'mlf-mail-recipient-name') {
                        field.required = false;
                    } else if (field.id === 'mlf-mail-subject' || field.id === 'mlf-mail-message') {
                        field.required = !useTemplate;
                    }
                });
            });
            var messageField = document.getElementById('mlf-mail-message');
            var messageVariable = document.getElementById('mlf-mail-message-variable');
            if (messageField) messageField.required = !useTemplate || !!(messageVariable && messageVariable.value.trim());
            document.querySelectorAll('[data-mlf-message-hint]').forEach(function (hint) {
                hint.hidden = hint.getAttribute('data-mlf-message-hint') !== (useTemplate ? 'template' : 'plain');
            });
        }

        var limitSelect = document.getElementById('mlf-mail-recipient-limit');
        var customPanel = document.querySelector('.mlf-mailing-custom-limit');
        var customInput = document.getElementById('mlf-mail-recipient-limit-custom');
        if (limitSelect && customPanel && customInput) {
            var isCustom = limitSelect.value === 'custom';
            customPanel.hidden = !isCustom;
            customInput.required = isCustom;
        }
    }

    document.addEventListener('change', function (event) {
        if (event.target.id === 'mlf-mail-recipient-source' || event.target.id === 'mlf-mail-recipient-limit' || event.target.id === 'mlf-mail-template' || event.target.id === 'mlf-mail-message-variable') {
            updateRecipientControls();
        }
    });
    updateRecipientControls();
    document.addEventListener('change', function (event) {
        if (event.target.id !== 'mlf-mailing-list-file' || !event.target.files || !event.target.files[0]) return;
        var help = event.target.parentNode.querySelector('p');
        if (help) help.textContent = event.target.files[0].name + ' selected. Its email addresses will be added when you send.';
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('#mlf-mailing-test-supabase');
        if (!button || !window.myLoginFormMailing) return;

        var status = document.getElementById('mlf-mailing-supabase-test-status');
        var url = document.getElementById('mlf-supabase-url');
        var anonKey = document.getElementById('mlf-supabase-anon');
        if (!status || !url || !anonKey) return;

        status.className = 'mlf-mailing-supabase-test-status is-testing';
        status.textContent = 'Testing connection…';
        button.disabled = true;

        var body = new URLSearchParams({
            action: 'my_login_supabase_test',
            nonce: window.myLoginFormMailing.supabaseTestNonce,
            url: url.value,
            anon_key: anonKey.value
        });
        fetch(window.myLoginFormMailing.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function (response) {
            return response.json();
        }).then(function (response) {
            var success = !!(response && response.success);
            status.className = 'mlf-mailing-supabase-test-status ' + (success ? 'is-success' : 'is-error');
            status.textContent = response && response.data && response.data.message
                ? response.data.message
                : (success ? 'Supabase connection succeeded.' : 'Could not connect. Check the project URL and anon key.');
        }).catch(function () {
            status.className = 'mlf-mailing-supabase-test-status is-error';
            status.textContent = 'Could not test the connection. Please try again.';
        }).finally(function () {
            button.disabled = false;
        });
    });
}());
