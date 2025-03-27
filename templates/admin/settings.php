<?php
if (!defined('ABSPATH')) {
    exit;
}

$this->render_template('header');
?>

<form method="post" action="">
    <?php echo $nonce_field; ?>
    
    <table class="form-table">
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
                <?php echo esc_html($this->__('Enable Plugin')); ?>
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
    </table>
    
    <?php submit_button($this->__('Save Settings')); ?>
</form> 