<?php
/**
 * Feed Processor Factory
 * 
 * This class provides a factory to determine and create the appropriate feed processor
 * based on the feed content type.
 * 
 * @package AthenaAI\Models\FeedProcessor
 */

namespace AthenaAI\Models\FeedProcessor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Feed Processor Factory Class
 */
class FeedProcessorFactory {
    
    /**
     * Create a feed processor based on feed content
     * 
     * @param string $content The feed content
     * @param string $url The feed URL (optional, may help with format detection)
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return AbstractFeedProcessor The appropriate feed processor
     */
    public static function create(string $content, string $url = '', bool $verbose_console = false): AbstractFeedProcessor {
        $content_type = self::detect_feed_type($content, $url, $verbose_console);
        
        if ($verbose_console) {
            echo "<script>console.log('FeedProcessorFactory: Detected feed type: {$content_type}');</script>";
        }
        
        // Return appropriate processor based on the detected type
        switch ($content_type) {
            case 'atom':
                return new AtomFeedProcessor();
            case 'json':
                return new JsonFeedProcessor();
            case 'rss':
            default:
                return new RssFeedProcessor();
        }
    }
    
    /**
     * Detect the feed type from content
     * 
     * @param string $content The feed content
     * @param string $url The feed URL (optional)
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return string The detected feed type (rss, atom, json)
     */
    private static function detect_feed_type(string $content, string $url = '', bool $verbose_console = false): string {
        
        // Trim content to check initial characters
        $trimmed_content = trim($content);
        
        // Check for JSON format (starts with { or [)
        if (substr($trimmed_content, 0, 1) === '{' || substr($trimmed_content, 0, 1) === '[') {
            // Basic validation for JSON
            json_decode($trimmed_content);
            if (json_last_error() === JSON_ERROR_NONE) {
                if ($verbose_console) {
                    echo "<script>console.log('FeedProcessorFactory: Detected JSON feed format');</script>";
                }
                return 'json';
            }
        }
        
        // Check for XML formats
        if (substr($trimmed_content, 0, 5) === '<?xml' || preg_match('/<([^>]+)>/i', $trimmed_content)) {
            // Check for Atom format
            if (stripos($trimmed_content, '<feed') !== false && 
                (stripos($trimmed_content, 'xmlns="http://www.w3.org/2005/Atom"') !== false || 
                 stripos($trimmed_content, 'xmlns=\'http://www.w3.org/2005/Atom\'') !== false)) {
                if ($verbose_console) {
                    echo "<script>console.log('FeedProcessorFactory: Detected Atom feed format');</script>";
                }
                return 'atom';
            }
            
            // Check for RSS format
            if (stripos($trimmed_content, '<rss') !== false || 
                stripos($trimmed_content, '<channel>') !== false || 
                stripos($trimmed_content, '<rdf:RDF') !== false) {
                if ($verbose_console) {
                    echo "<script>console.log('FeedProcessorFactory: Detected RSS feed format');</script>";
                }
                return 'rss';
            }
            
            // Check for Atom format within XML (without namespace in the first few lines)
            if (stripos($trimmed_content, '<entry>') !== false || 
                preg_match('/<entry\s+[^>]*>/i', $trimmed_content)) {
                if ($verbose_console) {
                    echo "<script>console.log('FeedProcessorFactory: Detected Atom feed format based on entry tags');</script>";
                }
                return 'atom';
            }
        }
        
        // Default to RSS if can't determine
        if ($verbose_console) {
            echo "<script>console.log('FeedProcessorFactory: Could not definitively detect feed type, defaulting to RSS');</script>";
        }
        return 'rss';
    }
    
    /**
     * Process feed content with the appropriate processor
     * 
     * Convenience method that detects, creates and runs the appropriate processor
     * 
     * @param string $content The feed content
     * @param string $url The feed URL (optional)
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array|false The processed feed items or false on failure
     */
    public static function process(string $content, string $url = '', bool $verbose_console = false): array|false {
        $processor = self::create($content, $url, $verbose_console);
        return $processor->process($content, $verbose_console);
    }
}
