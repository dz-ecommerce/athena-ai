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
        
        echo '<script>console.' . $type . '("Athena AI Feed: ' . esc_js($message) . '");</script>';
    }

    /**
     * Fetch content from a URL.
     *
     * @param string $url     The URL to fetch.
     * @param array  $options Additional options to merge with defaults.
     * @return string|false The fetched content or false on failure.
     */
    public function fetch(string $url, array $options = []) {
        // Merge default options with provided options
        $request_options = array_merge($this->default_options, $options);
        
        $this->consoleLog("Fetching feed from URL: {$url}", 'info');
        $this->consoleLog("Request options: " . wp_json_encode($request_options), 'log');
        
        // Make the request
        $response = wp_remote_get($url, $request_options);
        
        // Check for errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->consoleLog("Error fetching feed: {$error_message}", 'error');
            return false;
        }
        
        // Check response code
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $this->consoleLog("Invalid response code: {$status_code}", 'error');
            return false;
        }
        
        // Get the body
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            $this->consoleLog("Empty response body", 'error');
            return false;
        }
        
        $this->consoleLog("Successfully fetched feed content (" . strlen($body) . " bytes)", 'info');
        return $body;
    }
}
