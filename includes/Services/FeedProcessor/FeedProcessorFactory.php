<?php
/**
 * Feed Processor Factory
 * 
 * Factory for creating the appropriate feed processor.
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
 * Factory for creating and selecting feed processors.
 */
class FeedProcessorFactory {
    /**
     * Available feed processors.
     *
     * @var array
     */
    private array $processors = [];
    
    /**
     * Verbose console output flag.
     *
     * @var bool
     */
    private bool $verbose_console;
    
    /**
     * Constructor.
     *
     * @param bool $verbose_console Whether to output verbose console logs.
     */
    public function __construct(bool $verbose_console = false) {
        $this->verbose_console = $verbose_console;
        $this->registerDefaultProcessors();
    }
    
    /**
     * Register default feed processors.
     *
     * @return void
     */
    private function registerDefaultProcessors(): void {
        // Register standard processors
        $this->registerProcessor(new XmlFeedProcessor($this->verbose_console));
        $this->registerProcessor(new JsonFeedProcessor($this->verbose_console));
    }
    
    /**
     * Register a new feed processor.
     *
     * @param FeedProcessorInterface $processor The processor to register.
     * @return self
     */
    public function registerProcessor(FeedProcessorInterface $processor): self {
        $this->processors[] = $processor;
        return $this;
    }
    
    /**
     * Get all registered processors.
     *
     * @return array Array of processors.
     */
    public function getProcessors(): array {
        return $this->processors;
    }
    
    /**
     * Find the appropriate processor for feed content.
     *
     * @param string $content The feed content to process.
     * @return FeedProcessorInterface|null The selected processor or null if none found.
     */
    public function getProcessorForContent(string $content): ?FeedProcessorInterface {
        if ($this->verbose_console) {
            echo '<script>console.group("Finding appropriate feed processor");</script>';
        }
        
        foreach ($this->processors as $processor) {
            if ($this->verbose_console) {
                echo '<script>console.log("Trying processor: ' . esc_js($processor->getName()) . '");</script>';
            }
            
            if ($processor->canProcess($content)) {
                if ($this->verbose_console) {
                    echo '<script>console.log("Selected processor: ' . esc_js($processor->getName()) . '");</script>';
                    echo '<script>console.groupEnd();</script>';
                }
                return $processor;
            }
        }
        
        if ($this->verbose_console) {
            echo '<script>console.error("No suitable processor found for feed content");</script>';
            echo '<script>console.groupEnd();</script>';
        }
        
        return null;
    }
    
    /**
     * Process feed content using the appropriate processor.
     *
     * @param string $content The feed content to process.
     * @return array|null The processed feed items or null if processing failed.
     */
    public function process(string $content): ?array {
        $processor = $this->getProcessorForContent($content);
        
        if (!$processor) {
            if ($this->verbose_console) {
                echo '<script>console.error("No suitable processor found for content");</script>';
            }
            return null;
        }
        
        if ($this->verbose_console) {
            echo '<script>console.log("Processing with: ' . esc_js($processor->getName()) . '");</script>';
        }
        
        return $processor->process($content);
    }
}
