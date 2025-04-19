<?php
/**
 * RSS Feed Processor
 * 
 * Processor for standard and non-standard RSS feeds. Includes support for:
 * - RSS 2.0 (standard)  
 * - RSS 1.0 (RDF)  
 * - Non-standard RSS variants (like Gütersloh Feed)  
 * 
 * @package AthenaAI\Models\FeedProcessor
 */

namespace AthenaAI\Models\FeedProcessor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RSS Feed Processor
 */
class RssFeedProcessor extends AbstractFeedProcessor {
    
    /**
     * Process feed content
     *
     * @param string $content The feed content
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array|false Array of feed items or false on failure
     */
    public function process(string $content, bool $verbose_console = false): array|false {
        $this->debug_log("Starting RSS feed processing", "log", $verbose_console);
        
        // Empty content check
        if (empty($content)) {
            $this->debug_log("Empty content provided", "error", $verbose_console);
            return false;
        }
        
        // Special preprocessing for potentially problematic feeds
        $content = $this->preprocess_problematic_feed($content, $verbose_console);
        
        // Prepare content for parsing
        $content = $this->prepare_content($content, $verbose_console);
        
        // Show prepared content preview
        if ($verbose_console) {
            $preview = substr($content, 0, 200);
            $safe_preview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
            $this->debug_log("Prepared content: {$safe_preview}...", "log", $verbose_console);
        }
        
        // Try different parsing methods in sequence
        $items = $this->try_simplexml_parsing($content, $verbose_console);
        
        // If SimpleXML fails, try RegEx parsing
        if ($items === false) {
            $this->debug_log("SimpleXML parsing failed, trying regex parsing", "log", $verbose_console);
            $items = $this->try_regex_parsing($content, $verbose_console);
        }
        
        // If regex parsing also fails, try manual extraction
        if ($items === false) {
            $this->debug_log("Regex parsing failed, trying manual extraction", "log", $verbose_console);
            $items = $this->try_manual_extraction($content, $verbose_console);
        }
        
        // If all methods fail, return false
        if ($items === false || empty($items)) {
            $this->debug_log("All parsing methods failed", "error", $verbose_console);
            return false;
        }
        
        // Success! Log the number of found items
        $this->debug_log("Successfully extracted " . count($items) . " items", "log", $verbose_console);
        
        return $items;
    }
    
    /**
     * Preprocess potentially problematic feeds
     *
     * @param string $content The feed content
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return string The preprocessed content
     */
    private function preprocess_problematic_feed(string $content, bool $verbose_console = false): string {
        // Ersetze kritische Umlaute in XML-Tags (aber nicht im Content)
        $content = preg_replace_callback('/<([^>]*)>/', function($matches) {
            return '<' . str_replace(
                ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'], 
                ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'], 
                $matches[1]
            ) . '>';
        }, $content);
        
        // Stelle sicher, dass XML-Header korrekt ist
        if (strpos($content, '<?xml') === false) {
            $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
            if ($verbose_console) {
                $this->debug_log("Added missing XML header", "log", $verbose_console);
            }
        }
        
        // Versuche, das Format zu reparieren, wenn nötig
        if (strpos($content, '<rss') === false && strpos($content, '<feed') === false) {
            if (strpos($content, '<item') !== false) {
                // Entdecktes Format: <item>-Tags ohne <rss>-Wrapper
                if ($verbose_console) {
                    $this->debug_log("Feed appears to be missing RSS container tags, adding wrapper", "log", $verbose_console);
                }
                $content = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>' . $content . '</channel></rss>';
            }
        }
        
        return $content;
    }
    
    /**
     * Try to parse using SimpleXML 
     *
     * @param string $content The feed content
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array|false Array of feed items or false on failure
     */
    private function try_simplexml_parsing(string $content, bool $verbose_console = false): array|false {
        // Use libxml internal errors for better error handling
        libxml_use_internal_errors(true);
        
        // XML-Optionen anpassen für bessere Feed-Kompatibilität
        $xml_options = LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOCDATA;
        
        // Versuch 1: Normales Laden
        $xml = @simplexml_load_string($content);
        
        // Versuch 2: Mit Optionen laden
        if ($xml === false) {
            $xml = @simplexml_load_string($content, \SimpleXMLElement::class, $xml_options);
        }
        
        // Versuch 3: Mit erweiterten Optionen laden
        if ($xml === false) {
            $extended_options = $xml_options | LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_NOBLANKS;
            $xml = @simplexml_load_string($content, \SimpleXMLElement::class, $extended_options);
        }
        
        // Versuch 4: Mit DOMDocument als Fallback
        if ($xml === false) {
            if ($verbose_console) {
                $this->debug_log("Attempting to load XML with DOMDocument as fallback", "log", $verbose_console);
            }
            
            $dom = new \DOMDocument();
            $dom->recover = true; // Versuche, fehlerhafte XML zu reparieren
            $dom->strictErrorChecking = false;
            $dom->validateOnParse = false;
            
            // Fehler unterdrücken während des Ladens
            $success = @$dom->loadXML($content);
            
            if ($success) {
                if ($verbose_console) {
                    $this->debug_log("Successfully loaded XML with DOMDocument", "log", $verbose_console);
                }
                
                // Konvertiere DOMDocument zu SimpleXML
                $xml = simplexml_import_dom($dom);
            }
        }
        
        // Log parsing errors if all methods failed
        if ($xml === false) {
            if ($verbose_console) {
                $this->debug_log("SimpleXML parsing failed with errors:", "error", $verbose_console);
                echo '<script>console.group("RSS Feed Parsing Errors");</script>';
                
                foreach (libxml_get_errors() as $error) {
                    $message = "Line {$error->line}, Column {$error->column}: {$error->message}";
                    echo "<script>console.error(" . json_encode($message) . ");</script>";
                }
                
                echo '<script>console.groupEnd();</script>';
            }
            
            libxml_clear_errors();
            return false;
        }
        
        // Extract items based on RSS structure
        $items = [];
        
        // Check for channel > item structure (RSS 2.0)
        if (isset($xml->channel) && isset($xml->channel->item)) {
            $this->debug_log("RSS 2.0 structure detected", "log", $verbose_console);
            $items = $xml->channel->item;
        } 
        // Check for direct item elements (some non-standard RSS)
        elseif (isset($xml->item)) {
            $this->debug_log("Non-standard RSS structure with direct items detected", "log", $verbose_console);
            $items = $xml->item;
        }
        // Check for RDF structure (RSS 1.0)
        elseif (isset($xml->item) && $xml->getNamespaces() && in_array('http://www.w3.org/1999/02/22-rdf-syntax-ns#', $xml->getNamespaces())) {
            $this->debug_log("RSS 1.0 (RDF) structure detected", "log", $verbose_console);
            $items = $xml->item;
        }
        // Check other possible structures using XPath
        elseif (count($xml->xpath('//item')) > 0) {
            $items = $xml->xpath('//item');
            $this->debug_log("Found items using XPath //item", "log", $verbose_console);
        }
        
        // Convert items to array for standardization
        $items_array = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                $items_array[] = $item;
            }
        }
        
        // Check if we found any items
        if (empty($items_array)) {
            $this->debug_log("No items found in RSS feed structure", "error", $verbose_console);
            return false;
        }
        
        $this->debug_log("Successfully extracted " . count($items_array) . " items", "log", $verbose_console);
        
        // Standardize items to a common format
        return $this->standardize_items($items_array, $verbose_console);
    }
    
    /**
     * Try parsing with regular expressions
     *
     * @param string $content The feed content
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array|false Array of feed items or false on failure
     */
    private function try_regex_parsing(string $content, bool $verbose_console = false): array|false {
        // Versuche, Items mit RegEx zu extrahieren
        $items = [];
        
        // Versuche, <item> Tags zu finden
        if (preg_match_all('/<item[^>]*>(.*?)<\/item>/is', $content, $matches)) {
            foreach ($matches[0] as $item_content) {
                $item = new \stdClass();
                
                // Extrahiere Titel
                if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $item_content, $title_match)) {
                    $item->title = html_entity_decode(strip_tags($title_match[1]));
                } else {
                    $item->title = 'No Title';
                }
                
                // Extrahiere Link
                if (preg_match('/<link[^>]*>(.*?)<\/link>/is', $item_content, $link_match)) {
                    $item->link = strip_tags($link_match[1]);
                } else {
                    $item->link = '';
                }
                
                // Extrahiere Beschreibung
                if (preg_match('/<description[^>]*>(.*?)<\/description>/is', $item_content, $desc_match)) {
                    $item->description = $desc_match[1];
                } elseif (preg_match('/<content:encoded[^>]*>(.*?)<\/content:encoded>/is', $item_content, $content_match)) {
                    $item->description = $content_match[1];
                } else {
                    $item->description = '';
                }
                
                // Extrahiere Datum
                if (preg_match('/<pubDate[^>]*>(.*?)<\/pubDate>/is', $item_content, $date_match)) {
                    $item->pubDate = strip_tags($date_match[1]);
                } else {
                    $item->pubDate = date('r');
                }
                
                // Extrahiere GUID
                if (preg_match('/<guid[^>]*>(.*?)<\/guid>/is', $item_content, $guid_match)) {
                    $item->guid = strip_tags($guid_match[1]);
                } else {
                    $item->guid = !empty($item->link) ? $item->link : md5($item->title . $item->pubDate);
                }
                
                $items[] = $item;
            }
        }
        
        if (empty($items)) {
            return false;
        }
        
        return $this->standardize_items($items, $verbose_console);
    }
    
    /**
     * Try to extract items manually as a last resort
     *
     * @param string $content The feed content
     * @param bool $verbose_console Whether to output verbose debugging information
     * @return array|false Array of feed items or false on failure
     */
    private function try_manual_extraction(string $content, bool $verbose_console = false): array|false {
        // Versuche, Items manuell zu extrahieren
        $items = [];
        
        // Splitte den Content bei <item> oder ähnlichen Tags
        $pattern = '/<(item|entry)[^>]*>.*?<\/(item|entry)>/is';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $item_content) {
                $item = new \stdClass();
                $item->title = 'No Title';
                $item->link = '';
                $item->description = '';
                $item->pubDate = date('r');
                $item->guid = md5($item_content);
                
                // Versuche, Titel zu extrahieren
                if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $item_content, $title_match)) {
                    $item->title = trim(html_entity_decode(strip_tags($title_match[1])));
                }
                
                // Versuche, Link zu extrahieren
                if (preg_match('/href=[\'"]([\'"]+)[\'"]/is', $item_content, $href_match)) {
                    $item->link = trim($href_match[1]);
                } elseif (preg_match('/<link[^>]*>(.*?)<\/link>/is', $item_content, $link_match)) {
                    $item->link = trim(strip_tags($link_match[1] ?? ''));
                }
                
                // Versuche, Beschreibung zu extrahieren
                if (preg_match('/<description[^>]*>(.*?)<\/description>/is', $item_content, $desc_match)) {
                    $item->description = $desc_match[1];
                } elseif (preg_match('/<content:encoded[^>]*>(.*?)<\/content:encoded>/is', $item_content, $content_match)) {
                    $item->description = $content_match[1];
                } else {
                    // Nehme den gesamten Inhalt als Beschreibung, falls nichts anderes gefunden wird
                    $item->description = strip_tags($item_content);
                }
                
                // Versuche, ein besseres GUID zu erstellen
                $item->guid = !empty($item->link) ? $item->link : md5($item->title . $item->description);
                
                $items[] = $item;
            }
        }
        
        if (empty($items)) {
            return false;
        }
        
        return $this->standardize_items($items, $verbose_console);
    }
}
