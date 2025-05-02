<?php
/**
 * RSS Feed Parser
 *
 * @package AthenaAI\Services\FeedParser
 */

declare(strict_types=1);

namespace AthenaAI\Services\FeedParser;

if (!defined('ABSPATH')) {
    exit();
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

        // Simple check for RSS/XML format - exclude RDF formats (handled by RdfParser)
        return (stripos($content, '<rss') !== false ||
            stripos($content, '<feed') !== false ||
            stripos($content, '<?xml') !== false) &&
            // Exclude RDF formats (now handled by RdfParser)
            stripos($content, '<rdf:RDF') === false &&
            stripos($content, 'xmlns:rdf=') === false &&
            stripos($content, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#') === false;
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

        // Erkennung des Feed-Typs (RSS, Atom)
        $feed_type = $this->detectFeedType($content);
        $this->consoleLog("Erkannter Feed-Typ: $feed_type", 'info');

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
            $this->consoleLog('', 'groupEnd');
            return [];
        }

        // Get the items
        $items = [];
        foreach ($feed->get_items() as $item) {
            $items[] = $this->convert_item_to_array($item);
        }

        $this->consoleLog('Parsed ' . count($items) . ' items from feed', 'info');
        $this->consoleLog('', 'groupEnd');

        return $items;
    }

    /**
     * Detectiert den Feed-Typ basierend auf dem Inhalt
     *
     * @param string $content Der Feed-Inhalt
     * @return string Der erkannte Feed-Typ ('rss', 'atom', 'unknown')
     */
    private function detectFeedType(string $content): string {
        // RSS-Feed (RSS 2.0, RSS 0.9x)
        if (stripos($content, '<rss') !== false) {
            return 'rss';
        }

        // Atom-Feed
        if (
            stripos($content, '<feed') !== false &&
            (stripos($content, 'xmlns="http://www.w3.org/2005/Atom"') !== false ||
                stripos($content, 'xmlns="http://purl.org/atom/') !== false)
        ) {
            return 'atom';
        }

        // Standard-XML mit Feed-ähnlichen Elementen
        if (stripos($content, '<channel') !== false && stripos($content, '<item') !== false) {
            return 'rss';
        }

        return 'unknown';
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
            'categories' => [],
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
