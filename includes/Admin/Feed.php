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
} 