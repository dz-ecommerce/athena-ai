<?php
/**
 * Template for settings page
 */
?>
<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    
    <?php settings_errors('athena_ai_messages'); ?>

    <h2 class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-active" data-tab="github-settings">
            <?php esc_html_e('GitHub Settings', 'athena-ai'); ?>
        </a>
        <a href="#" class="nav-tab" data-tab="text-ai-settings">
            <?php esc_html_e('Text AI Settings', 'athena-ai'); ?>
        </a>
        <a href="#" class="nav-tab" data-tab="image-ai-settings">
            <?php esc_html_e('Image AI Settings', 'athena-ai'); ?>
        </a>
    </h2>

    <form method="post" action="">
        <?php echo $nonce_field; ?>

        <div id="github-settings" class="tab-content active">
            <h2><?php esc_html_e('GitHub Repository Settings', 'athena-ai'); ?></h2>
            <p class="description">
                <?php esc_html_e('Configure GitHub repository settings for automatic updates.', 'athena-ai'); ?>
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
                            <?php esc_html_e('Enter your GitHub personal access token for private repositories', 'athena-ai'); ?>
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
                            <?php esc_html_e('GitHub username or organization name', 'athena-ai'); ?>
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

        <div id="text-ai-settings" class="tab-content">
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
                        <input type="password" 
                               name="athena_ai_openai_api_key" 
                               id="athena_ai_openai_api_key" 
                               value="<?php echo esc_attr($settings['openai_api_key']); ?>" 
                               class="regular-text">
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
                        <input type="text" 
                               name="athena_ai_openai_org_id" 
                               id="athena_ai_openai_org_id" 
                               value="<?php echo esc_attr($settings['openai_org_id']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Optional: Enter your OpenAI organization ID', 'athena-ai'); ?>
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
                        <select name="athena_ai_openai_default_model" id="athena_ai_openai_default_model">
                            <?php foreach ($models['openai'] as $model_id => $model_name) : ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                    <?php selected($settings['openai_default_model'], $model_id); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_openai_temperature">
                            <?php esc_html_e('Temperature', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="range" 
                               name="athena_ai_openai_temperature" 
                               id="athena_ai_openai_temperature" 
                               min="0" 
                               max="2" 
                               step="0.1" 
                               value="<?php echo esc_attr($settings['openai_temperature']); ?>">
                        <span class="temperature-value"><?php echo esc_html($settings['openai_temperature']); ?></span>
                        <p class="description">
                            <?php esc_html_e('Controls randomness: 0 is focused, 2 is more creative', 'athena-ai'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Anthropic Settings -->
            <h3><?php esc_html_e('Anthropic Claude Settings', 'athena-ai'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="athena_ai_anthropic_api_key">
                            <?php esc_html_e('API Key', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               name="athena_ai_anthropic_api_key" 
                               id="athena_ai_anthropic_api_key" 
                               value="<?php echo esc_attr($settings['anthropic_api_key']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Enter your Anthropic API key', 'athena-ai'); ?>
                            <br>
                            <a href="https://console.anthropic.com/account/keys" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Get your API key', 'athena-ai'); ?> ↗
                            </a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_anthropic_model">
                            <?php esc_html_e('Model', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="athena_ai_anthropic_model" id="athena_ai_anthropic_model">
                            <?php foreach ($models['anthropic'] as $model_id => $model_name) : ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                    <?php selected($settings['anthropic_model'], $model_id); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Google AI Settings -->
            <h3><?php esc_html_e('Google Gemini Settings', 'athena-ai'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="athena_ai_google_api_key">
                            <?php esc_html_e('API Key', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               name="athena_ai_google_api_key" 
                               id="athena_ai_google_api_key" 
                               value="<?php echo esc_attr($settings['google_api_key']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Enter your Google AI API key', 'athena-ai'); ?>
                            <br>
                            <a href="https://makersuite.google.com/app/apikey" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Get your API key', 'athena-ai'); ?> ↗
                            </a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_google_model">
                            <?php esc_html_e('Model', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="athena_ai_google_model" id="athena_ai_google_model">
                            <?php foreach ($models['google'] as $model_id => $model_name) : ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                    <?php selected($settings['google_model'], $model_id); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Mistral AI Settings -->
            <h3><?php esc_html_e('Mistral AI Settings', 'athena-ai'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="athena_ai_mistral_api_key">
                            <?php esc_html_e('API Key', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               name="athena_ai_mistral_api_key" 
                               id="athena_ai_mistral_api_key" 
                               value="<?php echo esc_attr($settings['mistral_api_key']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Enter your Mistral AI API key', 'athena-ai'); ?>
                            <br>
                            <a href="https://console.mistral.ai/api-keys/" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Get your API key', 'athena-ai'); ?> ↗
                            </a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="athena_ai_mistral_model">
                            <?php esc_html_e('Model', 'athena-ai'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="athena_ai_mistral_model" id="athena_ai_mistral_model">
                            <?php foreach ($models['mistral'] as $model_id => $model_name) : ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                    <?php selected($settings['mistral_model'], $model_id); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div id="image-ai-settings" class="tab-content">
            <h2><?php esc_html_e('Image AI Settings', 'athena-ai'); ?></h2>
            <p class="description">
                <?php esc_html_e('Configure settings for image generation AI services.', 'athena-ai'); ?>
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
                            <?php foreach ($models['dalle']['sizes'] as $size) : ?>
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
                            <?php foreach ($models['dalle']['qualities'] as $quality) : ?>
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
                            <?php foreach ($models['dalle']['styles'] as $style) : ?>
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
                            <?php esc_html_e('Default style preset for image generation', 'athena-ai'); ?>
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
                               value="<?php echo esc_attr($settings['stablediffusion_api_key']); ?>" 
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
                            <?php foreach ($models['stablediffusion']['models'] as $model_id => $model_name) : ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                    <?php selected($settings['stablediffusion_model'], $model_id); ?>>
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
                            <?php esc_html_e('Number of inference steps (20-150). Higher values = better quality but slower generation', 'athena-ai'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button($this->__('Save Settings')); ?>
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
    });

    // Temperature slider value display
    $('#athena_ai_openai_temperature').on('input', function() {
        $(this).next('.temperature-value').text($(this).val());
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
</style>