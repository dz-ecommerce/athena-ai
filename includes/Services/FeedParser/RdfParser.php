<?php
/**
 * RDF Feed Parser
 *
 * @package AthenaAI\Services\FeedParser
 */

declare(strict_types=1);

namespace AthenaAI\Services\FeedParser;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parser speziell für RDF-Feeds (RSS 1.0).
 */
class RdfParser implements FeedParserInterface {
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
        
        echo '<script>console.' . $type . '("Athena AI RDF Parser: ' . $message . '");</script>';
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
        
        // Spezifische Prüfung für RDF-Feeds
        $is_rdf = (
            stripos($content, '<rdf:RDF') !== false || 
            stripos($content, 'xmlns:rdf=') !== false ||
            // Weitere RDF-spezifische Marker
            stripos($content, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#') !== false ||
            // RDF-Items mit about-Attribut
            preg_match('/<item\s+rdf:about=/', $content)
        );
        
        return $is_rdf;
    }
    
    /**
     * Parse the RDF feed content.
     *
     * @param string|null $content The feed content to parse.
     * @return array The parsed feed items.
     */
    public function parse(?string $content): array {
        $this->consoleLog('Parsing feed with RDF parser', 'group');
        
        // Behandle NULL-Werte
        if ($content === null || empty($content)) {
            $this->consoleLog('Feed content is null or empty', 'error');
            $this->consoleLog('', 'groupEnd');
            return [];
        }
        
        // Normalisiere XML-Inhalt und Namespaces
        $content = $this->normalizeRdfContent($content);
        
        // Versuche zunächst mit SimplePie (wenn verfügbar)
        $simplepie_items = $this->parseWithSimplePie($content);
        if (!empty($simplepie_items)) {
            $this->consoleLog('SimplePie parsing successful', 'info');
            $this->consoleLog('', 'groupEnd');
            return $simplepie_items;
        }
        
        // Fallback: Verwende DOM/XPath
        $this->consoleLog('Falling back to DOM/XPath parsing', 'info');
        $items = $this->parseWithDomXpath($content);
        
        $this->consoleLog("Parsed " . count($items) . " items from RDF feed", 'info');
        $this->consoleLog('', 'groupEnd');
        
        return $items;
    }
    
    /**
     * Normalisiert den RDF-Feed-Inhalt
     *
     * @param string $content Der Feed-Inhalt
     * @return string Der normalisierte Feed-Inhalt
     */
    private function normalizeRdfContent(string $content): string {
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
                'admin' => 'http://webns.net/mvcb/',
                'cc' => 'http://web.resource.org/cc/'
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
     * Parst den Feed mit SimplePie
     *
     * @param string $content Der Feed-Inhalt
     * @return array Die geparsten Feed-Items
     */
    private function parseWithSimplePie(string $content): array {
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
        $feed->force_feed(true);
        $feed->set_output_encoding('UTF-8');
        
        // Force the feed to be parsed
        $success = $feed->init();
        
        if (!$success) {
            $this->consoleLog('Failed to initialize SimplePie: ' . $feed->error(), 'error');
            return [];
        }
        
        // Get the items
        $items = [];
        foreach ($feed->get_items() as $item) {
            $items[] = $this->convertSimplePieItemToArray($item);
        }
        
        return $items;
    }
    
    /**
     * Parst den Feed mit DOM und XPath
     *
     * @param string $content Der Feed-Inhalt
     * @return array Die geparsten Feed-Items
     */
    private function parseWithDomXpath(string $content): array {
        $items = [];
        
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $success = @$doc->loadXML($content);
        
        if (!$success) {
            $this->consoleLog("Could not load XML with DOM", 'error');
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $this->consoleLog("XML error: {$error->message} at line {$error->line}", 'error');
            }
            libxml_clear_errors();
            return [];
        }
        
        $xpath = new \DOMXPath($doc);
        
        // Registriere die Namespaces
        $namespaces = [
            'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'dc' => 'http://purl.org/dc/elements/1.1/',
            'content' => 'http://purl.org/rss/1.0/modules/content/',
            'sy' => 'http://purl.org/rss/1.0/modules/syndication/',
            'admin' => 'http://webns.net/mvcb/',
            'cc' => 'http://web.resource.org/cc/'
        ];
        
        foreach ($namespaces as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }
        
        // Erhalte Feed-Metadaten
        $feed_title = $this->getNodeValue($xpath, '//channel/title');
        $feed_link = $this->getNodeValue($xpath, '//channel/link');
        
        // Versuche verschiedene XPath-Abfragen für Items
        $queries = [
            '//item', // Standard-RDF-Items
            '//rdf:RDF/item', // Alternative Struktur
            '//*[local-name()="item"]' // Generischer Ansatz
        ];
        
        $item_nodes = null;
        
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes && $nodes->length > 0) {
                $item_nodes = $nodes;
                $this->consoleLog("Found {$nodes->length} items with query: {$query}", 'info');
                break;
            }
        }
        
        if (!$item_nodes || $item_nodes->length === 0) {
            $this->consoleLog("No item nodes found in RDF feed", 'error');
            return [];
        }
        
        // Durchlaufe alle gefundenen Items
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
            
            // URL aus dem about-Attribut extrahieren
            $about = $item_node->getAttributeNS($namespaces['rdf'], 'about');
            if ($about) {
                $item['link'] = $about;
                $item['permalink'] = $about;
                $item['id'] = $about;
            }
            
            // Extrahiere Item-Daten mit XPath relativ zum Item-Knoten
            $item['title'] = $this->getNodeValueRelative($xpath, './title', $item_node);
            
            // Link extrahieren wenn nicht aus about-Attribut
            if (empty($item['link'])) {
                $item['link'] = $this->getNodeValueRelative($xpath, './link', $item_node);
                $item['permalink'] = $item['link'];
                $item['id'] = $item['link'];
            }
            
            $item['description'] = $this->getNodeValueRelative($xpath, './description', $item_node);
            $item['content'] = $this->getNodeValueRelative($xpath, './content:encoded', $item_node) ?: $item['description'];
            
            // Versuche verschiedene Datumsformate
            $item['date'] = $this->getNodeValueRelative($xpath, './dc:date', $item_node) ?: 
                       $this->getNodeValueRelative($xpath, './pubDate', $item_node) ?: 
                       $this->getNodeValueRelative($xpath, './date', $item_node);
            
            $item['author'] = $this->getNodeValueRelative($xpath, './dc:creator', $item_node) ?: 
                         $this->getNodeValueRelative($xpath, './author', $item_node);
            
            // Kategorien extrahieren
            $category_nodes = $xpath->query('./dc:subject', $item_node);
            if ($category_nodes) {
                foreach ($category_nodes as $cat_node) {
                    $item['categories'][] = $cat_node->textContent;
                }
            }
            
            // Alternativ nach category-Elementen suchen
            $alt_category_nodes = $xpath->query('./category', $item_node);
            if ($alt_category_nodes) {
                foreach ($alt_category_nodes as $cat_node) {
                    $item['categories'][] = $cat_node->textContent;
                }
            }
            
            // Thumbnail extrahieren
            if (!empty($item['content'])) {
                preg_match('/<img[^>]+src=([\'"])([^\'"]+)\\1/i', $item['content'], $matches);
                if (!empty($matches[2])) {
                    $item['thumbnail'] = $matches[2];
                }
            }
            
            // ID generieren, falls nicht vorhanden
            if (empty($item['id'])) {
                $item['id'] = !empty($item['link']) ? $item['link'] : md5($item['title'] . ($item['date'] ?? ''));
            }
            
            // Nur Items mit mindestens Titel und Link hinzufügen
            if (!empty($item['title']) && !empty($item['link'])) {
                $items[] = $item;
            }
        }
        
        return $items;
    }
    
    /**
     * Extrahiert den Textwert eines XPath-Knotens
     *
     * @param \DOMXPath $xpath Die XPath-Instanz
     * @param string $query Die XPath-Abfrage
     * @return string|null Der Textwert oder null
     */
    private function getNodeValue(\DOMXPath $xpath, string $query): ?string {
        $nodes = $xpath->query($query);
        if ($nodes && $nodes->length > 0) {
            return $nodes->item(0)->textContent;
        }
        return null;
    }
    
    /**
     * Extrahiert den Textwert eines relativen XPath-Knotens
     *
     * @param \DOMXPath $xpath Die XPath-Instanz
     * @param string $query Die relative XPath-Abfrage
     * @param \DOMNode $context_node Der Kontext-Knoten
     * @return string|null Der Textwert oder null
     */
    private function getNodeValueRelative(\DOMXPath $xpath, string $query, \DOMNode $context_node): ?string {
        $nodes = $xpath->query($query, $context_node);
        if ($nodes && $nodes->length > 0) {
            return $nodes->item(0)->textContent;
        }
        return null;
    }
    
    /**
     * Konvertiert ein SimplePie-Item in ein Array
     *
     * @param \SimplePie_Item $item Das SimplePie-Item
     * @return array Das Item als Array
     */
    private function convertSimplePieItemToArray(\SimplePie_Item $item): array {
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
            $media_ns = 'http://search.yahoo.com/mrss/';
            $thumbnail_tags = $item->get_item_tags($media_ns, 'thumbnail');
            
            if ($thumbnail_tags && isset($thumbnail_tags[0]['attribs']['']['url'])) {
                $result['thumbnail'] = $thumbnail_tags[0]['attribs']['']['url'];
            } else {
                // Versuche, ein Bild aus dem Inhalt zu extrahieren
                preg_match('/<img[^>]+src=([\'"])([^\'"]+)\\1/i', $result['content'], $matches);
                if (!empty($matches[2])) {
                    $result['thumbnail'] = $matches[2];
                }
            }
        }
        
        return $result;
    }
} 