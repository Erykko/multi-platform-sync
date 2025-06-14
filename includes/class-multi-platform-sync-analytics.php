<?php
/**
 * Analytics and reporting functionality for the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.1.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

/**
 * Analytics and reporting functionality for the plugin.
 *
 * Provides insights into sync performance and data flow.
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 * @author     Eric Mutema <eric@righthereinteractive.com>
 */
class Multi_Platform_Sync_Analytics {

    /**
     * Get sync statistics for a date range.
     *
     * @since    1.1.0
     * @param    string    $start_date    Start date (Y-m-d format).
     * @param    string    $end_date      End date (Y-m-d format).
     * @return   array     Sync statistics.
     */
    public static function get_sync_stats($start_date = null, $end_date = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mps_sync_logs';

        // Default to last 30 days if no dates provided
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        $stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    sync_type,
                    status,
                    COUNT(*) as count,
                    DATE(timestamp) as date
                FROM {$table_name}
                WHERE DATE(timestamp) BETWEEN %s AND %s
                GROUP BY sync_type, status, DATE(timestamp)
                ORDER BY date DESC, sync_type, status",
                $start_date,
                $end_date
            ),
            ARRAY_A
        );

        return self::process_sync_stats($stats);
    }

    /**
     * Process raw sync statistics into a structured format.
     *
     * @since    1.1.0
     * @param    array    $raw_stats    Raw statistics from database.
     * @return   array    Processed statistics.
     */
    private static function process_sync_stats($raw_stats) {
        $processed = array(
            'summary' => array(
                'total_syncs' => 0,
                'successful_syncs' => 0,
                'failed_syncs' => 0,
                'success_rate' => 0
            ),
            'by_type' => array(),
            'by_date' => array(),
            'trends' => array()
        );

        foreach ($raw_stats as $stat) {
            $sync_type = $stat['sync_type'];
            $status = $stat['status'];
            $count = intval($stat['count']);
            $date = $stat['date'];

            // Update summary
            $processed['summary']['total_syncs'] += $count;
            if ($status === 'success') {
                $processed['summary']['successful_syncs'] += $count;
            } else {
                $processed['summary']['failed_syncs'] += $count;
            }

            // Group by type
            if (!isset($processed['by_type'][$sync_type])) {
                $processed['by_type'][$sync_type] = array(
                    'total' => 0,
                    'success' => 0,
                    'error' => 0
                );
            }
            $processed['by_type'][$sync_type]['total'] += $count;
            $processed['by_type'][$sync_type][$status] += $count;

            // Group by date
            if (!isset($processed['by_date'][$date])) {
                $processed['by_date'][$date] = array(
                    'total' => 0,
                    'success' => 0,
                    'error' => 0
                );
            }
            $processed['by_date'][$date]['total'] += $count;
            $processed['by_date'][$date][$status] += $count;
        }

        // Calculate success rate
        if ($processed['summary']['total_syncs'] > 0) {
            $processed['summary']['success_rate'] = round(
                ($processed['summary']['successful_syncs'] / $processed['summary']['total_syncs']) * 100,
                2
            );
        }

        // Calculate success rates for each type
        foreach ($processed['by_type'] as $type => &$data) {
            $data['success_rate'] = $data['total'] > 0 ? 
                round(($data['success'] / $data['total']) * 100, 2) : 0;
        }

        return $processed;
    }

    /**
     * Get performance metrics.
     *
     * @since    1.1.0
     * @param    int    $days    Number of days to analyze.
     * @return   array  Performance metrics.
     */
    public static function get_performance_metrics($days = 7) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mps_sync_logs';

        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get average response times (if we start tracking them)
        $metrics = array(
            'avg_daily_syncs' => 0,
            'peak_hour' => null,
            'most_active_day' => null,
            'error_patterns' => array(),
            'form_performance' => array()
        );

        // Average daily syncs
        $daily_avg = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(daily_count) FROM (
                    SELECT DATE(timestamp) as date, COUNT(*) as daily_count
                    FROM {$table_name}
                    WHERE timestamp >= %s
                    GROUP BY DATE(timestamp)
                ) as daily_stats",
                $start_date
            )
        );
        $metrics['avg_daily_syncs'] = round(floatval($daily_avg), 2);

        // Peak hour analysis
        $peak_hour = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT HOUR(timestamp) as hour, COUNT(*) as count
                FROM {$table_name}
                WHERE timestamp >= %s
                GROUP BY HOUR(timestamp)
                ORDER BY count DESC
                LIMIT 1",
                $start_date
            ),
            ARRAY_A
        );
        if ($peak_hour) {
            $metrics['peak_hour'] = array(
                'hour' => intval($peak_hour['hour']),
                'count' => intval($peak_hour['count'])
            );
        }

        // Most active day
        $active_day = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT DAYNAME(timestamp) as day, COUNT(*) as count
                FROM {$table_name}
                WHERE timestamp >= %s
                GROUP BY DAYNAME(timestamp)
                ORDER BY count DESC
                LIMIT 1",
                $start_date
            ),
            ARRAY_A
        );
        if ($active_day) {
            $metrics['most_active_day'] = array(
                'day' => $active_day['day'],
                'count' => intval($active_day['count'])
            );
        }

        // Error patterns
        $error_patterns = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    SUBSTRING(message, 1, 100) as error_pattern,
                    COUNT(*) as count
                FROM {$table_name}
                WHERE timestamp >= %s AND status = 'error'
                GROUP BY SUBSTRING(message, 1, 100)
                ORDER BY count DESC
                LIMIT 5",
                $start_date
            ),
            ARRAY_A
        );
        $metrics['error_patterns'] = $error_patterns;

        // Form performance (if form_id is available)
        $form_performance = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    form_id,
                    COUNT(*) as total_syncs,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_syncs,
                    ROUND((SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as success_rate
                FROM {$table_name}
                WHERE timestamp >= %s AND form_id IS NOT NULL
                GROUP BY form_id
                ORDER BY total_syncs DESC
                LIMIT 10",
                $start_date
            ),
            ARRAY_A
        );
        $metrics['form_performance'] = $form_performance;

        return $metrics;
    }

    /**
     * Get data flow analysis.
     *
     * @since    1.1.0
     * @param    int    $days    Number of days to analyze.
     * @return   array  Data flow analysis.
     */
    public static function get_data_flow_analysis($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mps_sync_logs';

        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $flow_data = array(
            'sources' => array(),
            'destinations' => array(),
            'conversion_funnel' => array()
        );

        // Analyze sync types as data flow
        $sync_flow = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    sync_type,
                    status,
                    COUNT(*) as count
                FROM {$table_name}
                WHERE timestamp >= %s
                GROUP BY sync_type, status
                ORDER BY sync_type, status",
                $start_date
            ),
            ARRAY_A
        );

        foreach ($sync_flow as $flow) {
            $type = $flow['sync_type'];
            $status = $flow['status'];
            $count = intval($flow['count']);

            if (!isset($flow_data['destinations'][$type])) {
                $flow_data['destinations'][$type] = array(
                    'total' => 0,
                    'success' => 0,
                    'error' => 0
                );
            }

            $flow_data['destinations'][$type]['total'] += $count;
            $flow_data['destinations'][$type][$status] += $count;
        }

        // Create conversion funnel
        $total_attempts = array_sum(array_column($flow_data['destinations'], 'total'));
        $total_successes = array_sum(array_column($flow_data['destinations'], 'success'));

        $flow_data['conversion_funnel'] = array(
            'form_submissions' => $total_attempts,
            'sync_attempts' => $total_attempts,
            'successful_syncs' => $total_successes,
            'conversion_rate' => $total_attempts > 0 ? round(($total_successes / $total_attempts) * 100, 2) : 0
        );

        return $flow_data;
    }

    /**
     * Generate a sync report.
     *
     * @since    1.1.0
     * @param    array    $options    Report options.
     * @return   array    Generated report.
     */
    public static function generate_report($options = array()) {
        $defaults = array(
            'period' => '30days',
            'include_performance' => true,
            'include_errors' => true,
            'include_recommendations' => true
        );

        $options = wp_parse_args($options, $defaults);

        // Determine date range
        switch ($options['period']) {
            case '7days':
                $days = 7;
                break;
            case '30days':
                $days = 30;
                break;
            case '90days':
                $days = 90;
                break;
            default:
                $days = 30;
        }

        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date = date('Y-m-d');

        $report = array(
            'period' => array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'days' => $days
            ),
            'generated_at' => current_time('mysql'),
            'stats' => self::get_sync_stats($start_date, $end_date)
        );

        if ($options['include_performance']) {
            $report['performance'] = self::get_performance_metrics($days);
        }

        if ($options['include_errors']) {
            $report['error_analysis'] = self::get_error_analysis($days);
        }

        if ($options['include_recommendations']) {
            $report['recommendations'] = self::generate_recommendations($report);
        }

        return $report;
    }

    /**
     * Get error analysis.
     *
     * @since    1.1.0
     * @param    int    $days    Number of days to analyze.
     * @return   array  Error analysis.
     */
    private static function get_error_analysis($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mps_sync_logs';

        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $error_analysis = array(
            'total_errors' => 0,
            'error_rate' => 0,
            'common_errors' => array(),
            'error_trends' => array()
        );

        // Total errors
        $total_errors = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} 
                WHERE timestamp >= %s AND status = 'error'",
                $start_date
            )
        );
        $error_analysis['total_errors'] = intval($total_errors);

        // Error rate
        $total_syncs = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} 
                WHERE timestamp >= %s",
                $start_date
            )
        );
        if ($total_syncs > 0) {
            $error_analysis['error_rate'] = round(($total_errors / $total_syncs) * 100, 2);
        }

        // Common errors
        $common_errors = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    SUBSTRING(message, 1, 200) as error_message,
                    COUNT(*) as count,
                    sync_type
                FROM {$table_name}
                WHERE timestamp >= %s AND status = 'error'
                GROUP BY SUBSTRING(message, 1, 200), sync_type
                ORDER BY count DESC
                LIMIT 10",
                $start_date
            ),
            ARRAY_A
        );
        $error_analysis['common_errors'] = $common_errors;

        return $error_analysis;
    }

    /**
     * Generate recommendations based on report data.
     *
     * @since    1.1.0
     * @param    array    $report    The report data.
     * @return   array    Recommendations.
     */
    private static function generate_recommendations($report) {
        $recommendations = array();

        // Check success rate
        if (isset($report['stats']['summary']['success_rate'])) {
            $success_rate = $report['stats']['summary']['success_rate'];
            
            if ($success_rate < 80) {
                $recommendations[] = array(
                    'type' => 'warning',
                    'title' => __('Low Success Rate', 'multi-platform-sync'),
                    'message' => sprintf(
                        __('Your sync success rate is %s%%. Consider reviewing error logs and API configurations.', 'multi-platform-sync'),
                        $success_rate
                    ),
                    'action' => 'review_errors'
                );
            } elseif ($success_rate >= 95) {
                $recommendations[] = array(
                    'type' => 'success',
                    'title' => __('Excellent Performance', 'multi-platform-sync'),
                    'message' => sprintf(
                        __('Your sync success rate is %s%%. Great job!', 'multi-platform-sync'),
                        $success_rate
                    )
                );
            }
        }

        // Check for high error rates in specific sync types
        if (isset($report['stats']['by_type'])) {
            foreach ($report['stats']['by_type'] as $type => $data) {
                if ($data['total'] > 10 && $data['success_rate'] < 70) {
                    $recommendations[] = array(
                        'type' => 'error',
                        'title' => sprintf(__('%s Integration Issues', 'multi-platform-sync'), ucfirst($type)),
                        'message' => sprintf(
                            __('The %s integration has a low success rate of %s%%. Check API credentials and settings.', 'multi-platform-sync'),
                            $type,
                            $data['success_rate']
                        ),
                        'action' => 'check_' . $type . '_settings'
                    );
                }
            }
        }

        // Check for rate limiting issues
        if (isset($report['error_analysis']['common_errors'])) {
            foreach ($report['error_analysis']['common_errors'] as $error) {
                if (stripos($error['error_message'], 'rate limit') !== false) {
                    $recommendations[] = array(
                        'type' => 'info',
                        'title' => __('Rate Limiting Detected', 'multi-platform-sync'),
                        'message' => __('Consider enabling queue processing or adjusting rate limit settings to improve reliability.', 'multi-platform-sync'),
                        'action' => 'enable_queue'
                    );
                    break;
                }
            }
        }

        return $recommendations;
    }

    /**
     * Export report data.
     *
     * @since    1.1.0
     * @param    array     $report    The report data.
     * @param    string    $format    Export format (json, csv).
     * @return   string    Exported data.
     */
    public static function export_report($report, $format = 'json') {
        switch ($format) {
            case 'csv':
                return self::export_to_csv($report);
            case 'json':
            default:
                return wp_json_encode($report, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Export report to CSV format.
     *
     * @since    1.1.0
     * @param    array    $report    The report data.
     * @return   string   CSV data.
     */
    private static function export_to_csv($report) {
        $csv_data = array();
        
        // Add summary
        $csv_data[] = array('Metric', 'Value');
        $csv_data[] = array('Period', $report['period']['start_date'] . ' to ' . $report['period']['end_date']);
        $csv_data[] = array('Total Syncs', $report['stats']['summary']['total_syncs']);
        $csv_data[] = array('Successful Syncs', $report['stats']['summary']['successful_syncs']);
        $csv_data[] = array('Failed Syncs', $report['stats']['summary']['failed_syncs']);
        $csv_data[] = array('Success Rate', $report['stats']['summary']['success_rate'] . '%');
        $csv_data[] = array(''); // Empty row
        
        // Add by type data
        $csv_data[] = array('Sync Type', 'Total', 'Success', 'Error', 'Success Rate');
        foreach ($report['stats']['by_type'] as $type => $data) {
            $csv_data[] = array(
                $type,
                $data['total'],
                $data['success'],
                $data['error'],
                $data['success_rate'] . '%'
            );
        }
        
        // Convert to CSV string
        $output = '';
        foreach ($csv_data as $row) {
            $output .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }
        
        return $output;
    }
}