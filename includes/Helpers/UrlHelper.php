<?php

namespace AthenaAI\Helpers;

/**
 * UrlHelper
 * 
 * Helper-Klasse für URL-bezogene Operationen mit verbesserter NULL-Wert-Behandlung.
 * 
 * @package AthenaAI\Helpers
 */
class UrlHelper {
    
    /**
     * Sichere Version von esc_url, die mit null-Werten umgehen kann
     * 
     * @param mixed $url Die zu bereinigende URL
     * @param array|null $protocols Optional. Array mit erlaubten Protokollen
     * @param string $_context Optional. Kontext der URL-Bereinigung
     * @return string Die bereinigte URL oder leerer String bei ungültiger URL
     */
    public static function safe_esc_url($url, $protocols = null, $_context = 'display'): string {
        // Null-Wert-Behandlung
        if ($url === null) {
            return '';
        }
        
        // Typumwandlung zu String, falls ein anderer Typ übergeben wurde
        if (!is_string($url)) {
            $url = (string)$url;
        }
        
        // Prüfen, ob die URL leer ist
        if (trim($url) === '') {
            return '';
        }
        
        // WordPress esc_url verwenden, falls verfügbar
        if (function_exists('\\esc_url')) {
            return \esc_url($url, $protocols, $_context);
        }
        
        // Fallback-Implementierung
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
    
    /**
     * Sichere Version von esc_url_raw, die mit null-Werten umgehen kann
     * 
     * @param mixed $url Die zu bereinigende URL
     * @param array|null $protocols Optional. Array mit erlaubten Protokollen
     * @return string Die bereinigte URL oder leerer String bei ungültiger URL
     */
    public static function safe_esc_url_raw($url, $protocols = null): string {
        // Null-Wert-Behandlung
        if ($url === null) {
            return '';
        }
        
        // Typumwandlung zu String, falls ein anderer Typ übergeben wurde
        if (!is_string($url)) {
            $url = (string)$url;
        }
        
        // Prüfen, ob die URL leer ist
        if (trim($url) === '') {
            return '';
        }
        
        // WordPress esc_url_raw verwenden, falls verfügbar
        if (function_exists('\\esc_url_raw')) {
            return \esc_url_raw($url, $protocols);
        }
        
        // Fallback-Implementierung
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
    
    /**
     * Prüft, ob eine URL gültig ist
     * 
     * @param mixed $url Die zu prüfende URL
     * @return bool True, wenn die URL gültig ist, sonst false
     */
    public static function is_valid_url($url): bool {
        // Null-Wert-Behandlung
        if ($url === null) {
            return false;
        }
        
        // Typumwandlung zu String, falls ein anderer Typ übergeben wurde
        if (!is_string($url)) {
            $url = (string)$url;
        }
        
        // Prüfen, ob die URL leer ist
        if (trim($url) === '') {
            return false;
        }
        
        // URL validieren
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
