<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.1.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and adds admin menu pages and settings.
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/admin
 * @author     Eric Mutema <eric@righthereinteractive.com>
 */
class Multi_Platform_Sync_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.1.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.1.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, MPS_PLUGIN_URL . 'assets/css/multi-platform-sync-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.1.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, MPS_PLUGIN_URL . 'assets/js/multi-platform-sync-admin.js', array('jquery'), $this->version, false);
        
        // Localize the script with data for our JS
        wp_localize_script($this->plugin_name, 'mps_admin_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mps_admin_nonce'),
            'strings' => array(
                'sync_success' => __('Sync completed successfully!', 'multi-platform-sync'),
                'sync_error' => __('There was an error during sync. Please check logs.', 'multi-platform-sync'),
                'confirm_sync' => __('Are you sure you want to run a manual sync?', 'multi-platform-sync'),
                'testing_connection' => __('Testing connection...', 'multi-platform-sync'),
                'connection_successful' => __('Connection successful!', 'multi-platform-sync'),
                'connection_failed' => __('Connection failed. Please check your settings.', 'multi-platform-sync')
            )
        ));
    }

    /**
     * Register admin notices.
     *
     * @since    1.1.0
     */
    public function register_admin_notices() {
        // Check if we're on our plugin's page
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, $this->plugin_name) === false) {
            return;
        }
        
        // Check if Gravity Forms Zapier add-on is detected
        if (get_option('mps_gf_zapier_addon_detected', false)) {
            add_action('admin_notices', array($this, 'display_zapier_addon_notice'));
        }

        // Check for upgrade notices
        $this->check_upgrade_notices();
    }

    /**
     * Check for upgrade notices.
     *
     * @since    1.1.0
     */
    private function check_upgrade_notices() {
        $last_version = get_option('mps_last_version_notice', '0.0.0');
        
        if (version_compare($last_version, '1.1.0', '<') && version_compare(MPS_VERSION, '1.1.0', '>=')) {
            add_action('admin_notices', array($this, 'display_upgrade_notice'));
            update_option('mps_last_version_notice', MPS_VERSION);
        }
    }

    /**
     * Display upgrade notice.
     *
     * @since    1.1.0
     */
    public function display_upgrade_notice() {
        ?>
        <div class="notice notice-success is-dismissible">
            <h3><?php _e('Multi-Platform Sync Updated!', 'multi-platform-sync'); ?></h3>
            <p>
                <strong><?php _e('New in version 1.1.0:', 'multi-platform-sync'); ?></strong>
            </p>
            <ul style="list-style-type: disc; padding-left: 20px;">
                <li><?php _e('Enhanced field mapping with intelligent detection', 'multi-platform-sync'); ?></li>
                <li><?php _e('Background queue processing for improved reliability', 'multi-platform-sync'); ?></li>
                <li><?php _e('Advanced analytics and reporting dashboard', 'multi-platform-sync'); ?></li>
                <li><?php _e('Data transformation and validation', 'multi-platform-sync'); ?></li>
                <li><?php _e('Connection testing for all integrations', 'multi-platform-sync'); ?></li>
            </ul>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=multi-platform-sync-analytics')); ?>" class="button button-primary">
                    <?php _e('View Analytics Dashboard', 'multi-platform-sync'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=multi-platform-sync-settings')); ?>" class="button">
                    <?php _e('Review Settings', 'multi-platform-sync'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Display notice about Gravity Forms Zapier add-on.
     *
     * @since    1.1.0
     */
    public function display_zapier_addon_notice() {
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php _e('Gravity Forms Zapier Add-on Detected', 'multi-platform-sync'); ?></strong>
            </p>
            <p>
                <?php _e('The official Gravity Forms Zapier Add-on is active. Multi-Platform Sync has automatically configured itself to:', 'multi-platform-sync'); ?>
            </p>
            <ul style="list-style-type: disc; padding-left: 20px;">
                <li><?php _e('Let the official add-on handle sending Gravity Forms data to Zapier', 'multi-platform-sync'); ?></li>
                <li><?php _e('Continue processing data from Zapier to Campaign Monitor and Quickbase', 'multi-platform-sync'); ?></li>
            </ul>
            <p>
                <?php _e('This prevents duplicate data being sent to Zapier and ensures compatibility.', 'multi-platform-sync'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Add menu items to the admin dashboard.
     *
     * @since    1.1.0
     */
    public function add_admin_menu() {
        // Register admin notices
        $this->register_admin_notices();
        
        // Main menu item
        add_menu_page(
            __('Multi-Platform Sync', 'multi-platform-sync'),
            __('Multi-Platform Sync', 'multi-platform-sync'),
            'manage_multi_platform_sync',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page'),
            'dashicons-update',
            30
        );

        // Submenu - Dashboard
        add_submenu_page(
            $this->plugin_name,
            __('Dashboard', 'multi-platform-sync'),
            __('Dashboard', 'multi-platform-sync'),
            'manage_multi_platform_sync',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page')
        );

        // Submenu - Analytics (NEW)
        add_submenu_page(
            $this->plugin_name,
            __('Analytics', 'multi-platform-sync'),
            __('Analytics', 'multi-platform-sync'),
            'manage_multi_platform_sync',
            $this->plugin_name . '-analytics',
            array($this, 'display_plugin_analytics_page')
        );

        // Submenu - Settings
        add_submenu_page(
            $this->plugin_name,
            __('Settings', 'multi-platform-sync'),
            __('Settings', 'multi-platform-sync'),
            'manage_multi_platform_sync',
            $this->plugin_name . '-settings',
            array($this, 'display_plugin_settings_page')
        );
        
        // Submenu - Logs
        add_submenu_page(
            $this->plugin_name,
            __('Sync Logs', 'multi-platform-sync'),
            __('Sync Logs', 'multi-platform-sync'),
            'manage_multi_platform_sync',
            $this->plugin_name . '-logs',
            array($this, 'display_plugin_logs_page')
        );
    }

    /**
     * Register plugin settings.
     *
     * @since    1.1.0
     */
    public function register_settings() {
        // Handle export requests
        $this->handle_export_requests();

        // Zapier settings section
        add_settings_section(
            'mps_zapier_settings',
            __('Zapier Integration Settings', 'multi-platform-sync'),
            array($this, 'zapier_settings_section_callback'),
            $this->plugin_name . '-settings'
        );

        // Zapier webhook URL
        register_setting(
            $this->plugin_name . '-settings',
            'mps_zapier_webhook_url',
            array(
                'sanitize_callback' => array($this, 'sanitize_zapier_webhook_url'),
                'default' => ''
            )
        );

        add_settings_field(
            'mps_zapier_webhook_url',
            __('Zapier Webhook URL', 'multi-platform-sync'),
            array($this, 'zapier_webhook_url_render'),
            $this->plugin_name . '-settings',
            'mps_zapier_settings'
        );

        // Gravity Forms section
        add_settings_section(
            'mps_gravity_forms_settings',
            __('Gravity Forms Settings', 'multi-platform-sync'),
            array($this, 'gravity_forms_settings_section_callback'),
            $this->plugin_name . '-settings'
        );

        // Gravity Forms to sync
        register_setting(
            $this->plugin_name . '-settings',
            'mps_gravity_forms_to_sync',
            array(
                'sanitize_callback' => array($this, 'sanitize_gravity_forms_array'),
                'default' => array()
            )
        );

        add_settings_field(
            'mps_gravity_forms_to_sync',
            __('Forms to Sync', 'multi-platform-sync'),
            array($this, 'gravity_forms_to_sync_render'),
            $this->plugin_name . '-settings',
            'mps_gravity_forms_settings'
        );
        
        // Campaign Monitor settings section
        add_settings_section(
            'mps_campaign_monitor_settings',
            __('Campaign Monitor Settings', 'multi-platform-sync'),
            array($this, 'campaign_monitor_settings_section_callback'),
            $this->plugin_name . '-settings'
        );
        
        // Campaign Monitor API Key
        register_setting(
            $this->plugin_name . '-settings',
            'mps_campaign_monitor_api_key',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
        
        add_settings_field(
            'mps_campaign_monitor_api_key',
            __('API Key', 'multi-platform-sync'),
            array($this, 'campaign_monitor_api_key_render'),
            $this->plugin_name . '-settings',
            'mps_campaign_monitor_settings'
        );
        
        // Campaign Monitor List ID
        register_setting(
            $this->plugin_name . '-settings',
            'mps_campaign_monitor_list_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
        
        add_settings_field(
            'mps_campaign_monitor_list_id',
            __('List ID', 'multi-platform-sync'),
            array($this, 'campaign_monitor_list_id_render'),
            $this->plugin_name . '-settings',
            'mps_campaign_monitor_settings'
        );
        
        // Quickbase settings section
        add_settings_section(
            'mps_quickbase_settings',
            __('Quickbase Settings', 'multi-platform-sync'),
            array($this, 'quickbase_settings_section_callback'),
            $this->plugin_name . '-settings'
        );
        
        // Quickbase Realm Hostname
        register_setting(
            $this->plugin_name . '-settings',
            'mps_quickbase_realm_hostname',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
        
        add_settings_field(
            'mps_quickbase_realm_hostname',
            __('Realm Hostname', 'multi-platform-sync'),
            array($this, 'quickbase_realm_hostname_render'),
            $this->plugin_name . '-settings',
            'mps_quickbase_settings'
        );
        
        // Quickbase User Token
        register_setting(
            $this->plugin_name . '-settings',
            'mps_quickbase_user_token',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
        
        add_settings_field(
            'mps_quickbase_user_token',
            __('User Token', 'multi-platform-sync'),
            array($this, 'quickbase_user_token_render'),
            $this->plugin_name . '-settings',
            'mps_quickbase_settings'
        );
        
        // Quickbase App ID
        register_setting(
            $this->plugin_name . '-settings',
            'mps_quickbase_app_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
        
        add_settings_field(
            'mps_quickbase_app_id',
            __('App ID', 'multi-platform-sync'),
            array($this, 'quickbase_app_id_render'),
            $this->plugin_name . '-settings',
            'mps_quickbase_settings'
        );
        
        // Quickbase Table ID
        register_setting(
            $this->plugin_name . '-settings',
            'mps_quickbase_table_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
        
        add_settings_field(
            'mps_quickbase_table_id',
            __('Table ID', 'multi-platform-sync'),
            array($this, 'quickbase_table_id_render'),
            $this->plugin_name . '-settings',
            'mps_quickbase_settings'
        );
        
        // Rate Limiting settings section
        add_settings_section(
            'mps_rate_limiting_settings',
            __('API Rate Limiting Settings', 'multi-platform-sync'),
            array($this, 'rate_limiting_settings_section_callback'),
            $this->plugin_name . '-settings'
        );
        
        // Enable rate limiting
        register_setting(
            $this->plugin_name . '-settings',
            'mps_rate_limit_enabled',
            array(
                'sanitize_callback' => 'intval',
                'default' => 1
            )
        );
        
        add_settings_field(
            'mps_rate_limit_enabled',
            __('Enable Rate Limiting', 'multi-platform-sync'),
            array($this, 'rate_limit_enabled_render'),
            $this->plugin_name . '-settings',
            'mps_rate_limiting_settings'
        );
        
        // Max requests setting
        register_setting(
            $this->plugin_name . '-settings',
            'mps_rate_limit_max_requests',
            array(
                'sanitize_callback' => array($this, 'sanitize_positive_integer'),
                'default' => 10
            )
        );
        
        add_settings_field(
            'mps_rate_limit_max_requests',
            __('Max Requests Per Period', 'multi-platform-sync'),
            array($this, 'rate_limit_max_requests_render'),
            $this->plugin_name . '-settings',
            'mps_rate_limiting_settings'
        );
        
        // Period setting
        register_setting(
            $this->plugin_name . '-settings',
            'mps_rate_limit_period',
            array(
                'sanitize_callback' => array($this, 'sanitize_positive_integer'),
                'default' => 60
            )
        );
        
        add_settings_field(
            'mps_rate_limit_period',
            __('Period (seconds)', 'multi-platform-sync'),
            array($this, 'rate_limit_period_render'),
            $this->plugin_name . '-settings',
            'mps_rate_limiting_settings'
        );

        // Advanced settings section (NEW)
        add_settings_section(
            'mps_advanced_settings',
            __('Advanced Settings', 'multi-platform-sync'),
            array($this, 'advanced_settings_section_callback'),
            $this->plugin_name . '-settings'
        );

        // Enable queue processing
        register_setting(
            $this->plugin_name . '-settings',
            'mps_enable_queue_processing',
            array(
                'sanitize_callback' => 'intval',
                'default' => 1
            )
        );
        
        add_settings_field(
            'mps_enable_queue_processing',
            __('Enable Queue Processing', 'multi-platform-sync'),
            array($this, 'enable_queue_processing_render'),
            $this->plugin_name . '-settings',
            'mps_advanced_settings'
        );

        // Enable field mapping
        register_setting(
            $this->plugin_name . '-settings',
            'mps_enable_field_mapping',
            array(
                'sanitize_callback' => 'intval',
                'default' => 1
            )
        );
        
        add_settings_field(
            'mps_enable_field_mapping',
            __('Enable Smart Field Mapping', 'multi-platform-sync'),
            array($this, 'enable_field_mapping_render'),
            $this->plugin_name . '-settings',
            'mps_advanced_settings'
        );

        // Log retention days
        register_setting(
            $this->plugin_name . '-settings',
            'mps_log_retention_days',
            array(
                'sanitize_callback' => array($this, 'sanitize_positive_integer'),
                'default' => 30
            )
        );
        
        add_settings_field(
            'mps_log_retention_days',
            __('Log Retention (days)', 'multi-platform-sync'),
            array($this, 'log_retention_days_render'),
            $this->plugin_name . '-settings',
            'mps_advanced_settings'
        );
    }

    /**
     * Handle export requests.
     *
     * @since    1.1.0
     */
    private function handle_export_requests() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'multi-platform-sync-analytics') {
            return;
        }

        if (!isset($_GET['export']) || !current_user_can('manage_multi_platform_sync')) {
            return;
        }

        $export_format = sanitize_text_field($_GET['export']);
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30days';

        if (!in_array($export_format, array('json', 'csv'))) {
            return;
        }

        $report = Multi_Platform_Sync_Analytics::generate_report(array('period' => $period));
        $exported_data = Multi_Platform_Sync_Analytics::export_report($report, $export_format);

        $filename = 'mps-analytics-' . date('Y-m-d') . '.' . $export_format;
        
        header('Content-Type: application/' . $export_format);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($exported_data));
        
        echo $exported_data;
        exit;
    }
    
    /**
     * Sanitize Gravity Forms array.
     *
     * @since    1.1.0
     * @param    array    $input    Array of form IDs.
     * @return   array    Sanitized array.
     */
    public function sanitize_gravity_forms_array($input) {
        if (!is_array($input)) {
            return array();
        }
        
        return array_map('absint', $input);
    }

    /**
     * Callback for Zapier settings section.
     *
     * @since    1.1.0
     */
    public function zapier_settings_section_callback() {
        // Check if Gravity Forms Zapier add-on is detected
        if (get_option('mps_gf_zapier_addon_detected', false)) {
            echo '<div class="notice notice-info inline"><p>';
            echo '<strong>' . esc_html__('Gravity Forms Zapier Add-on Detected', 'multi-platform-sync') . '</strong><br>';
            echo esc_html__('The plugin is configured to use the official Gravity Forms Zapier Add-on for sending data to Zapier.', 'multi-platform-sync');
            echo '</p></div>';
            
            echo '<p>' . esc_html__('To send data from Zapier back to this site (for Campaign Monitor and Quickbase integrations), use this webhook URL in your Zap:', 'multi-platform-sync') . '</p>';
            
            $webhook_url = rest_url('multi-platform-sync/v1/webhook');
            echo '<input type="text" class="large-text code" readonly value="' . esc_url($webhook_url) . '" onclick="this.select();" />';
            echo '<p class="description">' . esc_html__('Add this as a "Webhook" action step in your Zap to send data back to this site.', 'multi-platform-sync') . '</p>';
        } else {
            echo '<p>' . esc_html__('Configure your Zapier webhook URL to send form data.', 'multi-platform-sync') . '</p>';
        }
    }

    /**
     * Render the Zapier webhook URL field.
     *
     * @since    1.1.0
     */
    public function zapier_webhook_url_render() {
        // Check if Gravity Forms Zapier add-on is detected
        if (get_option('mps_gf_zapier_addon_detected', false)) {
            echo '<p>' . esc_html__('This setting is not used when the Gravity Forms Zapier Add-on is active.', 'multi-platform-sync') . '</p>';
            echo '<p>' . esc_html__('Configure your Zapier integration through the Gravity Forms Zapier Add-on settings.', 'multi-platform-sync') . '</p>';
            
            // Hidden field to preserve the value
            $webhook_url = get_option('mps_zapier_webhook_url', '');
            echo '<input type="hidden" name="mps_zapier_webhook_url" value="' . esc_attr($webhook_url) . '" />';
        } else {
            $webhook_url = get_option('mps_zapier_webhook_url', '');
            ?>
            <input type="url" class="regular-text" name="mps_zapier_webhook_url" value="<?php echo esc_url($webhook_url); ?>" />
            <button type="button" class="button mps-test-connection" data-platform="zapier">
                <?php esc_html_e('Test Connection', 'multi-platform-sync'); ?>
            </button>
            <p class="description"><?php esc_html_e('Enter the webhook URL provided by your Zapier Zap.', 'multi-platform-sync'); ?></p>
            <?php
        }
    }

    /**
     * Callback for Gravity Forms settings section.
     *
     * @since    1.1.0
     */
    public function gravity_forms_settings_section_callback() {
        // Check if Gravity Forms Zapier add-on is detected
        if (get_option('mps_gf_zapier_addon_detected', false)) {
            echo '<div class="notice notice-info inline"><p>';
            echo esc_html__('Form selection is not needed when using the Gravity Forms Zapier Add-on. The add-on handles which forms send data to Zapier.', 'multi-platform-sync');
            echo '</p></div>';
        } else {
            echo '<p>' . esc_html__('Select which Gravity Forms to sync with external platforms.', 'multi-platform-sync') . '</p>';
            echo '<p><strong>' . esc_html__('Note:', 'multi-platform-sync') . '</strong> ';
            echo esc_html__('This selection is only needed if you want to send Gravity Forms data directly to Zapier. If you\'re receiving data from external sources through Zapier, you don\'t need to select any forms here.', 'multi-platform-sync') . '</p>';
        }
    }

    /**
     * Render the Gravity Forms selection field.
     *
     * @since    1.1.0
     */
    public function gravity_forms_to_sync_render() {
        // Check if Gravity Forms Zapier add-on is detected
        if (get_option('mps_gf_zapier_addon_detected', false)) {
            echo '<div class="notice notice-info inline"><p>';
            echo esc_html__('When using the Gravity Forms Zapier Add-on, form selection is managed through the Gravity Forms Zapier Add-on settings.', 'multi-platform-sync');
            echo '</p></div>';
            
            // Hidden field to preserve values
            $selected_forms = get_option('mps_gravity_forms_to_sync', array());
            foreach ($selected_forms as $form_id) {
                echo '<input type="hidden" name="mps_gravity_forms_to_sync[]" value="' . esc_attr($form_id) . '" />';
            }
            
            return;
        }
        
        if (!class_exists('GFForms')) {
            echo '<p>' . esc_html__('Gravity Forms is not installed or activated.', 'multi-platform-sync') . '</p>';
            return;
        }
        
        $forms = \GFAPI::get_forms();
        $selected_forms = get_option('mps_gravity_forms_to_sync', array());
        
        if (empty($forms)) {
            echo '<p>' . esc_html__('No Gravity Forms found.', 'multi-platform-sync') . '</p>';
            return;
        }
        
        echo '<fieldset>';
        foreach ($forms as $form) {
            $checked = in_array($form['id'], $selected_forms) ? 'checked="checked"' : '';
            echo '<label>';
            echo '<input type="checkbox" name="mps_gravity_forms_to_sync[]" value="' . esc_attr($form['id']) . '" ' . $checked . ' />';
            echo esc_html($form['title']) . ' (ID: ' . esc_html($form['id']) . ')';
            echo '</label><br>';
        }
        echo '</fieldset>';
    }
    
    /**
     * Callback for Campaign Monitor settings section.
     *
     * @since    1.1.0
     */
    public function campaign_monitor_settings_section_callback() {
        echo '<p>' . esc_html__('Configure your Campaign Monitor integration settings.', 'multi-platform-sync') . '</p>';
    }
    
    /**
     * Render the Campaign Monitor API Key field.
     *
     * @since    1.1.0
     */
    public function campaign_monitor_api_key_render() {
        $api_key = get_option('mps_campaign_monitor_api_key', '');
        ?>
        <input type="password" class="regular-text" name="mps_campaign_monitor_api_key" value="<?php echo esc_attr($api_key); ?>" />
        <button type="button" class="button mps-test-connection" data-platform="campaign_monitor">
            <?php esc_html_e('Test Connection', 'multi-platform-sync'); ?>
        </button>
        <p class="description"><?php esc_html_e('Enter your Campaign Monitor API key.', 'multi-platform-sync'); ?></p>
        <?php
    }
    
    /**
     * Render the Campaign Monitor List ID field.
     *
     * @since    1.1.0
     */
    public function campaign_monitor_list_id_render() {
        $list_id = get_option('mps_campaign_monitor_list_id', '');
        ?>
        <input type="text" class="regular-text" name="mps_campaign_monitor_list_id" value="<?php echo esc_attr($list_id); ?>" />
        <p class="description"><?php esc_html_e('Enter your Campaign Monitor List ID.', 'multi-platform-sync'); ?></p>
        <?php
    }
    
    /**
     * Callback for Quickbase settings section.
     *
     * @since    1.1.0
     */
    public function quickbase_settings_section_callback() {
        echo '<p>' . esc_html__('Configure your Quickbase integration settings.', 'multi-platform-sync') . '</p>';
    }
    
    /**
     * Render the Quickbase Realm Hostname field.
     *
     * @since    1.1.0
     */
    public function quickbase_realm_hostname_render() {
        $hostname = get_option('mps_quickbase_realm_hostname', '');
        ?>
        <input type="text" class="regular-text" name="mps_quickbase_realm_hostname" value="<?php echo esc_attr($hostname); ?>" />
        <p class="description"><?php esc_html_e('Enter your Quickbase realm hostname (e.g., yourrealm.quickbase.com).', 'multi-platform-sync'); ?></p>
        <?php
    }
    
    /**
     * Render the Quickbase User Token field.
     *
     * @since    1.1.0
     */
    public function quickbase_user_token_render() {
        $user_token = get_option('mps_quickbase_user_token', '');
        ?>
        <input type="password" class="regular-text" name="mps_quickbase_user_token" value="<?php echo esc_attr($user_token); ?>" />
        <button type="button" class="button mps-test-connection" data-platform="quickbase">
            <?php esc_html_e('Test Connection', 'multi-platform-sync'); ?>
        </button>
        <p class="description"><?php esc_html_e('Enter your Quickbase user token.', 'multi-platform-sync'); ?></p>
        <?php
    }
    
    /**
     * Render the Quickbase App ID field.
     *
     * @since    1.1.0
     */
    public function quickbase_app_id_render() {
        $app_id = get_option('mps_quickbase_app_id', '');
        ?>
        <input type="text" class="regular-text" name="mps_quickbase_app_id" value="<?php echo esc_attr($app_id); ?>" />
        <p class="description"><?php esc_html_e('Enter your Quickbase application ID.', 'multi-platform-sync'); ?></p>
        <?php
    }
    
    /**
     * Render the Quickbase Table ID field.
     *
     * @since    1.1.0
     */
    public function quickbase_table_id_render() {
        $table_id = get_option('mps_quickbase_table_id', '');
        ?>
        <input type="text" class="regular-text" name="mps_quickbase_table_id" value="<?php echo esc_attr($table_id); ?>" />
        <p class="description"><?php esc_html_e('Enter your Quickbase table ID.', 'multi-platform-sync'); ?></p>
        <?php
    }

    /**
     * Callback for Rate Limiting settings section.
     *
     * @since    1.1.0
     */
    public function rate_limiting_settings_section_callback() {
        echo '<p>' . esc_html__('Configure API rate limiting to prevent hitting API limits and improve reliability.', 'multi-platform-sync') . '</p>';
    }
    
    /**
     * Render the Enable Rate Limiting field.
     *
     * @since    1.1.0
     */
    public function rate_limit_enabled_render() {
        $enabled = get_option('mps_rate_limit_enabled', 1);
        ?>
        <label>
            <input type="checkbox" name="mps_rate_limit_enabled" value="1" <?php checked(1, $enabled); ?> />
            <?php esc_html_e('Enable API rate limiting', 'multi-platform-sync'); ?>
        </label>
        <p class="description"><?php esc_html_e('Recommended to prevent hitting API rate limits.', 'multi-platform-sync'); ?></p>
        <?php
    }
    
    /**
     * Render the Max Requests field.
     *
     * @since    1.1.0
     */
    public function rate_limit_max_requests_render() {
        $max_requests = get_option('mps_rate_limit_max_requests', 10);
        ?>
        <input type="number" min="1" class="small-text" name="mps_rate_limit_max_requests" value="<?php echo esc_attr($max_requests); ?>" />
        <p class="description"><?php esc_html_e('Maximum number of requests allowed within the time period.', 'multi-platform-sync'); ?></p>
        <?php
    }
    
    /**
     * Render the Period field.
     *
     * @since    1.1.0
     */
    public function rate_limit_period_render() {
        $period = get_option('mps_rate_limit_period', 60);
        ?>
        <input type="number" min="1" class="small-text" name="mps_rate_limit_period" value="<?php echo esc_attr($period); ?>" />
        <p class="description"><?php esc_html_e('Time period in seconds for rate limiting.', 'multi-platform-sync'); ?></p>
        <?php
    }

    /**
     * Callback for Advanced settings section.
     *
     * @since    1.1.0
     */
    public function advanced_settings_section_callback() {
        echo '<p>' . esc_html__('Advanced configuration options for enhanced functionality.', 'multi-platform-sync') . '</p>';
    }

    /**
     * Render the Enable Queue Processing field.
     *
     * @since    1.1.0
     */
    public function enable_queue_processing_render() {
        $enabled = get_option('mps_enable_queue_processing', 1);
        ?>
        <label>
            <input type="checkbox" name="mps_enable_queue_processing" value="1" <?php checked(1, $enabled); ?> />
            <?php esc_html_e('Enable background queue processing', 'multi-platform-sync'); ?>
        </label>
        <p class="description"><?php esc_html_e('Process syncs in the background for better performance and reliability. Recommended for high-volume sites.', 'multi-platform-sync'); ?></p>
        <?php
    }

    /**
     * Render the Enable Field Mapping field.
     *
     * @since    1.1.0
     */
    public function enable_field_mapping_render() {
        $enabled = get_option('mps_enable_field_mapping', 1);
        ?>
        <label>
            <input type="checkbox" name="mps_enable_field_mapping" value="1" <?php checked(1, $enabled); ?> />
            <?php esc_html_e('Enable intelligent field mapping', 'multi-platform-sync'); ?>
        </label>
        <p class="description"><?php esc_html_e('Automatically detect and map form fields to standard field types for better data consistency.', 'multi-platform-sync'); ?></p>
        <?php
    }

    /**
     * Render the Log Retention Days field.
     *
     * @since    1.1.0
     */
    public function log_retention_days_render() {
        $days = get_option('mps_log_retention_days', 30);
        ?>
        <input type="number" min="1" max="365" class="small-text" name="mps_log_retention_days" value="<?php echo esc_attr($days); ?>" />
        <p class="description"><?php esc_html_e('Number of days to keep sync logs. Older logs will be automatically deleted.', 'multi-platform-sync'); ?></p>
        <?php
    }

    /**
     * Render the main admin page.
     *
     * @since    1.1.0
     */
    public function display_plugin_admin_page() {
        // Check user capability
        if (!current_user_can('manage_multi_platform_sync')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'multi-platform-sync'));
        }
        
        include_once MPS_PLUGIN_DIR . 'includes/admin/partials/admin-display.php';
    }

    /**
     * Render the analytics page.
     *
     * @since    1.1.0
     */
    public function display_plugin_analytics_page() {
        // Check user capability
        if (!current_user_can('manage_multi_platform_sync')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'multi-platform-sync'));
        }
        
        include_once MPS_PLUGIN_DIR . 'includes/admin/partials/admin-analytics.php';
    }

    /**
     * Render the settings page.
     *
     * @since    1.1.0
     */
    public function display_plugin_settings_page() {
        // Check user capability
        if (!current_user_can('manage_multi_platform_sync')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'multi-platform-sync'));
        }
        
        include_once MPS_PLUGIN_DIR . 'includes/admin/partials/admin-settings.php';
    }
    
    /**
     * Render the logs page.
     *
     * @since    1.1.0
     */
    public function display_plugin_logs_page() {
        // Check user capability
        if (!current_user_can('manage_multi_platform_sync')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'multi-platform-sync'));
        }
        
        include_once MPS_PLUGIN_DIR . 'includes/admin/partials/admin-logs.php';
    }
    
    /**
     * Add settings link to plugin listing.
     *
     * @since    1.1.0
     * @param    array    $links    Default plugin action links.
     * @return   array    Plugin action links.
     */
    public function add_plugin_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=' . $this->plugin_name . '-settings') . '">' . __('Settings', 'multi-platform-sync') . '</a>';
        $analytics_link = '<a href="' . admin_url('admin.php?page=' . $this->plugin_name . '-analytics') . '">' . __('Analytics', 'multi-platform-sync') . '</a>';
        array_unshift($links, $analytics_link, $settings_link);
        return $links;
    }

    /**
     * Sanitize and validate the Zapier webhook URL.
     *
     * @since    1.1.0
     * @param    string    $input    The webhook URL to sanitize.
     * @return   string    The sanitized webhook URL.
     */
    public function sanitize_zapier_webhook_url($input) {
        // Basic sanitization
        $sanitized_url = esc_url_raw($input);
        
        // If empty, return as is (user might be clearing the field)
        if (empty($sanitized_url)) {
            return '';
        }
        
        // Validate that it's a valid URL
        if (!filter_var($sanitized_url, FILTER_VALIDATE_URL)) {
            add_settings_error(
                'mps_zapier_webhook_url',
                'invalid_url',
                __('The provided Zapier webhook URL is not a valid URL.', 'multi-platform-sync')
            );
            return get_option('mps_zapier_webhook_url', '');
        }
        
        // Validate that it's a Zapier webhook URL
        $parsed_url = parse_url($sanitized_url);
        $is_zapier_url = false;
        
        // Check if the domain is from Zapier (zapier.com or hooks.zapier.com)
        if (isset($parsed_url['host'])) {
            $host = strtolower($parsed_url['host']);
            if ($host === 'hooks.zapier.com' || $host === 'zapier.com' || strpos($host, '.zapier.com') !== false) {
                $is_zapier_url = true;
            }
        }
        
        if (!$is_zapier_url) {
            add_settings_error(
                'mps_zapier_webhook_url',
                'not_zapier_url',
                __('The provided URL does not appear to be a valid Zapier webhook URL. It should be from zapier.com or hooks.zapier.com.', 'multi-platform-sync')
            );
            return get_option('mps_zapier_webhook_url', '');
        }
        
        return $sanitized_url;
    }

    /**
     * Sanitize a positive integer.
     *
     * @since    1.1.0
     * @param    mixed    $input    The input to sanitize.
     * @return   int      The sanitized positive integer.
     */
    public function sanitize_positive_integer($input) {
        $value = absint($input);
        return $value > 0 ? $value : 1; // Ensure value is at least 1
    }
}