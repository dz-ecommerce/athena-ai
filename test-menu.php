<?php
/**
 * Plugin Name: Athena AI Test Menu
 * Description: Test plugin to add a menu item
 * Version: 1.0.0
 * Author: Athena AI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add a test menu
add_action('admin_menu', 'athena_test_menu');

function athena_test_menu() {
    add_menu_page(
        'Athena Test',
        'Athena Test',
        'manage_options',
        'athena-test',
        'athena_test_page',
        'dashicons-admin-generic',
        30
    );
}

function athena_test_page() {
    echo '<div class="wrap">';
    echo '<h1>Athena Test Page</h1>';
    echo '<p>This is a test page.</p>';
    echo '</div>';
}
