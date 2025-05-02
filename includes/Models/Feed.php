<?php
/**
 * Feed Model class
 *
 * @package AthenaAI\Models
 */

declare(strict_types=1);

namespace AthenaAI\Models;

use AthenaAI\Services\FeedService;
use AthenaAI\Repositories\FeedRepository;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Model class for feeds.
 * Represents a feed as a data object without direct database operations.
 */
class Feed {
    /**
     * Feed ID (post ID in WordPress)
     *
     * @var int|null
     */
    private ?int $post_id = null;

    /**
     * Feed URL
     *
     * @var string|null
     */
    private ?string $url = null;

    /**
     * Last error message
     *
     * @var string
     */
    private string $last_error = '';

    /**
     * When the feed was last checked
     *
     * @var \DateTime|null
     */
    private ?\DateTime $last_checked = null;

    /**
     * Update interval in seconds
     *
     * @var int
     */
    private int $update_interval = 3600; // Standard: 1 Stunde

    /**
     * Whether the feed is active
     *
     * @var bool
     */
    private bool $active = true;

    /**
     * Constructor for the Feed class
     *
     * @param string $url            The feed URL
     * @param int    $update_interval The update interval in seconds
     * @param bool   $active         Whether the feed is active
     */
    public function __construct(string $url, int $update_interval = 3600, bool $active = true) {
        $this->url = $url;
        $this->update_interval = $update_interval;
        $this->active = $active;
    }

    /**
     * Get the last error message
     *
     * @return string The last error message
     */
    public function get_last_error(): string {
        return $this->last_error;
    }

    /**
     * Set the last error message
     *
     * @param string|null $error The error message
     * @return self
     */
    public function set_last_error(?string $error): self {
        $this->last_error = $error ?? 'Unbekannter Fehler';
        return $this;
    }

    /**
     * Aktualisiert die Fehlermeldung im Objekt und in der Datenbank
     *
     * @param string|null $error Die Fehlermeldung
     * @return self
     */
    public function update_feed_error(?string $error): self {
        $this->set_last_error($error ?? 'Unbekannter Fehler');

        // Wenn eine Post-ID vorhanden ist, aktualisiere den Fehler in der Datenbank
        if ($this->post_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'feed_metadata';

            // Prüfe, ob die Tabelle existiert
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

            if ($table_exists) {
                // Prüfe, ob bereits ein Eintrag für diesen Feed existiert
                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table_name} WHERE feed_id = %d",
                        $this->post_id
                    )
                );

                if ($exists) {
                    // Aktualisiere den bestehenden Eintrag
                    $wpdb->update(
                        $table_name,
                        ['last_error' => $error],
                        ['feed_id' => $this->post_id],
                        ['%s'],
                        ['%d']
                    );
                } else {
                    // Erstelle einen neuen Eintrag
                    $wpdb->insert(
                        $table_name,
                        [
                            'feed_id' => $this->post_id,
                            'last_error' => $error,
                            'last_fetched' => \current_time('mysql'),
                            'fetch_count' => 0,
                        ],
                        ['%d', '%s', '%s', '%d']
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Get the feed URL
     *
     * @return string|null The feed URL
     */
    public function get_url(): ?string {
        return $this->url;
    }

    /**
     * Set the feed URL
     *
     * @param string $url The new feed URL
     * @return self
     */
    public function set_url(string $url): self {
        $this->url = $url;
        return $this;
    }

    /**
     * Get the feed's post ID
     *
     * @return int|null The feed's post ID
     */
    public function get_post_id(): ?int {
        return $this->post_id;
    }

    /**
     * Set the feed's post ID
     *
     * @param int $post_id The feed's post ID
     * @return self
     */
    public function set_post_id(int $post_id): self {
        $this->post_id = $post_id;
        return $this;
    }

    /**
     * Gibt den Zeitstempel des letzten Abrufs zurück
     *
     * @return \DateTime|null
     */
    public function get_last_checked(): ?\DateTime {
        // Wenn Objekt bereits einen Wert hat, verwende diesen
        if ($this->last_checked instanceof \DateTime) {
            return $this->last_checked;
        }

        // Versuche den Wert aus der Datenbank zu laden
        if ($this->post_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'feed_metadata';

            // Prüfe zunächst in der feed_metadata Tabelle
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

            if ($table_exists) {
                $last_fetched = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT last_fetched FROM {$table_name} WHERE feed_id = %d",
                        $this->post_id
                    )
                );

                if (!empty($last_fetched)) {
                    try {
                        $this->last_checked = new \DateTime($last_fetched);
                        return $this->last_checked;
                    } catch (\Exception $e) {
                        // Fehler bei der Umwandlung, ignorieren und weiter versuchen
                    }
                }
            }

            // Wenn kein Wert in der Tabelle gefunden, prüfe das post_meta
            $meta_value = \get_post_meta($this->post_id, '_athena_feed_last_checked', true);
            if (!empty($meta_value)) {
                try {
                    $this->last_checked = new \DateTime($meta_value);
                    return $this->last_checked;
                } catch (\Exception $e) {
                    // Fehler bei der Umwandlung, ignorieren
                }
            }
        }

        // Kein Wert gefunden oder keine Post-ID
        return null;
    }

    /**
     * Set the last checked timestamp
     *
     * @param \DateTime $datetime The last checked timestamp
     * @return self
     */
    public function set_last_checked(\DateTime $datetime): self {
        $this->last_checked = $datetime;
        return $this;
    }

    /**
     * Aktualisiert den Zeitstempel des letzten Abrufs im Objekt und in der Datenbank
     *
     * @return self
     */
    public function update_last_checked(): self {
        $now = new \DateTime();
        $this->set_last_checked($now);

        // Wenn eine Post-ID vorhanden ist, aktualisiere den Zeitstempel in der Datenbank
        if ($this->post_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'feed_metadata';

            // Prüfe, ob die Tabelle existiert
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

            if ($table_exists) {
                // Aktuelles Datum/Uhrzeit im MySQL-Format
                $current_time = \current_time('mysql');

                // Aktualisiere auch das post_meta für Kompatibilität
                \update_post_meta($this->post_id, '_athena_feed_last_checked', $current_time);

                // Prüfe, ob bereits ein Eintrag für diesen Feed existiert
                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table_name} WHERE feed_id = %d",
                        $this->post_id
                    )
                );

                if ($exists) {
                    // Aktualisiere den bestehenden Eintrag
                    $wpdb->update(
                        $table_name,
                        [
                            'last_fetched' => $current_time,
                            'fetch_count' =>
                                $wpdb->get_var(
                                    $wpdb->prepare(
                                        "SELECT fetch_count FROM {$table_name} WHERE feed_id = %d",
                                        $this->post_id
                                    )
                                ) + 1,
                        ],
                        ['feed_id' => $this->post_id],
                        ['%s', '%d'],
                        ['%d']
                    );
                } else {
                    // Erstelle einen neuen Eintrag
                    $wpdb->insert(
                        $table_name,
                        [
                            'feed_id' => $this->post_id,
                            'last_fetched' => $current_time,
                            'fetch_count' => 1,
                            'last_error' => '',
                        ],
                        ['%d', '%s', '%d', '%s']
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Get the update interval
     *
     * @return int The update interval in seconds
     */
    public function get_update_interval(): int {
        return $this->update_interval;
    }

    /**
     * Set the update interval
     *
     * @param int $interval The update interval in seconds
     * @return self
     */
    public function set_update_interval(int $interval): self {
        $this->update_interval = $interval;
        return $this;
    }

    /**
     * Check if the feed is active
     *
     * @return bool Whether the feed is active
     */
    public function is_active(): bool {
        return $this->active;
    }

    /**
     * Set whether the feed is active
     *
     * @param bool $active Whether the feed is active
     * @return self
     */
    public function set_active(bool $active): self {
        $this->active = $active;
        return $this;
    }

    /**
     * Generiert einen Titel für den Feed basierend auf der URL
     *
     * @return string Der generierte Titel
     */
    public function generate_title(): string {
        $host = \parse_url($this->url, PHP_URL_HOST);
        return $host ?: $this->url;
    }

    /**
     * Ruft den Feed ab und verarbeitet ihn
     *
     * Diese Methode verwendet den FeedService, um den Feed abzurufen und zu verarbeiten.
     * Sie aktualisiert auch die Feed-Metadaten in der Datenbank.
     *
     * @param bool $verbose_console Ob detaillierte Konsolenausgaben erzeugt werden sollen
     * @return bool Gibt true zurück, wenn der Feed erfolgreich abgerufen und verarbeitet wurde, sonst false
     */
    public function fetch(bool $verbose_console = false): bool {
        // Setze den letzten Fehler zurück
        $this->last_error = '';

        // Prüfe, ob eine URL vorhanden ist
        if (empty($this->url)) {
            $error = 'Keine URL zum Abrufen angegeben';
            $this->update_feed_error($error);

            if ($verbose_console) {
                // Sicheres Escaping für die Konsole
                echo '<script>console.error("Feed konnte nicht abgerufen werden: Keine URL angegeben");</script>';
            }

            return false;
        }

        try {
            // Feed-Service-Instanz erstellen und Feed abrufen
            $service = \AthenaAI\Services\FeedService::create();
            $service->setVerboseMode($verbose_console);
            $success = $service->fetch_and_process_feed($this, $verbose_console);

            return $success;
        } catch (\Exception $e) {
            // Bei einer Exception, setze eine Fehlermeldung
            $error = 'Exception beim Abrufen des Feeds: ' . $e->getMessage();
            $this->update_feed_error($error);

            if ($verbose_console) {
                // Sicheres Escaping für die Konsole
                $safe_error = function_exists('esc_js')
                    ? \esc_js($error)
                    : htmlspecialchars($error, ENT_QUOTES, 'UTF-8');

                echo '<script>console.error("' . $safe_error . '");</script>';
            }

            return false;
        }
    }

    /**
     * Holt einen Feed anhand seiner ID
     *
     * @param int $feed_id Die ID des Feeds
     * @return Feed|null Das Feed-Objekt oder null, wenn nicht gefunden
     */
    public static function get_by_id(int $feed_id): ?Feed {
        // Prüfe, ob der Feed existiert
        $feed_post = \get_post($feed_id);
        if (!$feed_post || $feed_post->post_type !== 'athena-feed') {
            return null;
        }

        // Hole die Feed-URL und andere Metadaten
        $feed_url = \get_post_meta($feed_id, '_athena_feed_url', true);
        if (empty($feed_url)) {
            return null;
        }

        $update_interval =
            (int) \get_post_meta($feed_id, '_athena_feed_update_interval', true) ?: 3600;
        $active = \get_post_meta($feed_id, '_athena_feed_active', true) !== '0';

        // Erstelle ein neues Feed-Objekt
        $feed = new self($feed_url, $update_interval, $active);
        $feed->set_post_id($feed_id);

        // Hole den letzten Fehler aus der Datenbank
        global $wpdb;
        $table_name = $wpdb->prefix . 'feed_metadata';

        // Prüfe, ob die Tabelle existiert
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

        if ($table_exists) {
            $metadata = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT last_error, last_fetched FROM {$table_name} WHERE feed_id = %d",
                    $feed_id
                )
            );

            if ($metadata) {
                if (!empty($metadata->last_error)) {
                    $feed->set_last_error($metadata->last_error);
                }

                if (!empty($metadata->last_fetched)) {
                    $last_checked = new \DateTime($metadata->last_fetched);
                    $feed->set_last_checked($last_checked);
                }
            }
        }

        return $feed;
    }

    /**
     * Holt alle Feeds, die aktualisiert werden müssen
     *
     * @return array Ein Array von Feed-Objekten, die aktualisiert werden müssen
     */
    public static function get_feeds_to_update(): array {
        // Erstelle eine Instanz des FeedRepository
        $repository = new FeedRepository();

        // Hole alle Feeds, die aktualisiert werden müssen
        return $repository->get_feeds_to_update();
    }
}
