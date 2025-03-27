<?php
namespace AthenaAI\Admin;

abstract class BaseAdmin {
    /**
     * Render a template file
     *
     * @param string $template Template file name
     * @param array $args Arguments to pass to the template
     */
    protected function render_template($template, $args = []) {
        $template_path = ATHENA_AI_PLUGIN_DIR . 'templates/admin/' . $template . '.php';
        
        if (file_exists($template_path)) {
            // Ensure title is always set
            if (!isset($args['title'])) {
                $args['title'] = $this->__('Athena AI', 'athena-ai');
            }
            
            extract($args);
            include $template_path;
        } else {
            wp_die(sprintf(__('Template file %s not found.', 'athena-ai'), $template));
        }
    }

    /**
     * Get a nonce field
     *
     * @param string $action The nonce action
     * @return string The nonce field HTML
     */
    protected function get_nonce_field($action) {
        return wp_nonce_field($action, '_wpnonce', true, false);
    }

    /**
     * Verify nonce
     *
     * @param string $action The nonce action
     * @return bool Whether the nonce is valid
     */
    protected function verify_nonce($action) {
        return isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], $action);
    }

    /**
     * Get a translated string
     *
     * @param string $text The text to translate
     * @return string The translated text
     */
    protected function __($text) {
        return __($text, 'athena-ai');
    }

    /**
     * Get a translated string with context
     *
     * @param string $text The text to translate
     * @param string $context The translation context
     * @return string The translated text
     */
    protected function _x($text, $context) {
        return _x($text, $context, 'athena-ai');
    }
} 