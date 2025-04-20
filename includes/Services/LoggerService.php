<?php
/**
 * Logger Service
 * 
 * Zentralisierter Logger für alle Arten von Logging im Plugin.
 *
 * @package AthenaAI\Services
 */

declare(strict_types=1);

namespace AthenaAI\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zentraler Logger für das Athena AI Plugin.
 */
class LoggerService {
    /**
     * Singleton-Instanz.
     *
     * @var self|null
     */
    private static ?self $instance = null;
    
    /**
     * Ob Verbose-Logging in der Konsole aktiviert ist.
     *
     * @var bool
     */
    private bool $verbose_console = false;
    
    /**
     * Ob der Debug-Modus aktiviert ist.
     *
     * @var bool
     */
    private bool $debug_mode = false;
    
    /**
     * Präfix für Log-Meldungen.
     *
     * @var string
     */
    private string $prefix = 'Athena AI';
    
    /**
     * Privater Konstruktor für Singleton-Pattern.
     */
    private function __construct() {
        $this->debug_mode = get_option('athena_ai_enable_debug_mode', false) || 
                           (defined('WP_DEBUG') && WP_DEBUG);
    }
    
    /**
     * Singleton-Instanz abrufen.
     *
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Setzt den Komponentenpräfix für Logs.
     *
     * @param string $component Komponentenname (z.B. "Feed", "Admin", etc.)
     * @return self
     */
    public function setComponent(string $component): self {
        $this->prefix = 'Athena AI' . ($component ? ' ' . $component : '');
        return $this;
    }
    
    /**
     * Verbose-Modus für Konsole aktivieren/deaktivieren.
     *
     * @param bool $verbose Ob verbose Logging aktiviert werden soll.
     * @return self
     */
    public function setVerboseMode(bool $verbose): self {
        $this->verbose_console = $verbose;
        return $this;
    }
    
    /**
     * Debug-Modus aktivieren/deaktivieren.
     *
     * @param bool $debug Ob Debug-Modus aktiviert werden soll.
     * @return self
     */
    public function setDebugMode(bool $debug): self {
        $this->debug_mode = $debug;
        return $this;
    }
    
    /**
     * Nachricht in die Konsole loggen, wenn Verbose-Modus aktiviert ist.
     *
     * @param string $message Die zu loggende Nachricht.
     * @param string $type    Log-Typ: 'log', 'info', 'warn', 'error', 'group', 'groupEnd'.
     * @return void
     */
    public function console(string $message, string $type = 'log'): void {
        if (!$this->verbose_console) {
            return;
        }
        
        $allowed_types = ['log', 'info', 'warn', 'error', 'group', 'groupEnd', 'groupCollapsed'];
        $type = in_array($type, $allowed_types) ? $type : 'log';
        
        // Bei groupEnd brauchen wir keinen Präfix und keine Nachricht
        if ($type === 'groupEnd') {
            echo '<script>console.groupEnd();</script>';
            return;
        }
        
        // Nachricht mit Präfix formatieren (außer für leere groupEnd-Nachrichten)
        $formatted = empty($message) ? '' : "{$this->prefix}: {$message}";
        
        echo '<script>console.' . $type . '("' . esc_js($formatted) . '");</script>';
    }
    
    /**
     * Fehler in die PHP-Fehlerlog schreiben, wenn Debug-Modus aktiviert ist.
     *
     * @param string $message Fehlermeldung.
     * @param string $code    Optional. Fehlercode für einfachere Identifikation.
     * @return void
     */
    public function error(string $message, string $code = ''): void {
        if (!$this->debug_mode) {
            return;
        }
        
        $error_message = $this->prefix;
        
        if ($code) {
            $error_message .= " Error ({$code})";
        }
        
        $error_message .= ": {$message}";
        
        error_log($error_message);
        
        // Auch in die Konsole loggen, wenn Verbose-Modus aktiviert
        if ($this->verbose_console) {
            $this->console($message, 'error');
        }
    }
    
    /**
     * Info-Level Nachricht loggen (Konsole und/oder Fehlerlog).
     *
     * @param string $message Die Nachricht.
     * @param bool   $console_only Ob nur in die Konsole geloggt werden soll.
     * @return void
     */
    public function info(string $message, bool $console_only = true): void {
        // Immer in die Konsole loggen, wenn Verbose-Modus aktiviert
        if ($this->verbose_console) {
            $this->console($message, 'info');
        }
        
        // In den Fehlerlog schreiben, wenn Debug-Modus aktiviert und nicht console_only
        if ($this->debug_mode && !$console_only) {
            $info_message = "{$this->prefix} Info: {$message}";
            error_log($info_message);
        }
    }
    
    /**
     * Warn-Level Nachricht loggen (Konsole und/oder Fehlerlog).
     *
     * @param string $message Die Nachricht.
     * @param bool   $console_only Ob nur in die Konsole geloggt werden soll.
     * @return void
     */
    public function warn(string $message, bool $console_only = true): void {
        // Immer in die Konsole loggen, wenn Verbose-Modus aktiviert
        if ($this->verbose_console) {
            $this->console($message, 'warn');
        }
        
        // In den Fehlerlog schreiben, wenn Debug-Modus aktiviert und nicht console_only
        if ($this->debug_mode && !$console_only) {
            $warn_message = "{$this->prefix} Warning: {$message}";
            error_log($warn_message);
        }
    }
    
    /**
     * Debug-Level Nachricht loggen (nur im Debug-Modus).
     *
     * @param string $message Die Nachricht.
     * @param bool   $console_only Ob nur in die Konsole geloggt werden soll.
     * @return void
     */
    public function debug(string $message, bool $console_only = true): void {
        if (!$this->debug_mode) {
            return;
        }
        
        // In die Konsole loggen, wenn Verbose-Modus aktiviert
        if ($this->verbose_console) {
            $this->console($message, 'log');
        }
        
        // In den Fehlerlog schreiben, wenn nicht console_only
        if (!$console_only) {
            $debug_message = "{$this->prefix} Debug: {$message}";
            error_log($debug_message);
        }
    }
    
    /**
     * Gruppierte Logs beginnen (nur Konsole).
     * 
     * @param string $title Titel der Gruppe.
     * @param bool   $collapsed Ob die Gruppe initial zusammengeklappt sein soll.
     * @return void
     */
    public function group(string $title, bool $collapsed = false): void {
        if (!$this->verbose_console) {
            return;
        }
        
        $type = $collapsed ? 'groupCollapsed' : 'group';
        $this->console($title, $type);
    }
    
    /**
     * Gruppierte Logs beenden (nur Konsole).
     *
     * @return void
     */
    public function groupEnd(): void {
        if (!$this->verbose_console) {
            return;
        }
        
        $this->console('', 'groupEnd');
    }
}
