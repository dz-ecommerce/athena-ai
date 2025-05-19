error_log('ATHENA AI DEBUG: functions.php geladen');
add_action('wp_ajax_athena_ai_modal_debug', function() {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Handler reached!\n\n";
    echo "POST:\n";
    print_r($_POST);
    echo "\nREQUEST:\n";
    print_r($_REQUEST);
    echo "\nGefilterte Felder:\n";
    print_r([
        'page_id'    => $_POST['page_id'] ?? null,
        'extra_info' => $_POST['extra_info'] ?? null,
    ]);
    exit;
});
add_action('wp_ajax_nopriv_athena_ai_modal_debug', function() {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Handler reached!\n\n";
    echo "POST:\n";
    print_r($_POST);
    echo "\nREQUEST:\n";
    print_r($_REQUEST);
    echo "\nGefilterte Felder:\n";
    print_r([
        'page_id'    => $_POST['page_id'] ?? null,
        'extra_info' => $_POST['extra_info'] ?? null,
    ]);
    exit;
}); 