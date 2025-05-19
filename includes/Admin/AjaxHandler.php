<?php

namespace AthenaAI\Admin;

class AjaxHandler {
    public function __construct() {
        add_action('wp_ajax_athena_ai_modal_debug', [$this, 'handleModalDebug']);
        add_action('wp_ajax_nopriv_athena_ai_modal_debug', [$this, 'handleModalDebug']);
    }

    public function handleModalDebug() {
        header('Content-Type: text/plain; charset=utf-8');
        $page_id = $_POST['page_id'] ?? null;
        $extra_info = $_POST['extra_info'] ?? null;
        $page_content = null;

        if ($page_id) {
            $post = get_post($page_id);
            if ($post && $post->post_type === 'page') {
                $page_content = $post->post_content;
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
        exit;
    }
} 