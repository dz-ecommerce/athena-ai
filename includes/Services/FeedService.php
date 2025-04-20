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
use AthenaAI\Services\FeedParser\ParserRegistry;

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
     * Parser registry.
     *
     * @var ParserRegistry
     */
    private ParserRegistry $parser_registry;
    
    /**
     * Whether to output verbose console logs.
     *
     * @var bool
     */
    private bool $verbose_console = false;
    
    /**
     * Constructor.
     *
     * @param FeedRepository $repository      Feed repository.
     * @param FeedHttpClient $http_client     HTTP client.
     * @param ParserRegistry $parser_registry Parser registry.
     */
    public function __construct(
        FeedRepository $repository,
        FeedHttpClient $http_client,
        ParserRegistry $parser_registry
    ) {
        $this->repository = $repository;
        $this->http_client = $http_client;
        $this->parser_registry = $parser_registry;
    }
    
    /**
     * Create a default instance with all dependencies.
     *
     * @return self
     */
    public static function create(): self {
        return new self(
            new FeedRepository(),
            new FeedHttpClient(),
            new ParserRegistry()
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
        $this->parser_registry->setVerboseMode($verbose);
        return $this;
    }
    
    /**
     * Process a single feed.
     *
     * @param Feed $feed The feed to process.
     * @return bool Whether the processing was successful.
     */
    public function processFeed(Feed $feed): bool {
        $this->consoleLog("Processing feed: " . $feed->get_url(), 'group');
        
        // Fetch the feed content
        $content = $this->fetchFeed($feed);
        if (!$content) {
            $this->consoleLog("Failed to fetch feed content", 'error');
            $this->consoleLog('', 'groupEnd');
            return false;
        }
        
        // Parse the feed
        $items = $this->parseFeed($content);
        if (empty($items)) {
            $this->consoleLog("No items found in feed", 'warn');
            $this->consoleLog('', 'groupEnd');
            
            // Update feed metadata to record the attempt
            $this->repository->update_feed_metadata($feed, 0);
            return true;
        }
        
        // Save the items
        $result = $this->saveItems($feed, $items);
        $this->consoleLog("Saved " . count($items) . " items", 'info');
        $this->consoleLog('', 'groupEnd');
        
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
    private function fetchFeed(Feed $feed) {
        $url = $feed->get_url();
        if (!$url) {
            $this->logError($feed, 'no_url', 'No feed URL provided');
            return false;
        }
        
        $content = $this->http_client->fetch($url);
        if (!$content) {
            $this->logError($feed, 'fetch_failed', 'Failed to fetch feed content');
            return false;
        }
        
        return $content;
    }
    
    /**
     * Parse feed content.
     *
     * @param string $content The feed content to parse.
     * @return array The parsed feed items.
     */
    private function parseFeed(string $content): array {
        return $this->parser_registry->parse($content);
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
            $this->logError($feed, 'no_feed_id', 'Feed has no ID');
            return false;
        }
        
        $feed_id = $feed->get_id();
        $new_items_count = 0;
        $items_table = $wpdb->prefix . 'feed_raw_items';
        
        foreach ($items as $item) {
            // Skip items without required fields
            if (empty($item['link']) || empty($item['title'])) {
                continue;
            }
            
            // Generate a unique key for the item
            $item_key = md5($item['link'] . $feed_id);
            
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
            
            // Prepare the data
            $data = [
                'feed_id' => $feed_id,
                'item_key' => $item_key,
                'title' => $item['title'] ?? '',
                'link' => $item['link'] ?? '',
                'pub_date' => $item['date'] ?? current_time('mysql'),
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
            }
        }
        
        // Update feed metadata
        $this->repository->update_feed_metadata($feed, $new_items_count);
        
        return true;
    }
    
    /**
     * Log an error for a feed.
     *
     * @param Feed   $feed    The feed to log the error for.
     * @param string $code    The error code.
     * @param string $message The error message.
     * @return void
     */
    private function logError(Feed $feed, string $code, string $message): void {
        $feed->set_last_error($message);
        
        if ($feed->get_id()) {
            $this->repository->update_feed_error($feed, $code, $message);
        }
        
        $this->consoleLog("Error ({$code}): {$message}", 'error');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Athena AI Feed Error ({$code}): {$message}");
        }
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
}
