<?php
/**
 * Test Script für Feed-Kategorien
 * 
 * Dieses Script testet die Kategorien-Funktionalität der Feed Items
 */

// WordPress laden
require_once 'wp-load.php';

// Sicherheitscheck
if (!current_user_can('manage_options')) {
    wp_die('Sie haben nicht die erforderlichen Berechtigungen, um dieses Skript auszuführen.');
}

echo '<h1>Athena AI Feed Kategorien Test</h1>';

global $wpdb;

// 1. Prüfe ob die Kategorien-Tabelle existiert
echo '<h2>1. Tabellen-Status</h2>';
$categories_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_item_categories'");
echo '<p>feed_item_categories Tabelle: ' . ($categories_table_exists ? '✅ Existiert' : '❌ Fehlt') . '</p>';

$raw_items_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
echo '<p>feed_raw_items Tabelle: ' . ($raw_items_table_exists ? '✅ Existiert' : '❌ Fehlt') . '</p>';

// 2. Zeige Tabellenstruktur
if ($categories_table_exists) {
    echo '<h3>Struktur der feed_item_categories Tabelle:</h3>';
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_item_categories");
    echo '<table border="1" style="border-collapse: collapse;">';
    echo '<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th></tr>';
    foreach ($columns as $column) {
        echo '<tr>';
        echo '<td>' . esc_html($column->Field) . '</td>';
        echo '<td>' . esc_html($column->Type) . '</td>';
        echo '<td>' . esc_html($column->Null) . '</td>';
        echo '<td>' . esc_html($column->Key) . '</td>';
        echo '<td>' . esc_html($column->Default) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// 3. Prüfe vorhandene Feed Items und deren Kategorien
echo '<h2>2. Vorhandene Feed Items</h2>';
$feed_items = $wpdb->get_results("
    SELECT ri.id, ri.guid, 
           JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.title')) as title,
           JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.categories')) as json_categories,
           p.post_title as feed_name
    FROM {$wpdb->prefix}feed_raw_items ri
    JOIN {$wpdb->posts} p ON ri.feed_id = p.ID
    ORDER BY ri.created_at DESC
    LIMIT 10
");

if ($feed_items) {
    echo '<p>Gefunden: ' . count($feed_items) . ' Feed Items (zeige die neuesten 10)</p>';
    echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
    echo '<tr><th>ID</th><th>Titel</th><th>Feed</th><th>Kategorien (JSON)</th><th>Gespeicherte Kategorien</th></tr>';
    
    foreach ($feed_items as $item) {
        echo '<tr>';
        echo '<td>' . esc_html($item->id) . '</td>';
        echo '<td>' . esc_html($item->title ?: 'Kein Titel') . '</td>';
        echo '<td>' . esc_html($item->feed_name) . '</td>';
        echo '<td>' . esc_html($item->json_categories ?: 'Keine') . '</td>';
        
        // Prüfe gespeicherte Kategorien
        if ($categories_table_exists) {
            $saved_categories = $wpdb->get_col($wpdb->prepare(
                "SELECT category FROM {$wpdb->prefix}feed_item_categories WHERE item_id = %d",
                $item->id
            ));
            echo '<td>' . esc_html(implode(', ', $saved_categories) ?: 'Keine') . '</td>';
        } else {
            echo '<td>Tabelle fehlt</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p>Keine Feed Items gefunden.</p>';
}

// 4. Teste die Kategorien-Extraktion aus einem Beispiel-RSS
echo '<h2>3. Test der Kategorien-Extraktion</h2>';

$test_rss = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
<title>Test Feed</title>
<item>
    <title>Test Article with Categories</title>
    <link>https://example.com/test-article</link>
    <description>This is a test article</description>
    <category><![CDATA[Technology]]></category>
    <category><![CDATA[Web Development]]></category>
    <dc:subject>Programming</dc:subject>
    <pubDate>Thu, 19 Dec 2024 10:00:00 +0000</pubDate>
    <guid>https://example.com/test-article</guid>
</item>
</channel>
</rss>';

// Verwende den RSS Parser
$parser = new \AthenaAI\Services\FeedParser\RssParser();
$parsed_items = $parser->parse($test_rss);

if ($parsed_items) {
    echo '<p>✅ RSS erfolgreich geparst. Gefundene Items: ' . count($parsed_items) . '</p>';
    
    foreach ($parsed_items as $index => $item) {
        echo '<h4>Item ' . ($index + 1) . ':</h4>';
        echo '<ul>';
        echo '<li><strong>Titel:</strong> ' . esc_html($item['title'] ?? 'Kein Titel') . '</li>';
        echo '<li><strong>Link:</strong> ' . esc_html($item['link'] ?? 'Kein Link') . '</li>';
        echo '<li><strong>Kategorien:</strong> ';
        if (isset($item['categories']) && is_array($item['categories'])) {
            echo esc_html(implode(', ', $item['categories']));
        } else {
            echo 'Keine Kategorien gefunden';
        }
        echo '</li>';
        echo '</ul>';
    }
} else {
    echo '<p>❌ Fehler beim Parsen des Test-RSS</p>';
}

// 5. Test der SchemaManager Tabellen-Erstellung
echo '<h2>4. Test der Tabellen-Erstellung</h2>';

if (!$categories_table_exists) {
    echo '<p>Versuche Kategorien-Tabelle zu erstellen...</p>';
    
    \AthenaAI\Repositories\SchemaManager::setup_tables();
    
    // Prüfe erneut
    $categories_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_item_categories'");
    echo '<p>Nach setup_tables(): ' . ($categories_table_exists ? '✅ Tabelle erstellt' : '❌ Tabelle fehlt noch') . '</p>';
}

echo '<h2>Test abgeschlossen</h2>';
echo '<p><a href="' . admin_url('admin.php?page=athena-feed-items') . '">Zurück zu Feed Items</a></p>';
?> 