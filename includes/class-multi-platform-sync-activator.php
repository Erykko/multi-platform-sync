<?php
/**
 * Fired during plugin activation.
 *
 * @link       https://righthereinteractive.com
 * @since      1.1.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.1.0
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 * @author     Eric Mutema <eric@righthereinteractive.com>
 */
class Multi_Platform_Sync_Activator {

    /**
     * Activate the plugin.
     *
     * Creates necessary database tables and sets up initial options.
     *
     * @since    1.1.0
     */
    public static function activate() {
        // Store the current version
        $installed_version = get_option('mps_version', '0.0.0');
        
        // Create options with default values if they don't exist
        self::create_default_options();
        
        // Add capabilities to administrators
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_multi_platform_sync');
        }
        
        // Create or update database tables
        self::create_or_update_tables();
        
        // Run upgrade routines if needed
        if (version_compare($installed_version, MPS_VERSION, '<')) {
            self::upgrade_from_version($installed_version);
        }
        
        // Update the stored version
        update_option('mps_version', MPS_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Schedule queue cleanup
        if (!wp_next_scheduled('mps_cleanup_queue')) {
            wp_schedule_event(time(), 'daily', 'mps_cleanup_queue');
        }
    }
    
    /**
     * Create default options.
     *
     * @since    1.1.0
     */
    private static function create_default_options() {
        $default_options = array(
            'mps_zapier_webhook_url' => '',
            'mps_gravity_forms_to_sync' => array(),
            'mps_campaign_monitor_api_key' => '',
            'mps_campaign_monitor_list_id' => '',
            'mps_quickbase_realm_hostname' => '',
            'mps_quickbase_user_token' => '',
            'mps_quickbase_app_id' => '',
            'mps_quickbase_table_id' => '',
            'mps_rate_limit_enabled' => true,
            'mps_rate_limit_max_requests' => 10,
            'mps_rate_limit_period' => 60,
            'mps_enable_queue_processing' => true,
            'mps_analytics_retention_days' => 90,
            'mps_enable_field_mapping' => true,
            'mps_enable_data_transformation' => true,
            'mps_log_retention_days' => 30
        );
        
        foreach ($default_options as $option_name => $default_value) {
            if (false === get_option($option_name)) {
                add_option($option_name, $default_value);
            }
        }
    }
    
    /**
     * Create or update database tables.
     *
     * @since    1.1.0
     */
    private static function create_or_update_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sync logs table (existing)
        $logs_table = $wpdb->prefix . 'mps_sync_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            sync_type varchar(50) NOT NULL,
            form_id mediumint(9),
            entry_id varchar(50),
            status varchar(20) NOT NULL,
            message text NOT NULL,
            PRIMARY KEY  (id),
            KEY sync_type (sync_type),
            KEY status (status),
            KEY form_id (form_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Queue table (new)
        $queue_table = $wpdb->prefix . 'mps_sync_queue';
        $queue_sql = "CREATE TABLE $queue_table (
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
            KEY priority (priority),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Field mappings table (new)
        $mappings_table = $wpdb->prefix . 'mps_field_mappings';
        $mappings_sql = "CREATE TABLE $mappings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            field_id varchar(50) NOT NULL,
            field_label varchar(255) NOT NULL,
            mapped_type varchar(50) NOT NULL,
            platform varchar(50) NOT NULL,
            confidence_score decimal(3,2) DEFAULT 0.00,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY form_field_platform (form_id, field_id, platform),
            KEY mapped_type (mapped_type),
            KEY platform (platform),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($logs_sql);
        dbDelta($queue_sql);
        dbDelta($mappings_sql);
    }
    
    /**
     * Upgrade from previous version.
     *
     * @since    1.1.0
     * @param    string    $from_version    The version being upgraded from.
     */
    private static function upgrade_from_version($from_version) {
        // Upgrade routines for different versions
        
        if (version_compare($from_version, '1.1.0', '<')) {
            // Upgrading to 1.1.0 - add new tables and options
            self::upgrade_to_1_1_0();
        }
        
        // Add more upgrade routines as needed for future versions
    }
    
    /**
     * Upgrade to version 1.1.0.
     *
     * @since    1.1.0
     */
    private static function upgrade_to_1_1_0() {
        // Queue table is created in create_or_update_tables()
        // Field mappings table is created in create_or_update_tables()
        
        // Migrate any existing data if needed
        self::migrate_existing_data();
        
        // Add new options
        $new_options = array(
            'mps_enable_queue_processing' => true,
            'mps_analytics_retention_days' => 90,
            'mps_enable_field_mapping' => true,
            'mps_enable_data_transformation' => true,
            'mps_log_retention_days' => 30
        );
        
        foreach ($new_options as $option_name => $default_value) {
            if (false === get_option($option_name)) {
                add_option($option_name, $default_value);
            }
        }
        
        // Schedule new cron jobs
        if (!wp_next_scheduled('mps_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'mps_cleanup_logs');
        }
    }
    
    /**
     * Migrate existing data for new features.
     *
     * @since    1.1.0
     */
    private static function migrate_existing_data() {
        global $wpdb;
        
        // Check if we have existing forms configured
        $selected_forms = get_option('mps_gravity_forms_to_sync', array());
        
        if (!empty($selected_forms) && class_exists('GFAPI')) {
            // Generate field mappings for existing forms
            foreach ($selected_forms as $form_id) {
                $form = GFAPI::get_form($form_id);
                if ($form) {
                    self::generate_initial_field_mappings($form);
                }
            }
        }
    }
    
    /**
     * Generate initial field mappings for a form.
     *
     * @since    1.1.0
     * @param    array    $form    The form object.
     */
    private static function generate_initial_field_mappings($form) {
        global $wpdb;
        
        $mappings_table = $wpdb->prefix . 'mps_field_mappings';
        $form_id = intval($form['id']);
        
        foreach ($form['fields'] as $field) {
            if (isset($field['inputs']) && is_array($field['inputs'])) {
                // Handle multi-input fields
                foreach ($field['inputs'] as $input) {
                    $field_id = strval($input['id']);
                    $field_label = isset($input['label']) ? $input['label'] : $field['label'];
                    
                    self::create_field_mapping_entry(
                        $mappings_table,
                        $form_id,
                        $field_id,
                        $field_label
                    );
                }
            } else {
                // Handle single input fields
                $field_id = strval($field['id']);
                $field_label = $field['label'];
                
                self::create_field_mapping_entry(
                    $mappings_table,
                    $form_id,
                    $field_id,
                    $field_label
                );
            }
        }
    }
    
    /**
     * Create a field mapping entry.
     *
     * @since    1.1.0
     * @param    string    $table_name    The mappings table name.
     * @param    int       $form_id       The form ID.
     * @param    string    $field_id      The field ID.
     * @param    string    $field_label   The field label.
     */
    private static function create_field_mapping_entry($table_name, $form_id, $field_id, $field_label) {
        global $wpdb;
        
        // Use the field mapper to detect field type
        require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-field-mapper.php';
        
        $detected_type = Multi_Platform_Sync_Field_Mapper::detect_field_type($field_label, '');
        
        if ($detected_type) {
            $platforms = array('campaign_monitor', 'quickbase');
            
            foreach ($platforms as $platform) {
                // Check if mapping already exists
                $existing = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM $table_name 
                        WHERE form_id = %d AND field_id = %s AND platform = %s",
                        $form_id,
                        $field_id,
                        $platform
                    )
                );
                
                if (!$existing) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'form_id' => $form_id,
                            'field_id' => $field_id,
                            'field_label' => $field_label,
                            'mapped_type' => $detected_type,
                            'platform' => $platform,
                            'confidence_score' => 0.8, // Default confidence for auto-detected
                            'is_active' => 1,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%s')
                    );
                }
            }
        }
    }
}