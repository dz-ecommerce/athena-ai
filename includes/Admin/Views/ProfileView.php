<?php
/**
 * Handles rendering of profile-related views
 */
namespace AthenaAI\Admin\Views;

use AthenaAI\Admin\Services\ProfileService;

class ProfileView {
    /**
     * @var ProfileService Profile service instance
     */
    private $profileService;

    /**
     * Constructor
     * 
     * @param ProfileService $profileService Profile service instance
     */
    public function __construct($profileService) {
        $this->profileService = $profileService;
    }

    /**
     * Render the profile page
     */
    public function renderProfilePage() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get profile data
        $profile_data = $this->profileService->getProfile();
        
        // Get pages for dropdown
        $pages = get_pages([
            'sort_column' => 'post_title',
            'sort_order' => 'asc'
        ]);

        // Include the template
        include ATHENA_AI_PLUGIN_DIR . 'templates/admin/profile.php';
    }

    /**
     * Render a form field
     * 
     * @param string $field_name Field name
     * @param string $label Field label
     * @param string $type Input type (text, textarea, select, etc.)
     * @param array $options Options for select/radio/checkbox
     * @param string $default Default value
     * @param string $description Field description
     */
    public function renderField($field_name, $label, $type = 'text', $options = [], $default = '', $description = '') {
        $value = $this->profileService->getProfileField($field_name, $default);
        $field_id = sanitize_title($field_name);
        
        echo '<div class="form-group">';
        
        // Label
        if (!empty($label)) {
            echo sprintf(
                '<label for="%s" class="block text-sm font-medium text-gray-700 mb-1">%s</label>',
                esc_attr($field_id),
                esc_html($label)
            );
        }
        
        // Input field
        switch ($type) {
            case 'textarea':
                echo sprintf(
                    '<textarea id="%s" name="athena_ai_profile[%s]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">%s</textarea>',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_textarea($value)
                );
                break;
                
            case 'select':
                echo sprintf('<select id="%s" name="athena_ai_profile[%s]" class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm">', 
                    esc_attr($field_id), 
                    esc_attr($field_name)
                );
                
                foreach ($options as $option_value => $option_label) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($option_value),
                        selected($value, $option_value, false),
                        esc_html($option_label)
                    );
                }
                
                echo '</select>';
                break;
                
            case 'checkbox':
                echo sprintf(
                    '<input type="checkbox" id="%s" name="athena_ai_profile[%s]" value="1" %s class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    checked($value, 1, false)
                );
                break;
                
            case 'radio':
                echo '<div class="space-y-2">';
                foreach ($options as $option_value => $option_label) {
                    echo '<div class="flex items-center">';
                    printf(
                        '<input id="%s_%s" name="athena_ai_profile[%s]" type="radio" value="%s" %s class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">',
                        esc_attr($field_id),
                        esc_attr($option_value),
                        esc_attr($field_name),
                        esc_attr($option_value),
                        checked($value, $option_value, false)
                    );
                    printf(
                        '<label for="%s_%s" class="ml-2 block text-sm text-gray-700">%s</label>',
                        esc_attr($field_id),
                        esc_attr($option_value),
                        esc_html($option_label)
                    );
                    echo '</div>';
                }
                echo '</div>';
                break;
                
            default: // text, email, number, etc.
                echo sprintf(
                    '<input type="%s" id="%s" name="athena_ai_profile[%s]" value="%s" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">',
                    esc_attr($type),
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($value)
                );
        }
        
        // Description
        if (!empty($description)) {
            echo sprintf(
                '<p class="mt-1 text-sm text-gray-500">%s</p>',
                esc_html($description)
            );
        }
        
        echo '</div>';
    }
}
