<?php
/**
 * OpenAI Service class
 *
 * Handles API communication with OpenAI.
 *
 * @package AthenaAI\Services
 */

declare(strict_types=1);

namespace AthenaAI\Services;

// Ensure WordPress environment
if (!defined('ABSPATH')) {
    exit();
}

/**
 * OpenAI API service.
 */
class OpenAIService {
    /**
     * API endpoint
     * 
     * @var string
     */
    private string $api_endpoint = 'https://api.openai.com/v1/chat/completions';
    
    /**
     * API key
     * 
     * @var string
     */
    private string $api_key = '';
    
    /**
     * Organization ID
     * 
     * @var string
     */
    private string $org_id = '';
    
    /**
     * Default model
     * 
     * @var string
     */
    private string $default_model = 'gpt-4';
    
    /**
     * Default temperature
     * 
     * @var float
     */
    private float $temperature = 0.7;
    
    /**
     * Last error message
     * 
     * @var string
     */
    private string $last_error = '';
    
    /**
     * Constructor - Initialize the service with settings from WordPress options
     */
    public function __construct() {
        $this->load_settings();
    }
    
    /**
     * Load settings from WordPress options
     */
    private function load_settings() {
        // Get encrypted API key
        $encrypted_key = get_option('athena_ai_openai_api_key', '');
        if (!empty($encrypted_key)) {
            $this->api_key = $this->decrypt_api_key($encrypted_key);
        }
        
        $this->org_id = get_option('athena_ai_openai_org_id', '');
        $this->default_model = get_option('athena_ai_openai_default_model', 'gpt-4');
        $this->temperature = (float) get_option('athena_ai_openai_temperature', 0.7);
    }
    
    /**
     * Send a request to OpenAI API
     * 
     * @param string $prompt The prompt to send to OpenAI
     * @param array $options Additional options for the API request
     * @return array|WP_Error The API response or error
     */
    public function generate_content($prompt, $options = []) {
        if (empty($this->api_key)) {
            $this->last_error = 'OpenAI API key is not set';
            return new \WP_Error('api_key_missing', $this->last_error);
        }
        
        $model = $options['model'] ?? $this->default_model;
        $temperature = $options['temperature'] ?? $this->temperature;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
        ];
        
        if (!empty($this->org_id)) {
            $headers['OpenAI-Organization'] = $this->org_id;
        }
        
        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful AI assistant that creates content based on user input. Respond in markdown format.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $temperature,
        ];
        
        $response = wp_remote_post(
            $this->api_endpoint,
            [
                'headers' => $headers,
                'body' => json_encode($payload),
                'timeout' => 60,
                'sslverify' => true,
            ]
        );
        
        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            $this->last_error = "OpenAI API error ({$status_code}): {$error_message}";
            return new \WP_Error('api_error', $this->last_error);
        }
        
        return $data;
    }
    
    /**
     * Get the last error message
     * 
     * @return string The last error message
     */
    public function get_last_error() {
        return $this->last_error;
    }
    
    /**
     * Decrypt API key using the same method as in Settings class
     * 
     * @param string $encrypted_text The encrypted API key
     * @return string The decrypted API key
     */
    private function decrypt_api_key($encrypted_text) {
        $key = $this->get_encryption_key();
        $data = base64_decode($encrypted_text);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key from database
     * 
     * @return string The encryption key
     */
    private function get_encryption_key() {
        $option_name = 'athena_ai_encryption_key';
        $key = get_option($option_name, '');
        if (empty($key)) {
            // This should rarely happen as the key should already be set by Settings class
            $key = bin2hex(random_bytes(32));
            update_option($option_name, $key);
        }
        return $key;
    }
} 