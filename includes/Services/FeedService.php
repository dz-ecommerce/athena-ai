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
     * Whether to output verbose console logs.
     *
     * @var bool
     */
    private bool $verbose_console = false;
    
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
     */
    public function __construct(
        FeedRepository $repository,
        FeedHttpClient $http_client,
        FeedProcessorFactory $processor_factory,
        ErrorHandler $error_handler
    ) {
        $this->repository = $repository;
        $this->http_client = $http_client;
        $this->processor_factory = $processor_factory;
        $this->error_handler = $error_handler;
    }
    
    /**
     * Create a default instance with all dependencies.
     *
     * @return self
     */
    public static function create(): self {
        $repository = new FeedRepository();
        return new self(
            $repository,
            new FeedHttpClient(),
            new FeedProcessorFactory(),
            new ErrorHandler($repository)
        );
    }
    
    /**
     * Set verbose console output mode.
     *
     * @param bool $verbose Whether to output verbose console logs.
     * @return self
     */
    public function setVerboseMode(bool $verbose): self {
        $this->verbose_console = $verbose;
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
        $this->error_handler->consoleLog("Processing feed: " . $feed->get_url(), 'group');
        
        // Fetch the feed content
        $content = $this->fetchFeed($feed);
        if (!$content) {
            $this->error_handler->consoleLog("Failed to fetch feed content", 'error');
            $this->error_handler->consoleLog('', 'groupEnd');
            return false;
        }
        
        // Process the feed content
        $items = $this->processFeedContent($content);
        if (empty($items)) {
            $this->error_handler->consoleLog("No items found in feed", 'warn');
            $this->error_handler->consoleLog('', 'groupEnd');
            
            // Update feed metadata to record the attempt
            $this->repository->update_feed_metadata($feed, 0);
            return true;
        }
        
        // Save the items
        $result = $this->saveItems($feed, $items);
        $this->error_handler->consoleLog("Saved " . count($items) . " items", 'info');
        $this->error_handler->consoleLog('', 'groupEnd');
        
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
        
        $this->error_handler->consoleLog("Fetching feed from URL: {$url}", 'info');
        
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
        
        if (!$feed->get_id()) {
            $this->error_handler->logError($feed, 'no_feed_id', 'Feed has no ID');
            return false;
        }
        
        $feed_id = $feed->get_id();
        $new_items_count = 0;
        $items_table = $wpdb->prefix . 'feed_raw_items';
        
        foreach ($items as $item) {
            // Skip items without required fields
            if (empty($item['guid']) || (empty($item['link']) && empty($item['title']))) {
                continue;
            }
            
            // Generate a unique key for the item - preferring guid, falling back to link+title hash
            $item_key = isset($item['guid']) ? md5($item['guid']) : md5(($item['link'] ?? '') . ($item['title'] ?? ''));
            
            // Check if the item already exists
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$items_table} WHERE item_key = %s",
                    $item_key
                )
            );
            
            if ($exists) {
                continue;
            }
            
            // Format date properly
            $pub_date = isset($item['pub_date']) ? $item['pub_date'] : current_time('mysql');
            
            // Prepare the data
            $data = [
                'feed_id' => $feed_id,
                'item_key' => $item_key,
                'title' => $item['title'] ?? '',
                'link' => $item['link'] ?? '',
                'pub_date' => $pub_date,
                'raw_content' => wp_json_encode($item),
                'fetched_date' => current_time('mysql')
            ];
            
            // Insert the item
            $result = $wpdb->insert(
                $items_table,
                $data,
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ]
            );
            
            if ($result) {
                $new_items_count++;
            } else {
                $this->error_handler->consoleLog("Failed to insert item: " . $wpdb->last_error, 'error');
            }
        }
        
        // Update feed metadata
        $this->repository->update_feed_metadata($feed, $new_items_count);
        
        return true;
    }
    

}
