<?php
/**
 * Plugin Name: Multi-Platform Sync
 * Plugin URI: https://righthereinteractive.com/multi-platform-sync
 * Description: Advanced sync solution for Gravity Forms with Zapier, Campaign Monitor, and Quickbase. Features intelligent field mapping, background processing, and comprehensive analytics.
 * Version: 1.1.0
 * Author: Eric Mutema
 * Author URI: https://righthereinteractive.com
 * Text Domain: multi-platform-sync
 * Domain Path: /languages
 * License: GPL-2.0+
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('MPS_VERSION', '1.1.0');
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
                <p>
                    <strong><?php _e('Multi-Platform Sync', 'multi-platform-sync'); ?></strong>
                    <?php _e('requires Gravity Forms to be installed and activated.', 'multi-platform-sync'); ?>
                </p>
                <p>
                    <a href="https://www.gravityforms.com/" target="_blank" class="button button-primary">
                        <?php _e('Get Gravity Forms', 'multi-platform-sync'); ?>
                    </a>
                </p>
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

/**
 * Add custom cron schedules.
 */
add_filter('cron_schedules', function($schedules) {
    $schedules['mps_queue_interval'] = array(
        'interval' => 300, // 5 minutes
        'display'  => __('Every 5 Minutes (MPS Queue)', 'multi-platform-sync')
    );
    return $schedules;
});

/**
 * Handle queue cleanup cron job.
 */
add_action('mps_cleanup_queue', function() {
    if (class_exists('Multi_Platform_Sync_Queue')) {
        Multi_Platform_Sync_Queue::clear_old_items(7); // Clear items older than 7 days
    }
});

/**
 * Handle log cleanup cron job.
 */
add_action('mps_cleanup_logs', function() {
    global $wpdb;
    
    $retention_days = get_option('mps_log_retention_days', 30);
    $cutoff_date = date('Y-m-d H:i:s', time() - ($retention_days * DAY_IN_SECONDS));
    
    $table_name = $wpdb->prefix . 'mps_sync_logs';
    
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE timestamp < %s",
            $cutoff_date
        )
    );
});

/**
 * Add plugin action links.
 */
add_filter('plugin_action_links_' . MPS_PLUGIN_BASENAME, function($links) {
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=multi-platform-sync') . '">' . __('Dashboard', 'multi-platform-sync') . '</a>',
        '<a href="' . admin_url('admin.php?page=multi-platform-sync-analytics') . '">' . __('Analytics', 'multi-platform-sync') . '</a>',
        '<a href="' . admin_url('admin.php?page=multi-platform-sync-settings') . '">' . __('Settings', 'multi-platform-sync') . '</a>',
    );
    
    return array_merge($plugin_links, $links);
});

/**
 * Add plugin meta links.
 */
add_filter('plugin_row_meta', function($links, $file) {
    if ($file === MPS_PLUGIN_BASENAME) {
        $meta_links = array(
            '<a href="https://righthereinteractive.com/support" target="_blank">' . __('Support', 'multi-platform-sync') . '</a>',
            '<a href="https://righthereinteractive.com/docs/multi-platform-sync" target="_blank">' . __('Documentation', 'multi-platform-sync') . '</a>',
        );
        
        return array_merge($links, $meta_links);
    }
    
    return $links;
}, 10, 2);

/**
 * Load plugin textdomain for translations.
 */
add_action('plugins_loaded', function() {
    load_plugin_textdomain(
        'multi-platform-sync',
        false,
        dirname(MPS_PLUGIN_BASENAME) . '/languages/'
    );
});

/**
 * Add admin body class for plugin pages.
 */
add_filter('admin_body_class', function($classes) {
    $screen = get_current_screen();
    
    if ($screen && strpos($screen->id, 'multi-platform-sync') !== false) {
        $classes .= ' mps-admin-page';
    }
    
    return $classes;
});

/**
 * Enqueue admin styles globally for plugin pages.
 */
add_action('admin_enqueue_scripts', function() {
    $screen = get_current_screen();
    
    if ($screen && strpos($screen->id, 'multi-platform-sync') !== false) {
        wp_enqueue_style(
            'mps-admin-global',
            MPS_PLUGIN_URL . 'assets/css/multi-platform-sync-admin.css',
            array(),
            MPS_VERSION
        );
    }
});

/**
 * Add dashboard widget for quick stats.
 */
add_action('wp_dashboard_setup', function() {
    // Use fallback capability check
    if (current_user_can('manage_multi_platform_sync') || current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'mps_dashboard_widget',
            __('Multi-Platform Sync Stats', 'multi-platform-sync'),
            'mps_dashboard_widget_content'
        );
    }
});

/**
 * Dashboard widget content.
 */
function mps_dashboard_widget_content() {
    if (!class_exists('Multi_Platform_Sync_Analytics')) {
        echo '<p>' . __('Analytics not available.', 'multi-platform-sync') . '</p>';
        return;
    }
    
    $stats = Multi_Platform_Sync_Analytics::get_sync_stats(
        date('Y-m-d', strtotime('-7 days')),
        date('Y-m-d')
    );
    
    ?>
    <div class="mps-dashboard-widget">
        <div class="mps-widget-stats">
            <div class="mps-widget-stat">
                <span class="mps-widget-number"><?php echo esc_html(number_format($stats['summary']['total_syncs'])); ?></span>
                <span class="mps-widget-label"><?php _e('Total Syncs (7 days)', 'multi-platform-sync'); ?></span>
            </div>
            <div class="mps-widget-stat">
                <span class="mps-widget-number mps-success"><?php echo esc_html($stats['summary']['success_rate']); ?>%</span>
                <span class="mps-widget-label"><?php _e('Success Rate', 'multi-platform-sync'); ?></span>
            </div>
        </div>
        
        <div class="mps-widget-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=multi-platform-sync')); ?>" class="button button-primary">
                <?php _e('View Dashboard', 'multi-platform-sync'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=multi-platform-sync-analytics')); ?>" class="button">
                <?php _e('View Analytics', 'multi-platform-sync'); ?>
            </a>
        </div>
    </div>
    
    <style>
        .mps-dashboard-widget .mps-widget-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .mps-dashboard-widget .mps-widget-stat {
            text-align: center;
            flex: 1;
        }
        .mps-dashboard-widget .mps-widget-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        .mps-dashboard-widget .mps-widget-number.mps-success {
            color: #46b450;
        }
        .mps-dashboard-widget .mps-widget-label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .mps-dashboard-widget .mps-widget-actions {
            text-align: center;
        }
        .mps-dashboard-widget .mps-widget-actions .button {
            margin: 0 5px;
        }
    </style>
    <?php
}

/**
 * Fix capability issues on plugin activation.
 */
add_action('init', function() {
    // Ensure capabilities are properly set for administrators
    $admin_role = get_role('administrator');
    if ($admin_role && !$admin_role->has_cap('manage_multi_platform_sync')) {
        $admin_role->add_cap('manage_multi_platform_sync');
    }
    
    // Also add to editor role
    $editor_role = get_role('editor');
    if ($editor_role && !$editor_role->has_cap('manage_multi_platform_sync')) {
        $editor_role->add_cap('manage_multi_platform_sync');
    }
});