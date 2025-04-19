<?php
/**
 * Abstract Feed Processor
 * 
 * Base class for all feed processors. Defines the interface and common functionality
 * for processing different feed types (RSS, Atom, JSON Feed).
 * 
 * @package AthenaAI\Models\FeedProcessor
 */

namespace AthenaAI\Models\FeedProcessor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Feed Processor Class
 */
abstract class AbstractFeedProcessor {
    
    /**
     * Process feed content
     * 
     * @param string $content The raw feed content
     * @param bool $verbose_console Whether to output verbose debugging information to the JavaScript console
     * @return array|false An array of feed items or false on failure
     */
    abstract public function process(string $content, bool $verbose_console = false);
    
    /**
     * Prepare feed content for processing
     * 
     * Common content preparation steps that apply to all feed types
     * 
     * @param string $content The raw feed content
     * @param bool $verbose_console Whether to output verbose debugging information to the JavaScript console
     * @return string The prepared content
     */
    protected function prepare_content(string $content, bool $verbose_console = false): string {
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Normalize line breaks
        $content = str_replace("\r\n", "\n", $content);
        
        // Convert from ISO-8859-1 to UTF-8 if necessary
        if (!mb_check_encoding($content, 'UTF-8')) {
            $original_content = $content;
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
            
            if ($verbose_console) {
                echo '<script>console.log("FeedProcessor: Converted content from ISO-8859-1 to UTF-8");</script>';
            }
        }
        
        // Clean invalid XML characters that could cause parsing issues
        $content = $this->clean_invalid_xml_chars($content);
        
        return $content;
    }
    
    /**
     * Clean invalid XML characters
     * 
     * @param string $content The content to clean
     * @return string The cleaned content
     */
    protected function clean_invalid_xml_chars(string $content): string {
        // XML 1.0 legal characters: #x9 | #xA | #xD | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
        return preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $content);
    }
    
    /**
     * Standardize feed items to a common format
     * 
     * @param array $items The feed items to standardize
     * @param bool $verbose_console Whether to output verbose debugging information to the JavaScript console
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
            
            // Link
            if (isset($item->link)) {
                if (is_string($item->link)) {
                    $standardized['link'] = $item->link;
                } elseif (isset($item->link['href'])) {
                    $standardized['link'] = (string)$item->link['href'];
                } else {
                    $standardized['link'] = (string)$item->link;
                }
            }
            
            // Description
            if (isset($item->description)) {
                $standardized['description'] = is_string($item->description) ? $item->description : (string)$item->description;
            } elseif (isset($item->content)) {
                $standardized['description'] = is_string($item->content) ? $item->content : (string)$item->content;
            } elseif (isset($item->summary)) {
                $standardized['description'] = is_string($item->summary) ? $item->summary : (string)$item->summary;
            }
            
            // Publication Date
            if (isset($item->pubDate)) {
                $standardized['pubDate'] = is_string($item->pubDate) ? $item->pubDate : (string)$item->pubDate;
            } elseif (isset($item->published)) {
                $standardized['pubDate'] = is_string($item->published) ? $item->published : (string)$item->published;
            } elseif (isset($item->updated)) {
                $standardized['pubDate'] = is_string($item->updated) ? $item->updated : (string)$item->updated;
            } else {
                $standardized['pubDate'] = date('r');
            }
            
            // GUID
            if (isset($item->guid)) {
                $standardized['guid'] = is_string($item->guid) ? $item->guid : (string)$item->guid;
            } elseif (isset($item->id)) {
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
    
    /**
     * Debug log to JavaScript console
     * 
     * @param string $message The message to log
     * @param string $type The type of log (log, error, warn, info)
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return void
     */
    protected function debug_log(string $message, string $type = 'log', bool $verbose_console = false): void {
        if ($verbose_console) {
            $class_name = (new \ReflectionClass($this))->getShortName();
            $safe_message = esc_js($message);
            echo "<script>console.{$type}(\"{$class_name}: {$safe_message}\");</script>";
        }
    }
}
