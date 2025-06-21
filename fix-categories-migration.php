<?php
/**
 * Fix Categories Migration Script
 * 
 * Dieses Script stellt sicher, dass die Kategorien-Tabelle existiert und 
 * migriert bestehende Kategorien aus dem JSON in die separate Tabelle.
 */

// WordPress laden
require_once 'wp-load.php';

// Sicherheitscheck
if (!current_user_can('manage_options')) {
    wp_die('Sie haben nicht die erforderlichen Berechtigungen, um dieses Skript auszuführen.');
}

echo '<h1>Athena AI Kategorien Migration</h1>';

global $wpdb;

// 1. Stelle sicher, dass alle Tabellen existiert
echo '<h2>1. Tabellen-Setup</h2>';

// Verwende SchemaManager um alle Tabellen zu erstellen
\AthenaAI\Repositories\SchemaManager::setup_tables();

// Prüfe Tabellen-Status
$tables_to_check = [
    'feed_raw_items',
    'feed_item_categories', 
    'feed_metadata',
    'feed_errors'
];

foreach ($tables_to_check as $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
    echo '<p>' . $table . ': ' . ($table_exists ? '✅ Existiert' : '❌ Fehlt') . '</p>';
}

// 2. Prüfe feed_raw_items Struktur
echo '<h2>2. feed_raw_items Tabellenstruktur</h2>';
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_raw_items");
$column_names = array_map(function($col) { return $col->Field; }, $columns);

echo '<p>Spalten: ' . implode(', ', $column_names) . '</p>';

$has_id_column = in_array('id', $column_names);
echo '<p>ID-Spalte vorhanden: ' . ($has_id_column ? '✅ Ja' : '❌ Nein') . '</p>';

// 3. Wenn keine ID-Spalte vorhanden, füge sie hinzu
if (!$has_id_column) {
    echo '<h3>Füge ID-Spalte hinzu...</h3>';
    
    // Prüfe ob es einen Primary Key gibt
    $primary_keys = $wpdb->get_results("SHOW KEYS FROM {$wpdb->prefix}feed_raw_items WHERE Key_name = 'PRIMARY'");
    
    if (!empty($primary_keys)) {
        echo '<p>Entferne bestehenden Primary Key...</p>';
        $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items DROP PRIMARY KEY");
    }
    
    // Füge ID-Spalte hinzu
    $result = $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST");
    
    if ($result !== false) {
        echo '<p>✅ ID-Spalte erfolgreich hinzugefügt</p>';
        $has_id_column = true;
    } else {
        echo '<p>❌ Fehler beim Hinzufügen der ID-Spalte: ' . $wpdb->last_error . '</p>';
    }
}

// 4. Migration der Kategorien
echo '<h2>3. Kategorien Migration</h2>';

if ($has_id_column) {
    // Prüfe aktuelle Kategorien-Anzahl
    $categories_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_item_categories");
    $items_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items");
    
    echo '<p>Feed Items: ' . $items_count . '</p>';
    echo '<p>Gespeicherte Kategorien: ' . $categories_count . '</p>';
    
    if ($items_count > 0) {
        // Hole alle Items mit Kategorien im JSON
        $items_with_categories = $wpdb->get_results("
            SELECT id, raw_content 
            FROM {$wpdb->prefix}feed_raw_items 
            WHERE raw_content IS NOT NULL 
            AND JSON_EXTRACT(raw_content, '$.categories') IS NOT NULL
        ");
        
        echo '<p>Items mit Kategorien im JSON: ' . count($items_with_categories) . '</p>';
        
        if (count($items_with_categories) > 0) {
            $migrated_count = 0;
            $total_categories = 0;
            
            foreach ($items_with_categories as $item) {
                $raw_data = json_decode($item->raw_content, true);
                
                if (isset($raw_data['categories']) && is_array($raw_data['categories'])) {
                    foreach ($raw_data['categories'] as $category) {
                        if (!empty(trim($category))) {
                            // Prüfe ob Kategorie bereits existiert
                            $exists = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}feed_item_categories 
                                WHERE item_id = %d AND category = %s",
                                $item->id,
                                substr(trim($category), 0, 255)
                            ));
                            
                            if ($exists == 0) {
                                $result = $wpdb->insert(
                                    $wpdb->prefix . 'feed_item_categories',
                                    [
                                        'item_id' => $item->id,
                                        'category' => substr(trim($category), 0, 255),
                                        'created_at' => current_time('mysql'),
                                    ],
                                    ['%d', '%s', '%s']
                                );
                                
                                if ($result !== false) {
                                    $migrated_count++;
                                }
                            }
                            $total_categories++;
                        }
                    }
                }
            }
            
            echo '<p>✅ Migration abgeschlossen:</p>';
            echo '<ul>';
            echo '<li>Verarbeitete Kategorien: ' . $total_categories . '</li>';
            echo '<li>Neu migrierte Kategorien: ' . $migrated_count . '</li>';
            echo '</ul>';
        }
    }
} else {
    echo '<p>❌ Kann nicht migrieren - ID-Spalte fehlt</p>';
}

// 5. Test der Kategorien-Anzeige
echo '<h2>4. Test der Kategorien-Anzeige</h2>';

if ($has_id_column) {
    $test_items = $wpdb->get_results("
        SELECT ri.id, 
               JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.title')) as title,
               GROUP_CONCAT(DISTINCT fic.category SEPARATOR ', ') as categories
        FROM {$wpdb->prefix}feed_raw_items ri
        LEFT JOIN {$wpdb->prefix}feed_item_categories fic ON ri.id = fic.item_id
        GROUP BY ri.id
        ORDER BY ri.created_at DESC
        LIMIT 5
    ");
    
    if ($test_items) {
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        echo '<tr><th>ID</th><th>Titel</th><th>Kategorien</th></tr>';
        
        foreach ($test_items as $item) {
            echo '<tr>';
            echo '<td>' . esc_html($item->id) . '</td>';
            echo '<td>' . esc_html($item->title ?: 'Kein Titel') . '</td>';
            echo '<td>' . esc_html($item->categories ?: 'Keine Kategorien') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>Keine Test-Items gefunden</p>';
    }
}

// 6. Abschluss
echo '<h2>Migration abgeschlossen</h2>';

$final_categories_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_item_categories");
echo '<p>Finale Anzahl gespeicherter Kategorien: <strong>' . $final_categories_count . '</strong></p>';

echo '<p><a href="' . admin_url('admin.php?page=athena-feed-items') . '">→ Zu den Feed Items</a></p>';
?> 