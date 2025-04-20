<?php
/**
 * Autoloader für Athena AI Plugin.
 *
 * @package AthenaAI
 */

namespace AthenaAI;

/**
 * PSR-4 kompatible Autoloader-Implementation für Athena AI.
 */
class Autoloader {
    /**
     * Plugin-Namespace-Präfix.
     *
     * @var string
     */
    private $prefix = 'AthenaAI\\';

    /**
     * Basis-Verzeichnis für die Klassen.
     *
     * @var string
     */
    private $base_dir;

    /**
     * Konstruktor.
     */
    public function __construct() {
        $this->base_dir = dirname(__FILE__) . '/';
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Autoloader-Methode.
     *
     * @param string $class Der vollqualifizierte Klassenname.
     * @return void
     */
    public function autoload(string $class): void {
        // Prüfen, ob der Klassenname mit unserem Namespace-Präfix beginnt
        $len = strlen($this->prefix);
        if (strncmp($this->prefix, $class, $len) !== 0) {
            return;
        }

        // Relativen Klassennamen bekommen (ohne Namespace-Präfix)
        $relative_class = substr($class, $len);
        
        // Relativen Klassennamen in einen Dateipfad umwandeln
        $file = $this->base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // Wenn die Datei existiert, einbinden
        if (file_exists($file)) {
            require $file;
        }
    }
}
