<?php
/**
 * Handles AI-related operations
 */
class AIService {
    /**
     * Generate content using AI
     * 
     * @param string $prompt The prompt to send to the AI
     * @param string $provider The AI provider to use (e.g., 'openai', 'gemini')
     * @param array $options Additional options
     * @return array Result with status and generated content or error message
     */
    public function generateContent($prompt, $provider = 'openai', $options = []) {
        if (empty($prompt)) {
            return [
                'success' => false,
                'message' => 'No prompt provided'
            ];
        }

        // Default options
        $defaults = [
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'test_mode' => false
        ];

        $options = wp_parse_args($options, $defaults);

        // If in test mode, return the prompt without making an API call
        if ($options['test_mode']) {
            return [
                'success' => true,
                'content' => $prompt,
                'test_mode' => true
            ];
        }


        // TODO: Implement actual API calls to AI providers
        // This is a placeholder implementation
        try {
            // Simulate API call delay
            sleep(2);
            
            // For now, just return the prompt as if it was generated
            return [
                'success' => true,
                'content' => $prompt,
                'provider' => $provider
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to generate content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract products and services from text
     * 
     * @param string $text The text to analyze
     * @param string $provider The AI provider to use
     * @return array Extracted products and services
     */
    public function extractProductsAndServices($text, $provider = 'openai') {
        if (empty($text)) {
            return [
                'success' => false,
                'message' => 'No text provided'
            ];
        }

        $prompt = "Extract a comma-separated list of products and services from the following text. Only return the list, no additional text.\n\n" . $text;
        
        $result = $this->generateContent($prompt, $provider);
        
        if (!$result['success']) {
            return $result;
        }

        // Process the result into an array
        $items = array_map('trim', explode(',', $result['content']));
        $items = array_filter($items);
        $items = array_unique($items);

        return [
            'success' => true,
            'items' => array_values($items)
        ];
    }
}
