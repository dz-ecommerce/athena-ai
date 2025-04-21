<?php
/**
 * Feed HTTP Client class
 * 
 * Handles HTTP requests for fetching feed content.
 *
 * @package AthenaAI\Services
 */

declare(strict_types=1);

namespace AthenaAI\Services;

// Ensure WordPress environment
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HTTP client for fetching feed content.
 */
class FeedHttpClient {
    /**
     * Default request options.
     *
     * @var array
     */
    private array $default_options = [
        'timeout' => 30,
        'redirection' => 5,
        'sslverify' => false,
        'headers' => [
            'Accept' => 'application/rss+xml, application/rdf+xml, application/atom+xml, application/xml, text/xml, */*',
            'User-Agent' => 'Mozilla/5.0 (compatible; Athena AI Feed Fetcher/1.0)'
        ]
    ];

    /**
     * Whether to output verbose console logs.
     *
     * @var bool
     */
    private bool $verbose_console = false;

    /**
     * Set verbose console output mode.
     *
     * @param bool $verbose Whether to output verbose console logs.
     * @return self
     */
    public function setVerboseMode(bool $verbose): self {
        $this->verbose_console = $verbose;
        return $this;
    }

    /**
     * Output a console log message if verbose mode is enabled.
     *
     * @param string $message The message to log.
     * @param string $type    The type of log message (log, info, warn, error, group, groupEnd).
     * @return void
     */
    private function consoleLog(string $message, string $type = 'log'): void {
        if (!$this->verbose_console) {
            return;
        }

        $valid_types = ['log', 'info', 'warn', 'error', 'group', 'groupEnd'];
        $type = in_array($type, $valid_types) ? $type : 'log';
        
        $escaped_message = function_exists('esc_js') ? \esc_js($message) : htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo '<script>console.' . $type . '("Athena AI Feed: ' . $escaped_message . '");</script>';
    }

    /**
     * Fetch content from a URL.
     *
     * @param string $url     The URL to fetch.
     * @param array  $options Additional options to merge with defaults.
     * @return string|false The fetched content or false on failure.
     */
    public function fetch(string $url, array $options = []) {
        // Log fetching attempt
        $this->consoleLog("Fetching feed from URL: {$url}", 'info');

        // Basic URL validation
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->consoleLog("Invalid URL format: {$url}", 'error');
            return false;
        }
        
        // Merge default options with provided options
        $request_options = array_merge($this->default_options, $options);
        
        // Debug options
        if (function_exists('wp_json_encode')) {
            $options_json = \wp_json_encode($request_options);
        } else {
            $options_json = json_encode($request_options);
        }
        $this->consoleLog("Request options: {$options_json}", 'log');
        
        // Make the request using WordPress functions if available, otherwise fallback to curl
        if (function_exists('wp_remote_get')) {
            // Tell PHP we're accessing the global function in the root namespace
            $response = \wp_remote_get($url, $request_options);
            
            // Check for errors
            if (function_exists('is_wp_error')) {
                if (\is_wp_error($response)) {
                    $error_message = $response->get_error_message();
                    $this->consoleLog("WordPress error fetching feed: {$error_message}", 'error');
                    return false;
                }
            }
            
            // Check response code
            if (function_exists('wp_remote_retrieve_response_code')) {
                $status_code = \wp_remote_retrieve_response_code($response);
                if ($status_code !== 200) {
                    $this->consoleLog("Invalid response code: {$status_code}", 'error');
                    return false;
                }
            }
            
            // Get the body
            if (function_exists('wp_remote_retrieve_body')) {
                $body = \wp_remote_retrieve_body($response);
            } else {
                $body = is_array($response) && isset($response['body']) ? $response['body'] : false;
            }
        } else {
            // Fallback to curl if WordPress functions are not available
            $this->consoleLog("WordPress HTTP functions not available, using cURL fallback", 'warn');
            $body = $this->curlFetch($url, $request_options);
        }
        
        if (empty($body)) {
            $this->consoleLog("Empty response body", 'error');
            return false;
        }
        
        $this->consoleLog("Successfully fetched feed content (" . strlen($body) . " bytes)", 'info');
        
        // Preview the first part of the content for debugging
        if (!empty($body) && is_string($body)) {
            $content_preview = substr($body, 0, 200);
            $this->consoleLog("Content preview: {$content_preview}...", 'log');
        }
        
        return $body;
    }
    
    /**
     * Fallback fetch implementation using cURL
     * 
     * @param string $url     The URL to fetch
     * @param array  $options Request options
     * @return string|false The fetched content or false on failure
     */
    private function curlFetch(string $url, array $options = []): string|false {
        if (!function_exists('curl_init')) {
            $this->consoleLog("cURL is not available", 'error');
            return false;
        }
        
        $ch = curl_init($url);
        
        // Set basic cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $options['redirection'] ?? 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['sslverify'] ?? false);
        
        // Set headers if provided
        if (isset($options['headers']) && is_array($options['headers'])) {
            $headers = [];
            foreach ($options['headers'] as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // Execute cURL request
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($response === false) {
            $this->consoleLog("cURL error: {$error}", 'error');
            return false;
        }
        
        if ($status_code !== 200) {
            $this->consoleLog("Invalid response code from cURL: {$status_code}", 'error');
            return false;
        }
        
        return $response;
    }
}
