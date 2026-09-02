jQuery(document).ready(function($) {
    var mlf_ajax = {
        nonce: myLoginFormAjax.nonce,
        ajax_url: myLoginFormAjax.ajax_url
    };

    // Clear cache action
    window.clearPluginCache = function() {
        if (!confirm('Clear the plugin cache?')) {
            return;
        }

        showLoading(true);

        $.ajax({
            url: mlf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mlf_clear_cache',
                nonce: mlf_ajax.nonce
            },
            success: function(response) {
                showLoading(false);
                if (response.success) {
                    showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice('error', response.data.message || response.data);
                }
            },
            error: function() {
                showLoading(false);
                showNotice('error', 'Something went wrong. Please try again.');
            }
        });
    };

    // Reset settings action
    window.resetSettings = function() {
        if (!confirm('Reset all settings to their default values? This cannot be undone.')) {
            return;
        }

        showLoading(true);

        $.ajax({
            url: mlf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mlf_reset_settings',
                nonce: mlf_ajax.nonce,
                confirm: 'yes'
            },
            success: function(response) {
                showLoading(false);
                if (response.success) {
                    showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice('error', response.data.message || response.data);
                }
            },
            error: function() {
                showLoading(false);
                showNotice('error', 'Something went wrong. Please try again.');
            }
        });
    };

    // Export settings action
    window.exportSettings = function() {
        $.ajax({
            url: mlf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mlf_export_settings',
                nonce: mlf_ajax.nonce
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(response) {
                var blob = new Blob([response], {type: 'application/json'});
                var link = document.createElement('a');
                var url = URL.createObjectURL(blob);
                var filename = 'my-login-form-settings-' + new Date().toISOString().slice(0, 10) + '.json';

                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                showNotice('success', 'Settings exported successfully.');
            },
            error: function() {
                showNotice('error', 'Failed to export settings.');
            }
        });
    };

    // Import settings action
    $('#import-file').on('change', function() {
        var file = this.files[0];
        if (!file) {
            return;
        }

        if (file.type !== 'application/json') {
            showNotice('error', 'Please choose a valid JSON file.');
            this.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showNotice('error', 'File is too large (max 2MB).');
            this.value = '';
            return;
        }

        if (!confirm('Import settings from this file? Existing settings will be overwritten.')) {
            this.value = '';
            return;
        }

        showLoading(true);

        var formData = new FormData();
        formData.append('action', 'mlf_import_settings');
        formData.append('nonce', mlf_ajax.nonce);
        formData.append('import_file', file);

        $.ajax({
            url: mlf_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                showLoading(false);
                if (response.success) {
                    showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice('error', response.data.message || response.data);
                }
                $('#import-file').val('');
            },
            error: function() {
                showLoading(false);
                showNotice('error', 'Failed to import settings.');
                $('#import-file').val('');
            }
        });
    });

    // Helper: Show loading state
    function showLoading(show) {
        if (show) {
            $('body').append('<div id="mlf-loading" class="mlf-loading-overlay"><div class="mlf-loading-spinner"></div></div>');
        } else {
            $('#mlf-loading').remove();
        }
    }

    // Helper: Show notice message
    function showNotice(type, message, duration) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p></p></div>');
        notice.find('p').text(message);

        $('.wrap.my-login-form-settings').before(notice);

        if (duration) {
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, duration);
        }

        notice.on('click', '.notice-dismiss', function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    // ── CODE EDITORS (Custom CSS / JS) ─────────────────────
    // Same wp.codeEditor component used on the Designer page. Falls back to
    // the plain <textarea> if the user disabled syntax highlighting in their
    // profile (wp_enqueue_code_editor() then returns false).
    var cssEditor, jsEditor;

    if (typeof wp !== 'undefined' && wp.codeEditor) {
        if (myLoginFormAjax.cssEditorSettings && document.getElementById('custom_css')) {
            cssEditor = wp.codeEditor.initialize(document.getElementById('custom_css'), myLoginFormAjax.cssEditorSettings);
        }
        if (myLoginFormAjax.jsEditorSettings && document.getElementById('custom_js')) {
            jsEditor = wp.codeEditor.initialize(document.getElementById('custom_js'), myLoginFormAjax.jsEditorSettings);
        }
    }

    // CodeMirror owns the keystrokes once initialized, so the underlying
    // <textarea> never updates on its own — sync it back before the form posts.
    $('#my-login-form-settings-form').on('submit', function() {
        if (cssEditor) { cssEditor.codemirror.save(); }
        if (jsEditor) { jsEditor.codemirror.save(); }
    });
});
