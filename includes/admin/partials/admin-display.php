<?php
/**
 * Main admin dashboard for the plugin.
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
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="mps-dashboard">
        <div class="mps-dashboard-header">
            <div class="mps-dashboard-heading">
                <h2><?php esc_html_e('Sync Dashboard', 'multi-platform-sync'); ?></h2>
            </div>
            <div class="mps-dashboard-actions">
                <button id="mps-manual-sync" class="button button-primary">
                    <?php esc_html_e('Run Manual Sync', 'multi-platform-sync'); ?>
                </button>
            </div>
        </div>
        
        <div class="mps-dashboard-content">
            <div class="mps-card">
                <h3><?php esc_html_e('Status', 'multi-platform-sync'); ?></h3>
                <div class="mps-status">
                    <?php
                    $webhook_url = get_option('mps_zapier_webhook_url', '');
                    $selected_forms = get_option('mps_gravity_forms_to_sync', array());
                    
                    if (empty($webhook_url)) {
                        echo '<div class="mps-status-item mps-status-error">';
                        echo '<span class="dashicons dashicons-warning"></span>';
                        echo esc_html__('Zapier webhook URL is not configured.', 'multi-platform-sync');
                        echo '</div>';
                    } else {
                        echo '<div class="mps-status-item mps-status-success">';
                        echo '<span class="dashicons dashicons-yes"></span>';
                        echo esc_html__('Zapier webhook URL is configured.', 'multi-platform-sync');
                        echo '</div>';
                    }
                    
                    if (empty($selected_forms)) {
                        echo '<div class="mps-status-item mps-status-error">';
                        echo '<span class="dashicons dashicons-warning"></span>';
                        echo esc_html__('No Gravity Forms selected for syncing.', 'multi-platform-sync');
                        echo '</div>';
                    } else {
                        echo '<div class="mps-status-item mps-status-success">';
                        echo '<span class="dashicons dashicons-yes"></span>';
                        echo esc_html(sprintf(
                            _n('%d Gravity Form selected for syncing.', '%d Gravity Forms selected for syncing.', count($selected_forms), 'multi-platform-sync'),
                            count($selected_forms)
                        ));
                        echo '</div>';
                    }
                    
                    $campaign_monitor_api_key = get_option('mps_campaign_monitor_api_key', '');
                    $campaign_monitor_list_id = get_option('mps_campaign_monitor_list_id', '');
                    
                    if (empty($campaign_monitor_api_key) || empty($campaign_monitor_list_id)) {
                        echo '<div class="mps-status-item mps-status-warning">';
                        echo '<span class="dashicons dashicons-warning"></span>';
                        echo esc_html__('Campaign Monitor integration is not fully configured.', 'multi-platform-sync');
                        echo '</div>';
                    } else {
                        echo '<div class="mps-status-item mps-status-success">';
                        echo '<span class="dashicons dashicons-yes"></span>';
                        echo esc_html__('Campaign Monitor integration is configured.', 'multi-platform-sync');
                        echo '</div>';
                    }
                    
                    $quickbase_user_token = get_option('mps_quickbase_user_token', '');
                    $quickbase_realm = get_option('mps_quickbase_realm_hostname', '');
                    $quickbase_app_id = get_option('mps_quickbase_app_id', '');
                    $quickbase_table_id = get_option('mps_quickbase_table_id', '');
                    
                    if (empty($quickbase_user_token) || empty($quickbase_realm) || empty($quickbase_app_id) || empty($quickbase_table_id)) {
                        echo '<div class="mps-status-item mps-status-warning">';
                        echo '<span class="dashicons dashicons-warning"></span>';
                        echo esc_html__('Quickbase integration is not fully configured.', 'multi-platform-sync');
                        echo '</div>';
                    } else {
                        echo '<div class="mps-status-item mps-status-success">';
                        echo '<span class="dashicons dashicons-yes"></span>';
                        echo esc_html__('Quickbase integration is configured.', 'multi-platform-sync');
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="mps-card">
                <h3><?php esc_html_e('Recent Sync Activity', 'multi-platform-sync'); ?></h3>
                <?php
                // Get the last 5 log entries
                global $wpdb;
                $table_name = $wpdb->prefix . 'mps_sync_logs';
                
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                    echo '<p>' . esc_html__('No sync logs available yet.', 'multi-platform-sync') . '</p>';
                } else {
                    $logs = $wpdb->get_results(
                        "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 5",
                        ARRAY_A
                    );
                    
                    if (empty($logs)) {
                        echo '<p>' . esc_html__('No sync logs available yet.', 'multi-platform-sync') . '</p>';
                    } else {
                        echo '<table class="widefat">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>' . esc_html__('Time', 'multi-platform-sync') . '</th>';
                        echo '<th>' . esc_html__('Type', 'multi-platform-sync') . '</th>';
                        echo '<th>' . esc_html__('Status', 'multi-platform-sync') . '</th>';
                        echo '<th>' . esc_html__('Message', 'multi-platform-sync') . '</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        foreach ($logs as $log) {
                            $time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['timestamp']));
                            $status_class = $log['status'] === 'success' ? 'mps-success' : 'mps-error';
                            
                            echo '<tr>';
                            echo '<td>' . esc_html($time) . '</td>';
                            echo '<td>' . esc_html($log['sync_type']) . '</td>';
                            echo '<td class="' . esc_attr($status_class) . '">' . esc_html($log['status']) . '</td>';
                            echo '<td>' . esc_html($log['message']) . '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        
                        echo '<p class="mps-view-all-logs">';
                        echo '<a href="' . esc_url(admin_url('admin.php?page=multi-platform-sync-logs')) . '">';
                        echo esc_html__('View All Logs', 'multi-platform-sync');
                        echo '</a>';
                        echo '</p>';
                    }
                }
                ?>
            </div>
            
            <div class="mps-card">
                <h3><?php esc_html_e('Getting Started', 'multi-platform-sync'); ?></h3>
                <div class="mps-getting-started">
                    <ol>
                        <li>
                            <strong><?php esc_html_e('Configure Zapier Integration', 'multi-platform-sync'); ?></strong>
                            <p><?php esc_html_e('Create a Zap in Zapier that accepts webhook data from this plugin.', 'multi-platform-sync'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Set Up External Connections', 'multi-platform-sync'); ?></strong>
                            <p><?php esc_html_e('Configure your Zapier actions to send data to Campaign Monitor and Quickbase.', 'multi-platform-sync'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Select Gravity Forms', 'multi-platform-sync'); ?></strong>
                            <p><?php esc_html_e('Choose which Gravity Forms should trigger the sync process.', 'multi-platform-sync'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Test the Connection', 'multi-platform-sync'); ?></strong>
                            <p><?php esc_html_e('Submit a test form entry or use the manual sync button to verify everything works.', 'multi-platform-sync'); ?></p>
                        </li>
                    </ol>
                    
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=multi-platform-sync-settings')); ?>" class="button">
                            <?php esc_html_e('Go to Settings', 'multi-platform-sync'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="mps-sync-modal" class="mps-modal">
    <div class="mps-modal-content">
        <span class="mps-modal-close">&times;</span>
        <h3><?php esc_html_e('Sync Progress', 'multi-platform-sync'); ?></h3>
        <div id="mps-sync-progress">
            <div class="mps-progress-message"><?php esc_html_e('Syncing data...', 'multi-platform-sync'); ?></div>
            <div class="mps-loader"></div>
            <div id="mps-sync-result"></div>
        </div>
    </div>
</div> 