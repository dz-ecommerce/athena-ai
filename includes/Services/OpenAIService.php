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
    private string $default_model = 'gpt-3.5-turbo';
    
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
            // Prüfen, ob der Key verschlüsselt ist (Base64-Decodierung sollte funktionieren)
            if ($this->is_base64($encrypted_key)) {
                // Key ist verschlüsselt
                $this->api_key = $this->decrypt_api_key($encrypted_key);
            } else {
                // Key wurde unverschlüsselt gespeichert
                $this->api_key = $encrypted_key;
            }
        }
        
        // Org ID nur setzen, wenn wirklich ein Wert existiert
        $org_id = get_option('athena_ai_openai_org_id', '');
        if (!empty($org_id) && trim($org_id) !== '') {
            $this->org_id = trim($org_id);
        } else {
            $this->org_id = ''; // Explizit leer setzen
        }
        
        $this->default_model = get_option('athena_ai_openai_default_model', 'gpt-3.5-turbo');
        $this->temperature = (float) get_option('athena_ai_openai_temperature', 0.7);
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
        $fallback_attempted = isset($options['fallback_attempted']) ? $options['fallback_attempted'] : false;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
        ];
        
        // Nur wenn org_id explizit gesetzt wurde und nicht leer ist, senden wir den Header
        $use_org_id = !empty($this->org_id);
        if ($use_org_id) {
            $headers['OpenAI-Organization'] = $this->org_id;
        }
        
        // Debug-Log
        error_log('OpenAI Request: API key length: ' . strlen($this->api_key) . ', Model: ' . $model . ', Using Org ID: ' . ($use_org_id ? 'yes' : 'no'));
        
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
            error_log('OpenAI API WP Error: ' . $this->last_error);
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            
            // Spezielle Fehlerbehandlung für Quota-Überschreitung
            if (strpos($error_message, 'exceeded your current quota') !== false || 
                strpos($error_message, 'billing details') !== false) {
                
                $this->last_error = "⚠️ OpenAI-Kontingent aufgebraucht: Ihr OpenAI-Konto hat keine verfügbaren Credits mehr. \n\nBitte aktualisieren Sie Ihr OpenAI-Konto:\n1. Gehen Sie zu https://platform.openai.com/account/usage\n2. Prüfen Sie Ihren Kontostatus\n3. Führen Sie ein Upgrade durch oder kaufen Sie weitere Credits unter https://platform.openai.com/account/billing";
                error_log('OpenAI API Error: Quota exceeded');
                
                return new \WP_Error('quota_exceeded', $this->last_error);
            }
            
            $this->last_error = "OpenAI API error ({$status_code}): {$error_message}";
            
            error_log('OpenAI API Error: ' . $this->last_error);
            
            // Fallback-Strategie für nicht verfügbare Modelle
            if (strpos($error_message, 'does not exist or you do not have access to it') !== false && !$fallback_attempted) {
                error_log('Falling back to gpt-3.5-turbo model');
                // Versuche es mit gpt-3.5-turbo als Fallback
                return $this->generate_content($prompt, [
                    'model' => 'gpt-3.5-turbo',
                    'temperature' => $temperature,
                    'fallback_attempted' => true
                ]);
            }
            
            // Spezialfall: 401 mit Hinweis auf Organization 
            if ($status_code === 401 && strpos($error_message, 'Organization') !== false && $use_org_id) {
                error_log('Retrying OpenAI request without organization ID');
                
                // Versuche erneut ohne Org ID
                $headers_without_org = $headers;
                unset($headers_without_org['OpenAI-Organization']);
                
                $retry_response = wp_remote_post(
                    $this->api_endpoint,
                    [
                        'headers' => $headers_without_org,
                        'body' => json_encode($payload),
                        'timeout' => 60,
                        'sslverify' => true,
                    ]
                );
                
                if (is_wp_error($retry_response)) {
                    return $retry_response;
                }
                
                $retry_status_code = wp_remote_retrieve_response_code($retry_response);
                $retry_body = wp_remote_retrieve_body($retry_response);
                $retry_data = json_decode($retry_body, true);
                
                if ($retry_status_code === 200) {
                    error_log('OpenAI retry request succeeded without org ID');
                    return $retry_data;
                }
                
                // Auch der erneute Versuch schlug fehl
                $retry_error_message = isset($retry_data['error']['message']) ? $retry_data['error']['message'] : 'Unknown error';
                
                // Spezielle Fehlerbehandlung für Quota-Überschreitung im Retry
                if (strpos($retry_error_message, 'exceeded your current quota') !== false || 
                    strpos($retry_error_message, 'billing details') !== false) {
                    
                    $this->last_error = "⚠️ OpenAI-Kontingent aufgebraucht: Ihr OpenAI-Konto hat keine verfügbaren Credits mehr. \n\nBitte aktualisieren Sie Ihr OpenAI-Konto:\n1. Gehen Sie zu https://platform.openai.com/account/usage\n2. Prüfen Sie Ihren Kontostatus\n3. Führen Sie ein Upgrade durch oder kaufen Sie weitere Credits unter https://platform.openai.com/account/billing";
                    error_log('OpenAI API Error: Quota exceeded (retry)');
                    
                    return new \WP_Error('quota_exceeded', $this->last_error);
                }
                
                $this->last_error .= " (Retry failed: {$retry_error_message})";
                
                // Fallback nach fehlgeschlagenem Retry
                if (strpos($retry_error_message, 'does not exist or you do not have access to it') !== false && !$fallback_attempted) {
                    error_log('Falling back to gpt-3.5-turbo model after retry failed');
                    // Versuche es mit gpt-3.5-turbo als Fallback
                    return $this->generate_content($prompt, [
                        'model' => 'gpt-3.5-turbo',
                        'temperature' => $temperature,
                        'fallback_attempted' => true
                    ]);
                }
            }
            
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