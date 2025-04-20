<?php
/**
 * Feed Parser Interface
 *
 * @package AthenaAI\Services\FeedParser
 */

declare(strict_types=1);

namespace AthenaAI\Services\FeedParser;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for feed parsers.
 */
interface FeedParserInterface {
    /**
     * Check if this parser can handle the given content.
     *
     * @param string $content The feed content to check.
     * @return bool Whether this parser can handle the content.
     */
    public function canParse(string $content): bool;
    
    /**
     * Parse the feed content.
     *
     * @param string $content The feed content to parse.
     * @return array The parsed feed items.
     */
    public function parse(string $content): array;
}
