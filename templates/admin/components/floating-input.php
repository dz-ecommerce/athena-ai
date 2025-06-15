<?php
/**
 * Floating Input Component
 * 
 * @param array $args {
 *     @type string $name         Required. The name attribute for the input
 *     @type string $id           Required. The id attribute for the input  
 *     @type string $label        Required. The label text (will be translated)
 *     @type string $value        Optional. The current value
 *     @type string $type         Optional. Input type. Default 'text'
 *     @type string $placeholder  Optional. Placeholder text
 *     @type string $help_text    Optional. Help text below the input
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
    'type' => 'text',
    'placeholder' => '',
    'help_text' => '',
    'classes' => [],
    'attributes' => []
];

$args = wp_parse_args($args ?? [], $defaults);

// Validierung der erforderlichen Parameter
if (empty($args['name']) || empty($args['id']) || empty($args['label'])) {
    return;
}

// CSS-Klassen zusammenstellen
$base_classes = [
    'focus:ring-blue-500',
    'focus:border-blue-500', 
    'block',
    'w-full',
    'shadow-sm',
    'sm:text-sm',
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

// Placeholder hinzufügen wenn vorhanden
if (!empty($args['placeholder'])) {
    $attributes[] = 'placeholder="' . esc_attr__($args['placeholder'], 'athena-ai') . '"';
}

$attributes_string = implode(' ', $attributes);
?>

<div class="relative">
    <div class="form-group">
        <label class="floating-label" for="<?php echo esc_attr($args['id']); ?>">
            <?php echo esc_html__($args['label'], 'athena-ai'); ?>
        </label>
        <input 
            type="<?php echo esc_attr($args['type']); ?>"
            name="<?php echo esc_attr($args['name']); ?>" 
            id="<?php echo esc_attr($args['id']); ?>" 
            value="<?php echo esc_attr($args['value']); ?>"
            class="<?php echo esc_attr($class_string); ?>"
            <?php echo $attributes_string; ?>
        >
        <?php if (!empty($args['help_text'])): ?>
            <p class="mt-1 text-sm text-gray-500">
                <?php echo esc_html__($args['help_text'], 'athena-ai'); ?>
            </p>
        <?php endif; ?>
    </div>
</div> 