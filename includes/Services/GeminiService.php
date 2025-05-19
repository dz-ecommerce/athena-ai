<?php
/**
 * Google Gemini Service class
 *
 * Handles API communication with Google Gemini.
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
 * Google Gemini API service.
 */
class GeminiService {
    /**
     * API endpoint
     * 
     * @var string
     */
    private string $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    /**
     * API key
     * 
     * @var string
     */
    private string $api_key = '';
    
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
        $encrypted_key = get_option('athena_ai_gemini_api_key', '');
        if (!empty($encrypted_key)) {
            // Prüfen, ob der Key verschlüsselt ist (Base64-Decodierung sollte funktionieren)
            if ($this->is_base64($encrypted_key)) {
                // Key ist verschlüsselt
                $this->api_key = $this->decrypt_api_key($encrypted_key);
            } else {
                // Key wurde unverschlüsselt gespeichert
                $this->api_key = $encrypted_key;
            }
        }
        
        $this->temperature = (float) get_option('athena_ai_gemini_temperature', 0.7);
    }
    
    /**
     * Send a request to Google Gemini API
     * 
     * @param string $prompt The prompt to send to Gemini
     * @param array $options Additional options for the API request
     * @return array|WP_Error The API response or error
     */
    public function generate_content($prompt, $options = []) {
        if (empty($this->api_key)) {
            $this->last_error = 'Google Gemini API key is not set';
            return new \WP_Error('api_key_missing', $this->last_error);
        }
        
        $temperature = $options['temperature'] ?? $this->temperature;
        
        // Debug-Log
        error_log('Gemini Request: API key length: ' . strlen($this->api_key) . ', Temperature: ' . $temperature);
        
        // Construct the API URL with key
        $api_url = $this->api_endpoint . '?key=' . $this->api_key;
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temperature,
            ],
        ];
        
        $response = wp_remote_post(
            $api_url,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($payload),
                'timeout' => 60,
                'sslverify' => true,
            ]
        );
        
        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            error_log('Gemini API WP Error: ' . $this->last_error);
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            
            // Spezielle Fehlerbehandlung für Quota-Überschreitung
            if (strpos($error_message, 'API key not valid') !== false || 
                strpos($error_message, 'quota') !== false) {
                
                $this->last_error = "⚠️ Google Gemini API-Schlüssel-Problem: Ihr Google API-Schlüssel ist nicht gültig oder Ihr Kontingent ist aufgebraucht. \n\nBitte aktualisieren Sie Ihren API-Schlüssel:\n1. Gehen Sie zu https://ai.google.dev/\n2. Erstellen Sie einen neuen API-Schlüssel oder überprüfen Sie Ihren bestehenden";
                error_log('Gemini API Error: Key or quota issue');
                
                return new \WP_Error('api_key_error', $this->last_error);
            }
            
            $this->last_error = "Gemini API error ({$status_code}): {$error_message}";
            error_log('Gemini API Error: ' . $this->last_error);
            
            return new \WP_Error('api_error', $this->last_error);
        }
        
        return $data;
    }
    
    /**
     * Extract text content from Gemini response
     * 
     * @param array $response The API response
     * @return string The extracted text content
     */
    public function extract_content($response) {
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }
        return "Keine Antwort erhalten oder unerwartetes Antwortformat";
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
     * Prüft, ob ein String base64-encoded ist
     */
    private function is_base64($string) {
        if (!is_string($string)) return false;
        
        // Versuche Base64-Decodierung
        $decoded = base64_decode($string, true);
        if ($decoded === false) return false;
        
        // Additional check: Base64 strings have a length divisible by 4
        if (strlen($string) % 4 !== 0) return false;
        
        // Base64 strings contain only these chars
        return preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string);
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