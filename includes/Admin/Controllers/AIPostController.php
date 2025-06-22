<?php
declare(strict_types=1);

namespace AthenaAI\Admin\Controllers;

/**
 * Controller für AI Post Erstellung
 */
class AIPostController {
    /**
     * Capability required to access this functionality
     */
    const CAPABILITY = 'manage_options';

    /**
     * Nonce action for form submissions
     */
    const NONCE_ACTION = 'athena_ai_post_nonce';

    /**
     * Initialize the AI Post Controller
     */
    public static function init(): void {
        // Handle AJAX requests for step navigation
        add_action('wp_ajax_athena_ai_post_step', [self::class, 'handle_step_navigation']);
        
        // Handle form submission
        add_action('wp_ajax_athena_ai_post_generate', [self::class, 'handle_post_generation']);
    }

    /**
     * Get the current step from request or session
     */
    public static function get_current_step(): int {
        if (isset($_POST['current_step'])) {
            return max(1, min(4, intval($_POST['current_step'])));
        }
        
        if (isset($_GET['step'])) {
            return max(1, min(4, intval($_GET['step'])));
        }
        
        return 1; // Default to step 1
    }

    /**
     * Handle AJAX step navigation
     */
    public static function handle_step_navigation(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Security check failed.', 'athena-ai')]);
        }

        // Check permissions
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'athena-ai')]);
        }

        $step = isset($_POST['step']) ? intval($_POST['step']) : 1;
        $step = max(1, min(4, $step)); // Ensure step is between 1-4

        // Store form data in session if needed
        if (isset($_POST['form_data'])) {
            self::store_form_data($_POST['form_data']);
        }

        wp_send_json_success([
            'step' => $step,
            'message' => sprintf(__('Moved to step %d', 'athena-ai'), $step)
        ]);
    }

    /**
     * Handle post generation
     */
    public static function handle_post_generation(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Security check failed.', 'athena-ai')]);
        }

        // Check permissions
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'athena-ai')]);
        }

        // Get form data
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : [];
        
        // TODO: Implement actual AI post generation logic here
        // For now, just return success
        wp_send_json_success([
            'message' => __('AI Post generation started!', 'athena-ai'),
            'redirect' => admin_url('admin.php?page=athena-feed-items')
        ]);
    }

    /**
     * Store form data in session
     */
    private static function store_form_data(array $data): void {
        if (!session_id()) {
            session_start();
        }
        $_SESSION['athena_ai_post_data'] = $data;
    }

    /**
     * Get stored form data from session
     */
    public static function get_stored_form_data(): array {
        if (!session_id()) {
            session_start();
        }
        return $_SESSION['athena_ai_post_data'] ?? [];
    }

    /**
     * Clear stored form data
     */
    public static function clear_stored_form_data(): void {
        if (!session_id()) {
            session_start();
        }
        unset($_SESSION['athena_ai_post_data']);
    }

    /**
     * Get step configuration
     */
    public static function get_step_config(): array {
        return [
            1 => [
                'title' => __('Content Source', 'athena-ai'),
                'description' => __('Select the source for your AI-generated content', 'athena-ai'),
                'icon' => 'fa-solid fa-file-text'
            ],
            2 => [
                'title' => __('Content Type', 'athena-ai'),
                'description' => __('Choose the type of content you want to create', 'athena-ai'),
                'icon' => 'fa-solid fa-list'
            ],
            3 => [
                'title' => __('Customization', 'athena-ai'),
                'description' => __('Customize your content preferences', 'athena-ai'),
                'icon' => 'fa-solid fa-cog'
            ],
            4 => [
                'title' => __('Review & Generate', 'athena-ai'),
                'description' => __('Review your settings and generate the post', 'athena-ai'),
                'icon' => 'fa-solid fa-check'
            ]
        ];
    }

    /**
     * Render step navigation
     */
    public static function render_step_navigation(int $current_step): string {
        $steps = self::get_step_config();
        $html = '<div class="mb-8">';
        $html .= '<ol class="flex items-center w-full text-xs text-gray-900 font-medium sm:text-base">';

        foreach ($steps as $step_number => $step_config) {
            $is_active = $step_number === $current_step;
            $is_completed = $step_number < $current_step;
            $is_last = $step_number === count($steps);
            
            // Determine text color
            $text_color = $is_active ? 'text-purple-600' : 'text-gray-900';
            
            // Determine after pseudo-element classes for the line
            $after_classes = '';
            if (!$is_last) {
                if ($is_completed || $is_active) {
                    $after_classes = "after:content-[''] after:w-full after:h-0.5 after:bg-purple-600 after:inline-block after:absolute lg:after:top-5 after:top-3 after:left-4";
                } else {
                    $after_classes = "after:content-[''] after:w-full after:h-0.5 after:bg-gray-200 after:inline-block after:absolute lg:after:top-5 after:top-3 after:left-4";
                }
            }
            
            $li_classes = "flex w-full relative $text_color $after_classes";
            
            // Determine circle styles
            if ($is_completed) {
                $circle_classes = 'w-6 h-6 bg-green-500 border-2 border-green-500 rounded-full flex justify-center items-center mx-auto mb-3 text-sm text-white lg:w-10 lg:h-10';
                $circle_content = '<i class="fa-solid fa-check text-xs"></i>';
            } elseif ($is_active) {
                $circle_classes = 'w-6 h-6 bg-purple-600 border-2 border-transparent rounded-full flex justify-center items-center mx-auto mb-3 text-sm text-white lg:w-10 lg:h-10';
                $circle_content = $step_number;
            } else {
                $circle_classes = 'w-6 h-6 bg-gray-50 border-2 border-gray-200 rounded-full flex justify-center items-center mx-auto mb-3 text-sm lg:w-10 lg:h-10';
                $circle_content = $step_number;
            }

            $html .= '<li class="' . $li_classes . '">';
            $html .= '<div class="block whitespace-nowrap z-10">';
            
            if ($is_completed || $is_active) {
                $html .= '<button type="button" onclick="navigateToStep(' . $step_number . ')" class="' . $circle_classes . ' cursor-pointer hover:opacity-80 transition-opacity">';
            } else {
                $html .= '<span class="' . $circle_classes . '">';
            }
            
            $html .= $circle_content;
            
            if ($is_completed || $is_active) {
                $html .= '</button>';
            } else {
                $html .= '</span>';
            }
            
            $html .= '<span class="block text-center mt-1">' . esc_html($step_config['title']) . '</span>';
            $html .= '</div>';
            $html .= '</li>';
        }

        $html .= '</ol>';
        $html .= '</div>';

        return $html;
    }
} 