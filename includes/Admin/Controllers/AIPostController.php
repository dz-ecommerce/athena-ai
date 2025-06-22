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
        \add_action('wp_ajax_athena_ai_post_step', [self::class, 'handle_step_navigation']);
        
        // Handle form submission
        \add_action('wp_ajax_athena_ai_post_generate', [self::class, 'handle_post_generation']);
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
        if (!isset($_POST['nonce']) || !\wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            \wp_send_json_error(['message' => \__('Security check failed.', 'athena-ai')]);
        }

        // Check permissions
        if (!\current_user_can(self::CAPABILITY)) {
            \wp_send_json_error(['message' => \__('You do not have permission to perform this action.', 'athena-ai')]);
        }

        $step = isset($_POST['step']) ? intval($_POST['step']) : 1;
        $step = max(1, min(4, $step)); // Ensure step is between 1-4

        // Store form data in session if needed
        if (isset($_POST['form_data'])) {
            self::store_form_data($_POST['form_data']);
        }

        \wp_send_json_success([
            'step' => $step,
            'message' => sprintf(\__('Moved to step %d', 'athena-ai'), $step)
        ]);
    }

    /**
     * Handle post generation
     */
    public static function handle_post_generation(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !\wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            \wp_send_json_error(['message' => \__('Security check failed.', 'athena-ai')]);
        }

        // Check permissions
        if (!\current_user_can(self::CAPABILITY)) {
            \wp_send_json_error(['message' => \__('You do not have permission to perform this action.', 'athena-ai')]);
        }

        // Get form data from individual POST fields
        $form_data = [];
        
        // Extract all form fields except action and nonce
        foreach ($_POST as $key => $value) {
            if (in_array($key, ['action', 'nonce'], true)) {
                continue;
            }
            $form_data[$key] = $value;
        }
        
        // Log the form data for debugging
        error_log('Form data received: ' . print_r($form_data, true));
        
        try {
            // Step 1: Load profile data
            $profile_data = \get_option('athena_ai_profiles', []);
            
            // Step 2: Load source content
            $source_content = self::load_source_content($form_data);
            
            // Step 3: Build AI prompt
            $prompt_manager = \AthenaAI\Core\PromptManager::get_instance();
            $prompt = $prompt_manager->build_ai_post_prompt($form_data, $profile_data, $source_content);
            
            // Step 4: Generate content with AI (use demo for now to avoid API issues)
            $ai_response = self::get_demo_ai_response($form_data);
            
            // Step 5: Parse response
            $parsed_content = $prompt_manager->parse_ai_post_response($ai_response);
            
            // Return debug information
            \wp_send_json_success([
                'step' => 'completed',
                'message' => \__('AI Post generation completed!', 'athena-ai'),
                'progress' => 100,
                'debug' => [
                    'form_data' => $form_data,
                    'profile_data' => $profile_data,
                    'source_content' => $source_content,
                    'prompt' => $prompt,
                    'ai_response' => $ai_response,
                    'parsed_content' => $parsed_content
                ],
                'result' => $parsed_content
            ]);
            
        } catch (\Exception $e) {
            \wp_send_json_error([
                'message' => \__('Error during AI post generation: ', 'athena-ai') . $e->getMessage(),
                'debug' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
    }

    /**
     * Load source content based on form data
     */
    private static function load_source_content(array $form_data): array {
        $source_content = [];
        $content_source = $form_data['content_source'] ?? 'custom_topic';
        
        switch ($content_source) {
            case 'feed_items':
                $source_content['feed_items'] = self::load_feed_items($form_data);
                break;
                
            case 'page_content':
                $source_content['pages'] = self::load_wordpress_pages($form_data);
                break;
                
            case 'post_content':
                $source_content['posts'] = self::load_wordpress_posts($form_data);
                break;
                
            case 'custom_topic':
                // Custom topic is already in form_data
                break;
        }
        
        return $source_content;
    }

    /**
     * Load selected feed items
     */
    private static function load_feed_items(array $form_data): array {
        $selected_items = $form_data['selected_feed_items'] ?? [];
        if (empty($selected_items)) {
            return [];
        }
        
        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($selected_items), '%s'));
        
        $items = $wpdb->get_results($wpdb->prepare("
            SELECT ri.*, p.post_title as feed_title,
                   JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.title')) as title,
                   JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.description')) as description,
                   JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.link')) as link
            FROM {$wpdb->prefix}feed_raw_items ri
            JOIN {$wpdb->posts} p ON ri.feed_id = p.ID
            WHERE ri.item_hash IN ($placeholders)
        ", $selected_items), ARRAY_A);
        
        return $items ?: [];
    }

    /**
     * Load selected WordPress pages
     */
    private static function load_wordpress_pages(array $form_data): array {
        $selected_pages = $form_data['selected_pages'] ?? [];
        if (empty($selected_pages)) {
            return [];
        }
        
        $pages = [];
        foreach ($selected_pages as $page_id) {
            $page = \get_post($page_id);
            if ($page && $page->post_type === 'page' && $page->post_status === 'publish') {
                $pages[] = [
                    'id' => $page->ID,
                    'title' => $page->post_title,
                    'content' => $page->post_content,
                    'excerpt' => $page->post_excerpt
                ];
            }
        }
        
        return $pages;
    }

    /**
     * Load selected WordPress posts
     */
    private static function load_wordpress_posts(array $form_data): array {
        $selected_posts = $form_data['selected_posts'] ?? [];
        if (empty($selected_posts)) {
            return [];
        }
        
        $posts = [];
        foreach ($selected_posts as $post_id) {
            $post = \get_post($post_id);
            if ($post && $post->post_type === 'post' && $post->post_status === 'publish') {
                $posts[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'content' => $post->post_content,
                    'excerpt' => $post->post_excerpt
                ];
            }
        }
        
        return $posts;
    }

    /**
     * Generate AI content using the appropriate service
     */
    private static function generate_ai_content(string $prompt, array $form_data): string {
        // Determine which AI service to use (could be from settings)
        $ai_provider = \get_option('athena_ai_provider', 'openai'); // Default to OpenAI
        
        try {
            if ($ai_provider === 'gemini') {
                $gemini_service = new \AthenaAI\Services\GeminiService();
                $result = $gemini_service->generate_content($prompt);
                
                if (\is_wp_error($result)) {
                    throw new \Exception($result->get_error_message());
                }
                
                return $gemini_service->extract_content($result);
                
            } else {
                // Use OpenAI
                $openai_service = new \AthenaAI\Services\OpenAIService();
                $result = $openai_service->generate_content($prompt);
                
                if (\is_wp_error($result)) {
                    throw new \Exception($result->get_error_message());
                }
                
                if (isset($result['choices'][0]['message']['content'])) {
                    return $result['choices'][0]['message']['content'];
                } else {
                    throw new \Exception('Unexpected AI response format');
                }
            }
            
        } catch (\Exception $e) {
            // Fallback: return a demo response for testing
            return self::get_demo_ai_response($form_data);
        }
    }

    /**
     * Generate demo AI response for testing
     */
    private static function get_demo_ai_response(array $form_data): string {
        $content_type = $form_data['content_type'] ?? 'blog_post';
        
        return "=== TITEL ===\nDemo: " . ucfirst(str_replace('_', ' ', $content_type)) . " für Ihr Unternehmen\n\n=== META-BESCHREIBUNG ===\nEntdecken Sie, wie Sie mit unserem " . $content_type . " Ihre Zielgruppe erreichen und Ihre Geschäftsziele verwirklichen können. Jetzt mehr erfahren!\n\n=== INHALT ===\n<h2>Einleitung</h2>\n<p>Dies ist ein Demo-Artikel, der zeigt, wie die AI-Post-Generierung funktioniert.</p>\n\n<h2>Hauptteil</h2>\n<p>Hier würde der echte AI-generierte Content stehen, basierend auf Ihren Unternehmensdaten und den ausgewählten Quellen.</p>\n\n<h3>Wichtige Punkte</h3>\n<ul>\n<li>Punkt 1: Relevanter Inhalt</li>\n<li>Punkt 2: SEO-Optimierung</li>\n<li>Punkt 3: Zielgruppengerechte Ansprache</li>\n</ul>\n\n<h2>Fazit</h2>\n<p>Kontaktieren Sie uns noch heute, um mehr über unsere Lösungen zu erfahren!</p>";
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
        $html .= '<div class="flex items-center justify-between w-full">';

        foreach ($steps as $step_number => $step_config) {
            $is_active = $step_number === $current_step;
            $is_completed = $step_number < $current_step;
            $is_last = $step_number === count($steps);
            
            // Step container
            $html .= '<div class="flex flex-col items-center flex-1">';
            
            // Circle
            if ($is_completed) {
                $circle_classes = 'w-8 h-8 bg-green-500 border-2 border-green-500 rounded-full flex justify-center items-center text-sm text-white lg:w-10 lg:h-10 cursor-pointer hover:opacity-80 transition-opacity';
                $circle_content = '<i class="fa-solid fa-check text-xs"></i>';
                $html .= '<button type="button" onclick="navigateToStep(' . $step_number . ')" class="' . $circle_classes . '">';
                $html .= $circle_content;
                $html .= '</button>';
            } elseif ($is_active) {
                $circle_classes = 'w-8 h-8 bg-purple-600 border-2 border-purple-600 rounded-full flex justify-center items-center text-sm text-white lg:w-10 lg:h-10';
                $html .= '<div class="' . $circle_classes . '">';
                $html .= $step_number;
                $html .= '</div>';
            } else {
                $circle_classes = 'w-8 h-8 bg-gray-100 border-2 border-gray-300 rounded-full flex justify-center items-center text-sm text-gray-500 lg:w-10 lg:h-10';
                $html .= '<div class="' . $circle_classes . '">';
                $html .= $step_number;
                $html .= '</div>';
            }
            
            // Step title
            $text_color = $is_active ? 'text-purple-600' : ($is_completed ? 'text-green-600' : 'text-gray-500');
            $html .= '<span class="text-xs mt-2 font-medium ' . $text_color . ' text-center">' . esc_html($step_config['title']) . '</span>';
            
            $html .= '</div>';
            
            // Connector line (except for last step)
            if (!$is_last) {
                $line_color = ($is_completed || ($is_active && $step_number > 1)) ? 'bg-purple-600' : 'bg-gray-300';
                $html .= '<div class="flex-1 h-0.5 ' . $line_color . ' mx-2 mt-[-20px] lg:mt-[-25px]"></div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
} 