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
     * Setzt die Cache-Dauer in Sekunden.
     * 
     * @param int $seconds Cache-Dauer in Sekunden
     * @return FeedService
     */
    public function setCacheExpiration(int $seconds): FeedService {
        $this->cache_expiration = $seconds;
        return $this;
    }
    
    /**
     * Generiert einen eindeutigen Cache-Schlüssel für eine Feed-URL.
     * 
     * @param string $url Die Feed-URL
     * @return string Der Cache-Schlüssel
     */
    private function generateCacheKey(string $url): string {
        $key = 'athena_feed_cache_' . md5($url);
        
        // Registriere die URL im URL-Registry
        $this->registerUrlMapping($url, $key);
        
        return $key;
    }
    
    /**
     * Registriert eine URL-zu-Cache-Key-Zuordnung im Registry.
     * 
     * @param string $url Die Feed-URL
     * @param string $cache_key Der Cache-Schlüssel
     * @return bool Erfolg oder Misserfolg
     */
    private function registerUrlMapping(string $url, string $cache_key): bool {
        // Wenn in WordPress-Umgebung, nutze Options API
        if (function_exists('update_option') && function_exists('get_option')) {
            $registry_key = 'athena_feed_url_registry';
            
            // Hole das bestehende Registry
            $registry = get_option($registry_key, []);
            
            // Aktualisiere oder füge die Zuordnung hinzu
            $registry[$cache_key] = $url;
            
            // Speichere das aktualisierte Registry
            return update_option($registry_key, $registry);
        }
        
        // Fallback: Dateibasiertes Registry
        $registry_file = sys_get_temp_dir() . '/athena_feed_cache/url_registry.php';
        
        // Sicherstellen, dass das Verzeichnis existiert
        $cache_dir = sys_get_temp_dir() . '/athena_feed_cache';
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        // Lade das bestehende Registry
        $registry = [];
        if (file_exists($registry_file)) {
            $registry_content = file_get_contents($registry_file);
            if ($registry_content) {
                // Entferne PHP-Header, falls vorhanden
                $registry_content = preg_replace('/^<\?php.+?\?>/s', '', $registry_content);
                $registry = unserialize($registry_content) ?: [];
            }
        }
        
        // Aktualisiere oder füge die Zuordnung hinzu
        $registry[$cache_key] = $url;
        
        // Speichere das aktualisierte Registry mit PHP-Header für Sicherheit
        $registry_content = '<?php exit; ?>' . serialize($registry);
        return file_put_contents($registry_file, $registry_content) !== false;
    }
    
    /**
     * Holt eine URL aus dem Registry basierend auf dem Cache-Schlüssel.
     * 
     * @param string $cache_key Der Cache-Schlüssel
     * @return string|false Die URL oder false, wenn nicht gefunden
     */
    private function getUrlFromRegistry(string $cache_key): string|false {
        // Wenn in WordPress-Umgebung, nutze Options API
        if (function_exists('get_option')) {
            $registry_key = 'athena_feed_url_registry';
            
            // Hole das Registry
            $registry = get_option($registry_key, []);
            
            // Prüfe, ob der Schlüssel existiert
            return $registry[$cache_key] ?? false;
        }
        
        // Fallback: Dateibasiertes Registry
        $registry_file = sys_get_temp_dir() . '/athena_feed_cache/url_registry.php';
        
        if (!file_exists($registry_file)) {
            return false;
        }
        
        // Lade das Registry
        $registry_content = file_get_contents($registry_file);
        if (!$registry_content) {
            return false;
        }
        
        // Entferne PHP-Header, falls vorhanden
        $registry_content = preg_replace('/^<\?php.+?\?>/s', '', $registry_content);
        $registry = unserialize($registry_content) ?: [];
        
        // Prüfe, ob der Schlüssel existiert
        return $registry[$cache_key] ?? false;
    }
    
    /**
     * Speichert einen Feed-Inhalt im Cache.
     * 
     * @param string $url Die Feed-URL
     * @param string $content Der Feed-Inhalt
     * @return bool Erfolg oder Misserfolg
     */
    private function cacheContent(string $url, string $content): bool {
        $cache_key = $this->generateCacheKey($url);
        
        // Wenn in WordPress-Umgebung, nutze Transients API
        if (function_exists('set_transient')) {
            return set_transient($cache_key, $content, $this->cache_expiration);
        }
        
        // Fallback: Dateibasiertes Caching
        return $this->fileCache('set', $cache_key, $content);
    }
    
    /**
     * Holt einen Feed-Inhalt aus dem Cache.
     * 
     * @param string $url Die Feed-URL
     * @return string|false Der Feed-Inhalt oder false, wenn nicht im Cache
     */
    private function getCachedContent(string $url) {
        $cache_key = $this->generateCacheKey($url);
        
        // Wenn in WordPress-Umgebung, nutze Transients API
        if (function_exists('get_transient')) {
            return get_transient($cache_key);
        }
        
        // Fallback: Dateibasiertes Caching
        return $this->fileCache('get', $cache_key);
    }
    
    /**
     * Löscht einen Feed-Inhalt aus dem Cache.
     * 
     * @param string $url Die Feed-URL
     * @return bool Erfolg oder Misserfolg
     */
    private function clearCache(string $url): bool {
        $cache_key = $this->generateCacheKey($url);
        
        // Wenn in WordPress-Umgebung, nutze Transients API
        if (function_exists('delete_transient')) {
            return delete_transient($cache_key);
        }
        
        // Fallback: Dateibasiertes Caching
        return $this->fileCache('delete', $cache_key);
    }
    
    /**
     * Dateibasiertes Caching als Fallback, wenn WordPress Transients nicht verfügbar sind.
     * 
     * @param string $action Die Aktion ('set', 'get', 'delete')
     * @param string $key Der Cache-Schlüssel
     * @param string|null $data Die zu speichernden Daten (nur für 'set')
     * @return mixed Die Daten oder Erfolgsstatus
     */
    private function fileCache(string $action, string $key, ?string $data = null) {
        // Cache-Verzeichnis erstellen/bestimmen
        $cache_dir = sys_get_temp_dir() . '/athena_feed_cache';
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        $file_path = $cache_dir . '/' . $key;
        
        switch ($action) {
            case 'set':
                // Speichere Daten mit Ablaufzeit
                $cache_data = [
                    'expires' => time() + $this->cache_expiration,
                    'content' => $data
                ];
                return file_put_contents($file_path, serialize($cache_data)) !== false;
                
            case 'get':
                if (!file_exists($file_path)) {
                    return false;
                }
                
                $cache_data = unserialize(file_get_contents($file_path));
                
                // Prüfe, ob der Cache abgelaufen ist
                if ($cache_data['expires'] < time()) {
                    unlink($file_path);
                    return false;
                }
                
                return $cache_data['content'];
                
            case 'delete':
                if (file_exists($file_path)) {
                    return unlink($file_path);
                }
                return true;
                
            default:
                return false;
        }
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
        $this->logger->info("Verarbeite tagesschau.de Feed mit spezieller Methode");
        
        // 1. RDF-Format: Besorge die item IDs aus der Sequenz
        $resource_ids = [];
        $seq_pattern = '/<rdf:Seq>(.*?)<\/rdf:Seq>/s';
        $resource_pattern = '/<rdf:li rdf:resource="([^"]+)"/';
        
        if (preg_match($seq_pattern, $content, $seq_match) && 
            preg_match_all($resource_pattern, $seq_match[1], $resources)) {
            
            $resource_ids = $resources[1];
            $this->logger->info("Gefunden: " . count($resource_ids) . " Links in RDF-Sequenz");
        }
        
        // 2. Extrahiere die tatsächlichen Items basierend auf den IDs
        foreach ($resource_ids as $id) {
            // Pattern, um das entsprechende Item zu finden
            $item_pattern = '/<item[^>]*rdf:about="' . preg_quote($id, '/') . '"[^>]*>(.*?)<\/item>/s';
            
            if (preg_match($item_pattern, $content, $item_match)) {
                $item_content = $item_match[1];
                
                // Extrahiere Titel
                $title = '';
                if (preg_match('/<title[^>]*>(.*?)<\/title>/s', $item_content, $title_match)) {
                    $title = trim(html_entity_decode(strip_tags($title_match[1])));
                }
                
                // Extrahiere Link
                $link = '';
                if (preg_match('/<link[^>]*>(.*?)<\/link>/s', $item_content, $link_match)) {
                    $link = trim($link_match[1]);
                }
                
                // Extrahiere Beschreibung
                $description = '';
                if (preg_match('/<description[^>]*>(.*?)<\/description>/s', $item_content, $desc_match)) {
                    $description = trim(html_entity_decode(strip_tags($desc_match[1])));
                }
                
                // Extrahiere Content (falls vorhanden)
                $content_encoded = '';
                if (preg_match('/<content:encoded[^>]*>(.*?)<\/content:encoded>/s', $item_content, $content_match)) {
                    $content_encoded = trim(html_entity_decode(strip_tags($content_match[1])));
                }
                
                // Extrahiere GUID
                $guid = '';
                if (preg_match('/<guid[^>]*>(.*?)<\/guid>/s', $item_content, $guid_match)) {
                    $guid = trim($guid_match[1]);
                } else {
                    // Fallback: Wenn keine GUID, verwende ID
                    $guid = $id;
                }
                
                // Extrahiere Datum (bevorzuge dc:date über pubDate)
                $date = '';
                if (preg_match('/<dc:date[^>]*>(.*?)<\/dc:date>/s', $item_content, $date_match)) {
                    $date = trim($date_match[1]);
                } elseif (preg_match('/<pubDate[^>]*>(.*?)<\/pubDate>/s', $item_content, $date_match)) {
                    $date = trim($date_match[1]);
                }
                
                // Konvertiere ISO-Datum in MySQL-Format wenn nötig
                if (!empty($date)) {
                    try {
                        $date_obj = new \DateTime($date);
                        $date = $date_obj->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $this->logger->warn("Fehler beim Parsen des Datums: " . $e->getMessage());
                        $date = $this->getCurrentTime();
                    }
                } else {
                    $date = $this->getCurrentTime();
                }
                
                // Erstelle das Item-Array
                $item = [
                    'title' => $title,
                    'link' => $link,
                    'date' => $date,
                    'author' => 'tagesschau.de',
                    'content' => !empty($content_encoded) ? $content_encoded : $description,
                    'description' => $description,
                    'permalink' => $link,
                    'guid' => $guid,
                    'id' => $guid,
                    'thumbnail' => null,
                    'categories' => ['Nachrichten']
                ];
                
                // Wenn Titel oder Beschreibung vorhanden sind, füge das Item hinzu
                if (!empty($title) || !empty($description)) {
                    $items[] = $item;
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
                'guid' => 'tagesschau-dummy-' . time(),
                'id' => 'tagesschau-dummy-' . time(),
                'thumbnail' => null,
                'categories' => ['Nachrichten']
            ];
        }
        
        $this->logger->info("Tagesschau.de Feed erfolgreich verarbeitet: " . count($items) . " Items extrahiert");
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
     * @param bool $force_refresh Whether to force refresh the cache (default: false)
     * @return bool True if successful, false otherwise
     */
    public function fetch_and_process_feed(Feed $feed, bool $verbose_console = false, bool $force_refresh = false): bool {
        // Aktualisiere verbose mode entsprechend dem Parameter
        $this->setVerboseMode($verbose_console);
        
        $url = $feed->get_url();
        if (empty($url)) {
            $feed->update_feed_error(__('Feed URL is empty', 'athena-ai'));
            return false;
        }

        // Prüfe, ob der Feed im Cache ist und kein Refresh erzwungen wird
        $content = null;
        if (!$force_refresh) {
            $content = $this->getCachedContent($url);
            if ($content !== false) {
                $this->logger->info("Feed aus Cache geladen: " . $url);
            }
        }

        // Wenn nicht im Cache oder Refresh erzwungen, hole den Feed
        if ($content === false || $content === null) {
            $this->logger->info("Feed wird von Quelle geladen: " . $url);
            $content = $this->http_client->fetch($url);
            
            // Wenn erfolgreich geholt, im Cache speichern
            if ($content !== null && !empty($content)) {
                $this->cacheContent($url, $content);
                $this->logger->info("Feed in Cache gespeichert: " . $url);
            }
        }

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
     * Löscht alle Cache-Einträge.
     * Nützlich für Wartungsaufgaben oder Problemlösungen.
     * 
     * @return bool Erfolg oder Misserfolg
     */
    public function clearAllCaches(): bool {
        // Wenn in WordPress-Umgebung, nutze WP-Funktionen
        if (function_exists('delete_transient') && function_exists('get_option')) {
            global $wpdb;
            
            // Finde alle Transients mit unserem Präfix
            $transients = $wpdb->get_col(
                "SELECT option_name FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_athena_feed_cache_%'"
            );
            
            foreach ($transients as $transient) {
                // Konvertiere _transient_key zu key
                $transient_name = str_replace('_transient_', '', $transient);
                delete_transient($transient_name);
            }
            
            return true;
        }
        
        // Fallback: Lösche alle Cache-Dateien im Cache-Verzeichnis
        $cache_dir = sys_get_temp_dir() . '/athena_feed_cache';
        if (!file_exists($cache_dir)) {
            return true; // Verzeichnis existiert nicht, nichts zu löschen
        }
        
        $success = true;
        foreach (glob($cache_dir . '/*') as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
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

    /**
     * Manuelle Vorladung (Prefetching) eines Feeds ohne sofortige Verarbeitung.
     * Lädt den Feed-Inhalt und speichert ihn im Cache für spätere Verarbeitung.
     *
     * @param string $url Die Feed-URL
     * @param bool $force_refresh Ob der Cache ignoriert werden soll (default: false)
     * @return bool True bei erfolgreichem Caching, sonst false
     */
    public function prefetchFeed(string $url, bool $force_refresh = false): bool {
        if (empty($url)) {
            $this->logger->error("Prefetch: Feed URL ist leer");
            return false;
        }
        
        // Prüfe, ob der Feed bereits im Cache ist und kein Refresh erzwungen wird
        if (!$force_refresh && $this->getCachedContent($url) !== false) {
            $this->logger->info("Prefetch: Feed bereits im Cache: " . $url);
            return true;
        }
        
        // Hole Feed-Inhalt
        $this->logger->info("Prefetch: Lade Feed: " . $url);
        $content = $this->http_client->fetch($url);
        
        // Wenn erfolgreich geholt, im Cache speichern
        if ($content !== null && !empty($content)) {
            $result = $this->cacheContent($url, $content);
            $this->logger->info("Prefetch: Feed in Cache gespeichert: " . $url);
            return $result;
        }
        
        $this->logger->error("Prefetch: Konnte Feed nicht laden: " . $url);
        $this->logger->error("Prefetch: Fehler: " . $this->http_client->get_last_error());
        return false;
    }

    /**
     * Batchverarbeitung für Feeds.
     * Verarbeitet mehrere Feeds nacheinander.
     *
     * @param array $feeds Array mit Feed-Objekten oder Feed-URLs
     * @param bool $use_cache Ob der Cache verwendet werden soll (default: true)
     * @param bool $verbose_console Ob Konsolen-Ausgaben erfolgen sollen (default: false)
     * @return array Ergebnisse der Verarbeitung ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function batchProcessFeeds(array $feeds, bool $use_cache = true, bool $verbose_console = false): array {
        $this->setVerboseMode($verbose_console);
        $this->logger->info("Batch-Verarbeitung für " . count($feeds) . " Feeds gestartet");
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        
        foreach ($feeds as $feed) {
            // Bestimme Feed-Objekt und URL
            $feed_obj = null;
            $feed_url = '';
            
            if (is_string($feed)) {
                // Wenn es eine URL ist, versuche einen temporären Feed zu erstellen
                $feed_url = $feed;
                $feed_obj = $this->createTemporaryFeed($feed_url);
            } elseif (is_object($feed) && method_exists($feed, 'get_url')) {
                // Wenn es ein Feed-Objekt ist
                $feed_obj = $feed;
                $feed_url = $feed->get_url();
            } else {
                $this->logger->error("Batch: Ungültiger Feed-Typ übersprungen");
                $results['failed']++;
                $results['errors'][] = "Ungültiger Feed-Typ";
                continue;
            }
            
            if (empty($feed_url)) {
                $this->logger->error("Batch: Feed ohne URL übersprungen");
                $results['failed']++;
                $results['errors'][] = "Feed ohne URL";
                continue;
            }
            
            // Verarbeite den Feed
            $this->logger->info("Batch: Verarbeite Feed: " . $feed_url);
            $success = $this->fetch_and_process_feed($feed_obj, $verbose_console, !$use_cache);
            
            if ($success) {
                $results['success']++;
                $this->logger->info("Batch: Feed erfolgreich verarbeitet: " . $feed_url);
            } else {
                $results['failed']++;
                $error = method_exists($feed_obj, 'get_last_error') ? $feed_obj->get_last_error() : "Unbekannter Fehler";
                $results['errors'][] = $feed_url . ": " . $error;
                $this->logger->error("Batch: Fehler bei Feed: " . $feed_url . " - " . $error);
            }
        }
        
        $this->logger->info("Batch-Verarbeitung abgeschlossen. Erfolge: " . $results['success'] . ", Fehler: " . $results['failed']);
        return $results;
    }

    /**
     * Erstellt ein temporäres Feed-Objekt für die Verarbeitung.
     * 
     * @param string $url Die Feed-URL
     * @return object Ein einfaches Feed-Objekt-Äquivalent
     */
    private function createTemporaryFeed(string $url) {
        // Erstelle ein einfaches Objekt mit den notwendigen Methoden
        return new class($url) {
            private $url;
            private $post_id;
            private $last_error = '';
            
            public function __construct($url, $post_id = 0) {
                $this->url = $url;
                $this->post_id = $post_id ?: rand(10000, 99999);
            }
            
            public function get_url() {
                return $this->url;
            }
            
            public function get_post_id() {
                return $this->post_id;
            }
            
            public function update_feed_error($error) {
                $this->last_error = $error;
                return true;
            }
            
            public function update_last_checked() {
                return true;
            }
            
            public function get_last_error() {
                return $this->last_error;
            }
        };
    }

    /**
     * Alle im Cache befindlichen Feeds verarbeiten.
     * Nützlich für geplante Aufgaben oder Massenupdates.
     *
     * @param bool $verbose_console Ob Konsolen-Ausgaben erfolgen sollen (default: false)
     * @return array Ergebnisse der Verarbeitung wie bei batchProcessFeeds
     */
    public function processCachedFeeds(bool $verbose_console = false): array {
        $this->setVerboseMode($verbose_console);
        $this->logger->info("Verarbeitung aller zwischengespeicherten Feeds gestartet");
        
        $feed_urls = $this->getAllCachedFeeds();
        if (empty($feed_urls)) {
            $this->logger->info("Keine zwischengespeicherten Feeds gefunden");
            return [
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ];
        }
        
        $this->logger->info(count($feed_urls) . " zwischengespeicherte Feeds gefunden");
        return $this->batchProcessFeeds($feed_urls, true, $verbose_console);
    }

    /**
     * Holt alle im Cache befindlichen Feed-URLs.
     *
     * @return array Array mit Feed-URLs
     */
    private function getAllCachedFeeds(): array {
        $feed_urls = [];
        
        // Wenn in WordPress-Umgebung, nutze WP-Funktionen
        if (function_exists('get_option')) {
            global $wpdb;
            
            // Hole URL-Registry
            $registry = get_option('athena_feed_url_registry', []);
            
            // Finde alle Transients mit unserem Präfix
            $transients = $wpdb->get_col(
                "SELECT option_name FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_athena_feed_cache_%'"
            );
            
            foreach ($transients as $transient) {
                // Extrahiere Cache-Schlüssel
                $transient_name = str_replace('_transient_', '', $transient);
                
                // Verwende Registry, um URL zu finden
                if (isset($registry[$transient_name])) {
                    $feed_urls[] = $registry[$transient_name];
                }
            }
        } else {
            // Fallback: Dateibasiertes Registry und Cache
            $cache_dir = sys_get_temp_dir() . '/athena_feed_cache';
            $registry_file = $cache_dir . '/url_registry.php';
            
            if (file_exists($registry_file)) {
                // Lade das Registry
                $registry_content = file_get_contents($registry_file);
                if ($registry_content) {
                    // Entferne PHP-Header, falls vorhanden
                    $registry_content = preg_replace('/^<\?php.+?\?>/s', '', $registry_content);
                    $registry = unserialize($registry_content) ?: [];
                    
                    // Prüfe welche Cache-Dateien existieren
                    foreach ($registry as $cache_key => $url) {
                        $cache_file = $cache_dir . '/' . $cache_key;
                        if (file_exists($cache_file)) {
                            $feed_urls[] = $url;
                        }
                    }
                }
            }
        }
        
        return $feed_urls;
    }
}
