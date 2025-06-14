<?php
/**
 * Analytics page for the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.1.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get analytics data
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30days';
$report_options = array(
    'period' => $period,
    'include_performance' => true,
    'include_errors' => true,
    'include_recommendations' => true
);

$report = Multi_Platform_Sync_Analytics::generate_report($report_options);
$queue_stats = Multi_Platform_Sync_Queue::get_queue_stats();
?>

<div class="wrap mps-admin-page">
    <h1 class="screen-reader-text"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Enhanced Analytics Header -->
    <div class="mps-analytics-header">
        <div class="mps-analytics-heading">
            <h2 style="margin: 0; color: #2c3e50; display: flex; align-items: center;">
                <span class="dashicons dashicons-chart-bar" style="margin-right: 10px; color: #667eea; font-size: 24px;"></span>
                <?php esc_html_e('Analytics Dashboard', 'multi-platform-sync'); ?>
            </h2>
            <p style="margin: 5px 0 0 34px; color: #6c757d; font-size: 14px;">
                <?php esc_html_e('Comprehensive insights into your sync performance and data flow', 'multi-platform-sync'); ?>
            </p>
        </div>
        
        <div class="mps-analytics-controls">
            <div class="mps-period-selector">
                <label for="mps-period-select" style="font-weight: 600; color: #2c3e50;">
                    <?php esc_html_e('Time Period:', 'multi-platform-sync'); ?>
                </label>
                <select id="mps-period-select" onchange="window.location.href='?page=multi-platform-sync-analytics&period=' + this.value;" style="margin-left: 10px;">
                    <option value="7days" <?php selected($period, '7days'); ?>><?php esc_html_e('Last 7 Days', 'multi-platform-sync'); ?></option>
                    <option value="30days" <?php selected($period, '30days'); ?>><?php esc_html_e('Last 30 Days', 'multi-platform-sync'); ?></option>
                    <option value="90days" <?php selected($period, '90days'); ?>><?php esc_html_e('Last 90 Days', 'multi-platform-sync'); ?></option>
                </select>
            </div>
            
            <div class="mps-export-actions">
                <a href="?page=multi-platform-sync-analytics&period=<?php echo esc_attr($period); ?>&export=json" class="button">
                    <span class="dashicons dashicons-download" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>
                    <?php esc_html_e('Export JSON', 'multi-platform-sync'); ?>
                </a>
                <a href="?page=multi-platform-sync-analytics&period=<?php echo esc_attr($period); ?>&export=csv" class="button">
                    <span class="dashicons dashicons-media-spreadsheet" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>
                    <?php esc_html_e('Export CSV', 'multi-platform-sync'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="mps-analytics-dashboard">
        <!-- Enhanced Summary Cards -->
        <div class="mps-summary-cards">
            <div class="mps-card mps-summary-card">
                <h3>
                    <span class="dashicons dashicons-update" style="margin-right: 8px;"></span>
                    <?php esc_html_e('Total Syncs', 'multi-platform-sync'); ?>
                </h3>
                <div class="mps-metric-value"><?php echo esc_html(number_format($report['stats']['summary']['total_syncs'])); ?></div>
                <div class="mps-metric-period">
                    <?php echo esc_html(sprintf(__('Last %d days', 'multi-platform-sync'), $report['period']['days'])); ?>
                </div>
            </div>
            
            <div class="mps-card mps-summary-card">
                <h3>
                    <span class="dashicons dashicons-yes-alt" style="margin-right: 8px;"></span>
                    <?php esc_html_e('Success Rate', 'multi-platform-sync'); ?>
                </h3>
                <div class="mps-metric-value mps-success-rate">
                    <?php echo esc_html($report['stats']['summary']['success_rate']); ?>%
                </div>
                <div class="mps-metric-details">
                    <?php echo esc_html(sprintf(
                        __('%s successful, %s failed', 'multi-platform-sync'),
                        number_format($report['stats']['summary']['successful_syncs']),
                        number_format($report['stats']['summary']['failed_syncs'])
                    )); ?>
                </div>
            </div>
            
            <div class="mps-card mps-summary-card">
                <h3>
                    <span class="dashicons dashicons-clock" style="margin-right: 8px;"></span>
                    <?php esc_html_e('Queue Status', 'multi-platform-sync'); ?>
                </h3>
                <div class="mps-metric-value"><?php echo esc_html(number_format($queue_stats['pending'])); ?></div>
                <div class="mps-metric-details"><?php esc_html_e('Pending items', 'multi-platform-sync'); ?></div>
            </div>
            
            <?php if (isset($report['performance']['avg_daily_syncs'])): ?>
            <div class="mps-card mps-summary-card">
                <h3>
                    <span class="dashicons dashicons-chart-line" style="margin-right: 8px;"></span>
                    <?php esc_html_e('Daily Average', 'multi-platform-sync'); ?>
                </h3>
                <div class="mps-metric-value"><?php echo esc_html(number_format($report['performance']['avg_daily_syncs'], 1)); ?></div>
                <div class="mps-metric-details"><?php esc_html_e('Syncs per day', 'multi-platform-sync'); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Enhanced Performance by Integration -->
        <div class="mps-card">
            <h3 style="display: flex; align-items: center;">
                <span class="dashicons dashicons-performance" style="margin-right: 10px; color: #667eea;"></span>
                <?php esc_html_e('Performance by Integration', 'multi-platform-sync'); ?>
            </h3>
            <div class="mps-sync-types-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 150px;"><?php esc_html_e('Integration', 'multi-platform-sync'); ?></th>
                            <th style="width: 100px;"><?php esc_html_e('Total Syncs', 'multi-platform-sync'); ?></th>
                            <th style="width: 100px;"><?php esc_html_e('Successful', 'multi-platform-sync'); ?></th>
                            <th style="width: 100px;"><?php esc_html_e('Failed', 'multi-platform-sync'); ?></th>
                            <th style="width: 120px;"><?php esc_html_e('Success Rate', 'multi-platform-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report['stats']['by_type'])): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px; color: #6c757d;">
                                <span class="dashicons dashicons-info" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></span><br>
                                <strong><?php esc_html_e('No sync data available', 'multi-platform-sync'); ?></strong><br>
                                <span style="font-size: 14px;"><?php esc_html_e('Data will appear here once syncs are performed', 'multi-platform-sync'); ?></span>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($report['stats']['by_type'] as $type => $data): ?>
                        <tr>
                            <td>
                                <strong style="display: flex; align-items: center;">
                                    <?php
                                    $icon = 'admin-generic';
                                    switch($type) {
                                        case 'zapier': $icon = 'admin-generic'; break;
                                        case 'campaign_monitor': $icon = 'email'; break;
                                        case 'quickbase': $icon = 'database'; break;
                                    }
                                    ?>
                                    <span class="dashicons dashicons-<?php echo esc_attr($icon); ?>" style="margin-right: 8px; color: #667eea;"></span>
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $type))); ?>
                                </strong>
                            </td>
                            <td><strong><?php echo esc_html(number_format($data['total'])); ?></strong></td>
                            <td class="mps-success">
                                <span class="dashicons dashicons-yes-alt" style="margin-right: 5px; font-size: 14px;"></span>
                                <?php echo esc_html(number_format($data['success'])); ?>
                            </td>
                            <td class="mps-error">
                                <span class="dashicons dashicons-warning" style="margin-right: 5px; font-size: 14px;"></span>
                                <?php echo esc_html(number_format($data['error'])); ?>
                            </td>
                            <td>
                                <span class="mps-success-rate-badge <?php echo $data['success_rate'] >= 90 ? 'good' : ($data['success_rate'] >= 70 ? 'warning' : 'poor'); ?>">
                                    <?php echo esc_html($data['success_rate']); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Enhanced Recommendations -->
        <?php if (!empty($report['recommendations'])): ?>
        <div class="mps-card">
            <h3 style="display: flex; align-items: center;">
                <span class="dashicons dashicons-lightbulb" style="margin-right: 10px; color: #667eea;"></span>
                <?php esc_html_e('Recommendations', 'multi-platform-sync'); ?>
            </h3>
            <div class="mps-recommendations">
                <?php foreach ($report['recommendations'] as $recommendation): ?>
                <div class="mps-recommendation mps-recommendation-<?php echo esc_attr($recommendation['type']); ?>">
                    <h4 style="display: flex; align-items: center;">
                        <?php
                        $icon = 'info';
                        switch($recommendation['type']) {
                            case 'success': $icon = 'yes-alt'; break;
                            case 'warning': $icon = 'warning'; break;
                            case 'error': $icon = 'dismiss'; break;
                            case 'info': $icon = 'info'; break;
                        }
                        ?>
                        <span class="dashicons dashicons-<?php echo esc_attr($icon); ?>" style="margin-right: 8px;"></span>
                        <?php echo esc_html($recommendation['title']); ?>
                    </h4>
                    <p><?php echo esc_html($recommendation['message']); ?></p>
                    <?php if (isset($recommendation['action'])): ?>
                    <div class="mps-recommendation-action">
                        <?php
                        switch ($recommendation['action']) {
                            case 'review_errors':
                                echo '<a href="' . esc_url(admin_url('admin.php?page=multi-platform-sync-logs')) . '" class="button">';
                                echo '<span class="dashicons dashicons-list-view" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>';
                                echo esc_html__('Review Error Logs', 'multi-platform-sync') . '</a>';
                                break;
                            case 'enable_queue':
                                echo '<a href="' . esc_url(admin_url('admin.php?page=multi-platform-sync-settings#advanced-settings')) . '" class="button">';
                                echo '<span class="dashicons dashicons-admin-settings" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>';
                                echo esc_html__('Check Settings', 'multi-platform-sync') . '</a>';
                                break;
                            default:
                                if (strpos($recommendation['action'], 'check_') === 0) {
                                    echo '<a href="' . esc_url(admin_url('admin.php?page=multi-platform-sync-settings')) . '" class="button">';
                                    echo '<span class="dashicons dashicons-admin-settings" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>';
                                    echo esc_html__('Check Settings', 'multi-platform-sync') . '</a>';
                                }
                                break;
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Enhanced Error Analysis -->
        <?php if (isset($report['error_analysis']) && $report['error_analysis']['total_errors'] > 0): ?>
        <div class="mps-card">
            <h3 style="display: flex; align-items: center;">
                <span class="dashicons dashicons-warning" style="margin-right: 10px; color: #dc3545;"></span>
                <?php esc_html_e('Error Analysis', 'multi-platform-sync'); ?>
            </h3>
            <div class="mps-error-summary" style="background: #f8d7da; padding: 15px; border-radius: 6px; border-left: 4px solid #dc3545; margin-bottom: 20px;">
                <p style="margin: 0; color: #721c24; font-weight: 600;">
                    <span class="dashicons dashicons-warning" style="margin-right: 8px;"></span>
                    <?php echo esc_html(sprintf(
                        __('Total errors: %s (Error rate: %s%%)', 'multi-platform-sync'),
                        number_format($report['error_analysis']['total_errors']),
                        $report['error_analysis']['error_rate']
                    )); ?>
                </p>
            </div>
            
            <?php if (!empty($report['error_analysis']['common_errors'])): ?>
            <h4 style="color: #2c3e50; margin-bottom: 15px;">
                <span class="dashicons dashicons-list-view" style="margin-right: 8px; color: #667eea;"></span>
                <?php esc_html_e('Most Common Errors', 'multi-platform-sync'); ?>
            </h4>
            <div class="mps-common-errors">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Error Message', 'multi-platform-sync'); ?></th>
                            <th style="width: 120px;"><?php esc_html_e('Integration', 'multi-platform-sync'); ?></th>
                            <th style="width: 80px;"><?php esc_html_e('Count', 'multi-platform-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['error_analysis']['common_errors'] as $error): ?>
                        <tr>
                            <td>
                                <span style="font-family: monospace; font-size: 13px; background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">
                                    <?php echo esc_html(substr($error['error_message'], 0, 100)); ?><?php echo strlen($error['error_message']) > 100 ? '...' : ''; ?>
                                </span>
                            </td>
                            <td>
                                <span class="dashicons dashicons-admin-generic" style="margin-right: 5px; color: #667eea; font-size: 14px;"></span>
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $error['sync_type']))); ?>
                            </td>
                            <td>
                                <strong style="color: #dc3545;"><?php echo esc_html(number_format($error['count'])); ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Enhanced Performance Insights -->
        <?php if (isset($report['performance'])): ?>
        <div class="mps-card">
            <h3 style="display: flex; align-items: center;">
                <span class="dashicons dashicons-chart-line" style="margin-right: 10px; color: #667eea;"></span>
                <?php esc_html_e('Performance Insights', 'multi-platform-sync'); ?>
            </h3>
            <div class="mps-performance-grid">
                <?php if (isset($report['performance']['peak_hour'])): ?>
                <div class="mps-performance-item">
                    <h4>
                        <span class="dashicons dashicons-clock" style="margin-right: 8px;"></span>
                        <?php esc_html_e('Peak Activity Hour', 'multi-platform-sync'); ?>
                    </h4>
                    <p style="font-size: 18px; font-weight: 600; color: #2c3e50; margin: 10px 0;">
                        <?php echo esc_html(sprintf(
                            __('%d:00 (%s syncs)', 'multi-platform-sync'),
                            $report['performance']['peak_hour']['hour'],
                            number_format($report['performance']['peak_hour']['count'])
                        )); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if (isset($report['performance']['most_active_day'])): ?>
                <div class="mps-performance-item">
                    <h4>
                        <span class="dashicons dashicons-calendar-alt" style="margin-right: 8px;"></span>
                        <?php esc_html_e('Most Active Day', 'multi-platform-sync'); ?>
                    </h4>
                    <p style="font-size: 18px; font-weight: 600; color: #2c3e50; margin: 10px 0;">
                        <?php echo esc_html(sprintf(
                            __('%s (%s syncs)', 'multi-platform-sync'),
                            $report['performance']['most_active_day']['day'],
                            number_format($report['performance']['most_active_day']['count'])
                        )); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Enhanced Queue Statistics -->
        <div class="mps-card">
            <h3 style="display: flex; align-items: center;">
                <span class="dashicons dashicons-list-view" style="margin-right: 10px; color: #667eea;"></span>
                <?php esc_html_e('Queue Management', 'multi-platform-sync'); ?>
            </h3>
            <div class="mps-queue-stats">
                <div class="mps-queue-stat">
                    <span class="mps-queue-label">
                        <span class="dashicons dashicons-clock" style="margin-right: 5px;"></span>
                        <?php esc_html_e('Pending', 'multi-platform-sync'); ?>
                    </span>
                    <span class="mps-queue-value" style="color: #ffc107;"><?php echo esc_html(number_format($queue_stats['pending'])); ?></span>
                </div>
                <div class="mps-queue-stat">
                    <span class="mps-queue-label">
                        <span class="dashicons dashicons-update" style="margin-right: 5px;"></span>
                        <?php esc_html_e('Processing', 'multi-platform-sync'); ?>
                    </span>
                    <span class="mps-queue-value" style="color: #17a2b8;"><?php echo esc_html(number_format($queue_stats['processing'])); ?></span>
                </div>
                <div class="mps-queue-stat">
                    <span class="mps-queue-label">
                        <span class="dashicons dashicons-yes-alt" style="margin-right: 5px;"></span>
                        <?php esc_html_e('Completed', 'multi-platform-sync'); ?>
                    </span>
                    <span class="mps-queue-value mps-success"><?php echo esc_html(number_format($queue_stats['completed'])); ?></span>
                </div>
                <div class="mps-queue-stat">
                    <span class="mps-queue-label">
                        <span class="dashicons dashicons-warning" style="margin-right: 5px;"></span>
                        <?php esc_html_e('Failed', 'multi-platform-sync'); ?>
                    </span>
                    <span class="mps-queue-value mps-error"><?php echo esc_html(number_format($queue_stats['failed'])); ?></span>
                </div>
            </div>
            
            <div class="mps-queue-actions">
                <button id="mps-process-queue" class="mps-button-primary">
                    <span class="dashicons dashicons-controls-play" style="margin-right: 8px; font-size: 16px; line-height: 1;"></span>
                    <?php esc_html_e('Process Queue Now', 'multi-platform-sync'); ?>
                </button>
                <button id="mps-clear-completed" class="button">
                    <span class="dashicons dashicons-trash" style="margin-right: 8px; font-size: 16px; line-height: 1;"></span>
                    <?php esc_html_e('Clear Completed Items', 'multi-platform-sync'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Enhanced queue processing with better UX
    $('#mps-process-queue').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php esc_attr_e('Process pending queue items now?', 'multi-platform-sync'); ?>')) {
            return;
        }
        
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true)
               .html('<span class="dashicons dashicons-update" style="margin-right: 8px; animation: spin 1s linear infinite;"></span><?php esc_attr_e('Processing...', 'multi-platform-sync'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mps_process_queue',
                nonce: '<?php echo wp_create_nonce('mps_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $button.html('<span class="dashicons dashicons-yes-alt" style="margin-right: 8px; color: #28a745;"></span><?php esc_attr_e('Success!', 'multi-platform-sync'); ?>');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    $button.html('<span class="dashicons dashicons-warning" style="margin-right: 8px; color: #dc3545;"></span><?php esc_attr_e('Error', 'multi-platform-sync'); ?>');
                    alert(response.data.message);
                }
            },
            error: function() {
                $button.html('<span class="dashicons dashicons-warning" style="margin-right: 8px; color: #dc3545;"></span><?php esc_attr_e('Error', 'multi-platform-sync'); ?>');
                alert('<?php esc_attr_e('Error processing queue.', 'multi-platform-sync'); ?>');
            },
            complete: function() {
                setTimeout(() => {
                    $button.prop('disabled', false).html(originalText);
                }, 3000);
            }
        });
    });
    
    // Enhanced clear completed with better UX
    $('#mps-clear-completed').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php esc_attr_e('Clear completed queue items?', 'multi-platform-sync'); ?>')) {
            return;
        }
        
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true)
               .html('<span class="dashicons dashicons-update" style="margin-right: 8px; animation: spin 1s linear infinite;"></span><?php esc_attr_e('Clearing...', 'multi-platform-sync'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mps_clear_completed_queue',
                nonce: '<?php echo wp_create_nonce('mps_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $button.html('<span class="dashicons dashicons-yes-alt" style="margin-right: 8px; color: #28a745;"></span><?php esc_attr_e('Cleared!', 'multi-platform-sync'); ?>');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    $button.html('<span class="dashicons dashicons-warning" style="margin-right: 8px; color: #dc3545;"></span><?php esc_attr_e('Error', 'multi-platform-sync'); ?>');
                    alert(response.data.message);
                }
            },
            error: function() {
                $button.html('<span class="dashicons dashicons-warning" style="margin-right: 8px; color: #dc3545;"></span><?php esc_attr_e('Error', 'multi-platform-sync'); ?>');
                alert('<?php esc_attr_e('Error clearing queue.', 'multi-platform-sync'); ?>');
            },
            complete: function() {
                setTimeout(() => {
                    $button.prop('disabled', false).html(originalText);
                }, 3000);
            }
        });
    });
});
</script>

<style>
/* Additional CSS for spinning animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Enhanced responsive design for analytics */
@media (max-width: 768px) {
    .mps-analytics-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .mps-analytics-controls {
        display: flex;
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }
    
    .mps-export-actions {
        display: flex;
        gap: 10px;
    }
    
    .mps-summary-cards {
        grid-template-columns: 1fr;
    }
    
    .mps-queue-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .mps-performance-grid {
        grid-template-columns: 1fr;
    }
}
</style>