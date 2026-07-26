(function ($) {
    'use strict';

    // ============================================================
    // TEMPLATES
    // ============================================================
    const T = {
        mainContainer: (id, btnColor, btnText, width, minHeight) => {
            const w = (width !== undefined && width !== null && width !== '') ? width : 100;
            const mh = minHeight ? `min-height:${minHeight}px;` : '';
            return `
            <div class="main-container my-login-main-container" data-id="${id}" data-type="main" style="width:${w}%;${mh}">
                <div class="element-controls">
                    <span class="element-handle" title="Move">⠿</span>
                    <button class="btn-edit-element" data-id="${id}" data-etype="main" title="Edit">✏️</button>
                    <button class="btn-remove-element" data-id="${id}" title="Remove">✕</button>
                </div>
                <div class="drop-zone main-drop-zone" data-parent="${id}" data-zone-type="main">
                    <div class="drop-hint">Drop fields, sub divs, or social sections here</div>
                </div>
                <div class="container-submit">
                    <button class="preview-submit-btn my-login-submit-btn" style="background:linear-gradient(135deg,${btnColor} 0%,#0F5900 100%);box-shadow:0 4px 14px ${btnColor}59;pointer-events:none;">${btnText}</button>
                </div>
            </div>`;
        },

        subDiv: (id, bg) => `
            <div class="sub-div my-login-sub-div element-item" data-id="${id}" data-type="sub" style="background:${bg||'#F3FBF0'}">
                <div class="element-controls">
                    <span class="element-handle" title="Move">⠿</span>
                    <button class="btn-edit-element" data-id="${id}" data-etype="sub" title="Edit">✏️</button>
                    <button class="btn-remove-element" data-id="${id}" title="Remove">✕</button>
                </div>
                <div class="drop-zone sub-drop-zone" data-parent="${id}" data-zone-type="sub">
                    <div class="drop-hint">Drop fields or social sections here</div>
                </div>
            </div>`,

        socialSection: (id, bg) => `
            <div class="social-section my-login-social-section element-item" data-id="${id}" data-type="social_section" style="background:${bg||'#fff'}">
                <div class="element-controls">
                    <span class="element-handle" title="Move">⠿</span>
                    <button class="btn-edit-element" data-id="${id}" data-etype="social_section" title="Edit">✏️</button>
                    <button class="btn-remove-element" data-id="${id}" title="Remove">✕</button>
                </div>
                <div class="social-divider-text my-login-social-divider">— or continue with —</div>
                <div class="drop-zone social-drop-zone my-login-social-buttons" data-parent="${id}" data-zone-type="social">
                    <div class="drop-hint">Drop social login buttons here</div>
                </div>
            </div>`,

        field: (id, fieldType, label, htmlType, placeholder, showLabels, required) => {
            const reqMark = required ? '<span class="my-login-req" style="color:#8A0000">*</span>' : '';
            const labelHtml = (showLabels && htmlType !== 'checkbox' && htmlType !== 'radio')
                ? `<label class="field-label-preview my-login-label">${label}${reqMark}</label>` : '';
            const inp = T.inputHtml(htmlType, placeholder, required, label);
            return `<div class="form-field my-login-form-field element-item" data-id="${id}" data-type="field"
                         data-field-type="${fieldType}" data-html-type="${htmlType}">
                        <div class="element-controls">
                            <span class="element-handle" title="Move">⠿</span>
                            <button class="btn-edit-element" data-id="${id}" data-etype="field" title="Edit">✏️</button>
                            <button class="btn-remove-element" data-id="${id}" title="Remove">✕</button>
                        </div>
                        <div class="field-preview">${labelHtml}${inp}</div>
                    </div>`;
        },

        greeting: (id, title, subtitle) => {
            const titleHtml    = title    ? `<h2 class="my-login-greeting-title">${title}</h2>` : '';
            const subtitleHtml = subtitle ? `<p class="my-login-greeting-subtitle">${subtitle}</p>` : '';
            return `<div class="greeting-block my-login-greeting element-item" data-id="${id}" data-type="greeting">
                        <div class="element-controls">
                            <span class="element-handle" title="Move">⠿</span>
                            <button class="btn-edit-element" data-id="${id}" data-etype="greeting" title="Edit">✏️</button>
                            <button class="btn-remove-element" data-id="${id}" title="Remove">✕</button>
                        </div>
                        <div class="greeting-preview">${titleHtml}${subtitleHtml}</div>
                    </div>`;
        },

        inputHtml: (type, ph, req, label) => {
            const r = req ? ' required' : '';
            const uid = 'f_' + Math.random().toString(36).slice(2, 7);
            const map = {
                text:     `<input type="text" placeholder="${ph||''}" ${r} style="pointer-events:none">`,
                email:    `<input type="email" placeholder="${ph||''}" ${r} style="pointer-events:none">`,
                password: `<input type="password" placeholder="${ph||''}" ${r} style="pointer-events:none">`,
                number:   `<input type="number" placeholder="${ph||''}" ${r} style="pointer-events:none">`,
                tel:      `<input type="tel" placeholder="${ph||''}" ${r} style="pointer-events:none">`,
                date:     `<input type="date" ${r} style="pointer-events:none">`,
                textarea: `<textarea placeholder="${ph||''}" rows="3" ${r} style="pointer-events:none"></textarea>`,
                select:   `<select ${r} style="pointer-events:none"><option>Select...</option><option>Option 1</option></select>`,
                checkbox: `<label style="pointer-events:none"><input type="checkbox" id="${uid}" ${r}> ${label||'Checkbox'}</label>`,
                radio:    `<label style="pointer-events:none"><input type="radio" id="${uid}" ${r}> ${label||'Radio'}</label>`,
            };
            return map[type] || map.text;
        },

        socialBtn: (provider, color, label, icon) => {
            const iconHtml = icon ? `<i class="${icon}" style="pointer-events:none;font-size:14px;"></i> ` : '';
            return `<div class="social-btn-preview my-login-social-btn" data-provider="${provider}"
                  style="background:${color};color:#fff;padding:9px 18px;border-radius:6px;
                         display:inline-flex;align-items:center;gap:6px;margin:4px;cursor:default;font-size:13px;">
                ${iconHtml}${label}
             </div>`;
        },

        dropHint: () => `<div class="drop-hint">Drop fields, sub divs, or social sections here</div>`,
        emptyBuilder: () => `<div class="builder-empty">📦 Drag a <strong>Main Container</strong> from the left panel to start</div>`,

        layoutPlaceholder: () => `<p class="placeholder-message" style="color:#94a3b8;font-size:13px;padding:12px 0;">📌 Click ✏️ on any field or container to edit its properties here</p>`,
        customizationPlaceholder: () => `<p class="placeholder-message" style="color:#94a3b8;font-size:13px;padding:12px 0;">🎨 Click ✏️ on any element to edit its colors and styles here</p>`,
    };

    // ============================================================
    // FORM BUILDER
    // ============================================================
    class FormBuilder {
        containers = {};
        idCounter   = 0;
        currentFormId  = null;
        currentFormKey = null;
        settings = { btn_color: '#1FBB00', button_text: 'Submit', show_labels: false };
        saveTimer = null;
        selectedId   = null;
        selectedType = null;
        cssEditor = null;
        jsEditor  = null;

        constructor() {
            this.bind();
            this.initTabs();
            this.initModals();
            this.initCodeEditors();

            const fid = MyLoginDesigner.current_form_id;
            if (fid) {
                this.loadForm(fid);
                this.highlightFormItem(fid);
            } else if (MyLoginDesigner.forms && MyLoginDesigner.forms.length) {
                const first = MyLoginDesigner.forms[0];
                this.loadForm(first.id);
                this.highlightFormItem(first.id);
            } else {
                this.renderEmpty();
            }
        }

        uid(prefix) { return `${prefix}_${Date.now()}_${++this.idCounter}`; }

        // ── CODE EDITORS (CSS / JS tabs) ───────────────────────
        // Uses WordPress's own CodeMirror-based editor (wp.codeEditor) — the
        // same component behind the Customizer's Custom CSS field. Falls back
        // to the plain <textarea> if the user has disabled syntax highlighting
        // in their profile (wp_enqueue_code_editor() then returns false).
        initCodeEditors() {
            // Fallback textarea (no wp.codeEditor / syntax highlighting disabled) —
            // the "input" event fires directly on it as the admin types.
            $('#customCSS').on('input.livecss', () => this.updateLiveCSS());

            if (typeof wp === 'undefined' || !wp.codeEditor) return;

            if (MyLoginDesigner.css_editor_settings) {
                this.cssEditor = wp.codeEditor.initialize(document.getElementById('customCSS'), MyLoginDesigner.css_editor_settings);
                // CodeMirror owns the keystrokes here, so the textarea's own
                // "input" event above never fires — its "change" is the live signal.
                this.cssEditor.codemirror.on('change', () => this.updateLiveCSS());
            }
            if (MyLoginDesigner.js_editor_settings) {
                this.jsEditor = wp.codeEditor.initialize(document.getElementById('customJS'), MyLoginDesigner.js_editor_settings);
            }
        }

        // ── LIVE CSS PREVIEW ────────────────────────────────────
        // Reflects whatever the admin is typing in the CSS tab straight into the
        // Form Builder canvas, before Save CSS is ever clicked. Scoped under
        // #formBuilder so admin-authored rules (often plain ".my-login-submit-btn"
        // selectors, same as the frontend) can never leak out into the rest of wp-admin.
        updateLiveCSS() {
            let $style = $('#mlLiveCssPreview');
            if (!$style.length) { $style = $('<style>', { id: 'mlLiveCssPreview' }).appendTo('head'); }
            $style.text(this.forceImportant(this.scopeCss(this.getCssValue())));
        }

        scopeCss(css) {
            return String(css || '').replace(/([^{}]+)\{/g, (match, selector) => {
                const trimmed = selector.trim();
                if (!trimmed || trimmed.charAt(0) === '@' || trimmed.indexOf('#formBuilder') === 0) return match;
                const scoped = trimmed.split(',').map(sel => {
                    // The frontend's outer wrapper (.my-login-wrap / .my-login-wrap-N /
                    // #my-login-wrap-N, see FormsShortcodes.php) is one form card — its
                    // builder-canvas equivalent is .main-container, NOT #formBuilder.
                    // #formBuilder is the whole scrollable drop area (can hold several
                    // containers, needs its own overflow-y/padding — see designer.css); forcing
                    // the wrap's box model (max-width:400px, overflow:hidden, margin:auto, …)
                    // onto it instead of onto each card shrank and clipped the entire canvas.
                    const s = sel.trim().replace(/^(\.my-login-wrap(-\d+)?|#my-login-wrap-\d+)\b/, '.main-container');
                    return s.indexOf('#formBuilder') === 0 ? s : '#formBuilder ' + s;
                }).join(', ');
                return scoped + '{';
            });
        }

        // Several canvas preview elements (submit button, social buttons) carry
        // their default look as an inline style="" attribute (see templates T.*
        // above), which always wins over a plain selector rule no matter how the
        // selector is scoped. This is a preview-only stylesheet, so it's safe to
        // force every declaration to !important — the real saved CSS (written to
        // the .css file on Save) is untouched.
        forceImportant(css) {
            return String(css || '').replace(/\{([^{}]*)\}/g, (match, body) => {
                const forced = body.replace(/([\w-]+)\s*:\s*([^;]+);?/g, (decl, prop, value) => {
                    const val = value.trim().replace(/\s*!important\s*$/i, '');
                    return val ? `${prop}:${val} !important;` : decl;
                });
                return '{' + forced + '}';
            });
        }

        getCssValue() {
            if (this.cssEditor) { this.cssEditor.codemirror.save(); }
            return $('#customCSS').val() || '';
        }

        getJsValue() {
            if (this.jsEditor) { this.jsEditor.codemirror.save(); }
            return $('#customJS').val() || '';
        }

        setCssValue(value) {
            if (this.cssEditor) { this.cssEditor.codemirror.setValue(value || ''); }
            else { $('#customCSS').val(value || ''); }
        }

        setJsValue(value) {
            if (this.jsEditor) { this.jsEditor.codemirror.setValue(value || ''); }
            else { $('#customJS').val(value || ''); }
        }

        highlightFormItem(formId) {
            $('#formList .form-item').removeClass('active selected');
            $(`#formList .form-item[data-form-id="${formId}"]`).addClass('active selected');
        }

        renderEmpty() {
            $('#formBuilder').html(T.emptyBuilder());
            this.initMainDrop();
        }

        // ── LOAD form from server ──────────────────────────────
        loadForm(formId) {
            $('#formBuilder').html('<div class="builder-loading">⏳ Loading form…</div>');
            this.highlightFormItem(formId);
            this.clearSelection();

            $.ajax({
                url: MyLoginDesigner.ajax_url, type: 'POST',
                data: { action: 'my_login_get_form', form_id: formId, nonce: MyLoginDesigner.nonces.get_form },
                success: (r) => {
                    if (!r.success) { this.renderEmpty(); return; }
                    const d = r.data;
                    this.currentFormId  = d.id;
                    this.currentFormKey = d.form_key;
                    this.containers = (d.containers && Object.keys(d.containers).length) ? d.containers : {};
                    if (d.settings && d.settings.btn_color) {
                        this.settings = { ...this.settings, ...d.settings };
                    }
                    this.setCssValue(d.css_content);
                    this.setJsValue(d.js_content);
                    this.updateLiveCSS();
                    this.applySettingsUI();
                    this.renderAll();
                    this.highlightFormItem(formId);
                    $('#currentFormTitle').text(d.name || '');
                },
                error: () => this.renderEmpty()
            });
        }

        // ── RENDER ────────────────────────────────────────────
        renderAll() {
            $('#formBuilder').empty();
            if (!Object.keys(this.containers).length) { this.renderEmpty(); return; }
            for (const id in this.containers) {
                this.renderContainerDOM(this.containers[id]);
            }
            this.initMainDrop();
        }

        renderContainerDOM(c) {
            const s = c.styles || {};
            const $el = $(T.mainContainer(c.id, this.settings.btn_color, this.settings.button_text, s.width, s.min_height));
            $('#formBuilder').append($el);
            this.makeMainDroppable(c.id);
            this.makeMainSortable(c.id);
            if (c.items && c.items.length) {
                this.renderItems(c.id, c.items, 'main');
            }
        }

        renderItems(parentId, items, zoneType) {
            const $zone = this.getDropZone(parentId, zoneType);
            for (const item of items) {
                if (item.type === 'sub') {
                    const $sub = $(T.subDiv(item.id, item.bgColor));
                    $zone.append($sub);
                    this.makeSubDroppable(item.id);
                    this.makeSubSortable(item.id);
                    if (item.items && item.items.length) {
                        this.renderItems(item.id, item.items, 'sub');
                    }
                } else if (item.type === 'social_section') {
                    const $ss = $(T.socialSection(item.id, item.bgColor));
                    $zone.append($ss);
                    this.makeSocialDroppable(item.id);
                    if (item.socialButtons && item.socialButtons.length) {
                        const $szone = this.getDropZone(item.id, 'social');
                        item.socialButtons.forEach(b => {
                            const p = MyLoginDesigner.social_providers[b.provider];
                            if (p) $szone.append(T.socialBtn(b.provider, p.color, p.label, p.icon));
                        });
                    }
                    this.updateDropHint($zone);
                } else if (item.type === 'field') {
                    $zone.append(T.field(
                        item.id, item.fieldType, item.label, item.htmlType,
                        item.placeholder, this.settings.show_labels, item.required
                    ));
                } else if (item.type === 'greeting') {
                    $zone.append(T.greeting(item.id, item.title, item.subtitle));
                    this.refreshGreetingDOM(item.id, item);
                }
            }
            this.updateDropHint($zone);
        }

        getDropZone(parentId, zoneType) {
            if (zoneType === 'main')   return $(`.main-container[data-id="${parentId}"] .main-drop-zone`);
            if (zoneType === 'sub')    return $(`.sub-div[data-id="${parentId}"] .sub-drop-zone`);
            if (zoneType === 'social') return $(`.social-section[data-id="${parentId}"] .social-drop-zone`);
            return $();
        }

        // ── DROPPABLE ─────────────────────────────────────────
        initMainDrop() {
            if ($('#formBuilder').hasClass('ui-droppable')) $('#formBuilder').droppable('destroy');
            $('#formBuilder').droppable({
                accept: '.palette-main-draggable',
                hoverClass: 'drop-hover',
                tolerance: 'pointer',
                drop: (e, ui) => { e.stopPropagation(); this.addMainContainer(); }
            });
        }

        makeMainDroppable(containerId) {
            const $zone = $(`.main-container[data-id="${containerId}"] .main-drop-zone`);
            if ($zone.hasClass('ui-droppable')) $zone.droppable('destroy');
            $zone.droppable({
                accept: (el) => {
                    const t = $(el).data('drag-type');
                    return t === 'field' || t === 'sub_div' || t === 'social_section' || t === 'greeting';
                },
                hoverClass: 'drop-hover', tolerance: 'pointer', greedy: true,
                drop: (e, ui) => { e.stopPropagation(); this.handleDrop(containerId, 'main', ui.draggable); }
            });
        }

        makeSubDroppable(subId) {
            const $zone = $(`.sub-div[data-id="${subId}"] .sub-drop-zone`);
            if ($zone.hasClass('ui-droppable')) $zone.droppable('destroy');
            $zone.droppable({
                accept: (el) => {
                    const t = $(el).data('drag-type');
                    return t === 'field' || t === 'social_section' || t === 'greeting';
                },
                hoverClass: 'drop-hover', tolerance: 'pointer', greedy: true,
                drop: (e, ui) => { e.stopPropagation(); this.handleDrop(subId, 'sub', ui.draggable); }
            });
        }

        makeSocialDroppable(sectionId) {
            const $zone = $(`.social-section[data-id="${sectionId}"] .social-drop-zone`);
            if ($zone.hasClass('ui-droppable')) $zone.droppable('destroy');
            $zone.droppable({
                accept: (el) => $(el).data('drag-type') === 'social',
                hoverClass: 'drop-hover', tolerance: 'pointer', greedy: true,
                drop: (e, ui) => { e.stopPropagation(); this.handleDrop(sectionId, 'social', ui.draggable); }
            });
        }

        // ── SORTABLE ──────────────────────────────────────────
        makeMainSortable(containerId) {
            const $zone = $(`.main-container[data-id="${containerId}"] .main-drop-zone`);
            if ($zone.hasClass('ui-sortable')) $zone.sortable('destroy');
            $zone.sortable({
                items: '.element-item', handle: '.element-handle',
                placeholder: 'sortable-placeholder',
                cancel: '.btn-remove-element,.btn-edit-element',
                update: () => this.syncOrderFromDOM(containerId, 'main')
            });
        }

        makeSubSortable(subId) {
            const $zone = $(`.sub-div[data-id="${subId}"] .sub-drop-zone`);
            if ($zone.hasClass('ui-sortable')) $zone.sortable('destroy');
            $zone.sortable({
                items: '.element-item', handle: '.element-handle',
                placeholder: 'sortable-placeholder',
                cancel: '.btn-remove-element,.btn-edit-element',
                update: () => this.autoSave()
            });
        }

        // ── DROP HANDLER ──────────────────────────────────────
        handleDrop(parentId, zoneType, $drag) {
            const dragType = $drag.data('drag-type');
            if (dragType === 'field') {
                this.addField(parentId, zoneType, {
                    fieldType: $drag.data('field-type'),
                    label:     $drag.data('field-label'),
                    htmlType:  $drag.data('field-html-type'),
                });
            } else if (dragType === 'sub_div') {
                this.addSubDiv(parentId, zoneType);
            } else if (dragType === 'social_section') {
                this.addSocialSection(parentId, zoneType);
            } else if (dragType === 'greeting') {
                this.addGreeting(parentId, zoneType);
            } else if (dragType === 'social') {
                if ($drag.data('no-supabase')) {
                    if (!confirm('⚠️ Supabase is not configured. Social login will not work until you set it up. Add anyway?')) return;
                }
                this.addSocialButton(parentId, $drag.data('social-provider'));
            }
        }

        // ── ADD ELEMENTS ──────────────────────────────────────
        addMainContainer() {
            const id = this.uid('main');
            this.containers[id] = { id, type: 'main', bgColor: '#ffffff', styles: {}, items: [] };
            const $el = $(T.mainContainer(id, this.settings.btn_color, this.settings.button_text));
            $('#formBuilder .builder-empty').remove();
            $('#formBuilder').append($el);
            this.makeMainDroppable(id);
            this.makeMainSortable(id);
            this.initMainDrop();
            this.autoSave();
        }

        addSubDiv(parentId, zoneType) {
            const id = this.uid('sub');
            const item = { id, type: 'sub', bgColor: '#f8f9fa', styles: {}, items: [] };
            this.insertIntoData(parentId, item);
            const $zone = this.getDropZone(parentId, zoneType);
            $zone.find('.drop-hint').remove();
            $zone.append(T.subDiv(id));
            this.makeSubDroppable(id);
            this.makeSubSortable(id);
            this.updateDropHint($zone);
            this.autoSave();
        }

        addSocialSection(parentId, zoneType) {
            const id = this.uid('ss');
            const item = { id, type: 'social_section', bgColor: '#ffffff', socialButtons: [] };
            this.insertIntoData(parentId, item);
            const $zone = this.getDropZone(parentId, zoneType);
            $zone.find('.drop-hint').remove();
            $zone.append(T.socialSection(id));
            this.makeSocialDroppable(id);
            this.updateDropHint($zone);
            this.autoSave();
        }

        addGreeting(parentId, zoneType) {
            const id = this.uid('greet');
            const item = { id, type: 'greeting', title: 'Welcome Back', subtitle: 'Please sign in to continue', styles: {} };
            this.insertIntoData(parentId, item);
            const $zone = this.getDropZone(parentId, zoneType);
            $zone.find('.drop-hint').remove();
            $zone.append(T.greeting(id, item.title, item.subtitle));
            this.updateDropHint($zone);
            this.autoSave();
        }

        addField(parentId, zoneType, opts) {
            const id = this.uid('fld');
            const placeholder = opts.placeholder || ('Enter ' + opts.label.toLowerCase());
            const item = {
                id, type: 'field',
                fieldType: opts.fieldType, label: opts.label,
                htmlType: opts.htmlType, placeholder, required: false, styles: {}
            };
            this.insertIntoData(parentId, item);
            const $zone = this.getDropZone(parentId, zoneType);
            $zone.find('.drop-hint').remove();
            $zone.append(T.field(id, opts.fieldType, opts.label, opts.htmlType,
                placeholder, this.settings.show_labels, false));
            this.updateDropHint($zone);
            this.autoSave();
        }

        addSocialButton(sectionId, provider) {
            const p = MyLoginDesigner.social_providers[provider];
            if (!p) return;
            this.addSocialBtnToData(sectionId, provider);
            const $zone = this.getDropZone(sectionId, 'social');
            $zone.find('.drop-hint').remove();
            $zone.append(T.socialBtn(provider, p.color, p.label, p.icon));
            this.updateDropHint($zone);
            this.autoSave();
        }

        // ── DATA OPERATIONS ───────────────────────────────────
        insertIntoData(parentId, item) {
            if (this.containers[parentId]) { this.containers[parentId].items.push(item); return; }
            for (const cid in this.containers) {
                if (this._insertNested(this.containers[cid].items, parentId, item)) return;
            }
        }

        _insertNested(items, parentId, newItem) {
            for (const it of items) {
                if (it.id === parentId) { it.items = it.items || []; it.items.push(newItem); return true; }
                if (it.items && this._insertNested(it.items, parentId, newItem)) return true;
            }
            return false;
        }

        addSocialBtnToData(sectionId, provider) {
            for (const cid in this.containers) {
                if (this._addSocialNested(this.containers[cid].items, sectionId, provider)) return;
            }
        }

        _addSocialNested(items, sectionId, provider) {
            for (const it of items) {
                if (it.id === sectionId && it.type === 'social_section') {
                    it.socialButtons = it.socialButtons || [];
                    if (!it.socialButtons.find(b => b.provider === provider)) {
                        it.socialButtons.push({ provider });
                    }
                    return true;
                }
                if (it.items && this._addSocialNested(it.items, sectionId, provider)) return true;
            }
            return false;
        }

        removeById(id) {
            if (this.containers[id]) { delete this.containers[id]; return; }
            for (const cid in this.containers) {
                if (this._removeNested(this.containers[cid].items, id)) return;
            }
        }

        _removeNested(items, id) {
            for (let i = 0; i < items.length; i++) {
                if (items[i].id === id) { items.splice(i, 1); return true; }
                if (items[i].items && this._removeNested(items[i].items, id)) return true;
            }
            return false;
        }

        syncOrderFromDOM(containerId, zoneType) {
            const $zone = this.getDropZone(containerId, zoneType);
            const newOrder = [];
            $zone.children('.element-item').each((_, el) => {
                const id = $(el).data('id');
                const found = this.findById(id);
                if (found) newOrder.push(found);
            });
            if (this.containers[containerId]) this.containers[containerId].items = newOrder;
            this.autoSave();
        }

        findById(id) {
            for (const cid in this.containers) {
                const found = this._findNested(this.containers[cid].items, id);
                if (found) return found;
            }
            return null;
        }

        _findNested(items, id) {
            for (const it of items) {
                if (it.id === id) return it;
                if (it.items) { const f = this._findNested(it.items, id); if (f) return f; }
            }
            return null;
        }

        updateDropHint($zone) {
            const hasChildren = $zone.children('.element-item,.social-btn-preview').length > 0;
            if (hasChildren) $zone.find('.drop-hint').hide();
            else $zone.find('.drop-hint').show();
        }

        // ── ELEMENT SELECTION — populates Layout / Customization tabs ──
        selectElement(id, type) {
            const item = type === 'main' ? this.containers[id] : this.findById(id);
            if (!item) return;

            this.selectedId   = id;
            this.selectedType = type;

            const labels = { main: 'Main Container', sub: 'Sub Div', social_section: 'Social Section', field: 'Field', greeting: 'Greeting' };
            const name = type === 'field' ? (item.label || item.fieldType || 'Field') : labels[type];
            $('#selectionInfo').show();
            $('#selectedItemName').text(name);

            // Highlight selected in builder
            $('.main-container,.sub-div,.social-section,.form-field,.greeting-block').removeClass('selected');
            $(`.main-container[data-id="${id}"], .sub-div[data-id="${id}"], .social-section[data-id="${id}"], .form-field[data-id="${id}"], .greeting-block[data-id="${id}"]`).addClass('selected');

            // Build panels
            $('#layoutContent').html(this.buildLayoutPanel(type, item));
            $('#customizationContent').html(this.buildCustomizationPanel(type, item));

            // Activate Layout tab
            $('.tab-btn[data-tab="layout"]').trigger('click');

            // Wire up live controls
            this.bindPanelControls(id, type, item);
        }

        clearSelection() {
            this.selectedId   = null;
            this.selectedType = null;
            $('#selectionInfo').hide();
            $('.main-container,.sub-div,.social-section,.form-field,.greeting-block').removeClass('selected');
            $('#layoutContent').html(T.layoutPlaceholder());
            $('#customizationContent').html(T.customizationPlaceholder());
        }

        // ── BUILD LAYOUT PANEL ────────────────────────────────
        buildLayoutPanel(type, item) {
            const s = item.styles || {};
            let html = '';

            if (type === 'field') {
                html = `<div class="settings-group">
                    <h4>Field Properties</h4>
                    <div class="setting-field">
                        <label>Label</label>
                        <input type="text" class="widefat" id="ctrl_label" value="${this.esc(item.label)}">
                    </div>
                    <div class="setting-field">
                        <label>Placeholder</label>
                        <input type="text" class="widefat" id="ctrl_placeholder" value="${this.esc(item.placeholder)}">
                    </div>
                    <div class="setting-field">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" id="ctrl_required" ${item.required ? 'checked' : ''} style="width:auto">
                            Required field
                        </label>
                    </div>
                </div>
                <div class="settings-group">
                    <h4>Layout</h4>
                    <div class="setting-field">
                        <label>Width</label>
                        <input type="range" id="ctrl_width" min="20" max="100" value="${s.width || 100}">
                        <span class="range-value" id="ctrl_width_lbl">${s.width || 100}%</span>
                    </div>
                    <div class="setting-field">
                        <label>Margin Bottom</label>
                        <input type="range" id="ctrl_margin_bottom" min="0" max="40" value="${s.margin_bottom || 12}">
                        <span class="range-value" id="ctrl_margin_bottom_lbl">${s.margin_bottom || 12}px</span>
                    </div>
                    <div class="setting-field">
                        <label>Label Font Size</label>
                        <input type="range" id="ctrl_label_font_size" min="10" max="22" value="${s.label_font_size || 13}">
                        <span class="range-value" id="ctrl_label_font_size_lbl">${s.label_font_size || 13}px</span>
                    </div>
                    <div class="setting-field">
                        <label>Input Padding</label>
                        <input type="range" id="ctrl_input_padding" min="4" max="24" value="${s.input_padding || 10}">
                        <span class="range-value" id="ctrl_input_padding_lbl">${s.input_padding || 10}px</span>
                    </div>
                </div>`;

            } else if (type === 'main') {
                const padDef = s.padding !== undefined ? s.padding : 20;
                const radDef = s.border_radius !== undefined ? s.border_radius : 12;
                const bwDef  = s.border_width !== undefined ? s.border_width : 0;
                const widthDef = s.width !== undefined ? s.width : 100;
                const heightDef = s.min_height !== undefined ? s.min_height : 0;
                html = `<div class="settings-group">
                    <h4>Layout</h4>
                    <div class="setting-field">
                        <label>Width</label>
                        <input type="range" id="ctrl_width" min="20" max="100" value="${widthDef}">
                        <span class="range-value" id="ctrl_width_lbl">${widthDef}%</span>
                    </div>
                    <div class="setting-field">
                        <label>Min Height</label>
                        <input type="range" id="ctrl_min_height" min="0" max="800" step="10" value="${heightDef}">
                        <span class="range-value" id="ctrl_min_height_lbl">${heightDef > 0 ? heightDef + 'px' : 'Auto'}</span>
                    </div>
                    <div class="setting-field">
                        <label>Padding</label>
                        <input type="range" id="ctrl_padding" min="0" max="60" value="${padDef}">
                        <span class="range-value" id="ctrl_padding_lbl">${padDef}px</span>
                    </div>
                    <div class="setting-field">
                        <label>Border Radius</label>
                        <input type="range" id="ctrl_border_radius" min="0" max="40" value="${radDef}">
                        <span class="range-value" id="ctrl_border_radius_lbl">${radDef}px</span>
                    </div>
                    <div class="setting-field">
                        <label>Border Width</label>
                        <input type="range" id="ctrl_border_width" min="0" max="8" value="${bwDef}">
                        <span class="range-value" id="ctrl_border_width_lbl">${bwDef}px</span>
                    </div>
                </div>`;

            } else if (type === 'sub') {
                const padDef = s.padding !== undefined ? s.padding : 12;
                const radDef = s.border_radius !== undefined ? s.border_radius : 8;
                html = `<div class="settings-group">
                    <h4>Sub Div Layout</h4>
                    <div class="setting-field">
                        <label>Padding</label>
                        <input type="range" id="ctrl_padding" min="0" max="48" value="${padDef}">
                        <span class="range-value" id="ctrl_padding_lbl">${padDef}px</span>
                    </div>
                    <div class="setting-field">
                        <label>Border Radius</label>
                        <input type="range" id="ctrl_border_radius" min="0" max="30" value="${radDef}">
                        <span class="range-value" id="ctrl_border_radius_lbl">${radDef}px</span>
                    </div>
                </div>`;

            } else if (type === 'social_section') {
                html = `<div class="settings-group">
                    <h4>Social Section</h4>
                    <p style="font-size:13px;color:#64748b;line-height:1.6;">
                        Drag social login buttons from the left palette into this section.<br>
                        Use the <em>Customization</em> tab to change the background color.
                    </p>
                </div>`;

            } else if (type === 'greeting') {
                html = `<div class="settings-group">
                    <h4>Greeting Text</h4>
                    <div class="setting-field">
                        <label>Title</label>
                        <input type="text" class="widefat" id="ctrl_title" value="${this.esc(item.title)}" placeholder="e.g. Welcome Back">
                    </div>
                    <div class="setting-field">
                        <label>Subtitle</label>
                        <input type="text" class="widefat" id="ctrl_subtitle" value="${this.esc(item.subtitle)}" placeholder="e.g. Please sign in to continue">
                    </div>
                </div>`;
            }

            return html || T.layoutPlaceholder();
        }

        // ── BUILD CUSTOMIZATION PANEL ─────────────────────────
        buildCustomizationPanel(type, item) {
            const s = item.styles || {};

            if (type === 'field') {
                return `<div class="settings-group">
                    <h4>Field Style</h4>
                    <div class="setting-field">
                        <label>Input Background</label>
                        <input type="color" id="ctrl_background_color" value="${s.background_color || '#ffffff'}">
                    </div>
                    <div class="setting-field">
                        <label>Border Color</label>
                        <input type="color" id="ctrl_border_color" value="${s.border_color || '#d1d5db'}">
                    </div>
                    <div class="setting-field">
                        <label>Text Color</label>
                        <input type="color" id="ctrl_text_color" value="${s.text_color || '#111827'}">
                    </div>
                    <div class="setting-field">
                        <label>Label Color</label>
                        <input type="color" id="ctrl_label_color" value="${s.label_color || '#374151'}">
                    </div>
                    <div class="setting-field">
                        <label>Focus Border Color</label>
                        <input type="color" id="ctrl_focus_color" value="${s.focus_color || '#6366f1'}">
                    </div>
                </div>`;
            }

            if (type === 'greeting') {
                return `<div class="settings-group">
                    <h4>Greeting Style</h4>
                    <div class="setting-field">
                        <label>Title Color</label>
                        <input type="color" id="ctrl_title_color" value="${s.title_color || '#1A2E05'}">
                    </div>
                    <div class="setting-field">
                        <label>Subtitle Color</label>
                        <input type="color" id="ctrl_subtitle_color" value="${s.subtitle_color || '#6B8064'}">
                    </div>
                    <div class="setting-field">
                        <label>Alignment</label>
                        <select id="ctrl_align">
                            <option value="left"   ${(s.align||'center')==='left'?'selected':''}>Left</option>
                            <option value="center" ${(s.align||'center')==='center'?'selected':''}>Center</option>
                            <option value="right"  ${(s.align||'center')==='right'?'selected':''}>Right</option>
                        </select>
                    </div>
                </div>`;
            }

            const bgVal = (type === 'main' || type === 'social_section') ? (item.bgColor || '#ffffff')
                        : (type === 'sub') ? (item.bgColor || '#f8f9fa') : '#ffffff';
            const borderDef = type === 'main' ? '#c7d2fe' : (type === 'sub' ? '#fbb6ce' : '#a5b4fc');
            const bwDef = s.border_width !== undefined ? s.border_width : 0;

            return `<div class="settings-group">
                <h4>Style</h4>
                <div class="setting-field">
                    <label>Background Color</label>
                    <input type="color" id="ctrl_bgColor" value="${bgVal}">
                </div>
                <div class="setting-field">
                    <label>Border Color</label>
                    <input type="color" id="ctrl_border_color" value="${s.border_color || borderDef}">
                </div>
                <div class="setting-field">
                    <label>Border Width</label>
                    <input type="range" id="ctrl_border_width" min="0" max="6" value="${bwDef}">
                    <span class="range-value" id="ctrl_border_width_lbl">${bwDef}px</span>
                </div>
                <div class="setting-field">
                    <label>Box Shadow</label>
                    <select id="ctrl_box_shadow">
                        <option value="none" ${(s.box_shadow||'none')==='none'?'selected':''}>None</option>
                        <option value="light" ${s.box_shadow==='light'?'selected':''}>Light</option>
                        <option value="medium" ${s.box_shadow==='medium'?'selected':''}>Medium</option>
                        <option value="strong" ${s.box_shadow==='strong'?'selected':''}>Strong</option>
                    </select>
                </div>
            </div>`;
        }

        // ── WIRE PANEL CONTROLS ───────────────────────────────
        bindPanelControls(id, type, item) {
            const self = this;

            $('#layoutContent, #customizationContent').off('.panel').on('input.panel change.panel', 'input, select', function() {
                const ctrlId  = this.id;                          // e.g. "ctrl_label"
                const prop    = ctrlId.replace(/^ctrl_/, '');     // e.g. "label"
                const isRange = this.type === 'range';
                const isCheck = this.type === 'checkbox';
                const val     = isCheck ? this.checked : this.value;

                // Update range display label
                if (isRange) {
                    const unit = (prop === 'width') ? '%' : 'px';
                    const label = (prop === 'min_height' && Number(val) === 0) ? 'Auto' : (val + unit);
                    $(`#${ctrlId}_lbl`).text(label);
                }

                self.applyPanelChange(id, type, item, prop, val);
            });
        }

        esc(v) { return String(v || '').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

        applyPanelChange(id, type, item, prop, value) {
            if (type === 'field') {
                if (prop === 'label') {
                    item.label = value;
                    this.refreshFieldDOM(id, item);
                } else if (prop === 'placeholder') {
                    item.placeholder = value;
                    this.refreshFieldDOM(id, item);
                } else if (prop === 'required') {
                    item.required = !!value;
                    this.refreshFieldDOM(id, item);
                } else if (prop === 'bgColor' || prop === 'background_color') {
                    item.styles = item.styles || {};
                    item.styles.background_color = value;
                    $(`.form-field[data-id="${id}"] input, .form-field[data-id="${id}"] textarea, .form-field[data-id="${id}"] select`).css('background', value);
                } else if (prop === 'border_color') {
                    item.styles = item.styles || {};
                    item.styles.border_color = value;
                    $(`.form-field[data-id="${id}"] input, .form-field[data-id="${id}"] textarea, .form-field[data-id="${id}"] select`).css('border-color', value);
                } else if (prop === 'text_color') {
                    item.styles = item.styles || {};
                    item.styles.text_color = value;
                    $(`.form-field[data-id="${id}"] input, .form-field[data-id="${id}"] textarea`).css('color', value);
                } else if (prop === 'label_color') {
                    item.styles = item.styles || {};
                    item.styles.label_color = value;
                    $(`.form-field[data-id="${id}"] .field-label-preview`).css('color', value);
                } else if (prop === 'focus_color') {
                    item.styles = item.styles || {};
                    item.styles.focus_color = value;
                } else if (prop === 'width') {
                    item.styles = item.styles || {};
                    item.styles.width = Number(value);
                    $(`.form-field[data-id="${id}"]`).css('width', value + '%');
                } else if (prop === 'margin_bottom') {
                    item.styles = item.styles || {};
                    item.styles.margin_bottom = Number(value);
                    $(`.form-field[data-id="${id}"]`).css('margin-bottom', value + 'px');
                } else if (prop === 'label_font_size') {
                    item.styles = item.styles || {};
                    item.styles.label_font_size = Number(value);
                    $(`.form-field[data-id="${id}"] .field-label-preview`).css('font-size', value + 'px');
                } else if (prop === 'input_padding') {
                    item.styles = item.styles || {};
                    item.styles.input_padding = Number(value);
                    $(`.form-field[data-id="${id}"] input, .form-field[data-id="${id}"] textarea`).css('padding', value + 'px');
                }

            } else if (type === 'greeting') {
                if (prop === 'title') {
                    item.title = value;
                    this.refreshGreetingDOM(id, item);
                } else if (prop === 'subtitle') {
                    item.subtitle = value;
                    this.refreshGreetingDOM(id, item);
                } else if (prop === 'title_color') {
                    item.styles = item.styles || {};
                    item.styles.title_color = value;
                    $(`.greeting-block[data-id="${id}"] .my-login-greeting-title`).css('color', value);
                } else if (prop === 'subtitle_color') {
                    item.styles = item.styles || {};
                    item.styles.subtitle_color = value;
                    $(`.greeting-block[data-id="${id}"] .my-login-greeting-subtitle`).css('color', value);
                } else if (prop === 'align') {
                    item.styles = item.styles || {};
                    item.styles.align = value;
                    $(`.greeting-block[data-id="${id}"]`).css('text-align', value);
                }

            } else {
                // Container (main / sub / social_section)
                const $el = $(`.main-container[data-id="${id}"], .sub-div[data-id="${id}"], .social-section[data-id="${id}"]`);

                if (prop === 'bgColor') {
                    item.bgColor = value;
                    $el.css('background', value);
                } else if (prop === 'width') {
                    item.styles = item.styles || {};
                    item.styles.width = Number(value);
                    $el.css('width', value + '%');
                } else if (prop === 'min_height') {
                    item.styles = item.styles || {};
                    item.styles.min_height = Number(value);
                    $el.css('min-height', Number(value) > 0 ? value + 'px' : '');
                } else if (prop === 'border_color') {
                    item.styles = item.styles || {};
                    item.styles.border_color = value;
                    $el.css('border-color', value);
                } else if (prop === 'border_width') {
                    item.styles = item.styles || {};
                    item.styles.border_width = Number(value);
                    $el.css({ 'border-width': value + 'px', 'border-style': Number(value) > 0 ? 'solid' : 'none' });
                } else if (prop === 'padding') {
                    item.styles = item.styles || {};
                    item.styles.padding = Number(value);
                    $el.css('padding', value + 'px');
                } else if (prop === 'border_radius') {
                    item.styles = item.styles || {};
                    item.styles.border_radius = Number(value);
                    $el.css('border-radius', value + 'px');
                } else if (prop === 'box_shadow') {
                    item.styles = item.styles || {};
                    item.styles.box_shadow = value;
                    const shadows = {
                        none:   'none',
                        light:  '0 2px 8px rgba(0,0,0,.08)',
                        medium: '0 4px 16px rgba(0,0,0,.14)',
                        strong: '0 8px 32px rgba(0,0,0,.22)',
                    };
                    $el.css('box-shadow', shadows[value] || 'none');
                }
            }
            this.autoSave();
        }

        refreshFieldDOM(id, item) {
            const $el = $(`.form-field[data-id="${id}"]`);
            const $preview = $el.find('.field-preview');
            const reqMk = item.required ? '<span style="color:#e53e3e">*</span>' : '';
            const labelHtml = (this.settings.show_labels && item.htmlType !== 'checkbox' && item.htmlType !== 'radio')
                ? `<label class="field-label-preview">${item.label}${reqMk}</label>` : '';
            $preview.html(labelHtml + T.inputHtml(item.htmlType, item.placeholder, item.required, item.label));
            // Reapply custom styles
            const s = item.styles || {};
            if (s.background_color) $el.find('input,textarea,select').css('background', s.background_color);
            if (s.border_color)     $el.find('input,textarea,select').css('border-color', s.border_color);
            if (s.text_color)       $el.find('input,textarea').css('color', s.text_color);
            if (s.label_color)      $el.find('.field-label-preview').css('color', s.label_color);
            if (s.label_font_size)  $el.find('.field-label-preview').css('font-size', s.label_font_size + 'px');
            if (s.input_padding)    $el.find('input,textarea').css('padding', s.input_padding + 'px');
        }

        refreshGreetingDOM(id, item) {
            const $el = $(`.greeting-block[data-id="${id}"]`);
            const $preview = $el.find('.greeting-preview');
            const titleHtml    = item.title    ? `<h2 class="my-login-greeting-title">${item.title}</h2>` : '';
            const subtitleHtml = item.subtitle ? `<p class="my-login-greeting-subtitle">${item.subtitle}</p>` : '';
            $preview.html(titleHtml + subtitleHtml);
            const s = item.styles || {};
            if (s.title_color)    $el.find('.my-login-greeting-title').css('color', s.title_color);
            if (s.subtitle_color) $el.find('.my-login-greeting-subtitle').css('color', s.subtitle_color);
            $el.css('text-align', s.align || 'center');
        }

        // Matches the gradient build_form_css() generates for .my-login-submit-btn
        // on the live site, so the canvas button never falls back to a flat fill.
        applyButtonStyle(btnColor, buttonText) {
            $('.preview-submit-btn').css({
                background: `linear-gradient(135deg,${btnColor} 0%,#0F5900 100%)`,
                'box-shadow': `0 4px 14px ${btnColor}59`,
            }).text(buttonText);
        }

        // ── SETTINGS ──────────────────────────────────────────
        applySettingsUI() {
            $('#btnColor').val(this.settings.btn_color || '#1FBB00');
            $('#buttonText').val(this.settings.button_text || 'Submit');
            $('#showLabels').prop('checked', !!this.settings.show_labels);
            $('#redirectAfterLogin').val(this.settings.redirect_after_login || 'home');
            $('#redirectAfterRegistration').val(this.settings.redirect_after_registration || 'home');
            $('#customRedirectUrl').val(this.settings.custom_redirect_url || '');
            this.toggleCustomRedirectField();
            this.applyButtonStyle(this.settings.btn_color || '#1FBB00', this.settings.button_text);
        }

        toggleCustomRedirectField() {
            const needsCustom = $('#redirectAfterLogin').val() === 'custom' || $('#redirectAfterRegistration').val() === 'custom';
            $('#customRedirectUrlField').toggle(needsCustom);
        }

        updateSettings() {
            this.settings.btn_color                  = $('#btnColor').val();
            this.settings.button_text                = $('#buttonText').val();
            this.settings.show_labels                = $('#showLabels').is(':checked');
            this.settings.redirect_after_login        = $('#redirectAfterLogin').val();
            this.settings.redirect_after_registration = $('#redirectAfterRegistration').val();
            this.settings.custom_redirect_url         = $('#customRedirectUrl').val();
            this.toggleCustomRedirectField();
            this.applyButtonStyle(this.settings.btn_color, this.settings.button_text);
            this.autoSave();
        }

        // ── SAVE ──────────────────────────────────────────────
        autoSave() {
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.save(), 700);
        }

        save() {
            if (!this.currentFormId) return;
            this.indicator('saving');
            $.ajax({
                url: MyLoginDesigner.ajax_url, type: 'POST',
                data: {
                    action:    'my_login_save_form_settings',
                    nonce:     MyLoginDesigner.nonces.save_form,
                    form_data: JSON.stringify({
                        form_id:     this.currentFormId,
                        containers:  this.containers,
                        settings:    this.settings,
                        css_content: this.getCssValue(),
                        js_content:  this.getJsValue(),
                    })
                },
                success: (r) => {
                    if (r.success) { this.indicator('saved'); this.saveHTML(); }
                    else this.indicator('error');
                },
                error: () => this.indicator('error')
            });
        }

        saveCSS() {
            if (!this.currentFormId) { alert(MyLoginDesigner.strings.select_form); return; }
            $.ajax({
                url: MyLoginDesigner.ajax_url, type: 'POST',
                data: {
                    action:      'my_login_save_form_css',
                    nonce:       MyLoginDesigner.nonces.save_css,
                    form_id:     this.currentFormId,
                    form_key:    this.currentFormKey,
                    css_content: this.getCssValue(),
                },
                success: (r) => alert(r.success ? MyLoginDesigner.strings.css_saved : 'CSS save failed')
            });
        }

        saveJS() {
            if (!this.currentFormId) { alert(MyLoginDesigner.strings.select_form); return; }
            $.ajax({
                url: MyLoginDesigner.ajax_url, type: 'POST',
                data: {
                    action:     'my_login_save_form_js',
                    nonce:      MyLoginDesigner.nonces.save_js,
                    form_id:    this.currentFormId,
                    form_key:   this.currentFormKey,
                    js_content: this.getJsValue(),
                },
                success: (r) => alert(r.success ? MyLoginDesigner.strings.js_saved : 'JS save failed')
            });
        }

        saveHTML() {
            if (!this.currentFormId) return;
            $.ajax({
                url: MyLoginDesigner.ajax_url, type: 'POST',
                data: {
                    action:   'my_login_save_form_html',
                    nonce:    MyLoginDesigner.nonces.save_html,
                    form_id:  this.currentFormId,
                    form_key: this.currentFormKey,
                    content:  this.buildFrontendHTML(),
                }
            });
        }

        indicator(state) {
            const $el = $('#saveIndicator');
            $el.removeClass('saving saved error').addClass(state);
            $el.text({ saving: '⏳ Saving…', saved: '✅ Saved', error: '❌ Error' }[state] || '');
            if (state === 'saved') setTimeout(() => $el.removeClass('saved').text(''), 2500);
        }

        // ── FRONTEND HTML GENERATOR ───────────────────────────
        buildFrontendHTML() {
            let out = '';
            for (const id in this.containers) out += this.buildContainerHTML(this.containers[id]);
            return out;
        }

        buildContainerHTML(c) {
            const s   = c.styles || {};
            const bg  = c.bgColor || '#fff';
            const pad = (s.padding !== undefined ? s.padding : 28) + 'px';
            const rad = (s.border_radius !== undefined ? s.border_radius : 12) + 'px';
            const bw  = (s.border_width || 0) + 'px';
            const bc  = s.border_color || 'transparent';
            const shadow = { light: '0 2px 8px rgba(0,0,0,.08)', medium: '0 4px 16px rgba(0,0,0,.14)', strong: '0 8px 32px rgba(0,0,0,.22)' }[s.box_shadow] || 'none';
            const items = (c.items || []).map(i => this.buildItemHTML(i)).join('');
            return `<div class="my-login-main-container" style="background:${bg};padding:${pad};border-radius:${rad};border:${bw} solid ${bc};box-shadow:${shadow};margin-bottom:20px;">
                ${items}
                <div class="my-login-form-submit" style="margin-top:16px;text-align:center;">
                    <button type="submit" class="my-login-submit-btn">${this.settings.button_text}</button>
                </div>
            </div>`;
        }

        buildItemHTML(item) {
            if (item.type === 'field') return this.buildFieldHTML(item);
            if (item.type === 'sub') {
                const s   = item.styles || {};
                const bg  = item.bgColor || '#f8f9fa';
                const pad = (s.padding !== undefined ? s.padding : 12) + 'px';
                const rad = (s.border_radius !== undefined ? s.border_radius : 8) + 'px';
                const bw  = (s.border_width || 0) + 'px';
                const bc  = s.border_color || 'transparent';
                const kids = (item.items || []).map(i => this.buildItemHTML(i)).join('');
                return `<div class="my-login-sub-div" style="background:${bg};padding:${pad};border-radius:${rad};border:${bw} solid ${bc};margin:10px 0;">${kids}</div>`;
            }
            if (item.type === 'social_section') return this.buildSocialSectionHTML(item);
            if (item.type === 'greeting') return this.buildGreetingHTML(item);
            return '';
        }

        buildGreetingHTML(item) {
            const s = item.styles || {};
            const alignStyle = ` style="text-align:${s.align || 'center'}"`;
            const titleStyle    = s.title_color    ? ` style="color:${s.title_color}"` : '';
            const subtitleStyle = s.subtitle_color ? ` style="color:${s.subtitle_color}"` : '';
            let inner = '';
            if (item.title)    inner += `<h2 class="my-login-greeting-title"${titleStyle}>${item.title}</h2>`;
            if (item.subtitle) inner += `<p class="my-login-greeting-subtitle"${subtitleStyle}>${item.subtitle}</p>`;
            if (!inner) return '';
            return `<div class="my-login-greeting"${alignStyle}>${inner}</div>`;
        }

        buildFieldHTML(f) {
            const req   = f.required ? ' required' : '';
            const reqMk = f.required ? '<span class="my-login-req">*</span>' : '';
            const show  = this.settings.show_labels;
            const isChk = f.htmlType === 'checkbox';
            const isRad = f.htmlType === 'radio';
            const s     = f.styles || {};
            const uid   = 'my_login_' + f.id;

            const inputStyle = [
                s.background_color ? `background:${s.background_color}` : '',
                s.border_color     ? `border-color:${s.border_color}` : '',
                s.text_color       ? `color:${s.text_color}` : '',
                s.input_padding    ? `padding:${s.input_padding}px` : '',
            ].filter(Boolean).join(';');

            const labelStyle = [
                s.label_color     ? `color:${s.label_color}` : '',
                s.label_font_size ? `font-size:${s.label_font_size}px` : '',
            ].filter(Boolean).join(';');

            const wrapStyle = [
                s.width         ? `width:${s.width}%` : '',
                s.margin_bottom ? `margin-bottom:${s.margin_bottom}px` : '',
            ].filter(Boolean).join(';');

            let inner = '';
            if (isChk || isRad) {
                inner = `<label><input type="${f.htmlType}" name="${f.fieldType}" id="${uid}"${req}> ${f.label}${reqMk}</label>`;
            } else if (f.htmlType === 'textarea') {
                const lbl = show ? `<label for="${uid}" class="my-login-label"${labelStyle ? ` style="${labelStyle}"` : ''}>${f.label}${reqMk}</label>` : '';
                inner = lbl + `<textarea id="${uid}" name="${f.fieldType}" placeholder="${f.placeholder||''}" rows="4"${req}${inputStyle ? ` style="${inputStyle}"` : ''}></textarea>`;
            } else if (f.htmlType === 'select') {
                const lbl = show ? `<label for="${uid}" class="my-login-label"${labelStyle ? ` style="${labelStyle}"` : ''}>${f.label}${reqMk}</label>` : '';
                inner = lbl + `<select id="${uid}" name="${f.fieldType}"${req}${inputStyle ? ` style="${inputStyle}"` : ''}><option value="">Select…</option></select>`;
            } else {
                const lbl = show ? `<label for="${uid}" class="my-login-label"${labelStyle ? ` style="${labelStyle}"` : ''}>${f.label}${reqMk}</label>` : '';
                inner = lbl + `<input type="${f.htmlType}" id="${uid}" name="${f.fieldType}" placeholder="${f.placeholder||''}"${req}${inputStyle ? ` style="${inputStyle}"` : ''}>`;
            }
            return `<div class="my-login-form-field"${wrapStyle ? ` style="${wrapStyle}"` : ''}>${inner}</div>`;
        }

        buildSocialSectionHTML(item) {
            const btns = (item.socialButtons || []).map(b => {
                const p = MyLoginDesigner.social_providers[b.provider] || MyLoginDesigner.all_social_providers[b.provider];
                if (!p) return '';
                const iconHtml = p.icon ? `<i class="${p.icon}"></i> ` : '';
                return `<button type="button" class="my-login-social-btn" data-provider="${b.provider}"
                                style="background:${p.color};color:#fff;padding:9px 18px;border:none;
                                       border-radius:6px;cursor:pointer;margin:4px;font-size:13px;
                                       display:inline-flex;align-items:center;gap:8px;">
                            ${iconHtml}${p.label}
                        </button>`;
            }).join('');
            if (!btns) return '';
            return `<div class="my-login-social-section" style="text-align:center;padding:14px 0;border-top:1px solid #f0f0f0;margin-top:10px;">
                <div class="my-login-social-divider" style="font-size:12px;color:#999;margin-bottom:10px;">— or continue with —</div>
                <div class="my-login-social-buttons">${btns}</div>
            </div>`;
        }

        // ── EVENT BINDINGS ─────────────────────────────────────
        bind() {
            const self = this;

            // Palette draggables
            $(document).on('mouseenter', '.draggable-field', function() {
                if (!$(this).hasClass('ui-draggable')) {
                    $(this).draggable({
                        helper: 'clone', appendTo: 'body', zIndex: 9999,
                        opacity: 0.85, cursor: 'grabbing', revert: 'invalid',
                        start: (e, ui) => {
                            ui.helper.css({ width: $(this).outerWidth(), padding: '8px 12px',
                                background: '#0073aa', color: '#fff', borderRadius: '6px' });
                        }
                    });
                }
            });

            // Form list click
            $('#formList').on('click', '.form-item', function(e) {
                if ($(e.target).closest('.form-actions').length) return;
                const fid = $(this).data('form-id');
                self.highlightFormItem(fid);
                self.loadForm(fid);
            });

            // Copy shortcode
            $('#formList').on('click', '.copy-shortcode', function(e) {
                e.stopPropagation();
                const fid = $(this).data('form-id');
                navigator.clipboard.writeText(`[my_login_form id="${fid}"]`).then(() => {
                    const $b = $(this); const orig = $b.text();
                    $b.text('✅'); setTimeout(() => $b.text(orig), 1500);
                });
            });

            // Delete / duplicate
            $('#formList').on('click', '.delete-form', function(e) {
                e.stopPropagation();
                if (!confirm(MyLoginDesigner.strings.delete_confirm)) return;
                $.post(MyLoginDesigner.ajax_url, {
                    action: 'my_login_delete_form', form_id: $(this).data('form-id'),
                    nonce: MyLoginDesigner.nonces.delete_form
                }, r => { if (r.success) location.reload(); else alert('Delete failed'); });
            });
            $('#formList').on('click', '.duplicate-form', function(e) {
                e.stopPropagation();
                if (!confirm(MyLoginDesigner.strings.duplicate_confirm)) return;
                $.post(MyLoginDesigner.ajax_url, {
                    action: 'my_login_duplicate_form', form_id: $(this).data('form-id'),
                    nonce: MyLoginDesigner.nonces.duplicate_form
                }, r => { if (r.success) location.reload(); else alert('Duplicate failed'); });
            });

            // Remove element
            $(document).on('click', '.btn-remove-element', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                if (!confirm('Remove this element?')) return;
                if (self.selectedId === id) self.clearSelection();
                self.removeById(id);
                $(this).closest('.main-container,.element-item').remove();
                if (!$('#formBuilder .main-container').length) self.renderEmpty();
                self.autoSave();
            });

            // Edit element — opens Layout/Customization tabs (no popup)
            $(document).on('click', '.btn-edit-element', function(e) {
                e.stopPropagation();
                const id   = $(this).data('id');
                const type = $(this).data('etype');
                self.selectElement(id, type);
            });

            // Click outside to deselect (click on builder background)
            $('#formBuilder').on('click', function(e) {
                if ($(e.target).is('#formBuilder') || $(e.target).hasClass('builder-empty')) {
                    self.clearSelection();
                }
            });

            // Clear selection button
            $('#clearSelectionBtn').on('click', () => this.clearSelection());

            // Save / Clear
            $('#saveFormBtn').on('click', () => this.save());
            $('#saveCSSBtn').on('click',  () => this.saveCSS());
            $('#saveJSBtn').on('click',   () => this.saveJS());
            $('#clearAllBtn').on('click', () => {
                if (!confirm(MyLoginDesigner.strings.clear_confirm)) return;
                this.containers = {};
                this.clearSelection();
                this.renderEmpty();
                this.autoSave();
            });

            // Settings changes
            $('#btnColor,#buttonText,#showLabels,#redirectAfterLogin,#redirectAfterRegistration,#customRedirectUrl').on('change input', () => this.updateSettings());

            // Create new form
            $('#createNewFormBtn').on('click', () => $('#createFormModal').show());
            $('#createFormSubmitBtn').on('click', () => {
                const name = $('#newFormName').val().trim();
                if (!name) { alert('Enter a form name'); return; }
                const $btn = $('#createFormSubmitBtn').prop('disabled', true).text('Creating…');
                $.post(MyLoginDesigner.ajax_url, {
                    action: 'my_login_create_form',
                    form_name: name,
                    form_type: $('#newFormType').val(),
                    nonce: MyLoginDesigner.nonces.create_form
                }, r => {
                    if (r.success) location.reload();
                    else { alert('Could not create form: ' + (r.data || '')); $btn.prop('disabled', false).text('Create'); }
                });
            });

            // Preset buttons
            $(document).on('click', '.preset-btn', function() {
                const preset = $(this).data('preset');
                const presets = {
                    modern:  { btn_color: '#6366f1', button_text: 'Continue', show_labels: true },
                    minimal: { btn_color: '#111827', button_text: 'Submit',   show_labels: false },
                    dark:    { btn_color: '#0ea5e9', button_text: 'Sign In',  show_labels: true },
                };
                if (presets[preset]) {
                    self.settings = { ...self.settings, ...presets[preset] };
                    self.applySettingsUI();
                    self.autoSave();
                }
            });

            // Modal close
            $(document).on('click', '.close-modal,.cancel-modal', () => $('.my-login-modal,.modal').hide());
            $(window).on('click', e => { if ($(e.target).hasClass('my-login-modal') || $(e.target).hasClass('modal')) $(e.target).hide(); });
        }

        initTabs() {
            const self = this;
            $('.tab-btn').on('click', function() {
                const tab = $(this).data('tab');
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').removeClass('active');
                $(`#tab-${tab}`).addClass('active');

                // CodeMirror is initialized once at page load, while every tab
                // but "Layout" is still display:none — it renders at zero
                // height in a hidden container, so it must be told to
                // re-measure itself the moment its tab actually becomes
                // visible. Without this the CSS/JS editors are unusable.
                if (tab === 'css' && self.cssEditor) self.cssEditor.codemirror.refresh();
                if (tab === 'js'  && self.jsEditor)  self.jsEditor.codemirror.refresh();
            });
        }

        initModals() {}
    }

    // ── BOOT ──────────────────────────────────────────────────
    $(document).ready(() => { window.mlBuilder = new FormBuilder(); });

})(jQuery);
