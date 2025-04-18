<?php
/**
 * Gütersloh Feed Processor
 * 
 * Diese Klasse enthält spezialisierte Funktionen zur Verarbeitung des Gütersloh-Feeds,
 * der eine nicht-standardkonforme XML-Struktur haben kann.
 * 
 * @package AthenaAI\Models
 */

namespace AthenaAI\Models;

/**
 * Gütersloh Feed Processor
 */
class GueterslohFeedProcessor {
    
    /**
     * Verarbeitet den Gütersloh-Feed und gibt die Items zurück
     *
     * @param string $content Der Inhalt des Feeds
     * @param bool $verbose_console Ob Konsolen-Output für Debugging angezeigt werden soll
     * @return array|false Ein Array von Feed-Items oder false bei Fehler
     */
    public static function process(string $content, bool $verbose_console = false) {
        if ($verbose_console) {
            echo '<script>console.log("GueterslohFeedProcessor: Starting specialized processing...");</script>';
        }
        
        // Leeren Inhalt abfangen
        if (empty($content)) {
            if ($verbose_console) {
                echo '<script>console.error("GueterslohFeedProcessor: Empty content provided");</script>';
            }
            return false;
        }
        
        // Vorbereitende Schritte zur Verbesserung der XML-Kompatibilität
        $content = self::prepare_content($content, $verbose_console);
        
        // Zeige den vorbereiteten Inhalt an
        if ($verbose_console) {
            $preview = substr($content, 0, 200);
            $safe_preview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
            echo '<script>console.log("GueterslohFeedProcessor: Prepared content: ' . esc_js($safe_preview) . '...");</script>';
        }
        
        // Versuche die Verarbeitung mit verschiedenen Methoden
        $items = self::try_simplexml_parsing($content, $verbose_console);
        
        // Wenn SimpleXML fehlschlägt, versuche RegEx-Parsing
        if ($items === false) {
            if ($verbose_console) {
                echo '<script>console.log("GueterslohFeedProcessor: SimpleXML parsing failed, trying regex parsing...");</script>';
            }
            $items = self::try_regex_parsing($content, $verbose_console);
        }
        
        // Wenn auch RegEx fehlschlägt, versuche das manuelle Extrahieren von Items
        if ($items === false) {
            if ($verbose_console) {
                echo '<script>console.log("GueterslohFeedProcessor: Regex parsing failed, trying manual extraction...");</script>';
            }
            $items = self::try_manual_extraction($content, $verbose_console);
        }
        
        // Wenn alle Methoden fehlschlagen, geben wir auf
        if ($items === false || empty($items)) {
            if ($verbose_console) {
                echo '<script>console.error("GueterslohFeedProcessor: All parsing methods failed");</script>';
            }
            return false;
        }
        
        // Erfolg! Zeige die Anzahl der gefundenen Items an
        if ($verbose_console) {
            echo '<script>console.log("GueterslohFeedProcessor: Successfully extracted ' . count($items) . ' items");</script>';
        }
        
        return $items;
    }
    
    /**
     * Bereitet den Feed-Content für die Verarbeitung vor
     *
     * @param string $content Der Feed-Inhalt
     * @param bool $verbose_console Ob Konsolen-Output für Debugging angezeigt werden soll
     * @return string Der vorbereitete Inhalt
     */
    private static function prepare_content(string $content, bool $verbose_console = false): string {
        // BOM entfernen (falls vorhanden)
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Normalisiere Zeilenumbrüche
        $content = str_replace("\r\n", "\n", $content);
        
        // Konvertiere ISO-8859-1 nach UTF-8 falls notwendig
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
            if ($verbose_console) {
                echo '<script>console.log("GueterslohFeedProcessor: Converted content from ISO-8859-1 to UTF-8");</script>';
            }
        }
        
        // Ersetze Umlaute in XML-Tags (aber nicht im Content)
        $content = preg_replace_callback('/<([^>]*)>/', function($matches) {
            return '<' . str_replace(
                ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'], 
                ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'], 
                $matches[1]
            ) . '>';
        }, $content);
        
        // XML-Header hinzufügen, wenn er fehlt
        if (strpos($content, '<?xml') === false) {
            $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
        }
        
        // Stelle sicher, dass es einen RSS-Wrapper gibt
        if (strpos($content, '<rss') === false && strpos($content, '<feed') === false) {
            if (strpos($content, '<item') !== false) {
                $content = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>Gütersloh Feed</title>' . $content . '</channel></rss>';
            }
        }
        
        // Entferne ungültige Zeichen
        $content = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $content);
        
        return $content;
    }
    
    /**
     * Versuche das Parsen mit SimpleXML
     *
     * @param string $content Der Feed-Inhalt
     * @param bool $verbose_console Ob Konsolen-Output für Debugging angezeigt werden soll
     * @return array|false Ein Array von Feed-Items oder false bei Fehler
     */
    private static function try_simplexml_parsing(string $content, bool $verbose_console = false) {
        // Fehler unterdrücken und Optionen für bessere Kompatibilität
        libxml_use_internal_errors(true);
        $xml_options = LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOCDATA | LIBXML_PARSEHUGE;
        
        // Versuch 1: Standard-Parsing
        $xml = @simplexml_load_string($content);
        
        // Versuch 2: Mit erweiterten Optionen
        if ($xml === false) {
            $xml = @simplexml_load_string($content, \SimpleXMLElement::class, $xml_options);
        }
        
        // Bei Fehler abbrechen
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            if ($verbose_console && !empty($errors)) {
                echo '<script>console.group("GueterslohFeedProcessor: SimpleXML parsing errors");</script>';
                foreach ($errors as $error) {
                    echo '<script>console.error("Line ' . $error->line . ': ' . esc_js($error->message) . '");</script>';
                }
                echo '<script>console.groupEnd();</script>';
            }
            
            return false;
        }
        
        // Items aus der XML-Struktur extrahieren
        $items = [];
        
        // RSS-Format
        if (isset($xml->channel) && isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $items[] = $item;
            }
        } 
        // Atom-Format
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $items[] = $entry;
            }
        }
        // Direktes Item-Format (ungewöhnlich, aber manchmal bei Gütersloh)
        elseif (isset($xml->item)) {
            foreach ($xml->item as $item) {
                $items[] = $item;
            }
        }
        
        if (empty($items)) {
            if ($verbose_console) {
                echo '<script>console.error("GueterslohFeedProcessor: No items found in XML structure");</script>';
            }
            return false;
        }
        
        return $items;
    }
    
    /**
     * Versuche das Parsen mit regulären Ausdrücken
     *
     * @param string $content Der Feed-Inhalt
     * @param bool $verbose_console Ob Konsolen-Output für Debugging angezeigt werden soll
     * @return array|false Ein Array von Feed-Items oder false bei Fehler
     */
    private static function try_regex_parsing(string $content, bool $verbose_console = false) {
        $items = [];
        
        // Extrahiere alle <item>-Tags mit ihrem Inhalt
        if (preg_match_all('/<item[^>]*>(.*?)<\/item>/is', $content, $matches)) {
            foreach ($matches[0] as $key => $item_xml) {
                $item = new \stdClass();
                
                // Titel extrahieren
                if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $item_xml, $title_match)) {
                    $item->title = trim(html_entity_decode(strip_tags($title_match[1])));
                } else {
                    $item->title = 'Untitled Item';
                }
                
                // Link extrahieren
                if (preg_match('/<link[^>]*>(.*?)<\/link>/is', $item_xml, $link_match)) {
                    $item->link = trim(strip_tags($link_match[1]));
                } elseif (preg_match('/<link[^>]*?href=[\'"]([^\'"]+)[\'"][^>]*?>/is', $item_xml, $link_match)) {
                    $item->link = trim($link_match[1]);
                } else {
                    $item->link = '';
                }
                
                // Beschreibung extrahieren
                if (preg_match('/<description[^>]*>(.*?)<\/description>/is', $item_xml, $desc_match)) {
                    $item->description = trim(html_entity_decode(strip_tags($desc_match[1])));
                } elseif (preg_match('/<content[^>]*>(.*?)<\/content>/is', $item_xml, $desc_match)) {
                    $item->description = trim(html_entity_decode(strip_tags($desc_match[1])));
                } else {
                    $item->description = '';
                }
                
                // Datum extrahieren
                if (preg_match('/<pubDate[^>]*>(.*?)<\/pubDate>/is', $item_xml, $date_match)) {
                    $item->pubDate = trim(strip_tags($date_match[1]));
                } elseif (preg_match('/<published[^>]*>(.*?)<\/published>/is', $item_xml, $date_match)) {
                    $item->pubDate = trim(strip_tags($date_match[1]));
                } else {
                    $item->pubDate = date('r'); // Aktuelles Datum als Fallback
                }
                
                // GUID generieren, falls keine vorhanden
                if (preg_match('/<guid[^>]*>(.*?)<\/guid>/is', $item_xml, $guid_match)) {
                    $item->guid = trim(strip_tags($guid_match[1]));
                } else {
                    // GUID aus Link oder Titel generieren
                    $item->guid = !empty($item->link) ? $item->link : md5($item->title . $item->pubDate);
                }
                
                $items[] = $item;
            }
        }
        
        if (empty($items)) {
            return false;
        }
        
        return $items;
    }
    
    /**
     * Versuche eine manuelle Extraktion von Items
     *
     * @param string $content Der Feed-Inhalt
     * @param bool $verbose_console Ob Konsolen-Output für Debugging angezeigt werden soll
     * @return array|false Ein Array von Feed-Items oder false bei Fehler
     */
    private static function try_manual_extraction(string $content, bool $verbose_console = false) {
        // Letzte Rettung: Händische Extraktion mit einfacher Heuristik
        $items = [];
        
        // Teile den Inhalt bei offensichtlichen Item-Trennzeichen
        $potential_items = preg_split('/<item[^>]*>|<entry[^>]*>/i', $content);
        
        if (count($potential_items) <= 1) {
            return false;
        }
        
        // Ignoriere den ersten Teil, da er wahrscheinlich kein Item ist
        array_shift($potential_items);
        
        foreach ($potential_items as $item_content) {
            // Erstelle ein Dummy-Item mit Fallback-Werten
            $item = new \stdClass();
            $item->title = 'Gütersloh Feed Item';
            $item->link = '';
            $item->description = '';
            $item->pubDate = date('r');
            $item->guid = md5($item_content);
            
            // Versuche, Titel zu extrahieren
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $item_content, $title_match)) {
                $item->title = trim(html_entity_decode(strip_tags($title_match[1])));
            }
            
            // Versuche, Link zu extrahieren
            if (preg_match('/href=[\'"]([^\'"]+)[\'"]|<link[^>]*>(.*?)<\/link>/is', $item_content, $link_match)) {
                $item->link = !empty($link_match[1]) ? trim($link_match[1]) : trim(strip_tags($link_match[2] ?? ''));
            }
            
            // Nehme den gesamten Inhalt als Beschreibung, falls nichts anderes gefunden wird
            $item->description = trim(strip_tags($item_content));
            
            // Versuche, ein besseres GUID zu erstellen
            $item->guid = !empty($item->link) ? $item->link : md5($item->title . $item->description);
            
            $items[] = $item;
        }
        
        if (empty($items)) {
            return false;
        }
        
        return $items;
    }
    
    /**
     * Konvertiert die extrahierten Items in ein WordPress-kompatibles Format
     *
     * @param array $items Die extrahierten Feed-Items
     * @param bool $verbose_console Ob Konsolen-Output für Debugging angezeigt werden soll
     * @return array Ein Array von standardisierten Feed-Items
     */
    public static function standardize_items(array $items, bool $verbose_console = false): array {
        $standardized_items = [];
        
        foreach ($items as $item) {
            $standardized = [
                'title' => '',
                'link' => '',
                'description' => '',
                'pubDate' => '',
                'guid' => '',
            ];
            
            // Titel
            if (isset($item->title)) {
                $standardized['title'] = is_string($item->title) ? $item->title : (string)$item->title;
            }
            
            // Link
            if (isset($item->link)) {
                if (is_string($item->link)) {
                    $standardized['link'] = $item->link;
                } elseif (isset($item->link['href'])) {
                    $standardized['link'] = (string)$item->link['href'];
                } else {
                    $standardized['link'] = (string)$item->link;
                }
            }
            
            // Beschreibung
            if (isset($item->description)) {
                $standardized['description'] = is_string($item->description) ? $item->description : (string)$item->description;
            } elseif (isset($item->content)) {
                $standardized['description'] = is_string($item->content) ? $item->content : (string)$item->content;
            }
            
            // Publikationsdatum
            if (isset($item->pubDate)) {
                $standardized['pubDate'] = is_string($item->pubDate) ? $item->pubDate : (string)$item->pubDate;
            } elseif (isset($item->published)) {
                $standardized['pubDate'] = is_string($item->published) ? $item->published : (string)$item->published;
            } elseif (isset($item->updated)) {
                $standardized['pubDate'] = is_string($item->updated) ? $item->updated : (string)$item->updated;
            } else {
                $standardized['pubDate'] = date('r');
            }
            
            // GUID
            if (isset($item->guid)) {
                $standardized['guid'] = is_string($item->guid) ? $item->guid : (string)$item->guid;
            } elseif (!empty($standardized['link'])) {
                $standardized['guid'] = $standardized['link'];
            } else {
                $standardized['guid'] = md5($standardized['title'] . $standardized['pubDate']);
            }
            
            $standardized_items[] = $standardized;
        }
        
        return $standardized_items;
    }
}
