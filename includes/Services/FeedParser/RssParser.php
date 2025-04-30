<?php
/**
 * RSS Feed Parser
 *
 * @package AthenaAI\Services\FeedParser
 */

declare(strict_types=1);

namespace AthenaAI\Services\FeedParser;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parser for RSS feeds.
 */
class RssParser implements FeedParserInterface {
    /**
     * Verbose console output mode.
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

        $valid_types = ['log', 'info', 'warn', 'error', 'group', 'groupEnd'];
        $type = in_array($type, $valid_types) ? $type : 'log';
        
        // Behandle NULL-Werte
        if ($message === null) {
            $message = 'NULL message provided to console log';
            $type = 'warn';
        }
        
        // Eigene Implementierung von esc_js
        $message = strtr($message, [
            '\\' => '\\\\',
            "\n" => '\\n',
            "\r" => '\\r',
            "\t" => '\\t',
            '"' => '\\"',
            "'" => "\\'",
            '</' => '<\\/',
        ]);
        
        echo '<script>console.' . $type . '("Athena AI Feed: ' . $message . '");</script>';
    }

    /**
     * Check if this parser can handle the given content.
     *
     * @param string|null $content The feed content to check.
     * @return bool Whether this parser can handle the content.
     */
    public function canParse(?string $content): bool {
        // Behandle NULL-Werte
        if ($content === null || empty($content)) {
            return false;
        }
        
        // Simple check for RSS/XML format
        return (
            stripos($content, '<rss') !== false || 
            stripos($content, '<feed') !== false || 
            stripos($content, '<rdf:RDF') !== false ||
            stripos($content, '<?xml') !== false
        );
    }
    
    /**
     * Parse the feed content using SimplePie.
     *
     * @param string|null $content The feed content to parse.
     * @return array The parsed feed items.
     */
    public function parse(?string $content): array {
        $this->consoleLog('Parsing feed with RSS parser', 'group');
        
        // Behandle NULL-Werte
        if ($content === null || empty($content)) {
            $this->consoleLog('Feed content is null or empty', 'error');
            $this->consoleLog('', 'groupEnd');
            return [];
        }

        // Erkennung des Feed-Typs (RSS, Atom, RDF)
        $feed_type = $this->detectFeedType($content);
        $this->consoleLog("Erkannter Feed-Typ: $feed_type", 'info');
        
        // Vorverarbeitung für RDF-Feeds, da SimplePie manchmal Probleme mit ihnen hat
        if ($feed_type === 'rdf' && stripos($content, '<rdf:RDF') !== false) {
            $this->consoleLog("RDF-Feed erkannt, wende spezielle Optimierungen an", 'info');
            
            // Manchmal fehlen notwendige Namespace-Deklarationen 
            if (stripos($content, 'xmlns:dc=') === false) {
                $content = str_replace('<rdf:RDF', '<rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/"', $content);
            }
            
            // Stelle sicher, dass es ein wohlgeformtes XML ist
            libxml_use_internal_errors(true);
            $xml = @simplexml_load_string($content);
            
            if ($xml === false) {
                $this->consoleLog("RDF-XML-Parsing fehlgeschlagen, versuche Error-Correction", 'warn');
                // Versuche, die XML-Fehler zu beheben
                $error_info = libxml_get_errors();
                libxml_clear_errors();
                
                // Behebe typische RDF-XML-Probleme
                if (!empty($error_info)) {
                    foreach ($error_info as $error) {
                        $this->consoleLog("XML-Fehler: " . $error->message, 'warn');
                    }
                    
                    // Normalisiere XML-Struktur
                    $content = $this->normalizeXmlContent($content);
                }
            }
        }

        // Make sure SimplePie is loaded
        if (!class_exists('SimplePie')) {
            // Verwenden Sie einen relativen Pfad, wenn möglich
            if (file_exists(dirname(__FILE__, 4) . '/wp-includes/class-simplepie.php')) {
                require_once dirname(__FILE__, 4) . '/wp-includes/class-simplepie.php';
            } else {
                // Fallback: Versuchen, SimplePie direkt zu laden
                $this->consoleLog('SimplePie konnte nicht geladen werden', 'error');
                return [];
            }
        }
        
        // Create a new SimplePie instance
        $feed = new \SimplePie();
        $feed->set_raw_data($content);
        $feed->enable_cache(false);
        $feed->set_stupidly_fast(true);
        
        // Force all known formats
        $feed->force_feed(true);
        
        // Deaktiviere die Zeichensatzkonvertierung, um XML-Fehler zu vermeiden
        $feed->set_output_encoding('UTF-8');
        
        // Force the feed to be parsed
        $success = $feed->init();
        
        if (!$success) {
            $this->consoleLog('Failed to initialize SimplePie: ' . $feed->error(), 'error');
            
            // Bei RDF-Feeds: Versuche manuelle XML-Verarbeitung als Fallback
            if ($feed_type === 'rdf') {
                $this->consoleLog('Versuche manuelle RDF-Verarbeitung als Fallback', 'info');
                $items = $this->parseRdfManually($content);
                if (!empty($items)) {
                    $this->consoleLog('Manuelle RDF-Verarbeitung erfolgreich: ' . count($items) . ' Items gefunden', 'info');
                    $this->consoleLog('', 'groupEnd');
                    return $items;
                }
            }
            
            $this->consoleLog('', 'groupEnd');
            return [];
        }
        
        // Get the items
        $items = [];
        foreach ($feed->get_items() as $item) {
            $items[] = $this->convert_item_to_array($item);
        }
        
        $this->consoleLog("Parsed " . count($items) . " items from feed", 'info');
        $this->consoleLog('', 'groupEnd');
        
        return $items;
    }
    
    /**
     * Detectiert den Feed-Typ basierend auf dem Inhalt
     *
     * @param string $content Der Feed-Inhalt
     * @return string Der erkannte Feed-Typ ('rss', 'atom', 'rdf', 'unknown')
     */
    private function detectFeedType(string $content): string {
        // RDF-Feed (RDF Site Summary, RSS 1.0)
        if (stripos($content, '<rdf:RDF') !== false || 
            stripos($content, 'xmlns:rdf=') !== false) {
            return 'rdf';
        }
        
        // RSS-Feed (RSS 2.0, RSS 0.9x)
        if (stripos($content, '<rss') !== false) {
            return 'rss';
        }
        
        // Atom-Feed
        if (stripos($content, '<feed') !== false &&
            (stripos($content, 'xmlns="http://www.w3.org/2005/Atom"') !== false ||
             stripos($content, 'xmlns="http://purl.org/atom/') !== false)) {
            return 'atom';
        }
        
        // Standard-XML mit Feed-ähnlichen Elementen
        if (stripos($content, '<channel') !== false && 
            stripos($content, '<item') !== false) {
            return 'rss';
        }
        
        return 'unknown';
    }
    
    /**
     * Normalisiert XML-Inhalte, um häufige Probleme zu beheben
     *
     * @param string $content Der XML-Inhalt
     * @return string Der normalisierte XML-Inhalt
     */
    private function normalizeXmlContent(string $content): string {
        // Entferne invalide XML-Zeichen
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
        
        // Stelle sicher, dass es eine XML-Deklaration gibt
        if (stripos($content, '<?xml') === false) {
            $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $content;
        }
        
        // Stelle sicher, dass es kein Byte-Order-Mark (BOM) gibt
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Korrigiere fehlende Namespaces in RDF-Feeds
        if (stripos($content, '<rdf:RDF') !== false) {
            $rdf_ns_added = false;
            
            // Prüfe, ob rdf-Namespace fehlt
            if (stripos($content, 'xmlns:rdf=') === false) {
                $content = str_replace('<rdf:RDF', '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"', $content);
                $rdf_ns_added = true;
            }
            
            // Weitere häufig fehlende Namespaces
            $common_ns = [
                'dc' => 'http://purl.org/dc/elements/1.1/',
                'content' => 'http://purl.org/rss/1.0/modules/content/',
                'sy' => 'http://purl.org/rss/1.0/modules/syndication/',
            ];
            
            foreach ($common_ns as $prefix => $uri) {
                if (stripos($content, "xmlns:$prefix=") === false && 
                    stripos($content, "<$prefix:") !== false) {
                    
                    // Füge fehlenden Namespace hinzu
                    if ($rdf_ns_added) {
                        $content = str_replace(
                            'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"',
                            'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:' . $prefix . '="' . $uri . '"',
                            $content
                        );
                    } else {
                        $content = str_replace(
                            '<rdf:RDF',
                            '<rdf:RDF xmlns:' . $prefix . '="' . $uri . '"',
                            $content
                        );
                        $rdf_ns_added = true;
                    }
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Parst einen RDF-Feed manuell, wenn SimplePie fehlschlägt
     *
     * @param string $content Der RDF-Feed-Inhalt
     * @return array Die geparsten Feed-Items
     */
    private function parseRdfManually(string $content): array {
        $items = [];
        
        // Versuche DOM-basiertes Parsing
        $doc = new \DOMDocument();
        $success = @$doc->loadXML($content);
        
        if (!$success) {
            $this->consoleLog("Konnte RDF nicht als DOM laden", 'error');
            return [];
        }
        
        // Feed-Titel ermitteln
        $feed_title = '';
        $title_nodes = $doc->getElementsByTagName('title');
        if ($title_nodes->length > 0) {
            $feed_title = $title_nodes->item(0)->nodeValue;
        }
        
        // RDF-Items finden
        $item_nodes = $doc->getElementsByTagName('item');
        foreach ($item_nodes as $item_node) {
            $item = [
                'title' => '',
                'link' => '',
                'date' => '',
                'author' => '',
                'content' => '',
                'description' => '',
                'permalink' => '',
                'id' => '',
                'thumbnail' => null,
                'categories' => []
            ];
            
            // Item-Informationen extrahieren
            $child_nodes = $item_node->childNodes;
            foreach ($child_nodes as $child) {
                if ($child->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                
                $tag_name = strtolower($child->localName);
                $namespace = $child->namespaceURI;
                
                switch ($tag_name) {
                    case 'title':
                        $item['title'] = $child->nodeValue;
                        break;
                    case 'link':
                        $item['link'] = $child->nodeValue;
                        $item['permalink'] = $child->nodeValue;
                        break;
                    case 'description':
                        $item['description'] = $child->nodeValue;
                        $item['content'] = $child->nodeValue;
                        break;
                    case 'date':
                    case 'pubdate':
                    case 'issued':
                        $item['date'] = $child->nodeValue;
                        break;
                    case 'creator':
                        if ($namespace === 'http://purl.org/dc/elements/1.1/') {
                            $item['author'] = $child->nodeValue;
                        }
                        break;
                    case 'subject':
                        if ($namespace === 'http://purl.org/dc/elements/1.1/') {
                            $item['categories'][] = $child->nodeValue;
                        }
                        break;
                }
            }
            
            // Generiere ID falls nicht vorhanden
            if (empty($item['id']) && !empty($item['link'])) {
                $item['id'] = $item['link'];
            } elseif (empty($item['id'])) {
                $item['id'] = md5($item['title'] . (isset($item['date']) ? $item['date'] : ''));
            }
            
            // Versuche, ein Thumbnail zu finden
            if (empty($item['thumbnail']) && !empty($item['content'])) {
                preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $item['content'], $matches);
                if (!empty($matches[1])) {
                    $item['thumbnail'] = $matches[1];
                }
            }
            
            $items[] = $item;
        }
        
        return $items;
    }
    
    /**
     * Convert a SimplePie item to an array.
     *
     * @param \SimplePie_Item $item The SimplePie item.
     * @return array The item as an array.
     */
    private function convert_item_to_array(\SimplePie_Item $item): array {
        $enclosure = $item->get_enclosure();
        $thumbnail = null;
        
        if ($enclosure) {
            $thumbnail = $enclosure->get_link();
        }
        
        // Extract data
        $result = [
            'title' => $item->get_title(),
            'link' => $item->get_link(),
            'date' => $item->get_date('Y-m-d H:i:s'),
            'author' => $item->get_author() ? $item->get_author()->get_name() : '',
            'content' => $item->get_content(),
            'description' => $item->get_description(),
            'permalink' => $item->get_permalink(),
            'id' => $item->get_id(),
            'thumbnail' => $thumbnail,
            'categories' => []
        ];
        
        // Get categories
        $categories = $item->get_categories();
        if ($categories) {
            foreach ($categories as $category) {
                $result['categories'][] = $category->get_label();
            }
        }
        
        // Try to find thumbnail if not found in enclosure
        if (!$result['thumbnail']) {
            // Look for media:thumbnail
            $result['thumbnail'] = $this->find_thumbnail($item);
        }
        
        return $result;
    }
    
    /**
     * Find a thumbnail for an item.
     *
     * @param \SimplePie_Item $item The SimplePie item.
     * @return string|null The thumbnail URL or null if not found.
     */
    private function find_thumbnail(\SimplePie_Item $item): ?string {
        // 1. Try media:thumbnail
        $thumbnail = null;
        
        // Get the child elements
        if (method_exists($item, 'get_item_tags')) {
            // Look for media:thumbnail
            $media_ns = 'http://search.yahoo.com/mrss/';
            $thumbnail_tags = $item->get_item_tags($media_ns, 'thumbnail');
            
            if ($thumbnail_tags && isset($thumbnail_tags[0]['attribs']['']['url'])) {
                $thumbnail = $thumbnail_tags[0]['attribs']['']['url'];
            }
        }
        
        // 2. Try featured image in content
        if (!$thumbnail) {
            $content = $item->get_content();
            if ($content) {
                preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
                if (!empty($matches[1])) {
                    $thumbnail = $matches[1];
                }
            }
        }
        
        // 3. Try description
        if (!$thumbnail) {
            $description = $item->get_description();
            if ($description) {
                preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $description, $matches);
                if (!empty($matches[1])) {
                    $thumbnail = $matches[1];
                }
            }
        }
        
        // Validate and clean thumbnail URL
        if ($thumbnail) {
            $feed_link = $item->get_feed()->get_link();
            
            // Convert relative URLs to absolute
            if (!preg_match('/^https?:\/\//i', $thumbnail)) {
                // Eigene Implementierung von wp_parse_url
                $parsed_url = parse_url($feed_link);
                if ($parsed_url && isset($parsed_url['scheme'], $parsed_url['host'])) {
                    $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                    
                    if ($thumbnail !== null && strpos($thumbnail, '//') === 0) {
                        $thumbnail = $parsed_url['scheme'] . ':' . $thumbnail;
                    } elseif ($thumbnail !== null && strpos($thumbnail, '/') === 0) {
                        $thumbnail = $base_url . $thumbnail;
                    } else {
                        $thumbnail = $base_url . '/' . $thumbnail;
                    }
                }
            }
            
            // Eigene Implementierung von esc_url_raw
            // Einfache Validierung der URL
            if ($thumbnail !== null && filter_var($thumbnail, FILTER_VALIDATE_URL)) {
                return $thumbnail;
            } elseif ($thumbnail !== null) {
                // Grundlegende Bereinigung
                return preg_replace('/[^a-zA-Z0-9\/:._\-]/', '', $thumbnail);
            }
            
            return null;
        }
        
        return null;
    }
}
