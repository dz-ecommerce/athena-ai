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

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base class for feed processors with common functionality.
 */
abstract class AbstractFeedProcessor implements FeedProcessorInterface {
    /**
     * Whether to output verbose debugging information.
     *
     * @var bool
     */
    protected bool $verbose_console = false;
    
    /**
     * Debug mode enabled.
     *
     * @var bool
     */
    protected bool $debug_mode = false;
    
    /**
     * Constructor.
     *
     * @param bool $verbose_console Whether to output verbose debugging to console.
     */
    public function __construct(bool $verbose_console = false) {
        $this->verbose_console = $verbose_console;
        $this->debug_mode = get_option('athena_ai_enable_debug_mode', false);
    }
    
    /**
     * Output a message to the console if verbose mode is enabled.
     *
     * @param string $message The message to output.
     * @param string $level   Log level: log, info, warn, or error.
     * @return void
     */
    protected function consoleLog(string $message, string $level = 'log'): void {
        if (!$this->verbose_console) {
            return;
        }
        
        $allowed_levels = ['log', 'info', 'warn', 'error'];
        $level = in_array($level, $allowed_levels) ? $level : 'log';
        
        echo '<script>console.' . $level . '("Athena AI Feed Processor: ' . esc_js($message) . '");</script>';
    }
    
    /**
     * Log an error message to the error log if debug mode is enabled.
     *
     * @param string $message The error message.
     * @return void
     */
    protected function logError(string $message): void {
        if ($this->debug_mode) {
            error_log("Athena AI Feed Processor: {$message}");
        }
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
        
        $value = strip_tags((string)$value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = trim($value);
        
        return empty($value) ? null : $value;
    }
}
