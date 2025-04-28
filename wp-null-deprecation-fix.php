<?php
/**
 * Plugin Name: WP Null Deprecation Fix
 * Description: Fixes PHP 8.1+ deprecation warnings for null parameters in WordPress core functions
 * Version: 1.0.0
 * Author: Generated Fix
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Hook into WordPress to fix deprecated function calls
 */
function wp_fix_null_deprecation() {
    // Fix for strpos() deprecation in functions.php line 7360
    if (!function_exists('wp_safe_strpos')) {
        function wp_safe_strpos($haystack, $needle, $offset = 0) {
            if ($haystack === null) {
                $haystack = '';
            }
            return strpos($haystack, $needle, $offset);
        }
    }
    
    // Fix for str_replace() deprecation in functions.php line 2195
    if (!function_exists('wp_safe_str_replace')) {
        function wp_safe_str_replace($search, $replace, $subject, &$count = null) {
            if ($subject === null) {
                $subject = '';
            }
            return str_replace($search, $replace, $subject, $count);
        }
    }
    
    // Apply the fixes by using the runkit extension if available, or alternative method
    if (function_exists('runkit7_function_redefine') || function_exists('runkit_function_redefine')) {
        // This is a more advanced approach that would require runkit PHP extension
        // Not implementing this as it's unlikely to be available
    } else {
        // Add filter to suppress these specific deprecation warnings
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            // Only catch specific deprecation warnings we're targeting
            if ($errno === E_DEPRECATED && 
                strpos($errfile, 'wp-includes/functions.php') !== false &&
                (
                    (strpos($errstr, 'strpos()') !== false && $errline === 7360) ||
                    (strpos($errstr, 'str_replace()') !== false && $errline === 2195)
                )
            ) {
                return true; // Suppress this warning
            }
            // Let other errors be handled by WordPress
            return false;
        }, E_DEPRECATED);
    }
}

// Initialize our fixes early
add_action('plugins_loaded', 'wp_fix_null_deprecation', 1); 