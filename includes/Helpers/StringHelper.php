<?php

namespace AthenaAI\Helpers;

/**
 * StringHelper
 *
 * Helper-Klasse für String-bezogene Operationen mit verbesserter NULL-Wert-Behandlung.
 * Beseitigt Deprecated-Warnungen durch Vermeidung von null-Werten bei String-Funktionen in PHP 8+.
 *
 * @package AthenaAI\Helpers
 */
class StringHelper {
    /**
     * Sichere Version von strpos, die mit null-Werten umgehen kann
     *
     * @param mixed $haystack String, in dem gesucht wird oder null
     * @param mixed $needle String, nach dem gesucht wird oder null
     * @param int $offset Optional. Position, an der die Suche beginnt
     * @return int|false Position des ersten Vorkommens oder false, wenn nicht gefunden
     */
    public static function safe_strpos($haystack, $needle, int $offset = 0): int|false {
        // Null-Wert-Behandlung
        if ($haystack === null) {
            $haystack = '';
        }

        if ($needle === null) {
            $needle = '';
        }

        // Typumwandlung zu String, falls ein anderer Typ übergeben wurde
        if (!is_string($haystack)) {
            $haystack = (string) $haystack;
        }

        if (!is_string($needle)) {
            $needle = (string) $needle;
        }

        // Leere Nadel führt sofort zu Treffer an Position 0
        if ($needle === '') {
            return 0;
        }

        // Standard strpos verwenden mit nun sicheren Werten
        return strpos($haystack, $needle, $offset);
    }

    /**
     * Sichere Version von add_query_arg, die mit null-Werten umgehen kann
     *
     * @param mixed $key Entweder ein Schlüssel oder ein Array mit Schlüssel-Wert-Paaren
     * @param mixed $value Der Wert zum Hinzufügen, oder URL wenn $key ein Array ist
     * @param mixed $url Die URL, an die Query-Parameter angehängt werden sollen
     * @return string Die modifizierte URL
     */
    public static function safe_add_query_arg($key, $value = null, $url = null) {
        // Wenn alle Parameter null sind
        if ($key === null && $value === null && $url === null) {
            return '';
        }

        // Spezialfall, wenn $key ein Array ist und $url === null
        if (is_array($key) && $url === null) {
            // $value ist hier die URL
            if (function_exists('add_query_arg')) {
                return \add_query_arg($key, $value === null ? '' : $value);
            }
            return $value === null ? '' : $value; // Fallback
        }

        // Null-Werte für Schlüssel und Wert in leere Strings umwandeln
        $key = $key === null ? '' : $key;
        $value = $value === null ? '' : $value;
        $url = $url === null ? '' : $url;

        // Standard add_query_arg verwenden mit nun sicheren Werten
        if (function_exists('add_query_arg')) {
            return \add_query_arg($key, $value, $url);
        }

        // Einfacher Fallback für den Fall, dass add_query_arg nicht verfügbar ist
        $url = parse_url($url);
        $query = isset($url['query']) ? $url['query'] : '';
        parse_str($query, $query_args);
        $query_args[$key] = $value;
        $url['query'] = http_build_query($query_args);
        return self::build_url($url);
    }

    /**
     * Hilfsmethode zum Aufbau einer URL aus parse_url Komponenten
     *
     * @param array $url Die URL-Komponenten aus parse_url
     * @return string Die aufgebaute URL
     */
    private static function build_url(array $url): string {
        $scheme = isset($url['scheme']) ? $url['scheme'] . '://' : '';
        $host = isset($url['host']) ? $url['host'] : '';
        $port = isset($url['port']) ? ':' . $url['port'] : '';
        $user = isset($url['user']) ? $url['user'] : '';
        $pass = isset($url['pass']) ? ':' . $url['pass'] : '';
        $pass = $user || $pass ? "$pass@" : '';
        $path = isset($url['path']) ? $url['path'] : '';
        $query = isset($url['query']) ? '?' . $url['query'] : '';
        $fragment = isset($url['fragment']) ? '#' . $url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Sichere Version von str_replace, die mit null-Werten umgehen kann
     *
     * @param mixed $search String oder Array zu suchender Werte
     * @param mixed $replace String oder Array mit Ersetzungswerten
     * @param mixed $subject String oder Array, in dem ersetzt werden soll
     * @param int &$count Optional. Anzahl der Ersetzungen
     * @return string|array Ersetzte Zeichenkette oder Array
     */
    public static function safe_str_replace(
        $search,
        $replace,
        $subject,
        &$count = null
    ): string|array {
        // Null-Wert-Behandlung
        if ($search === null) {
            $search = '';
        }

        if ($replace === null) {
            $replace = '';
        }

        if ($subject === null) {
            return '';
        }

        // Typumwandlung bei nicht-Array $search und $replace zu String
        if (!is_array($search) && !is_string($search)) {
            $search = (string) $search;
        }

        if (!is_array($replace) && !is_string($replace)) {
            $replace = (string) $replace;
        }

        // Bei Array-subject jeden Wert prüfen und ggf. konvertieren
        if (is_array($subject)) {
            foreach ($subject as $key => $value) {
                if ($value === null) {
                    $subject[$key] = '';
                } elseif (!is_string($value) && !is_array($value)) {
                    $subject[$key] = (string) $value;
                }
            }
        } elseif (!is_string($subject)) {
            // Bei nicht-String subject zu String konvertieren
            $subject = (string) $subject;
        }

        // Standard str_replace mit nun sicheren Werten verwenden
        return str_replace($search, $replace, $subject, $count);
    }

    /**
     * Sichere Version von esc_attr, die mit null-Werten umgehen kann
     *
     * @param mixed $text Der zu bereinigende Text
     * @return string Der bereinigte Text
     */
    public static function safe_esc_attr($text): string {
        // Null-Wert-Behandlung
        if ($text === null) {
            return '';
        }

        // Typumwandlung zu String, falls ein anderer Typ übergeben wurde
        if (!is_string($text)) {
            $text = (string) $text;
        }

        // WordPress esc_attr verwenden, falls verfügbar
        if (function_exists('esc_attr')) {
            return \esc_attr($text);
        }

        // Fallback-Implementierung
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sichere Version von esc_html, die mit null-Werten umgehen kann
     *
     * @param mixed $text Der zu bereinigende Text
     * @return string Der bereinigte Text
     */
    public static function safe_esc_html($text): string {
        // Null-Wert-Behandlung
        if ($text === null) {
            return '';
        }

        // Typumwandlung zu String, falls ein anderer Typ übergeben wurde
        if (!is_string($text)) {
            $text = (string) $text;
        }

        // WordPress esc_html verwenden, falls verfügbar
        if (function_exists('esc_html')) {
            return \esc_html($text);
        }

        // Fallback-Implementierung
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sichere Version von sanitize_text_field, die mit null-Werten umgehen kann
     *
     * @param mixed $text Der zu bereinigende Text
     * @return string Der bereinigte Text
     */
    public static function safe_sanitize_text($text): string {
        // Null-Wert-Behandlung
        if ($text === null) {
            return '';
        }

        // Typumwandlung zu String, falls ein anderer Typ übergeben wurde
        if (!is_string($text)) {
            $text = (string) $text;
        }

        // WordPress sanitize_text_field verwenden, falls verfügbar
        if (function_exists('sanitize_text_field')) {
            return \sanitize_text_field($text);
        }

        // Einfache Fallback-Implementierung
        $text = trim($text);
        $text = strip_tags($text);
        $text = preg_replace('/[\r\n\t ]+/', ' ', $text);
        return trim($text);
    }
}
