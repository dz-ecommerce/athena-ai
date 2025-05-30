<?php
/**
 * Plugin Name:       Athena AI
 * Plugin URI:        https://example.com/plugins/athena-ai
 * Description:       A powerful AI assistant for WordPress that helps you create and manage content.
 * Version:           1.0.9
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       athena-ai
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('ATHENA_AI_VERSION', '1.0.10');
define('ATHENA_AI_PLUGIN_FILE', __FILE__);
define('ATHENA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ATHENA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ATHENA_AI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include the main plugin class
require_once ATHENA_AI_PLUGIN_DIR . 'includes/AthenaAI.php';

// Initialize the plugin
function run_athena_ai() {
    $plugin = new AthenaAI();
    $plugin->run();
}

// Run the plugin
run_athena_ai();
