// Copy shortcode to clipboard
console.log('Dashboard JS loaded');
function copyShortcode(element) {
    const shortcode = element.textContent;
    navigator.clipboard.writeText(shortcode).then(() => {
        const originalText = element.textContent;
        element.textContent = '✓ Copied!';
        setTimeout(() => {
            element.textContent = originalText;
        }, 1500);
    });
}

// Initialize chart
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('registrations-chart');
    if (canvas && typeof Chart !== 'undefined') {
        const ctx = canvas.getContext('2d');
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Registrations',
                    data: values,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});

// Refresh system status
function refreshSystemStatus() {
    const button = document.querySelector('.card-header .button');
    if (!button) return;
    
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    button.disabled = true;
    
    location.reload();
}


/**
 * My Login Form Dashboard JavaScript
 */

(function($) {
    'use strict';

    let dashboardChart = null;
    let refreshInterval = null;

    /**
     * Initialize Dashboard
     */
    function initDashboard() {
        initChart();
        initRefreshButton();
        initShortcodeCopy();
        
        // Auto-refresh every 60 seconds (optional)
        // startAutoRefresh();
    }

    /**
     * Initialize Chart
     */
    function initChart() {
        const canvas = document.getElementById('registrations-chart');
        if (!canvas) return;

        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');

        if (dashboardChart) {
            dashboardChart.destroy();
        }

        dashboardChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: myLoginFormDashboard.i18n.registrations || 'Registrations',
                    data: values,
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#0073aa',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: myLoginFormDashboard.i18n.numberOfRegistrations || 'Number of Registrations'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: myLoginFormDashboard.i18n.date || 'Date'
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize Refresh Button
     */
    function initRefreshButton() {
        $('.refresh-dashboard, .refresh-status').on('click', function() {
            refreshAllDashboardData();
        });
    }

    /**
     * Initialize Shortcode Copy
     */
    function initShortcodeCopy() {
        $(document).on('click', '.shortcode', function() {
            const $this = $(this);
            const text = $this.text();
            
            navigator.clipboard.writeText(text).then(() => {
                const originalText = $this.text();
                $this.text(myLoginFormDashboard.i18n.copied || 'Copied!');
                setTimeout(() => {
                    $this.text(originalText);
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                
                const originalText = $this.text();
                $this.text(myLoginFormDashboard.i18n.copied || 'Copied!');
                setTimeout(() => {
                    $this.text(originalText);
                }, 2000);
            });
        });
    }

    /**
     * Refresh All Dashboard Data
     */
    function refreshAllDashboardData() {
        const $button = $('.refresh-dashboard');
        const originalText = $button.html();
        
        $button.html('<i class="fas fa-spinner fa-spin"></i> ' + (myLoginFormDashboard.i18n.refreshing || 'Refreshing...')).prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'my_login_dashboard_refresh_all',
                nonce: $('.my-login-form-dashboard').data('nonce')
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardStats(response.data.stats);
                    updateRecentUsers(response.data.recent_users);
                    updateRecentForms(response.data.recent_forms);
                    updateChartData(response.data.chart_data);
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message);
                }
                $button.html(originalText).prop('disabled', false);
            },
            error: function() {
                showNotice('error', myLoginFormDashboard.i18n.refreshFailed || 'Failed to refresh dashboard');
                $button.html(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Update Dashboard Stats
     */
    function updateDashboardStats(stats) {
        if (!stats) return;
        
        $('.stat-total-forms').text(formatNumber(stats.total_forms || 0));
        $('.stat-total-users').text(formatNumber(stats.total_users || 0));
        $('.stat-active-users').text(formatNumber(stats.active_users || 0));
        $('.stat-social-users').text(formatNumber(stats.social_users || 0));
        
        // Update form types list if needed
        if (stats.form_types) {
            updateFormTypesList(stats.form_types);
        }
    }

    /**
     * Update Recent Users Table
     */
    function updateRecentUsers(users) {
        if (!users || users.length === 0) {
            $('.recent-users-table tbody').html('<tr><td colspan="4">' + (myLoginFormDashboard.i18n.noUsers || 'No users found') + '</td></tr>');
            return;
        }
        
        let html = '';
        users.forEach(function(user) {
            const userName = user.user_first_name && user.user_last_name 
                ? user.user_first_name + ' ' + user.user_last_name 
                : (user.user_email ? user.user_email.split('@')[0] : 'Anonymous');
            const statusBadge = user.email_verified 
                ? '<span class="badge badge-success">' + (myLoginFormDashboard.i18n.verified || 'Verified') + '</span>'
                : '<span class="badge badge-warning">' + (myLoginFormDashboard.i18n.pending || 'Pending') + '</span>';
            
            html += '<tr>' +
                '<td>' +
                    '<div class="user-info">' +
                        '<div class="user-avatar">' + getAvatarHtml(user.user_email) + '</div>' +
                        '<div>' +
                            '<div class="user-name">' + escapeHtml(userName) + '</div>' +
                            '<div class="user-id">ID: ' + user.id + '</div>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '<td>' + escapeHtml(user.user_email) + '</td>' +
                '<td>' + formatDate(user.created_at) + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '</tr>';
        });
        $('.recent-users-table tbody').html(html);
    }

    /**
     * Update Recent Forms Table
     */
    function updateRecentForms(forms) {
        if (!forms || forms.length === 0) {
            $('.recent-forms-table tbody').html('<tr><td colspan="4">' + (myLoginFormDashboard.i18n.noForms || 'No forms found') + '</td></tr>');
            return;
        }
        
        let html = '';
        forms.forEach(function(form) {
            const icon = getFormIcon(form.form_type);
            const typeName = form.form_type.replace('_', ' ');
            
            html += '<tr>' +
                '<td>' +
                    '<div class="form-info">' +
                        '<span class="form-icon">' + icon + '</span>' +
                        '<div>' +
                            '<div class="form-name">' + escapeHtml(form.name) + '</div>' +
                            '<div class="form-key">' + escapeHtml(form.form_key) + '</div>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '<td><span class="badge badge-info">' + escapeHtml(typeName) + '</span></td>' +
                '<td><code class="shortcode">[my_login_form id="' + form.id + '"]</code></td>' +
                '<td>' + formatNumber(form.views_count || 0) + '</td>' +
                '</tr>';
        });
        $('.recent-forms-table tbody').html(html);
        
        // Re-initialize shortcode copy for new elements
        initShortcodeCopy();
    }

    /**
     * Update Chart Data
     */
    function updateChartData(chartData) {
        if (!dashboardChart || !chartData) return;
        
        dashboardChart.data.labels = chartData.labels || [];
        dashboardChart.data.datasets[0].data = chartData.data || [];
        dashboardChart.update();
    }

    /**
     * Update Form Types List
     */
    function updateFormTypesList(formTypes) {
        const $container = $('.form-types-list');
        if (!$container.length) return;
        
        if (Object.keys(formTypes).length === 0) {
            $container.html('<p class="no-data">' + (myLoginFormDashboard.i18n.noForms || 'No forms created yet.') + '</p>');
            return;
        }
        
        let html = '';
        for (const [type, count] of Object.entries(formTypes)) {
            const icon = getFormIcon(type);
            const typeName = type.replace('_', ' ');
            
            html += '<div class="form-type-item">' +
                '<div class="type-label">' +
                    '<span class="type-icon">' + icon + '</span>' +
                    '<span class="type-name">' + escapeHtml(typeName) + '</span>' +
                '</div>' +
                '<div class="type-count">' + count + '</div>' +
            '</div>';
        }
        $container.html(html);
    }

    /**
     * Get Form Icon
     */
    function getFormIcon(type) {
        const icons = {
            'login': '🔐',
            'register': '📝',
            'welcome': '👋',
            'forgot_password': '🔑',
            'popup': '💬',
            'contact': '✉️',
            'custom': '⚙️'
        };
        return icons[type] || '📄';
    }

    /**
     * Get Avatar HTML
     */
    function getAvatarHtml(email) {
        // This is a placeholder - you can implement Gravatar or other avatar service
        return '<img src="https://www.gravatar.com/avatar/' + md5(email.toLowerCase().trim()) + '?s=32&d=mm" width="32" height="32" style="border-radius: 50%;">';
    }

    /**
     * Simple MD5 for Gravatar
     */
    function md5(string) {
        return CryptoJS.MD5(string).toString();
    }

    /**
     * Format Number
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    /**
     * Format Date
     */
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show Notice
     */
    function showNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.my-login-form-dashboard').before($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Start Auto Refresh (optional)
     */
    function startAutoRefresh(interval = 60000) {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(function() {
            refreshAllDashboardData();
        }, interval);
    }

    /**
     * Stop Auto Refresh
     */
    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initDashboard();
    });

})(jQuery);