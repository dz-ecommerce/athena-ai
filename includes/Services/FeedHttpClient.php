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
            'wsj.com',
            'nytimes.com',
            'bloomberg.com',
            'washingtonpost.com',
            'economist.com',
            'wired.com',
            'forbes.com',
            'techcrunch.com',
            'mashable.com'
            // Diese Liste kann bei Bedarf erweitert werden
        ];
        
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            // Fallback: Wenn parse_url fehlschlägt, verwende die alte Methode
            foreach ($problem_domains as $domain) {
                if (strpos($url, $domain) !== false) {
                    $this->consoleLog("Bekannte problematische Domain erkannt: {$domain}", 'info');
                    return true;
                }
            }
            return false;
        }
        
        // Host ohne www-Präfix prüfen
        $host = preg_replace('/^www\./i', '', $host);
        
        foreach ($problem_domains as $domain) {
            // Prüfung mit endsWith-Logik für Subdomains
            if ($host === $domain || preg_match('/\.' . preg_quote($domain, '/') . '$/', $host)) {
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
        
        // Prüfe auf bekannte problematische URLs und behandle sie speziell
        if ($this->isProblemURL($url)) {
            $this->consoleLog("Verwende spezielle Handling-Strategie für problematische URL: {$url}", 'info');
            return $this->fetchWithAntiBlockStrategy($url, $options);
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
     * Fetch content from a URL with anti-block strategies
     * 
     * Verwendet verschiedene Techniken, um Webseiten abzurufen, die Bot-Blocker einsetzen
     * 
     * @param string $url     The URL to fetch
     * @param array|null $options Request options
     * @return string|null The fetched content or null on failure
     */
    private function fetchWithAntiBlockStrategy(string $url, ?array $options = null): ?string {
        // Verschiedene Strategien nacheinander versuchen
        
        // Strategie 1: Als moderner Browser mit vollen Chrome-Headers
        $chrome_options = $this->default_options;
        $chrome_options['headers'] = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9,de;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Referer' => 'https://www.google.com/search?q=' . urlencode(parse_url($url, PHP_URL_HOST)),
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'cross-site',
            'Sec-Fetch-User' => '?1',
            'sec-ch-ua' => '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'DNT' => '1'
        ];
        
        $this->consoleLog("Strategie 1: Versuche Abruf mit Chrome-Browser-Emulation", 'info');
        $result = $this->curlFetch($url, $chrome_options);
        if ($this->isValidFeedContent($result)) {
            return $result;
        }
        
        // Pause einlegen um Schutzmaßnahmen zu vermeiden
        sleep(1);
        
        // Strategie 2: Als Safari-Browser
        $safari_options = $this->default_options;
        $safari_options['headers'] = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Referer' => 'https://www.bing.com/search?q=' . urlencode(parse_url($url, PHP_URL_HOST)),
            'Upgrade-Insecure-Requests' => '1',
            'DNT' => '1'
        ];
        
        $this->consoleLog("Strategie 2: Versuche Abruf mit Safari-Browser-Emulation", 'info');
        $result = $this->curlFetch($url, $safari_options);
        if ($this->isValidFeedContent($result)) {
            return $result;
        }
        
        // Pause einlegen um Schutzmaßnahmen zu vermeiden
        sleep(2);
        
        // Strategie 3: Als Feedly-Feed-Reader (viele Websites erlauben Feed-Reader)
        $feedly_options = $this->default_options;
        $feedly_options['headers'] = [
            'User-Agent' => 'Feedly/1.0 (+http://feedly.com/fetcher.html; like FeedFetcher-Google)',
            'Accept' => 'application/xml,application/rss+xml,application/atom+xml,application/rdf+xml,text/xml;q=0.9,text/html;q=0.8,*/*;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate'
        ];
        
        $this->consoleLog("Strategie 3: Versuche Abruf als Feedly-Feed-Reader", 'info');
        $result = $this->curlFetch($url, $feedly_options);
        if ($this->isValidFeedContent($result)) {
            return $result;
        }
        
        // Strategie 4: Cache-busting mit zufälligem Parameter
        $delimiter = (strpos($url, '?') !== false) ? '&' : '?';
        $cache_bust_url = $url . $delimiter . '_t=' . time() . '_r=' . mt_rand(1000, 9999);
        
        $this->consoleLog("Strategie 4: Versuche Cache-Busting mit URL: " . $cache_bust_url, 'info');
        $result = $this->curlFetch($cache_bust_url, $chrome_options);
        if ($this->isValidFeedContent($result)) {
            return $result;
        }
        
        // Wenn nichts funktioniert hat, melden wir den Fehler
        $this->set_last_error("Alle Feed-Abruf-Strategien sind fehlgeschlagen für URL: " . $url);
        $this->consoleLog("Alle Feed-Abruf-Strategien fehlgeschlagen", 'error');
        
        return null;
    }
    
    /**
     * Überprüft, ob der Inhalt ein gültiger Feed sein kann
     * 
     * @param string|null $content Der zu prüfende Inhalt
     * @return bool True, wenn der Inhalt ein gültiger Feed sein könnte
     */
    private function isValidFeedContent(?string $content): bool {
        if (empty($content)) {
            return false;
        }
        
        // Prüfe nach XML-Struktur (RSS, Atom, etc.)
        $has_xml_structure = (
            strpos($content, '<?xml') !== false || 
            strpos($content, '<rss') !== false || 
            strpos($content, '<feed') !== false ||
            strpos($content, '<channel') !== false
        );
        
        if ($has_xml_structure) {
            return true;
        }
        
        // Prüfe nach JSON-Feed-Struktur
        if (strpos($content, '{') === 0) {
            $json = @json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && 
                (isset($json['items']) || isset($json['feed']) || isset($json['entries']))
            ) {
                return true;
            }
        }
        
        // Wenn der Content HTML ist, prüfe, ob er Bot-Block-Indikatoren enthält
        if (strpos($content, '<html') !== false) {
            $bot_block_indicators = [
                'captcha',
                'robot',
                'automated',
                'access denied',
                'blocked',
                'security check',
                'cloudflare',
                'detected unusual activity',
                'detected unusual traffic',
                'human verification',
                'please enable cookies',
                'javascript is required'
            ];
            
            foreach ($bot_block_indicators as $indicator) {
                if (stripos($content, $indicator) !== false) {
                    $this->consoleLog("Bot-Blocker erkannt: '$indicator'", 'warn');
                    return false;
                }
            }
        }
        
        // Wenn der Inhalt sehr kurz ist, ist es wahrscheinlich kein Feed
        if (strlen($content) < 500) {
            return false;
        }
        
        // In anderen Fällen nehmen wir an, dass es ein gültiger Inhalt sein könnte
        return true;
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
