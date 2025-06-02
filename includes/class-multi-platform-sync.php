<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

class Multi_Platform_Sync {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Multi_Platform_Sync_Loader    $loader    Maintains and registers all hooks.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = MPS_VERSION;
        $this->plugin_name = 'multi-platform-sync';
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_gravity_forms_hooks();
        
        // Add rate limiting options if they don't exist
        if (false === get_option('mps_rate_limit_enabled')) {
            add_option('mps_rate_limit_enabled', true);
        }
        
        if (false === get_option('mps_rate_limit_max_requests')) {
            add_option('mps_rate_limit_max_requests', 10);
        }
        
        if (false === get_option('mps_rate_limit_period')) {
            add_option('mps_rate_limit_period', 60); // 60 seconds
        }
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters of the core plugin.
        require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-loader.php';
        
        // The class responsible for defining all admin-specific functionality
        require_once MPS_PLUGIN_DIR . 'includes/admin/class-multi-platform-sync-admin.php';
        
        // Load the rate limiter class
        require_once MPS_PLUGIN_DIR . 'includes/class-multi-platform-sync-rate-limiter.php';
        
        // Integration classes
        require_once MPS_PLUGIN_DIR . 'includes/integrations/zapier/class-multi-platform-sync-zapier.php';
        require_once MPS_PLUGIN_DIR . 'includes/integrations/campaign-monitor/class-multi-platform-sync-campaign-monitor.php';
        require_once MPS_PLUGIN_DIR . 'includes/integrations/quickbase/class-multi-platform-sync-quickbase.php';
        
        $this->loader = new Multi_Platform_Sync_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    1.0.0
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
    }

    /**
     * Register all of the hooks related to Gravity Forms.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_gravity_forms_hooks() {
        // Initialize integrations
        $zapier = new Multi_Platform_Sync_Zapier($this->get_plugin_name(), $this->get_version());
        $campaign_monitor = new Multi_Platform_Sync_Campaign_Monitor($this->get_plugin_name(), $this->get_version());
        $quickbase = new Multi_Platform_Sync_Quickbase($this->get_plugin_name(), $this->get_version());
        
        // Register Gravity Forms hooks for form submission
        $this->loader->add_action('gform_after_submission', $zapier, 'process_form_submission', 10, 2);
        
        // Manual sync action hook for admin-triggered syncs
        $this->loader->add_action('mps_manual_sync', $zapier, 'process_manual_sync', 10, 1);
        $this->loader->add_action('mps_zapier_webhook_response', $campaign_monitor, 'process_zapier_data', 10, 1);
        $this->loader->add_action('mps_zapier_webhook_response', $quickbase, 'process_zapier_data', 10, 1);
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Multi_Platform_Sync_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
} 