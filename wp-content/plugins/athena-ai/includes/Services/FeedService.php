<?php
/**
 * Feed Service class (minimal implementation without external dependencies)
 *
 * @package AthenaAI\Services
 */

declare(strict_types=1);

namespace AthenaAI\Services;

if (!defined('ABSPATH')) {
    exit();
}

class FeedService {
    /**
     * Extrahiert den Volltext einer News-Seite anhand der Link-URL.
     * 
     * Diese Implementierung verwendet einfaches DOM-Parsing ohne externe Bibliotheken.
     *
     * @param string $url Die URL der News-Seite
     * @return string|null Der extrahierte Hauptinhalt oder null bei Fehler
     */
    private function extractFullTextFromUrl(string $url): ?string {
        try {
            $html = file_get_contents($url);
            if (empty($html)) {
                return null;
            }
            
            // Einfache Extraktion mit DOMDocument
            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new \DOMXPath($dom);
            
            // Versuche, den Hauptinhalt zu finden (vereinfachte Implementierung)
            $contentNodes = $xpath->query('//article|//div[@class="content"]|//div[@id="content"]|//div[@class="post-content"]');
            
            if ($contentNodes && $contentNodes->length > 0) {
                return $contentNodes->item(0)->textContent;
            }
            
            // Fallback: Hole <p>-Tags aus dem Body
            $paragraphs = $xpath->query('//body//p');
            if ($paragraphs && $paragraphs->length > 0) {
                $content = '';
                foreach ($paragraphs as $p) {
                    $content .= $p->textContent . "\n\n";
                }
                return $content;
            }
        } catch (\Throwable $e) {
            // Fehlerbehandlung (z.B. Logging)
        }
        return null;
    }
}
