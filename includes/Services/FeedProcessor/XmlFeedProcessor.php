<?php
/**
 * XML Feed Processor
 *
 * Processes XML format feeds (RSS, Atom, etc.)
 *
 * @package AthenaAI\Services\FeedProcessor
 */

declare(strict_types=1);

namespace AthenaAI\Services\FeedProcessor;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Processor for XML based feeds like RSS and Atom.
 */
class XmlFeedProcessor extends AbstractFeedProcessor {
    /**
     * Check if this processor can handle the content.
     *
     * @param string|null $content The feed content to check.
     * @return bool True if this processor can handle the content.
     */
    public function canProcess(?string $content): bool {
        // Behandle NULL-Werte
        if ($content === null || empty($content)) {
            return false;
        }

        // Check for XML/RSS markers
        $is_xml = strpos($content, '<?xml') !== false;
        $has_rss =
            strpos($content, '<rss') !== false ||
            strpos($content, '<feed') !== false ||
            strpos($content, '<channel') !== false;

        return $is_xml && $has_rss;
    }

    /**
     * Get processor name.
     *
     * @return string Processor name.
     */
    public function getName(): string {
        return 'XML/RSS';
    }

    /**
     * Process XML feed content.
     *
     * @param string|null $content The feed content to process.
     * @return array The extracted feed items.
     */
    public function process(?string $content): array {
        $this->consoleLog('Processing feed with XML/RSS processor', 'info');

        // Behandle NULL-Werte
        if ($content === null || empty($content)) {
            $this->consoleLog('Feed content is null or empty', 'error');
            $this->logError('Feed content is null or empty');
            return [];
        }

        // Initialize SimplePie
        $feed = new \SimplePie();
        $feed->set_raw_data($content);
        $feed->enable_cache(false);
        $feed->set_stupidly_fast(true);
        $feed->enable_order_by_date(true);
        $feed->init();

        if ($feed->error()) {
            $this->consoleLog('SimplePie error: ' . $feed->error(), 'error');
            $this->logError('SimplePie error: ' . $feed->error());
            return [];
        }

        $this->consoleLog('Found ' . $feed->get_item_quantity() . ' items in feed', 'info');

        // Process items
        $items = [];
        foreach ($feed->get_items() as $item) {
            $processed_item = $this->processItem($item);
            if ($processed_item) {
                $items[] = $processed_item;
            }
        }

        $this->consoleLog('Successfully processed ' . count($items) . ' items', 'info');
        return $items;
    }

    /**
     * Process an individual feed item.
     *
     * @param \SimplePie_Item $item The feed item to process.
     * @return array|null The processed item or null if invalid.
     */
    protected function processItem(\SimplePie_Item $item): ?array {
        // Extract required fields
        $guid = $item->get_id();

        // Skip items without GUID
        if (empty($guid)) {
            $this->logError('Skipping item without GUID');
            return null;
        }

        // Get publication date
        $pub_date = $item->get_date('Y-m-d H:i:s');
        if (empty($pub_date)) {
            $pub_date = current_time('mysql');
            $this->consoleLog('Using current time as fallback: ' . $pub_date, 'warn');
        }

        // Process item into consistent format
        $processed_item = [
            'guid' => $guid,
            'title' => $this->cleanFieldValue($item->get_title()),
            'link' => $this->cleanFieldValue($item->get_link()),
            'description' => $this->cleanFieldValue($item->get_description()),
            'content' => $this->cleanFieldValue($item->get_content()),
            'pub_date' => $pub_date,
            'author' => $this->cleanFieldValue(
                $item->get_author() ? $item->get_author()->get_name() : null
            ),
            'categories' => array_map(fn($cat) => $cat->get_term(), $item->get_categories() ?: []),
            'enclosures' => array_map(
                function ($enclosure) {
                    return [
                        'link' => $enclosure->get_link(),
                        'type' => $enclosure->get_type(),
                        'length' => $enclosure->get_length(),
                    ];
                },
                $item->get_enclosures() ?: []
            ),
        ];

        return $processed_item;
    }
}
