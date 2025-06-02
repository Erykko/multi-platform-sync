<?php
/**
 * Settings page for the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.0.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('multi-platform-sync-settings');
        ?>
        
        <div class="mps-settings-container">
            <div class="mps-settings-nav">
                <ul>
                    <li>
                        <a href="#zapier-settings" class="mps-tab-link active">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('Zapier', 'multi-platform-sync'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#gravity-forms-settings" class="mps-tab-link">
                            <span class="dashicons dashicons-feedback"></span>
                            <?php esc_html_e('Gravity Forms', 'multi-platform-sync'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#campaign-monitor-settings" class="mps-tab-link">
                            <span class="dashicons dashicons-email"></span>
                            <?php esc_html_e('Campaign Monitor', 'multi-platform-sync'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#quickbase-settings" class="mps-tab-link">
                            <span class="dashicons dashicons-database"></span>
                            <?php esc_html_e('Quickbase', 'multi-platform-sync'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="mps-settings-content">
                <div id="zapier-settings" class="mps-tab-content active">
                    <h2><?php esc_html_e('Zapier Integration Settings', 'multi-platform-sync'); ?></h2>
                    <?php do_settings_sections('multi-platform-sync-settings'); ?>
                </div>
                
                <div id="gravity-forms-settings" class="mps-tab-content">
                    <h2><?php esc_html_e('Gravity Forms Settings', 'multi-platform-sync'); ?></h2>
                    <?php do_settings_sections('multi-platform-sync-settings'); ?>
                </div>
                
                <div id="campaign-monitor-settings" class="mps-tab-content">
                    <h2><?php esc_html_e('Campaign Monitor Settings', 'multi-platform-sync'); ?></h2>
                    <?php do_settings_sections('multi-platform-sync-settings'); ?>
                </div>
                
                <div id="quickbase-settings" class="mps-tab-content">
                    <h2><?php esc_html_e('Quickbase Settings', 'multi-platform-sync'); ?></h2>
                    <?php do_settings_sections('multi-platform-sync-settings'); ?>
                </div>
            </div>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div> 