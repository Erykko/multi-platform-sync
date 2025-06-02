<?php
/**
 * Fired during plugin deactivation.
 *
 * @link       https://righthereinteractive.com
 * @since      1.0.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 * @author     Your Name <email@righthereinteractive.com>
 */
class Multi_Platform_Sync_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Cleans up when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Remove capabilities from administrators
        $role = get_role('administrator');
        if ($role) {
            $role->remove_cap('manage_multi_platform_sync');
        }
        
        // Note: We don't delete options here to preserve settings
        // if the plugin is reactivated. They can be cleaned up during uninstall.
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
} 