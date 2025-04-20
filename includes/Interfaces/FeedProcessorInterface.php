<?php
/**
 * Feed Processor Interface
 * 
 * Interface for feed processors that extract and format feed items.
 *
 * @package AthenaAI\Interfaces
 */

declare(strict_types=1);

namespace AthenaAI\Interfaces;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for feed content processors.
 */
interface FeedProcessorInterface {
    /**
     * Check if the processor can handle this content.
     *
     * @param string $content The feed content to check.
     * @return bool True if this processor can handle the content.
     */
    public function canProcess(string $content): bool;
    
    /**
     * Process the feed content and extract feed items.
     *
     * @param string $content The feed content to process.
     * @return array The extracted feed items.
     */
    public function process(string $content): array;
    
    /**
     * Get processor name for identification.
     *
     * @return string The name of the processor.
     */
    public function getName(): string;
}
