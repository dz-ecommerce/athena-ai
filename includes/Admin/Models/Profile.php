<?php
/**
 * Handles profile data operations
 */
namespace AthenaAI\Admin\Models;

use AthenaAI\Admin\Core\FieldStorage;
use AthenaAI\Admin\Core\FieldSanitizer;

class Profile {
    /**
     * @var FieldStorage Storage handler
     */
    private $storage;
    
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
     * Constructor
     */
    public function __construct() {
        $this->storage = new FieldStorage();
    }

    /**
     * Get profile data
     * 
     * @return array Profile data
     */
    public function getProfileData() {
        return $this->storage->get('profiles');
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

        $sanitized_data = FieldSanitizer::sanitize($data, self::FIELD_TYPES);
        return $this->storage->save('profiles', $sanitized_data);
    }

    /**
     * Get a specific profile field
     * 
     * @param string $field Field name
     * @param mixed $default Default value if field doesn't exist
     * @return mixed Field value or default
     */
    public function getProfileField($field, $default = '') {
        return $this->storage->getField('profiles', $field, $default);
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
