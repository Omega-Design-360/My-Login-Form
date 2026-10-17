(function () {
    'use strict';

    const config = window.mlfPasskeys || {};
    const strings = config.i18n || {};
    const ajaxUrl = config.ajaxUrl || (window.my_login_form_ajax && window.my_login_form_ajax.ajax_url);
    const nonce = config.nonce || (window.my_login_form_ajax && window.my_login_form_ajax.nonce);

    function decodeBase64Url(value) {
        const padded = value.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - value.length % 4) % 4);
        const binary = atob(padded);
        return Uint8Array.from(binary, char => char.charCodeAt(0)).buffer;
    }

    function encodeBase64Url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        bytes.forEach(byte => { binary += String.fromCharCode(byte); });
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    function decodeCreationOptions(options) {
        options.challenge = decodeBase64Url(options.challenge);
        options.user.id = decodeBase64Url(options.user.id);
        (options.excludeCredentials || []).forEach(item => { item.id = decodeBase64Url(item.id); });
        return options;
    }

    function decodeRequestOptions(options) {
        options.challenge = decodeBase64Url(options.challenge);
        (options.allowCredentials || []).forEach(item => { item.id = decodeBase64Url(item.id); });
        return options;
    }

    function credentialPayload(credential, kind, token) {
        const response = credential.response;
        const payload = {
            token: token,
            credentialId: encodeBase64Url(credential.rawId),
            clientDataJSON: encodeBase64Url(response.clientDataJSON)
        };
        if (kind === 'create') {
            payload.attestationObject = encodeBase64Url(response.attestationObject);
        } else {
            payload.authenticatorData = encodeBase64Url(response.authenticatorData);
            payload.signature = encodeBase64Url(response.signature);
            payload.userHandle = response.userHandle ? encodeBase64Url(response.userHandle) : '';
        }
        return payload;
    }

    async function postForm(action, data) {
        const body = new URLSearchParams(Object.assign({ action: action, my_login_form_nonce: nonce }, data || {}));
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        });
        return response.json();
    }

    async function postJson(action, data) {
        const body = new URLSearchParams(Object.assign({ action: action, my_login_form_nonce: nonce }, data || {}));
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        });
        return response.json();
    }

    function messageFor(button) {
        const wrapper = button.closest('.my-login-wrap, .my-login-form-container');
        if (!wrapper) return button.closest('.mlf-passkey-manager')?.querySelector('.mlf-passkey-status');
        return wrapper.querySelector('.my-login-msg, .message') || button.parentElement;
    }

    function showMessage(target, message, isError) {
        if (!target) return;
        target.textContent = message || '';
        target.classList.toggle('error', !!isError);
        target.classList.toggle('success', !isError);
        target.style.display = message ? 'block' : '';
    }

    function finishLogin(button, result) {
        const wrapper = button.closest('.my-login-wrap, .my-login-form-container');
        if (result && result.data && result.data.otp_required) {
            let otpForm = wrapper && wrapper.querySelector('.my-login-otp-form');
            const loginForm = wrapper && wrapper.querySelector('.my-login-form');
            if (wrapper && loginForm && !otpForm) {
                otpForm = document.createElement('form');
                otpForm.className = 'my-login-otp-form mlf-passkey-otp-fallback';
                otpForm.innerHTML = '<input type="hidden" name="email"><input type="hidden" name="context" value="login"><label class="my-login-label">Verification Code</label><input type="text" name="token" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required><button type="submit" class="my-login-submit-btn">Verify Code</button><button type="button" class="mlf-passkey-otp-resend">Resend code</button>';
                loginForm.insertAdjacentElement('afterend', otpForm);
            }
            const emailField = otpForm && otpForm.querySelector('[name="email"]');
            if (otpForm && loginForm && emailField) {
                emailField.value = result.data.email || '';
                loginForm.style.display = 'none';
                otpForm.style.display = 'block';
                showMessage(messageFor(button), result.data.message, false);
                return;
            }
        }
        if (result && result.success && result.data && result.data.redirect) {
            showMessage(messageFor(button), result.data.message || strings.login, false);
            window.location.assign(result.data.redirect);
            return;
        }
        const message = result && result.data && (result.data.message || result.data);
        showMessage(messageFor(button), typeof message === 'string' ? message : strings.failed, true);
    }

    async function signIn(button) {
        if (!window.PublicKeyCredential || !navigator.credentials) {
            showMessage(messageFor(button), strings.unsupported, true);
            return;
        }
        const wrapper = button.closest('.my-login-wrap, .my-login-form-container');
        const identifierField = wrapper && wrapper.querySelector('[name="username"], [name="user_login"], [name="email"]');
        const identifier = identifierField ? identifierField.value.trim() : '';
        if (!identifier) {
            showMessage(messageFor(button), strings.identifier, true);
            if (identifierField) identifierField.focus();
            return;
        }

        button.disabled = true;
        showMessage(messageFor(button), strings.login, false);
        try {
            const optionsResult = await postForm('mlf_passkey_login_options', { identifier: identifier });
            if (!optionsResult || !optionsResult.success) throw new Error((optionsResult && optionsResult.data && optionsResult.data.message) || strings.failed);
            const credential = await navigator.credentials.get({ publicKey: decodeRequestOptions(optionsResult.data.options) });
            const assertion = credentialPayload(credential, 'login', optionsResult.data.token);
            const loginForm = wrapper && wrapper.querySelector('.my-login-form');
            const redirect = loginForm && loginForm.querySelector('[name="redirect_to"]');
            assertion.redirect = redirect ? redirect.value : '';
            assertion.remember = !!(loginForm && loginForm.querySelector('[name="remember_me"]:checked'));
            finishLogin(button, await postJson('mlf_passkey_login_verify', assertion));
        } catch (error) {
            showMessage(messageFor(button), error && error.message ? error.message : strings.failed, true);
        } finally {
            button.disabled = false;
        }
    }

    async function loadCredentialList(manager) {
        const list = manager.querySelector('.mlf-passkey-list');
        if (!list) return;
        const result = await postForm('mlf_passkey_list');
        if (!result || !result.success) {
            showMessage(manager.querySelector('.mlf-passkey-status'), result?.data?.message || strings.failed, true);
            return;
        }
        list.replaceChildren();
        if (!result.data.credentials.length) {
            const empty = document.createElement('li');
            empty.textContent = strings.empty;
            list.appendChild(empty);
            return;
        }
        result.data.credentials.forEach(item => {
            const row = document.createElement('li');
            const name = document.createElement('span');
            name.textContent = item.name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'mlf-passkey-remove';
            remove.textContent = strings.remove;
            remove.dataset.key = item.key;
            row.append(name, remove);
            list.appendChild(row);
        });
    }

    async function addPasskey(button) {
        if (!window.PublicKeyCredential || !navigator.credentials) {
            showMessage(messageFor(button), strings.unsupported, true);
            return;
        }
        const manager = button.closest('.mlf-passkey-manager');
        const status = manager.querySelector('.mlf-passkey-status');
        button.disabled = true;
        try {
            const optionsResult = await postForm('mlf_passkey_create_options');
            if (!optionsResult || !optionsResult.success) throw new Error(optionsResult?.data?.message || strings.failed);
            const credential = await navigator.credentials.create({ publicKey: decodeCreationOptions(optionsResult.data.options) });
            const name = manager.querySelector('.mlf-passkey-name')?.value.trim() || '';
            const payload = credentialPayload(credential, 'create', optionsResult.data.token);
            payload.name = name;
            const saved = await postJson('mlf_passkey_create_verify', payload);
            if (!saved || !saved.success) throw new Error(saved?.data?.message || strings.failed);
            showMessage(status, strings.added, false);
            manager.querySelector('.mlf-passkey-name').value = '';
            await loadCredentialList(manager);
        } catch (error) {
            showMessage(status, error?.message || strings.failed, true);
        } finally {
            button.disabled = false;
        }
    }

    document.addEventListener('click', async function (event) {
        const login = event.target.closest('.mlf-passkey-login');
        if (login) { event.preventDefault(); await signIn(login); return; }
        const add = event.target.closest('.mlf-passkey-add');
        if (add) { event.preventDefault(); await addPasskey(add); return; }
        const remove = event.target.closest('.mlf-passkey-remove');
        if (remove) {
            event.preventDefault();
            const manager = remove.closest('.mlf-passkey-manager');
            const result = await postJson('mlf_passkey_delete', { key: remove.dataset.key });
            showMessage(manager.querySelector('.mlf-passkey-status'), result?.data?.message || strings.removed, !result?.success);
            if (result?.success) await loadCredentialList(manager);
            return;
        }
        const resend = event.target.closest('.mlf-passkey-otp-resend');
        if (resend) {
            const form = resend.closest('form');
            resend.disabled = true;
            const result = await postForm('my_login_form_resend_otp', {
                email: form.querySelector('[name="email"]').value,
                context: 'login'
            });
            const wrapper = resend.closest('.my-login-wrap, .my-login-form-container');
            showMessage(wrapper && wrapper.querySelector('.my-login-msg, .message'), result?.data?.message || 'If this email is eligible, a code has been sent.', !result?.success);
            window.setTimeout(() => { resend.disabled = false; }, 15000);
        }
    });

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('.mlf-passkey-otp-fallback');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        const wrapper = form.closest('.my-login-wrap, .my-login-form-container');
        try {
            const result = await postForm('my_login_form_verify_otp', {
                email: form.querySelector('[name="email"]').value,
                context: 'login',
                token: form.querySelector('[name="token"]').value,
                redirect_to: wrapper?.querySelector('[name="redirect_to"]')?.value || ''
            });
            if (result?.success && result.data?.redirect) {
                showMessage(wrapper?.querySelector('.my-login-msg, .message'), result.data.message, false);
                window.location.assign(result.data.redirect);
            } else {
                showMessage(wrapper?.querySelector('.my-login-msg, .message'), result?.data?.message || strings.failed, true);
            }
        } catch (error) {
            showMessage(wrapper?.querySelector('.my-login-msg, .message'), strings.failed, true);
        } finally {
            button.disabled = false;
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.mlf-passkey-manager').forEach(loadCredentialList);
    });
})();
