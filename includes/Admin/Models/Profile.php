<?php
/**
 * Handles profile data operations
 */
class Profile {
    /**
     * @var string The option name for storing profile data
     */
    private $option_name = 'athena_ai_profiles';

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
     * Sanitize profile data
     * 
     * @param array $data Raw profile data
     * @return array Sanitized profile data
     */
    private function sanitizeProfileData($data) {
        $sanitized = [];
        
        if (!is_array($data)) {
            return $sanitized;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
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
}
