<?php
/**
 * Logs page for the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.0.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Handle log clearing
if (isset($_POST['mps_clear_logs']) && wp_verify_nonce($_POST['mps_clear_logs_nonce'], 'mps_clear_logs')) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mps_sync_logs';
    $wpdb->query("TRUNCATE TABLE $table_name");
    
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('All logs have been cleared successfully.', 'multi-platform-sync') . '</p></div>';
}

// Get pagination parameters
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get logs from database
global $wpdb;
$table_name = $wpdb->prefix . 'mps_sync_logs';

// Check if the table exists
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $logs = array();
    $total_logs = 0;
} else {
    // Build WHERE clause for filters
    $where_conditions = array();
    $where_values = array();
    
    if (!empty($filter_type)) {
        $where_conditions[] = "sync_type = %s";
        $where_values[] = $filter_type;
    }
    
    if (!empty($filter_status)) {
        $where_conditions[] = "status = %s";
        $where_values[] = $filter_status;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "message LIKE %s";
        $where_values[] = '%' . $wpdb->esc_like($search) . '%';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get logs with pagination and filters
    $query = "SELECT * FROM $table_name $where_clause ORDER BY timestamp DESC LIMIT %d, %d";
    $where_values[] = $offset;
    $where_values[] = $per_page;
    
    if (!empty($where_values)) {
        $logs = $wpdb->get_results(
            $wpdb->prepare($query, $where_values),
            ARRAY_A
        );
    } else {
        $logs = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d, %d", $offset, $per_page),
            ARRAY_A
        );
    }
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
    if (!empty($where_conditions)) {
        $count_values = array_slice($where_values, 0, -2); // Remove LIMIT values
        $total_logs = $wpdb->get_var($wpdb->prepare($count_query, $count_values));
    } else {
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
}

// Calculate pagination data
$total_pages = ceil($total_logs / $per_page);

// Get unique sync types for filter dropdown
$sync_types = array();
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    $sync_types = $wpdb->get_col("SELECT DISTINCT sync_type FROM $table_name ORDER BY sync_type");
}
?>

<div class="wrap mps-admin-page">
    <h1 class="screen-reader-text"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Enhanced Logs Header -->
    <div class="mps-logs-header">
        <div class="mps-logs-heading">
            <h2 style="margin: 0; color: #2c3e50; display: flex; align-items: center;">
                <span class="dashicons dashicons-list-view" style="margin-right: 10px; color: #667eea; font-size: 24px;"></span>
                <?php esc_html_e('Sync Activity Logs', 'multi-platform-sync'); ?>
            </h2>
            <p style="margin: 5px 0 0 34px; color: #6c757d; font-size: 14px;">
                <?php esc_html_e('Detailed history of all synchronization activities and events', 'multi-platform-sync'); ?>
            </p>
        </div>
        <div class="mps-logs-actions">
            <form method="post" action="" style="display: inline-block;">
                <?php wp_nonce_field('mps_clear_logs', 'mps_clear_logs_nonce'); ?>
                <button type="submit" name="mps_clear_logs" class="button" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all logs? This action cannot be undone.', 'multi-platform-sync'); ?>');" style="background: #dc3545; color: white; border-color: #dc3545;">
                    <span class="dashicons dashicons-trash" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>
                    <?php esc_html_e('Clear All Logs', 'multi-platform-sync'); ?>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Enhanced Filters -->
    <div class="mps-card" style="margin-bottom: 20px;">
        <h3 style="margin-top: 0; display: flex; align-items: center;">
            <span class="dashicons dashicons-filter" style="margin-right: 8px; color: #667eea;"></span>
            <?php esc_html_e('Filter Logs', 'multi-platform-sync'); ?>
        </h3>
        
        <form method="get" action="" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="multi-platform-sync-logs" />
            
            <div style="flex: 1; min-width: 200px;">
                <label for="search" style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50;">
                    <?php esc_html_e('Search Messages', 'multi-platform-sync'); ?>
                </label>
                <input type="text" id="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search in log messages...', 'multi-platform-sync'); ?>" class="regular-text" />
            </div>
            
            <div style="min-width: 150px;">
                <label for="filter_type" style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50;">
                    <?php esc_html_e('Sync Type', 'multi-platform-sync'); ?>
                </label>
                <select id="filter_type" name="filter_type">
                    <option value=""><?php esc_html_e('All Types', 'multi-platform-sync'); ?></option>
                    <?php foreach ($sync_types as $type): ?>
                    <option value="<?php echo esc_attr($type); ?>" <?php selected($filter_type, $type); ?>>
                        <?php echo esc_html(ucwords(str_replace('_', ' ', $type))); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="min-width: 120px;">
                <label for="filter_status" style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50;">
                    <?php esc_html_e('Status', 'multi-platform-sync'); ?>
                </label>
                <select id="filter_status" name="filter_status">
                    <option value=""><?php esc_html_e('All Status', 'multi-platform-sync'); ?></option>
                    <option value="success" <?php selected($filter_status, 'success'); ?>><?php esc_html_e('Success', 'multi-platform-sync'); ?></option>
                    <option value="error" <?php selected($filter_status, 'error'); ?>><?php esc_html_e('Error', 'multi-platform-sync'); ?></option>
                    <option value="warning" <?php selected($filter_status, 'warning'); ?>><?php esc_html_e('Warning', 'multi-platform-sync'); ?></option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="mps-button-primary">
                    <span class="dashicons dashicons-search" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>
                    <?php esc_html_e('Filter', 'multi-platform-sync'); ?>
                </button>
                <a href="?page=multi-platform-sync-logs" class="button">
                    <span class="dashicons dashicons-dismiss" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>
                    <?php esc_html_e('Clear', 'multi-platform-sync'); ?>
                </a>
            </div>
        </form>
    </div>
    
    <?php if (empty($logs)) : ?>
        <div class="mps-card">
            <div class="mps-no-logs">
                <span class="dashicons dashicons-info" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3; color: #667eea;"></span><br>
                <h3 style="margin: 0 0 10px 0; color: #2c3e50;">
                    <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)): ?>
                        <?php esc_html_e('No logs match your filters', 'multi-platform-sync'); ?>
                    <?php else: ?>
                        <?php esc_html_e('No sync logs available', 'multi-platform-sync'); ?>
                    <?php endif; ?>
                </h3>
                <p style="color: #6c757d; margin: 0;">
                    <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)): ?>
                        <?php esc_html_e('Try adjusting your filter criteria or clearing all filters.', 'multi-platform-sync'); ?>
                    <?php else: ?>
                        <?php esc_html_e('Sync logs will appear here once you start using the plugin.', 'multi-platform-sync'); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php else : ?>
        <!-- Results Summary -->
        <div style="margin-bottom: 15px; padding: 10px 15px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #667eea;">
            <strong><?php esc_html_e('Results:', 'multi-platform-sync'); ?></strong>
            <?php
            printf(
                esc_html(_n('%s log entry found', '%s log entries found', $total_logs, 'multi-platform-sync')),
                '<span style="color: #667eea; font-weight: 600;">' . number_format_i18n($total_logs) . '</span>'
            );
            ?>
            <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)): ?>
                <span style="color: #6c757d;">
                    (<?php esc_html_e('filtered', 'multi-platform-sync'); ?>)
                </span>
            <?php endif; ?>
        </div>
        
        <div class="mps-card">
            <div class="mps-logs-table-container">
                <table class="wp-list-table widefat fixed striped mps-logs-table">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 60px;"><?php esc_html_e('ID', 'multi-platform-sync'); ?></th>
                            <th scope="col" style="width: 140px;"><?php esc_html_e('Time', 'multi-platform-sync'); ?></th>
                            <th scope="col" style="width: 120px;"><?php esc_html_e('Type', 'multi-platform-sync'); ?></th>
                            <th scope="col" style="width: 80px;"><?php esc_html_e('Form ID', 'multi-platform-sync'); ?></th>
                            
                            <th scope="col" style="width: 80px;"><?php esc_html_e('Entry ID', 'multi-platform-sync'); ?></th>
                            <th scope="col" style="width: 100px;"><?php esc_html_e('Status', 'multi-platform-sync'); ?></th>
                            <th scope="col"><?php esc_html_e('Message', 'multi-platform-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log) : 
                            $time = date_i18n('M j, Y g:i A', strtotime($log['timestamp']));
                            $status_class = $log['status'] === 'success' ? 'mps-success' : 'mps-error';
                            $status_icon = $log['status'] === 'success' ? 'yes-alt' : 'warning';
                            
                            // Determine sync type icon
                            $type_icon = 'admin-generic';
                            switch($log['sync_type']) {
                                case 'zapier': $type_icon = 'admin-generic'; break;
                                case 'campaign_monitor': $type_icon = 'email'; break;
                                case 'quickbase': $type_icon = 'database'; break;
                                case 'manual': $type_icon = 'admin-users'; break;
                                case 'automatic': $type_icon = 'update'; break;
                                case 'webhook': $type_icon = 'rest-api'; break;
                                case 'queue': $type_icon = 'list-view'; break;
                            }
                        ?>
                            <tr>
                                <td><strong style="color: #667eea;">#<?php echo esc_html($log['id']); ?></strong></td>
                                <td>
                                    <strong style="display: block;"><?php echo esc_html(date_i18n('M j, Y', strtotime($log['timestamp']))); ?></strong>
                                    <span style="font-size: 13px; color: #6c757d;"><?php echo esc_html(date_i18n('g:i A', strtotime($log['timestamp']))); ?></span>
                                </td>
                                <td>
                                    <span style="display: flex; align-items: center;">
                                        <span class="dashicons dashicons-<?php echo esc_attr($type_icon); ?>" style="margin-right: 5px; color: #667eea; font-size: 14px;"></span>
                                        <span style="font-weight: 500;"><?php echo esc_html(ucwords(str_replace('_', ' ', $log['sync_type']))); ?></span>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['form_id']): ?>
                                        <span style="background: #f0f2f5; padding: 2px 6px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                            <?php echo esc_html($log['form_id']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #adb5bd;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['entry_id']): ?>
                                        <span style="background: #f0f2f5; padding: 2px 6px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                            <?php echo esc_html($log['entry_id']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #adb5bd;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?php echo esc_attr($status_class); ?>">
                                    <span style="display: flex; align-items: center; font-weight: 600;">
                                        <span class="dashicons dashicons-<?php echo esc_attr($status_icon); ?>" style="margin-right: 5px; font-size: 14px;"></span>
                                        <?php echo esc_html(ucfirst($log['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="max-width: 400px;">
                                        <?php 
                                        $message = esc_html($log['message']);
                                        if (strlen($message) > 100) {
                                            echo '<span class="mps-message-short">' . substr($message, 0, 100) . '...</span>';
                                            echo '<span class="mps-message-full" style="display: none;">' . $message . '</span>';
                                            echo '<button type="button" class="button-link mps-toggle-message" style="color: #667eea; text-decoration: none; margin-left: 5px;">Show More</button>';
                                        } else {
                                            echo $message;
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($total_pages > 1) : ?>
            <div class="mps-pagination" style="margin-top: 20px;">
                <div class="tablenav-pages" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                    <span class="displaying-num" style="font-weight: 600; color: #2c3e50;">
                        <?php
                        printf(
                            esc_html(_n('%s item', '%s items', $total_logs, 'multi-platform-sync')),
                            '<strong>' . number_format_i18n($total_logs) . '</strong>'
                        );
                        ?>
                    </span>
                    
                    <span class="pagination-links" style="display: flex; gap: 5px;">
                        <?php
                        $base_url = add_query_arg(array(
                            'page' => 'multi-platform-sync-logs',
                            'search' => $search,
                            'filter_type' => $filter_type,
                            'filter_status' => $filter_status
                        ), admin_url('admin.php'));
                        
                        // First page link
                        if ($page > 1) {
                            echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1, $base_url)) . '"><span class="screen-reader-text">' . __('First page', 'multi-platform-sync') . '</span><span aria-hidden="true">&laquo;</span></a>';
                        } else {
                            echo '<span class="first-page button disabled"><span class="screen-reader-text">' . __('First page', 'multi-platform-sync') . '</span><span aria-hidden="true">&laquo;</span></span>';
                        }
                        
                        // Previous page link
                        if ($page > 1) {
                            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', max(1, $page - 1), $base_url)) . '"><span class="screen-reader-text">' . __('Previous page', 'multi-platform-sync') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                        } else {
                            echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Previous page', 'multi-platform-sync') . '</span><span aria-hidden="true">&lsaquo;</span></span>';
                        }
                        
                        // Current page info
                        echo '<span class="paging-input" style="display: flex; align-items: center; padding: 0 10px; font-weight: 600; color: #2c3e50;">' . $page . ' ' . __('of', 'multi-platform-sync') . ' <span class="total-pages" style="margin-left: 5px;">' . $total_pages . '</span></span>';
                        
                        // Next page link
                        if ($page < $total_pages) {
                            echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', min($total_pages, $page + 1), $base_url)) . '"><span class="screen-reader-text">' . __('Next page', 'multi-platform-sync') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                        } else {
                            echo '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Next page', 'multi-platform-sync') . '</span><span aria-hidden="true">&rsaquo;</span></span>';
                        }
                        
                        // Last page link
                        if ($page < $total_pages) {
                            echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages, $base_url)) . '"><span class="screen-reader-text">' . __('Last page', 'multi-platform-sync') . '</span><span aria-hidden="true">&raquo;</span></a>';
                        } else {
                            echo '<span class="last-page button disabled"><span class="screen-reader-text">' . __('Last page', 'multi-platform-sync') . '</span><span aria-hidden="true">&raquo;</span></span>';
                        }
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle long messages
    $('.mps-toggle-message').on('click', function() {
        const $button = $(this);
        const $short = $button.siblings('.mps-message-short');
        const $full = $button.siblings('.mps-message-full');
        
        if ($full.is(':visible')) {
            $full.hide();
            $short.show();
            $button.text('Show More');
        } else {
            $short.hide();
            $full.show();
            $button.text('Show Less');
        }
    });
    
    // Auto-submit form on filter change (optional)
    $('#filter_type, #filter_status').on('change', function() {
        // Uncomment the line below to auto-submit on filter change
        // $(this).closest('form').submit();
    });
    
    // Keyboard shortcut for search
    $('#search').on('keydown', function(e) {
        if (e.key === 'Enter') {
            $(this).closest('form').submit();
        }
    });
});
</script>

<style>
/* Additional styles for enhanced logs page */
.mps-logs-table {
    font-size: 14px;
}

.mps-logs-table td {
    vertical-align: top;
    padding: 12px 8px;
}

.mps-logs-table th {
    font-weight: 600;
    background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
}

.mps-message-full {
    word-break: break-word;
    line-height: 1.5;
}

.mps-toggle-message {
    font-size: 12px;
    padding: 0;
    border: none;
    background: none;
    cursor: pointer;
    text-decoration: underline;
}

.mps-toggle-message:hover {
    color: #5a6fd8 !important;
}

/* Responsive table */
@media (max-width: 768px) {
    .mps-logs-table-container {
        overflow-x: auto;
    }
    
    .mps-logs-table {
        min-width: 800px;
    }
    
    .mps-pagination .tablenav-pages {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}

/* Filter form responsive */
@media (max-width: 768px) {
    .mps-card form {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    
    .mps-card form > div {
        min-width: auto !important;
        flex: none !important;
    }
    
    .mps-card form > div:last-child {
        margin-top: 15px;
        justify-content: center;
    }
}
</style>