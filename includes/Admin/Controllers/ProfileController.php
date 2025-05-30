<?php
/**
 * Handles profile-related requests
 */
class ProfileController {
    /**
     * @var ProfileService Profile service instance
     */
    private $profileService;

    /**
     * @var AIService AI service instance
     */
    private $aiService;

    /**
     * Constructor
     * 
     * @param ProfileService $profileService Profile service instance
     * @param AIService $aiService AI service instance
     */
    public function __construct($profileService, $aiService) {
        $this->profileService = $profileService;
        $this->aiService = $aiService;
        
        $this->registerHooks();
    }

    /**
     * Register WordPress hooks
     */
    private function registerHooks() {
        add_action('admin_post_save_athena_ai_profile', [$this, 'handleSaveProfile']);
        add_action('wp_ajax_athena_ai_generate_content', [$this, 'handleGenerateContent']);
        add_action('wp_ajax_athena_ai_extract_products', [$this, 'handleExtractProducts']);
    }

    /**
     * Handle profile save request
     */
    public function handleSaveProfile() {
        check_admin_referer('athena_ai_save_profile', 'athena_ai_nonce');

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        $profile_data = $_POST['athena_ai_profile'] ?? [];
        $result = $this->profileService->updateProfile($profile_data);

        if ($result['success']) {
            add_settings_error(
                'athena_ai_messages',
                'athena_ai_message',
                __('Profile saved successfully', 'athena-ai'),
                'success'
            );
        } else {
            add_settings_error(
                'athena_ai_messages',
                'athena_ai_message',
                __('Failed to save profile', 'athena-ai'),
                'error'
            );
        }

        set_transient('settings_errors', get_settings_errors(), 30);
        wp_redirect(admin_url('admin.php?page=athena-ai-profile&settings-updated=true'));
        exit;
    }

    /**
     * Handle AJAX request to generate content
     */
    public function handleGenerateContent() {
        check_ajax_referer('athena_ai_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $provider = sanitize_text_field($_POST['provider'] ?? 'openai');
        $test_mode = isset($_POST['test_mode']) && $_POST['test_mode'] === 'true';

        if (empty($prompt)) {
            wp_send_json_error('No prompt provided');
        }

        $result = $this->aiService->generateContent(
            $prompt,
            $provider,
            ['test_mode' => $test_mode]
        );

        if ($result['success']) {
            wp_send_json_success([
                'content' => $result['content'],
                'test_mode' => $test_mode
            ]);
        } else {
            wp_send_json_error($result['message'] ?? 'Failed to generate content');
        }
    }

    /**
     * Handle AJAX request to extract products
     */
    public function handleExtractProducts() {
        check_ajax_referer('athena_ai_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $text = sanitize_textarea_field($_POST['text'] ?? '');
        $provider = sanitize_text_field($_POST['provider'] ?? 'openai');

        if (empty($text)) {
            wp_send_json_error('No text provided');
        }

        $result = $this->aiService->extractProductsAndServices($text, $provider);

        if ($result['success']) {
            wp_send_json_success([
                'items' => $result['items']
            ]);
        } else {
            wp_send_json_error($result['message'] ?? 'Failed to extract products');
        }
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

        // Include the template
        include ATHENA_AI_PLUGIN_DIR . 'templates/admin/profile.php';
    }
}
