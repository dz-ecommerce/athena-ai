<?php
/**
 * Abstract Feed Processor
 *
 * Base class for feed processors.
 *
 * @package AthenaAI\Services\FeedProcessor
 */

declare(strict_types=1);

namespace AthenaAI\Services\FeedProcessor;

use AthenaAI\Interfaces\FeedProcessorInterface;
use AthenaAI\Services\LoggerService;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Base class for feed processors with common functionality.
 */
abstract class AbstractFeedProcessor implements FeedProcessorInterface {
    /**
     * Logger service instance.
     *
     * @var LoggerService
     */
    protected LoggerService $logger;

    /**
     * Constructor.
     *
     * @param bool $verbose_console Whether to output verbose debugging to console.
     */
    public function __construct(bool $verbose_console = false) {
        $this->logger = LoggerService::getInstance()
            ->setComponent('Feed Processor')
            ->setVerboseMode($verbose_console);
    }

    /**
     * Log a message to the console.
     *
     * @param string|null $message The message to output.
     * @param string $level   Log level: log, info, warn, or error.
     * @return void
     */
    protected function consoleLog(?string $message, string $level = 'log'): void {
        if ($message === null) {
            $message = 'NULL message provided to console log';
            $level = 'warn';
        }
        $this->logger->console($message, $level);
    }

    /**
     * Log an error message.
     *
     * @param string|null $message The error message.
     * @param string $code    Optional error code.
     * @return void
     */
    protected function logError(?string $message, string $code = ''): void {
        if ($message === null) {
            $message = 'NULL error message provided';
        }
        $this->logger->error($message, $code);
    }

    /**
     * Clean and prepare a field value.
     *
     * @param mixed $value The value to clean.
     * @return string|null Cleaned value or null if empty.
     */
    protected function cleanFieldValue($value): ?string {
        if (empty($value)) {
            return null;
        }

        if (is_array($value)) {
            $value = reset($value);
        }

        $value = strip_tags((string) $value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = trim($value);

        return empty($value) ? null : $value;
    }
}
