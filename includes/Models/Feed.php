<?php
declare(strict_types=1);

namespace AthenaAI\Models;

if (!defined('ABSPATH')) {
    exit;
}

class Feed {
    private int $feed_id;
    private string $url;
    private ?\DateTime $last_checked;
    private int $update_interval;
    private bool $active;

    public function __construct(
        string $url,
        int $update_interval = 3600,
        bool $active = true
    ) {
        $this->url = esc_url_raw($url);
        $this->update_interval = $update_interval;
        $this->active = $active;
    }

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
                    'feed_id' => $this->feed_id,
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

    private function log_error(string $code, string $message): void {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'feed_errors',
            [
                'feed_id' => $this->feed_id,
                'error_code' => $code,
                'error_message' => $message
            ],
            ['%d', '%s', '%s']
        );
    }

    private function update_last_checked(): void {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'feed_metadata',
            ['last_checked' => current_time('mysql')],
            ['feed_id' => $this->feed_id],
            ['%s'],
            ['%d']
        );
        
        $this->last_checked = new \DateTime();
    }

    public static function get_by_id(int $feed_id): ?self {
        global $wpdb;
        
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}feed_metadata WHERE feed_id = %d",
                $feed_id
            ),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        $feed = new self($row['url'], (int)$row['update_interval'], (bool)$row['active']);
        $feed->feed_id = (int)$row['feed_id'];
        $feed->last_checked = $row['last_checked'] ? new \DateTime($row['last_checked']) : null;
        
        return $feed;
    }

    public function save(): bool {
        global $wpdb;

        $data = [
            'url' => $this->url,
            'update_interval' => $this->update_interval,
            'active' => $this->active
        ];

        if (isset($this->feed_id)) {
            $result = $wpdb->update(
                $wpdb->prefix . 'feed_metadata',
                $data,
                ['feed_id' => $this->feed_id],
                ['%s', '%d', '%d'],
                ['%d']
            );
        } else {
            $result = $wpdb->insert(
                $wpdb->prefix . 'feed_metadata',
                $data,
                ['%s', '%d', '%d']
            );
            if ($result) {
                $this->feed_id = $wpdb->insert_id;
            }
        }

        return $result !== false;
    }
}
