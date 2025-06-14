<?php
/**
 * Data transformation functionality for the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.1.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

/**
 * Data transformation functionality for the plugin.
 *
 * Handles data transformation and formatting for different platforms.
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 * @author     Eric Mutema <eric@righthereinteractive.com>
 */
class Multi_Platform_Sync_Data_Transformer {

    /**
     * Transform data for Campaign Monitor.
     *
     * @since    1.1.0
     * @param    array    $data    The data to transform.
     * @return   array    The transformed data.
     */
    public static function transform_for_campaign_monitor($data) {
        $mapped_data = Multi_Platform_Sync_Field_Mapper::map_fields($data);
        
        $subscriber_data = array();

        // Required email field
        if (isset($mapped_data['email'])) {
            $subscriber_data['EmailAddress'] = $mapped_data['email'];
        } else {
            throw new Exception(__('Email address is required for Campaign Monitor.', 'multi-platform-sync'));
        }

        // Name field
        if (isset($mapped_data['full_name'])) {
            $subscriber_data['Name'] = $mapped_data['full_name'];
        } elseif (isset($mapped_data['first_name']) && isset($mapped_data['last_name'])) {
            $subscriber_data['Name'] = trim($mapped_data['first_name'] . ' ' . $mapped_data['last_name']);
        } elseif (isset($mapped_data['first_name'])) {
            $subscriber_data['Name'] = $mapped_data['first_name'];
        }

        // Custom fields
        $custom_fields = array();
        $excluded_fields = array('email', 'full_name', 'first_name', 'last_name', '_mapped_fields', '_original_fields');

        foreach ($mapped_data as $key => $value) {
            if (in_array($key, $excluded_fields) || empty($value)) {
                continue;
            }

            // Format field name for Campaign Monitor
            $field_name = self::format_field_name_for_campaign_monitor($key);
            
            if (!empty($field_name)) {
                $custom_fields[] = array(
                    'Key' => $field_name,
                    'Value' => self::format_field_value($value)
                );
            }
        }

        if (!empty($custom_fields)) {
            $subscriber_data['CustomFields'] = $custom_fields;
        }

        // Set consent fields if available
        $subscriber_data['ConsentToTrack'] = 'Yes';
        $subscriber_data['Resubscribe'] = true;

        return $subscriber_data;
    }

    /**
     * Transform data for Quickbase.
     *
     * @since    1.1.0
     * @param    array    $data    The data to transform.
     * @return   array    The transformed data.
     */
    public static function transform_for_quickbase($data) {
        $mapped_data = Multi_Platform_Sync_Field_Mapper::map_fields($data);
        
        $record_data = array();

        // Process all mapped fields
        foreach ($mapped_data as $key => $value) {
            if (strpos($key, '_') === 0 || empty($value)) {
                continue; // Skip metadata fields and empty values
            }

            $field_name = self::format_field_name_for_quickbase($key);
            
            if (!empty($field_name)) {
                $record_data[$field_name] = array(
                    'value' => self::format_field_value_for_quickbase($value, $key)
                );
            }
        }

        // Add metadata fields
        if (isset($data['form_id'])) {
            $record_data['Source_Form_ID'] = array('value' => intval($data['form_id']));
        }

        if (isset($data['entry_id'])) {
            $record_data['Source_Entry_ID'] = array('value' => sanitize_text_field($data['entry_id']));
        }

        if (isset($data['date_created'])) {
            $record_data['Submission_Date'] = array('value' => self::format_date_for_quickbase($data['date_created']));
        }

        // Add sync timestamp
        $record_data['Sync_Timestamp'] = array('value' => current_time('mysql'));

        return $record_data;
    }

    /**
     * Format field name for Campaign Monitor.
     *
     * @since    1.1.0
     * @param    string    $field_name    The original field name.
     * @return   string    The formatted field name.
     */
    private static function format_field_name_for_campaign_monitor($field_name) {
        // Campaign Monitor allows alphanumeric characters, spaces, and some special characters
        $formatted = preg_replace('/[^a-zA-Z0-9\s\-_.]/', '', $field_name);
        $formatted = trim($formatted);
        
        // Convert to title case
        $formatted = ucwords(str_replace(array('-', '_'), ' ', $formatted));
        
        // Limit length
        return substr($formatted, 0, 200);
    }

    /**
     * Format field name for Quickbase.
     *
     * @since    1.1.0
     * @param    string    $field_name    The original field name.
     * @return   string    The formatted field name.
     */
    private static function format_field_name_for_quickbase($field_name) {
        // Quickbase prefers underscores and no spaces
        $formatted = preg_replace('/[^a-zA-Z0-9_]/', '_', $field_name);
        $formatted = preg_replace('/_+/', '_', $formatted); // Remove multiple underscores
        $formatted = trim($formatted, '_');
        
        // Ensure it starts with a letter
        if (!preg_match('/^[a-zA-Z]/', $formatted)) {
            $formatted = 'Field_' . $formatted;
        }
        
        // Convert to title case with underscores
        $parts = explode('_', $formatted);
        $parts = array_map('ucfirst', $parts);
        $formatted = implode('_', $parts);
        
        // Limit length
        return substr($formatted, 0, 255);
    }

    /**
     * Format field value for general use.
     *
     * @since    1.1.0
     * @param    mixed    $value    The value to format.
     * @return   string   The formatted value.
     */
    private static function format_field_value($value) {
        if (is_array($value)) {
            return implode(', ', array_map('sanitize_text_field', $value));
        }
        
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        
        return sanitize_text_field(strval($value));
    }

    /**
     * Format field value for Quickbase.
     *
     * @since    1.1.0
     * @param    mixed     $value      The value to format.
     * @param    string    $field_type The field type for context.
     * @return   mixed     The formatted value.
     */
    private static function format_field_value_for_quickbase($value, $field_type = '') {
        if (is_array($value)) {
            return implode('; ', array_map('sanitize_text_field', $value));
        }
        
        if (is_bool($value)) {
            return $value;
        }
        
        // Handle numeric values
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        // Handle date fields
        if ($field_type === 'date' || strpos($field_type, 'date') !== false) {
            return self::format_date_for_quickbase($value);
        }
        
        return sanitize_text_field(strval($value));
    }

    /**
     * Format date for Quickbase.
     *
     * @since    1.1.0
     * @param    mixed    $date    The date to format.
     * @return   string   The formatted date.
     */
    private static function format_date_for_quickbase($date) {
        if (empty($date)) {
            return '';
        }
        
        // Try to parse the date
        $timestamp = is_numeric($date) ? intval($date) : strtotime($date);
        
        if ($timestamp === false) {
            return sanitize_text_field(strval($date));
        }
        
        // Return in ISO 8601 format
        return date('c', $timestamp);
    }

    /**
     * Transform data for Zapier webhook.
     *
     * @since    1.1.0
     * @param    array    $data    The data to transform.
     * @return   array    The transformed data.
     */
    public static function transform_for_zapier($data) {
        $mapped_data = Multi_Platform_Sync_Field_Mapper::map_fields($data);
        
        // Create a clean structure for Zapier
        $zapier_data = array();
        
        // Add mapped fields with clean names
        foreach ($mapped_data as $key => $value) {
            if (strpos($key, '_') === 0) {
                continue; // Skip metadata fields
            }
            
            $clean_key = self::format_field_name_for_zapier($key);
            $zapier_data[$clean_key] = self::format_field_value($value);
        }
        
        // Add metadata
        $zapier_data['_metadata'] = array(
            'sync_timestamp' => current_time('mysql'),
            'plugin_version' => MPS_VERSION,
            'mapped_fields' => isset($mapped_data['_mapped_fields']) ? $mapped_data['_mapped_fields'] : array()
        );
        
        return $zapier_data;
    }

    /**
     * Format field name for Zapier.
     *
     * @since    1.1.0
     * @param    string    $field_name    The original field name.
     * @return   string    The formatted field name.
     */
    private static function format_field_name_for_zapier($field_name) {
        // Convert to snake_case for consistency
        $formatted = strtolower($field_name);
        $formatted = preg_replace('/[^a-z0-9_]/', '_', $formatted);
        $formatted = preg_replace('/_+/', '_', $formatted);
        $formatted = trim($formatted, '_');
        
        return $formatted;
    }

    /**
     * Validate transformed data.
     *
     * @since    1.1.0
     * @param    array     $data        The transformed data.
     * @param    string    $platform    The target platform.
     * @return   array     Validation result with status and messages.
     */
    public static function validate_transformed_data($data, $platform) {
        $result = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array()
        );

        switch ($platform) {
            case 'campaign_monitor':
                if (!isset($data['EmailAddress']) || !is_email($data['EmailAddress'])) {
                    $result['valid'] = false;
                    $result['errors'][] = __('Valid email address is required for Campaign Monitor.', 'multi-platform-sync');
                }
                break;

            case 'quickbase':
                if (empty($data)) {
                    $result['valid'] = false;
                    $result['errors'][] = __('No data provided for Quickbase.', 'multi-platform-sync');
                }
                break;

            case 'zapier':
                if (empty($data)) {
                    $result['valid'] = false;
                    $result['errors'][] = __('No data provided for Zapier.', 'multi-platform-sync');
                }
                break;
        }

        return $result;
    }
}