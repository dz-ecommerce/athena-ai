<?php
if (!defined('ABSPATH')) {
    exit;
}

$this->render_template('header');
?>

<div class="wrap">
    <h1><?php echo esc_html($this->__('Settings')); ?></h1>

    <?php settings_errors('athena_ai_messages'); ?>

    <form method="post" action="">
        <?php echo $nonce_field; ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="athena_ai_enabled"><?php echo esc_html($this->__('Enable Plugin')); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="athena_ai_enabled" 
                               value="1" 
                               <?php checked($settings['enabled']); ?>>
                        <?php echo esc_html($this->__('Enable Athena AI features')); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="athena_ai_api_key"><?php echo esc_html($this->__('API Key')); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="athena_ai_api_key" 
                           name="athena_ai_api_key" 
                           value="<?php echo esc_attr($settings['api_key']); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php echo esc_html($this->__('Enter your Athena AI API key.')); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="athena_ai_github_token"><?php echo esc_html($this->__('GitHub Token')); ?></label>
                </th>
                <td>
                    <input type="password" 
                           id="athena_ai_github_token" 
                           name="athena_ai_github_token" 
                           value="<?php echo esc_attr($settings['github_token']); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php echo esc_html($this->__('Enter your GitHub personal access token to enable automatic updates from private repositories')); ?>
                        <br>
                        <a href="https://github.com/settings/tokens" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html($this->__('Generate a token')); ?> ↗
                        </a>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button($this->__('Save Settings')); ?>
    </form>
</div>