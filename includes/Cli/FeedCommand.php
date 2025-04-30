<?php
/**
 * Feed CLI Command
 *
 * @package AthenaAI\Cli
 */

declare(strict_types=1);

namespace AthenaAI\Cli;

use AthenaAI\Models\Feed;
use AthenaAI\Repositories\FeedRepository;
use AthenaAI\Services\FeedService;
use AthenaAI\Services\LoggerService;
use WP_CLI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage feeds in Athena AI plugin
 */
class FeedCommand {
    /**
     * Feed service.
     *
     * @var FeedService
     */
    private FeedService $feed_service;
    
    /**
     * Feed repository.
     *
     * @var FeedRepository
     */
    private FeedRepository $repository;
    
    /**
     * Logger service.
     *
     * @var LoggerService
     */
    private LoggerService $logger;
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->feed_service = FeedService::create();
        $this->logger = LoggerService::getInstance()->setComponent('FeedCommand');
        
        // Repository wird später initialisiert, da es WordPress-Funktionen benötigt
        if (function_exists('add_action')) {
            add_action('init', [$this, 'initialize_repository']);
        }
    }
    
    /**
     * Initialisiert das Feed-Repository, nachdem WordPress vollständig geladen ist.
     */
    public function initialize_repository() {
        $this->repository = new FeedRepository();
    }
    
    /**
     * Preload all feeds into cache without processing them.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Force refresh the cache even if it exists
     *
     * [--verbose]
     * : Output more information
     *
     * ## EXAMPLES
     *
     *     wp athena feed prefetch
     *     wp athena feed prefetch --force --verbose
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command options.
     */
    public function prefetch($args, $assoc_args) {
        $force = isset($assoc_args['force']);
        $verbose = isset($assoc_args['verbose']);
        
        $this->feed_service->setVerboseMode($verbose);
        
        if (!isset($this->repository)) {
            $this->initialize_repository();
        }
        
        // Hole alle Feed-URLs aus der Datenbank
        $feeds = $this->repository->get_all_feeds();
        
        if (empty($feeds)) {
            WP_CLI::warning('No feeds found in database');
            return;
        }
        
        WP_CLI::log(sprintf('Prefetching %d feeds...', count($feeds)));
        
        $start_time = microtime(true);
        $success_count = 0;
        $error_count = 0;
        
        foreach ($feeds as $feed) {
            if (!($feed instanceof Feed)) {
                continue;
            }
            
            $url = $feed->get_url();
            
            if (empty($url)) {
                WP_CLI::warning(sprintf('Feed ID %d has no URL', $feed->get_post_id()));
                continue;
            }
            
            WP_CLI::log(sprintf('Prefetching feed: %s', $url));
            
            if ($this->feed_service->prefetchFeed($url, $force)) {
                $success_count++;
                WP_CLI::success(sprintf('Successfully prefetched feed: %s', $url));
            } else {
                $error_count++;
                WP_CLI::error(sprintf('Failed to prefetch feed: %s', $url), false);
            }
        }
        
        $time_taken = microtime(true) - $start_time;
        
        WP_CLI::log(sprintf(
            'Prefetching completed in %.2f seconds. Success: %d, Errors: %d',
            $time_taken,
            $success_count,
            $error_count
        ));
    }
    
    /**
     * Process all cached feeds.
     *
     * ## OPTIONS
     *
     * [--verbose]
     * : Output more information
     *
     * ## EXAMPLES
     *
     *     wp athena feed process-cached
     *     wp athena feed process-cached --verbose
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command options.
     */
    public function process_cached($args, $assoc_args) {
        $verbose = isset($assoc_args['verbose']);
        
        $this->feed_service->setVerboseMode($verbose);
        
        WP_CLI::log('Processing all cached feeds...');
        
        $start_time = microtime(true);
        $results = $this->feed_service->processCachedFeeds($verbose);
        $time_taken = microtime(true) - $start_time;
        
        WP_CLI::log(sprintf(
            'Processing completed in %.2f seconds. Success: %d, Errors: %d',
            $time_taken,
            $results['success'],
            $results['failed']
        ));
        
        if (!empty($results['errors'])) {
            WP_CLI::log('Errors:');
            foreach ($results['errors'] as $error) {
                WP_CLI::warning(" - {$error}");
            }
        }
    }
    
    /**
     * Clear feed cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Clear all feed caches
     *
     * [--url=<url>]
     * : Clear cache for a specific feed URL
     *
     * ## EXAMPLES
     *
     *     wp athena feed clear-cache --all
     *     wp athena feed clear-cache --url=https://example.com/feed
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command options.
     */
    public function clear_cache($args, $assoc_args) {
        if (isset($assoc_args['all'])) {
            $this->feed_service->clearAllCaches();
            WP_CLI::success('Cleared all feed caches');
            return;
        }
        
        if (isset($assoc_args['url'])) {
            $url = $assoc_args['url'];
            
            if (empty($url)) {
                WP_CLI::error('URL cannot be empty');
                return;
            }
            
            if ($this->feed_service->clearCache($url)) {
                WP_CLI::success(sprintf('Cleared cache for feed: %s', $url));
            } else {
                WP_CLI::warning(sprintf('Cache not found for feed: %s', $url));
            }
            
            return;
        }
        
        WP_CLI::error('Please specify --all or --url=<url>');
    }
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('athena feed', '\\AthenaAI\\Cli\\FeedCommand');
} 