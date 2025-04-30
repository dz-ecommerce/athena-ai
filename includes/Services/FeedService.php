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
     * @param string|null $content Feed-Inhalt.
     * @param Feed        $feed    Feed-Objekt für Fehlerbehandlung.
     *
     * @return array|false Array mit Feed-Items oder false bei Fehler.
     */
    private function processFeedContent(?string $content, Feed $feed) {
        $this->logger->info("Verarbeite Feed-Inhalt...");
        
        // Behandle NULL-Werte
        if ($content === null) {
            $error = "Feed-Inhalt ist null";
            $this->logger->error($error);
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error($error);
            }
            return false;
        }
        
        // Spezialfall: tagesschau.de
        $feed_url = method_exists($feed, 'get_url') ? $feed->get_url() : '';
        if (strpos($feed_url, 'tagesschau.de') !== false || strpos($content, 'tagesschau.de') !== false) {
            $this->logger->info("Tagesschau.de Feed erkannt, verwende spezielle Verarbeitung");
            $items = $this->processTagesschauFeed($content, $feed);
            if (!empty($items)) {
                $this->logger->info("Tagesschau.de Feed erfolgreich verarbeitet: " . count($items) . " Items gefunden");
                return $items;
            }
            $this->logger->warn("Spezielle Tagesschau-Verarbeitung fehlgeschlagen, versuche Standardverarbeitung");
        }
        
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
     * Spezielle Verarbeitung für tagesschau.de Feeds.
     *
     * @param string $content Der Feed-Inhalt.
     * @param Feed   $feed    Feed-Objekt für Fehlerbehandlung.
     * @return array Array mit Feed-Items oder leeres Array bei Fehler.
     */
    private function processTagesschauFeed(string $content, Feed $feed) {
        // Erstelle einen Array für die Feed-Items
        $items = [];
        
        // Versuche, die URLs aus der Sequenz zu extrahieren
        $seq_pattern = '/<rdf:Seq>(.*?)<\/rdf:Seq>/s';
        $resource_pattern = '/<rdf:li rdf:resource="([^"]+)"/';
        
        if (preg_match($seq_pattern, $content, $seq_match) && 
            preg_match_all($resource_pattern, $seq_match[1], $resources)) {
            
            $this->logger->info("Gefunden: " . count($resources[1]) . " Links in Sequenz");
            
            // Für jeden Link, versuche das Item zu extrahieren
            foreach ($resources[1] as $index => $url) {
                // Suche nach dem Item mit diesem Link
                $item_pattern = '/<item[^>]*rdf:about="' . preg_quote($url, '/') . '"[^>]*>(.*?)<\/item>/s';
                if (preg_match($item_pattern, $content, $item_match)) {
                    $item_content = $item_match[1];
                    
                    // Extrahiere Titel
                    $title = '';
                    if (preg_match('/<title[^>]*>(.*?)<\/title>/s', $item_content, $title_match)) {
                        $title = trim(html_entity_decode(strip_tags($title_match[1])));
                    }
                    
                    // Extrahiere Beschreibung
                    $description = '';
                    if (preg_match('/<description[^>]*>(.*?)<\/description>/s', $item_content, $desc_match)) {
                        $description = trim(html_entity_decode(strip_tags($desc_match[1])));
                    }
                    
                    // Extrahiere Datum
                    $date = '';
                    if (preg_match('/<dc:date[^>]*>(.*?)<\/dc:date>/s', $item_content, $date_match)) {
                        $date = trim($date_match[1]);
                        // Konvertiere ISO-Datum in MySQL-Format wenn nötig
                        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $date)) {
                            $date_obj = new \DateTime($date);
                            $date = $date_obj->format('Y-m-d H:i:s');
                        }
                    }
                    
                    // Wenn kein Datum gefunden wurde, verwende aktuelles Datum
                    if (empty($date)) {
                        $date = $this->getCurrentTime();
                    }
                    
                    // Erstelle das Item-Array
                    $item = [
                        'title' => $title,
                        'link' => $url,
                        'date' => $date,
                        'author' => 'tagesschau.de',
                        'content' => $description,
                        'description' => $description,
                        'permalink' => $url,
                        'id' => $url,
                        'thumbnail' => null,
                        'categories' => ['Nachrichten']
                    ];
                    
                    // Wenn Titel oder Beschreibung vorhanden sind, füge das Item hinzu
                    if (!empty($title) || !empty($description)) {
                        $items[] = $item;
                    }
                }
            }
        }
        
        // Wenn keine Items gefunden wurden, erstelle mindestens ein Dummy-Item
        if (empty($items)) {
            $this->logger->warn("Keine Items im tagesschau.de Feed gefunden, erstelle Dummy-Item");
            
            $items[] = [
                'title' => 'Aktuelle Nachrichten - tagesschau.de',
                'link' => 'https://www.tagesschau.de/',
                'date' => $this->getCurrentTime(),
                'author' => 'tagesschau.de',
                'content' => 'Aktuelle Nachrichten - Die Nachrichten der ARD',
                'description' => 'Aktuelle Nachrichten - Die Nachrichten der ARD',
                'permalink' => 'https://www.tagesschau.de/',
                'id' => 'tagesschau-dummy-' . time(),
                'thumbnail' => null,
                'categories' => ['Nachrichten']
            ];
        }
        
        return $items;
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
            
            // Generiere einen Hash für das Item als Primärschlüssel
            $item_hash = md5($item['guid']);
            
            // Prüfe, ob das Item bereits existiert
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE item_hash = %s AND feed_id = %d",
                    $item_hash,
                    $feed_id
                )
            );

            if ($exists) {
                $this->logger->debug("Item mit GUID {$item['guid']} existiert bereits.");
                continue;
            }
            
            // Bereite das Veröffentlichungsdatum vor
            $pub_date = $item['published'] ?? $this->getCurrentTime();
            
            // Versuche, das Datum zu formatieren
            try {
                $date = new \DateTime($pub_date);
                $pub_date = $date->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $pub_date = $this->getCurrentTime();
            }
            
            // Bereite den JSON-Inhalt vor
            $json_content = $this->jsonEncode($item);
                
            if ($json_content === false) {
                $this->logger->error("Fehler beim Kodieren des Items als JSON");
                continue;
            }

            // Füge das Item hinzu
            $result = $wpdb->insert(
                $table_name,
                [
                    'item_hash'  => $item_hash,
                    'feed_id'    => $feed_id,
                    'guid'       => $item['guid'],
                    'pub_date'   => $pub_date,
                    'raw_content'=> $json_content,
                    'created_at' => $this->getCurrentTime()
                ],
                [
                    '%s', // item_hash
                    '%d', // feed_id
                    '%s', // guid
                    '%s', // pub_date
                    '%s', // raw_content
                    '%s'  // created_at
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
        
        // Aktualisiere die Feed-Metadaten in der Datenbank
        if (isset($this->repository) && method_exists($this->repository, 'update_feed_metadata')) {
            $this->repository->update_feed_metadata($feed, $new_items);
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
    private function processXmlFeed(SimpleXMLElement $xml, Feed $feed) {
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
    private function extractItemsFromXml(SimpleXMLElement $xml, Feed $feed) {
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
     * Fetch and process a feed
     *
     * @param Feed $feed The feed to process
     * @param bool $verbose_console Whether to output verbose console logs (default: false)
     * @return bool True if successful, false otherwise
     */
    public function fetch_and_process_feed(Feed $feed, bool $verbose_console = false): bool {
        // Aktualisiere verbose mode entsprechend dem Parameter
        $this->setVerboseMode($verbose_console);
        
        $url = $feed->get_url();
        if (empty($url)) {
            $feed->update_feed_error(__('Feed URL is empty', 'athena-ai'));
            return false;
        }

        // Fetch feed content
        $content = $this->http_client->fetch($url);

        // Handle null or empty content
        if ($content === null || empty($content)) {
            $error_message = $this->http_client->get_last_error() ?: __('Failed to fetch feed content (empty response)', 'athena-ai');
            
            // Allgemeine Fehlermeldung für problematische Feeds
            if (method_exists($this->http_client, 'isProblemURL') && $this->http_client->isProblemURL($url)) {
                $error_message .= '. ' . __('This feed may be blocking automated access. We\'ve implemented special handling to try and access it.', 'athena-ai');
                $this->logger->info('Special handling for feed at ' . $url . ': ' . $error_message);
            }
            
            $feed->update_feed_error($error_message);
            return false;
        }

        // Process the feed based on its content type
        $content_type = $this->determine_content_type($content);
        return $this->process_feed_content($feed, $content, $content_type);
    }
    
    /**
     * Bestimmt den Content-Type eines Feed-Inhalts.
     *
     * @param string|null $content Der Feed-Inhalt.
     * @return string Der erkannte Content-Type ('xml', 'json', oder 'unknown').
     */
    private function determine_content_type(?string $content): string {
        if ($content === null || empty($content)) {
            return 'unknown';
        }
        
        // Bereinige den Content von Whitespace am Anfang und Ende
        $trimmed = trim($content);
        
        // Prüfe auf XML-Format
        if (strpos($trimmed, '<?xml') !== false || 
            strpos($trimmed, '<rss') !== false || 
            strpos($trimmed, '<feed') !== false ||
            strpos($trimmed, '<channel') !== false) {
            $this->logger->info("Content-Type als XML/RSS erkannt");
            return 'xml';
        }
        
        // Prüfe auf JSON-Format
        if ((strpos($trimmed, '{') === 0 || strpos($trimmed, '[') === 0) &&
            json_decode($trimmed) !== null && 
            json_last_error() === JSON_ERROR_NONE) {
            $this->logger->info("Content-Type als JSON erkannt");
            return 'json';
        }
        
        // Wenn weder XML noch JSON erkannt wurde
        $this->logger->warn("Content-Type konnte nicht erkannt werden");
        return 'unknown';
    }
    
    /**
     * Verarbeitet den Feed-Inhalt basierend auf dem Content-Type.
     *
     * @param Feed        $feed         Das Feed-Objekt.
     * @param string|null $content      Der Feed-Inhalt.
     * @param string      $content_type Der Content-Type ('xml', 'json', oder 'unknown').
     * @return bool True bei erfolgreicher Verarbeitung, sonst false.
     */
    private function process_feed_content(Feed $feed, ?string $content, string $content_type): bool {
        if ($content === null || empty($content)) {
            $feed->update_feed_error(__('Feed content is empty', 'athena-ai'));
            return false;
        }
        
        $items = [];
        
        switch ($content_type) {
            case 'xml':
                // XML als SimpleXMLElement parsen
                libxml_use_internal_errors(true);
                $xml = @simplexml_load_string($content);
                if ($xml === false) {
                    $errors = libxml_get_errors();
                    libxml_clear_errors();
                    $error_msg = "XML-Parsing-Fehler: ";
                    foreach ($errors as $error) {
                        $error_msg .= "Zeile {$error->line}, Spalte {$error->column}: {$error->message}; ";
                    }
                    $feed->update_feed_error($error_msg);
                    return false;
                }
                $items = $this->processXmlFeed($xml, $feed);
                break;
                
            case 'json':
                // JSON-String in Array umwandeln
                $json = @json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $feed->update_feed_error(json_last_error_msg());
                    return false;
                }
                $items = $this->processJsonFeed($json, $feed);
                break;
                
            default:
                // Bei unbekanntem Format versuche es mit processFeedContent
                $this->logger->info("Unbekannter Content-Type, versuche generische Verarbeitung");
                $items = $this->processFeedContent($content, $feed);
                break;
        }
        
        // Prüfe, ob Items extrahiert wurden
        if ($items === false || empty($items)) {
            $feed->update_feed_error(__('No items found in feed', 'athena-ai'));
            return false;
        }
        
        // Speichere die Items in der Datenbank
        return $this->saveItems($feed, $items);
    }
    
    /**
     * Gibt das aktuelle Datum und die Uhrzeit im MySQL-Format zurück.
     * Fallback für die WordPress-Funktion current_time().
     *
     * @param string|null $type Format-Typ (default: 'mysql').
     * @return string Formatiertes Datum und Uhrzeit.
     */
    private function getCurrentTime(?string $type = 'mysql'): string {
        // Behandle NULL-Werte
        $type = $type ?? 'mysql';
        
        if (function_exists('current_time')) {
            return \current_time($type);
        }
        
        // Fallback: Aktuelles Datum im MySQL-Format
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Kodiert ein Array oder Objekt als JSON.
     * Fallback für die WordPress-Funktion wp_json_encode().
     *
     * @param mixed $data Zu kodierende Daten.
     * @param int|null $options JSON-Kodierungsoptionen.
     * @return string|false JSON-String oder false bei Fehler.
     */
    private function jsonEncode($data, ?int $options = 0) {
        // Behandle NULL-Werte
        $options = $options ?? 0;
        
        if (function_exists('wp_json_encode')) {
            return \wp_json_encode($data, $options);
        }
        
        // Fallback: Standard-PHP-Funktion mit Fehlerbehandlung
        $json = \json_encode($data, $options | JSON_UNESCAPED_UNICODE);
        if ($json === false && json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error("JSON-Kodierungsfehler: " . json_last_error_msg());
            return false;
        }
        
        return $json;
    }
    
    /**
     * Escaped einen String für die Verwendung in JavaScript.
     * Fallback für die WordPress-Funktion esc_js().
     *
     * @param string|null $text Zu escapender Text.
     * @return string Escapeter Text.
     */
    private function escapeJs(?string $text): string {
        // Behandle NULL-Werte
        if ($text === null) {
            return '';
        }
        
        if (function_exists('esc_js')) {
            return \esc_js($text);
        }
        
        // Fallback: Eigene Implementierung
        $text = str_replace("\\", "\\\\", $text);
        $text = str_replace("'", "\\'", $text);
        $text = str_replace('"', '\\"', $text);
        $text = str_replace("\r", "\\r", $text);
        $text = str_replace("\n", "\\n", $text);
        $text = str_replace("<", "\\x3C", $text); // Verhindert </script>-Angriffe
        $text = str_replace(">", "\\x3E", $text);
        $text = str_replace("&", "\\x26", $text);
        
        return $text;
    }
}
