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
        
        // Initialisiere die Services
        $this->openai_service = new OpenAIService();
        $this->gemini_service = new GeminiService();
    }

    public function handleModalDebug() {
        header('Content-Type: text/plain; charset=utf-8');
        $page_id = $_POST['page_id'] ?? null;
        $extra_info = $_POST['extra_info'] ?? null;
        $model_provider = $_POST['model_provider'] ?? 'openai'; // Default ist OpenAI
        $page_content = null;
        $ai_response = null;

        // Erstelle den Prompt für die KI
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
        ]);
        
        if ($ai_response) {
            echo "\n\n--- OPENAI ANTWORT ---\n\n";
            echo $ai_response;
        }
        
        exit;
    }
} 