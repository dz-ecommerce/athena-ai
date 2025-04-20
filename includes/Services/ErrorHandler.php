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

if (!defined('ABSPATH')) {
    exit;
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
     * Whether to output verbose debugging information.
     *
     * @var bool
     */
    private bool $verbose_console = false;
    
    /**
     * Debug mode flag.
     *
     * @var bool
     */
    private bool $debug_mode = false;
    
    /**
     * Constructor.
     *
     * @param FeedRepository $repository The feed repository.
     * @param bool           $verbose_console Whether to output verbose console logs.
     */
    public function __construct(FeedRepository $repository, bool $verbose_console = false) {
        $this->repository = $repository;
        $this->verbose_console = $verbose_console;
        $this->debug_mode = get_option('athena_ai_enable_debug_mode', false);
    }
    
    /**
     * Set verbose console output mode.
     *
     * @param bool $verbose Whether to output verbose console logs.
     * @return self
     */
    public function setVerboseMode(bool $verbose): self {
        $this->verbose_console = $verbose;
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
        
        // Console output if verbose mode is enabled
        $this->consoleLog("Error ({$code}): {$message}", 'error');
        
        // Log to error log if debug is enabled
        if ($this->debug_mode || (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log("Athena AI Feed Error ({$code}): {$message}");
        }
    }
    
    /**
     * Log a general error without requiring a feed.
     *
     * @param string $code    The error code.
     * @param string $message The error message.
     * @return void
     */
    public function logGeneralError(string $code, string $message): void {
        // Console output if verbose mode is enabled
        $this->consoleLog("Error ({$code}): {$message}", 'error');
        
        // Log to error log if debug is enabled
        if ($this->debug_mode || (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log("Athena AI Error ({$code}): {$message}");
        }
    }
    
    /**
     * Output a console log message if verbose mode is enabled.
     *
     * @param string $message The message to log.
     * @param string $type    The type of log message (log, info, warn, error, group, groupEnd).
     * @return void
     */
    public function consoleLog(string $message, string $type = 'log'): void {
        if (!$this->verbose_console) {
            return;
        }

        $valid_types = ['log', 'info', 'warn', 'error', 'group', 'groupEnd'];
        $type = in_array($type, $valid_types) ? $type : 'log';
        
        echo '<script>console.' . $type . '("Athena AI: ' . esc_js($message) . '");</script>';
    }
}
