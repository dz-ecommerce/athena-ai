<?php
/**
 * Feed Parser Registry
 *
 * @package AthenaAI\Services\FeedParser
 */

declare(strict_types=1);

namespace AthenaAI\Services\FeedParser;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registry that manages feed parsers.
 */
class ParserRegistry {
    /**
     * Registered parsers.
     *
     * @var FeedParserInterface[]
     */
    private array $parsers = [];
    
    /**
     * Verbose console output mode.
     *
     * @var bool
     */
    private bool $verbose_console = false;

    /**
     * Constructor.
     */
    public function __construct() {
        // Register default parsers
        $this->register_default_parsers();
    }

    /**
     * Set verbose console output mode.
     *
     * @param bool $verbose Whether to output verbose console logs.
     * @return self
     */
    public function setVerboseMode(bool $verbose): self {
        $this->verbose_console = $verbose;
        
        // Also set verbose mode on all parsers
        foreach ($this->parsers as $parser) {
            if (method_exists($parser, 'setVerboseMode')) {
                $parser->setVerboseMode($verbose);
            }
        }
        
        return $this;
    }

    /**
     * Register default parsers.
     *
     * @return void
     */
    private function register_default_parsers(): void {
        // Registriere den RDF-Parser zuerst, damit er Priorität hat für RDF-Feeds
        $this->register_parser(new RdfParser());
        
        // Danach den Standard-RSS-Parser für alle anderen Feed-Formate
        $this->register_parser(new RssParser());
    }
    
    /**
     * Register a parser.
     *
     * @param FeedParserInterface $parser The parser to register.
     * @return self
     */
    public function register_parser(FeedParserInterface $parser): self {
        $this->parsers[] = $parser;
        return $this;
    }
    
    /**
     * Find a parser that can handle the given content.
     *
     * @param string $content The feed content to parse.
     * @return FeedParserInterface|null The appropriate parser or null if none found.
     */
    public function find_parser(string $content): ?FeedParserInterface {
        foreach ($this->parsers as $parser) {
            if ($parser->canParse($content)) {
                if ($this->verbose_console && method_exists($parser, 'setVerboseMode')) {
                    $parser->setVerboseMode(true);
                }
                return $parser;
            }
        }
        
        return null;
    }
    
    /**
     * Parse feed content with the appropriate parser.
     *
     * @param string $content The feed content to parse.
     * @return array The parsed feed items or empty array if no parser found.
     */
    public function parse(string $content): array {
        $parser = $this->find_parser($content);
        
        if (!$parser) {
            if ($this->verbose_console) {
                echo '<script>console.error("Athena AI Feed: No suitable parser found for the feed content");</script>';
            }
            return [];
        }
        
        return $parser->parse($content);
    }
}
