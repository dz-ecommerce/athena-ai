<?php
/**
 * Template for settings page
 */
?>
<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    
    <?php settings_errors('athena_ai_messages'); ?>

    <h2 class="nav-tab-wrapper">
        <a href="#" data-tab="github-settings" class="nav-tab <?php echo $active_tab === 'github-settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('GitHub Settings', 'athena-ai'); ?></a>
        <a href="#" data-tab="text-ai-settings" class="nav-tab <?php echo $active_tab === 'text-ai-settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Text AI Settings', 'athena-ai'); ?></a>
        <a href="#" data-tab="image-ai-settings" class="nav-tab <?php echo $active_tab === 'image-ai-settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Image AI Settings', 'athena-ai'); ?></a>
        <a href="#" data-tab="maintenance-settings" class="nav-tab <?php echo $active_tab === 'maintenance-settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Maintenance', 'athena-ai'); ?></a>
    </h2>

    <form method="post" action="">
        <?php echo $nonce_field; ?>
        <input type="hidden" name="active_tab" id="active_tab" value="<?php echo esc_attr($active_tab); ?>">

        <div id="github-settings" class="tab-content <?php echo $active_tab === 'github-settings' ? 'active' : ''; ?>">
            <h2><?php esc_html_e('GitHub Repository Settings', 'athena-ai'); ?></h2>
            <p class="description">
                <?php esc_html_e(
                    'Configure GitHub repository settings for automatic updates.',
                    'athena-ai'
                ); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="athena_ai_github_token">
                            <?php esc_html_e('GitHub Token', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               name="athena_ai_github_token" 
                               id="athena_ai_github_token" 
                               value="<?php echo esc_attr($settings['github_token']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e(
                                'Enter your GitHub personal access token for private repositories',
                                'athena-ai'
                            ); ?>
                            <br>
                            <a href="https://github.com/settings/tokens" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Generate a token', 'athena-ai'); ?> ↗
                            </a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_github_owner">
                            <?php esc_html_e('Repository Owner', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="athena_ai_github_owner" 
                               id="athena_ai_github_owner" 
                               value="<?php echo esc_attr($settings['github_owner']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e(
                                'GitHub username or organization name',
                                'athena-ai'
                            ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_github_repo">
                            <?php esc_html_e('Repository Name', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="athena_ai_github_repo" 
                               id="athena_ai_github_repo" 
                               value="<?php echo esc_attr($settings['github_repo']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Name of the GitHub repository', 'athena-ai'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div id="text-ai-settings" class="tab-content <?php echo $active_tab === 'text-ai-settings' ? 'active' : ''; ?>">
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
                            // Direkter Datenbankzugriff für den aktuellsten Wert
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
                            <?php if ($has_key): ?>
                            <span class="api-key-indicator" style="position: absolute; right: 10px; top: 5px; color: green;" title="<?php esc_attr_e('API key is set: ' . $key_display, 'athena-ai'); ?>">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </span>
                            <?php endif; ?>
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
                            // Direkter Datenbankzugriff für den aktuellsten Wert
                            global $wpdb;
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
                        // Direkter Datenbankzugriff
                        global $wpdb;
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
                        // Direkter Datenbankzugriff
                        global $wpdb;
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
            
            <!-- Debug Info Panel -->
            <div class="debug-panel-toggle" style="margin-top: 20px;">
                <button type="button" id="toggle-debug-panel" class="button button-secondary">
                    <span class="dashicons dashicons-code-standards" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Toggle Debug Info', 'athena-ai'); ?>
                </button>
            </div>
            
            <div id="athena-settings-debug-panel" style="display: none; margin-top: 15px; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                <h3 style="margin-top: 0;">OpenAI Settings Debug Information</h3>
                
                <?php 
                // Get raw settings directly from database
                global $wpdb;
                $settings_debug = [
                    'athena_ai_openai_api_key',
                    'athena_ai_openai_org_id',
                    'athena_ai_openai_default_model',
                    'athena_ai_openai_temperature'
                ];
                
                echo '<table class="widefat" style="margin-bottom: 20px;">';
                echo '<thead><tr>';
                echo '<th>Setting</th>';
                echo '<th>Direct DB Value</th>';
                echo '<th>get_option() Value</th>';
                echo '<th>Length</th>';
                echo '<th>Status</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                
                foreach ($settings_debug as $option_name) {
                    // Direct DB query
                    $direct_value = $wpdb->get_var($wpdb->prepare(
                        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                        $option_name
                    ));
                    
                    // get_option value
                    $option_value = get_option($option_name, 'NOT_SET');
                    
                    // Display safely
                    $display_direct = $option_name === 'athena_ai_openai_api_key' && !empty($direct_value) 
                        ? substr($direct_value, 0, 3) . '...' . substr($direct_value, -3) 
                        : $direct_value;
                        
                    $display_option = $option_name === 'athena_ai_openai_api_key' && $option_value !== 'NOT_SET'
                        ? substr($option_value, 0, 3) . '...' . substr($option_value, -3)
                        : $option_value;
                    
                    // Status
                    $status = 'OK';
                    $status_color = 'green';
                    
                    if (empty($direct_value) && $option_name === 'athena_ai_openai_api_key') {
                        $status = 'MISSING';
                        $status_color = 'red';
                    } elseif ($direct_value !== $option_value && $option_value !== 'NOT_SET') {
                        $status = 'MISMATCH';
                        $status_color = 'orange';
                    } elseif ($option_value === 'NOT_SET') {
                        $status = 'CACHE MISS';
                        $status_color = 'orange';
                    }
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($option_name) . '</td>';
                    echo '<td>' . esc_html($display_direct) . '</td>';
                    echo '<td>' . esc_html($display_option) . '</td>';
                    echo '<td>' . (empty($direct_value) ? '0' : strlen($direct_value)) . '</td>';
                    echo '<td style="color: ' . $status_color . ';">' . $status . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                
                // Server information
                echo '<h4>Server Environment</h4>';
                echo '<ul>';
                echo '<li><strong>PHP Version:</strong> ' . phpversion() . '</li>';
                echo '<li><strong>MySQL Version:</strong> ' . $wpdb->db_version() . '</li>';
                echo '<li><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</li>';
                echo '<li><strong>WP_DEBUG:</strong> ' . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . '</li>';
                echo '<li><strong>WP_DEBUG_LOG:</strong> ' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Enabled' : 'Disabled') . '</li>';
                echo '</ul>';
                
                // Add cache reset button
                echo '<button type="button" id="flush-settings-cache" class="button button-secondary" style="margin-top: 15px;">';
                echo '<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> ';
                echo 'Flush Options Cache';
                echo '</button>';
                ?>
            </div>
        </div>

        <div id="image-ai-settings" class="tab-content <?php echo $active_tab === 'image-ai-settings' ? 'active' : ''; ?>">
            <h2><?php esc_html_e('Image AI Settings', 'athena-ai'); ?></h2>
            <p class="description">
                <?php esc_html_e(
                    'Configure settings for image generation AI services.',
                    'athena-ai'
                ); ?>
            </p>

            <!-- DALL-E Settings -->
            <h3><?php esc_html_e('DALL-E Settings (OpenAI)', 'athena-ai'); ?></h3>
            <p class="description">
                <?php esc_html_e('Uses the OpenAI API key from Text AI Settings', 'athena-ai'); ?>
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="athena_ai_dalle_size">
                            <?php esc_html_e('Default Image Size', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="athena_ai_dalle_size" id="athena_ai_dalle_size">
                            <?php foreach ($models['dalle']['sizes'] as $size): ?>
                                <option value="<?php echo esc_attr($size); ?>" 
                                    <?php selected($settings['dalle_size'], $size); ?>>
                                    <?php echo esc_html($size); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_dalle_quality">
                            <?php esc_html_e('Image Quality', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="athena_ai_dalle_quality" id="athena_ai_dalle_quality">
                            <?php foreach ($models['dalle']['qualities'] as $quality): ?>
                                <option value="<?php echo esc_attr($quality); ?>" 
                                    <?php selected($settings['dalle_quality'], $quality); ?>>
                                    <?php echo esc_html(ucfirst($quality)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_dalle_style">
                            <?php esc_html_e('Image Style', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="athena_ai_dalle_style" id="athena_ai_dalle_style">
                            <?php foreach ($models['dalle']['styles'] as $style): ?>
                                <option value="<?php echo esc_attr($style); ?>" 
                                    <?php selected($settings['dalle_style'], $style); ?>>
                                    <?php echo esc_html(ucfirst($style)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Midjourney Settings -->
            <h3><?php esc_html_e('Midjourney Settings', 'athena-ai'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="athena_ai_midjourney_api_key">
                            <?php esc_html_e('API Key', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               name="athena_ai_midjourney_api_key" 
                               id="athena_ai_midjourney_api_key" 
                               value="<?php echo esc_attr($settings['midjourney_api_key']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Enter your Midjourney API key', 'athena-ai'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_midjourney_version">
                            <?php esc_html_e('Version', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="athena_ai_midjourney_version" 
                               id="athena_ai_midjourney_version" 
                               value="<?php echo esc_attr($settings['midjourney_version']); ?>" 
                               class="small-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_midjourney_style">
                            <?php esc_html_e('Default Style', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="athena_ai_midjourney_style" 
                               id="athena_ai_midjourney_style" 
                               value="<?php echo esc_attr($settings['midjourney_style']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e(
                                'Default style preset for image generation',
                                'athena-ai'
                            ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Stable Diffusion Settings -->
            <h3><?php esc_html_e('Stable Diffusion Settings', 'athena-ai'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="athena_ai_stablediffusion_api_key">
                            <?php esc_html_e('API Key', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               name="athena_ai_stablediffusion_api_key" 
                               id="athena_ai_stablediffusion_api_key" 
                               value="<?php echo esc_attr(
                                   $settings['stablediffusion_api_key']
                               ); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Enter your Stable Diffusion API key', 'athena-ai'); ?>
                            <br>
                            <a href="https://platform.stability.ai/docs/getting-started/authentication" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Get your API key', 'athena-ai'); ?> ↗
                            </a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_stablediffusion_model">
                            <?php esc_html_e('Model', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="athena_ai_stablediffusion_model" id="athena_ai_stablediffusion_model">
                            <?php foreach (
                                $models['stablediffusion']['models']
                                as $model_id => $model_name
                            ): ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                    <?php selected(
                                        $settings['stablediffusion_model'],
                                        $model_id
                                    ); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_stablediffusion_steps">
                            <?php esc_html_e('Steps', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               name="athena_ai_stablediffusion_steps" 
                               id="athena_ai_stablediffusion_steps" 
                               value="<?php echo esc_attr($settings['stablediffusion_steps']); ?>" 
                               min="20" 
                               max="150" 
                               class="small-text">
                        <p class="description">
                            <?php esc_html_e(
                                'Number of inference steps (20-150). Higher values = better quality but slower generation',
                                'athena-ai'
                            ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Maintenance Tab Content -->
        <div id="maintenance-settings" class="tab-content <?php echo $active_tab === 'maintenance-settings' ? 'active' : ''; ?>">
            <h2><?php esc_html_e('System Maintenance', 'athena-ai'); ?></h2>
            <p class="description">
                <?php esc_html_e(
                    'Check and maintain system components for optimal performance.',
                    'athena-ai'
                ); ?>
            </p>

            <!-- Feed System Status -->
            <h3><?php esc_html_e('Feed System Status', 'athena-ai'); ?></h3>
            <table class="widefat" style="margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Component', 'athena-ai'); ?></th>
                        <th><?php esc_html_e('Status', 'athena-ai'); ?></th>
                        <th><?php esc_html_e('Details', 'athena-ai'); ?></th>
                        <th><?php esc_html_e('Actions', 'athena-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Feed Items Database -->
                    <tr>
                        <td><strong><?php esc_html_e(
                            'Feed Items Database',
                            'athena-ai'
                        ); ?></strong></td>
                        <td>
                            <?php if ($maintenance['feed_items_table_exists']): ?>
                                <span class="status-ok"><?php esc_html_e(
                                    'OK',
                                    'athena-ai'
                                ); ?></span>
                            <?php else: ?>
                                <span class="status-error"><?php esc_html_e(
                                    'Missing',
                                    'athena-ai'
                                ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($maintenance['feed_items_table_exists']): ?>
                                <?php printf(
                                    esc_html__('Table exists with %d items', 'athena-ai'),
                                    $maintenance['feed_items_count']
                                ); ?>
                            <?php else: ?>
                                <?php esc_html_e(
                                    'The feed items database table is missing',
                                    'athena-ai'
                                ); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$maintenance['feed_items_table_exists']): ?>
                                <form method="post" action="">
                                    <?php echo $maintenance_nonce_field; ?>
                                    <input type="hidden" name="create_tables" value="1">
                                    <button type="submit" class="button button-small"><?php esc_html_e(
                                        'Create Table',
                                        'athena-ai'
                                    ); ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Feed Metadata Database -->
                    <tr>
                        <td><strong><?php esc_html_e(
                            'Feed Metadata Database',
                            'athena-ai'
                        ); ?></strong></td>
                        <td>
                            <?php if ($maintenance['feed_metadata_table_exists']): ?>
                                <span class="status-ok"><?php esc_html_e(
                                    'OK',
                                    'athena-ai'
                                ); ?></span>
                            <?php else: ?>
                                <span class="status-error"><?php esc_html_e(
                                    'Missing',
                                    'athena-ai'
                                ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($maintenance['feed_metadata_table_exists']): ?>
                                <?php printf(
                                    esc_html__('Table exists with %d entries', 'athena-ai'),
                                    $maintenance['feed_metadata_count']
                                ); ?>
                            <?php else: ?>
                                <?php esc_html_e(
                                    'The feed metadata database table is missing',
                                    'athena-ai'
                                ); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$maintenance['feed_metadata_table_exists']): ?>
                                <form method="post" action="">
                                    <?php echo $maintenance_nonce_field; ?>
                                    <input type="hidden" name="create_tables" value="1">
                                    <button type="submit" class="button button-small"><?php esc_html_e(
                                        'Create Table',
                                        'athena-ai'
                                    ); ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Feed Cron Job -->
                    <tr>
                        <td><strong><?php esc_html_e(
                            'Feed Fetch Cron Job',
                            'athena-ai'
                        ); ?></strong></td>
                        <td>
                            <?php if ($maintenance['cron_event_scheduled']): ?>
                                <span class="status-ok"><?php esc_html_e(
                                    'Scheduled',
                                    'athena-ai'
                                ); ?></span>
                            <?php else: ?>
                                <span class="status-error"><?php esc_html_e(
                                    'Not Scheduled',
                                    'athena-ai'
                                ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($maintenance['cron_event_scheduled']): ?>
                                <?php printf(
                                    esc_html__('Next run: %s', 'athena-ai'),
                                    $maintenance['next_cron_run_human']
                                ); ?>
                                <br>
                                <?php printf(
                                    esc_html__('Last fetch: %s', 'athena-ai'),
                                    $maintenance['last_fetch_human']
                                ); ?>
                            <?php else: ?>
                                <?php esc_html_e(
                                    'The automatic feed fetch is not scheduled',
                                    'athena-ai'
                                ); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="">
                                <?php echo $maintenance_nonce_field; ?>
                                <input type="hidden" name="fix_cron" value="1">
                                <button type="submit" class="button button-small"><?php esc_html_e(
                                    'Fix Schedule',
                                    'athena-ai'
                                ); ?></button>
                            </form>
                        </td>
                    </tr>
                    
                    <!-- WordPress Cron Status -->
                    <tr>
                        <td><strong><?php esc_html_e(
                            'WordPress Cron',
                            'athena-ai'
                        ); ?></strong></td>
                        <td>
                            <?php if ($maintenance['wp_cron_disabled']): ?>
                                <span class="status-warning"><?php esc_html_e(
                                    'Disabled',
                                    'athena-ai'
                                ); ?></span>
                            <?php else: ?>
                                <span class="status-ok"><?php esc_html_e(
                                    'Enabled',
                                    'athena-ai'
                                ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($maintenance['wp_cron_disabled']): ?>
                                <?php esc_html_e(
                                    'WordPress cron is disabled in wp-config.php (DISABLE_WP_CRON is set to true)',
                                    'athena-ai'
                                ); ?>
                                <br>
                                <?php esc_html_e(
                                    'You need to set up a server cron job to trigger WordPress scheduled tasks',
                                    'athena-ai'
                                ); ?>
                            <?php else: ?>
                                <?php esc_html_e(
                                    'WordPress cron is enabled and will run on site visits',
                                    'athena-ai'
                                ); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($maintenance['wp_cron_disabled']): ?>
                                <a href="https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/" target="_blank" class="button button-small"><?php esc_html_e(
                                    'Learn More',
                                    'athena-ai'
                                ); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Feed Count -->
                    <tr>
                        <td><strong><?php esc_html_e('Active Feeds', 'athena-ai'); ?></strong></td>
                        <td>
                            <?php if ($maintenance['feed_count'] > 0): ?>
                                <span class="status-ok"><?php echo esc_html(
                                    $maintenance['feed_count']
                                ); ?></span>
                            <?php else: ?>
                                <span class="status-warning"><?php esc_html_e(
                                    'None',
                                    'athena-ai'
                                ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($maintenance['feed_count'] > 0): ?>
                                <?php printf(
                                    esc_html__('%d active feeds configured', 'athena-ai'),
                                    $maintenance['feed_count']
                                ); ?>
                            <?php else: ?>
                                <?php esc_html_e('No feeds are configured', 'athena-ai'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(
                                admin_url('edit.php?post_type=athena-feed')
                            ); ?>" class="button button-small"><?php esc_html_e(
    'Manage Feeds',
    'athena-ai'
); ?></a>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Debug Tools -->
            <h3><?php esc_html_e('Debug Tools', 'athena-ai'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="athena_ai_enable_debug_mode">
                            <?php esc_html_e('Debug Mode', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="athena_ai_enable_debug_mode" 
                                   id="athena_ai_enable_debug_mode" 
                                   value="1" 
                                   <?php checked($settings['enable_debug_mode'], true); ?>>
                            <?php esc_html_e('Enable debug mode', 'athena-ai'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e(
                                'When enabled, additional debug information will be logged to the WordPress debug log',
                                'athena-ai'
                            ); ?>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Feed Fetch Interval', 'athena-ai'); ?></th>
                    <td>
                        <select name="athena_ai_feed_cron_interval">
                            <option value="athena_1min" <?php selected(
                                $settings['feed_cron_interval'],
                                'athena_1min'
                            ); ?>><?php _e('Every Minute', 'athena-ai'); ?></option>
                            <option value="athena_5min" <?php selected(
                                $settings['feed_cron_interval'],
                                'athena_5min'
                            ); ?>><?php _e('Every 5 Minutes', 'athena-ai'); ?></option>
                            <option value="athena_15min" <?php selected(
                                $settings['feed_cron_interval'],
                                'athena_15min'
                            ); ?>><?php _e('Every 15 Minutes', 'athena-ai'); ?></option>
                            <option value="athena_30min" <?php selected(
                                $settings['feed_cron_interval'],
                                'athena_30min'
                            ); ?>><?php _e('Every 30 Minutes', 'athena-ai'); ?></option>
                            <option value="athena_45min" <?php selected(
                                $settings['feed_cron_interval'],
                                'athena_45min'
                            ); ?>><?php _e('Every 45 Minutes', 'athena-ai'); ?></option>
                            <option value="hourly" <?php selected(
                                $settings['feed_cron_interval'],
                                'hourly'
                            ); ?>><?php _e('Hourly', 'athena-ai'); ?></option>
                        </select>
                        <p class="description"><?php _e(
                            'How often should the plugin check for new feed items.',
                            'athena-ai'
                        ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php esc_html_e('Manual Feed Fetch', 'athena-ai'); ?>
                    </th>
                    <td>
                        <a href="<?php echo esc_url(
                            admin_url('admin-post.php?action=athena_debug_fetch_feeds')
                        ); ?>" class="button">
                            <?php esc_html_e('Fetch Feeds Now', 'athena-ai'); ?>
                        </a>
                        <p class="description">
                            <?php esc_html_e(
                                'Manually trigger the feed fetch process for all active feeds',
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
    // Tab functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('tab');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target content
        $('.tab-content').removeClass('active');
        $('#' + target).addClass('active');
        
        // Update hidden field
        $('#active_tab').val(target);
        
        // Save active tab to localStorage for persistence
        localStorage.setItem('athena_active_tab', target);
    });
    
    // Set active tab from PHP if provided, otherwise try localStorage
    var serverActiveTab = '<?php echo esc_js($active_tab); ?>';
    var storedActiveTab = localStorage.getItem('athena_active_tab');
    var tabToActivate = serverActiveTab || storedActiveTab || 'github-settings';
    
    // Activate the tab
    $('.nav-tab[data-tab="' + tabToActivate + '"]').trigger('click');

    // Temperature slider value display
    $('#athena_ai_openai_temperature').on('input', function() {
        $(this).next('.temperature-value').text($(this).val());
    });
    
    // Debug panel toggle
    $('#toggle-debug-panel').on('click', function() {
        $('#athena-settings-debug-panel').slideToggle();
    });
    
    // Cache flush functionality
    $('#flush-settings-cache').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="margin-top: 3px; animation: spin 2s linear infinite;"></span> Flushing...');
        
        // AJAX call to flush the options cache
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'athena_flush_options_cache',
                nonce: '<?php echo wp_create_nonce('athena_flush_options_cache'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $button.html('<span class="dashicons dashicons-yes" style="margin-top: 3px;"></span> Cache Flushed');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $button.html('<span class="dashicons dashicons-no" style="margin-top: 3px;"></span> Error');
                    setTimeout(function() {
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Flush Options Cache');
                    }, 2000);
                }
            },
            error: function() {
                $button.html('<span class="dashicons dashicons-no" style="margin-top: 3px;"></span> Error');
                setTimeout(function() {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Flush Options Cache');
                }, 2000);
            }
        });
    });
});
</script>

<style>
.tab-content {
    display: none;
    padding-top: 20px;
}
.tab-content.active {
    display: block;
}
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

/* Maintenance Tab Styles */
.status-ok {
    display: inline-block;
    padding: 3px 8px;
    background-color: #d4edda;
    color: #155724;
    border-radius: 3px;
    font-weight: bold;
}

.status-warning {
    display: inline-block;
    padding: 3px 8px;
    background-color: #fff3cd;
    color: #856404;
    border-radius: 3px;
    font-weight: bold;
}

.status-error {
    display: inline-block;
    padding: 3px 8px;
    background-color: #f8d7da;
    color: #721c24;
    border-radius: 3px;
    font-weight: bold;
}

#maintenance-settings .widefat th {
    padding: 10px;
}

#maintenance-settings .widefat td {
    padding: 12px 10px;
    vertical-align: middle;
}

/* Animation for spinner */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>