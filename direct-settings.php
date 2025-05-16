<?php
/**
 * OpenAI Direktspeicher
 * 
 * Ein einfaches Script zum direkten Speichern der OpenAI-Einstellungen in der WordPress-Datenbank.
 * Füge dieses Script in das Plugin-Verzeichnis ein und rufe es direkt auf.
 */

// WordPress bootstrap
require_once '../../../wp-load.php';

// Nur für eingeloggte Administratoren erlauben
if (!current_user_can('manage_options')) {
    wp_die('Zugriff verweigert');
}

// Nachricht-Container
$messages = [];

// Verarbeite Formular-Submission
if (isset($_POST['direct_settings_submit'])) {
    // Hole die Werte aus dem Formular
    $api_key = sanitize_text_field($_POST['openai_api_key'] ?? '');
    $org_id = sanitize_text_field($_POST['openai_org_id'] ?? '');
    $default_model = sanitize_text_field($_POST['openai_default_model'] ?? 'gpt-4');
    $temperature = (float)($_POST['openai_temperature'] ?? 0.7);

    // Speichere die Werte direkt in der Datenbank
    update_option('athena_ai_openai_api_key', $api_key);
    update_option('athena_ai_openai_org_id', $org_id);
    update_option('athena_ai_openai_default_model', $default_model);
    update_option('athena_ai_openai_temperature', $temperature);

    // Überprüfe, ob die Werte gespeichert wurden
    $saved_api_key = get_option('athena_ai_openai_api_key', '');
    $saved_org_id = get_option('athena_ai_openai_org_id', '');
    $saved_model = get_option('athena_ai_openai_default_model', '');
    $saved_temp = get_option('athena_ai_openai_temperature', '');

    // Erfolgsmeldung
    if (!empty($saved_api_key) && $saved_api_key === $api_key) {
        $messages[] = [
            'type' => 'success',
            'content' => 'Einstellungen erfolgreich gespeichert!'
        ];
    } else {
        $messages[] = [
            'type' => 'error',
            'content' => 'Fehler beim Speichern der Einstellungen. Siehe Debug-Informationen unten.'
        ];
    }
} else {
    // Lade die aktuellen Werte aus der Datenbank
    $api_key = get_option('athena_ai_openai_api_key', '');
    $org_id = get_option('athena_ai_openai_org_id', '');
    $default_model = get_option('athena_ai_openai_default_model', 'gpt-4');
    $temperature = get_option('athena_ai_openai_temperature', 0.7);
}

// Verfügbare Modelle
$models = [
    'gpt-4o' => 'GPT-4o',
    'gpt-4-turbo' => 'GPT-4 Turbo',
    'gpt-4' => 'GPT-4',
    'gpt-4-1106-preview' => 'GPT-4 (Version 1106)',
    'gpt-4-vision-preview' => 'GPT-4 Vision',
    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo (16K)',
];

// Header
$admin_url = admin_url('edit.php?post_type=athena-feed&page=athena-ai-settings');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OpenAI Direktspeicher</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #f0f0f1;
            color: #3c434a;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        h1 {
            color: #1d2327;
            font-size: 23px;
            font-weight: 400;
            margin: 0;
            padding: 9px 0 4px;
            line-height: 1.3;
        }
        .notice {
            background: #fff;
            border-left: 4px solid #72aee6;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
            margin: 20px 0;
            padding: 12px;
        }
        .notice-success {
            border-left-color: #00a32a;
        }
        .notice-error {
            border-left-color: #d63638;
        }
        .form-table {
            border-collapse: collapse;
            margin-top: 20px;
            width: 100%;
            clear: both;
        }
        .form-table th {
            vertical-align: top;
            text-align: left;
            padding: 20px 10px 20px 0;
            width: 200px;
            line-height: 1.3;
            font-weight: 600;
        }
        .form-table td {
            margin-bottom: 9px;
            padding: 15px 10px;
            line-height: 1.3;
            vertical-align: middle;
        }
        .form-table input[type="text"],
        .form-table input[type="password"] {
            width: 25em;
            margin: 0;
        }
        .button-primary {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            line-height: 2.15384615;
            min-height: 30px;
            margin: 0;
            padding: 0 10px;
            cursor: pointer;
            border-width: 1px;
            border-style: solid;
            border-radius: 3px;
            white-space: nowrap;
            box-sizing: border-box;
        }
        .button-primary:hover {
            background: #135e96;
            border-color: #135e96;
            color: #fff;
        }
        .debug-info {
            margin-top: 30px;
            background: #f6f7f7;
            padding: 15px;
            border-left: 4px solid #646970;
        }
        .debug-info h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>OpenAI Direktspeicher für Athena AI</h1>
        <p>Dieses Tool ermöglicht die direkte Speicherung der OpenAI-Einstellungen in der WordPress-Datenbank.</p>
        
        <?php foreach ($messages as $message): ?>
            <div class="notice notice-<?php echo $message['type']; ?>">
                <p><?php echo $message['content']; ?></p>
            </div>
        <?php endforeach; ?>
        
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="openai_api_key">API Key</label></th>
                    <td>
                        <input type="password" name="openai_api_key" id="openai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                        <p class="description">Enter your OpenAI API key</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="openai_org_id">Organization ID</label></th>
                    <td>
                        <input type="text" name="openai_org_id" id="openai_org_id" value="<?php echo esc_attr($org_id); ?>" class="regular-text">
                        <p class="description">Optional: Enter your OpenAI organization ID</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="openai_default_model">Default Model</label></th>
                    <td>
                        <select name="openai_default_model" id="openai_default_model">
                            <?php foreach ($models as $model_id => $model_name): ?>
                                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($default_model, $model_id); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="openai_temperature">Temperature</label></th>
                    <td>
                        <input type="range" name="openai_temperature" id="openai_temperature" min="0" max="2" step="0.1" value="<?php echo esc_attr($temperature); ?>">
                        <span class="temperature-value"><?php echo esc_html($temperature); ?></span>
                        <p class="description">Controls randomness: 0 is focused, 2 is more creative</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="direct_settings_submit" id="submit" class="button button-primary" value="Einstellungen speichern">
                <a href="<?php echo esc_url($admin_url); ?>" class="button">Zurück zu Athena AI Einstellungen</a>
            </p>
        </form>
        
        <div class="debug-info">
            <h3>Debug-Informationen</h3>
            <p><strong>Aktuelle Werte in der Datenbank:</strong></p>
            <ul>
                <li>API Key: <?php echo empty($api_key) ? '<span style="color: red;">Nicht gespeichert</span>' : '<span style="color: green;">Gespeichert (' . substr($api_key, 0, 3) . '***' . substr($api_key, -3) . ')</span>'; ?></li>
                <li>Organization ID: <?php echo empty($org_id) ? '<span style="color: red;">Nicht gespeichert</span>' : '<span style="color: green;">Gespeichert: ' . $org_id . '</span>'; ?></li>
                <li>Default Model: <?php echo empty($default_model) ? '<span style="color: red;">Nicht gespeichert</span>' : '<span style="color: green;">Gespeichert: ' . $default_model . '</span>'; ?></li>
                <li>Temperature: <?php echo ($temperature === '') ? '<span style="color: red;">Nicht gespeichert</span>' : '<span style="color: green;">Gespeichert: ' . $temperature . '</span>'; ?></li>
            </ul>
        </div>
    </div>
    
    <script>
        // Update Temperature value display
        document.addEventListener('DOMContentLoaded', function() {
            var temperatureInput = document.getElementById('openai_temperature');
            var temperatureValue = document.querySelector('.temperature-value');
            
            temperatureInput.addEventListener('input', function() {
                temperatureValue.textContent = this.value;
            });
        });
    </script>
</body>
</html>
