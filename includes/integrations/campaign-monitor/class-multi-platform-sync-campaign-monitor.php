<?php
/**
 * The Campaign Monitor integration functionality of the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.0.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes/integrations/campaign-monitor
 */

/**
 * The Campaign Monitor integration functionality of the plugin.
 *
 * Defines the methods necessary to process data from Zapier for Campaign Monitor.
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes/integrations/campaign-monitor
 * @author     Your Name <email@righthereinteractive.com>
 */
class Multi_Platform_Sync_Campaign_Monitor {

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
     * Process data received from Zapier.
     *
     * @since    1.0.0
     * @param    array    $response    The response from Zapier.
     */
    public function process_zapier_data($response) {
        // Validate input parameters
        if (!is_array($response) || !isset($response['data'])) {
            $this->log_sync_activity(
                'campaign_monitor',
                'error',
                __('Invalid response format from Zapier.', 'multi-platform-sync')
            );
            return;
        }
        
        $data = $response['data'];
        
        // Process the data regardless of whether it came from Gravity Forms or external source
        // This ensures the sync can happen without selecting a Gravity Form
        
        // Get Campaign Monitor API key and list ID
        $api_key = get_option('mps_campaign_monitor_api_key', '');
        $list_id = get_option('mps_campaign_monitor_list_id', '');
        
        if (empty($api_key) || empty($list_id)) {
            $this->log_sync_activity(
                'campaign_monitor',
                'error',
                __('Campaign Monitor API key or list ID is not configured.', 'multi-platform-sync')
            );
            return;
        }
        
        // Extract email field from data
        $email = '';
        $name = '';
        
        // Try to find email and name in the data
        foreach ($data as $key => $value) {
            // Skip if key is 'fields' or 'source'
            if ($key === 'fields' || $key === 'source') {
                continue;
            }
            
            // Check if this is an email field
            if (
                (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) ||
                (strtolower($key) === 'email') ||
                (stripos($key, 'email') !== false)
            ) {
                $email = $value;
            }
            
            // Check if this is a name field
            if (
                (strtolower($key) === 'name') ||
                (stripos($key, 'name') !== false) ||
                (stripos($key, 'first') !== false)
            ) {
                $name = $value;
            }
        }
        
        // If we still don't have an email, check the fields array
        if (empty($email) && isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $field) {
                if (isset($field['value']) && filter_var($field['value'], FILTER_VALIDATE_EMAIL)) {
                    $email = $field['value'];
                    break;
                }
                
                if (isset($field['label']) && (stripos($field['label'], 'email') !== false) && isset($field['value'])) {
                    $email = $field['value'];
                    break;
                }
            }
        }
        
        if (empty($email)) {
            $this->log_sync_activity(
                'campaign_monitor',
                'error',
                __('No valid email address found in the data.', 'multi-platform-sync')
            );
            return;
        }
        
        // Continue with Campaign Monitor API call...
        
        // Log successful activity
        $this->log_sync_activity(
            'campaign_monitor',
            'success',
            sprintf(__('Successfully processed data for email: %s', 'multi-platform-sync'), $email)
        );
    }
    
    /**
     * Extract email from form data.
     *
     * @since    1.0.0
     * @param    array    $data    The form data.
     * @return   string   The email address or empty string if not found.
     */
    private function extract_email_from_data($data) {
        if (!is_array($data)) {
            return '';
        }
        
        // Check if there's a field with "email" in the label
        foreach ($data as $key => $value) {
            // Skip non-string values or keys
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }
            
            $value = is_string($value) ? trim($value) : strval($value);
            
            if (is_email($value)) {
                return sanitize_email($value);
            }
            
            if (is_string($key) && stripos($key, 'email') !== false && is_email($value)) {
                return sanitize_email($value);
            }
        }
        
        // Check fields array for email
        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $field) {
                if (!isset($field['label']) || !isset($field['value']) || !is_scalar($field['value'])) {
                    continue;
                }
                
                $value = is_string($field['value']) ? trim($field['value']) : strval($field['value']);
                
                if (stripos($field['label'], 'email') !== false && is_email($value)) {
                    return sanitize_email($value);
                }
            }
        }
        
        return '';
    }
    
    /**
     * Extract name from form data.
     *
     * @since    1.0.0
     * @param    array    $data    The form data.
     * @return   string   The name or empty string if not found.
     */
    private function extract_name_from_data($data) {
        if (!is_array($data)) {
            return '';
        }
        
        $first_name = '';
        $last_name = '';
        $full_name = '';
        
        // Check for name fields in the flattened data
        foreach ($data as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }
            
            $value = is_string($value) ? sanitize_text_field(trim($value)) : sanitize_text_field(strval($value));
            
            if (empty($value)) {
                continue;
            }
            
            if (stripos($key, 'first name') !== false || stripos($key, 'firstname') !== false) {
                $first_name = $value;
            } elseif (stripos($key, 'last name') !== false || stripos($key, 'lastname') !== false) {
                $last_name = $value;
            } elseif (stripos($key, 'name') !== false && empty($full_name)) {
                $full_name = $value;
            }
        }
        
        // Check fields array
        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $field) {
                if (!isset($field['label']) || !isset($field['value']) || !is_scalar($field['value'])) {
                    continue;
                }
                
                $value = is_string($field['value']) ? sanitize_text_field(trim($field['value'])) : sanitize_text_field(strval($field['value']));
                
                if (empty($value)) {
                    continue;
                }
                
                if (stripos($field['label'], 'first name') !== false || stripos($field['label'], 'firstname') !== false) {
                    $first_name = $value;
                } elseif (stripos($field['label'], 'last name') !== false || stripos($field['label'], 'lastname') !== false) {
                    $last_name = $value;
                } elseif (stripos($field['label'], 'name') !== false && empty($full_name)) {
                    $full_name = $value;
                }
            }
        }
        
        // Combine first and last name if both exist
        if (!empty($first_name) && !empty($last_name)) {
            return trim($first_name . ' ' . $last_name);
        }
        
        // Return full name if it exists
        if (!empty($full_name)) {
            return trim($full_name);
        }
        
        // Return first name or last name if only one exists
        if (!empty($first_name)) {
            return trim($first_name);
        }
        
        if (!empty($last_name)) {
            return trim($last_name);
        }
        
        return '';
    }
    
    /**
     * Extract custom fields from form data.
     *
     * @since    1.0.0
     * @param    array    $data    The form data.
     * @return   array    The custom fields.
     */
    private function extract_custom_fields($data) {
        if (!is_array($data)) {
            return array();
        }
        
        $custom_fields = array();
        $excluded_fields = array('email', 'name', 'first name', 'firstname', 'last name', 'lastname');
        
        // Process fields from flattened data
        foreach ($data as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }
            
            // Skip empty values
            $sanitized_value = is_string($value) ? sanitize_text_field(trim($value)) : sanitize_text_field(strval($value));
            if (empty($sanitized_value)) {
                continue;
            }
            
            // Skip excluded fields
            $lower_key = strtolower($key);
            if (in_array($lower_key, $excluded_fields)) {
                continue;
            }
            
            // Sanitize the key for Campaign Monitor (alphanumeric and some special chars only)
            $sanitized_key = preg_replace('/[^a-zA-Z0-9_ .-]/', '', $key);
            if (empty($sanitized_key)) {
                continue;
            }
            
            $custom_fields[] = array(
                'Key' => $sanitized_key,
                'Value' => $sanitized_value
            );
        }
        
        return $custom_fields;
    }
    
    /**
     * Add a subscriber to Campaign Monitor.
     *
     * @since    1.0.0
     * @param    string    $api_key           The Campaign Monitor API key.
     * @param    string    $list_id           The Campaign Monitor list ID.
     * @param    array     $subscriber_data   The subscriber data.
     * @return   array     The result of the operation.
     */
    private function add_subscriber_to_campaign_monitor($api_key, $list_id, $subscriber_data) {
        // Validate required parameters
        if (empty($api_key) || empty($list_id) || !is_array($subscriber_data)) {
            return array(
                'status' => 'error',
                'message' => __('Missing required parameters for Campaign Monitor API call.', 'multi-platform-sync')
            );
        }
        
        // Validate email address
        if (empty($subscriber_data['EmailAddress']) || !is_email($subscriber_data['EmailAddress'])) {
            return array(
                'status' => 'error',
                'message' => __('Invalid email address for Campaign Monitor subscriber.', 'multi-platform-sync')
            );
        }
        
        // Campaign Monitor API endpoint for adding a subscriber
        $url = 'https://api.createsend.com/api/v3.2/subscribers/' . urlencode($list_id) . '.json';
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_key . ':x'),
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($subscriber_data),
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
        
        // Handle response based on status code
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'status' => 'success',
                'message' => __('Subscriber added/updated successfully.', 'multi-platform-sync')
            );
        } else {
            $error_message = __('Unknown error.', 'multi-platform-sync');
            
            // Try to get error message from response
            $response_data = json_decode($response_body, true);
            if (is_array($response_data) && !empty($response_data['Message'])) {
                $error_message = $response_data['Message'];
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