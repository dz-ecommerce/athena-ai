<?php
/**
 * Checkbox Group Component
 * 
 * @param array $args {
 *     @type string $name         Required. The name attribute for the checkbox group (will have [] appended)
 *     @type string $label        Required. The group label text (will be translated)
 *     @type array  $values       Optional. Array of currently selected values
 *     @type array  $options      Required. Array of options [value => label]
 *     @type string $layout       Optional. Layout style: 'horizontal', 'vertical', or 'grid'. Default 'horizontal'
 *     @type string $help_text    Optional. Help text below the group
 *     @type array  $classes      Optional. Additional CSS classes for the container
 *     @type array  $attributes   Optional. Additional HTML attributes for checkbox inputs
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
    'values' => [],
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

// Sicherstellen, dass values ein Array ist
$selected_values = (array) $args['values'];

// Layout-spezifische CSS-Klassen
$layout_classes = '';
switch ($args['layout']) {
    case 'vertical':
        $layout_classes = 'space-y-2';
        break;
    case 'grid':
        $layout_classes = 'flex flex-wrap -mx-2';
        break;
    case 'horizontal':
    default:
        $layout_classes = 'flex flex-wrap -mx-2';
        break;
}

// Container CSS-Klassen zusammenstellen
$container_classes = array_merge([$layout_classes], $args['classes']);
$container_class_string = implode(' ', $container_classes);

// Zusätzliche Attribute für Checkbox-Inputs zusammenstellen
$attributes = [];
foreach ($args['attributes'] as $key => $value) {
    $attributes[] = esc_attr($key) . '="' . esc_attr($value) . '"';
}
$attributes_string = implode(' ', $attributes);

// Item-spezifische CSS-Klassen basierend auf Layout
$item_classes = $args['layout'] === 'vertical' ? '' : 'px-2 py-1.5 flex items-center';
?>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
        <?php echo esc_html__($args['label'], 'athena-ai'); ?>
    </label>
    <div class="<?php echo esc_attr($container_class_string); ?>">
        <?php foreach ($args['options'] as $option_value => $option_label): ?>
            <?php 
            $checkbox_id = str_replace(['[', ']'], '', $args['name']) . '_' . $option_value;
            $is_checked = in_array($option_value, $selected_values);
            ?>
            <div class="<?php echo esc_attr($item_classes); ?>">
                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input 
                            type="checkbox" 
                            name="<?php echo esc_attr($args['name']); ?>[]" 
                            id="<?php echo esc_attr($checkbox_id); ?>" 
                            value="<?php echo esc_attr($option_value); ?>" 
                            class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded"
                            <?php checked($is_checked); ?>
                            <?php echo $attributes_string; ?>
                        >
                    </div>
                    <div class="ml-2.5 text-sm">
                        <label for="<?php echo esc_attr($checkbox_id); ?>" class="font-medium text-gray-700">
                            <?php echo esc_html($option_label); ?>
                        </label>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($args['help_text'])): ?>
        <p class="mt-1 text-sm text-gray-500">
            <?php echo esc_html__($args['help_text'], 'athena-ai'); ?>
        </p>
    <?php endif; ?>
</div> 