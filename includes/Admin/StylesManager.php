<?php
/**
 * Styles Manager
 *
 * @package AthenaAI\Admin
 */

namespace AthenaAI\Admin;

/**
 * StylesManager class
 */
class StylesManager extends BaseAdmin {
    /**
     * Initialize the StylesManager
     */
    public function __construct() {
        // Lade die Tailwind CSS Styles im Admin-Bereich
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);

        // Füge Body-Klasse für Tailwind-Scope hinzu
        \add_filter('admin_body_class', [$this, 'add_admin_body_class']);
    }

    /**
     * Enqueue Tailwind CSS Styles im Admin-Bereich
     *
     * @param string $hook_suffix Der aktuelle Admin-Hook
     */
    public function enqueue_admin_styles($hook_suffix) {
        // Stelle sicher, dass $hook_suffix ein String ist
        if (!is_string($hook_suffix)) {
            $hook_suffix = '';
        }

        // Auf allen Plugin-Seiten laden, AUSSER New AI Post (lädt eigenes CSS)
        if (
            (($hook_suffix !== '' && strpos($hook_suffix, 'athena-ai') !== false) ||
            ($hook_suffix !== '' && strpos($hook_suffix, 'athena-feed-items') !== false) ||
            ($hook_suffix !== '' && strpos($hook_suffix, 'athena-feed-maintenance') !== false) ||
            ($hook_suffix !== '' && strpos($hook_suffix, 'athena-database-upgrade') !== false) ||
            (isset($_GET['post_type']) && $_GET['post_type'] === 'athena-feed') ||
            $hook_suffix === 'toplevel_page_athena-feed-items' ||
            ($hook_suffix !== '' && strpos($hook_suffix, 'athena_page') !== false)) &&
            // EXCLUDE New AI Post page (it loads its own CSS)
            $hook_suffix !== 'feed-items_page_athena-new-ai-post' &&
            strpos($hook_suffix, 'athena-new-ai-post') === false
        ) {
            // Tailwind CSS - use the correct admin.css file
            \wp_enqueue_style(
                'athena-ai-tailwind',
                ATHENA_AI_PLUGIN_URL . 'assets/css/admin.css',
                [],
                ATHENA_AI_VERSION
            );

            // Google Fonts
            \wp_enqueue_style(
                'athena-ai-google-fonts',
                'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
                [],
                ATHENA_AI_VERSION
            );

            // Font Awesome für Icons
            \wp_enqueue_style(
                'athena-ai-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                [],
                '6.4.0'
            );

            // Add inline CSS to ensure styles work
            \wp_add_inline_style('athena-ai-tailwind', '
                /* Force styles for athena-ai-admin context */
                .athena-ai-admin .bg-white { background-color: #ffffff !important; }
                .athena-ai-admin .text-2xl { font-size: 1.5rem !important; line-height: 2rem !important; }
                .athena-ai-admin .font-bold { font-weight: 700 !important; }
                .athena-ai-admin .text-gray-800 { color: #1f2937 !important; }
                .athena-ai-admin .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; }
                .athena-ai-admin .px-6 { padding-left: 1.5rem !important; padding-right: 1.5rem !important; }
                .athena-ai-admin .py-5 { padding-top: 1.25rem !important; padding-bottom: 1.25rem !important; }
                .athena-ai-admin .mb-6 { margin-bottom: 1.5rem !important; }
                .athena-ai-admin .rounded-lg { border-radius: 0.5rem !important; }
                .athena-ai-admin .border { border-width: 1px !important; }
                .athena-ai-admin .border-gray-100 { border-color: #f3f4f6 !important; }
                .athena-ai-admin .flex { display: flex !important; }
                .athena-ai-admin .justify-between { justify-content: space-between !important; }
                .athena-ai-admin .items-center { align-items: center !important; }
                .athena-ai-admin .m-0 { margin: 0 !important; }
                .athena-ai-admin .bg-purple-100 { background-color: #f3e8ff !important; }
                .athena-ai-admin .text-purple-600 { color: #9333ea !important; }
                .athena-ai-admin .p-2 { padding: 0.5rem !important; }
                .athena-ai-admin .mr-3 { margin-right: 0.75rem !important; }
                .athena-ai-admin .p-8 { padding: 2rem !important; }
                .athena-ai-admin .max-w-4xl { max-width: 56rem !important; }
                .athena-ai-admin .mx-auto { margin-left: auto !important; margin-right: auto !important; }
                .athena-ai-admin .space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: 1.5rem !important; }
                .athena-ai-admin .min-h-screen { min-height: 100vh !important; }
                .athena-ai-admin .p-6 { padding: 1.5rem !important; }
                .athena-ai-admin .border-gray-200 { border-color: #e5e7eb !important; }
                .athena-ai-admin .hover\\:border-purple-300:hover { border-color: #c084fc !important; }
                .athena-ai-admin .transition-colors { transition-property: color, background-color, border-color, text-decoration-color, fill, stroke !important; }
                .athena-ai-admin .duration-200 { transition-duration: 200ms !important; }
                .athena-ai-admin .text-lg { font-size: 1.125rem !important; line-height: 1.75rem !important; }
                .athena-ai-admin .font-medium { font-weight: 500 !important; }
                .athena-ai-admin .text-gray-900 { color: #111827 !important; }
                .athena-ai-admin .text-gray-600 { color: #4b5563 !important; }
                .athena-ai-admin .text-sm { font-size: 0.875rem !important; line-height: 1.25rem !important; }
                .athena-ai-admin .space-x-3 > :not([hidden]) ~ :not([hidden]) { margin-left: 0.75rem !important; }
                .athena-ai-admin .cursor-pointer { cursor: pointer !important; }
                .athena-ai-admin .mt-1 { margin-top: 0.25rem !important; }
                .athena-ai-admin .grid { display: grid !important; }
                .athena-ai-admin .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
                .athena-ai-admin .md\\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
                .athena-ai-admin .gap-4 { gap: 1rem !important; }
                .athena-ai-admin .text-center { text-align: center !important; }
                .athena-ai-admin .mb-3 { margin-bottom: 0.75rem !important; }
                .athena-ai-admin .text-blue-600 { color: #2563eb !important; }
                .athena-ai-admin .mb-2 { margin-bottom: 0.5rem !important; }
                .athena-ai-admin .text-green-600 { color: #16a34a !important; }
                .athena-ai-admin .text-orange-600 { color: #ea580c !important; }
                .athena-ai-admin .block { display: block !important; }
                .athena-ai-admin .text-gray-700 { color: #374151 !important; }
                .athena-ai-admin .w-full { width: 100% !important; }
                .athena-ai-admin .px-3 { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
                .athena-ai-admin .py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
                .athena-ai-admin .border-gray-300 { border-color: #d1d5db !important; }
                .athena-ai-admin .focus\\:ring-purple-500:focus { --tw-ring-color: #a855f7 !important; }
                .athena-ai-admin .focus\\:border-purple-500:focus { border-color: #a855f7 !important; }
                .athena-ai-admin .hidden { display: none !important; }
                .athena-ai-admin .max-w-2xl { max-width: 42rem !important; }
                .athena-ai-admin .space-y-4 > :not([hidden]) ~ :not([hidden]) { margin-top: 1rem !important; }
            ');
        }
    }

    /**
     * Fügt die athena-ai-admin Klasse zum Body hinzu für Tailwind-Scoping
     *
     * @param string $classes Bestehende Klassen
     * @return string Modifizierte Klassen
     */
    public function add_admin_body_class($classes) {
        global $hook_suffix;

        // Stelle sicher, dass $hook_suffix ein String ist
        if (!is_string($hook_suffix)) {
            $hook_suffix = '';
        }

        // Auf allen Plugin-Seiten anwenden, AUSSER New AI Post
        if (
            (($hook_suffix !== '' && strpos($hook_suffix, 'athena-ai') !== false) ||
            ($hook_suffix !== '' && strpos($hook_suffix, 'athena-feed-items') !== false) ||
            ($hook_suffix !== '' && strpos($hook_suffix, 'athena-feed-maintenance') !== false) ||
            ($hook_suffix !== '' && strpos($hook_suffix, 'athena-database-upgrade') !== false) ||
            (isset($_GET['post_type']) && $_GET['post_type'] === 'athena-feed') ||
            $hook_suffix === 'toplevel_page_athena-feed-items' ||
            ($hook_suffix !== '' && strpos($hook_suffix, 'athena_page') !== false)) &&
            // EXCLUDE New AI Post page
            $hook_suffix !== 'feed-items_page_athena-new-ai-post' &&
            strpos($hook_suffix, 'athena-new-ai-post') === false
        ) {
            $classes .= ' athena-ai-admin';
        }

        return $classes;
    }
}
