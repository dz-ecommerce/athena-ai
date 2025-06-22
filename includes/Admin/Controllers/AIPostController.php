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
        $html = '<div class="flex items-center justify-center mb-8">';
        $html .= '<div class="flex items-center space-x-4">';

        foreach ($steps as $step_number => $step_config) {
            $is_active = $step_number === $current_step;
            $is_completed = $step_number < $current_step;
            
            $classes = 'flex items-center justify-center w-10 h-10 rounded-full border-2 text-sm font-medium transition-all duration-200';
            
            if ($is_active) {
                $classes .= ' bg-purple-600 border-purple-600 text-white';
            } elseif ($is_completed) {
                $classes .= ' bg-green-500 border-green-500 text-white cursor-pointer hover:bg-green-600';
            } else {
                $classes .= ' bg-gray-100 border-gray-300 text-gray-500';
            }

            $html .= '<div class="flex flex-col items-center">';
            $html .= '<button type="button" class="' . $classes . '" onclick="navigateToStep(' . $step_number . ')" ' . ($step_number > $current_step ? 'disabled' : '') . '>';
            
            if ($is_completed) {
                $html .= '<i class="fa-solid fa-check"></i>';
            } else {
                $html .= $step_number;
            }
            
            $html .= '</button>';
            $html .= '<span class="text-xs text-gray-600 mt-1 text-center max-w-20">' . esc_html($step_config['title']) . '</span>';
            $html .= '</div>';

            // Add connector line between steps
            if ($step_number < count($steps)) {
                $connector_classes = 'w-12 h-0.5 mx-2 transition-all duration-200';
                $connector_classes .= $step_number < $current_step ? ' bg-green-500' : ' bg-gray-300';
                $html .= '<div class="' . $connector_classes . '"></div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
} 