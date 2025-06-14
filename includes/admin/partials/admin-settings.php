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

<div class="wrap mps-admin-page">
    <h1 class="screen-reader-text"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Page Header -->
    <div class="mps-dashboard-header" style="margin-bottom: 30px;">
        <div class="mps-dashboard-heading">
            <h2><?php esc_html_e('Multi-Platform Sync Settings', 'multi-platform-sync'); ?></h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">
                <?php esc_html_e('Configure your integrations and sync preferences', 'multi-platform-sync'); ?>
            </p>
        </div>
        <div class="mps-dashboard-actions">
            <button type="submit" form="mps-settings-form" class="mps-button-primary">
                <span class="dashicons dashicons-yes" style="margin-right: 8px; font-size: 16px; line-height: 1;"></span>
                <?php esc_html_e('Save Settings', 'multi-platform-sync'); ?>
            </button>
        </div>
    </div>
    
    <form id="mps-settings-form" method="post" action="options.php">
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
                    <li>
                        <a href="#rate-limiting-settings" class="mps-tab-link">
                            <span class="dashicons dashicons-performance"></span>
                            <?php esc_html_e('Rate Limiting', 'multi-platform-sync'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#advanced-settings" class="mps-tab-link">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e('Advanced', 'multi-platform-sync'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="mps-settings-content">
                <!-- Zapier Settings -->
                <div id="zapier-settings" class="mps-tab-content active">
                    <h2 style="margin-top: 0; color: #2c3e50; display: flex; align-items: center;">
                        <span class="dashicons dashicons-admin-generic" style="margin-right: 10px; color: #667eea;"></span>
                        <?php esc_html_e('Zapier Integration Settings', 'multi-platform-sync'); ?>
                    </h2>
                    
                    <?php
                    // Check if Gravity Forms Zapier add-on is detected
                    if (get_option('mps_gf_zapier_addon_detected', false)) {
                        echo '<div class="notice notice-info inline" style="margin: 20px 0; padding: 15px; border-left: 4px solid #0073aa; background: #f0f6fc;">';
                        echo '<h4 style="margin-top: 0; color: #0073aa;">' . esc_html__('Gravity Forms Zapier Add-on Detected', 'multi-platform-sync') . '</h4>';
                        echo '<p>' . esc_html__('The plugin is configured to use the official Gravity Forms Zapier Add-on for sending data to Zapier.', 'multi-platform-sync') . '</p>';
                        echo '</div>';
                        
                        echo '<div class="mps-card" style="background: #f8f9fa; border-left: 4px solid #667eea;">';
                        echo '<h4>' . esc_html__('Webhook URL for Zapier', 'multi-platform-sync') . '</h4>';
                        echo '<p>' . esc_html__('To send data from Zapier back to this site (for Campaign Monitor and Quickbase integrations), use this webhook URL in your Zap:', 'multi-platform-sync') . '</p>';
                        
                        $webhook_url = rest_url('multi-platform-sync/v1/webhook');
                        echo '<div style="margin: 15px 0;">';
                        echo '<input type="text" class="large-text code" readonly value="' . esc_url($webhook_url) . '" onclick="this.select();" style="background: #ffffff; font-family: monospace; font-size: 13px;" />';
                        echo '<button type="button" class="button" onclick="navigator.clipboard.writeText(\'' . esc_js($webhook_url) . '\'); this.textContent=\'Copied!\'; setTimeout(() => this.textContent=\'Copy URL\', 2000);" style="margin-left: 10px;">Copy URL</button>';
                        echo '</div>';
                        echo '<p class="description" style="color: #6c757d;">' . esc_html__('Add this as a "Webhook" action step in your Zap to send data back to this site.', 'multi-platform-sync') . '</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="mps-card">';
                        echo '<p style="margin-bottom: 20px; color: #495057; line-height: 1.6;">' . esc_html__('Configure your Zapier webhook URL to send form data from Gravity Forms to external platforms.', 'multi-platform-sync') . '</p>';
                        
                        // Render the settings fields
                        do_settings_sections('multi-platform-sync-settings');
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <!-- Gravity Forms Settings -->
                <div id="gravity-forms-settings" class="mps-tab-content">
                    <h2 style="margin-top: 0; color: #2c3e50; display: flex; align-items: center;">
                        <span class="dashicons dashicons-feedback" style="margin-right: 10px; color: #667eea;"></span>
                        <?php esc_html_e('Gravity Forms Settings', 'multi-platform-sync'); ?>
                    </h2>
                    
                    <div class="mps-card">
                        <?php
                        // Check if Gravity Forms Zapier add-on is detected
                        if (get_option('mps_gf_zapier_addon_detected', false)) {
                            echo '<div class="notice notice-info inline" style="margin-bottom: 20px; padding: 15px; border-left: 4px solid #0073aa; background: #f0f6fc;">';
                            echo '<p>' . esc_html__('Form selection is not needed when using the Gravity Forms Zapier Add-on. The add-on handles which forms send data to Zapier.', 'multi-platform-sync') . '</p>';
                            echo '</div>';
                        } else {
                            echo '<p style="margin-bottom: 20px; color: #495057; line-height: 1.6;">' . esc_html__('Select which Gravity Forms should trigger data synchronization with external platforms.', 'multi-platform-sync') . '</p>';
                            echo '<div class="notice notice-info inline" style="margin-bottom: 20px; padding: 15px; border-left: 4px solid #0073aa; background: #f0f6fc;">';
                            echo '<p><strong>' . esc_html__('Note:', 'multi-platform-sync') . '</strong> ';
                            echo esc_html__('This selection is only needed if you want to send Gravity Forms data directly to Zapier. If you\'re receiving data from external sources through Zapier, you don\'t need to select any forms here.', 'multi-platform-sync') . '</p>';
                            echo '</div>';
                        }
                        
                        // Render the Gravity Forms selection
                        $this->gravity_forms_to_sync_render();
                        ?>
                    </div>
                </div>
                
                <!-- Campaign Monitor Settings -->
                <div id="campaign-monitor-settings" class="mps-tab-content">
                    <h2 style="margin-top: 0; color: #2c3e50; display: flex; align-items: center;">
                        <span class="dashicons dashicons-email" style="margin-right: 10px; color: #667eea;"></span>
                        <?php esc_html_e('Campaign Monitor Settings', 'multi-platform-sync'); ?>
                    </h2>
                    
                    <div class="mps-card">
                        <p style="margin-bottom: 25px; color: #495057; line-height: 1.6;">
                            <?php esc_html_e('Configure your Campaign Monitor integration to automatically add subscribers to your email lists.', 'multi-platform-sync'); ?>
                        </p>
                        
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_campaign_monitor_api_key" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('API Key', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $api_key = get_option('mps_campaign_monitor_api_key', '');
                                        ?>
                                        <input type="password" id="mps_campaign_monitor_api_key" name="mps_campaign_monitor_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                                        <button type="button" class="button mps-test-connection" data-platform="campaign_monitor" style="margin-left: 10px;">
                                            <span class="dashicons dashicons-admin-tools" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>
                                            <?php esc_html_e('Test Connection', 'multi-platform-sync'); ?>
                                        </button>
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Enter your Campaign Monitor API key. You can find this in your Campaign Monitor account settings.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_campaign_monitor_list_id" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('List ID', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $list_id = get_option('mps_campaign_monitor_list_id', '');
                                        ?>
                                        <input type="text" id="mps_campaign_monitor_list_id" name="mps_campaign_monitor_list_id" value="<?php echo esc_attr($list_id); ?>" class="regular-text" />
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Enter your Campaign Monitor List ID where subscribers will be added.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Quickbase Settings -->
                <div id="quickbase-settings" class="mps-tab-content">
                    <h2 style="margin-top: 0; color: #2c3e50; display: flex; align-items: center;">
                        <span class="dashicons dashicons-database" style="margin-right: 10px; color: #667eea;"></span>
                        <?php esc_html_e('Quickbase Settings', 'multi-platform-sync'); ?>
                    </h2>
                    
                    <div class="mps-card">
                        <p style="margin-bottom: 25px; color: #495057; line-height: 1.6;">
                            <?php esc_html_e('Configure your Quickbase integration to automatically create records in your database.', 'multi-platform-sync'); ?>
                        </p>
                        
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_quickbase_realm_hostname" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('Realm Hostname', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $hostname = get_option('mps_quickbase_realm_hostname', '');
                                        ?>
                                        <input type="text" id="mps_quickbase_realm_hostname" name="mps_quickbase_realm_hostname" value="<?php echo esc_attr($hostname); ?>" class="regular-text" placeholder="yourrealm.quickbase.com" />
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Enter your Quickbase realm hostname (e.g., yourrealm.quickbase.com).', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_quickbase_user_token" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('User Token', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $user_token = get_option('mps_quickbase_user_token', '');
                                        ?>
                                        <input type="password" id="mps_quickbase_user_token" name="mps_quickbase_user_token" value="<?php echo esc_attr($user_token); ?>" class="regular-text" />
                                        <button type="button" class="button mps-test-connection" data-platform="quickbase" style="margin-left: 10px;">
                                            <span class="dashicons dashicons-admin-tools" style="margin-right: 5px; font-size: 14px; line-height: 1;"></span>
                                            <?php esc_html_e('Test Connection', 'multi-platform-sync'); ?>
                                        </button>
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Enter your Quickbase user token for API authentication.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_quickbase_app_id" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('App ID', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $app_id = get_option('mps_quickbase_app_id', '');
                                        ?>
                                        <input type="text" id="mps_quickbase_app_id" name="mps_quickbase_app_id" value="<?php echo esc_attr($app_id); ?>" class="regular-text" />
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Enter your Quickbase application ID.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_quickbase_table_id" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('Table ID', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $table_id = get_option('mps_quickbase_table_id', '');
                                        ?>
                                        <input type="text" id="mps_quickbase_table_id" name="mps_quickbase_table_id" value="<?php echo esc_attr($table_id); ?>" class="regular-text" />
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Enter your Quickbase table ID where records will be created.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Rate Limiting Settings -->
                <div id="rate-limiting-settings" class="mps-tab-content">
                    <h2 style="margin-top: 0; color: #2c3e50; display: flex; align-items: center;">
                        <span class="dashicons dashicons-performance" style="margin-right: 10px; color: #667eea;"></span>
                        <?php esc_html_e('API Rate Limiting Settings', 'multi-platform-sync'); ?>
                    </h2>
                    
                    <div class="mps-card">
                        <p style="margin-bottom: 25px; color: #495057; line-height: 1.6;">
                            <?php esc_html_e('Configure API rate limiting to prevent hitting API limits and improve reliability.', 'multi-platform-sync'); ?>
                        </p>
                        
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_rate_limit_enabled" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('Enable Rate Limiting', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $enabled = get_option('mps_rate_limit_enabled', 1);
                                        ?>
                                        <label style="display: flex; align-items: center;">
                                            <input type="checkbox" name="mps_rate_limit_enabled" value="1" <?php checked(1, $enabled); ?> style="margin-right: 10px;" />
                                            <span style="font-weight: 500;"><?php esc_html_e('Enable API rate limiting', 'multi-platform-sync'); ?></span>
                                        </label>
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Recommended to prevent hitting API rate limits and ensure reliable operation.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_rate_limit_max_requests" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('Max Requests', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $max_requests = get_option('mps_rate_limit_max_requests', 10);
                                        ?>
                                        <input type="number" min="1" max="100" id="mps_rate_limit_max_requests" name="mps_rate_limit_max_requests" value="<?php echo esc_attr($max_requests); ?>" class="small-text" />
                                        <span style="margin-left: 10px; color: #6c757d;"><?php esc_html_e('requests per period', 'multi-platform-sync'); ?></span>
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Maximum number of API requests allowed within the time period.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_rate_limit_period" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('Time Period', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $period = get_option('mps_rate_limit_period', 60);
                                        ?>
                                        <input type="number" min="1" max="3600" id="mps_rate_limit_period" name="mps_rate_limit_period" value="<?php echo esc_attr($period); ?>" class="small-text" />
                                        <span style="margin-left: 10px; color: #6c757d;"><?php esc_html_e('seconds', 'multi-platform-sync'); ?></span>
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Time period in seconds for rate limiting (60 = 1 minute, 3600 = 1 hour).', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Advanced Settings -->
                <div id="advanced-settings" class="mps-tab-content">
                    <h2 style="margin-top: 0; color: #2c3e50; display: flex; align-items: center;">
                        <span class="dashicons dashicons-admin-tools" style="margin-right: 10px; color: #667eea;"></span>
                        <?php esc_html_e('Advanced Settings', 'multi-platform-sync'); ?>
                    </h2>
                    
                    <div class="mps-card">
                        <p style="margin-bottom: 25px; color: #495057; line-height: 1.6;">
                            <?php esc_html_e('Advanced configuration options for enhanced functionality and performance.', 'multi-platform-sync'); ?>
                        </p>
                        
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_enable_queue_processing" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('Queue Processing', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $enabled = get_option('mps_enable_queue_processing', 1);
                                        ?>
                                        <label style="display: flex; align-items: center;">
                                            <input type="checkbox" name="mps_enable_queue_processing" value="1" <?php checked(1, $enabled); ?> style="margin-right: 10px;" />
                                            <span style="font-weight: 500;"><?php esc_html_e('Enable background queue processing', 'multi-platform-sync'); ?></span>
                                        </label>
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Process syncs in the background for better performance and reliability. Recommended for high-volume sites.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_enable_field_mapping" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('Field Mapping', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $enabled = get_option('mps_enable_field_mapping', 1);
                                        ?>
                                        <label style="display: flex; align-items: center;">
                                            <input type="checkbox" name="mps_enable_field_mapping" value="1" <?php checked(1, $enabled); ?> style="margin-right: 10px;" />
                                            <span style="font-weight: 500;"><?php esc_html_e('Enable intelligent field mapping', 'multi-platform-sync'); ?></span>
                                        </label>
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Automatically detect and map form fields to standard field types for better data consistency.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="mps_log_retention_days" style="font-weight: 600; color: #2c3e50;">
                                            <?php esc_html_e('Log Retention', 'multi-platform-sync'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php
                                        $days = get_option('mps_log_retention_days', 30);
                                        ?>
                                        <input type="number" min="1" max="365" id="mps_log_retention_days" name="mps_log_retention_days" value="<?php echo esc_attr($days); ?>" class="small-text" />
                                        <span style="margin-left: 10px; color: #6c757d;"><?php esc_html_e('days', 'multi-platform-sync'); ?></span>
                                        <p class="description" style="margin-top: 8px;">
                                            <?php esc_html_e('Number of days to keep sync logs. Older logs will be automatically deleted to save database space.', 'multi-platform-sync'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* Additional styles for better form presentation */
.form-table th {
    width: 200px;
    padding: 20px 10px 20px 0;
    vertical-align: top;
}

.form-table td {
    padding: 15px 10px;
    vertical-align: top;
}

.form-table input[type="text"],
.form-table input[type="password"],
.form-table input[type="number"],
.form-table select {
    width: 100%;
    max-width: 400px;
}

.form-table .small-text {
    width: 80px;
}

.form-table .description {
    max-width: 500px;
}

/* Enhanced checkbox styling */
input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #667eea;
}

/* Button group styling */
.mps-test-connection {
    white-space: nowrap;
}

/* Responsive form adjustments */
@media (max-width: 768px) {
    .form-table th,
    .form-table td {
        display: block;
        width: 100%;
        padding: 10px 0;
    }
    
    .form-table th {
        border-bottom: none;
        padding-bottom: 5px;
    }
    
    .form-table td {
        padding-top: 5px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e1e5e9;
    }
    
    .mps-test-connection {
        margin-left: 0 !important;
        margin-top: 10px;
        display: block;
        width: auto;
    }
}
</style>

<script>
// Enhanced tab functionality with better UX
jQuery(document).ready(function($) {
    // Tab switching with smooth transitions
    $('.mps-tab-link').on('click', function(e) {
        e.preventDefault();
        
        var targetId = $(this).attr('href');
        
        // Update active states
        $('.mps-tab-content').removeClass('active');
        $('.mps-tab-link').removeClass('active');
        
        $(targetId).addClass('active');
        $(this).addClass('active');
        
        // Smooth scroll to content on mobile
        if (window.innerWidth <= 768) {
            $('html, body').animate({
                scrollTop: $('.mps-settings-content').offset().top - 20
            }, 300);
        }
        
        // Save active tab in localStorage
        localStorage.setItem('mps_active_tab', targetId);
    });
    
    // Restore active tab from localStorage
    var activeTab = localStorage.getItem('mps_active_tab');
    if (activeTab && $(activeTab).length) {
        $('.mps-tab-link[href="' + activeTab + '"]').click();
    }
    
    // Form validation feedback
    $('input[required]').on('blur', function() {
        if (!$(this).val().trim()) {
            $(this).css('border-color', '#dc3545');
        } else {
            $(this).css('border-color', '#28a745');
        }
    });
    
    // Auto-save indication
    var saveTimeout;
    $('input, select, textarea').on('change', function() {
        clearTimeout(saveTimeout);
        $('.mps-dashboard-actions').append('<span class="mps-save-indicator" style="margin-left: 10px; color: #ffc107;">Unsaved changes</span>');
        
        saveTimeout = setTimeout(function() {
            $('.mps-save-indicator').remove();
        }, 5000);
    });
    
    // Remove save indicator on form submit
    $('#mps-settings-form').on('submit', function() {
        $('.mps-save-indicator').remove();
    });
});
</script>