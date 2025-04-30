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
        // Standard Strategien zum Umgehen von Bot-Blockern
        $strategies = [
            'modern_chrome' => function() use ($url) {
                // Strategie 1: Als moderner Chrome-Browser mit erweiterten Headern
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
                $response = $this->curlFetch($url, $chrome_options);
                
                // Wenn wir einen 403-Fehler erhalten, geben wir null zurück und überlassen es dem Hauptalgorithmus, 
                // auf Proxy-Strategien zu wechseln
                if ($this->lastResponseCode === 403) {
                    return null;
                }
                
                return $response;
            },
            
            'safari_browser' => function() use ($url) {
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
                return $this->curlFetch($url, $safari_options);
            },
            
            'firefox_browser' => function() use ($url) {
                // Strategie 3: Als Firefox-Browser (neu hinzugefügt)
                $firefox_options = $this->default_options;
                $firefox_options['headers'] = [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                    'Sec-Fetch-Dest' => 'document',
                    'Sec-Fetch-Mode' => 'navigate',
                    'Sec-Fetch-Site' => 'cross-site',
                    'Pragma' => 'no-cache',
                    'Cache-Control' => 'no-cache',
                    'TE' => 'trailers'
                ];
                
                $this->consoleLog("Strategie 3: Versuche Abruf mit Firefox-Browser-Emulation", 'info');
                return $this->curlFetch($url, $firefox_options);
            },
            
            'mobile_device' => function() use ($url) {
                // Strategie 4: Als mobiles Gerät (neu hinzugefügt)
                $mobile_options = $this->default_options;
                $mobile_options['headers'] = [
                    'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection' => 'keep-alive',
                    'Referer' => 'https://www.google.com/search?q=' . urlencode(parse_url($url, PHP_URL_HOST)),
                    'Upgrade-Insecure-Requests' => '1'
                ];
                
                $this->consoleLog("Strategie 4: Versuche Abruf als mobiles Gerät", 'info');
                return $this->curlFetch($url, $mobile_options);
            },
            
            'feed_reader' => function() use ($url) {
                // Strategie 5: Als Feedly-Feed-Reader (viele Websites erlauben Feed-Reader)
                $feedly_options = $this->default_options;
                $feedly_options['headers'] = [
                    'User-Agent' => 'Feedly/1.0 (+http://feedly.com/fetcher.html; like FeedFetcher-Google)',
                    'Accept' => 'application/xml,application/rss+xml,application/atom+xml,application/rdf+xml,text/xml;q=0.9,text/html;q=0.8,*/*;q=0.7',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Accept-Encoding' => 'gzip, deflate'
                ];
                
                $this->consoleLog("Strategie 5: Versuche Abruf als Feedly-Feed-Reader", 'info');
                return $this->curlFetch($url, $feedly_options);
            },
            
            'inoreader' => function() use ($url) {
                // Strategie 6: Als Inoreader (neu hinzugefügt)
                $inoreader_options = $this->default_options;
                $inoreader_options['headers'] = [
                    'User-Agent' => 'Mozilla/5.0 (compatible; Inoreader/1.0; +http://www.inoreader.com)',
                    'Accept' => 'application/rss+xml,application/rdf+xml,application/atom+xml,application/xml;q=0.9,text/xml;q=0.8,*/*;q=0.7',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate'
                ];
                
                $this->consoleLog("Strategie 6: Versuche Abruf als Inoreader", 'info');
                return $this->curlFetch($url, $inoreader_options);
            },
            
            'google_feedfetcher' => function() use ($url) {
                // Strategie 7: Google FeedFetcher emulieren (neu hinzugefügt)
                $google_options = $this->default_options;
                $google_options['headers'] = [
                    'User-Agent' => 'FeedFetcher-Google; (+http://www.google.com/feedfetcher.html)',
                    'Accept' => '*/*',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Connection' => 'close'
                ];
                
                $this->consoleLog("Strategie 7: Versuche Abruf als Google FeedFetcher", 'info');
                return $this->curlFetch($url, $google_options);
            },
            
            'cache_bust' => function() use ($url) {
                // Strategie 8: Cache-busting mit zufälligem Parameter
                $chrome_options = $this->default_options;
                $chrome_options['headers'] = [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache'
                ];
                
                $delimiter = (strpos($url, '?') !== false) ? '&' : '?';
                $cache_bust_url = $url . $delimiter . '_t=' . time() . '_r=' . mt_rand(1000, 9999);
                
                $this->consoleLog("Strategie 8: Versuche Cache-Busting mit URL: " . $cache_bust_url, 'info');
                return $this->curlFetch($cache_bust_url, $chrome_options);
            },
            
            'curl_direct' => function() use ($url) {
                // Strategie 9: Direkter cURL-Aufruf ohne viele Header
                $minimal_options = [
                    'timeout' => 30,
                    'redirection' => 5,
                    'sslverify' => false,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (compatible; FeedFetcher/1.0)',
                        'Accept' => '*/*'
                    ]
                ];
                
                $this->consoleLog("Strategie 9: Versuche minimalen cURL-Aufruf", 'info');
                return $this->curlFetch($url, $minimal_options);
            }
        ];
        
        // Speicherung des letzten Statuscodes für Fehlerbehandlung
        $this->lastResponseCode = 0;
        
        // Versuche jede Strategie nacheinander
        foreach ($strategies as $name => $strategy) {
            $this->consoleLog("Versuche Abruf-Strategie: $name", 'group');
            
            // Kurze Pause zwischen den Versuchen, um Rate-Limiting zu vermeiden
            if ($name !== 'modern_chrome') { // Keine Pause vor dem ersten Versuch
                sleep(mt_rand(1, 3));
            }
            
            $result = $strategy();
            
            if ($this->isValidFeedContent($result)) {
                $this->consoleLog("Strategie $name war erfolgreich!", 'info');
                $this->consoleLog("", 'groupEnd');
                return $result;
            }
            
            $this->consoleLog("Strategie $name war nicht erfolgreich. Status: " . $this->lastResponseCode, 'warn');
            $this->consoleLog("", 'groupEnd');
            
            // Wenn wir einen 403 oder einen anderen Blockierungscode erhalten haben, wechseln wir zu den Proxy-Methoden
            if ($this->lastResponseCode === 403 || $this->lastResponseCode === 429 || $this->lastResponseCode === 451) {
                $this->consoleLog("Blockierungsstatus " . $this->lastResponseCode . " erkannt, wechsle zu Proxy-Methoden", 'info');
                break;
            }
        }
        
        // Wenn die Standard-Strategien fehlgeschlagen sind oder wir 403 erhalten haben, 
        // versuchen wir es mit Proxy-basierten Ansätzen
        $this->consoleLog("Verwende Proxy-basierte Strategien für die URL: " . $url, 'info');
        
        // Proxy-Strategien anwenden
        $proxy_result = $this->tryProxyStrategies($url);
        if ($proxy_result !== null) {
            return $proxy_result;
        }
        
        // Wenn alle fehlgeschlagen sind, versuchen wir es mit einem letzten Fallback
        $fallback_options = $this->default_options;
        // Array mit verschiedenen realistischen User-Agents
        $user_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 OPR/110.0.0.0',
        ];
        
        // Zufälligen User-Agent auswählen
        $fallback_options['headers']['User-Agent'] = $user_agents[array_rand($user_agents)];
        $fallback_options['headers']['Accept'] = 'application/rss+xml, application/atom+xml, application/rdf+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.7';
        
        // Cache-Busting-Parameter hinzufügen
        $delimiter = (strpos($url, '?') !== false) ? '&' : '?';
        $fallback_url = $url . $delimiter . 'rand=' . md5(mt_rand() . time());
        
        return $this->curlFetch($fallback_url, $fallback_options);
    }
    
    /**
     * Letzter erhaltener HTTP-Status-Code
     *
     * @var int
     */
    private int $lastResponseCode = 0;
    
    /**
     * Versucht verschiedene Proxy-Strategien, um Feeds abzurufen
     * 
     * @param string $url Die Feed-URL
     * @return string|null XML-Feed oder null bei Fehlern
     */
    private function tryProxyStrategies(string $url): ?string {
        $this->consoleLog("Beginne mit Proxy-Strategien für URL: " . $url, 'group');
        
        // Versuch 1: RSS2JSON Proxy Service
        $this->consoleLog("Versuch 1: RSS2JSON API", 'info');
        $rss2json_result = $this->fetchViaRss2Json($url);
        if ($this->isValidFeedContent($rss2json_result)) {
            $this->consoleLog("RSS2JSON war erfolgreich!", 'info');
            $this->consoleLog("", 'groupEnd');
            return $rss2json_result;
        }
        
        // Versuch 2: CORS Proxy
        $this->consoleLog("Versuch 2: CORS Proxy", 'info');
        $cors_result = $this->fetchViaCorsProxy($url);
        if ($this->isValidFeedContent($cors_result)) {
            $this->consoleLog("CORS Proxy war erfolgreich!", 'info');
            $this->consoleLog("", 'groupEnd');
            return $cors_result;
        }
        
        // Versuch 3: FetchRSS
        $this->consoleLog("Versuch 3: FetchRSS", 'info');
        $fetchrss_result = $this->fetchViaRssBridge($url);
        if ($this->isValidFeedContent($fetchrss_result)) {
            $this->consoleLog("FetchRSS war erfolgreich!", 'info');
            $this->consoleLog("", 'groupEnd');
            return $fetchrss_result;
        }
        
        $this->consoleLog("Alle Proxy-Strategien sind fehlgeschlagen", 'error');
        $this->consoleLog("", 'groupEnd');
        return null;
    }
    
    /**
     * Nutzt den CORS Proxy, um Feeds abzurufen
     * 
     * @param string $url Die Feed-URL
     * @return string|null XML-Feed oder null bei Fehlern
     */
    private function fetchViaCorsProxy(string $url): ?string {
        $this->consoleLog("Versuche Abruf über CORS Proxy", 'info');
        
        // Verschiedene CORS Proxies ausprobieren
        $cors_proxies = [
            'https://corsproxy.io/?',
            'https://api.allorigins.win/raw?url=',
            'https://thingproxy.freeboard.io/fetch/'
        ];
        
        foreach ($cors_proxies as $proxy) {
            $proxy_url = $proxy . urlencode($url);
            
            $options = [
                'timeout' => 30,
                'redirection' => 5,
                'headers' => [
                    'Accept' => 'application/xml, application/rss+xml, text/xml, */*',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            ];
            
            $response = $this->curlFetch($proxy_url, $options);
            if ($this->isValidFeedContent($response)) {
                return $response;
            }
            
            // Kurze Pause zwischen den Proxy-Versuchen
            sleep(1);
        }
        
        return null;
    }
    
    /**
     * Nutzt den RSS2JSON Proxy Service, um Feeds abzurufen
     * 
     * @param string $url Die Feed-URL
     * @return string|null XML-Feed oder null bei Fehlern
     */
    private function fetchViaRss2Json(string $url): ?string {
        $this->consoleLog("Versuche Abruf über RSS2JSON API", 'info');
        
        // API-URL für rss2json.com - limitiert auf 100 Anfragen/Tag in der kostenlosen Version
        $api_url = 'https://api.rss2json.com/v1/api.json?rss_url=' . urlencode($url);
        
        $options = [
            'timeout' => 30,
            'redirection' => 5,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
            ]
        ];
        
        $response = $this->curlFetch($api_url, $options);
        if (empty($response)) {
            return null;
        }
        
        // Parsen der JSON-Antwort
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['items']) || !is_array($data['items'])) {
            $this->consoleLog("RSS2JSON lieferte ungültiges JSON oder keine Items zurück", 'error');
            return null;
        }
        
        // Konvertieren zu XML für die Konsistenz mit dem Rest des Systems
        $xml = $this->convertJsonFeedToXml($data);
        
        return $xml;
    }
    
    /**
     * Nutzt RSS-Bridge/FetchRSS als Proxy für schwer zugängliche Feeds
     * 
     * @param string $url Die Feed-URL
     * @return string|null XML-Feed oder null bei Fehlern
     */
    private function fetchViaRssBridge(string $url): ?string {
        $this->consoleLog("Versuche Abruf über FetchRSS", 'info');
        
        // Öffentlicher FetchRSS-Dienst (vereinfachter Zugriff)
        $fetchrss_url = 'https://fetchrss.com/rss/' . urlencode(base64_encode($url));
        
        $options = [
            'timeout' => 30,
            'redirection' => 5,
            'headers' => [
                'Accept' => 'application/xml, application/rss+xml, text/xml',
                'User-Agent' => 'Mozilla/5.0 (compatible; FeedFetcher/1.0)'
            ]
        ];
        
        return $this->curlFetch($fetchrss_url, $options);
    }
    
    /**
     * Konvertiert einen JSON-Feed in XML für Konsistenz
     * 
     * @param array $data Die JSON-Daten
     * @return string XML-Feed
     */
    private function convertJsonFeedToXml(array $data): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<rss version="2.0">' . PHP_EOL;
        $xml .= '  <channel>' . PHP_EOL;
        
        // Feed-Metadaten
        $xml .= '    <title>' . htmlspecialchars($data['feed']['title'] ?? 'Unknown Feed', ENT_QUOTES) . '</title>' . PHP_EOL;
        $xml .= '    <link>' . htmlspecialchars($data['feed']['link'] ?? '#', ENT_QUOTES) . '</link>' . PHP_EOL;
        $xml .= '    <description>' . htmlspecialchars($data['feed']['description'] ?? '', ENT_QUOTES) . '</description>' . PHP_EOL;
        
        // Feed-Items
        foreach ($data['items'] as $item) {
            $xml .= '    <item>' . PHP_EOL;
            $xml .= '      <title>' . htmlspecialchars($item['title'] ?? '', ENT_QUOTES) . '</title>' . PHP_EOL;
            $xml .= '      <link>' . htmlspecialchars($item['link'] ?? '#', ENT_QUOTES) . '</link>' . PHP_EOL;
            
            if (isset($item['pubDate'])) {
                $xml .= '      <pubDate>' . htmlspecialchars($item['pubDate'], ENT_QUOTES) . '</pubDate>' . PHP_EOL;
            }
            
            // Content oder Description
            $content = $item['content'] ?? $item['description'] ?? '';
            $xml .= '      <description>' . htmlspecialchars($content, ENT_QUOTES) . '</description>' . PHP_EOL;
            
            // Optional: Kategorien
            if (isset($item['categories']) && is_array($item['categories'])) {
                foreach ($item['categories'] as $category) {
                    $xml .= '      <category>' . htmlspecialchars($category, ENT_QUOTES) . '</category>' . PHP_EOL;
                }
            }
            
            // GUID
            $xml .= '      <guid>' . htmlspecialchars($item['guid'] ?? $item['link'] ?? uniqid('item_'), ENT_QUOTES) . '</guid>' . PHP_EOL;
            
            $xml .= '    </item>' . PHP_EOL;
        }
        
        $xml .= '  </channel>' . PHP_EOL;
        $xml .= '</rss>';
        
        return $xml;
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
            strpos($content, '<channel') !== false ||
            strpos($content, '<item>') !== false ||
            strpos($content, '<entry>') !== false
        );
        
        if ($has_xml_structure) {
            // Zusätzliche Prüfung für gültiges XML
            libxml_use_internal_errors(true);
            $doc = simplexml_load_string($content);
            $xml_errors = libxml_get_errors();
            libxml_clear_errors();
            
            // Wenn es ein wohlgeformtes XML ohne schwerwiegende Fehler ist
            if ($doc !== false && (count($xml_errors) === 0 || $this->hasOnlyMinorXmlErrors($xml_errors))) {
                return true;
            }
            
            // Selbst wenn XML-Parsing fehlschlägt, könnte es ein Feed sein mit kleinen Fehlern
            if (preg_match('/<item>.*?<title>.*?<\/title>.*?<\/item>/s', $content) || 
                preg_match('/<entry>.*?<title>.*?<\/title>.*?<\/entry>/s', $content)) {
                return true;
            }
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
                'javascript is required',
                'browser check',
                'ddos protection',
                'checking your browser',
                'please wait',
                'attention required',
                'error code:',
                'challenge page',
                'Bot detected'
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
     * Prüft, ob XML-Fehler nur kleinere Probleme darstellen, die einen Feed nicht ungültig machen
     * 
     * @param array $xml_errors Die XML-Fehler von libxml
     * @return bool True, wenn nur kleinere Fehler vorhanden sind
     */
    private function hasOnlyMinorXmlErrors(array $xml_errors): bool {
        foreach ($xml_errors as $error) {
            // Schwerwiegende Fehler (Level 3) machen den Feed ungültig
            if ($error->level === LIBXML_ERR_FATAL) {
                return false;
            }
            
            // Ignoriere Warnungen über undefinierte Entitäten, die in Feeds häufig vorkommen
            if (strpos($error->message, 'Entity') !== false && strpos($error->message, 'not defined') !== false) {
                continue;
            }
            
            // Ignoriere Probleme mit Encoding-Deklarationen
            if (strpos($error->message, 'Encoding') !== false) {
                continue;
            }
        }
        
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
        $this->lastResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Speichere den HTTP-Code
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        // Log response info for debugging
        $this->consoleLog("cURL response code: {$this->lastResponseCode}, content-type: {$content_type}", 'info');
        
        curl_close($ch);
        
        if ($response === false) {
            $error_msg = "cURL error: {$error}";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            return null;
        }
        
        if ($this->lastResponseCode === 403) {
            $error_msg = "403 Forbidden: Zugriff verweigert";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            return null; // Null zurückgeben bei 403, damit wir zu Proxy-Methoden wechseln können
        }
        
        if ($this->lastResponseCode !== 200) {
            $error_msg = "Invalid response code from cURL: {$this->lastResponseCode}";
            $this->set_last_error($error_msg);
            $this->consoleLog($error_msg, 'error');
            
            // Wir geben bei Nicht-200-Statuscodes null zurück, damit der aufrufende Code entscheiden kann
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
