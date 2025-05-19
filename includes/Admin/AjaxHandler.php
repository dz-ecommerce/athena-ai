<?php

namespace AthenaAI\Admin;

use AthenaAI\Services\OpenAIService;

class AjaxHandler {
    /**
     * OpenAI Service instance
     * 
     * @var OpenAIService
     */
    private $openai_service;
    
    public function __construct() {
        add_action('wp_ajax_athena_ai_modal_debug', [$this, 'handleModalDebug']);
        add_action('wp_ajax_nopriv_athena_ai_modal_debug', [$this, 'handleModalDebug']);
        
        // Initialisiere den OpenAI Service
        $this->openai_service = new OpenAIService();
    }

    public function handleModalDebug() {
        header('Content-Type: text/plain; charset=utf-8');
        $page_id = $_POST['page_id'] ?? null;
        $extra_info = $_POST['extra_info'] ?? null;
        $page_content = null;
        $ai_response = null;

        if ($page_id) {
            $post = get_post($page_id);
            if ($post && $post->post_type === 'page') {
                $page_content = $post->post_content;
                
                // Erstelle den Prompt für OpenAI
                $prompt = "Du bist ein Assistent für WordPress-Inhalte. ";
                $prompt .= "Basierend auf dem folgenden Seiteninhalt und zusätzlichen Informationen, ";
                $prompt .= "erstelle eine optimierte Version des Inhalts. ";
                $prompt .= "Zusätzliche Informationen: " . $extra_info . "\n\n";
                $prompt .= "Seiteninhalt:\n" . $page_content;
                
                // Kommunikation mit OpenAI
                $ai_result = $this->openai_service->generate_content($prompt);
                
                if (!is_wp_error($ai_result)) {
                    // Extrahiere die Antwort
                    if (isset($ai_result['choices'][0]['message']['content'])) {
                        $ai_response = $ai_result['choices'][0]['message']['content'];
                    } else {
                        $ai_response = "Fehler bei der Verarbeitung der AI-Antwort: Unerwartetes Antwortformat";
                    }
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
            'page_id'      => $page_id,
            'extra_info'   => $extra_info,
            'page_content' => $page_content,
        ]);
        
        if ($ai_response) {
            echo "\n\n--- OPENAI ANTWORT ---\n\n";
            echo $ai_response;
        }
        
        exit;
    }
} 