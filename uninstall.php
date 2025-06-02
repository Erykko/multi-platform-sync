<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://righthereinteractive.com
 * @since      1.0.0
 *
 * @package    Multi_Platform_Sync
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
$options = array(
    'mps_zapier_webhook_url',
    'mps_gravity_forms_to_sync',
    'mps_campaign_monitor_api_key',
    'mps_campaign_monitor_list_id',
    'mps_quickbase_realm_hostname',
    'mps_quickbase_user_token',
    'mps_quickbase_app_id',
    'mps_quickbase_table_id'
);

foreach ($options as $option) {
    delete_option($option);
}

// Remove capabilities from administrators
$role = get_role('administrator');
if ($role) {
    $role->remove_cap('manage_multi_platform_sync');
}

// Drop custom tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mps_sync_logs");

// Clear any scheduled hooks
wp_clear_scheduled_hook('mps_manual_sync'); 