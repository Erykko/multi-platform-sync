<?php
/**
 * Field mapping functionality for the plugin.
 *
 * @link       https://righthereinteractive.com
 * @since      1.1.0
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 */

/**
 * Field mapping functionality for the plugin.
 *
 * Handles intelligent field mapping between different platforms.
 *
 * @package    Multi_Platform_Sync
 * @subpackage Multi_Platform_Sync/includes
 * @author     Eric Mutema <eric@righthereinteractive.com>
 */
class Multi_Platform_Sync_Field_Mapper {

    /**
     * Common field mappings for different platforms.
     *
     * @since    1.1.0
     * @access   private
     * @var      array    $field_mappings    Field mapping configurations.
     */
    private static $field_mappings = array(
        'email' => array(
            'patterns' => array('email', 'e-mail', 'email_address', 'emailaddress', 'mail'),
            'validation' => 'email'
        ),
        'first_name' => array(
            'patterns' => array('first_name', 'firstname', 'first name', 'fname', 'given_name'),
            'validation' => 'text'
        ),
        'last_name' => array(
            'patterns' => array('last_name', 'lastname', 'last name', 'lname', 'surname', 'family_name'),
            'validation' => 'text'
        ),
        'full_name' => array(
            'patterns' => array('name', 'full_name', 'fullname', 'full name', 'display_name'),
            'validation' => 'text'
        ),
        'phone' => array(
            'patterns' => array('phone', 'telephone', 'mobile', 'cell', 'phone_number'),
            'validation' => 'phone'
        ),
        'company' => array(
            'patterns' => array('company', 'organization', 'business', 'employer'),
            'validation' => 'text'
        ),
        'address' => array(
            'patterns' => array('address', 'street', 'address_line_1', 'street_address'),
            'validation' => 'text'
        ),
        'city' => array(
            'patterns' => array('city', 'town', 'locality'),
            'validation' => 'text'
        ),
        'state' => array(
            'patterns' => array('state', 'province', 'region', 'administrative_area'),
            'validation' => 'text'
        ),
        'zip' => array(
            'patterns' => array('zip', 'postal_code', 'postcode', 'zip_code'),
            'validation' => 'text'
        ),
        'country' => array(
            'patterns' => array('country', 'nation'),
            'validation' => 'text'
        )
    );

    /**
     * Map form fields to standardized field types.
     *
     * @since    1.1.0
     * @param    array    $data    The form data to map.
     * @return   array    The mapped data.
     */
    public static function map_fields($data) {
        if (!is_array($data)) {
            return array();
        }

        $mapped_data = array();
        $original_data = array();

        // Process both direct fields and nested fields array
        $fields_to_process = array();
        
        // Add direct fields
        foreach ($data as $key => $value) {
            if ($key !== 'fields' && !is_array($value)) {
                $fields_to_process[$key] = $value;
            }
        }

        // Add nested fields
        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $field_id => $field_data) {
                if (isset($field_data['label']) && isset($field_data['value'])) {
                    $fields_to_process[$field_data['label']] = $field_data['value'];
                }
            }
        }

        // Map each field
        foreach ($fields_to_process as $field_name => $field_value) {
            $mapped_type = self::detect_field_type($field_name, $field_value);
            
            if ($mapped_type) {
                // Validate and sanitize the value
                $sanitized_value = self::sanitize_field_value($field_value, $mapped_type);
                
                if ($sanitized_value !== false) {
                    $mapped_data[$mapped_type] = $sanitized_value;
                }
            }

            // Keep original data as well
            $original_data[sanitize_key($field_name)] = $field_value;
        }

        // Merge mapped and original data
        $result = array_merge($original_data, $mapped_data);

        // Add metadata
        $result['_mapped_fields'] = array_keys($mapped_data);
        $result['_original_fields'] = array_keys($original_data);

        return $result;
    }

    /**
     * Detect field type based on field name and value.
     *
     * @since    1.1.0
     * @param    string    $field_name     The field name.
     * @param    mixed     $field_value    The field value.
     * @return   string|false    The detected field type or false if not detected.
     */
    private static function detect_field_type($field_name, $field_value) {
        $field_name_lower = strtolower(trim($field_name));
        
        // First, try pattern matching on field name
        foreach (self::$field_mappings as $type => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (strpos($field_name_lower, strtolower($pattern)) !== false) {
                    // Validate that the value matches the expected type
                    if (self::validate_field_value($field_value, $config['validation'])) {
                        return $type;
                    }
                }
            }
        }

        // If no pattern match, try value-based detection
        if (is_email($field_value)) {
            return 'email';
        }

        if (self::is_phone_number($field_value)) {
            return 'phone';
        }

        return false;
    }

    /**
     * Validate field value against expected type.
     *
     * @since    1.1.0
     * @param    mixed     $value    The value to validate.
     * @param    string    $type     The validation type.
     * @return   bool      True if valid, false otherwise.
     */
    private static function validate_field_value($value, $type) {
        if (empty($value) && $value !== '0') {
            return false;
        }

        switch ($type) {
            case 'email':
                return is_email($value);
            
            case 'phone':
                return self::is_phone_number($value);
            
            case 'text':
                return is_string($value) || is_numeric($value);
            
            default:
                return true;
        }
    }

    /**
     * Sanitize field value based on type.
     *
     * @since    1.1.0
     * @param    mixed     $value    The value to sanitize.
     * @param    string    $type     The field type.
     * @return   mixed     The sanitized value or false if invalid.
     */
    private static function sanitize_field_value($value, $type) {
        switch ($type) {
            case 'email':
                $sanitized = sanitize_email($value);
                return is_email($sanitized) ? $sanitized : false;
            
            case 'phone':
                // Remove non-numeric characters except + and spaces
                $sanitized = preg_replace('/[^0-9+\s()-]/', '', $value);
                return !empty($sanitized) ? $sanitized : false;
            
            case 'first_name':
            case 'last_name':
            case 'full_name':
            case 'company':
            case 'address':
            case 'city':
            case 'state':
            case 'zip':
            case 'country':
                return sanitize_text_field($value);
            
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Check if a value looks like a phone number.
     *
     * @since    1.1.0
     * @param    mixed    $value    The value to check.
     * @return   bool     True if it looks like a phone number.
     */
    private static function is_phone_number($value) {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $value = strval($value);
        
        // Remove common phone number formatting
        $cleaned = preg_replace('/[^0-9]/', '', $value);
        
        // Check if it has a reasonable length for a phone number
        $length = strlen($cleaned);
        
        return $length >= 7 && $length <= 15;
    }

    /**
     * Get field mapping suggestions for a form.
     *
     * @since    1.1.0
     * @param    array    $form_fields    The form fields to analyze.
     * @return   array    Mapping suggestions.
     */
    public static function get_mapping_suggestions($form_fields) {
        $suggestions = array();

        if (!is_array($form_fields)) {
            return $suggestions;
        }

        foreach ($form_fields as $field_id => $field_data) {
            $field_name = isset($field_data['label']) ? $field_data['label'] : $field_id;
            $field_value = isset($field_data['value']) ? $field_data['value'] : '';

            $detected_type = self::detect_field_type($field_name, $field_value);
            
            if ($detected_type) {
                $suggestions[$field_id] = array(
                    'original_name' => $field_name,
                    'suggested_type' => $detected_type,
                    'confidence' => self::calculate_confidence($field_name, $field_value, $detected_type)
                );
            }
        }

        // Sort by confidence
        uasort($suggestions, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $suggestions;
    }

    /**
     * Calculate confidence score for field mapping.
     *
     * @since    1.1.0
     * @param    string    $field_name     The field name.
     * @param    mixed     $field_value    The field value.
     * @param    string    $detected_type  The detected type.
     * @return   float     Confidence score between 0 and 1.
     */
    private static function calculate_confidence($field_name, $field_value, $detected_type) {
        $confidence = 0.5; // Base confidence

        if (!isset(self::$field_mappings[$detected_type])) {
            return $confidence;
        }

        $config = self::$field_mappings[$detected_type];
        $field_name_lower = strtolower(trim($field_name));

        // Check for exact pattern matches
        foreach ($config['patterns'] as $pattern) {
            if ($field_name_lower === strtolower($pattern)) {
                $confidence += 0.4; // High confidence for exact match
                break;
            } elseif (strpos($field_name_lower, strtolower($pattern)) !== false) {
                $confidence += 0.2; // Medium confidence for partial match
            }
        }

        // Boost confidence if value validation passes strongly
        if (self::validate_field_value($field_value, $config['validation'])) {
            $confidence += 0.1;
        }

        return min(1.0, $confidence);
    }
}