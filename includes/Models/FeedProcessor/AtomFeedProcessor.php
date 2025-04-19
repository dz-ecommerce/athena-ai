<?php
/**
 * Atom Feed Processor
 * 
 * Processor for Atom feeds
 * 
 * @package AthenaAI\Models\FeedProcessor
 */

namespace AthenaAI\Models\FeedProcessor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Atom Feed Processor
 */
class AtomFeedProcessor extends AbstractFeedProcessor {
    
    /**
     * Process feed content
     *
     * @param string $content The feed content
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array|false Array of feed items or false on failure
     */
    public function process(string $content, bool $verbose_console = false): array|false {
        $this->debug_log("Starting Atom feed processing", "log", $verbose_console);
        
        // Empty content check
        if (empty($content)) {
            $this->debug_log("Empty content provided", "error", $verbose_console);
            return false;
        }
        
        // Prepare content for parsing
        $content = $this->prepare_content($content, $verbose_console);
        
        // Show prepared content preview
        if ($verbose_console) {
            $preview = substr($content, 0, 200);
            $safe_preview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
            $this->debug_log("Prepared content: {$safe_preview}...", "log", $verbose_console);
        }
        
        // Try to parse using SimpleXML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            // Log parsing errors
            if ($verbose_console) {
                $this->debug_log("SimpleXML parsing failed with errors:", "error", $verbose_console);
                echo '<script>console.group("Atom Feed Parsing Errors");</script>';
                
                foreach (libxml_get_errors() as $error) {
                    $message = "Line {$error->line}, Column {$error->column}: {$error->message}";
                    echo "<script>console.error(" . json_encode($message) . ");</script>";
                }
                
                echo '<script>console.groupEnd();</script>';
            }
            
            libxml_clear_errors();
            return false;
        }
        
        // Extract items based on Atom structure
        $items = [];
        
        // Check for entry elements (standard Atom)
        if (isset($xml->entry)) {
            $this->debug_log("Standard Atom structure detected", "log", $verbose_console);
            $items = $xml->entry;
        }
        // Some feeds might have feed->entry structure
        elseif (isset($xml->feed) && isset($xml->feed->entry)) {
            $this->debug_log("Nested Atom structure detected", "log", $verbose_console);
            $items = $xml->feed->entry;
        }
        
        // Register Atom namespace if it exists
        $namespaces = $xml->getNamespaces(true);
        if (isset($namespaces['atom'])) {
            $xml->registerXPathNamespace('atom', $namespaces['atom']);
            
            // Try to find entries using XPath with namespace
            if (empty($items)) {
                $entries = $xml->xpath('//atom:entry');
                if (!empty($entries)) {
                    $this->debug_log("Found Atom entries using XPath with namespace", "log", $verbose_console);
                    $items = $entries;
                }
            }
        }
        
        // Convert items to array for standardization
        $items_array = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                $items_array[] = $item;
            }
        }
        
        // Check if we found any items
        if (empty($items_array)) {
            $this->debug_log("No entries found in Atom feed structure", "error", $verbose_console);
            return false;
        }
        
        $this->debug_log("Successfully extracted " . count($items_array) . " entries", "log", $verbose_console);
        
        // Standardize items to a common format
        return $this->standardize_items($items_array, $verbose_console);
    }
    
    /**
     * Standardize Atom feed items
     * 
     * Override the parent method to handle Atom-specific elements
     *
     * @param array $items The feed items to standardize
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array The standardized items
     */
    protected function standardize_items(array $items, bool $verbose_console = false): array {
        $standardized_items = [];
        
        foreach ($items as $item) {
            $standardized = [
                'title' => '',
                'link' => '',
                'description' => '',
                'pubDate' => '',
                'guid' => '',
            ];
            
            // Title
            if (isset($item->title)) {
                $standardized['title'] = is_string($item->title) ? $item->title : (string)$item->title;
            }
            
            // Link - Atom typically uses <link href="..."> format
            if (isset($item->link)) {
                if ($item->link->count() > 0) {
                    foreach ($item->link as $link) {
                        $attributes = $link->attributes();
                        // Prefer the alternate link type if available
                        if (isset($attributes['rel']) && (string)$attributes['rel'] === 'alternate') {
                            $standardized['link'] = (string)$attributes['href'];
                            break;
                        }
                        // Otherwise take the first link
                        elseif (isset($attributes['href'])) {
                            $standardized['link'] = (string)$attributes['href'];
                        }
                    }
                } elseif (isset($item->link['href'])) {
                    $standardized['link'] = (string)$item->link['href'];
                }
            }
            
            // Description - Atom uses content, summary or both
            if (isset($item->content)) {
                $standardized['description'] = is_string($item->content) ? $item->content : (string)$item->content;
            } elseif (isset($item->summary)) {
                $standardized['description'] = is_string($item->summary) ? $item->summary : (string)$item->summary;
            }
            
            // Publication Date - Atom uses published or updated
            if (isset($item->published)) {
                $standardized['pubDate'] = is_string($item->published) ? $item->published : (string)$item->published;
            } elseif (isset($item->updated)) {
                $standardized['pubDate'] = is_string($item->updated) ? $item->updated : (string)$item->updated;
            } else {
                $standardized['pubDate'] = date('r');
            }
            
            // GUID - Atom uses id
            if (isset($item->id)) {
                $standardized['guid'] = is_string($item->id) ? $item->id : (string)$item->id;
            } elseif (!empty($standardized['link'])) {
                $standardized['guid'] = $standardized['link'];
            } else {
                $standardized['guid'] = md5($standardized['title'] . $standardized['pubDate']);
            }
            
            $standardized_items[] = $standardized;
        }
        
        return $standardized_items;
    }
}
