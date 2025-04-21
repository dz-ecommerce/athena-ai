<?php
/**
 * Plugin Name: Athena AI
 * Plugin URI: https://your-domain.com/athena-ai
 * Description: A powerful AI integration plugin for WordPress
 * Version: 1.0.187
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
define('ATHENA_AI_VERSION', '1.0.187');
define('ATHENA_AI_PLUGIN_FILE', __FILE__);
define('ATHENA_AI_PLUGIN_DIR', \plugin_dir_path(__FILE__));
define('ATHENA_AI_PLUGIN_URL', \plugin_dir_url(__FILE__));
define('ATHENA_AI_PLUGIN_BASENAME', \plugin_basename(__FILE__));

// Autoloader für Plugin-Klassen
require_once ATHENA_AI_PLUGIN_DIR . 'includes/Autoloader.php';
new AthenaAI\Autoloader();

// Wir laden die Textdomain nicht mehr direkt hier, sondern erst später im Plugin-Lebenszyklus
// Dies verhindert die "Translation loading triggered too early"-Warnung

// Bootstrap-Prozess starten
require_once ATHENA_AI_PLUGIN_DIR . 'includes/Core/Bootstrap.php';
AthenaAI\Core\Bootstrap::init();

// Funktion für Abwärtskompatibilität
function athena_ai_render_feed_items_page() {
    // Für Abwärtskompatibilität rufen wir nun die entsprechende Klassenmethode auf
    \AthenaAI\Admin\FeedItemsPage::render_page();
}