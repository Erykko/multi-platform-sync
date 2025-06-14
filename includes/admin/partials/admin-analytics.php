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

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="mps-analytics-header">
        <div class="mps-period-selector">
            <label for="mps-period-select"><?php esc_html_e('Period:', 'multi-platform-sync'); ?></label>
            <select id="mps-period-select" onchange="window.location.href='?page=multi-platform-sync-analytics&period=' + this.value;">
                <option value="7days" <?php selected($period, '7days'); ?>><?php esc_html_e('Last 7 Days', 'multi-platform-sync'); ?></option>
                <option value="30days" <?php selected($period, '30days'); ?>><?php esc_html_e('Last 30 Days', 'multi-platform-sync'); ?></option>
                <option value="90days" <?php selected($period, '90days'); ?>><?php esc_html_e('Last 90 Days', 'multi-platform-sync'); ?></option>
            </select>
        </div>
        
        <div class="mps-export-actions">
            <a href="?page=multi-platform-sync-analytics&period=<?php echo esc_attr($period); ?>&export=json" class="button">
                <?php esc_html_e('Export JSON', 'multi-platform-sync'); ?>
            </a>
            <a href="?page=multi-platform-sync-analytics&period=<?php echo esc_attr($period); ?>&export=csv" class="button">
                <?php esc_html_e('Export CSV', 'multi-platform-sync'); ?>
            </a>
        </div>
    </div>

    <div class="mps-analytics-dashboard">
        <!-- Summary Cards -->
        <div class="mps-summary-cards">
            <div class="mps-card mps-summary-card">
                <h3><?php esc_html_e('Total Syncs', 'multi-platform-sync'); ?></h3>
                <div class="mps-metric-value"><?php echo esc_html(number_format($report['stats']['summary']['total_syncs'])); ?></div>
                <div class="mps-metric-period"><?php echo esc_html(sprintf(__('Last %d days', 'multi-platform-sync'), $report['period']['days'])); ?></div>
            </div>
            
            <div class="mps-card mps-summary-card">
                <h3><?php esc_html_e('Success Rate', 'multi-platform-sync'); ?></h3>
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
                <h3><?php esc_html_e('Queue Status', 'multi-platform-sync'); ?></h3>
                <div class="mps-metric-value"><?php echo esc_html(number_format($queue_stats['pending'])); ?></div>
                <div class="mps-metric-details"><?php esc_html_e('Pending items', 'multi-platform-sync'); ?></div>
            </div>
            
            <?php if (isset($report['performance']['avg_daily_syncs'])): ?>
            <div class="mps-card mps-summary-card">
                <h3><?php esc_html_e('Daily Average', 'multi-platform-sync'); ?></h3>
                <div class="mps-metric-value"><?php echo esc_html(number_format($report['performance']['avg_daily_syncs'], 1)); ?></div>
                <div class="mps-metric-details"><?php esc_html_e('Syncs per day', 'multi-platform-sync'); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sync Types Performance -->
        <div class="mps-card">
            <h3><?php esc_html_e('Performance by Integration', 'multi-platform-sync'); ?></h3>
            <div class="mps-sync-types-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Integration', 'multi-platform-sync'); ?></th>
                            <th><?php esc_html_e('Total Syncs', 'multi-platform-sync'); ?></th>
                            <th><?php esc_html_e('Successful', 'multi-platform-sync'); ?></th>
                            <th><?php esc_html_e('Failed', 'multi-platform-sync'); ?></th>
                            <th><?php esc_html_e('Success Rate', 'multi-platform-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['stats']['by_type'] as $type => $data): ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucfirst($type)); ?></strong></td>
                            <td><?php echo esc_html(number_format($data['total'])); ?></td>
                            <td class="mps-success"><?php echo esc_html(number_format($data['success'])); ?></td>
                            <td class="mps-error"><?php echo esc_html(number_format($data['error'])); ?></td>
                            <td>
                                <span class="mps-success-rate-badge <?php echo $data['success_rate'] >= 90 ? 'good' : ($data['success_rate'] >= 70 ? 'warning' : 'poor'); ?>">
                                    <?php echo esc_html($data['success_rate']); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recommendations -->
        <?php if (!empty($report['recommendations'])): ?>
        <div class="mps-card">
            <h3><?php esc_html_e('Recommendations', 'multi-platform-sync'); ?></h3>
            <div class="mps-recommendations">
                <?php foreach ($report['recommendations'] as $recommendation): ?>
                <div class="mps-recommendation mps-recommendation-<?php echo esc_attr($recommendation['type']); ?>">
                    <h4><?php echo esc_html($recommendation['title']); ?></h4>
                    <p><?php echo esc_html($recommendation['message']); ?></p>
                    <?php if (isset($recommendation['action'])): ?>
                    <div class="mps-recommendation-action">
                        <?php
                        switch ($recommendation['action']) {
                            case 'review_errors':
                                echo '<a href="' . esc_url(admin_url('admin.php?page=multi-platform-sync-logs')) . '" class="button">' . esc_html__('Review Error Logs', 'multi-platform-sync') . '</a>';
                                break;
                            case 'enable_queue':
                                echo '<a href="' . esc_url(admin_url('admin.php?page=multi-platform-sync-settings')) . '" class="button">' . esc_html__('Check Settings', 'multi-platform-sync') . '</a>';
                                break;
                            default:
                                if (strpos($recommendation['action'], 'check_') === 0) {
                                    echo '<a href="' . esc_url(admin_url('admin.php?page=multi-platform-sync-settings')) . '" class="button">' . esc_html__('Check Settings', 'multi-platform-sync') . '</a>';
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

        <!-- Error Analysis -->
        <?php if (isset($report['error_analysis']) && $report['error_analysis']['total_errors'] > 0): ?>
        <div class="mps-card">
            <h3><?php esc_html_e('Error Analysis', 'multi-platform-sync'); ?></h3>
            <div class="mps-error-summary">
                <p>
                    <?php echo esc_html(sprintf(
                        __('Total errors: %s (Error rate: %s%%)', 'multi-platform-sync'),
                        number_format($report['error_analysis']['total_errors']),
                        $report['error_analysis']['error_rate']
                    )); ?>
                </p>
            </div>
            
            <?php if (!empty($report['error_analysis']['common_errors'])): ?>
            <h4><?php esc_html_e('Most Common Errors', 'multi-platform-sync'); ?></h4>
            <div class="mps-common-errors">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Error Message', 'multi-platform-sync'); ?></th>
                            <th><?php esc_html_e('Integration', 'multi-platform-sync'); ?></th>
                            <th><?php esc_html_e('Count', 'multi-platform-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['error_analysis']['common_errors'] as $error): ?>
                        <tr>
                            <td><?php echo esc_html(substr($error['error_message'], 0, 100)); ?><?php echo strlen($error['error_message']) > 100 ? '...' : ''; ?></td>
                            <td><?php echo esc_html(ucfirst($error['sync_type'])); ?></td>
                            <td><?php echo esc_html(number_format($error['count'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Performance Insights -->
        <?php if (isset($report['performance'])): ?>
        <div class="mps-card">
            <h3><?php esc_html_e('Performance Insights', 'multi-platform-sync'); ?></h3>
            <div class="mps-performance-grid">
                <?php if (isset($report['performance']['peak_hour'])): ?>
                <div class="mps-performance-item">
                    <h4><?php esc_html_e('Peak Activity Hour', 'multi-platform-sync'); ?></h4>
                    <p><?php echo esc_html(sprintf(
                        __('%d:00 (%s syncs)', 'multi-platform-sync'),
                        $report['performance']['peak_hour']['hour'],
                        number_format($report['performance']['peak_hour']['count'])
                    )); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (isset($report['performance']['most_active_day'])): ?>
                <div class="mps-performance-item">
                    <h4><?php esc_html_e('Most Active Day', 'multi-platform-sync'); ?></h4>
                    <p><?php echo esc_html(sprintf(
                        __('%s (%s syncs)', 'multi-platform-sync'),
                        $report['performance']['most_active_day']['day'],
                        number_format($report['performance']['most_active_day']['count'])
                    )); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Queue Statistics -->
        <div class="mps-card">
            <h3><?php esc_html_e('Queue Statistics', 'multi-platform-sync'); ?></h3>
            <div class="mps-queue-stats">
                <div class="mps-queue-stat">
                    <span class="mps-queue-label"><?php esc_html_e('Pending:', 'multi-platform-sync'); ?></span>
                    <span class="mps-queue-value"><?php echo esc_html(number_format($queue_stats['pending'])); ?></span>
                </div>
                <div class="mps-queue-stat">
                    <span class="mps-queue-label"><?php esc_html_e('Processing:', 'multi-platform-sync'); ?></span>
                    <span class="mps-queue-value"><?php echo esc_html(number_format($queue_stats['processing'])); ?></span>
                </div>
                <div class="mps-queue-stat">
                    <span class="mps-queue-label"><?php esc_html_e('Completed:', 'multi-platform-sync'); ?></span>
                    <span class="mps-queue-value mps-success"><?php echo esc_html(number_format($queue_stats['completed'])); ?></span>
                </div>
                <div class="mps-queue-stat">
                    <span class="mps-queue-label"><?php esc_html_e('Failed:', 'multi-platform-sync'); ?></span>
                    <span class="mps-queue-value mps-error"><?php echo esc_html(number_format($queue_stats['failed'])); ?></span>
                </div>
            </div>
            
            <div class="mps-queue-actions">
                <button id="mps-process-queue" class="button">
                    <?php esc_html_e('Process Queue Now', 'multi-platform-sync'); ?>
                </button>
                <button id="mps-clear-completed" class="button">
                    <?php esc_html_e('Clear Completed Items', 'multi-platform-sync'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.mps-analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.mps-period-selector select {
    margin-left: 10px;
}

.mps-export-actions .button {
    margin-left: 10px;
}

.mps-summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    grid-gap: 20px;
    margin-bottom: 20px;
}

.mps-summary-card {
    text-align: center;
    padding: 20px;
}

.mps-metric-value {
    font-size: 2.5em;
    font-weight: bold;
    color: #0073aa;
    margin: 10px 0;
}

.mps-success-rate {
    color: #46b450;
}

.mps-metric-period,
.mps-metric-details {
    color: #666;
    font-size: 0.9em;
}

.mps-success-rate-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-weight: bold;
    font-size: 0.9em;
}

.mps-success-rate-badge.good {
    background: #d4edda;
    color: #155724;
}

.mps-success-rate-badge.warning {
    background: #fff3cd;
    color: #856404;
}

.mps-success-rate-badge.poor {
    background: #f8d7da;
    color: #721c24;
}

.mps-recommendations {
    display: grid;
    grid-gap: 15px;
}

.mps-recommendation {
    padding: 15px;
    border-left: 4px solid;
    border-radius: 4px;
}

.mps-recommendation-success {
    background: #d4edda;
    border-color: #28a745;
}

.mps-recommendation-warning {
    background: #fff3cd;
    border-color: #ffc107;
}

.mps-recommendation-error {
    background: #f8d7da;
    border-color: #dc3545;
}

.mps-recommendation-info {
    background: #d1ecf1;
    border-color: #17a2b8;
}

.mps-recommendation h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.mps-recommendation-action {
    margin-top: 10px;
}

.mps-performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    grid-gap: 20px;
}

.mps-performance-item h4 {
    margin-bottom: 5px;
    color: #0073aa;
}

.mps-queue-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
}

.mps-queue-stat {
    text-align: center;
}

.mps-queue-label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.mps-queue-value {
    display: block;
    font-size: 1.5em;
    font-weight: bold;
}

.mps-queue-actions {
    text-align: center;
}

.mps-queue-actions .button {
    margin: 0 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#mps-process-queue').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php esc_attr_e('Process pending queue items now?', 'multi-platform-sync'); ?>')) {
            return;
        }
        
        $(this).prop('disabled', true).text('<?php esc_attr_e('Processing...', 'multi-platform-sync'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mps_process_queue',
                nonce: '<?php echo wp_create_nonce('mps_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php esc_attr_e('Error processing queue.', 'multi-platform-sync'); ?>');
            },
            complete: function() {
                $('#mps-process-queue').prop('disabled', false).text('<?php esc_attr_e('Process Queue Now', 'multi-platform-sync'); ?>');
            }
        });
    });
    
    $('#mps-clear-completed').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php esc_attr_e('Clear completed queue items?', 'multi-platform-sync'); ?>')) {
            return;
        }
        
        $(this).prop('disabled', true).text('<?php esc_attr_e('Clearing...', 'multi-platform-sync'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mps_clear_completed_queue',
                nonce: '<?php echo wp_create_nonce('mps_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php esc_attr_e('Error clearing queue.', 'multi-platform-sync'); ?>');
            },
            complete: function() {
                $('#mps-clear-completed').prop('disabled', false).text('<?php esc_attr_e('Clear Completed Items', 'multi-platform-sync'); ?>');
            }
        });
    });
});
</script>