<?php
namespace AthenaAI\Admin;

class Feed extends BaseAdmin {
    /**
     * Render the feed page
     */
    public function render_page() {
        $items = $this->get_feed_items();
        
        $this->render_template('feed', [
            'title' => $this->__('Athena AI Feed', 'athena-ai'),
            'items' => $items,
            'nonce_field' => $this->get_nonce_field('athena_ai_feed'),
        ]);
    }

    /**
     * Render the feeds list page
     */
    public function render_feeds_page() {
        $feeds = $this->get_feeds();
        
        $this->render_template('feeds', [
            'title' => $this->__('All Feeds', 'athena-ai'),
            'feeds' => $feeds,
            'nonce_field' => $this->get_nonce_field('athena_ai_feeds'),
        ]);
    }

    /**
     * Render the add new feed page
     */
    public function render_add_feed_page() {
        $this->render_template('add-feed', [
            'title' => $this->__('Add New Feed', 'athena-ai'),
            'nonce_field' => $this->get_nonce_field('athena_ai_add_feed'),
        ]);
    }

    /**
     * Get feed items
     *
     * @return array
     */
    private function get_feed_items() {
        // This is a placeholder. In a real implementation, you would fetch actual feed items
        return [
            [
                'id' => 1,
                'title' => $this->__('Sample Feed Item', 'athena-ai'),
                'content' => $this->__('This is a sample feed item.', 'athena-ai'),
                'date' => current_time('mysql'),
            ],
        ];
    }

    /**
     * Get all feeds
     *
     * @return array
     */
    private function get_feeds() {
        $args = [
            'post_type' => 'athena-feed',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        $feeds = get_posts($args);
        return array_map(function($feed) {
            return [
                'id' => $feed->ID,
                'title' => $feed->post_title,
                'url' => get_post_meta($feed->ID, '_feed_url', true),
                'last_updated' => get_post_meta($feed->ID, '_last_updated', true),
            ];
        }, $feeds);
    }
} 