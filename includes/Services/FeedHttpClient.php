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
     * @param string $error The error message
     * @return self
     */
    private function set_last_error(string $error): self {
        $this->last_error = $error;
        return $this;
    }

    /**
     * Prüft, ob eine URL zu socialmediaexaminer.com gehört
     * 
     * @param string $url Die zu prüfende URL
     * @return bool True, wenn die URL zu socialmediaexaminer.com gehört, sonst false
     */
    private function isSocialMediaExaminerUrl(string $url): bool {
        return strpos($url, 'socialmediaexaminer.com') !== false;
    }
    
    /**
     * Spezialisierte Methode zum Abrufen von socialmediaexaminer.com Feeds
     * 
     * Diese Methode umgeht die Feed-Blockierung, indem sie die Hauptseite abruft
     * und die Artikel direkt aus dem HTML extrahiert.
     * 
     * @param string $url Die Feed-URL
     * @return string|false Der Feed-Inhalt oder false bei Fehler
     */
    private function fetchSocialMediaExaminerFeed(string $url): string|false {
        $this->consoleLog("Using specialized method for Social Media Examiner feed", 'info');
        
        // Verwende die Hauptseite statt des Feeds
        $main_url = 'https://www.socialmediaexaminer.com/';
        
        // Spezielle Header für Social Media Examiner
        $options = [
            'timeout' => 60, // Längeres Timeout
            'redirection' => 10,
            'sslverify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,de;q=0.8',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1'
            ]
        ];
        
        // Verwende cURL für maximale Kontrolle
        $ch = curl_init($main_url);
        
        // Grundlegende cURL-Optionen
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $options['redirection']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['sslverify']);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        
        // Header setzen
        $headers = [];
        foreach ($options['headers'] as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // cURL-Anfrage ausführen
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        curl_close($ch);
        
        // Log-Ausgabe für Debugging
        $this->consoleLog("cURL response code: {$status_code}, content-type: {$content_type}", 'info');
        
        if ($response === false) {
            $error_msg = "cURL error: {$error}";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            return false;
        }
        
        if ($status_code !== 200) {
            $error_msg = "Invalid response code from cURL: {$status_code}";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            return false;
        }
        
        // Überprüfe, ob der Inhalt leer ist
        if (empty($response)) {
            $error_msg = "Empty response body from cURL";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            return false;
        }
        
        // Extrahiere Artikel aus dem HTML und erstelle einen XML-Feed
        return $this->convertHtmlToFeed($response, $main_url);
    }
    
    /**
     * Konvertiert HTML von socialmediaexaminer.com in einen XML-Feed
     * 
     * @param string $html Der HTML-Inhalt
     * @param string $base_url Die Basis-URL für relative Links
     * @return string Der generierte XML-Feed
     */
    private function convertHtmlToFeed(string $html, string $base_url): string {
        $this->consoleLog("Converting HTML to feed format", 'info');
        
        // Erstelle ein DOMDocument-Objekt
        $dom = new \DOMDocument();
        
        // Unterdrücke Fehler beim Parsen von ungültigem HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        // Erstelle einen DOMXPath für einfachere Abfragen
        $xpath = new \DOMXPath($dom);
        
        // Suche nach Artikeln (passe die XPath-Abfrage an die Struktur der Website an)
        $articles = $xpath->query('//article | //div[contains(@class, "post") or contains(@class, "article")] | //div[contains(@class, "entry")]');
        
        // Wenn keine Artikel gefunden wurden, versuche es mit einer allgemeineren Abfrage
        if ($articles->length === 0) {
            $articles = $xpath->query('//div[contains(@class, "content")] | //div[contains(@class, "main")]//a[contains(@href, "/20")]/..');
        }
        
        $this->consoleLog("Found {$articles->length} potential articles", 'info');
        
        // Erstelle einen XML-Feed im RSS-Format
        $feed = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>';
        $feed .= '<title>Social Media Examiner</title>';
        $feed .= '<link>' . htmlspecialchars($base_url) . '</link>';
        $feed .= '<description>Social Media Marketing Articles</description>';
        
        $count = 0;
        
        // Durchlaufe alle gefundenen Artikel
        foreach ($articles as $article) {
            // Versuche, den Titel zu finden
            $title_elem = $xpath->query('.//h1 | .//h2 | .//h3 | .//h4 | .//a[contains(@class, "title")]', $article)->item(0);
            $title = $title_elem ? trim($title_elem->textContent) : 'Untitled Article';
            
            // Versuche, den Link zu finden
            $link = '';
            $link_elem = $xpath->query('.//a[@href]', $article)->item(0);
            if ($link_elem) {
                $link = $link_elem->getAttribute('href');
                // Konvertiere relative URLs zu absoluten URLs
                if (strpos($link, 'http') !== 0) {
                    $link = rtrim($base_url, '/') . '/' . ltrim($link, '/');
                }
            }
            
            // Versuche, die Beschreibung zu finden
            $desc_elem = $xpath->query('.//p | .//div[contains(@class, "excerpt") or contains(@class, "summary")]', $article)->item(0);
            $description = $desc_elem ? trim($desc_elem->textContent) : '';
            
            // Versuche, das Datum zu finden
            $date_elem = $xpath->query('.//time | .//*[contains(@class, "date")]', $article)->item(0);
            $date = $date_elem ? $date_elem->textContent : date('r');
            
            // Erstelle eine eindeutige GUID für diesen Artikel
            $guid = !empty($link) ? $link : md5($title . $description);
            
            // Füge den Artikel zum Feed hinzu, wenn ein Titel und ein Link vorhanden sind
            if (!empty($title) && !empty($link)) {
                $feed .= '<item>';
                $feed .= '<title>' . htmlspecialchars($title) . '</title>';
                $feed .= '<link>' . htmlspecialchars($link) . '</link>';
                $feed .= '<guid>' . htmlspecialchars($guid) . '</guid>';
                $feed .= '<pubDate>' . htmlspecialchars($date) . '</pubDate>';
                $feed .= '<description>' . htmlspecialchars($description) . '</description>';
                $feed .= '</item>';
                $count++;
            }
        }
        
        $feed .= '</channel></rss>';
        
        $this->consoleLog("Generated feed with {$count} items", 'info');
        
        return $feed;
    }

    /**
     * Fetch content from a URL.
     *
     * @param string $url     The URL to fetch.
     * @param array  $options Additional options to merge with defaults.
     * @return string|false The fetched content or false on failure.
     */
    public function fetch(string $url, array $options = []) {
        // Reset last error
        $this->set_last_error('');
        
        // Log fetching attempt
        $this->consoleLog("Fetching feed from URL: {$url}", 'info');

        // Basic URL validation
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            $error = "Invalid URL format: {$url}";
            $this->set_last_error($error);
            $this->consoleLog($error, 'error');
            return false;
        }
        
        // Spezialbehandlung für Social Media Examiner
        if ($this->isSocialMediaExaminerUrl($url)) {
            return $this->fetchSocialMediaExaminerFeed($url);
        }
        
        // Merge default options with provided options
        $request_options = array_merge($this->default_options, $options);
        
        // Add random delay to avoid rate limiting (between 1-3 seconds)
        usleep(mt_rand(1000000, 3000000));
        
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
                    $this->set_last_error("WordPress error fetching feed: {$error_message}");
                    $this->consoleLog("WordPress error fetching feed: {$error_message}", 'error');
                    return false;
                }
            }
            
            // Check response code
            if (function_exists('wp_remote_retrieve_response_code')) {
                $status_code = \wp_remote_retrieve_response_code($response);
                if ($status_code !== 200) {
                    $error = "Invalid response code: {$status_code}";
                    $this->set_last_error($error);
                    $this->consoleLog($error, 'error');
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
            $error = "Empty response body";
            $this->set_last_error($error);
            $this->consoleLog($error, 'error');
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
            $error = "cURL is not available";
            $this->set_last_error($error);
            $this->consoleLog($error, 'error');
            return false;
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
            return false;
        }
        
        if ($status_code !== 200) {
            $error_msg = "Invalid response code from cURL: {$status_code}";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            
            // Wenn wir einen 403 Forbidden-Fehler erhalten, versuchen wir es mit einem alternativen User-Agent
            if ($status_code === 403 && (!isset($options['retry']) || $options['retry'] < 2)) {
                $retry_count = isset($options['retry']) ? $options['retry'] + 1 : 1;
                $this->consoleLog("Received 403 Forbidden, trying with alternative approach (attempt {$retry_count})", 'warn');
                
                // Verschiedene Strategien je nach Wiederholungsversuch
                if ($retry_count === 1) {
                    // Erster Wiederholungsversuch: Anderen Browser-User-Agent verwenden
                    $options['headers']['User-Agent'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Safari/605.1.15';
                    $options['headers']['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
                    $options['headers']['Accept-Language'] = 'en-US,en;q=0.9';
                    // Entferne den Google-Referer, da dieser blockiert werden könnte
                    if (isset($options['headers']['Referer']) && strpos($options['headers']['Referer'], 'google.com') !== false) {
                        unset($options['headers']['Referer']);
                    }
                } else {
                    // Zweiter Wiederholungsversuch: Direkter Zugriff ohne spezielle Header
                    $options['headers'] = [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                        'Accept' => '*/*'
                    ];
                }
                
                $options['retry'] = $retry_count; // Aktualisiere den Wiederholungszähler
                
                // Kurze Pause vor dem Wiederholungsversuch
                usleep(mt_rand(3000000, 6000000)); // 3-6 Sekunden Pause
                
                return $this->curlFetch($url, $options);
            }
            
            return false;
        }
        
        // Überprüfe, ob der Inhalt leer ist
        if (empty($response)) {
            $error_msg = "Empty response body from cURL";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            return false;
        }
        
        return $response;
    }
}
