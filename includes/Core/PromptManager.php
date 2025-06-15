<?php
/**
 * AI Prompt Manager
 * 
 * Verwaltet AI-Prompts aus der YAML-Konfigurationsdatei
 * 
 * @package AthenaAI\Core
 * @since 2.1.0
 */

namespace AthenaAI\Core;

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class PromptManager {
    
    /**
     * Singleton-Instanz
     */
    private static $instance = null;
    
    /**
     * Geladene Prompt-Konfiguration
     */
    private $prompts = null;
    
    /**
     * Pfad zur YAML-Konfigurationsdatei
     */
    private $config_file;
    
    /**
     * Konstruktor
     */
    private function __construct() {
        $this->config_file = ATHENA_AI_PLUGIN_DIR . 'config/ai-prompts.yaml';
        $this->load_prompts();
    }
    
    /**
     * Singleton-Instanz abrufen
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * YAML-Prompts laden
     */
    private function load_prompts() {
        if (!file_exists($this->config_file)) {
            error_log('Athena AI: Prompt-Konfigurationsdatei nicht gefunden: ' . $this->config_file);
            $this->prompts = [];
            return;
        }
        
        // Prüfen ob YAML-Extension verfügbar ist
        if (function_exists('yaml_parse_file')) {
            $this->prompts = yaml_parse_file($this->config_file);
        } else {
            // Fallback: Symfony YAML Component verwenden (falls installiert)
            if (class_exists('Symfony\Component\Yaml\Yaml')) {
                $yaml_content = file_get_contents($this->config_file);
                $this->prompts = \Symfony\Component\Yaml\Yaml::parse($yaml_content);
            } else {
                // Einfacher YAML-Parser als Fallback
                $this->prompts = $this->parse_simple_yaml($this->config_file);
            }
        }
        
        if ($this->prompts === false || $this->prompts === null) {
            error_log('Athena AI: Fehler beim Laden der Prompt-Konfiguration');
            $this->prompts = [];
        }
    }
    
    /**
     * Einfacher YAML-Parser als Fallback
     */
    private function parse_simple_yaml($file) {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $result = [];
        $current_section = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Kommentare und leere Zeilen überspringen
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Hauptsektion erkennen (ohne Einrückung)
            if (!preg_match('/^\s/', $line) && strpos($line, ':') !== false) {
                $parts = explode(':', $line, 2);
                $current_section = trim($parts[0]);
                $result[$current_section] = [];
                continue;
            }
            
            // Untersektion erkennen (mit Einrückung)
            if (preg_match('/^\s+(\w+):\s*"?([^"]*)"?$/', $line, $matches)) {
                if ($current_section) {
                    $key = $matches[1];
                    $value = trim($matches[2], '"');
                    $result[$current_section][$key] = $value;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Prompt für einen bestimmten Modal-Typ abrufen
     */
    public function get_prompt($modal_type, $prompt_part = null) {
        if (!isset($this->prompts[$modal_type])) {
            return null;
        }
        
        if ($prompt_part === null) {
            return $this->prompts[$modal_type];
        }
        
        return $this->prompts[$modal_type][$prompt_part] ?? null;
    }
    
    /**
     * Vollständigen Prompt zusammenbauen
     */
    public function build_full_prompt($modal_type, $extra_info = '') {
        $config = $this->get_prompt($modal_type);
        if (!$config) {
            return '';
        }
        
        $intro = $config['intro'] ?? '';
        $limit = $config['limit'] ?? '';
        
        $full_prompt = $intro;
        if (!empty($extra_info)) {
            $full_prompt .= "\n\n" . $extra_info;
        }
        if (!empty($limit)) {
            $full_prompt .= "\n\n" . $limit;
        }
        
        return $full_prompt;
    }
    
    /**
     * Alle verfügbaren Modal-Typen abrufen
     */
    public function get_available_modals() {
        $modals = [];
        foreach ($this->prompts as $key => $config) {
            if (is_array($config) && isset($config['intro'])) {
                $modals[] = $key;
            }
        }
        return $modals;
    }
    
    /**
     * Zielfeld für einen Modal-Typ abrufen
     */
    public function get_target_field($modal_type) {
        return $this->get_prompt($modal_type, 'target_field');
    }
    
    /**
     * Globale Einstellungen abrufen
     */
    public function get_global_setting($key) {
        return $this->prompts['global'][$key] ?? null;
    }
    
    /**
     * Provider-Einstellungen abrufen
     */
    public function get_provider_settings($provider) {
        return $this->prompts['providers'][$provider] ?? [];
    }
    
    /**
     * Validierungsregeln abrufen
     */
    public function get_validation_rules() {
        return $this->prompts['validation'] ?? [];
    }
    
    /**
     * Prompt-Konfiguration für JavaScript ausgeben
     */
    public function get_js_config($modal_type = null) {
        if ($modal_type) {
            $config = $this->get_prompt($modal_type);
            if (!$config) {
                return '{}';
            }
            
            return json_encode([
                'intro' => $config['intro'] ?? '',
                'limit' => $config['limit'] ?? '',
                'target_field' => $config['target_field'] ?? '',
                'max_words' => $config['max_words'] ?? null,
                'max_items' => $config['max_items'] ?? null,
                'format' => $config['format'] ?? 'text'
            ]);
        }
        
        // Alle Konfigurationen für JavaScript
        $js_config = [];
        foreach ($this->get_available_modals() as $modal) {
            $config = $this->get_prompt($modal);
            $js_config[$modal] = [
                'intro' => $config['intro'] ?? '',
                'limit' => $config['limit'] ?? '',
                'target_field' => $config['target_field'] ?? '',
                'max_words' => $config['max_words'] ?? null,
                'max_items' => $config['max_items'] ?? null,
                'format' => $config['format'] ?? 'text'
            ];
        }
        
        return json_encode($js_config);
    }
    
    /**
     * Konfiguration neu laden (für Entwicklung/Debug)
     */
    public function reload_config() {
        $this->prompts = null;
        $this->load_prompts();
    }
    
    /**
     * Prüfen ob Konfiguration gültig ist
     */
    public function is_config_valid() {
        return !empty($this->prompts) && is_array($this->prompts);
    }
    
    /**
     * Debug-Informationen abrufen
     */
    public function get_debug_info() {
        return [
            'config_file' => $this->config_file,
            'file_exists' => file_exists($this->config_file),
            'yaml_extension' => function_exists('yaml_parse_file'),
            'symfony_yaml' => class_exists('Symfony\Component\Yaml\Yaml'),
            'config_loaded' => $this->is_config_valid(),
            'available_modals' => $this->get_available_modals(),
            'config_size' => count($this->prompts)
        ];
    }
} 