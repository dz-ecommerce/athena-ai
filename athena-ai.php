<?php
/**
 * Plugin Name: Athena AI
 * Plugin URI: https://your-domain.com/athena-ai
 * Description: A powerful AI integration plugin for WordPress
 * Version: 1.0.85
 * Author: Your Name
 * Author URI: https://your-domain.com
 * Text Domain: athena-ai
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('ATHENA_AI_VERSION', '1.0.85');
define('ATHENA_AI_PLUGIN_FILE', __FILE__);
define('ATHENA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ATHENA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ATHENA_AI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'AthenaAI\\';
    $base_dir = ATHENA_AI_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Load plugin text domain
 */
function athena_ai_load_textdomain() {
    load_plugin_textdomain('athena-ai', false, dirname(ATHENA_AI_PLUGIN_BASENAME) . '/languages');
}
add_action('init', 'athena_ai_load_textdomain');

/**
 * Initialize the plugin
 */
function athena_ai_init() {
    // Initialize main plugin class
    $plugin = new \AthenaAI\Core\Plugin();
    $plugin->init();

    // Initialize GitHub updater
    $updater = new \AthenaAI\Core\UpdateChecker(
        'dz-ecommerce',           // GitHub username/organization
        'athena-ai',              // Repository name
        null                      // No token needed for public repositories
    );
    $updater->init();
}
add_action('plugins_loaded', 'athena_ai_init');

/**
 * Activation callback
 */
function athena_ai_activate() {
    // Create an instance of the Plugin class
    $plugin = new \AthenaAI\Core\Plugin();
    
    // Setup capabilities
    $plugin->setup_capabilities();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'athena_ai_activate');

/**
 * Deactivation callback
 */
function athena_ai_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'athena_ai_deactivate');