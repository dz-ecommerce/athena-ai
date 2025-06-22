<?php
declare(strict_types=1);

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
     * @var array Cached prompts configuration
     */
    private static $prompts_config = null;
    
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
        $stack = [&$result];
        $last_indent = -1;
        
        foreach ($lines as $line_num => $line) {
            // Kommentare und leere Zeilen überspringen
            if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Einrückung messen
            $indent = strlen($line) - strlen(ltrim($line));
            $line = trim($line);
            
            // Multi-line String erkennen (|)
            if (strpos($line, '|') !== false) {
                $parts = explode(':', $line, 2);
                $key = trim($parts[0]);
                $multiline_content = '';
                
                // Folgende Zeilen sammeln bis Einrückung wieder zurückgeht
                for ($i = $line_num + 1; $i < count($lines); $i++) {
                    $next_line = $lines[$i];
                    $next_indent = strlen($next_line) - strlen(ltrim($next_line));
                    
                    if (trim($next_line) === '' || strpos(trim($next_line), '#') === 0) {
                        $multiline_content .= "\n";
                        continue;
                    }
                    
                    if ($next_indent <= $indent) {
                        break;
                    }
                    
                    $multiline_content .= trim($next_line) . "\n";
                }
                
                $current = &$stack[count($stack) - 1];
                $current[$key] = trim($multiline_content);
                continue;
            }
            
            // Key-Value Paare
            if (strpos($line, ':') !== false) {
                $parts = explode(':', $line, 2);
                $key = trim($parts[0]);
                $value = trim($parts[1] ?? '');
                
                // Anführungszeichen entfernen
                $value = trim($value, '"\'');
                
                // Stack anpassen basierend auf Einrückung
                while (count($stack) > 1 && $indent <= $last_indent) {
                    array_pop($stack);
                    $last_indent -= 4; // Annahme: 4 Leerzeichen pro Ebene
                }
                
                $current = &$stack[count($stack) - 1];
                
                if (empty($value)) {
                    // Neue Sektion
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $last_indent = $indent;
                } else {
                    // Einfacher Wert
                    $current[$key] = $value;
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
     * Build comprehensive AI post generation prompt
     *
     * @param array $form_data Form data from the AI post form
     * @param array $profile_data Company profile data
     * @param array $source_content Content from selected sources (feed items, pages, posts)
     * @return string
     */
    public function build_ai_post_prompt($form_data, $profile_data, $source_content = []) {
        $config = $this->get_prompt('ai_post_generation');
        
        // Debug: Log what we found
        error_log('AI Post Generation Config: ' . print_r($config, true));
        
        if (empty($config)) {
            error_log('ai_post_generation config not found, using hardcoded prompt');
            return $this->build_hardcoded_ai_prompt($form_data, $profile_data, $source_content);
        }

        // Start with base introduction
        $prompt = $config['base_intro'] ?? '';
        $prompt .= "\n\n";

        // Add content type specific instructions
        $content_type = $form_data['content_type'] ?? 'blog_post';
        $content_type_config = $config['content_types'][$content_type] ?? $config['content_types']['blog_post'];
        
        $prompt .= "CONTENT-TYP: " . strtoupper($content_type) . "\n";
        $prompt .= $content_type_config['intro'] . "\n\n";
        $prompt .= "ANFORDERUNGEN:\n" . $content_type_config['requirements'] . "\n\n";

        // Add content source instructions
        $content_source = $form_data['content_source'] ?? 'custom_topic';
        $source_instruction = $config['content_sources'][$content_source]['instruction'] ?? '';
        $prompt .= "CONTENT-QUELLE:\n" . $source_instruction . "\n\n";

        // Add company information
        $prompt .= "UNTERNEHMENSINFORMATIONEN:\n";
        if (!empty($profile_data['company_name'])) {
            $prompt .= "- Firmenname: " . $profile_data['company_name'] . "\n";
        }
        if (!empty($profile_data['company_industry'])) {
            $prompt .= "- Branche: " . $profile_data['company_industry'] . "\n";
        }
        if (!empty($profile_data['company_description'])) {
            $prompt .= "- Beschreibung: " . $profile_data['company_description'] . "\n";
        }
        if (!empty($profile_data['company_products'])) {
            $prompt .= "- Produkte/Dienstleistungen: " . $profile_data['company_products'] . "\n";
        }
        if (!empty($profile_data['company_usps'])) {
            $prompt .= "- Alleinstellungsmerkmale: " . $profile_data['company_usps'] . "\n";
        }
        if (!empty($profile_data['target_audience'])) {
            $prompt .= "- Zielgruppe: " . $profile_data['target_audience'] . "\n";
        }
        if (!empty($profile_data['expertise_areas'])) {
            $prompt .= "- Expertise: " . $profile_data['expertise_areas'] . "\n";
        }
        if (!empty($profile_data['seo_keywords'])) {
            $prompt .= "- SEO-Keywords: " . $profile_data['seo_keywords'] . "\n";
        }
        $prompt .= "\n";

        // Add tone and style
        $tone = $form_data['tone'] ?? 'professional';
        $tone_instruction = $config['tone_styles'][$tone] ?? $config['tone_styles']['professional'];
        $prompt .= "TON UND STIL:\n" . $tone_instruction . "\n\n";

        // Add length guidelines
        $length = $form_data['content_length'] ?? 'medium';
        $length_instruction = $config['length_guidelines'][$length] ?? $config['length_guidelines']['medium'];
        $prompt .= "LÄNGE:\n" . $length_instruction . "\n\n";

        // Add custom target audience if provided
        if (!empty($form_data['target_audience']) && $form_data['target_audience'] !== $profile_data['target_audience']) {
            $prompt .= "SPEZIFISCHE ZIELGRUPPE FÜR DIESEN CONTENT:\n" . $form_data['target_audience'] . "\n\n";
        }

        // Add keywords if provided
        if (!empty($form_data['keywords'])) {
            $prompt .= "ZUSÄTZLICHE KEYWORDS:\n" . $form_data['keywords'] . "\n\n";
        }

        // Add source content
        if (!empty($source_content)) {
            $prompt .= "QUELL-CONTENT:\n";
            
            // Feed items
            if (!empty($source_content['feed_items'])) {
                $prompt .= "Feed-Artikel:\n";
                foreach ($source_content['feed_items'] as $item) {
                    $prompt .= "- Titel: " . ($item['title'] ?? 'Unbekannt') . "\n";
                    if (!empty($item['description'])) {
                        $prompt .= "  Beschreibung: " . substr($item['description'], 0, 300) . "...\n";
                    }
                    if (!empty($item['link'])) {
                        $prompt .= "  Link: " . $item['link'] . "\n";
                    }
                    $prompt .= "\n";
                }
            }
            
            // Pages
            if (!empty($source_content['pages'])) {
                $prompt .= "WordPress-Seiten:\n";
                foreach ($source_content['pages'] as $page) {
                    $prompt .= "- Titel: " . ($page['title'] ?? 'Unbekannt') . "\n";
                    if (!empty($page['content'])) {
                        $prompt .= "  Inhalt: " . substr(strip_tags($page['content']), 0, 500) . "...\n";
                    }
                    $prompt .= "\n";
                }
            }
            
            // Posts
            if (!empty($source_content['posts'])) {
                $prompt .= "WordPress-Beiträge:\n";
                foreach ($source_content['posts'] as $post) {
                    $prompt .= "- Titel: " . ($post['title'] ?? 'Unbekannt') . "\n";
                    if (!empty($post['content'])) {
                        $prompt .= "  Inhalt: " . substr(strip_tags($post['content']), 0, 500) . "...\n";
                    }
                    $prompt .= "\n";
                }
            }
            
            $prompt .= "\n";
        }

        // Add custom topic if provided
        if (!empty($form_data['custom_topic'])) {
            $prompt .= "CUSTOM TOPIC:\n" . $form_data['custom_topic'] . "\n\n";
        }

        // Add additional instructions
        if (!empty($form_data['instructions'])) {
            $prompt .= "ZUSÄTZLICHE ANWEISUNGEN:\n" . $form_data['instructions'] . "\n\n";
        }

        // Add final instructions
        $prompt .= "WICHTIGE HINWEISE:\n";
        $prompt .= "- Erstelle originellen, einzigartigen Content (kein Plagiat)\n";
        $prompt .= "- Integriere die Unternehmensinformationen natürlich in den Text\n";
        $prompt .= "- Verwende die angegebenen Keywords organisch\n";
        $prompt .= "- Schreibe für die definierte Zielgruppe\n";
        $prompt .= "- Halte den gewünschten Ton durchgehend bei\n";
        $prompt .= "- Struktur den Content für WordPress (mit Überschriften)\n";
        $prompt .= "- Beende mit einem Call-to-Action wenn passend\n\n";

        $prompt .= "AUSGABE-FORMAT:\n";
        $prompt .= "Erstelle GENAU in dieser Struktur (mit den Markierungen):\n\n";
        $prompt .= "=== TITEL ===\n";
        $prompt .= "[Hier den SEO-optimierten Titel schreiben - max. 60 Zeichen]\n\n";
        $prompt .= "=== META-BESCHREIBUNG ===\n";
        $prompt .= "[Hier die Meta-Beschreibung schreiben - 150-160 Zeichen, mit Call-to-Action]\n\n";
        $prompt .= "=== INHALT ===\n";
        $prompt .= "[Hier den vollständigen Artikel-Inhalt schreiben]\n\n";
        $prompt .= "Beginne jetzt mit der Content-Erstellung:";

                return $prompt;
    }

    /**
     * Build hardcoded AI prompt when YAML config fails
     */
    private function build_hardcoded_ai_prompt($form_data, $profile_data, $source_content = []) {
        $prompt = "Du bist ein professioneller Content-Marketing-Experte und WordPress-Redakteur.\n";
        $prompt .= "Erstelle hochwertigen, SEO-optimierten Content basierend auf den folgenden Informationen.\n\n";
        
        // Content Type
        $content_type = $form_data['content_type'] ?? 'blog_post';
        $prompt .= "CONTENT-TYP: " . strtoupper(str_replace('_', ' ', $content_type)) . "\n";
        
        if ($content_type === 'blog_post') {
            $prompt .= "Erstelle einen professionellen Blog-Artikel mit Einleitung, Hauptteil mit Zwischenüberschriften und Fazit.\n\n";
        } else {
            $prompt .= "Erstelle Content entsprechend dem gewählten Format.\n\n";
        }
        
        // Company Information
        $prompt .= "UNTERNEHMENSINFORMATIONEN:\n";
        if (!empty($profile_data['company_name'])) {
            $prompt .= "- Firmenname: " . $profile_data['company_name'] . "\n";
        }
        if (!empty($profile_data['company_industry'])) {
            $prompt .= "- Branche: " . $profile_data['company_industry'] . "\n";
        }
        if (!empty($profile_data['company_description'])) {
            $prompt .= "- Beschreibung: " . substr($profile_data['company_description'], 0, 500) . "...\n";
        }
        if (!empty($profile_data['company_products'])) {
            $prompt .= "- Produkte/Dienstleistungen: " . substr($profile_data['company_products'], 0, 300) . "...\n";
        }
        if (!empty($profile_data['target_audience'])) {
            $prompt .= "- Zielgruppe: " . $profile_data['target_audience'] . "\n";
        }
        $prompt .= "\n";
        
        // Source Content (Feed Items)
        if (!empty($source_content['feed_items'])) {
            $prompt .= "QUELL-ARTIKEL (als Inspiration verwenden):\n";
            foreach (array_slice($source_content['feed_items'], 0, 3) as $item) {
                $prompt .= "- Titel: " . ($item['title'] ?? 'Unbekannt') . "\n";
                if (!empty($item['description'])) {
                    $clean_desc = strip_tags($item['description']);
                    $prompt .= "  Inhalt: " . substr($clean_desc, 0, 200) . "...\n";
                }
                $prompt .= "\n";
            }
        }
        
        // Tone and Style
        $tone = $form_data['tone'] ?? 'professional';
        $prompt .= "TON: ";
        switch ($tone) {
            case 'professional':
                $prompt .= "Sachlich, kompetent, vertrauenswürdig\n";
                break;
            case 'casual':
                $prompt .= "Locker, zugänglich, freundlich\n";
                break;
            case 'friendly':
                $prompt .= "Warm, einladend, hilfsbereit\n";
                break;
            default:
                $prompt .= "Professionell und ansprechend\n";
        }
        $prompt .= "\n";
        
        // Length
        $length = $form_data['content_length'] ?? 'medium';
        $prompt .= "LÄNGE: ";
        switch ($length) {
            case 'short':
                $prompt .= "300-500 Wörter, prägnant und auf den Punkt\n";
                break;
            case 'long':
                $prompt .= "1000+ Wörter, umfassend und detailliert\n";
                break;
            default:
                $prompt .= "500-1000 Wörter, ausgewogen und gut strukturiert\n";
        }
        $prompt .= "\n";
        
        // Additional Instructions
        if (!empty($form_data['instructions'])) {
            $prompt .= "ZUSÄTZLICHE ANWEISUNGEN:\n" . $form_data['instructions'] . "\n\n";
        }
        
        // Keywords
        if (!empty($form_data['keywords'])) {
            $prompt .= "KEYWORDS (natürlich einbauen): " . $form_data['keywords'] . "\n\n";
        }
        
        // Output Format
        $prompt .= "AUSGABE-FORMAT:\n";
        $prompt .= "Erstelle GENAU in dieser Struktur:\n\n";
        $prompt .= "=== TITEL ===\n";
        $prompt .= "[SEO-optimierter Titel - max. 60 Zeichen]\n\n";
        $prompt .= "=== META-BESCHREIBUNG ===\n";
        $prompt .= "[Meta-Beschreibung 150-160 Zeichen mit Call-to-Action]\n\n";
        $prompt .= "=== INHALT ===\n";
        $prompt .= "[Vollständiger Artikel-Inhalt mit HTML-Struktur (h2, h3, p, ul, li)]\n\n";
        $prompt .= "Beginne jetzt mit der Content-Erstellung:";
        
        return $prompt;
    }

    /**
     * Parse AI response to extract title, meta description, and content
     *
     * @param string $ai_response The raw AI response
     * @return array Array with 'title', 'meta_description', and 'content' keys
     */
    public function parse_ai_post_response($ai_response) {
        $result = [
            'title' => '',
            'meta_description' => '',
            'content' => ''
        ];

        // Extract title
        if (preg_match('/=== TITEL ===\s*\n(.*?)(?=\n=== META-BESCHREIBUNG ===|\n=== INHALT ===|$)/s', $ai_response, $matches)) {
            $result['title'] = trim($matches[1]);
        }

        // Extract meta description
        if (preg_match('/=== META-BESCHREIBUNG ===\s*\n(.*?)(?=\n=== INHALT ===|\n=== TITEL ===|$)/s', $ai_response, $matches)) {
            $result['meta_description'] = trim($matches[1]);
        }

        // Extract content
        if (preg_match('/=== INHALT ===\s*\n(.*?)$/s', $ai_response, $matches)) {
            $result['content'] = trim($matches[1]);
        }

        // Fallback: if no structured format found, try to extract from unstructured response
        if (empty($result['title']) && empty($result['content'])) {
            // Look for first line as potential title
            $lines = explode("\n", trim($ai_response));
            if (!empty($lines)) {
                $potential_title = trim($lines[0]);
                // If first line looks like a title (not too long, no HTML)
                if (strlen($potential_title) <= 100 && !preg_match('/<[^>]+>/', $potential_title)) {
                    $result['title'] = $potential_title;
                    // Use rest as content
                    $result['content'] = trim(implode("\n", array_slice($lines, 1)));
                } else {
                    // Use entire response as content
                    $result['content'] = $ai_response;
                }
            }
        }

        // Generate fallback title if empty
        if (empty($result['title']) && !empty($result['content'])) {
            // Extract first heading or first sentence
            if (preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $result['content'], $matches)) {
                $result['title'] = strip_tags($matches[1]);
            } elseif (preg_match('/^(.{1,60}[.!?])/', strip_tags($result['content']), $matches)) {
                $result['title'] = trim($matches[1]);
            } else {
                $result['title'] = 'Neuer Beitrag';
            }
        }

        // Generate fallback meta description if empty
        if (empty($result['meta_description']) && !empty($result['content'])) {
            $clean_content = strip_tags($result['content']);
            $clean_content = preg_replace('/\s+/', ' ', $clean_content);
            if (strlen($clean_content) > 160) {
                $result['meta_description'] = substr($clean_content, 0, 157) . '...';
            } else {
                $result['meta_description'] = $clean_content;
            }
        }

        // Sanitize outputs
        $result['title'] = sanitize_text_field($result['title']);
        $result['meta_description'] = sanitize_text_field($result['meta_description']);
        $result['content'] = wp_kses_post($result['content']);

        return $result;
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