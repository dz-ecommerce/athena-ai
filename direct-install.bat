@echo off
echo Erstelle Vendor-Verzeichnis...
mkdir vendor\andreskrey
mkdir vendor\andreskrey\readability
mkdir vendor\autoload

echo Lade Readability.php herunter...
powershell -Command "Invoke-WebRequest -Uri 'https://github.com/andreskrey/readability.php/archive/refs/heads/master.zip' -OutFile 'readability.zip'"

echo Entpacke Readability.php...
powershell -Command "Expand-Archive -Path 'readability.zip' -DestinationPath 'temp' -Force"
xcopy temp\readability.php-master\* vendor\andreskrey\readability\ /E /I /Y

echo Erstelle simple Autoload-Datei...
echo ^<?php > vendor\autoload.php
echo spl_autoload_register(function($class) { >> vendor\autoload.php
echo     $prefix = 'andreskrey\\'; >> vendor\autoload.php
echo     $base_dir = __DIR__ . '/andreskrey/readability/src/'; >> vendor\autoload.php
echo     $len = strlen($prefix); >> vendor\autoload.php
echo     if (strncmp($prefix, $class, $len) !== 0) { return; } >> vendor\autoload.php
echo     $relative_class = substr($class, $len); >> vendor\autoload.php
echo     $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php'; >> vendor\autoload.php
echo     if (file_exists($file)) { require $file; } >> vendor\autoload.php
echo }); >> vendor\autoload.php

echo Bereinige temporäre Dateien...
rmdir /S /Q temp
del readability.zip

echo Installation abgeschlossen! 