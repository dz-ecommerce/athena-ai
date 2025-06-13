<?php
/**
 * Field Sanitizer
 * 
 * Handles sanitization and validation of field data
 */

declare(strict_types=1);

namespace AthenaAI\Admin\Core;

if (!defined('ABSPATH')) {
    exit();
}

class FieldSanitizer {
    
    /**
     * Sanitize data based on field configuration
     */
    public static function sanitize(array $data, array $field_config): array {
        $sanitized = [];
        
        foreach ($field_config as $field => $type) {
            if (!isset($data[$field])) continue;
            
            $sanitized[$field] = self::sanitizeByType($data[$field], $type);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize single value by type
     */
    public static function sanitizeByType($value, string $type) {
        return match($type) {
            'text', 'email', 'url' => sanitize_text_field($value),
            'textarea' => sanitize_textarea_field($value),
            'html' => wp_kses_post($value),
            'array' => is_array($value) ? array_map('sanitize_text_field', $value) : [],
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            default => sanitize_text_field($value)
        };
    }
    
    /**
     * Validate data based on field configuration
     */
    public static function validate(array $data, array $validation_rules): array {
        $errors = [];
        
        foreach ($validation_rules as $field => $rules) {
            $value = $data[$field] ?? '';
            
            foreach ($rules as $rule => $param) {
                $error = self::validateRule($value, $rule, $param, $field);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate single rule
     */
    private static function validateRule($value, string $rule, $param, string $field): ?string {
        return match($rule) {
            'required' => empty($value) && $param ? "Field {$field} is required" : null,
            'min_length' => strlen($value) < $param ? "Field {$field} must be at least {$param} characters" : null,
            'max_length' => strlen($value) > $param ? "Field {$field} must not exceed {$param} characters" : null,
            'email' => !is_email($value) && $param ? "Field {$field} must be a valid email" : null,
            'url' => !filter_var($value, FILTER_VALIDATE_URL) && $param ? "Field {$field} must be a valid URL" : null,
            default => null
        };
    }
} 