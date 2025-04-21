<?php
/**
 * Debug-Seite für das Athena AI Plugin
 *
 * @package AthenaAI\Admin
 */

declare(strict_types=1);

namespace AthenaAI\Admin;

use AthenaAI\Models\Feed;
use AthenaAI\Repositories\FeedRepository;
use AthenaAI\Services\LoggerService;
use AthenaAI\Services\FeedService;
use AthenaAI\Services\FeedHttpClient;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasse zur Verwaltung der Debug-Seite
 */
class DebugPage {
    /**
     * Logger-Service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Konstruktor
     */
    public function __construct() {
        $this->logger = LoggerService::getInstance()->setComponent('DebugPage');
    }

    /**
     * Initialisiert die Debug-Seite
     *
     * @return void
     */
    public static function init(): void {
        $instance = new self();
        \add_action('admin_menu', [$instance, 'register_debug_page']);
    }

    /**
     * Registriert die Debug-Seite im Admin-Menü
     *
     * @return void
     */
    public function register_debug_page(): void {
        \add_submenu_page(
            'athena-feed-items', // Parent Slug
            \__('Debug', 'athena-ai'),
            \__('Debug', 'athena-ai'),
            'manage_options',
            'athena-debug',
            [$this, 'render_debug_page']
        );
    }

    /**
     * Rendert die Debug-Seite
     *
     * @return void
     */
    public function render_debug_page(): void {
        if (!\current_user_can('manage_options')) {
            \wp_die(\__('You do not have sufficient permissions to access this page.', 'athena-ai'));
        }

        // Aktiviere Debug-Modus und Verbose-Konsole
        $this->logger->setDebugMode(true)->setVerboseMode(true);

        // Verarbeite Formular-Aktionen
        $this->process_actions();

        // Hole alle aktiven Feeds
        $repository = new FeedRepository();
        $feeds = $repository->get_all_active();

        // Hole die Feeds, die aktualisiert werden müssen
        $feeds_to_update = $repository->get_feeds_to_update();

        // Hole Informationen über den Cron-Job
        $next_scheduled = \wp_next_scheduled('athena_process_feeds');
        $next_scheduled_human = $next_scheduled ? \human_time_diff(time(), $next_scheduled) : \__('Not scheduled', 'athena-ai');

        // Rendere die Seite
        ?>
        <div class="wrap">
            <h1><?php echo \esc_html__('Athena AI Debug', 'athena-ai'); ?></h1>

            <div class="notice notice-info">
                <p><?php \esc_html_e('Diese Seite zeigt Debug-Informationen für das Athena AI Plugin und ermöglicht das manuelle Testen von Funktionen.', 'athena-ai'); ?></p>
            </div>

            <h2><?php \esc_html_e('Cron-Status', 'athena-ai'); ?></h2>
            <table class="widefat">
                <tr>
                    <th><?php \esc_html_e('Nächster geplanter Feed-Abruf', 'athena-ai'); ?></th>
                    <td><?php echo \esc_html($next_scheduled_human); ?> (<?php echo \esc_html(date('Y-m-d H:i:s', $next_scheduled ?: time())); ?>)</td>
                </tr>
                <tr>
                    <th><?php \esc_html_e('Anzahl aktiver Feeds', 'athena-ai'); ?></th>
                    <td><?php echo \count($feeds); ?></td>
                </tr>
                <tr>
                    <th><?php \esc_html_e('Feeds, die aktualisiert werden müssen', 'athena-ai'); ?></th>
                    <td><?php echo \count($feeds_to_update); ?></td>
                </tr>
            </table>

            <h2><?php \esc_html_e('Aktionen', 'athena-ai'); ?></h2>
            <form method="post" action="">
                <?php \wp_nonce_field('athena_debug_actions', 'athena_debug_nonce'); ?>
                
                <p>
                    <button type="submit" name="action" value="fetch_all_feeds" class="button button-primary">
                        <?php \esc_html_e('Alle Feeds jetzt abrufen', 'athena-ai'); ?>
                    </button>
                    
                    <button type="submit" name="action" value="reschedule_cron" class="button">
                        <?php \esc_html_e('Cron-Job neu planen', 'athena-ai'); ?>
                    </button>
                </p>
            </form>

            <h2><?php \esc_html_e('Aktive Feeds', 'athena-ai'); ?></h2>
            <?php if (empty($feeds)): ?>
                <p><?php \esc_html_e('Keine aktiven Feeds gefunden.', 'athena-ai'); ?></p>
            <?php else: ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php \esc_html_e('ID', 'athena-ai'); ?></th>
                            <th><?php \esc_html_e('URL', 'athena-ai'); ?></th>
                            <th><?php \esc_html_e('Letzter Abruf', 'athena-ai'); ?></th>
                            <th><?php \esc_html_e('Aktionen', 'athena-ai'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feeds as $feed): ?>
                            <tr>
                                <td><?php echo \esc_html($feed->get_post_id()); ?></td>
                                <td><?php echo \esc_html($feed->get_url()); ?></td>
                                <td>
                                    <?php 
                                    $last_checked = $feed->get_last_checked();
                                    if ($last_checked) {
                                        echo \esc_html($last_checked->format('Y-m-d H:i:s'));
                                    } else {
                                        \esc_html_e('Nie', 'athena-ai');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="post" action="" style="display:inline;">
                                        <?php \wp_nonce_field('athena_debug_actions', 'athena_debug_nonce'); ?>
                                        <input type="hidden" name="feed_id" value="<?php echo \esc_attr((string)$feed->get_post_id()); ?>">
                                        <button type="submit" name="action" value="fetch_single_feed" class="button button-small">
                                            <?php \esc_html_e('Abrufen', 'athena-ai'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h2><?php \esc_html_e('Datenbank-Status', 'athena-ai'); ?></h2>
            <?php
            global $wpdb;
            $feed_items_table = $wpdb->prefix . 'feed_raw_items';
            $feed_metadata_table = $wpdb->prefix . 'feed_metadata';
            
            // Prüfe, ob die Tabellen existieren
            $feed_items_exists = $wpdb->get_var("SHOW TABLES LIKE '$feed_items_table'") === $feed_items_table;
            $feed_metadata_exists = $wpdb->get_var("SHOW TABLES LIKE '$feed_metadata_table'") === $feed_metadata_table;
            
            // Hole Anzahl der Feed-Items
            $feed_items_count = 0;
            if ($feed_items_exists) {
                $feed_items_count = $wpdb->get_var("SELECT COUNT(*) FROM $feed_items_table");
            }
            ?>
            
            <table class="widefat">
                <tr>
                    <th><?php \esc_html_e('Feed-Items-Tabelle', 'athena-ai'); ?></th>
                    <td>
                        <?php if ($feed_items_exists): ?>
                            <span style="color:green;">✓</span> <?php \esc_html_e('Existiert', 'athena-ai'); ?> 
                            (<?php echo \esc_html((string)$feed_items_count); ?> <?php \esc_html_e('Einträge', 'athena-ai'); ?>)
                        <?php else: ?>
                            <span style="color:red;">✗</span> <?php \esc_html_e('Existiert nicht', 'athena-ai'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php \esc_html_e('Feed-Metadata-Tabelle', 'athena-ai'); ?></th>
                    <td>
                        <?php if ($feed_metadata_exists): ?>
                            <span style="color:green;">✓</span> <?php \esc_html_e('Existiert', 'athena-ai'); ?>
                        <?php else: ?>
                            <span style="color:red;">✗</span> <?php \esc_html_e('Existiert nicht', 'athena-ai'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h2><?php \esc_html_e('Debug-Konsole', 'athena-ai'); ?></h2>
            <div id="athena-debug-console" style="background: #23282d; color: #eee; padding: 15px; font-family: monospace; height: 300px; overflow: auto; margin-bottom: 20px;">
                <div style="color: #8bc34a;">--- <?php \esc_html_e('Debug-Konsole initialisiert', 'athena-ai'); ?> ---</div>
            </div>
        </div>

        <script>
        // Füge Konsolenfunktionen hinzu
        window.athenaDebugConsole = {
            log: function(message, type) {
                type = type || 'log';
                var colors = {
                    log: '#eee',
                    info: '#8bc34a',
                    warn: '#ffc107',
                    error: '#f44336'
                };
                var color = colors[type] || colors.log;
                var console = document.getElementById('athena-debug-console');
                var line = document.createElement('div');
                line.style.color = color;
                line.textContent = message;
                console.appendChild(line);
                console.scrollTop = console.scrollHeight;
            },
            info: function(message) {
                this.log(message, 'info');
            },
            warn: function(message) {
                this.log(message, 'warn');
            },
            error: function(message) {
                this.log(message, 'error');
            }
        };

        // Überschreibe die Standard-Konsolenfunktionen, um sie auch in unserer Debug-Konsole anzuzeigen
        var originalConsole = {
            log: console.log,
            info: console.info,
            warn: console.warn,
            error: console.error
        };

        console.log = function() {
            originalConsole.log.apply(console, arguments);
            var args = Array.prototype.slice.call(arguments);
            athenaDebugConsole.log(args.join(' '));
        };

        console.info = function() {
            originalConsole.info.apply(console, arguments);
            var args = Array.prototype.slice.call(arguments);
            athenaDebugConsole.info(args.join(' '));
        };

        console.warn = function() {
            originalConsole.warn.apply(console, arguments);
            var args = Array.prototype.slice.call(arguments);
            athenaDebugConsole.warn(args.join(' '));
        };

        console.error = function() {
            originalConsole.error.apply(console, arguments);
            var args = Array.prototype.slice.call(arguments);
            athenaDebugConsole.error(args.join(' '));
        };
        </script>
        <?php
    }

    /**
     * Verarbeitet die Formular-Aktionen
     *
     * @return void
     */
    private function process_actions(): void {
        if (!isset($_POST['athena_debug_nonce']) || !\wp_verify_nonce($_POST['athena_debug_nonce'], 'athena_debug_actions')) {
            return;
        }

        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'fetch_all_feeds':
                $this->fetch_all_feeds();
                break;

            case 'fetch_single_feed':
                $feed_id = isset($_POST['feed_id']) ? (int) $_POST['feed_id'] : 0;
                if ($feed_id > 0) {
                    $this->fetch_single_feed($feed_id);
                }
                break;

            case 'reschedule_cron':
                $this->reschedule_cron();
                break;
        }
    }

    /**
     * Ruft alle Feeds ab
     *
     * @return void
     */
    private function fetch_all_feeds(): void {
        // Zeige eine Nachricht in der Debug-Konsole
        echo '<script>console.info("Starte Abruf aller Feeds...");</script>';

        // Hole alle Feeds, die aktualisiert werden müssen
        $feeds = Feed::get_feeds_to_update();
        
        echo '<script>console.info("' . count($feeds) . ' Feeds zum Aktualisieren gefunden.");</script>';

        // Erstelle den FeedService
        $feed_service = FeedService::create();
        $feed_service->setVerboseMode(true);

        // Verarbeite jeden Feed
        $success_count = 0;
        $error_count = 0;

        foreach ($feeds as $feed) {
            // Stelle sicher, dass get_url() keinen NULL-Wert zurückgibt
            $feed_url = $feed->get_url();
            $feed_url = is_string($feed_url) ? $feed_url : '';
            echo '<script>console.info("Verarbeite Feed: ' . \esc_js($feed_url) . '");</script>';
            
            try {
                // Versuche, den Feed abzurufen und zu verarbeiten
                $result = $feed->fetch(true);
                
                if ($result) {
                    $success_count++;
                    // Stelle sicher, dass get_url() keinen NULL-Wert zurückgibt
                    $feed_url = $feed->get_url();
                    $feed_url = is_string($feed_url) ? $feed_url : '';
                    echo '<script>console.info("Feed erfolgreich verarbeitet: ' . \esc_js($feed_url) . '");</script>';
                } else {
                    $error_count++;
                    // Stelle sicher, dass get_url() und get_last_error() keine NULL-Werte zurückgeben
                    $feed_url = $feed->get_url();
                    $feed_url = is_string($feed_url) ? $feed_url : '';
                    $last_error = $feed->get_last_error();
                    $last_error = is_string($last_error) ? $last_error : '';
                    echo '<script>console.error("Fehler beim Verarbeiten des Feeds: ' . \esc_js($feed_url) . ' - ' . \esc_js($last_error) . '");</script>';
                    
                    // Versuche, mehr Informationen über den Fehler zu bekommen
                    $this->debug_feed_fetch($feed);
                }
            } catch (\Exception $e) {
                $error_count++;
                // Stelle sicher, dass get_url() und getMessage() keine NULL-Werte zurückgeben
                $feed_url = $feed->get_url();
                $feed_url = is_string($feed_url) ? $feed_url : '';
                $error_message = $e->getMessage();
                $error_message = is_string($error_message) ? $error_message : '';
                echo '<script>console.error("Exception beim Verarbeiten des Feeds: ' . \esc_js($feed_url) . ' - ' . \esc_js($error_message) . '");</script>';
            }
        }

        // Zeige eine Zusammenfassung
        echo '<script>console.info("Feed-Abruf abgeschlossen. Erfolge: ' . $success_count . ', Fehler: ' . $error_count . '");</script>';
        
        // Zeige eine Admin-Nachricht
        \add_action('admin_notices', function() use ($success_count, $error_count) {
            $class = $error_count > 0 ? 'notice-warning' : 'notice-success';
            $message = sprintf(
                \__('Feed-Abruf abgeschlossen. %d Feeds erfolgreich verarbeitet, %d Fehler.', 'athena-ai'),
                $success_count,
                $error_count
            );
            echo '<div class="notice ' . $class . ' is-dismissible"><p>' . $message . '</p></div>';
        });
    }

    /**
     * Ruft einen einzelnen Feed ab
     *
     * @param int $feed_id Die Feed-ID
     * @return void
     */
    private function fetch_single_feed(int $feed_id): void {
        // Hole den Feed
        $repository = new FeedRepository();
        $feed = $repository->get_by_id($feed_id);

        if (!$feed) {
            echo '<script>console.error("Feed mit ID ' . $feed_id . ' nicht gefunden.");</script>';
            return;
        }

        // Stelle sicher, dass get_url() keinen NULL-Wert zurückgibt
        $feed_url = $feed->get_url();
        $feed_url = is_string($feed_url) ? $feed_url : '';
        echo '<script>console.info("Starte Abruf des Feeds: ' . \esc_js($feed_url) . '");</script>';

        try {
            // Versuche, den Feed abzurufen und zu verarbeiten
            $result = $feed->fetch(true);
            
            if ($result) {
                echo '<script>console.info("Feed erfolgreich verarbeitet: ' . esc_js($feed->get_url()) . '");</script>';
                
                // Zeige eine Admin-Nachricht
                \add_action('admin_notices', function() use ($feed) {
                    $message = sprintf(
                        \__('Feed "%s" erfolgreich verarbeitet.', 'athena-ai'),
                        $feed->get_url()
                    );
                    echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
                });
            } else {
                echo '<script>console.error("Fehler beim Verarbeiten des Feeds: ' . esc_js($feed->get_url()) . ' - ' . esc_js($feed->get_last_error()) . '");</script>';
                
                // Versuche, mehr Informationen über den Fehler zu bekommen
                $this->debug_feed_fetch($feed);
                
                // Zeige eine Admin-Nachricht
                \add_action('admin_notices', function() use ($feed) {
                    $message = sprintf(
                        \__('Fehler beim Verarbeiten des Feeds "%s". Siehe Debug-Konsole für Details.', 'athena-ai'),
                        $feed->get_url()
                    );
                    echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
                });
            }
        } catch (\Exception $e) {
            echo '<script>console.error("Exception beim Verarbeiten des Feeds: ' . esc_js($feed->get_url()) . ' - ' . esc_js($e->getMessage()) . '");</script>';
            
            // Zeige eine Admin-Nachricht
            \add_action('admin_notices', function() use ($feed, $e) {
                $message = sprintf(
                    \__('Exception beim Verarbeiten des Feeds "%s": %s', 'athena-ai'),
                    $feed->get_url(),
                    $e->getMessage()
                );
                echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
            });
        }
    }

    /**
     * Plant den Cron-Job neu
     *
     * @return void
     */
    private function reschedule_cron(): void {
        // Entferne den bestehenden Cron-Job
        $timestamp = \wp_next_scheduled('athena_process_feeds');
        if ($timestamp) {
            \wp_unschedule_event($timestamp, 'athena_process_feeds');
            echo '<script>console.info("Bestehender Cron-Job entfernt.");</script>';
        }

        // Plane den Cron-Job neu
        \wp_schedule_event(time(), 'hourly', 'athena_process_feeds');
        echo '<script>console.info("Cron-Job neu geplant.");</script>';

        // Zeige eine Admin-Nachricht
        \add_action('admin_notices', function() {
            $message = \__('Cron-Job erfolgreich neu geplant.', 'athena-ai');
            echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
        });
    }

    /**
     * Debuggt den Feed-Abruf
     *
     * @param Feed $feed Der Feed
     * @return void
     */
    private function debug_feed_fetch(Feed $feed): void {
        // Stelle sicher, dass get_url() keinen NULL-Wert zurückgibt
        $feed_url = $feed->get_url();
        $feed_url = is_string($feed_url) ? $feed_url : '';
        echo '<script>console.info("Starte detailliertes Debugging für Feed: ' . \esc_js($feed_url) . '");</script>';

        // Erstelle einen HTTP-Client
        $http_client = new FeedHttpClient();
        $http_client->setVerboseMode(true);

        // Versuche, den Feed-Inhalt abzurufen
        echo '<script>console.info("Versuche, Feed-Inhalt direkt abzurufen...");</script>';
        
        try {
            $content = $http_client->fetch($feed->get_url());
            
            if ($content) {
                $content_length = strlen($content);
                echo '<script>console.info("Feed-Inhalt erfolgreich abgerufen. Länge: ' . $content_length . ' Bytes");</script>';
                
                // Zeige eine Vorschau des Inhalts
                if ($content !== null && is_string($content)) {
                    $preview = substr($content, 0, 200);
                    $preview = is_string($preview) ? str_replace("\n", "", $preview) : '';
                } else {
                    $preview = '';
                }
                // Stelle sicher, dass $preview ein String ist
                $preview = is_string($preview) ? $preview : '';
                echo '<script>console.info("Inhalt-Vorschau: ' . \esc_js($preview) . '...");</script>';
                
                // Versuche, den Inhalt zu parsen
                echo '<script>console.info("Versuche, Feed-Inhalt zu parsen...");</script>';
                
                $simple_xml = @simplexml_load_string($content);
                if ($simple_xml) {
                    echo '<script>console.info("Feed-Inhalt erfolgreich als XML geparst.");</script>';
                    
                    // Zeige Informationen über den Feed
                    if (isset($simple_xml->channel)) {
                        $title = (string) $simple_xml->channel->title;
                        $item_count = count($simple_xml->channel->item);
                        echo '<script>console.info("RSS-Feed gefunden. Titel: ' . esc_js($title) . ', Items: ' . $item_count . '");</script>';
                    } elseif (isset($simple_xml->entry)) {
                        $title = (string) $simple_xml->title;
                        $entry_count = count($simple_xml->entry);
                        echo '<script>console.info("Atom-Feed gefunden. Titel: ' . esc_js($title) . ', Entries: ' . $entry_count . '");</script>';
                    } else {
                        echo '<script>console.warn("XML-Format nicht erkannt.");</script>';
                    }
                } else {
                    echo '<script>console.warn("Feed-Inhalt konnte nicht als XML geparst werden. Versuche JSON...");</script>';
                    
                    $json_data = @json_decode($content, true);
                    if (is_array($json_data) && !empty($json_data)) {
                        echo '<script>console.info("Feed-Inhalt erfolgreich als JSON geparst.");</script>';
                        
                        // Zeige Informationen über den JSON-Feed
                        $item_count = count($json_data);
                        echo '<script>console.info("JSON-Daten gefunden. Anzahl der Elemente: ' . $item_count . '");</script>';
                    } else {
                        echo '<script>console.error("Feed-Inhalt konnte weder als XML noch als JSON geparst werden.");</script>';
                        
                        // Zeige die ersten 500 Zeichen des Inhalts
                        if ($content !== null && is_string($content)) {
                            $preview = substr($content, 0, 500);
                            $preview = is_string($preview) ? str_replace("\n", "", $preview) : '';
                        } else {
                            $preview = '';
                        }
                        echo '<script>console.info("Erweiterter Inhalt: ' . esc_js($preview) . '...");</script>';
                    }
                }
            } else {
                echo '<script>console.error("Feed-Inhalt konnte nicht abgerufen werden.");</script>';
            }
        } catch (\Exception $e) {
            echo '<script>console.error("Exception beim Abrufen des Feed-Inhalts: ' . esc_js($e->getMessage()) . '");</script>';
        }

        // Prüfe die Datenbank-Tabellen
        echo '<script>console.info("Prüfe Datenbank-Tabellen...");</script>';
        
        global $wpdb;
        $feed_items_table = $wpdb->prefix . 'feed_raw_items';
        $feed_metadata_table = $wpdb->prefix . 'feed_metadata';
        
        $feed_items_exists = $wpdb->get_var("SHOW TABLES LIKE '$feed_items_table'") === $feed_items_table;
        $feed_metadata_exists = $wpdb->get_var("SHOW TABLES LIKE '$feed_metadata_table'") === $feed_metadata_table;
        
        if ($feed_items_exists) {
            echo '<script>console.info("Feed-Items-Tabelle existiert.");</script>';
            
            // Prüfe, ob es Feed-Items für diesen Feed gibt
            $feed_items_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $feed_items_table WHERE feed_id = %d",
                $feed->get_post_id()
            ));
            
            echo '<script>console.info("Anzahl der Feed-Items für diesen Feed: ' . $feed_items_count . '");</script>';
        } else {
            echo '<script>console.error("Feed-Items-Tabelle existiert nicht!");</script>';
        }
        
        if ($feed_metadata_exists) {
            echo '<script>console.info("Feed-Metadata-Tabelle existiert.");</script>';
            
            // Prüfe, ob es Metadaten für diesen Feed gibt
            $feed_metadata = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $feed_metadata_table WHERE feed_id = %d",
                $feed->get_post_id()
            ));
            
            if ($feed_metadata) {
                echo '<script>console.info("Metadaten für diesen Feed gefunden.");</script>';
                
                // Zeige die Metadaten
                $last_fetched = $feed_metadata->last_fetched ?? 'Nie';
                $fetch_count = $feed_metadata->fetch_count ?? 0;
                $last_error = $feed_metadata->last_error ?? 'Keine';
                
                echo '<script>console.info("Letzter Abruf: ' . esc_js($last_fetched) . '");</script>';
                echo '<script>console.info("Anzahl der Abrufe: ' . $fetch_count . '");</script>';
                echo '<script>console.info("Letzter Fehler: ' . esc_js($last_error) . '");</script>';
            } else {
                echo '<script>console.warn("Keine Metadaten für diesen Feed gefunden.");</script>';
            }
        } else {
            echo '<script>console.error("Feed-Metadata-Tabelle existiert nicht!");</script>';
        }
        
        echo '<script>console.info("Detailliertes Debugging abgeschlossen.");</script>';
    }
}
