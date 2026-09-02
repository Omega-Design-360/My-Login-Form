// Select all checkboxes
document.getElementById('select-all')?.addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Apply bulk action
function applyBulkAction() {
    const action = document.getElementById('bulk_action_select').value;
    if (action === '-1') {
        alert('Please select an action');
        return;
    }

    // Sync / Create WP Users operate on all pending users (same as their
    // dedicated toolbar buttons) rather than the checkbox selection, since
    // that's what their AJAX endpoints support.
    if (action === 'sync') {
        syncWithSupabase();
        return;
    }

    if (action === 'create_wp') {
        createWordPressUsers();
        return;
    }

    // action === 'delete'
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one user');
        return;
    }

    if (!confirm('Are you sure you want to delete selected users? This action cannot be undone.')) {
        return;
    }

    const userIds = Array.from(checkboxes).map(cb => cb.value);
    const source = checkboxes[0].dataset.source || 'plugin';

    const applyBtn = document.querySelector('.bulkactions .button.action');
    const originalText = applyBtn ? applyBtn.innerHTML : '';
    if (applyBtn) {
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    }

    const params = new URLSearchParams();
    params.append('nonce', myLoginFormUsersData.nonce);
    params.append('source', source);
    userIds.forEach(id => params.append('user_ids[]', id));

    fetch(myLoginFormUsersData.ajaxUrl + '?action=my_login_form_bulk_delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.data && data.data.message ? data.data.message : data.data));
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
    })
    .finally(() => {
        if (applyBtn) {
            applyBtn.disabled = false;
            applyBtn.innerHTML = originalText;
        }
    });
}

// Export CSV
function exportCSV() {
    window.location.href = myLoginFormUsersData.ajaxUrl + '?action=my_login_form_export_csv&nonce=' + encodeURIComponent(myLoginFormUsersData.nonce);
}

// Sync with Supabase
function syncWithSupabase() {
    if (!confirm('Sync all pending users with Supabase?')) return;

    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
    button.disabled = true;

    fetch(myLoginFormUsersData.ajaxUrl + '?action=my_login_form_sync_supabase', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            nonce: myLoginFormUsersData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.data && data.data.message ? data.data.message : data.data));
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Create WordPress Users
function createWordPressUsers() {
    if (!confirm('Create WordPress user accounts for all registered users?')) return;

    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    button.disabled = true;

    fetch(myLoginFormUsersData.ajaxUrl + '?action=my_login_form_create_wp_users', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            nonce: myLoginFormUsersData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.data && data.data.message ? data.data.message : data.data));
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// View User Details
function viewUser(userId, source) {
    const modal = document.getElementById('userDetailsModal');
    const content = document.getElementById('userDetailsContent');

    modal.style.display = 'flex';
    content.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading user details...</div>';

    fetch(myLoginFormUsersData.ajaxUrl + '?action=my_login_form_get_user', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            user_id: userId,
            source: source || 'plugin',
            nonce: myLoginFormUsersData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const user = data.data;
            let html = `
            <div class="user-details-grid">
                <div class="details-section">
                    <h3><i class="fas fa-user-circle"></i> Personal Information</h3>
                    <table class="details-table">
                        <tr><td class="label">Full Name:</td><td class="value">${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)}</td></tr>
                        <tr><td class="label">Email:</td><td class="value">${escapeHtml(user.email)}</td></tr>
                        <tr><td class="label">Phone:</td><td class="value">${escapeHtml(user.phone || '—')}</td></tr>
                        <tr><td class="label">Gender:</td><td class="value">${escapeHtml(user.gender || '—')}</td></tr>
                        <tr><td class="label">Date of Birth:</td><td class="value">${escapeHtml(user.dob || '—')}</td></tr>
                        <tr><td class="label">Country:</td><td class="value">${escapeHtml(user.country || '—')}</td></tr>
                        <tr><td class="label">City:</td><td class="value">${escapeHtml(user.city || '—')}</td></tr>
                        <tr><td class="label">Address:</td><td class="value">${escapeHtml(user.address || '—')}</td></tr>
                    </table>
                </div>

                <div class="details-section">
                    <h3><i class="fas fa-chart-line"></i> Account Information</h3>
                    <table class="details-table">
                        <tr><td class="label">Registered:</td><td class="value">${user.created_at ? new Date(user.created_at).toLocaleString() : '—'}</td></tr>
                        <tr><td class="label">Last Updated:</td><td class="value">${user.updated_at ? new Date(user.updated_at).toLocaleString() : '—'}</td></tr>
                        <tr><td class="label">Last Login:</td><td class="value">${user.last_login ? new Date(user.last_login).toLocaleString() : '—'}</td></tr>
                        <tr><td class="label">Login Count:</td><td class="value">${user.login_count || 0}</td></tr>
                        <tr><td class="label">Email Verified:</td><td class="value">${user.email_verified ? '✅ Yes' : '❌ No'}</td></tr>
                        <tr><td class="label">Phone Verified:</td><td class="value">${user.phone_verified ? '✅ Yes' : '❌ No'}</td></tr>
                    </table>
                </div>

                <div class="details-section">
                    <h3><i class="fas fa-plug"></i> Integrations</h3>
                    <table class="details-table">
                        <tr><td class="label">WordPress User:</td><td class="value">${user.wp_user_id ? '✓ ID: ' + user.wp_user_id : '✗ Not created'}</td></tr>
                        <tr><td class="label">Supabase Sync:</td><td class="value">${user.supabase_uid ? '✓ Synced (UID: ' + user.supabase_uid.substring(0, 20) + '...)' : '✗ Not synced'}</td></tr>
                        <tr><td class="label">Social Login:</td><td class="value">${user.social_provider ? user.social_provider.charAt(0).toUpperCase() + user.social_provider.slice(1) + ' (ID: ' + (user.social_id ? user.social_id.substring(0, 20) + '...' : 'N/A') + ')' : '✗ Email registration'}</td></tr>
                        <tr><td class="label">Profile Picture:</td><td class="value">${user.profile_picture ? '<a href="' + escapeHtml(user.profile_picture) + '" target="_blank">View Image</a>' : '—'}</td></tr>
                    </table>
                </div>
            </div>
            `;
            content.innerHTML = html;
        } else {
            content.innerHTML = '<p style="color: #dc3545;">Error loading user details</p>';
        }
    })
    .catch(error => {
        content.innerHTML = '<p style="color: #dc3545;">Network error</p>';
    });
}

// Edit User
function editUser(userId, source) {
    const modal = document.getElementById('userEditModal');

    document.getElementById('editUserId').value = userId;
    document.getElementById('editUserSource').value = source || 'plugin';
    document.getElementById('editFirstName').value = '';
    document.getElementById('editLastName').value = '';
    document.getElementById('editEmail').value = '';
    document.getElementById('editPhone').value = '';

    modal.style.display = 'flex';

    fetch(myLoginFormUsersData.ajaxUrl + '?action=my_login_form_get_user', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            user_id: userId,
            source: source || 'plugin',
            nonce: myLoginFormUsersData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const user = data.data;
            document.getElementById('editFirstName').value = user.first_name || '';
            document.getElementById('editLastName').value = user.last_name || '';
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editPhone').value = user.phone || '';
        } else {
            alert('Error loading user details');
            closeUserEditModal();
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
        closeUserEditModal();
    });
}

// Save edited user
function saveUserEdit() {
    const userId = document.getElementById('editUserId').value;
    const source = document.getElementById('editUserSource').value;
    const email = document.getElementById('editEmail').value.trim();

    if (!email) {
        alert('Email is required');
        return;
    }

    fetch(myLoginFormUsersData.ajaxUrl + '?action=my_login_form_update_user', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            user_id: userId,
            source: source,
            first_name: document.getElementById('editFirstName').value,
            last_name: document.getElementById('editLastName').value,
            email: email,
            phone: document.getElementById('editPhone').value,
            nonce: myLoginFormUsersData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.data && data.data.message ? data.data.message : data.data));
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
    });
}

// Close user edit modal
function closeUserEditModal() {
    document.getElementById('userEditModal').style.display = 'none';
}

// Delete User
function deleteUser(userId, source) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return;
    }

    fetch(myLoginFormUsersData.ajaxUrl + '?action=my_login_form_delete_user', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            user_id: userId,
            source: source || 'plugin',
            nonce: myLoginFormUsersData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User deleted successfully');
            location.reload();
        } else {
            alert('Error: ' + (data.data && data.data.message ? data.data.message : data.data));
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
    });
}

// Close user modal
function closeUserModal() {
    document.getElementById('userDetailsModal').style.display = 'none';
}

// Close modal on background click
document.getElementById('userDetailsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserModal();
    }
});

document.getElementById('userEditModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserEditModal();
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUserModal();
        closeUserEditModal();
    }
});

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
