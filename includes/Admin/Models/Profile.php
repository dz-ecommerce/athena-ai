<?php
/**
 * Handles profile data operations
 */
namespace AthenaAI\Admin\Models;

class Profile {
    /**
     * @var string The option name for storing profile data
     */
    private $option_name = 'athena_ai_profiles';
    
    /**
     * Field type configuration for sanitization
     */
    private const FIELD_TYPES = [
        'company_name' => 'text',
        'company_industry' => 'text', 
        'company_description' => 'textarea',
        'company_products' => 'textarea',
        'company_usps' => 'textarea',
        'target_audience' => 'textarea',
        'age_group' => 'array',
        'company_values' => 'textarea',
        'expertise_areas' => 'textarea',
        'certifications' => 'textarea',
        'seo_keywords' => 'textarea',
        'avoided_topics' => 'textarea',
        'customer_type' => 'text',
        'preferred_tone' => 'text',
        'tonality' => 'array'
    ];

    /**
     * Get profile data
     * 
     * @return array Profile data
     */
    public function getProfileData() {
        return get_option($this->option_name, []);
    }

    /**
     * Save profile data
     * 
     * @param array $data Profile data to save
     * @return bool True on success, false on failure
     */
    public function saveProfileData($data) {
        if (!is_array($data)) {
            return false;
        }

        $sanitized_data = $this->sanitizeProfileData($data);
        return update_option($this->option_name, $sanitized_data);
    }

    /**
     * Sanitize profile data based on field types
     * 
     * @param array $data Raw profile data
     * @return array Sanitized profile data
     */
    private function sanitizeProfileData($data) {
        $sanitized = [];
        
        if (!is_array($data)) {
            return $sanitized;
        }

        foreach (self::FIELD_TYPES as $field => $type) {
            if (!isset($data[$field])) continue;
            
            $sanitized[$field] = match($type) {
                'text' => sanitize_text_field($data[$field]),
                'textarea' => sanitize_textarea_field($data[$field]),
                'array' => is_array($data[$field]) ? array_map('sanitize_text_field', $data[$field]) : []
            };
        }

        return $sanitized;
    }

    /**
     * Get a specific profile field
     * 
     * @param string $field Field name
     * @param mixed $default Default value if field doesn't exist
     * @return mixed Field value or default
     */
    public function getProfileField($field, $default = '') {
        $profile_data = $this->getProfileData();
        return $profile_data[$field] ?? $default;
    }
    
    /**
     * Get field configuration for validation/rendering
     * 
     * @return array Field type configuration
     */
    public static function getFieldTypes(): array {
        return self::FIELD_TYPES;
    }
}
