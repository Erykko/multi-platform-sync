<?php
/**
 * Fired during plugin activation.
 *
 * @link       https://righthereinteractive.com
 * @since      1.0.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
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
     * @since    1.0.0
     */
    public static function activate() {
        // Store the current version
        $installed_version = get_option('mps_version', '0.0.0');
        
        // Create options with default values if they don't exist
        if (false === get_option('mps_zapier_webhook_url')) {
            add_option('mps_zapier_webhook_url', '');
        }
        
        if (false === get_option('mps_gravity_forms_to_sync')) {
            add_option('mps_gravity_forms_to_sync', array());
        }
        
        if (false === get_option('mps_campaign_monitor_api_key')) {
            add_option('mps_campaign_monitor_api_key', '');
        }
        
        if (false === get_option('mps_campaign_monitor_list_id')) {
            add_option('mps_campaign_monitor_list_id', '');
        }
        
        if (false === get_option('mps_quickbase_realm_hostname')) {
            add_option('mps_quickbase_realm_hostname', '');
        }
        
        if (false === get_option('mps_quickbase_user_token')) {
            add_option('mps_quickbase_user_token', '');
        }
        
        if (false === get_option('mps_quickbase_app_id')) {
            add_option('mps_quickbase_app_id', '');
        }
        
        if (false === get_option('mps_quickbase_table_id')) {
            add_option('mps_quickbase_table_id', '');
        }
        
        // Add capabilities to administrators
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_multi_platform_sync');
        }
        
        // Create or update database tables
        self::create_or_update_tables();
        
        // Update the stored version
        update_option('mps_version', MPS_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create or update database tables.
     *
     * @since    1.0.0
     */
    private static function create_or_update_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mps_sync_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
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
            KEY form_id (form_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 