<?php
/**
 * Feed Service class
 *
 * @package AthenaAI\Services
 */

declare(strict_types=1);

namespace AthenaAI\Services;

use AthenaAI\Models\Feed;
use AthenaAI\Repositories\FeedRepository;
use AthenaAI\Services\FeedProcessor\FeedProcessorFactory;
use AthenaAI\Services\LoggerService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service class for feed operations.
 */
class FeedService {
    /**
     * Feed repository.
     *
     * @var FeedRepository
     */
    private FeedRepository $repository;
    
    /**
     * HTTP client.
     *
     * @var FeedHttpClient
     */
    private FeedHttpClient $http_client;
    
    /**
     * Feed processor factory.
     *
     * @var FeedProcessorFactory
     */
    private FeedProcessorFactory $processor_factory;
    
    /**
     * Logger service.
     *
     * @var LoggerService
     */
    private LoggerService $logger;
    
    /**
     * Error handler instance.
     *
     * @var ErrorHandler
     */
    private ErrorHandler $error_handler;
    
    /**
     * Constructor.
     *
     * @param FeedRepository      $repository        Feed repository.
     * @param FeedHttpClient      $http_client       HTTP client.
     * @param FeedProcessorFactory $processor_factory Feed processor factory.
     * @param ErrorHandler        $error_handler     Error handler.
     * @param LoggerService       $logger            Logger service.
     */
    public function __construct(
        FeedRepository $repository,
        FeedHttpClient $http_client,
        FeedProcessorFactory $processor_factory,
        ErrorHandler $error_handler,
        ?LoggerService $logger = null
    ) {
        $this->repository = $repository;
        $this->http_client = $http_client;
        $this->processor_factory = $processor_factory;
        $this->error_handler = $error_handler;
        $this->logger = $logger ?? LoggerService::getInstance()->setComponent('Feed Service');
    }
    
    /**
     * Create a default instance with all dependencies.
     *
     * @return self
     */
    public static function create(): self {
        $repository = new FeedRepository();
        $logger = LoggerService::getInstance()->setComponent('Feed Service');
        return new self(
            $repository,
            new FeedHttpClient(),
            new FeedProcessorFactory(),
            new ErrorHandler($repository),
            $logger
        );
    }
    
    /**
     * Set verbose console output mode.
     *
     * @param bool $verbose Whether to output verbose console logs.
     * @return self
     */
    public function setVerboseMode(bool $verbose): self {
        $this->logger->setVerboseMode($verbose);
        $this->http_client->setVerboseMode($verbose);
        $this->error_handler->setVerboseMode($verbose);
        
        // Set processor factory verbose mode
        if (method_exists($this->processor_factory, 'setVerboseMode')) {
            $this->processor_factory->setVerboseMode($verbose);
        }
        
        return $this;
    }
    
    /**
     * Process a single feed.
     *
     * @param Feed $feed The feed to process.
     * @return bool Whether the processing was successful.
     */
    public function processFeed(Feed $feed): bool {
        $this->logger->group("Processing feed: " . $feed->get_url());
        
        // Fetch the feed content
        $content = $this->fetchFeed($feed);
        if (!$content) {
            $this->logger->error("Failed to fetch feed content");
            $this->logger->groupEnd();
            return false;
        }
        
        // Process the feed content
        $items = $this->processFeedContent($content);
        if (empty($items)) {
            $this->logger->warn("No items found in feed");
            $this->logger->groupEnd();
            
            // Update feed metadata to record the attempt
            $this->repository->update_feed_metadata($feed, 0);
            return true;
        }
        
        // Save the items
        $result = $this->saveItems($feed, $items);
        $this->logger->info("Saved " . count($items) . " items");
        $this->logger->groupEnd();
        
        return $result;
    }
    
    /**
     * Process all feeds that need updating.
     *
     * @return array Statistics about the processed feeds.
     */
    public function processAllFeeds(): array {
        $stats = [
            'total' => 0,
            'success' => 0,
            'error' => 0,
            'new_items' => 0
        ];
        
        $feeds = $this->repository->get_feeds_to_update();
        $stats['total'] = count($feeds);
        
        foreach ($feeds as $feed) {
            $result = $this->processFeed($feed);
            if ($result) {
                $stats['success']++;
            } else {
                $stats['error']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Fetch a feed's content.
     *
     * @param Feed $feed The feed to fetch.
     * @return string|false The feed content or false on failure.
     */
    private function fetchFeed(Feed $feed): string|false {
        $url = $feed->get_url();
        if (empty($url)) {
            $this->error_handler->logError($feed, 'no_url', 'Feed URL is empty');
            return false;
        }
        
        $this->logger->info("Fetching feed from URL: {$url}");
        
        $content = $this->http_client->fetch($url);
        if (!$content) {
            $this->error_handler->logError($feed, 'fetch_failed', 'Failed to fetch feed content');
            return false;
        }
        
        return $content;
    }
    
    /**
     * Process feed content using the appropriate processor.
     *
     * @param string $content The feed content to process.
     * @return array The processed feed items.
     */
    private function processFeedContent(string $content): array {
        $items = $this->processor_factory->process($content);
        return $items ?? [];
    }
    
    /**
     * Save feed items to the database.
     *
     * @param Feed  $feed  The feed the items belong to.
     * @param array $items The feed items to save.
     * @return bool Whether the save was successful.
     */
    private function saveItems(Feed $feed, array $items): bool {
        global $wpdb;
        
        if (!$feed->get_post_id()) {
            $this->error_handler->logError($feed, 'no_feed_id', 'Feed has no ID');
            return false;
        }
        
        $feed_id = $feed->get_post_id();
        $new_items_count = 0;
        $items_table = $wpdb->prefix . 'feed_raw_items';
        
        foreach ($items as $item) {
            // Skip items without required fields
            if (empty($item['guid']) && empty($item['id']) && empty($item['link'])) {
                continue;
            }
            
            // Extract GUID with fallbacks
            $guid = '';
            if (isset($item['guid']) && !empty($item['guid'])) {
                $guid = (string) $item['guid'];
            } elseif (isset($item['id']) && !empty($item['id'])) {
                $guid = (string) $item['id'];
            } elseif (isset($item['link']) && !empty($item['link'])) {
                $guid = (string) $item['link'];
            } elseif (isset($item['title']) && !empty($item['title'])) {
                $guid = 'title-' . \md5((string) $item['title']);
            } else {
                $guid = 'feed-item-' . \uniqid();
            }
            
            // Generate hash for primary key
            $item_hash = \md5($guid);
            
            // Check if the item already exists
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$items_table} WHERE item_hash = %s AND feed_id = %d",
                    $item_hash,
                    $feed_id
                )
            );
            
            if ($exists) {
                continue;
            }
            
            // Extract publication date with fallbacks
            $pub_date = function_exists('current_time') ? \current_time('mysql') : date('Y-m-d H:i:s');
            if (isset($item['pubDate']) && !empty($item['pubDate'])) {
                $pub_date = (string) $item['pubDate'];
            } elseif (isset($item['published']) && !empty($item['published'])) {
                $pub_date = (string) $item['published'];
            } elseif (isset($item['date']) && !empty($item['date'])) {
                $pub_date = (string) $item['date'];
            }
            
            // Try to format the date properly
            try {
                $date = new \DateTime($pub_date);
                $pub_date = $date->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $pub_date = function_exists('current_time') ? \current_time('mysql') : date('Y-m-d H:i:s');
            }
            
            // Prepare JSON content
            $json_content = function_exists('wp_json_encode') ? \wp_json_encode($item, JSON_UNESCAPED_UNICODE) : json_encode($item, JSON_UNESCAPED_UNICODE);
            if ($json_content === false) {
                $this->logger->error("Failed to encode item JSON");
                continue;
            }
            
            // Insert the data
            $result = $wpdb->insert(
                $items_table,
                [
                    'item_hash' => $item_hash,
                    'feed_id' => $feed_id,
                    'guid' => $guid,
                    'pub_date' => $pub_date,
                    'raw_content' => $json_content,
                    'created_at' => function_exists('current_time') ? \current_time('mysql') : date('Y-m-d H:i:s')
                ],
                [
                    '%s', // item_hash
                    '%d', // feed_id
                    '%s', // guid
                    '%s', // pub_date
                    '%s', // raw_content
                    '%s'  // created_at
                ]
            );
            
            if ($result) {
                $new_items_count++;
            } else {
                $this->logger->error("Failed to insert item: " . $wpdb->last_error);
            }
        }
        
        // Update feed metadata
        $this->repository->update_feed_metadata($feed, $new_items_count);
        
        return true;
    }
    
    /**
     * Convert SimpleXML object to array format
     * 
     * @param \SimpleXMLElement $xml The XML element to convert
     * @return array The array of feed items
     */
    private function convertXmlToArray(\SimpleXMLElement $xml): array {
        $items = [];
        
        // Different XML formats have items in different locations
        // Try to detect RSS or Atom format
        
        // First check for RSS format (items are in <item> tags)
        if (isset($xml->channel) && isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $items[] = $this->processRssItem($item);
            }
        } 
        // Check for Atom format (items are in <entry> tags)
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $items[] = $this->processAtomEntry($entry);
            }
        }
        // Try another RSS format variation
        elseif (isset($xml->item)) {
            foreach ($xml->item as $item) {
                $items[] = $this->processRssItem($item);
            }
        }
        // Try for custom XML formats (Hubspot, etc)
        else {
            // Last resort - try to find any element that looks like an item
            $possibleItemTags = ['post', 'article', 'content', 'result', 'record'];
            
            foreach ($possibleItemTags as $tag) {
                if (isset($xml->$tag)) {
                    foreach ($xml->$tag as $item) {
                        $items[] = $this->extractGenericItemData($item);
                    }
                    break;
                }
            }
        }
        
        return $items;
    }
    
    /**
     * Process an RSS item
     * 
     * @param \SimpleXMLElement $item The RSS item
     * @return array The processed item data
     */
    private function processRssItem(\SimpleXMLElement $item): array {
        $result = [];
        
        // Extract common RSS fields
        $result['title'] = (string)($item->title ?? '');
        $result['link'] = (string)($item->link ?? '');
        $result['guid'] = (string)($item->guid ?? $item->link ?? '');
        $result['pubDate'] = (string)($item->pubDate ?? $item->date ?? date('Y-m-d H:i:s'));
        $result['description'] = (string)($item->description ?? '');
        
        // Handle namespaces and additional properties
        $namespaces = $item->getNamespaces(true);
        foreach ($namespaces as $prefix => $namespace) {
            $nsData = $item->children($namespace);
            foreach ($nsData as $key => $value) {
                $nsKey = $prefix . ':' . $key;
                $result[$nsKey] = (string)$value;
            }
        }
        
        return $result;
    }
    
    /**
     * Process an Atom entry
     * 
     * @param \SimpleXMLElement $entry The Atom entry
     * @return array The processed entry data
     */
    private function processAtomEntry(\SimpleXMLElement $entry): array {
        $result = [];
        
        // Extract common Atom fields
        $result['title'] = (string)($entry->title ?? '');
        $result['id'] = (string)($entry->id ?? '');
        $result['published'] = (string)($entry->published ?? $entry->updated ?? date('Y-m-d H:i:s'));
        $result['content'] = (string)($entry->content ?? '');
        $result['summary'] = (string)($entry->summary ?? '');
        
        // Handle link
        if (isset($entry->link)) {
            foreach ($entry->link as $link) {
                $rel = (string)$link['rel'];
                if ($rel === 'alternate' || empty($rel)) {
                    $result['link'] = (string)$link['href'];
                    break;
                }
            }
        }
        
        // Ensure we have a guid/id
        if (empty($result['id']) && !empty($result['link'])) {
            $result['id'] = $result['link'];
        }
        
        return $result;
    }
    
    /**
     * Extract data from a generic XML item
     * 
     * @param \SimpleXMLElement $item The XML item
     * @return array The extracted data
     */
    private function extractGenericItemData(\SimpleXMLElement $item): array {
        $result = [];
        
        // Try to extract common fields regardless of the format
        foreach ($item as $key => $value) {
            $result[$key] = (string)$value;
        }
        
        // Try to ensure we have required fields
        if (!isset($result['guid']) && isset($result['id'])) {
            $result['guid'] = $result['id'];
        }
        
        if (!isset($result['guid']) && isset($result['link'])) {
            $result['guid'] = $result['link'];
        }
        
        if (!isset($result['pubDate']) && !isset($result['published']) && !isset($result['date'])) {
            // Default to current date if no date field exists
            $result['pubDate'] = date('Y-m-d H:i:s');
        }
        
        return $result;
    }
    
    /**
     * Fetch and process a feed.
     * 
     * @param Feed $feed The feed to process.
     * @param bool $verbose_console Whether to output verbose console logs.
     * @return bool Whether the processing was successful.
     */
    public function fetch_and_process_feed(Feed $feed, bool $verbose_console = false): bool {
        // Set logger to verbose mode if required
        // No need to set verbose mode on logger as we're handling it directly here
        
        try {
            // Fetch the feed content
            $content = $this->http_client->fetch($feed->get_url());
            
            if (empty($content)) {
                // Handle error case: no content
                if ($verbose_console) {
                    $url = function_exists('esc_js') ? \esc_js($feed->get_url()) : htmlspecialchars($feed->get_url(), ENT_QUOTES, 'UTF-8');
                    echo '<script>console.error("No content found for feed: ' . $url . '");</script>';
                }
                return false;
            }
            
            // Determine content type and process accordingly
            $items = [];
            // Try to process the content as XML feed
            $simple_xml = @simplexml_load_string($content);
            if ($simple_xml) {
                // Convert SimpleXML to array
                $items = $this->convertXmlToArray($simple_xml);
            } else {
                // Fallback: try to parse as JSON
                $json_data = json_decode($content, true);
                if (is_array($json_data) && !empty($json_data)) {
                    $items = $json_data;
                } else if ($verbose_console) {
                    echo '<script>console.error("Unable to process feed content format");</script>';
                }
            }
            
            if (empty($items)) {
                if ($verbose_console) {
                    $url = function_exists('esc_js') ? \esc_js($feed->get_url()) : htmlspecialchars($feed->get_url(), ENT_QUOTES, 'UTF-8');
                    echo '<script>console.warn("No items found in feed: ' . $url . '");</script>';
                }
                
                // Still update the feed metadata to record the attempt
                $this->repository->update_feed_metadata($feed, 0);
                return true; // Consider this a success with zero items
            }
            
            // Save the items
            $result = $this->saveItems($feed, $items);
            
            if ($verbose_console) {
                $url = function_exists('esc_js') ? \esc_js($feed->get_url()) : htmlspecialchars($feed->get_url(), ENT_QUOTES, 'UTF-8');
                echo '<script>console.info("Processed feed: ' . $url . '");</script>';
            }
            
            return $result;
            
        } catch (\Exception $e) {
            // Handle any exceptions that might occur
            if ($verbose_console) {
                $error_message = function_exists('esc_js') ? \esc_js($e->getMessage()) : htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                echo '<script>console.error("Error processing feed: ' . $error_message . '");</script>';
            }
            
            // Log the error if error handler is available
            if (isset($this->error_handler) && method_exists($this->error_handler, 'logError')) {
                $this->error_handler->logError($feed, 'process_exception', $e->getMessage());
            }
            return false;
        }
    }
}
