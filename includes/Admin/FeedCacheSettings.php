<?php
/**
 * Feed Cache Settings
 *
 * @package AthenaAI\Admin
 */

declare(strict_types=1);

namespace AthenaAI\Admin;

use AthenaAI\Services\FeedService;
use AthenaAI\Services\LoggerService;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Feed Cache Settings Page Handler
 */
class FeedCacheSettings {
    /**
     * Feed service.
     *
     * @var FeedService
     */
    private FeedService $feed_service;

    /**
     * Logger service.
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Option group name.
     *
     * @var string
     */
    const OPTION_GROUP = 'athena_feed_cache';

    /**
     * Option name.
     *
     * @var string
     */
    const OPTION_NAME = 'athena_feed_cache_settings';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->feed_service = FeedService::create();
        $this->logger = LoggerService::getInstance()->setComponent('FeedCacheSettings');

        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);

        // AJAX-Handler registrieren
        add_action('wp_ajax_athena_clear_feed_cache', [$this, 'ajax_clear_cache']);
        add_action('wp_ajax_athena_prefetch_feeds', [$this, 'ajax_prefetch_feeds']);
        add_action('wp_ajax_athena_process_cached_feeds', [$this, 'ajax_process_cached_feeds']);
        add_action('wp_ajax_athena_read_feed_file', [$this, 'ajax_read_feed_file']);
        add_action('wp_ajax_athena_process_single_feed', [$this, 'ajax_process_single_feed']);
    }

    /**
     * Factory-Methode zum Erstellen einer FeedCacheSettings-Instanz.
     *
     * @return FeedCacheSettings
     */
    public static function create(): FeedCacheSettings {
        return new self();
    }

    /**
     * Fügt die Einstellungsseite hinzu.
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=athena_feed', // Elternseite (Feed-Liste)
            __('Feed Cache Settings', 'athena-ai'),
            __('Cache Settings', 'athena-ai'),
            'manage_options',
            'athena-feed-cache',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Registriert die Einstellungen.
     */
    public function register_settings() {
        register_setting(self::OPTION_GROUP, self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);

        add_settings_section(
            'athena_feed_cache_section',
            __('Feed Cache Settings', 'athena-ai'),
            [$this, 'render_settings_section'],
            'athena-feed-cache'
        );

        add_settings_field(
            'cache_expiration',
            __('Cache Duration (seconds)', 'athena-ai'),
            [$this, 'render_cache_expiration_field'],
            'athena-feed-cache',
            'athena_feed_cache_section'
        );

        add_settings_field(
            'prefetch_schedule',
            __('Prefetch Schedule', 'athena-ai'),
            [$this, 'render_prefetch_schedule_field'],
            'athena-feed-cache',
            'athena_feed_cache_section'
        );

        add_settings_field(
            'process_schedule',
            __('Process Schedule', 'athena-ai'),
            [$this, 'render_process_schedule_field'],
            'athena-feed-cache',
            'athena_feed_cache_section'
        );

        add_settings_field(
            'cache_actions',
            __('Cache Actions', 'athena-ai'),
            [$this, 'render_cache_actions_field'],
            'athena-feed-cache',
            'athena_feed_cache_section'
        );
    }

    /**
     * Sanitiert die Einstellungen.
     *
     * @param array $input Die Eingabewerte.
     * @return array Die sanitierten Werte.
     */
    public function sanitize_settings($input) {
        $output = [];

        // Cache-Dauer sanitieren (30 Sekunden bis 1 Woche)
        $output['cache_expiration'] = isset($input['cache_expiration'])
            ? intval($input['cache_expiration'])
            : 1800;

        if ($output['cache_expiration'] < 30) {
            $output['cache_expiration'] = 30;
        } elseif ($output['cache_expiration'] > 604800) {
            $output['cache_expiration'] = 604800;
        }

        // Prefetch-Schedule sanitieren
        $valid_schedules = ['hourly', 'twicedaily', 'daily'];
        $output['prefetch_schedule'] =
            isset($input['prefetch_schedule']) &&
            in_array($input['prefetch_schedule'], $valid_schedules)
                ? $input['prefetch_schedule']
                : 'hourly';

        // Process-Schedule sanitieren
        $output['process_schedule'] =
            isset($input['process_schedule']) &&
            in_array($input['process_schedule'], $valid_schedules)
                ? $input['process_schedule']
                : 'twicedaily';

        // Aktualisiere die Cache-Dauer im FeedService
        $this->feed_service->setCacheExpiration($output['cache_expiration']);

        // Aktualisiere die Cron-Schedules, falls nötig
        $this->update_cron_schedules($output);

        return $output;
    }

    /**
     * Aktualisiert die Cron-Zeitpläne.
     *
     * @param array $settings Die aktualisierten Einstellungen.
     */
    private function update_cron_schedules($settings) {
        // Hole die aktuellen Einstellungen
        $current_settings = get_option(self::OPTION_NAME, []);

        // Wenn sich die Zeitpläne geändert haben, aktualisiere die Cron-Jobs
        if (
            !isset($current_settings['prefetch_schedule']) ||
            !isset($current_settings['process_schedule']) ||
            $current_settings['prefetch_schedule'] !== $settings['prefetch_schedule'] ||
            $current_settings['process_schedule'] !== $settings['process_schedule']
        ) {
            // Lösche bestehende Cron-Jobs
            $prefetch_timestamp = wp_next_scheduled('athena_feed_prefetch_cron');
            if ($prefetch_timestamp) {
                wp_unschedule_event($prefetch_timestamp, 'athena_feed_prefetch_cron');
            }

            $process_timestamp = wp_next_scheduled('athena_feed_process_cron');
            if ($process_timestamp) {
                wp_unschedule_event($process_timestamp, 'athena_feed_process_cron');
            }

            // Erstelle neue Cron-Jobs mit aktualisierten Zeitplänen
            wp_schedule_event(time(), $settings['prefetch_schedule'], 'athena_feed_prefetch_cron');
            wp_schedule_event(time(), $settings['process_schedule'], 'athena_feed_process_cron');

            $this->logger->info(
                'Cron-Zeitpläne aktualisiert. Prefetch: ' .
                    $settings['prefetch_schedule'] .
                    ', Process: ' .
                    $settings['process_schedule']
            );
        }
    }

    /**
     * Rendert den Einstellungsbereich.
     */
    public function render_settings_section() {
        echo '<p>' .
            esc_html__('Configure how feeds are cached and processed.', 'athena-ai') .
            '</p>';
    }

    /**
     * Rendert das Feld für die Cache-Dauer.
     */
    public function render_cache_expiration_field() {
        $options = get_option(self::OPTION_NAME, ['cache_expiration' => 1800]);
        $value = isset($options['cache_expiration']) ? $options['cache_expiration'] : 1800;

        echo '<input type="number" id="cache_expiration" name="' .
            self::OPTION_NAME .
            '[cache_expiration]" value="' .
            esc_attr($value) .
            '" min="30" max="604800" step="1" class="regular-text" />';
        echo '<p class="description">' .
            esc_html__(
                'Time in seconds until the feed cache expires. Min: 30s, Max: 1 week (604800s)',
                'athena-ai'
            ) .
            '</p>';

        // Füge einige Voreinstellungen hinzu
        echo '<div class="preset-buttons" style="margin-top: 10px;">';
        echo '<button type="button" class="button" data-value="300">' .
            esc_html__('5 Minutes', 'athena-ai') .
            '</button> ';
        echo '<button type="button" class="button" data-value="1800">' .
            esc_html__('30 Minutes', 'athena-ai') .
            '</button> ';
        echo '<button type="button" class="button" data-value="3600">' .
            esc_html__('1 Hour', 'athena-ai') .
            '</button> ';
        echo '<button type="button" class="button" data-value="86400">' .
            esc_html__('1 Day', 'athena-ai') .
            '</button>';
        echo '</div>';

        // JavaScript für die Voreinstellungs-Buttons
        echo '<script>
            jQuery(document).ready(function($) {
                $(".preset-buttons button").click(function() {
                    $("#cache_expiration").val($(this).data("value"));
                });
            });
        </script>';
    }

    /**
     * Rendert das Feld für den Prefetch-Zeitplan.
     */
    public function render_prefetch_schedule_field() {
        $options = get_option(self::OPTION_NAME, ['prefetch_schedule' => 'hourly']);
        $value = isset($options['prefetch_schedule']) ? $options['prefetch_schedule'] : 'hourly';

        $schedules = [
            'hourly' => __('Hourly', 'athena-ai'),
            'twicedaily' => __('Twice Daily', 'athena-ai'),
            'daily' => __('Daily', 'athena-ai'),
        ];

        echo '<select id="prefetch_schedule" name="' .
            self::OPTION_NAME .
            '[prefetch_schedule]" class="regular-text">';
        foreach ($schedules as $schedule => $label) {
            echo '<option value="' .
                esc_attr($schedule) .
                '" ' .
                selected($value, $schedule, false) .
                '>' .
                esc_html($label) .
                '</option>';
        }
        echo '</select>';
        echo '<p class="description">' .
            esc_html__('How often to automatically prefetch feeds.', 'athena-ai') .
            '</p>';
    }

    /**
     * Rendert das Feld für den Process-Zeitplan.
     */
    public function render_process_schedule_field() {
        $options = get_option(self::OPTION_NAME, ['process_schedule' => 'twicedaily']);
        $value = isset($options['process_schedule']) ? $options['process_schedule'] : 'twicedaily';

        $schedules = [
            'hourly' => __('Hourly', 'athena-ai'),
            'twicedaily' => __('Twice Daily', 'athena-ai'),
            'daily' => __('Daily', 'athena-ai'),
        ];

        echo '<select id="process_schedule" name="' .
            self::OPTION_NAME .
            '[process_schedule]" class="regular-text">';
        foreach ($schedules as $schedule => $label) {
            echo '<option value="' .
                esc_attr($schedule) .
                '" ' .
                selected($value, $schedule, false) .
                '>' .
                esc_html($label) .
                '</option>';
        }
        echo '</select>';
        echo '<p class="description">' .
            esc_html__('How often to automatically process cached feeds.', 'athena-ai') .
            '</p>';
    }

    /**
     * Rendert das Feld für Cache-Aktionen.
     */
    public function render_cache_actions_field() {
        echo '<div class="cache-actions">';
        echo '<button type="button" id="clear-cache" class="button button-secondary">' .
            esc_html__('Clear All Caches', 'athena-ai') .
            '</button>';
        echo ' <button type="button" id="prefetch-feeds" class="button button-secondary">' .
            esc_html__('Prefetch All Feeds Now', 'athena-ai') .
            '</button>';
        echo ' <button type="button" id="process-feeds" class="button button-primary">' .
            esc_html__('Process Cached Feeds Now', 'athena-ai') .
            '</button>';
        echo '</div>';

        echo '<div id="action-status" style="margin-top: 10px; display: none;" class="notice">';
        echo '<p></p>';
        echo '</div>';
        // JavaScript für die Aktions-Buttons
        ?>
        <script>
            jQuery(document).ready(function($) {
                // Cache leeren
                $('#clear-cache').click(function() {
                    $(this).prop('disabled', true);
                    $('#action-status').attr('class', 'notice notice-info').show().find('p').text('<?php echo esc_js(
                        __('Clearing all feed caches...', 'athena-ai')
                    ); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'athena_clear_feed_cache',
                            nonce: '<?php echo wp_create_nonce('athena-feed-cache-action'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#action-status').attr('class', 'notice notice-success').find('p').text(response.data.message);
                            } else {
                                $('#action-status').attr('class', 'notice notice-error').find('p').text(response.data.message);
                            }
                            $('#clear-cache').prop('disabled', false);
                        },
                        error: function() {
                            $('#action-status').attr('class', 'notice notice-error').find('p').text('<?php echo esc_js(
                                __('An error occurred while clearing cache.', 'athena-ai')
                            ); ?>');
                            $('#clear-cache').prop('disabled', false);
                        }
                    });
                });
                
                // Feeds vorladen
                $('#prefetch-feeds').click(function() {
                    $(this).prop('disabled', true);
                    $('#action-status').attr('class', 'notice notice-info').show().find('p').text('<?php echo esc_js(
                        __('Prefetching all feeds...', 'athena-ai')
                    ); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'athena_prefetch_feeds',
                            nonce: '<?php echo wp_create_nonce('athena-feed-cache-action'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#action-status').attr('class', 'notice notice-success').find('p').text(response.data.message);
                            } else {
                                $('#action-status').attr('class', 'notice notice-error').find('p').text(response.data.message);
                            }
                            $('#prefetch-feeds').prop('disabled', false);
                        },
                        error: function() {
                            $('#action-status').attr('class', 'notice notice-error').find('p').text('<?php echo esc_js(
                                __('An error occurred while prefetching feeds.', 'athena-ai')
                            ); ?>');
                            $('#prefetch-feeds').prop('disabled', false);
                        }
                    });
                });
                
                // Feeds verarbeiten
                $('#process-feeds').click(function() {
                    $(this).prop('disabled', true);
                    $('#action-status').attr('class', 'notice notice-info').show().find('p').text('<?php echo esc_js(
                        __('Processing cached feeds...', 'athena-ai')
                    ); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'athena_process_cached_feeds',
                            nonce: '<?php echo wp_create_nonce('athena-feed-cache-action'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#action-status').attr('class', 'notice notice-success').find('p').text(response.data.message);
                            } else {
                                $('#action-status').attr('class', 'notice notice-error').find('p').text(response.data.message);
                            }
                            $('#process-feeds').prop('disabled', false);
                        },
                        error: function() {
                            $('#action-status').attr('class', 'notice notice-error').find('p').text('<?php echo esc_js(
                                __('An error occurred while processing feeds.', 'athena-ai')
                            ); ?>');
                            $('#process-feeds').prop('disabled', false);
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Rendert die Einstellungsseite.
     */
    public function render_settings_page() {
        // Hole die aktuelle Anzahl der gecachten Feeds
        $cached_feeds = $this->feed_service->getAllCachedFeeds();
        $cache_count = count($cached_feeds);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Feed Cache Settings', 'athena-ai'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <?php echo sprintf(
                        esc_html__(
                            'Currently %d feeds in cache. Cache settings affect how feeds are downloaded and processed.',
                            'athena-ai'
                        ),
                        $cache_count
                    ); ?>
                </p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections('athena-feed-cache');
                submit_button();
                ?>
            </form>
            
            <?php if ($cache_count > 0): ?>
                <h2><?php echo esc_html__('Cached Feed Files', 'athena-ai'); ?></h2>
                <p><?php echo esc_html__(
                    'Below is a list of all feeds currently in the cache. You can inspect these files to diagnose issues with feed parsing.',
                    'athena-ai'
                ); ?></p>
                
                <?php $this->render_feed_files_table(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendert eine Tabelle mit allen Feed-Dateien.
     */
    private function render_feed_files_table() {
        $cache_dir = $this->get_cache_dir();
        if (!file_exists($cache_dir)) {
            echo '<p>' . esc_html__('Cache directory does not exist yet.', 'athena-ai') . '</p>';
            return;
        }

        $files = glob($cache_dir . '/*.xml');
        if (empty($files)) {
            echo '<p>' . esc_html__('No cached feed files found.', 'athena-ai') . '</p>';
            return;
        }

        // Sortiere Dateien nach Änderungsdatum (neueste zuerst)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Filename', 'athena-ai'); ?></th>
                    <th><?php echo esc_html__('Source URL', 'athena-ai'); ?></th>
                    <th><?php echo esc_html__('Cached', 'athena-ai'); ?></th>
                    <th><?php echo esc_html__('Expires', 'athena-ai'); ?></th>
                    <th><?php echo esc_html__('Size', 'athena-ai'); ?></th>
                    <th><?php echo esc_html__('Actions', 'athena-ai'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                    <?php
                    $filename = basename($file);
                    $filesize = filesize($file);
                    $modified = filemtime($file);

                    // Extrahiere Metadaten aus dem Header
                    $url = '';
                    $cached_date = '';
                    $expires_date = '';

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
                            $url = trim($matches[1]);
                        }

                        // Extrahiere das Cache-Datum
                        if (preg_match('/Cached: (.*?)[\r\n]/', $header, $matches)) {
                            $cached_date = trim($matches[1]);
                        }

                        // Extrahiere das Ablaufdatum
                        if (preg_match('/Expires: (.*?)[\r\n]/', $header, $matches)) {
                            $expires_date = trim($matches[1]);
                        }
                    }

                    // Formatiere die Dateigröße lesbar
                    if ($filesize < 1024) {
                        $filesize_formatted = $filesize . ' B';
                    } elseif ($filesize < 1024 * 1024) {
                        $filesize_formatted = round($filesize / 1024, 2) . ' KB';
                    } else {
                        $filesize_formatted = round($filesize / (1024 * 1024), 2) . ' MB';
                    }
                    ?>
                    <tr>
                        <td>
                            <?php echo esc_html($filename); ?>
                        </td>
                        <td>
                            <?php if (!empty($url)): ?>
                                <a href="<?php echo esc_url(
                                    $url
                                ); ?>" target="_blank"><?php echo esc_html($url); ?></a>
                            <?php else: ?>
                                <em><?php echo esc_html__('Unknown', 'athena-ai'); ?></em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html($cached_date); ?>
                        </td>
                        <td>
                            <?php if (!empty($expires_date)) {
                                $expires_timestamp = strtotime($expires_date);
                                $now = time();

                                if ($expires_timestamp < $now) {
                                    echo '<span style="color: red;">' .
                                        esc_html($expires_date) .
                                        ' (' .
                                        esc_html__('Expired', 'athena-ai') .
                                        ')</span>';
                                } else {
                                    $diff = $expires_timestamp - $now;
                                    $minutes = round($diff / 60);

                                    if ($minutes < 60) {
                                        echo esc_html($expires_date) .
                                            ' (' .
                                            sprintf(
                                                esc_html__('Expires in %d minutes', 'athena-ai'),
                                                $minutes
                                            ) .
                                            ')';
                                    } else {
                                        $hours = round($minutes / 60, 1);
                                        echo esc_html($expires_date) .
                                            ' (' .
                                            sprintf(
                                                esc_html__('Expires in %s hours', 'athena-ai'),
                                                $hours
                                            ) .
                                            ')';
                                    }
                                }
                            } ?>
                        </td>
                        <td>
                            <?php echo esc_html($filesize_formatted); ?>
                        </td>
                        <td>
                            <button type="button" class="button view-feed-content" data-file="<?php echo esc_attr(
                                $file
                            ); ?>"><?php echo esc_html__('View', 'athena-ai'); ?></button>
                            <button type="button" class="button button-secondary process-feed" data-url="<?php echo esc_attr(
                                $url
                            ); ?>"><?php echo esc_html__('Process', 'athena-ai'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Modal für Feed-Inhaltsanzeige -->
        <div id="feed-content-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
            <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 1200px;">
                <span id="close-modal" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                <h2 id="feed-modal-title"></h2>
                <div style="display: flex; margin-bottom: 10px;">
                    <div style="margin-right: 10px;">
                        <button id="toggle-raw-view" class="button"><?php echo esc_html__(
                            'Toggle Raw/Formatted View',
                            'athena-ai'
                        ); ?></button>
                    </div>
                    <div>
                        <button id="download-feed" class="button"><?php echo esc_html__(
                            'Download Feed File',
                            'athena-ai'
                        ); ?></button>
                    </div>
                </div>
                <div id="feed-content-container" style="max-height: 70vh; overflow: auto; border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9;">
                    <pre id="feed-content-raw" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                    <div id="feed-content-formatted" style="display: none;"></div>
                </div>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                // Feed-Inhaltsanzeige
                $('.view-feed-content').click(function() {
                    var filePath = $(this).data('file');
                    var fileName = filePath.split('/').pop();
                    
                    $('#feed-modal-title').text('<?php echo esc_js(
                        __('Feed Content', 'athena-ai')
                    ); ?>: ' + fileName);
                    
                    // AJAX-Anfrage zum Lesen der Datei
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'athena_read_feed_file',
                            nonce: '<?php echo wp_create_nonce('athena-feed-cache-action'); ?>',
                            file_path: filePath
                        },
                        success: function(response) {
                            if (response.success) {
                                // Raw-Inhalt anzeigen
                                $('#feed-content-raw').text(response.data.content);
                                
                                // Formatierter Inhalt (als HTML)
                                var formattedContent = response.data.content
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/(".*?")/g, '<span style="color: blue;">$1</span>')
                                    .replace(/(<\/?[a-zA-Z0-9:]+(?:\s+[a-zA-Z0-9:]+=".*?")*>)/g, '<span style="color: #800000;">$1</span>')
                                    .replace(/<!--[\s\S]*?-->/g, '<span style="color: green;">$&</span>');
                                    
                                $('#feed-content-formatted').html(formattedContent);
                                
                                // Link für Download
                                $('#download-feed').off('click').on('click', function() {
                                    var blob = new Blob([response.data.content], {type: 'text/xml'});
                                    var link = document.createElement('a');
                                    link.href = window.URL.createObjectURL(blob);
                                    link.download = fileName;
                                    link.click();
                                });
                                
                                // Modal anzeigen
                                $('#feed-content-modal').show();
                            } else {
                                alert('<?php echo esc_js(
                                    __('Error loading feed content', 'athena-ai')
                                ); ?>: ' + response.data.message);
                            }
                        },
                        error: function() {
                            alert('<?php echo esc_js(
                                __('Error loading feed content', 'athena-ai')
                            ); ?>');
                        }
                    });
                });
                
                // Modal schließen
                $('#close-modal').click(function() {
                    $('#feed-content-modal').hide();
                });
                
                // Klick außerhalb des Modals schließt es
                $(window).click(function(event) {
                    if (event.target == document.getElementById('feed-content-modal')) {
                        $('#feed-content-modal').hide();
                    }
                });
                
                // Umschalten zwischen Raw und formatierter Ansicht
                $('#toggle-raw-view').click(function() {
                    if ($('#feed-content-raw').is(':visible')) {
                        $('#feed-content-raw').hide();
                        $('#feed-content-formatted').show();
                        $(this).text('<?php echo esc_js(__('Show Raw XML', 'athena-ai')); ?>');
                    } else {
                        $('#feed-content-raw').show();
                        $('#feed-content-formatted').hide();
                        $(this).text('<?php echo esc_js(
                            __('Show Formatted XML', 'athena-ai')
                        ); ?>');
                    }
                });
                
                // Process feed
                $('.process-feed').click(function() {
                    var url = $(this).data('url');
                    if (!url) {
                        alert('<?php echo esc_js(
                            __('No URL found for this feed', 'athena-ai')
                        ); ?>');
                        return;
                    }
                    
                    // AJAX-Anfrage zum Verarbeiten des Feeds
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'athena_process_single_feed',
                            nonce: '<?php echo wp_create_nonce('athena-feed-cache-action'); ?>',
                            feed_url: url
                        },
                        beforeSend: function() {
                            $(this).prop('disabled', true).text('<?php echo esc_js(
                                __('Processing...', 'athena-ai')
                            ); ?>');
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php echo esc_js(
                                    __('Feed processed successfully', 'athena-ai')
                                ); ?>: ' + response.data.message);
                            } else {
                                alert('<?php echo esc_js(
                                    __('Error processing feed', 'athena-ai')
                                ); ?>: ' + response.data.message);
                            }
                            $(this).prop('disabled', false).text('<?php echo esc_js(
                                __('Process', 'athena-ai')
                            ); ?>');
                        },
                        error: function() {
                            alert('<?php echo esc_js(
                                __('Error processing feed', 'athena-ai')
                            ); ?>');
                            $(this).prop('disabled', false).text('<?php echo esc_js(
                                __('Process', 'athena-ai')
                            ); ?>');
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Gibt das Cache-Verzeichnis zurück.
     *
     * @return string Das Cache-Verzeichnis
     */
    private function get_cache_dir() {
        if (method_exists($this->feed_service, 'getFeedCacheDir')) {
            return $this->feed_service->getFeedCacheDir();
        }

        // Fallback falls die Methode nicht existiert
        if (function_exists('wp_upload_dir')) {
            $upload_dir = wp_upload_dir();
            return $upload_dir['basedir'] . '/athena_feed_cache';
        }

        return sys_get_temp_dir() . '/athena_feed_cache';
    }

    /**
     * AJAX-Handler zum Leeren des Caches.
     */
    public function ajax_clear_cache() {
        // Nonce prüfen
        if (!check_ajax_referer('athena-feed-cache-action', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'athena-ai'),
            ]);
            return;
        }

        // Cache leeren
        $result = $this->feed_service->clearAllCaches();

        if ($result) {
            wp_send_json_success([
                'message' => __('All feed caches have been cleared successfully.', 'athena-ai'),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to clear feed caches.', 'athena-ai'),
            ]);
        }
    }

    /**
     * AJAX-Handler zum Vorladen aller Feeds.
     */
    public function ajax_prefetch_feeds() {
        // Nonce prüfen
        if (!check_ajax_referer('athena-feed-cache-action', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'athena-ai'),
            ]);
            return;
        }

        global $wpdb;

        // Hole alle Feed-Einträge aus der Datenbank
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
                'athena_feed',
                'publish'
            )
        );

        if (empty($posts)) {
            wp_send_json_error([
                'message' => __('No feeds found to prefetch.', 'athena-ai'),
            ]);
            return;
        }

        $success_count = 0;
        $error_count = 0;

        foreach ($posts as $post) {
            $feed_url = get_post_meta($post->ID, '_feed_url', true);

            if (empty($feed_url)) {
                continue;
            }

            if ($this->feed_service->prefetchFeed($feed_url, true)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Prefetched %d feeds successfully, %d failed.', 'athena-ai'),
                $success_count,
                $error_count
            ),
        ]);
    }

    /**
     * AJAX-Handler zum Verarbeiten aller gecachten Feeds.
     */
    public function ajax_process_cached_feeds() {
        // Nonce prüfen
        if (!check_ajax_referer('athena-feed-cache-action', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'athena-ai'),
            ]);
            return;
        }

        // Verarbeite alle gecachten Feeds
        $results = $this->feed_service->processCachedFeeds();

        if ($results['success'] > 0 || $results['failed'] === 0) {
            wp_send_json_success([
                'message' => sprintf(
                    __('Processed %d feeds successfully, %d failed.', 'athena-ai'),
                    $results['success'],
                    $results['failed']
                ),
            ]);
        } else {
            wp_send_json_error([
                'message' => sprintf(
                    __('Failed to process feeds. Successful: %d, Failed: %d', 'athena-ai'),
                    $results['success'],
                    $results['failed']
                ),
            ]);
        }
    }

    /**
     * AJAX-Handler zum Lesen einer Feed-Datei.
     */
    public function ajax_read_feed_file() {
        // Nonce prüfen
        if (!check_ajax_referer('athena-feed-cache-action', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'athena-ai'),
            ]);
            return;
        }

        // Datei-Pfad holen
        $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';

        if (empty($file_path)) {
            wp_send_json_error([
                'message' => __('No file path provided.', 'athena-ai'),
            ]);
            return;
        }

        // Prüfen, ob die Datei existiert und lesbar ist
        if (!file_exists($file_path) || !is_readable($file_path)) {
            wp_send_json_error([
                'message' => __('File does not exist or is not readable.', 'athena-ai'),
            ]);
            return;
        }

        // Sicherstellen, dass die Datei im Cache-Verzeichnis liegt
        $cache_dir = $this->get_cache_dir();
        if (strpos($file_path, $cache_dir) !== 0) {
            wp_send_json_error([
                'message' => __('File is not in the cache directory.', 'athena-ai'),
            ]);
            return;
        }

        // Dateigröße prüfen (maximal 10 MB)
        $max_size = 10 * 1024 * 1024; // 10 MB
        if (filesize($file_path) > $max_size) {
            wp_send_json_error([
                'message' => __('File is too large to be displayed. Max size: 10 MB.', 'athena-ai'),
            ]);
            return;
        }

        // Datei lesen
        $content = file_get_contents($file_path);

        if ($content === false) {
            wp_send_json_error([
                'message' => __('Error reading file.', 'athena-ai'),
            ]);
            return;
        }

        wp_send_json_success([
            'content' => $content,
        ]);
    }

    /**
     * AJAX-Handler zum Verarbeiten eines einzelnen Feeds.
     */
    public function ajax_process_single_feed() {
        // Nonce prüfen
        if (!check_ajax_referer('athena-feed-cache-action', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'athena-ai'),
            ]);
            return;
        }

        // Feed-URL holen
        $feed_url = isset($_POST['feed_url']) ? esc_url_raw($_POST['feed_url']) : '';

        if (empty($feed_url)) {
            wp_send_json_error([
                'message' => __('No feed URL provided.', 'athena-ai'),
            ]);
            return;
        }

        // Temporäres Feed-Objekt erstellen
        $feed = $this->create_temporary_feed($feed_url);

        // Feed verarbeiten
        $success = $this->feed_service->fetch_and_process_feed($feed, true, false);

        if ($success) {
            wp_send_json_success([
                'message' => __('Feed processed successfully.', 'athena-ai'),
            ]);
        } else {
            wp_send_json_error([
                'message' => $feed->get_last_error() ?: __('Failed to process feed.', 'athena-ai'),
            ]);
        }
    }

    /**
     * Erstellt ein temporäres Feed-Objekt für die Verarbeitung.
     *
     * @param string $url Die Feed-URL
     * @return object Ein temporäres Feed-Objekt
     */
    private function create_temporary_feed($url) {
        return new class ($url) {
            private $url;
            private $last_error = '';

            public function __construct($url) {
                $this->url = $url;
            }

            public function get_url() {
                return $this->url;
            }

            public function get_post_id() {
                return 0;
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
}
