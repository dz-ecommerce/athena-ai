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
use AthenaAI\Repositories\FeedRepository;
use AthenaAI\Services\FeedProcessor\FeedProcessorFactory;
use AthenaAI\Services\LoggerService;

if (!defined('ABSPATH')) {
    exit;
}

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
     * Constructor.
     *
     * @param FeedHttpClient      $http_client       HTTP client.
     * @param LoggerService       $logger            Logger service.
     */
    public function __construct(FeedHttpClient $http_client, LoggerService $logger) {
        $this->http_client = $http_client;
        $this->logger      = $logger->setComponent('FeedService');
        $this->verbose_mode = false;
    }
    
    /**
     * Factory-Methode zum Erstellen einer FeedService-Instanz.
     *
     * @return FeedService
     */
    public static function create(): FeedService {
        return new self(
            new FeedHttpClient(),
            LoggerService::getInstance()
        );
    }
    
    /**
     * Setzt den Verbose-Modus.
     *
     * @param bool $verbose_mode Verbose-Modus aktivieren/deaktivieren.
     * @return FeedService
     */
    public function setVerboseMode(bool $verbose_mode): FeedService {
        $this->verbose_mode = $verbose_mode;
        $this->logger->setVerboseMode($verbose_mode);
        $this->http_client->setVerboseMode($verbose_mode);
        return $this;
    }
    
    /**
     * Verarbeitet den Feed-Inhalt und extrahiert die Items.
     *
     * @param string $content Feed-Inhalt.
     * @param Feed   $feed    Feed-Objekt für Fehlerbehandlung.
     *
     * @return array|false Array mit Feed-Items oder false bei Fehler.
     */
    private function processFeedContent(string $content, Feed $feed) {
        $this->logger->info("Verarbeite Feed-Inhalt...");
        
        // Versuche, den Feed als XML zu parsen
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($content);
        
        if ($xml !== false) {
            $this->logger->info("Feed-Inhalt als XML erkannt.");
            $items = $this->processXmlFeed($xml, $feed);
            if (!empty($items)) {
                return $items;
            }
        } else {
            // XML-Parsing-Fehler protokollieren
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            if (!empty($errors)) {
                $error_msg = "XML-Parsing-Fehler: ";
                foreach ($errors as $error) {
                    $error_msg .= "Zeile {$error->line}, Spalte {$error->column}: {$error->message}; ";
                }
                $this->logger->error($error_msg);
                if (method_exists($feed, 'update_feed_error')) {
                    $feed->update_feed_error($error_msg);
                }
            }
        }

        // Versuche, den Feed als JSON zu parsen
        $json = @json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $this->logger->info("Feed-Inhalt als JSON erkannt.");
            $items = $this->processJsonFeed($json, $feed);
            if (!empty($items)) {
                return $items;
            }
        } else {
            $error = "JSON-Parsing-Fehler: " . json_last_error_msg();
            $this->logger->error($error);
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error($error);
            }
        }

        // Wenn weder XML noch JSON, gib false zurück
        $preview = substr($content, 0, 100);
        $this->logger->error("Feed-Format nicht erkannt. Inhalt-Vorschau: {$preview}...");
        if (method_exists($feed, 'update_feed_error')) {
            $feed->update_feed_error("Feed-Format nicht erkannt.");
        }
        return false;
    }
    
    /**
     * Verarbeitet einen JSON-Feed und extrahiert die Items.
     *
     * @param array $json JSON-Feed.
     * @param Feed  $feed Feed-Objekt für Fehlerbehandlung.
     *
     * @return array Array mit Feed-Items.
     */
    private function processJsonFeed(array $json, Feed $feed) {
        return $this->extractItemsFromJson($json, $feed);
    }
    
    /**
     * Extrahiert Items aus einem JSON-Feed.
     *
     * @param array $json JSON-Feed.
     * @param Feed  $feed Feed-Objekt für Fehlerbehandlung.
     *
     * @return array Array mit Feed-Items.
     */
    private function extractItemsFromJson(array $json, Feed $feed) {
        $items = [];

        // JSON-Feed verarbeiten
        if (isset($json['items']) && is_array($json['items'])) {
            $this->logger->info("JSON-Feed erkannt. Items: " . count($json['items']));
            
            foreach ($json['items'] as $item) {
                $extracted = $this->extractJsonFeedItem($item, $feed);
                if (!empty($extracted)) {
                    $items[] = $extracted;
                }
            }
        } else {
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error("Unbekanntes JSON-Format.");
            }
            $this->logger->error("Unbekanntes JSON-Format. Weder JSON-Feed noch JSON-Array erkannt.");
        }

        if (empty($items)) {
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error("Keine Items im JSON-Feed gefunden.");
            }
            $this->logger->warn("Keine Items im JSON-Feed gefunden.");
        } else {
            $this->logger->info(count($items) . " Items aus dem JSON-Feed extrahiert.");
        }

        return $items;
    }
    
    /**
     * Speichert die Feed-Items in der Datenbank.
     *
     * @param Feed  $feed  Feed-Objekt.
     * @param array $items Array mit Feed-Items.
     *
     * @return bool Erfolgsstatus.
     */
    private function saveItems(Feed $feed, array $items): bool {
        global $wpdb;

        if (empty($items)) {
            $this->logger->error("Keine Items zum Speichern vorhanden.");
            return false;
        }

        $table_name = $wpdb->prefix . 'feed_raw_items';
        $feed_id    = $feed->get_post_id();
        $success    = true;
        $new_items  = 0;
        $errors     = 0;

        // Prüfe, ob die Tabelle existiert
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            $error = "Tabelle {$table_name} existiert nicht. Feed-Items können nicht gespeichert werden.";
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error($error);
            }
            $this->logger->error($error);
            return false;
        }

        $this->logger->info("Speichere " . count($items) . " Feed-Items in die Datenbank...");

        foreach ($items as $item) {
            if (empty($item['guid'])) {
                $this->logger->warn("Item ohne GUID übersprungen.");
                continue;
            }
            
            // Prüfe, ob das Item bereits existiert
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE feed_id = %d AND guid = %s",
                    $feed_id,
                    $item['guid']
                )
            );

            if ($exists) {
                $this->logger->debug("Item mit GUID {$item['guid']} existiert bereits.");
                continue;
            }

            // Füge das Item hinzu
            $result = $wpdb->insert(
                $table_name,
                [
                    'feed_id'     => $feed_id,
                    'guid'        => $item['guid'],
                    'title'       => $item['title'] ?? '',
                    'link'        => $item['link'] ?? '',
                    'description' => $item['description'] ?? '',
                    'content'     => $item['content'] ?? '',
                    'author'      => $item['author'] ?? '',
                    'published'   => $item['published'] ?? \current_time('mysql'),
                    'updated'     => $item['updated'] ?? \current_time('mysql'),
                    'raw_data'    => json_encode($item['raw_data'] ?? []),
                    'created'     => \current_time('mysql'),
                ]
            );

            if ($result === false) {
                $errors++;
                $db_error = $wpdb->last_error;
                $this->logger->error("Fehler beim Speichern des Items: {$db_error}");
                $success = false;
            } else {
                $new_items++;
                $this->logger->debug("Item mit GUID {$item['guid']} erfolgreich gespeichert.");
            }
        }

        // Aktualisiere den Feed-Status
        if (method_exists($feed, 'update_last_checked')) {
            $feed->update_last_checked();
        }
        
        $this->logger->info("Feed-Items-Speicherung abgeschlossen. Neue Items: {$new_items}, Fehler: {$errors}");

        return $success;
    }
    
    /**
     * Verarbeitet einen XML-Feed und extrahiert die Items.
     *
     * @param SimpleXMLElement $xml  XML-Feed.
     * @param Feed             $feed Feed-Objekt für Fehlerbehandlung.
     *
     * @return array Array mit Feed-Items.
     */
    private function processXmlFeed(\SimpleXMLElement $xml, Feed $feed) {
        return $this->extractItemsFromXml($xml, $feed);
    }
    
    /**
     * Extrahiert Items aus einem XML-Feed.
     *
     * @param SimpleXMLElement $xml  XML-Feed.
     * @param Feed             $feed Feed-Objekt für Fehlerbehandlung.
     *
     * @return array Array mit Feed-Items.
     */
    private function extractItemsFromXml(\SimpleXMLElement $xml, Feed $feed) {
        $items = [];

        // RSS-Feed verarbeiten
        if (isset($xml->channel) && isset($xml->channel->item)) {
            $this->logger->info("RSS-Feed erkannt. Titel: " . (string)$xml->channel->title . ", Items: " . count($xml->channel->item));
            
            foreach ($xml->channel->item as $item) {
                $extracted = $this->extractRssItem($item, $feed);
                if (!empty($extracted)) {
                    $items[] = $extracted;
                }
            }
        }
        // Atom-Feed verarbeiten
        elseif (isset($xml->entry)) {
            $this->logger->info("Atom-Feed erkannt. Titel: " . (string)$xml->title . ", Entries: " . count($xml->entry));
            
            foreach ($xml->entry as $entry) {
                $extracted = $this->extractAtomItem($entry, $feed);
                if (!empty($extracted)) {
                    $items[] = $extracted;
                }
            }
        } else {
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error("Unbekanntes XML-Format. Weder RSS noch Atom erkannt.");
            }
            $this->logger->error("Unbekanntes XML-Format. Weder RSS noch Atom erkannt.");
        }

        if (empty($items)) {
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error("Keine Items im XML-Feed gefunden.");
            }
            $this->logger->warn("Keine Items im XML-Feed gefunden.");
        } else {
            $this->logger->info(count($items) . " Items aus dem XML-Feed extrahiert.");
        }

        return $items;
    }
    
    /**
     * Extrahiert ein RSS-Item aus einem XML-Feed.
     *
     * @param SimpleXMLElement $item RSS-Item.
     * @param Feed             $feed Feed-Objekt für Fehlerbehandlung.
     *
     * @return array Array mit RSS-Item-Daten.
     */
    private function extractRssItem(\SimpleXMLElement $item, Feed $feed) {
        $data = [];

        // Titel
        $data['title'] = (string)$item->title;

        // Link
        $data['link'] = (string)$item->link;

        // Beschreibung
        $data['description'] = (string)$item->description;

        // Autor
        $data['author'] = (string)$item->author;

        // Veröffentlichungsdatum
        $data['published'] = (string)$item->pubDate;

        // Aktualisierungsdatum
        $data['updated'] = (string)$item->lastBuildDate;

        // GUID
        $data['guid'] = (string)$item->guid;

        return $data;
    }
    
    /**
     * Extrahiert ein Atom-Item aus einem XML-Feed.
     *
     * @param SimpleXMLElement $entry Atom-Item.
     * @param Feed             $feed  Feed-Objekt für Fehlerbehandlung.
     *
     * @return array Array mit Atom-Item-Daten.
     */
    private function extractAtomItem(\SimpleXMLElement $entry, Feed $feed) {
        $data = [];

        // Titel
        $data['title'] = (string)$entry->title;

        // Link
        $data['link'] = (string)$entry->link['href'];

        // Beschreibung
        $data['description'] = (string)$entry->summary;

        // Autor
        $data['author'] = (string)$entry->author->name;

        // Veröffentlichungsdatum
        $data['published'] = (string)$entry->published;

        // Aktualisierungsdatum
        $data['updated'] = (string)$entry->updated;

        // GUID
        $data['guid'] = (string)$entry->id;

        return $data;
    }
    
    /**
     * Extrahiert ein JSON-Item aus einem JSON-Feed.
     *
     * @param array $item JSON-Item.
     * @param Feed  $feed Feed-Objekt für Fehlerbehandlung.
     *
     * @return array Array mit JSON-Item-Daten.
     */
    private function extractJsonFeedItem(array $item, Feed $feed) {
        $data = [];

        // Titel
        $data['title'] = $item['title'] ?? '';

        // Link
        $data['link'] = $item['link'] ?? '';

        // Beschreibung
        $data['description'] = $item['description'] ?? '';

        // Autor
        $data['author'] = $item['author'] ?? '';

        // Veröffentlichungsdatum
        $data['published'] = $item['published'] ?? '';

        // Aktualisierungsdatum
        $data['updated'] = $item['updated'] ?? '';

        // GUID
        $data['guid'] = $item['guid'] ?? '';

        return $data;
    }
    
    /**
     * Ruft einen Feed ab und verarbeitet ihn.
     *
     * @param Feed $feed            Feed-Objekt.
     * @param bool $verbose_console Ausführliche Konsolenausgabe aktivieren.
     *
     * @return bool Erfolgsstatus.
     */
    public function fetch_and_process_feed(Feed $feed, bool $verbose_console = false): bool {
        if ($verbose_console) {
            $this->setVerboseMode(true);
        }
        
        $this->logger->info("Starte Abruf des Feeds: {$feed->get_url()}");
        
        try {
            // Feed-Inhalt abrufen
            $content = $this->http_client->fetch($feed->get_url());
            
            if (empty($content)) {
                $error = $this->http_client->get_last_error() ?: 'Leerer Feed-Inhalt erhalten';
                if (method_exists($feed, 'update_feed_error')) {
                    $feed->update_feed_error($error);
                }
                $this->logger->error("Feed-Abruf fehlgeschlagen: {$error}");
                return false;
            }
            
            // Feed-Inhalt verarbeiten
            $items = $this->processFeedContent($content, $feed);
            $items = [];
            // Try to process the content as XML feed
            $simple_xml = @simplexml_load_string($content);
            if ($simple_xml) {
                // Convert SimpleXML to array
                $items = $this->convertXmlToArray($simple_xml);
            } else {
                // Fallback: try to parse as JSON
                $json_data = \json_decode($content, true);
                if (is_array($json_data) && !empty($json_data)) {
                    $items = $json_data;
                } else if ($verbose_console) {
                    echo '<script>console.error("Unable to process feed content format for ' . htmlspecialchars($feed->get_url(), ENT_QUOTES, 'UTF-8') . '");</script>';
                }
            }
            
            if (empty($items)) {
                if ($verbose_console) {
                    $url = function_exists('esc_js') ? \esc_js($feed->get_url()) : htmlspecialchars($feed->get_url(), ENT_QUOTES, 'UTF-8');
                    echo '<script>console.warn("No items found in feed: ' . $url . '");</script>';
                }
                
                // Still update the feed metadata to record the attempt
                $this->repository->update_feed_metadata($feed, 0);
                return true; // Consider this a success with zero items
            }
            
            // Save the items
            $result = $this->saveItems($feed, $items);
            
            if ($verbose_console) {
                $url = function_exists('esc_js') ? \esc_js($feed->get_url()) : htmlspecialchars($feed->get_url(), ENT_QUOTES, 'UTF-8');
                echo '<script>console.info("Processed feed: ' . $url . '");</script>';
            }
            
            return $result;
            
        } catch (\Exception $e) {
            // Handle any exceptions that might occur
            if ($verbose_console) {
                $error_message = function_exists('esc_js') ? \esc_js($e->getMessage()) : htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                echo '<script>console.error("Error processing feed: ' . $error_message . '");</script>';
            }
            
            // Log the error if error handler is available
            if (isset($this->error_handler) && method_exists($this->error_handler, 'logError')) {
                $this->error_handler->logError($feed, 'process_exception', $e->getMessage());
            }
            return false;
        }
    }
}
