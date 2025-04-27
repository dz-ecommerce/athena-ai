<?php
/**
 * Zentrale Wrapper-Klasse für sichere WordPress-Funktionen.
 *
 * @package AthenaAI\Core
 */

namespace AthenaAI\Core;

use AthenaAI\Helpers\UrlHelper;
use AthenaAI\Helpers\StringHelper;

/**
 * SafetyWrapper bietet zentrale statische Methoden für alle kritischen WordPress-Funktionen,
 * um NULL-Werte abzufangen und PHP 8 Deprecated-Warnungen zu vermeiden.
 */
class SafetyWrapper {
    /**
     * Sichere Version von esc_url, die NULL-Werte abfängt.
     *
     * @param string|null $url Die URL, die escaped werden soll.
     * @return string Die escaped URL oder ein leerer String, wenn $url null ist.
     */
    public static function esc_url($url): string {
        return UrlHelper::safe_esc_url($url);
    }
    
    /**
     * Sichere Version von esc_url_raw, die NULL-Werte abfängt.
     *
     * @param string|null $url Die URL, die escaped werden soll.
     * @return string Die escaped URL oder ein leerer String, wenn $url null ist.
     */
    public static function esc_url_raw($url): string {
        return UrlHelper::safe_esc_url_raw($url);
    }
    
    /**
     * Sichere Version von esc_attr, die NULL-Werte abfängt.
     *
     * @param string|null $text Der Text, der escaped werden soll.
     * @return string Der escaped Text oder ein leerer String, wenn $text null ist.
     */
    public static function esc_attr($text): string {
        return StringHelper::safe_esc_attr($text);
    }
    
    /**
     * Sichere Version von esc_html, die NULL-Werte abfängt.
     *
     * @param string|null $text Der Text, der escaped werden soll.
     * @return string Der escaped Text oder ein leerer String, wenn $text null ist.
     */
    public static function esc_html($text): string {
        return StringHelper::safe_esc_html($text);
    }
    
    /**
     * Sichere Version von esc_js, die NULL-Werte abfängt.
     *
     * @param string|null $text Der Text, der für JavaScript escaped werden soll.
     * @return string Der escaped Text oder ein leerer String, wenn $text null ist.
     */
    public static function esc_js($text): string {
        if ($text === null) {
            return '';
        }
        
        // Typumwandlung zu String
        if (!is_string($text)) {
            $text = (string)$text;
        }
        
        // WordPress esc_js verwenden, falls verfügbar
        if (function_exists('esc_js')) {
            return \esc_js($text);
        }
        
        // Einfache Fallback-Implementierung - unsicher, aber besser als nichts
        return addslashes($text);
    }
    
    /**
     * Sichere Version von sanitize_text_field, die NULL-Werte abfängt.
     *
     * @param mixed $text Der Text, der bereinigt werden soll
     * @return string Der bereinigte Text
     */
    public static function sanitize_text_field($text): string {
        return StringHelper::safe_sanitize_text($text);
    }
    
    /**
     * Sichere Version von wp_kses_post
     *
     * @param mixed $content Der Inhalt, der bereinigt werden soll
     * @return string Der bereinigte Inhalt
     */
    public static function wp_kses_post($content): string {
        // Prüfe auf null-Wert
        if ($content === null) {
            return '';
        }
        
        // Typumwandlung zu String
        if (!is_string($content)) {
            $content = (string)$content;
        }
        
        // WordPress wp_kses_post verwenden, falls verfügbar
        if (function_exists('wp_kses_post')) {
            return \wp_kses_post($content);
        }
        
        // Einfache Fallback-Implementierung - unsicher, aber besser als nichts
        return strip_tags($content, '<p><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><img><span><div>');
    }
    
    /**
     * Sichere Methode zur String-Konvertierung
     *
     * @param mixed $value Der zu konvertierende Wert
     * @param string $default Der Standardwert, falls $value null ist
     * @return string Der konvertierte String
     */
    public static function to_string($value, string $default = ''): string {
        if ($value === null) {
            return $default;
        }
        
        if (is_array($value) || is_object($value)) {
            // Bei Arrays oder Objekten sicher konvertieren
            try {
                if (method_exists($value, '__toString')) {
                    return (string)$value;
                } elseif (is_array($value)) {
                    return json_encode($value) ?: $default;
                } else {
                    return json_encode((array)$value) ?: $default;
                }
            } catch (\Throwable $e) {
                return $default;
            }
        }
        
        return (string)$value;
    }
}
