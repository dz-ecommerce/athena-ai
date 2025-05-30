<?php
/**
 * Handles business logic for profile management
 */
class ProfileService {
    /**
     * @var Profile Profile model instance
     */
    private $profile;

    /**
     * Constructor
     * 
     * @param Profile $profile Profile model instance
     */
    public function __construct($profile) {
        $this->profile = $profile;
    }

    /**
     * Get all profile data
     * 
     * @return array Profile data
     */
    public function getProfile() {
        return $this->profile->getProfileData();
    }

    /**
     * Update profile data
     * 
     * @param array $data Profile data to update
     * @return array Result with status and message
     */
    public function updateProfile($data) {
        if (empty($data)) {
            return [
                'success' => false,
                'message' => 'No data provided'
            ];
        }

        $result = $this->profile->saveProfileData($data);

        return [
            'success' => $result,
            'message' => $result 
                ? 'Profile updated successfully' 
                : 'Failed to update profile'
        ];
    }

    /**
     * Get specific profile field
     * 
     * @param string $field Field name
     * @param mixed $default Default value if field doesn't exist
     * @return mixed Field value or default
     */
    public function getProfileField($field, $default = '') {
        return $this->profile->getProfileField($field, $default);
    }
}