(function () {
    'use strict';

    var wrapperSelector = '.my-login-wrap, .my-login-form-container, .my-login-form-wrapper';
    var eyeSvg = '<svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    var eyeOffSvg = '<svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 3 18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a14 14 0 0 1-3 3.8"></path><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 2-.2 2.9-.5"></path></svg>';

    function addToggle(input) {
        var field = input.closest('.my-login-password-wrap');
        if (!field) {
            field = document.createElement('div');
            field.className = 'my-login-password-wrap';
            field.style.position = 'relative';
            input.parentNode.insertBefore(field, input);
            field.appendChild(input);
        }
        if (field.querySelector('.my-login-password-toggle')) return;

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'my-login-password-toggle';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-controls', input.id || '');
        button.setAttribute('title', 'Show password');
        button.tabIndex = 0;
        button.innerHTML = eyeSvg;
        button.style.cssText = 'position:absolute;top:50%;right:10px;transform:translateY(-50%);display:inline-flex;align-items:center;justify-content:center;padding:6px;border:0;background:transparent;color:inherit;cursor:pointer;z-index:2;';
        input.style.paddingRight = '42px';
        field.appendChild(button);
    }

    function initialize(root) {
        if (!root || !root.querySelectorAll) return;
        var roots = [];
        if (root.matches && root.matches(wrapperSelector)) roots.push(root);
        roots = roots.concat(Array.prototype.slice.call(root.querySelectorAll(wrapperSelector)));
        roots.forEach(function (wrapper) {
            wrapper.querySelectorAll('input[type="password"]').forEach(addToggle);
            enhanceSelects(wrapper);
        });
    }

    function enhanceSelects(wrapper) {
        var formMatch = (wrapper.id || '').match(/^my-login-wrap-(\d+)$/);
        if (!formMatch) return;
        var formId = formMatch[1];

        wrapper.querySelectorAll('select').forEach(function (select, index) {
            if (select.classList.contains('mlf-select-enhanced') || select.multiple) return;

            var required = select.required;
            select.required = false;
            select.classList.add('mlf-select-native', 'mlf-select-enhanced');
            select.setAttribute('aria-hidden', 'true');
            select.tabIndex = -1;
            select.dataset.mlfRequired = required ? '1' : '0';

            var menu = document.createElement('div');
            menu.className = 'mlf-select-menu mlf-select-menu-form-' + formId;
            menu.setAttribute('role', 'listbox');
            menu.hidden = true;

            Array.prototype.forEach.call(select.options, function (option, optionIndex) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'mlf-select-option';
                item.setAttribute('role', 'option');
                item.setAttribute('data-value', option.value);
                item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                item.textContent = option.text;
                item.disabled = option.disabled;
                if (optionIndex === 0 && option.value === '') item.classList.add('mlf-select-placeholder');
                menu.appendChild(item);
            });

            var widget = document.createElement('div');
            widget.className = 'mlf-select-widget';
            var trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'mlf-select-trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.setAttribute('aria-label', (wrapper.querySelector('label[for="' + select.id + '"]') || {}).textContent || select.name || 'Choose an option');
            trigger.setAttribute('aria-controls', 'mlf-select-menu-' + formId + '-' + index);
            menu.id = 'mlf-select-menu-' + formId + '-' + index;
            menu.dataset.mlfFormId = formId;
            var value = document.createElement('span');
            value.className = 'mlf-select-value';
            trigger.appendChild(value);
            widget.appendChild(trigger);
            select.parentNode.insertBefore(widget, select.nextSibling);
            document.body.appendChild(menu);

            function syncValue() {
                var selected = select.options[select.selectedIndex];
                value.textContent = selected ? selected.text : 'Select…';
                menu.querySelectorAll('.mlf-select-option').forEach(function (item) {
                    item.setAttribute('aria-selected', item.dataset.value === select.value ? 'true' : 'false');
                });
                trigger.setAttribute('aria-invalid', 'false');
            }
            function closeMenu(returnFocus) {
                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                if (returnFocus) trigger.focus();
            }
            function openMenu(focusSelected) {
                var vars = window.getComputedStyle(wrapper);
                var radius = window.getComputedStyle(trigger).borderRadius || vars.getPropertyValue('--mlf-template-control-radius').trim() || '12px';
                menu.style.setProperty('--mlf-template-accent', vars.getPropertyValue('--mlf-template-accent').trim() || '#1FBB00');
                menu.style.setProperty('--mlf-template-control-border', vars.getPropertyValue('--mlf-template-control-border').trim() || '#DCE8D6');
                menu.style.setProperty('--mlf-template-control-radius', radius);
                menu.style.setProperty('border-radius', radius, 'important');
                menu.querySelectorAll('.mlf-select-option').forEach(function (item) {
                    item.style.setProperty('border-radius', Math.max(4, Math.min(12, (parseFloat(radius) || 12) - 4)) + 'px', 'important');
                });
                menu.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');
                var rect = trigger.getBoundingClientRect();
                var menuHeight = Math.min(240, Math.max(48, menu.scrollHeight));
                var spaceBelow = window.innerHeight - rect.bottom;
                var openAbove = spaceBelow < menuHeight + 12 && rect.top > spaceBelow;
                menu.style.position = 'fixed';
                menu.style.top = Math.max(8, openAbove ? rect.top - menuHeight - 6 : rect.bottom + 6) + 'px';
                menu.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - rect.width - 8)) + 'px';
                menu.style.width = rect.width + 'px';
                menu.style.maxHeight = Math.min(240, window.innerHeight - 16) + 'px';
                if (focusSelected) {
                    var selectedOption = menu.querySelector('[aria-selected="true"]:not(:disabled)') || menu.querySelector('.mlf-select-option:not(:disabled)');
                    if (selectedOption) selectedOption.focus();
                }
            }

            trigger.addEventListener('click', function () {
                if (menu.hidden) openMenu(false);
                else closeMenu(false);
            });
            trigger.addEventListener('keydown', function (event) {
                if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                    event.preventDefault();
                    openMenu(true);
                }
            });
            menu.addEventListener('click', function (event) {
                var item = event.target.closest('.mlf-select-option:not(:disabled)');
                if (!item) return;
                select.value = item.dataset.value;
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
                closeMenu(true);
            });
            menu.addEventListener('keydown', function (event) {
                var options = Array.prototype.slice.call(menu.querySelectorAll('.mlf-select-option:not(:disabled)'));
                var current = options.indexOf(document.activeElement);
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeMenu(true);
                } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    var step = event.key === 'ArrowDown' ? 1 : -1;
                    if (options.length) options[(current + step + options.length) % options.length].focus();
                }
            });
            document.addEventListener('click', function (event) {
                if (!widget.contains(event.target) && !menu.contains(event.target)) closeMenu(false);
            });
            window.addEventListener('resize', function () { if (!menu.hidden) openMenu(false); });
            window.addEventListener('scroll', function () { if (!menu.hidden) openMenu(false); }, true);
            select.addEventListener('change', syncValue);
            var label = wrapper.querySelector('label[for="' + select.id + '"]');
            if (label) label.addEventListener('click', function (event) {
                event.preventDefault();
                trigger.click();
                trigger.focus();
            });
            syncValue();
        });
    }

    function start() {
        initialize(document);

        document.addEventListener('click', function (event) {
            var button = event.target.closest && event.target.closest('.my-login-password-toggle');
            if (!button) return;
            event.preventDefault();

            var field = button.closest('.my-login-password-wrap');
            var input = field && field.querySelector('input[type="password"], input[type="text"]');
            if (!input) return;

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.setAttribute('title', show ? 'Hide password' : 'Show password');

            var icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            } else {
                button.innerHTML = show ? eyeOffSvg : eyeSvg;
            }
        });

        if (window.MutationObserver) {
            new MutationObserver(function (records) {
                records.forEach(function (record) {
                    record.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) return;
                        var owner = node.matches && node.matches(wrapperSelector) ? node : (node.closest && node.closest(wrapperSelector));
                        initialize(owner || node);
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
