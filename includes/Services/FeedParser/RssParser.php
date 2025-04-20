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
     * Check if this parser can handle the given content.
     *
     * @param string $content The feed content to check.
     * @return bool Whether this parser can handle the content.
     */
    public function canParse(string $content): bool {
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
     * @param string $content The feed content to parse.
     * @return array The parsed feed items.
     */
    public function parse(string $content): array {
        $this->consoleLog('Parsing feed with RSS parser', 'group');

        // Make sure SimplePie is loaded
        if (!class_exists('SimplePie')) {
            require_once ABSPATH . WPINC . '/class-simplepie.php';
        }
        
        // Create a new SimplePie instance
        $feed = new \SimplePie();
        $feed->set_raw_data($content);
        $feed->enable_cache(false);
        $feed->set_stupidly_fast(true);
        
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
        
        $this->consoleLog("Parsed " . count($items) . " items from feed", 'info');
        $this->consoleLog('', 'groupEnd');
        
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
                $parsed_url = wp_parse_url($feed_link);
                if ($parsed_url && isset($parsed_url['scheme'], $parsed_url['host'])) {
                    $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                    
                    if (strpos($thumbnail, '//') === 0) {
                        $thumbnail = $parsed_url['scheme'] . ':' . $thumbnail;
                    } elseif (strpos($thumbnail, '/') === 0) {
                        $thumbnail = $base_url . $thumbnail;
                    } else {
                        $thumbnail = $base_url . '/' . $thumbnail;
                    }
                }
            }
            
            return esc_url_raw($thumbnail);
        }
        
        return null;
    }
}
