<?php

namespace AthenaAI\Admin;

use AthenaAI\Services\OpenAIService;
use AthenaAI\Services\GeminiService;

class AjaxHandler {
    /**
     * OpenAI Service instance
     * 
     * @var OpenAIService
     */
    private $openai_service;
    
    /**
     * Google Gemini Service instance
     * 
     * @var GeminiService
     */
    private $gemini_service;
    
    public function __construct() {
        add_action('wp_ajax_athena_ai_modal_debug', [$this, 'handleModalDebug']);
        add_action('wp_ajax_nopriv_athena_ai_modal_debug', [$this, 'handleModalDebug']);
        
        // AJAX-Aktion für Produkte-Modal
        add_action('wp_ajax_athena_ai_modal_debug_products', [$this, 'handleModalDebugProducts']);
        add_action('wp_ajax_nopriv_athena_ai_modal_debug_products', [$this, 'handleModalDebugProducts']);
        
        // AJAX-Aktion für universelles Modal
        add_action('wp_ajax_athena_ai_modal_debug_universal', [$this, 'handleModalDebugUniversal']);
        add_action('wp_ajax_nopriv_athena_ai_modal_debug_universal', [$this, 'handleModalDebugUniversal']);
        
        // Moderne AJAX-Handler für neue Modal-Architektur
        add_action('wp_ajax_athena_ai_generate_content', [$this, 'handleGenerateContent']);
        add_action('wp_ajax_nopriv_athena_ai_generate_content', [$this, 'handleGenerateContent']);
        
        // Universeller Prompt Handler (für profile-modals.js)
        add_action('wp_ajax_athena_ai_prompt', [$this, 'handle_prompt_request']);
        add_action('wp_ajax_nopriv_athena_ai_prompt', [$this, 'handle_prompt_request']);
        
        // Initialisiere die Services
        $this->openai_service = new OpenAIService();
        $this->gemini_service = new GeminiService();
    }

    public function handleModalDebug() {
        header('Content-Type: text/plain; charset=utf-8');
        $page_id = $_POST['page_id'] ?? null;
        $extra_info = $_POST['extra_info'] ?? null;
        $model_provider = $_POST['model_provider'] ?? 'openai'; // Default ist OpenAI
        $custom_prompt = $_POST['custom_prompt'] ?? null;
        $page_content = null;
        $ai_response = null;

        // Keine automatische Sammlung von Informationen mehr
        
        // Verwende den benutzerdefinierten Prompt, wenn vorhanden
        if ($custom_prompt) {
            $prompt = $custom_prompt;
            
            // Keine automatische Hinzufügung von Informationen mehr
            
            // Füge Seiteninhalt hinzu, wenn eine Seite ausgewählt wurde
            if ($page_id) {
                $post = get_post($page_id);
                if ($post && $post->post_type === 'page') {
                    $page_content = $post->post_content;
                    $prompt .= "\n\nSeiteninhalt:\n" . $page_content;
                }
            }
        } else {
            // Fallback auf den alten Prompt-Aufbau
            $prompt = "Du bist ein Assistent für WordPress-Inhalte. ";
            
            if ($page_id) {
                $post = get_post($page_id);
                if ($post && $post->post_type === 'page') {
                    $page_content = $post->post_content;
                    $prompt .= "Basierend auf dem folgenden Seiteninhalt und zusätzlichen Informationen, ";
                    $prompt .= "erstelle eine optimierte Version des Inhalts. ";
                    $prompt .= "Zusätzliche Informationen: " . $extra_info . "\n\n";
                    $prompt .= "Seiteninhalt:\n" . $page_content;
                }
            } else {
                // Wenn keine Seite ausgewählt wurde, nur die zusätzlichen Informationen verwenden
                $prompt .= "Basierend auf den folgenden Informationen, erstelle einen optimierten Inhalt: ";
                $prompt .= "\n\n" . $extra_info;
            }
        }
                
        // Wähle den richtigen Service basierend auf model_provider
        if ($model_provider === 'gemini') {
            // Kommunikation mit Google Gemini
            $ai_result = $this->gemini_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                // Extrahiere die Antwort aus dem Gemini-Format
                $ai_response = $this->gemini_service->extract_content($ai_result);
            } else {
                // Prüfe, ob es sich um einen speziellen Fehler handelt
                if ($ai_result->get_error_code() === 'api_key_error') {
                    $ai_response = "### " . $ai_result->get_error_message();
                } else {
                    $ai_response = "Fehler bei der Google Gemini-Kommunikation: " . $ai_result->get_error_message();
                }
            }
        } else {
            // Kommunikation mit OpenAI (Standard)
            $ai_result = $this->openai_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                // Extrahiere die Antwort aus dem OpenAI-Format
                if (isset($ai_result['choices'][0]['message']['content'])) {
                    $ai_response = $ai_result['choices'][0]['message']['content'];
                } else {
                    $ai_response = "Fehler bei der Verarbeitung der AI-Antwort: Unerwartetes Antwortformat";
                }
            } else {
                // Prüfe, ob es sich um den speziellen Quota-Fehler handelt
                if ($ai_result->get_error_code() === 'quota_exceeded') {
                    $ai_response = "### " . $ai_result->get_error_message();
                } else {
                    $ai_response = "Fehler bei der OpenAI-Kommunikation: " . $ai_result->get_error_message();
                }
            }
        }
        
        echo "Handler reached!\n\n";
        echo "POST:\n";
        print_r($_POST);
        echo "\nREQUEST:\n";
        print_r($_REQUEST);
        echo "\nGefilterte Felder:\n";
        print_r([
            'page_id'        => $page_id,
            'extra_info'     => $extra_info,
            'model_provider' => $model_provider,
            'page_content'   => $page_content,
            'custom_prompt'  => $custom_prompt,
        ]);
        
        if ($ai_response) {
            echo "\n\n--- OPENAI ANTWORT ---\n\n";
            echo $ai_response;
        }
        
        exit;
    }
    
    public function handleModalDebugProducts() {
        header('Content-Type: text/plain; charset=utf-8');
        $page_id = $_POST['page_id'] ?? null;
        $extra_info = $_POST['extra_info'] ?? null;
        $model_provider = $_POST['model_provider'] ?? 'openai'; // Default ist OpenAI
        $custom_prompt = $_POST['custom_prompt'] ?? null;
        $page_content = null;
        $ai_response = null;

        // Verwende den benutzerdefinierten Prompt, wenn vorhanden
        if ($custom_prompt) {
            $prompt = $custom_prompt;
            
            // Füge Seiteninhalt hinzu, wenn eine Seite ausgewählt wurde
            if ($page_id) {
                $post = get_post($page_id);
                if ($post && $post->post_type === 'page') {
                    $page_content = $post->post_content;
                    $prompt .= "\n\nSeiteninhalt:\n" . $page_content;
                }
            }
        } else {
            // Fallback auf den alten Prompt-Aufbau
            $prompt = "Du bist ein Assistent für WordPress-Inhalte. ";
            
            if ($page_id) {
                $post = get_post($page_id);
                if ($post && $post->post_type === 'page') {
                    $page_content = $post->post_content;
                    $prompt .= "Basierend auf dem folgenden Seiteninhalt und zusätzlichen Informationen, ";
                    $prompt .= "extrahiere alle Produkte und Dienstleistungen. ";
                    $prompt .= "Zusätzliche Informationen: " . $extra_info . "\n\n";
                    $prompt .= "Seiteninhalt:\n" . $page_content;
                }
            } else {
                // Wenn keine Seite ausgewählt wurde, nur die zusätzlichen Informationen verwenden
                $prompt .= "Basierend auf den folgenden Informationen, extrahiere alle Produkte und Dienstleistungen: ";
                $prompt .= "\n\n" . $extra_info;
            }
        }
                
        // Wähle den richtigen Service basierend auf model_provider
        if ($model_provider === 'gemini') {
            // Kommunikation mit Google Gemini
            $ai_result = $this->gemini_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                // Extrahiere die Antwort aus dem Gemini-Format
                $ai_response = $this->gemini_service->extract_content($ai_result);
            } else {
                // Prüfe, ob es sich um einen speziellen Fehler handelt
                if ($ai_result->get_error_code() === 'api_key_error') {
                    $ai_response = "### " . $ai_result->get_error_message();
                } else {
                    $ai_response = "Fehler bei der Google Gemini-Kommunikation: " . $ai_result->get_error_message();
                }
            }
        } else {
            // Kommunikation mit OpenAI (Standard)
            $ai_result = $this->openai_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                // Extrahiere die Antwort aus dem OpenAI-Format
                if (isset($ai_result['choices'][0]['message']['content'])) {
                    $ai_response = $ai_result['choices'][0]['message']['content'];
                } else {
                    $ai_response = "Fehler bei der Verarbeitung der AI-Antwort: Unerwartetes Antwortformat";
                }
            } else {
                // Prüfe, ob es sich um den speziellen Quota-Fehler handelt
                if ($ai_result->get_error_code() === 'quota_exceeded') {
                    $ai_response = "### " . $ai_result->get_error_message();
                } else {
                    $ai_response = "Fehler bei der OpenAI-Kommunikation: " . $ai_result->get_error_message();
                }
            }
        }
        
        echo "Handler reached!\n\n";
        echo "POST:\n";
        print_r($_POST);
        echo "\nREQUEST:\n";
        print_r($_REQUEST);
        echo "\nGefilterte Felder:\n";
        print_r([
            'page_id'        => $page_id,
            'extra_info'     => $extra_info,
            'model_provider' => $model_provider,
            'page_content'   => $page_content,
            'custom_prompt'  => $custom_prompt,
        ]);
        
        if ($ai_response) {
            echo "\n\n--- OPENAI ANTWORT ---\n\n";
            echo $ai_response;
        }
        
        exit;
    }
    
    /**
     * Handler für universelles Modal-System
     */
    public function handleModalDebugUniversal() {
        // Nonce-Prüfung
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'athena_ai_nonce')) {
            wp_die('Security check failed');
        }
        
        header('Content-Type: text/plain; charset=utf-8');
        
        $modal_type = $_POST['modal_type'] ?? 'company_description';
        $page_id = $_POST['page_id'] ?? null;
        $extra_info = $_POST['extra_info'] ?? null;
        $model_provider = $_POST['model_provider'] ?? 'openai';
        $custom_prompt = $_POST['custom_prompt'] ?? null;
        $page_content = null;
        $ai_response = null;

        // Verwende den benutzerdefinierten Prompt aus dem Prompt Manager
        if ($custom_prompt) {
            $prompt = $custom_prompt;
            
            // Füge Seiteninhalt hinzu, wenn eine Seite ausgewählt wurde
            if ($page_id) {
                $post = get_post($page_id);
                if ($post && $post->post_type === 'page') {
                    $page_content = $post->post_content;
                    $prompt .= "\n\nSeiteninhalt:\n" . $page_content;
                }
            }
        } else {
            // Fallback-Prompt basierend auf Modal-Typ
            $prompt = $this->buildFallbackPrompt($modal_type, $extra_info, $page_id);
        }
                
        // Wähle den richtigen Service basierend auf model_provider
        if ($model_provider === 'gemini') {
            $ai_result = $this->gemini_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                $ai_response = $this->gemini_service->extract_content($ai_result);
            } else {
                if ($ai_result->get_error_code() === 'api_key_error') {
                    $ai_response = "### " . $ai_result->get_error_message();
                } else {
                    $ai_response = "Fehler bei der Google Gemini-Kommunikation: " . $ai_result->get_error_message();
                }
            }
        } else {
            $ai_result = $this->openai_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                if (isset($ai_result['choices'][0]['message']['content'])) {
                    $ai_response = $ai_result['choices'][0]['message']['content'];
                } else {
                    $ai_response = "Fehler bei der Verarbeitung der AI-Antwort: Unerwartetes Antwortformat";
                }
            } else {
                if ($ai_result->get_error_code() === 'quota_exceeded') {
                    $ai_response = "### " . $ai_result->get_error_message();
                } else {
                    $ai_response = "Fehler bei der OpenAI-Kommunikation: " . $ai_result->get_error_message();
                }
            }
        }
        
        echo "Universal Modal Handler reached!\n\n";
        echo "Modal-Typ: " . $modal_type . "\n";
        echo "POST:\n";
        print_r($_POST);
        echo "\nGefilterte Felder:\n";
        print_r([
            'modal_type'     => $modal_type,
            'page_id'        => $page_id,
            'extra_info'     => $extra_info,
            'model_provider' => $model_provider,
            'page_content'   => $page_content,
            'custom_prompt'  => $custom_prompt,
        ]);
        
        if ($ai_response) {
            echo "\n\n--- OPENAI ANTWORT ---\n\n";
            echo $ai_response;
        }
        
        exit;
    }
    
    /**
     * Moderner Handler für Content-Generierung (JSON-Response)
     */
    public function handleGenerateContent() {
        // Nonce-Prüfung
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'athena_ai_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $prompt = $_POST['prompt'] ?? '';
        $provider = $_POST['provider'] ?? 'openai';
        $test_mode = $_POST['test_mode'] ?? false;
        
        if (empty($prompt)) {
            wp_send_json_error(['message' => 'Prompt ist erforderlich']);
            return;
        }
        
        if ($test_mode) {
            wp_send_json_success([
                'content' => 'Test-Modus: Hier würde der AI-generierte Content stehen.',
                'debug' => [
                    'prompt' => $prompt,
                    'provider' => $provider,
                    'test_mode' => true
                ]
            ]);
            return;
        }
        
        // Wähle den richtigen Service
        if ($provider === 'gemini') {
            $ai_result = $this->gemini_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                $content = $this->gemini_service->extract_content($ai_result);
                wp_send_json_success([
                    'content' => $content,
                    'provider' => 'gemini'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Gemini API Fehler: ' . $ai_result->get_error_message()
                ]);
            }
        } else {
            $ai_result = $this->openai_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                if (isset($ai_result['choices'][0]['message']['content'])) {
                    wp_send_json_success([
                        'content' => $ai_result['choices'][0]['message']['content'],
                        'provider' => 'openai'
                    ]);
                } else {
                    wp_send_json_error([
                        'message' => 'Unerwartetes OpenAI-Antwortformat'
                    ]);
                }
            } else {
                wp_send_json_error([
                    'message' => 'OpenAI API Fehler: ' . $ai_result->get_error_message()
                ]);
            }
        }
    }
    
    /**
     * Erstellt einen Fallback-Prompt basierend auf dem Modal-Typ
     */
    private function buildFallbackPrompt($modal_type, $extra_info, $page_id = null) {
        $prompts = [
            'company_description' => 'Erstelle eine professionelle Unternehmensbeschreibung (max. 100 Wörter) basierend auf folgenden Informationen: ',
            'products' => 'Analysiere den Text und liste alle Produkte und Dienstleistungen auf (durch Komma getrennt): ',

            'target_audience' => 'Beschreibe die Zielgruppe des Unternehmens (max. 80 Wörter): ',
            'company_usps' => 'Identifiziere die Alleinstellungsmerkmale (max. 5 USPs, je ein USP pro Zeile): ',
            'expertise_areas' => 'Extrahiere die Hauptkompetenzbereiche (max. 8, durch Komma getrennt): ',
            'seo_keywords' => 'Generiere relevante SEO-Keywords (max. 15, durch Komma getrennt): '
        ];
        
        $base_prompt = $prompts[$modal_type] ?? $prompts['company_description'];
        $prompt = $base_prompt . "\n\n" . $extra_info;
        
        // Seiteninhalt hinzufügen falls vorhanden
        if ($page_id) {
            $post = get_post($page_id);
            if ($post && $post->post_type === 'page') {
                $prompt .= "\n\nSeiteninhalt:\n" . $post->post_content;
            }
        }
        
        return $prompt;
    }
    
    /**
     * Universeller AJAX-Handler für profile-modals.js
     * Behandelt alle Prompt-Requests mit JSON-Response
     */
    public function handle_prompt_request() {
        // Nonce-Prüfung (optional, falls verwendet)
        $nonce = $_POST['nonce'] ?? '';
        if (!empty($nonce) && !wp_verify_nonce($nonce, 'athena_ai_ajax_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $modal_type = sanitize_text_field($_POST['modal_type'] ?? '');
        $page_id = intval($_POST['page_id'] ?? 0);
        $extra_info = sanitize_textarea_field($_POST['extra_info'] ?? '');
        $model_provider = sanitize_text_field($_POST['model_provider'] ?? 'openai');
        
        // Validierung
        if (empty($modal_type) || empty($extra_info)) {
            wp_send_json_error(['message' => 'Modal-Typ und zusätzliche Informationen sind erforderlich']);
            return;
        }
        
        // Prompt erstellen
        $prompt = $this->buildFallbackPrompt($modal_type, $extra_info, $page_id > 0 ? $page_id : null);
        
        // AI-Service auswählen und Content generieren
        if ($model_provider === 'gemini') {
            $ai_result = $this->gemini_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                $content = $this->gemini_service->extract_content($ai_result);
                wp_send_json_success([
                    'content' => $content,
                    'provider' => 'gemini',
                    'modal_type' => $modal_type,
                    'debug' => [
                        'prompt_length' => strlen($prompt),
                        'page_id' => $page_id
                    ]
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Gemini API Fehler: ' . $ai_result->get_error_message(),
                    'provider' => 'gemini'
                ]);
            }
        } else {
            // OpenAI (Standard)
            $ai_result = $this->openai_service->generate_content($prompt);
            
            if (!is_wp_error($ai_result)) {
                if (isset($ai_result['choices'][0]['message']['content'])) {
                    $content = $ai_result['choices'][0]['message']['content'];
                    wp_send_json_success([
                        'content' => $content,
                        'provider' => 'openai',
                        'modal_type' => $modal_type,
                        'debug' => [
                            'prompt_length' => strlen($prompt),
                            'page_id' => $page_id
                        ]
                    ]);
                } else {
                    wp_send_json_error([
                        'message' => 'Unerwartetes OpenAI-Antwortformat',
                        'provider' => 'openai'
                    ]);
                }
            } else {
                wp_send_json_error([
                    'message' => 'OpenAI API Fehler: ' . $ai_result->get_error_message(),
                    'provider' => 'openai'
                ]);
            }
        }
    }
} 