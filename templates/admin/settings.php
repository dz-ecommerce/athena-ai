<?php
/**
 * Template for settings page (nur Text AI Settings)
 */
?>
<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    
    <?php settings_errors('athena_ai_messages'); ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="athena_save_settings">
        <?php echo $nonce_field; ?>

        <div class="settings-section">
            <h2><?php esc_html_e('Text AI Settings', 'athena-ai'); ?></h2>
            <p class="description">
                <?php esc_html_e('Configure settings for text-based AI services.', 'athena-ai'); ?>
            </p>

            <!-- OpenAI Settings -->
            <h3><?php esc_html_e('OpenAI Settings', 'athena-ai'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="athena_ai_openai_api_key">
                            <?php esc_html_e('API Key', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <div class="api-key-input-container" style="position: relative;">
                            <?php 
                            global $wpdb;
                            $direct_api_key = $wpdb->get_var($wpdb->prepare(
                                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                                'athena_ai_openai_api_key'
                            ));
                            $has_key = !empty($direct_api_key); 
                            $key_display = $has_key ? (substr($direct_api_key, 0, 3) . '...' . substr($direct_api_key, -3)) : '';
                            ?>
                            <?php if ($has_key): ?>
                            <input type="hidden" name="previous_api_key" value="<?php echo esc_attr($direct_api_key); ?>">
                            <?php endif; ?>
                            <input type="password" 
                                   name="athena_ai_openai_api_key" 
                                   id="athena_ai_openai_api_key" 
                                   placeholder="<?php echo $has_key ? 'API key is saved (hidden for security)' : 'Enter your OpenAI API key'; ?>" 
                                   value="<?php echo esc_attr($direct_api_key); ?>" 
                                   class="regular-text"
                                   autocomplete="off">
                            <?php
                            // Statuspunkt anzeigen
                            $status = get_option('athena_ai_openai_api_key_status', '');
                            if ($status === 'valid') {
                                echo '<span class="api-key-status-dot" style="color:green; font-size: 1.5em; margin-left: 8px; vertical-align: middle;" title="API Key gültig">&#9679;</span>';
                            } elseif ($status === 'invalid') {
                                echo '<span class="api-key-status-dot" style="color:red; font-size: 1.5em; margin-left: 8px; vertical-align: middle;" title="API Key ungültig">&#9679;</span>';
                            }
                            ?>
                        </div>
                        <div class="debug-value" style="margin-top: 5px; padding: 5px; background: #f0f0f0; border-left: 4px solid #007cba;">
                            <strong>DB Value:</strong> 
                            <?php 
                                if ($has_key) {
                                    echo "API key gespeichert: " . substr($direct_api_key, 0, 3) . '****' . substr($direct_api_key, -3) . ' (' . strlen($direct_api_key) . ' Zeichen)';
                                } else {
                                    echo "<span style='color: #d63638;'>Kein API key gespeichert</span>";
                                }
                            ?>
                        </div>
                        <p class="description">
                            <?php esc_html_e('Enter your OpenAI API key', 'athena-ai'); ?>
                            <br>
                            <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Get your API key', 'athena-ai'); ?> ↗
                            </a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_openai_org_id">
                            <?php esc_html_e('Organization ID', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <div class="api-key-input-container" style="position: relative;">
                            <?php 
                            $direct_org_id = $wpdb->get_var($wpdb->prepare(
                                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                                'athena_ai_openai_org_id'
                            ));
                            $has_org_id = !empty($direct_org_id);
                            ?>
                            <?php if ($has_org_id): ?>
                            <input type="hidden" name="previous_org_id" value="<?php echo esc_attr($direct_org_id); ?>">
                            <?php endif; ?>
                            <input type="text" 
                                   name="athena_ai_openai_org_id" 
                                   id="athena_ai_openai_org_id" 
                                   placeholder="<?php echo $has_org_id ? '' : 'Optional: Enter your OpenAI organization ID'; ?>" 
                                   value="<?php echo esc_attr($direct_org_id); ?>" 
                                   class="regular-text"
                                   autocomplete="off">
                            <?php if ($has_org_id): ?>
                            <span class="api-key-indicator" style="position: absolute; right: 10px; top: 5px; color: green;" title="<?php esc_attr_e('Organization ID is set', 'athena-ai'); ?>">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="debug-value" style="margin-top: 5px; padding: 5px; background: #f0f0f0; border-left: 4px solid #007cba;">
                            <strong>DB Value:</strong> 
                            <?php 
                                if ($has_org_id) {
                                    echo "Organization ID gespeichert: " . $direct_org_id;
                                } else {
                                    echo "<span style='color: #d63638;'>Keine Organization ID gespeichert</span>";
                                }
                            ?>
                        </div>
                        <p class="description">
                            <?php esc_html_e(
                                'Optional: Enter your OpenAI organization ID',
                                'athena-ai'
                            ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_openai_default_model">
                            <?php esc_html_e('Default Model', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <?php 
                        $direct_model = $wpdb->get_var($wpdb->prepare(
                            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                            'athena_ai_openai_default_model'
                        )) ?: 'gpt-4';
                        ?>
                        <select name="athena_ai_openai_default_model" id="athena_ai_openai_default_model">
                            <?php foreach ($models['openai'] as $model_id => $model_name): ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                    <?php selected($direct_model, $model_id); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="debug-value" style="margin-top: 5px; padding: 5px; background: #f0f0f0; border-left: 4px solid #007cba;">
                            <strong>DB Value:</strong> Default Model gespeichert: <?php echo esc_html($direct_model); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_openai_temperature">
                            <?php esc_html_e('Temperature', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <?php 
                        $direct_temperature = $wpdb->get_var($wpdb->prepare(
                            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                            'athena_ai_openai_temperature'
                        )) ?: '0.7';
                        ?>
                        <input type="range" 
                               name="athena_ai_openai_temperature" 
                               id="athena_ai_openai_temperature" 
                               min="0" 
                               max="2" 
                               step="0.1" 
                               value="<?php echo esc_attr($direct_temperature); ?>">
                        <span class="temperature-value"><?php echo esc_html($direct_temperature); ?></span>
                        <div class="debug-value" style="margin-top: 5px; padding: 5px; background: #f0f0f0; border-left: 4px solid #007cba;">
                            <strong>DB Value:</strong> Temperature gespeichert: <?php echo esc_html($direct_temperature); ?>
                        </div>
                        <p class="description">
                            <?php esc_html_e(
                                'Controls randomness: 0 is focused, 2 is more creative',
                                'athena-ai'
                            ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <div class="submit-buttons-row" style="display: flex; gap: 10px;">
            <?php submit_button($this->__('Save Settings')); ?>
            <?php submit_button($this->__('Reset to Defaults'), 'secondary', 'reset_defaults', false, array('onclick' => 'return confirm(\'' . esc_js($this->__('Are you sure you want to reset all settings to their default values? This cannot be undone.', 'athena-ai')) . '\');')); ?>
        </div>
    </form>
</div>
<script>
jQuery(document).ready(function($) {
    // Temperature slider value display
    $('#athena_ai_openai_temperature').on('input', function() {
        $(this).next('.temperature-value').text($(this).val());
    });
});
</script>
<style>
.form-table th {
    width: 200px;
}
h3 {
    margin: 2em 0 1em;
    padding-bottom: 0.5em;
    border-bottom: 1px solid #ccc;
}
.temperature-value {
    margin-left: 10px;
    font-weight: bold;
}
</style>