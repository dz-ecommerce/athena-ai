<?php
/**
 * Error Handler Service
 *
 * Handles error logging and reporting for the feed system.
 *
 * @package AthenaAI\Services
 */

declare(strict_types=1);

namespace AthenaAI\Services;

use AthenaAI\Models\Feed;
use AthenaAI\Repositories\FeedRepository;
use AthenaAI\Services\LoggerService;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Error handler for feed operations.
 */
class ErrorHandler {
    /**
     * Feed repository.
     *
     * @var FeedRepository
     */
    private FeedRepository $repository;

    /**
     * Logger service instance.
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Constructor.
     *
     * @param FeedRepository $repository The feed repository.
     * @param bool           $verbose_console Whether to output verbose console logs.
     */
    public function __construct(FeedRepository $repository, bool $verbose_console = false) {
        $this->repository = $repository;
        $this->logger = LoggerService::getInstance()
            ->setComponent('Error Handler')
            ->setVerboseMode($verbose_console);
    }

    /**
     * Set verbose console output mode.
     *
     * @param bool $verbose Whether to output verbose console logs.
     * @return self
     */
    public function setVerboseMode(bool $verbose): self {
        $this->logger->setVerboseMode($verbose);
        return $this;
    }

    /**
     * Log an error for a feed.
     *
     * @param Feed   $feed    The feed to log the error for.
     * @param string $code    The error code.
     * @param string $message The error message.
     * @return void
     */
    public function logError(Feed $feed, string $code, string $message): void {
        // Update feed's internal error state
        if (method_exists($feed, 'set_last_error')) {
            $feed->set_last_error($message);
        }

        // Update feed error in database if it has an ID
        if ($feed->get_id()) {
            $this->repository->update_feed_error($feed, $code, $message);
        }

        // Logger handles console output and error logging as needed
        $this->logger->error($message, $code);
    }

    /**
     * Log a general error without requiring a feed.
     *
     * @param string $code    The error code.
     * @param string $message The error message.
     * @return void
     */
    public function logGeneralError(string $code, string $message): void {
        // Logger handles both console and error_log output
        $this->logger->error($message, $code);
    }

    /**
     * Output a console log message.
     *
     * @param string $message The message to log.
     * @param string $type    The type of log message (log, info, warn, error, group, groupEnd).
     * @return void
     */
    public function consoleLog(string $message, string $type = 'log'): void {
        $this->logger->console($message, $type);
    }
}
