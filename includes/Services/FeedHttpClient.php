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
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Referer' => 'https://www.google.com/',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive'
        ]
    ];
    
    /**
     * Last error message
     *
     * @var string
     */
    private string $last_error = '';

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
     * @param string|null $message The message to log.
     * @param string $type    The type of log message (log, info, warn, error, group, groupEnd).
     * @return void
     */
    private function consoleLog(?string $message, string $type = 'log'): void {
        if (!$this->verbose_console) {
            return;
        }

        // Behandle NULL-Werte
        if ($message === null) {
            $message = '(null)';
        }

        $valid_types = ['log', 'info', 'warn', 'error', 'group', 'groupEnd'];
        $type = in_array($type, $valid_types) ? $type : 'log';
        
        // Eigene Implementierung von esc_js
        $escaped_message = strtr($message, [
            '\\' => '\\\\',
            "\n" => '\\n',
            "\r" => '\\r',
            "\t" => '\\t',
            '"' => '\\"',
            "'" => "\\'",
            '</' => '<\\/',
        ]);
        echo '<script>console.' . $type . '("Athena AI Feed: ' . $escaped_message . '");</script>';
    }

    /**
     * Get the last error message
     * 
     * @return string The last error message
     */
    public function get_last_error(): string {
        return $this->last_error;
    }
    
    /**
     * Set the last error message
     * 
     * @param string|null $error The error message
     * @return self
     */
    private function set_last_error(?string $error): self {
        $this->last_error = $error ?? 'Unbekannter Fehler';
        return $this;
    }

    /**
     * Prüft, ob eine bestimmte URL-Domain möglicherweise Probleme beim Abrufen verursacht
     * 
     * @param string|null $url Die zu prüfende URL
     * @return bool True, wenn die URL bekannte Probleme verursachen könnte
     */
    public function isProblemURL(?string $url): bool {
        if ($url === null) {
            return false;
        }
        
        // Liste mit Domains, die Probleme beim Abrufen verursachen können
        $problem_domains = [
            'socialmediaexaminer.com',
            // Hier können weitere problematische Domains hinzugefügt werden
        ];
        
        foreach ($problem_domains as $domain) {
            if (strpos($url, $domain) !== false) {
                $this->consoleLog("Bekannte problematische Domain erkannt: {$domain}", 'info');
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Fetch content from a URL
     * 
     * @param string|null $url     The URL to fetch
     * @param array|null  $options Request options
     * @return string|null The fetched content or null on failure
     */
    public function fetch(?string $url, ?array $options = null): ?string {
        if ($url === null) {
            $error = "URL is null";
            $this->set_last_error($error);
            $this->consoleLog($error, 'error');
            return null;
        }
        
        // Optimierte Standardkonfiguration für alle Feed-Anfragen verwenden
        $special_options = $this->default_options;
        $special_options['headers'] = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'DNT' => '1',
            'Pragma' => 'no-cache'
        ];
        
        // Try to use cURL directly for more control
        return $this->curlFetch($url, $special_options);
    }
    
    /**
     * Fallback fetch implementation using cURL
     * 
     * @param string|null $url     The URL to fetch
     * @param array|null  $options Request options
     * @return string|null The fetched content or null on failure
     */
    private function curlFetch(?string $url, ?array $options = null): ?string {
        if (!function_exists('curl_init')) {
            $error = "cURL is not available";
            $this->set_last_error($error);
            $this->consoleLog($error, 'error');
            return null;
        }
        
        $ch = curl_init($url);
        
        // Set basic cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $options['redirection'] ?? 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['sslverify'] ?? false);
        curl_setopt($ch, CURLOPT_ENCODING, ''); // Accept all available encodings
        curl_setopt($ch, CURLOPT_AUTOREFERER, true); // Set referer on redirect
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout
        curl_setopt($ch, CURLOPT_COOKIESESSION, true); // Use cookies
        curl_setopt($ch, CURLOPT_USERAGENT, $options['headers']['User-Agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
        
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
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        // Log response info for debugging
        $this->consoleLog("cURL response code: {$status_code}, content-type: {$content_type}", 'info');
        
        curl_close($ch);
        
        if ($response === false) {
            $error_msg = "cURL error: {$error}";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            return null;
        }
        
        if ($status_code !== 200) {
            $error_msg = "Invalid response code from cURL: {$status_code}";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            
            // Für alle Fehler-Statuscodes einen erneuten Versuch unternehmen
            if (!isset($options['retry']) || $options['retry'] < 3) {
                $retry_count = isset($options['retry']) ? $options['retry'] + 1 : 1;
                $this->consoleLog("Received error status code {$status_code}, trying with alternative approach (attempt {$retry_count})", 'warn');
                
                // Verschiedene Strategien je nach Wiederholungsversuch
                if ($retry_count === 1) {
                    // First retry: Use Safari User-Agent
                    $options['headers']['User-Agent'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Safari/605.1.15';
                    $options['headers']['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
                    $options['headers']['Accept-Language'] = 'en-US,en;q=0.9';
                    // Remove Google referer which might be blocked
                    if (isset($options['headers']['Referer'])) {
                        unset($options['headers']['Referer']);
                    }
                } else if ($retry_count === 2) {
                    // Second retry: Try with minimal headers
                    $options['headers'] = [
                        'User-Agent' => 'Mozilla/5.0 (compatible; Feedfetcher; +http://localhost)',
                        'Accept' => '*/*'
                    ];
                } else {
                    // Third retry: Try pretending to be Feedly
                    $options['headers'] = [
                        'User-Agent' => 'Feedly/1.0 (+http://feedly.com/fetcher.html; like FeedFetcher-Google)',
                        'Accept' => 'application/xml,application/rss+xml,application/atom+xml,application/rdf+xml,text/xml;q=0.9,text/html;q=0.8,*/*;q=0.7',
                        'Accept-Language' => 'en-US,en;q=0.9',
                        'Accept-Encoding' => 'gzip, deflate'
                    ];
                }
                
                $options['retry'] = $retry_count; // Update retry counter
                
                // Pause before retry to avoid rate limiting
                sleep(2); 
                
                return $this->curlFetch($url, $options);
            }
            
            return null;
        }
        
        // Check if content is empty
        if (empty($response)) {
            $error_msg = "Empty response body from cURL";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            return null;
        }
        
        return $response;
    }
}
