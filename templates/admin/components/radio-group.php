<?php
/**
 * Radio Group Component
 * 
 * @param array $args {
 *     @type string $name         Required. The name attribute for the radio group
 *     @type string $label        Required. The group label text (will be translated)
 *     @type string $value        Optional. The current selected value
 *     @type array  $options      Required. Array of options [value => label]
 *     @type string $layout       Optional. Layout style: 'horizontal' or 'vertical'. Default 'horizontal'
 *     @type string $help_text    Optional. Help text below the group
 *     @type array  $classes      Optional. Additional CSS classes for the container
 *     @type array  $attributes   Optional. Additional HTML attributes for radio inputs
 * }
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Standardwerte setzen
$defaults = [
    'name' => '',
    'label' => '',
    'value' => '',
    'options' => [],
    'layout' => 'horizontal',
    'help_text' => '',
    'classes' => [],
    'attributes' => []
];

$args = wp_parse_args($args ?? [], $defaults);

// Validierung der erforderlichen Parameter
if (empty($args['name']) || empty($args['label']) || empty($args['options'])) {
    return;
}

// Layout-spezifische CSS-Klassen
$layout_classes = $args['layout'] === 'vertical' ? 'space-y-2' : 'flex space-x-6';

// Container CSS-Klassen zusammenstellen
$container_classes = array_merge([$layout_classes], $args['classes']);
$container_class_string = implode(' ', $container_classes);

// Zusätzliche Attribute für Radio-Inputs zusammenstellen
$attributes = [];
foreach ($args['attributes'] as $key => $value) {
    $attributes[] = esc_attr($key) . '="' . esc_attr($value) . '"';
}
$attributes_string = implode(' ', $attributes);
?>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
        <?php echo esc_html__($args['label'], 'athena-ai'); ?>
    </label>
    <div class="<?php echo esc_attr($container_class_string); ?>">
        <?php foreach ($args['options'] as $option_value => $option_label): ?>
            <?php 
            $radio_id = $args['name'] . '_' . $option_value;
            $checked = checked($args['value'], $option_value, false);
            ?>
            <div class="flex items-center">
                <input 
                    type="radio" 
                    name="<?php echo esc_attr($args['name']); ?>" 
                    id="<?php echo esc_attr($radio_id); ?>" 
                    value="<?php echo esc_attr($option_value); ?>" 
                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300"
                    <?php echo $checked; ?>
                    <?php echo $attributes_string; ?>
                >
                <label for="<?php echo esc_attr($radio_id); ?>" class="ml-2 block text-sm text-gray-700">
                    <?php echo esc_html($option_label); ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($args['help_text'])): ?>
        <p class="mt-1 text-sm text-gray-500">
            <?php echo esc_html__($args['help_text'], 'athena-ai'); ?>
        </p>
    <?php endif; ?>
</div> 