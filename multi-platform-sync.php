<?php
/**
 * Plugin Name: Multi-Platform Sync
 * Plugin URI: https://righthereinteractive.com/multi-platform-sync
 * Description: Syncs Gravity Forms data with Zapier, Campaign Monitor, and Quickbase.
 * Version: 1.0.0
 * Author: Eric Mutema
 * Author URI: https://righthereinteractive.com
 * Text Domain: multi-platform-sync
 * Domain Path: /languages
 * License: GPL-2.0+
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MPS_VERSION', '1.0.0');
define('MPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MPS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The core plugin class
 */
require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync.php';

/**
 * Begins execution of the plugin.
 */
function run_multi_platform_sync() {
    $plugin = new Multi_Platform_Sync();
    $plugin->run();
}

// Check if Gravity Forms is active
add_action('plugins_loaded', function() {
    if (class_exists('GFForms')) {
        run_multi_platform_sync();
    } else {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Multi-Platform Sync requires Gravity Forms to be installed and activated.', 'multi-platform-sync'); ?></p>
            </div>
            <?php
        });
    }
});

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'multi_platform_sync_activate');
register_deactivation_hook(__FILE__, 'multi_platform_sync_deactivate');

/**
 * Plugin activation.
 */
function multi_platform_sync_activate() {
    // Activation tasks
    require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-activator.php';
    Multi_Platform_Sync_Activator::activate();
}

/**
 * Plugin deactivation.
 */
function multi_platform_sync_deactivate() {
    // Deactivation tasks
    require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-deactivator.php';
    Multi_Platform_Sync_Deactivator::deactivate();
} 