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

// Get pagination parameters
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get logs from database
global $wpdb;
$table_name = $wpdb->prefix . 'mps_sync_logs';

// Check if the table exists
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $logs = array();
    $total_logs = 0;
} else {
    // Get logs with pagination
    $logs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d, %d",
            $offset,
            $per_page
        ),
        ARRAY_A
    );
    
    // Get total count for pagination
    $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
}

// Calculate pagination data
$total_pages = ceil($total_logs / $per_page);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="mps-logs-header">
        <div class="mps-logs-title">
            <h2><?php esc_html_e('Sync Activity Logs', 'multi-platform-sync'); ?></h2>
        </div>
        <div class="mps-logs-actions">
            <form method="post" action="">
                <?php wp_nonce_field('mps_clear_logs', 'mps_clear_logs_nonce'); ?>
                <button type="submit" name="mps_clear_logs" class="button" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all logs?', 'multi-platform-sync'); ?>');">
                    <?php esc_html_e('Clear Logs', 'multi-platform-sync'); ?>
                </button>
            </form>
        </div>
    </div>
    
    <?php if (empty($logs)) : ?>
        <div class="mps-no-logs">
            <p><?php esc_html_e('No sync logs available.', 'multi-platform-sync'); ?></p>
        </div>
    <?php else : ?>
        <div class="mps-logs-table-container">
            <table class="wp-list-table widefat fixed striped mps-logs-table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('ID', 'multi-platform-sync'); ?></th>
                        <th scope="col"><?php esc_html_e('Time', 'multi-platform-sync'); ?></th>
                        <th scope="col"><?php esc_html_e('Type', 'multi-platform-sync'); ?></th>
                        <th scope="col"><?php esc_html_e('Form ID', 'multi-platform-sync'); ?></th>
                        <th scope="col"><?php esc_html_e('Entry ID', 'multi-platform-sync'); ?></th>
                        <th scope="col"><?php esc_html_e('Status', 'multi-platform-sync'); ?></th>
                        <th scope="col"><?php esc_html_e('Message', 'multi-platform-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : 
                        $time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['timestamp']));
                        $status_class = $log['status'] === 'success' ? 'mps-success' : 'mps-error';
                    ?>
                        <tr>
                            <td><?php echo esc_html($log['id']); ?></td>
                            <td><?php echo esc_html($time); ?></td>
                            <td><?php echo esc_html($log['sync_type']); ?></td>
                            <td><?php echo esc_html($log['form_id'] ? $log['form_id'] : '-'); ?></td>
                            <td><?php echo esc_html($log['entry_id'] ? $log['entry_id'] : '-'); ?></td>
                            <td class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($log['status']); ?></td>
                            <td><?php echo esc_html($log['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1) : ?>
            <div class="mps-pagination">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        printf(
                            /* translators: %s: Number of items. */
                            _n('%s item', '%s items', $total_logs, 'multi-platform-sync'),
                            number_format_i18n($total_logs)
                        );
                        ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php
                        // First page link
                        if ($page > 1) {
                            echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1)) . '"><span class="screen-reader-text">' . __('First page', 'multi-platform-sync') . '</span><span aria-hidden="true">&laquo;</span></a>';
                        } else {
                            echo '<span class="first-page button disabled"><span class="screen-reader-text">' . __('First page', 'multi-platform-sync') . '</span><span aria-hidden="true">&laquo;</span></span>';
                        }
                        
                        // Previous page link
                        if ($page > 1) {
                            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', max(1, $page - 1))) . '"><span class="screen-reader-text">' . __('Previous page', 'multi-platform-sync') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                        } else {
                            echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Previous page', 'multi-platform-sync') . '</span><span aria-hidden="true">&lsaquo;</span></span>';
                        }
                        
                        // Current page info
                        echo '<span class="paging-input">' . $page . ' ' . __('of', 'multi-platform-sync') . ' <span class="total-pages">' . $total_pages . '</span></span>';
                        
                        // Next page link
                        if ($page < $total_pages) {
                            echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', min($total_pages, $page + 1))) . '"><span class="screen-reader-text">' . __('Next page', 'multi-platform-sync') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                        } else {
                            echo '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Next page', 'multi-platform-sync') . '</span><span aria-hidden="true">&rsaquo;</span></span>';
                        }
                        
                        // Last page link
                        if ($page < $total_pages) {
                            echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages)) . '"><span class="screen-reader-text">' . __('Last page', 'multi-platform-sync') . '</span><span aria-hidden="true">&raquo;</span></a>';
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