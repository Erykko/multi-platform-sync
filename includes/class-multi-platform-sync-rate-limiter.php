<?php
/**
 * Rate limiter functionality of the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.0.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

/**
 * Rate limiter functionality of the plugin.
 *
 * This class defines methods to manage API rate limiting.
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 * @author     Eric Mutema <eric@righthereinteractive.com>
 */
class Multi_Platform_Sync_Rate_Limiter {

    /**
     * Check if the API request can be made based on rate limits.
     *
     * @since    1.0.0
     * @param    string    $api_name    The name of the API to check.
     * @return   bool      True if the request can be made, false otherwise.
     */
    public static function can_make_request($api_name) {
        // Check if rate limiting is enabled
        if (!get_option('mps_rate_limit_enabled', true)) {
            return true; // Rate limiting disabled, always allow
        }
        
        // Get rate limit settings
        $max_requests = get_option('mps_rate_limit_max_requests', 10);
        $period = get_option('mps_rate_limit_period', 60); // In seconds
        
        // Generate a unique key for this API
        $transient_key = 'mps_rate_limit_' . sanitize_key($api_name);
        
        // Get current counter
        $counter = get_transient($transient_key);
        
        // If counter doesn't exist, initialize it
        if (false === $counter) {
            $counter = array(
                'count' => 0,
                'started_at' => time()
            );
        }
        
        // Check if the period has elapsed and reset if needed
        if (time() - $counter['started_at'] > $period) {
            $counter = array(
                'count' => 0,
                'started_at' => time()
            );
        }
        
        // Check if we've reached the limit
        if ($counter['count'] >= $max_requests) {
            // Calculate time remaining until rate limit resets
            $time_remaining = ($counter['started_at'] + $period) - time();
            
            // Log rate limit hit
            self::log_rate_limit_hit($api_name, $time_remaining);
            
            return false; // Rate limit exceeded
        }
        
        // Increment counter and update transient
        $counter['count']++;
        set_transient($transient_key, $counter, $period + 10); // Add a little buffer
        
        return true; // Request allowed
    }
    
    /**
     * Log a rate limit hit.
     *
     * @since    1.0.0
     * @param    string    $api_name        The name of the API.
     * @param    int       $time_remaining  The time remaining until rate limit resets.
     */
    private static function log_rate_limit_hit($api_name, $time_remaining) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mps_sync_logs';
        
        $message = sprintf(
            __('Rate limit exceeded for %s API. Limit will reset in %d seconds.', 'multi-platform-sync'),
            $api_name,
            $time_remaining
        );
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => current_time('mysql'),
                'sync_type' => 'rate_limit',
                'status' => 'error',
                'message' => $message
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );
    }
    
    /**
     * Get time remaining until rate limit resets.
     *
     * @since    1.0.0
     * @param    string    $api_name    The name of the API to check.
     * @return   int       The time remaining in seconds, or 0 if no rate limit.
     */
    public static function get_time_remaining($api_name) {
        // Check if rate limiting is enabled
        if (!get_option('mps_rate_limit_enabled', true)) {
            return 0; // Rate limiting disabled, no waiting time
        }
        
        // Get rate limit settings
        $period = get_option('mps_rate_limit_period', 60); // In seconds
        
        // Generate a unique key for this API
        $transient_key = 'mps_rate_limit_' . sanitize_key($api_name);
        
        // Get current counter
        $counter = get_transient($transient_key);
        
        // If counter doesn't exist, no waiting time
        if (false === $counter) {
            return 0;
        }
        
        // Calculate time remaining
        $elapsed = time() - $counter['started_at'];
        if ($elapsed >= $period) {
            return 0; // Period has passed, no waiting time
        }
        
        return $period - $elapsed;
    }
} 