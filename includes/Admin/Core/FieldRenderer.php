<?php
/**
 * Field Renderer
 * 
 * Handles rendering of form fields and UI elements
 */

declare(strict_types=1);

namespace AthenaAI\Admin\Core;

if (!defined('ABSPATH')) {
    exit();
}

class FieldRenderer {
    
    /**
     * Render a select field with options
     */
    public static function select(string $name, array $options, $selected = '', array $attributes = []): string {
        $attr_string = self::buildAttributes($attributes);
        $html = "<select name=\"{$name}\" {$attr_string}>";
        
        foreach ($options as $group_name => $group_options) {
            if (is_array($group_options)) {
                $html .= "<optgroup label=\"" . esc_attr($group_name) . "\">";
                foreach ($group_options as $value => $label) {
                    $selected_attr = selected($selected, $value, false);
                    $html .= "<option value=\"" . esc_attr($value) . "\" {$selected_attr}>" . esc_html($label) . "</option>";
                }
                $html .= "</optgroup>";
            } else {
                $selected_attr = selected($selected, $group_name, false);
                $html .= "<option value=\"" . esc_attr($group_name) . "\" {$selected_attr}>" . esc_html($group_options) . "</option>";
            }
        }
        
        $html .= "</select>";
        return $html;
    }
    
    /**
     * Render radio buttons
     */
    public static function radio(string $name, array $options, $selected = '', array $attributes = []): string {
        $html = '';
        
        foreach ($options as $value => $label) {
            $checked = checked($selected, $value, false);
            $id = sanitize_title($name . '_' . $value);
            
            $html .= '<div class="flex items-center">';
            $html .= "<input type=\"radio\" name=\"{$name}\" id=\"{$id}\" value=\"" . esc_attr($value) . "\" {$checked} class=\"focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300\">";
            $html .= "<label for=\"{$id}\" class=\"ml-2 block text-sm text-gray-700\">" . esc_html($label) . "</label>";
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Render checkboxes
     */
    public static function checkbox(string $name, array $options, array $selected = [], array $attributes = []): string {
        $html = '';
        
        foreach ($options as $value => $label) {
            $checked = checked(in_array($value, $selected), true, false);
            $id = sanitize_title($name . '_' . $value);
            
            $html .= '<div class="px-2 py-1.5 flex items-center">';
            $html .= '<div class="relative flex items-start">';
            $html .= '<div class="flex items-center h-5">';
            $html .= "<input type=\"checkbox\" name=\"{$name}[]\" id=\"{$id}\" value=\"" . esc_attr($value) . "\" {$checked} class=\"focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded\">";
            $html .= '</div>';
            $html .= '<div class="ml-2.5 text-sm">';
            $html .= "<label for=\"{$id}\" class=\"font-medium text-gray-700\">" . esc_html($label) . "</label>";
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Build HTML attributes string
     */
    private static function buildAttributes(array $attributes): string {
        $attr_parts = [];
        
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $attr_parts[] = esc_attr($key);
            } elseif ($value !== false && $value !== null) {
                $attr_parts[] = esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }
        
        return implode(' ', $attr_parts);
    }
} 