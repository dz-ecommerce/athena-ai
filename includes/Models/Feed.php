<?php
/**
 * Feed Model class
 *
 * @package AthenaAI\Models
 */

declare(strict_types=1);

namespace AthenaAI\Models;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model class for feeds.
 * Represents a feed as a data object without direct database operations.
 */
class Feed {
    /**
     * Feed ID (post ID in WordPress)
     *
     * @var int|null
     */
    private ?int $post_id = null;
    
    /**
     * Feed URL
     *
     * @var string|null
     */
    private ?string $url = null;
    
    /**
     * Last error message
     *
     * @var string
     */
    private string $last_error = '';
    
    /**
     * When the feed was last checked
     *
     * @var \DateTime|null
     */
    private ?\DateTime $last_checked = null;
    
    /**
     * Update interval in seconds
     *
     * @var int
     */
    private int $update_interval = 3600; // Standard: 1 Stunde
    
    /**
     * Whether the feed is active
     *
     * @var bool
     */
    private bool $active = true;

    /**
     * Constructor for the Feed class
     * 
     * @param string $url            The feed URL
     * @param int    $update_interval The update interval in seconds
     * @param bool   $active         Whether the feed is active
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
     * Get the last error message
     * 
     * @return string The last error message
     */
    public function get_last_error(): string {
        return $this->last_error;
    }
    
    /**
     * Set the last error message
     * 
     * @param string $error The error message
     * @return self
     */
    public function set_last_error(string $error): self {
        $this->last_error = $error;
        return $this;
    }
    
    /**
     * Get the feed URL
     *
     * @return string|null The feed URL
     */
    public function get_url(): ?string {
        return $this->url;
    }

    /**
     * Set the feed URL
     *
     * @param string $url The new feed URL
     * @return self
     */
    public function set_url(string $url): self {
        $this->url = esc_url_raw($url);
        return $this;
    }

    /**
     * Set the feed ID (post ID)
     *
     * @param int $id The post ID to set
     * @return self
     */
    public function set_id(int $id): self {
        $this->post_id = $id;
        return $this;
    }

    /**
     * Get the feed ID (post ID)
     *
     * @return int|null The post ID or null if not set
     */
    public function get_id(): ?int {
        return $this->post_id;
    }
    
    /**
     * Get the last checked timestamp
     *
     * @return \DateTime|null The last checked timestamp
     */
    public function get_last_checked(): ?\DateTime {
        return $this->last_checked;
    }
    
    /**
     * Set the last checked timestamp
     *
     * @param \DateTime $datetime The last checked timestamp
     * @return self
     */
    public function set_last_checked(\DateTime $datetime): self {
        $this->last_checked = $datetime;
        return $this;
    }
    
    /**
     * Get the update interval
     *
     * @return int The update interval in seconds
     */
    public function get_update_interval(): int {
        return $this->update_interval;
    }
    
    /**
     * Set the update interval
     *
     * @param int $interval The update interval in seconds
     * @return self
     */
    public function set_update_interval(int $interval): self {
        $this->update_interval = $interval;
        return $this;
    }
    
    /**
     * Check if the feed is active
     *
     * @return bool Whether the feed is active
     */
    public function is_active(): bool {
        return $this->active;
    }
    
    /**
     * Set whether the feed is active
     *
     * @param bool $active Whether the feed is active
     * @return self
     */
    public function set_active(bool $active): self {
        $this->active = $active;
        return $this;
    }
    
    /**
     * Save the feed to the database
     * This is a simple persistence method that relies on WP functions
     * More complex operations are handled by the FeedRepository
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
}
