<?php
/**
 * The Quickbase integration functionality of the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.0.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes/integrations/quickbase
 */

/**
 * The Quickbase integration functionality of the plugin.
 *
 * Defines the methods necessary to process data from Zapier for Quickbase.
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes/integrations/quickbase
 * @author     Your Name <email@righthereinteractive.com>
 */
class Multi_Platform_Sync_Quickbase {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Process data from Zapier and send to Quickbase.
     *
     * @since    1.0.0
     * @param    array    $response    The response from Zapier.
     */
    public function process_zapier_data($response) {
        // Validate response format
        if (!is_array($response) || !isset($response['data']) || !is_array($response['data'])) {
            $this->log_sync_activity(
                'quickbase',
                'error',
                __('Invalid response format from Zapier.', 'multi-platform-sync')
            );
            return;
        }
        
        // Get form and entry IDs for logging
        $form_id = isset($response['data']['form_id']) ? intval($response['data']['form_id']) : null;
        $entry_id = isset($response['data']['entry_id']) ? sanitize_text_field($response['data']['entry_id']) : null;
        
        // Get Quickbase settings
        $realm_hostname = get_option('mps_quickbase_realm_hostname', '');
        $user_token = get_option('mps_quickbase_user_token', '');
        $app_id = get_option('mps_quickbase_app_id', '');
        $table_id = get_option('mps_quickbase_table_id', '');
        
        if (empty($realm_hostname) || empty($user_token) || empty($app_id) || empty($table_id)) {
            $this->log_sync_activity(
                'quickbase',
                'error',
                __('Quickbase settings are not fully configured.', 'multi-platform-sync'),
                $form_id,
                $entry_id
            );
            return;
        }
        
        // Prepare record data
        $record_data = $this->prepare_record_data($response['data']);
        if (empty($record_data)) {
            $this->log_sync_activity(
                'quickbase',
                'error',
                __('No valid data found to create Quickbase record.', 'multi-platform-sync'),
                $form_id,
                $entry_id
            );
            return;
        }
        
        // Send data to Quickbase
        $result = $this->add_record_to_quickbase($realm_hostname, $user_token, $app_id, $table_id, $record_data);
        
        // Log the result
        if ($result['status'] === 'success') {
            $this->log_sync_activity(
                'quickbase',
                'success',
                sprintf(__('Successfully added record to Quickbase. Record ID: %s', 'multi-platform-sync'), $result['record_id']),
                $form_id,
                $entry_id
            );
        } else {
            $this->log_sync_activity(
                'quickbase',
                'error',
                sprintf(__('Error adding record to Quickbase: %s', 'multi-platform-sync'), $result['message']),
                $form_id,
                $entry_id
            );
        }
    }
    
    /**
     * Prepare record data for Quickbase.
     *
     * @since    1.0.0
     * @param    array    $data    The form data.
     * @return   array    The prepared record data.
     */
    private function prepare_record_data($data) {
        if (!is_array($data)) {
            return array();
        }
        
        $record_data = array();
        
        // Add all fields to the record
        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $field_id => $field_data) {
                if (!isset($field_data['label']) || !isset($field_data['value'])) {
                    continue;
                }
                
                // Skip empty values
                if ((is_string($field_data['value']) && trim($field_data['value']) === '') || 
                    is_null($field_data['value'])) {
                    continue;
                }
                
                // Sanitize field name for Quickbase
                $field_name = $this->sanitize_field_name($field_data['label']);
                if (empty($field_name)) {
                    continue;
                }
                
                // Sanitize field value based on type
                $value = $field_data['value'];
                if (is_string($value)) {
                    $value = sanitize_text_field(trim($value));
                } elseif (is_numeric($value)) {
                    $value = floatval($value);
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    $value = implode(', ', array_map('sanitize_text_field', $value));
                } else {
                    $value = sanitize_text_field(strval($value));
                }
                
                $record_data[$field_name] = array('value' => $value);
            }
        }
        
        // Add form metadata
        if (isset($data['form_id']) && !empty($data['form_id'])) {
            $record_data['FormID'] = array('value' => intval($data['form_id']));
        }
        
        if (isset($data['form_title']) && !empty($data['form_title'])) {
            $record_data['FormName'] = array('value' => sanitize_text_field($data['form_title']));
        }
        
        if (isset($data['entry_id']) && !empty($data['entry_id'])) {
            $record_data['EntryID'] = array('value' => sanitize_text_field($data['entry_id']));
        }
        
        if (isset($data['date_created']) && !empty($data['date_created'])) {
            $record_data['DateSubmitted'] = array('value' => sanitize_text_field($data['date_created']));
        }
        
        return $record_data;
    }
    
    /**
     * Sanitize field name for Quickbase.
     *
     * @since    1.0.0
     * @param    string    $field_name    The original field name.
     * @return   string    The sanitized field name.
     */
    private function sanitize_field_name($field_name) {
        if (!is_string($field_name) || empty($field_name)) {
            return '';
        }
        
        // Replace spaces with underscores
        $sanitized = str_replace(' ', '_', trim($field_name));
        
        // Remove any non-alphanumeric characters except underscore
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', $sanitized);
        
        // Truncate to maximum field name length (Quickbase typically allows 255 chars)
        $sanitized = substr($sanitized, 0, 255);
        
        // Ensure the field name starts with a letter
        if (!preg_match('/^[a-zA-Z]/', $sanitized)) {
            $sanitized = 'Field_' . $sanitized;
        }
        
        // If after all sanitization we have an empty string, return a default
        return empty($sanitized) ? 'Field_' . md5($field_name) : $sanitized;
    }
    
    /**
     * Add a record to Quickbase.
     *
     * @since    1.0.0
     * @param    string    $realm_hostname    The Quickbase realm hostname.
     * @param    string    $user_token        The Quickbase user token.
     * @param    string    $app_id            The Quickbase app ID.
     * @param    string    $table_id          The Quickbase table ID.
     * @param    array     $record_data       The record data to add.
     * @return   array     The result of the operation.
     */
    private function add_record_to_quickbase($realm_hostname, $user_token, $app_id, $table_id, $record_data) {
        // Validate required parameters
        if (empty($realm_hostname) || empty($user_token) || empty($app_id) || empty($table_id)) {
            return array(
                'status' => 'error',
                'message' => __('Missing required Quickbase parameters.', 'multi-platform-sync')
            );
        }
        
        // Validate record data
        if (empty($record_data) || !is_array($record_data)) {
            return array(
                'status' => 'error',
                'message' => __('Invalid record data for Quickbase.', 'multi-platform-sync')
            );
        }
        
        // Ensure realm hostname has https:// prefix
        if (strpos($realm_hostname, 'http') !== 0) {
            $realm_hostname = 'https://' . $realm_hostname;
        }
        
        // Remove trailing slash if present
        $realm_hostname = rtrim($realm_hostname, '/');
        
        // Build the API URL
        $url = "{$realm_hostname}/api/v1/records";
        
        // Prepare the request data
        $data = array(
            'to' => $table_id,
            'data' => array($record_data)
        );
        
        // Set up the request arguments
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'QB-Realm-Hostname' => parse_url($realm_hostname, PHP_URL_HOST),
                'Authorization' => 'QB-USER-TOKEN ' . $user_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );
        
        // Make the API request
        $response = wp_remote_request($url, $args);
        
        // Check for WP error
        if (is_wp_error($response)) {
            return array(
                'status' => 'error',
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Try to parse the response body
        $response_data = json_decode($response_body, true);
        
        // Handle response based on status code
        if ($response_code >= 200 && $response_code < 300) {
            $record_id = '';
            
            // Extract record ID if available
            if (is_array($response_data) && 
                isset($response_data['metadata']) && 
                is_array($response_data['metadata']) && 
                isset($response_data['metadata']['createdRecordIds']) && 
                is_array($response_data['metadata']['createdRecordIds']) &&
                !empty($response_data['metadata']['createdRecordIds'])) {
                $record_id = $response_data['metadata']['createdRecordIds'][0];
            }
            
            return array(
                'status' => 'success',
                'message' => __('Record added successfully.', 'multi-platform-sync'),
                'record_id' => $record_id
            );
        } else {
            $error_message = __('Unknown error.', 'multi-platform-sync');
            
            // Try to get error message from response
            if (is_array($response_data) && !empty($response_data['message'])) {
                $error_message = $response_data['message'];
            }
            
            return array(
                'status' => 'error',
                'message' => $error_message . ' (' . $response_code . ')'
            );
        }
    }
    
    /**
     * Log sync activity to the database.
     *
     * @since    1.0.0
     * @param    string    $sync_type    The type of sync (automatic, manual, etc.).
     * @param    string    $status       The status of the sync (success, error).
     * @param    string    $message      A message describing the result.
     * @param    int       $form_id      Optional. The ID of the form being synced.
     * @param    mixed     $entry_id     Optional. The ID of the entry being synced.
     */
    private function log_sync_activity($sync_type, $status, $message, $form_id = null, $entry_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mps_sync_logs';
        
        // Add debug backtrace information to error logs
        if ($status === 'error') {
            $debug_info = '';
            
            // Get debug backtrace info (limit to 3 frames to avoid too much data)
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            foreach ($backtrace as $idx => $frame) {
                $debug_info .= sprintf(
                    "#%d %s:%d - %s%s%s()\n",
                    $idx,
                    isset($frame['file']) ? basename($frame['file']) : 'unknown',
                    isset($frame['line']) ? $frame['line'] : 0,
                    isset($frame['class']) ? $frame['class'] : '',
                    isset($frame['type']) ? $frame['type'] : '',
                    isset($frame['function']) ? $frame['function'] : 'unknown'
                );
            }
            
            // Add memory usage
            $memory_usage = round(memory_get_usage() / 1024 / 1024, 2);
            $debug_info .= sprintf("Memory Usage: %s MB\n", $memory_usage);
            
            // Add timestamp with microseconds for precise timing issues
            $debug_info .= sprintf("Timestamp: %s\n", microtime(true));
            
            // Add the debug info to the message
            $message .= "\n\nDebug Info:\n" . $debug_info;
        }
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => current_time('mysql'),
                'sync_type' => $sync_type,
                'form_id' => $form_id,
                'entry_id' => $entry_id,
                'status' => $status,
                'message' => $message
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s'
            )
        );
        
        // For critical errors, also write to error log
        if ($status === 'error') {
            error_log(sprintf(
                '[Multi-Platform Sync] %s - Form ID: %s, Entry ID: %s - %s',
                $sync_type,
                $form_id ?: 'N/A',
                $entry_id ?: 'N/A',
                $message
            ));
        }
    }
} 