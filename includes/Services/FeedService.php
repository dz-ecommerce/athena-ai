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

    /**
     * Factory-Methode zum Erstellen einer FeedService-Instanz.
     *
     * @return FeedService
     */
    public static function create(): FeedService {
        return new self(new FeedHttpClient(), LoggerService::getInstance());
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
     * Erzeugt einen lesbaren Dateinamen für eine Feed-URL
     *
     * @param string $url Die Feed-URL
     * @return string Der lesbare Dateiname
     */
    private function generateReadableFilename(string $url): string {
        // Domain extrahieren
        $domain = parse_url($url, PHP_URL_HOST);
        if (empty($domain)) {
            $domain = 'unknown';
        }

        // Eventuelle "www." Präfixe entfernen
        $domain = preg_replace('/^www\./', '', $domain);

        // Dateiname generieren: Domainname + Timestamp + Hash
        $filename =
            $this->sanitizeFileName($domain) .
            '_' .
            date('Ymd_His') .
            '_' .
            substr(md5($url), 0, 8);

        return $filename;
    }

    /**
     * Säubert einen Dateinamen von unerlaubten Zeichen.
     * Fallback für die WordPress-Funktion sanitize_file_name().
     *
     * @param string $filename Der zu säubernde Dateiname
     * @return string Der gesäuberte Dateiname
     */
    private function sanitizeFileName(string $filename): string {
        // Wenn in WordPress-Umgebung, nutze die eingebaute Funktion
        if (function_exists('sanitize_file_name')) {
            return sanitize_file_name($filename);
        }

        // Eigene Implementierung der Dateisäuberung
        // Entferne alles außer Buchstaben, Zahlen, Punkte, Bindestriche und Unterstriche
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Ersetze mehrere aufeinanderfolgende Unterstriche durch einen einzelnen
        $filename = preg_replace('/_+/', '_', $filename);

        // Kürze den Dateinamen auf maximal 100 Zeichen
        if (strlen($filename) > 100) {
            $filename = substr($filename, 0, 100);
        }

        return $filename;
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
            $result = set_transient($cache_key, $content, $this->cache_expiration);

            // Zusätzlich als Datei speichern für einfachere Diagnose
            $this->storeAsFeedFile($url, $content);

            return $result;
        }

        // Fallback: Dateibasiertes Caching mit lesbarem Dateinamen
        return $this->storeAsFeedFile($url, $content);
    }

    /**
     * Speichert einen Feed-Inhalt als separate Datei mit lesbarem Namen.
     *
     * @param string $url Die Feed-URL
     * @param string $content Der Feed-Inhalt
     * @return bool Erfolg oder Misserfolg
     */
    private function storeAsFeedFile(string $url, string $content): bool {
        // Cache-Verzeichnis erstellen/bestimmen
        $cache_dir = $this->getFeedCacheDir();
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }

        // Lesbaren Dateinamen generieren
        $readable_filename = $this->generateReadableFilename($url);
        $file_path = $cache_dir . '/' . $readable_filename . '.xml';

        // Speichere Feed-Inhalt in einer Datei
        $cache_data = [
            'url' => $url,
            'expires' => time() + $this->cache_expiration,
            'content' => $content,
        ];

        // Speichere den Header mit Informationen und den eigentlichen Inhalt
        $header =
            "<!-- \n" .
            'URL: ' .
            $this->escapeUrl($url) .
            "\n" .
            'Cached: ' .
            date('Y-m-d H:i:s') .
            "\n" .
            'Expires: ' .
            date('Y-m-d H:i:s', time() + $this->cache_expiration) .
            "\n" .
            "-->\n";

        return file_put_contents($file_path, $header . $content) !== false;
    }

    /**
     * Escaped eine URL sicher.
     * Fallback für die WordPress-Funktion esc_url().
     *
     * @param string $url Die zu escapende URL
     * @return string Die escapte URL
     */
    private function escapeUrl(string $url): string {
        // Wenn in WordPress-Umgebung, nutze die eingebaute Funktion
        if (function_exists('esc_url')) {
            return esc_url($url);
        }

        // Eigene Implementierung des URL-Escapings
        // Entferne alle nicht erlaubten Zeichen
        $url = preg_replace('/[^a-zA-Z0-9_\-.~:\/\?#\[\]@!$&\'()*+,;=%]/', '', $url);

        // Stelle sicher, dass es sich um ein gültiges Schema handelt
        $allowed_schemes = ['http', 'https', 'ftp', 'ftps', 'feed'];
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!empty($scheme) && !in_array(strtolower($scheme), $allowed_schemes)) {
            return '';
        }

        return $url;
    }

    /**
     * Gibt das Feed-Cache-Verzeichnis zurück.
     *
     * @return string Der Pfad zum Feed-Cache-Verzeichnis
     */
    public function getFeedCacheDir(): string {
        // Wenn in WordPress-Umgebung, verwende Upload-Verzeichnis
        if (function_exists('wp_upload_dir')) {
            $upload_dir = wp_upload_dir();
            $cache_dir = $upload_dir['basedir'] . '/athena_feed_cache';
        } else {
            // Fallback: Temporäres Verzeichnis des Systems
            $cache_dir = sys_get_temp_dir() . '/athena_feed_cache';
        }

        return $cache_dir;
    }

    /**
     * Holt einen Feed-Inhalt aus dem Cache.
     *
     * @param string $url Die Feed-URL
     * @return string|false Der Feed-Inhalt oder false, wenn nicht im Cache
     */
    private function getCachedContent(string $url) {
        $cache_key = $this->generateCacheKey($url);

        // Wenn in WordPress-Umgebung, nutze Transients API als Hauptquelle
        if (function_exists('get_transient')) {
            $content = get_transient($cache_key);
            if ($content !== false) {
                return $content;
            }
        }

        // Versuche, den Cache aus der Datei zu holen
        return $this->getCachedFileContent($url);
    }

    /**
     * Holt den Feed-Inhalt aus einer Cache-Datei.
     *
     * @param string $url Die Feed-URL
     * @return string|false Der Feed-Inhalt oder false, wenn nicht vorhanden/abgelaufen
     */
    private function getCachedFileContent(string $url) {
        $cache_dir = $this->getFeedCacheDir();
        if (!file_exists($cache_dir)) {
            return false;
        }

        // Finde die passende Datei anhand der URL im Header
        $files = glob($cache_dir . '/*.xml');
        foreach ($files as $file) {
            // Lese die ersten Zeilen der Datei, um die URL zu prüfen
            $handle = fopen($file, 'r');
            if ($handle) {
                $header = '';
                for ($i = 0; $i < 10; $i++) {
                    $line = fgets($handle);
                    if ($line === false) {
                        break;
                    }
                    $header .= $line;
                }
                fclose($handle);

                // Prüfe, ob die URL in der Datei enthalten ist
                if (strpos($header, 'URL: ' . $url) !== false) {
                    // Prüfe das Ablaufdatum
                    if (
                        preg_match(
                            '/Expires: (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',
                            $header,
                            $matches
                        )
                    ) {
                        $expires = strtotime($matches[1]);
                        if ($expires < time()) {
                            // Cache ist abgelaufen, lösche die Datei
                            unlink($file);
                            return false;
                        }
                    }

                    // Lese den Inhalt, ohne den Header
                    $content = file_get_contents($file);
                    $content = preg_replace('/<!--[\s\S]*?-->/', '', $content, 1);
                    return $content;
                }
            }
        }

        return false;
    }

    /**
     * Löscht einen Feed-Inhalt aus dem Cache.
     *
     * @param string $url Die Feed-URL
     * @return bool Erfolg oder Misserfolg
     */
    private function clearCache(string $url): bool {
        $cache_key = $this->generateCacheKey($url);
        $success = true;

        // Wenn in WordPress-Umgebung, nutze Transients API
        if (function_exists('delete_transient')) {
            $success = delete_transient($cache_key);
        }

        // Finde und lösche auch die Cache-Datei
        $cache_dir = $this->getFeedCacheDir();
        if (file_exists($cache_dir)) {
            $files = glob($cache_dir . '/*.xml');
            foreach ($files as $file) {
                // Lese die ersten Zeilen der Datei, um die URL zu prüfen
                $handle = fopen($file, 'r');
                if ($handle) {
                    $header = '';
                    for ($i = 0; $i < 10; $i++) {
                        $line = fgets($handle);
                        if ($line === false) {
                            break;
                        }
                        $header .= $line;
                    }
                    fclose($handle);

                    // Wenn die URL in der Datei gefunden wurde, lösche die Datei
                    if (strpos($header, 'URL: ' . $url) !== false) {
                        if (unlink($file)) {
                            $success = true;
                        }
                        break;
                    }
                }
            }
        }

        return $success;
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
        $this->logger->info('Verarbeite Feed-Inhalt...');

        // Behandle NULL-Werte
        if ($content === null) {
            $error = 'Feed-Inhalt ist null';
            $this->logger->error($error);
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error($error);
            }
            return false;
        }

        // Feed-URL für Logging
        $feed_url = method_exists($feed, 'get_url') ? $feed->get_url() : 'unknown-url';

        // Versuche, den Feed als XML zu parsen
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($content);

        if ($xml !== false) {
            $this->logger->info('Feed-Inhalt als XML erkannt.');
            $items = $this->processXmlFeed($xml, $feed);
            if (!empty($items)) {
                $this->logger->info(
                    'XML-Parsing erfolgreich, ' . count($items) . ' Items gefunden'
                );
                return $items;
            }
            $this->logger->warn('XML-Parsing ergab keine Items');
        } else {
            // XML-Parsing-Fehler protokollieren
            $errors = libxml_get_errors();
            libxml_clear_errors();

            if (!empty($errors)) {
                $error_msg = 'XML-Parsing-Fehler: ';
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
            $this->logger->info('Feed-Inhalt als JSON erkannt.');
            $items = $this->processJsonFeed($json, $feed);
            if (!empty($items)) {
                $this->logger->info(
                    'JSON-Parsing erfolgreich, ' . count($items) . ' Items gefunden'
                );
                return $items;
            }
            $this->logger->warn('JSON-Parsing ergab keine Items');
        } else {
            $error = 'JSON-Parsing-Fehler: ' . json_last_error_msg();
            $this->logger->error($error);
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error($error);
            }
        }

        // Fallback: Versuche eine vereinfachte Extraktion mit Regex
        $this->logger->info('Versuche Extraktion mit Regex als Fallback');
        $items = $this->extractItemsWithRegex($content, $feed);
        if (!empty($items)) {
            $this->logger->info(
                'Regex-Extraktion erfolgreich, ' . count($items) . ' Items gefunden'
            );
            return $items;
        }

        // Wenn alle Methoden fehlschlagen, versuche einen absoluten Fallback
        if ($this->http_client->isProblemURL($feed_url)) {
            $this->logger->warn('Erkannter problematischer Feed. Erstelle Fallback-Item.');
            // Extrahiere Domain als Anzeigename
            $domain = parse_url($feed_url, PHP_URL_HOST);
            $domain = preg_replace('/^www\./', '', $domain ?: 'Unbekannte Quelle');

            return [
                [
                    'title' => 'Aktuelle Inhalte - ' . $domain,
                    'link' => $feed_url,
                    'date' => $this->getCurrentTime(),
                    'published' => $this->getCurrentTime(),
                    'author' => $domain,
                    'content' => 'Aktuelle Inhalte - ' . $domain,
                    'description' => 'Aktuelle Inhalte - ' . $domain,
                    'permalink' => $feed_url,
                    'guid' => 'feed-emergency-' . md5($feed_url . time()),
                    'id' => 'feed-emergency-' . md5($feed_url . time()),
                    'thumbnail' => null,
                    'categories' => ['Nachrichten'],
                ],
            ];
        }

        // Wenn weder XML noch JSON, gib false zurück
        if ($this->verbose_mode) {
            $preview = substr($content, 0, 500);
            $this->logger->error("Feed-Format nicht erkannt. Inhalt-Vorschau: {$preview}...");
        } else {
            $preview = substr($content, 0, 100);
            $this->logger->error("Feed-Format nicht erkannt. Inhalt-Vorschau: {$preview}...");
        }
        if (method_exists($feed, 'update_feed_error')) {
            $feed->update_feed_error('Feed-Format nicht erkannt.');
        }
        return false;
    }

    /**
     * Extrahiert Feed-Items mit einfachen regulären Ausdrücken.
     * Wird als Fallback verwendet, wenn XML- und JSON-Parsing fehlschlägt.
     *
     * @param string $content Der Feed-Inhalt
     * @param Feed   $feed    Feed-Objekt für Fehlerbehandlung
     * @return array Array mit Feed-Items oder leeres Array bei Fehler
     */
    private function extractItemsWithRegex(string $content, Feed $feed): array {
        $items = [];
        $this->logger->info('Versuche Feed-Extraktion mit regulären Ausdrücken');

        // Speichere den Feed für Debugging-Zwecke im Verbose-Modus
        if ($this->verbose_mode) {
            $debug_file = sys_get_temp_dir() . '/feed_debug_' . time() . '.xml';
            file_put_contents($debug_file, $content);
            $this->logger->info('Feed-Inhalt für Debugging gespeichert unter: ' . $debug_file);
        }

        // 1. Extrahiere zunächst alle Items mit regulären Ausdrücken
        $item_pattern = '/<item[^>]*>.*?<\/item>/s';
        if (preg_match_all($item_pattern, $content, $item_matches)) {
            $this->logger->info('Regex fand ' . count($item_matches[0]) . ' <item>-Tags');

            foreach ($item_matches[0] as $index => $item_content) {
                $item = [];

                // Titel extrahieren
                if (preg_match('/<title>(.*?)<\/title>/s', $item_content, $title_match)) {
                    $item['title'] = trim(strip_tags($title_match[1]));
                    $this->logger->debug("Item $index: Titel gefunden: " . $item['title']);
                } else {
                    $item['title'] = 'Artikel ' . ($index + 1);
                    $this->logger->debug("Item $index: Kein Titel gefunden, verwende Fallback");
                }

                // Link extrahieren - versuche mehrere Methoden
                $item['link'] = '';

                // Methode 1: Link aus RDF:about Attribut
                if (preg_match('/rdf:about="([^"]+)"/', $item_content, $about_match)) {
                    $item['link'] = trim($about_match[1]);
                    $this->logger->debug("Item $index: Link aus rdf:about: " . $item['link']);
                }
                // Methode 2: Link aus Link-Tag
                elseif (preg_match('/<link>(.*?)<\/link>/s', $item_content, $link_match)) {
                    $item['link'] = trim($link_match[1]);
                    $this->logger->debug("Item $index: Link aus link-Tag: " . $item['link']);
                }

                // Wenn noch kein Link gefunden, verwende Fallback
                if (empty($item['link'])) {
                    // Versuche, die Feed-URL als Basis für den Link zu verwenden
                    $feed_url = method_exists($feed, 'get_url') ? $feed->get_url() : '';
                    $item['link'] = $feed_url ?: '#';
                    $this->logger->debug("Item $index: Kein Link gefunden, verwende Fallback");
                }

                // Beschreibung extrahieren
                if (
                    preg_match('/<description>(.*?)<\/description>/s', $item_content, $desc_match)
                ) {
                    $item['description'] = trim(strip_tags($desc_match[1]));
                    $this->logger->debug("Item $index: Beschreibung gefunden");
                } else {
                    $item['description'] = 'Keine Beschreibung verfügbar';
                    $this->logger->debug("Item $index: Keine Beschreibung gefunden");
                }

                // Datum extrahieren - versuche DC:date zuerst
                $item['date'] = $this->getCurrentTime();
                if (preg_match('/<dc:date>(.*?)<\/dc:date>/s', $item_content, $date_match)) {
                    try {
                        $date = new \DateTime(trim($date_match[1]));
                        $item['date'] = $date->format('Y-m-d H:i:s');
                        $this->logger->debug("Item $index: Datum aus dc:date: " . $item['date']);
                    } catch (\Exception $e) {
                        $this->logger->warn(
                            "Item $index: Fehler beim Parsen des dc:date-Datums: " .
                                $e->getMessage()
                        );
                    }
                }
                // Fallback: pubDate
                elseif (preg_match('/<pubDate>(.*?)<\/pubDate>/s', $item_content, $date_match)) {
                    try {
                        $date = new \DateTime(trim($date_match[1]));
                        $item['date'] = $date->format('Y-m-d H:i:s');
                        $this->logger->debug("Item $index: Datum aus pubDate: " . $item['date']);
                    } catch (\Exception $e) {
                        $this->logger->warn(
                            "Item $index: Fehler beim Parsen des pubDate-Datums: " .
                                $e->getMessage()
                        );
                    }
                }

                // GUID - verwende Link als Basis wenn nicht vorhanden
                if (preg_match('/<guid[^>]*>(.*?)<\/guid>/s', $item_content, $guid_match)) {
                    $item['guid'] = trim($guid_match[1]);
                    $this->logger->debug("Item $index: GUID gefunden: " . $item['guid']);
                } else {
                    // Erstelle eine eindeutige GUID basierend auf Link und Titel
                    $guid_base = $item['link'] . '#' . $item['title'];
                    $item['guid'] = md5($guid_base);
                    $this->logger->debug("Item $index: GUID generiert: " . $item['guid']);
                }

                // Autor - verwende DC:creator oder Fallback
                if (
                    preg_match('/<dc:creator>(.*?)<\/dc:creator>/s', $item_content, $creator_match)
                ) {
                    $item['author'] = trim($creator_match[1]);
                    $this->logger->debug("Item $index: Autor aus dc:creator: " . $item['author']);
                } else {
                    // Extrahiere Domain als Autor-Fallback
                    $domain = parse_url($item['link'], PHP_URL_HOST);
                    $domain = preg_replace('/^www\./', '', $domain ?: 'Unbekannt');
                    $item['author'] = $domain;
                    $this->logger->debug("Item $index: Kein Autor gefunden, verwende Fallback");
                }

                // Kategorie - versuche aus DC:subject oder category
                $categories = [];
                if (
                    preg_match_all(
                        '/<dc:subject>(.*?)<\/dc:subject>/s',
                        $item_content,
                        $subject_matches
                    )
                ) {
                    foreach ($subject_matches[1] as $category) {
                        $categories[] = trim($category);
                    }
                    $this->logger->debug(
                        "Item $index: Kategorien aus dc:subject gefunden: " . count($categories)
                    );
                } elseif (
                    preg_match_all('/<category>(.*?)<\/category>/s', $item_content, $cat_matches)
                ) {
                    foreach ($cat_matches[1] as $category) {
                        $categories[] = trim($category);
                    }
                    $this->logger->debug(
                        "Item $index: Kategorien aus category gefunden: " . count($categories)
                    );
                }

                if (empty($categories)) {
                    $categories[] = 'Allgemein';
                    $this->logger->debug(
                        "Item $index: Keine Kategorien gefunden, verwende Fallback"
                    );
                }

                // Weitere erforderliche Felder für die Datenbankkompatibilität
                $item['published'] = $item['date'];
                $item['content'] = !empty($item['description'])
                    ? $item['description']
                    : $item['title'];
                $item['permalink'] = $item['link'];
                $item['id'] = $item['guid'];
                $item['thumbnail'] = null;
                $item['categories'] = $categories;

                // Item hinzufügen, wenn es mindestens Titel ODER Link hat
                if (!empty($item['title']) || !empty($item['link'])) {
                    $items[] = $item;
                    $this->logger->info("Item $index hinzugefügt: " . $item['title']);
                }
            }
        }

        // Versuche alternative Regex-Muster, wenn keine Items gefunden wurden
        if (empty($items)) {
            $this->logger->info(
                'Keine Items mit Standard-Regex gefunden, versuche alternative Muster'
            );

            // Versuch mit einfacheren Titelsuchmustern
            $title_pattern = '/<title[^>]*>(.*?)<\/title>/s';
            if (preg_match_all($title_pattern, $content, $title_matches)) {
                $this->logger->info(
                    'Alternative Regex fand ' . count($title_matches[0]) . ' <title>-Tags'
                );

                // Erstelle einfache Items basierend auf den gefundenen Titeln
                foreach ($title_matches[1] as $index => $title) {
                    // Überspringe den ersten Titel, der wahrscheinlich der Feed-Titel ist
                    if ($index === 0) {
                        continue;
                    }

                    $title = trim(html_entity_decode(strip_tags($title)));
                    if (!empty($title)) {
                        $feed_url = method_exists($feed, 'get_url') ? $feed->get_url() : '';
                        $domain = parse_url($feed_url, PHP_URL_HOST);
                        $domain = preg_replace('/^www\./', '', $domain ?: 'Unbekannt');

                        $items[] = [
                            'title' => $title,
                            'link' => $feed_url ?: '#',
                            'description' => 'Inhalt nicht verfügbar',
                            'guid' => 'feed-item-' . md5($title . $index . time()),
                            'date' => $this->getCurrentTime(),
                            'published' => $this->getCurrentTime(),
                            'author' => $domain,
                            'permalink' => $feed_url ?: '#',
                            'content' => 'Inhalt nicht verfügbar',
                            'id' => 'feed-item-' . md5($title . $index . time()),
                            'thumbnail' => null,
                            'categories' => ['Allgemein'],
                        ];
                        $this->logger->info("Alternativer Titel $index hinzugefügt: " . $title);
                    }
                }
            }
        }

        return $items;
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
        // Standard-Verarbeitung für alle Feeds
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

        // RDF-Feed verarbeiten
        if (isset($xml->channel) && isset($xml->item)) {
            $this->logger->info('RDF-Feed erkannt. Items: ' . count($xml->item));

            foreach ($xml->item as $item) {
                $extracted = $this->extractRssItem($item, $feed);
                if (!empty($extracted)) {
                    $items[] = $extracted;
                }
            }
        }
        // RSS-Feed verarbeiten
        elseif (isset($xml->channel) && isset($xml->channel->item)) {
            $this->logger->info(
                'RSS-Feed erkannt. Titel: ' .
                    (string) $xml->channel->title .
                    ', Items: ' .
                    count($xml->channel->item)
            );

            foreach ($xml->channel->item as $item) {
                $extracted = $this->extractRssItem($item, $feed);
                if (!empty($extracted)) {
                    $items[] = $extracted;
                }
            }
        }
        // Atom-Feed verarbeiten
        elseif (isset($xml->entry)) {
            $this->logger->info(
                'Atom-Feed erkannt. Titel: ' .
                    (string) $xml->title .
                    ', Entries: ' .
                    count($xml->entry)
            );

            foreach ($xml->entry as $entry) {
                $extracted = $this->extractAtomItem($entry, $feed);
                if (!empty($extracted)) {
                    $items[] = $extracted;
                }
            }
        } else {
            // Versuche RDF-Feed mit Namespaces
            $namespaces = $xml->getNamespaces(true);
            if (!empty($namespaces) && isset($xml->item)) {
                $this->logger->info('RDF-Feed mit Namespaces erkannt. Items: ' . count($xml->item));

                foreach ($xml->item as $item) {
                    $extracted = $this->extractRssItem($item, $feed);
                    if (!empty($extracted)) {
                        $items[] = $extracted;
                    }
                }
            } else {
                if (method_exists($feed, 'update_feed_error')) {
                    $feed->update_feed_error(
                        'Unbekanntes XML-Format. Weder RSS noch Atom erkannt.'
                    );
                }
                $this->logger->error('Unbekanntes XML-Format. Weder RSS noch Atom erkannt.');
            }
        }

        if (empty($items)) {
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error('Keine Items im XML-Feed gefunden.');
            }
            $this->logger->warn('Keine Items im XML-Feed gefunden.');
        } else {
            $this->logger->info(count($items) . ' Items aus dem XML-Feed extrahiert.');
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
        $namespaces = $item->getNamespaces(true);

        // Titel
        $data['title'] = (string) $item->title;

        // Link
        $data['link'] = (string) $item->link;

        // Fallback: Wenn der Link leer ist, versuchen, ihn aus dem about-Attribut zu extrahieren (RDF)
        if (empty($data['link']) && isset($item->attributes('rdf', true)->about)) {
            $data['link'] = (string) $item->attributes('rdf', true)->about;
        }

        // Beschreibung
        $data['description'] = (string) $item->description;

        // Autor - versuche verschiedene Formate
        $data['author'] = (string) $item->author;

        // Fallback für Dublin Core
        if (empty($data['author']) && isset($namespaces['dc'])) {
            $dc = $item->children($namespaces['dc']);
            $data['author'] = (string) $dc->creator;
        }

        if (empty($data['author'])) {
            $data['author'] = 'Unbekannt';
        }

        // Veröffentlichungsdatum - versuche verschiedene Formate
        $data['published'] = (string) $item->pubDate;

        // Fallback für Dublin Core Datum
        if (empty($data['published']) && isset($namespaces['dc'])) {
            $dc = $item->children($namespaces['dc']);
            $data['published'] = (string) $dc->date;
        }

        // Konvertiere ISO-Datum in MySQL-Format wenn nötig
        if (!empty($data['published'])) {
            try {
                $date_obj = new \DateTime($data['published']);
                $data['published'] = $date_obj->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $this->logger->warn('Fehler beim Parsen des Datums: ' . $e->getMessage());
                $data['published'] = $this->getCurrentTime();
            }
        } else {
            $data['published'] = $this->getCurrentTime();
        }

        // Datum für Datenbank-Kompatibilität
        $data['date'] = $data['published'];

        // GUID
        $data['guid'] = (string) $item->guid;

        // Wenn kein GUID vorhanden, verwende Link als Fallback
        if (empty($data['guid']) && !empty($data['link'])) {
            $data['guid'] = $data['link'];
        }

        // Wenn immer noch kein GUID vorhanden, generiere einen
        if (empty($data['guid'])) {
            $data['guid'] = 'feed-item-' . md5($data['title'] . time());
        }

        // Content
        $data['content'] = !empty($data['description']) ? $data['description'] : '';

        // Weitere Felder für die Datenbank-Kompatibilität
        $data['permalink'] = $data['link'];
        $data['id'] = $data['guid'];
        $data['thumbnail'] = null;
        $data['categories'] = ['Nachrichten'];

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
        $data['title'] = (string) $entry->title;

        // Link
        $data['link'] = (string) $entry->link['href'];

        // Beschreibung
        $data['description'] = (string) $entry->summary;

        // Autor
        $data['author'] = (string) $entry->author->name;

        // Veröffentlichungsdatum
        $data['published'] = (string) $entry->published;

        // Aktualisierungsdatum
        $data['updated'] = (string) $entry->updated;

        // GUID
        $data['guid'] = (string) $entry->id;

        return $data;
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
            $this->logger->info('JSON-Feed erkannt. Items: ' . count($json['items']));

            foreach ($json['items'] as $item) {
                $extracted = $this->extractJsonFeedItem($item, $feed);
                if (!empty($extracted)) {
                    $items[] = $extracted;
                }
            }
        } else {
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error('Unbekanntes JSON-Format.');
            }
            $this->logger->error(
                'Unbekanntes JSON-Format. Weder JSON-Feed noch JSON-Array erkannt.'
            );
        }

        if (empty($items)) {
            if (method_exists($feed, 'update_feed_error')) {
                $feed->update_feed_error('Keine Items im JSON-Feed gefunden.');
            }
            $this->logger->warn('Keine Items im JSON-Feed gefunden.');
        } else {
            $this->logger->info(count($items) . ' Items aus dem JSON-Feed extrahiert.');
        }

        return $items;
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
    public function fetch_and_process_feed(
        Feed $feed,
        bool $verbose_console = false,
        bool $force_refresh = false
    ): bool {
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
                $this->logger->info('Feed aus Cache geladen: ' . $url);
            }
        }

        // Wenn nicht im Cache oder Refresh erzwungen, hole den Feed
        if ($content === false || $content === null) {
            $this->logger->info('Feed wird von Quelle geladen: ' . $url);
            $content = $this->http_client->fetch($url);

            // Wenn erfolgreich geholt, im Cache speichern
            if ($content !== null && !empty($content)) {
                $this->cacheContent($url, $content);
                $this->logger->info('Feed in Cache gespeichert: ' . $url);
            }
        }

        // Handle null or empty content
        if ($content === null || empty($content)) {
            $error_message = $this->http_client->get_last_error() ?: __('Failed to fetch feed content (empty response)', 'athena-ai');
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
        if (
            strpos($trimmed, '<?xml') !== false ||
            strpos($trimmed, '<rss') !== false ||
            strpos($trimmed, '<feed') !== false ||
            strpos($trimmed, '<channel') !== false
        ) {
            $this->logger->info('Content-Type als XML/RSS erkannt');
            return 'xml';
        }

        // Prüfe auf JSON-Format
        if (
            (strpos($trimmed, '{') === 0 || strpos($trimmed, '[') === 0) &&
            json_decode($trimmed) !== null &&
            json_last_error() === JSON_ERROR_NONE
        ) {
            $this->logger->info('Content-Type als JSON erkannt');
            return 'json';
        }

        // Wenn weder XML noch JSON erkannt wurde
        $this->logger->warn('Content-Type konnte nicht erkannt werden');
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
    private function process_feed_content(
        Feed $feed,
        ?string $content,
        string $content_type
    ): bool {
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
                    $error_msg = 'XML-Parsing-Fehler: ';
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
                $this->logger->info('Unbekannter Content-Type, versuche generische Verarbeitung');
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
        $success = true;

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
        }

        // Lösche alle Dateien im Cache-Verzeichnis
        $cache_dir = $this->getFeedCacheDir();
        if (file_exists($cache_dir)) {
            $files = glob($cache_dir . '/*.xml');
            foreach ($files as $file) {
                if (!unlink($file)) {
                    $success = false;
                }
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
            $this->logger->error('JSON-Kodierungsfehler: ' . json_last_error_msg());
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
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace("'", "\\'", $text);
        $text = str_replace('"', '\\"', $text);
        $text = str_replace("\r", "\\r", $text);
        $text = str_replace("\n", "\\n", $text);
        $text = str_replace('<', "\\x3C", $text); // Verhindert </script>-Angriffe
        $text = str_replace('>', "\\x3E", $text);
        $text = str_replace('&', "\\x26", $text);

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
            $this->logger->error('Prefetch: Feed URL ist leer');
            return false;
        }

        // Prüfe, ob der Feed bereits im Cache ist und kein Refresh erzwungen wird
        if (!$force_refresh && $this->getCachedContent($url) !== false) {
            $this->logger->info('Prefetch: Feed bereits im Cache: ' . $url);
            return true;
        }

        // Hole Feed-Inhalt
        $this->logger->info('Prefetch: Lade Feed: ' . $url);
        $content = $this->http_client->fetch($url);

        // Wenn erfolgreich geholt, im Cache speichern
        if ($content !== null && !empty($content)) {
            $result = $this->cacheContent($url, $content);
            $this->logger->info('Prefetch: Feed in Cache gespeichert: ' . $url);
            return $result;
        }

        $this->logger->error('Prefetch: Konnte Feed nicht laden: ' . $url);
        $this->logger->error('Prefetch: Fehler: ' . $this->http_client->get_last_error());
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
    public function batchProcessFeeds(
        array $feeds,
        bool $use_cache = true,
        bool $verbose_console = false
    ): array {
        $this->setVerboseMode($verbose_console);
        $this->logger->info('Batch-Verarbeitung für ' . count($feeds) . ' Feeds gestartet');

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
                $this->logger->error('Batch: Ungültiger Feed-Typ übersprungen');
                $results['failed']++;
                $results['errors'][] = 'Ungültiger Feed-Typ';
                continue;
            }

            if (empty($feed_url)) {
                $this->logger->error('Batch: Feed ohne URL übersprungen');
                $results['failed']++;
                $results['errors'][] = 'Feed ohne URL';
                continue;
            }

            // Verarbeite den Feed
            $this->logger->info('Batch: Verarbeite Feed: ' . $feed_url);
            $success = $this->fetch_and_process_feed($feed_obj, $verbose_console, !$use_cache);

            if ($success) {
                $results['success']++;
                $this->logger->info('Batch: Feed erfolgreich verarbeitet: ' . $feed_url);
            } else {
                $results['failed']++;
                $error = method_exists($feed_obj, 'get_last_error')
                    ? $feed_obj->get_last_error()
                    : 'Unbekannter Fehler';
                $results['errors'][] = $feed_url . ': ' . $error;
                $this->logger->error('Batch: Fehler bei Feed: ' . $feed_url . ' - ' . $error);
            }
        }

        $this->logger->info(
            'Batch-Verarbeitung abgeschlossen. Erfolge: ' .
                $results['success'] .
                ', Fehler: ' .
                $results['failed']
        );
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
        return new class ($url) {
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
        $this->logger->info('Verarbeitung aller zwischengespeicherten Feeds gestartet');

        $feed_urls = $this->getAllCachedFeeds();
        if (empty($feed_urls)) {
            $this->logger->info('Keine zwischengespeicherten Feeds gefunden');
            return [
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ];
        }

        $this->logger->info(count($feed_urls) . ' zwischengespeicherte Feeds gefunden');
        return $this->batchProcessFeeds($feed_urls, true, $verbose_console);
    }

    /**
     * Holt alle im Cache befindlichen Feed-URLs.
     *
     * @return array Array mit Feed-URLs
     */
    public function getAllCachedFeeds(): array {
        $feed_urls = [];

        // Priorität haben die Cache-Dateien, da sie die lesbaren Feeds enthalten
        $cache_dir = $this->getFeedCacheDir();
        if (file_exists($cache_dir)) {
            $files = glob($cache_dir . '/*.xml');
            foreach ($files as $file) {
                // Lese die ersten Zeilen der Datei, um die URL zu extrahieren
                $handle = fopen($file, 'r');
                if ($handle) {
                    $header = '';
                    for ($i = 0; $i < 10; $i++) {
                        $line = fgets($handle);
                        if ($line === false) {
                            break;
                        }
                        $header .= $line;
                    }
                    fclose($handle);

                    // Extrahiere die URL aus dem Header
                    if (preg_match('/URL: (.*?)[\r\n]/', $header, $matches)) {
                        $feed_url = trim($matches[1]);
                        if (!empty($feed_url) && !in_array($feed_url, $feed_urls)) {
                            $feed_urls[] = $feed_url;
                        }
                    }
                }
            }
        }

        // Ergänze mit URLs aus dem WordPress-Transient-Cache, falls verfügbar
        if (function_exists('get_option')) {
            $registry = get_option('athena_feed_url_registry', []);
            foreach ($registry as $key => $url) {
                if (!in_array($url, $feed_urls)) {
                    $feed_urls[] = $url;
                }
            }
        }

        return $feed_urls;
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
            $this->logger->error('Keine Items zum Speichern vorhanden.');
            return false;
        }

        $table_name = $wpdb->prefix . 'feed_raw_items';
        $feed_id = $feed->get_post_id();
        $success = true;
        $new_items = 0;
        $errors = 0;

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

        $this->logger->info('Speichere ' . count($items) . ' Feed-Items in die Datenbank...');
        $this->logger->info('Feed ID: ' . $feed_id);

        // Debug: Zeige ein Beispiel-Item
        if (!empty($items) && $this->verbose_mode) {
            $sample_item = reset($items);
            $this->logger->info('Beispiel-Item: ' . print_r($sample_item, true));
        }

        foreach ($items as $index => $item) {
            // Stelle sicher, dass alle erforderlichen Felder vorhanden sind
            $item = $this->ensureRequiredFields($item, $index);

            if (empty($item['guid'])) {
                $this->logger->warn(
                    "Item ohne GUID trotz Normalisierung übersprungen (Index: {$index})."
                );
                continue;
            }
            
            // Volltext automatisch nachladen, falls Link vorhanden und noch kein full_content
            // Deaktiviert um ungewünschte Website-Inhalte zu vermeiden
            /*
            if (!empty($item['link']) && empty($item['full_content'])) {
                $full_content = $this->extractFullTextFromUrl($item['link']);
                if (!empty($full_content)) {
                    $item['full_content'] = $full_content;
                }
            }
            */

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
            $pub_date = isset($item['date'])
                ? $item['date']
                : (isset($item['published'])
                    ? $item['published']
                    : $this->getCurrentTime());

            // Versuche, das Datum zu formatieren
            try {
                $date = new \DateTime($pub_date);
                $pub_date = $date->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $this->logger->warn(
                    'Konnte Datum nicht parsen: ' . $pub_date . ' | Fehler: ' . $e->getMessage()
                );
                $pub_date = $this->getCurrentTime();
            }

            // Bereite den JSON-Inhalt vor
            $json_content = $this->jsonEncode($item);

            if ($json_content === false) {
                $this->logger->error(
                    'Fehler beim Kodieren des Items als JSON: ' . print_r($item, true)
                );
                continue;
            }

            // Füge das Item hinzu
            $result = $wpdb->insert(
                $table_name,
                [
                    'item_hash' => $item_hash,
                    'feed_id' => $feed_id,
                    'guid' => $item['guid'],
                    'pub_date' => $pub_date,
                    'raw_content' => $json_content,
                    'created_at' => $this->getCurrentTime(),
                ],
                [
                    '%s', // item_hash
                    '%d', // feed_id
                    '%s', // guid
                    '%s', // pub_date
                    '%s', // raw_content
                    '%s', // created_at
                ]
            );

            if ($result === false) {
                $errors++;
                $db_error = $wpdb->last_error;
                $this->logger->error(
                    "Fehler beim Speichern des Items: {$db_error} | Item: " .
                        substr(print_r($item, true), 0, 200) .
                        '...'
                );
                $success = false;
            } else {
                $new_items++;
                $this->logger->debug("Item mit GUID {$item['guid']} erfolgreich gespeichert.");
            }
        }

        // Aktualisiere den Feed-Status
        if (method_exists($feed, 'update_last_checked')) {
            $feed->update_last_checked();
            $this->logger->info('Feed-Status aktualisiert.');
        }

        // Aktualisiere die Feed-Metadaten in der Datenbank
        if (isset($this->repository) && method_exists($this->repository, 'update_feed_metadata')) {
            $this->repository->update_feed_metadata($feed, $new_items);
            $this->logger->info('Feed-Metadaten aktualisiert.');
        }

        $this->logger->info(
            "Feed-Items-Speicherung abgeschlossen. Neue Items: {$new_items}, Fehler: {$errors}"
        );

        return $success;
    }

    /**
     * Stellt sicher, dass alle erforderlichen Felder im Item-Array vorhanden sind.
     *
     * @param array $item  Das Item-Array.
     * @param int   $index Der Index des Items (für Logging).
     * @return array Das normalisierte Item-Array.
     */
    private function ensureRequiredFields(array $item, int $index): array {
        // Erforderliche Felder und ihre Standardwerte
        $required_fields = [
            'title' => 'Ohne Titel (' . ($index + 1) . ')',
            'link' => '',
            'description' => '',
            'author' => 'Unbekannt',
            'published' => $this->getCurrentTime(),
            'date' => $this->getCurrentTime(),
            'guid' => '',
            'content' => '',
            'permalink' => '',
            'id' => '',
            'categories' => ['Allgemein'],
        ];

        // Fehlende Felder mit Standardwerten auffüllen
        foreach ($required_fields as $field => $default) {
            if (!isset($item[$field]) || (is_string($item[$field]) && trim($item[$field]) === '')) {
                $item[$field] = $default;
                if ($field !== 'categories') {
                    // Kategorien können leer sein
                    $this->logger->debug(
                        "Feld '{$field}' fehlt in Item {$index}, verwende Standardwert."
                    );
                }
            }
        }

        // GUID ist ein Sonderfall - wenn es fehlt, versuchen wir es zu generieren
        if (empty($item['guid'])) {
            if (!empty($item['link'])) {
                $item['guid'] = $item['link'];
                $this->logger->info("Generierte GUID aus Link für Item {$index}: {$item['link']}");
            } elseif (!empty($item['title'])) {
                $item['guid'] = md5($item['title'] . time() . $index);
                $this->logger->info(
                    "Generierte GUID aus Titel für Item {$index}: {$item['title']}"
                );
            } else {
                // Absoluter Fallback
                $item['guid'] = 'item-' . time() . '-' . $index;
                $this->logger->warn("Generierte Fallback-GUID für Item {$index}");
            }
        }

        // ID sollte mit GUID übereinstimmen
        if (empty($item['id'])) {
            $item['id'] = $item['guid'];
        }

        // Permalink sollte mit Link übereinstimmen
        if (empty($item['permalink']) && !empty($item['link'])) {
            $item['permalink'] = $item['link'];
        }

        // Content sollte mindestens die Beschreibung oder den Titel enthalten
        if (empty($item['content'])) {
            if (!empty($item['description'])) {
                $item['content'] = $item['description'];
            } else {
                $item['content'] = $item['title'];
            }
        }

        return $item;
    }

    /**
     * Extrahiert den Volltext einer News-Seite anhand der Link-URL.
     *
     * @param string $url Die URL der News-Seite
     * @return string|null Der extrahierte Hauptinhalt oder null bei Fehler
     */
    private function extractFullTextFromUrl(string $url): ?string {
        try {
            $html = $this->http_client->fetch($url);
            if (empty($html)) {
                return null;
            }
            // Nur den Body-Text extrahieren (ohne Readability)
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
                $body = strip_tags($matches[1]);
                return trim($body);
            }
            // Fallback: Gesamten HTML-Inhalt als Text
            return strip_tags($html);
        } catch (\Throwable $e) {
            $this->logger->warn('Fehler beim Extrahieren des Volltexts: ' . $e->getMessage());
        }
        return null;
    }
}
