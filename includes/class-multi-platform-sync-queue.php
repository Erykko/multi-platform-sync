<?php
/**
 * Queue management functionality for the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.1.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

/**
 * Queue management functionality for the plugin.
 *
 * Handles background processing and retry logic for failed syncs.
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 * @author     Eric Mutema <eric@righthereinteractive.com>
 */
class Multi_Platform_Sync_Queue {

    /**
     * Queue table name.
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $table_name    The queue table name.
     */
    private static $table_name;

    /**
     * Initialize the queue system.
     *
     * @since    1.1.0
     */
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'mps_sync_queue';
        
        // Schedule queue processing
        if (!wp_next_scheduled('mps_process_queue')) {
            wp_schedule_event(time(), 'mps_queue_interval', 'mps_process_queue');
        }
        
        add_action('mps_process_queue', array(__CLASS__, 'process_queue'));
        add_filter('cron_schedules', array(__CLASS__, 'add_queue_cron_interval'));
    }

    /**
     * Add custom cron interval for queue processing.
     *
     * @since    1.1.0
     * @param    array    $schedules    Existing cron schedules.
     * @return   array    Modified cron schedules.
     */
    public static function add_queue_cron_interval($schedules) {
        $schedules['mps_queue_interval'] = array(
            'interval' => 300, // 5 minutes
            'display'  => __('Every 5 Minutes (MPS Queue)', 'multi-platform-sync')
        );
        return $schedules;
    }

    /**
     * Add an item to the sync queue.
     *
     * @since    1.1.0
     * @param    string    $sync_type     The type of sync (zapier, campaign_monitor, quickbase).
     * @param    array     $data          The data to sync.
     * @param    int       $priority      Priority level (1-10, 1 being highest).
     * @param    int       $form_id       Optional form ID.
     * @param    string    $entry_id      Optional entry ID.
     * @return   int|false Queue item ID on success, false on failure.
     */
    public static function add_to_queue($sync_type, $data, $priority = 5, $form_id = null, $entry_id = null) {
        global $wpdb;

        if (empty($sync_type) || empty($data)) {
            return false;
        }

        $queue_data = array(
            'sync_type' => sanitize_text_field($sync_type),
            'data' => wp_json_encode($data),
            'priority' => max(1, min(10, intval($priority))),
            'form_id' => $form_id ? intval($form_id) : null,
            'entry_id' => $entry_id ? sanitize_text_field($entry_id) : null,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => current_time('mysql'),
            'scheduled_at' => current_time('mysql')
        );

        $result = $wpdb->insert(
            self::$table_name,
            $queue_data,
            array('%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Process the sync queue.
     *
     * @since    1.1.0
     * @param    int    $batch_size    Number of items to process in one batch.
     */
    public static function process_queue($batch_size = 10) {
        global $wpdb;

        // Get pending items ordered by priority and creation time
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::$table_name . " 
                WHERE status = 'pending' 
                AND scheduled_at <= %s 
                ORDER BY priority ASC, created_at ASC 
                LIMIT %d",
                current_time('mysql'),
                $batch_size
            ),
            ARRAY_A
        );

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            self::process_queue_item($item);
        }
    }

    /**
     * Process a single queue item.
     *
     * @since    1.1.0
     * @param    array    $item    The queue item to process.
     */
    private static function process_queue_item($item) {
        global $wpdb;

        $item_id = intval($item['id']);
        $sync_type = $item['sync_type'];
        $data = json_decode($item['data'], true);
        $attempts = intval($item['attempts']) + 1;

        // Mark as processing
        $wpdb->update(
            self::$table_name,
            array(
                'status' => 'processing',
                'attempts' => $attempts,
                'processed_at' => current_time('mysql')
            ),
            array('id' => $item_id),
            array('%s', '%d', '%s'),
            array('%d')
        );

        try {
            $result = self::execute_sync($sync_type, $data, $item['form_id'], $item['entry_id']);

            if ($result['status'] === 'success') {
                // Mark as completed
                $wpdb->update(
                    self::$table_name,
                    array(
                        'status' => 'completed',
                        'completed_at' => current_time('mysql'),
                        'result_message' => $result['message']
                    ),
                    array('id' => $item_id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );

                self::log_queue_activity($item_id, 'success', $result['message']);
            } else {
                self::handle_failed_item($item_id, $attempts, $result['message']);
            }
        } catch (Exception $e) {
            self::handle_failed_item($item_id, $attempts, $e->getMessage());
        }
    }

    /**
     * Execute the actual sync operation.
     *
     * @since    1.1.0
     * @param    string    $sync_type    The type of sync.
     * @param    array     $data         The data to sync.
     * @param    int       $form_id      Optional form ID.
     * @param    string    $entry_id     Optional entry ID.
     * @return   array     Result of the sync operation.
     */
    private static function execute_sync($sync_type, $data, $form_id = null, $entry_id = null) {
        switch ($sync_type) {
            case 'zapier':
                return self::execute_zapier_sync($data, $form_id, $entry_id);
            
            case 'campaign_monitor':
                return self::execute_campaign_monitor_sync($data, $form_id, $entry_id);
            
            case 'quickbase':
                return self::execute_quickbase_sync($data, $form_id, $entry_id);
            
            default:
                return array(
                    'status' => 'error',
                    'message' => sprintf(__('Unknown sync type: %s', 'multi-platform-sync'), $sync_type)
                );
        }
    }

    /**
     * Execute Zapier sync.
     *
     * @since    1.1.0
     * @param    array     $data       The data to sync.
     * @param    int       $form_id    Optional form ID.
     * @param    string    $entry_id   Optional entry ID.
     * @return   array     Result of the sync operation.
     */
    private static function execute_zapier_sync($data, $form_id = null, $entry_id = null) {
        $zapier = new Multi_Platform_Sync_Zapier('multi-platform-sync', MPS_VERSION);
        
        $webhook_url = get_option('mps_zapier_webhook_url', '');
        if (empty($webhook_url)) {
            return array(
                'status' => 'error',
                'message' => __('Zapier webhook URL not configured.', 'multi-platform-sync')
            );
        }

        // Transform data for Zapier
        $transformed_data = Multi_Platform_Sync_Data_Transformer::transform_for_zapier($data);
        
        // Use reflection to call private method (for queue processing)
        $reflection = new ReflectionClass($zapier);
        $method = $reflection->getMethod('send_to_zapier');
        $method->setAccessible(true);
        
        $response = $method->invoke($zapier, $webhook_url, $transformed_data);
        
        // Process response
        $process_method = $reflection->getMethod('process_zapier_response');
        $process_method->setAccessible(true);
        
        return $process_method->invoke($zapier, $response, $form_id, $entry_id);
    }

    /**
     * Execute Campaign Monitor sync.
     *
     * @since    1.1.0
     * @param    array     $data       The data to sync.
     * @param    int       $form_id    Optional form ID.
     * @param    string    $entry_id   Optional entry ID.
     * @return   array     Result of the sync operation.
     */
    private static function execute_campaign_monitor_sync($data, $form_id = null, $entry_id = null) {
        try {
            $transformed_data = Multi_Platform_Sync_Data_Transformer::transform_for_campaign_monitor($data);
            
            $api_key = get_option('mps_campaign_monitor_api_key', '');
            $list_id = get_option('mps_campaign_monitor_list_id', '');
            
            if (empty($api_key) || empty($list_id)) {
                return array(
                    'status' => 'error',
                    'message' => __('Campaign Monitor settings not configured.', 'multi-platform-sync')
                );
            }

            // Use reflection to call private method
            $cm = new Multi_Platform_Sync_Campaign_Monitor('multi-platform-sync', MPS_VERSION);
            $reflection = new ReflectionClass($cm);
            $method = $reflection->getMethod('add_subscriber_to_campaign_monitor');
            $method->setAccessible(true);
            
            return $method->invoke($cm, $api_key, $list_id, $transformed_data);
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Execute Quickbase sync.
     *
     * @since    1.1.0
     * @param    array     $data       The data to sync.
     * @param    int       $form_id    Optional form ID.
     * @param    string    $entry_id   Optional entry ID.
     * @return   array     Result of the sync operation.
     */
    private static function execute_quickbase_sync($data, $form_id = null, $entry_id = null) {
        try {
            $transformed_data = Multi_Platform_Sync_Data_Transformer::transform_for_quickbase($data);
            
            $realm_hostname = get_option('mps_quickbase_realm_hostname', '');
            $user_token = get_option('mps_quickbase_user_token', '');
            $app_id = get_option('mps_quickbase_app_id', '');
            $table_id = get_option('mps_quickbase_table_id', '');
            
            if (empty($realm_hostname) || empty($user_token) || empty($app_id) || empty($table_id)) {
                return array(
                    'status' => 'error',
                    'message' => __('Quickbase settings not configured.', 'multi-platform-sync')
                );
            }

            // Use reflection to call private method
            $qb = new Multi_Platform_Sync_Quickbase('multi-platform-sync', MPS_VERSION);
            $reflection = new ReflectionClass($qb);
            $method = $reflection->getMethod('add_record_to_quickbase');
            $method->setAccessible(true);
            
            return $method->invoke($qb, $realm_hostname, $user_token, $app_id, $table_id, $transformed_data);
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Handle a failed queue item.
     *
     * @since    1.1.0
     * @param    int       $item_id     The queue item ID.
     * @param    int       $attempts    Number of attempts made.
     * @param    string    $error_msg   The error message.
     */
    private static function handle_failed_item($item_id, $attempts, $error_msg) {
        global $wpdb;

        $max_attempts = apply_filters('mps_queue_max_attempts', 3);
        
        if ($attempts >= $max_attempts) {
            // Mark as failed permanently
            $wpdb->update(
                self::$table_name,
                array(
                    'status' => 'failed',
                    'result_message' => $error_msg,
                    'failed_at' => current_time('mysql')
                ),
                array('id' => $item_id),
                array('%s', '%s', '%s'),
                array('%d')
            );

            self::log_queue_activity($item_id, 'failed', $error_msg);
        } else {
            // Schedule for retry with exponential backoff
            $retry_delay = pow(2, $attempts) * 60; // 2, 4, 8 minutes
            $scheduled_at = date('Y-m-d H:i:s', time() + $retry_delay);
            
            $wpdb->update(
                self::$table_name,
                array(
                    'status' => 'pending',
                    'scheduled_at' => $scheduled_at,
                    'result_message' => $error_msg
                ),
                array('id' => $item_id),
                array('%s', '%s', '%s'),
                array('%d')
            );

            self::log_queue_activity($item_id, 'retry', sprintf(
                __('Attempt %d failed: %s. Retrying in %d minutes.', 'multi-platform-sync'),
                $attempts,
                $error_msg,
                $retry_delay / 60
            ));
        }
    }

    /**
     * Get queue statistics.
     *
     * @since    1.1.0
     * @return   array    Queue statistics.
     */
    public static function get_queue_stats() {
        global $wpdb;

        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM " . self::$table_name,
            ARRAY_A
        );

        return $stats ?: array(
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0
        );
    }

    /**
     * Clear completed queue items older than specified days.
     *
     * @since    1.1.0
     * @param    int    $days    Number of days to keep completed items.
     * @return   int    Number of items cleared.
     */
    public static function clear_old_items($days = 7) {
        global $wpdb;

        $cutoff_date = date('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . self::$table_name . " 
                WHERE status IN ('completed', 'failed') 
                AND (completed_at < %s OR failed_at < %s)",
                $cutoff_date,
                $cutoff_date
            )
        );

        return $result ?: 0;
    }

    /**
     * Log queue activity.
     *
     * @since    1.1.0
     * @param    int       $queue_id    The queue item ID.
     * @param    string    $status      The status.
     * @param    string    $message     The message.
     */
    private static function log_queue_activity($queue_id, $status, $message) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'mps_sync_logs';
        
        $wpdb->insert(
            $logs_table,
            array(
                'timestamp' => current_time('mysql'),
                'sync_type' => 'queue',
                'entry_id' => strval($queue_id),
                'status' => $status,
                'message' => sprintf(__('Queue Item #%d: %s', 'multi-platform-sync'), $queue_id, $message)
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Create the queue table.
     *
     * @since    1.1.0
     */
    public static function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE " . self::$table_name . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sync_type varchar(50) NOT NULL,
            data longtext NOT NULL,
            priority tinyint(2) NOT NULL DEFAULT 5,
            form_id mediumint(9) NULL,
            entry_id varchar(50) NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts tinyint(3) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            scheduled_at datetime NOT NULL,
            processed_at datetime NULL,
            completed_at datetime NULL,
            failed_at datetime NULL,
            result_message text NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY sync_type (sync_type),
            KEY scheduled_at (scheduled_at),
            KEY priority (priority)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}