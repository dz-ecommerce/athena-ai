<?php
/**
 * Feed Schema Fix Script
 *
 * Dieses Skript behebt das Problem mit der fehlenden item_hash-Spalte in der wp_feed_raw_items-Tabelle.
 * Es sollte im WordPress-Verzeichnis ausgeführt werden.
 */

// WordPress laden
require_once 'wp-load.php';

// Sicherheitscheck
if (!current_user_can('manage_options')) {
    wp_die('Sie haben nicht die erforderlichen Berechtigungen, um dieses Skript auszuführen.');
}

echo '<h1>Athena AI Feed Schema Fix</h1>';
echo '<p>Überprüfe und aktualisiere die Datenbankstruktur...</p>';

// Globale WordPress-Datenbank-Variable
global $wpdb;

// Debug-Ausgabe aktivieren
$wpdb->show_errors();

// Prüfen, ob die Tabelle existiert
$table_exists =
    $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'") ===
    $wpdb->prefix . 'feed_raw_items';

if (!$table_exists) {
    echo '<p>Die Tabelle feed_raw_items existiert nicht. Erstelle sie neu...</p>';

    // Tabelle erstellen
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$wpdb->prefix}feed_raw_items (
        id BIGINT UNSIGNED AUTO_INCREMENT,
        item_hash CHAR(32) NOT NULL,
        feed_id BIGINT UNSIGNED NOT NULL,
        raw_content LONGTEXT,
        pub_date DATETIME,
        guid VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (item_hash),
        INDEX (feed_id),
        INDEX (pub_date)
    ) $charset_collate;";

    dbDelta($sql);

    echo '<p>Tabelle feed_raw_items wurde erstellt.</p>';
} else {
    echo '<p>Die Tabelle feed_raw_items existiert bereits. Überprüfe die Spaltenstruktur...</p>';

    // Alle Spalten abrufen
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_raw_items");
    $column_names = array_map(function ($col) {
        return $col->Field;
    }, $columns);

    echo '<p>Vorhandene Spalten: ' . implode(', ', $column_names) . '</p>';

    // Prüfen, ob die ID-Spalte existiert
    if (!in_array('id', $column_names)) {
        echo '<p>Füge ID-Spalte hinzu...</p>';

        // ID-Spalte hinzufügen
        $wpdb->query(
            "ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN id BIGINT UNSIGNED AUTO_INCREMENT FIRST"
        );

        // Primärschlüssel setzen
        $primary_key = $wpdb->get_results(
            "SHOW KEYS FROM {$wpdb->prefix}feed_raw_items WHERE Key_name = 'PRIMARY'"
        );

        if (!empty($primary_key)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items DROP PRIMARY KEY");
            echo '<p>Bestehenden Primärschlüssel entfernt.</p>';
        }

        $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD PRIMARY KEY (id)");
        echo '<p>ID-Spalte als Primärschlüssel gesetzt.</p>';
    }

    // Prüfen, ob die item_hash-Spalte existiert
    if (!in_array('item_hash', $column_names)) {
        echo '<p>Füge item_hash-Spalte hinzu...</p>';

        // item_hash-Spalte hinzufügen
        $result = $wpdb->query(
            "ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN item_hash CHAR(32) NOT NULL AFTER id"
        );

        if ($result === false) {
            echo '<p style="color: red;">Fehler beim Hinzufügen der item_hash-Spalte: ' .
                $wpdb->last_error .
                '</p>';
        } else {
            echo '<p>item_hash-Spalte wurde hinzugefügt.</p>';

            // Daten in der Tabelle prüfen
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items");

            if ($count > 0) {
                echo '<p>Generiere Hash-Werte für bestehende Einträge...</p>';

                // Hash-Werte für bestehende Einträge generieren
                $wpdb->query(
                    "UPDATE {$wpdb->prefix}feed_raw_items SET item_hash = MD5(CONCAT(feed_id, '-', guid)) WHERE item_hash = ''"
                );

                echo '<p>Hash-Werte für ' . $count . ' Einträge generiert.</p>';
            }

            // Unique Key für item_hash hinzufügen
            $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD UNIQUE KEY (item_hash)");
            echo '<p>UNIQUE KEY für item_hash-Spalte hinzugefügt.</p>';
        }
    } else {
        echo '<p>Die item_hash-Spalte existiert bereits.</p>';
    }

    // Weitere Spalten prüfen
    $required_columns = [
        'feed_id' => 'BIGINT UNSIGNED NOT NULL',
        'raw_content' => 'LONGTEXT',
        'pub_date' => 'DATETIME',
        'guid' => 'VARCHAR(255)',
        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ];

    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $column_names)) {
            echo "<p>Füge $column-Spalte hinzu...</p>";
            $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN $column $definition"
            );
            echo "<p>$column-Spalte wurde hinzugefügt.</p>";
        }
    }
}

// Prüfen, ob die Tabelle korrekt aktualisiert wurde
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_raw_items");
$column_names = array_map(function ($col) {
    return $col->Field;
}, $columns);

echo '<h2>Aktualisierte Tabellenstruktur</h2>';
echo '<p>Spalten: ' . implode(', ', $column_names) . '</p>';

// Indizes prüfen
$indexes = $wpdb->get_results("SHOW INDEX FROM {$wpdb->prefix}feed_raw_items");
$index_names = array_map(function ($idx) {
    return $idx->Key_name;
}, $indexes);
$unique_index_names = array_unique($index_names);

echo '<p>Indizes: ' . implode(', ', $unique_index_names) . '</p>';

echo '<h2>Schema-Update abgeschlossen</h2>';
echo '<p>Die Datenbankstruktur wurde erfolgreich aktualisiert. Sie können nun zum <a href="' .
    admin_url('admin.php?page=athena-feed-items') .
    '">Feed-Management</a> zurückkehren.</p>';
