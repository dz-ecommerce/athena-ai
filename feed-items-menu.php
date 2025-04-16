<?php
/**
 * Plugin Name: Athena Feed Items Menu
 * Description: Adds the Feed Items menu
 * Version: 1.0.0
 */

// Add the menu on admin_menu hook
add_action('admin_menu', 'athena_feed_items_menu_add');

/**
 * Add the Feed Items menu
 */
function athena_feed_items_menu_add() {
    // Add a top-level menu
    add_menu_page(
        'Feed Items',
        'Feed Items',
        'manage_options',
        'athena-feed-items',
        'athena_feed_items_page',
        'dashicons-rss',
        30
    );
}

/**
 * Render the Feed Items page
 */
function athena_feed_items_page() {
    echo '<div class="wrap">';
    echo '<h1>Feed Items</h1>';
    echo '<p>This is the Feed Items page.</p>';
    echo '</div>';
}
