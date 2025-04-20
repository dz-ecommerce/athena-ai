<?php
/**
 * JSON Feed Processor
 * 
 * Processes JSON format feeds
 *
 * @package AthenaAI\Services\FeedProcessor
 */

declare(strict_types=1);

namespace AthenaAI\Services\FeedProcessor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Processor for JSON based feeds.
 */
class JsonFeedProcessor extends AbstractFeedProcessor {
    /**
     * Check if this processor can handle the content.
     *
     * @param string $content The feed content to check.
     * @return bool True if this processor can handle the content.
     */
    public function canProcess(string $content): bool {
        // Trim whitespace
        $trimmed = trim($content);
        
        // Basic checks for JSON structure
        $is_json = (
            // Should start with { for object or [ for array
            (strpos($trimmed, '{') === 0 || strpos($trimmed, '[') === 0) &&
            // Should be valid JSON
            json_decode($trimmed) !== null && 
            json_last_error() === JSON_ERROR_NONE
        );
        
        if ($is_json) {
            $this->consoleLog("Content identified as JSON format", 'info');
        }
        
        return $is_json;
    }
    
    /**
     * Get processor name.
     *
     * @return string Processor name.
     */
    public function getName(): string {
        return 'JSON';
    }
    
    /**
     * Process JSON feed content.
     *
     * @param string $content The feed content to process.
     * @return array The extracted feed items.
     */
    public function process(string $content): array {
        $this->consoleLog("Processing feed with JSON processor", 'info');
        
        // Decode JSON content
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->consoleLog("JSON decode error: " . json_last_error_msg(), 'error');
            $this->logError("JSON decode error: " . json_last_error_msg());
            return [];
        }
        
        // Extract items array - handle different potential formats
        $items = [];
        
        // Format 1: Direct array of items
        if (isset($data[0])) {
            $items = $data;
            $this->consoleLog("Found direct array of items format", 'info');
        } 
        // Format 2: Object with 'items' property
        elseif (isset($data['items'])) {
            $items = $data['items'];
            $this->consoleLog("Found items property in JSON", 'info');
        }
        // Format 3: Object with 'data' property
        elseif (isset($data['data'])) {
            $items = $data['data'];
            $this->consoleLog("Found data property in JSON", 'info');
        }
        // Format 4: Object with 'results' property
        elseif (isset($data['results'])) {
            $items = $data['results'];
            $this->consoleLog("Found results property in JSON", 'info');
        }
        // Format 5: Object with 'entries' property (similar to Atom)
        elseif (isset($data['entries'])) {
            $items = $data['entries'];
            $this->consoleLog("Found entries property in JSON", 'info');
        }
        // Format 6: HubSpot specific format with 'objects' property
        elseif (isset($data['objects'])) {
            $items = $data['objects'];
            $this->consoleLog("Found HubSpot-like objects property in JSON", 'info');
        }
        
        if (empty($items)) {
            $this->consoleLog("Could not identify items in JSON structure", 'error');
            $this->logError("Could not identify items in JSON structure");
            return [];
        }
        
        $this->consoleLog("Found " . count($items) . " items in feed", 'info');
        
        // Process items
        $processed_items = [];
        foreach ($items as $item) {
            $processed_item = $this->processItem($item);
            if ($processed_item) {
                $processed_items[] = $processed_item;
            }
        }
        
        $this->consoleLog("Successfully processed " . count($processed_items) . " items", 'info');
        return $processed_items;
    }
    
    /**
     * Process an individual feed item.
     *
     * @param array $item The feed item to process.
     * @return array|null The processed item or null if invalid.
     */
    protected function processItem(array $item): ?array {
        // Try to find a unique identifier (guid or id)
        $guid = $item['guid'] ?? $item['id'] ?? $item['uuid'] ?? null;
        
        // If no direct GUID found, try alternative fields
        if (empty($guid) && isset($item['link'])) {
            $guid = $item['link'];
        }
        
        // As a last resort, create a hash of the content
        if (empty($guid) && isset($item['content'])) {
            $guid = md5(maybe_serialize($item));
        }
        
        // Skip items without any identifiable GUID
        if (empty($guid)) {
            $this->logError("Skipping item without GUID or identifiable unique content");
            return null;
        }
        
        // Get publication date from various possible fields
        $pub_date = null;
        $date_fields = ['pub_date', 'pubDate', 'published_at', 'date', 'created_at', 'publishedAt', 'created'];
        
        foreach ($date_fields as $field) {
            if (!empty($item[$field])) {
                $date_value = $item[$field];
                
                // Handle various date formats
                if (is_numeric($date_value)) {
                    // Unix timestamp
                    $pub_date = date('Y-m-d H:i:s', (int)$date_value);
                } elseif (is_string($date_value)) {
                    // Try to parse date string
                    $timestamp = strtotime($date_value);
                    if ($timestamp !== false) {
                        $pub_date = date('Y-m-d H:i:s', $timestamp);
                    }
                }
                
                if ($pub_date) {
                    break;
                }
            }
        }
        
        // If no valid date found, use current time
        if (empty($pub_date)) {
            $pub_date = current_time('mysql');
            $this->consoleLog("Using current time as fallback for item: " . $guid, 'warn');
        }
        
        // Process item into consistent format with field mapping
        $processed_item = [
            'guid' => $guid,
            'title' => $this->cleanFieldValue($item['title'] ?? $item['name'] ?? null),
            'link' => $this->cleanFieldValue($item['link'] ?? $item['url'] ?? null),
            'description' => $this->cleanFieldValue($item['description'] ?? $item['summary'] ?? $item['excerpt'] ?? null),
            'content' => $this->cleanFieldValue($item['content'] ?? $item['body'] ?? $item['text'] ?? $item['description'] ?? null),
            'pub_date' => $pub_date,
            'author' => $this->extractAuthor($item),
            'categories' => $this->extractCategories($item),
            'enclosures' => $this->extractEnclosures($item),
        ];
        
        return $processed_item;
    }
    
    /**
     * Extract author information from various possible field formats.
     *
     * @param array $item Feed item.
     * @return string|null Author name or null.
     */
    protected function extractAuthor(array $item): ?string {
        // Direct author field as string
        if (isset($item['author']) && is_string($item['author'])) {
            return $this->cleanFieldValue($item['author']);
        }
        
        // Author as object with name property
        if (isset($item['author']['name'])) {
            return $this->cleanFieldValue($item['author']['name']);
        }
        
        // Check alternative field names
        $author_fields = ['creator', 'author_name', 'byline'];
        foreach ($author_fields as $field) {
            if (isset($item[$field])) {
                return $this->cleanFieldValue($item[$field]);
            }
        }
        
        return null;
    }
    
    /**
     * Extract categories from various possible field formats.
     *
     * @param array $item Feed item.
     * @return array List of categories.
     */
    protected function extractCategories(array $item): array {
        $categories = [];
        
        // Direct categories field as array
        if (isset($item['categories']) && is_array($item['categories'])) {
            // Handle both string arrays and object arrays
            foreach ($item['categories'] as $category) {
                if (is_string($category)) {
                    $categories[] = $category;
                } elseif (is_array($category) && isset($category['term'])) {
                    $categories[] = $category['term'];
                } elseif (is_array($category) && isset($category['name'])) {
                    $categories[] = $category['name'];
                }
            }
        }
        
        // Alternative field names
        $category_fields = ['tags', 'terms', 'keywords'];
        foreach ($category_fields as $field) {
            if (isset($item[$field]) && is_array($item[$field])) {
                foreach ($item[$field] as $value) {
                    if (is_string($value)) {
                        $categories[] = $value;
                    } elseif (is_array($value) && isset($value['name'])) {
                        $categories[] = $value['name'];
                    }
                }
            }
        }
        
        // Filter out empty values and return unique categories
        return array_unique(array_filter($categories));
    }
    
    /**
     * Extract enclosures from various possible field formats.
     *
     * @param array $item Feed item.
     * @return array List of enclosures.
     */
    protected function extractEnclosures(array $item): array {
        $enclosures = [];
        
        // Enclosures field
        if (isset($item['enclosures']) && is_array($item['enclosures'])) {
            foreach ($item['enclosures'] as $enclosure) {
                if (is_array($enclosure) && isset($enclosure['url']) || isset($enclosure['link'])) {
                    $enclosures[] = [
                        'link' => $enclosure['url'] ?? $enclosure['link'],
                        'type' => $enclosure['type'] ?? '',
                        'length' => $enclosure['length'] ?? 0
                    ];
                }
            }
        }
        
        // Media field (common in JSON Feed format)
        if (isset($item['attachments']) && is_array($item['attachments'])) {
            foreach ($item['attachments'] as $attachment) {
                if (is_array($attachment) && isset($attachment['url'])) {
                    $enclosures[] = [
                        'link' => $attachment['url'],
                        'type' => $attachment['mime_type'] ?? $attachment['type'] ?? '',
                        'length' => $attachment['size_in_bytes'] ?? 0
                    ];
                }
            }
        }
        
        // Check for image field
        if (isset($item['image']) && !empty($item['image'])) {
            $image = $item['image'];
            if (is_string($image)) {
                $enclosures[] = [
                    'link' => $image,
                    'type' => 'image/jpeg', // Assume image type
                    'length' => 0
                ];
            } elseif (is_array($image) && isset($image['url'])) {
                $enclosures[] = [
                    'link' => $image['url'],
                    'type' => $image['type'] ?? 'image/jpeg',
                    'length' => 0
                ];
            }
        }
        
        return $enclosures;
    }
}
