<?php
/**
 * The core plugin class.
 *
 * @since      1.1.0
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

class Multi_Platform_Sync {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.1.0
     * @access   protected
     * @var      Multi_Platform_Sync_Loader    $loader    Maintains and registers all hooks.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.1.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.1.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.1.0
     */
    public function __construct() {
        $this->version = MPS_VERSION;
        $this->plugin_name = 'multi-platform-sync';
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_gravity_forms_hooks();
        $this->define_queue_hooks();
        
        // Initialize queue system
        Multi_Platform_Sync_Queue::init();
        
        // Add default options if they don't exist
        $this->add_default_options();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.1.0
     * @access   private
     */
    private function load_dependencies() {
        // Core classes
        require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-loader.php';
        require_once MPS_PLUGIN_DIR . 'includes/admin/class-multi-platform-sync-admin.php';
        require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-rate-limiter.php';
        
        // New enhanced classes
        require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-field-mapper.php';
        require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-data-transformer.php';
        require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-queue.php';
        require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-analytics.php';
        
        // Integration classes
        require_once MPS_PLUGIN_DIR . 'includes/integrations/zapier/class-multi-platform-sync-zapier.php';
        require_once MPS_PLUGIN_DIR . 'includes/integrations/campaign-monitor/class-multi-platform-sync-campaign-monitor.php';
        require_once MPS_PLUGIN_DIR . 'includes/integrations/quickbase/class-multi-platform-sync-quickbase.php';
        
        $this->loader = new Multi_Platform_Sync_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    1.1.0
     * @access   private
     */
    private function define_admin_hooks() {
        $admin = new Multi_Platform_Sync_Admin($this->get_plugin_name(), $this->get_version());
        
        // Admin menu and settings
        $this->loader->add_action('admin_menu', $admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
        
        // Admin assets
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        
        // Add settings link on plugin page
        $this->loader->add_filter('plugin_action_links_' . MPS_PLUGIN_BASENAME, $admin, 'add_plugin_settings_link');
        
        // AJAX handlers for new features
        $this->loader->add_action('wp_ajax_mps_process_queue', $this, 'ajax_process_queue');
        $this->loader->add_action('wp_ajax_mps_clear_completed_queue', $this, 'ajax_clear_completed_queue');
        $this->loader->add_action('wp_ajax_mps_export_analytics', $this, 'ajax_export_analytics');
        $this->loader->add_action('wp_ajax_mps_test_connection', $this, 'ajax_test_connection');
    }

    /**
     * Check if the Gravity Forms Zapier Add-on is active.
     *
     * @since    1.1.0
     * @access   private
     * @return   boolean  True if the add-on is active, false otherwise.
     */
    private function is_gravity_forms_zapier_addon_active() {
        // Check if the GF_Zapier class exists (part of the Zapier Add-on)
        if (class_exists('GF_Zapier')) {
            return true;
        }
        
        // Check for the addon in the active plugins list
        $active_plugins = get_option('active_plugins', array());
        foreach ($active_plugins as $plugin) {
            if (strpos($plugin, 'gravityformszapier') !== false) {
                return true;
            }
        }
        
        // If we're in a multisite, check network active plugins
        if (is_multisite()) {
            $network_plugins = get_site_option('active_sitewide_plugins', array());
            foreach (array_keys($network_plugins) as $plugin) {
                if (strpos($plugin, 'gravityformszapier') !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Register all of the hooks related to Gravity Forms.
     *
     * @since    1.1.0
     * @access   private
     */
    private function define_gravity_forms_hooks() {
        // Initialize integrations
        $zapier = new Multi_Platform_Sync_Zapier($this->get_plugin_name(), $this->get_version());
        $campaign_monitor = new Multi_Platform_Sync_Campaign_Monitor($this->get_plugin_name(), $this->get_version());
        $quickbase = new Multi_Platform_Sync_Quickbase($this->get_plugin_name(), $this->get_version());
        
        // Check if Gravity Forms Zapier add-on is active
        $zapier_addon_active = $this->is_gravity_forms_zapier_addon_active();
        
        // Store this in an option so admin UI can access it
        update_option('mps_gf_zapier_addon_detected', $zapier_addon_active);
        
        // Only register direct Gravity Forms to Zapier hooks if the add-on is not active
        if (!$zapier_addon_active) {
            // Register Gravity Forms hooks for form submission
            $this->loader->add_action('gform_after_submission', $this, 'handle_form_submission', 10, 2);
            
            // Manual sync action hook for admin-triggered syncs
            $this->loader->add_action('mps_manual_sync', $zapier, 'process_manual_sync', 10, 1);
        }
        
        // Always register the Zapier webhook response handlers for Campaign Monitor and Quickbase
        // These will work with data from the Gravity Forms Zapier add-on or our own integration
        $this->loader->add_action('mps_zapier_webhook_response', $this, 'handle_zapier_response', 10, 1);
    }

    /**
     * Register queue-related hooks.
     *
     * @since    1.1.0
     * @access   private
     */
    private function define_queue_hooks() {
        // Queue processing hooks are handled in the Queue class
        // This method is for any additional queue-related hooks
    }

    /**
     * Handle form submission with enhanced processing.
     *
     * @since    1.1.0
     * @param    array    $entry    The entry that was just created.
     * @param    array    $form     The form object.
     */
    public function handle_form_submission($entry, $form) {
        // Check if this form is selected for syncing
        $selected_forms = get_option('mps_gravity_forms_to_sync', array());
        $form_id = absint($form['id']);
        
        if (!in_array($form_id, $selected_forms)) {
            return;
        }

        // Check if queue processing is enabled
        $use_queue = get_option('mps_enable_queue_processing', true);
        
        if ($use_queue) {
            // Add to queue for background processing
            $this->queue_form_submission($entry, $form);
        } else {
            // Process immediately (legacy behavior)
            $this->process_form_submission_immediately($entry, $form);
        }
    }

    /**
     * Queue form submission for background processing.
     *
     * @since    1.1.0
     * @param    array    $entry    The entry that was just created.
     * @param    array    $form     The form object.
     */
    private function queue_form_submission($entry, $form) {
        $zapier = new Multi_Platform_Sync_Zapier($this->get_plugin_name(), $this->get_version());
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($zapier);
        $method = $reflection->getMethod('prepare_form_data');
        $method->setAccessible(true);
        
        $data = $method->invoke($zapier, $entry, $form);
        
        // Add to queue with high priority for form submissions
        Multi_Platform_Sync_Queue::add_to_queue(
            'zapier',
            $data,
            2, // High priority
            $form['id'],
            $entry['id']
        );
    }

    /**
     * Process form submission immediately.
     *
     * @since    1.1.0
     * @param    array    $entry    The entry that was just created.
     * @param    array    $form     The form object.
     */
    private function process_form_submission_immediately($entry, $form) {
        $zapier = new Multi_Platform_Sync_Zapier($this->get_plugin_name(), $this->get_version());
        $zapier->process_form_submission($entry, $form);
    }

    /**
     * Handle Zapier webhook response with enhanced processing.
     *
     * @since    1.1.0
     * @param    array    $response    The response from Zapier.
     */
    public function handle_zapier_response($response) {
        if (!is_array($response) || !isset($response['data'])) {
            return;
        }

        $data = $response['data'];
        
        // Check if queue processing is enabled
        $use_queue = get_option('mps_enable_queue_processing', true);
        
        if ($use_queue) {
            // Add Campaign Monitor and Quickbase syncs to queue
            $this->queue_platform_syncs($data);
        } else {
            // Process immediately
            $this->process_platform_syncs_immediately($data);
        }
    }

    /**
     * Queue platform syncs for background processing.
     *
     * @since    1.1.0
     * @param    array    $data    The data to sync.
     */
    private function queue_platform_syncs($data) {
        // Queue Campaign Monitor sync
        $cm_api_key = get_option('mps_campaign_monitor_api_key', '');
        $cm_list_id = get_option('mps_campaign_monitor_list_id', '');
        
        if (!empty($cm_api_key) && !empty($cm_list_id)) {
            Multi_Platform_Sync_Queue::add_to_queue(
                'campaign_monitor',
                $data,
                3 // Medium priority
            );
        }

        // Queue Quickbase sync
        $qb_settings = array(
            'realm_hostname' => get_option('mps_quickbase_realm_hostname', ''),
            'user_token' => get_option('mps_quickbase_user_token', ''),
            'app_id' => get_option('mps_quickbase_app_id', ''),
            'table_id' => get_option('mps_quickbase_table_id', '')
        );
        
        if (!in_array('', $qb_settings, true)) {
            Multi_Platform_Sync_Queue::add_to_queue(
                'quickbase',
                $data,
                3 // Medium priority
            );
        }
    }

    /**
     * Process platform syncs immediately.
     *
     * @since    1.1.0
     * @param    array    $data    The data to sync.
     */
    private function process_platform_syncs_immediately($data) {
        // Process Campaign Monitor
        $campaign_monitor = new Multi_Platform_Sync_Campaign_Monitor($this->get_plugin_name(), $this->get_version());
        $campaign_monitor->process_zapier_data(array('data' => $data));

        // Process Quickbase
        $quickbase = new Multi_Platform_Sync_Quickbase($this->get_plugin_name(), $this->get_version());
        $quickbase->process_zapier_data(array('data' => $data));
    }

    /**
     * AJAX handler for processing queue.
     *
     * @since    1.1.0
     */
    public function ajax_process_queue() {
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

        // Process queue
        Multi_Platform_Sync_Queue::process_queue(20); // Process up to 20 items
        
        wp_send_json_success(array(
            'message' => __('Queue processed successfully.', 'multi-platform-sync')
        ));
    }

    /**
     * AJAX handler for clearing completed queue items.
     *
     * @since    1.1.0
     */
    public function ajax_clear_completed_queue() {
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

        // Clear completed items
        $cleared = Multi_Platform_Sync_Queue::clear_old_items(7);
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Cleared %d completed queue items.', 'multi-platform-sync'),
                $cleared
            )
        ));
    }

    /**
     * AJAX handler for exporting analytics.
     *
     * @since    1.1.0
     */
    public function ajax_export_analytics() {
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

        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';

        $report = Multi_Platform_Sync_Analytics::generate_report(array('period' => $period));
        $exported_data = Multi_Platform_Sync_Analytics::export_report($report, $format);

        wp_send_json_success(array(
            'data' => $exported_data,
            'filename' => 'mps-analytics-' . date('Y-m-d') . '.' . $format
        ));
    }

    /**
     * AJAX handler for testing connections.
     *
     * @since    1.1.0
     */
    public function ajax_test_connection() {
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

        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : '';
        
        switch ($platform) {
            case 'campaign_monitor':
                $result = $this->test_campaign_monitor_connection();
                break;
            case 'quickbase':
                $result = $this->test_quickbase_connection();
                break;
            case 'zapier':
                $result = $this->test_zapier_connection();
                break;
            default:
                wp_send_json_error(array(
                    'message' => __('Invalid platform specified.', 'multi-platform-sync')
                ));
        }

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Test Campaign Monitor connection.
     *
     * @since    1.1.0
     * @return   array    Test result.
     */
    private function test_campaign_monitor_connection() {
        $api_key = get_option('mps_campaign_monitor_api_key', '');
        $list_id = get_option('mps_campaign_monitor_list_id', '');
        
        if (empty($api_key) || empty($list_id)) {
            return array(
                'success' => false,
                'message' => __('Campaign Monitor API key or list ID not configured.', 'multi-platform-sync')
            );
        }

        // Test API connection by getting list details
        $url = 'https://api.createsend.com/api/v3.2/lists/' . urlencode($list_id) . '.json';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_key . ':x')
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'message' => __('Campaign Monitor connection successful.', 'multi-platform-sync')
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('Campaign Monitor API returned error: %d', 'multi-platform-sync'), $response_code)
            );
        }
    }

    /**
     * Test Quickbase connection.
     *
     * @since    1.1.0
     * @return   array    Test result.
     */
    private function test_quickbase_connection() {
        $realm_hostname = get_option('mps_quickbase_realm_hostname', '');
        $user_token = get_option('mps_quickbase_user_token', '');
        $app_id = get_option('mps_quickbase_app_id', '');
        
        if (empty($realm_hostname) || empty($user_token) || empty($app_id)) {
            return array(
                'success' => false,
                'message' => __('Quickbase settings not fully configured.', 'multi-platform-sync')
            );
        }

        // Ensure realm hostname has https:// prefix
        if (strpos($realm_hostname, 'http') !== 0) {
            $realm_hostname = 'https://' . $realm_hostname;
        }

        // Test API connection by getting app info
        $url = rtrim($realm_hostname, '/') . '/api/v1/app/' . urlencode($app_id);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'QB-Realm-Hostname' => parse_url($realm_hostname, PHP_URL_HOST),
                'Authorization' => 'QB-USER-TOKEN ' . $user_token
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'message' => __('Quickbase connection successful.', 'multi-platform-sync')
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('Quickbase API returned error: %d', 'multi-platform-sync'), $response_code)
            );
        }
    }

    /**
     * Test Zapier connection.
     *
     * @since    1.1.0
     * @return   array    Test result.
     */
    private function test_zapier_connection() {
        $webhook_url = get_option('mps_zapier_webhook_url', '');
        
        if (empty($webhook_url)) {
            return array(
                'success' => false,
                'message' => __('Zapier webhook URL not configured.', 'multi-platform-sync')
            );
        }

        // Send test data to Zapier
        $test_data = array(
            'test' => true,
            'timestamp' => current_time('mysql'),
            'message' => 'Test connection from Multi-Platform Sync'
        );

        $response = wp_remote_post($webhook_url, array(
            'body' => wp_json_encode($test_data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'message' => __('Zapier webhook connection successful.', 'multi-platform-sync')
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('Zapier webhook returned error: %d', 'multi-platform-sync'), $response_code)
            );
        }
    }

    /**
     * Add default options.
     *
     * @since    1.1.0
     * @access   private
     */
    private function add_default_options() {
        // Rate limiting options
        if (false === get_option('mps_rate_limit_enabled')) {
            add_option('mps_rate_limit_enabled', true);
        }
        
        if (false === get_option('mps_rate_limit_max_requests')) {
            add_option('mps_rate_limit_max_requests', 10);
        }
        
        if (false === get_option('mps_rate_limit_period')) {
            add_option('mps_rate_limit_period', 60);
        }

        // Queue processing options
        if (false === get_option('mps_enable_queue_processing')) {
            add_option('mps_enable_queue_processing', true);
        }

        // Analytics options
        if (false === get_option('mps_analytics_retention_days')) {
            add_option('mps_analytics_retention_days', 90);
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.1.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.1.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.1.0
     * @return    Multi_Platform_Sync_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.1.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}