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
        
        $value = (string) $value;
        
        // Entferne bereits alle HTML-Tags vor weiterer Verarbeitung
        $value = strip_tags($value);
        
        // Entferne JSON-LD Strukturen
        $value = preg_replace('/\{\s*"@context"\s*:\s*"https?:\/\/schema\.org".*?\}/s', '', $value);
        
        // Entferne JavaScript Code
        $value = preg_replace('/\$\(document\)\.ready\(function\(\)\s*\{.*?\}\);/s', '', $value);
        $value = preg_replace('/function\s+[a-zA-Z0-9_]+\s*\([^)]*\)\s*\{.*?\}/s', '', $value);
        $value = preg_replace('/\$\([\'"]{1}[^\'"][\'"]{1}\)\..*?;/s', '', $value);
        
        // Entferne URLs am Ende des Textes
        $value = preg_replace('/https?:\/\/[^\s]+$/s', '', $value);
        
        // Entferne Code-ähnliche Strukturen
        $value = preg_replace('/^\s*var\s+[a-zA-Z0-9_]+\s*=.*?;/m', '', $value);
        
        // Entferne Cookie-Hinweise und ähnliche Texte
        $patterns = [
            '/Auf externen Websites.*?Akzeptieren\s+Ablehnen/s',
            '/Diese Website verwendet Cookies.*?OK/s',
            '/Wir verwenden Cookies.*?Einverstanden/s',
            '/By clicking.*?cookies/s',
            '/datenschutz|privacy policy|impressum|imprint/i'
        ];
        
        foreach ($patterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }
        
        // Entferne mehrfache Leerzeichen und Zeilenumbrüche
        $value = preg_replace('/\s+/', ' ', $value);
        
        // Dekodiere HTML-Entities
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        
        // Nochmalige grundlegende Bereinigung
        $value = trim($value);
        
        // Entferne Webseiten-Namen und häufige Trennzeichen am Ende
        $value = preg_replace('/, [A-Za-z0-9\s]+, [A-Za-z0-9\s]+, OWL live$/s', '', $value);
        $value = trim($value);
        
        return empty($value) ? null : $value;
    }
}
