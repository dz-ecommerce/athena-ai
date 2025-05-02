<?php
/**
 * Feed Items Manager
 *
 * @package AthenaAI\Admin
 */

namespace AthenaAI\Admin;

/**
 * FeedItemsManager class
 */
class FeedItemsManager extends BaseAdmin {
    /**
     * Initialize the FeedItemsManager
     */
    public function __construct() {
        // Registriere AJAX-Handler
        add_action('wp_ajax_athena_get_feed_item_content', [$this, 'get_feed_item_content']);
        add_action('wp_ajax_athena_delete_feed_item', [$this, 'delete_feed_item']);
    }

    /**
     * AJAX-Handler zum Abrufen des Feed-Item-Inhalts
     */
    public function get_feed_item_content(): void {
        // Überprüfe die Nonce
        if (
            !isset($_POST['nonce']) ||
            !wp_verify_nonce($_POST['nonce'], 'athena_get_feed_item_content')
        ) {
            wp_send_json_error(['message' => __('Security check failed.', 'athena-ai')]);
        }

        // Überprüfe Berechtigungen
        if (!current_user_can('read')) {
            wp_send_json_error([
                'message' => __('You do not have permission to view this content.', 'athena-ai'),
            ]);
        }

        // Hole die Item-ID
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        if (!$item_id) {
            wp_send_json_error(['message' => __('Invalid item ID.', 'athena-ai')]);
        }

        // Hole das Feed-Item aus der Datenbank
        global $wpdb;
        $item = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}feed_raw_items WHERE id = %d", $item_id)
        );

        if (!$item) {
            wp_send_json_error(['message' => __('Item not found.', 'athena-ai')]);
        }

        // Extrahiere die Daten aus dem raw_content JSON
        $title = '';
        $link = '';
        $description = '';
        $author = '';

        // Überprüfe, ob raw_content existiert und ein gültiger JSON-String ist
        if (isset($item->raw_content) && is_string($item->raw_content)) {
            $raw_content = json_decode($item->raw_content);

            if (json_last_error() === JSON_ERROR_NONE && is_object($raw_content)) {
                // Titel extrahieren
                if (isset($raw_content->title) && is_scalar($raw_content->title)) {
                    $title = (string) $raw_content->title;
                }

                // Link extrahieren
                if (isset($raw_content->link) && is_scalar($raw_content->link)) {
                    $link = (string) $raw_content->link;
                } elseif (isset($raw_content->guid) && is_scalar($raw_content->guid)) {
                    $link = (string) $raw_content->guid;
                }

                // Beschreibung extrahieren
                $desc = '';
                $content_field = '';
                $full_content = '';
                if (isset($raw_content->full_content) && is_scalar($raw_content->full_content)) {
                    $full_content = (string) $raw_content->full_content;
                }
                if (isset($raw_content->description) && is_scalar($raw_content->description)) {
                    $desc = (string) $raw_content->description;
                }
                if (isset($raw_content->content) && is_scalar($raw_content->content)) {
                    $content_field = (string) $raw_content->content;
                }
                // Wähle den längsten verfügbaren Text: full_content > content > description
                if (!empty($full_content)) {
                    $description = $full_content;
                } elseif (strlen($content_field) > strlen($desc)) {
                    $description = $content_field;
                } else {
                    $description = $desc;
                }

                // Autor extrahieren
                if (isset($raw_content->author) && is_scalar($raw_content->author)) {
                    $author = (string) $raw_content->author;
                } elseif (isset($raw_content->creator) && is_scalar($raw_content->creator)) {
                    $author = (string) $raw_content->creator;
                }
            }
        }

        // Fallback für Titel, wenn keiner gefunden wurde
        if (empty($title)) {
            if (!empty($description)) {
                $title = wp_trim_words($description, 10, '...');
            } else {
                $title = __('(No Title)', 'athena-ai');
            }
        }

        // Bereite den Inhalt für die Anzeige vor
        $content = '';

        // Titel
        $content .= '<h2 class="text-xl font-bold mb-4">' . esc_html($title) . '</h2>';

        // Metadaten
        $content .= '<div class="flex flex-wrap gap-2 mb-4">';

        // Feed-Quelle
        $feed_title = get_the_title($item->feed_id);
        if ($feed_title) {
            $content .=
                '<span class="athena-badge bg-blue-100 text-blue-800"><i class="fa-solid fa-rss mr-1"></i> ' .
                esc_html($feed_title) .
                '</span>';
        }

        // Publikationsdatum
        if ($item->pub_date) {
            $pub_date = strtotime($item->pub_date);
            $content .=
                '<span class="athena-badge bg-gray-100 text-gray-800"><i class="fa-solid fa-calendar mr-1"></i> ' .
                date_i18n(get_option('date_format'), $pub_date) .
                '</span>';
        }

        // Autoren
        if (!empty($author)) {
            $content .=
                '<span class="athena-badge bg-purple-100 text-purple-800"><i class="fa-solid fa-user mr-1"></i> ' .
                esc_html($author) .
                '</span>';
        }

        $content .= '</div>';

        // Link zum Original
        if (!empty($link)) {
            $content .= '<div class="mb-4">';
            $content .=
                '<a href="' .
                \AthenaAI\Core\SafetyWrapper::esc_url($link) .
                '" target="_blank" class="text-blue-600 hover:text-blue-800 underline">';
            $content .=
                '<i class="fa-solid fa-external-link-alt mr-1"></i> ' .
                esc_html__('View Original', 'athena-ai');
            $content .= '</a>';
            $content .= '</div>';
        }

        // Trennlinie
        $content .= '<hr class="my-4 border-gray-200" />';

        // Zusammenfassung
        if (!empty($description)) {
            $content .= '<div class="prose prose-blue max-w-none text-gray-700">';
            $content .= wp_kses_post($description);
            $content .= '</div>';
        } else {
            $content .=
                '<div class="italic text-gray-500">' .
                esc_html__('No content available.', 'athena-ai') .
                '</div>';
        }

        // Sende die Antwort
        wp_send_json_success([
            'content' => $content,
        ]);
    }

    /**
     * AJAX-Handler zum Löschen eines Feed-Items
     */
    public function delete_feed_item() {
        // Überprüfe die Nonce
        if (
            !isset($_POST['nonce']) ||
            !wp_verify_nonce($_POST['nonce'], 'athena_delete_feed_item')
        ) {
            wp_send_json_error(['message' => __('Security check failed.', 'athena-ai')]);
        }

        // Überprüfe Berechtigungen
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to delete feed items.', 'athena-ai'),
            ]);
        }

        // Hole die Item-ID
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        if (!$item_id) {
            wp_send_json_error(['message' => __('Invalid item ID.', 'athena-ai')]);
        }

        // Lösche das Feed-Item aus der Datenbank
        global $wpdb;
        $deleted = $wpdb->delete($wpdb->prefix . 'feed_raw_items', ['id' => $item_id], ['%d']);

        if ($deleted) {
            wp_send_json_success(['message' => __('Item deleted successfully.', 'athena-ai')]);
        } else {
            wp_send_json_error(['message' => __('Error deleting item.', 'athena-ai')]);
        }
    }
}
