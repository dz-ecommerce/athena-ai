<?php
/**
 * Floating Select Component
 * 
 * @param array $args {
 *     @type string $name         Required. The name attribute for the select
 *     @type string $id           Required. The id attribute for the select  
 *     @type string $label        Required. The label text (will be translated)
 *     @type string $value        Optional. The current selected value
 *     @type array  $options      Required. Array of options [value => label] or [group_name => [value => label]]
 *     @type string $placeholder  Optional. Placeholder option text
 *     @type string $help_text    Optional. Help text below the select
 *     @type array  $classes      Optional. Additional CSS classes
 *     @type array  $attributes   Optional. Additional HTML attributes
 * }
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Standardwerte setzen
$defaults = [
    'name' => '',
    'id' => '',
    'label' => '',
    'value' => '',
    'options' => [],
    'placeholder' => '',
    'help_text' => '',
    'classes' => [],
    'attributes' => []
];

$args = wp_parse_args($args ?? [], $defaults);

// Validierung der erforderlichen Parameter
if (empty($args['name']) || empty($args['id']) || empty($args['label']) || empty($args['options'])) {
    return;
}

// CSS-Klassen zusammenstellen
$base_classes = [
    'focus:ring-blue-500',
    'focus:border-blue-500', 
    'block',
    'w-full',
    'border-gray-300',
    'rounded-md',
    'py-2.5',
    'px-4'
];

$css_classes = array_merge($base_classes, $args['classes']);
$class_string = implode(' ', $css_classes);

// Zusätzliche Attribute zusammenstellen
$attributes = [];
foreach ($args['attributes'] as $key => $value) {
    $attributes[] = esc_attr($key) . '="' . esc_attr($value) . '"';
}

// data-filled Attribut hinzufügen wenn Wert vorhanden
if (!empty($args['value'])) {
    $attributes[] = 'data-filled';
}

$attributes_string = implode(' ', $attributes);

/**
 * Hilfsfunktion zum Rendern von Optionen
 */
function render_select_options($options, $selected_value) {
    foreach ($options as $value => $label) {
        if (is_array($label)) {
            // Optgroup
            echo '<optgroup label="' . esc_attr__($value, 'athena-ai') . '">';
            render_select_options($label, $selected_value);
            echo '</optgroup>';
        } else {
            // Normale Option
            $selected = $selected_value === $value ? ' selected' : '';
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
    }
}
?>

<div class="relative">
    <div class="form-group">
        <label class="floating-label" for="<?php echo esc_attr($args['id']); ?>">
            <?php echo esc_html__($args['label'], 'athena-ai'); ?>
        </label>
        <select 
            name="<?php echo esc_attr($args['name']); ?>" 
            id="<?php echo esc_attr($args['id']); ?>" 
            class="<?php echo esc_attr($class_string); ?>"
            <?php echo $attributes_string; ?>
        >
            <?php if (!empty($args['placeholder'])): ?>
                <option value="" disabled<?php selected(empty($args['value'])); ?>>
                    <?php echo esc_html__($args['placeholder'], 'athena-ai'); ?>
                </option>
            <?php endif; ?>
            <?php render_select_options($args['options'], $args['value']); ?>
        </select>
        <?php if (!empty($args['help_text'])): ?>
            <p class="mt-1 text-sm text-gray-500">
                <?php echo esc_html__($args['help_text'], 'athena-ai'); ?>
            </p>
        <?php endif; ?>
    </div>
</div> 