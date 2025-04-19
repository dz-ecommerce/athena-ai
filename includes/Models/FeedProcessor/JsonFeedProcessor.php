<?php
/**
 * JSON Feed Processor
 * 
 * Processor for JSON Feed format (https://jsonfeed.org/)
 * 
 * @package AthenaAI\Models\FeedProcessor
 */

namespace AthenaAI\Models\FeedProcessor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * JSON Feed Processor
 */
class JsonFeedProcessor extends AbstractFeedProcessor {
    
    /**
     * Process feed content
     *
     * @param string $content The feed content
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array|false Array of feed items or false on failure
     */
    public function process(string $content, bool $verbose_console = false): array|false {
        $this->debug_log("Starting JSON feed processing", "log", $verbose_console);
        
        // Empty content check
        if (empty($content)) {
            $this->debug_log("Empty content provided", "error", $verbose_console);
            return false;
        }
        
        // JSON doesn't need the same preparation as XML, but we'll still do basic cleanup
        $content = $this->prepare_json_content($content, $verbose_console);
        
        // Show prepared content preview
        if ($verbose_console) {
            $preview = substr($content, 0, 200);
            $safe_preview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
            $this->debug_log("Prepared content: {$safe_preview}...", "log", $verbose_console);
        }
        
        // Try to decode the JSON
        $json_data = json_decode($content);
        
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = json_last_error_msg();
            $this->debug_log("JSON parsing failed: {$error_message}", "error", $verbose_console);
            return false;
        }
        
        // Validate the JSON structure for JSON Feed format
        if (!isset($json_data->items) || !is_array($json_data->items)) {
            // Try to detect non-standard JSON feeds
            $items = $this->detect_json_items($json_data, $verbose_console);
            
            if (empty($items)) {
                $this->debug_log("Invalid JSON Feed structure: 'items' array not found", "error", $verbose_console);
                return false;
            }
        } else {
            $items = $json_data->items;
        }
        
        // Check if we found any items
        if (empty($items)) {
            $this->debug_log("No items found in JSON feed", "error", $verbose_console);
            return false;
        }
        
        $this->debug_log("Successfully extracted " . count($items) . " items", "log", $verbose_console);
        
        // Standardize items to a common format
        return $this->standardize_json_items($items, $verbose_console);
    }
    
    /**
     * Prepare JSON content
     * 
     * @param string $content The JSON content
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return string The prepared content
     */
    protected function prepare_json_content(string $content, bool $verbose_console = false): string {
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Normalize line breaks
        $content = str_replace("\r\n", "\n", $content);
        
        // Ensure UTF-8 encoding
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
            $this->debug_log("Converted content from ISO-8859-1 to UTF-8", "log", $verbose_console);
        }
        
        // Remove any leading whitespace or non-JSON content
        $content = preg_replace('/^[\s\n\r]+/', '', $content);
        
        // If the content doesn't start with '{' or '[', try to find where the JSON starts
        if (!preg_match('/^\s*[\[\{]/', $content)) {
            if (preg_match('/[\[\{]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $start_pos = $matches[0][1];
                $content = substr($content, $start_pos);
                $this->debug_log("Trimmed content to start at JSON beginning", "log", $verbose_console);
            }
        }
        
        return $content;
    }
    
    /**
     * Detect JSON items in non-standard JSON feed structures
     * 
     * @param object $json_data The parsed JSON data
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array The detected items or empty array if none found
     */
    protected function detect_json_items(object $json_data, bool $verbose_console = false): array {
        $items = [];
        
        // Recursively search for possible item arrays in the JSON structure
        $potential_item_keys = ['entries', 'posts', 'results', 'data', 'feed', 'articles', 'content'];
        
        foreach ($potential_item_keys as $key) {
            if (isset($json_data->$key) && is_array($json_data->$key)) {
                $items = $json_data->$key;
                $this->debug_log("Found items using non-standard key: {$key}", "log", $verbose_console);
                break;
            }
        }
        
        // Check nested structures
        if (empty($items)) {
            foreach ($json_data as $key => $value) {
                if (is_object($value)) {
                    foreach ($potential_item_keys as $item_key) {
                        if (isset($value->$item_key) && is_array($value->$item_key)) {
                            $items = $value->$item_key;
                            $this->debug_log("Found items in nested structure: {$key}->{$item_key}", "log", $verbose_console);
                            break 2;
                        }
                    }
                }
            }
        }
        
        return $items;
    }
    
    /**
     * Standardize JSON feed items
     * 
     * @param array $items The JSON feed items
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array The standardized items
     */
    protected function standardize_json_items(array $items, bool $verbose_console = false): array {
        $standardized_items = [];
        
        foreach ($items as $item) {
            // Convert item to object if it's an array
            if (is_array($item)) {
                $item = (object)$item;
            }
            
            $standardized = [
                'title' => '',
                'link' => '',
                'description' => '',
                'pubDate' => '',
                'guid' => '',
            ];
            
            // Title - JSON Feed uses 'title'
            if (isset($item->title)) {
                $standardized['title'] = $item->title;
            }
            
            // Link - JSON Feed uses 'url', but could also use 'link' or 'permalink'
            if (isset($item->url)) {
                $standardized['link'] = $item->url;
            } elseif (isset($item->external_url)) {
                $standardized['link'] = $item->external_url;
            } elseif (isset($item->link)) {
                $standardized['link'] = $item->link;
            } elseif (isset($item->permalink)) {
                $standardized['link'] = $item->permalink;
            }
            
            // Description - JSON Feed uses 'content_text', 'content_html', or 'summary'
            if (isset($item->content_html)) {
                $standardized['description'] = $item->content_html;
            } elseif (isset($item->content_text)) {
                $standardized['description'] = $item->content_text;
            } elseif (isset($item->summary)) {
                $standardized['description'] = $item->summary;
            } elseif (isset($item->description)) {
                $standardized['description'] = $item->description;
            } elseif (isset($item->content)) {
                if (is_string($item->content)) {
                    $standardized['description'] = $item->content;
                } elseif (is_object($item->content) && isset($item->content->html)) {
                    $standardized['description'] = $item->content->html;
                } elseif (is_object($item->content) && isset($item->content->text)) {
                    $standardized['description'] = $item->content->text;
                }
            }
            
            // Publication Date - JSON Feed uses 'date_published' or 'date_modified'
            if (isset($item->date_published)) {
                $standardized['pubDate'] = $item->date_published;
            } elseif (isset($item->date_modified)) {
                $standardized['pubDate'] = $item->date_modified;
            } elseif (isset($item->published)) {
                $standardized['pubDate'] = $item->published;
            } elseif (isset($item->pubDate)) {
                $standardized['pubDate'] = $item->pubDate;
            } elseif (isset($item->created)) {
                $standardized['pubDate'] = $item->created;
            } elseif (isset($item->updated)) {
                $standardized['pubDate'] = $item->updated;
            } else {
                $standardized['pubDate'] = date('r');
            }
            
            // GUID - JSON Feed uses 'id'
            if (isset($item->id)) {
                $standardized['guid'] = $item->id;
            } elseif (isset($item->guid)) {
                $standardized['guid'] = $item->guid;
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
