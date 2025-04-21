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
use AthenaAI\Services\LoggerService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Factory for creating and selecting feed processors.
 */
class FeedProcessorFactory {
    /**
     * Logger service instance.
     *
     * @var LoggerService
     */
    private LoggerService $logger;
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
        $this->logger = LoggerService::getInstance()->setComponent('Feed Processor Factory');
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
     * @param string|null $content The feed content to process.
     * @return FeedProcessorInterface|null The selected processor or null if none found.
     */
    public function getProcessorForContent(?string $content): ?FeedProcessorInterface {
        if ($this->verbose_console) {
            $this->logger->console("Finding appropriate feed processor", 'group');
        }
        
        // Behandle NULL-Werte
        if ($content === null || empty($content)) {
            if ($this->verbose_console) {
                $this->logger->console("Feed content is null or empty", 'error');
                $this->logger->console("", 'groupEnd');
            }
            return null;
        }
        
        foreach ($this->processors as $processor) {
            if ($this->verbose_console) {
                $this->logger->console("Trying processor: " . $processor->getName(), 'info');
            }
            
            if ($processor->canProcess($content)) {
                if ($this->verbose_console) {
                    $this->logger->console("Selected processor: " . $processor->getName(), 'info');
                    $this->logger->console("", 'groupEnd');
                }
                return $processor;
            }
        }
        
        if ($this->verbose_console) {
            $this->logger->console("No suitable processor found for feed content", 'error');
            $this->logger->console("", 'groupEnd');
        }
        
        return null;
    }
    
    /**
     * Process feed content using the appropriate processor.
     *
     * @param string|null $content The feed content to process.
     * @return array|null The processed feed items or null if processing failed.
     */
    public function process(?string $content): ?array {
        // Behandle NULL-Werte
        if ($content === null || empty($content)) {
            if ($this->verbose_console) {
                $this->logger->console("Cannot process null or empty content", 'error');
            }
            return null;
        }
        
        $processor = $this->getProcessorForContent($content);
        
        if (!$processor) {
            if ($this->verbose_console) {
                $this->logger->console("No suitable processor found for content", 'error');
            }
            return null;
        }
        
        if ($this->verbose_console) {
            $this->logger->console("Processing with: " . $processor->getName(), 'info');
        }
        
        return $processor->process($content);
    }
}
