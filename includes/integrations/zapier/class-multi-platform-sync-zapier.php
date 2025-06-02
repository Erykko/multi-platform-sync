<?php
/**
 * The Zapier integration functionality of the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.0.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes/integrations/zapier
 */

/**
 * The Zapier integration functionality of the plugin.
 *
 * Defines the methods necessary to sync Gravity Forms data with Zapier.
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes/integrations/zapier
 * @author     Your Name <email@righthereinteractive.com>
 */
class Multi_Platform_Sync_Zapier {

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
        
        // Register AJAX handler for manual sync
        add_action('wp_ajax_mps_manual_sync', array($this, 'ajax_manual_sync'));
        
        // Check if we should listen for incoming webhooks from GF Zapier add-on
        if (get_option('mps_gf_zapier_addon_detected', false)) {
            // Add handler for incoming data from the Gravity Forms Zapier add-on
            add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
        }
    }
    
    /**
     * Register a webhook endpoint to receive data from Zapier.
     *
     * This allows Zapier to send data back to WordPress, which can then be
     * processed and sent to Campaign Monitor and Quickbase.
     *
     * @since    1.0.0
     */
    public function register_webhook_endpoint() {
        register_rest_route('multi-platform-sync/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_webhook_data'),
            'permission_callback' => '__return_true', // Public endpoint with webhook security
        ));
    }
    
    /**
     * Process incoming webhook data from Zapier.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request    The incoming request object.
     * @return   WP_REST_Response             The response to send back to Zapier.
     */
    public function process_webhook_data($request) {
        // Get the JSON body
        $data = $request->get_json_params();
        
        // Basic validation
        if (empty($data)) {
            $this->log_sync_activity(
                'webhook',
                'error',
                __('Empty webhook data received from Zapier.', 'multi-platform-sync')
            );
            
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Empty data received'
            ), 400);
        }
        
        // Log the received data
        $this->log_sync_activity(
            'webhook',
            'success',
            __('Received webhook data from Zapier.', 'multi-platform-sync')
        );
        
        // Create a response package that matches our normal format
        $response = array(
            'response' => array(
                'code' => 200,
                'message' => 'OK',
                'body' => json_encode(array('success' => true))
            ),
            'data' => $data
        );
        
        // Trigger the same action that our normal flow would trigger
        do_action('mps_zapier_webhook_response', $response);
        
        // Return a success response
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Data processed successfully'
        ), 200);
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

    /**
     * Process form submission and send data to Zapier.
     *
     * @since    1.0.0
     * @param    array     $entry    The entry that was just created.
     * @param    array     $form     The form object.
     */
    public function process_form_submission($entry, $form) {
        // Validate input parameters
        if (!is_array($entry) || empty($entry) || !is_array($form) || empty($form) || !isset($form['id'])) {
            $this->log_sync_activity(
                'automatic',
                'error',
                __('Invalid form submission data.', 'multi-platform-sync')
            );
            return;
        }
        
        $form_id = absint($form['id']);
        $entry_id = isset($entry['id']) ? sanitize_text_field($entry['id']) : '';
        
        $selected_forms = get_option('mps_gravity_forms_to_sync', array());
        
        // Check if this form is selected for syncing
        if (!in_array($form_id, $selected_forms)) {
            return;
        }
        
        // Get Zapier webhook URL
        $webhook_url = get_option('mps_zapier_webhook_url', '');
        if (empty($webhook_url) || !filter_var($webhook_url, FILTER_VALIDATE_URL)) {
            $this->log_sync_activity(
                'automatic',
                'error',
                __('Zapier webhook URL is not configured or is invalid.', 'multi-platform-sync'),
                $form_id,
                $entry_id
            );
            return;
        }
        
        try {
            // Prepare form data for Zapier
            $data = $this->prepare_form_data($entry, $form);
            
            // Send data to Zapier
            $response = $this->send_to_zapier($webhook_url, $data);
            
            // Process response
            $this->process_zapier_response($response, $form_id, $entry_id);
        } catch (Exception $e) {
            $this->log_sync_activity(
                'automatic',
                'error',
                sprintf(__('Exception occurred during sync: %s', 'multi-platform-sync'), $e->getMessage()),
                $form_id,
                $entry_id
            );
        }
    }
    
    /**
     * Process manual sync request via AJAX.
     *
     * @since    1.0.0
     */
    public function ajax_manual_sync() {
        // Check permissions
        if (!current_user_can('manage_multi_platform_sync')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'multi-platform-sync')
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mps_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'multi-platform-sync')
            ));
        }
        
        // Get form ID from request
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        
        // If form ID is provided, sync only that form's latest entries
        if ($form_id > 0) {
            $result = $this->process_manual_sync($form_id);
        } else {
            // Otherwise sync all selected forms
            $selected_forms = get_option('mps_gravity_forms_to_sync', array());
            
            if (empty($selected_forms)) {
                wp_send_json_error(array(
                    'message' => __('No forms selected for syncing.', 'multi-platform-sync')
                ));
            }
            
            $results = array();
            foreach ($selected_forms as $form_id) {
                $results[$form_id] = $this->process_manual_sync($form_id);
            }
            
            $success_count = count(array_filter($results, function($result) {
                return $result['status'] === 'success';
            }));
            
            if ($success_count === count($results)) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Successfully synced %d forms.', 'multi-platform-sync'),
                        count($results)
                    )
                ));
            } else {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Partially synced %d out of %d forms. Check logs for details.', 'multi-platform-sync'),
                        $success_count,
                        count($results)
                    )
                ));
            }
        }
        
        if ($result['status'] === 'success') {
            wp_send_json_success(array(
                'message' => $result['message']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }
    
    /**
     * Process manual sync for a specific form.
     *
     * @since    1.0.0
     * @param    int       $form_id    The ID of the form to sync.
     * @return   array     Result of the sync operation.
     */
    public function process_manual_sync($form_id) {
        // Validate form ID
        $form_id = absint($form_id);
        if (empty($form_id)) {
            return array(
                'status' => 'error',
                'message' => __('Invalid form ID.', 'multi-platform-sync')
            );
        }
        
        // Check if user has capability
        if (!current_user_can('manage_multi_platform_sync')) {
            return array(
                'status' => 'error',
                'message' => __('You do not have permission to perform this action.', 'multi-platform-sync')
            );
        }
        
        // Get Zapier webhook URL
        $webhook_url = get_option('mps_zapier_webhook_url', '');
        if (empty($webhook_url) || !filter_var($webhook_url, FILTER_VALIDATE_URL)) {
            $this->log_sync_activity(
                'manual',
                'error',
                __('Zapier webhook URL is not configured or is invalid.', 'multi-platform-sync'),
                $form_id
            );
            
            return array(
                'status' => 'error',
                'message' => __('Zapier webhook URL is not configured or is invalid.', 'multi-platform-sync')
            );
        }
        
        // Check if Gravity Forms is active
        if (!class_exists('GFAPI')) {
            $this->log_sync_activity(
                'manual',
                'error',
                __('Gravity Forms is not active.', 'multi-platform-sync'),
                $form_id
            );
            
            return array(
                'status' => 'error',
                'message' => __('Gravity Forms is not active.', 'multi-platform-sync')
            );
        }
        
        try {
            // Get form
            $form = GFAPI::get_form($form_id);
            if (empty($form)) {
                $this->log_sync_activity(
                    'manual',
                    'error',
                    sprintf(__('Form %d not found.', 'multi-platform-sync'), $form_id),
                    $form_id
                );
                
                return array(
                    'status' => 'error',
                    'message' => sprintf(__('Form %d not found.', 'multi-platform-sync'), $form_id)
                );
            }
            
            // Get recent entries (last 10)
            $search_criteria = array();
            $sorting = array('key' => 'date_created', 'direction' => 'DESC');
            $paging = array('offset' => 0, 'page_size' => 10);
            
            $entries = GFAPI::get_entries($form_id, $search_criteria, $sorting, $paging);
            
            if (is_wp_error($entries)) {
                $this->log_sync_activity(
                    'manual',
                    'error',
                    sprintf(__('Error fetching entries: %s', 'multi-platform-sync'), $entries->get_error_message()),
                    $form_id
                );
                
                return array(
                    'status' => 'error',
                    'message' => sprintf(__('Error fetching entries: %s', 'multi-platform-sync'), $entries->get_error_message())
                );
            }
            
            if (empty($entries)) {
                $this->log_sync_activity(
                    'manual',
                    'success',
                    sprintf(__('No entries found for form %d.', 'multi-platform-sync'), $form_id),
                    $form_id
                );
                
                return array(
                    'status' => 'success',
                    'message' => sprintf(__('No entries found for form %d.', 'multi-platform-sync'), $form_id)
                );
            }
            
            // Sync each entry
            $success_count = 0;
            $error_messages = array();
            
            foreach ($entries as $entry) {
                // Prepare form data for Zapier
                $data = $this->prepare_form_data($entry, $form);
                
                // Send data to Zapier
                $response = $this->send_to_zapier($webhook_url, $data);
                
                // Process response
                $result = $this->process_zapier_response($response, $form_id, $entry['id']);
                
                if ($result['status'] === 'success') {
                    $success_count++;
                } else {
                    $error_messages[] = sprintf(__('Entry %s: %s', 'multi-platform-sync'), $entry['id'], $result['message']);
                }
            }
            
            if ($success_count === count($entries)) {
                $message = sprintf(
                    __('Successfully synced %d entries for form %d.', 'multi-platform-sync'),
                    $success_count,
                    $form_id
                );
                $status = 'success';
            } else {
                $message = sprintf(
                    __('Partially synced %d out of %d entries for form %d.', 'multi-platform-sync'),
                    $success_count,
                    count($entries),
                    $form_id
                );
                
                if (!empty($error_messages)) {
                    $message .= ' ' . __('Errors: ', 'multi-platform-sync') . implode('; ', array_slice($error_messages, 0, 3));
                    
                    if (count($error_messages) > 3) {
                        $message .= sprintf(__(' and %d more errors.', 'multi-platform-sync'), count($error_messages) - 3);
                    }
                }
                
                $status = 'error';
            }
            
            $this->log_sync_activity(
                'manual',
                $status,
                $message,
                $form_id
            );
            
            return array(
                'status' => $status,
                'message' => $message
            );
        } catch (Exception $e) {
            $error_message = sprintf(__('Exception occurred during sync: %s', 'multi-platform-sync'), $e->getMessage());
            
            $this->log_sync_activity(
                'manual',
                'error',
                $error_message,
                $form_id
            );
            
            return array(
                'status' => 'error',
                'message' => $error_message
            );
        }
    }
    
    /**
     * Prepare form data for sending to Zapier.
     *
     * @since    1.0.0
     * @param    array     $entry    The entry object.
     * @param    array     $form     The form object.
     * @return   array     The prepared data.
     */
    private function prepare_form_data($entry, $form) {
        $data = array(
            'form_id' => $form['id'],
            'form_title' => $form['title'],
            'entry_id' => $entry['id'],
            'date_created' => $entry['date_created'],
            'fields' => array()
        );
        
        // Add all fields with labels
        foreach ($form['fields'] as $field) {
            if (isset($field['inputs']) && is_array($field['inputs'])) {
                // Handle multi-input fields (like name, address, checkboxes)
                foreach ($field['inputs'] as $input) {
                    $input_id = (string) $input['id'];
                    if (isset($entry[$input_id])) {
                        $data['fields'][$input_id] = array(
                            'label' => $input['label'],
                            'value' => $entry[$input_id]
                        );
                    }
                }
            } else {
                // Handle single input fields
                $field_id = (string) $field['id'];
                if (isset($entry[$field_id])) {
                    $data['fields'][$field_id] = array(
                        'label' => $field['label'],
                        'value' => $entry[$field_id]
                    );
                }
            }
        }
        
        // Add a flattened version for easier access in Zapier
        foreach ($data['fields'] as $field_id => $field_data) {
            $data[$field_data['label']] = $field_data['value'];
        }
        
        return $data;
    }
    
    /**
     * Send data to Zapier webhook.
     *
     * @since    1.0.0
     * @param    string    $webhook_url    The Zapier webhook URL.
     * @param    array     $data           The data to send.
     * @return   array     The response from Zapier.
     */
    private function send_to_zapier($webhook_url, $data) {
        // Validate input parameters
        if (empty($webhook_url) || !filter_var($webhook_url, FILTER_VALIDATE_URL)) {
            return array(
                'response' => new WP_Error('invalid_webhook_url', __('Invalid Zapier webhook URL.', 'multi-platform-sync')),
                'data' => $data
            );
        }
        
        if (empty($data) || !is_array($data)) {
            return array(
                'response' => new WP_Error('invalid_data', __('Invalid data for Zapier webhook.', 'multi-platform-sync')),
                'data' => $data
            );
        }
        
        // Check rate limit before making the request
        if (!Multi_Platform_Sync_Rate_Limiter::can_make_request('zapier')) {
            $time_remaining = Multi_Platform_Sync_Rate_Limiter::get_time_remaining('zapier');
            
            return array(
                'response' => new WP_Error(
                    'rate_limit_exceeded',
                    sprintf(
                        __('Rate limit exceeded for Zapier API. Try again in %d seconds.', 'multi-platform-sync'),
                        $time_remaining
                    )
                ),
                'data' => $data,
                'cached' => false
            );
        }
        
        // Generate a cache key based on the webhook URL and data
        $cache_key = 'mps_zapier_' . md5($webhook_url . serialize($data));
        
        // Check if we have a cached response
        $cached_response = get_transient($cache_key);
        if (false !== $cached_response) {
            // Add a flag to indicate this was a cached response
            $cached_response['cached'] = true;
            return $cached_response;
        }
        
        // Set up the request args
        $args = array(
            'body' => wp_json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'sslverify' => true
        );
        
        // Make the API request
        $response = wp_remote_post($webhook_url, $args);
        
        $result = array(
            'response' => $response,
            'data' => $data,
            'cached' => false
        );
        
        // Cache the response for successful requests
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) >= 200 && wp_remote_retrieve_response_code($response) < 300) {
            // Cache successful responses for 5 minutes
            set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        }
        
        return $result;
    }
    
    /**
     * Process the response from Zapier.
     *
     * @since    1.0.0
     * @param    array     $response    The response from Zapier.
     * @param    int       $form_id     The form ID.
     * @param    string    $entry_id    The entry ID.
     * @return   array     Result of the operation.
     */
    private function process_zapier_response($response, $form_id, $entry_id) {
        // Validate input parameters
        if (!is_array($response) || !isset($response['response']) || !isset($response['data'])) {
            $this->log_sync_activity(
                'zapier',
                'error',
                __('Invalid response format from Zapier.', 'multi-platform-sync'),
                $form_id,
                $entry_id
            );
            
            return array(
                'status' => 'error',
                'message' => __('Invalid response format from Zapier.', 'multi-platform-sync')
            );
        }
        
        // Check if this was a cached response
        $is_cached = isset($response['cached']) && $response['cached'];
        $log_prefix = $is_cached ? __('(Cached) ', 'multi-platform-sync') : '';
        
        // Handle WP_Error responses
        if (is_wp_error($response['response'])) {
            $error_message = $response['response']->get_error_message();
            
            $this->log_sync_activity(
                'zapier',
                'error',
                sprintf(__('%sError sending data to Zapier: %s', 'multi-platform-sync'), $log_prefix, $error_message),
                $form_id,
                $entry_id
            );
            
            return array(
                'status' => 'error',
                'message' => $error_message
            );
        }
        
        // Process HTTP response
        $response_code = wp_remote_retrieve_response_code($response['response']);
        $response_body = wp_remote_retrieve_body($response['response']);
        
        // Check for valid response code
        if (!is_numeric($response_code)) {
            $this->log_sync_activity(
                'zapier',
                'error',
                sprintf(__('%sInvalid response code from Zapier.', 'multi-platform-sync'), $log_prefix),
                $form_id,
                $entry_id
            );
            
            return array(
                'status' => 'error',
                'message' => __('Invalid response code from Zapier.', 'multi-platform-sync')
            );
        }
        
        // Handle success response (2xx status codes)
        if ($response_code >= 200 && $response_code < 300) {
            $this->log_sync_activity(
                'zapier',
                'success',
                sprintf(__('%sData successfully sent to Zapier. Response: %s', 'multi-platform-sync'), 
                    $log_prefix,
                    substr($response_body, 0, 255)), // Truncate long responses
                $form_id,
                $entry_id
            );
            
            // Only trigger action for other integrations on non-cached responses
            if (!$is_cached) {
                do_action('mps_zapier_webhook_response', $response);
            }
            
            return array(
                'status' => 'success',
                'message' => sprintf(__('%sData successfully sent to Zapier.', 'multi-platform-sync'), $log_prefix),
                'cached' => $is_cached
            );
        } else {
            // Handle error response
            $this->log_sync_activity(
                'zapier',
                'error',
                sprintf(__('%sError response from Zapier: %s (%d)', 'multi-platform-sync'), 
                    $log_prefix,
                    substr($response_body, 0, 255), // Truncate long responses
                    $response_code),
                $form_id,
                $entry_id
            );
            
            return array(
                'status' => 'error',
                'message' => sprintf(__('%sError response from Zapier: %s (%d)', 'multi-platform-sync'), 
                    $log_prefix,
                    substr($response_body, 0, 255), // Truncate long responses
                    $response_code),
                'cached' => $is_cached
            );
        }
    }
} 