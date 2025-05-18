<?php
/**
 * Diagnostisches Skript für Athena AI Einstellungen
 * 
 * Dieses Skript überprüft, ob die Athena AI Einstellungen korrekt in der Datenbank gespeichert werden.
 */

// WordPress-Kern laden
require_once 'wp-load.php';

// Sicherstellen, dass nur Administratoren Zugriff haben
if (!current_user_can('manage_options')) {
    wp_die('Zugriff verweigert');
}

echo '<h1>Athena AI Einstellungen Diagnose</h1>';

// Wichtige Einstellungen, die überprüft werden sollen
$options_to_check = [
    'athena_ai_openai_api_key',
    'athena_ai_openai_org_id',
    'athena_ai_openai_default_model',
    'athena_ai_openai_temperature',
    'athena_ai_github_token',
    'athena_ai_github_owner',
    'athena_ai_github_repo'
];

echo '<h2>1. WordPress Options API Check</h2>';
echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
echo '<tr><th>Option Name</th><th>Value exists?</th><th>Value (truncated/safe)</th><th>Type</th><th>Raw Length</th></tr>';

foreach ($options_to_check as $option) {
    $value = get_option($option);
    $exists = $value !== false;
    $type = gettype($value);
    $length = is_string($value) ? strlen($value) : (is_array($value) ? count($value) : '-');
    
    // Maskiere sensible Daten
    $display_value = '';
    if ($exists) {
        if (is_string($value)) {
            // Bei API-Keys und Tokens nur Teile anzeigen
            if (strpos($option, 'api_key') !== false || strpos($option, 'token') !== false) {
                $display_value = !empty($value) ? substr($value, 0, 3) . '...' . substr($value, -3) : 'empty string';
            } else {
                $display_value = $value;
            }
        } else {
            $display_value = print_r($value, true);
        }
    } else {
        $display_value = 'NOT FOUND';
    }
    
    echo "<tr>";
    echo "<td>{$option}</td>";
    echo "<td>" . ($exists ? 'YES' : 'NO') . "</td>";
    echo "<td>{$display_value}</td>";
    echo "<td>{$type}</td>";
    echo "<td>{$length}</td>";
    echo "</tr>";
}
echo '</table>';

// Direkte Datenbankabfrage
echo '<h2>2. Direkte Datenbankabfrage</h2>';
global $wpdb;

echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
echo '<tr><th>Option Name</th><th>Value exists in DB?</th><th>Value (truncated/safe)</th><th>Autoload?</th></tr>';

foreach ($options_to_check as $option) {
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        $option
    ));
    
    $exists = $result !== null;
    $value = $exists ? $result->option_value : 'NOT IN DATABASE';
    $autoload = $exists ? $result->autoload : '-';
    
    // Maskiere sensible Daten
    $display_value = '';
    if ($exists) {
        if (is_string($value)) {
            // Bei API-Keys und Tokens nur Teile anzeigen
            if (strpos($option, 'api_key') !== false || strpos($option, 'token') !== false) {
                $display_value = !empty($value) ? substr($value, 0, 3) . '...' . substr($value, -3) . ' (' . strlen($value) . ' chars)' : 'empty string';
            } else {
                $display_value = $value;
            }
        } else {
            $display_value = gettype($value);
        }
    } else {
        $display_value = 'NOT IN DATABASE';
    }
    
    echo "<tr>";
    echo "<td>{$option}</td>";
    echo "<td>" . ($exists ? 'YES' : 'NO') . "</td>";
    echo "<td>{$display_value}</td>";
    echo "<td>{$autoload}</td>";
    echo "</tr>";
}
echo '</table>';

// Alle Athena-Einträge in der Datenbank
echo '<h2>3. Alle athena* Einträge in der Datenbank</h2>';

$all_athena_options = $wpdb->get_results(
    "SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE 'athena%' ORDER BY option_name"
);

if (empty($all_athena_options)) {
    echo '<p><strong>KEINE ATHENA-OPTIONEN IN DER DATENBANK GEFUNDEN!</strong></p>';
} else {
    echo '<p>Gefundene Einträge: ' . count($all_athena_options) . '</p>';
    echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
    echo '<tr><th>Option Name</th><th>Value (truncated/safe)</th><th>Autoload</th><th>Length</th></tr>';
    
    foreach ($all_athena_options as $option) {
        $name = $option->option_name;
        $value = $option->option_value;
        $autoload = $option->autoload;
        $length = is_string($value) ? strlen($value) : '-';
        
        // Maskiere sensible Daten
        $display_value = '';
        if (is_string($value)) {
            // Bei API-Keys und Tokens nur Teile anzeigen
            if (strpos($name, 'api_key') !== false || strpos($name, 'token') !== false) {
                $display_value = !empty($value) ? substr($value, 0, 3) . '...' . substr($value, -3) . ' (' . strlen($value) . ' chars)' : 'empty string';
            } else {
                $display_value = $value;
            }
        } else {
            $display_value = gettype($value);
        }
        
        echo "<tr>";
        echo "<td>{$name}</td>";
        echo "<td>{$display_value}</td>";
        echo "<td>{$autoload}</td>";
        echo "<td>{$length}</td>";
        echo "</tr>";
    }
    echo '</table>';
}

// Überprüfe die form_data, die beim Speichern gesendet werden
echo '<h2>4. Debugging-Hilfen für Formulardaten</h2>';

// Code für ein Test-Formular zur direkten Speicherung
echo '<h3>Manuelles Test-Formular für direkte Speicherung</h3>';
echo '<form method="post" action="">';
echo '<input type="hidden" name="action" value="test_direct_save">';
echo wp_nonce_field('test_direct_save', '_wpnonce', true, false);
echo '<p><label>Test API Key: <input type="text" name="test_api_key" value="test-api-key-123"></label></p>';
echo '<p><input type="submit" name="test_direct_save" value="Direkt in Datenbank speichern"></p>';
echo '</form>';

// Verarbeite das Testformular
if (isset($_POST['test_direct_save']) && wp_verify_nonce($_POST['_wpnonce'], 'test_direct_save')) {
    $test_value = sanitize_text_field($_POST['test_api_key']);
    
    // Direkte Speicherung in DB
    $option_name = 'athena_test_direct_save';
    $direct_result = $wpdb->replace(
        $wpdb->options,
        [
            'option_name' => $option_name,
            'option_value' => $test_value,
            'autoload' => 'yes'
        ],
        ['%s', '%s', '%s']
    );
    
    // Normaler update_option Aufruf
    $wp_result = update_option('athena_test_wp_save', $test_value, true);
    
    echo '<div style="padding: 15px; margin: 10px 0; background: #e7f7e7; border-left: 5px solid #46b450;">';
    echo '<h4>Test-Ergebnisse:</h4>';
    echo '<p>Direct DB insert result: ' . ($direct_result !== false ? 'SUCCESS' : 'FAILURE') . '</p>';
    echo '<p>WordPress update_option result: ' . ($wp_result ? 'SUCCESS' : 'FAILURE') . '</p>';
    
    // Verifizieren
    $direct_verify = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        $option_name
    ));
    
    $wp_verify = get_option('athena_test_wp_save', 'NOT FOUND');
    
    echo '<p>Verification direct: ' . ($direct_verify === $test_value ? 'SUCCESS' : 'FAILURE') . ' - Value: ' . $direct_verify . '</p>';
    echo '<p>Verification WP: ' . ($wp_verify === $test_value ? 'SUCCESS' : 'FAILURE') . ' - Value: ' . $wp_verify . '</p>';
    echo '</div>';
}

// DB-Diagnose
echo '<h2>5. Datenbank-Systeminformationen</h2>';
echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
echo '<tr><td>WordPress Version</td><td>' . get_bloginfo('version') . '</td></tr>';
echo '<tr><td>DB Name</td><td>' . DB_NAME . '</td></tr>';
echo '<tr><td>DB Host</td><td>' . DB_HOST . '</td></tr>';
echo '<tr><td>Table Prefix</td><td>' . $wpdb->prefix . '</td></tr>';
echo '<tr><td>DB Charset</td><td>' . DB_CHARSET . '</td></tr>';
echo '<tr><td>wp_options Table</td><td>' . $wpdb->options . '</td></tr>';
echo '<tr><td>Total options count</td><td>' . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options}") . '</td></tr>';
echo '</table>';

// DB-Rechte testen
echo '<h3>Datenbank-Schreibrechte Test</h3>';
$test_option = 'athena_db_write_test_' . time();
$write_result = $wpdb->insert(
    $wpdb->options,
    [
        'option_name' => $test_option,
        'option_value' => 'test_value',
        'autoload' => 'no'
    ],
    ['%s', '%s', '%s']
);

echo '<p>DB Write Test: ' . ($write_result !== false ? 'SUCCESS' : 'FAILURE - keine Schreibrechte!') . '</p>';

// Aufräumen
if ($write_result !== false) {
    $wpdb->delete($wpdb->options, ['option_name' => $test_option], ['%s']);
}

// Zeige alle PHP-Fehler
echo '<h2>6. PHP-Fehlermeldungen</h2>';
$error_log_path = ini_get('error_log');
echo '<p>PHP Error Log: ' . (empty($error_log_path) ? 'Not set' : $error_log_path) . '</p>';

if (file_exists(WP_CONTENT_DIR . '/debug.log')) {
    $log_content = file_get_contents(WP_CONTENT_DIR . '/debug.log', false, null, -10000);
    echo '<h4>Last 10KB of debug.log:</h4>';
    echo '<pre style="background: #f8f8f8; padding: 10px; max-height: 300px; overflow: auto; font-size: 12px;">';
    echo htmlspecialchars($log_content);
    echo '</pre>';
} else {
    echo '<p>No debug.log file found.</p>';
}

// Ausgabe von grundlegenden Systeminfos
echo '<h2>7. WordPress Status</h2>';
echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
echo '<tr><td>WP_DEBUG</td><td>' . (defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF') . '</td></tr>';
echo '<tr><td>WP_DEBUG_LOG</td><td>' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'ON' : 'OFF') . '</td></tr>';
echo '<tr><td>SAVEQUERIES</td><td>' . (defined('SAVEQUERIES') && SAVEQUERIES ? 'ON' : 'OFF') . '</td></tr>';
echo '<tr><td>Object Cache</td><td>' . (wp_using_ext_object_cache() ? 'External' : 'Default') . '</td></tr>';
echo '<tr><td>Active Plugins</td><td>' . count(get_option('active_plugins')) . '</td></tr>';
echo '</table>';

// Überprüfe, ob eine bekannte WordPress-Option funktioniert
echo '<h3>WordPress Core Options Test</h3>';
$timestamp = time();
update_option('athena_test_timestamp', $timestamp);
$retrieved = get_option('athena_test_timestamp');

echo '<p>Timestamp set: ' . $timestamp . '</p>';
echo '<p>Timestamp get: ' . $retrieved . '</p>';
echo '<p>Test result: ' . ($timestamp == $retrieved ? 'SUCCESS' : 'FAILURE') . '</p>';

// Datei-Diagnose für die Settings-Klasse
echo '<h2>8. Athena AI Plugin-Dateistatus</h2>';
$settings_file = WP_PLUGIN_DIR . '/athena-ai/includes/Admin/Settings.php';
echo '<p>Settings.php file exists: ' . (file_exists($settings_file) ? 'YES' : 'NO') . '</p>';
if (file_exists($settings_file)) {
    echo '<p>Settings.php file size: ' . filesize($settings_file) . ' bytes</p>';
    echo '<p>Settings.php file permissions: ' . substr(sprintf('%o', fileperms($settings_file)), -4) . '</p>';
}

// Debug der force_save_option Methode
echo '<h2>9. Test der force_save_option Methode</h2>';

// Muss die Funktionalität der force_save_option Methode nachbauen
function test_force_save_option($option_name, $option_value) {
    global $wpdb;
    
    // Serialisierung, falls nötig
    if (!is_scalar($option_value)) {
        $option_value = maybe_serialize($option_value);
    }
    
    // Sicherstellen, dass der Wert als String vorliegt
    if (is_bool($option_value)) {
        $option_value = $option_value ? '1' : '0';
    } elseif (is_numeric($option_value)) {
        $option_value = (string) $option_value;
    }
    
    // Vorab Cache leeren
    wp_cache_delete($option_name, 'options');
    
    // Existenzprüfung der Option
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        $option_name
    ));
    
    // Ausführung mit Fehlerprüfung
    if ($exists) {
        // Update
        $success = $wpdb->update(
            $wpdb->options,
            ['option_value' => $option_value, 'autoload' => 'yes'],
            ['option_name' => $option_name],
            ['%s', '%s'],
            ['%s']
        );
    } else {
        // Insert
        $success = $wpdb->insert(
            $wpdb->options,
            [
                'option_name' => $option_name,
                'option_value' => $option_value,
                'autoload' => 'yes'
            ],
            ['%s', '%s', '%s']
        );
    }
    
    // Erneut Cache leeren nach Operation
    wp_cache_delete($option_name, 'options');
    
    // Verifizieren, dass der Wert tatsächlich gespeichert wurde
    $saved_value = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        $option_name
    ));
    
    $is_saved = ($saved_value !== null && $saved_value == $option_value);
    
    return [
        'option_name' => $option_name,
        'option_value' => $option_value,
        'db_operation' => $exists ? 'update' : 'insert',
        'operation_result' => $success !== false ? 'SUCCESS' : 'FAILED',
        'verification' => $is_saved ? 'VERIFIED' : 'FAILED',
        'saved_value' => $saved_value
    ];
}

// Teste die Methode
$test_results = [];
$test_results[] = test_force_save_option('athena_test_direct_key1', 'test-value-1');
$test_results[] = test_force_save_option('athena_test_direct_key2', 123);
$test_results[] = test_force_save_option('athena_test_direct_key3', true);

echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
echo '<tr><th>Option Name</th><th>Value Type</th><th>Operation</th><th>Result</th><th>Verification</th></tr>';

foreach ($test_results as $result) {
    echo '<tr>';
    echo '<td>' . $result['option_name'] . '</td>';
    echo '<td>' . gettype($result['option_value']) . '</td>';
    echo '<td>' . $result['db_operation'] . '</td>';
    echo '<td>' . $result['operation_result'] . '</td>';
    echo '<td>' . $result['verification'] . '</td>';
    echo '</tr>';
}

echo '</table>';

$sql_debug = $wpdb->queries;
echo '<p>SQL Queries: ' . count($sql_debug) . '</p>';
if (defined('SAVEQUERIES') && SAVEQUERIES && !empty($sql_debug)) {
    echo '<h3>Die 5 letzten SQL-Abfragen:</h3>';
    echo '<pre style="background: #f8f8f8; padding: 10px; max-height: 300px; overflow: auto; font-size: 12px;">';
    // Nur die letzten 5 Queries zeigen
    $last_queries = array_slice($sql_debug, -5);
    foreach ($last_queries as $q) {
        echo htmlspecialchars($q[0]) . "\n\n";
    }
    echo '</pre>';
}

echo '<h2>Test abgeschlossen</h2>';
echo '<p>Diagnose erstellt am: ' . date('Y-m-d H:i:s') . '</p>'; 