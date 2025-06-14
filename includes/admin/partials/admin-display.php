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

<div class="wrap mps-admin-page">
    <h1 class="screen-reader-text"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="mps-dashboard">
        <div class="mps-dashboard-header">
            <div class="mps-dashboard-heading">
                <h2><?php esc_html_e('Multi-Platform Sync Dashboard', 'multi-platform-sync'); ?></h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">
                    <?php esc_html_e('Monitor and manage your data synchronization across platforms', 'multi-platform-sync'); ?>
                </p>
            </div>
            <div class="mps-dashboard-actions">
                <button id="mps-manual-sync" class="mps-button-primary">
                    <span class="dashicons dashicons-update" style="margin-right: 8px; font-size: 16px; line-height: 1;"></span>
                    <?php esc_html_e('Run Manual Sync', 'multi-platform-sync'); ?>
                </button>
            </div>
        </div>
        
        <div class="mps-dashboard-content">
            <div class="mps-card">
                <h3>
                    <span class="dashicons dashicons-admin-tools" style="margin-right: 8px; color: #667eea;"></span>
                    <?php esc_html_e('System Status', 'multi-platform-sync'); ?>
                </h3>
                <div class="mps-status">
                    <?php
                    $webhook_url = get_option('mps_zapier_webhook_url', '');
                    $selected_forms = get_option('mps_gravity_forms_to_sync', array());
                    $zapier_addon_detected = get_option('mps_gf_zapier_addon_detected', false);
                    
                    if ($zapier_addon_detected) {
                        echo '<div class="mps-status-item mps-status-success">';
                        echo '<span class="dashicons dashicons-yes-alt"></span>';
                        echo '<div>';
                        echo '<strong>' . esc_html__('Gravity Forms Zapier Add-on Detected', 'multi-platform-sync') . '</strong><br>';
                        echo '<span style="font-size: 13px; opacity: 0.8;">' . esc_html__('Using official add-on for Gravity Forms to Zapier integration', 'multi-platform-sync') . '</span>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        if (empty($webhook_url)) {
                            echo '<div class="mps-status-item mps-status-error">';
                            echo '<span class="dashicons dashicons-warning"></span>';
                            echo '<div>';
                            echo '<strong>' . esc_html__('Zapier Webhook URL Missing', 'multi-platform-sync') . '</strong><br>';
                            echo '<span style="font-size: 13px; opacity: 0.8;">' . esc_html__('Configure webhook URL in settings to enable Zapier integration', 'multi-platform-sync') . '</span>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<div class="mps-status-item mps-status-success">';
                            echo '<span class="dashicons dashicons-yes-alt"></span>';
                            echo '<div>';
                            echo '<strong>' . esc_html__('Zapier Integration Configured', 'multi-platform-sync') . '</strong><br>';
                            echo '<span style="font-size: 13px; opacity: 0.8;">' . esc_html__('Webhook URL is configured and ready', 'multi-platform-sync') . '</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                        
                        if (empty($selected_forms)) {
                            echo '<div class="mps-status-item mps-status-warning">';
                            echo '<span class="dashicons dashicons-info"></span>';
                            echo '<div>';
                            echo '<strong>' . esc_html__('No Forms Selected', 'multi-platform-sync') . '</strong><br>';
                            echo '<span style="font-size: 13px; opacity: 0.8;">' . esc_html__('Select forms in settings to enable automatic syncing', 'multi-platform-sync') . '</span>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<div class="mps-status-item mps-status-success">';
                            echo '<span class="dashicons dashicons-yes-alt"></span>';
                            echo '<div>';
                            echo '<strong>' . esc_html(sprintf(
                                _n('%d Form Selected', '%d Forms Selected', count($selected_forms), 'multi-platform-sync'),
                                count($selected_forms)
                            )) . '</strong><br>';
                            echo '<span style="font-size: 13px; opacity: 0.8;">' . esc_html__('Forms are configured for automatic syncing', 'multi-platform-sync') . '</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                    
                    $campaign_monitor_api_key = get_option('mps_campaign_monitor_api_key', '');
                    $campaign_monitor_list_id = get_option('mps_campaign_monitor_list_id', '');
                    
                    if (empty($campaign_monitor_api_key) || empty($campaign_monitor_list_id)) {
                        echo '<div class="mps-status-item mps-status-warning">';
                        echo '<span class="dashicons dashicons-email-alt"></span>';
                        echo '<div>';
                        echo '<strong>' . esc_html__('Campaign Monitor Setup Incomplete', 'multi-platform-sync') . '</strong><br>';
                        echo '<span style="font-size: 13px; opacity: 0.8;">' . esc_html__('Configure API key and list ID to enable email marketing integration', 'multi-platform-sync') . '</span>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="mps-status-item mps-status-success">';
                        echo '<span class="dashicons dashicons-email-alt"></span>';
                        echo '<div>';
                        echo '<strong>' . esc_html__('Campaign Monitor Ready', 'multi-platform-sync') . '</strong><br>';
                        echo '<span style="font-size: 13px; opacity: 0.8;">' . esc_html__('Email marketing integration is configured and active', 'multi-platform-sync') . '</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    $quickbase_user_token = get_option('mps_quickbase_user_token', '');
                    $quickbase_realm = get_option('mps_quickbase_realm_hostname', '');
                    $quickbase_app_id = get_option('mps_quickbase_app_id', '');
                    $quickbase_table_id = get_option('mps_quickbase_table_id', '');
                    
                    if (empty($quickbase_user_token) || empty($quickbase_realm) || empty($quickbase_app_id) || empty($quickbase_table_id)) {
                        echo '<div class="mps-status-item mps-status-warning">';
                        echo '<span class="dashicons dashicons-database"></span>';
                        echo '<div>';
                        echo '<strong>' . esc_html__('Quickbase Setup Incomplete', 'multi-platform-sync') . '</strong><br>';
                        echo '<span style="font-size: 13px; opacity: 0.8;">' . esc_html__('Complete configuration to enable database integration', 'multi-platform-sync') . '</span>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="mps-status-item mps-status-success">';
                        echo '<span class="dashicons dashicons-database"></span>';
                        echo '<div>';
                        echo '<strong>' . esc_html__('Quickbase Ready', 'multi-platform-sync') . '</strong><br>';
                        echo '<span style="font-size: 13px; opacity: 0.8;">' . esc_html__('Database integration is configured and active', 'multi-platform-sync') . '</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="mps-card">
                <h3>
                    <span class="dashicons dashicons-chart-line" style="margin-right: 8px; color: #667eea;"></span>
                    <?php esc_html_e('Recent Activity', 'multi-platform-sync'); ?>
                </h3>
                <?php
                // Get the last 5 log entries
                global $wpdb;
                $table_name = $wpdb->prefix . 'mps_sync_logs';
                
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                    echo '<div class="mps-no-logs">';
                    echo '<span class="dashicons dashicons-info" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></span><br>';
                    echo '<strong>' . esc_html__('No Activity Yet', 'multi-platform-sync') . '</strong><br>';
                    echo '<span style="font-size: 14px; opacity: 0.7;">' . esc_html__('Sync logs will appear here once you start using the plugin', 'multi-platform-sync') . '</span>';
                    echo '</div>';
                } else {
                    $logs = $wpdb->get_results(
                        "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 5",
                        ARRAY_A
                    );
                    
                    if (empty($logs)) {
                        echo '<div class="mps-no-logs">';
                        echo '<span class="dashicons dashicons-info" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></span><br>';
                        echo '<strong>' . esc_html__('No Activity Yet', 'multi-platform-sync') . '</strong><br>';
                        echo '<span style="font-size: 14px; opacity: 0.7;">' . esc_html__('Sync logs will appear here once you start using the plugin', 'multi-platform-sync') . '</span>';
                        echo '</div>';
                    } else {
                        echo '<div class="mps-logs-table-container">';
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th style="width: 140px;">' . esc_html__('Time', 'multi-platform-sync') . '</th>';
                        echo '<th style="width: 100px;">' . esc_html__('Type', 'multi-platform-sync') . '</th>';
                        echo '<th style="width: 80px;">' . esc_html__('Status', 'multi-platform-sync') . '</th>';
                        echo '<th>' . esc_html__('Message', 'multi-platform-sync') . '</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        foreach ($logs as $log) {
                            $time = date_i18n('M j, g:i A', strtotime($log['timestamp']));
                            $status_class = $log['status'] === 'success' ? 'mps-success' : 'mps-error';
                            $status_icon = $log['status'] === 'success' ? 'yes-alt' : 'warning';
                            
                            echo '<tr>';
                            echo '<td><strong>' . esc_html($time) . '</strong></td>';
                            echo '<td><span class="dashicons dashicons-admin-generic" style="margin-right: 5px; font-size: 14px;"></span>' . esc_html(ucfirst($log['sync_type'])) . '</td>';
                            echo '<td class="' . esc_attr($status_class) . '">';
                            echo '<span class="dashicons dashicons-' . esc_attr($status_icon) . '" style="margin-right: 5px; font-size: 14px;"></span>';
                            echo esc_html(ucfirst($log['status']));
                            echo '</td>';
                            echo '<td>' . esc_html(wp_trim_words($log['message'], 15)) . '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                        
                        echo '<div class="mps-view-all-logs">';
                        echo '<a href="' . esc_url(admin_url('admin.php?page=multi-platform-sync-logs')) . '" class="button">';
                        echo '<span class="dashicons dashicons-list-view" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>';
                        echo esc_html__('View All Logs', 'multi-platform-sync');
                        echo '</a>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
            
            <div class="mps-card">
                <h3>
                    <span class="dashicons dashicons-lightbulb" style="margin-right: 8px; color: #667eea;"></span>
                    <?php esc_html_e('Quick Start Guide', 'multi-platform-sync'); ?>
                </h3>
                <div class="mps-getting-started">
                    <ol>
                        <li>
                            <strong><?php esc_html_e('Configure Integrations', 'multi-platform-sync'); ?></strong>
                            <p><?php esc_html_e('Set up your API credentials for Campaign Monitor and Quickbase in the settings page.', 'multi-platform-sync'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Set Up Zapier Connection', 'multi-platform-sync'); ?></strong>
                            <p><?php esc_html_e('Create a Zap in Zapier to connect your forms with external platforms using webhooks.', 'multi-platform-sync'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Select Forms to Sync', 'multi-platform-sync'); ?></strong>
                            <p><?php esc_html_e('Choose which Gravity Forms should trigger the synchronization process.', 'multi-platform-sync'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Test Your Setup', 'multi-platform-sync'); ?></strong>
                            <p><?php esc_html_e('Use the connection test tools and submit a test form to verify everything works correctly.', 'multi-platform-sync'); ?></p>
                        </li>
                    </ol>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=multi-platform-sync-settings')); ?>" class="mps-button-primary" style="margin-right: 10px;">
                            <span class="dashicons dashicons-admin-settings" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>
                            <?php esc_html_e('Go to Settings', 'multi-platform-sync'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=multi-platform-sync-analytics')); ?>" class="button">
                            <span class="dashicons dashicons-chart-bar" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>
                            <?php esc_html_e('View Analytics', 'multi-platform-sync'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Modal -->
<div id="mps-sync-modal" class="mps-modal" role="dialog" aria-labelledby="mps-modal-title" aria-hidden="true">
    <div class="mps-modal-content">
        <span class="mps-modal-close" aria-label="<?php esc_attr_e('Close', 'multi-platform-sync'); ?>">&times;</span>
        <h3 id="mps-modal-title"><?php esc_html_e('Sync Progress', 'multi-platform-sync'); ?></h3>
        <div id="mps-sync-progress">
            <div class="mps-progress-message"><?php esc_html_e('Initializing sync process...', 'multi-platform-sync'); ?></div>
            <div class="mps-loader" role="status" aria-label="<?php esc_attr_e('Loading', 'multi-platform-sync'); ?>"></div>
            <div id="mps-sync-result"></div>
        </div>
    </div>
</div>

<style>
/* Additional inline styles for better visual hierarchy */
.mps-status-item div {
    flex: 1;
}

.mps-status-item strong {
    display: block;
    margin-bottom: 2px;
    font-size: 14px;
}

.mps-logs-table-container {
    overflow-x: auto;
}

.mps-getting-started ol li {
    position: relative;
    padding-left: 0;
}

.mps-getting-started ol li::before {
    content: counter(list-item);
    counter-increment: list-item;
    position: absolute;
    left: -30px;
    top: 0;
    background: #667eea;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

@media (max-width: 768px) {
    .mps-getting-started ol {
        margin-left: 0;
    }
    
    .mps-getting-started ol li::before {
        position: relative;
        left: 0;
        margin-right: 10px;
        margin-bottom: 5px;
    }
}
</style>