@echo off
REM Definiere den Pfad zu PHP 8.3
SET PHP_PATH=%~dp0php83\php.exe

REM Verwende diese PHP-Version mit Composer
%PHP_PATH% %COMPOSER_HOME%\composer.phar %* 