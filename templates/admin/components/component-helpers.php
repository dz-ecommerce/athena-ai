<?php
/**
 * Component Helper Functions
 * 
 * Diese Datei enthält Hilfsfunktionen für die wiederverwendbaren Komponenten
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rendert eine Floating Textarea Komponente
 * 
 * @param array $args Konfigurationsarray für die Textarea
 */
function athena_ai_floating_textarea($args) {
    include ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/floating-textarea.php';
}

/**
 * Rendert eine Floating Input Komponente
 * 
 * @param array $args Konfigurationsarray für das Input
 */
function athena_ai_floating_input($args) {
    include ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/floating-input.php';
}

/**
 * Rendert eine Floating Select Komponente
 * 
 * @param array $args Konfigurationsarray für das Select
 */
function athena_ai_floating_select($args) {
    include ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/floating-select.php';
}

/**
 * Rendert eine Radio Group Komponente
 * 
 * @param array $args Konfigurationsarray für die Radio Group
 */
function athena_ai_radio_group($args) {
    include ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/radio-group.php';
}

/**
 * Rendert eine Checkbox Group Komponente
 * 
 * @param array $args Konfigurationsarray für die Checkbox Group
 */
function athena_ai_checkbox_group($args) {
    include ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/checkbox-group.php';
}

/**
 * Rendert einen Fieldset Wrapper
 * 
 * @param array $args Konfigurationsarray für das Fieldset
 * @param callable $content_callback Callback-Funktion für den Inhalt
 */
function athena_ai_fieldset_wrapper($args, $content_callback = null) {
    include ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/fieldset-wrapper.php';
}

/**
 * Rendert einen AI Assistant Button
 * 
 * @param array $args Konfigurationsarray für den Button
 */
function athena_ai_assistant_button($args) {
    include ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/ai-button.php';
}

// AI Modal Helper Functions laden
include_once ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/ai-modal-helper.php'; 