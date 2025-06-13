<?php
/**
 * Field Storage Handler
 * 
 * Handles storage and retrieval of field data
 */

declare(strict_types=1);

namespace AthenaAI\Admin\Core;

if (!defined('ABSPATH')) {
    exit();
}

class FieldStorage {
    
    /**
     * @var string Base option prefix
     */
    private string $option_prefix;
    
    /**
     * Constructor
     */
    public function __construct(string $option_prefix = 'athena_ai_') {
        $this->option_prefix = $option_prefix;
    }
    
    /**
     * Save field data
     */
    public function save(string $option_name, array $data): bool {
        return update_option($this->option_prefix . $option_name, $data);
    }
    
    /**
     * Get field data
     */
    public function get(string $option_name, array $default = []): array {
        return get_option($this->option_prefix . $option_name, $default);
    }
    
    /**
     * Get specific field value
     */
    public function getField(string $option_name, string $field, $default = '') {
        $data = $this->get($option_name);
        return $data[$field] ?? $default;
    }
    
    /**
     * Delete field data
     */
    public function delete(string $option_name): bool {
        return delete_option($this->option_prefix . $option_name);
    }
} 