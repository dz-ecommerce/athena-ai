<?php

namespace AthenaAI\Models;

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Feed {
    private int $post_id;
    private string $url;
    private ?\DateTime $last_checked;
    private int $update_interval;
    private bool $active;

    /**
     * Constructor for the Feed class
     * 
     * @param string $url The feed URL
     * @param int $update_interval The update interval in seconds
     * @param bool $active Whether the feed is active
     */
    public function __construct(
        string $url,
        int $update_interval = 3600,
        bool $active = true
    ) {
        $this->url = esc_url_raw($url);
        $this->update_interval = $update_interval;
        $this->active = $active;
    }

    /**
     * Fetch feed content from the URL
     * 
     * @return bool Whether the fetch was successful
     */
    public function fetch(): bool {
        $response = wp_safe_remote_get($this->url, [
            'timeout' => 15,
            'headers' => ['Accept' => 'application/rss+xml, application/atom+xml']
        ]);

        if (is_wp_error($response)) {
            $this->log_error($response->get_error_code(), $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return $this->process_feed_content($body);
    }

    /**
     * Process the feed content
     * 
     * @param string $content The feed content
     * @return bool Whether the processing was successful
     */
    private function process_feed_content(string $content): bool {
        global $wpdb;

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            $this->log_error('xml_parse_error', 'Failed to parse feed XML');
            return false;
        }

        $items = $xml->channel->item ?? $xml->entry ?? [];
        $processed = 0;

        foreach ($items as $item) {
            $guid = (string)($item->guid ?? $item->id ?? '');
            $pub_date = (string)($item->pubDate ?? $item->published ?? '');
            
            if (empty($guid) || empty($pub_date)) {
                continue;
            }

            $item_hash = md5($guid . $pub_date);
            $raw_content = wp_json_encode($item);

            // Store raw item
            $wpdb->replace(
                $wpdb->prefix . 'feed_raw_items',
                [
                    'item_hash' => $item_hash,
                    'feed_id' => $this->post_id, // Using post_id instead of feed_id
                    'raw_content' => $raw_content,
                    'pub_date' => date('Y-m-d H:i:s', strtotime($pub_date)),
                    'guid' => $guid
                ],
                ['%s', '%d', '%s', '%s', '%s']
            );

            $processed++;
        }

        $this->update_last_checked();
        return $processed > 0;
    }

    /**
     * Log an error for this feed
     * 
     * @param string $code The error code
     * @param string $message The error message
     */
    private function log_error(string $code, string $message): void {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'feed_errors',
            [
                'feed_id' => $this->post_id, // Using post_id instead of feed_id
                'error_code' => $code,
                'error_message' => $message
            ],
            ['%d', '%s', '%s']
        );
    }

    /**
     * Update the last checked timestamp
     */
    private function update_last_checked(): void {
        $now = current_time('mysql');
        update_post_meta($this->post_id, '_athena_feed_last_checked', $now);
        $this->last_checked = new \DateTime();
    }

    /**
     * Get a feed by its post ID
     * 
     * @param int $post_id The post ID
     * @return self|null The feed object or null if not found
     */
    public static function get_by_id(int $post_id): ?self {
        // Check if post exists and is the correct type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'athena-feed') {
            return null;
        }

        $url = get_post_meta($post_id, '_athena_feed_url', true);
        if (empty($url)) {
            return null;
        }

        $update_interval = (int)get_post_meta($post_id, '_athena_feed_update_interval', true) ?: 3600;
        $active = (bool)get_post_meta($post_id, '_athena_feed_active', true) ?: true;
        $last_checked = get_post_meta($post_id, '_athena_feed_last_checked', true);

        $feed = new self($url, $update_interval, $active);
        $feed->post_id = $post_id;
        $feed->last_checked = $last_checked ? new \DateTime($last_checked) : null;
        
        return $feed;
    }

    /**
     * Get the feed ID (post ID)
     *
     * @return int|null The post ID or null if not set
     */
    public function get_id(): ?int {
        return isset($this->post_id) ? $this->post_id : null;
    }
    
    /**
     * Get the feed URL
     *
     * @return string The feed URL
     */
    public function get_url(): string {
        return $this->url;
    }
    
    /**
     * Set the feed URL
     *
     * @param string $url The new feed URL
     * @return void
     */
    public function set_url(string $url): void {
        $this->url = esc_url_raw($url);
    }
    
    /**
     * Save the feed to the database
     *
     * @return bool Whether the save was successful
     */
    public function save(): bool {
        // If we have a post_id, update the existing post
        if (isset($this->post_id)) {
            // Update post meta
            update_post_meta($this->post_id, '_athena_feed_url', $this->url);
            update_post_meta($this->post_id, '_athena_feed_update_interval', $this->update_interval);
            update_post_meta($this->post_id, '_athena_feed_active', $this->active ? '1' : '0');
            
            return true;
        } else {
            // Create a new post
            $post_id = wp_insert_post([
                'post_title' => parse_url($this->url, PHP_URL_HOST) ?: $this->url,
                'post_type' => 'athena-feed',
                'post_status' => 'publish'
            ]);
            
            if (is_wp_error($post_id)) {
                return false;
            }
            
            // Set post meta
            update_post_meta($post_id, '_athena_feed_url', $this->url);
            update_post_meta($post_id, '_athena_feed_update_interval', $this->update_interval);
            update_post_meta($post_id, '_athena_feed_active', $this->active ? '1' : '0');
            
            $this->post_id = $post_id;
            return true;
        }
    }
    
    /**
     * Get all active feeds
     * 
     * @return array Array of Feed objects
     */
    public static function get_all_active(): array {
        $feeds = [];
        
        $posts = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_athena_feed_active',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ]);
        
        foreach ($posts as $post) {
            $feed = self::get_by_id($post->ID);
            if ($feed) {
                $feeds[] = $feed;
            }
        }
        
        return $feeds;
    }
    
    /**
     * Get feeds that need to be updated
     * 
     * @return array Array of Feed objects
     */
    public static function get_feeds_to_update(): array {
        $feeds = [];
        $current_time = current_time('timestamp');
        
        $posts = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_athena_feed_active',
                    'value' => '1',
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_athena_feed_last_checked',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_athena_feed_last_checked',
                        'value' => date('Y-m-d H:i:s', $current_time - 3600), // Default 1 hour
                        'compare' => '<',
                        'type' => 'DATETIME'
                    ]
                ]
            ]
        ]);
        
        foreach ($posts as $post) {
            $feed = self::get_by_id($post->ID);
            if ($feed) {
                $feeds[] = $feed;
            }
        }
        
        return $feeds;
    }
}
