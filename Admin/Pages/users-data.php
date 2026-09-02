<?php
/**
 * Users Management Template
 * 
 * @package MyLoginForm\Admin
 */

// Prevent Direct Access
defined('ABSPATH') || exit;

// Ensure all variables are defined with defaults
$stats = $stats ?? [
    'total' => 0,
    'synced' => 0,
    'today' => 0,
    'social' => 0,
    'wp_users' => 0,
    'pending_sync' => 0,
    'sync_rate' => 0
];
$search = $search ?? '';
$users = $users ?? [];
$total_users = $total_users ?? 0;
$total_pages = $total_pages ?? 1;
$current_page = $current_page ?? 1;
?>

<div class="wrap my-login-form-users">
    <div class="users-header">
        <h1 class="wp-heading-inline"><i class="fas fa-users"></i> <?php _e('User Data Management', 'my-login-form'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=my-login-form-dashboard'); ?>" class="page-title-action">
            <i class="fas fa-arrow-left"></i> <?php _e('Back to Dashboard', 'my-login-form'); ?>
        </a>
    </div>
    
    <hr class="wp-header-end">
    
    <!-- Stats Cards -->
    <div class="users-stats-grid">
        <div class="stat-card stat-card-primary">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('Total Users', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['total']); ?></div>
            </div>
        </div>

        <div class="stat-card stat-card-success">
            <div class="stat-icon"><i class="fas fa-sync-alt"></i></div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('Synced with Supabase', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['synced']); ?></div>
                <div class="stat-sub"><?php printf(__('%d%% of total', 'my-login-form'), $stats['sync_rate']); ?></div>
            </div>
        </div>

        <div class="stat-card stat-card-warning">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <div class="stat-label"><?php _e("Today's Registrations", 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['today']); ?></div>
            </div>
        </div>

        <div class="stat-card stat-card-info">
            <div class="stat-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('Social Login Users', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['social']); ?></div>
            </div>
        </div>

        <div class="stat-card stat-card-secondary">
            <div class="stat-icon"><i class="fab fa-wordpress"></i></div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('WordPress Users', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['wp_users']); ?></div>
            </div>
        </div>

        <div class="stat-card stat-card-pending">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('Pending Sync', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['pending_sync']); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Actions Bar -->
    <div class="users-actions-bar">
        <div class="search-container">
            <form method="get">
                <input type="hidden" name="page" value="my-login-form-users-data">
                <div class="search-wrapper">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php _e('Search by name or email...', 'my-login-form'); ?>" 
                           class="search-input">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="action-buttons">
            <button onclick="exportCSV()" class="button button-primary">
                <i class="fas fa-download"></i> <?php _e('Export CSV', 'my-login-form'); ?>
            </button>
            <button onclick="syncWithSupabase()" class="button">
                <i class="fas fa-sync-alt"></i> <?php _e('Sync with Supabase', 'my-login-form'); ?>
            </button>
            <button onclick="createWordPressUsers()" class="button">
                <i class="fas fa-user-plus"></i> <?php _e('Create WP Users', 'my-login-form'); ?>
            </button>
        </div>
    </div>
    
    <!-- Users Table -->
    <form method="post" id="users-form">
        <input type="hidden" name="bulk_action" id="bulk_action_field" value="">
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action_select" id="bulk_action_select">
                    <option value="-1"><?php _e('Bulk Actions', 'my-login-form'); ?></option>
                    <option value="delete"><?php _e('Delete', 'my-login-form'); ?></option>
                    <option value="sync"><?php _e('Sync with Supabase', 'my-login-form'); ?></option>
                    <option value="create_wp"><?php _e('Create WordPress Users', 'my-login-form'); ?></option>
                </select>
                <button type="button" class="button action" onclick="applyBulkAction()">
                    <?php _e('Apply', 'my-login-form'); ?>
                </button>
            </div>
            
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php echo sprintf(__('%d items', 'my-login-form'), $total_users); ?>
                </span>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-links">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page,
                            'type' => 'plain'
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="check-column"><input type="checkbox" id="select-all"></td>
                    <th class="column-id"><?php _e('ID', 'my-login-form'); ?></th>
                    <th class="column-name"><?php _e('Name', 'my-login-form'); ?></th>
                    <th class="column-email"><?php _e('Email', 'my-login-form'); ?></th>
                    <th class="column-role"><?php _e('Role', 'my-login-form'); ?></th>
                    <th class="column-phone"><?php _e('Phone', 'my-login-form'); ?></th>
                    <?php if (!empty($use_woo)): ?>
                    <th class="column-woo"><?php _e('Orders / Spent', 'my-login-form'); ?></th>
                    <?php endif; ?>
                    <th class="column-date"><?php _e('Registered', 'my-login-form'); ?></th>
                    <th class="column-status"><?php _e('Status', 'my-login-form'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'my-login-form'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="<?php echo !empty($use_woo) ? 10 : 9; ?>" class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <h3><?php _e('No users found', 'my-login-form'); ?></h3>
                            <?php if ($search): ?>
                                <p><?php printf(__('No users match your search "%s"', 'my-login-form'), esc_html($search)); ?></p>
                                <a href="<?php echo remove_query_arg('s'); ?>" class="button">
                                    <?php _e('Clear Search', 'my-login-form'); ?>
                                </a>
                            <?php else: ?>
                                <p><?php _e('No users have registered yet', 'my-login-form'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <th class="check-column"><input type="checkbox" name="user_ids[]" value="<?php echo $user->id; ?>" data-source="<?php echo esc_attr($user->source); ?>"></th>
                            <td><?php echo $user->id; ?></td>
                            <td class="user-name">
                                <div class="user-avatar">
                                    <?php echo get_avatar($user->user_email, 32); ?>
                                </div>
                                <div class="user-info">
                                    <strong><?php echo esc_html($user->user_first_name . ' ' . $user->user_last_name); ?></strong>
                                    <?php if ($user->social_provider): ?>
                                        <span class="social-badge">
                                            <i class="fab fa-<?php echo esc_attr($user->social_provider); ?>"></i>
                                            <?php echo esc_html(ucfirst($user->social_provider)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php echo esc_html($user->user_email); ?>
                                <?php if ($user->wp_user_id): ?>
                                    <br>
                                    <span class="wp-badge">
                                        <i class="fab fa-wordpress"></i> <?php _e('WP User', 'my-login-form'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(!empty($user->user_roles) ? $user->user_roles : '—'); ?></td>
                            <td><?php echo esc_html($user->user_phone ?: '—'); ?></td>
                            <?php if (!empty($use_woo)): ?>
                            <td>
                                <?php if (!empty($user->woo_orders)): ?>
                                    <strong><?php echo intval($user->woo_orders); ?></strong> <?php _e('orders', 'my-login-form'); ?><br>
                                    <span class="woo-spent"><?php echo wp_kses_post($user->woo_spent ?? '—'); ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td>
                                <div class="date-info">
                                    <div><?php echo date_i18n(get_option('date_format'), strtotime($user->created_at)); ?></div>
                                    <span class="time-info"><?php echo date_i18n(get_option('time_format'), strtotime($user->created_at)); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_icon = '';
                                $status_text = '';
                                
                                switch ($user->sync_status) {
                                    case 'synced':
                                        $status_class = 'status-success';
                                        $status_icon = 'fas fa-check-circle';
                                        $status_text = __('Synced', 'my-login-form');
                                        break;
                                    case 'pending':
                                        $status_class = 'status-warning';
                                        $status_icon = 'fas fa-sync-alt';
                                        $status_text = __('Pending', 'my-login-form');
                                        break;
                                    case 'failed':
                                        $status_class = 'status-error';
                                        $status_icon = 'fas fa-times-circle';
                                        $status_text = __('Failed', 'my-login-form');
                                        break;
                                    default:
                                        $status_class = 'status-default';
                                        $status_icon = 'fas fa-pause-circle';
                                        $status_text = __('Not synced', 'my-login-form');
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <i class="<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                </span>
                                <?php if (!empty($user->firebase_uid)): ?>
                                    <div class="firebase-uid">UID: <?php echo substr($user->firebase_uid, 0, 12) . '...'; ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <button onclick="viewUser(<?php echo intval($user->id); ?>, '<?php echo esc_js($user->source); ?>')" class="button button-small action-view" title="<?php _e('View Details', 'my-login-form'); ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="editUser(<?php echo intval($user->id); ?>, '<?php echo esc_js($user->source); ?>')" class="button button-small action-edit" title="<?php _e('Edit', 'my-login-form'); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteUser(<?php echo intval($user->id); ?>, '<?php echo esc_js($user->source); ?>')" class="button button-small action-delete" title="<?php _e('Delete', 'my-login-form'); ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
    
    <!-- User Details Modal -->
    <div id="userDetailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="userModalTitle"><?php _e('User Details', 'my-login-form'); ?></h2>
                <button class="modal-close" onclick="closeUserModal()">&times;</button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> <?php _e('Loading...', 'my-login-form'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeUserModal()" class="button"><?php _e('Close', 'my-login-form'); ?></button>
            </div>
        </div>
    </div>

    <!-- User Edit Modal -->
    <div id="userEditModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php _e('Edit User', 'my-login-form'); ?></h2>
                <button class="modal-close" onclick="closeUserEditModal()">&times;</button>
            </div>
            <div class="modal-body" id="userEditContent">
                <input type="hidden" id="editUserId" value="">
                <input type="hidden" id="editUserSource" value="">
                <p>
                    <label for="editFirstName"><?php _e('First Name', 'my-login-form'); ?></label><br>
                    <input type="text" id="editFirstName" class="regular-text">
                </p>
                <p>
                    <label for="editLastName"><?php _e('Last Name', 'my-login-form'); ?></label><br>
                    <input type="text" id="editLastName" class="regular-text">
                </p>
                <p>
                    <label for="editEmail"><?php _e('Email', 'my-login-form'); ?></label><br>
                    <input type="email" id="editEmail" class="regular-text">
                </p>
                <p>
                    <label for="editPhone"><?php _e('Phone', 'my-login-form'); ?></label><br>
                    <input type="text" id="editPhone" class="regular-text">
                </p>
            </div>
            <div class="modal-footer">
                <button onclick="closeUserEditModal()" class="button"><?php _e('Cancel', 'my-login-form'); ?></button>
                <button onclick="saveUserEdit()" class="button button-primary"><?php _e('Save Changes', 'my-login-form'); ?></button>
            </div>
        </div>
    </div>
</div>



