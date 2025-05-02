<?php
/**
 * Feed Service class
 *
 * @package AthenaAI\Services
 */

declare(strict_types=1);

namespace AthenaAI\Services;

use AthenaAI\Models\Feed;
use AthenaAI\Repositories\FeedRepository;
use AthenaAI\Services\FeedProcessor\FeedProcessorFactory;
use AthenaAI\Services\LoggerService;
use SimpleXMLElement;

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Service class for feed operations.
 */
class FeedService {
    /**
     * Feed repository.
     *
     * @var FeedRepository
     */
    private FeedRepository $repository;

    /**
     * HTTP client.
     *
     * @var FeedHttpClient
     */
    private FeedHttpClient $http_client;

    /**
     * Feed processor factory.
     *
     * @var FeedProcessorFactory
     */
    private FeedProcessorFactory $processor_factory;

    /**
     * Logger service.
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Error handler instance.
     *
     * @var ErrorHandler
     */
    private ErrorHandler $error_handler;

    /**
     * Verbose-Modus aktiviert/deaktiviert
     *
     * @var bool
     */
    private bool $verbose_mode = false;

    /**
     * Cache-Dauer in Sekunden
     *
     * @var int
     */
    private int $cache_expiration = 1800; // 30 Minuten Standard-Cache-Zeit

    /**
     * Constructor.
     *
     * @param FeedHttpClient      $http_client       HTTP client.
     * @param LoggerService       $logger            Logger service.
     */
    public function __construct(FeedHttpClient $http_client, LoggerService $logger) {
        $this->http_client = $http_client;
        $this->logger = $logger->setComponent('FeedService');
        $this->verbose_mode = false;
    }

    if (empty($html)) {
        return null;
    }
    $readability = new \andreskrey\Readability\Readability($html, $url);
}
